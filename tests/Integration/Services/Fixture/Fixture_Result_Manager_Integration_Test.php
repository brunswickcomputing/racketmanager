<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Integration\Services\Fixture;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Racketmanager_Match;
use Racketmanager\Services\Fixture\Fixture_Result_Manager;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Result_Service;
use Racketmanager\Services\Competition\Knockout_Progression_Service;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Results_Checker_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Results_Report_Repository_Interface;
use Racketmanager\Services\Fixture\Fixture_Maintenance_Service;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Repositories\Repository_Provider;
use Racketmanager\Services\Fixture\Service_Provider as Fixture_Service_Provider;
use Racketmanager\Domain\DTO\Fixture\Fixture_Result_Update_Request;
use Racketmanager\Domain\DTO\Fixture\Team_Result_Update_Request;
use Racketmanager\Domain\DTO\Fixture\Team_Result_Confirmation_Request;
use Racketmanager\Services\Result\Rubber_Result_Manager;
use Racketmanager\Services\Validator\Player_Validation_Service;
use Racketmanager\Services\Notification\Notification_Service;
use Racketmanager\Domain\Enums\Fixture\Fixture_Update_Status;
use Racketmanager\Services\Validator\Score_Validation_Service;
use Racketmanager\Domain\Competition\Stage;
use Racketmanager\Domain\Scoring\Scoring_Context;
use Racketmanager\Domain\DTO\Rubber\Rubber_Update_Result;
use stdClass;

interface Event_Mock_Interface {
    public function get_season_by_name();
    public function competition_obj();
}

class League_Mock extends \Racketmanager\Domain\Competition\League {
    public function get_competition_type(): string { return 'league'; }
    public function update_standings(string $season): void {}
}

class Tournament_Mock extends \Racketmanager\Domain\Competition\League {
    public function get_competition_type(): string { return 'tournament'; }
}

class Event_Mock extends \Racketmanager\Domain\Competition\Event {
    public function competition_obj() { return $this->competition; }
    public function get_season_by_name(string $name): ?array { return []; }
}

#[AllowMockObjectsWithoutExpectations]
class Fixture_Result_Manager_Integration_Test extends TestCase {
    private $result_service;
    private $progression_service;
    private $league_service;
    private $score_validator;
    private $rubber_manager;
    private $player_validator;
    private $notification_service;
    private $settings_service;
    private $manager;
    private $permission_service;
    private $league_team_repository;
    private $team_repository;
    private $player_repository;
    private $league_repository_repo;
    private $rubber_repository;
    private $results_checker_repository;
    private $results_report_repository;
    private $fixture_maintenance_service;
    private $fixture_repository;
    private $result_reporting_service;

