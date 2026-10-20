<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Admin_Season;
use Racketmanager\Admin\Flash\Admin_Flash_Message_Store;
use Racketmanager\Admin\View_Models\Error_Bag;
use Racketmanager\Admin\View_Models\Seasons_Page_View_Model;
use Racketmanager\Domain\Season\Season;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Redirector_Interface;
use Racketmanager\RacketManager;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Season_Service;
use Racketmanager\Services\View\Php_View_Renderer;
use Racketmanager\Services\View\View_Renderer_Interface;
use Racketmanager\Tests\Helpers\Creates_Admin_Delivery_Container;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

#[AllowMockObjectsWithoutExpectations]
final class Admin_Season_Delivery_Test extends TestCase {
    use Creates_Admin_Delivery_Container;
    use Manages_Wordpress_Globals;

    private Simple_Container $container;
    private Admin_Redirector_Interface $redirector;
    private View_Renderer_Interface $renderer;
    private Admin_Flash_Message_Store $flash_store;
    private Action_Guard_Interface $guard;
    private Season_Service $season_service;
    private Admin_Season $screen;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $GLOBALS['wp_stubs_current_user_can'] = true;

        $this->redirector     = $this->createMock( Admin_Redirector_Interface::class );
        $this->renderer       = $this->createMock( View_Renderer_Interface::class );
        $this->flash_store    = $this->createMock( Admin_Flash_Message_Store::class );
        $this->guard          = $this->createMock( Action_Guard_Interface::class );
        $this->season_service = $this->createMock( Season_Service::class );

        $this->container = $this->create_admin_delivery_container(
            services: array(
                'season_service' => $this->season_service,
            ),
            guard: $this->guard,
            redirector: $this->redirector,
            renderer: $this->renderer,
            flash_store: $this->flash_store,
        );

        $racketmanager            = $this->createStub( RacketManager::class );
        $racketmanager->container = $this->container;

        $this->screen = new Admin_Season( $racketmanager );
    }

    protected function tearDown(): void {
        $this->restore_globals();
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
