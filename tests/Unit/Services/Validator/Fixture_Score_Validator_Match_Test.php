<?php
declare(strict_types=1);

namespace {
    // Mock __ function if it doesn't exist in the environment
    if (!function_exists('__')) {
        function __($text, $domain) {
            return $text;
        }
    }
}

namespace Racketmanager\Tests\Unit\Services\Validator {

    use PHPUnit\Framework\Attributes\DataProvider;
    use PHPUnit\Framework\TestCase;
    use Racketmanager\Domain\Scoring\Scoring_Context;
    use Racketmanager\Services\Validator\Score_Validation_Service;

    class Fixture_Score_Validator_Match_Test extends TestCase
    {
        private $validator;

        protected function setUp(): void
        {
            parent::setUp();
            $this->validator = new Score_Validation_Service();
        }

        private function create_scoring_context($overrides = []): Scoring_Context
        {
            $data = [
                'num_sets_to_win' => 2,
                'scoring_type'    => 'TB',
                'point_rule'      => ['match_result' => 'sets'],
                'is_championship' => false,
                'final_round'     => null,
                'num_rubbers'     => null,
                'leg'             => null,
                'num_sets'        => 3,
            ];

            foreach ($overrides as $key => $value) {
                $data[$key] = $value;
            }

            return new Scoring_Context(
                num_sets_to_win: $data['num_sets_to_win'],
                scoring_type:    $data['scoring_type'],
                point_rule:      $data['point_rule'],
                is_championship: $data['is_championship'],
                final_round:     $data['final_round'],
                num_rubbers:     $data['num_rubbers'],
                leg:             $data['leg'],
                num_sets:        $data['num_sets']
            );
        }

        public function test_validate_complete_match_2_0(): void
        {
            $context = $this->create_scoring_context();
            $sets = [
                1 => ['player1' => '6', 'player2' => '4', 'tiebreak' => ''],
                2 => ['player1' => '6', 'player2' => '2', 'tiebreak' => ''],
                3 => ['player1' => '', 'player2' => '', 'tiebreak' => ''],
            ];

            $this->validator->validate($context, $sets, null, 'set_');

            $this->assertFalse($this->validator->get_error());
            $this->assertEquals(2, $this->validator->get_home_points());
            $this->assertEquals(0, $this->validator->get_away_points());
            
            $stats = $this->validator->get_stats();
            $this->assertEquals(2, $stats['sets']['home']);
            $this->assertEquals(0, $stats['sets']['away']);
            $this->assertEquals(12, $stats['games']['home']);
            $this->assertEquals(6, $stats['games']['away']);

            $validated_sets = $this->validator->get_sets();
            $this->assertTrue($validated_sets[1]['completed']);
            $this->assertTrue($validated_sets[2]['completed']);
            $this->assertFalse($validated_sets[3]['completed']);
            $this->assertEquals('null', $validated_sets[3]['settype']);
        }

        #[DataProvider('points_outcome_scenarios_provider')]
        public function test_validate_points_outcomes(
            array $sets,
            ?string $match_status,
            int $expected_home_points,
            int $expected_away_points
        ): void {
            $context = $this->create_scoring_context();

            $this->validator->validate($context, $sets, $match_status, 'set_');

            $this->assertFalse($this->validator->get_error());
            $this->assertEquals($expected_home_points, $this->validator->get_home_points());
            $this->assertEquals($expected_away_points, $this->validator->get_away_points());
        }

        public static function points_outcome_scenarios_provider(): array
        {
            return [
                'complete match 2-1' => [
                    'sets' => [
                        1 => ['player1' => '6', 'player2' => '4', 'tiebreak' => ''],
                        2 => ['player1' => '4', 'player2' => '6', 'tiebreak' => ''],
                        3 => ['player1' => '6', 'player2' => '0', 'tiebreak' => ''],
                    ],
                    'match_status' => null,
                    'expected_home_points' => 2,
                    'expected_away_points' => 1,
                ],
                'retired match player 1' => [
                    'sets' => [
                        1 => ['player1' => '6', 'player2' => '4', 'tiebreak' => ''],
                        2 => ['player1' => '1', 'player2' => '2', 'tiebreak' => ''],
                        3 => ['player1' => '', 'player2' => '', 'tiebreak' => ''],
                    ],
                    // Retirement awards remaining sets to the opponent.
                    'match_status' => 'retired_player1',
                    'expected_home_points' => 1,
                    'expected_away_points' => 2,
                ],
                'abandoned match' => [
                    'sets' => [
                        1 => ['player1' => '6', 'player2' => '4', 'tiebreak' => ''],
                        2 => ['player1' => '2', 'player2' => '2', 'tiebreak' => ''],
                        3 => ['player1' => '', 'player2' => '', 'tiebreak' => ''],
                    ],
                    // Abandoned before a winner: home keeps set 1; the 2 unplayed sets (3 - 1 - 0) are credited to both players, giving 3/2.
                    'match_status' => 'abandoned',
                    'expected_home_points' => 3,
                    'expected_away_points' => 2,
                ],
                'walkover match player 1' => [
                    'sets' => [
                        1 => ['player1' => '', 'player2' => '', 'tiebreak' => ''],
                        2 => ['player1' => '', 'player2' => '', 'tiebreak' => ''],
                        3 => ['player1' => '', 'player2' => '', 'tiebreak' => ''],
                    ],
                    // Walkover: home is awarded the match (num_sets_to_win = 2); away gets 0, as no forwalkover_rubber penalty is configured.
                    'match_status' => 'walkover_player1',
                    'expected_home_points' => 2,
                    'expected_away_points' => 0,
                ],
            ];
        }

