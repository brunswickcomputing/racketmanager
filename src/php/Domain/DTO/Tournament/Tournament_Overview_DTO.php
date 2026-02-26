<?php
/**
 * Tournament\Tournament_Overview_DTO API: Tournament\Tournament_Overview_DTO class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain
 */

namespace Racketmanager\Domain\DTO\Tournament;

/**
 * Class to implement the Competition Overview Data Transfer Object
 */
class Tournament_Overview_DTO {
    public int $id;
    public string $name;
    public string $season;
    public int $num_events;
    public int $num_entries;
    public ?string $competition_code;
    public string $competition_name;
    public string $age_group;
    public ?string $date_end;
    public ?string $date_closing;
    public ?string $date_withdrawal;
    public ?string $date_open;
    public ?string $date_start;
    public ?string $venue_name;
    public string $phase;

    public function __construct( $data ) {
        $this->id               = $data->id;
        $this->name             = $data->tournament_name;
        $this->season           = $data->season;
        $this->competition_name = $data->competition_name;
        $this->competition_code = $data->competition_code;
        $this->age_group        = $data->age_group;
        $this->date_end         = $data->date;
        $this->date_closing     = $data->date_closing;
        $this->date_withdrawal  = $data->date_withdrawal;
        $this->date_open        = $data->date_open;
        $this->date_start       = $data->date_start;
        $this->venue_name       = $data->venue_name;
        $this->phase            = $data->phase;
        $this->num_events       = $data->num_events;
        $this->num_entries      = $data->num_entries;
    }

}
