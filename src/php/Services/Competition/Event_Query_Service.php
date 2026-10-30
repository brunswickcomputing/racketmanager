<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Competition;

use Closure;
use LogicException;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Fixture\Rubber;
use Racketmanager\Domain\Fixture\Rubber_Factory;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Infrastructure\Wordpress\Request\Competition_Request;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Util\Util;
use Racketmanager\Util\Util_Lookup;
use stdClass;

/**
 * Application service that runs repository-backed Event reads and writes.
 *
 * Supplies explicit repository arguments to Event data methods, giving callers
 * outside the domain a single dependency without storing collaborators on Event.
 */
class Event_Query_Service {
	private Event_Repository_Interface $event_repository;
	private Fixture_Repository_Interface|Closure|null $fixture_repository;
	private Rubber_Repository_Interface|Closure|null $rubber_repository;
	private League_Repository_Interface|Closure|null $league_repository;
	private Team_Repository_Interface|Closure|null $team_repository;
	private Club_Repository_Interface|Closure|null $club_repository;
	private Player_Repository_Interface|Closure|null $player_repository;

	public function __construct(
		Event_Repository_Interface $event_repository,
		Fixture_Repository_Interface|Closure|null $fixture_repository = null,
		Rubber_Repository_Interface|Closure|null $rubber_repository = null,
		League_Repository_Interface|Closure|null $league_repository = null,
		Team_Repository_Interface|Closure|null $team_repository = null,
		Club_Repository_Interface|Closure|null $club_repository = null,
		Player_Repository_Interface|Closure|null $player_repository = null
	) {
		$this->event_repository   = $event_repository;
		$this->fixture_repository = $fixture_repository;
		$this->rubber_repository  = $rubber_repository;
		$this->league_repository  = $league_repository;
		$this->team_repository    = $team_repository;
		$this->club_repository    = $club_repository;
		$this->player_repository  = $player_repository;
	}

	public function get_event_repository(): Event_Repository_Interface {
		return $this->event_repository;
	}

	public function get_fixture_repository(): Fixture_Repository_Interface {
		if ( $this->fixture_repository instanceof Closure ) {
			$this->fixture_repository = ( $this->fixture_repository )();
		}
		if ( ! $this->fixture_repository instanceof Fixture_Repository_Interface ) {
			throw new LogicException( 'Fixture_Repository_Interface is required by Event_Query_Service but was not configured.' );
		}
		return $this->fixture_repository;
	}

	public function get_rubber_repository(): Rubber_Repository_Interface {
		if ( $this->rubber_repository instanceof Closure ) {
			$this->rubber_repository = ( $this->rubber_repository )();
		}
		if ( ! $this->rubber_repository instanceof Rubber_Repository_Interface ) {
			throw new LogicException( 'Rubber_Repository_Interface is required by Event_Query_Service but was not configured.' );
		}
		return $this->rubber_repository;
	}

	public function get_league_repository(): League_Repository_Interface {
		if ( $this->league_repository instanceof Closure ) {
			$this->league_repository = ( $this->league_repository )();
		}
		if ( ! $this->league_repository instanceof League_Repository_Interface ) {
			throw new LogicException( 'League_Repository_Interface is required by Event_Query_Service but was not configured.' );
		}
		return $this->league_repository;
	}

	public function get_team_repository(): Team_Repository_Interface {
		if ( $this->team_repository instanceof Closure ) {
			$this->team_repository = ( $this->team_repository )();
		}
		if ( ! $this->team_repository instanceof Team_Repository_Interface ) {
			throw new LogicException( 'Team_Repository_Interface is required by Event_Query_Service but was not configured.' );
		}
		return $this->team_repository;
	}

	public function get_club_repository(): Club_Repository_Interface {
		if ( $this->club_repository instanceof Closure ) {
			$this->club_repository = ( $this->club_repository )();
		}
		if ( ! $this->club_repository instanceof Club_Repository_Interface ) {
			throw new LogicException( 'Club_Repository_Interface is required by Event_Query_Service but was not configured.' );
		}
		return $this->club_repository;
	}

