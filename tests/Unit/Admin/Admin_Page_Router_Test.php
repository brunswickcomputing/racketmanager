<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Admin_Club;
use Racketmanager\Admin\Admin_Cup;
use Racketmanager\Admin\Admin_Finances;
use Racketmanager\Admin\Admin_Import;
use Racketmanager\Admin\Admin_Index;
use Racketmanager\Admin\Admin_League;
use Racketmanager\Admin\Admin_Options;
use Racketmanager\Admin\Admin_Page_Router;
use Racketmanager\Admin\Admin_Player;
use Racketmanager\Admin\Admin_Result;
use Racketmanager\Admin\Admin_Season;
use Racketmanager\Admin\Admin_Tournament;
use Racketmanager\Admin\Admin_Upgrade;
use Racketmanager\Admin\Flash\Admin_Flash_Message_Store;
use Racketmanager\Admin\View_Models\Documentation_Page_View_Model;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Screen_Presenter;
use Racketmanager\RacketManager;
use Racketmanager\Services\Admin\Admin_Message_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Application\Ports\View_Renderer_Interface;
use Racketmanager\Tests\Helpers\Creates_Admin_Delivery_Container;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

require_once __DIR__ . '/../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Admin_Page_Router_Test extends TestCase {
    use Creates_Admin_Delivery_Container;
    use Manages_Wordpress_Globals;

    private Simple_Container $container;
    private Admin_Screen_Presenter $screen_presenter;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $GLOBALS['wp_stubs_flushed_rewrite_rules']      = false;
        $GLOBALS['wp_stubs_flushed_rewrite_rules_hard'] = null;
        $_GET                                           = array();
        $_POST                                          = array();
        $_REQUEST                                       = array();

        $this->container        = $this->create_admin_delivery_container();
        $this->screen_presenter = $this->container->get( Admin_Screen_Presenter::class );
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_display_flushes_rewrite_rules_and_updates_version_when_version_mismatch(): void {
        $plugin          = $this->createMock( RacketManager::class );
        $plugin->options = array(
            'version'   => '0.9.0',
            'dbversion' => RACKETMANAGER_DBVERSION,
        );

        $plugin->expects( self::once() )
            ->method( 'update_plugin_options' )
            ->with(
                self::callback(
                    static function ( array $opts ): bool {
                        return RACKETMANAGER_VERSION === $opts['version'];
                    }
                )
            );

        $called     = false;
        $mock_index = new class( $called ) {
            public function __construct( private bool &$called ) {
            }
            public function display_index_page(): void {
                $this->called = true;
            }
        };
        $this->container->set( Admin_Index::class, fn() => $mock_index );

        $router = new Admin_Page_Router( $this->container, $plugin, $this->screen_presenter );
        $router->display();

        self::assertTrue( $GLOBALS['wp_stubs_flushed_rewrite_rules'] );
        self::assertTrue( $GLOBALS['wp_stubs_flushed_rewrite_rules_hard'] );
        self::assertTrue( $called );
    }

    public function test_display_routes_to_upgrade_screen_when_db_version_mismatch(): void {
        $plugin          = $this->createMock( RacketManager::class );
        $plugin->options = array(
            'version'   => RACKETMANAGER_VERSION,
            'dbversion' => '10.0.0',
        );

        $called       = false;
        $mock_upgrade = new class( $called ) {
            public function __construct( private bool &$called ) {
            }
            public function handle_display(): void {
                $this->called = true;
            }
        };
        $this->container->set( Admin_Upgrade::class, fn() => $mock_upgrade );

        $router = new Admin_Page_Router( $this->container, $plugin, $this->screen_presenter );
        $router->display();

        self::assertTrue( $called );
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function documentation_slugs(): array {
        return array(
            'doc'           => array( 'racketmanager-doc' ),
            'documentation' => array( 'racketmanager-documentation' ),
        );
    }

    #[DataProvider( 'documentation_slugs' )]
    public function test_display_routes_documentation_to_presenter_with_typed_view_model( string $page ): void {
        $plugin   = $this->plugin_with_current_versions();
        $renderer = $this->createMock( View_Renderer_Interface::class );
        $renderer->expects( self::once() )
            ->method( 'render' )
            ->with(
                'admin/documentation',
                self::isInstanceOf( Documentation_Page_View_Model::class )
            );

        $store     = $this->createStub( Admin_Flash_Message_Store::class );
        $presenter = new Admin_Screen_Presenter( $renderer, new Admin_Message_Service( $store ) );

        $_GET['page'] = $page;

        $router = new Admin_Page_Router( $this->container, $plugin, $presenter );
        $router->display();
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: class-string, 3: string}>
     */
    public static function screen_routes(): array {
        return array(
            'leagues'     => array( 'racketmanager-leagues', 'plan', Admin_League::class, 'handle_display' ),
            'cups'        => array( 'racketmanager-cups', 'draw', Admin_Cup::class, 'handle_display' ),
            'tournaments' => array( 'racketmanager-tournaments', 'overview', Admin_Tournament::class, 'handle_display' ),
            'clubs'       => array( 'racketmanager-clubs', 'teams', Admin_Club::class, 'handle_display' ),
            'results'     => array( 'racketmanager-results', 'resultschecker', Admin_Result::class, 'handle_display' ),
            'seasons'     => array( 'racketmanager-seasons', 'seasons', Admin_Season::class, 'handle_display' ),
            'players'     => array( 'racketmanager-players', 'players', Admin_Player::class, 'handle_display' ),
            'finances'    => array( 'racketmanager-finances', 'invoices', Admin_Finances::class, 'handle_display' ),
            'settings'    => array( 'racketmanager-settings', 'club-players', Admin_Options::class, 'handle_display' ),
            'import'      => array( 'racketmanager-import', 'import', Admin_Import::class, 'handle_display' ),
            'index'       => array( 'racketmanager', '', Admin_Index::class, 'display_index_page' ),
        );
    }

    #[DataProvider( 'screen_routes' )]
    public function test_display_resolves_screen_and_calls_method(
        string $page,
        string $view,
        string $screen_class,
        string $method
    ): void {
        $plugin        = $this->plugin_with_current_versions();
        $called_method = null;
        $captured_view = null;
        $mock_screen   = new class( $called_method, $captured_view ) {
            public function __construct( private ?string &$called_method, private ?string &$captured_view ) {
            }
            public function handle_display( string $view = '' ): void {
                $this->called_method = 'handle_display';
                $this->captured_view = $view;
            }
            public function display_index_page(): void {
                $this->called_method = 'display_index_page';
            }
        };

        $this->container->set( $screen_class, fn() => $mock_screen );

        $_GET['page'] = $page;
        $_GET['view'] = $view;

        $router = new Admin_Page_Router( $this->container, $plugin, $this->screen_presenter );
        $router->display();

        self::assertSame( $method, $called_method );
        if ( 'handle_display' === $method ) {
            self::assertSame( $view, $captured_view );
        }
    }

    public function test_unknown_slug_falls_through_to_index(): void {
        $plugin = $this->plugin_with_current_versions();

        $called_method = null;
        $mock_index    = new class( $called_method ) {
            public function __construct( private ?string &$called_method ) {
            }
            public function handle_display( string $view = '' ): void {
                $this->called_method = 'handle_display';
            }
            public function display_index_page(): void {
                $this->called_method = 'display_index_page';
            }
        };
        $this->container->set( Admin_Index::class, fn() => $mock_index );

        $_GET['page'] = 'racketmanager-unknown';

        $router = new Admin_Page_Router( $this->container, $plugin, $this->screen_presenter );
        $router->display();

        self::assertSame( 'display_index_page', $called_method );
    }

    private function plugin_with_current_versions(): RacketManager {
        $plugin          = $this->createStub( RacketManager::class );
        $plugin->options = array(
            'version'   => RACKETMANAGER_VERSION,
            'dbversion' => RACKETMANAGER_DBVERSION,
        );

        return $plugin;
    }
}