        #[DataProvider('error_scoring_scenarios_provider')]
        public function test_validate_error_scoring_scenarios(
            array $sets,
            string $prefix,
            string $expected_err_fld,
            ?string $expected_err_msg = null
        ): void {
            $context = $this->create_scoring_context();

            $this->validator->validate($context, $sets, null, $prefix);

            $this->assertTrue($this->validator->get_error());
            $this->assertContains($expected_err_fld, $this->validator->get_err_flds());
            if ($expected_err_msg !== null) {
                $this->assertContains($expected_err_msg, $this->validator->get_err_msgs());
            }
        }

        public static function error_scoring_scenarios_provider(): array
        {
            return [
                'invalid tied score in match' => [
                    'sets' => [
                        1 => ['player1' => '6', 'player2' => '6', 'tiebreak' => ''],
                        2 => ['player1' => '6', 'player2' => '2', 'tiebreak' => ''],
                        3 => ['player1' => '', 'player2' => '', 'tiebreak' => ''],
                    ],
                    'prefix' => 'set_',
                    'expected_err_fld' => 'set_1_player1',
                    'expected_err_msg' => null,
                ],
                'too many sets entered' => [
                    'sets' => [
                        1 => ['player1' => '6', 'player2' => '4', 'tiebreak' => ''],
                        2 => ['player1' => '6', 'player2' => '4', 'tiebreak' => ''],
                        3 => ['player1' => '6', 'player2' => '4', 'tiebreak' => ''],
                    ],
                    'prefix' => 'set_',
                    'expected_err_fld' => 'set_3_player1',
                    'expected_err_msg' => 'Set score should be empty',
                ],
                'tiebreak required' => [
                    'sets' => [
                        1 => ['player1' => '6', 'player2' => '4', 'tiebreak' => ''],
                        2 => ['player1' => '4', 'player2' => '6', 'tiebreak' => ''],
                        3 => ['player1' => '7', 'player2' => '6', 'tiebreak' => ''],
                    ],
                    'prefix' => 'set_1_',
                    'expected_err_fld' => 'set_1_3_tiebreak',
                    'expected_err_msg' => 'Tie break score required',
                ],
            ];
        }

        public function test_validate_mtb_set_score_no_tiebreak_required(): void
        {
            // Simulating MTB set with 9-11 score. 
            // According to Util::get_set_info('MTB'):
            // min_win = 10, tiebreak_allowed = false, tiebreak_set = null
            
            // We use standard context but we need to ensure the set_type is MTB for the 3rd set.
            // Score_Validation_Service::validate calls Util::get_set_type
            // and Util::get_set_info.
            // Since we can't easily mock Util (it's static), and Score_Validation_Service uses it directly,
            // we should try to set up the context so Util::get_set_type returns 'MTB'.
            
            // Looking at Util::get_set_type:
            /*
            public static function get_set_type( string $scoring, ?string $round, int $num_sets, int $set, ?int $rubber_number, ?int $num_rubbers, ?int $leg ): string {
                // ...
                if ( 'MTB' === $scoring && $set === $num_sets ) {
                    return 'MTB';
                }
                // ...
            }
            */
            
            $context = $this->create_scoring_context([
                'scoring_type' => 'MTB',
                'num_sets' => 3
            ]);

            $sets = [
                1 => ['player1' => '6', 'player2' => '4', 'tiebreak' => ''],
                2 => ['player1' => '4', 'player2' => '6', 'tiebreak' => ''],
                3 => ['player1' => '9', 'player2' => '11', 'tiebreak' => ''],
            ];
            
            $this->validator->validate($context, $sets, null, 'set_1_');

            // MTB should not require a tiebreak score
            $this->assertNotContains('set_1_3_tiebreak', $this->validator->get_err_flds(), 'MTB should not require a tiebreak score');
        }
    }
}

