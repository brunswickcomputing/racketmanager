<?php
/**
 * Tournament_Invoice_Details_DTO API: Tournament_Invoice_Details_DTO class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain/DTO
 */

namespace Racketmanager\Domain\DTO\Tournament;

use stdClass;

/**
 * Class to implement the Tournament Invoice Details Data Transfer Object
 */
readonly class Tournament_Invoice_Details_DTO {
    public int $num_teams;
    public ?float $fee_competition;
    public float $fee_events;
    public float $fee;
    public float $total;

    /**
     * Tournament_Invoice_Details_DTO constructor.
     *
     * @param int $id
     * @param string $name
     * @param array $events
     * @param int|float|null $event_fee
     * @param int|float|null $competition_fee
     * @param int|float $paid
     */
    public function __construct(
        public int $id,
        public string $name,
        public array $events = array(),
        int|float|null $event_fee = 0,
        int|float|null $competition_fee = 0,
        public float $paid = 0
    ) {
        $this->num_teams       = count( $events );
        $this->fee_competition = null === $competition_fee ? null : (float) $competition_fee;
        $this->fee_events      = (float) ( $event_fee ?? 0 ) * $this->num_teams;
        $this->fee             = ( $this->fee_competition ?? 0 ) + $this->fee_events;
        $this->total           = $this->fee - $this->paid;
    }

}
