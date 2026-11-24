<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Finance;

use InvalidArgumentException;
use Racketmanager\Domain\DTO\Finance\Charge_Hydration_DTO;
use Racketmanager\Domain\Enums\Charge_Status;
use Racketmanager\Domain\Tournament\Currency;
use Racketmanager\Domain\Value_Objects\Money;

/**
 * Class to implement the charge object
 */
class Charge {
    /**
     * Id
     *
     * @var int|null
     */
    public private(set) ?int $id = null;

    /**
     * Season
     *
     * @var string|null
     */
    public private(set) ?string $season = null;

    /**
     * Competition id
     *
     * @var int|null
     */
    public private(set) ?int $competition_id = null;

    /**
     * Competition
     *
     * @var object|null
     */
    public private(set) ?object $competition = null;

    /**
     * Status
     *
     * @var string
     */
    public private(set) string $status = 'draft';

    /**
     * Date
     *
     * @var string|null
     */
    public private(set) ?string $date = null;

    /**
     * Club fee
     *
     * @var float|null
     */
    public private(set) ?float $fee_competition = null;

    /**
     * Team fee
     *
     * @var float|null
     */
    public private(set) ?float $fee_event = null;

    /**
     * Total
     *
     * @var float|null
     */
    public private(set) ?float $total = null;

    /**
     * Construct class instance
     *
     * @param Charge_Hydration_DTO|null $dto Hydration DTO.
     */
    public function __construct( ?Charge_Hydration_DTO $dto = null ) {
        if ( is_null( $dto ) ) {
            return;
        }
        $this->set_id( $dto->id );
        $this->set_competition_id( $dto->competition_id );
        $this->set_season( $dto->season );
        $this->set_status( $dto->status );
        $this->set_date( $dto->date );
        $this->set_fee_competition( $dto->fee_competition );
        $this->set_fee_event( $dto->fee_event );
        $this->set_total( $dto->total );
    }

    /**
     * Create from a generic object (e.g. database row) via Charge_Factory.
     *
     * @param object $row Database row object.
     * @return self
     */
    public static function from_object( object $row ): self {
        return Charge_Factory::from_object( $row );
    }

    /**
     * Create from an array via Charge_Factory.
     *
     * @param array<string, mixed> $data Input data array.
     * @return self
     */
    public static function from_array( array $data ): self {
        return Charge_Factory::from_array( $data );
    }

    /**
     * Get charge ID.
     *
     * @return int|null
     */
    public function get_id(): ?int {
        return $this->id;
    }

    /**
     * Set charge ID.
     *
     * @param int|null $id Charge ID.
     * @return void
     */
    public function set_id( ?int $id ): void {
        $this->id = $id;
    }

    /**
     * Get competition ID.
     *
     * @return int|null
     */
    public function get_competition_id(): ?int {
        return $this->competition_id;
    }

    /**
     * Set competition ID.
     *
     * @param int|null $competition_id Competition ID.
     * @return void
     */
    public function set_competition_id( ?int $competition_id ): void {
        $this->competition_id = $competition_id;
    }

    /**
     * Get competition entity.
     *
     * @return object|null
     */
    public function get_competition(): ?object {
        return $this->competition;
    }

    /**
     * Set competition entity.
     *
     * @param object|null $competition Competition entity.
     * @return void
     */
    public function set_competition( ?object $competition ): void {
        $this->competition = $competition;
    }

    /**
     * Get season.
     *
     * @return string|null
     */
    public function get_season(): ?string {
        return $this->season;
    }

    /**
     * Set season.
     *
     * @param string|null $season Season.
     * @return void
     */
    public function set_season( ?string $season ): void {
        $this->season = $season;
    }

    /**
     * Get date.
     *
     * @return string|null
     */
    public function get_date(): ?string {
        return $this->date;
    }

    /**
     * Set date.
     *
     * @param string|null $date Date.
     * @return void
     */
    public function set_date( ?string $date ): void {
        $this->date = $date;
    }

    /**
     * Get charge status string.
     *
     * @return string
     */
    public function get_status(): string {
        return $this->status;
    }

    /**
     * Set charge status.
     *
     * @param string|Charge_Status $status Status value.
     * @return void
     */
    public function set_status( string|Charge_Status $status ): void {
        if ( $status instanceof Charge_Status ) {
            $this->status = $status->value;
            return;
        }
        $enum         = Charge_Status::try_from_value( $status );
        $this->status = $enum ? $enum->value : $status;
    }

    /**
     * Get status enum.
     *
     * @return Charge_Status|null
     */
    public function get_status_enum(): ?Charge_Status {
        return Charge_Status::try_from_value( $this->status );
    }

    /**
     * Set status enum.
     *
     * @param Charge_Status $status Status enum.
     * @return void
     */
    public function set_status_enum( Charge_Status $status ): void {
        $this->status = $status->value;
    }

    /**
     * Check if status is draft.
     *
     * @return bool
     */
    public function is_draft(): bool {
        return $this->get_status_enum()?->is_draft() ?? false;
    }

    /**
     * Check if status is final.
     *
     * @return bool
     */
    public function is_final(): bool {
        return $this->get_status_enum()?->is_final() ?? false;
    }

    /**
     * Get competition fee.
     *
     * @return float|null
     */
    public function get_fee_competition(): ?float {
        return $this->fee_competition;
    }

    /**
     * Get competition fee as Money value object.
     *
     * @param Currency|null $currency Currency.
     * @return Money|null
     */
    public function get_competition_fee_money( ?Currency $currency = null ): ?Money {
        if ( ! isset( $this->fee_competition ) ) {
            return null;
        }
        return Money::from_float( $this->fee_competition, $currency );
    }

    /**
     * Set club fee.
     *
     * @param float|int|string|Money|null $fee_competition Fee competition value.
     * @return void
     * @throws InvalidArgumentException If fee is negative.
     */
    public function set_fee_competition( float|int|string|Money|null $fee_competition ): void {
        $this->fee_competition = Fee_Normaliser::normalise( $fee_competition, 'Competition fee' );
    }

    /**
     * Get event fee.
     *
     * @return float|null
     */
    public function get_fee_event(): ?float {
        return $this->fee_event;
    }

    /**
     * Get event fee as Money value object.
     *
     * @param Currency|null $currency Currency.
     * @return Money|null
     */
    public function get_event_fee_money( ?Currency $currency = null ): ?Money {
        if ( ! isset( $this->fee_event ) ) {
            return null;
        }
        return Money::from_float( $this->fee_event, $currency );
    }

    /**
     * Set team fee.
     *
     * @param float|int|string|Money|null $fee_event Team fee value.
     * @return void
     * @throws InvalidArgumentException If fee is negative.
     */
    public function set_fee_event( float|int|string|Money|null $fee_event ): void {
        $this->fee_event = Fee_Normaliser::normalise( $fee_event, 'Event fee' );
    }

    /**
     * Get total fee.
     *
     * @return float|null
     */
    public function get_total(): ?float {
        return $this->total;
    }

    /**
     * Set total fee.
     *
     * @param float|int|string|Money|null $total Total fee value.
     * @return void
     * @throws InvalidArgumentException If total is negative.
     */
    public function set_total( float|int|string|Money|null $total ): void {
        $this->total = Fee_Normaliser::normalise( $total, 'Total fee' );
    }

    /**
     * Get total fee as Money value object.
     *
     * @param Currency|null $currency Currency.
     * @return Money|null
     */
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
}
