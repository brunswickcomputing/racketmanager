<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Player;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Player\Player_Ranking;

/**
 * @covers \Racketmanager\Domain\Player\Player_Ranking
 */
final class Player_Ranking_Test extends TestCase {

    public function test_ranked_player(): void {
        $ranking = Player_Ranking::create( 1, 8.5, 1200.0, 'singles' );

        self::assertTrue( $ranking->is_ranked() );
        self::assertTrue( $ranking->has_rating() );
        self::assertSame( 1, $ranking->get_rank() );
        self::assertSame( 8.5, $ranking->get_rating() );
        self::assertSame( '8.5', $ranking->get_raw_value() );
        self::assertSame( 1200.0, $ranking->get_points() );
        self::assertSame( 'singles', $ranking->get_discipline() );
    }

    public function test_unranked_player(): void {
        $unranked = Player_Ranking::unranked( 6.2, discipline: 'doubles' );

        self::assertFalse( $unranked->is_ranked() );
        self::assertTrue( $unranked->has_rating() );
        self::assertSame( 6.2, $unranked->get_rating() );
        self::assertSame( '6.2', $unranked->get_raw_value() );
        self::assertSame( 'doubles', $unranked->get_discipline() );
    }

    public function test_from_raw_numeric_and_precision_preservation(): void {
        // Integer strings
        $r_int = Player_Ranking::from_raw( '40', 'singles' );
        self::assertTrue( $r_int->has_rating() );
        self::assertSame( 40.0, $r_int->get_rating() );
        self::assertSame( '40', $r_int->get_raw_value() );

        // Multiple decimal places
        $r_dec = Player_Ranking::from_raw( '24.53', 'doubles' );
        self::assertTrue( $r_dec->has_rating() );
        self::assertSame( 24.53, $r_dec->get_rating() );
        self::assertSame( '24.53', $r_dec->get_raw_value() );

        // Float and integer scalar values
        $r_float = Player_Ranking::from_raw( 7.0 );
        self::assertTrue( $r_float->has_rating() );
        self::assertSame( 7.0, $r_float->get_rating() );

        $r_int_val = Player_Ranking::from_raw( 7 );
        self::assertTrue( $r_int_val->has_rating() );
        self::assertSame( 7.0, $r_int_val->get_rating() );
        self::assertSame( '7', $r_int_val->get_raw_value() );

        // Trimmed whitespace
        $r_space = Player_Ranking::from_raw( ' 12.3' );
        self::assertTrue( $r_space->has_rating() );
        self::assertSame( 12.3, $r_space->get_rating() );
        self::assertSame( ' 12.3', $r_space->get_raw_value() );
    }

    public function test_from_raw_non_numeric_and_negative_inputs(): void {
        // Placeholders and empty values
        $r_dash = Player_Ranking::from_raw( '-' );
        self::assertFalse( $r_dash->has_rating() );
        self::assertNull( $r_dash->get_rating() );
        self::assertSame( '-', $r_dash->get_raw_value() );

        $r_na = Player_Ranking::from_raw( 'N/A' );
        self::assertFalse( $r_na->has_rating() );
        self::assertSame( 'N/A', $r_na->get_raw_value() );

        $r_empty = Player_Ranking::from_raw( '' );
        self::assertFalse( $r_empty->has_rating() );
        self::assertSame( '', $r_empty->get_raw_value() );

        $r_null = Player_Ranking::from_raw( null );
        self::assertFalse( $r_null->has_rating() );
        self::assertSame( '', $r_null->get_raw_value() );

        // Negative values
        $r_neg = Player_Ranking::from_raw( '-1' );
        self::assertFalse( $r_neg->has_rating() );
        self::assertNull( $r_neg->get_rating() );
        self::assertSame( '-1', $r_neg->get_raw_value() );
    }

    public function test_invalid_rank_throws(): void {
        $this->expectException( InvalidArgumentException::class );
        Player_Ranking::create( 0 );
    }

    public function test_invalid_points_throws(): void {
        $this->expectException( InvalidArgumentException::class );
        Player_Ranking::create( 5, 8.0, -10.0 );
    }

    public function test_equality_and_json(): void {
        $r1 = Player_Ranking::create( 10, 7.5, 500.0 );
        $r2 = Player_Ranking::create( 10, 7.5, 500.0 );
        $r3 = Player_Ranking::create( 11, 7.5, 500.0 );

        self::assertTrue( $r1->equals( $r2 ) );
        self::assertFalse( $r1->equals( $r3 ) );

        $json = $r1->jsonSerialize();
        self::assertSame( 10, $json['rank'] );
        self::assertSame( 7.5, $json['rating'] );
        self::assertSame( '7.5', $json['raw_value'] );
        self::assertSame( 500.0, $json['points'] );
    }
}
