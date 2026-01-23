<?php
/**
 * Entry Form Validation API: Entry form validator class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Validate
 */

namespace Racketmanager\Services\Validator;

use DateMalformedStringException;
use DateTime;
use Racketmanager\Util\Util_Lookup;

/**
 * Class to implement the Entry form Validator object
 */
final class Validator_Entry_Form extends Validator {
    /**
     * Validate nonce
     *
     * @param string $nonce_key nonce key.
     * @return object $validation updated validation object.
     */
    public function nonce( string $nonce_key ): object {
        if ( !isset( $_POST['_wpnonce'] ) || !wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), $nonce_key ) ) {
            $this->error      = true;
            $this->err_flds[] = '';
            $this->err_msgs[] = __( 'Form has expired. Please refresh the page and resubmit', 'racketmanager' );
        }
        return $this;
    }

    /**
     * Validate if user logged in
     *
     * @return object updated validation object.
     */
    public function logged_in_entry(): object {
        $error_field   = 'clubId';
        $error_message = __( 'You must be logged in to submit an entry', 'racketmanager' );
        $this->set_errors( $error_field, $error_message );
        return $this;
    }

    /**
     * Validate club
     *
     * @param string|null $club_id club.
     *
     * @return object $validation updated validation object.
     */
    public function club_membership( ?string $club_id ): object {
        if ( ! $club_id ) {
            $error_field   = 'clubId';
            $error_message = __( 'Select the club you are a member of', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
        }
        return $this;
    }

    /**
     * Validate events
     *
     * @param array    $events array of events.
     * @param int|null $max_entries maximum number of entries.
     * @return object $validation updated validation object.
     */
    public function events_entry( array $events, ?int $max_entries = null ): object {
        $error_field = 'event';
        if ( empty( $events ) ) {
            $error_message = __( 'You must select a event to enter', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
        } elseif ( ! empty( $max_entries ) ) {
            if ( count( $events ) > $max_entries ) {
                $error_message = __( 'You have entered too many events', 'racketmanager' );
                $this->set_errors( $error_field, $error_message );
            }
        }
        return $this;
    }

    /**
     * Validate events missing teams
     *
     * @param array|int $events array of events with no teams.
     *
     * @return object $validation updated validation object.
     */
    public function events_missing_teams( array|null|int $events ): object {
        if ( ! empty( $events ) ) {
            foreach ( $events as $event_id ) {
                $error_field   = 'event-' . $event_id;
                $error_message = __( 'No teams selected for this event', 'racketmanager' );
                $this->set_errors( $error_field, $error_message );
            }
        }
        return $this;
    }

    /**
     * Validate events has teams
     *
     * @param int|null $team_id
     * @param null $event_id
     *
     * @return object $validation updated validation object.
     */
    public function events_has_teams( ?int $team_id, $event_id = null ): object {
        if ( empty( $team_id ) ) {
            $error_field   = 'event-' . $event_id;
            $error_message = __( 'No teams selected for this event', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
        }
        return $this;
    }

    /**
     * Validate number of courts available
     *
     * @param int $num_courts_available number of courts available.
     * @param array $court_data courts and teams.
     * @param string $match_day match day.
     * @param string $match_time match time.
     * @return object $validation updated validation object.
     */
    public function court_needs( int $num_courts_available, array $court_data, string $match_day, string $match_time ): object {
        $court_needs        = $court_data['courts'] / $court_data['teams'];
        $court_needs_by_day = $court_needs * ceil( $court_data['teams'] / 2 );
        $match_day_name     = Util_Lookup::get_match_day( $match_day );
        if ( $court_needs_by_day > $num_courts_available ) {
            $error_field = 'numCourtsAvailable';
            /* translators: %1$s: match day, %2$s: match time, %3$s: courts needed */
            $error_message = sprintf( __( 'There are not enough courts available for %1$s at %2$s. You need %3$s courts.', 'racketmanager' ), $match_day_name, $match_time, $court_needs_by_day );
            $this->set_errors( $error_field, $error_message );
        }
        return $this;
    }

    /**
     * Validate entry acceptance
     *
     * @param bool $acceptance acceptance indicator.
     * @return object $validation updated validation object.
     */
    public function entry_acceptance( bool $acceptance ): object {
        if ( empty( $acceptance ) ) {
            $error_field   = 'acceptance';
            $error_message = __( 'You must agree to the rules', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
        }
        return $this;
    }
    /**
     * Validate match time for overlap
     *
     * @param string $match_time match time.
     * @param string $schedule_time current scheduled match time.
     * @param string $field_ref field reference.
     * @return object $validation updated validation object.
     */
    public function match_overlap( string $match_time, string $schedule_time, string $field_ref ): object {
        $date_format = '!H:i:s';
        $start_time  = DateTime::createFromFormat( $date_format, $match_time );
        $error_field = 'matchtime-' . $field_ref;
        try {
            $start_time->modify( '-2 hours' );
            $end_time = DateTime::createFromFormat( $date_format, $match_time );
            $end_time->modify( '+2 hours' );
            $current_match_time = DateTime::createFromFormat( $date_format, $schedule_time );
            if ( $current_match_time > $start_time && $current_match_time < $end_time ) {
                $error_message = __( 'Match overlap', 'racketmanager' );
                $this->set_errors( $error_field, $error_message );
            }
        } catch ( DateMalformedStringException ) {
            $error_message = __( 'Invalid time', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
        }
        return $this;
    }

    /**
     * Validate tournament open
     *
     * @param object $tournament tournament object.
     * @return object $validation updated validation object.
     */
    public function tournament_open( object $tournament ): object {
        $error_field = 'event';
        if ( empty( $tournament->date_closing ) ) {
            $error_message = __( 'Tournament close date not set', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
        } else {
            if ( ! $tournament->is_open && ! $tournament->is_closed && ( ! current_user_can( 'manage_racketmanager' ) || ! $tournament->is_started ) ) {
                $error_message = __( 'Tournament not open for entries', 'racketmanager' );
                $this->set_errors( $error_field, $error_message );
            }
        }
        return $this;
    }
    /**
     * Validate competition open
     *
     * @param object $competition competition object.
     * @return object $validation updated validation object.
     */
    public function competition_open( object $competition ): object {
        if ( ! $competition->is_open ) {
            $error_field   = 'acceptance';
            $error_message = __( 'Competition not open for entries', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
        }
        return $this;
    }
    /**
     * Validate weekend match
     *
     * @param string $field_ref field reference.
     * @return object $validation updated validation object.
     */
    public function weekend_match( string $field_ref ): object {
        $error_field   = 'matchday-' . $field_ref;
        $error_message = __( 'A higher ranked team is already playing at the weekend', 'racketmanager' );
        $this->set_errors( $error_field, $error_message );
        return $this;
    }

    /**
     * Validate free slots
     *
     * @param string $slots slots available.
     * @return object $validation updated validation object.
     */
    public function free_slots( string $slots ): object {
        if ( $slots < 1 ) {
            $error_field   = 'event';
            $error_message = __( 'Weekend games not allowed when free weekday slots', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
        }
        return $this;
    }
}
