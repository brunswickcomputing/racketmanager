<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Competition;

use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\Scheduling\Competition_Scheduler;
use Racketmanager\Domain\Competition\Scheduling\Schedule_Division_DTO;
use Racketmanager\Domain\Competition\Scheduling\Schedule_Request_DTO;
use Racketmanager\Domain\Competition\Scheduling\Schedule_Result_DTO;
use Racketmanager\Domain\Competition\Scheduling\Schedule_Team_DTO;
use Racketmanager\Domain\Fixture\Fixture_Factory;
use Racketmanager\Exceptions\Schedule_Validation_Exception;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\Fixture_Service;
use stdClass;

/**
 * Application service that turns the leagues of the selected events into
 * scheduler divisions, runs the competition scheduling engine and persists
 * the generated fixtures.
 *
 * Mirrors the data assembly of the 9.7.0 `Admin_League::schedule_league_matches()`
 * and `generate_matches_from_schedule()`.
 */
class Competition_Schedule_Service {
	public function __construct(
		private Event_Repository_Interface $event_repository,
		private League_Repository_Interface $league_repository,
		private Event_Query_Service $event_query_service,
		private League_Query_Service $league_query_service,
		private Fixture_Service $fixture_service,
		private Competition_Scheduler $scheduler,
	) {
	}

	/**
	 * Build scheduler divisions from the active league teams of the given events.
	 *
	 * @param array  $event_ids Event ids to schedule.
	 * @param string $season    Season to schedule.
	 *
	 * @return Schedule_Division_DTO[] keyed by league id.
	 */
	public function build_divisions( array $event_ids, string $season ): array {
		$divisions = array();
		foreach ( $event_ids as $event_id ) {
			$event_id = (int) $event_id;
			$event    = $this->event_repository->find_by_id( $event_id );
			if ( ! $event ) {
				/* translators: %d: event ID. */
				throw new Schedule_Validation_Exception( [ sprintf( __( 'Event %d not found', 'racketmanager' ), $event_id ) ] );
			}
			$leagues = $this->event_query_service->get_leagues( $event );
			if ( ! is_array( $leagues ) ) {
				/* translators: %d: event ID. */
				throw new Schedule_Validation_Exception( [ sprintf( __( 'Could not load leagues for event %d', 'racketmanager' ), $event_id ) ] );
			}
			foreach ( $leagues as $league ) {
				$league_id = is_object( $league ) ? (int) ( $league->id ?? 0 ) : (int) $league;
				$league    = $this->resolve_league( $league );
				if ( ! $league ) {
					/* translators: %d: league ID. */
					throw new Schedule_Validation_Exception( [ sprintf( __( 'League %d not found', 'racketmanager' ), $league_id ) ] );
				}
				if ( ! $league->season_exists( $season ) ) {
					/* translators: 1: season name, 2: league name. */
					throw new Schedule_Validation_Exception( [ sprintf( __( 'Season %1$s is not configured for %2$s', 'racketmanager' ), $season, $league->title ) ] );
				}
				$teams = $this->league_query_service->get_league_teams(
					$league,
					array(
						'season'      => $season,
						'status'      => 'active',
						'get_details' => true,
						'orderby'     => array(
							'group' => 'ASC',
							'title' => 'ASC',
						),
					)
				);
				$division_teams = array();
				foreach ( is_array( $teams ) ? $teams : array() as $team ) {
					$division_teams[] = $this->map_team( $team, $event_id );
				}
				$divisions[ (int) $league->id ] = new Schedule_Division_DTO(
					league_id: (int) $league->id,
					league_name: (string) ( $league->title ?? '' ),
					teams: $division_teams,
				);
			}
		}
		return $divisions;
	}

	/**
	 * Schedule the given events and persist the fixtures when a valid schedule was found.
	 *
	 * @param array  $event_ids          Event ids to schedule.
	 * @param string $season             Season to schedule.
	 * @param int    $teams_per_division Number of team slots per division (`num_rounds + 1`).
	 * @param bool   $home_away          Whether teams play each other home and away.
	 * @param array  $match_dates        Week start dates (`Y-m-d`), one per week.
	 *
	 * @return Schedule_Result_DTO
	 *
	 * @throws Schedule_Validation_Exception when a division holds more teams than the schedule
	 *                                       allows, or a generated match has no date.
	 */
	public function schedule_events( array $event_ids, string $season, int $teams_per_division, bool $home_away, array $match_dates ): Schedule_Result_DTO {
		$divisions = $this->build_divisions( $event_ids, $season );
		$this->assert_division_sizes( $divisions, $teams_per_division );

		$request = new Schedule_Request_DTO(
			divisions: $divisions,
			teams_per_division: $teams_per_division,
			is_home_away: $home_away,
			match_dates: array_values( $match_dates ),
		);
		$result  = $this->scheduler->schedule( $request );
		if ( $result->is_valid ) {
			$this->generate_matches_from_schedule( $result, $season );
		}
		return $result;
	}