    protected function setUp(): void {
        parent::setUp();
        $this->result_service = $this->createMock(Result_Service::class);
        $this->progression_service = $this->createMock(Knockout_Progression_Service::class);
        $this->league_service = $this->createMock(League_Service::class);
        $this->score_validator = $this->createMock(Score_Validation_Service::class);
        $this->rubber_manager = $this->createMock(Rubber_Result_Manager::class);
        $this->player_validator = $this->createMock(Player_Validation_Service::class);
        $this->notification_service = $this->createMock(Notification_Service::class);
        $this->settings_service = $this->createMock(\Racketmanager\Services\Settings_Service::class);
        $this->settings_service->method('get_all_options')->willReturn([]);
        $this->league_team_repository = $this->createMock(League_Team_Repository_Interface::class);
        $this->team_repository = $this->createMock(Team_Repository_Interface::class);
        $this->player_repository = $this->createMock(Player_Repository_Interface::class);
        $this->league_repository_repo = $this->createMock(League_Repository_Interface::class);
        $this->rubber_repository = $this->createMock(Rubber_Repository_Interface::class);
        $this->results_checker_repository = $this->createMock(Results_Checker_Repository_Interface::class);
        $this->results_report_repository = $this->createMock(Results_Report_Repository_Interface::class);
        $this->fixture_repository = $this->createMock(Fixture_Repository_Interface::class);
        $this->result_reporting_service = $this->createMock(\Racketmanager\Services\Result\Result_Reporting_Service::class);

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
        $racketmanager_instance = new class($container) {
            public $container;
            public $result_warnings = [];
            public function __construct($container) { $this->container = $container; }
            public function get_confirmation_email() { return 'admin@example.com'; }
            public function get_options() {
                return [
                    'league' => ['resultConfirmation' => 'manual'],
                    'tournament' => ['resultConfirmation' => 'manual'],
                ];
            }
            public function get_result_warnings( $args ) { return $this->result_warnings; }
        };
        $GLOBALS['racketmanager'] = $racketmanager_instance;

        $repository_provider = new Repository_Provider(
            league_repository: $this->league_repository_repo,
            league_team_repository: $this->league_team_repository,
            team_repository: $this->team_repository,
            player_repository: $this->player_repository,
            rubber_repository: $this->rubber_repository,
            results_checker_repository: $this->results_checker_repository,
            results_report_repository: $this->results_report_repository,
            fixture_repository: $this->fixture_repository
        );

        $service_provider = new Fixture_Service_Provider(
            result_service: $this->result_service,
            progression_service: $this->progression_service,
            league_service: $this->league_service,
            score_validator: $this->score_validator,
            notification_service: $this->notification_service,
            registration_service: $reg_service
        );
        $service_provider->set_player_validator( $this->player_validator );
        $service_provider->set_rubber_manager( $this->rubber_manager );
        $service_provider->set_settings_service( $this->settings_service );
        $service_provider->set_result_reporting_service( $this->result_reporting_service );
        $this->fixture_maintenance_service = $this->createMock( Fixture_Maintenance_Service::class );
        $service_provider->set_fixture_maintenance_service( $this->fixture_maintenance_service );

        $this->permission_service = $this->createMock(\Racketmanager\Services\Fixture\Fixture_Permission_Service::class);
        $this->permission_service->method('is_update_allowed')->willReturnCallback(function($fixture) {
            if ($fixture->get_id() === 999) {
                return (object)[
                    'user_can_update' => false,
                    'user_type' => 'none',
                    'user_team' => 'none',
                    'message' => 'Result entry not permitted',
                    'match_approval_mode' => false,
                    'match_update' => false
                ];
            }
            return (object)[
                'user_can_update' => true,
                'user_type' => 'admin',
                'user_team' => 'home',
                'message' => '',
                'match_approval_mode' => false,
                'match_update' => true
            ];
        });
        $service_provider->set_fixture_permission_service($this->permission_service);

        $this->manager = new Fixture_Result_Manager(
            $service_provider,
            $repository_provider
        );

        $GLOBALS['wp_stubs_current_user_can'] = true;
        $GLOBALS['wp_stubs_get_current_user_id'] = 1;
    }

    protected function tearDown(): void {
        unset($GLOBALS['wp_stubs_current_user_can']);
        unset($GLOBALS['wp_stubs_get_current_user_id']);
        parent::tearDown();
    }

    public function test_is_any_team_withdrawn_returns_true_if_home_team_is_withdrawn(): void {
        $fixture_data = new stdClass();
        $fixture_data->id = 123;
        $fixture_data->league_id = 456;
        $fixture_data->home_team = '100';
        $fixture_data->away_team = '200';
        $fixture_data->season = '2026';
        $fixture = new Fixture($fixture_data);

        $home_league_team = $this->createMock(\Racketmanager\Domain\Competition\League_Team::class);
        $home_league_team->is_withdrawn = true;
        $away_league_team = $this->createMock(\Racketmanager\Domain\Competition\League_Team::class);
        $away_league_team->is_withdrawn = false;

        $this->league_team_repository->method('find_by_team_league_and_season')
            ->willReturnMap([
                [100, 456, 2026, $home_league_team],
                [200, 456, 2026, $away_league_team],
            ]);

        $reflection = new \ReflectionClass(Fixture_Result_Manager::class);
        $method = $reflection->getMethod('is_any_team_withdrawn');

        $this->assertTrue($method->invoke($this->manager, $fixture));
    }

