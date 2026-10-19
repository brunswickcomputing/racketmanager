<?php
declare(strict_types=1);

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Racketmanager\Admin\Controllers\Tournament_Event_Config_Admin_Controller;
use Racketmanager\Admin\View_Models\Tournament_Event_Config_Page_View_Model;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Settings;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\Season_Collection;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Event_Not_Found_Exception;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use stdClass;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final
class Tournament_Event_Config_Admin_Controller_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private $tournament_service;
    private $competition_service;
    private $guard;
    private $controller;
    private Simple_Container $container;
    private $event_repository;
    private $league_repository;
    private $league_service;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();

        $this->container           = new Simple_Container();
        $this->tournament_service  = $this->createStub( Tournament_Service::class );
        $this->competition_service = $this->createMock( Competition_Service::class );
        $this->guard               = $this->createStub( Action_Guard_Interface::class );
        $this->event_repository    = $this->createMock( Event_Repository_Interface::class );
        $this->league_repository   = $this->createMock( League_Repository_Interface::class );
        $this->league_service      = $this->createMock( League_Service::class );

        $this->container->set( 'competition_service', $this->competition_service );
        $this->container->set( 'event_repository', $this->event_repository );
        $this->container->set( 'league_repository', $this->league_repository );
        $this->container->set( 'league_service', $this->league_service );

        $racketmanager = $this->getMockBuilder( RacketManager::class )
            ->disableOriginalConstructor()
            ->getMock();
        $racketmanager->container = $this->container;
        $GLOBALS['racketmanager'] = $racketmanager;

        $this->controller = new Tournament_Event_Config_Admin_Controller(
            $this->tournament_service,
            $this->competition_service,
            $this->guard
        );
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_handle_returns_error_if_no_capability(): void {
        $this->guard->method( 'assert_capability' )->willThrowException( new \Exception( 'No cap' ) );

        $result = $this->controller->handle();

        self::assertEquals( 'No cap', $result['message'] );
    }

    public function test_handle_returns_view_model_for_display_new_event(): void {
        $_GET['competition_id'] = '123';
        $_GET['event_id'] = '999';

        $competition = $this->createStub( Competition::class );
        $this->competition_service->method( 'get_by_id' )->willReturn( $competition );
        $this->competition_service->method( 'get_event_by_id' )->willThrowException( new Event_Not_Found_Exception() );

        $result = $this->controller->handle();

        self::assertArrayHasKey( 'view_model', $result );
        self::assertTrue( $result['view_model']->new_event );
    }

    public function test_handle_returns_view_model_for_display_existing_event(): void {
        $_GET['competition_id'] = '123';
        $_GET['event_id'] = '456';

        $competition = $this->createStub( Competition::class );
        $event = $this->createStub( Event::class );
        $event->settings = [];
        $event->num_sets = 3;
        $event->num_rubbers = 6;

        $this->competition_service->method( 'get_by_id' )->willReturn( $competition );
        $this->competition_service->expects( self::once() )->method( 'get_event_by_id' )->with( 456 )->willReturn( $event );

        $result = $this->controller->handle();

        self::assertArrayHasKey( 'view_model', $result );
        self::assertFalse( $result['view_model']->new_event );
    }

    public function test_handle_add_creates_event_and_sets_primary_league_for_non_championship_tournament(): void {
        $_POST['addEventConfig'] = '1';
        $_POST['event_title']    = 'Men\'s Singles';
        $_POST['type']           = 'singles';
        $_POST['num_sets']       = '3';
        $_POST['num_rubbers']    = '1';
        $_GET['competition_id']  = '10';

        $competition = $this->createMock( Competition::class );
        $competition->id = 10;
        $competition->is_tournament = true;
        $competition->is_championship = false;
        $competition->is_player_entry = true;
        $competition->is_team_entry = false;
        $competition->is_league = false;
        $competition->type = Competition_Type::TOURNAMENT;
        $competition->settings = new Competition_Settings( [ 'mode' => 'tournament' ] );
        $competition->seasons = new Season_Collection( [ '2026' => [ 'name' => '2026', 'home_away' => 0, 'num_match_days' => 1, 'match_dates' => [] ] ] );
        $competition->standings = [];
        $competition->current_season = [ 'name' => '2026' ];

        $this->competition_service->method( 'get_by_id' )->willReturn( $competition );
        $this->competition_service->method( 'get_event_by_id' )->willThrowException( new Event_Not_Found_Exception() );

        $this->event_repository->expects( $this->once() )
            ->method( 'insert_event' )
            ->willReturn( 42 );

        // League service should only be called once to create primary league (no Plate)
        $league_obj     = $this->createMock( League::class );
        $league_obj->id = 789;
        $this->league_service->expects( $this->once() )
            ->method( 'add_league_to_event' )
            ->with( 42, 'Men\'s Singles' )
            ->willReturn( $league_obj );

        // update_settings_and_rules should be called with primary_league = 789
        $this->event_repository->expects( $this->once() )
            ->method( 'update_settings_and_rules' )
            ->with(
                42,
                $this->callback( function ( array $settings ): bool {
                    return isset( $settings['primary_league'] ) && 789 === $settings['primary_league'];
                } ),
                $this->anything(),
                $this->anything(),
                $this->anything()
            );

        $result = $this->controller->handle();

        $this->assertSame( 'Event added', $result['message'] );
        $this->assertSame( 'success', $result['message_type'] );
    }

    public function test_handle_add_creates_plate_league_for_championship_tournament(): void {
        $_POST['addEventConfig'] = '1';
        $_POST['event_title']    = 'Men\'s Doubles';
        $_POST['type']           = 'doubles';
        $_POST['num_sets']       = '3';
        $_POST['num_rubbers']    = '1';
        $_GET['competition_id']  = '10';

        $competition = $this->createMock( Competition::class );
        $competition->id = 10;
        $competition->is_tournament = true;
        $competition->is_championship = true;
        $competition->is_player_entry = true;
        $competition->is_team_entry = false;
        $competition->is_league = false;
        $competition->type = Competition_Type::TOURNAMENT;
        $competition->settings = new Competition_Settings( [ 'mode' => 'championship' ] );
        $competition->seasons = new Season_Collection( [ '2026' => [ 'name' => '2026', 'home_away' => 0, 'num_match_days' => 1, 'match_dates' => [] ] ] );
        $competition->standings = [];
        $competition->current_season = [ 'name' => '2026' ];

        $this->competition_service->method( 'get_by_id' )->willReturn( $competition );
        $this->competition_service->method( 'get_event_by_id' )->willThrowException( new Event_Not_Found_Exception() );

        $this->event_repository->expects( $this->once() )
            ->method( 'insert_event' )
            ->willReturn( 42 );

        // League service should be called twice: main league + Plate league
        $league_obj     = $this->createMock( League::class );
        $league_obj->id = 789;
        $plate_obj      = $this->createMock( League::class );
        $plate_obj->id  = 790;
        $this->league_service->expects( $this->exactly( 2 ) )
            ->method( 'add_league_to_event' )
            ->willReturnMap( [
                [ 42, 'Men\'s Doubles', $league_obj ],
                [ 42, 'Men\'s Doubles Plate', $plate_obj ],
            ] );

        $this->event_repository->expects( $this->once() )
            ->method( 'update_settings_and_rules' )
            ->with(
                42,
                $this->callback( function ( array $settings ): bool {
                    return isset( $settings['primary_league'] ) && 789 === $settings['primary_league'];
                } ),
                $this->anything(),
                $this->anything(),
                $this->anything()
            );

        $result = $this->controller->handle();

        $this->assertSame( 'Event added', $result['message'] );
        $this->assertSame( 'success', $result['message_type'] );
    }

    public function test_handle_update_preserves_primary_league_when_omitted_or_zero_in_post(): void {
        $_POST['updateEventConfig'] = '1';
        $_POST['event_id']          = '456';
        $_POST['event_title']       = 'Men\'s Singles';
        $_POST['type']              = 'singles';
        $_POST['num_sets']          = '5'; // changed from 3
        $_POST['primary_league']    = '0'; // 0 sent from empty select
        $_GET['competition_id']     = '10';
        $_GET['event_id']           = '456';

        $competition = $this->createMock( Competition::class );
        $competition->id = 10;
        $competition->is_tournament = true;
        $competition->is_championship = false;
        $competition->is_player_entry = true;
        $competition->is_team_entry = false;
        $competition->is_league = false;
        $competition->type = Competition_Type::TOURNAMENT;
        $competition->settings = new Competition_Settings( [ 'mode' => 'tournament' ] );
        $competition->seasons = new Season_Collection( [ '2026' => [ 'name' => '2026', 'home_away' => 0, 'num_match_days' => 1, 'match_dates' => [] ] ] );
        $competition->standings = [];
        $competition->current_season = [ 'name' => '2026' ];

        $this->competition_service->method( 'get_by_id' )->willReturn( $competition );

        $event_raw                 = new stdClass();
        $event_raw->id             = 456;
        $event_raw->competition_id = 10;
        $event_raw->name           = 'Men\'s Singles';
        $event_raw->type           = 'singles';
        $event_raw->num_sets       = 3;
        $event_raw->num_rubbers    = 1;
        $event_raw->seasons        = '["2026"]';
        $event_raw->settings       = [ 'primary_league' => 555 ];

        $event = new Event( $event_raw );
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event );

        $this->event_repository->expects( $this->once() )
            ->method( 'update_settings_and_rules' )
            ->with(
                456,
                $this->callback( function ( array $settings ): bool {
                    return isset( $settings['primary_league'] ) && 555 === $settings['primary_league'];
                } ),
                $this->anything(),
                $this->anything(),
                $this->anything()
            );

        $result = $this->controller->handle();

        $this->assertSame( 'Event updated', $result['message'] );
        $this->assertSame( 'success', $result['message_type'] );
        $this->assertSame( 555, $event->primary_league );
    }

    public function test_handle_update_heals_primary_league_when_legacy_is_zero_and_league_query_resolves_id(): void {
        $_POST['updateEventConfig'] = '1';
        $_POST['event_id']          = '456';
        $_POST['event_title']       = 'Men\'s Singles';
        $_POST['type']              = 'singles';
        $_POST['num_sets']          = '5';
        $_POST['primary_league']    = '0';
        $_GET['competition_id']     = '10';
        $_GET['event_id']           = '456';

        $competition = $this->createMock( Competition::class );
        $competition->id = 10;
        $competition->is_tournament = true;
        $competition->is_championship = false;
        $competition->is_player_entry = true;
        $competition->is_team_entry = false;
        $competition->is_league = false;
        $competition->type = Competition_Type::TOURNAMENT;
        $competition->settings = new Competition_Settings( [ 'mode' => 'tournament' ] );
        $competition->seasons = new Season_Collection( [ '2026' => [ 'name' => '2026', 'home_away' => 0, 'num_match_days' => 1, 'match_dates' => [] ] ] );
        $competition->standings = [];
        $competition->current_season = [ 'name' => '2026' ];

        $this->competition_service->method( 'get_by_id' )->willReturn( $competition );

        $event_raw                 = new stdClass();
        $event_raw->id             = 456;
        $event_raw->competition_id = 10;
        $event_raw->name           = 'Men\'s Singles';
        $event_raw->type           = 'singles';
        $event_raw->num_sets       = 3;
        $event_raw->num_rubbers    = 1;
        $event_raw->seasons        = '["2026"]';
        $event_raw->settings       = [];

        $event = new Event( $event_raw );
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event );

        $this->event_repository->expects( $this->atLeastOnce() )->method( 'query_leagues' )
            ->willReturn( [ (object) [ 'id' => 321 ] ] );

        $this->event_repository->expects( $this->once() )
            ->method( 'update_settings_and_rules' )
            ->with(
                456,
                $this->callback( function ( array $settings ): bool {
                    return isset( $settings['primary_league'] ) && 321 === $settings['primary_league'];
                } ),
                $this->anything(),
                $this->anything(),
                $this->anything()
            );

        $result = $this->controller->handle();

        $this->assertSame( 'Event updated', $result['message'] );
        $this->assertSame( 'success', $result['message_type'] );
        $this->assertSame( 321, $event->primary_league );
    }

    public function test_handle_update_sets_primary_league_when_positive_value_in_post(): void {
        $_POST['updateEventConfig'] = '1';
        $_POST['event_id']          = '456';
        $_POST['event_title']       = 'Men\'s Singles';
        $_POST['type']              = 'singles';
        $_POST['num_sets']          = '5';
        $_POST['primary_league']    = '777';
        $_GET['competition_id']     = '10';
        $_GET['event_id']           = '456';

        $competition = $this->createMock( Competition::class );
        $competition->id = 10;
        $competition->is_tournament = true;
        $competition->is_championship = false;
        $competition->is_player_entry = true;
        $competition->is_team_entry = false;
        $competition->is_league = false;
        $competition->type = Competition_Type::TOURNAMENT;
        $competition->settings = new Competition_Settings( [ 'mode' => 'tournament' ] );
        $competition->seasons = new Season_Collection( [ '2026' => [ 'name' => '2026', 'home_away' => 0, 'num_match_days' => 1, 'match_dates' => [] ] ] );
        $competition->standings = [];
        $competition->current_season = [ 'name' => '2026' ];

        $this->competition_service->method( 'get_by_id' )->willReturn( $competition );

        $event_raw                 = new stdClass();
        $event_raw->id             = 456;
        $event_raw->competition_id = 10;
        $event_raw->name           = 'Men\'s Singles';
        $event_raw->type           = 'singles';
        $event_raw->num_sets       = 3;
        $event_raw->num_rubbers    = 1;
        $event_raw->seasons        = '["2026"]';
        $event_raw->settings       = [ 'primary_league' => 555 ];

        $event = new Event( $event_raw );
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event );

        $this->event_repository->expects( $this->once() )
            ->method( 'update_settings_and_rules' )
            ->with(
                456,
                $this->callback( function ( array $settings ): bool {
                    return isset( $settings['primary_league'] ) && 777 === $settings['primary_league'];
                } ),
                $this->anything(),
                $this->anything(),
                $this->anything()
            );

        $result = $this->controller->handle();

        $this->assertSame( 'Event updated', $result['message'] );
        $this->assertSame( 'success', $result['message_type'] );
        $this->assertSame( 777, $event->primary_league );
    }

    public function test_handle_add_returns_error_when_event_name_already_exists(): void {
        $_POST['addEventConfig'] = '1';
        $_POST['event_id']       = '';
        $_POST['event_title']    = 'Men\'s Singles';
        $_POST['type']           = 'singles';
        $_POST['num_sets']       = '3';
        $_GET['competition_id']  = '10';

        $existing_event       = new stdClass();
        $existing_event->name = 'Men\'s Singles';

        $competition = $this->createMock( Competition::class );
        $competition->id = 10;
        $competition->is_tournament = true;
        $competition->is_championship = false;
        $competition->type = Competition_Type::TOURNAMENT;
        $competition->settings = new Competition_Settings( [ 'mode' => 'tournament' ] );
        $competition->seasons = new Season_Collection( [ '2026' => [ 'name' => '2026', 'home_away' => 0, 'num_match_days' => 1, 'match_dates' => [] ] ] );
        $competition->standings = [];
        $competition->current_season = [ 'name' => '2026' ];
        $competition->method( 'get_events' )->willReturn( [ $existing_event ] );

        $this->competition_service->method( 'get_by_id' )->willReturn( $competition );
        $this->competition_service->method( 'get_event_by_id' )->willThrowException( new Event_Not_Found_Exception() );

        $result = $this->controller->handle();

        $this->assertSame( 'Event already exists', $result['message'] );
        $this->assertTrue( $result['message_type'] );
    }
}
