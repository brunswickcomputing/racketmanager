<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Integration\Services;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Fixture\Rubber;
use Racketmanager\Repositories\Fixture_Repository;
use Racketmanager\Repositories\Rubber_Repository;
use Racketmanager\Repositories\Repository_Provider;
use Racketmanager\Services\Fixture_Service;
use Racketmanager\Services\Fixture\Service_Provider as Fixture_Service_Provider;
use Racketmanager\Services\Notification\Notification_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\League_Service;

class Fixture_Service_Integration_Test extends TestCase {
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
        parent::setUp();
        
        $this->fixture_repository = $this->createMock( Fixture_Repository::class );
        $this->rubber_repository = $this->createMock( Rubber_Repository::class );
        $this->notification_service = $this->createMock( Notification_Service::class );
        $this->registration_service = $this->createMock( Registration_Service::class );
        $this->competition_service = $this->createMock( Competition_Service::class );
        $this->team_service = $this->createMock( Team_Service::class );
        
        $this->repository_provider = new Repository_Provider(
            fixture_repository: $this->fixture_repository,
            rubber_repository: $this->rubber_repository,
            league_repository: $this->createMock( \Racketmanager\Repositories\League_Repository::class ),
            team_repository: $this->createMock( \Racketmanager\Repositories\Team_Repository::class ),
            club_repository: $this->createMock( \Racketmanager\Repositories\Club_Repository::class )
        );

        $this->service_provider = new Fixture_Service_Provider(
            notification_service: $this->notification_service,
            registration_service: $this->registration_service
        );
        $this->service_provider->set_competition_service( $this->competition_service );
        $this->service_provider->set_team_service( $this->team_service );

        $this->service = new Fixture_Service( $this->repository_provider, $this->service_provider );
    }

    public function test_update_fixture_location(): void {
        $fixture = new Fixture( (object)[ 'id' => 1, 'location' => 'Old' ] );
        
        $this->fixture_repository->expects( $this->once() )
            ->method( 'save' )
            ->with( $this->callback( function( $f ) {
                return $f->get_location() === 'New Location';
            } ) );

        $this->service->update_fixture_location( $fixture, 'New Location' );
        $this->assertEquals( 'New Location', $fixture->get_location() );
    }

    public function test_set_fixture_date_and_sync_rubbers(): void {
        $fixture = new Fixture( (object)[ 'id' => 10, 'date' => '2026-01-01 00:00:00' ] );
        
        $rubber1 = $this->createMock( Rubber::class );
        $rubber1->method( 'get_match_id' )->willReturn( 10 );
        $rubber2 = $this->createMock( Rubber::class );
        $rubber2->method( 'get_match_id' )->willReturn( 10 );
        
        $this->rubber_repository->expects( $this->once() )
            ->method( 'find_by_fixture_id' )
            ->with( 10 )
            ->willReturn( [ $rubber1, $rubber2 ] );

        $this->fixture_repository->expects( $this->once() )
            ->method( 'save' )
            ->with( $this->callback( function( $f ) {
                return $f->get_date() === '2026-03-31 19:00';
            } ) );

        $rubber1->expects( $this->once() )
            ->method( 'set_date' )
            ->with( '2026-03-31 19:00' );
        $rubber2->expects( $this->once() )
            ->method( 'set_date' )
            ->with( '2026-03-31 19:00' );

        $this->rubber_repository->expects( $this->exactly( 2 ) )
            ->method( 'save' );

        $this->service->set_fixture_date( $fixture, '2026-03-31', null, '19:00' );
        
        $this->assertEquals( '2026-03-31 19:00', $fixture->get_date() );
    }

    public function test_update_fixture_date_with_original_date_triggers_notification(): void {
        $fixture = new Fixture( (object)[ 'id' => 20, 'date' => '2026-01-01 00:00:00' ] );
        $new_date = '2026-02-01 10:00:00';
        $original_date = '2026-01-01 00:00:00';

        $this->rubber_repository->method( 'find_by_fixture_id' )->willReturn( [] );

        $this->notification_service->expects( $this->once() )
            ->method( 'send_date_change_notification' )
            ->with( $fixture );

        $this->service->update_fixture_date( $fixture, $new_date, $original_date );
        
        $this->assertEquals( $new_date, $fixture->get_date() );
        $this->assertEquals( $original_date, $fixture->get_date_original() );
    }
}
