<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Tournament;

use DomainException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Tournament\Currency;
use Racketmanager\Domain\Tournament\Events\Tournament_Entry_Submitted_Event;
use Racketmanager\Domain\Tournament\Events\Tournament_Entry_Withdrawn_Event;
use Racketmanager\Domain\Tournament\Tournament_Entry;
use Racketmanager\Domain\Value_Objects\Money;

class Tournament_Entry_Test extends TestCase {
    public function test_instantiation_without_data(): void {
        $entry = new Tournament_Entry();
        $this->assertInstanceOf( Tournament_Entry::class, $entry );
        $this->assertNull( $entry->get_id() );
        $this->assertNull( $entry->get_fee() );
        $this->assertNull( $entry->get_fee_money() );
        $this->assertNull( $entry->get_club_id() );
        $this->assertNull( $entry->get_club() );
        $this->assertFalse( $entry->has_fee() );
        $this->assertFalse( $entry->has_club() );
        $this->assertFalse( $entry->has_domain_events() );
        $this->assertEmpty( $entry->pull_domain_events() );
    }

    public function test_instantiation_with_data_and_getters_setters(): void {
        $data = (object) [
            'id'            => 55,
            'tournament_id' => 12,
            'player_id'     => 34,
            'status'        => 1,
            'fee'           => '15.00',
            'club_id'       => 7,
        ];

        $entry = Tournament_Entry::from_object( $data );

        $this->assertSame( 55, $entry->get_id() );
        $this->assertSame( 12, $entry->get_tournament_id() );
        $this->assertSame( 34, $entry->get_player_id() );
        $this->assertSame( 1, $entry->get_status() );
        $this->assertSame( 15, $entry->get_fee() );
        $this->assertInstanceOf( Money::class, $entry->get_fee_money() );
        $this->assertSame( 1500, $entry->get_fee_money()->get_cents() );
        $this->assertSame( '£15.00', (string) $entry->get_fee_money() );
        $this->assertSame( Currency::GBP, $entry->get_fee_money()->get_currency() );
        $this->assertSame( 7, $entry->get_club_id() );
        $this->assertFalse( $entry->has_domain_events() );

        $entry->set_id( 60 );
        $entry->set_tournament_id( 14 );
        $entry->set_player_id( 36 );
        $entry->set_status( 2 );
        $entry->set_fee( 20 );
        $entry->set_club_id( 9 );

        $club = (object) [ 'name' => 'Westcliff Hardcourts' ];
        $entry->set_club( $club );

        $this->assertSame( 60, $entry->get_id() );
        $this->assertSame( 14, $entry->get_tournament_id() );
        $this->assertSame( 36, $entry->get_player_id() );
        $this->assertSame( 2, $entry->get_status() );
        $this->assertSame( 20, $entry->get_fee() );
        $this->assertSame( 2000, $entry->get_fee_money()?->get_cents() );
        $this->assertSame( '£20.00', (string) $entry->get_fee_money() );
        $this->assertSame( 9, $entry->get_club_id() );
        $this->assertSame( $club, $entry->get_club() );
    }

    public function test_fee_money_getter_and_setter_sync_with_fee(): void {
        $entry = new Tournament_Entry();
        $this->assertNull( $entry->get_fee() );
        $this->assertNull( $entry->get_fee_money() );

        $money  = Money::from_cents( 3500, Currency::GBP );
        $result = $entry->set_fee_money( $money );

        $this->assertSame( $entry, $result );
        $this->assertSame( 35, $entry->get_fee() );
        $this->assertSame( $money, $entry->get_fee_money() );
        $this->assertSame( Currency::GBP, $entry->get_fee_money()?->get_currency() );

        $entry->set_fee_money( null );
        $this->assertNull( $entry->get_fee() );
        $this->assertNull( $entry->get_fee_money() );

        $entry->set_fee( 12 );
        $this->assertSame( 12, $entry->get_fee() );
        $this->assertInstanceOf( Money::class, $entry->get_fee_money() );
        $this->assertSame( 1200, $entry->get_fee_money()->get_cents() );
        $this->assertSame( '£12.00', (string) $entry->get_fee_money() );
        $this->assertSame( Currency::GBP, $entry->get_fee_money()->get_currency() );

        $entry->set_fee( null );
        $this->assertNull( $entry->get_fee() );
        $this->assertNull( $entry->get_fee_money() );
    }

