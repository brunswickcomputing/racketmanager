<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Enums\Fixture_Status;
use Racketmanager\Services\Result_Calculator;
use Racketmanager\Domain\Scoring\Set_Score;
use stdClass;

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

    public function test_calculate_stats_with_string_and_numeric_enum_values(): void {
        $rubber_wo_away = (object) [
            'status'      => '1', // String Walkover code
            'winner_id'   => '20',
            'home_points' => 0,
            'away_points' => 2,
        ];
        $rubber_shared = (object) [
            'status'      => Fixture_Status::SHARED->value,
            'winner_id'   => '-1',
            'home_points' => 1,
            'away_points' => 1,
        ];

        $rubbers = [ $rubber_wo_away, $rubber_shared ];
        $home_id = '10';
        $away_id = '20';

        $result = Result_Calculator::calculate_stats_from_rubbers( $rubbers, $home_id, $away_id );

        $this->assertEquals( 1, $result['home_walkover'] );
        $this->assertEquals( 0, $result['away_walkover'] );
        $this->assertEquals( 1, $result['shared'] );
        $this->assertEquals( 1, $result['away_win'] );
        $this->assertEquals( 1, $result['draw'] );
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

    public function test_calculate_stats_from_rubbers_handles_string_and_int_ids(): void {
        $rubber1 = new stdClass();
        $rubber1->status = 0;
        $rubber1->winner_id = 101; // integer ID
        $rubber1->home_points = 2.0;
        $rubber1->away_points = 0.0;

        $rubber2 = new stdClass();
        $rubber2->status = 0;
        $rubber2->winner_id = '102'; // string ID
        $rubber2->home_points = 0.0;
        $rubber2->away_points = 2.0;

        $rubber3 = new stdClass();
        $rubber3->status = 3; // Shared
        $rubber3->winner_id = -1;
        $rubber3->home_points = 1.0;
        $rubber3->away_points = 1.0;

        $rubber4 = new stdClass();
        $rubber4->status = 1; // Walkover
        $rubber4->winner_id = '101';
        $rubber4->home_points = 2.0;
        $rubber4->away_points = 0.0;

        $rubbers = [ $rubber1, $rubber2, $rubber3, $rubber4 ];

        $stats = Result_Calculator::calculate_stats_from_rubbers( $rubbers, '101', '102' );

        $this->assertSame( 2, $stats['home_win'] );
        $this->assertSame( 1, $stats['away_win'] );
        $this->assertSame( 1, $stats['draw'] );
        $this->assertSame( 1, $stats['shared'] );
        $this->assertSame( 1, $stats['away_walkover'] );
        $this->assertSame( 0, $stats['home_walkover'] );
        $this->assertSame( 5.0, $stats['home_points'] );
        $this->assertSame( 3.0, $stats['away_points'] );
        $this->assertSame( 2.5, $stats['stats']['rubbers']['home'] );
        $this->assertSame( 1.5, $stats['stats']['rubbers']['away'] );
    }

    public function test_calculate_points_from_stats_uses_float_comparison_for_wins(): void {
        $stats_result = [
            'home_points'   => 4.0,
            'away_points'   => 4.0,
            'home_win'      => 2.5, // Float wins from shared rubbers
            'away_win'      => 2.0,
            'draw'          => 0,
            'home_walkover' => 0,
            'away_walkover' => 0,
            'stats'         => [
                'games' => [ 'home' => 30, 'away' => 25 ],
            ],
        ];

        $point_rule = [
            'matches_win'  => 2.0,
            'matches_draw' => 1.0,
        ];

        $res = Result_Calculator::calculate_points_from_stats( $stats_result, $point_rule, 6, 0 );

        $this->assertSame( 6.0, $res['home_points'] );
        $this->assertSame( 4.0, $res['away_points'] );

        // Away wins > home wins
        $stats_result['home_win'] = 1.5;
        $stats_result['away_win'] = 2.0;

        $res2 = Result_Calculator::calculate_points_from_stats( $stats_result, $point_rule, 6, 0 );

        $this->assertSame( 4.0, $res2['home_points'] );
        $this->assertSame( 6.0, $res2['away_points'] );

        // Equal wins -> draw points
        $stats_result['home_win'] = 2.0;
        $stats_result['away_win'] = 2.0;

        $res3 = Result_Calculator::calculate_points_from_stats( $stats_result, $point_rule, 6, 0 );

        $this->assertSame( 5.0, $res3['home_points'] );
        $this->assertSame( 5.0, $res3['away_points'] );
    }

    public function test_determine_winner_and_loser_walkover_direction(): void {
        // Custom walkover = home (home conceded -> away is winner)
        $res1 = Result_Calculator::determine_winner_and_loser( 0.0, 0.0, 10, 20, 1, [ 'walkover' => 'home' ] );
        $this->assertSame( 10, $res1['winner_id'] );
        $this->assertSame( 20, $res1['loser_id'] );

        // Custom walkover = away (away conceded -> home is winner)
        $res2 = Result_Calculator::determine_winner_and_loser( 0.0, 0.0, 10, 20, 1, [ 'walkover' => 'away' ] );
        $this->assertSame( 20, $res2['winner_id'] );
        $this->assertSame( 10, $res2['loser_id'] );

        // Fallback to points when direction not specified
        $res3 = Result_Calculator::determine_winner_and_loser( 4.0, 2.0, 10, 20, 1, [] );
        $this->assertSame( 10, $res3['winner_id'] );
        $this->assertSame( 20, $res3['loser_id'] );

        $res4 = Result_Calculator::determine_winner_and_loser( 2.0, 4.0, 10, 20, 1, [] );
        $this->assertSame( 20, $res4['winner_id'] );
        $this->assertSame( 10, $res4['loser_id'] );
    }

    public function test_determine_winner_and_loser_withdrawn_retired_and_bye(): void {
        // Withdrawn
        $res_withdrawn = Result_Calculator::determine_winner_and_loser( 0.0, 0.0, 10, 20, 7, [] );
        $this->assertSame( -1, $res_withdrawn['winner_id'] );
        $this->assertSame( -1, $res_withdrawn['loser_id'] );

        // Retired away
        $res_retired = Result_Calculator::determine_winner_and_loser( 0.0, 0.0, 10, 20, 2, [ 'retired' => 'away' ] );
        $this->assertSame( 10, $res_retired['winner_id'] );
        $this->assertSame( 20, $res_retired['loser_id'] );

        // Bye / team id -1
        $res_bye_home = Result_Calculator::determine_winner_and_loser( 0.0, 0.0, -1, 20 );
        $this->assertSame( 20, $res_bye_home['winner_id'] );
        $this->assertSame( 0, $res_bye_home['loser_id'] );

        // Bye / team id '-1'
        $res_bye_away = Result_Calculator::determine_winner_and_loser( 0.0, 0.0, 10, '-1' );
        $this->assertSame( 10, $res_bye_away['winner_id'] );
        $this->assertSame( 0, $res_bye_away['loser_id'] );
    }

    public function test_calculate_stats_from_rubbers_with_mixed_type_ids(): void {
        $rubbers = [
            (object) [
                'winner_id'   => 101, // int winner
                'loser_id'    => '102',
                'status'      => 0,
                'home_points' => '3.0',
                'away_points' => 0.0,
            ],
            (object) [
                'winner_id'   => '102', // string winner
                'loser_id'    => 101,
                'status'      => 1, // Walkover
                'home_points' => 0,
                'away_points' => '3.0',
            ],
            (object) [
                'winner_id'   => -1, // Draw
                'status'      => 3,
                'home_points' => 1.5,
                'away_points' => 1.5,
            ],
        ];

        // Pass integer home_id and string away_id
        $stats = Result_Calculator::calculate_stats_from_rubbers( $rubbers, 101, '102' );

        $this->assertSame( 1, $stats['home_win'] );
        $this->assertSame( 1, $stats['away_win'] );
        $this->assertSame( 1, $stats['draw'] );
        $this->assertSame( 1, $stats['home_walkover'] );
        $this->assertSame( 0, $stats['away_walkover'] );
        $this->assertSame( 4.5, $stats['home_points'] );
        $this->assertSame( 4.5, $stats['away_points'] );
    }

    public function test_calculate_aggregate_result_mixed_ids(): void {
        // Current Leg (Leg 2): Home (101) = 4, Away (102) = 1.
        // Linked Leg (Leg 1): Home (102) = 4, Away (101) = 2.
        // Aggregate for Home (101): Leg 2 Home (4) + Leg 1 Away (2) = 6.
        // Aggregate for Away (102): Leg 2 Away (1) + Leg 1 Home (4) = 5.
        $res = Result_Calculator::calculate_aggregate_result(
            current_home_points: 4.0,
            current_away_points: 1.0,
            linked_home_points: 4.0,
            linked_away_points: 2.0,
            home_team_id: 101,
            away_team_id: '102'
        );

        $this->assertSame( 6.0, $res['home_points_tie'] );
        $this->assertSame( 5.0, $res['away_points_tie'] );
        $this->assertSame( 101, $res['winner_id_tie'] );
        $this->assertSame( '102', $res['loser_id_tie'] );
    }
}
