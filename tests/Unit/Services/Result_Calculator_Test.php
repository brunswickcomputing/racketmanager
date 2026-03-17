<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Racketmanager\Services\Result_Calculator;

final class Result_Calculator_Test extends TestCase {

    public function test_calculate_points_from_sets(): void {
        $sets = [
            [ 'player1' => 6, 'player2' => 4 ],
            [ 'player1' => 3, 'player2' => 6 ],
            [ 'player1' => 7, 'player2' => 5 ],
        ];

        $result = Result_Calculator::calculate_points_from_sets( $sets );

        $this->assertEquals( 2.0, $result['home_points'] );
        $this->assertEquals( 1.0, $result['away_points'] );
    }

    public function test_calculate_points_from_sets_with_missing_scores(): void {
        $sets = [
            [ 'player1' => 6, 'player2' => 4 ],
            [ 'player1' => 3 ], // missing player2
            [ 'player2' => 5 ], // missing player1
        ];

        $result = Result_Calculator::calculate_points_from_sets( $sets );

        $this->assertEquals( 1.0, $result['home_points'] );
        $this->assertEquals( 0.0, $result['away_points'] );
    }

    public function test_calculate_points_from_sets_with_draw(): void {
        $sets = [
            [ 'player1' => 6, 'player2' => 6 ],
        ];

        $result = Result_Calculator::calculate_points_from_sets( $sets );

        $this->assertEquals( 0.0, $result['home_points'] );
        $this->assertEquals( 0.0, $result['away_points'] );
    }
}
