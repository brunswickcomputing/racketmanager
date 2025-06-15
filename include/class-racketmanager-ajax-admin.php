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
        add_action( 'wp_ajax_racketmanager_get_event_dropdown', array( &$this, 'get_event_dropdown' ) );
        add_action( 'wp_ajax_racketmanager_get_league_dropdown', array( &$this, 'get_league_dropdown' ) );
		add_action( 'wp_ajax_racketmanager_get_season_dropdown', array( &$this, 'set_season_dropdown' ) );
		add_action( 'wp_ajax_racketmanager_get_match_dropdown', array( &$this, 'set_match_dropdown' ) );
		add_action( 'wp_ajax_racketmanager_check_team_exists', array( &$this, 'check_team_exists' ) );
		add_action( 'wp_ajax_racketmanager_get_player_clubs', array( &$this, 'get_player_clubs' ) );

		add_action( 'wp_ajax_racketmanager_email_constitution', array( &$this, 'email_constitution' ) );
		add_action( 'wp_ajax_racketmanager_notify_competition_entries_open', array( &$this, 'notify_competition_entries_open' ) );
		add_action( 'wp_ajax_racketmanager_notify_tournament_entries_open', array( &$this, 'notify_tournament_entries_open' ) );

		add_action( 'wp_ajax_racketmanager_notify_teams', array( &$this, 'notify_teams_fixture' ) );
		add_action( 'wp_ajax_racketmanager_set_tournament_dates', array( &$this, 'set_tournament_dates' ) );
		add_action( 'wp_ajax_racketmanager_send_fixtures', array( &$this, 'send_fixtures' ) );
	}

	/**
	 * AJAX response to manually set additional points
	 *
	 * @see admin/standings.php
	 */
	public function save_add_points(): void {
		$return = $this->check_security_token();
		if ( ! isset( $return->error ) ) {
			$table_id = isset( $_POST['table_id'] ) ? intval( $_POST['table_id'] ) : null;
			if ( $table_id ) {
				$league_entry = get_league_team( $table_id );
				if ( $league_entry ) {
                    $add_points = isset( $_POST['points'] ) ? intval( $_POST['points'] ) : 0;
                    $league_entry->amend_points( $add_points );
				} else {
					$return->error = true;
					$return->msg   = __( 'League entry not found', 'racketmanager' );
                }
			}
		}
		if ( isset( $return->error ) ) {
			wp_send_json_error( $return->msg, '500' );
		} else {
			wp_send_json_success();
		}
	}
	/**
	 * Insert home team stadium if available
	 *
	 * @see admin/match.php
	 */
	public function insert_home_stadium(): void {
		$stadium = null;
		$return  = $this->check_security_token();
		if ( empty( $return->error ) ) {
            $team_id = isset( $_POST['team_id'] ) ? intval( $_POST['team_id'] ) : null;
			if ( $team_id ) {
				$team = get_team( $team_id );
				if ( $team ) {
					$stadium = trim( $team->stadium );
				}
			}
		}
		if ( empty( $return->error ) ) {
            wp_send_json_success( $stadium );
		} else {
            wp_send_json_error( $return->msg, $return->status );
		}
	}
    /**
     * Display event dropdown
     *
     */
    public function get_event_dropdown(): void {
        $return  = $this->check_security_token();
        if ( empty( $return->error ) ) {
            $competition_id = isset( $_POST['competition_id'] ) ? intval( $_POST['competition_id'] ) : null;
            $output         = event_dropdown( $competition_id );
            wp_send_json_success( $output );
        } else {
            wp_send_json_error( $return->msg, $return->status );
        }
    }
    /**
     * Display league dropdown
     *
     */
    public function get_league_dropdown(): void {
        $return  = $this->check_security_token();
        if ( empty( $return->error ) ) {
            $event_id = isset( $_POST['event_id'] ) ? intval( $_POST['event_id'] ) : null;
            $output   = league_dropdown( $event_id );
            wp_send_json_success( $output );
        } else {
            wp_send_json_error( $return->msg, $return->status );
        }
    }
	/**
	 * Set season dropdown for post meta-box for match report
	 *
	 * @see admin/admin.php
	 */
	public function set_season_dropdown(): void {
		$return = $this->check_security_token();
		if ( ! isset( $return->error ) ) {
            $league_id = isset( $_POST['league_id'] ) ? intval( $_POST['league_id'] ) : null;
            $output    = season_dropdown( $league_id );
            wp_send_json_success( $output );
		} else {
			wp_send_json_error( $return->msg, $return->status );
		}
	}
	/**
	 * Set matches dropdown for post meta-box for match report
	 *
	 * @see admin/admin.php
	 */
	public function set_match_dropdown(): void {
		$return = $this->check_security_token();
		if ( ! isset( $return->error ) ) {
            $league_id = isset( $_POST['league_id'] ) ? intval( $_POST['league_id'] ) : null;
            $season    = isset( $_POST['season'] ) ? sanitize_text_field( wp_unslash( $_POST['season'] ) ) : null;
            $output    = match_dropdown( $league_id, array( 'season' => $season ) );
            wp_send_json_success( $output );
        } else {
			wp_send_json_error( $return->msg, $return->status );
		}
	}
	/**
	 * Ajax Response to get check if Team Exists
	 */
	public function check_team_exists(): void {
        global $racketmanager;
        $found  = null;
		$return = $this->check_security_token();
		if ( ! isset( $return->error ) ) {
			$found = false;
			if ( isset( $_POST['name'] ) ) {
				$name = stripslashes( sanitize_text_field( wp_unslash( $_POST['name'] ) ) );
				$team = $racketmanager->get_team_id( $name );
				if ( $team ) {
					$found = true;
				}
			}
		}
		if ( isset( $return->error ) ) {
			wp_send_json_error( $return->msg, '500' );
		} else {
			wp_send_json_success( $found );
		}
	}
	/**
	 * Get clubs for player
	 */
	public function get_player_clubs(): void {
        $player_clubs = array();
		$return = $this->check_security_token();
		if ( ! isset( $return->error ) ) {
			$player_id = isset( $_POST['player'] ) ? intval( $_POST['player'] ) : null;
			if ( $player_id ) {
				$player = get_player( $player_id );
				if ( $player ) {
					$player_clubs = $player->get_clubs();
					$return->msg = __( 'Captains emailed', 'racketmanager' );
				} else {
					$return->error = true;
					$return->msg   = __( 'Player not found', 'racketmanager' );
				}
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
	 * Send match secretaries constitution
	 *
	 * @see templates/email/competition-entry-open.php
	 */
	public function email_constitution(): void {
		$return = $this->check_security_token();
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
	public function notify_competition_entries_open(): void {
		$return = $this->check_security_token();
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
							$entry_found = $competition->get_clubs(
								array(
									'count'   => true,
									'season'  => $season,
									'status'  => 1,
								)
							);
							if ( $entry_found ) {
								$return = $competition->notify_team_entry_reminder( $season );
							} else {
								$return = $competition->notify_team_entry_open( $season );
							}
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
	public function notify_tournament_entries_open(): void {
		$return = $this->check_security_token();
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
	public function notify_teams_fixture(): void {
		$return = $this->check_security_token();
		if ( ! isset( $return->error ) ) {
			$message_sent = false;
			if ( isset( $_POST['matchId'] ) ) {
				$match        = get_match( sanitize_text_field( wp_unslash( $_POST['matchId'] ) ) );
				$message_sent = $match->notify_next_match_teams();
			}
			if ( $message_sent ) {
				$return->msg = __( 'Teams notified', 'racketmanager' );
			} else {
				$return->error = false;
				$return->msg   = __( 'No notification', 'racketmanager' );
			}
		}
		if ( isset( $return->error ) ) {
			wp_send_json_error( $return->msg, '500' );
		} else {
			wp_send_json_success( array( 'message' => $return->msg ) );
		}
	}
	/**
	 * Send fixtures to captains
	 *
	 * @see templates/email/send_fixtures.php
	 */
	public function send_fixtures(): void {
		global $racketmanager, $event;
		$return = $this->check_security_token();
		if ( ! isset( $return->error ) ) {
			$event_id          = isset( $_POST['eventId'] ) ? intval( $_POST['eventId'] ) : null;
			$event             = get_event( $event_id );
			$season            = $event->current_season['name'];
			$message_sent      = false;
			$return            = array();
			$from_email        = $racketmanager->get_confirmation_email( $event->competition->type );
			$organisation_name = $racketmanager->site_name;
			$leagues           = $event->get_leagues();
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
					$headers[]     = 'cc: ' . ucfirst( $event->competition->type ) . ' Secretary <' . $from_email . '>';
					$email_subject = $racketmanager->site_name . ' - ' . $league->title . ' - Season ' . $team->season . ' - Fixtures - ' . $team->title;
					if ( isset( $team->contactemail ) ) {
						$email_to = $team->captain . ' <' . $team->contactemail . '>';
						$club     = get_club( $team->club_id );
						if ( isset( $club->match_secretary_email ) ) {
							$headers[] = 'cc: ' . $club->match_secretary_name . ' <' . $club->match_secretary_email . '>';
						}
						$action_url    = $racketmanager->site_url . '/' . $event->competition->type . '/' . seo_url( $league->title ) . '/' . $team->season . '/' . __( 'team', 'racketmanager' ) . '/' . seo_url( $team->title );
						$email_message = $racketmanager->shortcodes->load_template(
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
	public function set_tournament_dates(): void {
		global $racketmanager;
        $date_open     = null;
        $date_closing  = null;
        $date_withdraw = null;
		$return        = $this->check_security_token();
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
