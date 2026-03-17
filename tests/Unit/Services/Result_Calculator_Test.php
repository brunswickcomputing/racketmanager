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

    public function test_calculate_stats_from_rubbers(): void {
        $rubber1 = (object) [
            'status'      => 0,
            'winner_id'   => '10',
            'home_points' => 2,
            'away_points' => 0,
            'custom'      => [
                'stats' => [
                    'sets'  => [ 'home' => 2, 'away' => 0 ],
                    'games' => [ 'home' => 12, 'away' => 4 ],
                ]
            ]
        ];
        $rubber2 = (object) [
            'status'      => 0,
            'winner_id'   => '20',
            'home_points' => 0,
            'away_points' => 2,
            'custom'      => [
                'stats' => [
                    'sets'  => [ 'home' => 0, 'away' => 2 ],
                    'games' => [ 'home' => 5, 'away' => 12 ],
                ]
            ]
        ];
        $rubber3 = (object) [
            'status'      => 3, // Shared
            'winner_id'   => '-1',
            'home_points' => 1,
            'away_points' => 1,
            'custom'      => [
                'stats' => [
                    'sets'  => [ 'home' => 1, 'away' => 1 ],
                    'games' => [ 'home' => 10, 'away' => 10 ],
                ]
            ]
        ];

        $rubbers = [ $rubber1, $rubber2, $rubber3 ];
        $home_id = '10';
        $away_id = '20';

        $result = Result_Calculator::calculate_stats_from_rubbers( $rubbers, $home_id, $away_id );

        $this->assertEquals( 1.5, $result['stats']['rubbers']['home'] );
        $this->assertEquals( 1.5, $result['stats']['rubbers']['away'] );
        $this->assertEquals( 3, $result['stats']['sets']['home'] );
        $this->assertEquals( 3, $result['stats']['sets']['away'] );
        $this->assertEquals( 27, $result['stats']['games']['home'] );
        $this->assertEquals( 26, $result['stats']['games']['away'] );
        $this->assertEquals( 1, $result['home_win'] );
        $this->assertEquals( 1, $result['away_win'] );
        $this->assertEquals( 1, $result['draw'] );
        $this->assertEquals( 1, $result['shared'] );
        $this->assertEquals( 3.0, $result['home_points'] );
        $this->assertEquals( 3.0, $result['away_points'] );
    }

    public function test_calculate_stats_with_walkovers(): void {
        $rubber1 = (object) [
            'status'      => 1, // Walkover
            'winner_id'   => '10',
            'home_points' => 2,
            'away_points' => 0,
        ];

        $rubbers = [ $rubber1 ];
        $home_id = '10';
        $away_id = '20';

        $result = Result_Calculator::calculate_stats_from_rubbers( $rubbers, $home_id, $away_id );

        $this->assertEquals( 1, $result['away_walkover'] );
        $this->assertEquals( 0, $result['home_walkover'] );
        $this->assertEquals( 1, $result['home_win'] );
        $this->assertEquals( 2.0, $result['home_points'] );
    }

    public function test_calculate_points_from_stats_match_win_points(): void {
        $stats_result = [
            'home_win'      => 2,
            'away_win'      => 1,
            'draw'          => 0,
            'home_walkover' => 0,
            'away_walkover' => 0,
            'home_points'   => 4.0, // Rubbers points
            'away_points'   => 2.0,
        ];
        $point_rule = [
            'matches_win' => 3,
        ];
        $status = 0;
        $num_rubbers = 3;

        $points = Result_Calculator::calculate_points_from_stats( $stats_result, $point_rule, $status, $num_rubbers );

        // 4 + 3 = 7
        $this->assertEquals( 7.0, $points['home_points'] );
        $this->assertEquals( 2.0, $points['away_points'] );
    }

    public function test_calculate_points_from_stats_rubber_count_mode(): void {
        $stats_result = [
            'home_win'      => 2,
            'away_win'      => 1,
            'draw'          => 0,
            'home_walkover' => 0,
            'away_walkover' => 0,
            'home_points'   => 0.0,
            'away_points'   => 0.0,
        ];
        $point_rule = [
            'match_result' => 'rubber_count',
            'rubber_win'   => 2,
        ];
        $status = 0;
        $num_rubbers = 3;

        $points = Result_Calculator::calculate_points_from_stats( $stats_result, $point_rule, $status, $num_rubbers );

        // 2 wins * 2 points = 4
        $this->assertEquals( 4.0, $points['home_points'] );
        // 1 win * 2 points = 2
        $this->assertEquals( 2.0, $points['away_points'] );
    }

    public function test_determine_winner_and_loser_standard(): void {
        $result = Result_Calculator::determine_winner_and_loser( 3.0, 1.0, 10, 20 );
        $this->assertEquals( 10, $result['winner_id'] );
        $this->assertEquals( 20, $result['loser_id'] );

        $result = Result_Calculator::determine_winner_and_loser( 1.0, 3.0, 10, 20 );
        $this->assertEquals( 20, $result['winner_id'] );
        $this->assertEquals( 10, $result['loser_id'] );
    }

    public function test_determine_winner_and_loser_walkover(): void {
        $result = Result_Calculator::determine_winner_and_loser( 2.0, 0.0, 10, 20, 1, ['walkover' => 'home'] );
        $this->assertEquals( 10, $result['winner_id'] );
        $this->assertEquals( 20, $result['loser_id'] );

        $result = Result_Calculator::determine_winner_and_loser( 0.0, 2.0, 10, 20, 1, ['walkover' => 'away'] );
        $this->assertEquals( 20, $result['winner_id'] );
        $this->assertEquals( 10, $result['loser_id'] );
    }

    public function test_determine_winner_and_loser_draw(): void {
        $result = Result_Calculator::determine_winner_and_loser( 2.0, 2.0, 10, 20 );
        $this->assertEquals( -1, $result['winner_id'] );
        $this->assertEquals( -1, $result['loser_id'] );
    }

    public function test_determine_winner_and_loser_bye(): void {
        $result = Result_Calculator::determine_winner_and_loser( 2.0, 0.0, '-1', 20 );
        $this->assertEquals( 20, $result['winner_id'] );
        $this->assertEquals( 0, $result['loser_id'] );
    }
}