	public function get_player_repository(): Player_Repository_Interface {
		if ( $this->player_repository instanceof Closure ) {
			$this->player_repository = ( $this->player_repository )();
		}
		if ( ! $this->player_repository instanceof Player_Repository_Interface ) {
			throw new LogicException( 'Player_Repository_Interface is required by Event_Query_Service but was not configured.' );
		}
		return $this->player_repository;
	}

	public function get_event( int|string $event_id, string $search_term = 'id' ): ?Event {
		if ( 'name' === $search_term && is_numeric( $event_id ) ) {
			$search_term = 'id';
			$event_id    = (int) $event_id;
		}
		$event = 'name' === $search_term
			? $this->event_repository->find_by_name( (string) $event_id )
			: $this->event_repository->find_by_id( $event_id );

		return $event instanceof Event ? $event : null;
	}

	public function set_season( Event $event, ?string $season = null, bool $force_overwrite = false ): void {
		if ( ! $force_overwrite ) {
			$requested_season = $this->resolve_requested_season( $event );
			if ( null !== $requested_season ) {
				$event->set_season( $requested_season, force_overwrite: false, strict: true );
				return;
			}
		}

		$event->set_season( $season, $force_overwrite );
	}

	public function resolve_requested_season( Event $event ): ?string {
		if ( ! isset( $event->id ) || (int) $event->id <= 0 ) {
			return null;
		}
		return Competition_Request::season( (int) $event->id );
	}

	public function get_leagues( Event $event, array $args = array() ): array|int {
		if ( ! isset( $event->id ) ) {
			return ! empty( $args['count'] ) ? 0 : array();
		}
		$defaults    = array(
			'offset'      => 0,
			'limit'       => 99999999,
			'orderby'     => array( 'title' => 'ASC' ),
			'consolation' => false,
			'count'       => false,
			'season'      => false,
		);
		$args        = array_merge( $defaults, $args );
		$consolation = $args['consolation'];
		$count       = $args['count'];

		$result = $this->event_repository->query_leagues( (int) $event->id, $args );
		if ( $count ) {
			return (int) $result;
		}

		$league_repo  = $this->get_league_repository();
		$leagues      = is_array( $result ) ? $result : [];
		$league_index = array();
		foreach ( $leagues as $i => $league ) {
			$league_id = is_object( $league ) ? (int) ( $league->id ?? 0 ) : (int) $league;
			$league_index[ $league_id ] = $i;
			$loaded_league = $league_repo ? $league_repo->find_by_id( $league_id ) : ( $league instanceof League ? $league : null );
			if ( $consolation && ( ! $loaded_league || ! $loaded_league->championship?->is_consolation ) ) {
				unset( $leagues[ $i ] );
			}
			if ( isset( $leagues[ $i ] ) && $loaded_league ) {
				$leagues[ $i ] = $loaded_league;
			}
		}
		if ( ! $consolation ) {
			$event->leagues      = $leagues;
			$event->league_index = $league_index;
		}

		return $leagues;
	}

