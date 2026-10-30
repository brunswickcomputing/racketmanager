<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Application\Club\Queries;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Club\DTOs\Club_Overview_Result;
use Racketmanager\Application\Club\Queries\Get_Club_Handler;
use Racketmanager\Application\Club\Queries\Get_Club_Query;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\DTO\Club\Club_Details_DTO;
use Racketmanager\Infrastructure\Security\Security_Service_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Settings_Service;
use Racketmanager\Tests\Helpers\Club_Test_Factory;

class Get_Club_Handler_Test extends TestCase {
    private function club(): Club {
        return Club_Test_Factory::create(
            id: 10,
            name: 'Leigh Tennis Club',
            shortcode: 'Leigh LTC'
        );
    }

    private function handler( Club_Service $club_service, Security_Service_Interface $security ): Get_Club_Handler {
        return new Get_Club_Handler(
            $club_service,
            new Settings_Service( [ 'keys' => [ 'googleMapsKey' => 'MAP_KEY' ] ] ),
            $security
        );
    }

    private function club_service_for( Club $club, bool $is_secretary = false ): Club_Service {
        $club_service = $this->createStub( Club_Service::class );
        $club_service->method( 'get_club_by_shortcode' )->willReturn( $club );
        $club_service->method( 'get_club_details' )->willReturn( new Club_Details_DTO( $club ) );
        $club_service->method( 'is_user_match_secretary' )->willReturn( $is_secretary );

        return $club_service;
    }

    public function test_logged_out_user_cannot_update_club(): void {
        $security = $this->createStub( Security_Service_Interface::class );
        $security->method( 'is_user_logged_in' )->willReturn( false );

        $vm = $this->handler( $this->club_service_for( $this->club() ), $security )
            ->handle( new Get_Club_Query( club_name: 'Leigh LTC' ) );

        $this->assertInstanceOf( Club_Overview_Result::class, $vm );
        $this->assertFalse( $vm->user_can_update_club );
        $this->assertSame( 'MAP_KEY', $vm->google_maps_key );
        $this->assertNull( $vm->error_message );
    }

    public function test_admin_capability_can_update_club(): void {
        $security = $this->createStub( Security_Service_Interface::class );
        $security->method( 'is_user_logged_in' )->willReturn( true );
        $security->method( 'get_current_user_id' )->willReturn( 5 );
        $security->method( 'current_user_can' )->willReturn( true );

        $vm = $this->handler( $this->club_service_for( $this->club(), false ), $security )
            ->handle( new Get_Club_Query( club_name: 'Leigh LTC' ) );

        $this->assertTrue( $vm->user_can_update_club );
    }

    public function test_match_secretary_can_update_club(): void {
        $security = $this->createStub( Security_Service_Interface::class );
        $security->method( 'is_user_logged_in' )->willReturn( true );
        $security->method( 'get_current_user_id' )->willReturn( 7 );
        $security->method( 'current_user_can' )->willReturn( false );

        $vm = $this->handler( $this->club_service_for( $this->club(), true ), $security )
            ->handle( new Get_Club_Query( club_name: 'Leigh LTC' ) );

        $this->assertTrue( $vm->user_can_update_club );
    }

    public function test_logged_in_non_secretary_cannot_update_club(): void {
        $security = $this->createStub( Security_Service_Interface::class );
        $security->method( 'is_user_logged_in' )->willReturn( true );
        $security->method( 'get_current_user_id' )->willReturn( 9 );
        $security->method( 'current_user_can' )->willReturn( false );

        $vm = $this->handler( $this->club_service_for( $this->club(), false ), $security )
            ->handle( new Get_Club_Query( club_name: 'Leigh LTC' ) );

        $this->assertFalse( $vm->user_can_update_club );
    }
}
