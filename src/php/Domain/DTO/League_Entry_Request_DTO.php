<?php
/**
 * League_Entry_Request_DTO API: League_Entry_Request_DTO class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain/DTO
 */

namespace Racketmanager\Domain\DTO;

use stdClass;

/**
 * Class to implement the League Entry Request Data Transfer Object
 */
readonly class League_Entry_Request_DTO {
    public ?int $competition_id;
    public ?string $season;
    public ?int $club_id;
    public ?string $comments;
    public bool $acceptance;
    public ?int $num_courts_available;
    public array $events_entered;
    /** @var int[] List of event IDs the user did NOT check */
    public array $missed_event_ids;

    /** @var array<int, int[]> [event_id => [missed_team_id, ...]] */
    public array $missed_team_ids;
    /** @var int[] Event IDs selected with zero teams */
    public array $empty_event_ids;

    /**
     * Cup_Entry_Request_DTO constructor.
     *
     * @param array $data
     */
    public function __construct( array $data ) {
        $this->competition_id       = isset( $data['competitionId'] ) ? intval( $data['competitionId'] ) : null;
        $this->season               = isset( $data['season'] ) ? sanitize_text_field( wp_unslash( $data['season'] ) ) : null;
        $this->club_id              = isset( $data['clubId'] ) ? intval( $data['clubId'] ) : null;
        $this->comments             = isset( $data['commentDetails'] ) ? sanitize_text_field( wp_unslash( $data['commentDetails'] ) ) : null;
        $this->num_courts_available = isset( $data['numCourtsAvailable'] ) ? intval( $data['numCourtsAvailable'] ) : null;
        $this->acceptance           = isset( $data['acceptance'] );
        $this->events_entered       = $this->parse_submissions( $data );

        // 1. Identify all possible events from the competition_events string
        $all_possible_events = array_filter( array_map( 'absint', explode( ',', (string) ( $data['competition_events'] ?? '' ) ) ) );

        // 2. Identify which ones were actually selected
        $selected_event_ids = array_map( 'absint', (array) ( $data['event'] ?? [] ) );

        // 3. Find the difference (Events not entered)
        $this->missed_event_ids = array_diff( $all_possible_events, $selected_event_ids );

        // 4. Find missed teams within selected events
        $this->missed_team_ids = $this->identify_missed_teams( $data, $selected_event_ids );
        // 5. Get IDs of all events the user actually CHECKED
        $selected_event_ids = array_map( 'absint', (array) ( $data['event'] ?? [] ) );

        // 6. Identify which events actually have team data organized
        $events_with_teams = array_keys( $this->events_entered );

        // 7. The difference is events that were selected but are empty
        $this->empty_event_ids = array_values( array_diff( $selected_event_ids, $events_with_teams ) );
    }

    /**
     * Parse input data from the event array
     *
     * @param array $data
     *
     * @return array
     */
    private function parse_submissions( array $data ): array {
        $result = [];

        // 1. 'event' contains ONLY the IDs of events the user checked.
        $selected_event_ids = array_map( 'absint', (array) ( $data['event'] ?? [] ) );

        foreach ( $selected_event_ids as $event_id ) {
            // 2. 'teamEvent' contains ONLY the team IDs checked for this event.
            $selected_teams = (array) ( $data['teamEvent'][ $event_id ] ?? [] );

            foreach ( $selected_teams as $team_id ) {
                $team_id = absint( $team_id );

                // 3. Map the data into a clean object using a helper
                $result[ $event_id ][ $team_id ] = $this->map_row( $data, $event_id, $team_id );
            }
        }

        return $result;
    }

    /**
     * Map input data for a team within an event
     *
     * @param array $data
     * @param int $e_id
     * @param int $t_id
     *
     * @return stdClass
     */
    private function map_row( array $data, int $e_id, int $t_id ): stdClass {
        $row = new stdClass();

        // Internal helper to fetch and sanitize nested POST values
        $fetch = function ( $key, $is_int = false ) use ( $data, $e_id, $t_id ) {
            $val = $data[ $key ][ $e_id ][ $t_id ] ?? '';
            $val = wp_unslash( $val );

            return $is_int ? absint( $val ) : sanitize_text_field( $val );
        };

        $row->team_name    = $fetch( 'teamEventTitle' );
        $row->league_id    = $fetch( 'teamEventLeague', true );
        $row->captain_name = $fetch( 'captain' );
        $row->captain_id   = $fetch( 'captainId', true );
        $row->phone        = $fetch( 'contactno' );
        $row->email        = sanitize_email( $data['contactemail'][ $e_id ][ $t_id ] ?? '' );
        $row->match_day    = $fetch( 'matchday', true );
        $row->match_time   = $fetch( 'matchtime' );

        return $row;
    }

    /**
     * Identify teams not selected
     *
     * @param array $data
     * @param array $selected_events
     *
     * @return array
     */
    private function identify_missed_teams( array $data, array $selected_events ): array {
        $missed = [];

        foreach ( $selected_events as $event_id ) {
            // event_teams contains ALL previously entered teams for this event
            $all_teams_for_event = array_filter( array_map( 'absint', explode( ',', (string) ( $data['event_teams'][ $event_id ] ?? '' ) ) ) );

            // teamEvent contains ONLY checked teams
            $selected_teams_for_event = array_map( 'absint', (array) ( $data['teamEvent'][ $event_id ] ?? [] ) );

            // Teams that exist for this event but were unchecked
            $diff = array_diff( $all_teams_for_event, $selected_teams_for_event );

            if ( ! empty( $diff ) ) {
                $missed[ $event_id ] = array_values( $diff );
            }
        }

        return $missed;
    }

}