    public function test_is_any_team_withdrawn_returns_true_if_away_team_is_withdrawn(): void {
        $fixture_data = new stdClass();
        $fixture_data->id = 123;
        $fixture_data->league_id = 456;
        $fixture_data->home_team = '100';
        $fixture_data->away_team = '200';
        $fixture_data->season = '2026';
        $fixture = new Fixture($fixture_data);

        $home_league_team = $this->createMock(\Racketmanager\Domain\Competition\League_Team::class);
        $home_league_team->is_withdrawn = false;
        $away_league_team = $this->createMock(\Racketmanager\Domain\Competition\League_Team::class);
        $away_league_team->is_withdrawn = true;

        $this->league_team_repository->method('find_by_team_league_and_season')
            ->willReturnMap([
                [100, 456, 2026, $home_league_team],
                [200, 456, 2026, $away_league_team],
            ]);

        $reflection = new \ReflectionClass(Fixture_Result_Manager::class);
        $method = $reflection->getMethod('is_any_team_withdrawn');

        $this->assertTrue($method->invoke($this->manager, $fixture));
    }

    public function test_is_any_team_withdrawn_returns_false_if_no_team_is_withdrawn(): void {
        $fixture_data = new stdClass();
        $fixture_data->id = 123;
        $fixture_data->league_id = 456;
        $fixture_data->home_team = '100';
        $fixture_data->away_team = '200';
        $fixture_data->season = '2026';
        $fixture = new Fixture($fixture_data);

        $home_league_team = $this->createMock(\Racketmanager\Domain\Competition\League_Team::class);
        $home_league_team->is_withdrawn = false;
        $away_league_team = $this->createMock(\Racketmanager\Domain\Competition\League_Team::class);
        $away_league_team->is_withdrawn = false;

        $this->league_team_repository->method('find_by_team_league_and_season')
            ->willReturnMap([
                [100, 456, 2026, $home_league_team],
                [200, 456, 2026, $away_league_team],
            ]);

        $reflection = new \ReflectionClass(Fixture_Result_Manager::class);
        $method = $reflection->getMethod('is_any_team_withdrawn');

        $this->assertFalse($method->invoke($this->manager, $fixture));
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
                             ->with($fixture, $this->isInstanceOf(\Racketmanager\Domain\Result\Result::class), null);

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
                             ->with($fixture, $this->isInstanceOf(\Racketmanager\Domain\Result\Result::class), null);

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

        $event = $this->getMockBuilder(\Racketmanager\Domain\Competition\Event::class)
                      ->disableOriginalConstructor()
                      ->getMock();
        $event->competition = (object)['type' => 'league'];
        $league->event = $event;
        
        $this->league_service->method('get_league')->with(789)->willReturn($league);
        $this->league_repository_repo->method('find_by_id')->with(789)->willReturn($league);
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

        $event = $this->getMockBuilder(\Racketmanager\Domain\Competition\Event::class)
                      ->disableOriginalConstructor()
                      ->getMock();
        $event->competition = (object)['type' => 'league'];
        $league->event = $event;

        $this->league_service->method('get_league')->with(456)->willReturn($league);
        $this->league_repository_repo->method('find_by_id')->with(456)->willReturn($league);
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

        $league = $this->getMockBuilder(League_Mock::class)
                       ->disableOriginalConstructor()
                       ->onlyMethods(['get_id', 'get_name', 'get_event_id', 'get_point_rule'])
                       ->getMock();
        $league->method('get_id')->willReturn(456);
        $league->method('get_name')->willReturn('Test League');
        $league->method('get_event_id')->willReturn(10);
        $league->method('get_point_rule')->willReturn(['match_result' => 'sets']);
        $league->num_sets_to_win = 2;
        $league->num_sets = 3;
        $league->num_rubbers = 1;

        $event = $this->getMockBuilder(\Racketmanager\Domain\Competition\Event::class)
                      ->disableOriginalConstructor()
                      ->getMock();
        $event->competition = (object)['type' => 'league'];
        $league->event = $event;

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

        $this->result_service->expects($this->once())
             ->method('apply_to_fixture')
             ->willReturnCallback(function($f, $r, $c) {
                 $f->set_result($r);
                 $f->set_confirmed($c);
             });

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

        // Verify winner_id and loser_id on the fixture (since update_result should have been called)
        $this->assertEquals(100, $fixture->get_winner_id(), 'Winner ID should be 100');
        $this->assertEquals(200, $fixture->get_loser_id(), 'Loser ID should be 200');

        unset($GLOBALS['wp_stubs_teams'][100]);
        unset($GLOBALS['wp_stubs_teams'][200]);
    }

