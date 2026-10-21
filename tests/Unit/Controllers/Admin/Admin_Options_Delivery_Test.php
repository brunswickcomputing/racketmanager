<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Admin_Display;
use Racketmanager\Admin\Admin_Options;
use Racketmanager\Admin\Flash\Admin_Flash_Message_Store;
use Racketmanager\Admin\View_Models\Options_Page_View_Model;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Gateways\Option\Option_Gateway_Interface;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Action_Adapter;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Redirector_Interface;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Screen_Presenter;
use Racketmanager\Infrastructure\Wordpress\Options\Option_Service;
use Racketmanager\RacketManager;
use Racketmanager\Services\Admin\Admin_Message_Service;
use Racketmanager\Services\Admin\Options\Options_Action_Dispatcher_Interface;
use Racketmanager\Services\Container\Container_Bootstrap;
use Racketmanager\Services\View\Php_View_Renderer;
use Racketmanager\Services\View\View_Renderer_Interface;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

final class Admin_Options_Delivery_Test extends TestCase {
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $_GET  = array();
        $_POST = array();
        $_SERVER['REQUEST_METHOD']         = 'GET';
        $GLOBALS['wp_stubs_current_user_can'] = true;
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_post_dispatches_and_redirects_with_flash_without_rendering(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET  = array( 'page' => 'racketmanager-settings' );
        $_POST = array(
            'updateRacketManager' => '1',
            'active-tab'          => 'billing',
        );

        $flash_store = $this->createMock( Admin_Flash_Message_Store::class );
        $flash_store->expects( self::never() )->method( 'pop' );
        $flash_store->expects( self::once() )
            ->method( 'set' )
            ->with( 'Settings saved', false );

        $redirector = $this->createMock( Admin_Redirector_Interface::class );
        $redirector->expects( self::once() )
            ->method( 'redirect' )
            ->with(
                self::callback(
                    static function ( string $url ): bool {
                        return str_contains( $url, 'page=racketmanager-settings' ) && str_contains( $url, 'tab=billing' );
                    }
                )
            );

        $renderer = $this->createMock( View_Renderer_Interface::class );
        $renderer->expects( self::never() )->method( 'render' );

        $dispatcher = $this->createMock( Options_Action_Dispatcher_Interface::class );
        $dispatcher->expects( self::once() )
            ->method( 'handle' )
            ->with( $_GET, $_POST )
            ->willReturn(
                new Action_Result_DTO(
                    message: 'Settings saved',
                    message_type: Admin_Message_Type::SUCCESS,
                    tab_override: 'billing'
                )
            );

        $screen = $this->create_screen(
            new Admin_Screen_Presenter( $renderer, new Admin_Message_Service( $flash_store ) ),
            new Admin_Action_Adapter( new Admin_Message_Service( $flash_store ), $redirector ),
            $dispatcher
        );

        $screen->display_options_page();
    }

