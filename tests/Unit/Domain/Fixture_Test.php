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

    public function test_is_pending_reflects_winner_id(): void {
        $fixture = new Fixture((object)[ 'winner_id' => null ]);
        $this->assertTrue($fixture->is_pending());

        $fixture = new Fixture((object)[ 'winner_id' => 0 ]);
        $this->assertTrue($fixture->is_pending());

        $fixture = new Fixture((object)[ 'winner_id' => 123 ]);
        $this->assertFalse($fixture->is_pending());

        $result = new Result(home_points: 3, away_points: 0, winner_id: 456);
        $fixture->set_result($result);
        $this->assertFalse($fixture->is_pending());

        $fixture->reset_result();
        $this->assertTrue($fixture->is_pending());
    }

    public function test_start_time_is_set_from_constructor(): void {
        $fixture = new Fixture((object)[ 'start_time' => '10:00' ]);
        $this->assertEquals('10:00', $fixture->get_start_time());

        $fixture->set_start_time('11:30');
        $this->assertEquals('11:30', $fixture->get_start_time());
    }
}
