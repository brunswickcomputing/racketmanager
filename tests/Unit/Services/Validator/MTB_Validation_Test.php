<?php
declare(strict_types=1);

namespace Racketmanager\Tests\Unit\Services\Validator;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Scoring\Scoring_Context;
use Racketmanager\Services\Validator\Score_Validation_Service;

if (!function_exists('__')) {
    function __($text, $domain) {
        return $text;
    }
}

class MTB_Validation_Test extends TestCase
{
    private Score_Validation_Service $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new Score_Validation_Service();
    }

    public function test_validate_mtb_set_score_9_11_no_tiebreak_required(): void
    {
        // Scoring context for a match with MTB decider
        $context = new Scoring_Context(
            num_sets_to_win: 2,
            scoring_type:    'TM', // TM means 3rd set is MTB
            point_rule:      ['match_result' => 'sets'],
            is_championship: false,
            num_sets:        3
        );

        $sets = [
            1 => ['player1' => '6', 'player2' => '4', 'tiebreak' => ''],
            2 => ['player1' => '4', 'player2' => '6', 'tiebreak' => ''],
            3 => ['player1' => '9', 'player2' => '11', 'tiebreak' => ''],
        ];
        
        $this->validator->validate($context, $sets, null, 'set_1_');

        $this->assertFalse($this->validator->get_error(), 'Should not have validation errors');
        $this->assertNotContains('set_1_3_tiebreak', $this->validator->get_err_flds(), 'MTB should not require a tiebreak score');
    }
}
