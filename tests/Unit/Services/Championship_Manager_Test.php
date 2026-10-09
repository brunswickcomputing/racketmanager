<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Services\Championship_Manager;
use Racketmanager\Services\Notification\Notification_Service;
use Racketmanager\Services\Result_Service;
use stdClass;

#[AllowMockObjectsWithoutExpectations]
class Championship_Manager_Test extends TestCase {

    private Fixture_Repository_Interface $fixture_repository;
    private Notification_Service $notification_service;
    private Result_Service $result_service;
    private Championship_Manager $manager;

    protected function setUp(): void {
        parent::setUp();

        $this->fixture_repository   = $this->createMock( Fixture_Repository_Interface::class );
        $this->notification_service = $this->createMock( Notification_Service::class );
        $this->result_service       = $this->createMock( Result_Service::class );

        $this->manager = new Championship_Manager(
            $this->result_service,
            $this->fixture_repository,
            $this->notification_service
        );
    }

    public function test_set_teams_updates_teams_saves_and_notifies(): void {
        $primary_raw = new stdClass();
        $primary_raw->id = 10;
        $primary_raw->home_team = '100';
        $primary_raw->away_team = null;
        $primary_raw->linked_match = 20;
        $primary_fixture = new Fixture( $primary_raw );

        $linked_raw = new stdClass();
        $linked_raw->id = 20;
        $linked_raw->home_team = '100';
        $linked_raw->away_team = null;
        $linked_fixture = new Fixture( $linked_raw );

        $this->fixture_repository->method( 'find_by_id' )
            ->willReturnMap( [
                [ 10, $primary_fixture ],
                [ 20, $linked_fixture ],
            ] );

        $this->fixture_repository->expects( $this->exactly( 2 ) )
            ->method( 'save' );

        $this->notification_service->expects( $this->exactly( 2 ) )
            ->method( 'send_next_fixture_notification' );

        $this->manager->set_teams( (object) [ 'id' => 10 ], '101', '202' );

        $this->assertSame( '101', $primary_fixture->get_home_team() );
        $this->assertSame( '202', $primary_fixture->get_away_team() );
        $this->assertSame( '101', $linked_fixture->get_home_team() );
        $this->assertSame( '202', $linked_fixture->get_away_team() );
    }

    public function test_set_teams_when_fixture_not_found_does_nothing(): void {
        $this->fixture_repository->method( 'find_by_id' )
            ->with( 999 )
            ->willReturn( null );

        $this->fixture_repository->expects( $this->never() )
            ->method( 'save' );
        $this->notification_service->expects( $this->never() )
            ->method( 'send_next_fixture_notification' );

        $this->manager->set_teams( 999, '100', '200' );
    }

    public function test_set_teams_with_non_numeric_teams_does_not_notify(): void {
        $primary_raw = new stdClass();
        $primary_raw->id = 10;
        $primary_raw->home_team = null;
        $primary_raw->away_team = null;
        $primary_fixture = new Fixture( $primary_raw );

        $this->fixture_repository->method( 'find_by_id' )
            ->with( 10 )
            ->willReturn( $primary_fixture );

        $this->fixture_repository->expects( $this->once() )
            ->method( 'save' )
            ->with( $primary_fixture );

        $this->notification_service->expects( $this->never() )
            ->method( 'send_next_fixture_notification' );

        $this->manager->set_teams( 10, 'Winner Semi 1', 'Winner Semi 2' );

        $this->assertSame( 'Winner Semi 1', $primary_fixture->get_home_team() );
        $this->assertSame( 'Winner Semi 2', $primary_fixture->get_away_team() );
    }
}