    public function test_get_applies_flash_and_renders_typed_view_model_with_real_template(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = array(
            'page' => 'racketmanager-settings',
            'tab'  => 'players',
        );

        $stored_options = array(
            'billing' => array(
                'api_secret_key_live'   => 'sk_live_persisted_secret',
                'api_endpoint_key_live' => 'whsec_persisted_secret',
                'bankName'              => 'Persisted Bank',
            ),
            'keys'    => array(
                'recaptchaSecretKey' => 'recaptcha_persisted_secret',
            ),
        );

        $flash_store = $this->createMock( Admin_Flash_Message_Store::class );
        $flash_store->expects( self::once() )
            ->method( 'pop' )
            ->willReturn(
                array(
                    'message'      => 'Only one check can be set for ratings and wtn',
                    'message_type' => 'error',
                    'flash_data'   => array(
                        'tab'     => 'players',
                        'options' => array(
                            'checks'  => array(
                                'ageLimitCheck'  => true,
                                'ratingCheck'    => true,
                                'wtn_check'      => true,
                                'rosterLeadTime' => 21,
                            ),
                            'rosters' => array(
                                'btm' => true,
                            ),
                            'display' => array(
                                'wtn' => true,
                            ),
                            'billing' => array(
                                'bankName' => 'Form Submitted Bank',
                            ),
                        ),
                    ),
                )
            );

        $redirector = $this->createMock( Admin_Redirector_Interface::class );
        $redirector->expects( self::never() )->method( 'redirect' );

        $dispatcher = $this->createMock( Options_Action_Dispatcher_Interface::class );
        $dispatcher->expects( self::never() )->method( 'handle' );

        $renderer = new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' );
        $screen   = $this->create_screen(
            new Admin_Screen_Presenter( $renderer, new Admin_Message_Service( $flash_store ) ),
            new Admin_Action_Adapter( new Admin_Message_Service( $flash_store ), $redirector ),
            $dispatcher,
            $stored_options
        );

        ob_start();
        try {
            $screen->display_options_page();
            $output = (string) ob_get_contents();
        } finally {
            ob_end_clean();
        }

        self::assertStringContainsString( 'Racketmanager Global Settings', $output );
        self::assertStringContainsString( 'activaTab(\'players\')', $output );
        self::assertStringContainsString( 'name="active-tab" value="players"', $output );
        self::assertStringContainsString( "name='updateRacketManager'", $output );

        // Flash rehydration assertions
        self::assertMatchesRegularExpression( '/name=[\'"]ageLimitCheck[\'"][^>]*checked/', $output );
        self::assertMatchesRegularExpression( '/name=[\'"]ratingCheck[\'"][^>]*checked/', $output );
        self::assertMatchesRegularExpression( '/name=[\'"]wtnCheck[\'"][^>]*checked/', $output );
        self::assertMatchesRegularExpression( '/name=[\'"]btmRequired[\'"][^>]*checked/', $output );
        self::assertMatchesRegularExpression( '/name=[\'"]wtnDisplay[\'"][^>]*checked/', $output );
        self::assertMatchesRegularExpression( '/value=[\'"]Form Submitted Bank[\'"]/', $output );
        self::assertMatchesRegularExpression( '/value=[\'"]21[\'"]/', $output );

        // Preserved secrets merged from stored options
        self::assertMatchesRegularExpression( '/value=[\'"]sk_live_persisted_secret[\'"]/', $output );
        self::assertMatchesRegularExpression( '/value=[\'"]whsec_persisted_secret[\'"]/', $output );
        self::assertMatchesRegularExpression( '/value=[\'"]recaptcha_persisted_secret[\'"]/', $output );
    }

    public function test_get_without_flash_renders_stored_options_from_racketmanager(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = array(
            'page' => 'racketmanager-settings',
            'tab'  => 'billing',
        );

        $stored_options = array(
            'billing' => array(
                'bankName'      => 'Stored Main Bank',
                'accountNumber' => 87654321,
                'sortCode'      => '65-43-21',
            ),
            'display' => array(
                'wtn' => true,
            ),
            'keys'    => array(
                'googleMapsKey' => 'GMAP_STORED_KEY',
            ),
        );

        $flash_store = $this->createMock( Admin_Flash_Message_Store::class );
        $flash_store->expects( self::once() )
            ->method( 'pop' )
            ->willReturn( array() );

        $redirector = $this->createMock( Admin_Redirector_Interface::class );
        $redirector->expects( self::never() )->method( 'redirect' );

        $dispatcher = $this->createMock( Options_Action_Dispatcher_Interface::class );
        $dispatcher->expects( self::never() )->method( 'handle' );

        $renderer = new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' );
        $screen   = $this->create_screen(
            new Admin_Screen_Presenter( $renderer, new Admin_Message_Service( $flash_store ) ),
            new Admin_Action_Adapter( new Admin_Message_Service( $flash_store ), $redirector ),
            $dispatcher,
            $stored_options
        );

        ob_start();
        try {
            $screen->display_options_page();
            $output = (string) ob_get_contents();
        } finally {
            ob_end_clean();
        }

        self::assertStringContainsString( 'Racketmanager Global Settings', $output );
        self::assertStringContainsString( 'activaTab(\'billing\')', $output );
        self::assertMatchesRegularExpression( '/value=[\'"]Stored Main Bank[\'"]/', $output );
        self::assertMatchesRegularExpression( '/value=[\'"]87654321[\'"]/', $output );
        self::assertMatchesRegularExpression( '/value=[\'"]65-43-21[\'"]/', $output );
        self::assertMatchesRegularExpression( '/value=[\'"]GMAP_STORED_KEY[\'"]/', $output );
        self::assertMatchesRegularExpression( '/name=[\'"]wtnDisplay[\'"][^>]*checked/', $output );
    }

