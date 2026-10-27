<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\DTO\Club;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Club\Club_Role_Hydration_DTO;

final class Club_Role_Hydration_DTO_Test extends TestCase {

    public function test_from_object_casts_string_db_row_to_declared_types(): void {
        $dto = Club_Role_Hydration_DTO::from_object( (object) [ 'id' => '3', 'user_id' => '10', 'club_id' => '5', 'role_id' => '1' ] );

        $this->assertSame( 3, $dto->id );
        $this->assertSame( 10, $dto->user_id );
        $this->assertSame( 5, $dto->club_id );
        $this->assertSame( 1, $dto->role_id );
        $this->assertNull( $dto->role );
        $this->assertNull( $dto->club );
        $this->assertNull( $dto->user );
    }

    public function test_empty_columns_become_zero_and_absent_columns_stay_null(): void {
        $dto = Club_Role_Hydration_DTO::from_object( (object) [ 'id' => '', 'user_id' => '' ] );

        $this->assertSame( 0, $dto->id );
        $this->assertSame( 0, $dto->user_id );
        $this->assertNull( $dto->club_id );
        $this->assertNull( $dto->role_id );
    }

    public function test_attached_objects_are_kept_and_scalars_in_their_place_dropped(): void {
        $user = (object) [ 'id' => 10 ];
        $dto  = Club_Role_Hydration_DTO::from_object( (object) [ 'id' => 1, 'user' => $user, 'club' => 'not an object', 'extra' => 1 ] );

        $this->assertSame( $user, $dto->user );
        $this->assertNull( $dto->club );
        $this->assertFalse( property_exists( $dto, 'extra' ) );
    }

    public function test_from_array_matches_from_object(): void {
        $data = [ 'club_id' => 5, 'role_id' => 2, 'user_id' => 10 ];

        $this->assertEquals( Club_Role_Hydration_DTO::from_object( (object) $data ), Club_Role_Hydration_DTO::from_array( $data ) );
    }
}
