<?php
/**
 * Racketmanager_Results_Checker API: Racketmanager_Results_Checker class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Racketmanager_Results_Checker
 */

namespace Racketmanager;

/**
 * Class to implement the results checker object
 */
final class Racketmanager_Results_Checker {
	/**
	 * Id
	 *
	 * @var int
	 */
	public int $id;
	/**
	 * Match id
	 *
	 * @var int
	 */
	public int $match_id;
	/**
	 * Team id
	 *
	 * @var int
	 */
	public int $team_id;
	/**
	 * Player id
	 *
	 * @var int|null
	 */
	public ?int $player_id;
	/**
	 * League Id
	 *
	 * @var int
	 */
	public int $league_id;
	/**
	 * Rubber Id
	 *
	 * @var int|null
	 */
	public ?int $rubber_id;
	/**
	 * Status
	 *
	 * @var int|null
	 */
	public ?int $status;
	/**
	 * Status description
	 *
	 * @var string
	 */
	public string $status_desc;
	/**
	 * Description
	 *
	 * @var string
	 */
	public string $description;
	/**
	 * Updated date
	 *
	 * @var string|null
	 */
	public ?string $updated_date;
	/**
	 * Updated user id
	 *
	 * @var int|null
	 */
	public ?int $updated_user;
	/**
	 * Updated username
	 *
	 * @var string|int|null
	 */
	public string|int|null $updated_user_name;
	/**
	 * Match
	 *
	 * @var Racketmanager_Match|null
	 */
	public null|Racketmanager_Match $match;
	/**
	 * Player
	 *
	 * @var object|null
	 */
	public mixed $player = null;
	/**
	 * Team
	 *
	 * @var object
	 */
	public mixed $team;
	/**
	 * Results checker object
	 *
	 * @var object
	 */
	public object $data;
	/**
	 * Get class instance
	 *
	 * @param int $results_checker_id id.
	 */
	public static function get_instance( int $results_checker_id ) {
		global $wpdb;
		if ( ! $results_checker_id ) {
			return false;
		}
		$results_checker = wp_cache_get( $results_checker_id, 'results_checker' );

		if ( ! $results_checker ) {
			$results_checker = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT `id`, `league_id`, `match_id`, `team_id`, `player_id`, `rubber_id`, `updated_date`, `updated_user`, `description`, `status` FROM $wpdb->racketmanager_results_checker WHERE `id` = %d LIMIT 1",
					$results_checker_id
				)
			);  // db call ok.

			if ( ! $results_checker ) {
				return false;
			}

			$results_checker = new Racketmanager_Results_Checker( $results_checker );

