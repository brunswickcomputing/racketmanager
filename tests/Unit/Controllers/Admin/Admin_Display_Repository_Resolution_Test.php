<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Admin_Display;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\RacketManager;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Tests\Helpers\Creates_Admin_Delivery_Container;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

/**
 * Admin_Display container bridge resolution tests.
 */
#[AllowMockObjectsWithoutExpectations]
final class Admin_Display_Repository_Resolution_Test extends TestCase {
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

    private function create_screen(): Admin_Display {
        $plugin            = $this->createStub( RacketManager::class );
        $plugin->container = $this->container;
        $plugin->options   = array(
            'version'   => defined( 'RACKETMANAGER_VERSION' ) ? RACKETMANAGER_VERSION : '1.0.0',
            'dbversion' => defined( 'RACKETMANAGER_DBVERSION' ) ? RACKETMANAGER_DBVERSION : '10.0.18',
        );
        return new Admin_Display( $plugin );
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string, 3: string, 4: string}>
     */
    public static function container_screen_routes(): array {
        return array(
            'league'     => array( 'racketmanager-leagues', 'leagues', 'league_competitions_admin_controller', 'admin_league_screen', 'admin_league' ),
            'cup'        => array( 'racketmanager-cups', 'cups', 'cup_competitions_admin_controller', 'admin_cup_screen', 'admin_cup' ),
            'tournament' => array( 'racketmanager-tournaments', 'tournaments', 'tournament_tournaments_admin_controller', 'admin_tournament_screen', 'admin_tournament' ),
            'club'       => array( 'racketmanager-clubs', 'clubs', 'clubs_admin_controller', 'admin_club_screen', 'admin_club' ),
            'player'     => array( 'racketmanager-players', 'players', 'players_admin_controller', 'admin_player_screen', 'admin_players' ),
            'finances'   => array( 'racketmanager-finances', '', 'finances_home_admin_controller', 'admin_finances_screen', 'admin_finances' ),
        );
    }

    #[DataProvider( 'container_screen_routes' )]
    public function test_display_resolves_bridge_from_container_and_handles_request(
        string $page,
        string $view,
        string $controller_key,
        string $screen_key,
        string $property
    ): void {
        $this->container->set(
            $controller_key,
            fn() => new class {
                public function __call( string $name, array $arguments ): array {
                    throw new Invalid_Status_Exception( 'Handled' );
                }
            }
        );

        $bridge = $this->container->get( $screen_key );

        $_GET['page'] = $page;
        $_GET['view'] = $view;

        $this->expectOutputRegex( '/Handled/' );

        $screen = $this->create_screen();
        $screen->display();

        self::assertSame( $bridge, $screen->{$property} );
    }
}
