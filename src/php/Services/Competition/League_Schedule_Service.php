<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Competition;

use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\League_Factory;
use Racketmanager\Domain\Fixture\Fixture_Factory;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\Fixture_Service;
use Racketmanager\Services\Schedule_Round_Robin;
use Racketmanager\Util\Util;
use Racketmanager\Util\Util_Lookup;
use stdClass;

/**
 * Service responsible for scheduling matches in league competitions.
 */
class League_Schedule_Service {
	private ?Fixture_Service $fixture_service = null;
	private Schedule_Round_Robin $schedule_round_robin;
	private ?League_Repository_Interface $league_repository;

	public function __construct(
		?Fixture_Service $fixture_service = null,
		?Schedule_Round_Robin $schedule_round_robin = null,
		?League_Repository_Interface $league_repository = null,
		private ?Event_Query_Service $event_query_service = null,
		private League_Query_Service|\Closure|null $league_query_service = null
	) {
		$this->fixture_service      = $fixture_service;
		$this->schedule_round_robin = $schedule_round_robin ?? new Schedule_Round_Robin();
		$this->league_repository    = $league_repository;
	}

	public function set_fixture_service( Fixture_Service $fixture_service ): void {
		$this->fixture_service = $fixture_service;
	}

	public function get_fixture_service(): ?Fixture_Service {
		return $this->fixture_service;
	}

	/**
	 * Schedule matches for a league.
	 *
	 * @param League $league
	 * @return void
	 */
	public function schedule_league( League $league ): void {
		$season         = $league->get_season();
		$schedule_teams = $this->league_query()->get_league_teams( $league,
			array(
				'season'  => $season,
				'status'  => 'active',
				'orderby' => array(
					'group' => 'ASC',
					'title' => 'ASC',
				),
			)
		);

		if ( ! empty( $league->event->is_box ) ) {
			$num_teams = $league->num_teams;
			if ( 0 !== $league->num_teams % 2 ) {
				++$num_teams;
			}
			$num_rounds    = (string) ( $num_teams - 1 );
			$num_teams_max = $num_teams;
			$home_away     = false;
		} else {
			$num_rounds = (string) ( $league->current_season['num_match_days'] ?? 0 );
			$home_away  = ! empty( $league->event->current_season['home_away'] );
			if ( $home_away ) {
				$num_rounds = (string) ( (int) $num_rounds / 2 );
			}
			$num_teams_max = (int) $num_rounds + 1;
		}

		$refs = array();
		for ( $i = 1; $i <= $num_teams_max; $i++ ) {
			$refs[] = $i;
		}

		foreach ( $schedule_teams as $team ) {
			if ( ! empty( $team->group ) ) {
				$ref = array_search( intval( $team->group ), $refs, true );
				if ( false !== $ref ) {
					array_splice( $refs, $ref, 1 );
				}
			}
		}

		foreach ( $schedule_teams as $team ) {
			if ( empty( $team->group ) && ! empty( $refs ) ) {
				$group = $refs[0];
				Util::set_table_group( $group, $team->table_id );
				array_splice( $refs, 0, 1 );
			}
		}

		$schedule_teams = $this->league_query()->get_league_teams( $league,
			array(
				'season'      => $season,
				'status'      => 'active',
				'get_details' => true,
				'cache'       => false,
				'orderby'     => array(
					'group' => 'ASC',
					'title' => 'ASC',
				),
			)
		);

		if ( $schedule_teams ) {
			if ( $refs ) {
				foreach ( $refs as $ref ) {
					$team             = array(
						'id'     => -1,
						'title'  => __( 'Bye', 'racketmanager' ),
						'player' => array(),
						'group'  => $ref,
					);
					$schedule_teams[] = (object) $team;
				}
				usort( $schedule_teams, fn ( $a, $b ) => ( $a->group ?? 0 ) <=> ( $b->group ?? 0 ) );
			}
			$this->create_schedule( $league, $schedule_teams, (string) $num_rounds, $home_away );
		}
	}

	/**
	 * Create schedule for teams.
	 *
	 * @param League $league
	 * @param array $teams
	 * @param string $num_rounds
	 * @param bool $home_away
	 * @return void
	 */
	public function create_schedule( League $league, array $teams, string $num_rounds, bool $home_away ): void {
		$season      = $league->current_season['name'] ?? '';
		$match_dates = $league->current_season['match_dates'] ?? [];
		$num_teams   = count( $teams );
		if ( empty( $num_rounds ) ) {
			$num_rounds = (string) ( $league->current_season['num_match_days'] ?? 0 );
		}
		if ( $num_teams & 1 ) {
			++$num_teams;
		}
		$rounds = $this->schedule_round_robin->generate( $num_teams, (int) $num_rounds, $home_away );
		$is_box = ! empty( $league->event->is_box );
		$this->create_match_schedule( $league, $rounds, $teams, $match_dates, $season, $is_box );
	}

