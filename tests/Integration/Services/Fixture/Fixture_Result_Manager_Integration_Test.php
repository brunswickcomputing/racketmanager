<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Integration\Services\Fixture;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Fixture;
use Racketmanager\Domain\League;
use Racketmanager\Domain\Racketmanager_Match;
use Racketmanager\Services\Fixture\Fixture_Result_Manager;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Result_Service;
use Racketmanager\Services\Competition\Knockout_Progression_Service;
use Racketmanager\Domain\DTO\Fixture\Fixture_Result_Update_Request;
use Racketmanager\Domain\DTO\Fixture\Team_Result_Update_Request;
use Racketmanager\Domain\DTO\Fixture\Team_Result_Confirmation_Request;
use Racketmanager\Domain\DTO\Rubber\Rubber_Update_Request;
use Racketmanager\Services\Result\Rubber_Result_Manager;
use Racketmanager\Domain\Enums\Fixture\Fixture_Update_Status;
use Racketmanager\Services\Validator\Score_Validation_Service;
use Racketmanager\Domain\Competition\Stage;
use Racketmanager\Repositories\League_Repository;
use Racketmanager\Domain\Scoring\Scoring_Context;
use stdClass;

class Fixture_Result_Manager_Integration_Test extends TestCase {
    private $result_service;
    private $progression_service;
    private $league_service;
    private $score_validator;
    private $rubber_manager;
    private $manager;

