<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Fixture;

use Racketmanager\RacketManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Fixture\Fixture_Result_Update_Request;
use Racketmanager\Domain\DTO\Fixture\Team_Result_Update_Request;
use Racketmanager\Domain\Result\Result;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Results_Report_Repository_Interface;
use Racketmanager\Services\Fixture\Fixture_Maintenance_Service;
use Racketmanager\Repositories\Repository_Provider;
use Racketmanager\Services\Fixture\Fixture_Result_Manager;
use Racketmanager\Services\Fixture\Fixture_Permission_Service;
use Racketmanager\Services\Fixture\Service_Provider;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Result\Result_Reporting_Service;
use Racketmanager\Services\Result_Service;
use Racketmanager\Services\Validator\Score_Validation_Service;
use Racketmanager\Services\Validator\Player_Validation_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Domain\Enums\Fixture\Fixture_Update_Status;
use Racketmanager\Domain\Competition\Stage;
use Racketmanager\Services\Competition\Knockout_Progression_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Settings_Service;

#[AllowMockObjectsWithoutExpectations]
class Fixture_Result_Manager_Test extends TestCase {

    private $fixture_repository;
    private $results_report_repository;
    private $fixture_maintenance_service;
    private $league_repository;
    private $league_service;
    private $result_reporting_service;
    private $permission_service;
    private $score_validator;
    private $result_service;
    private $player_validator;
    private $settings_service;
    private $progression_service;
    private $service_provider;
    private $repository_provider;
    private Fixture_Result_Manager $manager;

    protected function setUp(): void {
        $racketmanager = $this->createMock( RacketManager::class );
        $container = new Simple_Container();
        $container->set( 'competition_service', $this->createMock( Competition_Service::class ) );
        $container->set( 'club_service', $this->createMock( \Racketmanager\Services\Club_Service::class ) );
        $container->set( 'player_service', $this->createMock( \Racketmanager\Services\Player_Service::class ) );
        $container->set( 'registration_service', $this->createMock( \Racketmanager\Services\Registration_Service::class ) );
        
        $racketmanager->container = $container;
        $GLOBALS['racketmanager'] = $racketmanager;
        
        $this->fixture_repository = $this->createMock( Fixture_Repository_Interface::class );
        $this->results_report_repository = $this->createMock( Results_Report_Repository_Interface::class );
        $this->league_repository = $this->createMock( League_Repository_Interface::class );
        
        $this->repository_provider = $this->createStub( Repository_Provider::class );
        $this->repository_provider->method( 'get_fixture_repository' )->willReturn( $this->fixture_repository );
        $this->repository_provider->method( 'get_results_report_repository' )->willReturn( $this->results_report_repository );
        $this->repository_provider->method( 'get_league_repository' )->willReturn( $this->league_repository );

        $this->league_service = $this->createMock( League_Service::class );
        $this->result_reporting_service = $this->createMock( Result_Reporting_Service::class );
        $this->permission_service = $this->createMock( Fixture_Permission_Service::class );
        $this->score_validator = $this->createMock( Score_Validation_Service::class );
        $this->result_service = $this->createMock( Result_Service::class );
        $this->player_validator = $this->createMock( Player_Validation_Service::class );
        $this->settings_service = $this->createMock( Settings_Service::class );
        $this->progression_service = $this->createMock( Knockout_Progression_Service::class );

        $this->service_provider = $this->createStub( Service_Provider::class );
        $this->fixture_maintenance_service = $this->createMock( Fixture_Maintenance_Service::class );
        $this->service_provider->method( 'get_fixture_maintenance_service' )->willReturn( $this->fixture_maintenance_service );
        $this->service_provider->method( 'get_league_service' )->willReturn( $this->league_service );
        $this->service_provider->method( 'get_result_reporting_service' )->willReturn( $this->result_reporting_service );
        $this->service_provider->method( 'get_permission_service' )->willReturn( $this->permission_service );
        $this->service_provider->method( 'get_score_validator' )->willReturn( $this->score_validator );
        $this->service_provider->method( 'get_result_service' )->willReturn( $this->result_service );
        $this->service_provider->method( 'get_player_validator' )->willReturn( $this->player_validator );
        $this->service_provider->method( 'get_settings_service' )->willReturn( $this->settings_service );
        $this->service_provider->method( 'get_progression_service' )->willReturn( $this->progression_service );

        $this->manager = new Fixture_Result_Manager(
            $this->service_provider,
            $this->repository_provider
        );
    }

