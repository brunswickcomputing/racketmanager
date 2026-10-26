<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Competition;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\Scheduling\Competition_Scheduler;
use Racketmanager\Domain\Competition\Scheduling\Schedule_Division_DTO;
use Racketmanager\Domain\Competition\Scheduling\Schedule_Result_DTO;
use Racketmanager\Domain\Competition\Scheduling\Schedule_Team_DTO;
use Racketmanager\Domain\Competition\Scheduling\Scheduled_Match_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Exceptions\Schedule_Validation_Exception;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\Competition\Competition_Schedule_Service;
use Racketmanager\Services\Competition\Event_Query_Service;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\Fixture_Service;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
class Competition_Schedule_Service_Test extends TestCase {
	private Event_Repository_Interface&MockObject $event_repository;
	private League_Repository_Interface&MockObject $league_repository;
	private Event_Query_Service&MockObject $event_query_service;
	private League_Query_Service&MockObject $league_query_service;
	private Fixture_Service&MockObject $fixture_service;
	private Competition_Scheduler $scheduler;
	private Competition_Schedule_Service $service;

	protected function setUp(): void {
		parent::setUp();
		$this->event_repository     = $this->createMock( Event_Repository_Interface::class );
		$this->league_repository    = $this->createMock( League_Repository_Interface::class );
		$this->event_query_service  = $this->createMock( Event_Query_Service::class );
		$this->league_query_service = $this->createMock( League_Query_Service::class );
		$this->fixture_service      = $this->createMock( Fixture_Service::class );
		// Real engine (final class) made deterministic: identity shuffle, rand_bool true, frozen clock, 3 retries.
		$this->scheduler            = new Competition_Scheduler(
			3,
			60.0,
			static function ( array &$items ): void {},
			static fn(): bool => true,
			static fn(): float => 0.0
		);
		$this->service              = new Competition_Schedule_Service(
			$this->event_repository,
			$this->league_repository,
			$this->event_query_service,
			$this->league_query_service,
			$this->fixture_service,
			$this->scheduler
		);
	}

	private function make_event( int $id ): Event {
		$event     = ( new \ReflectionClass( Event::class ) )->newInstanceWithoutConstructor();
		$event->id = $id;
		return $event;
	}

	private function make_league( int $id, string $title, string $season = '2026' ): League {
		$league        = ( new \ReflectionClass( League::class ) )->newInstanceWithoutConstructor();
		$league->id    = $id;
		$league->title = $title;
		$league->seasons[ $season ] = array();
		return $league;
	}

	private function make_team( int $id, string $title, int $club_id, string $day, string $time, string $shortcode ): object {
		return (object) [
			'id'         => $id,
			'title'      => $title,
			'club_id'    => $club_id,
			'match_day'  => $day,
			'match_time' => $time,
			'club'       => (object) [ 'shortcode' => $shortcode ],
		];
	}

	private function team_dto( int $id, string $name, int $club_id = 1, string $time = '18:30', string $location = 'CLB' ): Schedule_Team_DTO {
		return new Schedule_Team_DTO( $id, $name, $club_id, 'Monday', $time, $location, 'Monday ' . $time, 1 );
	}

	private function result_with_matches( array $schedule, bool $is_valid = true ): Schedule_Result_DTO {
		return new Schedule_Result_DTO( retry: 3, is_valid: $is_valid, total_weeks: 3, schedule: $schedule );
	}

	// ----- build_divisions -----

