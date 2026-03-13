<?php

namespace Racketmanager\Domain;

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
     * @var string
     */
    public string $season;
    /**
     * Competition id
     *
     * @var int
     */
    public int $competition_id;
    /**
     * Competition
     *
     * @var object|null
     */
    public null|object $competition;
    /**
     * Status
     *
     * @var string
     */
    public string $status;
    /**
     * Date
     *
     * @var string
     */
    public string $date;
    /**
     * Club fee
     *
     * @var null|float
     */
    public null|float $fee_competition;
    /**
     * Team fee
     *
     * @var null|float
     */
    public null|float $fee_event;
    /**
     * Total
     *
     * @var float
     */
    public float $total;

    /**
     * Construct class instance
     *
     * @param object|null $charges charges object.
     */
    public function __construct( ?object $charges = null ) {
        if ( is_null( $charges ) ) {
            return;
        }
        $this->id              = $charges->id ?? null;
        $this->competition_id  = $charges->competition_id;
        $this->season          = $charges->season;
        $this->status          = $charges->status;
        $this->date            = $charges->date ?? null;
        $this->fee_competition = $charges->fee_competition ?? null;
        $this->fee_event       = $charges->fee_event ?? null;
    }

    public function get_id(): int|null {
        return $this->id;
    }

    public function set_id( int $insert_id ): void {
        $this->id = $insert_id;
    }

    public function get_competition_id(): int {
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

    public function get_season(): string {
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

    public function get_date(): string {
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
     * @param string $status status value.
     */
    public function set_status( string $status ): void {
        $this->status = $status;
    }

    public function get_fee_competition(): float|null {
        return $this->fee_competition;
    }

    /**
     * Set club fee
     *
     * @param float|null $fee_competition
     */
    public function set_fee_competition( float|null $fee_competition ): void {
        $this->fee_competition = number_format( $fee_competition, 2, '.', '' );
    }

    public function get_fee_event(): float|null {
        return $this->fee_event;
    }

    /**
     * Set team fee
     *
     * @param float|null $fee_event team fee value.
     */
    public function set_fee_event( float|null $fee_event ): void {
        $this->fee_event = number_format( $fee_event, 2, '.', '' );
    }

}
