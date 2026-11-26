<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Tournament\Events;

use DateTimeImmutable;
use Error;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Events\Domain_Event_Interface;
use Racketmanager\Domain\Tournament\Currency;
use Racketmanager\Domain\Tournament\Events\Tournament_Entry_Submitted_Event;
use Racketmanager\Domain\Tournament\Events\Tournament_Entry_Withdrawn_Event;
use Racketmanager\Domain\Value_Objects\Money;
use ValueError;

/**
 * Unit tests for Tournament Entry Domain Events.
 */
final class Tournament_Entry_Events_Test extends TestCase {

    public function test_tournament_entry_submitted_event_implements_interface(): void {
        $event = new Tournament_Entry_Submitted_Event(
            entry_id: 1,
            tournament_id: 10,
            player_id: 20
        );

        $this->assertInstanceOf( Domain_Event_Interface::class, $event );
        $this->assertSame( Tournament_Entry_Submitted_Event::class, $event->get_event_name() );
        $this->assertInstanceOf( DateTimeImmutable::class, $event->get_occurred_on() );
        $this->assertSame( 1, $event->entry_id );
        $this->assertSame( 10, $event->tournament_id );
        $this->assertSame( 20, $event->player_id );
        $this->assertNull( $event->club_id );
        $this->assertNull( $event->fee );
        $this->assertNull( $event->fee_cents );
        $this->assertNull( $event->currency );
    }

    public function test_tournament_entry_submitted_event_with_money_instance(): void {
        $now   = new DateTimeImmutable( '2026-11-26 12:00:00' );
        $money = Money::from_cents( 2500, Currency::EUR );

        $event = new Tournament_Entry_Submitted_Event(
            entry_id: 101,
            tournament_id: 50,
            player_id: 300,
            club_id: 5,
            fee: $money,
            occurred_on: $now
        );

        $this->assertSame( 101, $event->entry_id );
        $this->assertSame( 50, $event->tournament_id );
        $this->assertSame( 300, $event->player_id );
        $this->assertSame( 5, $event->club_id );
        $this->assertSame( $money, $event->fee );
        $this->assertSame( 2500, $event->fee_cents );
        $this->assertSame( Currency::EUR, $event->currency );
        $this->assertSame( $now, $event->get_occurred_on() );
    }

    public function test_tournament_entry_submitted_event_with_money_and_matching_currency(): void {
        $money = Money::from_cents( 2500, Currency::EUR );

        $event = new Tournament_Entry_Submitted_Event(
            entry_id: 101,
            tournament_id: 50,
            player_id: 300,
            fee: $money,
            currency: 'EUR'
        );

        $this->assertSame( Currency::EUR, $event->currency );
        $this->assertSame( $money, $event->fee );
    }

    public function test_tournament_entry_submitted_event_throws_exception_on_conflicting_currency(): void {
        $money = Money::from_cents( 2500, Currency::EUR );

        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessage( 'Explicit currency USD does not match Money currency EUR.' );

        new Tournament_Entry_Submitted_Event(
            entry_id: 101,
            tournament_id: 50,
            player_id: 300,
            fee: $money,
            currency: Currency::USD
        );
    }

    public function test_tournament_entry_submitted_event_with_integer_fee_and_currency(): void {
        $event = new Tournament_Entry_Submitted_Event(
            entry_id: 102,
            tournament_id: 50,
            player_id: 301,
            club_id: 12,
            fee: 1500,
            currency: 'USD'
        );

        $this->assertSame( 1500, $event->fee_cents );
        $this->assertSame( Currency::USD, $event->currency );
        $this->assertInstanceOf( Money::class, $event->fee );
        $this->assertSame( 1500, $event->fee->get_cents() );
        $this->assertSame( Currency::USD, $event->fee->get_currency() );
    }

    public function test_tournament_entry_submitted_event_with_integer_fee_default_currency(): void {
        $event = new Tournament_Entry_Submitted_Event(
            entry_id: 103,
            tournament_id: 50,
            player_id: 302,
            fee: 3000
        );

        $this->assertSame( 3000, $event->fee_cents );
        $this->assertSame( Currency::GBP, $event->currency );
        $this->assertInstanceOf( Money::class, $event->fee );
        $this->assertSame( 3000, $event->fee->get_cents() );
        $this->assertSame( Currency::GBP, $event->fee->get_currency() );
    }

    public function test_tournament_entry_submitted_event_with_null_fee_leaves_currency_null(): void {
        $event = new Tournament_Entry_Submitted_Event(
            entry_id: 104,
            tournament_id: 50,
            player_id: 303,
            currency: Currency::EUR
        );

        $this->assertNull( $event->fee );
        $this->assertNull( $event->fee_cents );
        $this->assertNull( $event->currency );
    }

    public function test_tournament_entry_submitted_event_throws_value_error_on_invalid_currency_code(): void {
        $this->expectException( ValueError::class );

        new Tournament_Entry_Submitted_Event(
            entry_id: 105,
            tournament_id: 50,
            player_id: 304,
            fee: 1000,
            currency: 'INVALID_CURRENCY'
        );
    }

    public function test_tournament_entry_submitted_event_is_immutable(): void {
        $event = new Tournament_Entry_Submitted_Event(
            entry_id: 1,
            tournament_id: 10,
            player_id: 20
        );

        $this->expectException( Error::class );
        $this->expectExceptionMessageMatches( '/Cannot modify readonly property/' );

        // @phpstan-ignore-next-line
        $event->entry_id = 999;
    }

    public function test_tournament_entry_withdrawn_event_implements_interface(): void {
        $event = new Tournament_Entry_Withdrawn_Event(
            entry_id: 5,
            tournament_id: 15,
            player_id: 42
        );

        $this->assertInstanceOf( Domain_Event_Interface::class, $event );
        $this->assertSame( Tournament_Entry_Withdrawn_Event::class, $event->get_event_name() );
        $this->assertInstanceOf( DateTimeImmutable::class, $event->get_occurred_on() );
        $this->assertSame( 5, $event->entry_id );
        $this->assertSame( 15, $event->tournament_id );
        $this->assertSame( 42, $event->player_id );
        $this->assertNull( $event->reason );
    }

    public function test_tournament_entry_withdrawn_event_with_reason_and_custom_timestamp(): void {
        $occurred = new DateTimeImmutable( '2026-11-26 15:30:00' );
        $event    = new Tournament_Entry_Withdrawn_Event(
            entry_id: 8,
            tournament_id: 15,
            player_id: 99,
            reason: 'Injury',
            occurred_on: $occurred
        );

        $this->assertSame( 8, $event->entry_id );
        $this->assertSame( 15, $event->tournament_id );
        $this->assertSame( 99, $event->player_id );
        $this->assertSame( 'Injury', $event->reason );
        $this->assertSame( $occurred, $event->get_occurred_on() );
    }

    public function test_tournament_entry_withdrawn_event_is_immutable(): void {
        $event = new Tournament_Entry_Withdrawn_Event(
            entry_id: 8,
            tournament_id: 15,
            player_id: 99,
            reason: 'Injury'
        );

        $this->expectException( Error::class );
        $this->expectExceptionMessageMatches( '/Cannot modify readonly property/' );

        // @phpstan-ignore-next-line
        $event->reason = 'New Reason';
    }
}
