<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Racketmanager\Services\Result_Calculator;
use Racketmanager\Domain\Scoring\Set_Score;

final class Result_Calculator_Test extends TestCase {

    public function test_calculate_points_from_sets(): void {
        $sets = [
            new Set_Score( 6, 4 ),
            new Set_Score( 3, 6 ),
            new Set_Score( 7, 5 ),
        ];

        $result = Result_Calculator::calculate_points_from_sets( $sets );

        $this->assertEquals( 2.0, $result['home_points'] );
        $this->assertEquals( 1.0, $result['away_points'] );
    }

    public function test_calculate_points_from_sets_with_draw(): void {
        $sets = [
            new Set_Score( 6, 6 ),
        ];

        $result = Result_Calculator::calculate_points_from_sets( $sets );

        $this->assertEquals( 0.0, $result['home_points'] );
        $this->assertEquals( 0.0, $result['away_points'] );
    }

    public function test_calculate_points_from_sets_with_tiebreak(): void {
        $sets = [
            new Set_Score( 6, 7, 5, 7 ),
        ];

        $result = Result_Calculator::calculate_points_from_sets( $sets );

        $this->assertEquals( 0.0, $result['home_points'] );
        $this->assertEquals( 1.0, $result['away_points'] );
    }

    public function test_result_factory_handles_player_and_tiebreak_keys(): void {
        $data = [
            'home_points' => 0,
            'away_points' => 0,
            'sets' => [
                1 => ['player1' => 6, 'player2' => 2],
                2 => ['player1' => 6, 'player2' => 7, 'tiebreak' => 5],
                3 => ['player1' => 1, 'player2' => 0, 'settype' => 'MTB']
            ]
        ];

        $result = \Racketmanager\Services\Result_Factory::from_array($data, 10, 20);
        $sets = $result->get_sets();

        $this->assertCount(3, $sets);
        $this->assertEquals(6, $sets[1]->get_home_games());
        $this->assertEquals(2, $sets[1]->get_away_games());
        $this->assertNull($sets[1]->get_home_tiebreak());

        $this->assertEquals(6, $sets[2]->get_home_games());
        $this->assertEquals(7, $sets[2]->get_away_games());
        $this->assertNull($sets[2]->get_home_tiebreak());
        $this->assertEquals(5, $sets[2]->get_away_tiebreak());

        $this->assertEquals(1, $sets[3]->get_home_games());
        $this->assertEquals(0, $sets[3]->get_away_games());
        $this->assertEquals(2.0, $result->get_home_points());
        $this->assertEquals(1.0, $result->get_away_points());
    }

    public function test_result_factory_handles_empty_sets_and_tiebreaks(): void {
        $data = [
            'home_points' => 0,
            'away_points' => 0,
            'sets' => [
                1 => ['player1' => 6, 'player2' => 2],
                2 => ['player1' => 6, 'player2' => 0],
                3 => ['player1' => '', 'player2' => '', 'tiebreak' => '']
            ]
        ];

        $result = \Racketmanager\Services\Result_Factory::from_array($data, 10, 20);
        $sets = $result->get_sets();

        $this->assertCount(3, $sets);
        
        // Set 3 should be empty/null if it's 6-2 6-0
        // Currently Result_Factory::from_array converts '' to 0 via (int)
        // We want them to be null or some indicator that they are not set
        $this->assertNull($sets[3]->get_home_games());
        $this->assertNull($sets[3]->get_away_games());
        $this->assertNull($sets[3]->get_home_tiebreak());
        $this->assertNull($sets[3]->get_away_tiebreak());
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

    public function test_calculate_aggregate_result(): void {
        $current_home_points = 2.0;
        $current_away_points = 1.0;
        $linked_home_points  = 2.0; // Team 20 was home in linked leg
        $linked_away_points  = 0.0; // Team 10 was away in linked leg
        $home_team_id        = 10;
        $away_team_id        = 20;

        $result = Result_Calculator::calculate_aggregate_result(
            $current_home_points,
            $current_away_points,
            $linked_home_points,
            $linked_away_points,
            $home_team_id,
            $away_team_id
        );

        // Aggregate Home (10): 2.0 (leg 2 home) + 0.0 (leg 1 away) = 2.0
        // Aggregate Away (20): 1.0 (leg 2 away) + 2.0 (leg 1 home) = 3.0
        $this->assertEquals( 2.0, $result['home_points_tie'] );
        $this->assertEquals( 3.0, $result['away_points_tie'] );
        $this->assertEquals( 20, $result['winner_id_tie'] );
        $this->assertEquals( 10, $result['loser_id_tie'] );
    }

    public function test_calculate_aggregate_result_draw(): void {
        // Team 10 (Home): 2.0 in Leg 2, Team 20 (Away): 1.0 in Leg 2
        // Team 20 (Home): 2.0 in Leg 1, Team 10 (Away): 1.0 in Leg 1
        $result = Result_Calculator::calculate_aggregate_result( 2.0, 1.0, 2.0, 1.0, 10, 20 );

        // Aggregate Home (10): 2.0 + 1.0 = 3.0
        // Aggregate Away (20): 1.0 + 2.0 = 3.0
        $this->assertEquals( 3.0, $result['home_points_tie'] );
        $this->assertEquals( 3.0, $result['away_points_tie'] );
        $this->assertEquals( -1, $result['winner_id_tie'] );
        $this->assertEquals( -1, $result['loser_id_tie'] );
    }
}
