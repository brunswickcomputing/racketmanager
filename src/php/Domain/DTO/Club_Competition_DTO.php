<?php
/**
 * Club_Competition_DTO API: Club_Competition_DTO class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain
 */

namespace Racketmanager\Domain\DTO;

/**
 * Class to implement the Club Competition Data Transfer Object
 */
class Club_Competition_DTO {
    public int $id;
    public string $name;
    public string $shortcode;
    public int $num_teams;
    public int $num_players;

    public function __construct( $data ) {
        $this->id          = (int) $data->club_id;
        $this->name        = $data->club_name;
        $this->shortcode   = $data->club_shortcode;
        $this->num_teams   = (int) $data->num_teams;
        $this->num_players = (int) $data->num_players;
    }

}
