<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Club\Club_Role;
use Racketmanager\Infrastructure\Wordpress\Options\Option_Service;
use Racketmanager\Repositories\Club_Repository;
use Racketmanager\Repositories\Interfaces\Club_Role_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Registration_Repository_Interface;
use Racketmanager\Repositories\Player_Repository;
use Racketmanager\Repositories\Team_Repository;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Player_Service;

class Club_Service_Test extends TestCase {
    private Club_Service $club_service;
    private Club_Role_Repository_Interface $club_role_repository;

    protected function setUp(): void {
        parent::setUp();

        $this->club_role_repository = $this->createStub( Club_Role_Repository_Interface::class );
        $this->club_service         = $this->make_club_service( $this->club_role_repository );
    }

    public function test_can_user_update_players_returns_false_if_not_logged_in(): void {
        // This relies on is_user_logged_in() which needs to be stubbed if it doesn't exist.
        $this->assertFalse( $this->club_service->can_user_update_players( 1 ) );
    }

    public function test_reassign_role_user_clears_stale_loaded_user_and_updates_user_id(): void {
        $club_role_repository = $this->createMock( Club_Role_Repository_Interface::class );
        $club_service         = $this->make_club_service( $club_role_repository );
        $role = new Club_Role();
        $role->set_id( 10 );
        $role->set_user_id( 20 );
        $role->set_user( (object) array( 'id' => 20, 'name' => 'Previous User' ) );

        $club_role_repository
            ->expects( $this->once() )
            ->method( 'find' )
            ->with( 10 )
            ->willReturn( $role );

        $club_role_repository
            ->expects( $this->once() )
            ->method( 'save' )
            ->with( $role );

        $result = $club_service->reassign_role_user( 10, 30 );

        $this->assertSame( $role, $result );
        $this->assertSame( 30, $result->user_id );
        $this->assertSame( 30, $result->get_user_id() );
        $this->assertNull( $result->user );
        $this->assertNull( $result->get_user() );
    }

    public function test_reassign_role_user_returns_null_when_role_not_found(): void {
        $club_role_repository = $this->createMock( Club_Role_Repository_Interface::class );
        $club_service         = $this->make_club_service( $club_role_repository );

        $club_role_repository
            ->expects( $this->once() )
            ->method( 'find' )
            ->with( 999 )
            ->willReturn( null );

        $club_role_repository
            ->expects( $this->never() )
            ->method( 'save' );

        $result = $club_service->reassign_role_user( 999, 30 );

        $this->assertNull( $result );
    }

    private function make_club_service( Club_Role_Repository_Interface $club_role_repository ): Club_Service {
        return new Club_Service(
            $this->createStub( Club_Repository::class ),
            $this->createStub( Registration_Repository_Interface::class ),
            $club_role_repository,
            $this->createStub( Player_Repository::class ),
            $this->createStub( Team_Repository::class ),
            $this->createStub( Player_Service::class ),
            $this->createStub( Option_Service::class )
        );
    }
}