	public function get_matches( Event $event, array $match_args = array() ): array|int {
		if ( ! isset( $event->id ) ) {
			return ! empty( $match_args['count'] ) ? 0 : array();
		}
		$defaults = array(
			'leagueId'            => false,
			'season'              => false,
			'final'               => false,
			'orderby'             => array(
				'league_id' => 'ASC',
				'id'        => 'ASC',
			),
			'confirmed'           => false,
			'player'              => false,
			'match_date'          => false,
			'time'                => false,
			'timeOffset'          => false,
			'history'             => false,
			'club'                => false,
			'league_name'         => false,
			'team_id'             => false,
			'team_name'           => false,
			'home_team'           => false,
			'away_team'           => false,
			'match_day'           => false,
			'home_club'           => false,
			'count'               => false,
			'confirmationPending' => false,
			'resultPending'       => false,
			'status'              => false,
			'pending'             => false,
		);
		$match_args = array_merge( $defaults, $match_args );
		$count      = $match_args['count'] ?? false;
		$player     = $match_args['player'] ?? false;

		$result = $this->event_repository->query_matches( (int) $event->id, $match_args );
		if ( $count ) {
			return (int) $result;
		}

		$fixture_repo = $this->get_fixture_repository();
		$rubber_repo  = $player ? $this->get_rubber_repository() : null;

		$matches          = is_array( $result ) ? $result : [];
		$class            = '';
		$hydrated_matches = [];

		foreach ( $matches as $match ) {
			$match_id = is_object( $match ) ? (int) ( $match->id ?? 0 ) : (int) $match;
			$fixture  = $fixture_repo->find_by_id_with_relations( $match_id );
			if ( ! $fixture ) {
				continue;
			}
			$class          = ( 'alternate' === $class ) ? '' : 'alternate';
			$fixture->class = $class;
			if ( $player && $rubber_repo ) {
				$fixture->set_rubbers( $rubber_repo->find_by_fixture_id( (int) $fixture->get_id(), (int) $player ) );
			}
			if ( ! empty( $fixture->date ) && 19 === strlen( (string) $fixture->date ) && preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', (string) $fixture->date ) ) {
				$fixture->date = substr( (string) $fixture->date, 0, 16 );
			}
			$fixture->get_league();
			if ( empty( $fixture->match_title ) ) {
				$fixture->match_title = $fixture->get_title( __( 'Bye', 'racketmanager' ), __( 'Unknown', 'racketmanager' ) );
			}
			$hydrated_matches[] = $fixture;
		}

		return $hydrated_matches;
	}

	public function get_teams( Event $event, array $args = array() ): array|int {
		if ( ! isset( $event->id ) ) {
			return ! empty( $args['count'] ) ? 0 : array();
		}
		$defaults = array(
			'offset'  => 0,
			'limit'   => 99999999,
			'season'  => false,
			'orderby' => array(
				'league_title' => 'ASC',
				'name'         => 'ASC',
			),
			'club'    => false,
			'league'  => false,
			'status'  => false,
			'count'   => false,
			'name'    => false,
			'player'  => false,
			'partner' => false,
		);
		$args     = array_merge( $defaults, $args );
		$season   = $args['season'];
		$count    = $args['count'];

		if ( ! $count ) {
			$club_repo   = $this->get_club_repository();
			$team_repo   = $this->get_team_repository();
			$player_repo = $this->get_player_repository();
		}

		$result = $this->event_repository->query_teams( (int) $event->id, $args );
		if ( $count ) {
			return (int) $result;
		}

		$event_teams = is_array( $result ) ? $result : [];
		foreach ( $event_teams as $i => $event_team ) {
			$event_team->roster = maybe_unserialize( $event_team->roster );
			$event_team->club   = $club_repo->find_by_id( (int) $event_team->club_id );
			if ( str_contains( (string) $event_team->name, '_' ) ) {
				$team_name = Util::generate_team_name( (string) $event_team->name );
				if ( ! empty( $team_name ) ) {
					$event_team->title = $team_name;
				}
			} else {
				$event_team->title = $event_team->name;
			}
			if ( 'P' === $event_team->team_type && ! empty( $event_team->roster ) ) {
				$team                = $team_repo->find_by_id( (int) $event_team->team_id );
				$event_team->players = $team?->players ?? array();
				$p                   = 1;
				if ( ! empty( $team?->players ) ) {
					foreach ( $team->players as $team_player ) {
						$event_team->player[ $p ]    = $team_player->get_fullname();
						$event_team->player_id[ $p ] = $team_player->get_id();
						++$p;
					}
				}
			} elseif ( $event_team->club ) {
				$event_team->player_count = $this->get_players(
					$event,
					array(
						'season' => $season,
						'count'  => true,
						'team'   => $event_team->team_id,
					)
				);
			} else {
				$event_team->player_count = 0;
			}
			$event_team->info  = $this->get_team_info( $event, (int) $event_team->team_id );
			$event_teams[ $i ] = $event_team;
		}

		$event->event_teams = $event_teams;

		return $event_teams;
	}

