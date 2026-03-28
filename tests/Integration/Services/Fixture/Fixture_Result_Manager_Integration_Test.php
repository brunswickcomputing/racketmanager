<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Integration\Services\Fixture;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Racketmanager_Match;
use Racketmanager\Services\Fixture\Fixture_Result_Manager;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Result_Service;
use Racketmanager\Services\Competition\Knockout_Progression_Service;
use Racketmanager\Repositories\Repository_Provider;
use Racketmanager\Services\Fixture\Service_Provider as Fixture_Service_Provider;
use Racketmanager\Domain\DTO\Fixture\Fixture_Result_Update_Request;
use Racketmanager\Domain\DTO\Fixture\Team_Result_Update_Request;
use Racketmanager\Domain\DTO\Fixture\Team_Result_Confirmation_Request;
use Racketmanager\Domain\DTO\Rubber\Rubber_Update_Request;
use Racketmanager\Services\Result\Rubber_Result_Manager;
use Racketmanager\Services\Validator\Player_Validation_Service;
use Racketmanager\Services\Notification\Notification_Service;
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
    private $player_validator;
    private $notification_service;
    private $manager;
    private $league_team_repository;
    private $rubber_repository;
    private $results_checker_repository;

    protected function setUp(): void {
        parent::setUp();
        $this->result_service = $this->createMock(Result_Service::class);
        $this->progression_service = $this->createMock(Knockout_Progression_Service::class);
        $this->league_service = $this->createMock(League_Service::class);
        $this->score_validator = $this->createMock(Score_Validation_Service::class);
        $this->rubber_manager = $this->createMock(Rubber_Result_Manager::class);
        $this->player_validator = $this->createMock(Player_Validation_Service::class);
        $this->notification_service = $this->createMock(Notification_Service::class);
        $this->league_team_repository = $this->createMock(\Racketmanager\Repositories\League_Team_Repository::class);
        $this->rubber_repository = $this->createMock(\Racketmanager\Repositories\Rubber_Repository::class);
        $this->results_checker_repository = $this->createMock(\Racketmanager\Repositories\Results_Checker_Repository::class);
        $fixture_repository = $this->createMock(\Racketmanager\Repositories\Fixture_Repository::class);

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
            league_team_repository: $this->league_team_repository,
            rubber_repository: $this->rubber_repository,
            results_checker_repository: $this->results_checker_repository,
            fixture_repository: $fixture_repository
        );

        $service_provider = new Fixture_Service_Provider(
            result_service: $this->result_service,
            progression_service: $this->progression_service,
            league_service: $this->league_service,
            score_validator: $this->score_validator,
            rubber_manager: $this->rubber_manager,
            notification_service: $this->notification_service,
            registration_service: $reg_service
        );

        $this->manager = new Fixture_Result_Manager(
            $service_provider,
            $repository_provider
        );
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
        $method->setAccessible(true);

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
        $method->setAccessible(true);

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
        $method->setAccessible(true);

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

        $league = $this->getMockBuilder(League::class)
                       ->disableOriginalConstructor()
                       ->onlyMethods(['get_id', 'get_name', 'get_event_id', 'get_point_rule'])
                       ->addMethods(['get_competition_type'])
                       ->getMock();
        $league->method('get_id')->willReturn(456);
        $league->method('get_name')->willReturn('Test League');
        $league->method('get_event_id')->willReturn(10);
        $league->method('get_point_rule')->willReturn(['match_result' => 'sets']);
        $league->method('get_competition_type')->willReturn('league');
        $league->num_sets_to_win = 2;
        $league->num_sets = 3;
        $league->num_rubbers = 1;

        $event = $this->getMockBuilder(\Racketmanager\Domain\Competition\Event::class)
                      ->disableOriginalConstructor()
                      ->getMock();
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

    public function test_handle_team_result_confirmation_updates_fixture(): void {
        $fixture_data = new stdClass();
        $fixture_data->id = 123;
        $fixture_data->league_id = 456;
        $fixture_data->home_team = '100';
        $fixture_data->away_team = '200';
        $fixture_data->confirmed = null;
        $fixture = new Fixture($fixture_data);

        $league = $this->getMockBuilder(League::class)
                       ->disableOriginalConstructor()
                       ->onlyMethods(['get_id'])
                       ->addMethods(['get_competition_type'])
                       ->getMock();
        $league->method('get_id')->willReturn(456);
        $league->method('get_competition_type')->willReturn('league');
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

    public function test_handle_player_warnings_returns_correct_data(): void {
        $fixture_data = new stdClass();
        $fixture_data->id = 123;
        $fixture_data->home_team = '100';
        $fixture_data->away_team = '200';
        $fixture = new Fixture($fixture_data);

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
        $method->setAccessible(true);

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

        $league = $this->createMock(League::class);
        $league->method('get_id')->willReturn(456);
        $event = $this->getMockBuilder(\Racketmanager\Domain\Competition\Event::class)
                      ->disableOriginalConstructor()
                      ->getMock();
        $competition = $this->getMockBuilder(\Racketmanager\Domain\Competition\Competition::class)
                            ->disableOriginalConstructor()
                            ->getMock();
        $event->competition = $competition;
        $league->event = $event;

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
}
