<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Component;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Infrastructure\Wordpress\Ajax\Ajax_Registry;
use Racketmanager\Infrastructure\Wordpress\Component\Component_Coordinator;
use Racketmanager\Infrastructure\Wordpress\Export\Export_Delivery_Adapter;
use Racketmanager\RacketManager;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use ReflectionClass;
use stdClass;

require_once dirname( __DIR__, 4 ) . '/wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Component_Coordinator_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private Simple_Container $container;
    private Ajax_Registry $ajax_registry;
    private Component_Coordinator $coordinator;
    private RacketManager $app;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();

        $this->container     = new Simple_Container();
        $this->ajax_registry = new Ajax_Registry();
        $this->coordinator   = new Component_Coordinator( $this->container, $this->ajax_registry );

        $this->app            = ( new ReflectionClass( RacketManager::class ) )->newInstanceWithoutConstructor();
        $this->app->container = $this->container;
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_init_components_resolves_and_populates_all_app_properties(): void {
        $account_ajax    = $this->createMock( \Racketmanager\Infrastructure\Wordpress\Ajax\Account_Ajax_Controller::class );
        $club_ajax       = $this->createMock( \Racketmanager\Infrastructure\Wordpress\Ajax\Club_Ajax_Controller::class );
        $finance_ajax    = $this->createMock( \Racketmanager\Infrastructure\Wordpress\Ajax\Finance_Ajax_Controller::class );
        $frontend_ajax   = $this->createMock( \Racketmanager\Infrastructure\Wordpress\Ajax\Frontend_Ajax_Controller::class );
        $fixture_ajax    = $this->createMock( \Racketmanager\Infrastructure\Wordpress\Ajax\Fixture_Ajax_Controller::class );
        $tournament_ajax = $this->createMock( \Racketmanager\Infrastructure\Wordpress\Ajax\Tournament_Ajax_Controller::class );

        $account_ajax->expects( self::once() )->method( 'register_hooks' )->with( $this->ajax_registry );
        $club_ajax->expects( self::once() )->method( 'register_hooks' )->with( $this->ajax_registry );
        $finance_ajax->expects( self::once() )->method( 'register_hooks' )->with( $this->ajax_registry );
        $frontend_ajax->expects( self::once() )->method( 'register_hooks' )->with( $this->ajax_registry );
        $tournament_ajax->expects( self::once() )->method( 'register_hooks' )->with( $this->ajax_registry );

        $this->container->set( 'account_ajax_controller', fn() => $account_ajax );
        $this->container->set( 'club_ajax_controller', fn() => $club_ajax );
        $this->container->set( 'finance_ajax_controller', fn() => $finance_ajax );
        $this->container->set( 'frontend_ajax_controller', fn() => $frontend_ajax );
        $this->container->set( 'fixture_ajax_controller', fn() => $fixture_ajax );
        $this->container->set( 'tournament_ajax_controller', fn() => $tournament_ajax );

        $this->container->set( 'general_shortcode_adapter', fn() => new stdClass() );
        $this->container->set( 'club_shortcode_adapter', fn() => new stdClass() );
        $this->container->set( 'competition_shortcode_adapter', fn() => new stdClass() );
        $this->container->set( 'email_shortcode_adapter', fn() => new stdClass() );
        $this->container->set( 'event_shortcode_adapter', fn() => new stdClass() );
        $this->container->set( 'league_shortcode_adapter', fn() => new stdClass() );
        $this->container->set( 'login_shortcode_adapter', fn() => new stdClass() );
        $this->container->set( 'matches_shortcode_adapter', fn() => new stdClass() );
        $this->container->set( 'message_shortcode_adapter', fn() => new stdClass() );
        $this->container->set( 'tournament_shortcode_adapter', fn() => new stdClass() );

        $this->coordinator->init_components( $this->app );

        self::assertSame( $account_ajax, $this->app->ajax_account );
        self::assertSame( $club_ajax, $this->app->ajax_club );
        self::assertSame( $finance_ajax, $this->app->ajax_finance );
        self::assertSame( $frontend_ajax, $this->app->ajax_frontend );
        self::assertSame( $fixture_ajax, $this->app->ajax_fixture );
        self::assertSame( $tournament_ajax, $this->app->ajax_tournament );

        self::assertInstanceOf( \Racketmanager\Public\Shortcodes::class, $this->app->shortcodes );
        self::assertInstanceOf( stdClass::class, $this->app->shortcodes_general );
        self::assertInstanceOf( stdClass::class, $this->app->shortcodes_club );
        self::assertInstanceOf( stdClass::class, $this->app->shortcodes_competition );
        self::assertInstanceOf( stdClass::class, $this->app->shortcodes_email );
        self::assertInstanceOf( stdClass::class, $this->app->shortcodes_event );
        self::assertInstanceOf( stdClass::class, $this->app->shortcodes_league );
        self::assertInstanceOf( stdClass::class, $this->app->shortcodes_login );
        self::assertInstanceOf( stdClass::class, $this->app->shortcodes_match );
        self::assertInstanceOf( stdClass::class, $this->app->shortcodes_message );
        self::assertInstanceOf( stdClass::class, $this->app->shortcodes_tournament );

        self::assertInstanceOf( \Racketmanager\Services\Rewrites::class, $this->app->rewrites );
        self::assertInstanceOf( \Racketmanager\Services\Login::class, $this->app->login );
    }

    public function test_handle_exports_delegates_to_delivery_adapter_when_requested(): void {
        $_GET['racketmanager_export'] = 'players';

        $export_adapter = $this->createMock( Export_Delivery_Adapter::class );
        $export_adapter->expects( self::once() )->method( 'handle_export' );
        $this->container->set( 'export_delivery_adapter', fn() => $export_adapter );

        $this->coordinator->handle_exports( $this->app );
    }

    public function test_handle_exports_does_nothing_when_no_export_param(): void {
        unset( $_GET['racketmanager_export'] );

        $export_adapter = $this->createMock( Export_Delivery_Adapter::class );
        $export_adapter->expects( self::never() )->method( 'handle_export' );
        $this->container->set( 'export_delivery_adapter', fn() => $export_adapter );

        $this->coordinator->handle_exports( $this->app );
    }
}
