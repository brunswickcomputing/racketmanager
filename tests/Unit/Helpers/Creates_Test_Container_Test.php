<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;
use Racketmanager\RacketManager;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Tests\Helpers\Club_Test_Factory;
use Racketmanager\Tests\Helpers\Creates_Test_Container;
use stdClass;

class Creates_Test_Container_Test extends TestCase {
    use Creates_Test_Container;

    public function test_create_container_with_services_registers_and_preserves_instance_identity(): void {
        $service_one = $this->createStub( Club_Service::class );
        $service_two = new stdClass();

        $container = $this->create_container_with_services( [
            'club_service' => $service_one,
            'custom_obj'   => $service_two,
        ] );

        $this->assertInstanceOf( Simple_Container::class, $container );
        $this->assertSame( $service_one, $container->get( 'club_service' ) );
        $this->assertSame( $service_two, $container->get( 'custom_obj' ) );
    }

    public function test_create_app_with_services_attaches_container_and_preserves_mock_identity(): void {
        $club    = Club_Test_Factory::create( id: 1 );
        $service = $this->createMock( Club_Service::class );
        $service->expects( $this->once() )->method( 'get_club' )->with( 1 )->willReturn( $club );

        $app = $this->create_app_with_services( [
            'club_service' => $service,
        ] );

        $this->assertInstanceOf( RacketManager::class, $app );
        $this->assertInstanceOf( Simple_Container::class, $app->container );
        $this->assertSame( $service, $app->container->get( 'club_service' ) );
        $this->assertSame( $club, $app->container->get( 'club_service' )->get_club( 1 ) );
    }

    public function test_create_app_with_services_handles_empty_services(): void {
        $app = $this->create_app_with_services();

        $this->assertInstanceOf( RacketManager::class, $app );
        $this->assertInstanceOf( Simple_Container::class, $app->container );
    }
}