	public function test_build_divisions_maps_leagues_and_teams_to_dtos(): void {
		$event   = $this->make_event( 7 );
		$league1 = $this->make_league( 10, 'Division 1' );
		$league2 = $this->make_league( 11, 'Division 2' );

		$this->event_repository->expects( $this->once() )->method( 'find_by_id' )->with( 7 )->willReturn( $event );
		$this->event_query_service->expects( $this->once() )->method( 'get_leagues' )->with( $event )->willReturn( [ $league1, $league2 ] );

		$expected_args = [
			'season'      => '2026',
			'status'      => 'active',
			'get_details' => true,
			'orderby'     => [
				'group' => 'ASC',
				'title' => 'ASC',
			],
		];
		$this->league_query_service->expects( $this->exactly( 2 ) )
			->method( 'get_league_teams' )
			->with( $this->isInstanceOf( League::class ), $expected_args )
			->willReturnCallback( function ( League $league ) {
				if ( 10 === $league->id ) {
					return [
						$this->make_team( 101, 'Alpha A', 1, 'Monday', '18:30', 'ALP' ),
						$this->make_team( 102, 'Beta A', 2, 'Tuesday', '19:00', 'BET' ),
					];
				}
				return [ $this->make_team( 201, 'Alpha B', 1, 'Monday', '18:30', 'ALP' ) ];
			} );

		$divisions = $this->service->build_divisions( [ '7' ], '2026' );

		$this->assertSame( [ 10, 11 ], array_keys( $divisions ) );
		$this->assertContainsOnlyInstancesOf( Schedule_Division_DTO::class, $divisions );
		$this->assertSame( 10, $divisions[10]->league_id );
		$this->assertSame( 'Division 1', $divisions[10]->league_name );
		$this->assertCount( 2, $divisions[10]->teams );
		$this->assertCount( 1, $divisions[11]->teams );

		$team = $divisions[10]->teams[1];
		$this->assertInstanceOf( Schedule_Team_DTO::class, $team );
		$this->assertSame( 102, $team->id );
		$this->assertSame( 'Beta A', $team->name );
		$this->assertSame( 2, $team->club_id );
		$this->assertSame( 'Tuesday', $team->match_day );
		$this->assertSame( '19:00', $team->match_time );
		$this->assertSame( 'BET', $team->location );
		$this->assertSame( 'Tuesday 19:00', $team->preferred_slot );
		$this->assertSame( 7, $team->event_id );
	}

	public function test_build_divisions_resolves_league_ids_through_repository(): void {
		$event  = $this->make_event( 7 );
		$league = $this->make_league( 10, 'Division 1' );

		$this->event_repository->expects( $this->once() )->method( 'find_by_id' )->with( 7 )->willReturn( $event );
		$this->event_query_service->expects( $this->once() )->method( 'get_leagues' )->with( $event )->willReturn( [ (object) [ 'id' => 10 ] ] );
		$this->league_repository->expects( $this->once() )->method( 'find_by_id' )->with( 10 )->willReturn( $league );
		$this->league_query_service->method( 'get_league_teams' )->willReturn( [] );

		$divisions = $this->service->build_divisions( [ 7 ], '2026' );

		$this->assertSame( [ 10 ], array_keys( $divisions ) );
		$this->assertSame( [], $divisions[10]->teams );
	}

	public function test_build_divisions_tolerates_teams_without_club_details(): void {
		$event  = $this->make_event( 7 );
		$league = $this->make_league( 10, 'Division 1' );
		$this->event_repository->method( 'find_by_id' )->willReturn( $event );
		$this->event_query_service->method( 'get_leagues' )->willReturn( [ $league ] );
		$this->league_query_service->method( 'get_league_teams' )->willReturn( [ (object) [ 'id' => 5, 'title' => 'Lonely' ] ] );

		$team = $this->service->build_divisions( [ 7 ], '2026' )[10]->teams[0];

		$this->assertSame( 5, $team->id );
		$this->assertSame( 0, $team->club_id );
		$this->assertSame( '', $team->location );
		$this->assertSame( '', $team->match_day );
		$this->assertSame( ' ', $team->preferred_slot );
	}

	// ----- generate_matches_from_schedule -----

