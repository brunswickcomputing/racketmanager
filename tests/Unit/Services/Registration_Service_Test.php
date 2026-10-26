<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\Club\Club_Player;
use Racketmanager\Domain\Club\Events\Club_Player_Registered_Event;
use Racketmanager\Domain\DTO\Player\Player_Hydration_DTO;
use Racketmanager\Domain\Events\Domain_Event_Dispatcher_Interface;
use Racketmanager\Domain\Events\Domain_Event_Interface;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Gateways\Mail\Mailer_Interface;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Club_Repository;
use Racketmanager\Repositories\Interfaces\Registration_Repository_Interface;
use Racketmanager\Repositories\Player_Repository;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Tests\Support\Fixed_Clock;

final class Registration_Service_Test extends TestCase {

    public function test_register_player_to_club_dispatches_pending_event_when_confirmation_is_manual(): void {
        $app = $this->createStub( RacketManager::class );
        $app->method( 'get_options' )->willReturn( [ 'rosterConfirmation' => 'manual', 'rosterConfirmationEmail' => '' ] );

        $club_player_repo = $this->createMock( Registration_Repository_Interface::class );
        $player_repo      = $this->createMock( Player_Repository::class );
        $club_repo        = $this->createMock( Club_Repository::class );
        $player_service   = $this->createStub( Player_Service::class );
        $mailer           = $this->createStub( Mailer_Interface::class );
        $dispatcher       = $this->createMock( Domain_Event_Dispatcher_Interface::class );

        $club = $this->createStub( Club::class );
        $club_repo->expects( $this->once() )->method( 'find_by_id' )->with( 5 )->willReturn( $club );

        $player_data = (object) [ 'id' => 20, 'display_name' => 'Jane Doe' ];
        $player      = new Player( Player_Hydration_DTO::from_object( $player_data ) );
        $player_service->method( 'validate_player' )->willReturn( $player_data );
        $player_repo->expects( $this->once() )->method( 'find' )->with( 20 )->willReturn( $player );

        $club_player_repo->expects( $this->once() )->method( 'find_by_club_and_player' )->with( 5, 20 )->willReturn( null );
        $club_player_repo->expects( $this->once() )->method( 'save' );

        $dispatched_events = [];
        $dispatcher->expects( $this->once() )
            ->method( 'dispatch' )
            ->willReturnCallback( function ( Domain_Event_Interface $event ) use ( &$dispatched_events ) {
                $dispatched_events[] = $event;
            } );

        $service = new Registration_Service(
            plugin_instance: $app,
            club_player_repository: $club_player_repo,
            player_repository: $player_repo,
            club_repository: $club_repo,
            player_service: $player_service,
            mailer: $mailer,
            event_dispatcher: $dispatcher
        );

        $msg = $service->register_player_to_club( 5, 1, [] );

        $this->assertSame( 'Player registration pending', $msg );
        $this->assertCount( 1, $dispatched_events );
        $this->assertInstanceOf( Club_Player_Registered_Event::class, $dispatched_events[0] );
        $this->assertSame( 5, $dispatched_events[0]->club_id );
        $this->assertSame( 20, $dispatched_events[0]->player_id );
        $this->assertSame( 1, $dispatched_events[0]->registered_by_user_id );
        $this->assertSame( 'pending', $dispatched_events[0]->status );
    }

