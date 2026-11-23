<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Club;

use Error;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Club\Club_Role;
use Racketmanager\Domain\Club\Club_Role_Factory;
use Racketmanager\Domain\DTO\Club\Club_Role_Hydration_DTO;
use Racketmanager\Domain\Enums\Club_Role as Club_Role_Enum;
use Racketmanager\Util\Util_Lookup;

final class Club_Role_Test extends TestCase {

    public function test_empty_instantiation_defaults_all_properties(): void {
        $club_role = new Club_Role();

        $this->assertNull( $club_role->id );
        $this->assertNull( $club_role->get_id() );
        $this->assertSame( 0, $club_role->user_id );
        $this->assertSame( 0, $club_role->get_user_id() );
        $this->assertSame( 0, $club_role->club_id );
        $this->assertSame( 0, $club_role->get_club_id() );
        $this->assertSame( 0, $club_role->role_id );
        $this->assertSame( 0, $club_role->get_role_id() );
        $this->assertNull( $club_role->role );
        $this->assertNull( $club_role->get_role() );
        $this->assertNull( $club_role->club );
        $this->assertNull( $club_role->get_club() );
        $this->assertNull( $club_role->user );
        $this->assertNull( $club_role->get_user() );

        $this->assertNull( $club_role->get_role_enum() );
        $this->assertFalse( $club_role->is_match_secretary() );
        $this->assertFalse( $club_role->is_coach() );
        $this->assertFalse( $club_role->is_treasurer() );
    }

    public function test_hydration_roundtrip_from_object(): void {
        $user_obj = (object) [ 'id' => 45, 'display_name' => 'John Doe' ];
        $club_obj = (object) [ 'id' => 78, 'name' => 'Leigh Tennis Club' ];

        $row = (object) [
            'id'      => 12,
            'user_id' => 45,
            'club_id' => 78,
            'role_id' => 1,
            'club'    => $club_obj,
            'user'    => $user_obj,
        ];

        $club_role = Club_Role::from_object( $row );

        $this->assertSame( 12, $club_role->id );
        $this->assertSame( 12, $club_role->get_id() );
        $this->assertSame( 45, $club_role->user_id );
        $this->assertSame( 45, $club_role->get_user_id() );
        $this->assertSame( 78, $club_role->club_id );
        $this->assertSame( 78, $club_role->get_club_id() );
        $this->assertSame( 1, $club_role->role_id );
        $this->assertSame( 1, $club_role->get_role_id() );
        $this->assertEquals( Util_Lookup::get_club_role( '1' ), $club_role->role );
        $this->assertEquals( Util_Lookup::get_club_role( '1' ), $club_role->get_role() );
        $this->assertSame( 'Match secretary', $club_role->role->desc );
        $this->assertSame( 'Match secretary', $club_role->get_role()->desc );
        $this->assertSame( $club_obj, $club_role->club );
        $this->assertSame( $club_obj, $club_role->get_club() );
        $this->assertSame( $user_obj, $club_role->user );
        $this->assertSame( $user_obj, $club_role->get_user() );
    }

    public function test_hydration_roundtrip_from_array(): void {
        $user_obj = (object) [ 'id' => 101 ];
        $club_obj = (object) [ 'id' => 202 ];

        $data = [
            'id'      => 99,
            'user_id' => 101,
            'club_id' => 202,
            'role_id' => 2,
            'club'    => $club_obj,
            'user'    => $user_obj,
        ];

        $club_role = Club_Role::from_array( $data );

        $this->assertSame( 99, $club_role->id );
        $this->assertSame( 99, $club_role->get_id() );
        $this->assertSame( 101, $club_role->user_id );
        $this->assertSame( 101, $club_role->get_user_id() );
        $this->assertSame( 202, $club_role->club_id );
        $this->assertSame( 202, $club_role->get_club_id() );
        $this->assertSame( 2, $club_role->role_id );
        $this->assertSame( 2, $club_role->get_role_id() );
        $this->assertEquals( Util_Lookup::get_club_role( '2' ), $club_role->role );
        $this->assertEquals( Util_Lookup::get_club_role( '2' ), $club_role->get_role() );
        $this->assertSame( 'Coach', $club_role->role->desc );
        $this->assertSame( 'Coach', $club_role->get_role()->desc );
        $this->assertSame( $club_obj, $club_role->club );
        $this->assertSame( $club_obj, $club_role->get_club() );
        $this->assertSame( $user_obj, $club_role->user );
        $this->assertSame( $user_obj, $club_role->get_user() );
    }