    public function test_fee_money_with_fractional_pence(): void {
        $entry = new Tournament_Entry();

        // 15.50
        $entry->set_fee_money( Money::from_string( '15.50', Currency::GBP ) );
        $this->assertSame( 1550, $entry->get_fee_money()?->get_cents() );
        $this->assertSame( 16, $entry->get_fee() );
        $this->assertSame( '£15.50', (string) $entry->get_fee_money() );

        // 15.49
        $entry->set_fee_money( Money::from_string( '15.49', Currency::GBP ) );
        $this->assertSame( 1549, $entry->get_fee_money()?->get_cents() );
        $this->assertSame( 15, $entry->get_fee() );
        $this->assertSame( '£15.49', (string) $entry->get_fee_money() );
    }

    public function test_hydration_with_decimal_strings_and_floats(): void {
        $entry = Tournament_Entry::from_array( [ 'fee' => '15.50' ] );
        $this->assertSame( 1550, $entry->get_fee_money()?->get_cents() );
        $this->assertSame( 16, $entry->get_fee() );

        $entry = Tournament_Entry::from_array( [ 'fee' => '15.49' ] );
        $this->assertSame( 1549, $entry->get_fee_money()?->get_cents() );
        $this->assertSame( 15, $entry->get_fee() );

        $entry = Tournament_Entry::from_array( [ 'fee' => 12.50 ] );
        $this->assertSame( 1250, $entry->get_fee_money()?->get_cents() );
        $this->assertSame( 13, $entry->get_fee() );

        $entry = Tournament_Entry::from_array( [ 'fee' => null ] );
        $this->assertNull( $entry->get_fee() );
        $this->assertNull( $entry->get_fee_money() );
    }

    public function test_fee_money_rejects_non_gbp_currency(): void {
        $entry = new Tournament_Entry();
        $money = Money::from_cents( 2500, Currency::EUR );

        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessage( 'Tournament entry fee currency must be GBP, got EUR.' );

        $entry->set_fee_money( $money );
    }

    public function test_domain_status_methods_and_helpers(): void {
        $entry = Tournament_Entry::from_array(
            [
                'id'            => 10,
                'tournament_id' => 5,
                'player_id'     => 42,
                'status'        => 0,
                'fee'           => 25,
                'club_id'       => 3,
            ]
        );

        $this->assertTrue( $entry->is_pending() );
        $this->assertFalse( $entry->is_confirmed() );
        $this->assertFalse( $entry->is_withdrawn() );
        $this->assertTrue( $entry->has_fee() );
        $this->assertTrue( $entry->has_club() );
        $this->assertTrue( $entry->is_for_tournament( 5 ) );
        $this->assertFalse( $entry->is_for_tournament( 6 ) );
        $this->assertTrue( $entry->is_for_player( 42 ) );
        $this->assertFalse( $entry->is_for_player( 99 ) );

        $entry->set_status( 2 );
        $this->assertFalse( $entry->is_pending() );
        $this->assertTrue( $entry->is_confirmed() );
        $this->assertFalse( $entry->is_withdrawn() );

        $entry->withdraw();
        $this->assertFalse( $entry->is_pending() );
        $this->assertFalse( $entry->is_confirmed() );
        $this->assertTrue( $entry->is_withdrawn() );
        $this->assertSame( 3, $entry->get_status() );
    }

    public function test_submit_records_event_and_updates_status(): void {
        $entry = Tournament_Entry::create( 22, 33, 2 );
        $entry->set_id( 101 );

        $this->assertFalse( $entry->has_domain_events() );

        $money = Money::from_cents( 1800, Currency::GBP );
        $res   = $entry->submit( fee: $money, club_id: 15 );

        $this->assertSame( $entry, $res );
        $this->assertSame( 2, $entry->get_status() );
        $this->assertSame( 15, $entry->get_club_id() );
        $this->assertSame( 18, $entry->get_fee() );
        $this->assertSame( $money, $entry->get_fee_money() );
        $this->assertTrue( $entry->has_domain_events() );

        $events = $entry->pull_domain_events();
        $this->assertCount( 1, $events );
        $this->assertFalse( $entry->has_domain_events() );

        $event = $events[0];
        $this->assertInstanceOf( Tournament_Entry_Submitted_Event::class, $event );
        $this->assertSame( 101, $event->entry_id );
        $this->assertSame( 22, $event->tournament_id );
        $this->assertSame( 33, $event->player_id );
        $this->assertSame( 15, $event->club_id );
        $this->assertSame( $money, $event->fee );
        $this->assertSame( 1800, $event->fee_cents );
        $this->assertSame( Currency::GBP, $event->currency );
    }