	public function test_generate_matches_creates_one_fixture_per_non_bye_match(): void {
		$home  = $this->team_dto( 101, 'Alpha A', 1, '18:30', 'ALP' );
		$away  = $this->team_dto( 102, 'Beta A', 2, '19:00', 'BET' );
		$third = $this->team_dto( 103, 'Gamma A', 3 );

		$schedule = [
			10 => [
				'league_id'   => 10,
				'league_name' => 'Division 1',
				'weeks'       => [
					1 => [
						Scheduled_Match_DTO::fixture( $home, $away, '2026-04-06', '1_Monday 18:30_C0' ),
						Scheduled_Match_DTO::bye( $third, '2026-04-06' ),
					],
					2 => [
						Scheduled_Match_DTO::fixture( $away, $third, '2026-04-14', '2_Monday 19:00_C0' ),
					],
				],
			],
		];
		$league   = $this->make_league( 10, 'Division 1' );
		$this->league_repository->expects( $this->once() )->method( 'find_by_id' )->with( 10 )->willReturn( $league );

		$created = [];
		$this->fixture_service->expects( $this->exactly( 2 ) )
			->method( 'create_fixture' )
			->willReturnCallback( function ( Fixture $fixture, object $passed_league ) use ( &$created, $league ) {
				$this->assertSame( $league, $passed_league );
				$created[] = $fixture;
				return $fixture;
			} );

		$count = $this->service->generate_matches_from_schedule( $this->result_with_matches( $schedule ), '2026' );

		$this->assertSame( 2, $count );
		$this->assertCount( 2, $created );

		$first = $created[0];
		$this->assertSame( '2026', $first->get_season() );
		$this->assertSame( 10, $first->get_league_id() );
		$this->assertSame( 101, (int) $first->get_home_team() );
		$this->assertSame( 102, (int) $first->get_away_team() );
		$this->assertSame( '2026-04-06 18:30', $first->get_date() );
		$this->assertSame( 1, $first->get_match_day() );
		$this->assertSame( 'ALP', $first->get_location() );

		$second = $created[1];
		$this->assertSame( 102, (int) $second->get_home_team() );
		$this->assertSame( 103, (int) $second->get_away_team() );
		$this->assertSame( '2026-04-14 19:00', $second->get_date() );
		$this->assertSame( 2, $second->get_match_day() );
		$this->assertSame( 'BET', $second->get_location() );
	}

	public function test_generate_matches_uses_extra_match_location_for_cross_division_matches(): void {
		$home     = $this->team_dto( 101, 'Alpha A', 1, '18:30', 'ALP' );
		$away     = $this->team_dto( 102, 'Beta A', 2, '19:00', 'BET' );
		$schedule = [
			10 => [
				'league_id'   => 10,
				'league_name' => 'Division 1',
				'weeks'       => [
					3 => [ Scheduled_Match_DTO::fixture( $home, $away, '2026-04-20', '1_Monday 18:30_C0', 'Extra Match', true ) ],
				],
			],
		];
		$this->league_repository->method( 'find_by_id' )->willReturn( $this->make_league( 10, 'Division 1' ) );
		$this->fixture_service->expects( $this->once() )
			->method( 'create_fixture' )
			->with( $this->callback( fn( Fixture $f ) => 'Extra Match' === $f->get_location() && 3 === $f->get_match_day() ) )
			->willReturnArgument( 0 );

		$this->assertSame( 1, $this->service->generate_matches_from_schedule( $this->result_with_matches( $schedule ), '2026' ) );
	}

	public function test_generate_matches_pre_resolves_all_leagues_before_persisting(): void {
		$home     = $this->team_dto( 101, 'Alpha A' );
		$away     = $this->team_dto( 102, 'Beta A', 2 );
		$schedule = [
			10 => [
				'league_id'   => 10,
				'league_name' => 'Division 1',
				'weeks'       => [ 1 => [ Scheduled_Match_DTO::fixture( $home, $away, '2026-04-06', 'r' ) ] ],
			],
			11 => [
				'league_id'   => 11,
				'league_name' => 'Division 2',
				'weeks'       => [ 1 => [ Scheduled_Match_DTO::fixture( $away, $home, '2026-04-06', 'r' ) ] ],
			],
		];
		$looked_up = [];
		$league    = $this->make_league( 10, 'Division 1' );
		$this->league_repository->expects( $this->exactly( 2 ) )
			->method( 'find_by_id' )
			->willReturnCallback( static function ( int $league_id ) use ( &$looked_up, $league ) {
				$looked_up[] = $league_id;
				return 10 === $league_id ? $league : null;
			} );
		$this->fixture_service->expects( $this->never() )->method( 'create_fixture' );

		try {
			$this->service->generate_matches_from_schedule( $this->result_with_matches( $schedule ), '2026' );
			$this->fail( 'Expected Schedule_Validation_Exception' );
		} catch ( Schedule_Validation_Exception $e ) {
			$this->assertSame( [ 'League 11 not found' ], $e->get_error_msgs() );
		}
		$this->assertSame( [ 10, 11 ], $looked_up );
	}

