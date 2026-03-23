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
    use Racketmanager\Domain\Scoring\Scoring_Context;
    use Racketmanager\Services\Validator\Score_Validation_Service;
    use stdClass;

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

        public function test_validate_complete_match_2_1(): void
        {
            $context = $this->create_scoring_context();
            $sets = [
                1 => ['player1' => '6', 'player2' => '4', 'tiebreak' => ''],
                2 => ['player1' => '4', 'player2' => '6', 'tiebreak' => ''],
                3 => ['player1' => '6', 'player2' => '0', 'tiebreak' => ''],
            ];

            $this->validator->validate($context, $sets, null, 'set_');

            $this->assertFalse($this->validator->get_error());
            $this->assertEquals(2, $this->validator->get_home_points());
            $this->assertEquals(1, $this->validator->get_away_points());
        }

        public function test_validate_retired_match(): void
        {
            $context = $this->create_scoring_context();
            // Player 1 retires in the second set
            $sets = [
                1 => ['player1' => '6', 'player2' => '4', 'tiebreak' => ''],
                2 => ['player1' => '1', 'player2' => '2', 'tiebreak' => ''],
                3 => ['player1' => '', 'player2' => '', 'tiebreak' => ''],
            ];

            $this->validator->validate($context, $sets, 'retired_player1', 'set_');

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
            $context = $this->create_scoring_context();
            $sets = [
                1 => ['player1' => '6', 'player2' => '4', 'tiebreak' => ''],
                2 => ['player1' => '2', 'player2' => '2', 'tiebreak' => ''],
                3 => ['player1' => '', 'player2' => '', 'tiebreak' => ''],
            ];

            $this->validator->validate($context, $sets, 'abandoned', 'set_');

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
            $context = $this->create_scoring_context();
            $sets = [
                1 => ['player1' => '', 'player2' => '', 'tiebreak' => ''],
                2 => ['player1' => '', 'player2' => '', 'tiebreak' => ''],
                3 => ['player1' => '', 'player2' => '', 'tiebreak' => ''],
            ];

            $this->validator->validate($context, $sets, 'walkover_player1', 'set_');

            $this->assertFalse($this->validator->get_error());
            // Walkover home: home gets max points
            $this->assertEquals(2, $this->validator->get_home_points());
            $this->assertEquals(0, $this->validator->get_away_points());
        }

        public function test_validate_invalid_score_in_match(): void
        {
            $context = $this->create_scoring_context();
            $sets = [
                1 => ['player1' => '6', 'player2' => '6', 'tiebreak' => ''], // Invalid tied score
                2 => ['player1' => '6', 'player2' => '2', 'tiebreak' => ''],
                3 => ['player1' => '', 'player2' => '', 'tiebreak' => ''],
            ];

            $this->validator->validate($context, $sets, null, 'set_');

            $this->assertTrue($this->validator->get_error());
            $this->assertContains('set_1_player1', $this->validator->get_err_flds());
        }

        public function test_validate_too_many_sets_entered(): void
        {
            $context = $this->create_scoring_context();
            $sets = [
                1 => ['player1' => '6', 'player2' => '4', 'tiebreak' => ''],
                2 => ['player1' => '6', 'player2' => '4', 'tiebreak' => ''],
                3 => ['player1' => '6', 'player2' => '4', 'tiebreak' => ''], // Should be empty
            ];

            $this->validator->validate($context, $sets, null, 'set_');

            $this->assertTrue($this->validator->get_error());
            $this->assertContains('set_3_player1', $this->validator->get_err_flds());
            $this->assertContains('Set score should be empty', $this->validator->get_err_msgs());
        }
    }
}
