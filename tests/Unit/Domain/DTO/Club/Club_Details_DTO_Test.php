<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\DTO\Club;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Club;
use Racketmanager\Domain\DTO\Club\Club_Details_DTO;
use Racketmanager\Presenters\Club_Presenter;
use Racketmanager\Tests\Helpers\Club_Test_Factory;
use Racketmanager\Tests\Helpers\Player_Test_Factory;

class Club_Details_DTO_Test extends TestCase {

    public function test_construct_with_full_club_details(): void {
        $club = Club_Test_Factory::create(
            id: 12,
            name: 'Leigh Tennis Club',
            shortcode: 'leigh-tc',
            type: 'tennis',
            website: 'https://example.test',
            contactno: '01234567890',
            founded: 1920,
            facilities: '6 hard courts, clubhouse',
            address: '123 Tennis Lane, SS0 9SB'
        );

        $match_secretary = Player_Test_Factory::create_sparse( [
            'id'           => 99,
            'display_name' => 'John Doe',
            'email'        => 'john@example.test',
            'contactno'    => '01234567890',
        ] );
        $roles           = array(
            (object) array(
                'role'      => 'Secretary',
                'user_name' => 'John Doe',
            ),
        );

        $dto = new Club_Details_DTO( $club, $roles, $match_secretary );

        $this->assertSame( 12, $dto->id );
        $this->assertSame( 'Leigh Tennis Club', $dto->name );
        $this->assertSame( 'leigh-tc', $dto->shortcode );
        $this->assertSame( 'tennis', $dto->type );
        $this->assertSame( 'https://example.test', $dto->website );
        $this->assertSame( '01234567890', $dto->contact_no );
        $this->assertSame( '1920', (string) $dto->founded );
        $this->assertSame( '6 hard courts, clubhouse', $dto->facilities );
        $this->assertSame( '123 Tennis Lane, SS0 9SB', $dto->address );
        $this->assertSame( $match_secretary, $dto->match_secretary );
        $this->assertSame( $roles, $dto->roles );
    }

    public function test_construct_with_minimal_club(): void {
        $club = new Club();
        $dto  = new Club_Details_DTO( $club );

        $this->assertNull( $dto->id );
        $this->assertSame( '', $dto->name );
        $this->assertNull( $dto->shortcode );
        $this->assertNull( $dto->type );
        $this->assertNull( $dto->website );
        $this->assertNull( $dto->contact_no );
        $this->assertNull( $dto->founded );
        $this->assertNull( $dto->facilities );
        $this->assertNull( $dto->address );
        $this->assertNull( $dto->match_secretary );
        $this->assertSame( array(), $dto->roles );
    }

    public function test_club_presenter_integration_with_dto(): void {
        $club      = Club_Test_Factory::create(
            id: 5,
            name: 'Westcliff Hardcourt',
            shortcode: 'westcliff-hard',
            address: '456 Court Way, SS0 1AA'
        );
        $dto       = new Club_Details_DTO( $club );
        $presenter = new Club_Presenter();

        $this->assertSame( '/clubs/westcliff_hard/', $presenter->get_link( $dto ) );
        $this->assertSame( '/clubs/westcliff_hard/roles/', $presenter->get_link( $dto, 'roles' ) );
        $this->assertSame( 'Westcliff Hardcourt', $presenter->get_display_name( $dto ) );
        $this->assertStringContainsString( rawurlencode( '456 Court Way' ), (string) $presenter->get_map_url( $dto, 'MAP_KEY' ) );
    }
}
