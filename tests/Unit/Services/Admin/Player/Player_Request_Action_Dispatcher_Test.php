<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Admin\Player;

use Exception;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Registration_Not_Found_Exception;
use Racketmanager\Services\Admin\Player\Player_Request_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Registration_Service;

require_once __DIR__ . '/../../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Player_Request_Action_Dispatcher_Test extends TestCase {

    private Registration_Service $registration_service;
    private Action_Guard_Interface $guard;
    private Player_Request_Action_Dispatcher $dispatcher;

    protected function setUp(): void {
        parent::setUp();
        $this->registration_service = $this->createMock( Registration_Service::class );
        $this->guard                = $this->createMock( Action_Guard_Interface::class );
        $this->dispatcher           = new Player_Request_Action_Dispatcher( $this->registration_service, $this->guard );
    }

    public function test_unhandled_action_returns_empty_dto(): void {
        $result = $this->dispatcher->handle( array() );
        self::assertNull( $result->message );
    }

    public function test_do_player_request_guard_failure(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->willThrowException( new Invalid_Status_Exception( 'Forbidden' ) );

        $this->registration_service->expects( self::never() )->method( 'approve_registration' );
        $this->registration_service->expects( self::never() )->method( 'remove_registration' );

        $result = $this->dispatcher->handle(
            array(
                'doPlayerRequest' => '1',
                'action'          => 'approve',
            )
        );

        self::assertSame( 'Forbidden', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_do_player_request_missing_or_invalid_action(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );

        $result = $this->dispatcher->handle(
            array(
                'doPlayerRequest' => '1',
                'action'          => 'invalid',
            )
        );

        self::assertSame( 'No action selected', $result->message );
        self::assertSame( Admin_Message_Type::WARNING, $result->message_type );
    }

    public function test_do_player_request_empty_selection(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );

        $result = $this->dispatcher->handle(
            array(
                'doPlayerRequest' => '1',
                'action'          => 'approve',
                'playerRequest'   => array(),
            )
        );

        self::assertSame( 'No selection made', $result->message );
        self::assertSame( Admin_Message_Type::WARNING, $result->message_type );
    }

    public function test_do_player_request_approve_success(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );

        $this->registration_service->expects( self::exactly( 2 ) )
            ->method( 'approve_registration' )
            ->willReturnCallback( function( int $id, int $user_id ): bool {
                return true;
            } );

        $result = $this->dispatcher->handle(
            array(
                'doPlayerRequest' => '1',
                'action'          => 'approve',
                'playerRequest'   => array( '5', '6' ),
            )
        );

        self::assertSame( '5 - Player has been approved<br>6 - Player has been approved', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
    }

    public function test_do_player_request_delete_success(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );

        $this->registration_service->expects( self::exactly( 2 ) )
            ->method( 'remove_registration' )
            ->willReturnCallback( function( int $id, int $user_id ): bool {
                return true;
            } );

        $result = $this->dispatcher->handle(
            array(
                'doPlayerRequest' => '1',
                'action'          => 'delete',
                'playerRequest'   => array( '5', '6' ),
            )
        );

        self::assertSame( '5 - Player has been removed from club<br>6 - Player has been removed from club', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
    }

    public function test_do_player_request_not_found_exception(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );

        $this->registration_service->expects( self::once() )
            ->method( 'approve_registration' )
            ->willThrowException( new Registration_Not_Found_Exception( 'Registration not found' ) );

        $result = $this->dispatcher->handle(
            array(
                'doPlayerRequest' => '1',
                'action'          => 'approve',
                'playerRequest'   => array( '5' ),
            )
        );

        self::assertSame( 'Registration not found', $result->message );
        self::assertSame( Admin_Message_Type::WARNING, $result->message_type );
    }
}
