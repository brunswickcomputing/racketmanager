<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Tournament\Events;

use DateTimeImmutable;
use InvalidArgumentException;
use Racketmanager\Domain\Events\Abstract_Domain_Event;
use Racketmanager\Domain\Tournament\Currency;
use Racketmanager\Domain\Value_Objects\Money;

/**
 * Dispatched when a tournament entry is submitted.
 */
final readonly class Tournament_Entry_Submitted_Event extends Abstract_Domain_Event {
    public ?Money $fee;
    public ?int $fee_cents;
    public ?Currency $currency;

    /**
     * @param int                    $entry_id      Entry identifier.
     * @param int                    $tournament_id Tournament identifier.
     * @param int                    $player_id     Player identifier.
     * @param int|null               $club_id       Club identifier.
     * @param Money|int|null         $fee           Monetary fee VO or fee in minor units (cents/pence).
     * @param Currency|string|null   $currency      Currency enum or ISO code/symbol (defaults to GBP if fee is int; ignored when fee is null).
     * @param DateTimeImmutable|null $occurred_on   Timestamp when the event occurred.
     *
     * @throws InvalidArgumentException When explicit currency conflicts with a Money fee.
     * Currency::from_symbol_or_code() also throws ValueError for an unrecognised currency string.
     */
    public function __construct(
        public int $entry_id,
        public int $tournament_id,
        public int $player_id,
        public ?int $club_id = null,
        Money|int|null $fee = null,
        Currency|string|null $currency = null,
        ?DateTimeImmutable $occurred_on = null,
    ) {
        parent::__construct( $occurred_on );

        if ( $fee instanceof Money ) {
            if ( null !== $currency ) {
                $resolved_currency = $currency instanceof Currency
                    ? $currency
                    : Currency::from_symbol_or_code( $currency );

                if ( $resolved_currency !== $fee->get_currency() ) {
                    // phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
                    throw new InvalidArgumentException(
                        sprintf(
                            'Explicit currency %s does not match Money currency %s.',
                            $resolved_currency->value,
                            $fee->get_currency()->value
                        )
                    );
                    // phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
                }
            }
            $this->fee = $fee;
        } elseif ( null !== $fee ) {
            $this->fee = Money::from_cents( $fee, $currency );
        } else {
            $this->fee = null;
        }

        $this->fee_cents = $this->fee?->get_cents();
        $this->currency  = $this->fee?->get_currency();
    }
}
