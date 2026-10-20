<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Competition;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
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
			$this->createStub( Event_Repository_Interface::class )
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

	public function test_cascade_season_to_events_reads_events_from_event_repository_and_passes_league_team_repository(): void {
		$league_team_repository = $this->createStub( League_Team_Repository_Interface::class );

		$event = new class() {
			public ?array $season = null;
			public ?League_Team_Repository_Interface $repo = null;

			public function add_season( array $season, League_Team_Repository_Interface $repo ): void {
				$this->season = $season;
				$this->repo   = $repo;
			}
		};

		$event_repository = $this->createMock( Event_Repository_Interface::class );
		$event_repository->expects( $this->once() )
			->method( 'find_by_competition_id' )
			->with( 31 )
			->willReturn( [ $event ] );

		$service = new Competition_Season_Service(
			$league_team_repository,
			$this->createStub( Club_Repository_Interface::class ),
			$event_repository
		);

		$season                 = new \stdClass();
		$season->name           = '2026';
		$season->home_away      = 'true';
		$season->num_match_days = 10;
		$season->match_dates    = [ '1' => '2026-06-01' ];

		$service->cascade_season_to_events( Competition_Test_Factory::create( id: 31 ), $season );

		$this->assertSame( $league_team_repository, $event->repo );
		$this->assertSame( '2026', $event->season['name'] );
		$this->assertSame( 'true', $event->season['home_away'] );
		$this->assertSame( 10, $event->season['num_match_days'] );
		$this->assertSame( [ '1' => '2026-06-01' ], $event->season['match_dates'] );
	}

	public function test_cascade_season_to_events_without_events_does_nothing(): void {
		$event_repository = $this->createMock( Event_Repository_Interface::class );
		$event_repository->expects( $this->once() )->method( 'find_by_competition_id' )->with( 31 )->willReturn( [] );

		$service = new Competition_Season_Service(
			$this->createStub( League_Team_Repository_Interface::class ),
			$this->createStub( Club_Repository_Interface::class ),
			$event_repository
		);

		$season       = new \stdClass();
		$season->name = '2026';
		$service->cascade_season_to_events( Competition_Test_Factory::create( id: 31 ), $season );
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
			$this->createStub( Event_Repository_Interface::class )
		);
	}
}
