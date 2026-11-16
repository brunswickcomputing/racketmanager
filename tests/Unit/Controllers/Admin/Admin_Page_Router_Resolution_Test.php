<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Admin_Club;
use Racketmanager\Admin\Admin_Cup;
use Racketmanager\Admin\Admin_Finances;
use Racketmanager\Admin\Admin_League;
use Racketmanager\Admin\Admin_Page_Router;
use Racketmanager\Admin\Admin_Player;
use Racketmanager\Admin\Admin_Tournament;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Screen_Presenter;
use Racketmanager\RacketManager;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Tests\Helpers\Creates_Admin_Delivery_Container;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

require_once __DIR__ . '/../../../wp-stubs.php';

/**
 * Admin_Page_Router container bridge identity tests.
 */
#[AllowMockObjectsWithoutExpectations]
final class Admin_Page_Router_Resolution_Test extends TestCase {
    use Creates_Admin_Delivery_Container;
    use Manages_Wordpress_Globals;

    private Simple_Container $container;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $GLOBALS['wp_stubs_current_user_can'] = true;
        $_GET                                 = array();
        $_POST                                = array();
        $_REQUEST                             = array();

        $this->container = $this->create_admin_delivery_container();
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    private function create_router(): Admin_Page_Router {
        $plugin            = $this->createStub( RacketManager::class );
        $plugin->container = $this->container;
        $plugin->options   = array(
            'version'   => RACKETMANAGER_VERSION,
            'dbversion' => RACKETMANAGER_DBVERSION,
        );
        $presenter         = $this->container->get( Admin_Screen_Presenter::class );
        return new Admin_Page_Router( $this->container, $plugin, $presenter );
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: class-string}>
     */
    public static function container_screen_routes(): array {
        return array(
            'league'     => array( 'racketmanager-leagues', 'leagues', Admin_League::class ),
            'cup'        => array( 'racketmanager-cups', 'cups', Admin_Cup::class ),
            'tournament' => array( 'racketmanager-tournaments', 'tournaments', Admin_Tournament::class ),
            'club'       => array( 'racketmanager-clubs', 'clubs', Admin_Club::class ),
            'player'     => array( 'racketmanager-players', 'players', Admin_Player::class ),
            'finances'   => array( 'racketmanager-finances', '', Admin_Finances::class ),
        );
    }

    #[DataProvider( 'container_screen_routes' )]
    public function test_display_uses_the_container_bridge_instance(
        string $page,
        string $view,
        string $screen_class
    ): void {
        $calls = 0;
        $seen  = null;
        $spy   = new class( $calls, $seen ) {
            public function __construct( private int &$calls, private ?string &$seen ) {
            }
            public function handle_display( string $view = '' ): void {
                ++$this->calls;
                $this->seen = $view;
            }
        };

        $this->container->set( $screen_class, fn() => $spy );

        self::assertSame( $spy, $this->container->get( $screen_class ) );

        $_GET['page'] = $page;
        $_GET['view'] = $view;

        $this->create_router()->display();

        self::assertSame( 1, $calls );
        self::assertSame( $view, $seen );
        self::assertSame( $spy, $this->container->get( $screen_class ) );
    }
}