	public function test_generate_matches_with_empty_schedule_creates_nothing(): void {
		$this->fixture_service->expects( $this->never() )->method( 'create_fixture' );
		$this->league_repository->expects( $this->never() )->method( 'find_by_id' );

		$this->assertSame( 0, $this->service->generate_matches_from_schedule( $this->result_with_matches( [] ), '2026' ) );
	}

	public function test_generate_matches_refuses_to_persist_when_any_match_has_no_date(): void {
		$home     = $this->team_dto( 101, 'Alpha A' );
		$away     = $this->team_dto( 102, 'Beta A', 2 );
		$schedule = [
			10 => [
				'league_id'   => 10,
				'league_name' => 'Division 1',
				'weeks'       => [
					1 => [ Scheduled_Match_DTO::fixture( $home, $away, '2026-04-06', 'r' ) ],
					2 => [ Scheduled_Match_DTO::fixture( $away, $home, null, 'r' ) ],
				],
			],
		];
		$this->fixture_service->expects( $this->never() )->method( 'create_fixture' );
		$this->league_repository->expects( $this->never() )->method( 'find_by_id' );

		try {
			$this->service->generate_matches_from_schedule( $this->result_with_matches( $schedule ), '2026' );
			$this->fail( 'Expected Schedule_Validation_Exception' );
		} catch ( Schedule_Validation_Exception $e ) {
			$this->assertSame( [ 'No match date available for Division 1 week 2' ], $e->get_error_msgs() );
			$this->assertSame( 'No match date available for Division 1 week 2', $e->getMessage() );
		}
	}

	// ----- schedule_events -----

	public function test_schedule_events_builds_request_runs_engine_and_persists_when_valid(): void {
		$event  = $this->make_event( 7 );
		$league = $this->make_league( 10, 'Division 1' );
		$this->event_repository->method( 'find_by_id' )->willReturn( $event );
		$this->event_query_service->method( 'get_leagues' )->willReturn( [ $league ] );
		$this->league_query_service->method( 'get_league_teams' )->willReturn( [
			$this->make_team( 101, 'Alpha A', 1, 'Monday', '18:30', 'ALP' ),
			$this->make_team( 102, 'Beta A', 2, 'Tuesday', '19:00', 'BET' ),
		] );
		$this->league_repository->expects( $this->once() )->method( 'find_by_id' )->with( 10 )->willReturn( $league );

		$created = [];
		$this->fixture_service->expects( $this->exactly( 2 ) )
			->method( 'create_fixture' )
			->willReturnCallback( function ( Fixture $fixture, object $passed_league ) use ( &$created, $league ) {
				$this->assertSame( $league, $passed_league );
				$created[] = $fixture;
				return $fixture;
			} );

		// 2 team slots, home and away => 2 weeks, one match per week with sides swapped in week 2.
		$result = $this->service->schedule_events( [ 7 ], '2026', 2, true, [ 'a' => '2026-04-06', 'b' => '2026-04-13' ] );

		$this->assertTrue( $result->is_valid );
		$this->assertSame( 2, $result->total_weeks );
		$this->assertSame( [ 10 ], array_keys( $result->schedule ) );
		$this->assertSame( 'Division 1', $result->schedule[10]['league_name'] );
		$this->assertCount( 2, $result->fixtures() );
		$this->assertCount( 2, $created );

		[ $week1, $week2 ] = $created;
		$this->assertSame( 1, $week1->get_match_day() );
		$this->assertSame( 2, $week2->get_match_day() );
		$this->assertSame( (int) $week1->get_home_team(), (int) $week2->get_away_team() );
		$this->assertSame( (int) $week1->get_away_team(), (int) $week2->get_home_team() );
		foreach ( $created as $fixture ) {
			$this->assertSame( '2026', $fixture->get_season() );
			$this->assertSame( 10, $fixture->get_league_id() );
			if ( 101 === (int) $fixture->get_home_team() ) {
				$this->assertSame( 'ALP', $fixture->get_location() );
				$this->assertStringEndsWith( ' 18:30', (string) $fixture->get_date() );
			} else {
				$this->assertSame( 'BET', $fixture->get_location() );
				$this->assertStringEndsWith( ' 19:00', (string) $fixture->get_date() );
			}
		}
		// Week 1 starts 2026-04-06 (Monday); week 2 starts 2026-04-13 - Tuesday team plays on the 14th.
		$dates = array_map( static fn( Fixture $f ) => substr( (string) $f->get_date(), 0, 10 ), $created );
		$this->assertContains( $dates[0], [ '2026-04-06', '2026-04-07' ] );
		$this->assertContains( $dates[1], [ '2026-04-13', '2026-04-14' ] );
	}

