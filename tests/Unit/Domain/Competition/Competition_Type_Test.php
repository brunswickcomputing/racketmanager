<?php
declare( strict_types=1 );
namespace Racketmanager\Tests\Unit\Domain\Competition;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition_Type;
class Competition_Type_Test extends TestCase {
    public function test_is_league() {
        $this->assertTrue( Competition_Type::LEAGUE->is_league() );
        $this->assertFalse( Competition_Type::CUP->is_league() );
    }
    public function test_is_cup() {
        $this->assertTrue( Competition_Type::CUP->is_cup() );
        $this->assertFalse( Competition_Type::LEAGUE->is_cup() );
    }
    public function test_is_tournament() {
        $this->assertTrue( Competition_Type::TOURNAMENT->is_tournament() );
        $this->assertFalse( Competition_Type::LEAGUE->is_tournament() );
    }
    public function test_is_team_entry() {
        $this->assertTrue( Competition_Type::LEAGUE->is_team_entry() );
        $this->assertTrue( Competition_Type::CUP->is_team_entry() );
        $this->assertFalse( Competition_Type::TOURNAMENT->is_team_entry() );
    }
    public function test_is_player_entry() {
        $this->assertFalse( Competition_Type::LEAGUE->is_player_entry() );
        $this->assertFalse( Competition_Type::CUP->is_player_entry() );
        $this->assertTrue( Competition_Type::TOURNAMENT->is_player_entry() );
    }
}