	public function get_teams_info( Event $event, array $args = array() ): array {
		if ( ! isset( $event->id ) ) {
			return array();
		}
		$season = $event->get_season();
		if ( empty( $season ) ) {
			return array();
		}
		$club_repo = $this->get_club_repository();
		$teams = $this->event_repository->get_teams_info( (int) $event->id, $season, $args );

		$class = '';
		foreach ( $teams as $i => $team ) {
			$class        = ( 'alternate' === $class ) ? '' : 'alternate';
			$captain_id   = $team->captain ?? null;
			$captain      = $captain_id ? get_userdata( $captain_id ) : null;
			$team->roster = isset( $team->roster ) ? maybe_unserialize( $team->roster ) : null;
			$team->title  = htmlspecialchars( stripslashes( (string) ( $team->title ?? '' ) ), ENT_QUOTES );
			if ( ! empty( $captain ) ) {
				$team->captain      = $captain->display_name;
				$team->captain_id   = $captain->ID;
				$team->contactno    = get_user_meta( $captain->ID, 'contactno', true );
				$team->contactemail = $captain->user_email;
			} else {
				$team->captain      = 'Unknown';
				$team->captain_id   = '';
				$team->contactno    = '';
				$team->contactemail = '';
			}
			$team->club_id = stripslashes( (string) ( $team->club_id ?? '' ) );
			$team->club    = ! empty( $team->club_id ) ? $club_repo->find_by_id( (int) $team->club_id ) : null;
			$team->stadium = stripslashes( (string) ( $team->stadium ?? '' ) );
			$team->class   = $class;
			$teams[ $i ]   = $team;
		}

		return $teams;
	}

	public function get_team_info( Event $event, int $team_id ): ?object {
		if ( ! isset( $event->id ) ) {
			return null;
		}
		$season = $event->get_season();
		$team   = $this->event_repository->get_team_info( (int) $event->id, $team_id, $season );

		if ( $team ) {
			if ( ! empty( $team->match_day ) ) {
				$team->match_day_num = Util_Lookup::get_match_day_number( $team->match_day );
			} else {
				$team->match_day_num = null;
			}
			$captain = get_userdata( $team->captain );
			if ( $captain ) {
				$team->captain      = $captain->display_name;
				$team->captain_id   = $captain->ID;
				$team->contactno    = get_user_meta( $captain->ID, 'contactno', true );
				$team->contactemail = $captain->user_email;
			} else {
				$team->captain      = 'Unknown';
				$team->captain_id   = '';
				$team->contactno    = '';
				$team->contactemail = '';
			}
		}

		return $team;
	}

	public function get_players( Event $event, array|string $args = array() ): array|int {
		if ( ! isset( $event->id ) ) {
			return ! empty( $args['count'] ) ? 0 : array();
		}
		$defaults = array(
			'offset'  => 0,
			'limit'   => 99999999,
			'season'  => false,
			'orderby' => array(),
			'club'    => false,
			'team'    => false,
			'count'   => false,
		);
		$args     = array_merge( $defaults, (array) $args );
		$count    = $args['count'];
		if ( empty( $args['season'] ) ) {
			$args['season'] = is_array( $event->current_season ) ? ( $event->current_season['name'] ?? '' ) : '';
		}

		$is_player_entry = (bool) ( $event->competition->is_player_entry ?? false );
		$result = $this->event_repository->query_players( (int) $event->id, $is_player_entry, $args );
		if ( $count ) {
			return (int) $result;
		}

		$player_repo   = $this->get_player_repository();
		$players       = is_array( $result ) ? $result : [];
		$event_players = array();
		foreach ( $players as $player ) {
			$player_entity = null;
			if ( $player_repo ) {
				try {
					$player_entity = $player_repo->find( (int) $player->player_id );
				} catch ( Player_Not_Found_Exception ) {
					$player_entity = null;
				}
			}
			if ( ! $player_entity || $player_entity->system_record ) {
				continue;
			}
			$event_players[] = $player_entity;
		}
		asort( $event_players );
		$event->players = $event_players;
		return $event->players;
	}

