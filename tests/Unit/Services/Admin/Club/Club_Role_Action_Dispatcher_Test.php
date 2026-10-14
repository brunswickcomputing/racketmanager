<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Admin\Club;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\Club\Club_Role_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;

require_once __DIR__ . '/../../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Club_Role_Action_Dispatcher_Test extends TestCase {

    public function test_unhandled_action_returns_empty_dto(): void {
        $club_service = $this->createMock( Club_Service::class );
        $guard        = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::never() )->method( 'assert_allowed' );

        $dispatcher = new Club_Role_Action_Dispatcher( $club_service, $guard );
        $result     = $dispatcher->handle( array(), array() );

        self::assertNull( $result->message );
        self::assertNull( $result->message_type );
    }

    public function test_add_role_guard_failure(): void {
        $club_service = $this->createMock( Club_Service::class );
        $guard        = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_add-club-role', 'edit_teams' )
            ->willThrowException( new Invalid_Status_Exception( 'Invalid token' ) );

        $dispatcher = new Club_Role_Action_Dispatcher( $club_service, $guard );
        $result     = $dispatcher->handle( array(), array( 'addRole' => '1' ) );

        self::assertSame( 'Invalid token', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_add_role_success(): void {
        $club_service = $this->createMock( Club_Service::class );
        $guard        = $this->createStub( Action_Guard_Interface::class );

        $club_service->expects( self::once() )
            ->method( 'set_club_role' )
            ->with( 5, 2, 10 )
            ->willReturn( true );

        $dispatcher = new Club_Role_Action_Dispatcher( $club_service, $guard );
        $result     = $dispatcher->handle(
            array(),
            array(
                'addRole' => '1',
                'club_id' => 5,
                'role_id' => 2,
                'user_id' => 10,
            )
        );

        self::assertSame( 'Role added', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
    }

    public function test_delete_roles_success(): void {
        $club_service = $this->createMock( Club_Service::class );
        $guard        = $this->createStub( Action_Guard_Interface::class );

        $club_service->expects( self::exactly( 2 ) )
            ->method( 'remove_club_role' );

        $dispatcher = new Club_Role_Action_Dispatcher( $club_service, $guard );
        $result     = $dispatcher->handle(
            array(),
            array(
                'delRole' => '1',
                'action'  => 'delete',
                'role'    => array( 1, 2 ),
            )
        );

        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
    }
}
