<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Club;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Club\Club_Player;
use Racketmanager\Domain\Club\Club_Player_Factory;
use Racketmanager\Domain\DTO\Club\Club_Player_Hydration_DTO;

final class Club_Player_Factory_Test extends TestCase {

    public function test_create_from_dto_copies_every_field_including_private_status(): void {
        $player      = (object) [ 'id' => 10 ];
        $club_player = Club_Player_Factory::create_from_dto( new Club_Player_Hydration_DTO(
            id: 3,
            player_id: 10,
            club_id: 5,
            created_date: '2026-01-01 00:00:00',
            created_user: 2,
            requested_date: '2026-01-01',
            requested_user: 2,
            player: $player,
            system_record: true,
            status: 'active',
        ) );

        $this->assertInstanceOf( Club_Player::class, $club_player );
        $this->assertSame( 3, $club_player->get_id() );
        $this->assertSame( 10, $club_player->player_id );
        $this->assertSame( 5, $club_player->club_id );
        $this->assertSame( '2026-01-01 00:00:00', $club_player->created_date );
        $this->assertSame( 2, $club_player->created_user );
        $this->assertSame( '2026-01-01', $club_player->requested_date );
        $this->assertSame( 2, $club_player->requested_user );
        $this->assertSame( $player, $club_player->player );
        $this->assertTrue( $club_player->system_record );
        $this->assertSame( 'active', $club_player->get_status() );
        $this->assertNull( $club_player->removed_date );
    }

    public function test_from_object_casts_string_db_row_like_the_legacy_constructor(): void {
        $club_player = Club_Player_Factory::from_object( (object) [
            'id'             => '3',
            'player_id'      => '10',
            'system_record'  => '',
            'club_id'        => '5',
            'removed_date'   => null,
            'removed_user'   => '',
            'requested_date' => null,
            'requested_user' => null,
            'status'         => 'pending',
        ] );

        $this->assertSame( 3, $club_player->id );
        $this->assertSame( 10, $club_player->player_id );
        $this->assertFalse( $club_player->system_record );
        $this->assertNull( $club_player->removed_date );
        $this->assertSame( 0, $club_player->removed_user );
        $this->assertNull( $club_player->requested_date );
        $this->assertNull( $club_player->requested_user );
        $this->assertSame( 'pending', $club_player->get_status() );
    }

    public function test_from_array_and_lenient_entity_helpers_match_from_object(): void {
        $data = [ 'club_id' => 5, 'player_id' => 10, 'requested_date' => '2026-01-01', 'requested_user' => 2, 'status' => 'pending' ];

        $this->assertEquals( Club_Player_Factory::from_object( (object) $data ), Club_Player_Factory::from_array( $data ) );
        $this->assertEquals( Club_Player_Factory::from_object( (object) $data ), Club_Player::from_object( (object) $data ) );
        $this->assertEquals( Club_Player::from_object( (object) $data ), Club_Player::from_array( $data ) );
    }

    public function test_empty_constructor_yields_empty_registration_and_raw_object_is_rejected(): void {
        $empty = new Club_Player();
        $this->assertNull( $empty->get_id() );
        $this->assertNull( $empty->get_status() );

        $this->expectException( \TypeError::class );
        new Club_Player( (object) [ 'id' => 1 ] );
    }
}
