<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Fixture;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Results_Checker;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Results_Checker_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Results_Report_Repository_Interface;
use Racketmanager\Domain\Results_Report;
use Racketmanager\Repositories\Repository_Provider;
use Racketmanager\Services\Fixture\Fixture_Maintenance_Service;
use Racketmanager\Services\Fixture\Fixture_Result_Manager;
use Racketmanager\Services\Fixture\Service_Provider;
use Racketmanager\Services\Notification\Notification_Service;
use Racketmanager\Services\Settings_Service;
use Racketmanager\Domain\DTO\Fixture\Fixture_Update_Response;
use Racketmanager\Domain\Enums\Fixture\Fixture_Update_Status;

#[AllowMockObjectsWithoutExpectations]
class Fixture_Maintenance_Service_Test extends TestCase {

    private $fixture_repository;
    private $league_repository;
    private $club_repository;
    private $team_repository;
    private $results_checker_repository;
    private $results_report_repository;
    private $notification_service;
    private $settings_service;
    private $fixture_result_manager;
    private $service_provider;
    private $repository_provider;
    private Fixture_Maintenance_Service $service;

    protected function setUp(): void {
        $this->fixture_repository = $this->createMock( Fixture_Repository_Interface::class );
        $this->league_repository = $this->createMock( League_Repository_Interface::class );
        $this->club_repository = $this->createStub( Club_Repository_Interface::class );
        $this->team_repository = $this->createStub( Team_Repository_Interface::class );
        $this->results_checker_repository = $this->createMock( Results_Checker_Repository_Interface::class );
        $this->results_report_repository = $this->createMock( Results_Report_Repository_Interface::class );
        $this->notification_service = $this->createMock( Notification_Service::class );
        $this->settings_service = $this->createStub( Settings_Service::class );
        $this->fixture_result_manager = $this->createStub( Fixture_Result_Manager::class );

        $this->service_provider = $this->createStub( Service_Provider::class );
        $this->service_provider->method( 'get_notification_service' )->willReturn( $this->notification_service );
        $this->service_provider->method( 'get_settings_service' )->willReturn( $this->settings_service );

        $this->repository_provider = $this->createStub( Repository_Provider::class );
        $this->repository_provider->method( 'get_fixture_repository' )->willReturn( $this->fixture_repository );
        $this->repository_provider->method( 'get_league_repository' )->willReturn( $this->league_repository );
        $this->repository_provider->method( 'get_club_repository' )->willReturn( $this->club_repository );
        $this->repository_provider->method( 'get_team_repository' )->willReturn( $this->team_repository );
        $this->repository_provider->method( 'get_results_checker_repository' )->willReturn( $this->results_checker_repository );
        $this->repository_provider->method( 'get_results_report_repository' )->willReturn( $this->results_report_repository );

        $this->service = new Fixture_Maintenance_Service(
            $this->service_provider,
            $this->repository_provider,
            $this->fixture_result_manager
        );
    }

    public function test_delete_result_report_calls_repository(): void {
        $this->results_report_repository->expects( $this->once() )
            ->method( 'delete_by_fixture_id' )
            ->with( 321 );
        $this->service->delete_result_report( 321 );
    }

    public function test_save_result_report_calls_repository(): void {
        $data = (object) [ 'k' => 'v' ];
        $this->results_report_repository->expects( $this->once() )
            ->method( 'save' )
            ->with( $this->callback( function ( $report ) use ( $data ) {
                return $report instanceof Results_Report && $report->match_id === 654 && wp_json_encode( $data ) === $report->result_object;
            } ) );
        $this->service->save_result_report( 654, $data );
    }

    public function test_chase_match_result_sends_notification(): void {
        $fixture = $this->createStub( Fixture::class );
        $fixture->method( 'get_league_id' )->willReturn( 1 );

        $league = $this->createStub( League::class );
        $event = $this->createStub( Event::class );
        $event->competition = (object) [
            'type' => 'league'
        ];
        $league->event = $event;

        $this->league_repository->method( 'find_by_id' )->willReturn( $league );
        $this->settings_service->method( 'get_option' )->willReturn( 'admin@test.com' );

        $this->notification_service->expects( $this->once() )
            ->method( 'send_chase_result_notification' )
            ->with( $fixture, $this->callback( function ( $args ) {
                return $args['from_email'] === 'admin@test.com';
            } ) );

        $result = $this->service->chase_match_result( $fixture, '2 days' );
        $this->assertTrue( $result );
    }

