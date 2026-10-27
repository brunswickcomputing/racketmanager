<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Club;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Club\Club_Role;
use Racketmanager\Domain\Club\Club_Role_Factory;
use Racketmanager\Domain\DTO\Club\Club_Role_Hydration_DTO;
use Racketmanager\Util\Util_Lookup;
use TypeError;

final class Club_Role_Factory_Test extends TestCase {

    public function test_create_from_dto_copies_fields_and_resolves_role_from_role_id(): void {
        $user      = (object) [ 'id' => 10 ];
        $club_role = Club_Role_Factory::create_from_dto( new Club_Role_Hydration_DTO( id: 3, user_id: 10, club_id: 5, role_id: 1, user: $user ) );

        $this->assertInstanceOf( Club_Role::class, $club_role );
        $this->assertSame( 3, $club_role->get_id() );
        $this->assertSame( 10, $club_role->get_user_id() );
        $this->assertSame( 5, $club_role->get_club_id() );
        $this->assertSame( 1, $club_role->get_role_id() );
        $this->assertSame( $user, $club_role->user );
        $this->assertNull( $club_role->club );
        $this->assertEquals( Util_Lookup::get_club_role( '1' ), $club_role->role );
        $this->assertIsObject( $club_role->role );
    }

    public function test_from_object_casts_string_db_row_like_the_legacy_constructor(): void {
        $club_role = Club_Role_Factory::from_object( (object) [ 'id' => '3', 'user_id' => '10', 'club_id' => '5', 'role_id' => '1' ] );

        $this->assertSame( 3, $club_role->id );
        $this->assertSame( 10, $club_role->user_id );
        $this->assertSame( 5, $club_role->club_id );
        $this->assertSame( 1, $club_role->role_id );
        $this->assertEquals( Util_Lookup::get_club_role( '1' ), $club_role->role );
    }

    public function test_from_array_and_lenient_entity_helpers_match_from_object(): void {
        $data = [ 'club_id' => 5, 'role_id' => 1, 'user_id' => 10 ];

        $this->assertEquals( Club_Role_Factory::from_object( (object) $data ), Club_Role_Factory::from_array( $data ) );
        $this->assertEquals( Club_Role_Factory::from_object( (object) $data ), Club_Role::from_object( (object) $data ) );
        $this->assertEquals( Club_Role::from_object( (object) $data ), Club_Role::from_array( $data ) );
        $this->assertNull( Club_Role::from_array( $data )->get_id() );
    }

    public function test_empty_constructor_yields_empty_role_and_raw_object_is_rejected(): void {
        $empty = new Club_Role();
        $this->assertNull( $empty->get_id() );
        $this->assertNull( $empty->role );

        $this->expectException( TypeError::class );
        $this->assertInstanceOf(
            Club_Role::class,
            new Club_Role( (object) [ 'id' => 1 ] )
        );
    }
}
