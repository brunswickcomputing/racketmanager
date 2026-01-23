<?php
/**
 * Event_Details_DTO API: Event_Details_DTO class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain
 */

namespace Racketmanager\Domain\DTO;

/**
 * Class to implement the Event Details Data Transfer Object
 */
class Event_Details_DTO {
    public int $event_id;
    public string $event_name;
    public string $format;
    public int $num_leagues;
    public int $num_teams;
    public int $num_clubs;
    public int $num_players;
    public mixed $age_limit;
    public mixed $age_offset;

    public function __construct( $data ) {
        $settings          = maybe_unserialize( $data->settings );
        $this->event_id    = (int) $data->event_id;
        $this->event_name  = $data->event_name;
        $this->format      = $data->format;
        $this->age_limit   = $settings['age_limit'] ?? null;
        $this->age_offset  = $settings['age_offset'] ?? null;
        $this->num_leagues = (int) $data->num_leagues;
        $this->num_teams   = (int) $data->num_teams;
        $this->num_clubs   = (int) $data->num_clubs;
        $this->num_players = (int) $data->num_players;
    }

}
