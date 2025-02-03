<?php
/**
 * AJAX admin response methods

 * @package    RacketManager
 * @subpackage RacketManager_AJAX
 */

namespace Racketmanager;

/**
 * Implement AJAX responses for admin only functions.
 *
 * @author Paul Moffat
 */
class Racketmanager_Ajax_Admin extends Racketmanager_Ajax {
	/**
	 * Register ajax actions.
	 */
	public function __construct() {
		parent::__construct();
		add_action( 'wp_ajax_racketmanager_save_add_points', array( &$this, 'save_add_points' ) );
		add_action( 'wp_ajax_racketmanager_insert_home_stadium', array( &$this, 'insert_home_stadium' ) );
		add_action( 'wp_ajax_racketmanager_get_season_dropdown', array( &$this, 'set_season_dropdown' ) );
		add_action( 'wp_ajax_racketmanager_get_match_dropdown', array( &$this, 'set_match_dropdown' ) );
		add_action( 'wp_ajax_racketmanager_check_team_exists', array( &$this, 'check_team_exists' ) );
		add_action( 'wp_ajax_racketmanager_get_player_clubs', array( &$this, 'get_player_clubs' ) );
		add_action( 'wp_ajax_racketmanager_show_match_header', array( &$this, 'show_admin_match_header' ) );
		add_action( 'wp_ajax_racketmanager_show_rubbers', array( &$this, 'show_rubbers' ) );

		add_action( 'wp_ajax_racketmanager_email_constitution', array( &$this, 'email_constitution' ) );
		add_action( 'wp_ajax_racketmanager_notify_competition_entries_open', array( &$this, 'notify_competition_entries_open' ) );
		add_action( 'wp_ajax_racketmanager_notify_tournament_entries_open', array( &$this, 'notify_tournament_entries_open' ) );

		add_action( 'wp_ajax_racketmanager_notify_teams', array( &$this, 'notify_teams_fixture' ) );
		add_action( 'wp_ajax_racketmanager_chase_match_result', array( &$this, 'chase_match_result_email' ) );
		add_action( 'wp_ajax_racketmanager_chase_match_approval', array( &$this, 'chase_match_approval_email' ) );
		add_action( 'wp_ajax_racketmanager_set_tournament_dates', array( &$this, 'set_tournament_dates' ) );
		add_action( 'wp_ajax_racketmanager_send_fixtures', array( &$this, 'send_fixtures' ) );
	}

