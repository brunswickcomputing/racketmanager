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
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use stdClass;

require_once dirname( __DIR__, 3 ) . '/wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
class Event_Primary_League_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private Simple_Container $container;
    private $event_repository;
    private $league_repository;
    private Event $event;
    private Competition $competition;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();

        $this->container        = new Simple_Container();
        $this->event_repository = $this->createMock( Event_Repository_Interface::class );
        $this->league_repository = $this->createMock( League_Repository_Interface::class );

        $this->container->set( 'event_repository', $this->event_repository );
        $this->container->set( 'league_repository', $this->league_repository );

        $competition_service = $this->createMock( Competition_Service::class );
        $this->container->set( 'competition_service', $competition_service );

        $this->competition = $this->createMock( Competition::class );
        $competition_settings = new Competition_Settings( [ 'mode' => 'tournament' ] );
        $this->competition->settings = $competition_settings;
        $this->competition->type = Competition_Type::TOURNAMENT;
        $this->competition->is_tournament = true;
        $this->competition->is_championship = false;
        $this->competition->is_player_entry = true;
        $this->competition->standings = [];
        $this->competition->current_season = [ 'name' => '2026' ];
        $competition_service->method( 'get_by_id' )->willReturn( $this->competition );

        $racketmanager = $this->getMockBuilder( RacketManager::class )
            ->disableOriginalConstructor()
            ->getMock();
        $racketmanager->container = $this->container;
        $GLOBALS['racketmanager'] = $racketmanager;

        $event_raw                 = new stdClass();
        $event_raw->id             = 42;
        $event_raw->competition_id = 1;
        $event_raw->name           = 'Men\'s Singles';
        $event_raw->type           = 'singles';
        $event_raw->num_sets       = 3;
        $event_raw->num_rubbers    = 1;
        $event_raw->seasons        = '["2026"]';
        $event_raw->settings       = [];

        $this->event = new Event( $event_raw );
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_get_primary_league_id_returns_explicit_primary_league(): void {
        $this->event->primary_league = 101;
        $this->assertSame( 101, $this->event->get_primary_league_id() );
    }

    public function test_get_primary_league_id_falls_back_to_in_memory_leagues_array(): void {
        $this->event->primary_league = 0;

        $league = new stdClass();
        $league->id = 55;
        $this->event->leagues = [ $league ];

        $this->assertSame( 55, $this->event->get_primary_league_id() );
    }

    public function test_get_primary_league_id_lazily_loads_from_repository(): void {
        $this->event->primary_league = 0;
        $this->event->leagues = [];

        $league_db_record = (object) [ 'id' => 77 ];
        $this->event_repository->expects( $this->once() )
            ->method( 'query_leagues' )
            ->with( 42, $this->anything() )
            ->willReturn( [ $league_db_record ] );

        $this->assertSame( 77, $this->event->get_primary_league_id() );
    }

    public function test_get_primary_league_id_returns_null_when_no_leagues_exist(): void {
        $this->event->primary_league = 0;
        $this->event->leagues = [];

        $this->event_repository->expects( $this->once() )
            ->method( 'query_leagues' )
            ->with( 42, $this->anything() )
            ->willReturn( [] );

        $this->assertNull( $this->event->get_primary_league_id() );
    }

    public function test_get_primary_league_returns_league_instance(): void {
        $this->event->primary_league = 88;

        $league = $this->createMock( League::class );
        $league->id = 88;
        $this->event->leagues = [ $league ];
        $this->event->league_index = [ 88 => 0 ];

        $result = $this->event->get_primary_league();
        $this->assertSame( $league, $result );
    }

    public function test_get_primary_league_returns_null_when_primary_id_is_null(): void {
        $this->event->primary_league = 0;
        $this->event->leagues = [];

        $this->event_repository->method( 'query_leagues' )->willReturn( [] );

        $this->assertNull( $this->event->get_primary_league() );
    }

    public function test_set_config_persists_primary_league_for_tournament_competition(): void {
        $this->event_repository->expects( $this->once() )
            ->method( 'update_settings_and_rules' )
            ->with(
                42,
                $this->callback( function ( array $settings ): bool {
                    return isset( $settings['primary_league'] ) && 99 === $settings['primary_league'];
                } ),
                $this->anything(),
                $this->anything(),
                $this->anything()
            );

        $config = new stdClass();
        $config->name = 'Men\'s Singles';
        $config->type = 'singles';
        $config->num_sets = 3;
        $config->primary_league = 99;

        $updated = $this->event->set_config( $config );

        $this->assertTrue( $updated );
        $this->assertSame( 99, $this->event->primary_league );
    }

    public function test_set_config_preserves_existing_primary_league_when_zero_or_empty_passed(): void {
        $this->event->primary_league = 123;

        $this->event_repository->expects( $this->once() )
            ->method( 'update_settings_and_rules' )
            ->with(
                42,
                $this->callback( function ( array $settings ): bool {
                    return isset( $settings['primary_league'] ) && 123 === $settings['primary_league'];
                } ),
                $this->anything(),
                $this->anything(),
                $this->anything()
            );

        $config = new stdClass();
        $config->name = 'Men\'s Singles';
        $config->type = 'singles';
        $config->num_sets = 5;
        $config->primary_league = 0; // Empty/zero select option

        $updated = $this->event->set_config( $config );

        $this->assertTrue( $updated );
        $this->assertSame( 123, $this->event->primary_league );
    }

    public function test_set_config_does_not_write_primary_league_for_standard_league_competition_when_not_passed(): void {
        $league_competition = $this->createMock( Competition::class );
        $league_competition->type = Competition_Type::LEAGUE;
        $league_competition->is_tournament = false;
        $league_competition->is_championship = false;
        $league_competition->is_league = true;
        $league_competition->is_player_entry = false;
        $league_competition->is_team_entry = false;
        $league_competition->standings = [];
        $league_competition->current_season = [ 'name' => '2026' ];

        $this->event->competition = $league_competition;
        $this->event->primary_league = 0;

        $this->event_repository->expects( $this->once() )
            ->method( 'update_settings_and_rules' )
            ->with(
                42,
                $this->callback( function ( array $settings ): bool {
                    return ! array_key_exists( 'primary_league', $settings );
                } ),
                $this->anything(),
                $this->anything(),
                $this->anything()
            );

        $config = new stdClass();
        $config->name = 'Division 1';
        $config->type = 'singles';
        $config->num_sets = 5;

        $updated = $this->event->set_config( $config );

        $this->assertTrue( $updated );
    }

    public function test_set_config_preserves_existing_age_limit_scoring_offset_when_omitted(): void {
        $this->event->age_limit = '45';
        $this->event->age_offset = '1';
        $this->event->scoring = '3_sets';
        $this->event->primary_league = 50;

        $this->event_repository->expects( $this->once() )
            ->method( 'update_settings_and_rules' )
            ->with(
                42,
                $this->callback( function ( array $settings ): bool {
                    return $settings['age_limit'] === '45'
                        && $settings['age_offset'] === '1'
                        && $settings['scoring'] === '3_sets'
                        && $settings['primary_league'] === 50;
                } ),
                $this->anything(),
                $this->anything(),
                $this->anything()
            );

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
    }

    public function test_set_config_preserves_offset_for_non_tournament(): void {
        $this->event->competition->is_tournament = false;
        $this->event->offset = 3;

        $this->event_repository->expects( $this->once() )
            ->method( 'update_settings_and_rules' )
            ->with(
                42,
                $this->callback( function ( array $settings ): bool {
                    return isset( $settings['offset'] ) && 3 === $settings['offset'];
                } ),
                $this->anything(),
                $this->anything(),
                $this->anything()
            );

        $config = new stdClass();
        $config->name = 'Men\'s Singles';
        $config->type = 'singles';
        $config->num_sets = 5;

        $updated = $this->event->set_config( $config );

        $this->assertTrue( $updated );
        $this->assertSame( 3, $this->event->offset );
    }
}
