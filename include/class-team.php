<?php
/**
* Team API: Team class
*
* @author Kolja Schleich
* @package RacketManager
* @subpackage Team
*/

/**
* Class to implement the Team object
*
*/
final class Team {

	public $id;
	public $title;
	public $stadium;
	public $roster;
	public $profile;
	public $affiliatedclub;
	public $affiliatedclubname;
	public $status;
	public $player;
	public $playerId;
	public $player1;
	public $player2;
	public $player1Id;
	public $player2Id;
	public $type;
	private $msgTeamUpdated = 'Team updated';
	private $msgTeamAdded = 'Team added';
	private $msgTeamUpdateError = 'Team update error';
	private $msgTeamAddError = 'Team add error';
	private $msgNoUpdate = 'No updates';
	private $msgDetailsMissing = 'Team details missing';
	private $msgTeamContactError = 'Error updating team contact';

	/**
	* retrieve team instance
	*
	* @param int $team_id
	*/
	public static function get_instance($team_id) {
		global $wpdb;
		if ( is_numeric($team_id) ) {
			$search = "`id` = '%d'";
		} else {
			$search = "`title` = '%s'";
		}
		if ( ! $team_id ) {
			return false;
		}
		$team = wp_cache_get( $team_id, 'teams' );

		if ( ! $team ) {
			if ( $team_id == -1) {
				$team = (object)array( 'id' => $team_id, 'title' => __( 'Bye', 'racketmanager' ) );
			} else {
				$team = $wpdb->get_row( $wpdb->prepare( "SELECT `id`, `title`, `stadium`, `home`, `roster`, `profile`, `status`, `affiliatedclub`, `type` FROM {$wpdb->racketmanager_teams} WHERE ".$search." LIMIT 1", $team_id ) );
			}

			if ( !$team ) {
				return false;
			}
			$team = new Team( $team );
			wp_cache_set( $team->id, $team, 'teams' );
		}

		return $team;
	}

	/**
	* Constructor
	*
	* @param object $team Team object.
	*/
	public function __construct( $team = null ) {

		if ( !is_null($team) ) {
			foreach ( get_object_vars( $team ) as $key => $value ) {
				$this->$key = $value;
			}
			if ( !isset($this->id) || $this->id == '' ) {
				$this->add();
			}
			$this->title = htmlspecialchars(stripslashes($this->title), ENT_QUOTES);
			$this->stadium = stripslashes($this->stadium);
			$this->roster = maybe_unserialize($this->roster);
			$this->profile = intval($this->profile);
			$this->affiliatedclubname = get_club( $this->affiliatedclub )->name;
			if ( $this->status == 'P' && $this->roster != null ) {
				$i = 1;
				foreach ($this->roster as $player) {
					$teamplayer = get_player($player);
					$this->player[$i] = $teamplayer->fullname;
					$this->playerId[$i] = $player;
					$i++;
				}
			}
		}
	}

	/**
	* add new Team
	*
	* @return boolean
	*/
	private function add() {
		global $wpdb, $racketmanager;
		if ( isset($this->status) && $this->status == 'P' ) {
			if ( $this->type == 'LD' ) { $this->type = 'XD'; }
			$players = array();
			$this->title = $this->player1;
			$players[] = $this->player1Id;
			if ( $this->player2Id ) {
			  $this->title .= ' / '.$this->player2;
			  $players[] = $this->player2Id;
			}
			$this->roster = $players;
			$this->stadium = '';
			$this->profile = '';
			$sql = "INSERT INTO {$wpdb->racketmanager_teams} (`title`, `affiliatedclub`, `roster`, `status`, `type` ) VALUES ('%s', '%d', '%s', '%s', '%s')";
			$result = $wpdb->query( $wpdb->prepare ( $sql, $this->title, $this->affiliatedclub, maybe_serialize($players), $this->status, $this->type ) );
			$this->id = $wpdb->insert_id;
		
		} else {
			$this->roster = '';
			$this->profile = '';
			$this->status = '';
			$sql = "INSERT INTO {$wpdb->racketmanager_teams} (`title`, `stadium`, `affiliatedclub`, `type`) VALUES ('%s', '%s', '%d', '%s')";
			$result = $wpdb->query( $wpdb->prepare ( $sql, $this->title, $this->stadium, $this->affiliatedclub, $this->type) );
			$this->id = $wpdb->insert_id;
		}
		if ( $result ) {
			$racketmanager->setMessage( __($this->msgTeamAdded,'racketmanager') );
		} else {
			$racketmanager->setMessage( __($this->msgTeamAddError, 'racketmanager'), true );
			error_log('error with team creation');
			error_log($wpdb->last_error);

		}
	}

