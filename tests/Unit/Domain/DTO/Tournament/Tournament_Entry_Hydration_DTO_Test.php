<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\DTO\Tournament;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Tournament\Tournament_Entry_Hydration_DTO;

final class Tournament_Entry_Hydration_DTO_Test extends TestCase {

    public function test_from_object_casts_string_db_row_to_ints(): void {
        $dto = Tournament_Entry_Hydration_DTO::from_object( (object) [
            'id'            => '55',
            'tournament_id' => '12',
            'player_id'     => '34',
            'status'        => '1',
            'fee'           => '15.00',
            'club_id'       => '7',
        ] );

        $this->assertSame( 55, $dto->id );
        $this->assertSame( 12, $dto->tournament_id );
        $this->assertSame( 34, $dto->player_id );
        $this->assertSame( 1, $dto->status );
        $this->assertSame( 15, $dto->fee );
        $this->assertSame( 7, $dto->club_id );
    }

    public function test_null_empty_and_missing_columns_become_null(): void {
        $dto = Tournament_Entry_Hydration_DTO::from_object( (object) [ 'tournament_id' => 1, 'fee' => null, 'club_id' => '' ] );

        $this->assertNull( $dto->id );
        $this->assertSame( 1, $dto->tournament_id );
        $this->assertNull( $dto->player_id );
        $this->assertNull( $dto->status );
        $this->assertNull( $dto->fee );
        $this->assertNull( $dto->club_id );
    }

    public function test_unknown_keys_are_dropped(): void {
        $dto = Tournament_Entry_Hydration_DTO::from_object( (object) [ 'id' => 1, 'club' => (object) [ 'id' => 2 ], 'player' => 'x' ] );

        $this->assertSame( [ 'id', 'tournament_id', 'player_id', 'status', 'fee', 'club_id' ], array_keys( get_object_vars( $dto ) ) );
    }

    public function test_from_array_matches_from_object(): void {
        $data = [ 'id' => '3', 'tournament_id' => '4', 'player_id' => '5', 'status' => '0' ];

        $this->assertEquals( Tournament_Entry_Hydration_DTO::from_object( (object) $data ), Tournament_Entry_Hydration_DTO::from_array( $data ) );
    }
}
