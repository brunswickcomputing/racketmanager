<?php
/**
 * Team_Competition_DTO API: Team_Competition_DTO class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain
 */

namespace Racketmanager\Domain\DTO;

/**
 * Class to implement the Team Competition Data Transfer Object
 */
class Team_Competition_DTO {
    public int $team_id;
    public string $team_name;
    public string $league_name;
    public string $club_shortcode;
    public int $num_players;
    public int $club_id;

    public function __construct( $data ) {
        $this->team_id        = (int) $data->team_id;
        $this->team_name      = $data->team_name;
        $this->league_name    = $data->league_name;
        $this->club_id        = (int) $data->club_id;
        $this->club_shortcode = $data->club_shortcode;
        $this->num_players    = (int) $data->num_players;
    }

}
