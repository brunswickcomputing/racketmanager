<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Rest;

use PHPUnit\Framework\TestCase;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Rest\Rest_Routes;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Export\Export_Service;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Season_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

require_once __DIR__ . '/../../wp-stubs.php';

final class Rest_Routes_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private RacketManager $plugin;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();

        $this->plugin = $this->createStub( RacketManager::class );

        $container = new Simple_Container();
        $container->set( 'competition_service', fn() => $this->createStub( Competition_Service::class ) );
        $container->set( 'club_service', fn() => $this->createStub( Club_Service::class ) );
        $container->set( 'season_service', fn() => $this->createStub( Season_Service::class ) );
        $container->set( 'player_service', fn() => $this->createStub( Player_Service::class ) );
        $container->set( 'registration_service', fn() => $this->createStub( Registration_Service::class ) );
        $container->set( 'finance_service', fn() => $this->createStub( Finance_Service::class ) );
        $container->set( 'exporter', fn() => $this->createStub( Export_Service::class ) );
        $container->set( 'league_query_service', new League_Query_Service(
            $this->createStub( League_Repository_Interface::class ),
            $this->createStub( Fixture_Repository_Interface::class ),
            $this->createStub( Rubber_Repository_Interface::class )
        ) );

        $this->plugin->container = $container;
        $GLOBALS['racketmanager'] = $this->plugin;
        $GLOBALS['wp_stubs_registered_rest_routes'] = [];
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_constructor_registers_rest_api_init_action(): void {
        $routes = new Rest_Routes( $this->plugin );

        self::assertTrue( has_action( 'rest_api_init', [ $routes, 'register_rest_routes' ] ) !== false );
    }

    public function test_register_rest_routes_registers_all_endpoints(): void {
        $routes = new Rest_Routes( $this->plugin );
        $routes->register_rest_routes();

        $registered = $GLOBALS['wp_stubs_registered_rest_routes']['racketmanager/v1'] ?? [];

        self::assertArrayHasKey( '/standings', $registered );
        self::assertArrayHasKey( '/stripe', $registered );
        self::assertArrayHasKey( '/export/calendar', $registered );
        self::assertArrayHasKey( '/export/results', $registered );
        self::assertArrayHasKey( '/export/fixtures', $registered );
    }
}
