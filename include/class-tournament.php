<?php
/**
* tournament API: tournament class
*
* @author Paul Moffat
* @package RacketManager
* @subpackage Tournament
*/

/**
* Class to implement the Tournament object
*
*/
final class Tournament {

	/**
	* retrieve tournament instance
	*
	* @param int $tournament_id
	*/
	public static function get_instance($tournament_id) {
		global $wpdb;

		if ( ! $tournament_id ) {
			return false;
		}
		if ( is_numeric($tournament_id) ) {
			$searchString = $wpdb->prepare(" WHERE `id` = '%s'", $tournament_id);
		} else {
			$searchString = $wpdb->prepare("WHERE `name` = '%s'", $tournament_id);
		}
		$tournament = wp_cache_get( $tournament_id, 'tournaments' );

		if ( ! $tournament ) {
			$tournament = $wpdb->get_row( $wpdb->prepare( "SELECT `id`, `name`, `type`, `season`, `venue`, DATE_FORMAT(`date`, '%%Y-%%m-%%d') AS date, DATE_FORMAT(`closingdate`, '%%Y-%%m-%%d') AS closingdate, `numcourts`, `starttime`, `timeincrement`, `orderofplay` FROM {$wpdb->racketmanager_tournaments} $searchString" ) );
			if ( !$tournament ) {
				return false;
			}
			$tournament = new Tournament( $tournament );
			wp_cache_set( $tournament_id, $tournament, 'tournaments' );
		}

		return $tournament;
	}

	/**
	* Constructor
	*
	* @param object $tournament Tournament object.
	*/
	public function __construct( $tournament = null ) {
		global $racketmanager;
		if ( !is_null($tournament) ) {
			foreach ( $tournament as $key => $value ) {
				$this->$key = $value;
			}

			if ( !isset($this->id) ) {
				$this->id = $this->add();
			}
			$this->dateDisplay = ( substr($this->date, 0, 10) == '0000-00-00' ) ? 'TBC' : mysql2date($racketmanager->date_format, $this->date);
			$this->closingDateDisplay = ( substr($this->closingdate, 0, 10) == '0000-00-00' ) ? 'N/A' : mysql2date($racketmanager->date_format, $this->closingdate);
		
			if ( $this->venue == 0 ) {
			  $this->venue = '';
			  $this->venueName = 'TBC';
			} else {
			  $this->venueName = get_club($tournament->venue)->name;
			}
		
			if ( isset($this->closingdate) && $this->closingdate >= date("Y-m-d") ) {
			  $this->open = true;
			} else {
			  $this->open = false;
			}
			if ( isset($this->date) && $this->date >= date("Y-m-d") ) {
			  $this->active = true;
			} else {
			  $this->active = false;
			}
			$this->orderofplay = (array)maybe_unserialize($this->orderofplay);
				
		}
	}

  	/**
	* add tournament
	*/
	private function add() {
		global $wpdb, $racketmanager;
		$wpdb->query( $wpdb->prepare ( "INSERT INTO {$wpdb->racketmanager_tournaments} (`name`, `type`, `season`, `venue`, `date`, `closingdate`, `numcourts`, `starttime` ) VALUES ('%s', '%s', '%d', '%d', '%s', '%s', %d, '%s' )", $this->name, $this->type, $this->season, $this->venue, $this->date, $this->closingdate, $this->numcourts, $this->starttime ) );
		$racketmanager->setMessage( __('Tournament added','racketmanager') );
		$this->id = $wpdb->insert_id;
		$this->orderofplay = '';
		return $this->id;
	}
	
	/**
	* update tournament
	*
	* @param int $club_id
	* @param string $name
	* @param string $type
	* @param string $season
	* @param int $venue
	* @param string $date
	* @param string $closingdate
	* @param int $numcourts
	* @param string s$tarttime
	* @return boolean
	*/
	public function update( $name, $type, $season, $venue, $date, $closingdate, $numcourts, $starttime ) {
		global $wpdb, $racketmanager;

		$wpdb->query( $wpdb->prepare ( "UPDATE {$wpdb->racketmanager_tournaments} SET `name` = '%s', `type` = '%s', `season` = '%s', `venue` = '%d', `date` = '%s', `closingdate` = '%s', `numcourts` = %d, `starttime` = '%s' WHERE `id` = %d", $name, $type, $season, $venue, $date, $closingdate, $numcourts, $starttime, $this->id ) );

		$racketmanager->setMessage( __('Tournament updated','racketmanager') );

		return true;
	}

