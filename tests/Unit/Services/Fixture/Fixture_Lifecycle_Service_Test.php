<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Fixture;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Repositories\Repository_Provider;
use Racketmanager\Services\Fixture\Fixture_Lifecycle_Service;
use Racketmanager\Services\Fixture\Fixture_Maintenance_Service;
use Racketmanager\Services\Notification\Notification_Service;

#[AllowMockObjectsWithoutExpectations]
class Fixture_Lifecycle_Service_Test extends TestCase {

    private $repository_provider;
    private $fixture_repository;
    private $rubber_repository;
    private $maintenance_service;
    private $notification_service;
    private Fixture_Lifecycle_Service $service;

    public function test_reschedule_fixture_updates_date_and_notifies(): void {
        $fixture = $this->createMock( Fixture::class );
        $fixture->method( 'get_id' )->willReturn( 123 );
        $new_date      = '2026-05-20';
        $original_date = '2026-05-10';

        $this->maintenance_service->expects( $this->once() )->method( 'update_fixture_date' )->with( 123, $new_date, $original_date )->willReturn( true );

        $fixture->expects( $this->once() )->method( 'set_date' )->with( $new_date );
        $fixture->expects( $this->once() )->method( 'set_date_original' )->with( $original_date );

        $this->rubber_repository->expects( $this->once() )->method( 'update_date_by_fixture_id' )->with( 123, $new_date );

        $this->notification_service->expects( $this->once() )->method( 'send_date_change_notification' )->with( $fixture );

        $result = $this->service->reschedule_fixture( $fixture, $new_date, $original_date );

        $this->assertTrue( $result );
    }

    public function test_advance_teams_sends_notification(): void {
        $fixture = $this->createMock( Fixture::class );
        $fixture->method( 'get_home_team' )->willReturn( '100' );
        $fixture->method( 'get_away_team' )->willReturn( '200' );

        $this->notification_service->expects( $this->once() )->method( 'send_next_fixture_notification' )->with( $fixture );

        $this->service->advance_teams( $fixture );
    }

    public function test_advance_teams_does_not_send_notification_if_teams_missing(): void {
        $fixture = $this->createMock( Fixture::class );
        $fixture->method( 'get_home_team' )->willReturn( '-1' );
        $fixture->method( 'get_away_team' )->willReturn( '200' );

        $this->notification_service->expects( $this->never() )->method( 'send_next_fixture_notification' );

        $this->service->advance_teams( $fixture );
    }

    public function test_handle_withdrawal_notifies_opponent(): void {
        $fixture = $this->createMock( Fixture::class );
        $team_id = 100;

        $this->notification_service->expects( $this->once() )->method( 'notify_team_withdrawal' )->with( $fixture, $team_id );

        $this->service->handle_withdrawal( $fixture, $team_id );
    }

    protected function setUp(): void {
        parent::setUp();

        $this->fixture_repository   = $this->createMock( Fixture_Repository_Interface::class );
        $this->rubber_repository    = $this->createMock( Rubber_Repository_Interface::class );
        $this->repository_provider  = $this->createMock( Repository_Provider::class );
        $this->maintenance_service  = $this->createMock( Fixture_Maintenance_Service::class );
        $this->notification_service = $this->createMock( Notification_Service::class );

        $this->repository_provider->method( 'get_fixture_repository' )->willReturn( $this->fixture_repository );
        $this->repository_provider->method( 'get_rubber_repository' )->willReturn( $this->rubber_repository );

        $this->service = new Fixture_Lifecycle_Service( $this->repository_provider, $this->maintenance_service, $this->notification_service );
    }
}