	public function get_constitution( Event $event, array $args = array() ): array|int {
		if ( ! isset( $event->id ) ) {
			return ! empty( $args['count'] ) ? 0 : array();
		}
		$defaults  = array(
			'offset'    => 0,
			'limit'     => 99999999,
			'season'    => false,
			'oldseason' => false,
			'club'      => false,
			'count'     => false,
		);
		$args      = array_merge( $defaults, $args );
		$count     = $args['count'];

		$result = $this->event_repository->query_constitution( (int) $event->id, $args );
		if ( $count ) {
			return (int) $result;
		}

		$league_repo   = $this->get_league_repository();
		$constitutions = is_array( $result ) ? $result : [];
		foreach ( $constitutions as $i => $constitution ) {
			if ( isset( $constitution->old_league_id ) ) {
				$loaded_league                  = $league_repo ? $league_repo->find_by_id( (int) $constitution->old_league_id ) : null;
				$constitution->old_league_title = $loaded_league?->title ?? '';
			} else {
				$constitution->old_league_title = '';
			}
			$constitutions[ $i ] = $constitution;
		}

		$event->constitutions = $constitutions;

		return $constitutions;
	}

	public function build_constitution( Event $event, array|string $args = array() ): array {
		if ( ! isset( $event->id ) ) {
			return array();
		}
		$defaults = array(
			'offset' => 0,
			'limit'  => 99999999,
			'season' => false,
			'club'   => false,
		);
		$args     = array_merge( $defaults, (array) $args );

		$constitutions = $this->event_repository->build_constitution( (int) $event->id, $args );
		$constitutions = Competition_Season_Service::normalize_constitution_rows( is_array( $constitutions ) ? $constitutions : array() );

		$event->constitutions = $constitutions;

		return $constitutions;
	}

	public function get_clubs( Event $event, array|string $args = array() ): array|int {
		if ( ! isset( $event->id ) ) {
			return ! empty( $args['count'] ) ? 0 : array();
		}
		$defaults = array(
			'offset'  => 0,
			'limit'   => 99999999,
			'season'  => false,
			'status'  => false,
			'count'   => false,
			'name'    => false,
		);
		$args     = array_merge( $defaults, (array) $args );
		$count    = $args['count'];
		$season   = $args['season'] ?: ( $event->current_season['name'] ?? false );
		$args['season'] = $season;

		if ( ! $count ) {
			$club_repo   = $this->get_club_repository();
			$player_repo = $this->get_player_repository();
		}

		$result = $this->event_repository->query_clubs( (int) $event->id, $args );
		if ( $count ) {
			return (int) $result;
		}

		$event_clubs = is_array( $result ) ? $result : [];
		foreach ( $event_clubs as $i => $event_club ) {
			$team_count  = (int) ( $event_club->team_count ?? 0 );
			$loaded_club = $club_repo ? $club_repo->find_by_id( (int) $event_club->club_id ) : null;
			if ( ! $loaded_club ) {
				continue;
			}
			$loaded_club->team_count   = $team_count;
			$loaded_club->player_count = (int) ( $this->get_players(
				$event,
				array(
					'season' => $season,
					'count'  => true,
					'club'   => $loaded_club->id,
				)
			) ?? 0 );
			$event_clubs[ $i ]        = $loaded_club;
		}

		$event->clubs = $event_clubs;

		return $event_clubs;
	}

