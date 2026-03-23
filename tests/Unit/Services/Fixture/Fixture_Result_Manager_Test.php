<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Fixture;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Fixture;
use Racketmanager\Domain\League;
use Racketmanager\Domain\Racketmanager_Match;
use Racketmanager\Services\Fixture\Fixture_Result_Manager;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Result_Service;
use Racketmanager\Services\Competition\Knockout_Progression_Service;
use Racketmanager\Domain\DTO\Fixture\Fixture_Result_Update_Request;
use Racketmanager\Services\Validator\Fixture_Score_Validator;
use Racketmanager\Domain\Competition\Stage;
use Racketmanager\Repositories\League_Repository;
use stdClass;

class Fixture_Result_Manager_Test extends TestCase {
    private $result_service;
    private $progression_service;
    private $league_service;
    private $score_validator;
    private $manager;

    protected function setUp(): void {
        parent::setUp();
        $this->result_service = $this->createMock(Result_Service::class);
        $this->progression_service = $this->createMock(Knockout_Progression_Service::class);
        $this->league_service = $this->createMock(League_Service::class);
        $this->score_validator = $this->createMock(Fixture_Score_Validator::class);
        $this->manager = new Fixture_Result_Manager(
            $this->result_service,
            $this->progression_service,
            $this->league_service,
            $this->score_validator
        );
    }

    public function test_reset_result_for_non_championship_league(): void {
        $fixture_data = new stdClass();
        $fixture_data->id = 123;
        $fixture_data->league_id = 456;
        $fixture_data->season = '2026';
        $fixture_data->home_points = '3';
        $fixture_data->away_points = '1';
        $fixture_data->winner_id = 10;
        $fixture = new Fixture($fixture_data);

        $league = $this->createMock(League::class);
        $league->id = 456;
        $league->is_championship = false;
        $league->method('get_id')->willReturn(456);
        $league->method('get_event_id')->willReturn(10);
        
        $this->league_service->method('get_league')->with(456)->willReturn($league);
        $GLOBALS['wp_stubs_leagues'][456] = $league;

        $league->expects($this->once())
               ->method('update_standings')
               ->with('2026');

        $this->progression_service->expects($this->never())
                                  ->method('reset_progression');

        // Result_Service::apply_to_fixture is expected to be called during reset to save the fixture.
        $this->result_service->expects($this->once())
                             ->method('apply_to_fixture')
                             ->with($fixture, $this->isInstanceOf(\Racketmanager\Domain\Result::class), null);

        $this->manager->reset_result($fixture);

        $this->assertNull($fixture->get_home_points());
        $this->assertNull($fixture->get_away_points());
        $this->assertEquals(0, $fixture->get_winner_id());
        $this->assertNull($fixture->get_confirmed());
    }

    public function test_reset_result_for_championship_league(): void {
        $fixture_data = new stdClass();
        $fixture_data->id = 123;
        $fixture_data->league_id = 789;
        $fixture_data->season = '2026';
        $fixture_data->final = 'R1';
        $fixture_data->confirmed = 'Y';
        $fixture = new Fixture($fixture_data);

        $league = $this->createMock(League::class);
        $league->id = 789;
        $league->is_championship = true;
        $league->method('get_id')->willReturn(789);
        $league->method('get_name')->willReturn('Tournament');
        $league->method('get_event_id')->willReturn(10);
        
        $this->league_service->method('get_league')->with(789)->willReturn($league);
        $GLOBALS['wp_stubs_leagues'][789] = $league;

        $this->progression_service->expects($this->once())
               ->method('reset_progression')
               ->with($this->isInstanceOf(Stage::class), $fixture, $league);

        $league->expects($this->never())
               ->method('update_standings');

        // Result_Service::apply_to_fixture is expected to be called during reset to save the fixture.
        $this->result_service->expects($this->once())
                             ->method('apply_to_fixture')
                             ->with($fixture, $this->isInstanceOf(\Racketmanager\Domain\Result::class), null);

        $this->manager->reset_result($fixture);

        $this->assertNull($fixture->get_home_points());
        $this->assertNull($fixture->get_confirmed());
    }

    public function test_handle_single_result_update_triggers_progression_for_championship(): void {
        $fixture_data = new stdClass();
        $fixture_data->id = 123;
        $fixture_data->league_id = 789;
        $fixture_data->season = '2026';
        $fixture_data->final = 'R1';
        $fixture = new Fixture($fixture_data);

        $league = $this->createMock(League::class);
        $league->id = 789;
        $league->is_championship = true;
        $league->method('get_id')->willReturn(789);
        $league->method('get_name')->willReturn('Tournament');
        $league->method('get_event_id')->willReturn(10);
        
        $GLOBALS['wp_stubs_leagues'][789] = $league;
        
        $match = $this->createMock(Racketmanager_Match::class);
        $GLOBALS['wp_stubs_matches'][123] = $match;

        $this->result_service->expects($this->once())
                             ->method('apply_to_fixture');

        $this->progression_service->expects($this->once())
                                  ->method('progress_winner')
                                  ->with($this->isInstanceOf(Stage::class), $fixture, $league);

        $request = new Fixture_Result_Update_Request(123, [], 'share', 'Y');
        $this->manager->handle_fixture_result_update($fixture, $request);
    }
}