    public function test_handle_team_result_update_sets_date_result_entered(): void {
        $fixture_data = new stdClass();
        $fixture_data->id = 123;
        $fixture_data->league_id = 456;
        $fixture_data->home_team = '100';
        $fixture_data->away_team = '200';
        $fixture_data->stage_id = 1;
        $fixture_data->season = '2026';
        $fixture_data->date_result_entered = null;
        $fixture = new Fixture($fixture_data);

        $league = $this->getMockBuilder(League_Mock::class)
                       ->disableOriginalConstructor()
                       ->onlyMethods(['get_id', 'get_name', 'get_event_id', 'get_point_rule'])
                       ->getMock();
        $league->method('get_id')->willReturn(456);
        $league->method('get_name')->willReturn('Test League');
        $league->method('get_event_id')->willReturn(10);
        $league->method('get_point_rule')->willReturn(['match_result' => 'sets']);
        $league->num_sets_to_win = 2;
        $league->num_sets = 3;
        $league->num_rubbers = 1;

        $event = $this->getMockBuilder(\Racketmanager\Domain\Competition\Event::class)
                      ->disableOriginalConstructor()
                      ->getMock();
        $event->competition = (object)['type' => 'league'];
        $league->event = $event;

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

        $this->rubber_manager->method('handle_rubber_update')
             ->willReturn($rubber_result);

        $this->result_service->expects($this->once())
             ->method('apply_to_fixture')
             ->willReturnCallback(function($f, $r, $c) {
                 $f->set_result($r);
                 $f->set_confirmed($c);
             });

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

        $this->assertNull($fixture->get_date_result_entered());

        $this->manager->handle_team_result_update($fixture, $request, $fixture_repo);

        $this->assertNotNull($fixture->get_date_result_entered());
        $this->assertStringMatchesFormat('%d-%d-%d %d:%d:%d', $fixture->get_date_result_entered());

        unset($GLOBALS['wp_stubs_teams'][100]);
        unset($GLOBALS['wp_stubs_teams'][200]);
    }

    public function test_handle_fixture_result_update_sets_date_result_entered(): void {
        $fixture_data = new stdClass();
        $fixture_data->id = 123;
        $fixture_data->league_id = 456;
        $fixture_data->home_team = '100';
        $fixture_data->away_team = '200';
        $fixture_data->season = '2026';
        $fixture_data->date_result_entered = null;
        $fixture = new Fixture($fixture_data);

        $league = $this->getMockBuilder(Tournament_Mock::class)
                       ->disableOriginalConstructor()
                       ->onlyMethods(['get_id', 'get_point_rule'])
                       ->getMock();
        $league->method('get_id')->willReturn(456);
        $league->method('get_point_rule')->willReturn(['match_result' => 'sets']);
        $league->num_sets_to_win = 2;
        $league->num_sets = 3;
        $league->num_rubbers = 0;
        $league->is_championship = true;

        $this->league_service->method('get_league')->willReturn($league);

        $fixture_repo = $this->createMock(\Racketmanager\Repositories\League_Repository::class);
        $fixture_repo->method('find_by_id')->willReturn($league);

        $this->score_validator->method('get_error')->willReturn(false);

        $request = new Fixture_Result_Update_Request(123, ['set_1_home' => 6, 'set_1_away' => 0], 'completed', 'P');

        $this->assertNull($fixture->get_date_result_entered());

        $this->manager->handle_fixture_result_update($fixture, $request);

        $this->assertNotNull($fixture->get_date_result_entered());
        $this->assertStringMatchesFormat('%d-%d-%d %d:%d:%d', $fixture->get_date_result_entered());
    }