    public function test_submit_with_int_fee_and_without_club_keeps_created_pending_status(): void {
        $entry = Tournament_Entry::create( 25, 35, 0 );
        $entry->set_id( 102 );

        $entry->submit( fee: 12, status: 0 );

        $this->assertSame( 0, $entry->get_status() );
        $this->assertTrue( $entry->is_pending() );
        $this->assertSame( 12, $entry->get_fee() );
        $this->assertSame( 1200, $entry->get_fee_money()?->get_cents() );
        $this->assertSame( '£12.00', (string) $entry->get_fee_money() );

        $events = $entry->pull_domain_events();
        $this->assertCount( 1, $events );
        $event = $events[0];
        $this->assertInstanceOf( Tournament_Entry_Submitted_Event::class, $event );
        $this->assertSame( 1200, $event->fee_cents );
        $this->assertNull( $event->club_id );
    }

    public function test_submit_with_explicit_status_from_workflow(): void {
        $entry = Tournament_Entry::create( 26, 36, 0 );
        $entry->set_id( 103 );

        // Status 1 (payment due) or status 4 (invoice pending) from real workflow
        $entry->submit( fee: 25, status: 1 );
        $this->assertSame( 1, $entry->get_status() );

        $events = $entry->pull_domain_events();
        $this->assertCount( 1, $events );
    }

    public function test_create_then_save_then_submit_emits_exactly_one_event(): void {
        // Mirrors the service flow: build new entry (status 0/2), repository save() assigns the ID, then submit().
        foreach ( [ 0, 2 ] as $status ) {
            $entry = Tournament_Entry::create( 12, 34, $status, '15.00', 7 );
            $this->assertTrue( $entry->is_pending_submission() );
            $this->assertFalse( $entry->has_domain_events() );

            $entry->set_id( 77 );
            $entry->submit();

            $this->assertFalse( $entry->is_pending_submission() );
            $this->assertSame( $status, $entry->get_status() );
            $events = $entry->pull_domain_events();
            $this->assertCount( 1, $events );
            $this->assertInstanceOf( Tournament_Entry_Submitted_Event::class, $events[0] );
            $this->assertSame( 77, $events[0]->entry_id );
            $this->assertSame( 7, $events[0]->club_id );
            $this->assertSame( 1500, $events[0]->fee_cents );

            $entry->submit();
            $this->assertFalse( $entry->has_domain_events() );
        }
    }

    public function test_hydrated_existing_active_entry_never_emits_on_submit(): void {
        foreach ( [ 0, 1, 2, 4 ] as $status ) {
            $entry = Tournament_Entry::from_array(
                [
                    'id'            => 55,
                    'tournament_id' => 12,
                    'player_id'     => 34,
                    'status'        => $status,
                    'fee'           => '15.00',
                ]
            );
            $this->assertFalse( $entry->is_pending_submission() );

            $entry->submit();
            $entry->submit( fee: 20, club_id: 3, status: $status );

            $this->assertFalse( $entry->has_domain_events() );
            $this->assertSame( 20, $entry->get_fee() );
        }
    }

    public function test_create_rejects_non_active_status(): void {
        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessage( 'Invalid tournament entry submission status 3.' );

        Tournament_Entry::create( 1, 2, 3 );
    }

    public function test_create_without_id_cannot_submit_and_keeps_pending_flag(): void {
        $entry = Tournament_Entry::create( 1, 2, 0 );

        try {
            $entry->submit();
            $this->fail( 'Expected DomainException.' );
        } catch ( DomainException ) {
            $this->assertTrue( $entry->is_pending_submission() );
            $this->assertFalse( $entry->has_domain_events() );
        }
    }

    public function test_submit_guards_against_duplicate_submission(): void {
        $entry = Tournament_Entry::create( 27, 37, 0 );
        $entry->set_id( 104 );

        $entry->submit( fee: 15, club_id: 5 );
        $this->assertCount( 1, $entry->pull_domain_events() );

        // Calling submit again (with or without arguments) while active should not record new events
        $entry->submit();
        $this->assertFalse( $entry->has_domain_events() );

        $entry->submit( status: 2 );
        $this->assertFalse( $entry->has_domain_events() );

        $entry->submit( fee: 20 );
        $this->assertSame( 20, $entry->get_fee() );
        $this->assertFalse( $entry->has_domain_events() );
    }

    public function test_submit_rejects_withdrawn_status(): void {
        $entry = new Tournament_Entry();
        $entry->set_id( 108 );
        $entry->set_tournament_id( 27 );
        $entry->set_player_id( 37 );

        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessage( 'Invalid tournament entry submission status 3.' );

        $entry->submit( status: 3 );
    }

