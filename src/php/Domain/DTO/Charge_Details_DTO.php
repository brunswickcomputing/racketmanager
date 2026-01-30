<?php
/**
 * Charge_Details_DTO API: Charge_Details_DTO class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain\DTO
 */

namespace Racketmanager\Domain\DTO;

use stdClass;

/**
 * Class to implement the Charges Details Data Transfer Object
 */
class Charge_Details_DTO {
    public int $id;
    public string $charge_name;
    public string $status;
    public int $competition_id;
    public string $season;
    public string $date;
    public int $fee_competition;
    public int $fee_event;
    public string $competition_type;
    public bool $competition_is_team_entry;

    /**
     * Team_Details_DTO constructor.
     *
     * @param stdClass $data
     */
    public function __construct( stdClass $data ) {
        $this->id               = $data->id;
        $this->competition_id   = $data->competition_id;
        $this->season           = $data->season;
        $this->status           = $data->status;
        $this->date             = $data->date;
        $this->fee_competition  = $data->fee_competition;
        $this->fee_event        = $data->fee_event;
        $this->charge_name      = $data->season . ' ' . $data->competition_name;
        $this->competition_type = $data->competition_type;
        if ( 'tournament' === $data->competition_type ) {
            $this->competition_is_team_entry = false;
        } else {
            $this->competition_is_team_entry = true;
        }
    }

}
