<?php
/**
 * Entry Form Validation API: Entry form validator class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Validate
 */

namespace Racketmanager;

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
	public function nonce( string $nonce_key ): object {
		if ( !isset( $_POST['_wpnonce'] ) || !wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), $nonce_key ) ) {
			$this->error = true;
			$this->error_field[$this->error_id] = '';
			$this->error_msg[$this->error_id] = __( 'Form has expired. Please refresh the page and resubmit', 'racketmanager' );
			++$this->error_id;
		}
		return $this;
	}

	/**
	 * Validate if user logged in
	 *
	 * @return object updated validation object.
	 */
	public function logged_in_entry(): object {
		$this->error = true;
		$this->error_field[$this->error_id] = 'clubId';
		$this->error_msg[$this->error_id] = __( 'You must be logged in to submit an entry', 'racketmanager' );
		++$this->error_id;
		return $this;
	}

	/**
	 * Validate club
	 *
	 * @param string $club club.
	 *
	 * @return object $validation updated validation object.
	 */
	public function club( string $club ): object {
		if ( !$club ) {
			$this->error = true;
			$this->error_field[$this->error_id] = 'clubId';
			$this->error_msg[$this->error_id] = __( 'Select the club you are a member of', 'racketmanager' );
			++$this->error_id;
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
			$this->error = true;
			$this->error_field[$this->error_id] = 'event';
			$this->error_msg[$this->error_id] = __( 'You must select a event to enter', 'racketmanager' );
			++$this->error_id;
		} elseif ( ! empty( $max_entries ) ) {
			if ( count( $events ) > $max_entries ) {
				$this->error = true;
				$this->error_field[$this->error_id] = 'event';
				$this->error_msg[$this->error_id] = __( 'You have entered too many events', 'racketmanager' );
				++$this->error_id;
			}
		}
		return $this;
	}

	/**
	 * Validate teams
	 *
	 * @param array $teams array of teams.
	 * @param string $field_ref field reference.
	 * @param string $field_name field name.
	 * @return object $validation updated validation object.
	 */
	public function teams( array $teams, string $field_ref, string $field_name ): object {
		if ( empty( $teams ) ) {
			$this->error = true;
			$this->error_field[$this->error_id] = 'event-' . $field_ref;
			/* translators: %s: competition name */
			$this->error_msg[$this->error_id] = sprintf( __( 'No teams selected for %s', 'racketmanager' ), $field_name );
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
	public function num_courts_available( int $num_courts_available ): object {
		if ( empty( $num_courts_available ) ) {
			$this->error = true;
			$this->error_field[$this->error_id] = 'numCourtsAvailable';
			/* translators: %s: competition name */
			$this->error_msg[$this->error_id] = __( 'You must specify the number of courts available', 'racketmanager' );
			++$this->error_id;
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
			$this->error = true;
			$this->error_field[$this->error_id] = 'numCourtsAvailable';
			/* translators: %1$s: match day,  %2$s: match time, %3$s: courts needed */
			$this->error_msg[$this->error_id] = sprintf( __( 'There are not enough courts available for %1$s at %2$s. You need %3$s courts.', 'racketmanager' ), $match_day_name, $match_time, $court_needs_by_day );
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
	public function entry_acceptance( string $acceptance ): object {
		if ( empty( $acceptance ) ) {
			$this->error = true;
			$this->error_field[$this->error_id] = 'acceptance';
			$this->error_msg[$this->error_id] = __( 'You must agree to the rules', 'racketmanager' );
			++$this->error_id;
		}
		return $this;
	}

	/**
	 * Validate captain details
	 *
	 * @param string $captain captain id.
	 * @param string $contactno contact number.
	 * @param string $contactemail email.
	 * @param string $field_ref field reference.
	 * @param string $field_name field name.
	 * @return object $validation updated validation object.
	 */
	public function captain( string $captain, string $contactno, string $contactemail, string $field_ref, string $field_name ): object {
		if ( empty( $captain ) ) {
			$this->error = true;
			$this->error_field[$this->error_id] = 'captain-' . $field_ref;
			/* translators: %s: competition name */
			$this->error_msg[$this->error_id] = sprintf( __( 'Captain not selected for %s', 'racketmanager' ), $field_name );
			++$this->error_id;
		} elseif ( empty( $contactno ) || empty( $contactemail ) ) {
			$this->error = true;
			$this->error_field[$this->error_id] = 'captain-' . $field_ref;
			/* translators: %s: competition name */
			$this->error_msg[$this->error_id] = sprintf( __( 'Captain contact details missing for %s', 'racketmanager' ), $field_name );
			++$this->error_id;
		}
		return $this;
	}

	/**
	 * Validate match day details
	 *
	 * @param int    $match_day match day.
	 * @param string $field_ref field reference.
	 * @param boolean $match_day_restriction match day restriction indicator.
	 * @param array $match_days_allowed array of match days allowed.
	 * @return object $validation updated validation object.
	 */
	public function match_day( int $match_day, string $field_ref, bool $match_day_restriction = false, array $match_days_allowed = array() ): object {
		if ( empty( $match_day ) && 0 !== $match_day ) {
			$this->error = true;
			$this->error_field[$this->error_id] = 'matchday-' . $field_ref;
			$this->error_msg[$this->error_id] = __( 'Match day not selected', 'racketmanager' );
			++$this->error_id;
		} elseif ( $match_day_restriction ) {
			if ( !empty( $match_days_allowed ) && empty( $match_days_allowed[$match_day] ) ) {
				$this->error = true;
				$this->error_field[$this->error_id] = 'matchday-' . $field_ref;
				$this->error_msg[$this->error_id] = __( 'Match day not valid for event', 'racketmanager' );
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
	 * @param string $match_day match day.
	 * @param array $start_times min/max start times.
	 * @return object $validation updated validation object.
	 */
	public function match_time( string $match_time, string $field_ref, string $match_day, array $start_times ): object {
		if ( empty( $match_time ) ) {
			$this->error = true;
			$this->error_field[$this->error_id] = 'matchtime-' . $field_ref;
			$this->error_msg[$this->error_id] = __( 'Match time not selected', 'racketmanager' );
			++$this->error_id;
		} elseif ( $match_day >= 0 ) {
			$match_time = substr( $match_time, 0, 5 );
			if ( $match_day <= 5 ) {
				$index = 'weekday';
			} else {
				$index = 'weekend';
			}
			if ( isset( $start_times[$index] ) ) {
				if ( $match_time < $start_times[$index]['min'] ) {
					$this->error = true;
					$this->error_field[$this->error_id] = 'matchtime-' . $field_ref;
					$this->error_msg[$this->error_id] = __( 'Match time less than earliest start', 'racketmanager' );
					++$this->error_id;
				} elseif ( $match_time > $start_times[$index]['max'] ) {
					$this->error = true;
					$this->error_field[$this->error_id] = 'matchtime-' . $field_ref;
					$this->error_msg[$this->error_id] = __( 'Match time greater than latest start', 'racketmanager' );
					++$this->error_id;
				}
			}
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
	 * @throws \DateMalformedStringException
	 */
	public function match_overlap( string $match_time, string $schedule_time, string $field_ref ): object {
		$start_time = \DateTime::createFromFormat( '!H:i:s', $match_time );
		$start_time->modify( '-2 hours' );
		$end_time = \DateTime::createFromFormat( '!H:i:s', $match_time );
		$end_time->modify( '+2 hours' );
		$current_match_time = \DateTime::createFromFormat( '!H:i:s', $schedule_time );
		if ( $current_match_time > $start_time && $current_match_time < $end_time ) {
			$this->error = true;
			$this->error_field[$this->error_id] = 'matchtime-' . $field_ref;
			$this->error_msg[$this->error_id] = __( 'Match overlap', 'racketmanager' );
			++$this->error_id;
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
			$error_field = 'partner';
		} else {
			$error_field = 'partner-' . $field_ref;
		}
		if ( empty( $partner ) ) {
			$this->error = true;
			$this->error_field[$this->error_id] = $error_field;
			$this->error_msg[$this->error_id] = __( 'Partner not selected', 'racketmanager' );
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
				if ( ! in_array( $player_id, $partner_team->player_id, true ) ) {
					$partner_found = true;
				}
			}
			if ( $partner_found ) {
				$this->error = true;
				$this->error_field[$this->error_id] = $error_field;
				$this->error_msg[$this->error_id] = __( 'Partner is in another team in this event', 'racketmanager' );
				++$this->error_id;
			} else {
				if ( !empty( $event->age_limit ) && 'open' !== $event->age_limit ) {
					$partner = get_player( $partner );
					$partner_age = substr( $date_end, 0, 4 ) - intval( $partner->year_of_birth );
					if ( empty( $partner->age ) ) {
						$this->error = true;
						$this->error_field[$this->error_id] = $error_field;
						$this->error_msg[$this->error_id] = __( 'Partner has no age specified', 'racketmanager' );
						++$this->error_id;
					} elseif ( $event->age_limit >= 30 ) {
						if ( 'F' === $partner->gender && ! empty( $event->age_offset ) ) {
							$age_limit = $event->age_limit - $event->age_offset;
						} else {
							$age_limit = $event->age_limit;
						}
						if ( $partner_age < $age_limit ) {
							$this->error = true;
							$this->error_field[$this->error_id] = $error_field;
							$this->error_msg[$this->error_id] = __( 'Partner is too young', 'racketmanager' );
							++$this->error_id;
						}
					} elseif ( $partner_age > $event->age_limit ) {
						$this->error = true;
						$this->error_field[$this->error_id] = $error_field;
						$this->error_msg[$this->error_id] = __( 'Partner is too old', 'racketmanager' );
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
	 * @param object $tournament tournament object.
	 * @return object $validation updated validation object.
	 */
	public function tournament_open( object $tournament ): object {
		if ( empty( $tournament->date_closing ) ) {
			$this->error = true;
			$this->error_field[$this->error_id] = 'event';
			$this->error_msg[$this->error_id] = __( 'Tournament close date not set', 'racketmanager' );
			++$this->error_id;
		} else {
			if ( !$tournament->is_open && !$tournament->is_closed ) {
				$this->error = true;
				$this->error_field[$this->error_id] = 'event';
				$this->error_msg[$this->error_id] = __( 'Tournament not open for entries', 'racketmanager' );
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
	public function weekend_match( string $field_ref ): object {
		$this->error = true;
		$this->error_field[$this->error_id] = 'matchday-' . $field_ref;
		$this->error_msg[$this->error_id] = __( 'A higher ranked team is already playing at the weekend', 'racketmanager' );
		++$this->error_id;
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
			$this->error = true;
			$this->error_msg[$this->error_id] = __( 'Weekend games not allowed when free weekday slots', 'racketmanager' );
			++$this->error_id;
		}
		return $this;
	}
}
