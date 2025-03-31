<?php
/**
 * AJAX response methods

 * @package    RacketManager
 * @subpackage RacketManager_AJAX
 */

namespace Racketmanager;

/**
 * Implement AJAX responses for calls from both front end and admin.
 *
 * @author Paul Moffat
 */
class Racketmanager_Ajax extends RacketManager {
	/**
	 * Register ajax actions.
	 */
	public function __construct() {
		add_action( 'wp_ajax_racketmanager_get_player_details', array( &$this, 'get_player_details' ) );
		add_action( 'wp_ajax_racketmanager_match_mode', array( &$this, 'match_mode' ) );
		add_action( 'wp_ajax_racketmanager_update_match_header', array( &$this, 'update_match_header' ) );
		add_action( 'wp_ajax_racketmanager_update_match', array( &$this, 'update_match' ) );
		add_action( 'wp_ajax_nopriv_racketmanager_update_match', array( &$this, 'logged_out' ) );
		add_action( 'wp_ajax_racketmanager_update_rubbers', array( &$this, 'update_rubbers' ) );
		add_action( 'wp_ajax_nopriv_racketmanager_update_rubbers', array( &$this, 'logged_out' ) );
	}
	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public function logged_out() {
		$return    = array();
		$err_msg   = array();
		$err_field = array();
		$msg       = __( 'Must be logged in to access this feature', 'racketmanager' );
		array_push( $return, $msg, $err_msg, $err_field );
		wp_send_json_error( $return, '401' );
	}
	/**
	 * Ajax Response to get player information
	 */
	public function get_player_details() {
		global $wpdb;
		$valid       = true;
		$search_term = null;
		$message     = null;
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
			$name = isset( $_POST['name'] ) ? stripslashes( sanitize_text_field( wp_unslash( $_POST['name'] ) ) ) : '';
			$name = $wpdb->esc_like( $name ) . '%';
			if ( ! empty( $_POST['club'] ) ) {
				$affiliated_club = sanitize_text_field( wp_unslash( $_POST['club'] ) );
				$search_term     = $wpdb->prepare(
					' AND C.`id` = %s',
					$affiliated_club
				);
			}
			$gender  = empty( $_POST['partnerGender'] ) ? null : sanitize_text_field( wp_unslash( $_POST['partnerGender'] ) );
			$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT  P.`display_name` AS `fullname`, C.`name` as club, R.`id` as roster_id, C.`id` as club_id, P.`id` as player_id, P.`user_email` FROM $wpdb->racketmanager_club_players R, $wpdb->users P, $wpdb->racketmanager_clubs C WHERE R.`player_id` = P.`ID` AND R.`removed_date` IS NULL AND C.`id` = R.`club_id` $search_term AND `display_name` like %s ORDER BY 1,2,3",
					$name
				)
			);
			$players = array();
			$player  = array();
			if ( $results ) {
				foreach ( $results as $r ) {
					$player['label']      = addslashes( $r->fullname ) . ' - ' . $r->club;
					$player['value']      = addslashes( $r->fullname );
					$player['id']         = $r->roster_id;
					$player['club_id']    = $r->club_id;
					$player['club']       = $r->club;
					$player['playerId']   = $r->player_id;
					$player['user_email'] = $r->user_email;
					$player['contactno']  = get_user_meta( $r->player_id, 'contactno', true );
					$player['btm']        = get_user_meta( $r->player_id, 'btm', true );
					if ( $gender ) {
						$player['gender'] = get_user_meta( $r->player_id, 'gender', true );
						if ( $gender !== $player['gender'] ) {
							continue;
						}
					}
					array_push( $players, $player );
				}
			} else {
				$players[] = array(
					'label' => __( 'No results found', 'racketmanager' ),
					'value' => 'null',
				);
			}
		}
		if ( $valid ) {
			$response = wp_json_encode( $players );
			wp_send_json_success( $response );
		} else {
			wp_send_json_error( $message, 500 );
		}
	}

	/**
	 * Match screen mode
	 */
	public function match_mode() {
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
			$match_screen = '';
			$match_id     = isset( $_POST['match_id'] ) ? intval( $_POST['match_id'] ) : null;
			$mode         = isset( $_POST['mode'] ) ? sanitize_text_field( wp_unslash( $_POST['mode'] ) ) : null;
			if ( 'edit' === $mode ) {
				$is_edit_mode = true;
			} else {
				$is_edit_mode = false;
			}
			if ( ! empty( $match_id ) ) {
				$match = get_match( $match_id );
			}
			if ( $match ) {
				if ( $match->league->event->competition->is_tournament ) {
					$args       = array();
					$tournament = isset( $_POST['tournament'] ) ? sanitize_text_field( wp_unslash( $_POST['tournament'] ) ) : null;
					if ( $tournament ) {
						$tournament         = un_seo_url( $tournament );
						$args['tournament'] = $tournament;
					}
					$message = isset( $_POST['message'] ) ? sanitize_text_field( wp_unslash( $_POST['message'] ) ) : null;
					if ( $message ) {
						$args['message'] = $message;
					}
					$match_screen = racketmanager_tournament_match( $match_id, $args );
				} else {
					$match_screen = $racketmanager->show_match_screen( $match, $is_edit_mode );
				}
			} else {
				$valid   = false;
				$message = __( 'Match not found', 'racketmanager' );
			}
		}
		if ( $valid ) {
			$output = $match_screen;
		} else {
			$output = $this->return_error( $message );
			status_header( 404 );
		}
		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		wp_die();
	}

	/**
	 * Update match header
	 */
	public function update_match_header() {
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
			$match_header = '';
			$match_id     = isset( $_POST['match_id'] ) ? intval( $_POST['match_id'] ) : null;
			if ( ! empty( $match_id ) ) {
				$match = get_match( $match_id );
				if ( $match ) {
					$edit_mode    = isset( $_POST['edit_mode'] ) ? sanitize_text_field( wp_unslash( $_POST['edit_mode'] ) ) : false;
					$match_header = $racketmanager->show_match_header( $match, $edit_mode );
					wp_send_json_success( $match_header );
				} else {
					$valid   = false;
					$message = __( 'Match not found', 'racketmanager' );
				}
			} else {
				$valid   = false;
				$message = __( 'Match id not found', 'racketmanager' );
			}
		}
		if ( ! $valid ) {
			wp_send_json_error( $message, 500 );
		}
	}

	/**
	 * Update match scores
	 */
	public function update_match() {
		global $league, $match, $racketmanager;

		$return    = array();
		$err_msg   = array();
		$err_field = array();
		$error     = false;
		if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'scores-match' ) ) {
			$error       = true;
			$err_field[] = '';
			$err_msg[]   = __( 'Form has expired. Please refresh the page and resubmit', 'racketmanager' );
		} else {
			$match_id            = isset( $_POST['current_match_id'] ) ? intval( $_POST['current_match_id'] ) : 0;
			$match               = get_match( $match_id );
			$league              = get_league( $match->league_id );
			$match_round         = isset( $_POST['match_round'] ) ? sanitize_text_field( wp_unslash( $_POST['match_round'] ) ) : null;
			$match_confirmed     = 'P';
			$matches             = $match_id;
			$home_points         = 0;
			$away_points         = 0;
			$home_team           = isset( $_POST['home_team'] ) ? intval( $_POST['home_team'] ) : null;
			$away_team           = isset( $_POST['away_team'] ) ? intval( $_POST['away_team'] ) : null;
			$custom['sets']      = isset( $_POST['sets'] ) ? $_POST['sets'] : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			$season              = isset( $_POST['current_season'] ) ? sanitize_text_field( wp_unslash( $_POST['current_season'] ) ) : null;
			$match_status        = isset( $_POST['match_status'] ) ? sanitize_text_field( wp_unslash( $_POST['match_status'] ) ) : null;
			$set_prefix          = 'set_';
			$errors['err_msg']   = $err_msg;
			$errors['err_field'] = $err_field;
			$sets                = isset( $custom['sets'] ) ? $custom['sets'] : null;
			$match_validate      = $this->validate_match_score( $match, $sets, $set_prefix, $errors, false, $match_status );
			$error               = $match_validate[0];
			$err_msg             = $match_validate[1];
			$home_points         = $match_validate[3];
			$away_points         = $match_validate[4];
			$err_field           = $match_validate[2];
			$sets                = $match_validate[5];
			$custom['sets']      = $sets;
			if ( $match_status ) {
				switch ( $match_status ) {
					case 'walkover_player1':
						$custom['walkover'] = 'home';
						break;
					case 'walkover_player2':
						$custom['walkover'] = 'away';
						break;
					case 'retired_player1':
						$custom['retired'] = 'home';
						break;
					case 'retired_player2':
						$custom['retired'] = 'away';
						break;
					case 'share':
						$custom['share'] = 'true';
						break;
					case 'abandoned':
						$custom['abandoned'] = 'true';
						break;
					case 'cancelled':
						$custom['cancelled'] = 'true';
						break;
					default:
						break;
				}
			}
		}

		if ( ! $error ) {
			$match->update_sets( $sets );
			$match_updated = $match->update_result( $home_points, $away_points, $custom, $match_confirmed );
			if ( $match_updated ) {
				$match_message       = __( 'Result saved', 'racketmanager' );
				$match               = get_match( $match_id );
				$home_points         = $match->home_points;
				$away_points         = $match->away_points;
				$msg                 = $match_message;
				$rm_options          = $racketmanager->get_options();
				$result_confirmation = $rm_options[ $match->league->event->competition->type ]['resultConfirmation'];
				if ( 'auto' === $result_confirmation || ( current_user_can( 'manage_racketmanager' ) ) ) {
					$match->confirmed = 'Y';
					$update           = $match->update_league_with_result( $match );
					$msg              = $update->msg;
					if ( ! current_user_can( 'manage_racketmanager' ) ) {
						$match_confirmed = 'Y';
						$match->result_notification( $match_confirmed, $match_message );
					}
				} else {
					$match->result_notification( $match_confirmed, $match_message );
				}
			} else {
				$msg = __( 'No result to save', 'racketmanager' );
			}
			array_push( $return, $msg, $match->home_points, $match->away_points, $match->winner_id, $sets );
			wp_send_json_success( $return );
		} else {
			$msg = __( 'Unable to update match result', 'racketmanager' );
			array_push( $return, $msg, $err_msg, $err_field );
			wp_send_json_error( $return, 500 );
		}
	}

	/**
	 * Update match rubber scores
	 */
	public function update_rubbers() {
		global $racketmanager, $league, $match;
		$return          = array();
		$msg             = '';
		$err_field       = array();
		$err_msg         = array();
		$error           = false;
		$updated_rubbers = '';
		if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'rubbers-match' ) ) {
			$error       = true;
			$err_field[] = '';
			$err_msg[]   = __( 'Form has expired. Please refresh the page and resubmit', 'racketmanager' );
		} elseif ( isset( $_POST['updateRubber'] ) ) {
			$updated_rubbers     = '';
			$match_id            = isset( $_POST['current_match_id'] ) ? intval( $_POST['current_match_id'] ) : 0;
			$match               = get_match( $match_id );
			$home_points         = array();
			$away_points         = array();
			$rm_options          = $racketmanager->get_options();
			$match_confirmed     = '';
			$is_update_allowed   = $match->is_update_allowed();
			$user_can_update     = $is_update_allowed->user_can_update;
			$user_type           = $is_update_allowed->user_type;
			$user_team           = $is_update_allowed->user_team;
			$result_confirmation = $rm_options[ $match->league->event->competition->type ]['resultConfirmation'];
			$match_comments      = isset( $_POST['matchComments'] ) ? wp_unslash( $_POST['matchComments'] ) : ''; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$confirm_comments    = isset( $_POST['resultConfirmComments'] ) ? sanitize_text_field( wp_unslash( $_POST['resultConfirmComments'] ) ) : '';
			if ( 'results' === $_POST['updateRubber'] ) {
				$user_can_update = true;
				if ( $user_can_update ) {
					$player_found = false;
					if ( 'player' === $user_type ) {
						if ( 'home' === $user_team || 'both' === $user_team ) {
							if ( get_current_user_id() === intval( $match->teams['home']->captain_id ) || get_current_user_id() === intval( $match->teams['home']->club->matchsecretary ) ) {
								$player_found = true;
							}
							$club_id = $match->teams['home']->club_id;
						} elseif ( 'away' === $user_team ) {
							if ( get_current_user_id() === intval( $match->teams['away']->captain_id ) || get_current_user_id() === intval( $match->teams['away']->club->match_secretary ) ) {
								$player_found = true;
							}
							$club_id = $match->teams['away']->club_id;
						}
						if ( ! $player_found ) {
							$club           = get_club( $club_id );
							$club_player    = $club->get_players(
								array(
									'player' => get_current_user_id(),
									'active' => true,
								)
							);
							$club_player_id = $club_player[0]->roster_id;
							for ( $ix = 1; $ix <= $match->num_rubbers; $ix++ ) {
								$players = isset( $_POST['players'][ $ix ] ) ? ( wp_unslash( $_POST['players'][ $ix ] ) ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
								if ( 'home' === $user_team || 'both' === $user_team ) {
									$home_players = (array) $players['home'];
									$player_found = array_search( $club_player_id, $home_players, true );
									if ( $player_found ) {
										break;
									}
								}
								if ( ! $player_found && ( 'away' === $user_team || 'both' === $user_team ) ) {
									$away_players = (array) $players['away'];
									$player_found = array_search( $club_player_id, $away_players, true );
									if ( $player_found ) {
										break;
									}
								}
							}
						}
						if ( ! $player_found ) {
							$user_can_update = false;
							$err_msg[]       = __( 'Player cannot submit results', 'racketmanager' );
							$error           = true;
						}
					}
				}
				if ( $user_can_update ) {
					$match_status    = isset( $_POST['new_match_status'] ) ? sanitize_text_field( wp_unslash( $_POST['new_match_status'] ) ) : null;
					$rubber_result   = $this->update_rubber_results( $match, $rm_options, $match_status );
					$error           = $rubber_result[0];
					$match_confirmed = $rubber_result[1];
					$err_msg         = $rubber_result[2];
					$err_field       = $rubber_result[3];
					$updated_rubbers = $rubber_result[4];
				}
			} elseif ( 'confirm' === $_POST['updateRubber'] ) {
				$result_confirm  = isset( $_POST['resultConfirm'] ) ? sanitize_text_field( wp_unslash( $_POST['resultConfirm'] ) ) : null;
				$match_confirmed = $this->confirm_rubber_results( $result_confirm );
				if ( empty( $match_confirmed ) ) {
					$error       = true;
					$err_field[] = 'resultConfirm';
					$err_field[] = 'resultChallenge';
					$err_msg[]   = __( 'Either confirm or challenge result', 'racketmanager' );
				} elseif ( 'C' === $match_confirmed ) {
					if ( empty( $confirm_comments ) ) {
						$error       = true;
						$err_field[] = 'resultConfirmComments';
						$err_msg[]   = __( 'You must enter a reason for challenging the result', 'racketmanager' );
					}
				}
				if ( ! $error ) {
					$match->delete_result_check();
					$rubbers = $match->get_rubbers();
					foreach ( $rubbers as $rubber ) {
						$ratings = $rubber->check_players();
					}
				}
			}
		}
		if ( ! $error ) {
			if ( $match_confirmed ) {
				$actioned_by = '';
				if ( 'D' !== $match_confirmed ) {
					if ( isset( $_POST['result_home'] ) ) {
						$actioned_by = 'home';
					} elseif ( isset( $_POST['result_away'] ) ) {
						$actioned_by = 'away';
					}
					$match_updated_by = $match->update_match_result_status( $match_confirmed, $match_comments, $confirm_comments, $user_team, $actioned_by );
				} else {
					$match_updated_by = $match->update_match_result_status( $match_confirmed, null, null, null, null );
				}
				$match_message = null;
				if ( 'D' === $match_confirmed ) {
					$match_message = __( 'Match Postponed', 'racketmanager' );
					$msg           = $match_message;
				} elseif ( 'A' === $match_confirmed ) {
					if ( 'auto' === $result_confirmation || 'admin' === $user_type  ) {
						$match->confirmed = 'Y';
						$update           = $match->update_league_with_result( $match );
						$msg              = $update->msg;
						if ( 'admin' !== $user_type ) {
							$match_message = __( 'Result Approved', 'racketmanager' );
							if ( $update->updated || 'Y' === $match->updated ) {
								$match_confirmed = 'Y';
							}
						}
					}
				} elseif ( 'C' === $match_confirmed ) {
					$match_message = __( 'Result Challenged', 'racketmanager' );
					$msg           = $match_message;
				} elseif ( 'P' === $match_confirmed ) {
					$msg = __( 'Result Saved', 'racketmanager' );
					if ( ! current_user_can( 'manage_racketmanager' ) ) {
						$match_message = $msg;
					}
				}
				if ( $match_message ) {
					$match->result_notification( $match_confirmed, $match_message, $match_updated_by );
				}
			} elseif ( ! $msg ) {
				$msg = __( 'No results to save', 'racketmanager' );
			}
			$player_warnings = null;
			if ( $match->has_result_check() ) {
				$msg            .= '<br>' . __( 'Match has player warnings', 'racketmanager' );
				$result_status   = 'warning';
				$result_warnings = $racketmanager->get_result_warnings( array( 'match' => $match->id ) );
				foreach ( $result_warnings as $player_warning ) {
					if ( $player_warning->rubber_id ) {
						$rubber = get_rubber( $player_warning->rubber_id );
						if ( $rubber ) {
							if ( $player_warning->team_id === $match->home_team ) {
								$team = 'home';
							} else {
								$team = 'away';
							}
							if ( intval( $player_warning->player_id ) === intval( $rubber->players[ $team ]['1']->id ) ) {
								$player_number = 1;
							} else {
								$player_number = 2;
							}
							$player_ref                     = 'players_' . $rubber->rubber_number . '_' . $team . '_' . $player_number;
							$player_warnings[ $player_ref ] = $player_warning->description;
						}
					}
				}
			} else {
				$result_status = 'success';
			}
			$home_points = isset( $updated_rubbers['homepoints'] ) ? $updated_rubbers['homepoints'] : null;
			$away_points = isset( $updated_rubbers['awaypoints'] ) ? $updated_rubbers['awaypoints'] : null;
			array_push( $return, $msg, $home_points, $away_points, $updated_rubbers, $result_status, $player_warnings );
			wp_send_json_success( $return );
		} else {
			$msg = __( 'Unable to save result', 'racketmanager' );
			array_push( $return, $msg, $err_msg, $err_field, $updated_rubbers );
			wp_send_json_error( $return, 500 );
		}
	}
	/**
	 * Update results for each rubber
	 *
	 * @param object $match match details.
	 * @param array  $options options for match.
	 * @param string $new_match_status match status.
	 */
	public function update_rubber_results( $match, $options, $new_match_status ) {
		global $racketmanager, $league, $match;
		$return              = array();
		$error               = false;
		$err_msg             = array();
		$err_field           = array();
		$match_confirmed     = '';
		$home_team_score     = 0;
		$away_team_score     = 0;
		$home_team_score_tie = 0;
		$away_team_score_tie = 0;
		if ( ! empty( $match->leg ) && '2' === $match->leg && ! empty( $match->linked_match ) ) {
			$linked_match = get_match( $match->linked_match );
			if ( ! empty( $linked_match->winner_id ) ) {
				$home_team_score_tie = $linked_match->home_points;
				$away_team_score_tie = $linked_match->away_points;
			}
		}
		$players                              = array();
		$match_players                        = array();
		$player_options                       = $racketmanager->get_options( 'player' );
		$club                                 = get_club( $match->teams['home']->club_id );
		$player['walkover']['male']['home']   = $club->get_player( $player_options['walkover']['male'] );
		$player['walkover']['female']['home'] = $club->get_player( $player_options['walkover']['female'] );
		$player['noplayer']['male']['home']   = $club->get_player( $player_options['noplayer']['male'] );
		$player['noplayer']['female']['home'] = $club->get_player( $player_options['noplayer']['female'] );
		$player['share']['male']['home']      = $club->get_player( $player_options['share']['male'] );
		$player['share']['female']['home']    = $club->get_player( $player_options['share']['female'] );
		$club                                 = get_club( $match->teams['away']->club_id );
		$player['walkover']['male']['away']   = $club->get_player( $player_options['walkover']['male'] );
		$player['walkover']['female']['away'] = $club->get_player( $player_options['walkover']['female'] );
		$player['noplayer']['male']['away']   = $club->get_player( $player_options['noplayer']['male'] );
		$player['noplayer']['female']['away'] = $club->get_player( $player_options['noplayer']['female'] );
		$player['share']['male']['away']      = $club->get_player( $player_options['share']['male'] );
		$player['share']['female']['away']    = $club->get_player( $player_options['share']['female'] );
		$updated_rubbers                      = array();

		$match        = get_match( $match );
		if ( empty( $match->date_result_entered ) ) {
			$match->set_result_entered();
		}
		$is_withdrawn = false;
		if ( $match->teams['home']->is_withdrawn || $match->teams['away']->is_withdrawn ) {
			$is_withdrawn     = true;
			$new_match_status = 'withdrawn';
		}
		$match->home_points = 0;
		$match->away_points = 0;
		$match->delete_result_check();
		$stats                    = array();
		$stats['rubbers']['home'] = 0;
		$stats['rubbers']['away'] = 0;
		$stats['sets']['home']    = 0;
		$stats['sets']['away']    = 0;
		$stats['games']['home']   = 0;
		$stats['games']['away']   = 0;

		for ( $ix = 1; $ix <= $match->num_rubbers; $ix++ ) {
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			$rubber_id    = isset( $_POST['id'][ $ix ] ) ? intval( $_POST['id'][ $ix ] ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$rubber_type  = isset( $_POST['type'][ $ix ] ) ? sanitize_text_field( wp_unslash( $_POST['type'][ $ix ] ) ) : null;
			$walkover     = '';
			$share        = false;
			$players      = isset( $_POST['players'][ $ix ] ) ? ( wp_unslash( $_POST['players'][ $ix ] ) ) : array(); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$sets         = isset( $_POST['sets'][ $ix ] ) ? ( wp_unslash( $_POST['sets'][ $ix ] ) ) : array(); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$match_status = isset( $_POST['match_status'][ $ix ] ) ? sanitize_text_field( wp_unslash( $_POST['match_status'][ $ix ] ) ) : null;
			// phpcs:enable WordPress.Security.NonceVerification.Missing
			$rubber    = get_rubber( $rubber_id );
			$winner    = '';
			$loser     = '';
			$opponents = array( 'home', 'away' );
			if ( 'D' === substr( $rubber_type, 1, 1 ) ) {
				$player_numbers = array( '1', '2' );
			} else {
				$player_numbers = array( '1' );
			}
			$sets_shared    = 0;
			$homescore      = 0;
			$awayscore      = 0;
			$set_prefix     = 'set_' . $ix . '_';
			$validate_match = true;
			$playoff        = false;
			$share          = null;
			$walkover       = null;
			$retired        = null;
			$invalid        = null;
			$abandoned      = null;
			$is_cancelled   = null;
			if ( $is_withdrawn ) {
				$match_status = 'withdrawn';
			}
			switch ( $match_status ) {
				case 'share':
					$share = true;
					if ( 'MD' === $match->league->type || 'BD' === $match->league->type ) {
						$players['home']['1'] = $player['share']['male']['home']->roster_id;
						$players['home']['2'] = $players['home']['1'];
						$players['away']['1'] = $player['share']['male']['away']->roster_id;
						$players['away']['2'] = $players['away']['1'];
					} elseif ( 'WD' === $match->league->type || 'GD' === $match->league->type ) {
						$players['home']['1'] = $player['share']['female']['home']->roster_id;
						$players['home']['2'] = $players['home']['1'];
						$players['away']['1'] = $player['share']['female']['away']->roster_id;
						$players['away']['2'] = $players['away']['1'];
					} elseif ( 'XD' === $match->league->type ) {
						$players['home']['1'] = $player['share']['male']['home']->roster_id;
						$players['home']['2'] = $player['share']['female']['home']->roster_id;
						$players['away']['1'] = $player['share']['male']['away']->roster_id;
						$players['away']['2'] = $player['share']['female']['away']->roster_id;
					}
					break;
				case 'walkover_player1':
					$walkover = 'home';
					if ( 'MD' === $match->league->type || 'BD' === $match->league->type ) {
						if ( empty( $players['home']['1'] ) ) {
							$players['home']['1'] = $player['walkover']['male']['home']->roster_id;
						}
						if ( empty( $players['home']['2'] ) ) {
							$players['home']['2'] = $player['walkover']['male']['home']->roster_id;
						}
						$players['away']['1'] = $player['noplayer']['male']['away']->roster_id;
						$players['away']['2'] = $players['away']['1'];
					} elseif ( 'WD' === $match->league->type || 'GD' === $match->league->type ) {
						if ( empty( $players['home']['1'] ) ) {
							$players['home']['1'] = $player['walkover']['female']['home']->roster_id;
						}
						if ( empty( $players['home']['2'] ) ) {
							$players['home']['2'] = $player['walkover']['female']['home']->roster_id;
						}
						$players['away']['1'] = $player['noplayer']['female']['away']->roster_id;
						$players['away']['2'] = $players['away']['1'];
					} elseif ( 'XD' === $match->league->type ) {
						if ( empty( $players['home']['1'] ) ) {
							$players['home']['1'] = $player['walkover']['male']['home']->roster_id;
						}
						if ( empty( $players['home']['2'] ) ) {
							$players['home']['2'] = $player['walkover']['female']['home']->roster_id;
						}
						$players['away']['1'] = $player['noplayer']['male']['away']->roster_id;
						$players['away']['2'] = $player['noplayer']['female']['away']->roster_id;
					}
					break;
				case 'walkover_player2':
					$walkover = 'away';
					if ( 'MD' === $match->league->type || 'BD' === $match->league->type ) {
						$players['home']['1'] = $player['noplayer']['male']['home']->roster_id;
						$players['home']['2'] = $players['home']['1'];
						if ( empty( $players['away']['1'] ) ) {
							$players['away']['1'] = $player['walkover']['male']['away']->roster_id;
						}
						if ( empty( $players['away']['2'] ) ) {
							$players['away']['2'] = $player['walkover']['male']['away']->roster_id;
						}
					} elseif ( 'WD' === $match->league->type || 'GD' === $match->league->type ) {
						$players['home']['1'] = $player['noplayer']['female']['home']->roster_id;
						$players['home']['2'] = $players['home']['1'];
						if ( empty( $players['away']['1'] ) ) {
							$players['away']['1'] = $player['walkover']['female']['away']->roster_id;
						}
						if ( empty( $players['away']['2'] ) ) {
							$players['away']['2'] = $player['walkover']['female']['away']->roster_id;
						}
					} elseif ( 'XD' === $match->league->type ) {
						$players['home']['1'] = $player['noplayer']['male']['home']->roster_id;
						$players['home']['2'] = $player['noplayer']['female']['home']->roster_id;
						if ( empty( $players['away']['1'] ) ) {
							$players['away']['1'] = $player['walkover']['male']['away']->roster_id;
						}
						if ( empty( $players['away']['2'] ) ) {
							$players['away']['2'] = $player['walkover']['female']['away']->roster_id;
						}
					}
					break;
				case 'retired_player1':
					$retired = 'home';
					break;
				case 'retired_player2':
					$retired = 'away';
					break;
				case 'invalid_player1':
					$invalid = 'home';
					break;
				case 'invalid_player2':
					$invalid = 'away';
					break;
				case 'invalid_players':
					$invalid = 'both';
					break;
				case 'abandoned':
					$abandoned = true;
					break;
				case 'cancelled':
					$is_cancelled = true;
					break;
				default:
					break;
			}
			if ( isset( $match->league->scoring ) && ( 'TP' === $match->league->scoring || 'MP' === $match->league->scoring || 'MPL' === $match->league->scoring ) && intval( $match->num_rubbers ) === $ix && intval( $match->num_rubbers ) > $match->league->num_rubbers ) {
				if ( empty( $match->leg ) || '2' !== $match->leg ) {
					if ( $home_team_score !== $away_team_score ) {
						$validate_match = false;
					} else {
						$playoff = true;
					}
				} elseif ( $home_team_score_tie !== $away_team_score_tie ) {
						$validate_match = false;
				} else {
					$playoff = true;
				}
			}
			if ( $validate_match ) {
				if ( empty( $share ) && empty( $is_withdrawn ) && empty( $is_cancelled ) ) {
					foreach ( $opponents as $opponent ) {
						$team_players = isset( $players[ $opponent ] ) ? $players[ $opponent ] : array();
						foreach ( $player_numbers as $player_number ) {
							if ( empty( $team_players[ $player_number ] ) ) {
								$err_field[] = 'players_' . $ix . '_' . $opponent . '_' . $player_number;
								$err_msg[]   = __( 'Player not selected', 'racketmanager' );
								$error       = true;
							} else {
								$player_ref  = $team_players[ $player_number ];
								$club_player = get_club_player( $player_ref );
								if ( ! $club_player->system_record ) {
									$player_found = in_array( $player_ref, $match_players, true );
									if ( ! $player_found ) {
										if ( $playoff ) {
											$err_field[] = 'players_' . $ix . '_' . $opponent . '_' . $player_number;
											$err_msg[]   = __( 'Player for playoff must have played', 'racketmanager' );
											$error       = true;
										} elseif ( $rubber->reverse_rubber ) {
											$err_field[] = 'players_' . $ix . '_' . $opponent . '_' . $player_number;
											$err_msg[]   = __( 'Player for reverse rubber must have played', 'racketmanager' );
											$error       = true;
										} else {
											$match_players[] = $player_ref;
										}
									} elseif ( ! $playoff && ! $rubber->reverse_rubber ) {
										$err_field[] = 'players_' . $ix . '_' . $opponent . '_' . $player_number;
										$err_msg[]   = __( 'Player already selected', 'racketmanager' );
										$error       = true;
									}
								}
							}
						}
					}
				}
				$status              = null;
				$rubber_number       = $ix;
				$errors['err_msg']   = $err_msg;
				$errors['err_field'] = $err_field;
				$match_validate      = $this->validate_match_score( $match, $sets, $set_prefix, $errors, $rubber_number, $match_status );
				$error               = $match_validate[0];
				$err_msg             = $match_validate[1];
				$err_field           = $match_validate[2];
				$homescore           = $match_validate[3];
				$awayscore           = $match_validate[4];
				$sets                = $match_validate[5];
				$match_stats         = $match_validate[6];
				$points              = $match_validate[7];
				if ( ! $error ) {
					$custom         = array();
					$custom['sets'] = $sets;
					if ( $walkover ) {
						$status             = 1;
						$custom['walkover'] = $walkover;
					} elseif ( $share ) {
						$status          = 3;
						$custom['share'] = true;
					} elseif ( $retired ) {
						$status            = 2;
						$custom['retired'] = $retired;
					} elseif ( $abandoned ) {
						$status              = 6;
						$custom['abandoned'] = true;
					} elseif ( $is_cancelled ) {
						$status              = 8;
						$custom['cancelled'] = true;
					} elseif ( $invalid ) {
						$status            = 9;
						$custom['invalid'] = $invalid;
					} elseif ( empty( $status ) ) {
						$status = 0;
					}
					$custom['stats']         = $match_stats;
					$stats['sets']['home']  += $match_stats['sets']['home'];
					$stats['sets']['away']  += $match_stats['sets']['away'];
					$stats['games']['home'] += $match_stats['games']['home'];
					$stats['games']['away'] += $match_stats['games']['away'];
					$points['home']['team']  = $match->home_team;
					$points['away']['team']  = $match->away_team;
					$result                  = $rubber->calculate_result( $points );
					$homescore               = $result->home;
					$awayscore               = $result->away;
					$winner                  = $result->winner;
					$loser                   = $result->loser;
					if ( is_numeric( $homescore ) ) {
						$home_team_score     += $homescore;
						$home_team_score_tie += $homescore;
					}
					if ( is_numeric( $awayscore ) ) {
						$away_team_score     += $awayscore;
						$away_team_score_tie += $awayscore;
					}
					if ( $winner === $match->home_team ) {
						++$stats['rubbers']['home'];
					} elseif ( $winner === $match->away_team ) {
						++$stats['rubbers']['away'];
					} else {
						$stats['rubbers']['home'] += 0.5;
						$stats['rubbers']['away'] += 0.5;
					}
					if ( ! empty( $homescore ) || ! empty( $awayscore ) || $is_withdrawn || $is_cancelled || $invalid ) {
						$homescore                                   = ! empty( $homescore ) ? $homescore : 0;
						$awayscore                                   = ! empty( $awayscore ) ? $awayscore : 0;
						$updated_rubbers['homepoints'][ $rubber_id ] = $homescore;
						$updated_rubbers['awaypoints'][ $rubber_id ] = $awayscore;
						$match->home_points                         += $homescore;
						$match->away_points                         += $awayscore;

						$rubber->set_players( $players );
						$rubber->home_points = $homescore;
						$rubber->away_points = $awayscore;
						$rubber->winner_id   = $winner;
						$rubber->loser_id    = $loser;
						$rubber->custom      = $custom;
						$rubber->status      = $status;
						$rubber->update_result();
						$match_confirmed = 'P';
						foreach ( $opponents as $opponent ) {
							foreach ( $player_numbers as $player_number ) {
								$updated_rubbers[ $rubber_id ]['players'][ $opponent ][] = isset( $players[ $opponent ][ $player_number ] ) ? $players[ $opponent ][ $player_number ] : null;
							}
						}
						$updated_rubbers[ $rubber_id ]['sets']   = $sets;
						$updated_rubbers[ $rubber_id ]['winner'] = $winner;
					}
				}
			}
		}
		if ( ! $error ) {
			if ( $is_withdrawn || $is_cancelled ) {
				$match_confirmed = 'P';
				$home_team_score = 0;
				$away_team_score = 0;
			} else {
				$check_options = $racketmanager->get_options( 'checks' );
				$match->delete_result_check();
				$rubbers      = $match->get_rubbers();
				$prev_ratings = array();
				foreach ( $rubbers as $rubber ) {
					$check_results = $rubber->check_players();
					if ( ! empty( $match->league->event->competition->rules['leadTimecheck'] ) && ! empty( $check_options['wtn_check'] ) ) {
						$wtns = $check_results['wtns'];
						if ( ! empty( $prev_wtns ) ) {
							foreach ( $wtns as $opponent => $wtn ) {
								if ( $wtn < $prev_wtns[ $opponent ] ) {
									$team_err = $opponent . '_team';
									$team     = $match->$team_err;
									/* translators: %1$d: rubber number, %2$d: rubber team rating, %3$d: previous rubber rating*/
									$message = sprintf( __( 'Players out of order. Rubber %1$d has wtn %2$.1f - previous rubber has wtn %3$.1f', 'racketmanager' ), $rubber->rubber_number, $wtn, $prev_wtns[ $opponent ] );
									$players = $rubber->players[ $opponent ];
									foreach ( $players as $player ) {
										$match->add_player_result_check( $team, $player->id, $message, $rubber->id );
									}
								}
							}
						}
						$prev_wtns = $wtns;
					} elseif ( ! empty( $match->league->event->competition->rules['ratingCheck'] ) && ! empty( $check_options['ratingCheck'] ) ) {
						$ratings = $check_results['ratings'];
						if ( ! empty( $prev_ratings ) ) {
							foreach ( $ratings as $opponent => $rating ) {
								if ( $rating > $prev_ratings[ $opponent ] ) {
									$team_err = $opponent . '_team';
									$team     = $match->$team_err;
									/* translators: %1$d: rubber number, %2$d: rubber team rating, %3$d: previous rubber rating*/
									$message = sprintf( __( 'Players out of order. Rubber %1$d has rating %2$d - previous rubber has rating %3$d', 'racketmanager' ), $rubber->rubber_number, $rating, $prev_ratings[ $opponent ] );
									$players = $rubber->players[ $opponent ];
									foreach ( $players as $player ) {
										$match->add_player_result_check( $team, $player->id, $message, $rubber->id );
									}
								}
							}
						}
						$prev_ratings = $ratings;
					}
				}
			}
			$match_custom['stats'] = $stats;
			$status                = Racketmanager_Util::get_match_status_code( $new_match_status );
			$match->update_result( $home_team_score, $away_team_score, $match_custom, $match_confirmed, $status );
			$result_late = false;
			$competition_options = $racketmanager->get_options( $match->league->event->competition->type );
			$result_timeout      = isset( $competition_options['resultTimeout'] ) ? $competition_options['resultTimeout'] : null;
			if ( $result_timeout ) {
				if ( ! empty( $match->date_result_entered ) ) {
					$date_result_entered = date_create( $match->date_result_entered );
					$match_date          = date_create( $match->date );
					$diff                = date_diff( $date_result_entered, $match_date );
					if ( $diff->invert ) {
						$time_diff  = $diff->days * 24 * 60;
						$time_diff += $diff->h * 60;
						$time_diff += $diff->i;
						$timeout    = $result_timeout * 60;
						if ( $time_diff > $timeout ) {
							$result_late = true;
							$time_diff_hours = $time_diff / 60;
							/* translators: %d: number of hours */
							$reason = sprintf( __( 'result entered %d hours after match', 'racketmanager' ), $time_diff_hours );
							$match->add_match_result_check( $match->home_team, $reason );
						}
					}
				}
			}
		}
		array_push( $return, $error, $match_confirmed, $err_msg, $err_field, $updated_rubbers );
		return $return;
	}
	/**
	 * Validate Match Score
	 *
	 * @param object $match match details.
	 * @param array  $sets set details.
	 * @param string $set_prefix_start set prefix.
	 * @param array  $errors array of error messages and error fields.
	 * @param int    $rubber_number optional rubber number.
	 * @param string $match_status match_status setting.
	 */
	public function validate_match_score( $match, $sets, $set_prefix_start, $errors, $rubber_number = false, $match_status = false ) {
		global $racketmanager;
		$num_sets_to_win  = intval( $match->league->num_sets_to_win );
		$num_games_to_win = 1;
		$point_rule       = $match->league->get_point_rule();
		$points_format    = null;
		if ( 1 === $num_sets_to_win && ! empty( $point_rule['match_result'] ) && 'games' === $point_rule['match_result'] ) {
			$points_format = 'games';
		}
		$return                 = array();
		$homescore              = 0;
		$awayscore              = 0;
		$error                  = false;
		$scoring                = isset( $match->league->scoring ) ? $match->league->scoring : 'TB';
		$sets_updated           = array();
		$s                      = 1;
		$stats                  = array();
		$stats['sets']['home']  = 0;
		$stats['sets']['away']  = 0;
		$stats['games']['home'] = 0;
		$stats['games']['away'] = 0;

		$points['home']['sets']   = 0;
		$points['away']['sets']   = 0;
		$points['shared']['sets'] = 0;
		$points['split']['sets']  = 0;
		if ( ! empty( $sets ) ) {
			$num_sets    = count( $sets );
			$set_retired = null;
			if ( 'retired_player1' === $match_status || 'retired_player2' === $match_status || 'abandoned' === $match_status ) {
				for ( $s1 = $num_sets; $s1 >= 1; $s1-- ) {
					if ( '' !== $sets[ $s1 ]['player1'] || '' !== $sets[ $s1 ]['player2'] ) {
						$set_retired = $s1;
						break;
					}
				}
			}
			foreach ( $sets as $set ) {
				$set_prefix = $set_prefix_start . $s . '_';
				$set_type   = Racketmanager_Util::get_set_type( $scoring, $match->final_round, $match->league->num_sets, $s, $rubber_number, $match->num_rubbers, $match->leg );
				$set_info   = Racketmanager_Util::get_set_info( $set_type );
				if ( 1 === $s ) {
					$num_games_to_win = $set_info->min_win;
				}
				if ( ( $s > $num_sets_to_win ) && ( $homescore === $num_sets_to_win || $awayscore === $num_sets_to_win ) ) {
					$set_info->set_type = 'null';
				}
				$set_status = null;
				switch ( $match_status ) {
					case 'retired_player1':
					case 'retired_player2':
					case 'abandoned':
						if ( $set_retired === $s ) {
							$set_status = $match_status;
						} elseif ( $s > $set_retired ) {
							$set_info->set_type = 'null';
						}
						break;
					case 'cancelled':
						$set_status = $match_status;
						break;
					default:
						$set_status = $match_status;
						break;
				}
				$set_validate        = $this->validate_set( $set, $set_prefix, $errors['err_msg'], $errors['err_field'], $set_info, $set_status );
				$set                 = $set_validate[2];
				$errors['err_msg']   = $set_validate[0];
				$errors['err_field'] = $set_validate[1];
				if ( $errors['err_msg'] ) {
					$error = true;
				}
				$set_player_1  = strtoupper( $set['player1'] );
				$set_player_2  = strtoupper( $set['player2'] );
				$set_completed = $set['completed'];
				if ( null !== $set_player_1 && null !== $set_player_2 ) {
					if ( ( $set_player_1 > $set_player_2 && ( empty( $set_status ) || ( 'abandoned' === $set_status && $set_completed ) ) ) || ( 'retired_player2' ) === $set_status || ( 'invalid_player2' ) === $set_status || ( 'invalid_players' ) === $set_status ) {
						if ( empty( $points_format ) ) {
							++$points['home']['sets'];
							++$stats['sets']['home'];
							++$homescore;
							if ( 'MTB' === $set['settype'] ) {
								++$stats['games']['home'];
							}
						} else {
							$homescore = $set_player_1;
							$awayscore = $set_player_2;
						}
					} elseif ( ( $set_player_1 < $set_player_2 && ( empty( $set_status ) || ( 'abandoned' === $set_status && $set_completed ) ) ) || ( 'retired_player1' ) === $set_status || ( 'invalid_player1' ) === $set_status || ( 'invalid_players' ) === $set_status ) {
						if ( empty( $points_format ) ) {
							++$points['away']['sets'];
							++$stats['sets']['away'];
							++$awayscore;
							if ( 'MTB' === $set['settype'] ) {
								++$stats['games']['away'];
							}
						} else {
							$homescore = $set_player_1;
							$awayscore = $set_player_2;
						}
					} elseif ( 'S' === $set_player_1 ) {
						++$points['shared']['sets'];
						$stats['sets']['home'] += 0.5;
						$stats['sets']['away'] += 0.5;
						$homescore             += 0.5;
						$awayscore             += 0.5;
					}
				}
				if ( is_numeric( $set_player_1 ) && 'MTB' !== $set['settype'] ) {
					$stats['games']['home'] += $set_player_1;
				}
				if ( is_numeric( $set_player_2 ) && 'MTB' !== $set['settype'] ) {
					$stats['games']['away'] += $set_player_2;
				}
				$sets_updated[ $s ] = $set;
				++$s;
			}
			if ( ! empty( $homescore ) && ! empty( $awayscore ) ) {
				++$points['split']['sets'];
			}
		}
		if ( 'league' === $match->league->event->competition->type ) {
			$point_rule              = $match->league->get_point_rule();
			$walkover_rubber_penalty = empty( $point_rule['forwalkover_rubber'] ) ? 0 : $point_rule['forwalkover_rubber'];
		} else {
			$walkover_rubber_penalty = 0;
		}
		if ( 'walkover_player1' === $match_status ) {
			$stats['sets']['home']     += $num_sets_to_win;
			$points['home']['sets']    += $num_sets_to_win;
			$points['away']['walkover'] = true;
			$homescore                 += $num_sets_to_win;
			$awayscore                 -= $walkover_rubber_penalty;
			$stats['games']['home']    += $num_games_to_win * $num_sets_to_win;
		} elseif ( 'walkover_player2' === $match_status ) {
			$stats['sets']['away']     += $num_sets_to_win;
			$points['away']['sets']    += $num_sets_to_win;
			$points['home']['walkover'] = true;
			$awayscore                 += $num_sets_to_win;
			$homescore                 -= $walkover_rubber_penalty;
			$stats['games']['away']    += $num_games_to_win * $num_sets_to_win;
		} elseif ( 'retired_player1' === $match_status ) {
			$points['home']['retired'] = true;
			$points['away']['sets']    = $num_sets_to_win;
			$stats['sets']['away']     = $num_sets_to_win;
			$awayscore                 = $num_sets_to_win;
		} elseif ( 'retired_player2' === $match_status ) {
			$points['away']['retired'] = true;
			$points['home']['sets']    = $num_sets_to_win;
			$stats['sets']['home']     = $num_sets_to_win;
			$homescore                 = $num_sets_to_win;
		} elseif ( 'invalid_player2' === $match_status ) {
			$stats['sets']['home']     = $num_sets_to_win;
			$points['home']['sets']    = $num_sets_to_win;
			$points['away']['invalid'] = true;
			$homescore                 = $num_sets_to_win;
			$awayscore                -= $walkover_rubber_penalty;
			$stats['games']['home']    = $num_games_to_win * $num_sets_to_win;
			$stats['games']['away']    = 0;
		} elseif ( 'invalid_player1' === $match_status ) {
			$stats['sets']['away']     = $num_sets_to_win;
			$points['away']['sets']    = $num_sets_to_win;
			$points['home']['invalid'] = true;
			$awayscore                 = $num_sets_to_win;
			$homescore                -= $walkover_rubber_penalty;
			$stats['games']['away']    = $num_games_to_win * $num_sets_to_win;
			$stats['games']['home']    = 0;
		} elseif ( 'invalid_players' === $match_status ) {
			$stats['sets']['home']     = 0;
			$points['home']['sets']    = 0;
			$stats['sets']['away']     = 0;
			$points['away']['sets']    = 0;
			$points['both']['invalid'] = true;
			$awayscore                 = $walkover_rubber_penalty;
			$homescore                 = $walkover_rubber_penalty;
			$stats['games']['away']    = 0;
			$stats['games']['home']    = 0;
		} elseif ( 'share' === $match_status ) {
			$shared_sets              = $match->league->num_sets / 2;
			$points['shared']['sets'] = $match->league->num_sets;
			$homescore               += $shared_sets;
			$awayscore               += $shared_sets;
		} elseif ( 'withdrawn' === $match_status ) {
			$points['withdrawn'] = 1;
		} elseif ( 'cancelled' === $match_status ) {
			$points['cancelled'] = 1;
		} elseif ( 'abandoned' === $match_status ) {
			if ( $homescore !== $num_sets_to_win && $awayscore !== $num_sets_to_win ) {
				$shared_sets              = $match->league->num_sets - $homescore - $awayscore;
				$points['shared']['sets'] = $shared_sets;
				$homescore               += $shared_sets;
				$awayscore               += $shared_sets;
			}
		}
		array_push( $return, $error, $errors['err_msg'], $errors['err_field'], $homescore, $awayscore, $sets_updated, $stats, $points );
		return $return;
	}

	/**
	 * Validate set
	 *
	 * @param array  $set set information.
	 * @param string $set_prefix sert prefix.
	 * @param array  $err_msg error messages.
	 * @param array  $err_field error fields.
	 * @param object $set_info type of set.
	 * @param string $match_status match_status setting.
	 */
	public function validate_set( $set, $set_prefix, $err_msg, $err_field, $set_info, $match_status ) {
		$return         = array();
		$completed_set  = false;
		$set_type       = $set_info->set_type;
		$set['player1'] = strtoupper( $set['player1'] );
		$set['player2'] = strtoupper( $set['player2'] );
		if ( 'walkover_player1' === $match_status || 'walkover_player2' === $match_status ) {
			if ( 'null' === $set_type ) {
				$set['player1']  = '';
				$set['player2']  = '';
				$set['tiebreak'] = '';
			} else {
				$set['player1']  = null;
				$set['player2']  = null;
				$set['tiebreak'] = '';
			}
		} elseif ( 'retired_player1' === $match_status || 'retired_player2' === $match_status || 'abandoned' === $match_status ) {
			if ( 'null' === $set_type ) {
				$set['player1']  = '';
				$set['player2']  = '';
				$set['tiebreak'] = '';
			}
		} elseif ( null !== $set['player1'] && null !== $set['player2'] ) {
			if ( 'null' === $set_type ) {
				if ( '' !== $set['player1'] ) {
					$err_msg[]   = __( 'Set score should be empty', 'racketmanager' );
					$err_field[] = $set_prefix . 'player1';
				}
				if ( '' !== $set['player2'] ) {
					$err_msg[]   = __( 'Set score should be empty', 'racketmanager' );
					$err_field[] = $set_prefix . 'player2';
				}
				if ( '' !== $set['tiebreak'] ) {
					$err_msg[]   = __( 'Tie break should be empty', 'racketmanager' );
					$err_field[] = $set_prefix . 'tiebreak';
				}
			} elseif ( 'share' === $match_status || 'withdrawn' === $match_status ) {
				$set['player1']  = '';
				$set['player2']  = '';
				$set['tiebreak'] = '';
			} elseif ( 'S' === $set['player1'] || 'S' === $set['player2'] ) {
				if ( 'S' !== $set['player1'] ) {
					$err_msg[]   = __( 'Both scores must be shared', 'racketmanager' );
					$err_field[] = $set_prefix . 'player1';
				}
				if ( 'S' !== $set['player2'] ) {
					$err_msg[]   = __( 'Both scores must be shared', 'racketmanager' );
					$err_field[] = $set_prefix . 'player2';
				}
			} elseif ( empty( $set['player1'] ) && empty( $set['player2'] ) ) {
				if ( 'retired_player1' !== $match_status && 'retired_player2' !== $match_status && 'abandoned' !== $match_status ) {
					$err_msg[]   = __( 'Set scores must be entered', 'racketmanager' );
					$err_field[] = $set_prefix . 'player1';
					$err_field[] = $set_prefix . 'player2';
				} else {
					$completed_set = false;
				}
			} elseif ( $set['player1'] === $set['player2'] ) {
				if ( 'retired_player1' !== $match_status && 'retired_player2' !== $match_status && 'abandoned' !== $match_status ) {
					$err_msg[]   = __( 'Set scores must be different', 'racketmanager' );
					$err_field[] = $set_prefix . 'player1';
					$err_field[] = $set_prefix . 'player2';
				} else {
					$completed_set = false;
				}
			} elseif ( $set['player1'] > $set['player2'] ) {
				$set_data        = new \stdClass();
				$set_data->msg   = $err_msg;
				$set_data->field = $err_field;
				$set_data        = $this->validate_set_score( $set, $set_prefix, 'player1', 'player2', $set_data, $set_info, $match_status );
				$err_msg         = $set_data->msg;
				$err_field       = $set_data->field;
				$completed_set   = $set_data->completed_set;
			} elseif ( $set['player1'] < $set['player2'] ) {
				$set_data        = new \stdClass();
				$set_data->msg   = $err_msg;
				$set_data->field = $err_field;
				$set_data        = $this->validate_set_score( $set, $set_prefix, 'player2', 'player1', $set_data, $set_info, $match_status );
				$err_msg         = $set_data->msg;
				$err_field       = $set_data->field;
				$completed_set   = $set_data->completed_set;
			} elseif ( '' === $set['player1'] || '' === $set['player2'] ) {
				if ( 'retired_player1' !== $match_status && 'retired_player2' !== $match_status ) {
					$err_msg[] = __( 'Set score not entered', 'racketmanager' );
					if ( '' === $set['player1'] ) {
						$err_field[] = $set_prefix . 'player1';
					}
					if ( '' === $set['player2'] ) {
						$err_field[] = $set_prefix . 'player2';
					}
				}
			}
		}
		$set['completed'] = $completed_set;
		$set['settype']   = $set_type;
		array_push( $return, $err_msg, $err_field, $set );
		return $return;
	}
	/**
	 * Validate set score function
	 *
	 * @param array  $set set details.
	 * @param string $set_prefix ste prefix.
	 * @param string $team_1 team 1.
	 * @param string $team_2 team 2.
	 * @param object $return_data return data.
	 * @param object $set_info set info.
	 * @param string $match_status match status.
	 * @return object
	 */
	private function validate_set_score( $set, $set_prefix, $team_1, $team_2, $return_data, $set_info, $match_status = null ) {
		$tiebreak_allowed  = $set_info->tiebreak_allowed;
		$tiebreak_required = $set_info->tiebreak_required;
		$max_win           = $set_info->max_win;
		$min_win           = $set_info->min_win;
		$max_loss          = $set_info->max_loss;
		$min_loss          = $set_info->min_loss;
		$err_msg           = $return_data->msg;
		$err_field         = $return_data->field;
		$retired_player    = 'retired_' . $team_2;
		$completed_set     = true;
		if ( $set[ $team_1 ] < $min_win && $match_status !== $retired_player ) {
			if ( 'abandoned' === $match_status ) {
				$completed_set = false;
			} else {
				$err_msg[]   = __( 'Winning set score too low', 'racketmanager' );
				$err_field[] = $set_prefix . $team_1;
			}
		} elseif ( $set[ $team_1 ] > $max_win ) {
			$err_msg[]   = __( 'Winning set score too high', 'racketmanager' );
			$err_field[] = $set_prefix . $team_1;
		} elseif ( intval( $set[ $team_1 ] ) === intval( $min_win ) && $max_win !== $min_win && $set[ $team_2 ] > $min_loss && $match_status !== $retired_player ) {
			$err_msg[]   = __( 'Games difference must be at least 2', 'racketmanager' );
			$err_field[] = $set_prefix . $team_1;
			$err_field[] = $set_prefix . $team_2;
		} elseif ( intval( $set[ $team_1 ] ) === $max_win ) {
			if ( $set[ $team_2 ] < $max_loss && $max_win !== $min_win ) {
				$err_msg[]   = __( 'Games difference incorrect', 'racketmanager' );
				$err_field[] = $set_prefix . $team_1;
				$err_field[] = $set_prefix . $team_2;
			} elseif ( $tiebreak_allowed && $set[ $team_2 ] > $max_loss ) {
				if ( ! strlen( $set['tiebreak'] ) > 0 ) {
					$err_msg[]   = __( 'Tie break score required', 'racketmanager' );
					$err_field[] = $set_prefix . 'tiebreak';
				} elseif ( ! is_numeric( $set['tiebreak'] ) || strval( round( $set['tiebreak'] ) ) !== $set['tiebreak'] ) {
					$err_msg[]   = __( 'Tie break score must be whole number', 'racketmanager' );
					$err_field[] = $set_prefix . 'tiebreak';
				}
			} elseif ( $tiebreak_required && '' === $set['tiebreak'] ) {
				$err_msg[]   = __( 'Tie break score required', 'racketmanager' );
				$err_field[] = $set_prefix . 'tiebreak';
			}
		} elseif ( $set[ $team_1 ] > $min_win && $set[ $team_2 ] < $min_loss ) {
			$err_msg[]   = __( 'Games difference incorrect', 'racketmanager' );
			$err_field[] = $set_prefix . $team_1;
			$err_field[] = $set_prefix . $team_2;
		} elseif ( $set[ $team_1 ] > $min_win && $set[ $team_2 ] > $min_loss && ( $set[ $team_1 ] - 2 ) !== intval( $set[ $team_2 ] ) ) {
			$err_msg[]   = __( 'Games difference incorrect', 'racketmanager' );
			$err_field[] = $set_prefix . $team_2;
		} elseif ( $set['tiebreak'] > '' ) {
			if ( ! $tiebreak_required ) {
				$err_msg[]   = __( 'Tie break score should be empty', 'racketmanager' );
				$err_field[] = $set_prefix . 'tiebreak';
			}
		} elseif ( $tiebreak_required ) {
			if ( '' === $set['tiebreak'] ) {
				$err_msg[]   = __( 'Tie break score required', 'racketmanager' );
				$err_field[] = $set_prefix . 'tiebreak';
			} elseif ( ! is_numeric( $set['tiebreak'] ) || strval( round( $set['tiebreak'] ) ) !== $set['tiebreak'] ) {
				$err_msg[]   = __( 'Tie break score must be whole number', 'racketmanager' );
				$err_field[] = $set_prefix . 'tiebreak';
			}
		}
		$return_data->msg           = $err_msg;
		$return_data->field         = $err_field;
		$return_data->completed_set = $completed_set;
		return $return_data;
	}
	/**
	 * Confirm results of rubbers
	 *
	 * @param string $result_confirm result confirmation.
	 */
	public function confirm_rubber_results( $result_confirm ) {
		$match_confirmed = '';
		switch ( $result_confirm ) {
			case 'confirm':
				$match_confirmed = 'A';
				break;
			case 'challenge':
				$match_confirmed = 'C';
				break;
			default:
				$match_confirmed = '';
		}

		return $match_confirmed;
	}
	/**
	 * Return error function
	 *
	 * @param string $msg mesage to display.
	 * @return string output html modal
	 */
	protected function return_error( $msg ) {
		ob_start();
		?>
		<div>
			<div class="alert_rm alert--danger">
				<div class="alert__body">
					<div class="alert__body-inner">
						<span><?php echo esc_html( $msg ); ?></span>
					</div>
				</div>
			</div>
		</div>
		<?php
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}
}