	public function test_schedule_events_creates_no_fixtures_when_schedule_is_invalid(): void {
		// Deterministic unavoidable conflict: three same-club teams on one court slot in one division.
		$event   = $this->make_event( 7 );
		$league1 = $this->make_league( 10, 'Division 1' );
		$league2 = $this->make_league( 11, 'Division 2' );
		$this->event_repository->method( 'find_by_id' )->willReturn( $event );
		$this->event_query_service->method( 'get_leagues' )->willReturn( [ $league1, $league2 ] );
		$this->league_query_service->method( 'get_league_teams' )->willReturnCallback( function ( League $league ) {
			$clubs = 10 === $league->id ? [ 1, 1, 1, 2 ] : [ 1, 2, 3, 4 ];
			$teams = [];
			foreach ( $clubs as $i => $club ) {
				$id      = $league->id * 100 + $i;
				$teams[] = $this->make_team( $id, 'Team ' . $id, $club, 'Monday', '18:30', 'C' . $club );
			}
			return $teams;
		} );
		$this->fixture_service->expects( $this->never() )->method( 'create_fixture' );
		$this->league_repository->expects( $this->never() )->method( 'find_by_id' );

		$result = $this->service->schedule_events( [ 7 ], '2026', 4, false, [ '2026-04-06', '2026-04-13', '2026-04-20' ] );

		$this->assertFalse( $result->is_valid );
		$this->assertSame( 4, $result->retry, 'three attempts exhausted, legacy reports max_retries + 1' );
		$this->assertNotEmpty( $result->conflicts );
		$this->assertSame( [ 'week', 'division', 'court', 'home', 'with' ], array_keys( $result->conflicts[0] ) );
		$this->assertNotEmpty( $result->fixtures(), 'best-effort schedule is still returned for the report' );
	}

	public function test_schedule_events_does_not_persist_when_match_dates_are_too_short(): void {
		$event  = $this->make_event( 7 );
		$league = $this->make_league( 10, 'Division 1' );
		$this->event_repository->method( 'find_by_id' )->willReturn( $event );
		$this->event_query_service->method( 'get_leagues' )->willReturn( [ $league ] );
		$this->league_query_service->method( 'get_league_teams' )->willReturn( [
			$this->make_team( 101, 'Alpha A', 1, 'Monday', '18:30', 'ALP' ),
			$this->make_team( 102, 'Beta A', 2, 'Monday', '19:00', 'BET' ),
		] );
		$this->fixture_service->expects( $this->never() )->method( 'create_fixture' );

		try {
			// home/away => 2 weeks but only one match date supplied.
			$this->service->schedule_events( [ 7 ], '2026', 2, true, [ '2026-04-06' ] );
			$this->fail( 'Expected Schedule_Validation_Exception' );
		} catch ( Schedule_Validation_Exception $e ) {
			$this->assertSame( [ 'No match date available for Division 1 week 2' ], $e->get_error_msgs() );
		}
	}

