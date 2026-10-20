<?php
/**
 * Results checker action dispatcher tests
 *
 * @package RacketManager
 * @subpackage Unit/Services/Admin/Result
 */

declare( strict_types=1 );

namespace Tests\Unit\Services\Admin\Result;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Domain\DTOs\Results_Checker_Data;
use Racketmanager\Domain\Result\Results_Checker;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Repositories\Interfaces\Results_Checker_Repository_Interface;
use Racketmanager\Services\Admin\Result\Results_Checker_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Result\Results_Checker_Manager;

require_once __DIR__ . '/../../../../wp-stubs.php';

final class Results_Checker_Action_Dispatcher_Test extends TestCase {

    private $manager;
    private $repository;
    private $guard;
    private Results_Checker_Action_Dispatcher $dispatcher;

    protected function setUp(): void {
        $this->manager    = $this->createMock( Results_Checker_Manager::class );
        $this->repository = $this->createMock( Results_Checker_Repository_Interface::class );
        $this->guard      = $this->createMock( Action_Guard_Interface::class );
        $this->dispatcher = new Results_Checker_Action_Dispatcher( $this->manager, $this->repository, $this->guard );
    }

    public function test_no_post_action_returns_empty_dto(): void {
        $this->guard->expects( $this->never() )->method( 'assert_allowed' );
        $this->repository->expects( $this->never() )->method( 'find_by_id' );
        $this->manager->expects( $this->never() )->method( 'approve' );

        $result = $this->dispatcher->handle( array() );
        $this->assertNull( $result->message );
        $this->assertNull( $result->message_type );
        $this->assertNull( $result->tab_override );
    }

    public function test_guard_failure_returns_error_dto_with_zero_mutations(): void {
        $this->guard->expects( $this->once() )
            ->method( 'assert_allowed' )
            ->willThrowException( new Invalid_Status_Exception( 'Unauthorized' ) );

        $this->repository->expects( $this->never() )->method( 'find_by_id' );
        $this->repository->expects( $this->never() )->method( 'delete' );
        $this->manager->expects( $this->never() )->method( 'approve' );
        $this->manager->expects( $this->never() )->method( 'handle' );

        $result = $this->dispatcher->handle( array( 'doResultsChecker' => '1' ) );

        $this->assertSame( Admin_Message_Type::ERROR, $result->message_type );
        $this->assertSame( 'Unauthorized', $result->message );
        $this->assertSame( 'resultschecker', $result->tab_override );
    }

    public function test_empty_selection_returns_error_and_does_not_mutate(): void {
        $this->guard->expects( $this->once() )->method( 'assert_allowed' );

        $this->repository->expects( $this->never() )->method( 'find_by_id' );
        $this->repository->expects( $this->never() )->method( 'delete' );
        $this->manager->expects( $this->never() )->method( 'approve' );
        $this->manager->expects( $this->never() )->method( 'handle' );

        $result = $this->dispatcher->handle(
            array(
                'doResultsChecker' => '1',
                'action'           => 'approve',
                'resultsChecker'   => array(),
            )
        );

        $this->assertSame( Admin_Message_Type::ERROR, $result->message_type );
        $this->assertSame( 'No actions flagged', $result->message );
        $this->assertSame( 'resultschecker', $result->tab_override );
    }

    public function test_unknown_or_negative_action_returns_error_and_does_not_mutate(): void {
        $this->guard->expects( $this->once() )->method( 'assert_allowed' );

        $this->repository->expects( $this->never() )->method( 'find_by_id' );
        $this->repository->expects( $this->never() )->method( 'delete' );
        $this->manager->expects( $this->never() )->method( 'approve' );
        $this->manager->expects( $this->never() )->method( 'handle' );

        $result = $this->dispatcher->handle(
            array(
                'doResultsChecker' => '1',
                'action'           => '-1',
                'resultsChecker'   => array( 1 ),
            )
        );

        $this->assertSame( Admin_Message_Type::ERROR, $result->message_type );
        $this->assertSame( 'No actions flagged', $result->message );
        $this->assertSame( 'resultschecker', $result->tab_override );
    }