    public function test_handle_team_result_confirmation_updates_fixture(): void {
        $fixture_data = new stdClass();
        $fixture_data->id = 123;
        $fixture_data->league_id = 456;
        $fixture_data->home_team = '100';
        $fixture_data->away_team = '200';
        $fixture_data->confirmed = null;
        $fixture = new Fixture($fixture_data);

        $league = $this->getMockBuilder(League_Mock::class)
                       ->disableOriginalConstructor()
                       ->onlyMethods(['get_id'])
                       ->getMock();
        $league->method('get_id')->willReturn(456);
        $event = $this->getMockBuilder(\Racketmanager\Domain\Competition\Event::class)
                      ->disableOriginalConstructor()
                      ->getMock();
        $event->competition = (object)['type' => 'league'];
        $league->event = $event;
        $this->league_service->method('get_league')->willReturn($league);

        // Mock result_notification if we don't want to rely on get_match
        // But for now, we'll let it call get_match and we'll stub it
        $legacy_match = $this->getMockBuilder(Racketmanager_Match::class)
                             ->disableOriginalConstructor()
                             ->getMock();
        $GLOBALS['wp_stubs_matches'][123] = $legacy_match;

        $request = new Team_Result_Confirmation_Request(
            match_id: 123,
            result_confirm: 'A',
            confirm_comments: 'Looks good',
            result_home: true,
            result_away: false
        );

        $this->result_service->expects($this->once())
                             ->method('apply_to_fixture');

        $result = $this->manager->handle_team_result_confirmation($fixture, $request);

        $this->assertEquals('success', $result->status);
        $this->assertEquals('A', $fixture->get_confirmed());
        $comments = maybe_unserialize($fixture->get_comments());
        $this->assertArrayHasKey('home_confirm', $comments);
        $this->assertEquals('Looks good', $comments['home_confirm']);

        unset($GLOBALS['wp_stubs_matches'][123]);
    }

    public function test_handle_team_result_update_blocks_unauthorized_user(): void {
        $fixture_data = new stdClass();
        $fixture_data->id = 999;
        $fixture_data->league_id = 456;
        $fixture_data->home_team = '100';
        $fixture_data->away_team = '200';
        $fixture_data->season = '2026';
        $fixture = new Fixture($fixture_data);

        $league = $this->createMock(League::class);
        $league->method('get_id')->willReturn(456);
        $event = $this->getMockBuilder(\Racketmanager\Domain\Competition\Event::class)
                      ->disableOriginalConstructor()
                      ->getMock();
        $event->competition = (object)['type' => 'league'];
        $league->event = $event;
        $this->league_service->method('get_league')->willReturn($league);
        $this->league_repository_repo->method('find_by_id')->willReturn($league);

        $GLOBALS['wp_stubs_current_user_can'] = false;

        $home_team = $this->getMockBuilder(\Racketmanager\Domain\Team::class)
                          ->disableOriginalConstructor()
                          ->getMock();
        $home_team->method('get_club_id')->willReturn(10);
        $away_team = $this->getMockBuilder(\Racketmanager\Domain\Team::class)
                          ->disableOriginalConstructor()
                          ->getMock();
        $away_team->method('get_club_id')->willReturn(20);

        $request = new Team_Result_Update_Request(
            match_id: 999,
            match_status: 'completed',
            rubber_statuses: ['1' => 'completed'],
            match_comments: [],
            rubber_ids: [1 => 10],
            rubber_types: [1 => 'S'],
            players: [1 => []],
            sets: [1 => []]
        );

        $this->rubber_manager->method('handle_rubber_update')->willReturn(new Rubber_Update_Result(
            rubber_id: 10,
            home_points: 2.0,
            away_points: 0.0,
            winner_id: 5,
            players: [],
            sets: [],
            status: 1,
            custom: [],
            stats: []
        ));

        $result = $this->manager->handle_team_result_update($fixture, $request);

        $this->assertTrue($result->error, 'Should have an error for unauthorized user');
        $this->assertEquals('Result entry not permitted', $result->msg);
    }