    public function test_factory_auto_populates_role_object_from_role_id(): void {
        $row = (object) [
            'id'      => 5,
            'user_id' => 10,
            'club_id' => 20,
            'role_id' => 1,
        ];

        $club_role = Club_Role_Factory::from_object( $row );

        $this->assertSame( 1, $club_role->role_id );
        $this->assertSame( 1, $club_role->get_role_id() );
        $this->assertIsObject( $club_role->role );
        $this->assertIsObject( $club_role->get_role() );
        $this->assertEquals( Util_Lookup::get_club_role( '1' ), $club_role->role );
        $this->assertEquals( Util_Lookup::get_club_role( '1' ), $club_role->get_role() );
        $this->assertSame( 'Match secretary', $club_role->role->desc );
    }

    public function test_factory_unknown_role_id_hydrates_with_null_role_and_false_classification(): void {
        $row = (object) [
            'id'      => 8,
            'user_id' => 12,
            'club_id' => 22,
            'role_id' => 999,
        ];

        $club_role = Club_Role_Factory::from_object( $row );

        $this->assertSame( 999, $club_role->role_id );
        $this->assertNull( $club_role->role );
        $this->assertNull( $club_role->get_role() );
        $this->assertNull( $club_role->get_role_enum() );
        $this->assertFalse( $club_role->is_match_secretary() );
        $this->assertFalse( $club_role->is_coach() );
        $this->assertFalse( $club_role->is_treasurer() );
    }

    public function test_factory_auto_populates_role_id_from_role_object(): void {
        $role_obj = (object) [ 'desc' => 'Match secretary' ];
        $data     = [
            'id'      => 6,
            'user_id' => 11,
            'club_id' => 21,
            'role'    => $role_obj,
        ];

        $club_role = Club_Role_Factory::from_array( $data );

        $expected_role_id = Util_Lookup::get_club_role_ref( 'Match secretary' );
        $this->assertSame( 1, $expected_role_id );
        $this->assertSame( $expected_role_id, $club_role->role_id );
        $this->assertSame( $expected_role_id, $club_role->get_role_id() );
        $this->assertSame( $role_obj, $club_role->role );
        $this->assertSame( $role_obj, $club_role->get_role() );
    }

    public function test_direct_dto_instantiation_with_role_object_resolves_role_id(): void {
        $dto       = new Club_Role_Hydration_DTO(
            id: 7,
            user_id: 12,
            club_id: 22,
            role: (object) [ 'desc' => 'Coach' ],
        );
        $club_role = new Club_Role( $dto );

        $this->assertSame( 2, $club_role->role_id );
        $this->assertSame( 2, $club_role->get_role_id() );
        $this->assertSame( 'Coach', $club_role->role->desc );
    }

    public function test_typed_getters_and_setters_mutate_and_access_all_properties(): void {
        $club_role = new Club_Role();

        $club_role->set_id( 100 );
        $this->assertSame( 100, $club_role->id );
        $this->assertSame( 100, $club_role->get_id() );

        $club_role->set_id( null );
        $this->assertNull( $club_role->id );
        $this->assertNull( $club_role->get_id() );

        $club_role->set_user_id( 200 );
        $this->assertSame( 200, $club_role->user_id );
        $this->assertSame( 200, $club_role->get_user_id() );

        $club_role->set_club_id( 300 );
        $this->assertSame( 300, $club_role->club_id );
        $this->assertSame( 300, $club_role->get_club_id() );

        $club_role->set_role_id( 2 );
        $this->assertSame( 2, $club_role->role_id );
        $this->assertSame( 2, $club_role->get_role_id() );

        $role_obj = (object) [ 'desc' => 'Coach' ];
        $club_role->set_role( $role_obj );
        $this->assertSame( $role_obj, $club_role->role );
        $this->assertSame( $role_obj, $club_role->get_role() );

        $club_role->set_role( null );
        $this->assertNull( $club_role->role );
        $this->assertNull( $club_role->get_role() );

        $club_obj = (object) [ 'name' => 'Westcliff LTC' ];
        $club_role->set_club( $club_obj );
        $this->assertSame( $club_obj, $club_role->club );
        $this->assertSame( $club_obj, $club_role->get_club() );

        $club_role->set_club( null );
        $this->assertNull( $club_role->club );
        $this->assertNull( $club_role->get_club() );

        $user_obj = (object) [ 'name' => 'Jane Doe' ];
        $club_role->set_user( $user_obj );
        $this->assertSame( $user_obj, $club_role->user );
        $this->assertSame( $user_obj, $club_role->get_user() );

        $club_role->set_user( null );
        $this->assertNull( $club_role->user );
        $this->assertNull( $club_role->get_user() );
    }

