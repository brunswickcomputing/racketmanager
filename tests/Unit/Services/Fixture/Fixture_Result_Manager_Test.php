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
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Settings_Service;

#[AllowMockObjectsWithoutExpectations]
class Fixture_Result_Manager_Test extends TestCase {

    private $fixture_repository;
    private $results_report_repository;
    private $league_repository;
    private $league_service;
    private $result_reporting_service;
    private $permission_service;
    private $score_validator;
    private $result_service;
    private $player_validator;
    private $settings_service;
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

        $this->service_provider = $this->createStub( Service_Provider::class );
        $this->service_provider->method( 'get_league_service' )->willReturn( $this->league_service );
        $this->service_provider->method( 'get_result_reporting_service' )->willReturn( $this->result_reporting_service );
        $this->service_provider->method( 'get_permission_service' )->willReturn( $this->permission_service );
        $this->service_provider->method( 'get_score_validator' )->willReturn( $this->score_validator );
        $this->service_provider->method( 'get_result_service' )->willReturn( $this->result_service );
        $this->service_provider->method( 'get_player_validator' )->willReturn( $this->player_validator );
        $this->service_provider->method( 'get_settings_service' )->willReturn( $this->settings_service );

        $this->manager = new Fixture_Result_Manager(
            $this->service_provider,
            $this->repository_provider
        );
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
        
        $this->results_report_repository->expects( $this->once() )
            ->method( 'delete_by_fixture_id' )
            ->with( 100 );
            
        $this->result_reporting_service->expects( $this->once() )
            ->method( 'report_result' )
            ->with( $fixture )
            ->willReturn( (object) [ 'some' => 'data' ] );
            
        $this->results_report_repository->expects( $this->once() )
            ->method( 'save' );

        $this->manager->confirm_result( $fixture, 'admin' );
    }
}
