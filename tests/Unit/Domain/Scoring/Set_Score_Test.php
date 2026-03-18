<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Scoring;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Scoring\Set_Score;

final class Set_Score_Test extends TestCase {
    public function test_getters(): void {
        $score = new Set_Score(6, 4, 7, 5);
        $this->assertSame(6, $score->get_home_games());
        $this->assertSame(4, $score->get_away_games());
        $this->assertSame(7, $score->get_home_tiebreak());
        $this->assertSame(5, $score->get_away_tiebreak());
    }

    public function test_array_access(): void {
        $score = new Set_Score(6, 4, 7, 5);
        
        // Test player1/player2 alias
        $this->assertSame(6, $score['player1']);
        $this->assertSame(4, $score['player2']);
        
        // Test home/away alias
        $this->assertSame(6, $score['home']);
        $this->assertSame(4, $score['away']);
        
        // Test tiebreak
        $this->assertSame(7, $score['home_tb']);
        $this->assertSame(5, $score['away_tb']);
        $this->assertSame(7, $score['tiebreak']);
    }

    public function test_tiebreak_alias_away(): void {
        $score = new Set_Score(6, 7, null, 10);
        $this->assertSame(10, $score['tiebreak']);
    }

    public function test_winner(): void {
        $this->assertSame('home', (new Set_Score(6, 4))->winner());
        $this->assertSame('away', (new Set_Score(4, 6))->winner());
        $this->assertSame('home', (new Set_Score(7, 6, 7, 5))->winner());
        $this->assertSame('away', (new Set_Score(6, 7, 5, 7))->winner());
        $this->assertNull((new Set_Score(6, null))->winner());
    }

    public function test_immutable_set(): void {
        $this->expectException(\BadMethodCallException::class);
        $score = new Set_Score(6, 4);
        $score['home'] = 7;
    }

    public function test_immutable_unset(): void {
        $this->expectException(\BadMethodCallException::class);
        $score = new Set_Score(6, 4);
        unset($score['home']);
    }
}
