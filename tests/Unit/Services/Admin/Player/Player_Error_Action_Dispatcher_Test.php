<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Admin\Player;

use Exception;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\Player\Player_Error_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Player_Service;

require_once __DIR__ . '/../../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Player_Error_Action_Dispatcher_Test extends TestCase {

    private Player_Service $player_service;
    private Action_Guard_Interface $guard;
    private Player_Error_Action_Dispatcher $dispatcher;

    protected function setUp(): void {
        parent::setUp();
        $this->player_service = $this->createMock( Player_Service::class );
        $this->guard          = $this->createMock( Action_Guard_Interface::class );
        $this->dispatcher     = new Player_Error_Action_Dispatcher( $this->player_service, $this->guard );
    }

    public function test_unhandled_action_returns_empty_dto(): void {
        $result = $this->dispatcher->handle( array() );
        self::assertNull( $result->message );
    }

    public function test_do_player_error_bulk_action_not_delete(): void {
        $result = $this->dispatcher->handle(
            array(
                'doPlayerErrorBulk' => '1',
                'action'            => 'other',
            )
        );
        self::assertNull( $result->message );
    }

    public function test_do_player_error_bulk_guard_failure(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->willThrowException( new Invalid_Status_Exception( 'Forbidden' ) );

        $this->player_service->expects( self::never() )->method( 'remove_player_error' );

        $result = $this->dispatcher->handle(
            array(
                'doPlayerErrorBulk' => '1',
                'action'            => 'delete',
            )
        );

        self::assertSame( 'Forbidden', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_do_player_error_bulk_empty_selection(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );

        $result = $this->dispatcher->handle(
            array(
                'doPlayerErrorBulk' => '1',
                'action'            => 'delete',
                'playerError'       => array(),
            )
        );

        self::assertSame( 'No selection made', $result->message );
        self::assertSame( Admin_Message_Type::WARNING, $result->message_type );
    }

    public function test_do_player_error_bulk_success(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );

        $this->player_service->expects( self::exactly( 2 ) )
            ->method( 'remove_player_error' )
            ->willReturnCallback( function( int $id ): bool {
                return true;
            } );

        $result = $this->dispatcher->handle(
            array(
                'doPlayerErrorBulk' => '1',
                'action'            => 'delete',
                'playerError'       => array( '10', '20' ),
            )
        );

        self::assertSame( '10 - Player error has been removed<br>20 - Player error has been removed', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
    }

    public function test_do_player_error_bulk_exception(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );

        $this->player_service->expects( self::once() )
            ->method( 'remove_player_error' )
            ->willThrowException( new Exception( 'Error removing error' ) );

        $result = $this->dispatcher->handle(
            array(
                'doPlayerErrorBulk' => '1',
                'action'            => 'delete',
                'playerError'       => array( '10' ),
            )
        );

        self::assertSame( 'Error removing error', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
    }
}
