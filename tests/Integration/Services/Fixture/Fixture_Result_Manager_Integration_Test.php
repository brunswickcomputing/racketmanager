<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Integration\Services\Fixture;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Competition\League;
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
use Racketmanager\Services\Fixture\Fixture_Finalization_Service;
use Racketmanager\Services\Fixture\Fixture_Maintenance_Service;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Repositories\Repository_Provider;
use Racketmanager\Services\Fixture\Service_Provider as Fixture_Service_Provider;
use Racketmanager\Services\Result\Rubber_Result_Manager;
use Racketmanager\Services\Validator\Player_Validation_Service;
use Racketmanager\Services\Notification\Notification_Service;
use Racketmanager\Domain\Enums\Fixture\Fixture_Update_Status;
use Racketmanager\Services\Validator\Score_Validation_Service;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Stage;
use Racketmanager\Domain\Scoring\Scoring_Context;
use Racketmanager\Tests\Helpers\Fixture_Test_Factory;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use Racketmanager\Tests\Helpers\Result_Test_Factory;
use stdClass;

#[AllowMockObjectsWithoutExpectations]
class Fixture_Result_Manager_Integration_Test extends TestCase {
    use Manages_Wordpress_Globals;

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
    private $fixture_finalization_service;
    private $fixture_repository;
    private $result_reporting_service;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
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
        $this->fixture_finalization_service = new Fixture_Finalization_Service(
            null,
            $this->progression_service,
            $this->result_reporting_service
        );
        $service_provider->set_fixture_finalization_service( $this->fixture_finalization_service );
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
        $this->restore_globals();
        parent::tearDown();
    }

    private function create_league_mock(
        int $id = 456,
        bool $is_championship = false,
        string $comp_type = 'league',
        string $name = 'Test League',
        int $num_sets = 3,
        int $num_sets_to_win = 2,
        int $num_rubbers = 1
    ): League {
        $league = $this->createMock( League::class );
        $league->id = $id;
        $league->is_championship = $is_championship;
        $league->num_sets = $num_sets;
        $league->num_sets_to_win = $num_sets_to_win;
        $league->num_rubbers = $num_rubbers;
        $league->method( 'get_id' )->willReturn( $id );
        $league->method( 'get_name' )->willReturn( $name );
        $league->method( 'get_event_id' )->willReturn( 10 );
        $league->method( 'get_point_rule' )->willReturn( [ 'match_result' => 'sets' ] );
        $league->method( 'get_competition_type' )->willReturn( $comp_type );

        $event = $this->createMock( \Racketmanager\Domain\Competition\Event::class );
        $event->method( 'get_season_by_name' )->willReturn( [] );
        $event->method( 'get_type' )->willReturn( $comp_type );
        $event->competition = (object) [ 'type' => $comp_type ];
        $league->event = $event;

        return $league;
    }

    private function assert_is_any_team_withdrawn( bool $home_withdrawn, bool $away_withdrawn, bool $expected ): void {
        $fixture = Fixture_Test_Factory::create( id: 123, league_id: 456, home_team: '100', away_team: '200', season: '2026' );
        $home_team = $this->createMock( \Racketmanager\Domain\Competition\League_Team::class );
        $home_team->is_withdrawn = $home_withdrawn;
        $away_team = $this->createMock( \Racketmanager\Domain\Competition\League_Team::class );
        $away_team->is_withdrawn = $away_withdrawn;

        $this->league_team_repository->method( 'find_by_team_league_and_season' )
            ->willReturnMap( [
                [ 100, 456, 2026, $home_team ],
                [ 200, 456, 2026, $away_team ],
            ] );

        $reflection = new \ReflectionClass( Fixture_Result_Manager::class );
        $method     = $reflection->getMethod( 'is_any_team_withdrawn' );
        $this->assertSame( $expected, $method->invoke( $this->manager, $fixture ) );
    }

    public function test_is_any_team_withdrawn_returns_true_if_home_team_is_withdrawn(): void {
        $this->assert_is_any_team_withdrawn( true, false, true );
    }

    public function test_is_any_team_withdrawn_returns_true_if_away_team_is_withdrawn(): void {
        $this->assert_is_any_team_withdrawn( false, true, true );
    }

    public function test_is_any_team_withdrawn_returns_false_if_no_team_is_withdrawn(): void {
        $this->assert_is_any_team_withdrawn( false, false, false );
    }

    public function test_reset_result_for_non_championship_league(): void {
        $fixture = Fixture_Test_Factory::create(
            id: 123,
            league_id: 456,
            season: '2026',
            home_points: '3',
            away_points: '1',
            winner_id: 10
        );

        $league = $this->create_league_mock( id: 456, is_championship: false );
        
        $this->league_service->expects( $this->atLeastOnce() )->method( 'get_league' )->with( 456 )->willReturn( $league );
        $GLOBALS['wp_stubs_leagues'][456] = $league;

        $league->expects( $this->once() )
               ->method( 'update_standings' )
               ->with( '2026' );

        $this->progression_service->expects( $this->never() )
                                  ->method( 'reset_progression' );

        // Result_Service::apply_to_fixture is expected to be called during reset to save the fixture.
        $this->result_service->expects( $this->once() )
                             ->method( 'apply_to_fixture' )
                             ->with( $fixture, $this->isInstanceOf( \Racketmanager\Domain\Result\Result::class ), null );

        $this->rubber_repository->expects( $this->once() )
                                ->method( 'find_by_fixture_id' )
                                ->with( 123 )
                                ->willReturn( [] );

        $this->manager->reset_result( $fixture );

        $this->assertNull( $fixture->get_home_points() );
        $this->assertNull( $fixture->get_away_points() );
        $this->assertEquals( 0, $fixture->get_winner_id() );
        $this->assertNull( $fixture->get_confirmed() );
    }

    public function test_reset_result_for_championship_league(): void {
        $fixture = Fixture_Test_Factory::create(
            id: 123,
            league_id: 789,
            season: '2026',
            final: 'R1',
            confirmed: 'Y'
        );

        $league = $this->create_league_mock( id: 789, is_championship: true, name: 'Tournament' );
        
        $this->league_service->expects( $this->atLeastOnce() )->method( 'get_league' )->with( 789 )->willReturn( $league );
        $GLOBALS['wp_stubs_leagues'][789] = $league;

        $this->progression_service->expects( $this->once() )
               ->method( 'reset_progression' )
               ->with( $this->isInstanceOf( Stage::class ), $fixture, $league );

        $league->expects( $this->never() )
               ->method( 'update_standings' );

        // Result_Service::apply_to_fixture is expected to be called during reset to save the fixture.
        $this->result_service->expects( $this->once() )
                             ->method( 'apply_to_fixture' )
                             ->with( $fixture, $this->isInstanceOf( \Racketmanager\Domain\Result\Result::class ), null );

        $this->rubber_repository->expects( $this->once() )
                                ->method( 'find_by_fixture_id' )
                                ->with( 123 )
                                ->willReturn( [] );

        $this->manager->reset_result( $fixture );

        $this->assertNull( $fixture->get_home_points() );
        $this->assertNull( $fixture->get_confirmed() );
    }

    public function test_handle_single_result_update_triggers_progression_for_championship(): void {
        $fixture = Fixture_Test_Factory::create(
            id: 123,
            league_id: 789,
            season: '2026',
            final: 'R1'
        );

        $league = $this->create_league_mock( id: 789, is_championship: true, name: 'Tournament', num_sets: 3, num_sets_to_win: 2 );
        
        $this->league_service->expects( $this->atLeastOnce() )->method( 'get_league' )->with( 789 )->willReturn( $league );
        $this->league_repository_repo->expects( $this->once() )->method( 'find_by_id' )->with( 789 )->willReturn( $league );
        $GLOBALS['wp_stubs_leagues'][789] = $league;
        
        $this->result_service->expects( $this->once() )
                             ->method( 'apply_to_fixture' );

        $this->score_validator->expects( $this->once() )
                             ->method( 'validate' )
                             ->with( $this->isInstanceOf( Scoring_Context::class ), [], 'share', 'set_' );

        $this->progression_service->expects( $this->once() )
                                  ->method( 'progress_winner' )
                                  ->with( $this->isInstanceOf( Stage::class ), $fixture, $league );

        $request  = Result_Test_Factory::create_fixture_update_request( fixture_id: 123, sets: [], match_status: 'share', confirmed: 'Y' );
        $response = $this->manager->handle_fixture_result_update( $fixture, $request );

        $this->assertInstanceOf( \Racketmanager\Domain\DTO\Fixture\Fixture_Update_Response::class, $response );
        $this->assertTrue( $response->has_outcome( Fixture_Update_Status::SAVED ) );
        $this->assertTrue( $response->has_outcome( Fixture_Update_Status::PROGRESSED ) );
    }

    public function test_handle_single_result_update_triggers_standings_for_division(): void {
        $fixture = Fixture_Test_Factory::create(
            id: 123,
            league_id: 456,
            season: '2026'
        );

        $league = $this->create_league_mock( id: 456, is_championship: false, num_sets: 3, num_sets_to_win: 2 );

        $this->league_service->expects( $this->atLeastOnce() )->method( 'get_league' )->with( 456 )->willReturn( $league );
        $this->league_repository_repo->expects( $this->once() )->method( 'find_by_id' )->with( 456 )->willReturn( $league );
        $GLOBALS['wp_stubs_leagues'][456] = $league;

        $this->result_service->expects( $this->once() )
                             ->method( 'apply_to_fixture' );

        $league->expects( $this->once() )
               ->method( 'update_standings' )
               ->with( '2026' );

        $request  = Result_Test_Factory::create_fixture_update_request( fixture_id: 123, sets: [], match_status: 'share', confirmed: 'Y' );
        $response = $this->manager->handle_fixture_result_update( $fixture, $request );

        $this->assertInstanceOf( \Racketmanager\Domain\DTO\Fixture\Fixture_Update_Response::class, $response );
        $this->assertTrue( $response->has_outcome( Fixture_Update_Status::SAVED ) );
        $this->assertTrue( $response->has_outcome( Fixture_Update_Status::TABLE_UPDATED ) );
    }

    public function test_handle_team_result_update_no_longer_calls_legacy_match(): void {
        $fixture = Fixture_Test_Factory::create(
            id: 123,
            league_id: 456,
            home_team: '100',
            away_team: '200',
            season: '2026'
        );

        $league = $this->create_league_mock( id: 456, is_championship: false, num_sets: 3, num_sets_to_win: 2, num_rubbers: 1 );

        $this->league_service->method( 'get_league' )->willReturn( $league );

        $fixture_repo = $this->createMock( \Racketmanager\Repositories\League_Repository::class );
        $fixture_repo->method( 'find_by_id' )->willReturn( $league );

        $GLOBALS['wp_stubs_teams'][100] = (object) [ 'club_id' => 10, 'is_withdrawn' => false ];
        $GLOBALS['wp_stubs_teams'][200] = (object) [ 'club_id' => 20, 'is_withdrawn' => false ];

        $rubber_result = Result_Test_Factory::create_rubber_update_result(
            rubber_id: 10,
            home_points: 2.0,
            away_points: 0.0,
            winner_id: 100
        );

        $this->rubber_manager->expects( $this->once() )
             ->method( 'handle_rubber_update' )
             ->willReturn( $rubber_result );

        $this->result_service->expects( $this->once() )
             ->method( 'apply_to_fixture' )
             ->willReturnCallback( function ( $f, $r, $c ) {
                 $f->set_result( $r );
                 $f->set_confirmed( $c );
             } );

        $request = Result_Test_Factory::create_team_update_request(
            match_id: 123,
            match_status: 'completed',
            rubber_statuses: [ '1' => 'share' ],
            match_comments: [ 'comments' ],
            rubber_ids: [ 1 => 10 ],
            rubber_types: [ 1 => 'S' ],
            players: [ 1 => [ 'home' => [ 1 => 1001 ], 'away' => [ 1 => 2001 ] ] ],
            sets: [ 1 => [] ]
        );

        $result = $this->manager->handle_team_result_update( $fixture, $request, $fixture_repo );

        $this->assertEquals( 'success', $result->status );
        $this->assertArrayHasKey( 10, $result->rubbers );

        // Verify winner_id and loser_id on the fixture (since update_result should have been called)
        $this->assertEquals( 100, $fixture->get_winner_id(), 'Winner ID should be 100' );
        $this->assertEquals( 200, $fixture->get_loser_id(), 'Loser ID should be 200' );

        unset( $GLOBALS['wp_stubs_teams'][100] );
        unset( $GLOBALS['wp_stubs_teams'][200] );
    }

    public function test_handle_team_result_update_sets_date_result_entered(): void {
        $fixture = Fixture_Test_Factory::create(
            id: 123,
            league_id: 456,
            home_team: '100',
            away_team: '200',
            season: '2026',
            date_result_entered: null
        );

        $league = $this->create_league_mock( id: 456, is_championship: false, num_sets: 3, num_sets_to_win: 2, num_rubbers: 1 );

        $this->league_service->method( 'get_league' )->willReturn( $league );

        $fixture_repo = $this->createMock( \Racketmanager\Repositories\League_Repository::class );
        $fixture_repo->method( 'find_by_id' )->willReturn( $league );

        $GLOBALS['wp_stubs_teams'][100] = (object) [ 'club_id' => 10, 'is_withdrawn' => false ];
        $GLOBALS['wp_stubs_teams'][200] = (object) [ 'club_id' => 20, 'is_withdrawn' => false ];

        $rubber_result = Result_Test_Factory::create_rubber_update_result(
            rubber_id: 10,
            home_points: 2.0,
            away_points: 0.0,
            winner_id: 100
        );

        $this->rubber_manager->method( 'handle_rubber_update' )
             ->willReturn( $rubber_result );

        $this->result_service->expects( $this->once() )
             ->method( 'apply_to_fixture' )
             ->willReturnCallback( function ( $f, $r, $c ) {
                 $f->set_result( $r );
                 $f->set_confirmed( $c );
             } );

        $request = Result_Test_Factory::create_team_update_request(
            match_id: 123,
            match_status: 'completed',
            rubber_statuses: [ '1' => 'share' ],
            match_comments: [ 'comments' ],
            rubber_ids: [ 1 => 10 ],
            rubber_types: [ 1 => 'S' ],
            players: [ 1 => [ 'home' => [ 1 => 1001 ], 'away' => [ 1 => 2001 ] ] ],
            sets: [ 1 => [] ]
        );

        $this->assertNull( $fixture->get_date_result_entered() );

        $this->manager->handle_team_result_update( $fixture, $request, $fixture_repo );

        $this->assertNotNull( $fixture->get_date_result_entered() );
        $this->assertStringMatchesFormat( '%d-%d-%d %d:%d:%d', $fixture->get_date_result_entered() );

        unset( $GLOBALS['wp_stubs_teams'][100] );
        unset( $GLOBALS['wp_stubs_teams'][200] );
    }

    public function test_handle_fixture_result_update_sets_date_result_entered(): void {
        $fixture = Fixture_Test_Factory::create(
            id: 123,
            league_id: 456,
            home_team: '100',
            away_team: '200',
            season: '2026',
            date_result_entered: null
        );

        $league = $this->create_league_mock(
            id: 456,
            is_championship: true,
            comp_type: 'tournament',
            num_sets: 3,
            num_sets_to_win: 2,
            num_rubbers: 0
        );

        $this->league_service->method( 'get_league' )->willReturn( $league );

        $fixture_repo = $this->createMock( \Racketmanager\Repositories\League_Repository::class );
        $fixture_repo->method( 'find_by_id' )->willReturn( $league );

        $this->score_validator->method( 'get_error' )->willReturn( false );

        $request = Result_Test_Factory::create_fixture_update_request(
            fixture_id: 123,
            sets: [ 'set_1_home' => 6, 'set_1_away' => 0 ],
            match_status: 'completed',
            confirmed: 'P'
        );

        $this->assertNull( $fixture->get_date_result_entered() );

        $this->manager->handle_fixture_result_update( $fixture, $request );

        $this->assertNotNull( $fixture->get_date_result_entered() );
        $this->assertStringMatchesFormat( '%d-%d-%d %d:%d:%d', $fixture->get_date_result_entered() );
    }

    public function test_handle_team_result_confirmation_updates_fixture(): void {
        $fixture = Fixture_Test_Factory::create(
            id: 123,
            league_id: 456,
            home_team: '100',
            away_team: '200',
            confirmed: null
        );

        $league = $this->create_league_mock( id: 456 );
        $this->league_service->method( 'get_league' )->willReturn( $league );

        $request = Result_Test_Factory::create_team_confirmation_request(
            match_id: 123,
            result_confirm: 'A',
            confirm_comments: 'Looks good',
            result_home: true,
            result_away: false
        );

        $this->result_service->expects( $this->once() )
                             ->method( 'apply_to_fixture' );

        $result = $this->manager->handle_team_result_confirmation( $fixture, $request );

        $this->assertEquals( 'success', $result->status );
        $this->assertEquals( 'A', $fixture->get_confirmed() );
        $comments = maybe_unserialize( $fixture->get_comments() );
        $this->assertArrayHasKey( 'home_confirm', $comments );
        $this->assertEquals( 'Looks good', $comments['home_confirm'] );
    }

    public function test_handle_team_result_update_blocks_unauthorized_user(): void {
        $fixture = Fixture_Test_Factory::create(
            id: 999,
            league_id: 456,
            home_team: '100',
            away_team: '200',
            season: '2026'
        );

        $league = $this->create_league_mock( id: 456 );
        $this->league_service->method( 'get_league' )->willReturn( $league );
        $this->league_repository_repo->method( 'find_by_id' )->willReturn( $league );

        $GLOBALS['wp_stubs_current_user_can'] = false;

        $home_team = $this->getMockBuilder( \Racketmanager\Domain\Team::class )
                          ->disableOriginalConstructor()
                          ->getMock();
        $home_team->method( 'get_club_id' )->willReturn( 10 );
        $away_team = $this->getMockBuilder( \Racketmanager\Domain\Team::class )
                          ->disableOriginalConstructor()
                          ->getMock();
        $away_team->method( 'get_club_id' )->willReturn( 20 );

        $request = Result_Test_Factory::create_team_update_request(
            match_id: 999,
            players: [ 1 => [] ],
            sets: [ 1 => [] ]
        );

        $this->rubber_manager->method( 'handle_rubber_update' )->willReturn( Result_Test_Factory::create_rubber_update_result(
            rubber_id: 10,
            home_points: 2.0,
            away_points: 0.0,
            winner_id: 5,
            status: 1,
            stats: []
        ) );

        $result = $this->manager->handle_team_result_update( $fixture, $request );

        $this->assertTrue( $result->error, 'Should have an error for unauthorized user' );
        $this->assertEquals( 'Result entry not permitted', $result->msg );
    }

    public function test_confirm_result_updates_fixture_and_triggers_updates(): void {
        $fixture = Fixture_Test_Factory::create(
            id: 123,
            league_id: 456,
            home_team: '100',
            away_team: '200',
            home_points: '3',
            away_points: '2',
            season: '2026',
            status: 0,
            confirmed: null,
            custom: []
        );

        $league = $this->create_league_mock( id: 456, is_championship: false, name: 'Test League' );

        $this->league_service->method( 'get_league' )->willReturn( $league );
        $this->league_repository_repo->expects( $this->once() )->method( 'find_by_id' )->with( 456 )->willReturn( $league );

        $this->result_service->expects( $this->once() )
                             ->method( 'apply_to_fixture' )
                             ->with( $this->equalTo( $fixture ), $this->isInstanceOf( \Racketmanager\Domain\Result\Result::class ), $this->equalTo( 'Y' ) );

        $response = $this->manager->confirm_result( $fixture, 'home', 'Direct confirmation' );

        $this->assertEquals( 'Y', $fixture->get_confirmed() );
        $comments = maybe_unserialize( $fixture->get_comments() );
        $this->assertEquals( 'Direct confirmation', $comments['home_confirm'] );
        $this->assertInstanceOf( \Racketmanager\Domain\DTO\Fixture\Fixture_Update_Response::class, $response );
    }

    public function test_handle_player_warnings_returns_correct_data(): void {
        $fixture = Fixture_Test_Factory::create(
            id: 123,
            home_team: '100',
            away_team: '200',
            league_id: 456
        );

        $league = $this->create_league_mock( id: 456 );
        $this->league_repository_repo->method( 'find_by_id' )->willReturn( $league );

        $this->results_checker_repository->expects( $this->once() )
            ->method( 'has_results_check' )
            ->with( 123 )
            ->willReturn( true );

        $player_warning = new stdClass();
        $player_warning->rubber_id = 10;
        $player_warning->team_id = 100;
        $player_warning->player_id = 5;
        $player_warning->description = 'Warning description';

        $this->results_checker_repository->expects( $this->once() )
            ->method( 'find_by_fixture_id' )
            ->with( 123 )
            ->willReturn( [ $player_warning ] );

        $rubber = $this->createMock( \Racketmanager\Domain\Fixture\Rubber::class );
        $rubber->rubber_number = 1;
        $rubber->players = [
            'home' => [
                '1' => (object) [ 'id' => 5 ],
                '2' => (object) [ 'id' => 6 ],
            ],
        ];

        $this->rubber_repository->expects( $this->once() )
            ->method( 'find_by_id' )
            ->with( 10 )
            ->willReturn( $rubber );

        $reflection = new \ReflectionClass( Fixture_Result_Manager::class );
        $method = $reflection->getMethod( 'handle_player_warnings' );

        $result = $method->invoke( $this->manager, $fixture );

        $this->assertEquals( 'warning', $result->status );
        $this->assertStringContainsString( 'Match has player warnings', $result->msg );
        $this->assertArrayHasKey( 'players_1_home_1', $result->warnings );
        $this->assertEquals( 'Warning description', $result->warnings['players_1_home_1'] );
    }

    public function test_run_fixture_checks_loads_players_from_ids(): void {
        $fixture = Fixture_Test_Factory::create(
            id: 123,
            league_id: 456,
            season: '2026'
        );

        $league = $this->create_league_mock( id: 456 );
        $competition = $this->getMockBuilder( \Racketmanager\Domain\Competition\Competition::class )
                            ->onlyMethods( [ 'get_season_by_name' ] )
                            ->disableOriginalConstructor()
                            ->getMock();
        $competition->type = Competition_Type::LEAGUE;
        $competition->is_player_entry = false;
        $league->event->competition = $competition;
        $this->league_repository_repo->method( 'find_by_id' )->willReturn( $league );

        $competition->method( 'get_season_by_name' )->willReturn( [] );

        // Pass rubbers as array with player IDs instead of objects
        $rubbers = [
            [
                'id' => 10,
                'players' => [
                    'home' => [
                        '1' => 5, // Player ID 5
                        '2' => 6, // Player ID 6
                    ],
                    'away' => [],
                ],
            ],
        ];

        $player_dto5 = $this->getMockBuilder( \Racketmanager\Domain\DTO\Club\Club_Player_DTO::class )
                            ->disableOriginalConstructor()
                            ->getMock();
        $player_dto5->id = 5;
        $player_dto5->wtn = [ 'S' => 20.0 ];

        $player_dto6 = $this->getMockBuilder( \Racketmanager\Domain\DTO\Club\Club_Player_DTO::class )
                            ->disableOriginalConstructor()
                            ->getMock();
        $player_dto6->id = 6;
        $player_dto6->wtn = [ 'S' => 21.0 ];

        $reg_service = $this->createMock( \Racketmanager\Services\Registration_Service::class );
        $reg_service->expects( $this->exactly( 2 ) )
                    ->method( 'get_registration' )
                    ->willReturnMap( [
                        [ 5, $player_dto5 ],
                        [ 6, $player_dto6 ],
                    ] );

        $fixture_repo = $this->createMock( \Racketmanager\Repositories\Fixture_Repository::class );
        $player_validator = new Player_Validation_Service( $reg_service, $this->results_checker_repository, $fixture_repo );

        $player_validator->run_fixture_checks( $fixture, $league, $rubbers, [] );
    }

    public function test_update_legs(): void {
        $fixture = Fixture_Test_Factory::create( id: 123 );

        $this->fixture_repository->expects( $this->once() )
                                 ->method( 'save' )
                                 ->with( $fixture );

        $response = $this->manager->update_legs( $fixture, 1, 456 );

        $this->assertEquals( 1, $fixture->get_leg() );
        $this->assertEquals( 456, $fixture->get_linked_fixture() );
        $this->assertTrue( $response->has_outcome( Fixture_Update_Status::LEGS_UPDATED ) );
    }

    public function test_apply_penalty_leg_1(): void {
        $fixture = Fixture_Test_Factory::create(
            id: 123,
            home_team: 10,
            away_team: 20,
            home_points: '8.0',
            away_points: '2.0',
            leg: 1
        );

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
        $fixture = Fixture_Test_Factory::create(
            id: 123,
            home_team: 10,
            away_team: 20,
            home_points: '4.0',
            away_points: '2.0',
            leg: 2,
            linked_fixture: 456
        );

        $linked_fixture = Fixture_Test_Factory::create(
            id: 456,
            home_team: 20,
            away_team: 10,
            home_points: '5.0',
            away_points: '1.0',
            winner_id: 20
        );

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
        $fixture = Fixture_Test_Factory::create(
            id: 123,
            league_id: 1,
            home_points: '2',
            away_points: '1',
            confirmed: 'Y'
        );
        
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

        $fixture = Fixture_Test_Factory::create(
            id: 123,
            league_id: 1,
            home_captain: null
        );

        $league = $this->createStub( League::class );
        $this->league_service->method( 'get_league' )->willReturn( $league );

        $this->permission_service->method( 'is_update_allowed' )->willReturn( (object) [
            'user_team' => 'home',
        ] );

        $request = Result_Test_Factory::create_fixture_update_request(
            fixture_id: 123,
            sets: [],
            match_status: 'played',
            confirmed: 'N'
        );

        $this->score_validator->method( 'get_err_msgs' )->willReturn( [] );

        $this->manager->handle_fixture_result_update( $fixture, $request );

        $this->assertEquals( 123, $fixture->get_home_approver() );
        
        unset( $GLOBALS['wp_stubs_get_current_user_id'] );
    }

    public function test_handle_team_result_confirmation_fails_when_no_option_selected(): void {
        $fixture = Fixture_Test_Factory::create(
            id: 1,
            league_id: 10,
            home_team: '101',
            away_team: '102',
            season: '2024'
        );

        $request = Result_Test_Factory::create_team_confirmation_request(
            match_id: 1,
            result_confirm: null, // Simulate missing selection
            result_home: true
        );

        $response = $this->manager->handle_team_result_confirmation( $fixture, $request );

        $this->assertTrue( $response->error );
        $this->assertContains( 'Either confirm or challenge result', $response->err_msgs );
    }

    public function test_handle_team_result_confirmation_fails_when_challenge_no_comments(): void {
        $fixture = Fixture_Test_Factory::create(
            id: 1,
            league_id: 10,
            home_team: '101',
            away_team: '102',
            season: '2024'
        );

        $request = Result_Test_Factory::create_team_confirmation_request(
            match_id: 1,
            result_confirm: 'C', // Challenge
            confirm_comments: '', // Empty comments
            result_home: true
        );

        $response = $this->manager->handle_team_result_confirmation( $fixture, $request );

        $this->assertTrue( $response->error );
        $this->assertContains( 'You must enter a reason for challenging the result', $response->err_msgs );
    }

    public function test_handle_team_result_confirmation_assigns_away_approver(): void {
        $GLOBALS['wp_stubs_get_current_user_id'] = 456;

        $fixture = Fixture_Test_Factory::create(
            id: 1,
            league_id: 10,
            away_captain: null
        );

        $league = $this->createStub( League::class );
        $league->method( 'get_competition_type' )->willReturn( 'standard' );
        $this->league_service->method( 'get_league' )->willReturn( $league );

        $request = Result_Test_Factory::create_team_confirmation_request(
            match_id: 1,
            result_confirm: 'A',
            result_away: true // Away team confirming
        );

        $this->manager->handle_team_result_confirmation( $fixture, $request );

        $this->assertEquals( 456, $fixture->get_away_approver(), 'Away approver should be set to current user ID' );
        
        unset( $GLOBALS['wp_stubs_get_current_user_id'] );
    }
}
