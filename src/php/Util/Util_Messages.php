<?php
/**
 * Util_Messages API: Util_Messages class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Util
 */

namespace Racketmanager\Util;

class Util_Messages {
    /**
     * Set the Club not found message
     *
     * @param $club_id
     *
     * @return string
     */
    public static function club_not_found( $club_id = null ): string {
        if ( $club_id ) {
            return sprintf( __( 'Club with ID %s not found', 'racketmanager' ), $club_id );
        } else {
            return __( 'Club not found', 'racketmanager' );
        }
    }

    /**
     * Set the Team not found message
     *
     * @param $team_id
     *
     * @return string
     */
    public static function team_not_found( $team_id = null ): string {
        if ( $team_id ) {
            return sprintf( __( 'Team with ID %s not found', 'racketmanager' ), $team_id );
        } else {
            return __( 'Team not found', 'racketmanager' );
        }
    }

    /**
     * Set the Event not found message
     *
     * @param $event_id
     *
     * @return string
     */
    public static function event_not_found( $event_id = null ): string {
        if ( $event_id ) {
            return sprintf( __( 'Event with ID %s not found', 'racketmanager' ), $event_id );
        } else {
            return __( 'Event not found', 'racketmanager' );
        }
    }

    /**
     * Set the invalid team id message
     *
     * @return string
     */
    public static function invalid_team_id(): string {
        return __( 'Invalid team ID', 'racketmanager' );
    }

}