	/**
	* update team
	* @param string $title team name
	* @param int $clubId affiliated club id
	* @param string $type team type (mens/ladies/mixed/singles/doubles)
	*
	* @return none
	*/
	public function update($title, $clubId, $type) {
		global $wpdb, $racketmanager;

		$club = get_club($clubId);
		$stadium = $club->name;
		if ( $this->title != $title || $this->affiliatedclub != $clubId || $this->type != $type || $this->stadium != $stadium ) {
			$result = $wpdb->query( $wpdb->prepare ( "UPDATE {$wpdb->racketmanager_teams} SET `title` = '%s', `affiliatedclub` = '%d', `stadium` = '%s', `type` = '%s' WHERE `id` = %d", $title, $clubId, $stadium, $type, $this->id ) );
			if ( $result ) {
				wp_cache_delete( $this->id, 'teams' );
				$racketmanager->setMessage( __($this->msgTeamUpdated, 'racketmanager') );
			} else {
				$racketmanager->setMessage( __($this->msgTeamUpdateError, 'racketmanager'), true );
				error_log('error with team update');
				error_log($wpdb->last_error);
			}
		} else {
			$racketmanager->setMessage( __($this->msgNoUpdate, 'racketmanager') );
		}
	}

	/**
	* update team for players
	* @param string $player1 player 1 name
	* @param int $player1Id player 1 id
	* @param string $player2 player 2 name
	* @param int $player2Id player 2 id
	* @param int $clubId affiliated club id
	*
	* @return none
	*/
	public function updatePlayer($player1, $player1Id, $player2, $player2Id, $clubId) {
		global $wpdb, $racketmanager;

		$players = array();
		$players[] = $player1Id;
		$title = $player1;
		if ( $player2Id  ) {
			$title .= ' / '.$player2;
			$players[] = $player2Id;
		}
	  
		$club = get_club($clubId);
		$stadium = $club->name;
		if ( $this->title != $title || $this->affiliatedclub != $clubId || $this->roster != $players || $this->stadium != $stadium ) {
			$result = $wpdb->query( $wpdb->prepare ( "UPDATE {$wpdb->racketmanager_teams} SET `title` = '%s', `affiliatedclub` = '%d', `stadium` = '%s', `roster` = '%s' WHERE `id` = %d", $title, $clubId, $stadium, maybe_serialize($players), $this->id ) );
			if ( $result ) {
				wp_cache_delete( $this->id, 'teams' );
				$racketmanager->setMessage( __($this->msgTeamUpdated, 'racketmanager') );
			} else {
				$racketmanager->setMessage( __($this->msgTeamUpdateError, 'racketmanager'), true );
				error_log('Error with player team update');
				error_log($wpdb->last_error);
			}
		} else {
			$racketmanager->setMessage( __($this->msgNoUpdate, 'racketmanager') );
		}
	}

	/**
	* set Competition
	*
	* @param int $competitionId
	* @param string $captain optional
	* @param string $contactno optional
	* @param string $contactemail optional
	* @param int $matchday optional
	* @param int $matchtime optional
	* @return boolean
	*/
	public function setCompetition( $competitionId, $captain = null, $contactNo = null, $contactEmail = null , $matchDay = null, $matchTime = null) {
		global $wpdb, $racketmanager;

		$count = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->racketmanager_team_competition} WHERE `team_id` = '%d' AND `competition_id` = '%d'", $this->id, $competitionId) );
		if ($count) {
			$msg = $this->updateCompetition($competitionId, $captain, $contactNo, $contactEmail, $matchDay, $matchTime);
			$racketmanager->setMessage($msg);
		} else {
			$this->addCompetition( $competitionId, $captain, $contactNo, $contactEmail, $matchDay, $matchTime );
			$racketmanager->setMessage( __($this->msgTeamAdded, 'racketmanager') );
		}