    public function test_confirm_result_updates_fixture_and_triggers_updates(): void {
        $fixture_data = new stdClass();
        $fixture_data->id = 123;
        $fixture_data->league_id = 456;
        $fixture_data->season = '2026';
        $fixture_data->home_team = '100';
        $fixture_data->away_team = '200';
        $fixture_data->home_points = '3';
        $fixture_data->away_points = '2';
        $fixture_data->status = 0;
        $fixture_data->confirmed = null;
        $fixture_data->custom = null;
        $fixture = new Fixture($fixture_data);

        $league = $this->createMock(League::class);
        $league->method('get_id')->willReturn(456);
        $league->method('get_name')->willReturn('Test League');
        $league->method('get_event_id')->willReturn(1);
        $league->is_championship = false;
        $event = $this->getMockBuilder(\Racketmanager\Domain\Competition\Event::class)
                      ->disableOriginalConstructor()
                      ->getMock();
        $event->competition = (object)['type' => 'league'];
        $league->event = $event;

        $this->league_service->method('get_league')->willReturn($league);
        $this->league_repository_repo->method('find_by_id')->with(456)->willReturn($league);

        $this->result_service->expects($this->once())
                             ->method('apply_to_fixture')
                             ->with($this->equalTo($fixture), $this->isInstanceOf(\Racketmanager\Domain\Result\Result::class), $this->equalTo('Y'));

        $response = $this->manager->confirm_result($fixture, 'home', 'Direct confirmation');

        $this->assertEquals('Y', $fixture->get_confirmed());
        $comments = maybe_unserialize($fixture->get_comments());
        $this->assertEquals('Direct confirmation', $comments['home_confirm']);
        $this->assertInstanceOf(\Racketmanager\Domain\DTO\Fixture\Fixture_Update_Response::class, $response);
    }

    public function test_handle_player_warnings_returns_correct_data(): void {
        $fixture_data = new stdClass();
        $fixture_data->id = 123;
        $fixture_data->home_team = '100';
        $fixture_data->away_team = '200';
        $fixture_data->league_id = 456;
        $fixture = new Fixture($fixture_data);

        $league = $this->createMock(League::class);
        $league->method('get_id')->willReturn(456);
        $event = $this->getMockBuilder(\Racketmanager\Domain\Competition\Event::class)
                      ->disableOriginalConstructor()
                      ->getMock();
        $event->competition = (object)['type' => 'league'];
        $league->event = $event;
        $this->league_repository_repo->method('find_by_id')->willReturn($league);

        $this->results_checker_repository->expects($this->once())
            ->method('has_results_check')
            ->with(123)
            ->willReturn(true);

        $player_warning = new stdClass();
        $player_warning->rubber_id = 10;
        $player_warning->team_id = 100;
        $player_warning->player_id = 5;
        $player_warning->description = 'Warning description';

        $this->results_checker_repository->expects($this->once())
            ->method('find_by_fixture_id')
            ->with(123)
            ->willReturn([$player_warning]);

        $rubber = $this->createMock(\Racketmanager\Domain\Fixture\Rubber::class);
        $rubber->rubber_number = 1;
        $rubber->players = [
            'home' => [
                '1' => (object)['id' => 5],
                '2' => (object)['id' => 6]
            ]
        ];

        $this->rubber_repository->expects($this->once())
            ->method('find_by_id')
            ->with(10)
            ->willReturn($rubber);

        $reflection = new \ReflectionClass(Fixture_Result_Manager::class);
        $method = $reflection->getMethod('handle_player_warnings');

        $result = $method->invoke($this->manager, $fixture);

        $this->assertEquals('warning', $result->status);
        $this->assertStringContainsString('Match has player warnings', $result->msg);
        $this->assertArrayHasKey('players_1_home_1', $result->warnings);
        $this->assertEquals('Warning description', $result->warnings['players_1_home_1']);
    }

