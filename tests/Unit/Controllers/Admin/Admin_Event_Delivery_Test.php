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
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Redirector_Interface;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\RacketManager;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\View\View_Renderer_Interface;
use Racketmanager\Tests\Helpers\Creates_Admin_Delivery_Container;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

#[AllowMockObjectsWithoutExpectations]
final class Admin_Event_Delivery_Test extends TestCase {
    use Creates_Admin_Delivery_Container;
    use Manages_Wordpress_Globals;

    private Simple_Container $container;
    private Admin_Redirector_Interface $redirector;
    private View_Renderer_Interface $renderer;
    private Admin_Flash_Message_Store $flash_store;
    private Action_Guard_Interface $guard;
    private Competition_Service $competition_service;
    private Event_Repository_Interface $event_repository;
    private Admin_Event $screen;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
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

        $this->redirector          = $this->createMock( Admin_Redirector_Interface::class );
        $this->renderer            = $this->createMock( View_Renderer_Interface::class );
        $this->flash_store         = $this->createMock( Admin_Flash_Message_Store::class );
        $this->guard               = $this->createMock( Action_Guard_Interface::class );
        $this->competition_service = $this->createMock( Competition_Service::class );
        $this->event_repository    = $this->createMock( Event_Repository_Interface::class );

        $league_service = $this->createStub( League_Service::class );
        $league_service->method( 'add_league_to_event' )->willReturn( $this->make_league() );

        $this->container = $this->create_admin_delivery_container(
            services: array(
                'competition_service' => $this->competition_service,
                'league_service'      => $league_service,
                'event_repository'    => $this->event_repository,
            ),
            guard: $this->guard,
            redirector: $this->redirector,
            renderer: $this->renderer,
            flash_store: $this->flash_store,
        );

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
        $this->restore_globals();
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
        $this->event_repository->expects( self::once() )
            ->method( 'insert_event' )
            ->willReturn( 5 );

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