    public function test_submit_rejects_unknown_status(): void {
        $entry = new Tournament_Entry();
        $entry->set_id( 109 );
        $entry->set_tournament_id( 27 );
        $entry->set_player_id( 37 );

        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessage( 'Invalid tournament entry submission status 99.' );

        $entry->submit( status: 99 );
    }

    public function test_resubmit_withdrawn_entry_resets_status_and_records_event(): void {
        $entry = Tournament_Entry::create( 28, 38, 0 );
        $entry->set_id( 105 );

        $entry->submit( fee: 15 );
        $entry->withdraw();
        $this->assertTrue( $entry->is_withdrawn() );
        $entry->pull_domain_events(); // Clear events

        // Resubmit
        $entry->submit( club_id: 10 );
        $this->assertFalse( $entry->is_withdrawn() );
        $this->assertSame( 2, $entry->get_status() );
        $events = $entry->pull_domain_events();
        $this->assertCount( 1, $events );
        $this->assertInstanceOf( Tournament_Entry_Submitted_Event::class, $events[0] );

        // Resubmit withdrawn entry with explicit status 1
        $entry->withdraw();
        $entry->pull_domain_events();
        $entry->submit( status: 1 );
        $this->assertSame( 1, $entry->get_status() );
        $this->assertCount( 1, $entry->pull_domain_events() );
    }

    public function test_submit_throws_exception_when_unpersisted_or_incomplete(): void {
        $entry = new Tournament_Entry();
        $entry->set_tournament_id( 20 );
        // Missing ID and player_id

        $this->expectException( DomainException::class );
        $this->expectExceptionMessage( 'Cannot submit an incomplete or unpersisted tournament entry without ID, tournament_id, and player_id.' );

        $entry->submit();
    }

    public function test_withdraw_records_event_with_reason(): void {
        $entry = new Tournament_Entry();
        $entry->set_id( 205 );
        $entry->set_tournament_id( 10 );
        $entry->set_player_id( 88 );
        $entry->set_status( 2 );

        $entry->withdraw( 'Injured shoulder' );

        $this->assertSame( 3, $entry->get_status() );
        $this->assertTrue( $entry->is_withdrawn() );
        $this->assertTrue( $entry->has_domain_events() );

        $events = $entry->pull_domain_events();
        $this->assertCount( 1, $events );
        $this->assertFalse( $entry->has_domain_events() );

        $event = $events[0];
        $this->assertInstanceOf( Tournament_Entry_Withdrawn_Event::class, $event );
        $this->assertSame( 205, $event->entry_id );
        $this->assertSame( 10, $event->tournament_id );
        $this->assertSame( 88, $event->player_id );
        $this->assertSame( 'Injured shoulder', $event->reason );
    }

    public function test_withdraw_guards_against_duplicate_withdrawal(): void {
        $entry = new Tournament_Entry();
        $entry->set_id( 206 );
        $entry->set_tournament_id( 10 );
        $entry->set_player_id( 89 );
        $entry->set_status( 2 );

        $entry->withdraw( 'First withdrawal' );
        $this->assertCount( 1, $entry->pull_domain_events() );

        // Second withdrawal call should be a no-op
        $entry->withdraw( 'Duplicate call' );
        $this->assertFalse( $entry->has_domain_events() );
    }

    public function test_withdraw_throws_exception_when_unpersisted_or_incomplete(): void {
        $entry = new Tournament_Entry();
        $entry->set_tournament_id( 10 );
        $entry->set_player_id( 88 );
        // Missing ID

        $this->expectException( DomainException::class );
        $this->expectExceptionMessage( 'Cannot withdraw an incomplete or unpersisted tournament entry without ID, tournament_id, and player_id.' );

        $entry->withdraw();
    }

    public function test_pull_domain_events_clears_buffer(): void {
        $entry = Tournament_Entry::create( 2, 3, 0 );
        $entry->set_id( 1 );

        $entry->submit();
        $entry->withdraw( 'Changed mind' );

        $this->assertTrue( $entry->has_domain_events() );
        $events = $entry->pull_domain_events();
        $this->assertCount( 2, $events );
        $this->assertInstanceOf( Tournament_Entry_Submitted_Event::class, $events[0] );
        $this->assertInstanceOf( Tournament_Entry_Withdrawn_Event::class, $events[1] );

        $this->assertFalse( $entry->has_domain_events() );
        $this->assertEmpty( $entry->pull_domain_events() );
    }
}
