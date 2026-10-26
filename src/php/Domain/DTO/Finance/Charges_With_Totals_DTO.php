<?php
declare( strict_types=1 );
/**
 * Charges_With_Totals_DTO API: Charges_With_Totals_DTO class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain\DTO
 */

namespace Racketmanager\Domain\DTO\Finance;

use stdClass;

/**
 * Class to implement the Charges with Totals Data Transfer Object
 */
class Charges_With_Totals_DTO {
    public int $id;
    public string $name;
    public string $status;
    public int|string|float|null $total;
    public ?string $fee_competition;
    public ?string $fee_event;

    /**
     * Charges_With_Totals_DTO constructor.
     *
     * @param stdClass $data
     */
    public function __construct( stdClass $data ) {
        $this->id              = (int) $data->id;
        $this->name            = $data->season . ' ' . $data->name;
        $this->status          = (string) $data->status;
        $this->fee_competition = isset( $data->fee_competition ) ? (string) $data->fee_competition : null;
        $this->fee_event       = isset( $data->fee_event ) ? (string) $data->fee_event : null;
        $this->total           = isset( $data->total_invoice_value ) ? (string) $data->total_invoice_value : ( isset( $data->total ) ? (string) $data->total : null );
    }

}