	/**
	 * AJAX response to manually set additional points
	 *
	 * @see admin/standings.php
	 */
	public function save_add_points() {
		global $wpdb;
		$valid   = true;
		$message = null;
		if ( isset( $_POST['security'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), 'ajax-nonce' ) ) {
				$valid   = false;
				$message = __( 'Security token invalid', 'racketmanager' );
			}
		} else {
			$valid   = false;
			$message = __( 'No security token found in request', 'racketmanager' );
		}
		if ( $valid ) {
			$league_id = isset( $_POST['league_id'] ) ? intval( $_POST['league_id'] ) : null;
			if ( $league_id ) {
				$league = get_league( $league_id );
				if ( $league ) {
					$team_id = isset( $_POST['team_id'] ) ? intval( $_POST['team_id'] ) : null;
					if ( $team_id ) {
						$season = isset( $_POST['season'] ) ? sanitize_text_field( wp_unslash( $_POST['season'] ) ) : null;
						if ( $season ) {
							$add_points = isset( $_POST['points'] ) ? intval( $_POST['points'] ) : 0;
							$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
								$wpdb->prepare(
									"UPDATE {$wpdb->racketmanager_table} SET `add_points` = %s WHERE `team_id` = %d AND `league_id` = %d AND `season` = %s",
									$add_points,
									$team_id,
									$league->id,
									$season
								)
							);
							$league->set_teams_rank( $season );
						}
					}
				}
			}
		}
		if ( $valid ) {
			wp_send_json_success();
		} else {
			wp_send_json_error( $message, '500' );
		}
	}
	/**
	 * Insert home team stadium if available
	 *
	 * @see admin/match.php
	 */
	public function insert_home_stadium() {
		$valid   = true;
		$message = null;
		if ( isset( $_POST['security'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), 'ajax-nonce' ) ) {
				$valid   = false;
				$message = __( 'Security token invalid', 'racketmanager' );
			}
		} else {
			$valid   = false;
			$message = __( 'No security token found in request', 'racketmanager' );
		}
		if ( $valid ) {
			$stadium = '';
			if ( isset( $_POST['team_id'] ) ) {
				$team_id = get_team( intval( $_POST['team_id'] ) );
				$team    = get_team( $team_id );
				if ( $team ) {
					$stadium = trim( $team->stadium );
				}
			}
		}
		if ( $valid ) {
			wp_send_json_success( $stadium );
		} else {
			wp_send_json_error( $message, '500' );
		}
	}
	/**
	 * Set season dropdown for post metabox for match report
	 *
	 * @see admin/admin.php
	 */
	public function set_season_dropdown() {
		$valid   = true;
		$message = null;
		if ( isset( $_POST['security'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), 'ajax-nonce' ) ) {
				$valid   = false;
				$message = __( 'Security token invalid', 'racketmanager' );
			}
		} else {
			$valid   = false;
			$message = __( 'No security token found in request', 'racketmanager' );
		}
		if ( $valid ) {
			if ( isset( $_POST['league_id'] ) ) {
				$league = get_league( intval( $_POST['league_id'] ) );
				$output = $league->get_season_dropdown( true );
			} else {
				$message = __( 'League not selected', 'racketmanager' );
			}
		}
		if ( $valid ) {
			wp_send_json_success( $output );
		} else {
			wp_send_json_error( $message, '500' );
		}
	}
	/**
	 * Set matches dropdown for post metabox for match report
	 *
	 * @see admin/admin.php
	 */
	public function set_match_dropdown() {
		$valid   = true;
		$message = null;
		if ( isset( $_POST['security'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), 'ajax-nonce' ) ) {
				$valid   = false;
				$message = __( 'Security token invalid', 'racketmanager' );
			}
		} else {
			$valid   = false;
			$message = __( 'No security token found in request', 'racketmanager' );
		}
		if ( $valid ) {
			if ( isset( $_POST['league_id'] ) ) {
				$league = get_league( intval( $_POST['league_id'] ) );
				if ( isset( $_POST['season'] ) ) {
					$league->set_season( htmlspecialchars( sanitize_text_field( wp_unslash( $_POST['season'] ) ) ) );
					$output = $league->get_match_dropdown();
				}
			} else {
				$message = __( 'Season not selected', 'racketmanager' );
			}
		}
		if ( $valid ) {
			wp_send_json_success( $output );
		} else {
			wp_send_json_error( $message, '500' );
		}
	}
	/**
	 * Ajax Response to get check if Team Exists
	 */
	public function check_team_exists() {
		global $racketmanager;
		$valid   = true;
		$message = null;
		if ( isset( $_POST['security'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), 'ajax-nonce' ) ) {
				$valid   = false;
				$message = __( 'Security token invalid', 'racketmanager' );
			}
		} else {
			$valid   = false;
			$message = __( 'No security token found in request', 'racketmanager' );
		}
		if ( $valid ) {
			$found = false;
			if ( isset( $_POST['name'] ) ) {
				$name = stripslashes( sanitize_text_field( wp_unslash( $_POST['name'] ) ) );
				$team = $racketmanager->getteam_id( $name );
				if ( $team ) {
					$found = true;
				}
			}
		}
		if ( $valid ) {
			wp_send_json_success( $found );
		} else {
			wp_send_json_error( $message, '500' );
		}
	}
	/**
	 * Get clubs for player
	 */
	public function get_player_clubs() {
		$return = new \stdClass();
		if ( isset( $_POST['security'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), 'ajax-nonce' ) ) {
				$return->error = true;
				$return->msg   = __( 'Security token invalid', 'racketmanager' );
			}
		} else {
			$return->error = true;
			$return->msg   = __( 'No security token found in request', 'racketmanager' );
		}
		if ( ! isset( $return->error ) ) {
			$player_id = isset( $_POST['player'] ) ? intval( $_POST['player'] ) : null;
			if ( $player_id ) {
				$player = get_player( $player_id );
				if ( $player ) {
					$player_clubs = $player->get_clubs();
				} else {
					$return->error = true;
					$return->msg   = __( 'Player not found', 'racketmanager' );
				}
				$return->msg = __( 'Captains emailed', 'racketmanager' );
			} else {
				$return->error = true;
				$return->msg   = __( 'No player passed', 'racketmanager' );
			}
		}
		if ( isset( $return->error ) ) {
			wp_send_json_error( $return->msg, 500 );
		} else {
			wp_send_json_success( $player_clubs );
		}
	}
	/**
	 * Build screen to show match header
	 */
	public function show_admin_match_header() {
		$match_id = isset( $_GET['matchId'] ) ? intval( $_GET['matchId'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$match    = get_match( $match_id );
		if ( $match ) {
			ob_start();
			?>
			<div class="row justify-content-between" id="match-header-1">
				<div class="col-auto leaguetitle"><?php echo esc_html( $match->league->title ); ?></div>
				<?php if ( isset( $match->match_day ) && $match->match_day > 0 ) { ?>
					<div class="col-auto matchday">Week <?php echo esc_html( $match->match_day ); ?></div>
				<?php } ?>
				<div class="col-auto matchdate"><?php echo esc_html( substr( $match->date, 0, 10 ) ); ?></div>
			</div>
			<div class="row justify-content-center" id="match-header-2">
				<div class="col-auto matchtitle"><?php echo esc_html( $match->match_title ); ?></div>
			</div>
			<?php
			$output = ob_get_contents();
			ob_end_clean();
			wp_send_json_success( $output );
		} else {
			wp_send_json_error( __( 'Match not found', 'racketmanager' ), 500 );
		}
	}
	/**
	 * Build screen to allow input of match rubber scores
	 */
	public function show_rubbers() {
		global $racketmanager;

		$match_id = isset( $_GET['matchId'] ) ? intval( $_GET['matchId'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$match    = get_match( $match_id );
		if ( $match ) {
			$output = $racketmanager->show_match_screen( $match, true );
			wp_send_json_success( $output );
		} else {
			wp_send_json_error( __( 'Match not found', 'racketmanager' ), 500 );
		}
	}
	/**
	 * Notify match secretaries of competition entries open
	 *
	 * @see templates/email/competition-entry-open.php
	 */
	public function email_constitution() {
		global $racketmanager;
		$return = new \stdClass();
		if ( isset( $_POST['security'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), 'ajax-nonce' ) ) {
				$return->error = true;
				$return->msg   = __( 'Security token invalid', 'racketmanager' );
			}
		} else {
			$return->error = true;
			$return->msg   = __( 'No security token found in request', 'racketmanager' );
		}
		if ( ! isset( $return->error ) ) {
			$event_id = isset( $_POST['eventId'] ) ? intval( $_POST['eventId'] ) : null;
			if ( ! $event_id ) {
				$return->error = true;
				$return->msg   = __( 'Event not specified', 'racketmanager' );
			} else {
				$event = get_event( $event_id );
				if ( ! $event ) {
					$return->error = true;
					$return->msg   = __( 'Event not found', 'racketmanager' );
				} else {
					$season = $event->current_season;
					$event->send_constitution( $season );
					$return->msg = __( 'Constitution emailed', 'racketmanager' );
				}
			}
		}
		if ( isset( $return->error ) ) {
			wp_send_json_error( $return->msg, 500 );
		} else {
			wp_send_json_success( $return->msg );
		}
	}
	/**
	 * Notify match secretaries of competition entries open
	 *
	 * @see templates/email/competition-entry-open.php
	 */
	public function notify_competition_entries_open() {
		global $racketmanager;
		$return = new \stdClass();
		if ( isset( $_POST['security'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), 'ajax-nonce' ) ) {
				$return->error = true;
				$return->msg   = __( 'Security token invalid', 'racketmanager' );
			}
		} else {
			$return->error = true;
			$return->msg   = __( 'No security token found in request', 'racketmanager' );
		}
		if ( ! isset( $return->error ) ) {
			$competition_id = isset( $_POST['competitionId'] ) ? intval( $_POST['competitionId'] ) : null;
			if ( ! $competition_id ) {
				$return->error = true;
				$return->msg   = __( 'Competition not specified', 'racketmanager' );
			} else {
				$season = isset( $_POST['season'] ) ? intval( $_POST['season'] ) : null;
				if ( ! $season ) {
					$return->error = true;
					$return->msg   = __( 'Season not specified', 'racketmanager' );
				} else {
					$competition = get_competition( $competition_id );
					if ( ! $competition ) {
						$return->error = true;
						$return->msg   = __( 'Competition not found', 'racketmanager' );
					} elseif ( isset( $competition->seasons[ $season ] ) ) {
						if ( 'team' === $competition->entry_type ) {
							$return = $racketmanager->notify_team_entry_open( $competition->id, $season );
						} else {
							$return->error = true;
							$return->msg   = __( 'Invalid competition entry type', 'racketmanager' );
						}
					} else {
						$return->error = true;
						$return->msg   = __( 'Season not found for competition', 'racketmanager' );
					}
				}
			}
		}
		if ( isset( $return->error ) ) {
			wp_send_json_error( $return->msg, 500 );
		} else {
			wp_send_json_success( $return->msg );
		}
	}
	/**
	 * Notify match secretaries of tournament entries open
	 *
	 * @see templates/email/competition-entry-open.php
	 */
	public function notify_tournament_entries_open() {
		global $racketmanager;
		$return = new \stdClass();
		if ( isset( $_POST['security'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), 'ajax-nonce' ) ) {
				$return->error = true;
				$return->msg   = __( 'Security token invalid', 'racketmanager' );
			}
		} else {
			$return->error = true;
			$return->msg   = __( 'No security token found in request', 'racketmanager' );
		}
		if ( ! isset( $return->error ) ) {
			$tournament_id = isset( $_POST['tournamentId'] ) ? intval( $_POST['tournamentId'] ) : '';
			$tournament    = get_tournament( $tournament_id );
			if ( $tournament ) {
				$return = $tournament->notify_entry_open();
			} else {
				$return->error = true;
				$return->msg   = __( 'Tournament not found', 'racketmanager' );
			}
		}
		if ( isset( $return->error ) ) {
			wp_send_json_error( $return->msg, 500 );
		} else {
			wp_send_json_success( $return->msg );
		}
	}
	/**
	 * Notify teams of next match
	 *
	 * @see templates/email/match-notification.php
	 */
	public function notify_teams_fixture() {
		global $match, $racketmanager;
		$valid = 'true';
		if ( isset( $_POST['security'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), 'ajax-nonce' ) ) {
				$valid   = false;
				$message = __( 'Security token invalid', 'racketmanager' );
			}
		} else {
			$valid   = false;
			$message = __( 'No security token found in request', 'racketmanager' );
		}
		if ( $valid ) {
			$message_sent = false;
			if ( isset( $_POST['matchId'] ) ) {
				$match        = get_match( sanitize_text_field( wp_unslash( $_POST['matchId'] ) ) );
				$message_sent = $match->notify_next_match_teams();
			}
			if ( $message_sent ) {
				$message = __( 'Teams notified', 'racketmanager' );
			} else {
				$valid   = false;
				$message = __( 'No notification', 'racketmanager' );
			}
		}
		if ( $valid ) {
			wp_send_json_success( array( 'message' => $message ) );
		} else {
			wp_send_json_error( $message, '500' );
		}
	}
	/**
	 * Contact captain for match result
	 *
	 * @see templates/email/match-result-pending.php
	 */
	public function chase_match_result_email() {
		global $racketmanager;
		$return = new \stdClass();
		if ( isset( $_POST['security'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), 'ajax-nonce' ) ) {
				$return->error = true;
				$return->msg   = __( 'Security token invalid', 'racketmanager' );
			}
		} else {
			$return->error = true;
			$return->msg   = __( 'No security token found in request', 'racketmanager' );
		}
		if ( ! isset( $return->error ) ) {
			$match_id     = isset( $_POST['matchId'] ) ? intval( $_POST['matchId'] ) : '';
			$message_sent = false;
			$message_sent = $racketmanager->chase_match_result( $match_id );
			if ( $message_sent ) {
				$return->msg = __( 'Captain emailed', 'racketmanager' );
			} else {
				$return->error = true;
				$return->msg   = __( 'No notification', 'racketmanager' );
			}
		}
		if ( isset( $return->error ) ) {
			wp_send_json_error( $return->msg, 500 );
		} else {
			wp_send_json_success( $return );
		}
	}
	/**
	 * Contact captain for match approval
	 *
	 * @see templates/email/match-approval-pending.php
	 */
	public function chase_match_approval_email() {
		global $racketmanager;
		$return = new \stdClass();
		if ( isset( $_POST['security'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), 'ajax-nonce' ) ) {
				$return->error = true;
				$return->msg   = __( 'Security token invalid', 'racketmanager' );
			}
		} else {
			$return->error = true;
			$return->msg   = __( 'No security token found in request', 'racketmanager' );
		}
		if ( ! isset( $return->error ) ) {
			$match_id     = isset( $_POST['matchId'] ) ? intval( $_POST['matchId'] ) : '';
			$message_sent = false;
			$return       = array();
			$message_sent = $racketmanager->chase_match_approval( $match_id );
			if ( $message_sent ) {
				$return['msg'] = __( 'Captain emailed', 'racketmanager' );
			} else {
				$return['error'] = true;
				$return['msg']   = __( 'No notification', 'racketmanager' );
			}
		}
		if ( isset( $return->error ) ) {
			wp_send_json_error( $return->msg, 500 );
		} else {
			wp_send_json_success( $return );
		}
	}
	/**
	 * Send fixtures to captains
	 *
	 * @see templates/email/send_fixtures.php
	 */
	public function send_fixtures() {
		global $racketmanager, $racketmanager_shortcodes, $event;
		$return = new \stdClass();
		if ( isset( $_POST['security'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), 'ajax-nonce' ) ) {
				$return->error = true;
				$return->msg   = __( 'Security token invalid', 'racketmanager' );
			}
		} else {
			$return->error = true;
			$return->msg   = __( 'No security token found in request', 'racketmanager' );
		}
		if ( ! isset( $return->error ) ) {
			$event_id          = isset( $_POST['eventId'] ) ? intval( $_POST['eventId'] ) : null;
			$event             = get_event( $event_id );
			$season            = $event->current_season['name'];
			$message_sent      = false;
			$return            = array();
			$from_email        = $this->get_confirmation_email( $event->competition->type );
			$organisation_name = $racketmanager->site_name;
			$leagues           = $event->get_leagues( array() );
			foreach ( $leagues as $league ) {
				$league = get_league( $league->id );
				$teams  = $league->get_league_teams( array( 'get_details' => true ) );
				foreach ( $teams as $team ) {
					$matches       = $league->get_matches(
						array(
							'final'   => '',
							'team_id' => $team->id,
						)
					);
					$headers       = array();
					$headers[]     = 'From: ' . ucfirst( $event->competition->type ) . ' Secretary <' . $from_email . '>';
					$email_subject = $racketmanager->site_name . ' - ' . $league->title . ' - Season ' . $team->season . ' - Fixtures - ' . $team->title;
					$email_to      = '';
					if ( isset( $team->contactemail ) ) {
						$email_to = $team->captain . ' <' . $team->contactemail . '>';
						$club     = get_club( $team->club_id );
						if ( isset( $club->match_secretary_email ) ) {
							$headers[] = 'cc: ' . $club->match_secretary_name . ' <' . $club->match_secretary_email . '>';
						}
						$action_url    = $racketmanager->site_url . '/' . $event->competition->type . '/' . seo_url( $league->title ) . '/' . $team->season . '/' . __( 'team', 'racketmanager' ) . '/' . seo_url( $team->title );
						$email_message = $racketmanager_shortcodes->load_template(
							'send-fixtures',
							array(
								'competition'   => $event->name,
								'captain'       => $team->captain,
								'season'        => $season,
								'matches'       => $matches,
								'team'          => $team,
								'action_url'    => $action_url,
								'organisation'  => $organisation_name,
								'contact_email' => $from_email,
							),
							'email'
						);
						wp_mail( $email_to, $email_subject, $email_message, $headers );
						$message_sent = true;
					}
				}
			}
			if ( $message_sent ) {
				$return['msg'] = __( 'Captains emailed', 'racketmanager' );
			} else {
				$return['error'] = true;
				$return['msg']   = __( 'No notification', 'racketmanager' );
			}
		}
		if ( isset( $return->error ) ) {
			wp_send_json_error( $return->msg, 500 );
		} else {
			wp_send_json_success( $return );
		}
	}
	/**
	 * Set tournament dates for open/close/withdrawal based on start date and grade
	 *
	 * @see templates/email/match-approval-pending.php
	 */
	public function set_tournament_dates() {
		global $racketmanager;
		$return = new \stdClass();
		if ( isset( $_POST['security'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), 'ajax-nonce' ) ) {
				$return->error = true;
				$return->msg   = __( 'Security token invalid', 'racketmanager' );
			}
		} else {
			$return->error = true;
			$return->msg   = __( 'No security token found in request', 'racketmanager' );
		}
		if ( ! isset( $return->error ) ) {
			$grade      = isset( $_POST['grade'] ) ? sanitize_text_field( wp_unslash( $_POST['grade'] ) ) : '';
			$date_start = isset( $_POST['dateStart'] ) ? sanitize_text_field( wp_unslash( $_POST['dateStart'] ) ) : null;
			if ( $date_start ) {
				$parameters = $racketmanager->get_options( 'championship' );
				if ( $parameters ) {
					$date_open     = Racketmanager_Util::amend_date( $date_start, $parameters['open_lead_time'], '-' );
					$date_closing  = Racketmanager_Util::amend_date( $date_start, $parameters['date_closing'][ $grade], '-' );
					$date_withdraw = Racketmanager_Util::amend_date( $date_start, $parameters['date_withdrawal'][ $grade ], '-' );
				} else {
					$return->error = true;
					$return->msg   = __( 'No lead time parameters set', 'racketmanager' );
				}
			} else {
				$return->error = true;
				$return->msg   = __( 'No start date specified', 'racketmanager' );
			}
		}
		if ( isset( $return->error ) ) {
			wp_send_json_error( $return->msg, 500 );
		} else {
			$return->msg           = __( 'Dates set', 'racketmanager' );
			$return->date_open     = $date_open;
			$return->date_closing  = $date_closing;
			$return->date_withdraw = $date_withdraw;
			wp_send_json_success( $return );
		}
	}
}