    public function test_chase_match_approval_sends_notification(): void {
        $fixture = $this->createStub( Fixture::class );
        $fixture->method( 'get_league_id' )->willReturn( 1 );

        $league = $this->createStub( League::class );
        $event = $this->createStub( Event::class );
        $event->competition = (object) [
            'type' => 'league'
        ];
        $league->event = $event;

        $this->league_repository->method( 'find_by_id' )->willReturn( $league );
        $this->settings_service->method( 'get_option' )->willReturn( 'admin@test.com' );

        $this->notification_service->expects( $this->once() )
            ->method( 'send_chase_approval_notification' )
            ->with( $fixture, $this->callback( function ( $args ) {
                return $args['from_email'] === 'admin@test.com';
            } ) );

        $result = $this->service->chase_match_approval( $fixture, '2 days' );
        $this->assertTrue( $result );
    }

    public function test_complete_result_confirms_result(): void {
        $fixture = $this->createStub( Fixture::class );
        $fixture->method( 'get_league_id' )->willReturn( 1 );
        $fixture->method( 'get_home_points' )->willReturn( '5.0' );
        $fixture->method( 'get_away_points' )->willReturn( '3.0' );

        $league = $this->createStub( League::class );
        $event = $this->createStub( Event::class );
        $event->competition = (object) [
            'type' => 'league'
        ];
        $league->event = $event;

        $this->league_repository->method( 'find_by_id' )->willReturn( $league );
        $this->settings_service->method( 'get_option' )->willReturn( 'admin@test.com' );

        $response = new Fixture_Update_Response( [ Fixture_Update_Status::TABLE_UPDATED ] );
        $this->fixture_result_manager->method( 'confirm_result' )->willReturn( $response );

        $result = $this->service->complete_result( $fixture, 48 );
        $this->assertEquals( 1, $result );
    }

    public function test_check_result_timeout_saves_to_checker_on_timeout(): void {
        $fixture = $this->createStub( Fixture::class );
        $fixture->method( 'get_league_id' )->willReturn( 1 );
        $fixture->method( 'get_date' )->willReturn( '2023-01-01 10:00:00' );
        $fixture->method( 'get_date_result_entered' )->willReturn( '2023-01-02 11:00:00' ); // 25 hours later

        $league = $this->createStub( League::class );
        $event = $this->createStub( Event::class );
        $event->competition = (object) [
            'type' => 'league',
            'rules' => [ 'resultTimeout' => 24 ]
        ];
        $league->event = $event;

        $this->league_repository->method( 'find_by_id' )->willReturn( $league );
        $this->settings_service->method( 'get_option' )->willReturn( 24 );

        $this->results_checker_repository->expects( $this->once() )
            ->method( 'save' )
            ->with( $this->isInstanceOf( Results_Checker::class ) );

        $this->service->check_result_timeout( $fixture );
    }

    public function test_delete_fixture_calls_repository(): void {
        $this->fixture_repository->expects( $this->once() )
            ->method( 'delete' )
            ->with( 123 )
            ->willReturn( true );
        $this->assertTrue( $this->service->delete_fixture( 123 ) );
    }

    public function test_update_fixture_status_calls_repository(): void {
        $this->fixture_repository->expects( $this->once() )
            ->method( 'update_status' )
            ->with( 123, 5 )
            ->willReturn( true );
        $this->assertTrue( $this->service->update_fixture_status( 123, 5 ) );
    }

    public function test_update_fixture_teams_calls_repository(): void {
        $this->fixture_repository->expects( $this->once() )
            ->method( 'update_teams' )
            ->with( 123, 'Team A', 'Team B' )
            ->willReturn( true );
        $this->assertTrue( $this->service->update_fixture_teams( 123, 'Team A', 'Team B' ) );
    }

    public function test_update_fixture_date_calls_repository(): void {
        $this->fixture_repository->expects( $this->once() )
            ->method( 'update_date' )
            ->with( 123, '2023-01-01', '2022-12-31' )
            ->willReturn( true );
        $this->assertTrue( $this->service->update_fixture_date( 123, '2023-01-01', '2022-12-31' ) );
    }
}