    public function test_approve_success_with_multiple_ids(): void {
        $this->guard->expects( $this->once() )->method( 'assert_allowed' );

        $checker1 = new Results_Checker( new Results_Checker_Data( id: 1 ) );
        $checker2 = new Results_Checker( new Results_Checker_Data( id: 2 ) );

        $this->repository->expects( $this->exactly( 2 ) )
            ->method( 'find_by_id' )
            ->willReturnCallback(
                fn( int $id ) => match ( $id ) {
                    1 => $checker1,
                    2 => $checker2,
                    default => null,
                }
            );

        $this->manager->expects( $this->exactly( 2 ) )
            ->method( 'approve' );

        $result = $this->dispatcher->handle(
            array(
                'doResultsChecker' => '1',
                'action'           => 'approve',
                'resultsChecker'   => array( 1, 2 ),
            )
        );

        $this->assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        $this->assertSame( 'Result check(s) approved', $result->message );
        $this->assertSame( 'resultschecker', $result->tab_override );
    }

    public function test_handle_success(): void {
        $this->guard->expects( $this->once() )->method( 'assert_allowed' );

        $checker = new Results_Checker( new Results_Checker_Data( id: 5 ) );

        $this->repository->expects( $this->once() )
            ->method( 'find_by_id' )
            ->with( 5 )
            ->willReturn( $checker );

        $this->manager->expects( $this->once() )
            ->method( 'handle' )
            ->with( $checker );

        $result = $this->dispatcher->handle(
            array(
                'doResultsChecker' => '1',
                'action'           => 'handle',
                'resultsChecker'   => array( 5 ),
            )
        );

        $this->assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        $this->assertSame( 'Result check(s) handled', $result->message );
        $this->assertSame( 'resultschecker', $result->tab_override );
    }

    public function test_delete_success(): void {
        $this->guard->expects( $this->once() )->method( 'assert_allowed' );
        $this->manager->expects( $this->never() )->method( 'approve' );
        $this->manager->expects( $this->never() )->method( 'handle' );

        $checker = new Results_Checker( new Results_Checker_Data( id: 7 ) );

        $this->repository->expects( $this->once() )
            ->method( 'find_by_id' )
            ->with( 7 )
            ->willReturn( $checker );

        $this->repository->expects( $this->once() )
            ->method( 'delete' )
            ->with( 7 )
            ->willReturn( true );

        $result = $this->dispatcher->handle(
            array(
                'doResultsChecker' => '1',
                'action'           => 'delete',
                'resultsChecker'   => array( 7 ),
            )
        );

        $this->assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        $this->assertSame( 'Result check(s) deleted', $result->message );
        $this->assertSame( 'resultschecker', $result->tab_override );
    }

    public function test_all_records_missing_returns_error(): void {
        $this->guard->expects( $this->once() )->method( 'assert_allowed' );
        $this->manager->expects( $this->never() )->method( 'approve' );
        $this->manager->expects( $this->never() )->method( 'handle' );
        $this->repository->expects( $this->never() )->method( 'delete' );

        $this->repository->expects( $this->exactly( 2 ) )
            ->method( 'find_by_id' )
            ->willReturn( null );

        $result = $this->dispatcher->handle(
            array(
                'doResultsChecker' => '1',
                'action'           => 'approve',
                'resultsChecker'   => array( 10, 11 ),
            )
        );

        $this->assertSame( Admin_Message_Type::ERROR, $result->message_type );
        $this->assertSame( 'Result check not found', $result->message );
        $this->assertSame( 'resultschecker', $result->tab_override );
    }

    public function test_partial_results_mix_of_found_and_missing_returns_warning(): void {
        $this->guard->expects( $this->once() )->method( 'assert_allowed' );

        $checker = new Results_Checker( new Results_Checker_Data( id: 20 ) );

        $this->repository->expects( $this->exactly( 2 ) )
            ->method( 'find_by_id' )
            ->willReturnCallback(
                fn( int $id ) => match ( $id ) {
                    20 => $checker,
                    default => null,
                }
            );

        $this->manager->expects( $this->once() )
            ->method( 'approve' )
            ->with( $checker );

        $result = $this->dispatcher->handle(
            array(
                'doResultsChecker' => '1',
                'action'           => 'approve',
                'resultsChecker'   => array( 20, 21 ),
            )
        );

        $this->assertSame( Admin_Message_Type::WARNING, $result->message_type );
        $this->assertStringContainsString( 'Result check(s) approved', $result->message ?? '' );
        $this->assertStringContainsString( 'Result check not found', $result->message ?? '' );
        $this->assertSame( 'resultschecker', $result->tab_override );
    }
}
