<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Finance;

use Racketmanager\Domain\Enums\Charge_Status;
use Racketmanager\Domain\Value_Objects\Currency;
use Racketmanager\Domain\Value_Objects\Money;

/**
 * Class to implement the charges object
 */
class Charge {
    /**
     * Id
     *
     * @var int|null
     */
    public ?int $id = null;
    /**
     * Season
     *
     * @var string|null
     */
    public ?string $season = null;
    /**
     * Competition id
     *
     * @var int|null
     */
    public ?int $competition_id = null;
    /**
     * Competition
     *
     * @var object|null
     */
    public ?object $competition = null;
    /**
     * Status
     *
     * @var string
     */
    public string $status = 'draft';
    /**
     * Date
     *
     * @var string|null
     */
    public ?string $date = null;
    /**
     * Club fee
     *
     * @var float|null
     */
    public ?float $fee_competition = null;
    /**
     * Team fee
     *
     * @var float|null
     */
    public ?float $fee_event = null;
    /**
     * Total
     *
     * @var float|null
     */
    public ?float $total = null;

    /**
     * Construct class instance
     *
     * @param object|null $charges charges object.
     */
    public function __construct( ?object $charges = null ) {
        if ( is_null( $charges ) ) {
            return;
        }
        $this->id              = isset( $charges->id ) && '' !== (string) $charges->id ? (int) $charges->id : null;
        $this->competition_id  = isset( $charges->competition_id ) && '' !== (string) $charges->competition_id ? (int) $charges->competition_id : null;
        $this->season          = isset( $charges->season ) ? (string) $charges->season : null;
        $this->status          = isset( $charges->status ) ? (string) $charges->status : 'draft';
        $this->date            = isset( $charges->date ) ? (string) $charges->date : null;
        $this->fee_competition = isset( $charges->fee_competition ) && '' !== (string) $charges->fee_competition ? (float) $charges->fee_competition : null;
        $this->fee_event       = isset( $charges->fee_event ) && '' !== (string) $charges->fee_event ? (float) $charges->fee_event : null;
        $this->total           = isset( $charges->total ) && '' !== (string) $charges->total ? (float) $charges->total : null;
    }

    public function get_id(): int|null {
        return $this->id;
    }

    public function set_id( int $insert_id ): void {
        $this->id = $insert_id;
    }

    public function get_competition_id(): ?int {
        return $this->competition_id;
    }

    /**
     * Set competition id
     *
     * @param int $competition_id competition id.
     */
    public function set_competition_id( int $competition_id ): void {
        $this->competition_id = $competition_id;
    }

    public function get_season(): ?string {
        return $this->season;
    }

    /**
     * Set season
     *
     * @param string $season season.
     */
    public function set_season( string $season ): void {
        $this->season = $season;
    }

    public function get_date(): ?string {
        return $this->date;
    }

    /**
     * Set date
     *
     * @param string $date date.
     */
    public function set_date( string $date ): void {
        $this->date = $date;
    }

    public function get_status(): string {
        return $this->status;
    }

    /**
     * Set charge status
     *
     * @param string|Charge_Status $status status value.
     */
    public function set_status( string|Charge_Status $status ): void {
        if ( $status instanceof Charge_Status ) {
            $this->status = $status->value;
            return;
        }
        $enum = Charge_Status::try_from_value( $status );
        $this->status = $enum ? $enum->value : $status;
    }

    public function get_status_enum(): ?Charge_Status {
        return Charge_Status::try_from_value( $this->status );
    }

    public function set_status_enum( Charge_Status $status ): void {
        $this->status = $status->value;
    }

    public function is_draft(): bool {
        return $this->get_status_enum()?->is_draft() ?? false;
    }

    public function is_final(): bool {
        return $this->get_status_enum()?->is_final() ?? false;
    }

    public function get_fee_competition(): float|null {
        return $this->fee_competition;
    }

    public function get_competition_fee_money( ?Currency $currency = null ): ?Money {
        if ( ! isset( $this->fee_competition ) || null === $this->fee_competition ) {
            return null;
        }
        return Money::from_float( $this->fee_competition, $currency );
    }

    public function set_competition_fee_money( Money $money ): void {
        $this->set_fee_competition( $money->get_amount() );
    }

    /**
     * Set club fee
     *
     * @param float|int|string|Money|null $fee_competition
     */
    public function set_fee_competition( float|int|string|Money|null $fee_competition ): void {
        if ( $fee_competition instanceof Money ) {
            $this->set_competition_fee_money( $fee_competition );
            return;
        }
        $this->fee_competition = ( null === $fee_competition || '' === (string) $fee_competition ) ? null : floatval( number_format( (float) $fee_competition, 2, '.', '' ) );
    }

    public function get_fee_event(): float|null {
        return $this->fee_event;
    }

    public function get_event_fee_money( ?Currency $currency = null ): ?Money {
        if ( ! isset( $this->fee_event ) || null === $this->fee_event ) {
            return null;
        }
        return Money::from_float( $this->fee_event, $currency );
    }

    public function set_event_fee_money( Money $money ): void {
        $this->set_fee_event( $money->get_amount() );
    }

    public function get_total_money( ?Currency $currency = null ): ?Money {
        $comp_money  = $this->get_competition_fee_money( $currency );
        $event_money = $this->get_event_fee_money( $currency );

        if ( null === $comp_money && null === $event_money ) {
            return null;
        }

        if ( null !== $comp_money && null !== $event_money ) {
            return $comp_money->add( $event_money );
        }

        return $comp_money ?? $event_money;
    }

    /**
     * Set team fee
     *
     * @param float|int|string|Money|null $fee_event team fee value.
     */
    public function set_fee_event( float|int|string|Money|null $fee_event ): void {
        if ( $fee_event instanceof Money ) {
            $this->set_event_fee_money( $fee_event );
            return;
        }
        $this->fee_event = ( null === $fee_event || '' === (string) $fee_event ) ? null : floatval( number_format( (float) $fee_event, 2, '.', '' ) );
    }
}
