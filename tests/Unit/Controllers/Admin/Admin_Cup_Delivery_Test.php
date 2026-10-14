<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Admin_Cup;
use Racketmanager\Admin\Flash\Admin_Flash_Message_Store;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Domain\DTO\Admin\Championship\Draw_Action_Response_DTO;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Action_Adapter;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Redirector_Interface;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Screen_Presenter;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Services\Admin\Admin_Message_Service;
use Racketmanager\Services\Admin\Championship\Draw_Action_Dispatcher;
use Racketmanager\Services\Admin\Championship\Draw_Action_Handler_Interface;
use Racketmanager\Services\Admin\Club\Club_Action_Dispatcher;
use Racketmanager\Services\Admin\Club\Club_Player_Action_Dispatcher;
use Racketmanager\Services\Admin\Club\Club_Role_Action_Dispatcher;
use Racketmanager\Services\Admin\Club\Club_Team_Action_Dispatcher;
use Racketmanager\Services\Admin\Competition\Competition_Action_Dispatcher;
use Racketmanager\Services\Admin\Competition\Competition_Season_Action_Dispatcher;
use Racketmanager\Services\Admin\Cup\Cup_Plan_Action_Dispatcher;
use Racketmanager\Services\Admin\Cup\Cup_Setup_Action_Dispatcher;
use Racketmanager\Services\Admin\Event\Event_Action_Dispatcher;
use Racketmanager\Services\Admin\Finance\Finance_Charge_Action_Dispatcher;
use Racketmanager\Services\Admin\Finance\Finance_Invoice_Action_Dispatcher;
use Racketmanager\Services\Admin\League\Event_Setup_Action_Dispatcher;
use Racketmanager\Services\Admin\League\League_Constitution_Action_Dispatcher;
use Racketmanager\Services\Admin\League\League_Contact_Action_Dispatcher;
use Racketmanager\Services\Admin\League\League_Event_Action_Dispatcher;
use Racketmanager\Services\Admin\League\League_Match_Action_Dispatcher;
use Racketmanager\Services\Admin\League\League_Schedule_Action_Dispatcher;
use Racketmanager\Services\Admin\League\League_Schedule_Service;
use Racketmanager\Services\Admin\League\League_Setup_Action_Dispatcher;
use Racketmanager\Services\Admin\League\League_Team_Action_Dispatcher;
use Racketmanager\Services\Admin\League\Season_Delete_Action_Dispatcher;
use Racketmanager\Services\Admin\Player\Player_Action_Dispatcher;
use Racketmanager\Services\Admin\Player\Player_Error_Action_Dispatcher;
use Racketmanager\Services\Admin\Player\Player_Request_Action_Dispatcher;
use Racketmanager\Services\Admin\Season\Season_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Fixture\Fixture_Maintenance_Service;
use Racketmanager\Services\Fixture_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Season_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Services\View\View_Renderer_Interface;
use stdClass;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Admin_Cup_Delivery_Test extends TestCase {

    private Simple_Container $container;
    private Admin_Redirector_Interface $redirector;
    private View_Renderer_Interface $renderer;
    private Admin_Flash_Message_Store $flash_store;
    private Action_Guard_Interface $guard;
    private Competition_Service $competition_service;
    private Cup_Setup_Action_Dispatcher $cup_setup_dispatcher;
    private Cup_Plan_Action_Dispatcher $cup_plan_dispatcher;
    private Draw_Action_Dispatcher $draw_action_dispatcher;
    private Admin_Cup $screen;
    private ?string $original_method;
    private mixed $previous_racketmanager = null;
    private mixed $previous_capability    = null;

    protected function setUp(): void {
        parent::setUp();
        $this->original_method        = $_SERVER['REQUEST_METHOD'] ?? null;
        $this->previous_racketmanager = $GLOBALS['racketmanager'] ?? null;
        $this->previous_capability    = $GLOBALS['wp_stubs_current_user_can'] ?? null;
        $GLOBALS['wp_stubs_current_user_can'] = true;
        $_GET                         = array();
        $_POST                        = array();

        $this->redirector             = $this->createMock( Admin_Redirector_Interface::class );
        $this->renderer               = $this->createMock( View_Renderer_Interface::class );
        $this->flash_store            = $this->createMock( Admin_Flash_Message_Store::class );
        $this->guard                  = $this->createMock( Action_Guard_Interface::class );
        $this->competition_service    = $this->createMock( Competition_Service::class );
        $this->cup_setup_dispatcher   = $this->createMock( Cup_Setup_Action_Dispatcher::class );
        $this->cup_plan_dispatcher    = $this->createMock( Cup_Plan_Action_Dispatcher::class );
        $this->draw_action_dispatcher = $this->createMock( Draw_Action_Dispatcher::class );

        $message_service = new Admin_Message_Service( $this->flash_store );

        $this->container = new Simple_Container();
        $this->container->set( 'club_service', fn() => $this->createStub( Club_Service::class ) );
        $this->container->set( 'player_service', fn() => $this->createStub( Player_Service::class ) );
        $this->container->set( 'registration_service', fn() => $this->createStub( Registration_Service::class ) );
        $this->container->set( 'team_service', fn() => $this->createStub( Team_Service::class ) );
        $this->container->set( 'league_service', fn() => $this->createStub( League_Service::class ) );
        $this->container->set( 'competition_service', fn() => $this->competition_service );
        $this->container->set( 'finance_service', fn() => $this->createStub( Finance_Service::class ) );
        $this->container->set( 'season_service', fn() => $this->createStub( Season_Service::class ) );
        $this->container->set( 'tournament_service', fn() => $this->createStub( Tournament_Service::class ) );
        $this->container->set( 'fixture_repository', fn() => $this->createStub( Fixture_Repository_Interface::class ) );
        $this->container->set( 'admin_action_adapter', fn() => new Admin_Action_Adapter( $message_service, $this->redirector ) );
        $this->container->set( 'admin_screen_presenter', fn() => new Admin_Screen_Presenter( $this->renderer, $message_service ) );
        $this->container->set( 'admin_message_service', fn() => $message_service );

        $this->container->set( 'league_contact_action_dispatcher', fn() => new League_Contact_Action_Dispatcher( $this->createStub( League_Service::class ), $this->createStub( Competition_Service::class ), $this->guard ) );
        $this->container->set( 'league_setup_action_dispatcher', fn() => new League_Setup_Action_Dispatcher( $this->createStub( Competition_Service::class ), $this->guard ) );
        $this->container->set( 'event_setup_action_dispatcher', fn() => new Event_Setup_Action_Dispatcher( $this->createStub( Competition_Service::class ), $this->guard ) );
        $this->container->set( 'season_delete_action_dispatcher', fn() => new Season_Delete_Action_Dispatcher( $this->createStub( Competition_Service::class ), $this->guard ) );
        $this->container->set( 'league_team_action_dispatcher', fn() => new League_Team_Action_Dispatcher( $this->createStub( League_Service::class ), $this->guard ) );
        $this->container->set( 'league_match_action_dispatcher', fn() => new League_Match_Action_Dispatcher( $this->createStub( Fixture_Repository_Interface::class ), $this->createStub( Fixture_Maintenance_Service::class ), $this->guard ) );
        $this->container->set( 'league_constitution_action_dispatcher', fn() => new League_Constitution_Action_Dispatcher( $this->guard ) );
        $this->container->set( 'league_event_action_dispatcher', fn() => new League_Event_Action_Dispatcher( $this->createStub( League_Service::class ), $this->guard ) );
        $this->container->set( 'league_schedule_action_dispatcher', fn() => new League_Schedule_Action_Dispatcher( new League_Schedule_Service( $this->createStub( Fixture_Maintenance_Service::class ) ), $this->guard ) );

        $this->container->set( 'club_action_dispatcher', fn() => new Club_Action_Dispatcher( $this->createStub( Club_Service::class ), $this->createStub( Player_Service::class ), $this->guard ) );
        $this->container->set( 'club_player_action_dispatcher', fn() => new Club_Player_Action_Dispatcher( $this->createStub( Registration_Service::class ), $this->createStub( Player_Service::class ), $this->guard ) );
        $this->container->set( 'club_team_action_dispatcher', fn() => new Club_Team_Action_Dispatcher( $this->createStub( Club_Service::class ), $this->createStub( Team_Service::class ), $this->guard ) );
        $this->container->set( 'club_role_action_dispatcher', fn() => new Club_Role_Action_Dispatcher( $this->createStub( Club_Service::class ), $this->guard ) );
        $this->container->set( 'season_action_dispatcher', fn() => new Season_Action_Dispatcher( $this->createStub( Season_Service::class ), $this->guard ) );
        $this->container->set( 'competition_action_dispatcher', fn() => new Competition_Action_Dispatcher( $this->createStub( Competition_Service::class ), $this->guard ) );
        $this->container->set( 'competition_season_action_dispatcher', fn() => new Competition_Season_Action_Dispatcher( $this->createStub( Competition_Service::class ), $this->createStub( Finance_Service::class ), $this->guard ) );
        $this->container->set( 'event_action_dispatcher', fn() => new Event_Action_Dispatcher( $this->createStub( Competition_Service::class ), $this->guard ) );
        $this->container->set( 'finance_charge_action_dispatcher', fn() => new Finance_Charge_Action_Dispatcher( $this->createStub( Finance_Service::class ), $this->guard ) );
        $this->container->set( 'finance_invoice_action_dispatcher', fn() => new Finance_Invoice_Action_Dispatcher( $this->createStub( Finance_Service::class ), $this->guard ) );
        $this->container->set( 'player_action_dispatcher', fn() => new Player_Action_Dispatcher( $this->createStub( Player_Service::class ), $this->guard ) );
        $this->container->set( 'player_error_action_dispatcher', fn() => new Player_Error_Action_Dispatcher( $this->createStub( Player_Service::class ), $this->guard ) );
        $this->container->set( 'player_request_action_dispatcher', fn() => new Player_Request_Action_Dispatcher( $this->createStub( Registration_Service::class ), $this->guard ) );

        $this->container->set( 'cup_setup_action_dispatcher', fn() => $this->cup_setup_dispatcher );
        $this->container->set( 'cup_plan_action_dispatcher', fn() => $this->cup_plan_dispatcher );
        $this->container->set( 'draw_action_dispatcher', fn() => $this->draw_action_dispatcher );

        $racketmanager            = $this->createMock( RacketManager::class );
        $racketmanager->container = $this->container;
        $GLOBALS['racketmanager'] = $racketmanager;

        $this->screen = new Admin_Cup(
            $racketmanager,
            $this->container->get( 'admin_action_adapter' ),
            $this->cup_setup_dispatcher,
            $this->cup_plan_dispatcher,
            $this->draw_action_dispatcher
        );
    }

    protected function tearDown(): void {
        if ( null !== $this->original_method ) {
            $_SERVER['REQUEST_METHOD'] = $this->original_method;
        } else {
            unset( $_SERVER['REQUEST_METHOD'] );
        }
        $_GET  = array();
        $_POST = array();
        if ( null !== $this->previous_racketmanager ) {
            $GLOBALS['racketmanager'] = $this->previous_racketmanager;
        } else {
            unset( $GLOBALS['racketmanager'] );
        }
        if ( null !== $this->previous_capability ) {
            $GLOBALS['wp_stubs_current_user_can'] = $this->previous_capability;
        } else {
            unset( $GLOBALS['wp_stubs_current_user_can'] );
        }
        unset( $GLOBALS['wp_stubs_leagues'] );
        parent::tearDown();
    }

    public function test_setup_page_post_dispatches_and_redirects(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET                      = array( 'page' => 'racketmanager-cups', 'view' => 'setup', 'competition_id' => '5', 'season' => '2024' );
        $_POST                     = array( 'rank' => 'calculate_rank', 'season' => '2024', 'competition_id' => '5' );

        $this->cup_setup_dispatcher->expects( self::once() )
            ->method( 'handle' )
            ->with( $_GET, $_POST )
            ->willReturn( new Action_Result_DTO( 'Cup ratings set', Admin_Message_Type::SUCCESS ) );

        $this->flash_store->expects( self::once() )
            ->method( 'set' )
            ->with(
                'Cup ratings set',
                false,
                self::anything()
            );

        $this->redirector->expects( self::once() )
            ->method( 'redirect' )
            ->with( self::callback( function ( string $url ) {
                return str_contains( $url, 'page=racketmanager-cups' )
                    && str_contains( $url, 'view=setup' )
                    && str_contains( $url, 'competition_id=5' )
                    && str_contains( $url, 'season=2024' );
            } ) );

        $this->screen->display_cup_setup_page();
    }

    public function test_setup_event_page_post_dispatches_and_redirects(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET                      = array( 'page' => 'racketmanager-cups', 'view' => 'setup-event', 'competition_id' => '5', 'league' => '12', 'season' => '2024' );
        $_POST                     = array( 'action' => 'add', 'league_id' => '12', 'season' => '2024', 'rounds' => array() );

        $this->cup_setup_dispatcher->expects( self::once() )
            ->method( 'handle' )
            ->with( $_GET, $_POST )
            ->willReturn( new Action_Result_DTO( 'Fixtures added', Admin_Message_Type::SUCCESS ) );

        $this->flash_store->expects( self::once() )
            ->method( 'set' )
            ->with(
                'Fixtures added',
                false,
                self::anything()
            );

        $this->redirector->expects( self::once() )
            ->method( 'redirect' )
            ->with( self::callback( function ( string $url ) {
                return str_contains( $url, 'page=racketmanager-cups' )
                    && str_contains( $url, 'view=setup-event' )
                    && str_contains( $url, 'competition_id=5' )
                    && str_contains( $url, 'league=12' );
            } ) );

        $this->screen->display_setup_event_page();
    }

    public function test_plan_page_post_save_cup_dispatches_and_redirects_with_tab(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET                      = array( 'page' => 'racketmanager-cups', 'view' => 'plan', 'competition_id' => '5', 'season' => '2024' );
        $_POST                     = array( 'saveCup' => 'Save', 'startTime' => '10:00' );

        $this->cup_plan_dispatcher->expects( self::once() )
            ->method( 'handle' )
            ->with( $_GET, $_POST )
            ->willReturn( new Action_Result_DTO( 'Plan updated', Admin_Message_Type::SUCCESS, tab_override: 'config' ) );

        $this->flash_store->expects( self::once() )
            ->method( 'set' )
            ->with(
                'Plan updated',
                false,
                self::anything()
            );

        $this->redirector->expects( self::once() )
            ->method( 'redirect' )
            ->with( self::callback( function ( string $url ) {
                return str_contains( $url, 'page=racketmanager-cups' )
                    && str_contains( $url, 'view=plan' )
                    && str_contains( $url, 'tab=config' );
            } ) );

        $this->screen->display_cup_plan_page();
    }

    public function test_draw_page_post_dispatches_and_redirects(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET                      = array( 'page' => 'racketmanager-cups', 'view' => 'draw', 'competition_id' => '5', 'league' => '12', 'season' => '2024' );
        $_POST                     = array( 'saveRanking' => 'Save', 'season' => '2024' );

        $this->draw_action_dispatcher->expects( self::once() )
            ->method( 'handle' )
            ->willReturn( new Draw_Action_Response_DTO(
                message: 'Team ranking saved',
                message_type: Admin_Message_Type::SUCCESS,
                tab_override: 'preliminary'
            ) );

        $this->flash_store->expects( self::once() )
            ->method( 'set' )
            ->with(
                'Team ranking saved',
                false,
                self::anything()
            );

        $this->redirector->expects( self::once() )
            ->method( 'redirect' )
            ->with( self::callback( function ( string $url ) {
                return str_contains( $url, 'page=racketmanager-cups' )
                    && str_contains( $url, 'view=draw' )
                    && str_contains( $url, 'tab=preliminary' );
            } ) );

        $this->screen->display_cup_draw_page();
    }

    public function test_setup_page_guard_failure_redirects_with_error_flash(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET                      = array( 'page' => 'racketmanager-cups', 'view' => 'setup', 'competition_id' => '5', 'season' => '2024' );
        $_POST                     = array( 'rank' => 'calculate_rank', 'season' => '2024', 'competition_id' => '5' );

        $this->cup_setup_dispatcher->expects( self::once() )
            ->method( 'handle' )
            ->with( $_GET, $_POST )
            ->willReturn( new Action_Result_DTO( 'Forbidden', Admin_Message_Type::ERROR ) );

        $this->flash_store->expects( self::once() )
            ->method( 'set' )
            ->with(
                'Forbidden',
                true,
                self::anything()
            );

        $this->redirector->expects( self::once() )
            ->method( 'redirect' )
            ->with( self::callback( function ( string $url ) {
                return str_contains( $url, 'page=racketmanager-cups' )
                    && str_contains( $url, 'view=setup' );
            } ) );

        $this->screen->display_cup_setup_page();
    }

    public function test_plan_page_guard_failure_redirects_with_error_flash_and_tab(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET                      = array( 'page' => 'racketmanager-cups', 'view' => 'plan', 'competition_id' => '5', 'season' => '2024' );
        $_POST                     = array( 'saveCup' => 'Save' );

        $this->cup_plan_dispatcher->expects( self::once() )
            ->method( 'handle' )
            ->with( $_GET, $_POST )
            ->willReturn( new Action_Result_DTO( 'Permission denied', Admin_Message_Type::ERROR, tab_override: 'config' ) );

        $this->flash_store->expects( self::once() )
            ->method( 'set' )
            ->with(
                'Permission denied',
                true,
                self::anything()
            );

        $this->redirector->expects( self::once() )
            ->method( 'redirect' )
            ->with( self::callback( function ( string $url ) {
                return str_contains( $url, 'page=racketmanager-cups' )
                    && str_contains( $url, 'view=plan' )
                    && str_contains( $url, 'tab=config' );
            } ) );

        $this->screen->display_cup_plan_page();
    }

    public function test_draw_page_guard_failure_redirects_with_error_flash(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET                      = array( 'page' => 'racketmanager-cups', 'view' => 'draw', 'competition_id' => '5', 'league' => '12', 'season' => '2024' );
        $_POST                     = array( 'saveRanking' => 'Save', 'season' => '2024' );

        $this->draw_action_dispatcher->expects( self::once() )
            ->method( 'handle' )
            ->willReturn( new Draw_Action_Response_DTO(
                message: 'Invalid nonce',
                message_type: Admin_Message_Type::ERROR
            ) );

        $this->flash_store->expects( self::once() )
            ->method( 'set' )
            ->with(
                'Invalid nonce',
                true,
                self::anything()
            );

        $this->redirector->expects( self::once() )
            ->method( 'redirect' )
            ->with( self::callback( function ( string $url ) {
                return str_contains( $url, 'page=racketmanager-cups' )
                    && str_contains( $url, 'view=draw' );
            } ) );

        $this->screen->display_cup_draw_page();
    }

    private function create_test_competition( int $id = 5, string $season = '2024' ): Competition {
        $competition = new Competition( 'Test Cup', \Racketmanager\Domain\Competition\Competition_Type::CUP, 'open', null, array(), $id );
        $competition->set_seasons( array(
            $season => array(
                'name'           => $season,
                'date_start'     => '2024-05-01',
                'date_end'       => '2024-09-01',
                'date_closing'   => '2024-04-15',
                'round_length'   => 7,
                'match_dates'    => array( '2024-05-01', '2024-06-01', '2024-07-01' ),
                'starttime'      => '10:00',
                'time_increment' => '01:00:00',
                'num_courts'     => 4,
            ),
        ) );
        $competition->finals = array(
            array(
                'round'       => 1,
                'name'        => 'Final',
                'num_matches' => 1,
            ),
        );
        $competition->set_settings( array(
            'fixed_match_dates' => false,
        ) );
        return $competition;
    }

    public function test_unhandled_post_does_not_redirect(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET                      = array( 'page' => 'racketmanager-cups', 'view' => 'setup', 'competition_id' => '5', 'season' => '2024' );
        $_POST                     = array( 'unknown_field' => 'value' );

        $competition = $this->create_test_competition( 5, '2024' );
        $this->competition_service->expects( self::once() )->method( 'get_by_id' )->with( 5 )->willReturn( $competition );

        $this->cup_setup_dispatcher->expects( self::once() )
            ->method( 'handle' )
            ->willReturn( new Action_Result_DTO() );

        $this->redirector->expects( self::never() )->method( 'redirect' );
        $this->flash_store->expects( self::never() )->method( 'set' );

        ob_start();
        $this->screen->display_cup_setup_page();
        $output = ob_get_clean();

        $this->assertNotEmpty( $output );
    }

    public function test_display_cups_page_capability_denied(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET                      = array( 'page' => 'racketmanager-cups' );

        $GLOBALS['wp_stubs_current_user_can'] = false;
        $this->redirector->expects( self::never() )->method( 'redirect' );

        ob_start();
        $this->screen->display_cups_page();
        $output = ob_get_clean();

        $this->assertStringContainsString( 'alert', $output );
    }

    public function test_display_cups_page_renders_cleanly(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET                      = array( 'page' => 'racketmanager-cups' );

        $GLOBALS['wp_stubs_current_user_can'] = true;

        ob_start();
        $this->screen->display_cups_page();
        $output = ob_get_clean();

        $this->assertNotEmpty( $output );
    }

    public function test_display_cup_overview_page_renders_with_season(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET                      = array( 'page' => 'racketmanager-cups', 'view' => 'overview', 'competition_id' => '5', 'season' => '2024' );

        $competition = $this->create_test_competition( 5, '2024' );
        $this->competition_service->expects( self::once() )->method( 'get_competition_by_season' )->with( 5, 2024 )->willReturn( $competition );
        $this->competition_service->expects( self::once() )->method( 'get_events_for_competition' )->with( 5, 2024 )->willReturn( array() );
        $this->competition_service->expects( self::once() )->method( 'get_clubs_for_competition' )->with( 5, 2024 )->willReturn( array() );
        $this->competition_service->expects( self::once() )->method( 'get_competition_overview' )->with( 5, 2024 )->willReturn( null );

        ob_start();
        $this->screen->display_cup_overview_page();
        $output = ob_get_clean();

        $this->assertNotEmpty( $output );
    }

    public function test_display_cup_setup_page_renders_with_season(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET                      = array( 'page' => 'racketmanager-cups', 'view' => 'setup', 'competition_id' => '5', 'season' => '2024' );

        $competition = $this->create_test_competition( 5, '2024' );
        $this->competition_service->expects( self::once() )->method( 'get_by_id' )->with( 5 )->willReturn( $competition );

        ob_start();
        $this->screen->display_cup_setup_page();
        $output = ob_get_clean();

        $this->assertNotEmpty( $output );
    }

    public function test_display_setup_event_page_renders_with_season(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET                      = array( 'page' => 'racketmanager-cups', 'view' => 'setup-event', 'competition_id' => '5', 'league' => '12', 'season' => '2024' );

        $competition = $this->create_test_competition( 5, '2024' );
        $this->competition_service->expects( self::once() )->method( 'get_by_id' )->with( 5 )->willReturn( $competition );

        $event = new class {
            public int $offset = 0;
            public $competition;
            public function get_season_by_name( string $name ): array {
                return array( 'match_dates' => array( '2024-05-01' ) );
            }
        };

        $league = new class( $event, $competition ) {
            public int $id = 12;
            public $event;
            public $competition;
            public array $seasons = array();
            public function __construct( $event, $competition ) {
                $this->event = $event;
                $this->event->competition = $competition;
                $this->competition = $competition;
            }
            public function get_matches( array $args = array() ): int {
                return 0;
            }
        };

        $GLOBALS['wp_stubs_leagues'][12] = $league;

        ob_start();
        $this->screen->display_setup_event_page();
        $output = ob_get_clean();

        $this->assertNotEmpty( $output );
    }

    public function test_display_cup_matches_page_renders_via_legacy_locals_fallback(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET                      = array( 'page' => 'racketmanager-cups', 'view' => 'matches', 'competition_id' => '5', 'league' => '12', 'season' => '2024', 'final' => 'quarter' );

        $competition = $this->create_test_competition( 5, '2024' );
        $this->competition_service->expects( self::once() )->method( 'get_competition' )->with( 5 )->willReturn( $competition );

        $event_competition = (object) array(
            'id'            => 5,
            'name'          => 'Test Cup',
            'type'          => \Racketmanager\Domain\Competition\Competition_Type::CUP,
            'is_cup'        => true,
            'is_league'     => false,
            'is_tournament' => false,
            'is_team_entry' => true,
        );
        $event             = new class( $event_competition ) {
            public int $id = 3;
            public string $name = 'Cup Event';
            public $competition;
            public function __construct( $competition ) {
                $this->competition = $competition;
            }
            public function get_season_by_name( string $name ): array {
                return array();
            }
        };
        $championship      = new class {
            public bool $is_consolation = false;
            public int $num_teams_first_round = 8;
            public function get_finals( $key ): array {
                return array( 'num_matches' => 0, 'round' => 1, 'key' => $key );
            }
            public function get_final_teams( $key ): array {
                return array();
            }
        };
        $league            = new class( $event, $championship ) {
            public int $id = 12;
            public string $title = 'Cup League';
            public string $sport = 'tennis';
            public bool $is_championship = true;
            public array $current_season = array( 'name' => '2024' );
            public $event;
            public $championship;
            public function __construct( $event, $championship ) {
                $this->event        = $event;
                $this->championship = $championship;
            }
            public function get_matches( array $args = array() ): array {
                return array( (object) array( 'id' => 77, 'date' => '2024-05-01 10:00:00', 'home_team' => 1, 'away_team' => 2, 'location' => '', 'final_round' => 'quarter' ) );
            }
        };

        $GLOBALS['wp_stubs_leagues'][12] = $league;

        ob_start();
        try {
            $this->screen->display_cup_matches_page();
        } finally {
            $output = ob_get_clean();
        }

        $this->assertStringContainsString( 'page=racketmanager-cups', $output );
        $this->assertStringContainsString( 'Cup League', $output );
        $this->assertStringContainsString( 'Quarter Final', $output );
        $this->assertStringContainsString( 'view=draw&amp;competition_id=5&amp;league=12&amp;season=2024', $output );
    }

    public function test_display_cup_plan_page_renders_with_season_and_flash_errors(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET                      = array( 'page' => 'racketmanager-cups', 'view' => 'plan', 'competition_id' => '5', 'season' => '2024' );

        $competition = $this->create_test_competition( 5, '2024' );
        $this->competition_service->expects( self::once() )->method( 'get_competition_by_season' )->with( 5, 2024 )->willReturn( $competition );
        $this->competition_service->expects( self::once() )->method( 'get_events_for_competition' )->with( 5, 2024 )->willReturn( array() );

        $this->flash_store->method( 'pop' )->willReturn( array(
            'message'      => 'Error with plan details',
            'message_type' => 'error',
            'flash_data'   => array(
                'err_flds'  => array( 'start_time', 'timeIncrement' ),
                'err_msgs'  => array( 'Start time required', 'Time increment required' ),
                'form_data' => array(
                    'startTime'           => '11:30',
                    'timeIncrement'       => '02:00:00',
                    'numCourtsAvailable'  => 6,
                ),
            ),
        ) );

        ob_start();
        try {
            $this->screen->display_cup_plan_page();
        } finally {
            $output = ob_get_clean();
        }

        $this->assertStringContainsString( 'is-invalid', $output );
        $this->assertStringContainsString( 'Start time required', $output );
        $this->assertStringContainsString( '11:30', $output );
    }
}