			wp_cache_set( $results_checker->id, $results_checker, 'results_checker' );
		}

		return $results_checker;
	}

	/**
	 * Construct class instance
	 *
	 * @param object|null $results_checker results_checker object.
	 */
	public function __construct( object $results_checker = null ) {
		if ( ! is_null( $results_checker ) ) {
			foreach ( get_object_vars( $results_checker ) as $key => $value ) {
				$this->$key = $value;
			}
			if ( ! isset( $this->id ) ) {
				$this->add();
			}
			$this->match = get_match( $this->match_id );
			$this->team  = null;
			if ( $this->team_id > 0 ) {
				if ( $this->team_id === intval( $this->match->home_team ) ) {
					$this->team = $this->match->teams['home'];
				} elseif ( $this->team_id === intval( $this->match->away_team ) ) {
					$this->team = $this->match->teams['away'];
				}
			}
			if ( ! empty( $player_id ) ) {
				$player = get_player( $this->player_id );
				if ( $player ) {
					$this->player = $player;
				}
			}
			$this->updated_user_name = '';
			if ( ! empty( $this->updated_user ) ) {
				$user = get_userdata( $this->updated_user );
				if ( $user ) {
					$this->updated_user_name = $user->display_name;
				}
			}
			if ( 1 === $this->status ) {
				$this->status_desc = 'Approved';
			} elseif ( 2 === $this->status ) {
				$this->status_desc = 'Handled';
			} else {
				$this->status_desc = '';
			}
		}
	}
	/**
	 * Add new results checker entry
	 */
	private function add(): void {
		global $wpdb;
		if ( empty( $this->player_id ) ) {
			$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"INSERT INTO $wpdb->racketmanager_results_checker (`league_id`, `match_id`, `team_id`, `description`) values ( %d, %d, %d, %s) ",
					$this->league_id,
					$this->match_id,
					$this->team_id,
					$this->description
				)
			);
		} else {
			$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"INSERT INTO $wpdb->racketmanager_results_checker (`league_id`, `match_id`, `team_id`, `player_id`, `rubber_id`, `description`) values ( %d, %d, %d, %d, %d, %s) ",
					$this->league_id,
					$this->match_id,
					$this->team_id,
					$this->player_id,
					$this->rubber_id,
					$this->description
				)
			);
		}
		$this->status = null;
		$this->id     = $wpdb->insert_id;
	}
	/**
	 * Delete results checker
	 */
	public function delete(): void {
		global $wpdb;
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE FROM $wpdb->racketmanager_results_checker WHERE `id` = %d",
				$this->id
			)
		);
	}
	/**
	 * Update entry
	 *
	 * @param int $status status.
	 */
	public function update( int $status ): void {
		global $wpdb;
		$this->status = $status;
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"UPDATE $wpdb->racketmanager_results_checker SET `updated_date` = now(), `updated_user` = %d, `status` = %d WHERE `id` = %d ",
				get_current_user_id(),
				$status,
				$this->id
			)
		);
	}
	/**
	 * Approve entry
	 */
	public function approve(): void {
		global $racketmanager;
		if ( empty( $this->updated_date ) ) {
			$this->update( 1 );
			$racketmanager->set_message( __( 'Result check approved', 'racketmanager' ) );
		}
	}
	/**
	 * Handle entry
	 */
	public function handle(): void {
		global $racketmanager;
		if ( ! empty( $this->updated_date ) ) {
			$racketmanager->set_message( __( 'Result check already handled', 'racketmanager' ), 'error' );
			return;
		}
		if ( empty( $this->match ) ) {
			$racketmanager->set_message( __( 'Match not specified', 'racketmanager' ), 'error' );
		} else {
			$racketmanager->set_message( __( 'Result check handled', 'racketmanager' ) );
			$this->update( 2 );
			if ( empty( $this->player_id ) ) {
				$this->handle_match_error();
			} else {
				$this->handle_player_error();
			}
		}
	}
	/**
	 * Handle match error entry
	 */
	public function handle_match_error(): void {
		global $racketmanager;
		$match      = $this->match;
		$point_rule = $this->match->league->get_point_rule();
		$penalty    = empty( $point_rule['result_late'] ) ? 0 : $point_rule['result_late'];
		$comments   = $match->comments;
		if ( $penalty ) {
			$comment  = __( 'Penalty', 'racketmanager' ) . ': ';
			$comment .= sprintf( _n( '%d point deduction for', '%d points deduction for', $penalty, 'racketmanager' ), $penalty ) . ' ';
		} else {
			$comment = null;
		}
		$comment           .= $this->description;
		$comments['result'] = $comment;
		$match->set_comments( $comments );
		$match->update_result_with_penalty( 'home', $penalty );
		$match->update_league_with_result();
		$organisation_name = $racketmanager->site_name;
		$headers           = array();
		$email_from        = $racketmanager->get_confirmation_email( $match->league->event->competition->type );
		$headers[]         = 'From: ' . ucfirst( $match->league->event->competition->type ) . ' Secretary <' . $email_from . '>';
		$headers[]         = 'cc: ' . ucfirst( $match->league->event->competition->type ) . ' Secretary <' . $email_from . '>';
		$email_subject     = $racketmanager->site_name . ' - ' . $match->teams['home']->title . ' - ' . $match->teams['away']->title . ' - ' . __( 'result late', 'racketmanager' );
		if ( $this->team_id === intval( $match->home_team ) ) {
			$captain   = $match->teams['home']->captain;
			$email_to  = $match->teams['home']->captain . ' <' . $match->teams['home']->contactemail . '>';
			$headers[] = 'cc: ' . $match->teams['away']->captain . ' <' . $match->teams['away']->contactemail . '>';
		} elseif ( $this->team_id === intval( $match->away_team ) ) {
			$captain   = $match->teams['away']->captain;
			$email_to  = $match->teams['away']->captain . ' <' . $match->teams['away']->contactemail . '>';
			$headers[] = 'cc: ' . $match->teams['home']->captain . ' <' . $match->teams['home']->contactemail . '>';
		} else {
			$captain  = null;
			$email_to = null;
		}
		$headers[]     = 'cc: ' . $match->teams['home']->club->match_secretary_name . ' <' . $match->teams['home']->club->match_secretary_email . '>';
		$headers[]     = 'cc: ' . $match->teams['away']->club->match_secretary_name . ' <' . $match->teams['away']->club->match_secretary_email . '>';
		$email_message = $racketmanager->shortcodes->load_template(
			'result-check-match',
			array(
				'email_subject' => $email_subject,
				'organisation'  => $organisation_name,
				'captain'       => $captain,
				'reason'        => $this->description,
				'contact_email' => $email_from,
				'penalty'       => $penalty,
			),
			'email'
		);
		wp_mail( $email_to, $email_subject, $email_message, $headers );
	}
	/**
	 * Handle player error entry
	 */
	public function handle_player_error(): void {
		global $racketmanager;
		$match   = $this->match;
		$penalty = false;
		if ( 'league' === $this->match->league->event->competition->type ) {
			$point_rule = $this->match->league->get_point_rule();
			$penalty    = empty( $point_rule['forwalkover_rubber'] ) ? false : $point_rule['forwalkover_rubber'];
		}
		$rubber = get_rubber( $this->rubber_id );
		if ( $rubber ) {
			$num_sets_to_win  = $this->match->league->num_sets_to_win;
			$num_games_to_win = 1;
			$set_type         = $rubber->sets[1]['settype'] ?? null;
			if ( $set_type ) {
				$set_info = Racketmanager_Util::get_set_info( $set_type );
				if ( $set_info ) {
					$num_games_to_win = $set_info->min_win;
				}
			}
			$points = array();
			if ( $this->team_id === intval( $match->home_team ) ) {
				$points['home']['invalid'] = true;
				if ( isset( $rubber->custom['invalid'] ) && 'away' === $rubber->custom['invalid'] ) {
					$points['away']['invalid'] = true;
					$rubber->custom['invalid'] = 'both';
					$points['away']['sets']    = 0;
					$stats['sets']['away']     = 0;
					$stats['games']['away']    = 0;
				} else {
					$rubber->custom['invalid'] = 'home';
					$points['away']['sets']    = $num_sets_to_win;
					$stats['sets']['away']     = $num_sets_to_win;
					$stats['games']['away']    = $num_games_to_win * $num_sets_to_win;
				}
				$points['home']['sets'] = 0;
				$stats['sets']['home']  = 0;
				$stats['games']['home'] = 0;
			} else {
				$points['away']['invalid'] = true;
				if ( isset( $rubber->custom['invalid'] ) && 'home' === $rubber->custom['invalid'] ) {
					$points['home']['invalid'] = true;
					$rubber->custom['invalid'] = 'both';
					$points['home']['sets']    = 0;
					$stats['sets']['home']     = 0;
					$stats['games']['home']    = 0;
				} else {
					$rubber->custom['invalid'] = 'home';
					$points['home']['sets']    = $num_sets_to_win;
					$stats['sets']['home']     = $num_sets_to_win;
					$stats['games']['home']    = $num_games_to_win * $num_sets_to_win;
				}
				$points['away']['sets'] = 0;
				$stats['sets']['away']  = 0;
				$stats['games']['away'] = 0;
			}
			$points['home']['team']  = $match->home_team;
			$points['away']['team']  = $match->away_team;
			$result                  = $rubber->calculate_result( $points );
			$rubber->home_points     = $result->home;
			$rubber->away_points     = $result->away;
			$rubber->winner_id       = $result->winner;
			$rubber->loser_id        = $result->loser;
			$rubber->custom['stats'] = $stats;
			$rubber->status          = '1';
			$rubber->update_result();
		}
		$comments = $match->comments;
		$comment  = $rubber->title . ': ' . __( 'ineligible player', 'racketmanager' ) . ' ' . $this->player->display_name . ' - ' . $this->description;
		if ( empty( $comments['result'] ) ) {
			$comments['result'] = $comment;
		} else {
			$comments['result'] .= "\n" . $comment;
		}
		$match->set_comments( $comments );
		$match->update_result( $match->home_points, $match->away_points, $match->custom, $match->confirmed );
		$match->update_league_with_result();
		$organisation_name = $racketmanager->site_name;
		$headers           = array();
		$email_from        = $racketmanager->get_confirmation_email( $match->league->event->competition->type );
		$headers[]         = 'From: ' . ucfirst( $match->league->event->competition->type ) . ' Secretary <' . $email_from . '>';
		$headers[]         = 'cc: ' . ucfirst( $match->league->event->competition->type ) . ' Secretary <' . $email_from . '>';
		$email_subject     = $racketmanager->site_name . ' - ' . $match->teams['home']->title . ' - ' . $match->teams['away']->title . ' - ' . __( 'ineligible player', 'racketmanager' );
		if ( $this->team_id === intval( $match->home_team ) ) {
			$captain   = $match->teams['home']->captain;
			$opponent  = $match->teams['away']->title;
			$email_to  = $match->teams['home']->captain . ' <' . $match->teams['home']->contactemail . '>';
			$headers[] = 'cc: ' . $match->teams['away']->captain . ' <' . $match->teams['away']->contactemail . '>';
		} elseif ( $this->team_id === intval( $match->away_team ) ) {
			$captain   = $match->teams['away']->captain;
			$opponent  = $match->teams['home']->title;
			$email_to  = $match->teams['away']->captain . ' <' . $match->teams['away']->contactemail . '>';
			$headers[] = 'cc: ' . $match->teams['home']->captain . ' <' . $match->teams['home']->contactemail . '>';
		} else {
			$captain  = null;
			$email_to = null;
			$opponent = null;
		}
		$headers[]     = 'cc: ' . $match->teams['home']->club->match_secretary_name . ' <' . $match->teams['home']->club->match_secretary_email . '>';
		$headers[]     = 'cc: ' . $match->teams['away']->club->match_secretary_name . ' <' . $match->teams['away']->club->match_secretary_email . '>';
		$email_message = $racketmanager->shortcodes->load_template(
			'result-check-player',
			array(
				'email_subject' => $email_subject,
				'organisation'  => $organisation_name,
				'captain'       => $captain,
				'opponent'      => $opponent,
				'player'        => $this->player->display_name,
				'reason'        => $this->description,
				'contact_email' => $email_from,
				'penalty'       => $penalty,
			),
			'email'
		);
		wp_mail( $email_to, $email_subject, $email_message, $headers );
	}
}
