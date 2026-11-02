<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Admin;

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Admin_Championship;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Action_Adapter;
use Racketmanager\RacketManager;
use Racketmanager\Services\Admin\Admin_Message_Service;
use Racketmanager\Services\Admin\Season\Season_Action_Dispatcher;
use Racketmanager\Services\Championship_Manager;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition\Event_Query_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Season_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use ReflectionProperty;

final class Admin_Championship_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private Simple_Container $container;
    private RacketManager $plugin;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $GLOBALS['wp_stubs_current_user_can'] = true;
        $_GET  = array();
        $_POST = array();

        $this->container = new Simple_Container();
        $this->container->set( 'club_service', fn() => $this->createStub( Club_Service::class ) );
        $this->container->set( 'competition_service', fn() => $this->createStub( Competition_Service::class ) );
        $this->container->set( 'season_service', fn() => $this->createStub( Season_Service::class ) );
        $this->container->set( 'tournament_service', fn() => $this->createStub( Tournament_Service::class ) );
        $this->container->set( 'event_query_service', fn() => $this->createStub( Event_Query_Service::class ) );
        $message_service = new Admin_Message_Service(
            $this->createStub( \Racketmanager\Admin\Flash\Admin_Flash_Message_Store::class )
        );
        $admin_action_adapter = new Admin_Action_Adapter(
            $message_service,
            $this->createStub( \Racketmanager\Infrastructure\Wordpress\Admin\Admin_Redirector_Interface::class )
        );
        $this->container->set( 'admin_action_adapter', fn() => $admin_action_adapter );
        $this->container->set( 'admin_message_service', fn() => $message_service );
        $this->container->set(
            'season_action_dispatcher',
            fn() => new Season_Action_Dispatcher(
                $this->createStub( Season_Service::class ),
                $this->createStub( \Racketmanager\Services\Admin\Security\Action_Guard_Interface::class )
            )
        );

        $this->plugin            = $this->createStub( RacketManager::class );
        $this->plugin->container = $this->container;
        $this->plugin->options   = array( 'version' => '1.0', 'dbversion' => '1.0' );
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_constructor_accepts_injected_championship_manager(): void {
        $manager = ( new \ReflectionClass( Championship_Manager::class ) )->newInstanceWithoutConstructor();
        $admin   = new Admin_Championship( $this->plugin, $this->container, $manager );

        $prop = new ReflectionProperty( Admin_Championship::class, 'championship_manager' );
        $this->assertSame( $manager, $prop->getValue( $admin ) );
    }

    public function test_constructor_resolves_championship_manager_from_container(): void {
        $manager = ( new \ReflectionClass( Championship_Manager::class ) )->newInstanceWithoutConstructor();
        $this->container->set( 'championship_manager', fn() => $manager );
        $this->container->alias( Championship_Manager::class, 'championship_manager' );

        $admin = new Admin_Championship( $this->plugin, $this->container );

        $prop = new ReflectionProperty( Admin_Championship::class, 'championship_manager' );
        $this->assertSame( $manager, $prop->getValue( $admin ) );
    }

    public function test_constructor_leaves_championship_manager_null_when_not_registered(): void {
        $admin = new Admin_Championship( $this->plugin, $this->container );

        $prop = new ReflectionProperty( Admin_Championship::class, 'championship_manager' );
        $this->assertNull( $prop->getValue( $admin ) );
    }

    public function test_handle_championship_admin_page_sets_error_when_manager_not_found(): void {
        $admin = new Admin_Championship( $this->plugin, $this->container );

        $_POST = array(
            'action'              => 'updateFinalResults',
            'racketmanager_nonce' => 'valid_nonce',
            'custom'              => array(),
            'matches'             => array(),
            'home_points'         => array(),
            'away_points'         => array(),
        );
        $_REQUEST = $_POST;

        $league = ( new \ReflectionClass( League::class ) )->newInstanceWithoutConstructor();

        ob_start();
        $admin->handle_championship_admin_page( $league );
        $output = ob_get_clean();

        $this->assertStringContainsString( 'Championship manager not found', (string) $output );
        $this->assertSame( 'error', $admin->error );
    }

    public function test_handle_championship_admin_page_updates_results_when_manager_present(): void {
        $fixture_repo = $this->createStub( \Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface::class );
        $fixture_repo->method( 'find_by_id' )->willReturn( ( new \ReflectionClass( \Racketmanager\Domain\Fixture\Fixture::class ) )->newInstanceWithoutConstructor() );

        $manager = new Championship_Manager(
            $this->createStub( \Racketmanager\Services\Result_Service::class ),
            $fixture_repo,
            $this->createStub( \Racketmanager\Services\League_Service::class ),
            $this->createStub( \Racketmanager\Repositories\Interfaces\Event_Repository_Interface::class ),
            $this->createStub( \Racketmanager\Services\Competition\League_Query_Service::class )
        );

        $admin = new Admin_Championship( $this->plugin, $this->container, $manager );

        $_POST = array(
            'action'              => 'updateFinalResults',
            'racketmanager_nonce' => 'valid_nonce',
            'custom'              => array(),
            'matches'             => array( 101, 102 ),
            'home_points'         => array( 101 => 5, 102 => 4 ),
            'away_points'         => array( 101 => 1, 102 => 2 ),
            'round'               => 2,
        );
        $_REQUEST = $_POST;

        $championship = $this->createStub( \Racketmanager\Domain\Tournament\Championship::class );
        $championship->method( 'num_rounds' )->willReturn( 1 );

        $league = ( new \ReflectionClass( League::class ) )->newInstanceWithoutConstructor();
        $league->championship = $championship;

        ob_start();
        $admin->handle_championship_admin_page( $league );
        $output = ob_get_clean();

        $this->assertStringContainsString( 'Updated Results of 2 fixtures', (string) $output );
        $this->assertFalse( $admin->error );
    }
}
