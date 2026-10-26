<?php
declare( strict_types=1 );
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
     * Validated events list
     *
     * @var array
     */
    public array $validated_events = array();

    /**
     * Validate if user logged in
     *
     * @return object updated validation object.
     */
    public function logged_in_entry(): object {
        if ( ! is_user_logged_in() ) {
            $error_field   = 'clubId';
            $error_message = __( 'You must be logged in to submit an entry', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
        }
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
     * @param array      $events array of events.
     * @param int|null   $max_entries maximum number of entries.
     * @param array|null $allowed_event_ids allowed event ids.
     * @return object $validation updated validation object.
     */
    public function events_entry( array $events, ?int $max_entries = null, ?array $allowed_event_ids = null ): object {
        $this->validated_events = array();
        $error_field = 'event';
        if ( empty( $events ) ) {
            $error_message = __( 'You must select a event to enter', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
        } elseif ( null === $allowed_event_ids ) {
            $this->validated_events = $events;
        } else {
            $allowed_event_ids = array_map( 'intval', $allowed_event_ids );
            foreach ( $events as $event_key => $event_entry ) {
                $event_id = (int) $event_key;
                if ( $event_id < 1 || ! in_array( $event_id, $allowed_event_ids, true ) ) {
                    $error_message = __( 'One or more selected events are not available for this season', 'racketmanager' );
                    $this->set_errors( $error_field, $error_message );
                    continue;
                }
                $this->validated_events[ $event_key ] = $event_entry;
            }
        }
        if ( ! empty( $max_entries ) ) {
            $count = ! empty( $this->validated_events ) ? count( $this->validated_events ) : count( $events );
            if ( $count > $max_entries ) {
                $error_message = __( 'You have entered too many events', 'racketmanager' );
                $this->set_errors( $error_field, $error_message );
            }
        }
        return $this;
    }

    /**
     * Validate withdrawn team
     *
     * @param string $field_ref field reference.
     * @return object $validation updated validation object.
     */
    public function withdrawn_team( string $field_ref ): object {
        $error_field   = 'teamEvent-' . $field_ref;
        $error_message = __( 'A higher ranked team cannot be withdrawn if there are still active lower ranked teams', 'racketmanager' );
        $this->set_errors( $error_field, $error_message );
        return $this;
    }

    /**
     * Validate team withdrawn (alias)
     *
     * @param string $field_ref field reference.
     * @return object $validation updated validation object.
     */
    public function team_withdrawn( string $field_ref ): object {
        return $this->withdrawn_team( $field_ref );
    }

    /**
     * Validate team exists
     *
     * @param mixed $team team object or identifier.
     * @param string $field_ref field reference.
     * @return object $validation updated validation object.
     */
    public function team_exists( mixed $team, string $field_ref ): object {
        if ( empty( $team ) ) {
            $error_field   = 'teamEvent-' . $field_ref;
            $error_message = __( 'Team not found', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
        }
        return $this;
    }

    /**
     * Validate team duplicate / conflicting division
     *
     * @param string $field_ref field reference.
     * @return object $validation updated validation object.
     */
    public function team_duplicate( string $field_ref ): object {
        $error_field   = 'teamEvent-' . $field_ref;
        $error_message = __( 'Team is already registered in conflicting divisions', 'racketmanager' );
        $this->set_errors( $error_field, $error_message );
        return $this;
    }

    /**
     * Check player/partner event eligibility based on gender and age rules.
     *
     * @param object $player
     * @param object $event
     * @param int    $tournament_end_year
     *
     * @return array{eligible: bool, reason: string}
     */
    public static function check_player_event_eligibility( object $player, object $event, int $tournament_end_year ): array {
        $gender     = $player->gender ?? ( method_exists( $player, 'get_gender' ) ? $player->get_gender() : '' );
        $event_type = $event->type ?? ( method_exists( $event, 'get_type' ) ? $event->get_type() : '' );
        $age_limit  = $event->age_limit ?? null;
        $age_offset = $event->age_offset ?? 0;

        if ( 'M' === $gender ) {
            if ( str_starts_with( $event_type, 'W' ) || str_starts_with( $event_type, 'G' ) ) {
                return [ 'eligible' => false, 'reason' => 'gender_invalid' ];
            }
        } elseif ( 'F' === $gender ) {
            if ( str_starts_with( $event_type, 'M' ) || str_starts_with( $event_type, 'B' ) ) {
                return [ 'eligible' => false, 'reason' => 'gender_invalid' ];
            }
        } else {
            return [ 'eligible' => false, 'reason' => 'gender_invalid' ];
        }

        if ( empty( $age_limit ) || 'open' === $age_limit ) {
            return [ 'eligible' => true, 'reason' => '' ];
        }

        if ( $tournament_end_year <= 0 ) {
            return [ 'eligible' => false, 'reason' => 'missing_tournament_year' ];
        }

        $yob = $player->year_of_birth ?? ( method_exists( $player, 'get_year_of_birth' ) ? $player->get_year_of_birth() : 0 );
        if ( empty( $yob ) ) {
            return [ 'eligible' => false, 'reason' => 'no_age_specified' ];
        }

        $player_age = $tournament_end_year - (int) $yob;
        if ( (int) $age_limit >= 30 ) {
            $min_age = (int) $age_limit;
            if ( 'F' === $gender && ! empty( $age_offset ) ) {
                $min_age -= (int) $age_offset;
            }
            if ( $player_age < $min_age ) {
                return [ 'eligible' => false, 'reason' => 'too_young' ];
            }
        } elseif ( $player_age > (int) $age_limit ) {
            return [ 'eligible' => false, 'reason' => 'too_old' ];
        }

        return [ 'eligible' => true, 'reason' => '' ];
    }

    /**
     * Validate partner eligibility (gender and age)
     *
     * @param object $partner partner object.
     * @param object $event event object.
     * @param int $tournament_end_year end year of tournament.
     * @param string $err_field error field reference.
     * @return object $validation updated validation object.
     */
    public function partner_eligibility( object $partner, object $event, int $tournament_end_year, string $err_field ): object {
        $result = self::check_player_event_eligibility( $partner, $event, $tournament_end_year );
        if ( ! $result['eligible'] ) {
            $message = match ( $result['reason'] ) {
                'no_age_specified' => __( 'Partner has no age specified', 'racketmanager' ),
                'too_young'        => __( 'Partner is too young', 'racketmanager' ),
                'too_old'          => __( 'Partner is too old', 'racketmanager' ),
                default            => __( 'Partner is not eligible for this event', 'racketmanager' ),
            };
            $this->set_errors( $err_field, $message );
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
        $error_field = 'tournament';
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
        $error_message = __( 'Only the highest numbered team(s) can play at the weekend', 'racketmanager' );
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