    public function test_run_fixture_checks_loads_players_from_ids(): void {
        $fixture_data = new stdClass();
        $fixture_data->id = 123;
        $fixture_data->league_id = 456;
        $fixture_data->season = '2026';
        $fixture = new Fixture($fixture_data);

        $league = $this->getMockBuilder(League_Mock::class)
                       ->disableOriginalConstructor()
                       ->onlyMethods(['get_id'])
                       ->getMock();
        $league->method('get_id')->willReturn(456);
        $event = $this->getMockBuilder(Event_Mock::class)
                      ->onlyMethods(['get_season_by_name', 'competition_obj'])
                      ->disableOriginalConstructor()
                      ->getMock();
        $competition = $this->getMockBuilder(\Racketmanager\Domain\Competition\Competition::class)
                            ->onlyMethods(['get_season_by_name'])
                            ->disableOriginalConstructor()
                            ->getMock();
        $competition->type = 'league';
        $competition->is_player_entry = false;
        $event->competition = $competition;
        $event->method('competition_obj')->willReturn($competition);
        $league->event = $event;
        $this->league_repository_repo->method('find_by_id')->willReturn($league);

        $competition->method('get_season_by_name')->willReturn([]);
        $event->method('get_season_by_name')->willReturn([]);

        // Pass rubbers as array with player IDs instead of objects
        $rubbers = [
            [
                'id' => 10,
                'players' => [
                    'home' => [
                        '1' => 5, // Player ID 5
                        '2' => 6  // Player ID 6
                    ],
                    'away' => []
                ]
            ]
        ];

        $player_dto5 = $this->getMockBuilder(\Racketmanager\Domain\DTO\Club\Club_Player_DTO::class)
                            ->disableOriginalConstructor()
                            ->getMock();
        $player_dto5->id = 5;
        $player_dto5->wtn = ['S' => 20.0];

        $player_dto6 = $this->getMockBuilder(\Racketmanager\Domain\DTO\Club\Club_Player_DTO::class)
                            ->disableOriginalConstructor()
                            ->getMock();
        $player_dto6->id = 6;
        $player_dto6->wtn = ['S' => 21.0];

        $reg_service = $this->createMock(\Racketmanager\Services\Registration_Service::class);
        $reg_service->expects($this->exactly(2))
                    ->method('get_registration')
                    ->willReturnMap([
                        [5, $player_dto5],
                        [6, $player_dto6]
                    ]);

        $fixture_repo = $this->createMock(\Racketmanager\Repositories\Fixture_Repository::class);
        // Inject our mock reg_service into a real Player_Validation_Service
        $player_validator = new Player_Validation_Service($reg_service, $this->results_checker_repository, $fixture_repo);

        // We can't easily inject it into $this->manager because it's already created, 
        // but we can test the service directly.
        $player_validator->run_fixture_checks($fixture, $league, $rubbers, []);
        
        // If it didn't throw and reg_service was called, it means it tried to load them.
    }

    public function test_update_legs(): void {
        $fixture_data = new stdClass();
        $fixture_data->id = 123;
        $fixture = new Fixture( $fixture_data );

        $this->fixture_repository->expects( $this->once() )
                                 ->method( 'save' )
                                 ->with( $fixture );

        $response = $this->manager->update_legs( $fixture, 1, 456 );

        $this->assertEquals( 1, $fixture->get_leg() );
        $this->assertEquals( 456, $fixture->get_linked_match() );
        $this->assertTrue( $response->has_outcome( Fixture_Update_Status::LEGS_UPDATED ) );
    }

    public function test_apply_penalty_leg_1(): void {
        $fixture_data = new stdClass();
        $fixture_data->id          = 123;
        $fixture_data->home_team   = 10;
        $fixture_data->away_team   = 20;
        $fixture_data->home_points = '8.0';
        $fixture_data->away_points = '2.0';
        $fixture_data->leg         = 1;
        $fixture = new Fixture( $fixture_data );

        $this->fixture_repository->expects( $this->once() )
                                 ->method( 'save' )
                                 ->with( $fixture );

        $response = $this->manager->apply_penalty( $fixture, 'home', 2 );

        $this->assertEquals( 6.0, (float) $fixture->get_home_points() );
        $this->assertEquals( 2.0, (float) $fixture->get_away_points() );
        $this->assertEquals( 10, $fixture->get_winner_id() );
        $this->assertTrue( $response->has_outcome( Fixture_Update_Status::PENALTY_APPLIED ) );
        $this->assertFalse( $response->has_outcome( Fixture_Update_Status::TIE_UPDATED ) );
    }

