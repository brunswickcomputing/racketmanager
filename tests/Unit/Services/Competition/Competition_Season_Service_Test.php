<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Competition;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\Events\Competition_Season_Cascaded_Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Events\Domain_Event_Dispatcher_Interface;
use Racketmanager\Domain\Events\Domain_Event_Interface;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Services\Competition\Competition_Season_Service;
use Racketmanager\Tests\Helpers\Club_Test_Factory;
use Racketmanager\Tests\Helpers\Competition_Test_Factory;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

class Competition_Season_Service_Test extends TestCase {
	use Manages_Wordpress_Globals;

	private Competition_Season_Service $service;

	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();
		$_GET = [];
		unset( $GLOBALS['wp'] );
		$this->service = new Competition_Season_Service(
			$this->createStub( League_Team_Repository_Interface::class ),
			$this->createStub( Club_Repository_Interface::class ),
			$this->createStub( Event_Repository_Interface::class ),
			$this->createStub( League_Repository_Interface::class )
		);
	}

	protected function tearDown(): void {
		$this->restore_globals();
		parent::tearDown();
	}

	public function test_calculate_phase_end() {
		$data = [
			'date_end' => gmdate( 'Y-m-d', strtotime( '-1 day' ) ),
		];
		$this->assertEquals( 'end', $this->service->calculate_phase( $data ) );
		$this->assertEquals( 'end', Competition::calculate_phase( $data ) );
	}

	public function test_calculate_phase_start_on_end_boundary() {
		$data = [
			'date_start' => gmdate( 'Y-m-d', strtotime( '-5 days' ) ),
			'date_end'   => gmdate( 'Y-m-d' ),
		];
		$this->assertEquals( 'start', $this->service->calculate_phase( $data ) );
		$this->assertEquals( 'start', Competition::calculate_phase( $data ) );
	}

	public function test_calculate_phase_start_on_start_boundary() {
		$data = [
			'date_start' => gmdate( 'Y-m-d' ),
			'date_end'   => gmdate( 'Y-m-d', strtotime( '+5 days' ) ),
		];
		$this->assertEquals( 'start', $this->service->calculate_phase( $data ) );
		$this->assertEquals( 'start', Competition::calculate_phase( $data ) );
	}

	public function test_calculate_phase_close_boundary() {
		$data = [
			'date_closing' => gmdate( 'Y-m-d', strtotime( '-1 day' ) ),
			'date_start'   => gmdate( 'Y-m-d', strtotime( '+5 days' ) ),
			'date_end'     => gmdate( 'Y-m-d', strtotime( '+10 days' ) ),
		];
		$this->assertEquals( 'close', $this->service->calculate_phase( $data ) );
		$this->assertEquals( 'close', Competition::calculate_phase( $data ) );
	}

	public function test_calculate_phase_open_on_closing_boundary() {
		$data = [
			'date_open'    => gmdate( 'Y-m-d', strtotime( '-5 days' ) ),
			'date_closing' => gmdate( 'Y-m-d' ),
			'date_start'   => gmdate( 'Y-m-d', strtotime( '+5 days' ) ),
		];
		$this->assertEquals( 'open', $this->service->calculate_phase( $data ) );
		$this->assertEquals( 'open', Competition::calculate_phase( $data ) );
	}

	public function test_calculate_phase_open_on_open_boundary() {
		$data = [
			'date_open'    => gmdate( 'Y-m-d' ),
			'date_closing' => gmdate( 'Y-m-d', strtotime( '+5 days' ) ),
			'date_start'   => gmdate( 'Y-m-d', strtotime( '+10 days' ) ),
		];
		$this->assertEquals( 'open', $this->service->calculate_phase( $data ) );
		$this->assertEquals( 'open', Competition::calculate_phase( $data ) );
	}

	public function test_calculate_phase_pending() {
		$data = [
			'date_open' => gmdate( 'Y-m-d', strtotime( '+1 day' ) ),
		];
		$this->assertEquals( 'pending', $this->service->calculate_phase( $data ) );
		$this->assertEquals( 'pending', Competition::calculate_phase( $data ) );
	}

	public function test_calculate_phase_complete_when_no_dates() {
		$data = [];
		$this->assertEquals( 'complete', $this->service->calculate_phase( $data ) );
		$this->assertEquals( 'complete', Competition::calculate_phase( $data ) );
	}

	public function test_cascade_season_to_events_reads_events_from_event_repository_and_persists_seasons(): void {
		$event = $this->make_event( 25 );

		$event_repository = $this->createMock( Event_Repository_Interface::class );
		$event_repository->expects( $this->once() )
			->method( 'find_by_competition_id' )
			->with( 31 )
			->willReturn( [ $event ] );
		$event_repository->expects( $this->once() )
			->method( 'update_seasons' )
			->with(
				25,
				$this->callback(
					static function ( array $seasons ): bool {
						return isset( $seasons['2026'] )
							&& '2026' === $seasons['2026']['name']
							&& 'true' === $seasons['2026']['home_away']
							&& 10 === $seasons['2026']['num_match_days']
							&& [ '1' => '2026-06-01' ] === $seasons['2026']['match_dates'];
					}
				)
			)
			->willReturn( true );

		$service = new Competition_Season_Service(
			$this->createStub( League_Team_Repository_Interface::class ),
			$this->createStub( Club_Repository_Interface::class ),
			$event_repository,
			$this->createStub( League_Repository_Interface::class )
		);

		$season                 = new \stdClass();
		$season->name           = '2026';
		$season->home_away      = 'true';
		$season->num_match_days = 10;
		$season->match_dates    = [ '1' => '2026-06-01' ];

		$service->cascade_season_to_events( Competition_Test_Factory::create( id: 31 ), $season );

		$this->assertArrayHasKey( '2026', $event->get_seasons() );
		$this->assertSame( '2026', $event->get_seasons()['2026']['name'] );
		$this->assertSame( 'true', $event->get_seasons()['2026']['home_away'] );
		$this->assertSame( 10, $event->get_seasons()['2026']['num_match_days'] );
		$this->assertSame( [ '1' => '2026-06-01' ], $event->get_seasons()['2026']['match_dates'] );
	}

	public function test_cascade_season_to_events_without_events_does_nothing(): void {
		$event_repository = $this->createMock( Event_Repository_Interface::class );
		$event_repository->expects( $this->once() )->method( 'find_by_competition_id' )->with( 31 )->willReturn( [] );

		$service = new Competition_Season_Service(
			$this->createStub( League_Team_Repository_Interface::class ),
			$this->createStub( Club_Repository_Interface::class ),
			$event_repository,
			$this->createStub( League_Repository_Interface::class )
		);

		$season       = new \stdClass();
		$season->name = '2026';
		$service->cascade_season_to_events( Competition_Test_Factory::create( id: 31 ), $season );
	}

	public function test_cascade_season_to_events_dispatches_domain_event(): void {
		$league_team_repo = $this->createStub( League_Team_Repository_Interface::class );
		$club_repo        = $this->createStub( Club_Repository_Interface::class );
		$event_repo       = $this->createMock( Event_Repository_Interface::class );
		$dispatcher       = $this->createMock( Domain_Event_Dispatcher_Interface::class );

		$competition = Competition_Test_Factory::create( id: 10 );
		$event       = $this->make_event( 25 );

		$event_repo->expects( $this->once() )
			->method( 'find_by_competition_id' )
			->with( 10 )
			->willReturn( [ $event ] );
		$event_repo->method( 'update_seasons' )->willReturn( true );

		$dispatched_events = [];
		$dispatcher->expects( $this->once() )
			->method( 'dispatch' )
			->willReturnCallback( function ( Domain_Event_Interface $event ) use ( &$dispatched_events ) {
				$dispatched_events[] = $event;
			} );

		$service = new Competition_Season_Service(
			league_team_repository: $league_team_repo,
			club_repository: $club_repo,
			event_repository: $event_repo,
			league_repository: $this->createStub( League_Repository_Interface::class ),
			event_dispatcher: $dispatcher
		);

		$season                 = new \stdClass();
		$season->name           = 'Summer 2026';
		$season->home_away      = 1;
		$season->num_match_days = 10;
		$season->match_dates    = [];

		$service->cascade_season_to_events( $competition, $season );

		$this->assertCount( 1, $dispatched_events );
		$this->assertInstanceOf( Competition_Season_Cascaded_Event::class, $dispatched_events[0] );
		$this->assertSame( 10, $dispatched_events[0]->competition_id );
		$this->assertSame( 'Summer 2026', $dispatched_events[0]->season_name );
	}

	// ---------------------------------------------------------------------
	// resolve_current_season()
	// ---------------------------------------------------------------------

	public function test_resolve_without_request_or_season_uses_domain_active_season_and_backfills_match_dates(): void {
		$competition = $this->competition();

		$data = $this->service->resolve_current_season( $competition );

		$this->assertSame( '2025', $data['name'] );
		$this->assertSame( '2025-06-01', $data['date_start'], 'date_start is back-filled from the first match date' );
		$this->assertSame( '2025-08-01', $data['date_end'], 'date_end is back-filled from the last match date' );
		$this->assertSame( $data, $competition->current_season );
		$this->assertSame( 'end', $competition->current_phase );
		$this->assertTrue( $competition->is_complete );
	}

	public function test_resolve_uses_given_season_without_request(): void {
		$competition = $this->competition();

		$data = $this->service->resolve_current_season( $competition, '2024' );

		$this->assertSame( '2024', $data['name'] );
		$this->assertSame( 'close', $competition->current_phase );
		$this->assertTrue( $competition->is_closed );
		$this->assertFalse( $competition->is_complete );
	}

	public function test_resolve_unknown_given_season_falls_back_to_active_season(): void {
		$data = $this->service->resolve_current_season( $this->competition(), '1999' );

		$this->assertSame( '2025', $data['name'] );
	}

	public function test_request_get_season_takes_precedence_over_given_season(): void {
		$_GET['season'] = '2024';

		$data = $this->service->resolve_current_season( $this->competition(), '2026' );

		$this->assertSame( '2024', $data['name'] );
	}

	public function test_request_get_season_is_sanitised(): void {
		$_GET['season'] = '<b>2024</b>';

		$data = $this->service->resolve_current_season( $this->competition() );

		$this->assertSame( '2024', $data['name'] );
	}

	public function test_request_competition_specific_season_is_used_when_no_generic_season(): void {
		$_GET['season_7'] = '2024';
		$_GET['season_8'] = '2026';

		$data = $this->service->resolve_current_season( $this->competition() );

		$this->assertSame( '2024', $data['name'] );
	}

	public function test_request_generic_season_beats_competition_specific_season_and_query_var(): void {
		$_GET['season']   = '2026';
		$_GET['season_7'] = '2024';
		$GLOBALS['wp']    = (object) [ 'query_vars' => [ 'season' => '2025' ] ];

		$data = $this->service->resolve_current_season( $this->competition() );

		$this->assertSame( '2026', $data['name'] );
	}

	public function test_request_query_var_season_is_used_when_no_get_parameters(): void {
		$GLOBALS['wp'] = (object) [ 'query_vars' => [ 'season' => '2024' ] ];

		$data = $this->service->resolve_current_season( $this->competition() );

		$this->assertSame( '2024', $data['name'] );
	}

	public function test_unknown_request_season_is_ignored_and_given_season_used(): void {
		$_GET['season'] = '1999';

		$data = $this->service->resolve_current_season( $this->competition(), '2024' );

		$this->assertSame( '2024', $data['name'] );
	}

	public function test_unknown_request_season_without_given_season_falls_back_to_active_season(): void {
		$_GET['season'] = '1999';

		$data = $this->service->resolve_current_season( $this->competition() );

		$this->assertSame( '2025', $data['name'] );
	}

	public function test_forced_season_beats_request(): void {
		$_GET['season'] = '2024';

		$data = $this->service->resolve_current_season( $this->competition(), '2026', true );

		$this->assertSame( '2026', $data['name'] );
		$this->assertSame( 'pending', $this->competition_phase_of( '2026', true ) );
	}

	public function test_request_selected_season_backfills_match_dates(): void {
		$_GET['season'] = '2025';

		$data = $this->service->resolve_current_season( $this->competition() );

		$this->assertSame( '2025-06-01', $data['date_start'] );
		$this->assertSame( '2025-08-01', $data['date_end'] );
	}

	public function test_venue_name_is_resolved_once_from_club_repository(): void {
		$club_repository = $this->createMock( Club_Repository_Interface::class );
		$club_repository->expects( $this->once() )
			->method( 'find_by_id' )
			->with( 130 )
			->willReturn( Club_Test_Factory::create( id: 130, name: 'Thorpe Bay LTC', shortcode: 'Thorpe Bay' ) );
		$service = $this->service_with_clubs( $club_repository );

		$competition = $this->competition();
		$data        = $service->resolve_current_season( $competition );

		$this->assertSame( 130, $data['venue'] );
		$this->assertSame( 'Thorpe Bay', $data['venue_name'] );
		$this->assertSame( 'Thorpe Bay', $competition->current_season['venue_name'] );
	}

	public function test_venue_name_is_null_when_venue_club_missing(): void {
		$club_repository = $this->createMock( Club_Repository_Interface::class );
		$club_repository->expects( $this->once() )->method( 'find_by_id' )->with( 130 )->willReturn( null );

		$data = $this->service_with_clubs( $club_repository )->resolve_current_season( $this->competition() );

		$this->assertArrayHasKey( 'venue_name', $data );
		$this->assertNull( $data['venue_name'] );
	}

	public function test_venue_name_is_null_without_lookup_when_season_has_no_venue(): void {
		$club_repository = $this->createMock( Club_Repository_Interface::class );
		$club_repository->expects( $this->never() )->method( 'find_by_id' );

		$data = $this->service_with_clubs( $club_repository )->resolve_current_season( $this->competition(), '2024' );

		$this->assertArrayHasKey( 'venue_name', $data );
		$this->assertNull( $data['venue_name'] );
	}

	public function test_add_season_to_event_merges_and_persists_seasons(): void {
		$event      = $this->make_event( 5 );
		$event_repo = $this->createMock( Event_Repository_Interface::class );
		$event_repo->expects( $this->once() )
			->method( 'update_seasons' )
			->with( 5, [ '2026' => [ 'name' => '2026', 'home_away' => true ] ] )
			->willReturn( true );
		$event_repo->expects( $this->never() )->method( 'build_constitution' );

		$service = new Competition_Season_Service(
			$this->createStub( League_Team_Repository_Interface::class ),
			$this->createStub( Club_Repository_Interface::class ),
			$event_repo,
			$this->createStub( League_Repository_Interface::class )
		);

		$service->add_season_to_event( $event, [ 'name' => '2026', 'home_away' => true ] );

		$this->assertArrayHasKey( '2026', $event->get_seasons() );
		$this->assertTrue( $event->get_seasons()['2026']['home_away'] );
	}

	public function test_add_season_to_event_skips_seeding_for_non_league_event(): void {
		$competition            = $this->createStub( Competition::class );
		$competition->is_league = false;
		$event                  = $this->make_event( 5, [ '2025' => [ 'name' => '2025' ] ], $competition );

		$event_repo = $this->createMock( Event_Repository_Interface::class );
		$event_repo->expects( $this->once() )->method( 'update_seasons' )->willReturn( true );
		$event_repo->expects( $this->never() )->method( 'build_constitution' );

		$league_repo = $this->createMock( League_Repository_Interface::class );
		$league_repo->expects( $this->never() )->method( 'add_team_to_league' );

		$service = new Competition_Season_Service(
			$this->createStub( League_Team_Repository_Interface::class ),
			$this->createStub( Club_Repository_Interface::class ),
			$event_repo,
			$league_repo
		);

		$service->add_season_to_event( $event, [ 'name' => '2026' ] );
	}

	public function test_add_season_to_event_skips_seeding_when_there_is_no_previous_season(): void {
		$competition            = $this->createStub( Competition::class );
		$competition->is_league = true;
		$event                  = $this->make_event( 5, [], $competition );

		$event_repo = $this->createMock( Event_Repository_Interface::class );
		$event_repo->expects( $this->once() )->method( 'update_seasons' )->willReturn( true );
		$event_repo->expects( $this->never() )->method( 'build_constitution' );

		$league_repo = $this->createMock( League_Repository_Interface::class );
		$league_repo->expects( $this->never() )->method( 'add_team_to_league' );

		$service = new Competition_Season_Service(
			$this->createStub( League_Team_Repository_Interface::class ),
			$this->createStub( Club_Repository_Interface::class ),
			$event_repo,
			$league_repo
		);

		$service->add_season_to_event( $event, [ 'name' => '2026' ] );
	}

	public function test_add_season_to_event_seeds_previous_season_constitution_in_rank_order(): void {
		$competition            = $this->createStub( Competition::class );
		$competition->is_league = true;
		$event                  = $this->make_event( 50, [ '2025' => [ 'name' => '2025' ] ], $competition );

		$team_one = (object) [
			'team_id'       => 101,
			'old_league_id' => 22,
			'old_rank'      => 1,
			'status'        => 'P1',
			'captain'       => 55,
			'match_day'     => 'Monday',
			'match_time'    => '19:00',
		];
		$team_two = (object) [
			'team_id'       => 102,
			'old_league_id' => 22,
			'old_rank'      => 2,
			'status'        => 'W',
			'captain'       => 66,
			'match_day'     => 'Tuesday',
			'match_time'    => '18:30',
		];

		$event_repo = $this->createMock( Event_Repository_Interface::class );
		$event_repo->expects( $this->once() )->method( 'update_seasons' )->willReturn( true );
		$event_repo->expects( $this->once() )
			->method( 'build_constitution' )
			->with(
				50,
				[
					'offset' => 0,
					'limit'  => 99999999,
					'season' => '2025',
					'club'   => false,
				]
			)
			->willReturn( [ $team_one, $team_two ] );

		$league     = $this->createStub( League::class );
		$league->id = 22;

		$league_repo = $this->createMock( League_Repository_Interface::class );
		$league_repo->expects( $this->once() )->method( 'find_by_id' )->with( 22 )->willReturn( $league );
		$league_repo->expects( $this->exactly( 2 ) )
			->method( 'add_team_to_league' )
			->willReturnCallback(
				function ( int $league_id, int $team_id, string $season, string $rank, string $status, int $profile ): int {
					$this->assertSame( 22, $league_id );
					$this->assertSame( '2026', $season );
					$this->assertSame( '', $status );
					$this->assertSame( 0, $profile );
					if ( 101 === $team_id ) {
						$this->assertSame( '1', $rank );
						return 301;
					}
					$this->assertSame( 102, $team_id );
					$this->assertSame( '2', $rank );
					return 302;
				}
			);

		$league_team_repo = $this->createMock( League_Team_Repository_Interface::class );
		$league_team_repo->expects( $this->exactly( 2 ) )
			->method( 'update_details' )
			->willReturnCallback(
				function ( int $league_team_id, int $captain, string $match_day, string $match_time ): bool {
					if ( 301 === $league_team_id ) {
						$this->assertSame( 55, $captain );
						$this->assertSame( 'Monday', $match_day );
						$this->assertSame( '19:00', $match_time );
						return true;
					}
					$this->assertSame( 302, $league_team_id );
					$this->assertSame( 66, $captain );
					$this->assertSame( 'Tuesday', $match_day );
					$this->assertSame( '18:30', $match_time );
					return true;
				}
			);

		$service = new Competition_Season_Service(
			$league_team_repo,
			$this->createStub( Club_Repository_Interface::class ),
			$event_repo,
			$league_repo
		);

		$service->add_season_to_event( $event, [ 'name' => '2026' ] );

		$this->assertSame( 301, $team_one->league_team_id );
		$this->assertSame( 302, $team_two->league_team_id );
		$this->assertSame( '', $team_one->status );
		$this->assertSame( 'W', $team_two->status );
		$this->assertSame( '0', $team_one->profile );
		$this->assertSame( '0', $team_two->profile );
	}

	public function test_add_season_to_event_skips_missing_league(): void {
		$competition            = $this->createStub( Competition::class );
		$competition->is_league = true;
		$event                  = $this->make_event( 50, [ '2025' => [ 'name' => '2025' ] ], $competition );

		$team_row = (object) [
			'team_id'       => 101,
			'old_league_id' => 22,
			'old_rank'      => 1,
			'status'        => '',
			'captain'       => 55,
			'match_day'     => 'Monday',
			'match_time'    => '19:00',
		];

		$event_repo = $this->createMock( Event_Repository_Interface::class );
		$event_repo->expects( $this->once() )->method( 'update_seasons' )->willReturn( true );
		$event_repo->method( 'build_constitution' )->willReturn( [ $team_row ] );

		$league_repo = $this->createMock( League_Repository_Interface::class );
		$league_repo->expects( $this->once() )->method( 'find_by_id' )->with( 22 )->willReturn( null );
		$league_repo->expects( $this->never() )->method( 'add_team_to_league' );

		$league_team_repo = $this->createMock( League_Team_Repository_Interface::class );
		$league_team_repo->expects( $this->never() )->method( 'update_details' );

		$service = new Competition_Season_Service(
			$league_team_repo,
			$this->createStub( Club_Repository_Interface::class ),
			$event_repo,
			$league_repo
		);

		$service->add_season_to_event( $event, [ 'name' => '2026' ] );
		$this->assertObjectNotHasProperty( 'league_team_id', $team_row );
	}

	public function test_build_event_constitution_normalises_status_profile_and_rank(): void {
		$withdrawn = (object) [
			'old_rank'      => 3,
			'status'        => 'W',
			'old_league_id' => 22,
		];
		$promoted  = (object) [
			'old_rank'      => 1,
			'status'        => 'P1',
			'old_league_id' => 22,
		];

		$event_repo = $this->createMock( Event_Repository_Interface::class );
		$event_repo->expects( $this->once() )
			->method( 'build_constitution' )
			->with(
				50,
				[
					'offset' => 0,
					'limit'  => 99999999,
					'season' => '2025',
					'club'   => false,
				]
			)
			->willReturn( [ $withdrawn, $promoted ] );

		$service = new Competition_Season_Service(
			$this->createStub( League_Team_Repository_Interface::class ),
			$this->createStub( Club_Repository_Interface::class ),
			$event_repo,
			$this->createStub( League_Repository_Interface::class )
		);

		$result = $service->build_event_constitution( 50, [ 'season' => '2025' ] );

		$this->assertSame( 'W', $result[0]->status );
		$this->assertSame( 3, $result[0]->rank );
		$this->assertSame( '0', $result[0]->profile );
		$this->assertSame( 22, $result[0]->league_id );
		$this->assertSame( '', $result[1]->status );
		$this->assertSame( 1, $result[1]->rank );
		$this->assertSame( '0', $result[1]->profile );
		$this->assertSame( 22, $result[1]->league_id );
	}

	/**
	 * Competition 7 with a closed 2024 season, an active 2025 season held at venue 130 whose dates come
	 * from its match dates, and a future 2026 season.
	 */
	private function competition(): Competition {
		return Competition_Test_Factory::create(
			id: 7,
			seasons: [
				'2024' => [
					'name'         => '2024',
					'date_open'    => '2024-03-01',
					'date_closing' => '2024-05-01',
				],
				'2025' => [
					'name'         => '2025',
					'venue'        => 130,
					'date_open'    => '2025-03-01',
					'date_closing' => '2025-05-01',
					'match_dates'  => [ '2025-06-01', '2025-07-01', '2025-08-01' ],
				],
				'2026' => [
					'name'         => '2026',
					'date_open'    => '2099-01-01',
					'date_closing' => '2099-02-01',
				],
			]
		);
	}

	private function competition_phase_of( string $season, bool $force ): string {
		$competition = $this->competition();
		$this->service->resolve_current_season( $competition, $season, $force );

		return $competition->current_phase;
	}

	private function service_with_clubs( Club_Repository_Interface $club_repository ): Competition_Season_Service {
		return new Competition_Season_Service(
			$this->createStub( League_Team_Repository_Interface::class ),
			$club_repository,
			$this->createStub( Event_Repository_Interface::class ),
			$this->createStub( League_Repository_Interface::class )
		);
	}

	private function make_event( int $id = 5, array $seasons = [], ?Competition $competition = null ): Event {
		return new Event(
			(object) [
				'id'             => $id,
				'name'           => 'Men Doubles ' . $id,
				'type'           => 'MD',
				'num_sets'       => 3,
				'num_rubbers'    => 2,
				'settings'       => '',
				'seasons'        => wp_json_encode( $seasons ),
				'competition_id' => 10,
			],
			$competition
		);
	}
}
