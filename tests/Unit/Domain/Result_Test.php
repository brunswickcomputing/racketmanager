<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
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
        $this->assertTrue($result->is_walkover());
        $this->assertSame($sets, $result->get_sets());
        $this->assertSame($custom, $result->get_custom());
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
        $this->assertFalse($result->is_walkover());
        $this->assertSame([], $result->get_sets());
        $this->assertSame([], $result->get_custom());
    }
}
