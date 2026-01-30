<?php
/**
 * Charges_With_Totals_DTO API: Charges_With_Totals_DTO class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain\DTO
 */

namespace Racketmanager\Domain\DTO;

use stdClass;

/**
 * Class to implement the Charges with Totals Data Transfer Object
 */
class Charges_With_Totals_DTO {
    public int $id;
    public string $name;
    public string $status;
    public int $total;
    public ?int $fee_competition;
    public ?int $fee_event;

    /**
     * Team_Details_DTO constructor.
     *
     * @param stdClass $data
     */
    public function __construct( stdClass $data ) {
        $this->id              = $data->id;
        $this->name            = $data->season . ' ' . $data->name;
        $this->status          = $data->status;
        $this->fee_competition = $data->fee_competition;
        $this->fee_event       = $data->fee_event;
        $this->total           = $data->total_invoice_value;
    }

}
