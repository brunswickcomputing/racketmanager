<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Admin\Index;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Exceptions\Duplicate_Competition_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\Index\Index_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition_Service;

require_once __DIR__ . '/../../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Index_Action_Dispatcher_Test extends TestCase {

    public function test_unhandled_action_does_not_check_guard_or_mutate(): void {
        $service = $this->createMock( Competition_Service::class );
        $guard   = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::never() )->method( 'assert_allowed' );
        $service->expects( self::never() )->method( 'create' );
        $service->expects( self::never() )->method( 'remove' );

        $result = new Index_Action_Dispatcher( $service, $guard )->handle( array(), array() );

        self::assertNull( $result->message );
    }

    public function test_add_guard_failure_does_not_create_competition(): void {
        $service = $this->createMock( Competition_Service::class );
        $guard   = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_add-competition', 'edit_leagues' )
            ->willThrowException( new Invalid_Status_Exception( 'Invalid nonce or capability' ) );
        $service->expects( self::never() )->method( 'create' );

        $result = new Index_Action_Dispatcher( $service, $guard )->handle(
            array(),
            array( 'addCompetition' => 'competition', 'competition_name' => 'Summer Cup' )
        );

        self::assertSame( 'Invalid nonce or capability', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_add_validation_errors_preserve_form_values_without_mutation(): void {
        $service = $this->createMock( Competition_Service::class );
        $guard   = $this->createStub( Action_Guard_Interface::class );
        $service->expects( self::never() )->method( 'create' );

        $result = new Index_Action_Dispatcher( $service, $guard, $this->validator_factory(
            true,
            array( 'competition', 'type' ),
            array( 'A competition name is required', 'Select a competition type' ),
            'Please correct the highlighted fields'
        ) )->handle(
            array(),
            array(
                'addCompetition'  => 'competition',
                'competition_name' => 'Summer Cup',
                'type'             => 'tournament',
                'age_group'        => 'junior',
            )
        );

        self::assertSame( 'Please correct the highlighted fields', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertSame(
            array(
                'errors'           => array(
                    'competition' => 'A competition name is required',
                    'type'        => 'Select a competition type',
                ),
                'competition_name' => 'Summer Cup',
                'type'             => 'tournament',
                'age_group'        => 'junior',
            ),
            $result->flash_data
        );
    }

    public function test_add_duplicate_competition_returns_field_error_and_preserves_values(): void {
        $service = $this->createMock( Competition_Service::class );
        $guard   = $this->createStub( Action_Guard_Interface::class );
        $service->expects( self::once() )
            ->method( 'create' )
            ->with( 'Summer Cup', 'tournament', 'junior' )
            ->willThrowException( new Duplicate_Competition_Exception( 'Competition already exists' ) );

        $result = new Index_Action_Dispatcher( $service, $guard, $this->validator_factory() )->handle(
            array(),
            array(
                'addCompetition'  => 'competition',
                'competition_name' => 'Summer Cup',
                'type'             => 'tournament',
                'age_group'        => 'junior',
            )
        );

        self::assertSame( 'Competition already exists', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertSame( 'Competition already exists', $result->flash_data['errors']['competition'] );
        self::assertSame( 'Summer Cup', $result->flash_data['competition_name'] );
    }

    public function test_add_success_creates_competition_after_guard_and_validation(): void {
        $service = $this->createMock( Competition_Service::class );
        $guard   = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_add-competition', 'edit_leagues' );
        $service->expects( self::once() )
            ->method( 'create' )
            ->with( 'Summer Cup', 'tournament', 'junior' )
            ->willReturn( $this->createStub( Competition::class ) );

        $result = new Index_Action_Dispatcher( $service, $guard, $this->validator_factory() )->handle(
            array(),
            array(
                'addCompetition'  => 'competition',
                'competition_name' => 'Summer Cup',
                'type'             => 'tournament',
                'age_group'        => 'junior',
            )
        );

        self::assertSame( 'Competition added', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
    }

    public function test_bulk_delete_guard_failure_does_not_remove_competitions(): void {
        $service = $this->createMock( Competition_Service::class );
        $guard   = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_competitions-bulk', 'del_leagues' )
            ->willThrowException( new Invalid_Status_Exception( 'Deletion is not authorized' ) );
        $service->expects( self::never() )->method( 'remove' );

        $result = new Index_Action_Dispatcher( $service, $guard )->handle(
            array(),
            array( 'doCompDel' => '1', 'action' => 'delete', 'competition' => array( 5, 6 ) )
        );

        self::assertSame( 'Deletion is not authorized', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_bulk_delete_empty_selection_is_guarded_and_reports_warning(): void {
        $service = $this->createMock( Competition_Service::class );
        $guard   = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_competitions-bulk', 'del_leagues' );
        $service->expects( self::never() )->method( 'remove' );

        $result = new Index_Action_Dispatcher( $service, $guard )->handle(
            array(),
            array( 'doCompDel' => '1', 'action' => 'delete', 'competition' => array() )
        );

        self::assertSame( 'No deletions flagged', $result->message );
        self::assertSame( Admin_Message_Type::WARNING, $result->message_type );
    }

    public function test_bulk_delete_default_bulk_action_removes_nothing(): void {
        $service = $this->createMock( Competition_Service::class );
        $guard   = $this->createStub( Action_Guard_Interface::class );
        $service->expects( self::never() )->method( 'remove' );

        $result = new Index_Action_Dispatcher( $service, $guard )->handle(
            array(),
            array( 'doCompDel' => '1', 'action' => '-1', 'competition' => array( 5, 6 ) )
        );

        self::assertSame( 'No deletions flagged', $result->message );
        self::assertSame( Admin_Message_Type::WARNING, $result->message_type );
    }

    public function test_add_unexpected_exception_is_not_swallowed(): void {
        $service = $this->createMock( Competition_Service::class );
        $guard   = $this->createStub( Action_Guard_Interface::class );
        $service->method( 'create' )->willThrowException( new \RuntimeException( 'db down' ) );

        $this->expectException( \RuntimeException::class );
        new Index_Action_Dispatcher( $service, $guard, $this->validator_factory() )->handle(
            array(),
            array(
                'addCompetition'   => 'competition',
                'competition_name' => 'Summer Cup',
                'type'             => 'tournament',
                'age_group'        => 'junior',
            )
        );
    }

    public function test_bulk_delete_removes_positive_ids_and_ignores_invalid_ids(): void {
        $service = $this->createMock( Competition_Service::class );
        $guard   = $this->createStub( Action_Guard_Interface::class );
        $removed = array();
        $service->expects( self::exactly( 2 ) )
            ->method( 'remove' )
            ->willReturnCallback(
                static function ( int $id ) use ( &$removed ): void {
                    $removed[] = $id;
                }
            );

        $result = new Index_Action_Dispatcher( $service, $guard )->handle(
            array(),
            array(
                'doCompDel'   => '1',
                'action'      => 'delete',
                'competition' => array( '5' => '5', 'bad' => 'not-an-id', '0' => '0', '6' => '6' ),
            )
        );

        self::assertSame( array( 5, 6 ), $removed );
        self::assertSame( 'Deleted 5<br>Deleted 6', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
    }

    private function validator_factory(
        bool $error = false,
        array $fields = array(),
        array $messages = array(),
        string $message = '',
    ): callable {
        return static fn (): object => new class( $error, $fields, $messages, $message ) {
            public bool $error;
            public array $err_flds;
            public array $err_msgs;
            public string $msg;

            public function __construct( bool $error, array $fields, array $messages, string $message ) {
                $this->error    = $error;
                $this->err_flds = $fields;
                $this->err_msgs = $messages;
                $this->msg      = $message;
            }

            public function competition( ?string $name, bool $exists = true ): self {
                return $this;
            }

            public function competition_type( ?string $type ): self {
                return $this;
            }

            public function age_group( ?string $age_group ): self {
                return $this;
            }
        };
    }
}