    public function test_update_result_triggers_league_standings_update(): void {
        $fixture = $this->createMock( Fixture::class );
        $fixture->method( 'get_league_id' )->willReturn( 1 );
        $fixture->method( 'get_season' )->willReturn( '2026' );
        
        $result = $this->createMock( Result::class );
        
        $league = $this->createMock( League::class );
        $league->method( 'get_id' )->willReturn( 1 );
        $league->method( 'get_name' )->willReturn( 'Test League' );
        $league->method( 'get_event_id' )->willReturn( 10 );
        $league->is_championship = false;
        
        $this->league_repository->method( 'find_by_id' )->with( 1 )->willReturn( $league );
        
        $this->result_service->expects( $this->once() )
            ->method( 'apply_to_fixture' )
            ->with( $fixture, $result, 'Y' );
            
        $league->expects( $this->once() )
            ->method( 'update_standings' )
            ->with( '2026' );
            
        $response = $this->manager->update_result( $fixture, $result, 'Y' );
        
        $this->assertContains( Fixture_Update_Status::SAVED, $response->outcomes );
        $this->assertContains( Fixture_Update_Status::TABLE_UPDATED, $response->outcomes );
    }

    public function test_update_result_triggers_championship_progression(): void {
        $fixture = $this->createMock( Fixture::class );
        $fixture->method( 'get_league_id' )->willReturn( 1 );
        
        $result = $this->createMock( Result::class );
        
        $league = $this->createMock( League::class );
        $league->method( 'get_id' )->willReturn( 1 );
        $league->method( 'get_name' )->willReturn( 'Test Championship' );
        $league->method( 'get_event_id' )->willReturn( 10 );
        $league->is_championship = true;
        
        $this->league_repository->method( 'find_by_id' )->with( 1 )->willReturn( $league );
        
        $this->result_service->expects( $this->once() )
            ->method( 'apply_to_fixture' )
            ->with( $fixture, $result, 'Y' );
            
        $this->progression_service->expects( $this->once() )
            ->method( 'progress_winner' )
            ->with( $this->isInstanceOf( Stage::class ), $fixture, $league );
            
        $this->progression_service->expects( $this->once() )
            ->method( 'handle_consolation' )
            ->with( $this->isInstanceOf( Stage::class ), $fixture, $league );
            
        $response = $this->manager->update_result( $fixture, $result, 'Y' );
        
        $this->assertContains( Fixture_Update_Status::SAVED, $response->outcomes );
        $this->assertContains( Fixture_Update_Status::PROGRESSED, $response->outcomes );
    }

    public function test_handle_fixture_result_update_assigns_home_captain(): void {
        $fixture = $this->createMock( Fixture::class );
        $fixture->method( 'get_league_id' )->willReturn( 1 );
        $fixture->method( 'get_home_captain' )->willReturn( null );
        
        $league = $this->createStub( League::class );
        $this->league_service->method( 'get_league' )->willReturn( $league );
        
        $this->permission_service->method( 'is_update_allowed' )->willReturn( (object) [
            'user_can_update' => true,
            'user_team' => 'home',
            'user_type' => 'admin'
        ] );

        // The global function get_current_user_id from wp-stubs.php returns 1
        $fixture->expects( $this->once() )
            ->method( 'set_home_captain' )
            ->with( '1' );

        $request = new Fixture_Result_Update_Request(
            fixture_id: 1,
            sets: [],
            match_status: 'played',
            confirmed: 'N'
        );

        $this->score_validator->method( 'get_err_msgs' )->willReturn( [] );

        $this->manager->handle_fixture_result_update( $fixture, $request );
    }

    public function test_confirm_result_manages_results_report(): void {
        $fixture = $this->createMock( Fixture::class );
        $fixture->method( 'get_id' )->willReturn( 100 );
        $fixture->method( 'get_confirmed' )->willReturn( 'Y' );
        
        $this->fixture_maintenance_service->expects( $this->once() )
            ->method( 'delete_result_report' )
            ->with( 100 );
            
        $this->result_reporting_service->expects( $this->once() )
            ->method( 'report_result' )
            ->with( $fixture )
            ->willReturn( (object) [ 'some' => 'data' ] );
            
        $this->fixture_maintenance_service->expects( $this->once() )
            ->method( 'save_result_report' )
            ->with( 100, (object) [ 'some' => 'data' ] );

        $this->manager->confirm_result( $fixture, 'admin' );
    }
}
