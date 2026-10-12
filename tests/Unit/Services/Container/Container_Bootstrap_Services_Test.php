<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Container;

use PHPUnit\Framework\TestCase;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Result_Repository_Interface;
use Racketmanager\Services\Container\Container_Bootstrap;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Fixture\Fixture_Lifecycle_Service;
use Racketmanager\Services\Player_Query_Service;
use Racketmanager\Services\Player_Stats_Service;
use wpdb;

class Container_Bootstrap_Services_Test extends TestCase {
    private Simple_Container $container;
    private $original_wpdb;

    protected function setUp(): void {
        parent::setUp();

        $wpdb_mock = $this->createStub( wpdb::class );
        $wpdb_mock->prefix = 'wp_';
        global $wpdb;
        $this->original_wpdb = $wpdb ?? null;
        $wpdb = $wpdb_mock;

        $app_mock = $this->createStub( RacketManager::class );
        $app_mock->method( 'get_options' )->willReturn( [] );
        $app_mock->site_url = 'https://example.com';
        $app_mock->site_name = 'Test Site';

        $this->container = Container_Bootstrap::boot( $app_mock );
    }

    protected function tearDown(): void {
        global $wpdb;
        $wpdb = $this->original_wpdb;
        parent::tearDown();
    }

    public function test_container_resolves_result_repository(): void {
        $this->assertTrue( $this->container->has( 'result_repository' ) );
        $repo = $this->container->get( 'result_repository' );
        $this->assertInstanceOf( Result_Repository_Interface::class, $repo );
    }

    public function test_container_resolves_fixture_lifecycle_service(): void {
        $this->assertTrue( $this->container->has( 'fixture_lifecycle_service' ) );
        $service = $this->container->get( 'fixture_lifecycle_service' );
        $this->assertInstanceOf( Fixture_Lifecycle_Service::class, $service );
    }

    public function test_container_resolves_player_query_and_stats_services(): void {
        $this->assertTrue( $this->container->has( 'player_query_service' ) );
        $query_service = $this->container->get( 'player_query_service' );
        $this->assertInstanceOf( Player_Query_Service::class, $query_service );

        $this->assertTrue( $this->container->has( 'player_stats_service' ) );
        $stats_service = $this->container->get( 'player_stats_service' );
        $this->assertInstanceOf( Player_Stats_Service::class, $stats_service );
    }
}
