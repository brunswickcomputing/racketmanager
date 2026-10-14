<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Admin\Season;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Invalid_Argument_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\Season\Season_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Season_Service;
use WP_Error;

require_once __DIR__ . '/../../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Season_Action_Dispatcher_Test extends TestCase {

    public function test_unhandled_action_returns_empty_dto(): void {
        $season_service = $this->createMock( Season_Service::class );
        $guard          = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::never() )->method( 'assert_allowed' );

        $dispatcher = new Season_Action_Dispatcher( $season_service, $guard );
        $result     = $dispatcher->handle( array(), array() );

        self::assertNull( $result->message );
    }

    public function test_add_season_guard_failure(): void {
        $season_service = $this->createMock( Season_Service::class );
        $guard          = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_add-season', 'edit_seasons' )
            ->willThrowException( new Invalid_Status_Exception( 'Security token invalid' ) );

        $season_service->expects( self::never() )->method( 'create_season' );

        $dispatcher = new Season_Action_Dispatcher( $season_service, $guard );
        $result     = $dispatcher->handle(
            array(),
            array(
                'addSeason'  => 'season',
                'seasonName' => '2025',
            )
        );

        self::assertSame( 'Security token invalid', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_add_season_success(): void {
        $season_service = $this->createMock( Season_Service::class );
        $guard          = $this->createStub( Action_Guard_Interface::class );

        $season_service->expects( self::once() )
            ->method( 'create_season' )
            ->with( '2025' )
            ->willReturn( true );

        $dispatcher = new Season_Action_Dispatcher( $season_service, $guard );
        $result     = $dispatcher->handle(
            array(),
            array(
                'addSeason'  => 'season',
                'seasonName' => '2025',
            )
        );

        self::assertSame( 'Season added', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
    }

    public function test_add_season_wp_error(): void {
        $season_service = $this->createMock( Season_Service::class );
        $guard          = $this->createStub( Action_Guard_Interface::class );

        $wp_error = new WP_Error( 'season_exists', 'Season already exists' );
        $season_service->expects( self::once() )
            ->method( 'create_season' )
            ->with( '2025' )
            ->willReturn( $wp_error );

        $dispatcher = new Season_Action_Dispatcher( $season_service, $guard );
        $result     = $dispatcher->handle(
            array(),
            array(
                'addSeason'  => 'season',
                'seasonName' => '2025',
            )
        );

        self::assertSame( 'Season already exists', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertSame(
            array(
                'season_name' => '2025',
                'errors'      => array( 'seasonName' => 'Season already exists' ),
            ),
            $result->flash_data
        );
    }

    public function test_add_season_invalid_argument_exception(): void {
        $season_service = $this->createMock( Season_Service::class );
        $guard          = $this->createStub( Action_Guard_Interface::class );

        $season_service->expects( self::once() )
            ->method( 'create_season' )
            ->with( '2025' )
            ->willThrowException( new Invalid_Argument_Exception( 'Invalid season name' ) );

        $dispatcher = new Season_Action_Dispatcher( $season_service, $guard );
        $result     = $dispatcher->handle(
            array(),
            array(
                'addSeason'  => 'season',
                'seasonName' => '2025',
            )
        );

        self::assertSame( 'Invalid season name', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_delete_seasons_guard_failure(): void {
        $season_service = $this->createMock( Season_Service::class );
        $guard          = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_seasons-bulk', 'edit_seasons' )
            ->willThrowException( new Invalid_Status_Exception( 'Nonce check failed' ) );

        $season_service->expects( self::never() )->method( 'delete_season' );

        $dispatcher = new Season_Action_Dispatcher( $season_service, $guard );
        $result     = $dispatcher->handle(
            array(),
            array(
                'doSeasonDel' => '1',
                'action'      => 'delete',
                'season'      => array( '2024', '2025' ),
            )
        );

        self::assertSame( 'Nonce check failed', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_delete_seasons_non_delete_action_returns_empty_dto(): void {
        $season_service = $this->createMock( Season_Service::class );
        $guard          = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::never() )->method( 'assert_allowed' );

        $dispatcher = new Season_Action_Dispatcher( $season_service, $guard );
        $result     = $dispatcher->handle(
            array(),
            array(
                'doSeasonDel' => '1',
                'action'      => 'other',
            )
        );

        self::assertNull( $result->message );
    }

    public function test_delete_seasons_success(): void {
        $season_service = $this->createMock( Season_Service::class );
        $guard          = $this->createStub( Action_Guard_Interface::class );

        $season_service->expects( self::exactly( 2 ) )
            ->method( 'delete_season' )
            ->willReturnMap(
                array(
                    array( 2024, true ),
                    array( 2025, true ),
                )
            );

        $dispatcher = new Season_Action_Dispatcher( $season_service, $guard );
        $result     = $dispatcher->handle(
            array(),
            array(
                'doSeasonDel' => '1',
                'action'      => 'delete',
                'season'      => array( '2024', '2025' ),
            )
        );

        self::assertSame( 'Season(s) deleted', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
    }

    public function test_delete_seasons_error_when_none_deleted(): void {
        $season_service = $this->createMock( Season_Service::class );
        $guard          = $this->createStub( Action_Guard_Interface::class );

        $season_service->expects( self::once() )
            ->method( 'delete_season' )
            ->with( 2024 )
            ->willReturn( false );

        $dispatcher = new Season_Action_Dispatcher( $season_service, $guard );
        $result     = $dispatcher->handle(
            array(),
            array(
                'doSeasonDel' => '1',
                'action'      => 'delete',
                'season'      => array( '2024' ),
            )
        );

        self::assertSame( 'No season to delete', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }
}
