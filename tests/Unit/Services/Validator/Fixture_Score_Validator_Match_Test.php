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

    use PHPUnit\Framework\TestCase;
    use Racketmanager\Services\Validator\Fixture_Score_Validator;
    use stdClass;

    class Fixture_Score_Validator_Match_Test extends TestCase
    {
        private $validator;

        protected function setUp(): void
        {
            parent::setUp();
            $this->validator = new Fixture_Score_Validator();
        }

        private function create_mock_match($overrides = []): stdClass
        {
            $match = new stdClass();
            $match->final_round = null;
            $match->num_rubbers = null;
            $match->leg = null;
            
            $league = new stdClass();
            $league->num_sets_to_win = 2;
            $league->num_sets = 3;
            $league->scoring = 'TB';
            $league->get_point_rule = function() {
                return ['match_result' => 'sets'];
            };
            
            $match->league = $league;

            foreach ($overrides as $key => $value) {
                if ($key === 'league_overrides') {
                    foreach ($value as $lkey => $lvalue) {
                        $match->league->$lkey = $lvalue;
                    }
                } else {
                    $match->$key = $value;
                }
            }

            // In PHP, we can't easily mock methods on stdClass without some tricks or just using a real mock object.
            // But the code calls $match->league->get_point_rule(), so we need that to work.
            // Let's use an anonymous class or a simple mock.
            
            $match->league = new class($match->league) {
                public $num_sets_to_win;
                public $num_sets;
                public $scoring;
                public array $point_rule;
                
                public function __construct($league) {
                    $this->num_sets_to_win = $league->num_sets_to_win;
                    $this->num_sets = $league->num_sets;
                    $this->scoring = $league->scoring;
                    $this->point_rule = isset($league->point_rule) ? $league->point_rule : ['match_result' => 'sets'];
                }
                
                public function get_point_rule(): array {
                    return $this->point_rule;
                }
            };

            return $match;
        }

        public function test_validate_complete_match_2_0(): void
        {
            $match = $this->create_mock_match();
            $sets = [
                1 => ['player1' => '6', 'player2' => '4', 'tiebreak' => ''],
                2 => ['player1' => '6', 'player2' => '2', 'tiebreak' => ''],
                3 => ['player1' => '', 'player2' => '', 'tiebreak' => ''],
            ];

            $this->validator->validate($match, $sets, null, 'set_');

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

        public function test_validate_complete_match_2_1(): void
        {
            $match = $this->create_mock_match();
            $sets = [
                1 => ['player1' => '6', 'player2' => '4', 'tiebreak' => ''],
                2 => ['player1' => '4', 'player2' => '6', 'tiebreak' => ''],
                3 => ['player1' => '6', 'player2' => '0', 'tiebreak' => ''],
            ];

            $this->validator->validate($match, $sets, null, 'set_');

            $this->assertFalse($this->validator->get_error());
            $this->assertEquals(2, $this->validator->get_home_points());
            $this->assertEquals(1, $this->validator->get_away_points());
        }

        public function test_validate_retired_match(): void
        {
            $match = $this->create_mock_match();
            // Player 1 retires in the second set
            $sets = [
                1 => ['player1' => '6', 'player2' => '4', 'tiebreak' => ''],
                2 => ['player1' => '1', 'player2' => '2', 'tiebreak' => ''],
                3 => ['player1' => '', 'player2' => '', 'tiebreak' => ''],
            ];

            $this->validator->validate($match, $sets, 'retired_player1', 'set_');

            $this->assertFalse($this->validator->get_error());
            // Away player should get the remaining sets to win the match
            // Set 1: Home won
            // Set 2: Retired (Away wins)
            // Set 3: Not played (Away wins to reach 2 sets)
            $this->assertEquals(1, $this->validator->get_home_points());
            $this->assertEquals(2, $this->validator->get_away_points());
        }

        public function test_validate_abandoned_match(): void
        {
            $match = $this->create_mock_match();
            $sets = [
                1 => ['player1' => '6', 'player2' => '4', 'tiebreak' => ''],
                2 => ['player1' => '2', 'player2' => '2', 'tiebreak' => ''],
                3 => ['player1' => '', 'player2' => '', 'tiebreak' => ''],
            ];

            $this->validator->validate($match, $sets, 'abandoned', 'set_');

            $this->assertFalse($this->validator->get_error());
            // Abandoned logic typically shares remaining sets
            // Set 1: Home won
            // Remaining sets (2 and 3) are shared?
            // home_score = 1 (set 1)
            // shared = 3 - 1 - 0 = 2
            // home_score = 1 + 2 = 3
            // away_score = 0 + 2 = 2
            $this->assertEquals(3, $this->validator->get_home_points());
            $this->assertEquals(2, $this->validator->get_away_points());
        }

        public function test_validate_walkover(): void
        {
            $match = $this->create_mock_match();
            $sets = [
                1 => ['player1' => '', 'player2' => '', 'tiebreak' => ''],
                2 => ['player1' => '', 'player2' => '', 'tiebreak' => ''],
                3 => ['player1' => '', 'player2' => '', 'tiebreak' => ''],
            ];

            $this->validator->validate($match, $sets, 'walkover_player1', 'set_');

            $this->assertFalse($this->validator->get_error());
            // Walkover home: home gets max points
            $this->assertEquals(2, $this->validator->get_home_points());
            $this->assertEquals(0, $this->validator->get_away_points());
        }

        public function test_validate_invalid_score_in_match(): void
        {
            $match = $this->create_mock_match();
            $sets = [
                1 => ['player1' => '6', 'player2' => '6', 'tiebreak' => ''], // Invalid tied score
                2 => ['player1' => '6', 'player2' => '2', 'tiebreak' => ''],
                3 => ['player1' => '', 'player2' => '', 'tiebreak' => ''],
            ];

            $this->validator->validate($match, $sets, null, 'set_');

            $this->assertTrue($this->validator->get_error());
            $this->assertContains('set_1_player1', $this->validator->get_err_flds());
        }

        public function test_validate_too_many_sets_entered(): void
        {
            $match = $this->create_mock_match();
            $sets = [
                1 => ['player1' => '6', 'player2' => '4', 'tiebreak' => ''],
                2 => ['player1' => '6', 'player2' => '4', 'tiebreak' => ''],
                3 => ['player1' => '6', 'player2' => '4', 'tiebreak' => ''], // Should be empty
            ];

            $this->validator->validate($match, $sets, null, 'set_');

            $this->assertTrue($this->validator->get_error());
            $this->assertContains('set_3_player1', $this->validator->get_err_flds());
            $this->assertContains('Set score should be empty', $this->validator->get_err_msgs());
        }
    }
}
