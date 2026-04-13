<?php

namespace Racketmanager\Services\Export\DTO;

class Export_Criteria {
    public ?int $league_id;
    public ?int $competition_id;
    public mixed $season;
    public ?int $club_id;
    public ?int $team_id;
    public mixed $date_from;
    public mixed $date_to;
    public mixed $format;

    /**
     * @param array $args Initial properties.
     */
    public function __construct( array $args = array() ) {
        $this->league_id      = isset( $args['league_id'] ) ? (int) $args['league_id'] : null;
        $this->competition_id = isset( $args['competition_id'] ) ? (int) $args['competition_id'] : null;
        $this->season         = isset( $args['season'] ) ? sanitize_text_field( $args['season'] ) : null;
        $this->club_id        = isset( $args['club_id'] ) ? (int) $args['club_id'] : null;
        $this->team_id        = isset( $args['team_id'] ) ? (int) $args['team_id'] : null;
        $this->date_from      = isset( $args['date_from'] ) ? sanitize_text_field( $args['date_from'] ) : null;
        $this->date_to        = isset( $args['date_to'] ) ? sanitize_text_field( $args['date_to'] ) : null;
        $this->format         = isset( $args['format'] ) ? sanitize_text_field( $args['format'] ) : null;
    }
}
