<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Admin\League;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Competition_Not_Updated_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\League\Season_Delete_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition_Service;

require_once __DIR__ . '/../../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Season_Delete_Action_Dispatcher_Test extends TestCase {

    public function test_mismatched_competition_ids_do_not_delete(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->expects( self::never() )->method( 'delete_seasons' );

        $result = ( new Season_Delete_Action_Dispatcher( $competition_service, $this->createStub( Action_Guard_Interface::class ) ) )->handle(
            array( 'competition_id' => '3' ),
            array( 'doActionSeason' => '1', 'competition_id' => '9', 'action' => 'delete', 'del_season' => array( 2026 ) )
        );

        self::assertSame( 'Passed values do not match', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_nonce_failure_returns_error(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->expects( self::never() )->method( 'delete_seasons' );

        $guard = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'seasons-bulk', 'del_matches' )
            ->willThrowException( new Invalid_Status_Exception( 'Security token invalid' ) );

        $result = ( new Season_Delete_Action_Dispatcher( $competition_service, $guard ) )->handle(
            array( 'competition_id' => '3' ),
            array( 'doActionSeason' => '1', 'competition_id' => '3', 'action' => 'delete', 'del_season' => array( 2026 ) )
        );

        self::assertSame( 'Security token invalid', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_successful_delete(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->expects( self::once() )->method( 'delete_seasons' )->with( 3, array( 2026, 2025 ) );

        $result = ( new Season_Delete_Action_Dispatcher( $competition_service, $this->createStub( Action_Guard_Interface::class ) ) )->handle(
            array( 'competition_id' => '3' ),
            array(
                'doActionSeason' => 'Apply',
                'competition_id' => '3',
                'action'         => 'delete',
                'del_season'     => array( '2026', '2025' ),
            )
        );

        self::assertSame( 'Season(s) deleted', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
    }

    public function test_no_seasons_deleted_is_warning(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->expects( self::once() )->method( 'delete_seasons' )->willThrowException( new Competition_Not_Updated_Exception( 'No seasons deleted' ) );

        $result = ( new Season_Delete_Action_Dispatcher( $competition_service, $this->createStub( Action_Guard_Interface::class ) ) )->handle(
            array( 'competition_id' => '3' ),
            array( 'doActionSeason' => '1', 'competition_id' => '3', 'action' => 'delete', 'del_season' => array( 2026 ) )
        );

        self::assertSame( 'No seasons deleted', $result->message );
        self::assertSame( Admin_Message_Type::WARNING, $result->message_type );
    }

    public function test_competition_not_found_is_error(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->expects( self::once() )->method( 'delete_seasons' )->willThrowException( new Competition_Not_Found_Exception( 'Missing' ) );

        $result = ( new Season_Delete_Action_Dispatcher( $competition_service, $this->createStub( Action_Guard_Interface::class ) ) )->handle(
            array( 'competition_id' => '3' ),
            array( 'doActionSeason' => '1', 'competition_id' => '3', 'action' => 'delete', 'del_season' => array( 2026 ) )
        );

        self::assertSame( 'Missing', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_fixture_deletion_failure_is_an_error_dto(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->expects( self::once() )->method( 'delete_seasons' )
            ->with( 3, array( 2026 ) )->willThrowException( new \InvalidArgumentException( 'Invalid season' ) );

        $result = ( new Season_Delete_Action_Dispatcher( $competition_service, $this->createStub( Action_Guard_Interface::class ) ) )->handle(
            array( 'competition_id' => '3' ),
            array( 'doActionSeason' => '1', 'competition_id' => '3', 'action' => 'delete', 'del_season' => array( 2026 ) )
        );

        self::assertSame( 'Invalid season', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }
}
