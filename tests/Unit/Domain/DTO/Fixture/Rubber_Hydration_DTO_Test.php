<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\DTO\Fixture;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Fixture\Rubber_Hydration_DTO;

final class Rubber_Hydration_DTO_Test extends TestCase {

    public function test_from_object_casts_string_db_row_and_merges_serialized_custom(): void {
        $custom = serialize( [ 'sets' => [ 1 => [ 'player1' => '6', 'player2' => '3' ] ], 'stats' => [ 'aces' => 2 ], 'walkover' => 'home', 'share' => true ] );
        $dto    = Rubber_Hydration_DTO::from_object( (object) [
            'id'            => '7',
            'match_id'      => '12',
            'group'         => 'A',
            'date'          => '2026-03-01 19:30',
            'home_points'   => '6',
            'away_points'   => '3.5',
            'winner_id'     => '5',
            'loser_id'      => '6',
            'post_id'       => '0',
            'type'          => 'MD',
            'custom'        => $custom,
            'rubber_number' => '2',
            'status'        => '1',
        ] );

        $this->assertSame( 7, $dto->id );
        $this->assertSame( 12, $dto->match_id );
        $this->assertSame( 'A', $dto->group );
        $this->assertSame( '2026-03-01 19:30', $dto->date );
        $this->assertSame( 6.0, $dto->home_points );
        $this->assertSame( 3.5, $dto->away_points );
        $this->assertSame( 5, $dto->winner_id );
        $this->assertSame( 6, $dto->loser_id );
        $this->assertSame( 0, $dto->post_id );
        $this->assertSame( 'MD', $dto->type );
        $this->assertSame( 2, $dto->rubber_number );
        $this->assertSame( 1, $dto->status );
        $this->assertSame( [ 1 => [ 'player1' => '6', 'player2' => '3' ] ], $dto->sets );
        $this->assertSame( [ 'aces' => 2 ], $dto->stats );
        $this->assertSame( 'home', $dto->walkover );
        $this->assertSame( '1', $dto->share );
        $this->assertNull( $dto->retired );
        $this->assertIsArray( $dto->custom );
        $this->assertSame( 'home', $dto->custom['walkover'] );
    }

    public function test_empty_columns_follow_legacy_coercion_and_absent_columns_stay_null(): void {
        $dto = Rubber_Hydration_DTO::from_object( (object) [
            'id'          => '',
            'match_id'    => '',
            'status'      => '',
            'home_points' => '',
            'custom'      => '',
            'date'        => null,
        ] );

        $this->assertSame( 0, $dto->id );
        $this->assertSame( 0, $dto->match_id );
        $this->assertSame( 0, $dto->status );
        $this->assertSame( 0.0, $dto->home_points );
        $this->assertNull( $dto->away_points );
        $this->assertSame( '', $dto->custom );
        $this->assertNull( $dto->sets );
        $this->assertNull( $dto->date );
        $this->assertNull( $dto->type );
        $this->assertNull( $dto->players );
        $this->assertNull( $dto->reverse_rubbers );
    }

    public function test_false_id_array_custom_and_attachments_pass_through(): void {
        $dto = Rubber_Hydration_DTO::from_object( (object) [
            'id'              => false,
            'match_id'        => 2,
            'custom'          => [ 'sets' => [ 'a' ], 'retired' => 'away' ],
            'players'         => [ 'home' => [ 1 ] ],
            'reverse_rubbers' => 1,
            'reverse_rubber'  => '0',
            'class'           => 'alternate',
        ] );

        $this->assertFalse( $dto->id );
        $this->assertSame( [ 'a' ], $dto->sets );
        $this->assertSame( 'away', $dto->retired );
        $this->assertSame( [ 'home' => [ 1 ] ], $dto->players );
        $this->assertTrue( $dto->reverse_rubbers );
        $this->assertFalse( $dto->reverse_rubber );
        $this->assertSame( 'alternate', $dto->class );
    }

    public function test_slashed_custom_is_unslashed_and_unknown_keys_are_dropped(): void {
        $dto = Rubber_Hydration_DTO::from_object( (object) [
            'id'      => 1,
            'custom'  => [ 'sets' => [], 'invalid' => addslashes( "o'b" ) ],
            'unknown' => 'x',
        ] );

        $this->assertSame( "o'b", $dto->invalid );
        $this->assertSame( "o'b", $dto->custom['invalid'] );
        $this->assertFalse( property_exists( $dto, 'unknown' ) );
    }

    public function test_from_array_matches_from_object(): void {
        $data = [ 'id' => '3', 'match_id' => '4', 'date' => '2026-01-01 10:00', 'custom' => serialize( [ 'sets' => [] ] ) ];

        $this->assertEquals( Rubber_Hydration_DTO::from_object( (object) $data ), Rubber_Hydration_DTO::from_array( $data ) );
    }
}
