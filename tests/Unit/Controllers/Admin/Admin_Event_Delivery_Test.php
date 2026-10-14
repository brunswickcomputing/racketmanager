<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Admin_Event;
use Racketmanager\Admin\Flash\Admin_Flash_Message_Store;
use Racketmanager\Admin\View_Models\Tournament_Event_Config_Page_View_Model;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Action_Adapter;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Redirector_Interface;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Screen_Presenter;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Services\Admin\Admin_Message_Service;
use Racketmanager\Services\Admin\Event\Event_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Season_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Services\View\View_Renderer_Interface;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Admin_Event_Delivery_Test extends TestCase {

    private ?object $original_racketmanager;
    private mixed $original_wpdb = null;
    private mixed $original_request_method = null;
    private Simple_Container $container;
    private Admin_Redirector_Interface $redirector;
    private View_Renderer_Interface $renderer;
    private Admin_Flash_Message_Store $flash_store;
    private Action_Guard_Interface $guard;
    private Competition_Service $competition_service;
    private Admin_Event $screen;

    protected function setUp(): void {
        parent::setUp();
        $this->original_racketmanager         = $GLOBALS['racketmanager'] ?? null;
        $this->original_wpdb                  = $GLOBALS['wpdb'] ?? null;
        $this->original_request_method        = $_SERVER['REQUEST_METHOD'] ?? null;
        $GLOBALS['wpdb']                      = new class() {
            public int $insert_id = 5;
            public string $racketmanager_events = 'events';
            public string $racketmanager = 'leagues';
            public function prepare( string $q, mixed ...$a ): string {
                return $q;
            }
            public function query( string $q ): int {
                return 1;
            }
            public function get_var( string $q ): int {
                return 0;
            }
        };
        $GLOBALS['wp_stubs_current_user_can'] = true;
        $_GET  = array();
        $_POST = array();

        $this->redirector          = $this->createMock( Admin_Redirector_Interface::class );
        $this->renderer            = $this->createMock( View_Renderer_Interface::class );
        $this->flash_store         = $this->createMock( Admin_Flash_Message_Store::class );
        $this->guard               = $this->createMock( Action_Guard_Interface::class );
        $this->competition_service = $this->createMock( Competition_Service::class );

        $message_service = new Admin_Message_Service( $this->flash_store );

        $this->container = new Simple_Container();
        $this->container->set( 'competition_service', fn() => $this->competition_service );
        $this->container->set( 'finance_service', fn() => $this->createStub( Finance_Service::class ) );
        $this->container->set( 'season_service', fn() => $this->createStub( Season_Service::class ) );
        $this->container->set( 'club_service', fn() => $this->createStub( Club_Service::class ) );
        $this->container->set( 'player_service', fn() => $this->createStub( Player_Service::class ) );
        $this->container->set( 'registration_service', fn() => $this->createStub( Registration_Service::class ) );
        $this->container->set( 'team_service', fn() => $this->createStub( Team_Service::class ) );
        $league_service = $this->createStub( League_Service::class );
        $league_service->method( 'add_league_to_event' )->willReturn( $this->make_league() );
        $this->container->set( 'league_service', fn() => $league_service );
        $this->container->set( 'tournament_service', fn() => $this->createStub( Tournament_Service::class ) );
        $this->container->set( 'fixture_repository', fn() => $this->createStub( Fixture_Repository_Interface::class ) );
        $this->container->set( 'admin_action_adapter', fn() => new Admin_Action_Adapter( $message_service, $this->redirector ) );
        $this->container->set( 'admin_screen_presenter', fn() => new Admin_Screen_Presenter( $this->renderer, $message_service ) );
        $this->container->set( 'admin_message_service', fn() => $message_service );

        $this->container->set( 'league_contact_action_dispatcher', fn() => new \Racketmanager\Services\Admin\League\League_Contact_Action_Dispatcher( $this->createStub( League_Service::class ), $this->createStub( Competition_Service::class ), $this->guard ) );
        $this->container->set( 'league_setup_action_dispatcher', fn() => new \Racketmanager\Services\Admin\League\League_Setup_Action_Dispatcher( $this->createStub( Competition_Service::class ), $this->guard ) );
        $this->container->set( 'event_setup_action_dispatcher', fn() => new \Racketmanager\Services\Admin\League\Event_Setup_Action_Dispatcher( $this->createStub( Competition_Service::class ), $this->guard ) );
        $this->container->set( 'season_delete_action_dispatcher', fn() => new \Racketmanager\Services\Admin\League\Season_Delete_Action_Dispatcher( $this->createStub( Competition_Service::class ), $this->guard ) );
        $this->container->set( 'league_team_action_dispatcher', fn() => new \Racketmanager\Services\Admin\League\League_Team_Action_Dispatcher( $this->createStub( League_Service::class ), $this->guard ) );
        $this->container->set( 'league_match_action_dispatcher', fn() => new \Racketmanager\Services\Admin\League\League_Match_Action_Dispatcher( $this->createStub( Fixture_Repository_Interface::class ), $this->createStub( \Racketmanager\Services\Fixture\Fixture_Maintenance_Service::class ), $this->guard ) );
        $this->container->set( 'league_constitution_action_dispatcher', fn() => new \Racketmanager\Services\Admin\League\League_Constitution_Action_Dispatcher( $this->guard ) );
        $this->container->set( 'league_event_action_dispatcher', fn() => new \Racketmanager\Services\Admin\League\League_Event_Action_Dispatcher( $this->createStub( League_Service::class ), $this->guard ) );
        $this->container->set( 'league_schedule_action_dispatcher', fn() => new \Racketmanager\Services\Admin\League\League_Schedule_Action_Dispatcher( new \Racketmanager\Services\Admin\League\League_Schedule_Service( $this->createStub( \Racketmanager\Services\Fixture\Fixture_Maintenance_Service::class ) ), $this->guard ) );
        $this->container->set( 'club_action_dispatcher', fn() => new \Racketmanager\Services\Admin\Club\Club_Action_Dispatcher( $this->createStub( Club_Service::class ), $this->createStub( Player_Service::class ), $this->guard ) );
        $this->container->set( 'club_player_action_dispatcher', fn() => new \Racketmanager\Services\Admin\Club\Club_Player_Action_Dispatcher( $this->createStub( Registration_Service::class ), $this->createStub( Player_Service::class ), $this->guard ) );
        $this->container->set( 'club_team_action_dispatcher', fn() => new \Racketmanager\Services\Admin\Club\Club_Team_Action_Dispatcher( $this->createStub( Club_Service::class ), $this->createStub( Team_Service::class ), $this->guard ) );
        $this->container->set( 'club_role_action_dispatcher', fn() => new \Racketmanager\Services\Admin\Club\Club_Role_Action_Dispatcher( $this->createStub( Club_Service::class ), $this->guard ) );
        $this->container->set( 'season_action_dispatcher', fn() => new \Racketmanager\Services\Admin\Season\Season_Action_Dispatcher( $this->createStub( Season_Service::class ), $this->guard ) );

        $this->container->set( 'event_action_dispatcher', fn() => new Event_Action_Dispatcher( $this->competition_service, $this->guard ) );

        $racketmanager            = $this->createStub( RacketManager::class );
        $racketmanager->container = $this->container;
        $GLOBALS['racketmanager'] = $racketmanager;

        $this->screen = new Admin_Event( $racketmanager );
    }

    private function make_league(): \Racketmanager\Domain\Competition\League {
        $league     = $this->createStub( \Racketmanager\Domain\Competition\League::class );
        $league->id = 7;
        return $league;
    }

    protected function tearDown(): void {
        if ( null !== $this->original_wpdb ) {
            $GLOBALS['wpdb'] = $this->original_wpdb;
        } else {
            unset( $GLOBALS['wpdb'] );
        }
        if ( null !== $this->original_request_method ) {
            $_SERVER['REQUEST_METHOD'] = $this->original_request_method;
        } else {
            unset( $_SERVER['REQUEST_METHOD'] );
        }
        $_GET  = array();
        $_POST = array();
        if ( null !== $this->original_racketmanager ) {
            $GLOBALS['racketmanager'] = $this->original_racketmanager;
        } else {
            unset( $GLOBALS['racketmanager'] );
        }
        parent::tearDown();
    }

    public function test_post_add_event_config_redirects_and_sets_flash(): void {
        $competition                  = $this->createStub( Competition::class );
        $competition->id              = 1;
        $competition->is_team_entry   = false;
        $competition->is_tournament   = false;
        $competition->is_championship = false;
        $competition->settings = new \Racketmanager\Domain\Competition\Competition_Settings( array( 'mode' => 'league' ) );
        $competition->current_season  = array( 'name' => '2025', 'home_away' => true, 'num_match_days' => 10, 'match_dates' => array() );
        $competition->method( 'get_seasons' )->willReturn( array() );

        $this->guard->expects( self::once() )->method( 'assert_allowed' );
        $this->competition_service->expects( self::atLeastOnce() )
            ->method( 'get_by_id' )
            ->with( 1 )
            ->willReturn( $competition );

        $this->flash_store->expects( self::once() )
            ->method( 'set' )
            ->with( 'Event added', false, self::isArray() );

        $this->redirector->expects( self::once() )
            ->method( 'redirect' )
            ->with(
                self::logicalAnd(
                    self::stringContains( 'page=racketmanager-leagues' ),
                    self::stringContains( 'event_id=5' ),
                    self::stringContains( 'view=event-config' )
                )
            );

        $this->renderer->expects( self::never() )->method( 'render' );

        $_GET['competition_id'] = '1';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = array(
            'addEventConfig' => '1',
            'event_name'     => 'Men Singles',
            'type'           => 'singles',
            'age_limit'      => 'open',
            'age_offset'     => '0',
            'scoring'        => 'standard',
            'num_sets'       => '3',
            'offset'         => '0',
        );

        $this->screen->display_config_page();
    }

    public function test_get_config_page_renders_view_model(): void {
        $competition                  = $this->createStub( Competition::class );
        $competition->id              = 1;
        $competition->name            = 'Summer League';
        $competition->is_tournament   = false;
        $competition->is_championship = false;
        $competition->settings = new \Racketmanager\Domain\Competition\Competition_Settings( array( 'mode' => 'league' ) );
        $competition->method( 'get_type' )->willReturn( 'league' );

        $event              = $this->createStub( Event::class );
        $event->id          = 10;
        $event->name        = 'Men Singles';
        $event->settings    = array();
        $event->num_sets    = 3;
        $event->num_rubbers = 4;

        $this->competition_service->expects( self::once() )
            ->method( 'get_by_id' )
            ->with( 1 )
            ->willReturn( $competition );

        $this->competition_service->expects( self::once() )
            ->method( 'get_event_by_id' )
            ->with( 10 )
            ->willReturn( $event );

        $this->flash_store->expects( self::once() )
            ->method( 'pop' )
            ->willReturn( array() );

        $this->renderer->expects( self::once() )
            ->method( 'render' )
            ->with(
                'admin/includes/event-config',
                self::isInstanceOf( Tournament_Event_Config_Page_View_Model::class )
            );

        $_GET['competition_id'] = '1';
        $_GET['event_id']       = '10';

        $this->screen->display_config_page();
    }

    public function test_post_event_guard_failure_redirects_and_prevents_mutation(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->willThrowException( new Invalid_Status_Exception( 'Unauthorized' ) );

        $this->competition_service->expects( self::never() )->method( 'get_by_id' );

        $this->flash_store->expects( self::once() )
            ->method( 'set' )
            ->with( 'Unauthorized', true, self::isArray() );

        $this->redirector->expects( self::once() )
            ->method( 'redirect' )
            ->with(
                self::logicalAnd(
                    self::stringContains( 'page=racketmanager-leagues' ),
                    self::stringContains( 'competition_id=1' )
                )
            );

        $_GET['competition_id'] = '1';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = array(
            'addEventConfig' => '1',
            'event_name'     => 'Men Singles',
        );

        $this->screen->display_config_page();
    }
}
