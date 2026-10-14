<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Admin\League;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\League\Event_Setup_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition_Service;

require_once __DIR__ . '/../../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Event_Setup_Action_Dispatcher_Test extends TestCase {

    public function test_nonce_failure_returns_error(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->expects( self::never() )->method( 'get_event_by_id' );

        $guard = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_add_championship-matches', 'edit_matches' )
            ->willThrowException( new Invalid_Status_Exception( 'Security token invalid' ) );

        $result = ( new Event_Setup_Action_Dispatcher( $competition_service, $guard ) )->handle(
            array( 'event_id' => '7' ),
            array(
                'action' => 'update',
                'season' => '2026',
                'rounds' => array( array( 'round' => '1', 'match_date' => '2026-04-01' ) ),
            )
        );

        self::assertSame( 'Security token invalid', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_validation_failure_does_not_load_event(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->expects( self::never() )->method( 'get_event_by_id' );

        $result = ( new Event_Setup_Action_Dispatcher( $competition_service, $this->createStub( Action_Guard_Interface::class ) ) )->handle(
            array( 'event_id' => '7' ),
            array(
                'action' => 'update',
                'season' => '2026',
                'rounds' => array( array( 'round' => '1', 'match_date' => '' ) ),
            )
        );

        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertStringContainsString( 'Match date missing for round 1', strval( $result->message ) );
    }

    public function test_success_updates_event_match_dates(): void {
        $event = $this->createMock( Event::class );
        $event->expects( self::once() )->method( 'get_season_by_name' )->with( '2026' )->willReturn( array( 'name' => '2026' ) );
        $event->expects( self::once() )->method( 'get_seasons' )->willReturn( array( 2026 => array( 'name' => '2026' ) ) );
        $event->expects( self::once() )->method( 'update_seasons' )->with(
            self::callback(
                static function ( array $seasons ): bool {
                    return isset( $seasons[2026]['match_dates'][0] ) && '2026-04-01' === $seasons[2026]['match_dates'][0];
                }
            )
        )->willReturn( true );

        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->expects( self::once() )->method( 'get_event_by_id' )->with( 7 )->willReturn( $event );

        $result = ( new Event_Setup_Action_Dispatcher( $competition_service, $this->createStub( Action_Guard_Interface::class ) ) )->handle(
            array( 'event_id' => '7' ),
            array(
                'action' => 'update',
                'season' => '2026',
                'rounds' => array( array( 'round' => '1', 'match_date' => '2026-04-01' ) ),
            )
        );

        self::assertSame( 'Match dates updated', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
    }

    public function test_no_updates_returns_warning(): void {
        $event = $this->createMock( Event::class );
        $event->method( 'get_season_by_name' )->willReturn( array( 'name' => '2026' ) );
        $event->method( 'get_seasons' )->willReturn( array( 2026 => array( 'name' => '2026' ) ) );
        $event->method( 'update_seasons' )->willReturn( false );

        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->method( 'get_event_by_id' )->willReturn( $event );

        $result = ( new Event_Setup_Action_Dispatcher( $competition_service, $this->createStub( Action_Guard_Interface::class ) ) )->handle(
            array( 'event_id' => '7' ),
            array(
                'action' => 'update',
                'season' => '2026',
                'rounds' => array( array( 'round' => '1', 'match_date' => '2026-04-01' ) ),
            )
        );

        self::assertSame( 'No updates', $result->message );
        self::assertSame( Admin_Message_Type::WARNING, $result->message_type );
    }
}
