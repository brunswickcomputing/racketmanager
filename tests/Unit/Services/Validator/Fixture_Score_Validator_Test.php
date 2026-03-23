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
    use Racketmanager\Services\Validator\Score_Validation_Service;
    use stdClass;

    class Fixture_Score_Validator_Test extends TestCase
    {
        private $validator;

        protected function setUp(): void
        {
            parent::setUp();
            $this->validator = new Score_Validation_Service();
        }

        private function get_set_info($overrides = []): stdClass
        {
            $info = new stdClass();
            $info->set_type = 'standard';
            $info->tiebreak_allowed = true;
            $info->tiebreak_required = false;
            $info->max_win = 6;
            $info->min_win = 6;
            $info->max_loss = 4;
            $info->min_loss = 0;
            $info->tiebreak_set = 5;

            foreach ($overrides as $key => $value) {
                $info->$key = $value;
            }

            return $info;
        }

        /**
         * @dataProvider scores_provider
         */
        public function test_validate_set(array $set, object $set_info, ?string $match_status, ?string $expected_err_field): void
        {
            $result = $this->validator->validate_set($set, 'set_1_', $set_info, $match_status);

            if ($expected_err_field === null) {
                $this->assertFalse($this->validator->get_error(), 'Validator should not have error. Errors: ' . implode(', ', $this->validator->get_err_msgs()));

                if ($match_status === 'share' || $match_status === 'withdrawn') {
                    $this->assertEmpty($result['player1'], "Player 1 score should be cleared for {$match_status} status");
                    $this->assertEmpty($result['player2'], "Player 2 score should be cleared for {$match_status} status");
                } elseif ($match_status && str_starts_with($match_status, 'walkover')) {
                    if ($set_info->set_type === 'null') {
                        $this->assertEquals('', $result['player1']);
                        $this->assertEquals('', $result['player2']);
                    } else {
                        $this->assertNull($result['player1']);
                        $this->assertNull($result['player2']);
                    }
                } elseif ($match_status === 'cancelled') {
                    $this->assertNull($result['player1']);
                    $this->assertNull($result['player2']);
                } elseif (isset($set['player1']) && $set['player1'] === 'S' && isset($set['player2']) && $set['player2'] === 'S') {
                    // Shared scores don't necessarily mark set as completed in the same way,
                    // but the current implementation doesn't set it to true for 'S' scores.
                } elseif ($match_status === 'retired_player1' && ($set['player1'] === '' && $set['player2'] === '')) {
                    $this->assertFalse($result['completed'], 'Empty scores with retirement should not be marked as completed');
                } else {
                    $this->assertTrue($result['completed'], 'Set should be marked as completed');
                }
            } else {
                $this->assertTrue($this->validator->get_error(), 'Validator should have error');
                $this->assertContains($expected_err_field, $this->validator->get_err_flds(), 'Error fields: ' . implode(', ', $this->validator->get_err_flds()));
            }
        }

        public function scores_provider(): array
        {
            return [
                // Valid scores
                'standard 6-0' => [
                    ['player1' => 6, 'player2' => 0, 'tiebreak' => ''],
                    $this->get_set_info(),
                    null,
                    null
                ],
                'standard 6-4' => [
                    ['player1' => 6, 'player2' => 4, 'tiebreak' => ''],
                    $this->get_set_info(),
                    null,
                    null
                ],
                'standard 7-5' => [
                    ['player1' => 7, 'player2' => 5, 'tiebreak' => ''],
                    $this->get_set_info(['max_win' => 7, 'min_win' => 6, 'max_loss' => 5, 'tiebreak_set' => 6]),
                    null,
                    null
                ],
                'standard 7-6 with tiebreak' => [
                    ['player1' => 7, 'player2' => 6, 'tiebreak' => '7'],
                    $this->get_set_info(['max_win' => 7, 'min_win' => 6, 'max_loss' => 6]),
                    null,
                    null
                ],
                'retired 3-2' => [
                    ['player1' => 3, 'player2' => 2, 'tiebreak' => ''],
                    $this->get_set_info(),
                    'retired_player1',
                    null
                ],
                // Invalid scores
                'standard 6-5 (missing tiebreak)' => [
                    ['player1' => 6, 'player2' => 5, 'tiebreak' => ''],
                    $this->get_set_info(['tiebreak_allowed' => true]),
                    null,
                    'set_1_tiebreak'
                ],
                'standard 8-6 (too high)' => [
                    ['player1' => 8, 'player2' => 6, 'tiebreak' => ''],
                    $this->get_set_info(['max_win' => 6, 'min_win' => 6, 'max_loss' => 4, 'tiebreak_allowed' => false]),
                    null,
                    'set_1_player1'
                ],
                'tied scores' => [
                    ['player1' => 5, 'player2' => 5, 'tiebreak' => ''],
                    $this->get_set_info(),
                    null,
                    'set_1_player1'
                ],
                'empty scores' => [
                    ['player1' => '', 'player2' => '', 'tiebreak' => ''],
                    $this->get_set_info(),
                    null,
                    'set_1_player1'
                ],
                '7-6 missing tiebreak (explicitly required)' => [
                    ['player1' => 7, 'player2' => 6, 'tiebreak' => ''],
                    $this->get_set_info(['max_win' => 7, 'min_win' => 6, 'max_loss' => 6, 'tiebreak_required' => true]),
                    null,
                    'set_1_tiebreak'
                ],
                // Tiebreak score validation
                'valid numeric tiebreak' => [
                    ['player1' => 7, 'player2' => 6, 'tiebreak' => '10'],
                    $this->get_set_info(['max_win' => 7, 'min_win' => 6, 'max_loss' => 6]),
                    null,
                    null
                ],
                // Retired / Abandoned scenarios
                'abandoned set (completed by default)' => [
                    ['player1' => 2, 'player2' => 1, 'tiebreak' => ''],
                    $this->get_set_info(),
                    'abandoned',
                    null
                ],
                'retired set with low score (allowed by current logic)' => [
                    ['player1' => 2, 'player2' => 1, 'tiebreak' => ''],
                    $this->get_set_info(['min_win' => 6]),
                    'retired_player1',
                    null
                ],
                // Additional scenarios for match_score (indirectly via validate_set)
                'share status clears scores' => [
                    ['player1' => 6, 'player2' => 4, 'tiebreak' => ''],
                    $this->get_set_info(),
                    'share',
                    null
                ],
                'null set type expects empty scores' => [
                    ['player1' => 6, 'player2' => 4, 'tiebreak' => ''],
                    $this->get_set_info(['set_type' => 'null']),
                    null,
                    'set_1_player1'
                ],
                // New scenarios for validate_set
                'walkover home clears scores' => [
                    ['player1' => 6, 'player2' => 0, 'tiebreak' => ''],
                    $this->get_set_info(),
                    'walkover_player1',
                    null
                ],
                'walkover away clears scores' => [
                    ['player1' => 0, 'player2' => 6, 'tiebreak' => ''],
                    $this->get_set_info(),
                    'walkover_player2',
                    null
                ],
                'walkover null set type empty scores' => [
                    ['player1' => '', 'player2' => '', 'tiebreak' => ''],
                    $this->get_set_info(['set_type' => 'null']),
                    'walkover_player1',
                    null
                ],
                'cancelled status nulls scores' => [
                    ['player1' => 6, 'player2' => 4, 'tiebreak' => ''],
                    $this->get_set_info(),
                    'cancelled',
                    null
                ],
                'withdrawn status clears scores' => [
                    ['player1' => 6, 'player2' => 4, 'tiebreak' => ''],
                    $this->get_set_info(),
                    'withdrawn',
                    null
                ],
                'shared score S-S' => [
                    ['player1' => 'S', 'player2' => 'S', 'tiebreak' => ''],
                    $this->get_set_info(),
                    null,
                    null
                ],
                'shared score missing on player1' => [
                    ['player1' => 6, 'player2' => 'S', 'tiebreak' => ''],
                    $this->get_set_info(),
                    null,
                    'set_1_player1'
                ],
                'shared score missing on player2' => [
                    ['player1' => 'S', 'player2' => 4, 'tiebreak' => ''],
                    $this->get_set_info(),
                    null,
                    'set_1_player2'
                ],
                'empty scores with retirement allowed' => [
                    ['player1' => '', 'player2' => '', 'tiebreak' => ''],
                    $this->get_set_info(),
                    'retired_player1',
                    null
                ],
                'empty score player 1' => [
                    ['player1' => '', 'player2' => 4, 'tiebreak' => ''],
                    $this->get_set_info(),
                    null,
                    null
                ],
                'empty score player 2' => [
                    ['player1' => 6, 'player2' => '', 'tiebreak' => ''],
                    $this->get_set_info(),
                    null,
                    null
                ],
                'both scores empty' => [
                    ['player1' => '', 'player2' => '', 'tiebreak' => ''],
                    $this->get_set_info(),
                    null,
                    'set_1_player1'
                ],
                'partially empty scores with retirement' => [
                    ['player1' => '', 'player2' => 4, 'tiebreak' => ''],
                    $this->get_set_info(),
                    'retired_player1',
                    null
                ],
            ];
        }
    }
}