	/**
	 * Create match schedule with teams.
	 *
	 * @param League $league
	 * @param array $rounds
	 * @param array $teams
	 * @param array $match_dates
	 * @param string $season
	 * @param bool $is_box
	 * @return void
	 */
	public function create_match_schedule( League $league, array $rounds, array $teams, array $match_dates, string $season, bool $is_box ): void {
		$fixture_service = $this->get_fixture_service();
		if ( ! $fixture_service ) {
			throw new \RuntimeException( 'Cannot create match schedule: Fixture_Service collaborator is required.' );
		}

		$num_rounds = count( $rounds );
		for ( $i = 0; $i < $num_rounds; $i++ ) {
			if ( ! $is_box ) {
				$round_number              = $i + 1;
				$start_date                = $match_dates[ $i ] ?? null;
				$rounds[ $i ]['startDate'] = $start_date;
			}
			$fixtures = $rounds[ $i ]['fixtures'] ?? [];
			foreach ( $fixtures as $fixture ) {
				$home_team_dtls = $teams[ $fixture['home'] - 1 ] ?? null;
				$away_team_dtls = $teams[ $fixture['away'] - 1 ] ?? null;
				if ( $home_team_dtls && $away_team_dtls && -1 !== (int) $home_team_dtls->id && -1 !== (int) $away_team_dtls->id ) {
					$match            = new stdClass();
					$match->season    = $season;
					$match->league_id = $league->id;
					$match->home_team = $home_team_dtls->id;
					$match->away_team = $away_team_dtls->id;
					if ( $is_box ) {
						$match->date      = null;
						$match->match_day = 1;
						$match->location  = '';
					} else {
						$match_day        = $home_team_dtls->match_day ?? '';
						$match_time       = $home_team_dtls->match_time ?? '';
						$day              = Util_Lookup::get_match_day_number( $match_day );
						$match_date       = Util::amend_date( (string) $start_date, $day );
						$match->date      = $match_date . ' ' . $match_time;
						$match->match_day = $round_number;
						$match->location  = $home_team_dtls->club->shortcode ?? '';
					}
					$fixture_entity = Fixture_Factory::from_object( $match );
					$fixture_service->create_fixture( $fixture_entity, $league );
				}
			}
		}
	}

	/**
	 * Generate box league matches for an event.
	 *
	 * @param object $event
	 * @return array{scheduled: int, skipped: int}
	 */
	public function generate_box_league_matches( object $event ): array {
		return $this->schedule_event_leagues( $event );
	}

	/**
	 * Schedule all resolvable leagues belonging to an event.
	 *
	 * @return array{scheduled: int, skipped: int}
	 */
	public function schedule_event_leagues( object $event ): array {
		$leagues = $event instanceof Event
			? $this->event_query()->get_leagues( $event )
			: ( method_exists( $event, 'get_leagues' ) ? $event->get_leagues() : ( $event->leagues ?? array() ) );
		$result  = array( 'scheduled' => 0, 'skipped' => 0 );
		if ( ! is_iterable( $leagues ) ) {
			return $result;
		}
		foreach ( $leagues as $league ) {
			$league_obj = $this->resolve_league( $league );
			if ( $league_obj instanceof League ) {
				$this->schedule_league( $league_obj );
				++$result['scheduled'];
			} else {
				++$result['skipped'];
			}
		}
		return $result;
	}

	private function resolve_league( mixed $league ): ?League {
		if ( $league instanceof League ) {
			return $league;
		}
		$id = is_object( $league ) ? ( $league->id ?? null ) : $league;
		if ( ! is_numeric( $id ) || (int) $id <= 0 ) {
			return null;
		}
		if ( $this->league_repository ) {
			return $this->league_repository->find_by_id( (int) $id );
		}
		// Without a repository only a raw league row can be resolved (legacy get_league() object path):
		// hydrate it through the factory (event from the row or the event query service) and set its season.
		if ( is_object( $league ) && isset( $league->title, $league->event_id ) ) {
			$event = isset( $league->event ) && $league->event instanceof Event ? $league->event : null;
			if ( null === $event && $this->event_query_service instanceof Event_Query_Service ) {
				$event = $this->event_query_service->get_event( (int) $league->event_id );
			}
			$hydrated = League_Factory::from_object( $league, $event );
			if ( $hydrated instanceof League && $this->league_query_service ) {
				$this->league_query()->set_season( $hydrated, false, true );
			}
			return $hydrated;
		}
		return null;
	}

	private function event_query(): Event_Query_Service {
		if ( ! $this->event_query_service instanceof Event_Query_Service ) {
			throw new \LogicException( 'Event_Query_Service is required by League_Schedule_Service but was not configured.' );
		}
		return $this->event_query_service;
	}

	private function league_query(): League_Query_Service {
		if ( $this->league_query_service instanceof \Closure ) {
			$this->league_query_service = ( $this->league_query_service )();
		}
		if ( ! $this->league_query_service instanceof League_Query_Service ) {
			throw new \LogicException( 'League_Query_Service is required by League_Schedule_Service but was not configured.' );
		}
		return $this->league_query_service;
	}
}
