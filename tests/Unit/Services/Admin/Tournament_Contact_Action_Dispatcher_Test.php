<?php
declare(strict_types=1);

namespace Racketmanager\Tests\Unit\Services\Admin;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Domain\DTO\Admin\Tournament_Contact_Action_Result_DTO;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Admin\Tournament\Tournament_Contact_Action_Dispatcher;
use Racketmanager\Services\Tournament_Service;

final class Tournament_Contact_Action_Dispatcher_Test extends TestCase {

    public function test_resolves_preview_intent_and_only_checks_guard(): void {
        $tournament_service = $this->createMock( Tournament_Service::class );
        $tournament_service
            ->expects( self::never() )
            ->method( 'contact_teams' );

        $guard = $this->createMock( Action_Guard_Interface::class );
        $guard
            ->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_contact-teams', 'edit_teams' );
        $guard
            ->expects( self::never() )
            ->method( 'assert_capability' );

        $dispatcher = new Tournament_Contact_Action_Dispatcher( $tournament_service, $guard );

        $result = $dispatcher->handle(
            55,
            array(
                'contactTeamPreview' => '1',
            )
        );

        self::assertSame( Tournament_Contact_Action_Result_DTO::INTENT_PREVIEW, $result->intent );
        self::assertNull( $result->message );
        self::assertNull( $result->message_type );
    }

    public function test_resolves_send_active_intent_and_invokes_service(): void {
        $tournament_service = $this->createMock( Tournament_Service::class );
        $tournament_service
            ->expects( self::once() )
            ->method( 'contact_teams' )
            ->with( 77, '<p>Previewed</p>', true )
            ->willReturn( true );

        $guard = $this->createMock( Action_Guard_Interface::class );
        $guard
            ->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_contact-teams-preview', 'edit_teams' );
        $guard
            ->expects( self::never() )
            ->method( 'assert_capability' );

        $dispatcher = new Tournament_Contact_Action_Dispatcher( $tournament_service, $guard );

        $result = $dispatcher->handle(
            77,
            array(
                'contactTeamActive' => '1',
                'emailMessage' => '&lt;p&gt;Previewed&lt;/p&gt;',
            )
        );

        self::assertSame( Tournament_Contact_Action_Result_DTO::INTENT_SEND_ACTIVE, $result->intent );
        self::assertSame( 'Email sent to players', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
    }

    public function test_unknown_payload_is_a_no_op(): void {
        $tournament_service = $this->createMock( Tournament_Service::class );
        $tournament_service
            ->expects( self::never() )
            ->method( 'contact_teams' );

        $guard = $this->createMock( Action_Guard_Interface::class );
        $guard
            ->expects( self::never() )
            ->method( 'assert_allowed' );
        $guard
            ->expects( self::never() )
            ->method( 'assert_capability' );

        $dispatcher = new Tournament_Contact_Action_Dispatcher( $tournament_service, $guard );

        $result = $dispatcher->handle(
            12,
            array(
                'somethingElse' => '1',
            )
        );

        self::assertSame( Tournament_Contact_Action_Result_DTO::INTENT_NONE, $result->intent );
        self::assertNull( $result->message );
        self::assertNull( $result->message_type );
    }
}