<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Enums\Fixture_Status;
use Racketmanager\Domain\Result\Result;

final class Result_Test extends TestCase {
    public function test_result_constructor_and_getters(): void {
        $sets = [
            ['player1' => 6, 'player2' => 4],
            ['player1' => 7, 'player2' => 5],
        ];
        $custom = ['notes' => 'Great match'];
        
        $result = new Result(
            home_points: 2.0,
            away_points: 0.0,
            winner_id: 10,
            loser_id: 20,
            status: 1,
            is_walkover: true,
            sets: $sets,
            custom: $custom
        );

        $this->assertSame(2.0, $result->get_home_points());
        $this->assertSame(0.0, $result->get_away_points());
        $this->assertSame(10, $result->get_winner_id());
        $this->assertSame(20, $result->get_loser_id());
        $this->assertSame(1, $result->get_status());
        $this->assertSame(Fixture_Status::WALKOVER, $result->get_status_enum());
        $this->assertTrue($result->is_walkover());
        $this->assertSame($sets, $result->get_sets());
        $this->assertSame($custom, $result->get_custom());
        $this->assertFalse($result->is_reset());
    }

    public function test_result_optional_parameters_default_values(): void {
        $result = new Result(
            home_points: 1.0,
            away_points: 1.0
        );

        $this->assertSame(1.0, $result->get_home_points());
        $this->assertSame(1.0, $result->get_away_points());
        $this->assertNull($result->get_winner_id());
        $this->assertNull($result->get_loser_id());
        $this->assertNull($result->get_status());
        $this->assertNull($result->get_status_enum());
        $this->assertFalse($result->is_walkover());
        $this->assertSame([], $result->get_sets());
        $this->assertSame([], $result->get_custom());
        $this->assertTrue($result->is_reset());
    }

    public function test_result_status_enum_mapping(): void {
        $result_walkover = new Result( home_points: 2.0, away_points: 0.0, status: 1 );
        $this->assertSame( 1, $result_walkover->get_status() );
        $this->assertSame( Fixture_Status::WALKOVER, $result_walkover->get_status_enum() );

        $result_retired = new Result( home_points: 2.0, away_points: 0.0, status: 2 );
        $this->assertSame( 2, $result_retired->get_status() );
        $this->assertSame( Fixture_Status::RETIRED, $result_retired->get_status_enum() );

        $result_abandoned = new Result( home_points: 1.0, away_points: 1.0, status: 6 );
        $this->assertSame( 6, $result_abandoned->get_status() );
        $this->assertSame( Fixture_Status::ABANDONED, $result_abandoned->get_status_enum() );

        $result_unknown = new Result( home_points: 1.0, away_points: 1.0, status: 999 );
        $this->assertSame( 999, $result_unknown->get_status() );
        $this->assertNull( $result_unknown->get_status_enum() );

        $result_null = new Result( home_points: 1.0, away_points: 1.0, status: null );
        $this->assertNull( $result_null->get_status() );
        $this->assertNull( $result_null->get_status_enum() );
    }
}