	public function get_player_stats( Event $event, array $args ): array {
		if ( ! isset( $event->id ) ) {
			return array();
		}
		return $this->event_repository->get_player_stats( (int) $event->id, $args );
	}

	public function has_leagues( Event $event ): int {
		if ( ! isset( $event->id ) ) {
			return 0;
		}
		return $this->event_repository->count_leagues( (int) $event->id );
	}

	public function set_num_leagues( Event $event, bool $total = false ): void {
		if ( true === $total && isset( $event->id ) ) {
			$event->num_leagues = $this->event_repository->count_leagues( (int) $event->id );
		}
	}

	public function get_primary_league( Event $event ): ?League {
		if ( ! empty( $event->primary_league_obj ) && $event->primary_league_obj instanceof League ) {
			return $event->primary_league_obj;
		}
		$primary_league_id = $this->get_primary_league_id( $event );
		if ( ! $primary_league_id ) {
			return null;
		}

		if ( is_array( $event->leagues ) ) {
			if ( isset( $event->league_index[ $primary_league_id ] ) && isset( $event->leagues[ $event->league_index[ $primary_league_id ] ] ) ) {
				$league = $event->leagues[ $event->league_index[ $primary_league_id ] ];
				if ( $league instanceof League ) {
					return $league;
				}
			}
			foreach ( $event->leagues as $league ) {
				if ( $league instanceof League && isset( $league->id ) && (int) $league->id === $primary_league_id ) {
					return $league;
				}
			}
		}

		return $this->get_league_repository()->find_by_id( (int) $primary_league_id );
	}

	public function get_primary_league_id( Event $event ): ?int {
		if ( ! empty( $event->primary_league ) && (int) $event->primary_league > 0 ) {
			return (int) $event->primary_league;
		}

		$leagues = ! empty( $event->leagues ) ? $event->leagues : ( isset( $event->id ) ? $this->get_leagues( $event ) : array() );
		if ( is_array( $leagues ) && ! empty( $leagues ) ) {
			$first_league = reset( $leagues );
			if ( is_object( $first_league ) && ! empty( $first_league->id ) ) {
				return (int) $first_league->id;
			}
		}

		return null;
	}

	public function reload_settings( Event $event ): void {
		if ( ! isset( $event->id ) ) {
			return;
		}
		$settings = $this->event_repository->get_settings( (int) $event->id );
		foreach ( $settings as $key => $value ) {
			$event->$key = $value;
		}
	}