	public function test_schedule_events_rejects_divisions_with_more_teams_than_slots_before_running_engine(): void {
		$event   = $this->make_event( 7 );
		$league1 = $this->make_league( 10, 'Division 1' );
		$league2 = $this->make_league( 11, 'Division 2' );
		$this->event_repository->method( 'find_by_id' )->willReturn( $event );
		$this->event_query_service->method( 'get_leagues' )->willReturn( [ $league1, $league2 ] );
		$this->league_query_service->method( 'get_league_teams' )->willReturnCallback( function ( League $league ) {
			$teams = [];
			$n     = 10 === $league->id ? 5 : 3;
			for ( $i = 1; $i <= $n; $i++ ) {
				$teams[] = $this->make_team( $league->id * 100 + $i, 'Team ' . $i, $i, 'Monday', '18:30', 'T' . $i );
			}
			return $teams;
		} );
		$this->fixture_service->expects( $this->never() )->method( 'create_fixture' );

		try {
			$this->service->schedule_events( [ 7 ], '2026', 4, false, [ '2026-04-06' ] );
			$this->fail( 'Expected Schedule_Validation_Exception' );
		} catch ( Schedule_Validation_Exception $e ) {
			$this->assertSame( [ 'Division 1 has 5 teams but the schedule only allows 4' ], $e->get_error_msgs() );
		}
	}

	public function test_schedule_events_with_no_divisions_returns_valid_empty_schedule(): void {
		$event = $this->make_event( 99 );
		$this->event_repository->expects( $this->once() )->method( 'find_by_id' )->with( 99 )->willReturn( $event );
		$this->event_query_service->expects( $this->once() )->method( 'get_leagues' )->with( $event )->willReturn( [] );
		$this->fixture_service->expects( $this->never() )->method( 'create_fixture' );

		$result = $this->service->schedule_events( [ 99 ], '2026', 6, false, [] );

		$this->assertInstanceOf( Schedule_Result_DTO::class, $result );
		$this->assertTrue( $result->is_valid );
		$this->assertSame( [], $result->schedule );
		$this->assertSame( [], $result->fixtures() );
	}

	public function test_schedule_events_rejects_an_event_that_cannot_be_resolved(): void {
		$this->event_repository->expects( $this->once() )->method( 'find_by_id' )->with( 99 )->willReturn( null );
		$this->event_query_service->expects( $this->never() )->method( 'get_leagues' );
		$this->fixture_service->expects( $this->never() )->method( 'create_fixture' );

		try {
			$this->service->schedule_events( [ 99 ], '2026', 6, false, [] );
			$this->fail( 'Expected Schedule_Validation_Exception' );
		} catch ( Schedule_Validation_Exception $e ) {
			$this->assertSame( [ 'Event 99 not found' ], $e->get_error_msgs() );
		}
	}

	public function test_schedule_events_rejects_a_league_without_the_requested_season(): void {
		$event  = $this->make_event( 7 );
		$league = $this->make_league( 10, 'Division 1', '2025' );
		$this->event_repository->method( 'find_by_id' )->willReturn( $event );
		$this->event_query_service->method( 'get_leagues' )->willReturn( [ $league ] );
		$this->league_query_service->expects( $this->never() )->method( 'get_league_teams' );
		$this->fixture_service->expects( $this->never() )->method( 'create_fixture' );

		try {
			$this->service->schedule_events( [ 7 ], '2026', 6, false, [] );
			$this->fail( 'Expected Schedule_Validation_Exception' );
		} catch ( Schedule_Validation_Exception $e ) {
			$this->assertSame( [ 'Season 2026 is not configured for Division 1' ], $e->get_error_msgs() );
		}
	}
}
