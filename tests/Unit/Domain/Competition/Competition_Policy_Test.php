<?php
declare( strict_types=1 );
namespace Racketmanager\Tests\Unit\Domain\Competition;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition_Policy;
class Competition_Policy_Test extends TestCase {
    public function test_generate_cup_finals() {
        $finals = Competition_Policy::generate_cup_finals(3);
        $this->assertCount( 3, $finals );
        $this->assertArrayHasKey( 'final', $finals );
        $this->assertArrayHasKey( 'semi', $finals );
        $this->assertArrayHasKey( 'quarter', $finals );
        $this->assertEquals( 2, $finals['final']['num_teams'] );
        $this->assertEquals( 1, $finals['final']['num_matches'] );
        $this->assertEquals( 4, $finals['semi']['num_teams'] );
        $this->assertEquals( 2, $finals['semi']['num_matches'] );
        $this->assertEquals( 8, $finals['quarter']['num_teams'] );
        $this->assertEquals( 4, $finals['quarter']['num_matches'] );
    }
}
