<?php
/**
 * Charge_Details_DTO API: Charge_Details_DTO class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain\DTO
 */

namespace Racketmanager\Domain\DTO\Finance;

use Racketmanager\Domain\Enums\Charge_Status;
use Racketmanager\Domain\Value_Objects\Currency;
use Racketmanager\Domain\Value_Objects\Money;
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
    public ?string $fee_competition;
    public ?string $fee_event;
    public string $competition_type;
    public bool $competition_is_team_entry;

    /**
     * Charge_Details_DTO constructor.
     *
     * @param stdClass $data
     */
    public function __construct( stdClass $data ) {
        $this->id               = $data->id;
        $this->competition_id   = $data->competition_id;
        $this->season           = $data->season;
        $this->status           = $data->status;
        $this->date             = $data->date;
        $this->fee_competition  = isset( $data->fee_competition ) ? (string) $data->fee_competition : null;
        $this->fee_event        = isset( $data->fee_event ) ? (string) $data->fee_event : null;
        $this->charge_name      = $data->season . ' ' . $data->competition_name;
        $this->competition_type = $data->competition_type;
        if ( 'tournament' === $data->competition_type ) {
            $this->competition_is_team_entry = false;
        } else {
            $this->competition_is_team_entry = true;
        }
    }

    public function get_competition_fee_money( ?Currency $currency = null ): Money {
        return Money::from_string( $this->fee_competition ?? '0', $currency );
    }

    public function get_event_fee_money( ?Currency $currency = null ): Money {
        return Money::from_string( $this->fee_event ?? '0', $currency );
    }

    public function get_total_money( ?Currency $currency = null ): Money {
        return $this->get_competition_fee_money( $currency )->add( $this->get_event_fee_money( $currency ) );
    }

    public function get_status_enum(): ?Charge_Status {
        return Charge_Status::try_from_value( $this->status );
    }

}
