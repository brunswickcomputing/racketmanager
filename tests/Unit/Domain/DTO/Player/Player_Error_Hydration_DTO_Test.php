<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\DTO\Player;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Player\Player_Error_Hydration_DTO;

final class Player_Error_Hydration_DTO_Test extends TestCase {

    public function test_from_object_casts_string_db_row_to_declared_types(): void {
        $dto = Player_Error_Hydration_DTO::from_object( (object) [
            'id'           => '3',
            'player_id'    => '4',
            'message'      => 'WTN lookup failed',
            'created_date' => '2026-01-01 00:00:00',
        ] );

        $this->assertSame( 3, $dto->id );
        $this->assertSame( 4, $dto->player_id );
        $this->assertSame( 'WTN lookup failed', $dto->message );
        $this->assertSame( '2026-01-01 00:00:00', $dto->created_date );
    }

    public function test_missing_null_and_empty_identifiers_become_null(): void {
        $dto = Player_Error_Hydration_DTO::from_object( (object) [ 'id' => '', 'player_id' => null, 'message' => 'm' ] );

        $this->assertNull( $dto->id );
        $this->assertNull( $dto->player_id );
        $this->assertSame( 'm', $dto->message );
        $this->assertNull( $dto->created_date );
    }

    public function test_unknown_keys_are_dropped(): void {
        $dto = Player_Error_Hydration_DTO::from_object( (object) [ 'id' => 1, 'status' => 1, 'updated_user' => 2, 'updated_date' => 'x' ] );

        $this->assertSame( [ 'id', 'player_id', 'message', 'created_date' ], array_keys( get_object_vars( $dto ) ) );
    }

    public function test_from_array_matches_from_object(): void {
        $data = [ 'player_id' => '9', 'message' => 'm', 'created_date' => 'd' ];

        $this->assertEquals( Player_Error_Hydration_DTO::from_object( (object) $data ), Player_Error_Hydration_DTO::from_array( $data ) );
    }
}
