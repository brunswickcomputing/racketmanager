<?php
/**
 * Entry Form Validation API: Entry form validator class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Validate
 */

namespace Racketmanager;

use DateMalformedStringException;
use DateTime;

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
        $this->error      = true;
        $this->err_flds[] = 'clubId';
        $this->err_msgs[] = __( 'You must be logged in to submit an entry', 'racketmanager' );
        return $this;
    }

    /**
     * Validate club
     *
     * @param string|null $club club.
     *
     * @return object $validation updated validation object.
     */
    public function club( ?string $club ): object {
        if ( ! $club ) {
            $this->error      = true;
            $this->err_flds[] = 'clubId';
            $this->err_msgs[] = __( 'Select the club you are a member of', 'racketmanager' );
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
    public function events_entry( array $events, int $max_entries = null ): object {
        if ( empty( $events ) ) {
            $this->error      = true;
            $this->err_flds[] = 'event';
            $this->err_msgs[] = __( 'You must select a event to enter', 'racketmanager' );
        } elseif ( ! empty( $max_entries ) ) {
            if ( count( $events ) > $max_entries ) {
                $this->error      = true;
                $this->err_flds[] = 'event';
                $this->err_msgs[] = __( 'You have entered too many events', 'racketmanager' );
            }
        }
        return $this;
    }

    /**
     * Validate teams
     *
     * @param array|string|null $teams array of teams.
     * @param string $field_ref field reference.
     * @param string $field_name field name.
     * @return object $validation updated validation object.
     */
    public function teams( array|string|null $teams, string $field_ref, string $field_name ): object {
        if ( empty( $teams ) ) {
            $this->error      = true;
            $this->err_flds[] = 'event-' . $field_ref;
            /* translators: %s: competition name */
            $this->err_msgs[] = sprintf( __( 'No teams selected for %s', 'racketmanager' ), $field_name );
        }
        return $this;
    }

    /**
     * Validate number of courts available
     *
     * @param int $num_courts_available number of courts available.
     * @return object $validation updated validation object.
     */
    public function num_courts_available( int $num_courts_available ): object {
        if ( empty( $num_courts_available ) ) {
            $this->error      = true;
            $this->err_flds[] = 'numCourtsAvailable';
            /* translators: %s: competition name */
            $this->err_msgs[] = __( 'You must specify the number of courts available', 'racketmanager' );
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
        $court_needs = $court_data['courts'] / $court_data['teams'];
        $court_needs_by_day = $court_needs * ceil( $court_data['teams'] / 2 );
        $match_day_name = Racketmanager_Util::get_match_day( $match_day );
        if ( $court_needs_by_day > $num_courts_available ) {
            $this->error      = true;
            $this->err_flds[] = 'numCourtsAvailable';
            /* translators: %1$s: match day,  %2$s: match time, %3$s: courts needed */
            $this->err_msgs[] = sprintf( __( 'There are not enough courts available for %1$s at %2$s. You need %3$s courts.', 'racketmanager' ), $match_day_name, $match_time, $court_needs_by_day );
        }
        return $this;
    }

    /**
     * Validate entry acceptance
     *
     * @param string $acceptance acceptance indicator.
     * @return object $validation updated validation object.
     */
    public function entry_acceptance( string $acceptance ): object {
        if ( empty( $acceptance ) ) {
            $this->error      = true;
            $this->err_flds[] = 'acceptance';
            $this->err_msgs[] = __( 'You must agree to the rules', 'racketmanager' );
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
     * @throws DateMalformedStringException
     */
    public function match_overlap( string $match_time, string $schedule_time, string $field_ref ): object {
        $date_format = '!H:i:s';
        $start_time = DateTime::createFromFormat( $date_format, $match_time );
        $start_time->modify( '-2 hours' );
        $end_time = DateTime::createFromFormat( $date_format, $match_time );
        $end_time->modify( '+2 hours' );
        $current_match_time = DateTime::createFromFormat( $date_format, $schedule_time );
        if ( $current_match_time > $start_time && $current_match_time < $end_time ) {
            $this->error      = true;
            $this->err_flds[] = 'matchtime-' . $field_ref;
            $this->err_msgs[] = __( 'Match overlap', 'racketmanager' );
        }
        return $this;
    }

    /**
     * Validate partner details
     *
     * @param int $partner partner.
     * @param string $field_ref field reference.
     * @param string|null $field_name field name.
     * @param object $event event object.
     * @param string $season season name.
     * @param int $player_id player id.
     * @param string $date_end end date of competition.
     * @return object $validation updated validation object.
     */
    public function partner( int $partner, string $field_ref, ?string $field_name, object $event, string $season, int $player_id, string $date_end ): object {
        if ( empty( $field_name ) ) {
            $err_flds = 'partner';
        } else {
            $err_flds = 'partner-' . $field_ref;
        }
        if ( empty( $partner ) ) {
            $this->error      = true;
            $this->err_flds[] = $err_flds;
            $this->err_msgs[] = __( 'Partner not selected', 'racketmanager' );
        } else {
            $partner_found = false;
            $partner_teams = $event->get_teams(
                array(
                    'player' => $partner,
                    'season' => $season,
                )
            );
            foreach ( $partner_teams as $partner_team ) {
                if ( ! in_array( $player_id, $partner_team->player_id, true ) ) {
                    $partner_found = true;
                }
            }
            if ( $partner_found ) {
                $this->error      = true;
                $this->err_flds[] = $err_flds;
                $this->err_msgs[] = __( 'Partner is in another team in this event', 'racketmanager' );
            } else {
                $this->validate_partner_age( $partner, $event, $err_flds, $date_end );
            }
        }
        return $this;
    }
    private function validate_partner_age( $partner_id, $event, $err_flds, $date_end ): void {
        if ( empty( $event->age_limit ) || 'open' === $event->age_limit ) {
            return;
        }
        $partner = get_player( $partner_id );
        if ( ! $partner ) {
            $this->error      = true;
            $this->err_flds[] = $err_flds;
            $this->err_msgs[] = __( 'Partner not found', 'racketmanager' );
        }
        $partner_age = substr( $date_end, 0, 4 ) - intval( $partner->year_of_birth );
        if ( empty( $partner->age ) ) {
            $this->error      = true;
            $this->err_flds[] = $err_flds;
            $this->err_msgs[] = __( 'Partner has no age specified', 'racketmanager' );
         } elseif ( $event->age_limit >= 30 ) {
            if ( 'F' === $partner->gender && ! empty( $event->age_offset ) ) {
                $age_limit = $event->age_limit - $event->age_offset;
            } else {
                $age_limit = $event->age_limit;
            }
            if ( $partner_age < $age_limit ) {
                $this->error      = true;
                $this->err_flds[] = $err_flds;
                $this->err_msgs[] = __( 'Partner is too young', 'racketmanager' );
            }
        } elseif ( $partner_age > $event->age_limit ) {
            $this->error      = true;
            $this->err_flds[] = $err_flds;
            $this->err_msgs[] = __( 'Partner is too old', 'racketmanager' );
        }
    }
    /**
     * Validate tournament open
     *
     * @param object $tournament tournament object.
     * @return object $validation updated validation object.
     */
    public function tournament_open( object $tournament ): object {
        if ( empty( $tournament->date_closing ) ) {
            $this->error      = true;
            $this->err_flds[] = 'event';
            $this->err_msgs[] = __( 'Tournament close date not set', 'racketmanager' );
        } else {
            if ( !$tournament->is_open && !$tournament->is_closed ) {
                $this->error      = true;
                $this->err_flds[] = 'event';
                $this->err_msgs[] = __( 'Tournament not open for entries', 'racketmanager' );
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
            $this->error      = true;
            $this->err_flds[] = 'acceptance';
            $this->err_msgs[] = __( 'Competition not open for entries', 'racketmanager' );
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
        $this->error      = true;
        $this->err_flds[] = 'matchday-' . $field_ref;
        $this->err_msgs[] = __( 'A higher ranked team is already playing at the weekend', 'racketmanager' );
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
            $this->error      = true;
            $this->err_flds[] = 'event';
            $this->err_msgs[] = __( 'Weekend games not allowed when free weekday slots', 'racketmanager' );
        }
        return $this;
    }
}
