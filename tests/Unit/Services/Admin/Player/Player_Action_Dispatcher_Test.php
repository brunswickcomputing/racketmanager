<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Admin\Player;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Exceptions\Duplicate_BTM_Exception;
use Racketmanager\Exceptions\Duplicate_Email_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\LTA_Tennis_Number_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Exists_Exception;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Not_Updated_Exception;
use Racketmanager\Exceptions\WTN_Error_Exception;
use Racketmanager\Services\Admin\Player\Player_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Player_Service;
use WP_Error;

require_once __DIR__ . '/../../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Player_Action_Dispatcher_Test extends TestCase {

    private Player_Service $player_service;
    private Action_Guard_Interface $guard;
    private Player_Action_Dispatcher $dispatcher;

    protected function setUp(): void {
        parent::setUp();
        $this->player_service = $this->createMock( Player_Service::class );
        $this->guard          = $this->createMock( Action_Guard_Interface::class );
        $this->dispatcher     = new Player_Action_Dispatcher( $this->player_service, $this->guard );
    }

    public function test_unhandled_action_returns_empty_dto(): void {
        $result = $this->dispatcher->handle( array(), array() );
        self::assertNull( $result->message );
    }

    public function test_update_player_guard_failure(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->willThrowException( new Invalid_Status_Exception( 'Forbidden' ) );

        $this->player_service->expects( self::never() )->method( 'amend_player_details' );

        $result = $this->dispatcher->handle(
            array(),
            array(
                'updatePlayer' => '1',
                'playerId'     => '10',
            )
        );

        self::assertSame( 'Forbidden', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_update_player_missing_player_id(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );
        $this->player_service->expects( self::never() )->method( 'amend_player_details' );

        $result = $this->dispatcher->handle(
            array(),
            array(
                'updatePlayer' => '1',
                'playerId'     => '',
            )
        );

        self::assertSame( 'Player not found', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_update_player_validation_error_returns_errors_and_form_data(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );

        $wp_error = new WP_Error( 'firstname', 'First name required' );
        $this->player_service->expects( self::once() )
            ->method( 'amend_player_details' )
            ->with( 10, self::callback( function( array $data ): bool {
                return 'John' === $data['firstname'] && ! isset( $data['locked'] );
            } ) )
            ->willReturn( $wp_error );

        $result = $this->dispatcher->handle(
            array(),
            array(
                'updatePlayer'  => '1',
                'playerId'      => '10',
                'firstname'     => 'John',
                'surname'       => 'Doe',
                'page_referrer' => 'test-ref',
            )
        );

        self::assertSame( 'Error with player details', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertSame( array( 'firstname' => 'First name required' ), $result->flash_data['errors'] );
        self::assertSame( 'John', $result->flash_data['form_data']['firstname'] );
        self::assertSame( 'test-ref', $result->flash_data['form_data']['page_referrer'] );
        self::assertArrayNotHasKey( 'locked', $result->flash_data['form_data'] );
    }

    public function test_update_player_success(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );

        $player = $this->createMock( Player::class );
        $this->player_service->expects( self::once() )
            ->method( 'amend_player_details' )
            ->with( 10, self::callback( function( array $data ): bool {
                return 'Locked' === ( $data['locked'] ?? null );
            } ) )
            ->willReturn( $player );

        $result = $this->dispatcher->handle(
            array(),
            array(
                'updatePlayer' => '1',
                'playerId'     => '10',
                'firstname'    => 'John',
                'locked'       => 'Locked',
            )
        );

        self::assertSame( 'Player updated', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
    }

    public function test_update_player_not_updated_warning(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );

        $this->player_service->expects( self::once() )
            ->method( 'amend_player_details' )
            ->willThrowException( new Player_Not_Updated_Exception( 'No changes made' ) );

        $result = $this->dispatcher->handle(
            array(),
            array(
                'updatePlayer' => '1',
                'playerId'     => '10',
                'firstname'    => 'John',
            )
        );

        self::assertSame( 'No changes made', $result->message );
        self::assertSame( Admin_Message_Type::WARNING, $result->message_type );
        self::assertSame( 'John', $result->flash_data['form_data']['firstname'] );
    }

    public function test_update_player_duplicate_email_error(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );

        $this->player_service->expects( self::once() )
            ->method( 'amend_player_details' )
            ->willThrowException( new Duplicate_Email_Exception( 'Email in use' ) );

        $result = $this->dispatcher->handle(
            array(),
            array(
                'updatePlayer' => '1',
                'playerId'     => '10',
                'email'        => 'test@example.com',
            )
        );

        self::assertSame( 'Email in use', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertSame( 'test@example.com', $result->flash_data['form_data']['email'] );
    }

    public function test_set_wtn_guard_failure(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->willThrowException( new Invalid_Status_Exception( 'Forbidden' ) );

        $this->player_service->expects( self::never() )->method( 'get_latest_wtn' );

        $result = $this->dispatcher->handle(
            array(),
            array(
                'setWTN'   => '1',
                'playerId' => '10',
            )
        );

        self::assertSame( 'Forbidden', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_set_wtn_missing_player_id(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );
        $this->player_service->expects( self::never() )->method( 'get_latest_wtn' );

        $result = $this->dispatcher->handle(
            array(),
            array(
                'setWTN'   => '1',
                'playerId' => '',
            )
        );

        self::assertSame( 'Player not found', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_set_wtn_success(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );
        $this->player_service->expects( self::once() )
            ->method( 'get_latest_wtn' )
            ->with( 10 );

        $result = $this->dispatcher->handle(
            array(),
            array(
                'setWTN'   => '1',
                'playerId' => '10',
            )
        );

        self::assertSame( 'WTN set', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
    }

    public function test_set_wtn_exception(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );
        $this->player_service->expects( self::once() )
            ->method( 'get_latest_wtn' )
            ->willThrowException( new WTN_Error_Exception( 'WTN service unavailable' ) );

        $result = $this->dispatcher->handle(
            array(),
            array(
                'setWTN'   => '1',
                'playerId' => '10',
            )
        );

        self::assertSame( 'WTN service unavailable', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_add_player_guard_failure(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->willThrowException( new Invalid_Status_Exception( 'Forbidden' ) );

        $this->player_service->expects( self::never() )->method( 'add_new_player' );

        $result = $this->dispatcher->handle(
            array(),
            array(
                'addPlayer' => '1',
            )
        );

        self::assertSame( 'Forbidden', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_add_player_validation_error(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );

        $wp_error = new WP_Error( 'surname', 'Surname required' );
        $this->player_service->expects( self::once() )
            ->method( 'add_new_player' )
            ->willReturn( $wp_error );

        $result = $this->dispatcher->handle(
            array(),
            array(
                'addPlayer' => '1',
                'firstname' => 'Alice',
            )
        );

        self::assertSame( 'Error with player details', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertSame( array( 'surname' => 'Surname required' ), $result->flash_data['errors'] );
        self::assertSame( 'Alice', $result->flash_data['form_data']['firstname'] );
    }

    public function test_add_player_does_not_send_locked_when_checkbox_is_unchecked(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );
        $this->player_service->expects( self::once() )
            ->method( 'add_new_player' )
            ->with( self::callback( static function( array $data ): bool {
                return 'Alice' === $data['firstname'] && ! isset( $data['locked'] );
            } ) )
            ->willReturn( $this->createMock( Player::class ) );

        $result = $this->dispatcher->handle(
            array(),
            array(
                'addPlayer' => '1',
                'firstname' => 'Alice',
                'surname'   => 'Smith',
            )
        );

        self::assertSame( 'Player added', $result->message );
    }

    public function test_add_player_success(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );

        $player = $this->createMock( Player::class );
        $this->player_service->expects( self::once() )
            ->method( 'add_new_player' )
            ->willReturn( $player );

        $result = $this->dispatcher->handle(
            array(),
            array(
                'addPlayer' => '1',
                'firstname' => 'Alice',
                'surname'   => 'Smith',
            )
        );

        self::assertSame( 'Player added', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
    }

    public function test_add_player_exists_exception(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );

        $this->player_service->expects( self::once() )
            ->method( 'add_new_player' )
            ->willThrowException( new Player_Exists_Exception( 'Player already exists' ) );

        $result = $this->dispatcher->handle(
            array(),
            array(
                'addPlayer' => '1',
                'firstname' => 'Alice',
                'surname'   => 'Smith',
            )
        );

        self::assertSame( 'Player already exists', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertSame( 'Alice', $result->flash_data['form_data']['firstname'] );
    }

    public function test_do_player_del_unhandled_action(): void {
        $result = $this->dispatcher->handle(
            array(),
            array(
                'doPlayerDel' => '1',
                'action'      => 'other',
            )
        );

        self::assertNull( $result->message );
    }

    public function test_do_player_del_guard_failure(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->willThrowException( new Invalid_Status_Exception( 'Forbidden' ) );

        $this->player_service->expects( self::never() )->method( 'delete_player' );

        $result = $this->dispatcher->handle(
            array(),
            array(
                'doPlayerDel' => '1',
                'action'      => 'delete',
            )
        );

        self::assertSame( 'Forbidden', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_do_player_del_empty_selection(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );

        $result = $this->dispatcher->handle(
            array(),
            array(
                'doPlayerDel' => '1',
                'action'      => 'delete',
                'player'      => array(),
            )
        );

        self::assertSame( 'No selection made', $result->message );
        self::assertSame( Admin_Message_Type::WARNING, $result->message_type );
    }

    public function test_do_player_del_success(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );

        $this->player_service->expects( self::exactly( 2 ) )
            ->method( 'delete_player' )
            ->willReturnMap( array(
                array( 1, true ),
                array( 2, true ),
            ) );

        $result = $this->dispatcher->handle(
            array(),
            array(
                'doPlayerDel' => '1',
                'action'      => 'delete',
                'player'      => array( '1', '2' ),
            )
        );

        self::assertSame( '1 deleted<br>2 deleted', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
    }

    public function test_do_player_del_mixed_results(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );

        $this->player_service->expects( self::exactly( 2 ) )
            ->method( 'delete_player' )
            ->willReturnCallback( function( int $id ): bool {
                if ( 1 === $id ) {
                    return true;
                }
                throw new Player_Not_Found_Exception( 'Player 2 not found' );
            } );

        $result = $this->dispatcher->handle(
            array(),
            array(
                'doPlayerDel' => '1',
                'action'      => 'delete',
                'player'      => array( '1', '2' ),
            )
        );

        self::assertSame( '1 deleted<br>Player 2 not found', $result->message );
        self::assertSame( Admin_Message_Type::WARNING, $result->message_type );
    }
}