	public function set_settings( Event $event, array $settings ): void {
		$old_reverse_rubbers = $event->settings['reverse_rubbers'] ?? null;
		$event->set_settings( $settings );
		$num_rubbers = $event->num_rubbers ?? null;
		$num_sets    = $event->num_sets ?? null;
		$type        = $event->type;
		if ( isset( $settings['reverse_rubbers'] ) && '1' === $settings['reverse_rubbers'] ) {
			$match_args             = array();
			$match_args['season']   = $event->current_season['name'] ?? null;
			$match_args['event_id'] = $event->id;
			if ( null === $old_reverse_rubbers || $old_reverse_rubbers !== $settings['reverse_rubbers'] ) {
				$raw_matches = $this->event_repository->query_matches( (int) $event->id, $match_args );
				$matches     = array();
				if ( is_array( $raw_matches ) ) {
					foreach ( $raw_matches as $row ) {
						$match_id = is_object( $row ) ? (int) ( $row->id ?? 0 ) : (int) $row;
						$fixture  = $this->get_fixture_repository()->find_by_id_with_relations( $match_id );
						if ( $fixture ) {
							$matches[] = $fixture;
						}
					}
				}

				$rubber_repo = $this->get_rubber_repository();
				$league_repo = $this->get_league_repository();

				foreach ( $matches as $fixture ) {
					if ( ! is_object( $fixture ) || ! method_exists( $fixture, 'get_id' ) ) {
						continue;
					}
					$match_id     = (int) $fixture->get_id();
					$rubber_count = $rubber_repo->count_by_fixture_id( $match_id );
					$league_id    = $fixture->get_league_id();
					$league       = $league_id ? $league_repo->find_by_id( $league_id ) : null;
					$num_rubbers_threshold = $league ? (int) ( $league->num_rubbers ?? 0 ) : 0;
					$total_rubbers = $rubber_count * 2;
					if ( $num_rubbers_threshold > 0 && intval( $rubber_count ) === $num_rubbers_threshold ) {
						for ( $ix = $rubber_count + 1; $ix <= $total_rubbers; $ix++ ) {
							$rubber_data                = new stdClass();
							$rubber_data->type          = $event->type;
							$rubber_data->rubber_number = $ix;
							$rubber_data->date          = $fixture->get_date();
							$rubber_data->match_id      = $fixture->get_id();
							$rubber_entity              = Rubber_Factory::from_object( $rubber_data );
							$rubber_repo->save( $rubber_entity );
						}
					}
				}
			}
		}

		if ( isset( $event->id ) ) {
			$this->event_repository->update_settings_and_rules( (int) $event->id, $settings, $num_rubbers, $num_sets, $type );
		}
	}

	public function update_season( Event $event, array $season ): void {
		if ( $event->update_season( $season ) && isset( $event->id ) ) {
			$this->event_repository->update_seasons( (int) $event->id, $event->get_seasons() );
		}
	}

	public function update_seasons( Event $event, array $seasons ): bool {
		$updated = $event->update_seasons( $seasons );
		if ( $updated && isset( $event->id ) ) {
			$this->event_repository->update_seasons( (int) $event->id, $seasons );
		}
		return $updated;
	}

	public function mark_teams_withdrawn( Event $event, string $season, int $club, false|int $team = false ): bool {
		if ( ! isset( $event->id ) ) {
			return false;
		}
		return $this->event_repository->mark_teams_withdrawn( (int) $event->id, $season, $club, $team );
	}

	public function mark_teams_entered( Event $event, int $team, string $season ): bool {
		if ( ! isset( $event->id ) ) {
			return false;
		}
		return $this->event_repository->mark_teams_entered( (int) $event->id, $team, $season );
	}

	public function get_club( Event $event, object $club ): object {
		$season = $event->current_season['name'] ?? '';
		$club->teams = $this->get_teams(
			$event,
			array(
				'club'   => $club->id,
				'season' => $season,
				'status' => 1,
			)
		);
		$club->matches = array();
		$matches       = $this->get_matches(
			$event,
			array(
				'season'  => $season,
				'club'    => $club->id,
				'time'    => 'next',
				'orderby' => array(
					'date'      => 'ASC',
					'league_id' => 'DESC',
				),
			)
		);
		foreach ( $matches as $match ) {
			$key = substr( (string) $match->date, 0, 10 );
			if ( false === array_key_exists( $key, $club->matches ) ) {
				$club->matches[ $key ] = array();
			}
			$club->matches[ $key ][] = $match;
		}
		$club->results = array();
		$matches       = $this->get_matches(
			$event,
			array(
				'season'  => $season,
				'club'    => $club->id,
				'time'    => 'latest',
				'orderby' => array(
					'date'      => 'ASC',
					'league_id' => 'DESC',
				),
			)
		);
		foreach ( $matches as $match ) {
			$key = substr( (string) $match->date, 0, 10 );
			if ( false === array_key_exists( $key, $club->results ) ) {
				$club->results[ $key ] = array();
			}
			$club->results[ $key ][] = $match;
		}
		$club->players = $this->get_players(
			$event,
			array(
				'club'   => $club->id,
				'season' => $season,
			)
		);
		return $club;
	}
}