    public function test_view_options_denied_shows_alert_and_skips_dispatch_and_rendering(): void {
        $GLOBALS['wp_stubs_current_user_can'] = false;

        $flash_store = $this->createMock( Admin_Flash_Message_Store::class );
        $flash_store->expects( self::never() )->method( 'set' );
        $flash_store->expects( self::never() )->method( 'pop' );

        $redirector = $this->createMock( Admin_Redirector_Interface::class );
        $redirector->expects( self::never() )->method( 'redirect' );

        $renderer = $this->createMock( View_Renderer_Interface::class );
        $renderer->expects( self::never() )->method( 'render' );

        $dispatcher = $this->createMock( Options_Action_Dispatcher_Interface::class );
        $dispatcher->expects( self::never() )->method( 'handle' );

        $screen = $this->create_screen(
            new Admin_Screen_Presenter( $renderer, new Admin_Message_Service( $flash_store ) ),
            new Admin_Action_Adapter( new Admin_Message_Service( $flash_store ), $redirector ),
            $dispatcher
        );

        ob_start();
        try {
            $screen->display_options_page();
            $output = (string) ob_get_contents();
        } finally {
            ob_end_clean();
        }

        self::assertIsString( $output );
        self::assertStringContainsString( 'alert', $output );
    }

    public function test_constructor_resolves_services_from_container(): void {
        $wpdb_mock         = $this->createStub( \wpdb::class );
        $wpdb_mock->prefix = 'wp_';
        $GLOBALS['wpdb']   = $wpdb_mock;

        $racketmanager            = $this->createStub( RacketManager::class );
        $racketmanager->method( 'get_options' )->willReturn( array() );
        $racketmanager->options   = array();
        $racketmanager->site_url  = 'https://example.com';
        $racketmanager->site_name = 'Test Site';
        $container                = Container_Bootstrap::boot( $racketmanager );
        $racketmanager->container = $container;

        $screen = new Admin_Options( $racketmanager );

        $actions_prop    = ( new \ReflectionProperty( Admin_Options::class, 'actions' ) );
        $screens_prop    = ( new \ReflectionProperty( Admin_Options::class, 'screens' ) );
        $dispatcher_prop = ( new \ReflectionProperty( Admin_Options::class, 'options_action_dispatcher' ) );

        self::assertInstanceOf( Admin_Action_Adapter::class, $actions_prop->getValue( $screen ) );
        self::assertInstanceOf( Admin_Screen_Presenter::class, $screens_prop->getValue( $screen ) );
        self::assertNull( $dispatcher_prop->getValue( $screen ) );
    }

    private function create_screen(
        Admin_Screen_Presenter $screens,
        Admin_Action_Adapter $actions,
        Options_Action_Dispatcher_Interface $dispatcher,
        array $stored_options = array()
    ): Admin_Options {
        $reflection = new \ReflectionClass( Admin_Options::class );
        /** @var Admin_Options $screen */
        $screen = $reflection->newInstanceWithoutConstructor();

        $actions_prop = $reflection->getProperty( 'actions' );
        $actions_prop->setValue( $screen, $actions );

        $screens_prop = $reflection->getProperty( 'screens' );
        $screens_prop->setValue( $screen, $screens );

        $dispatcher_prop = $reflection->getProperty( 'options_action_dispatcher' );
        $dispatcher_prop->setValue( $screen, $dispatcher );

        $parent_actions = ( new \ReflectionProperty( Admin_Display::class, 'admin_action_adapter' ) );
        $parent_actions->setValue( $screen, $actions );

        $parent_messages = ( new \ReflectionProperty( Admin_Display::class, 'admin_message_service' ) );
        $parent_messages->setValue( $screen, new Admin_Message_Service( $this->createStub( Admin_Flash_Message_Store::class ) ) );

        $racketmanager          = ( new \ReflectionClass( RacketManager::class ) )->newInstanceWithoutConstructor();
        $racketmanager->options = $stored_options;
        $gateway                = $this->createStub( Option_Gateway_Interface::class );
        $gateway->method( 'get_option' )->willReturnCallback(
            fn( string $key, mixed $default = false ) => 'racketmanager' === $key ? $stored_options : $default
        );
        $racketmanager->option_service = new Option_Service( $gateway );

        $racketmanager_prop = ( new \ReflectionProperty( Admin_Display::class, 'racketmanager' ) );
        $racketmanager_prop->setValue( $screen, $racketmanager );

        return $screen;
    }
}
