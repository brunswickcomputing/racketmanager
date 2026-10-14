<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Admin_Season;
use Racketmanager\Admin\Flash\Admin_Flash_Message_Store;
use Racketmanager\Admin\View_Models\Error_Bag;
use Racketmanager\Admin\View_Models\Seasons_Page_View_Model;
use Racketmanager\Domain\Season;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Action_Adapter;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Redirector_Interface;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Screen_Presenter;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Services\Admin\Admin_Message_Service;
use Racketmanager\Services\Admin\Club\Club_Action_Dispatcher;
use Racketmanager\Services\Admin\Club\Club_Player_Action_Dispatcher;
use Racketmanager\Services\Admin\Club\Club_Role_Action_Dispatcher;
use Racketmanager\Services\Admin\Club\Club_Team_Action_Dispatcher;
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
use Racketmanager\Services\Admin\Season\Season_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Fixture\Fixture_Maintenance_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Season_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Services\View\View_Renderer_Interface;
use Racketmanager\Services\View\Php_View_Renderer;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Admin_Season_Delivery_Test extends TestCase {

    private Simple_Container $container;
    private Admin_Redirector_Interface $redirector;
    private View_Renderer_Interface $renderer;
    private Admin_Flash_Message_Store $flash_store;
    private Action_Guard_Interface $guard;
    private Season_Service $season_service;
    private Admin_Season $screen;
    private ?string $original_method;

    protected function setUp(): void {
        parent::setUp();
        $GLOBALS['wp_stubs_current_user_can'] = true;
        $this->original_method = $_SERVER['REQUEST_METHOD'] ?? null;
        $_GET                  = array();
        $_POST                 = array();

        $this->redirector     = $this->createMock( Admin_Redirector_Interface::class );
        $this->renderer       = $this->createMock( View_Renderer_Interface::class );
        $this->flash_store    = $this->createMock( Admin_Flash_Message_Store::class );
        $this->guard          = $this->createMock( Action_Guard_Interface::class );
        $this->season_service = $this->createMock( Season_Service::class );

        $message_service = new Admin_Message_Service( $this->flash_store );

        $this->container = new Simple_Container();
        $this->container->set( 'season_service', fn() => $this->season_service );
        $this->container->set( 'club_service', fn() => $this->createStub( Club_Service::class ) );
        $this->container->set( 'player_service', fn() => $this->createStub( Player_Service::class ) );
        $this->container->set( 'registration_service', fn() => $this->createStub( Registration_Service::class ) );
        $this->container->set( 'team_service', fn() => $this->createStub( Team_Service::class ) );
        $this->container->set( 'league_service', fn() => $this->createStub( League_Service::class ) );
        $this->container->set( 'competition_service', fn() => $this->createStub( Competition_Service::class ) );
        $this->container->set( 'finance_service', fn() => $this->createStub( Finance_Service::class ) );
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

        $this->container->set( 'season_action_dispatcher', fn() => new Season_Action_Dispatcher( $this->season_service, $this->guard ) );

        $racketmanager            = $this->createStub( RacketManager::class );
        $racketmanager->container = $this->container;
        global $racketmanager_instance;
        $racketmanager_instance = $racketmanager;

        $this->screen = new Admin_Season( $racketmanager );
    }

    protected function tearDown(): void {
        unset( $GLOBALS['wp_stubs_current_user_can'] );
        $_GET  = array();
        $_POST = array();
        if ( null === $this->original_method ) {
            unset( $_SERVER['REQUEST_METHOD'] );
        } else {
            $_SERVER['REQUEST_METHOD'] = $this->original_method;
        }
        parent::tearDown();
    }

    public function test_post_mutation_add_season_redirects_with_flash(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['addSeason']        = 'season';
        $_POST['seasonName']       = '2025';

        $this->guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_add-season', 'edit_seasons' );

        $this->season_service->expects( self::once() )
            ->method( 'create_season' )
            ->with( '2025' )
            ->willReturn( true );

        $this->flash_store->expects( self::once() )
            ->method( 'set' )
            ->with( 'Season added', false );

        $this->redirector->expects( self::once() )
            ->method( 'redirect' )
            ->with( self::stringContains( 'page=racketmanager-seasons' ) );

        $this->screen->display_seasons_page();
    }

    public function test_post_mutation_delete_seasons_redirects_with_flash(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['doSeasonDel']      = '1';
        $_POST['action']           = 'delete';
        $_POST['season']           = array( '2024' );

        $this->guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_seasons-bulk', 'edit_seasons' );

        $this->season_service->expects( self::once() )
            ->method( 'delete_season' )
            ->with( 2024 )
            ->willReturn( true );

        $this->flash_store->expects( self::once() )
            ->method( 'set' )
            ->with( 'Season(s) deleted', false );

        $this->redirector->expects( self::once() )
            ->method( 'redirect' )
            ->with( self::stringContains( 'page=racketmanager-seasons' ) );

        $this->screen->display_seasons_page();
    }

    public function test_post_mutation_guard_failure_redirects_with_error_flash_and_calls_no_service_mutation(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['addSeason']        = 'season';
        $_POST['seasonName']       = '2025';

        $this->guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->willThrowException( new Invalid_Status_Exception( 'Security token invalid' ) );

        $this->season_service->expects( self::never() )
            ->method( 'create_season' );

        $this->flash_store->expects( self::once() )
            ->method( 'set' )
            ->with( 'Security token invalid', true );

        $this->redirector->expects( self::once() )
            ->method( 'redirect' )
            ->with( self::stringContains( 'page=racketmanager-seasons' ) );

        $this->screen->display_seasons_page();
    }

    public function test_post_validation_error_preserves_field_error_and_entered_season_in_flash(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['addSeason']        = 'season';
        $_POST['seasonName']       = '2025';

        $this->guard->expects( self::once() )->method( 'assert_allowed' );
        $this->season_service->expects( self::once() )
            ->method( 'create_season' )
            ->with( '2025' )
            ->willReturn( new \WP_Error( 'seasonName', 'Season already exists' ) );

        $this->flash_store->expects( self::once() )
            ->method( 'set' )
            ->with(
                'Season already exists',
                true,
                array(
                    'season_name' => '2025',
                    'errors'      => array( 'seasonName' => 'Season already exists' ),
                )
            );
        $this->redirector->expects( self::once() )
            ->method( 'redirect' )
            ->with( self::stringContains( 'page=racketmanager-seasons' ) );

        $this->screen->display_seasons_page();
    }

    public function test_get_rendering_presents_view_model_to_renderer(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $season = new Season();
        $season->set_name( '2025' );

        $this->season_service->expects( self::once() )
            ->method( 'get_all_seasons' )
            ->willReturn( array( $season ) );

        $this->renderer->expects( self::once() )
            ->method( 'render' )
            ->with(
                'admin/show-seasons',
                self::callback( function ( $vm ) use ( $season ) {
                    return $vm instanceof Seasons_Page_View_Model && $vm->seasons === array( $season );
                } )
            );

        $this->screen->display_seasons_page();
    }

    public function test_get_maps_validation_flash_data_to_view_model(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->flash_store->expects( self::once() )
            ->method( 'pop' )
            ->willReturn(
                array(
                    'message'      => 'Season already exists',
                    'message_type' => true,
                    'flash_data'   => array(
                        'season_name' => '2025',
                        'errors'      => array( 'seasonName' => 'Season already exists' ),
                    ),
                )
            );

        $this->renderer->expects( self::once() )
            ->method( 'render' )
            ->with(
                'admin/show-seasons',
                self::callback(
                    static function ( $vm ): bool {
                        return $vm instanceof Seasons_Page_View_Model
                            && '2025' === $vm->season_name
                            && $vm->errors->has( 'seasonName' )
                            && 'Season already exists' === $vm->errors->message( 'seasonName' );
                    }
                )
            );

        ob_start();
        $this->screen->display_seasons_page();
        ob_end_clean();
    }

    public function test_real_template_renders_validation_error_and_preserves_entered_name(): void {
        if ( ! defined( 'RACKETMANAGER_IS_INVALID' ) ) {
            define( 'RACKETMANAGER_IS_INVALID', 'is-invalid' );
        }

        $renderer = new Php_View_Renderer( dirname( __DIR__, 4 ) . '/templates/' );
        $vm       = new Seasons_Page_View_Model(
            seasons: array(),
            errors: new Error_Bag( array( 'seasonName' => 'Season already exists' ) ),
            season_name: '2025'
        );
        $output   = $renderer->render_to_string( 'admin/show-seasons', $vm );

        self::assertStringContainsString( 'class="form-control is-invalid"', $output );
        self::assertStringContainsString( 'value="2025"', $output );
        self::assertStringContainsString( 'invalid-feedback">Season already exists</div>', $output );
    }

    public function test_get_capability_failure_does_not_render_view_model(): void {
        $_SERVER['REQUEST_METHOD']            = 'GET';
        $GLOBALS['wp_stubs_current_user_can'] = false;

        $this->season_service->expects( self::never() )
            ->method( 'get_all_seasons' );

        $this->renderer->expects( self::never() )
            ->method( 'render' );

        ob_start();
        $this->screen->display_seasons_page();
        $output = (string) ob_get_clean();

        self::assertStringContainsString( 'permission', $output );
    }
}
