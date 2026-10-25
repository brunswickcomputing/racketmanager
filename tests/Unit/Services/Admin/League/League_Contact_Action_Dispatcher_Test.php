<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Admin\League;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\League\League_Contact_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition\Competition_Notification_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\League_Service;

require_once __DIR__ . '/../../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class League_Contact_Action_Dispatcher_Test extends TestCase {

    public function test_guard_failure_returns_error_and_sends_nothing(): void {
        $league_service = $this->createMock( League_Service::class );
        $league_service->expects( self::never() )->method( 'get_league' );
        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->expects( self::never() )->method( 'get_competition' );
        $notification_service = $this->createMock( Competition_Notification_Service::class );
        $notification_service->expects( self::never() )->method( 'contact_teams' );

        $guard = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_contact-teams-preview', 'edit_teams' )
            ->willThrowException( new Invalid_Status_Exception( 'Security token invalid' ) );

        $result = ( new League_Contact_Action_Dispatcher( $league_service, $competition_service, $guard, $notification_service ) )->handle(
            array( 'league_id' => '5', 'season' => '2026', 'emailMessage' => 'Hi' )
        );

        self::assertSame( 'Security token invalid', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_sends_message_to_league_teams(): void {
        $league = $this->createMock( League::class );
        $league_service = $this->createMock( League_Service::class );
        $league_service->expects( self::once() )->method( 'get_league' )->with( 5 )->willReturn( $league );
        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->expects( self::never() )->method( 'get_competition' );
        $notification_service = $this->createMock( Competition_Notification_Service::class );
        $notification_service->expects( self::once() )
            ->method( 'contact_league_teams' )
            ->with( $league, '2026', '<p>Hi</p>' )
            ->willReturn( true );

        $guard = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )->method( 'assert_allowed' );

        $result = ( new League_Contact_Action_Dispatcher( $league_service, $competition_service, $guard, $notification_service ) )->handle(
            array( 'league_id' => '5', 'competition_id' => '9', 'season' => '2026', 'emailMessage' => '&lt;p&gt;Hi&lt;/p&gt;' )
        );

        self::assertSame( 'Email sent to captains', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
    }

    public function test_sends_message_to_competition_teams_when_no_league(): void {
        $competition = $this->createStub( Competition::class );
        $notification_service = $this->createMock( Competition_Notification_Service::class );
        $notification_service->expects( self::once() )->method( 'contact_teams' )->with( $competition, '2026', 'Hello' )->willReturn( true );

        $league_service = $this->createMock( League_Service::class );
        $league_service->expects( self::never() )->method( 'get_league' );
        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->expects( self::once() )->method( 'get_competition' )->with( 9 )->willReturn( $competition );

        $guard = $this->createStub( Action_Guard_Interface::class );

        $result = ( new League_Contact_Action_Dispatcher( $league_service, $competition_service, $guard, $notification_service ) )->handle(
            array( 'competition_id' => '9', 'season' => '2026', 'emailMessage' => 'Hello' )
        );

        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
    }

    public function test_nothing_sent_returns_empty_result_without_calling_services(): void {
        $league_service = $this->createMock( League_Service::class );
        $league_service->expects( self::never() )->method( 'get_league' );
        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->expects( self::never() )->method( 'get_competition' );
        $notification_service = $this->createMock( Competition_Notification_Service::class );
        $notification_service->expects( self::never() )->method( 'contact_teams' );

        $result = ( new League_Contact_Action_Dispatcher( $league_service, $competition_service, $this->createStub( Action_Guard_Interface::class ), $notification_service ) )->handle(
            array( 'league_id' => '5' )
        );

        self::assertNull( $result->message );
        self::assertNull( $result->message_type );
    }
}