	/**
	 * Create one fixture per non-bye match in the schedule.
	 *
	 * Nothing is persisted when any match lacks a date, so a short `match_dates`
	 * list can never produce fixtures with an invalid date.
	 *
	 * @param Schedule_Result_DTO $result Scheduler output.
	 * @param string              $season Season of the fixtures.
	 *
	 * @return int number of fixtures created.
	 *
	 * @throws Schedule_Validation_Exception when a match has no date.
	 */
	public function generate_matches_from_schedule( Schedule_Result_DTO $result, string $season ): int {
		$fixtures = $result->fixtures();
		$this->assert_fixture_dates( $fixtures, $result );

		$leagues = array();
		foreach ( $fixtures as $entry ) {
			$league_id = (int) $entry['league_id'];
			if ( array_key_exists( $league_id, $leagues ) ) {
				continue;
			}
			$league = $this->league_repository->find_by_id( $league_id );
			if ( ! $league ) {
				/* translators: %d: league ID. */
				throw new Schedule_Validation_Exception( [ sprintf( __( 'League %d not found', 'racketmanager' ), $league_id ) ] );
			}
			$leagues[ $league_id ] = $league;
		}

		$count = 0;
		foreach ( $fixtures as $entry ) {
			$league_id = (int) $entry['league_id'];
			$match     = $entry['match'];
			$league = $leagues[ $league_id ];
			$fixture            = new stdClass();
			$fixture->season    = $season;
			$fixture->league_id = $league_id;
			$fixture->home_team = $match->home_id;
			$fixture->away_team = $match->away_id;
			$fixture->date      = trim( $match->date . ' ' . (string) $match->time );
			$fixture->match_day = (int) $entry['week'];
			$fixture->location  = (string) $match->location;

			$this->fixture_service->create_fixture( Fixture_Factory::from_object( $fixture ), $league );
			++$count;
		}
		return $count;
	}

	/**
	 * @param Schedule_Division_DTO[] $divisions
	 *
	 * @throws Schedule_Validation_Exception
	 */
	private function assert_division_sizes( array $divisions, int $teams_per_division ): void {
		$errors = array();
		foreach ( $divisions as $division ) {
			$num_teams = count( $division->teams );
			if ( $num_teams > $teams_per_division ) {
				/* translators: 1: league name 2: number of teams 3: maximum teams */
				$errors[] = sprintf( __( '%1$s has %2$d teams but the schedule only allows %3$d', 'racketmanager' ), $division->league_name, $num_teams, $teams_per_division );
			}
		}
		if ( $errors ) {
			throw new Schedule_Validation_Exception( $errors );
		}
	}

	/**
	 * @param array<int, array{league_id: int, week: int, match: object}> $fixtures
	 *
	 * @throws Schedule_Validation_Exception
	 */
	private function assert_fixture_dates( array $fixtures, Schedule_Result_DTO $result ): void {
		$errors = array();
		foreach ( $fixtures as $entry ) {
			if ( null === $entry['match']->date || '' === $entry['match']->date ) {
				$league_name = $result->schedule[ $entry['league_id'] ]['league_name'] ?? (string) $entry['league_id'];
				/* translators: 1: league name 2: week number */
				$errors[] = sprintf( __( 'No match date available for %1$s week %2$d', 'racketmanager' ), $league_name, $entry['week'] );
			}
		}
		if ( $errors ) {
			throw new Schedule_Validation_Exception( array_values( array_unique( $errors ) ) );
		}
	}

	private function resolve_league( mixed $league ): ?League {
		if ( $league instanceof League ) {
			return $league;
		}
		$id = is_object( $league ) ? ( $league->id ?? null ) : $league;
		if ( ! is_numeric( $id ) || (int) $id <= 0 ) {
			return null;
		}
		return $this->league_repository->find_by_id( (int) $id );
	}

	private function map_team( object $team, int $event_id ): Schedule_Team_DTO {
		$match_day  = (string) ( $team->match_day ?? '' );
		$match_time = (string) ( $team->match_time ?? '' );
		return new Schedule_Team_DTO(
			id: (int) $team->id,
			name: (string) ( $team->title ?? '' ),
			club_id: (int) ( $team->club_id ?? 0 ),
			match_day: $match_day,
			match_time: $match_time,
			location: (string) ( $team->club->shortcode ?? '' ),
			preferred_slot: $match_day . ' ' . $match_time,
			event_id: $event_id,
		);
	}
}