    public function test_role_classification_helpers_and_enum_cases(): void {
        $club_role = new Club_Role();

        $club_role->set_role_id( Club_Role_Enum::MATCH_SECRETARY->value );
        $this->assertSame( Club_Role_Enum::MATCH_SECRETARY, $club_role->get_role_enum() );
        $this->assertTrue( $club_role->is_match_secretary() );
        $this->assertFalse( $club_role->is_coach() );
        $this->assertFalse( $club_role->is_treasurer() );

        $club_role->set_role_id( Club_Role_Enum::COACH->value );
        $this->assertSame( Club_Role_Enum::COACH, $club_role->get_role_enum() );
        $this->assertFalse( $club_role->is_match_secretary() );
        $this->assertTrue( $club_role->is_coach() );
        $this->assertFalse( $club_role->is_treasurer() );

        $club_role->set_role_id( Club_Role_Enum::TREASURER->value );
        $this->assertSame( Club_Role_Enum::TREASURER, $club_role->get_role_enum() );
        $this->assertFalse( $club_role->is_match_secretary() );
        $this->assertFalse( $club_role->is_coach() );
        $this->assertTrue( $club_role->is_treasurer() );

        $club_role->set_role_id( 999 );
        $this->assertNull( $club_role->get_role_enum() );
        $this->assertFalse( $club_role->is_match_secretary() );
        $this->assertFalse( $club_role->is_coach() );
        $this->assertFalse( $club_role->is_treasurer() );
    }

    public function test_reassign_user_updates_user_id_and_resets_user_object(): void {
        $club_role = new Club_Role();
        $old_user  = (object) [ 'id' => 10 ];
        $club_role->set_user_id( 10 );
        $club_role->set_user( $old_user );

        $this->assertSame( 10, $club_role->user_id );
        $this->assertSame( $old_user, $club_role->user );

        $club_role->reassign_user( 25 );

        $this->assertSame( 25, $club_role->user_id );
        $this->assertSame( 25, $club_role->get_user_id() );
        $this->assertNull( $club_role->user );
        $this->assertNull( $club_role->get_user() );
    }

    public function test_reassign_user_updates_user_id_and_sets_new_user_object(): void {
        $club_role = new Club_Role();
        $old_user  = (object) [ 'id' => 10 ];
        $new_user  = (object) [ 'id' => 30 ];
        $club_role->set_user_id( 10 );
        $club_role->set_user( $old_user );

        $club_role->reassign_user( 30, $new_user );

        $this->assertSame( 30, $club_role->user_id );
        $this->assertSame( 30, $club_role->get_user_id() );
        $this->assertSame( $new_user, $club_role->user );
        $this->assertSame( $new_user, $club_role->get_user() );
    }

    public function test_external_mutation_of_id_throws_error(): void {
        $club_role = new Club_Role();

        $this->expectException( Error::class );
        $this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property/' );

        $club_role->id = 123;
    }

    public function test_external_mutation_of_user_id_throws_error(): void {
        $club_role = new Club_Role();

        $this->expectException( Error::class );
        $this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property/' );

        $club_role->user_id = 123;
    }

    public function test_external_mutation_of_club_id_throws_error(): void {
        $club_role = new Club_Role();

        $this->expectException( Error::class );
        $this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property/' );

        $club_role->club_id = 123;
    }

    public function test_external_mutation_of_role_id_throws_error(): void {
        $club_role = new Club_Role();

        $this->expectException( Error::class );
        $this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property/' );

        $club_role->role_id = 123;
    }

    public function test_external_mutation_of_role_throws_error(): void {
        $club_role = new Club_Role();

        $this->expectException( Error::class );
        $this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property/' );

        $club_role->role = (object) [];
    }

    public function test_external_mutation_of_club_throws_error(): void {
        $club_role = new Club_Role();

        $this->expectException( Error::class );
        $this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property/' );

        $club_role->club = (object) [];
    }

    public function test_external_mutation_of_user_throws_error(): void {
        $club_role = new Club_Role();

        $this->expectException( Error::class );
        $this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property/' );

        $club_role->user = (object) [];
    }
}
