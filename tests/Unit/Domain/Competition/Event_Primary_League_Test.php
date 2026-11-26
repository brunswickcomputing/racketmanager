<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Competition;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Settings;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Tests\Helpers\League_Test_Factory;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\Competition\Event_Query_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Tests\Helpers\Competition_Test_Factory;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use stdClass;

require_once dirname( __DIR__, 3 ) . '/wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
class Event_Primary_League_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private $event_repository;
    private $league_repository;
    private Event_Query_Service $event_query_service;
    private Event $event;
    private Competition $competition;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        unset( $GLOBALS['racketmanager'] );

        $this->event_repository = $this->createMock( Event_Repository_Interface::class );
        $this->league_repository = $this->createMock( League_Repository_Interface::class );
        $this->event_query_service = new Event_Query_Service( $this->event_repository, league_repository: $this->league_repository );

        $this->competition = Competition_Test_Factory::create(
            id: 1,
            type: Competition_Type::TOURNAMENT,
            settings: [ 'mode' => 'tournament' ],
            current_season: [ 'name' => '2026' ]
        );
        $this->competition->set_standings( [] );

        $event_raw                 = new stdClass();
        $event_raw->id             = 42;
        $event_raw->competition_id = 1;
        $event_raw->name           = 'Men\'s Singles';
        $event_raw->type           = 'singles';
        $event_raw->num_sets       = 3;
        $event_raw->num_rubbers    = 1;
        $event_raw->seasons        = '["2026"]';
        $event_raw->settings       = [];

        $this->event = Event::from_object( $event_raw, $this->competition );
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_get_primary_league_id_returns_explicit_primary_league(): void {
        $this->event->set_primary_league( 101 );
        $this->assertSame( 101, $this->event_query_service->get_primary_league_id( $this->event ) );
    }

    public function test_get_primary_league_id_falls_back_to_in_memory_leagues_array(): void {
        $this->event->set_primary_league( 0 );

        $league = $this->createMock( League::class );
        League_Test_Factory::set_property( $league, 'id', 55 );
        $this->event->set_leagues( [ $league ] );

        $this->assertSame( 55, $this->event_query_service->get_primary_league_id( $this->event ) );
    }

    public function test_get_primary_league_id_lazily_loads_from_repository(): void {
        $this->event->set_primary_league( 0 );
        $this->event->set_leagues( [] );

        $league_db_record = (object) [ 'id' => 77 ];
        $this->event_repository->expects( $this->once() )
            ->method( 'query_leagues' )
            ->with( 42, $this->anything() )
            ->willReturn( [ $league_db_record ] );

        $hydrated_league = $this->createMock( League::class );
        League_Test_Factory::set_property( $hydrated_league, 'id', 77 );
        $this->league_repository->expects( $this->once() )
            ->method( 'find_by_id' )
            ->with( 77 )
            ->willReturn( $hydrated_league );

        $this->assertSame( 77, $this->event_query_service->get_primary_league_id( $this->event ) );
    }

    public function test_get_primary_league_id_returns_null_when_no_leagues_exist(): void {
        $this->event->set_primary_league( 0 );
        $this->event->set_leagues( [] );

        $this->event_repository->expects( $this->once() )
            ->method( 'query_leagues' )
            ->with( 42, $this->anything() )
            ->willReturn( [] );

        $this->assertNull( $this->event_query_service->get_primary_league_id( $this->event ) );
    }

    public function test_get_primary_league_returns_league_instance(): void {
        $this->event->set_primary_league( 88 );

        $league = $this->createMock( League::class );
        League_Test_Factory::set_property( $league, 'id', 88 );
        $this->event->set_leagues( [ $league ] );
        $this->event->set_league_index( [ 88 => 0 ] );

        $result = $this->event_query_service->get_primary_league( $this->event );
        $this->assertSame( $league, $result );
    }

    public function test_get_primary_league_returns_cached_primary_league_obj(): void {
        $event = new Event();
        $league = $this->createMock( League::class );
        League_Test_Factory::set_property( $league, 'id', 73 );
        $event->set_primary_league_obj( $league );
        $event->set_primary_league( 0 );

        $this->assertSame( $league, $this->event_query_service->get_primary_league( $event ) );
        $this->assertSame( 73, $this->event_query_service->get_primary_league_id( $event ) );
    }

    public function test_get_primary_league_falls_back_to_first_hydrated_league_directly(): void {
        $this->event->set_primary_league( 0 );

        $league1 = $this->createMock( League::class );
        League_Test_Factory::set_property( $league1, 'id', 12 );
        $league2 = $this->createMock( League::class );
        League_Test_Factory::set_property( $league2, 'id', 14 );

        $this->event->set_leagues( [ $league1, $league2 ] );

        $this->league_repository->expects( $this->never() )->method( 'find_by_id' );

        $this->assertSame( $league1, $this->event_query_service->get_primary_league( $this->event ) );
        $this->assertSame( 12, $this->event_query_service->get_primary_league_id( $this->event ) );
    }

    public function test_get_primary_league_hydrates_first_league_when_it_is_stdclass(): void {
        $this->event->set_primary_league( 0 );

        $league_record = (object) [ 'id' => 99 ];
        $this->event->set_leagues( [ $league_record ] );

        $hydrated_league = $this->createMock( League::class );
        League_Test_Factory::set_property( $hydrated_league, 'id', 99 );

        $this->league_repository->expects( $this->once() )
            ->method( 'find_by_id' )
            ->with( 99 )
            ->willReturn( $hydrated_league );

        $this->assertSame( $hydrated_league, $this->event_query_service->get_primary_league( $this->event ) );
        $this->assertSame( 99, $this->event_query_service->get_primary_league_id( $this->event ) );
    }

    public function test_get_primary_league_recovers_from_stale_league_index(): void {
        $this->event->set_primary_league( 55 );

        $league44 = $this->createMock( League::class );
        League_Test_Factory::set_property( $league44, 'id', 44 );
        $league55 = $this->createMock( League::class );
        League_Test_Factory::set_property( $league55, 'id', 55 );

        $this->event->set_leagues( [ $league44, $league55 ] );
        $this->event->set_league_index( [ 55 => 0 ] );

        $result = $this->event_query_service->get_primary_league( $this->event );
        $this->assertSame( $league55, $result );
    }

    public function test_get_primary_league_recovers_from_stale_league_index_with_stdclass_rows_without_corrupting_collection(): void {
        $this->event->set_primary_league( 55 );

        $row44 = (object) [ 'id' => 44 ];
        $row55 = (object) [ 'id' => 55 ];

        $this->event->set_leagues( [ $row44, $row55 ] );
        $this->event->set_league_index( [ 55 => 0 ] );

        $hydrated55 = $this->createMock( League::class );
        League_Test_Factory::set_property( $hydrated55, 'id', 55 );

        $this->league_repository->expects( $this->once() )
            ->method( 'find_by_id' )
            ->with( 55 )
            ->willReturn( $hydrated55 );

        $result = $this->event_query_service->get_primary_league( $this->event );

        $this->assertSame( $hydrated55, $result );
        $this->assertSame( [ $row44, $row55 ], $this->event->leagues );
        $this->assertSame( $row44, $this->event->leagues[0] );
        $this->assertSame( $row55, $this->event->leagues[1] );
    }

    public function test_get_primary_league_throws_logic_exception_when_league_repository_is_not_configured(): void {
        $service_without_repo = new Event_Query_Service( $this->event_repository );
        $this->event->set_primary_league( 99 );
        $this->event->set_leagues( [] );

        $this->expectException( \LogicException::class );
        $this->expectExceptionMessage( 'League_Repository_Interface is required by Event_Query_Service but was not configured.' );

        $service_without_repo->get_primary_league( $this->event );
    }

    public function test_get_primary_league_resolves_via_linear_scan_when_league_index_is_missing(): void {
        $this->event->set_primary_league( 66 );

        $league66 = $this->createMock( League::class );
        League_Test_Factory::set_property( $league66, 'id', 66 );

        $this->event->set_leagues( [ $league66 ] );
        $this->event->set_league_index( [] );

        $result = $this->event_query_service->get_primary_league( $this->event );
        $this->assertSame( $league66, $result );
    }

    public function test_get_primary_league_hydrates_from_repository_when_explicit_league_is_not_in_memory(): void {
        $this->event->set_primary_league( 77 );
        $this->event->set_leagues( [] );

        $hydrated_league = $this->createMock( League::class );
        League_Test_Factory::set_property( $hydrated_league, 'id', 77 );

        $this->league_repository->expects( $this->once() )
            ->method( 'find_by_id' )
            ->with( 77 )
            ->willReturn( $hydrated_league );

        $result = $this->event_query_service->get_primary_league( $this->event );
        $this->assertSame( $hydrated_league, $result );
    }

    public function test_get_primary_league_id_fast_path_does_not_call_repository_or_query(): void {
        $this->event->set_primary_league( 88 );

        $this->league_repository->expects( $this->never() )->method( $this->anything() );
        $this->event_repository->expects( $this->never() )->method( $this->anything() );

        $this->assertSame( 88, $this->event_query_service->get_primary_league_id( $this->event ) );
    }

    public function test_get_primary_league_returns_null_when_primary_id_is_null(): void {
        $this->event->set_primary_league( 0 );
        $this->event->set_leagues( [] );

        $this->event_repository->method( 'query_leagues' )->willReturn( [] );

        $this->assertNull( $this->event_query_service->get_primary_league( $this->event ) );
    }

    public function test_set_config_persists_primary_league_for_tournament_competition(): void {
        $config = new stdClass();
        $config->name = 'Men\'s Singles';
        $config->type = 'singles';
        $config->num_sets = 3;
        $config->primary_league = 99;

        $updated = $this->event->set_config( $config );

        $this->assertTrue( $updated );
        $this->assertSame( 99, $this->event->primary_league );
        $this->assertSame( 99, $this->event->settings['primary_league'] );
    }

    public function test_set_config_preserves_existing_primary_league_when_zero_or_empty_passed(): void {
        $this->event->set_primary_league( 123 );

        $config = new stdClass();
        $config->name = 'Men\'s Singles';
        $config->type = 'singles';
        $config->num_sets = 5;
        $config->primary_league = 0; // Empty/zero select option

        $updated = $this->event->set_config( $config );

        $this->assertTrue( $updated );
        $this->assertSame( 123, $this->event->primary_league );
        $this->assertSame( 123, $this->event->settings['primary_league'] );
    }

    public function test_set_config_does_not_write_primary_league_for_standard_league_competition_when_not_passed(): void {
        $league_competition = Competition_Test_Factory::create(
            id: 1,
            type: Competition_Type::LEAGUE,
            current_season: [ 'name' => '2026' ]
        );
        $league_competition->set_is_team_entry( false );
        $league_competition->set_standings( [] );

        $this->event->set_competition( $league_competition );
        $this->event->set_primary_league( 0 );

        $config = new stdClass();
        $config->name = 'Division 1';
        $config->type = 'singles';
        $config->num_sets = 5;

        $updated = $this->event->set_config( $config );

        $this->assertTrue( $updated );
        $this->assertArrayNotHasKey( 'primary_league', $this->event->settings );
    }

    public function test_set_config_preserves_existing_age_limit_scoring_offset_when_omitted(): void {
        $this->event->set_age_limit( '45' );
        $this->event->set_age_offset( '1' );
        $this->event->set_scoring( '3_sets' );
        $this->event->set_primary_league( 50 );

        $config = new stdClass();
        $config->name = 'Men\'s Singles';
        $config->type = 'singles';
        $config->num_sets = 5; // triggers update

        $updated = $this->event->set_config( $config );

        $this->assertTrue( $updated );
        $this->assertSame( '45', $this->event->age_limit );
        $this->assertSame( '1', $this->event->age_offset );
        $this->assertSame( '3_sets', $this->event->scoring );
        $this->assertSame( 50, $this->event->primary_league );
        $this->assertSame( '45', $this->event->settings['age_limit'] );
        $this->assertSame( '1', $this->event->settings['age_offset'] );
        $this->assertSame( '3_sets', $this->event->settings['scoring'] );
        $this->assertSame( 50, $this->event->settings['primary_league'] );
    }

    public function test_set_config_preserves_offset_for_non_tournament(): void {
        $this->event->competition->set_is_tournament( false );
        $this->event->set_offset( 3 );

        $config = new stdClass();
        $config->name = 'Men\'s Singles';
        $config->type = 'singles';
        $config->num_sets = 5;

        $updated = $this->event->set_config( $config );

        $this->assertTrue( $updated );
        $this->assertSame( 3, $this->event->offset );
        $this->assertSame( 3, $this->event->settings['offset'] );
    }

    public function test_get_primary_league_returns_null_for_unpersisted_event_without_id(): void {
        $event_raw                 = new stdClass();
        $event_raw->competition_id = 1;
        $event_raw->name           = 'Unpersisted Event';
        $event_raw->type           = 'singles';
        $event_raw->num_sets       = 3;
        $event_raw->num_rubbers    = 1;
        $event_raw->seasons        = '["2026"]';
        $event_raw->settings       = [];

        $event = Event::from_object( $event_raw, $this->competition );
        $event->set_id( null );
        $event->set_primary_league( 0 );
        $event->set_leagues( [] );

        $this->event_repository->expects( $this->never() )->method( 'query_leagues' );
        $this->league_repository->expects( $this->never() )->method( 'find_by_id' );

        $this->assertNull( $this->event_query_service->get_primary_league( $event ) );
        $this->assertNull( $this->event_query_service->get_primary_league_id( $event ) );
    }
}