    protected function setUp(): void {
        parent::setUp();
        $this->result_service = $this->createMock(Result_Service::class);
        $this->progression_service = $this->createMock(Knockout_Progression_Service::class);
        $this->league_service = $this->createMock(League_Service::class);
        $this->score_validator = $this->createMock(Score_Validation_Service::class);
        $this->rubber_manager = $this->createMock(Rubber_Result_Manager::class);
        $reg_service = $this->createMock(\Racketmanager\Services\Registration_Service::class);
        $reg_service->method('get_dummy_players')->willReturn([]);
        $comp_service = $this->createMock(\Racketmanager\Services\Competition_Service::class);
        $club_service = $this->createMock(\Racketmanager\Services\Club_Service::class);
        $player_service = $this->createMock(\Racketmanager\Services\Player_Service::class);
        $container = new \Racketmanager\Services\Container\Simple_Container();
        $container->set('registration_service', $reg_service);
        $container->set('competition_service', $comp_service);
        $container->set('club_service', $club_service);
        $container->set('player_service', $player_service);
        $racketmanager_instance = (object)[
            'container' => $container,
            'get_confirmation_email' => function() { return 'admin@example.com'; }
        ];
        $GLOBALS['racketmanager'] = $racketmanager_instance;

        $this->manager = new Fixture_Result_Manager(
            $this->result_service,
            $this->progression_service,
            $this->league_service,
            $this->score_validator,
            $this->rubber_manager,
            $reg_service
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
        $league->method('get_point_rule')->willReturn(['match_result' => 'sets']);
        $league->num_sets_to_win = 2;
        $league->num_sets = 3;
        
        $this->league_service->method('get_league')->with(789)->willReturn($league);
        $GLOBALS['wp_stubs_leagues'][789] = $league;
        
        $this->result_service->expects($this->once())
                             ->method('apply_to_fixture');

        $this->score_validator->expects($this->once())
                             ->method('validate')
                             ->with($this->isInstanceOf(Scoring_Context::class), [], 'share', 'set_');

        $this->progression_service->expects($this->once())
                                  ->method('progress_winner')
                                  ->with($this->isInstanceOf(Stage::class), $fixture, $league);

        $request = new Fixture_Result_Update_Request(123, [], 'share', 'Y');
        $response = $this->manager->handle_fixture_result_update($fixture, $request);

        $this->assertInstanceOf(\Racketmanager\Domain\DTO\Fixture\Fixture_Update_Response::class, $response);
        $this->assertTrue($response->has_outcome(Fixture_Update_Status::SAVED));
        $this->assertTrue($response->has_outcome(Fixture_Update_Status::PROGRESSED));
    }

    public function test_handle_single_result_update_triggers_standings_for_division(): void {
        $fixture_data = new stdClass();
        $fixture_data->id = 123;
        $fixture_data->league_id = 456;
        $fixture_data->season = '2026';
        $fixture = new Fixture($fixture_data);

        $league = $this->createMock(League::class);
        $league->id = 456;
        $league->is_championship = false;
        $league->method('get_id')->willReturn(456);
        $league->method('get_event_id')->willReturn(10);
        $league->method('get_point_rule')->willReturn(['match_result' => 'sets']);
        $league->num_sets_to_win = 2;
        $league->num_sets = 3;

        $this->league_service->method('get_league')->with(456)->willReturn($league);
        $GLOBALS['wp_stubs_leagues'][456] = $league;

        $this->result_service->expects($this->once())
                             ->method('apply_to_fixture');

        $league->expects($this->once())
               ->method('update_standings')
               ->with('2026');

        $request = new Fixture_Result_Update_Request(123, [], 'share', 'Y');
        $response = $this->manager->handle_fixture_result_update($fixture, $request);

        $this->assertInstanceOf(\Racketmanager\Domain\DTO\Fixture\Fixture_Update_Response::class, $response);
        $this->assertTrue($response->has_outcome(Fixture_Update_Status::SAVED));
        $this->assertTrue($response->has_outcome(Fixture_Update_Status::TABLE_UPDATED));
    }

    public function test_handle_team_result_update_no_longer_calls_legacy_match(): void {
        $fixture_data = new stdClass();
        $fixture_data->id = 123;
        $fixture_data->league_id = 456;
        $fixture_data->home_team = '100';
        $fixture_data->away_team = '200';
        $fixture_data->stage_id = 1;
        $fixture_data->season = '2026';
        $fixture = new Fixture($fixture_data);

        $league = $this->createMock(League::class);
        $league->method('get_id')->willReturn(456);
        $league->method('get_name')->willReturn('Test League');
        $league->method('get_event_id')->willReturn(10);
        $league->method('get_point_rule')->willReturn(['match_result' => 'sets']);
        $league->num_sets_to_win = 2;
        $league->num_sets = 3;
        $this->league_service->method('get_league')->willReturn($league);

        $fixture_repo = $this->createMock(\Racketmanager\Repositories\League_Repository::class);
        $fixture_repo->method('find_by_id')->willReturn($league);

        $home_team = (object)['club_id' => 10, 'is_withdrawn' => false];
        $away_team = (object)['club_id' => 20, 'is_withdrawn' => false];
        $GLOBALS['wp_stubs_teams'][100] = $home_team;
        $GLOBALS['wp_stubs_teams'][200] = $away_team;

        $rubber_result = new \Racketmanager\Domain\DTO\Rubber\Rubber_Update_Result(
            rubber_id: 10,
            home_points: 2.0,
            away_points: 0.0,
            winner_id: 100,
            players: [],
            sets: [],
            status: 0,
            custom: [],
            stats: ['sets' => ['home' => 2, 'away' => 0], 'games' => ['home' => 12, 'away' => 0]]
        );

        $this->rubber_manager->expects($this->once())
             ->method('handle_rubber_update')
             ->willReturn($rubber_result);

        $request = new Team_Result_Update_Request(
            match_id: 123,
            match_status: 'completed',
            rubber_statuses: ['1' => 'share'],
            match_comments: ['comments'],
            rubber_ids: [1 => 10],
            rubber_types: [1 => 'S'],
            players: [1 => []],
            sets: [1 => []]
        );

        $result = $this->manager->handle_team_result_update($fixture, $request, $fixture_repo);

        $this->assertEquals('success', $result->status);
        $this->assertArrayHasKey(10, $result->rubbers);

        unset($GLOBALS['wp_stubs_teams'][100]);
        unset($GLOBALS['wp_stubs_teams'][200]);
    }
}