	/**
	* update tournament plan
	*
	* @param int $tournament
	* @param text $starttime
	* @param int $numcourts
	* @param text $timeincrement
	* @return boolean updates performed
	*/
	public function updatePlan( $starttime, $numcourts, $timeincrement ) {
		global $wpdb, $racketmanager;

		$update = false;
		if ( $starttime != $this->starttime || $numcourts != $this->numcourts || $timeincrement != $this->timeincrement ) {
			wp_cache_flush_group('tournaments');
			$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->racketmanager_tournaments} SET `starttime` = '%s', `numcourts` = %d, `timeincrement` = '%s' WHERE `id` = %d", $starttime, $numcourts, $timeincrement, $this->id ) );
			$racketmanager->setMessage( __('Tournament updated', 'racketmanager') );
			$update = true;
		} else {
			$racketmanager->setMessage( __('No updates', 'racketmanager') );
		}
		return $update;
	}

	/**
	* update tournament plan
	*
	* @param int $tournament
	* @param array $court
	* @param array $matches
	* @param array $matchtimes
	* @return boolean updates performed
	*/
	public function savePlan( $courts, $starttimes, $matches, $matchtimes ) {
		global $wpdb, $racketmanager;
		$orderofplay = array();
		for ($i=0; $i < count($courts); $i++) {
			$orderofplay[$i]['court'] = $courts[$i];
			$orderofplay[$i]['starttime'] = $starttimes[$i];
			$orderofplay[$i]['matches'] = $matches[$i];
			for ($m=0; $m < count($matches[$i]); $m++) {
				$matchId = $matches[$i][$m];
				if ( $matchId != '' ) {
					$time = strtotime($starttimes[$i]) + $matchtimes[$i][$m];
					$match = get_match($matchId);
					$month = str_pad($match->month,2, '0', STR_PAD_LEFT);
					$day = str_pad($match->day,2, '0', STR_PAD_LEFT);
					$date = $match->year.'-'.$month.'-'.$day.' '.date('H:i', $time);
					$location = $courts[$i];
					if ( $date != $match->date || $location != $match->location ) {
						$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->racketmanager_matches} SET `date` = '%s', `location` = '%s' WHERE `id` = %d", $date, $location, $matchId) );
					}
				}
			}
		}
		if ( $orderofplay != $this->orderofplay ) {
			$orderofplay = maybe_serialize($orderofplay);
			wp_cache_flush_group('tournaments');
			$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->racketmanager_tournaments} SET `orderofplay` = '%s' WHERE `id` = %d", $orderofplay, $this->id ) );
			$racketmanager->setMessage( __('Tournament plan updated', 'racketmanager') );
		} else {
			$racketmanager->setMessage( __('No updates', 'racketmanager') );
		}
		return true;
	}

  	/**
	* reset tournament plan
	* @return boolean updates performed
	*/
	public function resetPlan() {
		global $wpdb, $racketmanager;

		$updates = true;
		$orderofplay = array();
		$finalMatches = $racketmanager->getMatches( array('season' => $this->season, 'final' => 'final', 'competitiontype' => 'tournament', 'competitionseason' => $this->type));

		foreach ($finalMatches as $match) {
			$month = str_pad($match->month,2, '0', STR_PAD_LEFT);
			$day = str_pad($match->day,2, '0', STR_PAD_LEFT);
			$date = $match->year.'-'.$month.'-'.$day.' 00:00';
			$location = '';
			if ( $date != $match->date || $location != $match->location ) {
				$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->racketmanager_matches} SET `date` = '%s', `location` = '%s' WHERE `id` = %d", $date, $location, $match->id) );
				$updates = true;
			}
		}
		if ( $orderofplay != $this->orderofplay ) {
			$orderofplay = maybe_serialize($orderofplay);
			wp_cache_flush_group('tournaments');
			$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->racketmanager_tournaments} SET `orderofplay` = '%s' WHERE `id` = %d", $orderofplay, $this->id ) );
			$updates = true;
		}
		if ( $updates ) {
			$racketmanager->setMessage( __('Tournament plan reset', 'racketmanager') );
		} else {
			$racketmanager->setMessage( __('No updates', 'racketmanager') );
		}
		return $updates;
	}
  	/**
	* delete tournament
	*/
	public function delete() {
		global $wpdb, $racketmanager;

		$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->racketmanager_tournaments} WHERE `id` = '%d'", $this->id) );
		$racketmanager->setMessage( __('Tournament Deleted','racketmanager') );
		wp_cache_flush_group('tournaments');
	}

}

/**
* get Tournament object
*
* @param int|Tournament|null Tournament ID or tournament object. Defaults to global $tournament
* @return object tournament|null
*/
function get_tournament( $tournament = null ) {
	if ( empty( $tournament ) && isset( $GLOBALS['tournament'] ) ) {
		$tournament = $GLOBALS['tournament'];
	}

	if ( $tournament instanceof Tournament ) {
		$_tournament = $tournament;
	} elseif ( is_object( $tournament ) ) {
		$_tournament = new Tournament( $tournament );
	} else {
		$_tournament = Tournament::get_instance( $tournament );
	}

	if ( ! $_tournament ) {
		return null;
	}

	return $_tournament;
}
?>
