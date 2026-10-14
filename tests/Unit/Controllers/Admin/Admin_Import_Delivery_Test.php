<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Admin_Display;
use Racketmanager\Admin\Admin_Import;
use Racketmanager\Admin\Flash\Admin_Flash_Message_Store;
use Racketmanager\Admin\View_Models\Import_Page_View_Model;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Action_Adapter;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Redirector_Interface;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Screen_Presenter;
use Racketmanager\RacketManager;
use Racketmanager\Services\Admin\Admin_Message_Service;
use Racketmanager\Services\Admin\Import\Import_Action_Dispatcher_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Container\Container_Bootstrap;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\View\Php_View_Renderer;
use Racketmanager\Services\View\View_Renderer_Interface;

require_once __DIR__ . '/../../../wp-stubs.php';

final class Admin_Import_Delivery_Test extends TestCase {

    private array $original_get;
    private array $original_post;
    private array $original_files;
    private array $original_server;
    private bool $had_capability_flag;
    private mixed $original_capability_flag;
    private bool $had_racketmanager;
    private mixed $original_racketmanager;

    protected function setUp(): void {
        parent::setUp();
        $this->original_get             = $_GET;
        $this->original_post            = $_POST;
        $this->original_files           = $_FILES;
        $this->original_server          = $_SERVER;
        $this->had_capability_flag      = array_key_exists( 'wp_stubs_current_user_can', $GLOBALS );
        $this->original_capability_flag = $GLOBALS['wp_stubs_current_user_can'] ?? null;
        $this->had_racketmanager        = array_key_exists( 'racketmanager', $GLOBALS );
        $this->original_racketmanager   = $GLOBALS['racketmanager'] ?? null;

        $_GET                              = array();
        $_POST                             = array();
        $_FILES                            = array();
        $_SERVER['REQUEST_METHOD']         = 'GET';
        $GLOBALS['wp_stubs_current_user_can'] = true;
    }

    protected function tearDown(): void {
        $_GET    = $this->original_get;
        $_POST   = $this->original_post;
        $_FILES  = $this->original_files;
        $_SERVER = $this->original_server;

        if ( $this->had_capability_flag ) {
            $GLOBALS['wp_stubs_current_user_can'] = $this->original_capability_flag;
        } else {
            unset( $GLOBALS['wp_stubs_current_user_can'] );
        }
        if ( $this->had_racketmanager ) {
            $GLOBALS['racketmanager'] = $this->original_racketmanager;
        } else {
            unset( $GLOBALS['racketmanager'] );
        }

        parent::tearDown();
    }

    public function test_post_dispatches_and_redirects_with_flash_without_rendering(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET  = array( 'page' => 'racketmanager-import' );
        $_POST = array( 'import' => '1', 'mode' => 'players' );
        $_FILES = array(
            'racketmanager_import' => array(
                'name' => 'players.csv',
                'size' => 120,
            ),
        );

        $flash_store = $this->createMock( Admin_Flash_Message_Store::class );
        $flash_store->expects( self::never() )->method( 'pop' );
        $flash_store->expects( self::once() )
            ->method( 'set' )
            ->with( '1 Players imported', false );

        $redirector = $this->createMock( Admin_Redirector_Interface::class );
        $redirector->expects( self::once() )
            ->method( 'redirect' )
            ->with(
                self::callback(
                    static function ( string $url ): bool {
                        return str_contains( $url, 'page=racketmanager-import' );
                    }
                )
            );

        $renderer = $this->createMock( View_Renderer_Interface::class );
        $renderer->expects( self::never() )->method( 'render' );

        $dispatcher = $this->createMock( Import_Action_Dispatcher_Interface::class );
        $dispatcher->expects( self::once() )
            ->method( 'handle' )
            ->with( $_GET, $_POST, $_FILES )
            ->willReturn(
                new Action_Result_DTO(
                    message: '1 Players imported',
                    message_type: Admin_Message_Type::SUCCESS,
                )
            );

        $club_service = $this->createMock( Club_Service::class );
        $club_service->expects( self::never() )->method( 'get_clubs' );

        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->expects( self::never() )->method( 'get_all' );

        $screen = $this->create_screen(
            $club_service,
            $competition_service,
            new Admin_Screen_Presenter( $renderer, new Admin_Message_Service( $flash_store ) ),
            new Admin_Action_Adapter( new Admin_Message_Service( $flash_store ), $redirector ),
            $dispatcher
        );

        $screen->display_import_page();
    }

