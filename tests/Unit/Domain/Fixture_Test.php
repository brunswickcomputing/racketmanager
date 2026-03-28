<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Result\Result;

final class Fixture_Test extends TestCase {
    public function test_set_result_updates_fixture_properties(): void {
        $fixture = new Fixture((object)[
            'id' => 1,
            'status' => 0
        ]);

        $result = new Result(
            home_points: 3.0,
            away_points: 1.0,
            winner_id: 10,
            loser_id: 20,
            status: 1, // Walkover
            sets: [['p1' => 6, 'p2' => 4]],
            custom: ['some' => 'data']
        );

        $fixture->set_result($result);

        $this->assertSame('3', $fixture->get_home_points());
        $this->assertSame('1', $fixture->get_away_points());
        $this->assertSame(10, $fixture->get_winner_id());
        $this->assertSame(20, $fixture->get_loser_id());
        $this->assertSame(1, $fixture->get_status());
        $this->assertTrue($fixture->is_walkover());
        
        $custom = $fixture->get_custom();
        $this->assertSame('data', $custom['some']);
        $this->assertSame([['p1' => 6, 'p2' => 4]], $custom['sets']);
    }

    public function test_set_result_updates_status_flags(): void {
        $fixture = new Fixture((object)['id' => 1]);
        
        // Test Shared status
        $result = new Result(home_points: 2, away_points: 2, status: 3);
        $fixture->set_result($result);
        $this->assertTrue($fixture->is_shared());
        $this->assertFalse($fixture->is_walkover());

        // Test Cancelled status
        $result = new Result(home_points: 0, away_points: 0, status: 8);
        $fixture->set_result($result);
        $this->assertTrue($fixture->is_cancelled());
        $this->assertFalse($fixture->is_shared());
    }
}
