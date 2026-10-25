<?php
/**
 * Cup_Entry_Request_DTO API: Cup_Entry_Request_DTO class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain/DTO
 */

namespace Racketmanager\Domain\DTO\Competition;

use stdClass;

/**
 * Class to implement the Cup Entry Request Data Transfer Object
 */
readonly class Cup_Entry_Request_DTO {
    public ?int $competition_id;
    public ?string $season;
    public ?int $club_id;
    public bool $acceptance;
    public ?string $comments;
    /** @var array<int, stdClass> Map of [event_id] => team_object */
    public array $events_entered;

    /**
     * Cup_Entry_Request_DTO constructor.
     *
     * @param array $data
     * @param int|null $competition_id
     * @param string|null $season
     * @param int|null $club_id
     * @param bool $acceptance
     * @param string|null $comments
     * @param array<int, stdClass> $events_entered
     */
    public function __construct(
        array $data = [],
        ?int $competition_id = null,
        ?string $season = null,
        ?int $club_id = null,
        bool $acceptance = false,
        ?string $comments = null,
        array $events_entered = []
    ) {
        if ( ! empty( $data ) ) {
            $this->competition_id = isset( $data['competitionId'] ) ? absint( $data['competitionId'] ) : ( isset( $data['competition_id'] ) ? absint( $data['competition_id'] ) : $competition_id );
            $this->season         = isset( $data['season'] ) ? sanitize_text_field( wp_unslash( $data['season'] ) ) : $season;
            $this->club_id        = isset( $data['clubId'] ) ? absint( $data['clubId'] ) : ( isset( $data['club_id'] ) ? absint( $data['club_id'] ) : $club_id );
            $this->comments       = isset( $data['commentDetails'] ) ? sanitize_textarea_field( wp_unslash( $data['commentDetails'] ) ) : ( isset( $data['comments'] ) ? sanitize_textarea_field( wp_unslash( $data['comments'] ) ) : $comments );
            $this->acceptance     = isset( $data['acceptance'] ) ? (bool) $data['acceptance'] : $acceptance;

            $selected_event_ids   = array_map( 'absint', (array) ( $data['event'] ?? [] ) );
            $this->events_entered = ! empty( $selected_event_ids ) ? $this->parse_cup_data( $data, $selected_event_ids ) : $events_entered;
        } else {
            $this->competition_id = $competition_id;
            $this->season         = $season;
            $this->club_id        = $club_id;
            $this->acceptance     = $acceptance;
            $this->comments       = $comments;
            $this->events_entered = $events_entered;
        }
    }

    /**
     * Parse cup data from input
     *
     * @param array $data
     * @param array $selected_events
     *
     * @return array
     */
    private function parse_cup_data( array $data, array $selected_events ): array {
        $organized = [];

        foreach ( $selected_events as $event_id ) {
            // Map the data into a clean object using the event_id as the primary key
            $organized[ $event_id ] = $this->map_cup_row( $data, $event_id );
        }

        return $organized;
    }

    /**
     * Map input data for a team within an event
     *
     * @param array $data
     * @param int $e_id
     *
     * @return stdClass
     */
    private function map_cup_row( array $data, int $e_id ): stdClass {
        $row = new stdClass();

        // Internal helper to fetch and sanitize nested POST values [field][event]
        $fetch = function ( $key, $is_int = false ) use ( $data, $e_id ) {
            $val = $data[ $key ][ $e_id ] ?? '';
            $val = wp_unslash( $val );

            return $is_int ? absint( $val ) : sanitize_text_field( $val );
        };

        $row->team_id    = $fetch( 'team', true );
        $row->captain    = $fetch( 'captain' );
        $row->captain_id = $fetch( 'captainId', true );
        $row->phone      = $fetch( 'contactno' );
        $row->email      = sanitize_email( $data['contactemail'][ $e_id ] ?? '' );
        $row->match_day  = $fetch( 'matchday', true );
        $row->match_time = $fetch( 'matchtime' );

        return $row;
    }

}