    public function test_get_applies_flash_builds_view_model_and_renders_template(): void {
        $flash_store = $this->createMock( Admin_Flash_Message_Store::class );
        $flash_store->expects( self::once() )
            ->method( 'pop' )
            ->willReturn( array( 'message' => 'Previous import done', 'message_type' => false ) );

        $redirector = $this->createMock( Admin_Redirector_Interface::class );
        $redirector->expects( self::never() )->method( 'redirect' );

        $club_service = $this->createMock( Club_Service::class );
        $club_service->expects( self::once() )
            ->method( 'get_clubs' )
            ->with( array( 'type' => 'affiliated' ) )
            ->willReturn( array( (object) array( 'id' => 1, 'name' => 'Westcliff Hardcourt' ) ) );

        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->expects( self::once() )
            ->method( 'get_all' )
            ->willReturn( array( (object) array( 'id' => 10, 'name' => 'Summer League' ) ) );

        $dispatcher = $this->createMock( Import_Action_Dispatcher_Interface::class );
        $dispatcher->expects( self::never() )->method( 'handle' );

        $renderer = new Php_View_Renderer( dirname( __DIR__, 4 ) . '/templates/' );

        $screen = $this->create_screen(
            $club_service,
            $competition_service,
            new Admin_Screen_Presenter( $renderer, new Admin_Message_Service( $flash_store ) ),
            new Admin_Action_Adapter( new Admin_Message_Service( $flash_store ), $redirector ),
            $dispatcher
        );

        ob_start();
        $screen->display_import_page();
        $output = ob_get_clean();

        $this->assertIsString( $output );
        $this->assertStringContainsString( 'RacketManager Import', $output );
        $this->assertStringContainsString( 'Westcliff Hardcourt', $output );
        $this->assertStringContainsString( 'Summer League', $output );
    }

    public function test_capability_check_denial_aborts_without_dispatch_or_render(): void {
        $GLOBALS['wp_stubs_current_user_can'] = false;

        $flash_store = $this->createMock( Admin_Flash_Message_Store::class );
        $redirector  = $this->createMock( Admin_Redirector_Interface::class );
        $renderer    = $this->createMock( View_Renderer_Interface::class );
        $dispatcher  = $this->createMock( Import_Action_Dispatcher_Interface::class );

        $club_service        = $this->createMock( Club_Service::class );
        $competition_service = $this->createMock( Competition_Service::class );

        $flash_store->expects( self::never() )->method( 'pop' );
        $redirector->expects( self::never() )->method( 'redirect' );
        $renderer->expects( self::never() )->method( 'render' );
        $dispatcher->expects( self::never() )->method( 'handle' );

        $screen = $this->create_screen(
            $club_service,
            $competition_service,
            new Admin_Screen_Presenter( $renderer, new Admin_Message_Service( $flash_store ) ),
            new Admin_Action_Adapter( new Admin_Message_Service( $flash_store ), $redirector ),
            $dispatcher
        );

        ob_start();
        $screen->display_import_page();
        $output = ob_get_clean();

        $this->assertStringContainsString( 'alert-danger', (string) $output );
        $this->assertStringNotContainsString( '<form', (string) $output );
    }

    public function test_admin_import_constructor_resolves_services_from_container(): void {
        $wpdb_mock         = $this->createStub( \wpdb::class );
        $wpdb_mock->prefix = 'wp_';
        global $wpdb;
        $original_wpdb = $wpdb ?? null;
        $wpdb          = $wpdb_mock;

        $racketmanager            = $this->createStub( RacketManager::class );
        $racketmanager->method( 'get_options' )->willReturn( array() );
        $racketmanager->options   = array();
        $racketmanager->site_url  = 'https://example.com';
        $racketmanager->site_name = 'Test Site';
        $container                = Container_Bootstrap::boot( $racketmanager );
        $racketmanager->container = $container;

        $GLOBALS['racketmanager'] = $racketmanager;

        $admin_import = new Admin_Import( $racketmanager );

        $actions_prop = ( new \ReflectionProperty( Admin_Import::class, 'actions' ) );
        $screens_prop = ( new \ReflectionProperty( Admin_Import::class, 'screens' ) );

        $this->assertInstanceOf( Admin_Action_Adapter::class, $actions_prop->getValue( $admin_import ) );
        $this->assertInstanceOf( Admin_Screen_Presenter::class, $screens_prop->getValue( $admin_import ) );

        $wpdb = $original_wpdb;
    }

    private function create_screen(
        Club_Service $club_service,
        Competition_Service $competition_service,
        Admin_Screen_Presenter $screens,
        Admin_Action_Adapter $actions,
        Import_Action_Dispatcher_Interface $dispatcher,
    ): Admin_Import {
        $screen = ( new \ReflectionClass( Admin_Import::class ) )->newInstanceWithoutConstructor();

        ( new \ReflectionProperty( Admin_Display::class, 'club_service' ) )->setValue( $screen, $club_service );
        ( new \ReflectionProperty( Admin_Display::class, 'competition_service' ) )->setValue( $screen, $competition_service );
        ( new \ReflectionProperty( Admin_Display::class, 'admin_action_adapter' ) )->setValue( $screen, $actions );
        ( new \ReflectionProperty( Admin_Display::class, 'admin_message_service' ) )->setValue( $screen, new Admin_Message_Service( $this->createStub( Admin_Flash_Message_Store::class ) ) );
        ( new \ReflectionProperty( Admin_Import::class, 'actions' ) )->setValue( $screen, $actions );
        ( new \ReflectionProperty( Admin_Import::class, 'screens' ) )->setValue( $screen, $screens );
        ( new \ReflectionProperty( Admin_Import::class, 'import_action_dispatcher' ) )->setValue( $screen, $dispatcher );

        return $screen;
    }
}
