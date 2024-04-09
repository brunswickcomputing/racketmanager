<?php
/**
 * Racketmanager_Team API: Team class
 *
 * @author Kolja Schleich
 * @package RacketManager
 * @subpackage Team
 */

namespace Racketmanager;

/**
 * Class to implement the Team object
 */
final class Racketmanager_Team {
	/**
	 * ID variable
	 *
	 * @var int
	 */
	public $id;
	/**
	 * Title variable
	 *
	 * @var string
	 */
	public $title;
	/**
	 * Stadium variable
	 *
	 * @var string
	 */
	public $stadium;
	/**
	 * Roster variable
	 *
	 * @var array
	 */
	public $roster;
	/**
	 * Profile variable
	 *
	 * @var string
	 */
	public $profile;
	/**
	 * Club id variable
	 *
	 * @var int
	 */
	public $affiliatedclub;
	/**
	 * Club object variable
	 *
	 * @var object
	 */
	public $club;
	/**
	 * Club name variable
	 *
	 * @var string
	 */
	public $affiliatedclubname;
	/**
	 * Status variable
	 *
	 * @var string
	 */
	public $status;
	/**
	 * Player variable
	 *
	 * @var string
	 */
	public $player;
	/**
	 * Player 1 name variable
	 *
	 * @var string
	 */
	public $player1;
	/**
	 * Player2 name variable
	 *
	 * @var string
	 */
	public $player2;
	/**
	 * Player 1 id variable
	 *
	 * @var int
	 */
	public $player1_id;
	/**
	 * Player 2 ID variable
	 *
	 * @var int
	 */
	public $player2_id;
	/**
	 * Team type variable
	 *
	 * @var string
	 */
	public $type;
	/**
	 * Home variable
	 *
	 * @var string
	 */
	public $home;
	/**
	 * Player id variable
	 *
	 * @var int
	 */
	public $player_id;
	/**
	 * Team updated variable
	 *
	 * @var string
	 */
	private $msg_team_updated = 'Team updated';
	/**
	 * Team added variable
	 *
	 * @var string
	 */
	private $msg_team_added = 'Team added';
	/**
	 * Team update error variable
	 *
	 * @var string
	 */
	private $msg_team_update_error = 'Team update error';
	/**
	 * Team add error variable
	 *
	 * @var string
	 */
	private $msg_team_add_error = 'Team add error';
	/**
	 * No updates variable
	 *
	 * @var string
	 */
	private $msg_no_update = 'No updates';
	/**
	 * Team details missing message variable
	 *
	 * @var string
	 */
	private $msg_details_missing = 'Team details missing';
	/**
	 * Error updating team contact variable
	 *
	 * @var string
	 */
	private $msg_team_contact_error = 'Error updating team contact';
	/**
	 * Player not found error message variable
	 *
	 * @var string
	 */
	private $player_not_found_error = 'Player not found';

