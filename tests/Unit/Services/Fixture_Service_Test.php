<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Fixture\Rubber;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Repository_Provider;
use Racketmanager\Services\Fixture_Service;
use Racketmanager\Services\Fixture\Service_Provider;
use Racketmanager\Services\Notification\Notification_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Fixture\Fixture_Permission_Service;
use Racketmanager\Services\Fixture\Fixture_Detail_Service;
use Racketmanager\Services\Fixture\Fixture_Link_Service;

#[AllowMockObjectsWithoutExpectations]
class Fixture_Service_Test extends TestCase {

    private $fixture_repository;
    private $rubber_repository;
    private $notification_service;
    private $registration_service;
    private $competition_service;
    private $team_service;
    private $repository_provider;
    private $service_provider;
    private Fixture_Service $service;

    protected function setUp(): void {
        $this->fixture_repository = $this->createMock( Fixture_Repository_Interface::class );
        $this->rubber_repository = $this->createMock( Rubber_Repository_Interface::class );
        $this->notification_service = $this->createMock( Notification_Service::class );
        $this->registration_service = $this->createMock( Registration_Service::class );
        $this->competition_service = $this->createMock( Competition_Service::class );
        $this->team_service = $this->createMock( Team_Service::class );

        $this->repository_provider = $this->createMock( Repository_Provider::class );
        $this->repository_provider->method( 'get_fixture_repository' )->willReturn( $this->fixture_repository );
        $this->repository_provider->method( 'get_rubber_repository' )->willReturn( $this->rubber_repository );
        $this->repository_provider->method( 'get_league_repository' )->willReturn( $this->createMock( League_Repository_Interface::class ) );
        $this->repository_provider->method( 'get_team_repository' )->willReturn( $this->createMock( Team_Repository_Interface::class ) );
        $this->repository_provider->method( 'get_club_repository' )->willReturn( $this->createMock( Club_Repository_Interface::class ) );

        $permission_service = new Fixture_Permission_Service( $this->repository_provider, $this->registration_service );
        $detail_service = new Fixture_Detail_Service(
            $this->repository_provider,
            $this->competition_service,
            $this->team_service,
            $permission_service,
            $this->createMock( Fixture_Link_Service::class )
        );

        $this->service = new Fixture_Service(
            $this->repository_provider,
            $this->service_provider_mock( $permission_service, $detail_service ),
            $permission_service,
            $detail_service
        );
    }

    private function service_provider_mock( $permission_service, $detail_service ) {
        $service_provider = $this->createMock( Service_Provider::class );
        $service_provider->method( 'get_notification_service' )->willReturn( $this->notification_service );
        $service_provider->method( 'get_registration_service' )->willReturn( $this->registration_service );
        $service_provider->method( 'get_competition_service' )->willReturn( $this->competition_service );
        $service_provider->method( 'get_team_service' )->willReturn( $this->team_service );

        $service_provider->method( 'get_fixture_permission_service' )->willReturn( $permission_service );
        $service_provider->method( 'get_fixture_detail_service' )->willReturn( $detail_service );

        return $service_provider;
    }

    public function test_update_fixture_location_updates_and_saves(): void {
        $fixture = $this->createMock( Fixture::class );
        $location = 'New Location';

        $fixture->expects( $this->once() )
            ->method( 'set_location' )
            ->with( $location );

        $this->fixture_repository->expects( $this->once() )
            ->method( 'save' )
            ->with( $fixture );

        $this->service->update_fixture_location( $fixture, $location );
    }

    public function test_update_fixture_date_updates_fixture_and_rubbers(): void {
        $fixture_id = 123;
        $new_date = '2026-04-01 19:00:00';
        $fixture = $this->createMock( Fixture::class );
        $fixture->method( 'get_id' )->willReturn( $fixture_id );

        $rubber1 = $this->createMock( Rubber::class );
        $rubber2 = $this->createMock( Rubber::class );
        $rubbers = [ $rubber1, $rubber2 ];

        $fixture->expects( $this->once() )
            ->method( 'set_date' )
            ->with( $new_date );

        $this->fixture_repository->expects( $this->once() )
            ->method( 'save' )
            ->with( $fixture );

        $this->rubber_repository->expects( $this->once() )
            ->method( 'find_by_fixture_id' )
            ->with( $fixture_id )
            ->willReturn( $rubbers );

        $rubber1->expects( $this->once() )
            ->method( 'set_date' )
            ->with( $new_date );
        $rubber2->expects( $this->once() )
            ->method( 'set_date' )
            ->with( $new_date );

        $this->rubber_repository->expects( $this->exactly( 2 ) )
            ->method( 'save' )
            ->with( $this->isInstanceOf( Rubber::class ) );

        $this->service->update_fixture_date( $fixture, $new_date );
    }

    public function test_update_fixture_date_sets_original_date_and_sends_notification(): void {
        $fixture_id = 456;
        $new_date = '2026-05-01 18:30:00';
        $original_date = '2026-05-01 10:00:00';
        
        $fixture = $this->createMock( Fixture::class );
        $fixture->method( 'get_id' )->willReturn( $fixture_id );
        // First call to get_date_original returns null, then it returns the value
        $fixture->method( 'get_date_original' )->willReturnOnConsecutiveCalls( null, $original_date );

        $fixture->expects( $this->once() )
            ->method( 'set_date_original' )
            ->with( $original_date );

        $this->rubber_repository->method( 'find_by_fixture_id' )->willReturn( [] );

        $this->notification_service->expects( $this->once() )
            ->method( 'send_date_change_notification' )
            ->with( $fixture );

        $this->service->update_fixture_date( $fixture, $new_date, $original_date );
    }

    public function test_set_fixture_date_calculates_correct_date_and_time(): void {
        $fixture = $this->createMock( Fixture::class );
        $start_date = '2026-06-01'; // Monday
        $match_day = 'Wednesday';   // +2 days
        $match_time = '19:30';
        
        // Mock set_date and other methods since set_fixture_date calls update_fixture_date
        $fixture->method( 'get_id' )->willReturn( 789 );
        $this->rubber_repository->method( 'find_by_fixture_id' )->willReturn( [] );

        // Wed June 3rd 2026 19:30:00
        $expected_date = '2026-06-03 19:30';

        $fixture->expects( $this->once() )
            ->method( 'set_date' )
            ->with( $expected_date );

        $this->service->set_fixture_date( $fixture, $start_date, $match_day, $match_time );
    }

    public function test_set_fixture_date_handles_long_start_date_string(): void {
        $fixture = $this->createMock( Fixture::class );
        $start_date = '2026-06-01T00:00:00Z'; // Longer than 10 chars
        $match_day = null;
        $match_time = '10:00';
        
        $fixture->method( 'get_id' )->willReturn( 111 );
        $this->rubber_repository->method( 'find_by_fixture_id' )->willReturn( [] );

        $expected_date = '2026-06-01 10:00';

        $fixture->expects( $this->once() )
            ->method( 'set_date' )
            ->with( $expected_date );

        $this->service->set_fixture_date( $fixture, $start_date, $match_day, $match_time );
    }
}
