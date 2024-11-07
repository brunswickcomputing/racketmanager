<?php
/**
 * Entry Form Validation API: Entry form validator class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Validate
 */

namespace Racketmanager;

use DateTime;

/**
 * Class to implement the Entry form Validator object
 */
final class Racketmanager_Validator_Entry_Form extends Racketmanager_Validator {
	/**
	 * Validate nonce
	 *
	 * @param string $nonce_key nonce key.
	 * @return object $validation updated validation object.
	 */
	public function nonce( $nonce_key ) {
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), $nonce_key ) ) {
			$this->error                          = true;
			$this->error_field[ $this->error_id ] = '';
			$this->error_msg[ $this->error_id ]   = __( 'Form has expired. Please refresh the page and resubmit', 'racketmanager' );
			++$this->error_id;
		}
		return $this;
	}
	/**
	 * Validate if user logged in
	 *
	 * @return object updated validation object.
	 */
	public function logged_in_entry() {
		$this->error                          = true;
		$this->error_field[ $this->error_id ] = 'affiliatedclub';
		$this->error_msg[ $this->error_id ]   = __( 'You must be logged in to submit an entry', 'racketmanager' );
		++$this->error_id;
		return $this;
	}

	/**
	 * Validate club
	 *
	 * @param string $club club.
	 * @return object $validation updated validation object.
	 */
	public function club( $club ) {
		if ( ! $club ) {
			$this->error                          = true;
			$this->error_field[ $this->error_id ] = 'affiliatedclub';
			$this->error_msg[ $this->error_id ]   = __( 'Select the club you are a member of', 'racketmanager' );
			++$this->error_id;
		}
		return $this;
	}
	/**
	 * Validate events
	 *
	 * @param array $events array of events.
	 * @return object $validation updated validation object.
	 */
	public function events_entry( $events ) {
		if ( empty( $events ) ) {
			$this->error                          = true;
			$this->error_field[ $this->error_id ] = 'event';
			$this->error_msg[ $this->error_id ]   = __( 'You must select a event to enter', 'racketmanager' );
			++$this->error_id;
		}
		return $this;
	}
	/**
	 * Validate teams
	 *
	 * @param array  $teams array of teams.
	 * @param string $field_ref field reference.
	 * @param string $field_name field name.
	 * @return object $validation updated validation object.
	 */
	public function teams( $teams, $field_ref, $field_name ) {
		if ( empty( $teams ) ) {
			$this->error                          = true;
			$this->error_field[ $this->error_id ] = 'event-' . $field_ref;
			/* translators: %s: competition name */
			$this->error_msg[ $this->error_id ] = sprintf( __( 'No teams selected for %s', 'racketmanager' ), $field_name );
			++$this->error_id;
		}
		return $this;
	}
	/**
	 * Validate number of courts available
	 *
	 * @param int $num_courts_available number of courts available.
	 * @return object $validation updated validation object.
	 */
	public function num_courts_available( $num_courts_available ) {
		if ( empty( $num_courts_available ) ) {
			$this->error                          = true;
			$this->error_field[ $this->error_id ] = 'numCourtsAvailable';
			/* translators: %s: competition name */
			$this->error_msg[ $this->error_id ] = __( 'You must specify the number of courts available', 'racketmanager' );
			++$this->error_id;
		}
		return $this;
	}
	/**
	 * Validate number of courts available
	 *
	 * @param int    $num_courts_available number of courts available.
	 * @param array  $court_data courts and teams.
	 * @param string $match_day match day.
	 * @param string $match_time match time.
	 * @return object $validation updated validation object.
	 */
	public function court_needs( $num_courts_available, $court_data, $match_day, $match_time ) {
		$court_needs        = $court_data['courts'] / $court_data['teams'];
		$court_needs_by_day = $court_needs * ceil( $court_data['teams'] / 2 );
		$match_day_name     = Racketmanager_Util::get_match_day( $match_day );
		if ( $court_needs_by_day > $num_courts_available ) {
			$this->error                          = true;
			$this->error_field[ $this->error_id ] = 'numCourtsAvailable';
			/* translators: %1$s: match day,  %2$s: match time, %3$s: courts needed */
			$this->error_msg[ $this->error_id ] = sprintf( __( 'There are not enough courts available for %1$s at %2$s. You need %3$s courts.', 'racketmanager' ), $match_day_name, $match_time, $court_needs_by_day );
			++$this->error_id;
		}
		return $this;
	}
	/**
	 * Validate entry acceptance
	 *
	 * @param string $acceptance acceptance indicator.
	 * @return object $validation updated validation object.
	 */
	public function entry_acceptance( $acceptance ) {
		if ( empty( $acceptance ) ) {
			$this->error                          = true;
			$this->error_field[ $this->error_id ] = 'acceptance';
			$this->error_msg[ $this->error_id ]   = __( 'You must agree to the rules', 'racketmanager' );
			++$this->error_id;
		}
		return $this;
	}

	/**
	 * Validate captain details
	 *
	 * @param int    $captain captain id.
	 * @param string $contactno contact number.
	 * @param string $contactemail email.
	 * @param string $field_ref field reference.
	 * @param string $field_name field name.
	 * @return object $validation updated validation object.
	 */
	public function captain( $captain, $contactno, $contactemail, $field_ref, $field_name ) {
		if ( empty( $captain ) ) {
			$this->error                          = true;
			$this->error_field[ $this->error_id ] = 'captain-' . $field_ref;
			/* translators: %s: competition name */
			$this->error_msg[ $this->error_id ] = sprintf( __( 'Captain not selected for %s', 'racketmanager' ), $field_name );
			++$this->error_id;
		} elseif ( empty( $contactno ) || empty( $contactemail ) ) {
				$this->error                          = true;
				$this->error_field[ $this->error_id ] = 'captain-' . $field_ref;
				/* translators: %s: competition name */
				$this->error_msg[ $this->error_id ] = sprintf( __( 'Captain contact details missing for %s', 'racketmanager' ), $field_name );
				++$this->error_id;
		}
		return $this;
	}
	/**
	 * Validate match day details
	 *
	 * @param string  $match_day match day.
	 * @param string  $field_ref field reference.
	 * @param string  $field_name field name.
	 * @param boolean $match_day_restriction match day restriction indicator.
	 * @param array   $match_days_allowed array of match days allowed.
	 * @return object $validation updated validation object.
	 */
	public function match_day( $match_day, $field_ref, $field_name, $match_day_restriction = false, $match_days_allowed = array() ) {
		if ( empty( $match_day ) && '0' !== $match_day ) {
			$this->error                          = true;
			$this->error_field[ $this->error_id ] = 'matchday-' . $field_ref;
			/* translators: %s: competition name */
			$this->error_msg[ $this->error_id ] = sprintf( __( 'Match day not selected for %s', 'racketmanager' ), $field_name );
			++$this->error_id;
		} elseif ( $match_day_restriction ) {
			if ( ! empty( $match_days_allowed ) && empty( $match_days_allowed[ $match_day ] ) ) {
				$this->error                          = true;
				$this->error_field[ $this->error_id ] = 'matchday-' . $field_ref;
				/* translators: %s: competition name */
				$this->error_msg[ $this->error_id ] = __( 'Match day not valid for event', 'racketmanager' );
				++$this->error_id;
			}
		}
		return $this;
	}

	/**
	 * Validate match time details
	 *
	 * @param string $match_time match time.
	 * @param string $field_ref field reference.
	 * @param string $field_name field name.
	 * @return object $validation updated validation object.
	 */
	public function match_time( $match_time, $field_ref, $field_name ) {
		if ( empty( $match_time ) ) {
			$this->error                          = true;
			$this->error_field[ $this->error_id ] = 'matchtime-' . $field_ref;
			/* translators: %s: competition name */
			$this->error_msg[ $this->error_id ] = sprintf( __( 'Match time not selected for %s', 'racketmanager' ), $field_name );
			++$this->error_id;
		}
		return $this;
	}
	/**
	 * Validate match time for overlap
	 *
	 * @param string $match_time match time.
	 * @param string $schedule_time current scheduled match time.
	 * @param string $field_ref field reference.
	 * @param string $field_name field name.
	 * @return object $validation updated validation object.
	 */
	public function match_overlap( $match_time, $schedule_time, $field_ref, $field_name ) {
		$start_time = \DateTime::createFromFormat( '!H:i:s', $match_time );
		$start_time->modify( '-2 hours' );
		$end_time = \DateTime::createFromFormat( '!H:i:s', $match_time );
		$end_time->modify( '+2 hours' );
		$current_match_time = \DateTime::createFromFormat( '!H:i:s', $schedule_time );
		if ( $current_match_time > $start_time && $current_match_time < $end_time ) {
			$this->error                          = true;
			$this->error_field[ $this->error_id ] = 'matchtime-' . $field_ref;
			/* translators: %s: team name */
			$this->error_msg[ $this->error_id ] = sprintf( __( 'Match overlap for %s', 'racketmanager' ), $field_name );
			++$this->error_id;
		}
		return $this;
	}
	/**
	 * Validate partner details
	 *
	 * @param int    $partner partner.
	 * @param string $field_ref field reference.
	 * @param string $field_name field name.
	 * @param object $event event object.
	 * @param string $season season name.
	 * @param int    $player_id player id.
	 * @return object $validation updated validation object.
	 */
	public function partner( $partner, $field_ref, $field_name, $event, $season, $player_id ) {
		if ( empty( $partner ) ) {
			$this->error                          = true;
			$this->error_field[ $this->error_id ] = 'partner-' . $field_ref;
			/* translators: %s: competition name */
			$this->error_msg[ $this->error_id ] = sprintf( __( 'Partner not selected for %s', 'racketmanager' ), $field_name );
			++$this->error_id;
		} else {
			$partner_found = false;
			$partner_teams = $event->get_teams(
				array(
					'player' => $partner,
					'season' => $season,
				)
			);
			foreach ( $partner_teams as $partner_team ) {
				if ( false === array_search( (string) $player_id, $partner_team->player_id, true ) ) {
					$partner_found = true;
				}
			}
			if ( $partner_found ) {
				$this->error                          = true;
				$this->error_field[ $this->error_id ] = 'partner-' . $field_ref;
				/* translators: %s: event name */
				$this->error_msg[ $this->error_id ] = sprintf( __( 'Partner for %s is playing', 'racketmanager' ), $field_name );
				++$this->error_id;
			}
			if ( ! empty( $event->age_limit ) && 'open' !== $event->age_limit ) {
				$partner = get_player( $partner );
				if ( empty( $partner->age ) ) {
					$this->error                          = true;
					$this->error_field[ $this->error_id ] = 'partner-' . $field_ref;
					/* translators: %s: competition name */
					$this->error_msg[ $this->error_id ] = sprintf( __( 'Partner for %s has no age specified', 'racketmanager' ), $field_name );
					++$this->error_id;
				} elseif ( $partner->age < $event->age_limit ) {
					$entry_invalid = false;
					if ( 'F' === $partner->gender && ! empty( $event->age_offset ) ) {
						$age_limit = $event->age_limit - $event->age_offset;
						if ( $partner->age < $age_limit ) {
							$entry_invalid = true;
						}
					} else {
						$entry_invalid = true;
					}
					if ( $entry_invalid ) {
						$this->error                          = true;
						$this->error_field[ $this->error_id ] = 'partner-' . $field_ref;
						/* translators: %s: competition name */
						$this->error_msg[ $this->error_id ] = sprintf( __( 'Partner for %s is not eligibile due to age', 'racketmanager' ), $field_name );
						++$this->error_id;
					}
				}
			}
		}
		return $this;
	}
	/**
	 * Validate tournament open
	 *
	 * @param string $tournament_close tournament close date.
	 * @return object $validation updated validation object.
	 */
	public function tournament_open( $tournament_close ) {
		if ( empty( $tournament_close ) ) {
			$this->error                          = true;
			$this->error_field[ $this->error_id ] = 'tournament';
			$this->error_msg[ $this->error_id ]   = __( 'Tournament close date not set', 'racketmanager' );
			++$this->error_id;
		} else {
			$today = new DateTime( 'now' );
			$close = new DateTime( $tournament_close . ' 23:59:59' );
			if ( $close < $today ) {
				$this->error                          = true;
				$this->error_field[ $this->error_id ] = 'tournament';
				$this->error_msg[ $this->error_id ]   = __( 'Tournament not open for entries', 'racketmanager' );
				++$this->error_id;
			}
		}
		return $this;
	}
	/**
	 * Validate weekend match
	 *
	 * @param string $field_ref field reference.
	 * @return object $validation updated validation object.
	 */
	public function weekend_match( $field_ref ) {
		$this->error                          = true;
		$this->error_field[ $this->error_id ] = 'matchday-' . $field_ref;
		$this->error_msg[ $this->error_id ]   = __( 'A higher ranked team is already playing at the weekend', 'racketmanager' );
		++$this->error_id;
		return $this;
	}
	/**
	 * Validate free slots
	 *
	 * @param string $slots slots available.
	 * @return object $validation updated validation object.
	 */
	public function free_slots( $slots ) {
		if ( $slots < 1 ) {
			$this->error                        = true;
			$this->error_msg[ $this->error_id ] = __( 'Weekend games not allowed when free weekday slots', 'racketmanager' );
			++$this->error_id;
		}
		return $this;
	}
}