		return true;
	}

	/**
	 * add team to competition
	*
	* @param int $competitionId
	* @param string $captain
	* @param string $contactno
	* @param string $contactemail
	* @param int $matchday
	* @param int $matchtime
	* @return $teamCompetitionId
	*/
	public function addCompetition( $competitionId, $captain = null, $contactno = null, $contactemail = null, $matchday = '', $matchtime = null ) {
		global $wpdb;

		$sql = "INSERT INTO {$wpdb->racketmanager_team_competition} (`team_id`, `competition_id`, `captain`, `match_day`, `match_time`) VALUES ('%d', '%d', '%d', '%s', '%s')";
		$wpdb->query( $wpdb->prepare ( $sql, $this->id, $competitionId, $captain, $matchday, $matchtime ) );
		$teamCompetitionId = $wpdb->insert_id;
		if ( $captain ) {
			$player = get_player($captain);
			$player->updateContact($contactno, $contactemail);
		}
		return $teamCompetitionId;
	}

	/**
	 * update competition details
	*
	* @param int $competitionId
	* @param string $captain
	* @param string $contactno
	* @param string $contactemail
	* @param int $matchday
	* @param int $matchtime
	* @return $teamCompetitionId
	*/
	public function updateCompetition( $competitionId, $captain, $contactno, $contactemail, $matchday, $matchtime ) {
		global $wpdb;
		$updates = false;
		$msg = false;
		$competition = get_competition($competitionId);
		$current = $competition->getTeamInfo($this->id);
		if ( $current->captainId != $captain || $current->match_day != $matchday || $current->match_time != $matchtime ) {
			if ( $captain && ( ($competition->entryType == 'team' && $matchday && $matchtime) || $competition->entryType == 'player') ) {
				$wpdb->query( $wpdb->prepare ( "UPDATE {$wpdb->racketmanager_team_competition} SET `captain` = '%s', `match_day` = '%s', `match_time` = '%s' WHERE `team_id` = %d AND `competition_id` = %d", $captain, $matchday, $matchtime, $this->id, $competitionId ) );
				$updates = true;
			} else {
				$msg = __($this->msgDetailsMissing, 'racketmanager');
			}
		}
		if ( $current->contactno != $contactno || $current->contactemail != $contactemail ) {
			$player = get_player($captain);
			$updates = $player->updateContact($contactno,$contactemail);
			if ( !$updates ) {
				$msg = __($this->msgTeamContactError, 'racketmanager');
			}
		}
		if ( $updates ) {
			$msg = __($this->msgTeamUpdated, 'racketmanager');
		} elseif ( empty($msg) ) {
			$msg = __($this->msgNoUpdate, 'racketmanager');
		}

		return $msg;
	}

	/**
	* delete team
	*
	* @return none
	*/
	public function delete() {
		global $wpdb;

		// remove matches and rubbers
		$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->racketmanager_rubbers} WHERE `match_id` in (select `id` from {$wpdb->racketmanager_matches} WHERE `home_team` = '%d' OR `away_team` = '%d')", $this->id, $this->id) );
		$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->racketmanager_matches} WHERE `home_team` = '%d' OR `away_team` = '%d'", $this->id, $this->id) );
		// remove tables
		$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->racketmanager_table} WHERE `team_id` = '%d'", $this->id) );
		// remove team competition
		$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->racketmanager_team_competition} WHERE `team_id` = '%d'", $this->id) );
		// remove team
		$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->racketmanager_teams} WHERE `id` = '%d'", $this->id) );
	}

  	/**
	* update title
	*
	* @param string $title
	*/
	public function updateTitle( $title ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare ( "UPDATE {$wpdb->racketmanager_teams} SET `title` = '%s' WHERE `id` = %d", $title, $this->id ) );
	}

}

/**
* get Team object
*
* @param int|Team|null Team ID or team object. Defaults to global $team
* @return object Team|null
*/
function get_team( $team = null ) {
	if ( empty( $team ) && isset( $GLOBALS['team'] ) ) {
		$team = $GLOBALS['team'];
	}

	if ( $team instanceof Team ) {
		$_team = $team;
	} elseif ( is_object( $team ) ) {
		$_team = new Team( $team );
	} else {
		$_team = Team::get_instance( $team );
	}

	if ( ! $_team ) {
		return null;
	}

	return $_team;
}
