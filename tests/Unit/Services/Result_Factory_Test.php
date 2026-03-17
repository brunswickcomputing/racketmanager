<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Racketmanager\Services\Result_Factory;

final class Result_Factory_Test extends TestCase {
    public function test_from_array_creates_result_with_full_data(): void {
        $data = [
            'home_points' => 3.5,
            'away_points' => 2.5,
            'winner_id'   => 123,
            'loser_id'    => 456,
            'status'      => 4,
            'is_walkover' => true,
            'sets'        => [['p1' => 6, 'p2' => 0]],
            'custom'      => ['note' => 'test']
        ];

        $result = Result_Factory::from_array($data);

        $this->assertSame(3.5, $result->get_home_points());
        $this->assertSame(2.5, $result->get_away_points());
        $this->assertSame(123, $result->get_winner_id());
        $this->assertSame(456, $result->get_loser_id());
        $this->assertSame(4, $result->get_status());
        $this->assertTrue($result->is_walkover());
        $this->assertSame([['p1' => 6, 'p2' => 0]], $result->get_sets());
        $this->assertSame(['note' => 'test'], $result->get_custom());
    }

    public function test_from_array_handles_empty_data(): void {
        $result = Result_Factory::from_array([]);

        $this->assertSame(0.0, $result->get_home_points());
        $this->assertSame(0.0, $result->get_away_points());
        $this->assertNull($result->get_winner_id());
        $this->assertNull($result->get_loser_id());
        $this->assertNull($result->get_status());
        $this->assertFalse($result->is_walkover());
        $this->assertSame([], $result->get_sets());
        $this->assertSame([], $result->get_custom());
    }
}