    public function test_apply_penalty_leg_2_triggers_tie_update(): void {
        $fixture_data = new stdClass();
        $fixture_data->id          = 123;
        $fixture_data->home_team   = 10;
        $fixture_data->away_team   = 20;
        $fixture_data->home_points = '4.0';
        $fixture_data->away_points = '2.0';
        $fixture_data->leg         = 2;
        $fixture_data->linked_match = 456;
        $fixture = new Fixture( $fixture_data );

        $linked_data = new stdClass();
        $linked_data->id          = 456;
        $linked_data->home_team   = 20;
        $linked_data->away_team   = 10;
        $linked_data->home_points = '5.0';
        $linked_data->away_points = '1.0';
        $linked_data->winner_id   = 20;
        $linked_fixture = new Fixture( $linked_data );

        $this->fixture_repository->expects( $this->exactly( 1 ) )
                                 ->method( 'find_by_id' )
                                 ->with( 456 )
                                 ->willReturn( $linked_fixture );

        $this->fixture_repository->expects( $this->exactly( 2 ) )
                                 ->method( 'save' );

        $response = $this->manager->apply_penalty( $fixture, 'away', 1 );

        // Leg 2 Home: 4.0, Away: 2.0 - 1.0 = 1.0
        // Leg 1 (Linked) Home (Team 20): 5.0, Away (Team 10): 1.0
        // Aggregate Team 10: 4.0 (leg 2 home) + 1.0 (leg 1 away) = 5.0
        // Aggregate Team 20: 1.0 (leg 2 away) + 5.0 (leg 1 home) = 6.0

        $this->assertEquals( 1.0, (float) $fixture->get_away_points() );
        $this->assertEquals( 5.0, (float) $fixture->get_home_points_tie() );
        $this->assertEquals( 6.0, (float) $fixture->get_away_points_tie() );
        $this->assertEquals( 20, $fixture->get_winner_id_tie() );
        $this->assertTrue( $response->has_outcome( Fixture_Update_Status::PENALTY_APPLIED ) );
        $this->assertTrue( $response->has_outcome( Fixture_Update_Status::TIE_UPDATED ) );
    }
    public function test_confirm_result_manages_report(): void {
        $fixture = new Fixture( (object) [ 'id' => 123, 'league_id' => 1, 'home_points' => 2, 'away_points' => 1, 'confirmed' => 'Y' ] );
        
        $this->fixture_maintenance_service->expects( $this->once() )
            ->method( 'delete_result_report' )
            ->with( 123 );

        $this->result_reporting_service->expects( $this->once() )
            ->method( 'report_result' )
            ->with( $fixture )
            ->willReturn( (object) [ 'report' => 'data' ] );

        $this->fixture_maintenance_service->expects( $this->once() )
            ->method( 'save_result_report' )
            ->with( 123, (object) [ 'report' => 'data' ] );

        $this->manager->confirm_result( $fixture );
    }

    public function test_handle_fixture_result_update_assigns_home_captain(): void {
        $GLOBALS['wp_stubs_get_current_user_id'] = 123;

        $fixture_data = new stdClass();
        $fixture_data->id = 123;
        $fixture_data->league_id = 1;
        $fixture_data->home_captain = null;
        $fixture = new Fixture( $fixture_data );

        $league = $this->createStub( League::class );
        $this->league_service->method( 'get_league' )->willReturn( $league );

        $this->permission_service->method( 'is_update_allowed' )->willReturn( (object) [
            'user_team' => 'home'
        ] );

        $request = new Fixture_Result_Update_Request(
            fixture_id: 123,
            sets: [],
            match_status: 'played',
            confirmed: 'N'
        );

        $this->score_validator->method( 'get_err_msgs' )->willReturn( [] );

        $this->manager->handle_fixture_result_update( $fixture, $request );

        $this->assertEquals( 123, $fixture->get_home_captain() );
        
        unset( $GLOBALS['wp_stubs_get_current_user_id'] );
    }
}
