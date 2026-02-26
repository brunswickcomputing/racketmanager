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
    public ?int $fee_competition;
    public int|float $fee_events;
    public int|null|float $fee;
    public int|null|float $total;

    /**
     * Tournament_Invoice_Details_DTO constructor.
     *
     * @param int $id
     * @param string $name
     * @param array $events
     * @param int|null $event_fee
     * @param int|null $competition_fee
     * @param int $paid
     */
    public function __construct(
        public int $id,
        public string $name,
        public array $events = array(),
        ?int $event_fee = 0,
        ?int $competition_fee = 0,
        public int $paid = 0
    ) {
        $this->num_teams       = count( $events );
        $this->fee_competition = $competition_fee;
        $this->fee_events      = $event_fee * $this->num_teams;
        $this->fee             = $this->fee_competition + $this->fee_events;
        $this->total           = $this->fee - $this->paid;
    }

}
