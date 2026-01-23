<?php
/**
 * Competition_Overview_DTO API: Competition_Overview_DTO class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain
 */

namespace Racketmanager\Domain\DTO;

/**
 * Class to implement the Competition Overview Data Transfer Object
 */
class Competition_Overview_DTO {
    public int $id;
    public string $name;
    public int $num_events;
    public int $num_teams;
    public int $num_clubs;
    public int $num_players;
    public ?string $competition_code;
    public ?string $grade;

    public function __construct( $data ) {
        $settings               = json_decode( $data->settings, true );
        $this->id               = (int) $data->id;
        $this->name             = $data->name;
        $this->competition_code = $settings['competition_code'] ?? null;
        $this->grade            = $settings['grade'] ?? null;
        $this->num_events       = (int) $data->num_events;
        $this->num_teams        = (int) $data->num_teams;
        $this->num_clubs        = (int) $data->num_clubs;
        $this->num_players      = (int) $data->num_players;
    }

}
