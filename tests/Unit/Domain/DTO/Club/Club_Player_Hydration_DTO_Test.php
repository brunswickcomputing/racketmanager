<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\DTO\Club;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Club\Club_Player_Hydration_DTO;

final class Club_Player_Hydration_DTO_Test extends TestCase {

    public function test_from_object_casts_string_db_row_to_declared_types(): void {
        $dto = Club_Player_Hydration_DTO::from_object( (object) [
            'id'             => '3',
            'player_id'      => '10',
            'system_record'  => '1',
            'club_id'        => '5',
            'removed_date'   => null,
            'removed_user'   => null,
            'created_date'   => '2026-01-01 00:00:00',
            'created_user'   => '2',
            'requested_date' => '2026-01-01',
            'requested_user' => '2',
            'status'         => 'active',
        ] );

        $this->assertSame( 3, $dto->id );
        $this->assertSame( 10, $dto->player_id );
        $this->assertTrue( $dto->system_record );
        $this->assertSame( 5, $dto->club_id );
        $this->assertNull( $dto->removed_date );
        $this->assertNull( $dto->removed_user );
        $this->assertSame( '2026-01-01 00:00:00', $dto->created_date );
        $this->assertSame( 2, $dto->created_user );
        $this->assertSame( '2026-01-01', $dto->requested_date );
        $this->assertSame( 2, $dto->requested_user );
        $this->assertSame( 'active', $dto->status );
        $this->assertNull( $dto->player );
    }

    public function test_empty_columns_follow_legacy_coercion(): void {
        $dto = Club_Player_Hydration_DTO::from_object( (object) [
            'id'            => '',
            'player_id'     => '',
            'removed_user'  => '',
            'system_record' => '',
            'removed_date'  => '',
        ] );

        $this->assertSame( 0, $dto->id );
        $this->assertSame( 0, $dto->player_id );
        $this->assertSame( 0, $dto->removed_user );
        $this->assertFalse( $dto->system_record );
        $this->assertSame( '', $dto->removed_date );
        $this->assertNull( $dto->club_id );
        $this->assertNull( $dto->status );
    }

    public function test_attached_player_object_is_kept_and_unknown_keys_dropped(): void {
        $player = (object) [ 'id' => 10 ];
        $dto    = Club_Player_Hydration_DTO::from_object( (object) [ 'id' => 1, 'player' => $player, 'team' => 'x' ] );

        $this->assertSame( $player, $dto->player );
        $this->assertFalse( property_exists( $dto, 'team' ) );
    }

    public function test_from_array_matches_from_object(): void {
        $data = [ 'id' => '1', 'player_id' => '2', 'club_id' => '3', 'status' => 'pending' ];

        $this->assertEquals( Club_Player_Hydration_DTO::from_object( (object) $data ), Club_Player_Hydration_DTO::from_array( $data ) );
    }
}