    public function test_register_player_to_club_dispatches_approved_event_when_confirmation_is_auto(): void {
        $app = $this->createStub( RacketManager::class );
        $app->method( 'get_options' )->willReturn( [ 'rosterConfirmation' => 'auto', 'rosterConfirmationEmail' => '' ] );

        $club_player_repo = $this->createMock( Registration_Repository_Interface::class );
        $player_repo      = $this->createMock( Player_Repository::class );
        $club_repo        = $this->createMock( Club_Repository::class );
        $player_service   = $this->createStub( Player_Service::class );
        $mailer           = $this->createStub( Mailer_Interface::class );
        $dispatcher       = $this->createMock( Domain_Event_Dispatcher_Interface::class );

        $club = $this->createStub( Club::class );
        $club_repo->expects( $this->once() )->method( 'find_by_id' )->with( 5 )->willReturn( $club );

        $player_data = (object) [ 'id' => 20, 'display_name' => 'Jane Doe' ];
        $player      = new Player( Player_Hydration_DTO::from_object( $player_data ) );
        $player_service->method( 'validate_player' )->willReturn( $player_data );
        $player_repo->expects( $this->once() )->method( 'find' )->with( 20 )->willReturn( $player );

        $club_player_repo->expects( $this->once() )->method( 'find_by_club_and_player' )->with( 5, 20 )->willReturn( null );

        $created_registration = new Club_Player( (object) [
            'id'             => 100,
            'club_id'        => 5,
            'player_id'      => 20,
            'status'         => 'pending',
            'requested_date' => '2026-01-01',
            'requested_user' => 1,
        ] );

        $club_player_repo->expects( $this->once() )->method( 'find' )->with( 0 )->willReturn( $created_registration );
        // Saved twice: once initially, once upon approval
        $club_player_repo->expects( $this->exactly( 2 ) )->method( 'save' );

        $dispatched_events = [];
        $dispatcher->expects( $this->once() )
            ->method( 'dispatch' )
            ->willReturnCallback( function ( Domain_Event_Interface $event ) use ( &$dispatched_events ) {
                $dispatched_events[] = $event;
            } );

        $service = new Registration_Service(
            plugin_instance: $app,
            club_player_repository: $club_player_repo,
            player_repository: $player_repo,
            club_repository: $club_repo,
            player_service: $player_service,
            mailer: $mailer,
            event_dispatcher: $dispatcher
        );

        $msg = $service->register_player_to_club( 5, 1, [] );

        $this->assertSame( 'Player added to club', $msg );
        $this->assertCount( 1, $dispatched_events );
        $this->assertInstanceOf( Club_Player_Registered_Event::class, $dispatched_events[0] );
        $this->assertSame( 5, $dispatched_events[0]->club_id );
        $this->assertSame( 20, $dispatched_events[0]->player_id );
        $this->assertSame( 1, $dispatched_events[0]->registered_by_user_id );
        $this->assertSame( 'approved', $dispatched_events[0]->status );
    }

    public function test_approve_registration_stamps_the_injected_clock_time(): void {
        $registration = new Club_Player( (object) [ 'id' => 100, 'club_id' => 5, 'player_id' => 20, 'status' => 'pending' ] );

        $club_player_repo = $this->createMock( Registration_Repository_Interface::class );
        $club_player_repo->expects( $this->once() )->method( 'find' )->with( 100 )->willReturn( $registration );
        $club_player_repo->expects( $this->once() )->method( 'save' )->with( $registration );

        $service = $this->service_with_clock( $club_player_repo, new Fixed_Clock( '2026-03-01 12:34:56' ) );

        $service->approve_registration( 100, 7 );

        $this->assertSame( 'approved', $registration->get_status() );
        $this->assertSame( 7, $registration->get_created_user() );
        $this->assertSame( '2026-03-01 12:34:56', $registration->get_created_date() );
    }

    /**
     * Legacy behaviour preserved: remove_registration() still routes through Club_Player::approve()
     * (pre-10.1.0 logic, out of scope for the WordPress-purity refactor); only the timestamp source changed.
     */
    public function test_remove_registration_stamps_the_injected_clock_time(): void {
        $registration = new Club_Player( (object) [ 'id' => 100, 'club_id' => 5, 'player_id' => 20, 'status' => 'pending' ] );

        $club_player_repo = $this->createMock( Registration_Repository_Interface::class );
        $club_player_repo->expects( $this->once() )->method( 'find' )->with( 100 )->willReturn( $registration );
        $club_player_repo->expects( $this->once() )->method( 'save' )->with( $registration );

        $service = $this->service_with_clock( $club_player_repo, new Fixed_Clock( '2026-07-15 08:00:00' ) );

        $service->remove_registration( 100, 9 );

        $this->assertSame( 9, $registration->get_created_user() );
        $this->assertSame( '2026-07-15 08:00:00', $registration->get_created_date() );
    }

    private function service_with_clock( Registration_Repository_Interface $club_player_repo, Fixed_Clock $clock ): Registration_Service {
        return new Registration_Service(
            plugin_instance: $this->createStub( RacketManager::class ),
            club_player_repository: $club_player_repo,
            player_repository: $this->createStub( Player_Repository::class ),
            club_repository: $this->createStub( Club_Repository::class ),
            player_service: $this->createStub( Player_Service::class ),
            mailer: $this->createStub( Mailer_Interface::class ),
            event_dispatcher: null,
            clock: $clock
        );
    }
}