	/**
	 * Retrieve team instance
	 *
	 * @param int $team_id team id.
	 * @return object|boolean
	 */
	public static function get_instance( $team_id ) {
		global $wpdb;
		if ( is_numeric( $team_id ) ) {
			$search = $wpdb->prepare(
				'`id` = %d',
				$team_id
			);
		} else {
			$search = $wpdb->prepare(
				'`title` = %s',
				$team_id
			);
		}
		if ( ! $team_id ) {
			return false;
		}
		$team = wp_cache_get( $team_id, 'teams' );

		if ( ! $team ) {
			if ( -1 === $team_id ) {
				$team = (object) array(
					'id'     => $team_id,
					'title'  => __( 'Bye', 'racketmanager' ),
					'player' => array(),
				);
			} else {
				$team = $wpdb->get_row(
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					"SELECT `id`, `title`, `stadium`, `home`, `roster`, `profile`, `status`, `affiliatedclub`, `type` FROM {$wpdb->racketmanager_teams} WHERE " . $search . ' LIMIT 1',
				); // db call ok.
			}
			if ( ! $team ) {
				return false;
			}
			$team = new Racketmanager_Team( $team );
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
		$this->msg_team_updated       = __( 'Team updated', 'racketmanager' );
		$this->msg_team_added         = __( 'Team added', 'racketmanager' );
		$this->msg_team_update_error  = __( 'Team update error', 'racketmanager' );
		$this->msg_team_add_error     = __( 'Team add error', 'racketmanager' );
		$this->msg_no_update          = __( 'No updates', 'racketmanager' );
		$this->msg_details_missing    = __( 'Team details missing', 'racketmanager' );
		$this->msg_team_contact_error = __( 'Error updating team contact', 'racketmanager' );
		$this->player_not_found_error = __( 'Player not found', 'racketmanager' );

		if ( ! is_null( $team ) ) {
			foreach ( get_object_vars( $team ) as $key => $value ) {
				$this->$key = $value;
			}
			if ( empty( $this->id ) ) {
				$this->add();
			}
			$this->title   = htmlspecialchars( stripslashes( $this->title ), ENT_QUOTES );
			$this->stadium = stripslashes( $this->stadium );
			$this->roster  = maybe_unserialize( $this->roster );
			$this->profile = intval( $this->profile );
			if ( $this->affiliatedclub ) {
				$this->club               = get_club( $this->affiliatedclub );
				$this->affiliatedclubname = $this->club->name;
			}
			if ( 'P' === $this->status && ! empty( $this->roster ) ) {
				$i = 1;
				foreach ( $this->roster as $player ) {
					$teamplayer = get_player( $player );
					if ( $teamplayer ) {
						$this->player[ $i ]    = $teamplayer->fullname;
						$this->player_id[ $i ] = $player;
						++$i;
					}
				}
			}
		}
	}

	/**
	 * Add new Team
	 */
	private function add() {
		global $wpdb, $racketmanager;
		if ( isset( $this->status ) && 'P' === $this->status ) {
			if ( 'LD' === $this->type ) {
				$this->type = 'XD';
			}
			$players     = array();
			$this->title = $this->player1;
			$players[]   = $this->player1_id;
			if ( $this->player2_id ) {
				$this->title .= ' / ' . $this->player2;
				$players[]    = $this->player2_id;
			}
			$this->roster  = $players;
			$this->stadium = '';
			$this->profile = '';
			$result        = $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"INSERT INTO {$wpdb->racketmanager_teams} (`title`, `affiliatedclub`, `roster`, `status`, `type` ) VALUES (%s, %d, %s, %s, %s)",
					$this->title,
					$this->affiliatedclub,
					maybe_serialize( $players ),
					$this->status,
					$this->type
				)
			);
			$this->id      = $wpdb->insert_id;
		} else {
			$this->roster  = '';
			$this->profile = '';
			$this->status  = '';
			$result        = $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"INSERT INTO {$wpdb->racketmanager_teams} (`title`, `stadium`, `affiliatedclub`, `type`) VALUES (%s, %s, %d, %s)",
					$this->title,
					$this->stadium,
					$this->affiliatedclub,
					$this->type
				)
			);
			$this->id      = $wpdb->insert_id;
		}
		if ( $result ) {
			$racketmanager->set_message( $this->msg_team_added );
		} else {
			$racketmanager->set_message( $this->msg_team_add_error, true );
			error_log( 'error with team creation' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( $wpdb->last_error ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}

	/**
	 * Update team
	 *
	 * @param string $title team name.
	 * @param int    $club_id affiliated club id.
	 * @param string $type team type (mens/ladies/mixed/singles/doubles).
	 */
	public function update( $title, $club_id, $type ) {
		global $wpdb, $racketmanager;

		$club    = get_club( $club_id );
		$stadium = $club->name;
		if ( $this->title !== $title || $this->affiliatedclub !== $club_id || $this->type !== $type || $this->stadium !== $stadium ) {
			$result = $wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->racketmanager_teams} SET `title` = %s, `affiliatedclub` = %d, `stadium` = %s, `type` = %s WHERE `id` = %d",
					$title,
					$club_id,
					$stadium,
					$type,
					$this->id
				)
			); // db call ok, no cache ok.
			if ( $result ) {
				wp_cache_delete( $this->id, 'teams' );
				$racketmanager->set_message( $this->msg_team_updated );
			} else {
				$racketmanager->set_message( $this->msg_team_update_error, true );
				error_log( 'error with team update' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( $wpdb->last_error ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
		} else {
			$racketmanager->set_message( $this->msg_no_update );
		}
	}

	/**
	 * Update team for players
	 *
	 * @param string $player1 player 1 name.
	 * @param int    $player1_id player 1 id.
	 * @param string $player2 player 2 name.
	 * @param int    $player2_id player 2 id.
	 * @param int    $club_id affiliated club id.
	 */
	public function update_player( $player1, $player1_id, $player2, $player2_id, $club_id ) {
		global $wpdb, $racketmanager;

		$players   = array();
		$players[] = $player1_id;
		$title     = $player1;
		if ( $player2_id ) {
			$title    .= ' / ' . $player2;
			$players[] = $player2_id;
		}

		$club    = get_club( $club_id );
		$stadium = $club->name;
		if ( $this->title !== $title || $this->affiliatedclub !== $club_id || $this->roster !== $players || $this->stadium !== $stadium ) {
			$result = $wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->racketmanager_teams} SET `title` = %s, `affiliatedclub` = %d, `stadium` = %s, `roster` = %s WHERE `id` = %d",
					$title,
					$club_id,
					$stadium,
					maybe_serialize( $players ),
					$this->id
				)
			); // db call ok, no cache ok.
			if ( $result ) {
				wp_cache_delete( $this->id, 'teams' );
				$racketmanager->set_message( $this->msg_team_updated );
			} else {
				$racketmanager->set_message( $this->msg_team_update_error, true );
				error_log( 'Error with player team update' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( $wpdb->last_error ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
		} else {
			$racketmanager->set_message( $this->msg_no_update );
		}
	}

	/**
	 * Set event
	 *
	 * @param int    $event_id event id.
	 * @param string $captain optional captain id.
	 * @param string $contact_no optional contact number.
	 * @param string $contact_email optional contact email.
	 * @param int    $match_day optional match day.
	 * @param int    $match_time optional match time.
	 * @return boolean
	 */
	public function set_event( $event_id, $captain = null, $contact_no = null, $contact_email = null, $match_day = null, $match_time = null ) {
		global $wpdb, $racketmanager;

		$count = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->racketmanager_team_events} WHERE `team_id` = %d AND `event_id` = %d",
				$this->id,
				$event_id
			)
		);
		if ( $count ) {
			if ( $captain ) {
				$msg = $this->update_event( $event_id, $captain, $contact_no, $contact_email, $match_day, $match_time );
			} else {
				$msg = __( 'Team added', 'racketmanager' );
			}
			$racketmanager->set_message( $msg );
		} else {
			$this->add_event( $event_id, $captain, $contact_no, $contact_email, $match_day, $match_time );
			$racketmanager->set_message( $this->msg_team_added );
		}

		return true;
	}
	/**
	 * Add team to event
	 *
	 * @param int    $event_id event id.
	 * @param string $captain captain id.
	 * @param string $contactno optional contact number.
	 * @param string $contactemail optional contact email.
	 * @param int    $matchday optional match day.
	 * @param int    $matchtime optional match time.
	 * @return $team_event_id
	 */
	public function add_event( $event_id, $captain = null, $contactno = null, $contactemail = null, $matchday = '', $matchtime = null ) {
		global $wpdb;

		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"INSERT INTO {$wpdb->racketmanager_team_events} (`team_id`, `event_id`, `captain`, `match_day`, `match_time`) VALUES (%d, %d, %d, %s, %s)",
				$this->id,
				$event_id,
				$captain,
				$matchday,
				$matchtime
			)
		);
		$team_event_id = $wpdb->insert_id;
		if ( $captain ) {
			$player = get_player( $captain );
			$player->update_contact( $contactno, $contactemail );
		}
		return $team_event_id;
	}

	/**
	 * Update event details
	 *
	 * @param int    $event_id event id.
	 * @param string $captain captain id.
	 * @param string $contactno optional contact number.
	 * @param string $contactemail optional contact email.
	 * @param int    $matchday optional match day.
	 * @param int    $matchtime optional match time.
	 * @return $team_event_id
	 */
	public function update_event( $event_id, $captain, $contactno, $contactemail, $matchday, $matchtime ) {
		global $wpdb;
		$updates = false;
		$msg     = false;
		$event   = get_event( $event_id );
		$current = $event->get_team_info( $this->id );
		if ( $current->captain_id !== $captain || $current->match_day !== $matchday || $current->match_time !== $matchtime ) {
			if ( $captain && ( ( 'team' === $event->competition->entry_type && $matchday && $matchtime ) || 'player' === $event->competition->entry_type ) ) {
				$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						"UPDATE {$wpdb->racketmanager_team_events} SET `captain` = %s, `match_day` = %s, `match_time` = %s WHERE `team_id` = %d AND `event_id` = %d",
						$captain,
						$matchday,
						$matchtime,
						$this->id,
						$event_id
					)
				);
				$updates = true;
			} else {
				$msg = $this->msg_details_missing;
			}
		}
		if ( $current->contactno !== $contactno || $current->contactemail !== $contactemail ) {
			$player = get_player( $captain );
			if ( $player ) {
				$updates = $player->update_contact( $contactno, $contactemail );
				if ( ! $updates ) {
					$msg = $this->msg_team_contact_error;
				}
			} else {
				$msg = $this->player_not_found_error;
			}
		}
		if ( $updates ) {
			$msg = $this->msg_team_updated;
		} elseif ( empty( $msg ) ) {
			$msg = $this->msg_no_update;
		}

		return $msg;
	}

	/**
	 * Delete team
	 */
	public function delete() {
		global $wpdb, $racketmanager;

		// remove matches and rubbers.
		$matches = $racketmanager->get_matches( array( 'team' => $this->id ) );
		foreach ( $matches as $match ) {
			$match = get_match( $match->id );
			$match->delete();
		}
		// remove tables.
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE FROM {$wpdb->racketmanager_table} WHERE `team_id` = %d",
				$this->id
			)
		);
		// remove team event.
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE FROM {$wpdb->racketmanager_team_events} WHERE `team_id` = %d",
				$this->id
			)
		);
		// remove team.
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE FROM {$wpdb->racketmanager_teams} WHERE `id` = %d",
				$this->id
			)
		);
	}

	/**
	 * Update title
	 *
	 * @param string $title title.
	 */
	public function update_title( $title ) {
		global $wpdb;
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"UPDATE {$wpdb->racketmanager_teams} SET `title` = %s WHERE `id` = %d",
				$title,
				$this->id
			)
		);
	}
}
