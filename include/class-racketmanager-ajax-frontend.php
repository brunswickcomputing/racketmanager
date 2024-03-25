<?php
/**
 * AJAX Front end response methods

 * @package    RacketManager
 * @subpackage RacketManager_AJAX
 */

namespace Racketmanager;

/**
 * Implement AJAX front end responses.
 *
 * @author Paul Moffat
 */
class Racketmanager_Ajax_Frontend extends Racketmanager_Ajax {
	/**
	 * Register ajax actions.
	 */
	public function __construct() {
		parent::__construct();
		add_action( 'wp_ajax_racketmanager_add_favourite', array( &$this, 'add_favourite' ) );

		add_action( 'wp_ajax_racketmanager_club_player_request', array( &$this, 'club_player_request' ) );
		add_action( 'wp_ajax_racketmanager_club_players_remove', array( &$this, 'club_player_remove' ) );

		add_action( 'wp_ajax_racketmanager_update_team', array( &$this, 'update_team' ) );
		add_action( 'wp_ajax_racketmanager_update_club', array( &$this, 'update_club' ) );
		add_action( 'wp_ajax_racketmanager_update_player', array( &$this, 'update_player' ) );

		add_action( 'wp_ajax_racketmanager_get_team_info', array( &$this, 'get_team_event_info' ) );
		add_action( 'wp_ajax_racketmanager_cup_entry', array( &$this, 'cup_entry_request' ) );
		add_action( 'wp_ajax_racketmanager_league_entry', array( &$this, 'league_entry_request' ) );
		add_action( 'wp_ajax_racketmanager_tournament_entry', array( &$this, 'tournament_entry_request' ) );

		add_action( 'wp_ajax_racketmanager_matchcard', array( &$this, 'print_match_card' ) );
		add_action( 'wp_ajax_nopriv_racketmanager_matchcard', array( &$this, 'print_match_card' ) );
	}
	/**
	 * Add item as favourite
	 */
	public function add_favourite() {
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
			$type            = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';
			$id              = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : '';
			$userid          = get_current_user_id();
			$meta_key        = 'favourite-' . $type;
			$meta            = get_user_meta( $userid, $meta_key );
			$favourite_found = ( array_search( strval( $id ), $meta, true ) );
			if ( ! is_numeric( $favourite_found ) ) {
				add_user_meta( $userid, $meta_key, $id );
				$return->msg    = __( 'Favourite added', 'racketmanager' );
				$return->action = 'add';
			} else {
				delete_user_meta( $userid, $meta_key, $id );
				$return->msg    = __( 'Favourite removed', 'racketmanager' );
				$return->action = 'del';
			}
		}
		if ( isset( $return->error ) ) {
			wp_send_json_error( $return->msg, 500 );
		} else {
			wp_send_json_success( $return );
		}
	}
	/**
	 * Save club player requests
	 *
	 * @see templates/club.php
	 */
	public function club_player_request() {
		global $racketmanager;

		$return      = array();
		$msg         = '';
		$error       = false;
		$error_field = array();
		$error_msg   = array();
		if ( check_admin_referer( 'club-player-request' ) ) {
			$player_valid = $racketmanager->validatePlayer();
			if ( $player_valid[0] ) {
				$new_player = $player_valid[1];
				if ( isset( $_POST['affiliatedClub'] ) ) {
					$club = get_club( intval( $_POST['affiliatedClub'] ) );
					$club->register_player( $new_player );
				} else {
					$error_field = 'surname';
					$error_msg   = __( 'Club not set', 'racketmanager' );
					$racketmanager->set_message( __( 'Error in player request', 'racketmanager' ), true );
				}
			} else {
				$error_field = $player_valid[1];
				$error_msg   = $player_valid[2];
				$racketmanager->set_message( __( 'Error in player request', 'racketmanager' ), true );
			}
			$msg   = $racketmanager->message;
			$error = $racketmanager->error;
		} else {
			$error     = true;
			$error_msg = __( 'Security token invalid', 'racketmanager' );
		}
		if ( ! $error ) {
			wp_send_json_success( $msg );
		} else {
			array_push( $return, $msg, $error, $error_field, $error_msg );
			wp_send_json_error( $return, '500' );
		}
	}
	/**
	 * Remove club player entry
	 *
	 * @see admin/settings.php
	 */
	public function club_player_remove() {
		global $racketmanager;

		$return      = array();
		$error       = false;
		$error_field = array();
		$error_msg   = array();
		if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'club-player-remove' ) ) {
			$error         = true;
			$error_field[] = '';
			$error_msg[]   = __( 'Form has expired. Please refresh the page and resubmit', 'racketmanager' );
		} elseif ( isset( $_POST['clubPlayer'] ) ) {
				//phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
				$club_players = $_POST['clubPlayer'];
			foreach ( $club_players as $roster_id ) {
				$racketmanager->delete_club_player( intval( $roster_id ) );
			}
		}
		if ( $error ) {
			$msg = __( 'Unable to remove player', 'racketmanager' );
			array_push( $return, $msg, $error_msg, $error_field );
			wp_send_json_error( $return, '500' );
		} else {
			$msg = __( 'Player removed', 'racketmanager' );
			wp_send_json_success( $msg );
		}
	}
	/**
	 * Update Team
	 *
	 * @see templates/team.php
	 */
	public function update_team() {
		$return      = array();
		$error       = false;
		$error_field = array();
		$error_msg   = array();
		if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'team-update' ) ) {
			$error         = true;
			$error_field[] = 'team';
			$error_msg[]   = __( 'Form has expired. Please refresh the page and resubmit', 'racketmanager' );
		} else {
			if ( ! empty( $_POST['event_id'] ) ) {
				$event_id = intval( $_POST['event_id'] );
			} else {
				$error         = true;
				$error_field[] = 'team';
				$error_msg[]   = __( 'Event not selected', 'racketmanager' );
			}
			if ( ! empty( $_POST['team_id'] ) ) {
				$team_id = intval( $_POST['team_id'] );
			} else {
				$error         = true;
				$error_field[] = 'team-' . $event_id;
				$error_msg[]   = __( 'Team not selected', 'racketmanager' );
			}
			if ( ! empty( $_POST[ 'captainId-' . $event_id . '-' . $team_id ] ) ) {
				$captain_id = sanitize_text_field( wp_unslash( $_POST[ 'captainId-' . $event_id . '-' . $team_id ] ) );
			} else {
				$error         = true;
				$error_field[] = 'captain-' . $event_id . '-' . $team_id;
				$error_msg[]   = __( 'Captain is required', 'racketmanager' );
			}
			if ( ! empty( $_POST[ 'contactno-' . $event_id . '-' . $team_id ] ) ) {
				$contactno = sanitize_text_field( wp_unslash( $_POST[ 'contactno-' . $event_id . '-' . $team_id ] ) );
			} else {
				$error         = true;
				$error_field[] = 'contactno-' . $event_id . '-' . $team_id;
				$error_msg[]   = __( 'Contact number is required', 'racketmanager' );
			}
			if ( ! empty( $_POST[ 'contactemail-' . $event_id . '-' . $team_id ] ) ) {
				$contactemail = sanitize_text_field( wp_unslash( $_POST[ 'contactemail-' . $event_id . '-' . $team_id ] ) );
			} else {
				$error         = true;
				$error_field[] = 'contactemail-' . $event_id . '-' . $team_id;
				$error_msg[]   = __( 'Email address is required', 'racketmanager' );
			}
			if ( ! empty( $_POST[ 'matchday-' . $event_id . '-' . $team_id ] ) ) {
				$match_day = sanitize_text_field( wp_unslash( $_POST[ 'matchday-' . $event_id . '-' . $team_id ] ) );
			} else {
				$error         = true;
				$error_field[] = 'matchday-' . $event_id . '-' . $team_id;
				$error_msg[]   = __( 'Match day is required', 'racketmanager' );
			}
			if ( ! empty( $_POST[ 'matchtime-' . $event_id . '-' . $team_id ] ) ) {
				$matchtime = sanitize_text_field( wp_unslash( $_POST[ 'matchtime-' . $event_id . '-' . $team_id ] ) );
			} else {
				$error         = true;
				$error_field[] = 'matchtime-' . $event_id . '-' . $team_id;
				$error_msg[]   = __( 'Match day is required', 'racketmanager' );
			}
			if ( $team_id ) {
				$team = get_team( $team_id );
				$msg  = $team->update_event( $event_id, $captain_id, $contactno, $contactemail, $match_day, $matchtime );
			}
		}
		if ( $error ) {
			$msg = __( 'Unable to update team', 'racketmanager' );
			array_push( $return, $msg, $error_msg, $error_field );
			wp_send_json_error( $return, '500' );
		} else {
			wp_send_json_success( $msg );
		}
	}
	/**
	 * Update Club
	 *
	 * @see templates/club.php
	 */
	public function update_club() {
		$updates     = false;
		$return      = array();
		$msg         = '';
		$error       = false;
		$error_field = array();
		$error_msg   = array();
		if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'club-update' ) ) {
			$error         = true;
			$error_field[] = 'club';
			$error_msg[]   = __( 'Form has expired. Please refresh the page and resubmit', 'racketmanager' );
		} else {
			if ( isset( $_POST['club_id'] ) ) {
				$club_id = intval( $_POST['club_id'] );
			} else {
				$error         = true;
				$error_field[] = 'club';
				$error_msg[]   = __( 'Club missing', 'racketmanager' );
			}
			$contactno  = isset( $_POST['clubContactNo'] ) ? sanitize_text_field( wp_unslash( $_POST['clubContactNo'] ) ) : null;
			$facilities = isset( $_POST['facilities'] ) ? sanitize_text_field( wp_unslash( $_POST['facilities'] ) ) : null;
			$founded    = isset( $_POST['founded'] ) ? sanitize_text_field( wp_unslash( $_POST['founded'] ) ) : null;
			if ( ! empty( $_POST['matchSecretaryId'] ) ) {
				$match_secretary_id = sanitize_text_field( wp_unslash( $_POST['matchSecretaryId'] ) );
			} else {
				$error         = true;
				$error_field[] = 'matchSecretaryId';
				$error_msg[]   = __( 'Match secretary missing', 'racketmanager' );
			}
			if ( ! empty( $_POST['matchSecretaryContactNo'] ) ) {
				$match_secretary_contact_no = sanitize_text_field( wp_unslash( $_POST['matchSecretaryContactNo'] ) );
			} else {
				$error         = true;
				$error_field[] = 'matchSecretaryContactNo';
				$error_msg[]   = __( 'Contact number is required', 'racketmanager' );
			}
			if ( ! empty( $_POST['matchSecretaryEmail'] ) ) {
				$match_secretary_email = sanitize_text_field( wp_unslash( $_POST['matchSecretaryEmail'] ) );
			} else {
				$error         = true;
				$error_field[] = 'matchSecretaryEmail';
				$error_msg[]   = __( 'Email address is required', 'racketmanager' );
			}
			$website = isset( $_POST['website'] ) ? sanitize_text_field( wp_unslash( $_POST['website'] ) ) : null;
			if ( ! empty( $_POST['address'] ) ) {
				$address = sanitize_text_field( wp_unslash( $_POST['address'] ) );
			} else {
				$error         = true;
				$error_field[] = 'address';
				$error_msg[]   = __( 'Address is required', 'racketmanager' );
			}
			if ( ! $error ) {
				$club = get_club( $club_id );
				if ( $club->contactno !== $contactno || $club->facilities !== $facilities || $club->founded !== $founded || $club->matchsecretary !== $match_secretary_id || $club->website !== $website || $club->match_secretary_contact_no !== $match_secretary_contact_no || $club->match_secretary_email !== $match_secretary_email || $club->address !== $address ) {
					$club->contactno                  = $contactno;
					$club->facilities                 = $facilities;
					$club->founded                    = $founded;
					$club->matchsecretary             = $match_secretary_id;
					$club->website                    = $website;
					$club->match_secretary_contact_no = $match_secretary_contact_no;
					$club->match_secretary_email      = $match_secretary_email;
					$club->address                    = $address;
					$club->update( $club );
					$updates = true;
				}
			}
		}

		if ( $updates ) {
			$msg = __( 'Club updated', 'racketmanager' );
		} elseif ( $error ) {
			$msg = __( 'Error in club update', 'racketmanager' );
		} else {
			$msg = __( 'Nothing to update', 'racketmanager' );
		}

		if ( $error ) {
			array_push( $return, $msg, $error_msg, $error_field );
			wp_send_json_error( $return, '500' );
		} else {
			wp_send_json_success( $msg );
		}
	}
	/**
	 * Update Player
	 *
	 * @see templates/player.php
	 */
	public function update_player() {
		global $racketmanager;

		$error_field = array();
		$error_msg   = array();
		$return      = array();
		$msg         = '';
		if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'player-update' ) ) {
			$error         = true;
			$error_field[] = '';
			$error_msg[]   = __( 'Form has expired. Please refresh the page and resubmit', 'racketmanager' );
		} else {
			$player_id = isset( $_POST['playerId'] ) ? intval( $_POST['playerId'] ) : null;
			if ( $player_id ) {
				$player_valid = $racketmanager->validatePlayer();
				if ( $player_valid[0] ) {
					$player     = get_player( $player_id );
					$new_player = $player_valid[1];
					$player->update( $new_player );
					$error = $racketmanager->error;
					$msg   = $racketmanager->message;
				} else {
					$error         = true;
					$error_field[] = $player_valid[1];
					$error_msg[]   = $player_valid[2];
					$msg           = __( 'Error with player details', 'racketmanager' );
				}
			} else {
				$error         = true;
				$error_field[] = 'surname';
				$error_msg[]   = __( 'Player id not supplied', 'racketmanager' );
				$msg           = __( 'Error with player details', 'racketmanager' );
			}
		}
		if ( $error ) {
			array_push( $return, $msg, $error_msg, $error_field );
			wp_send_json_error( $return, '500' );
		} else {
			wp_send_json_success( $msg );
		}
	}
	/**
	 * Tournament entry request
	 *
	 * @see templates/tournamententry.php
	 */
	public function tournament_entry_request() {
		global $racketmanager;

		$return    = array();
		$msg       = '';
		$validator = new Racketmanager_Entry_Form_Validator();
		//phpcs:disable WordPress.Security.NonceVerification.Missing
		$validator = $validator->nonce( 'tournament-entry' );
		if ( ! $validator->error ) {
			if ( ! is_user_logged_in() ) {
				$validator = $validator->logged_in_entry();
			} else {
				$player        = get_player( wp_get_current_user()->ID );
				$tournament_id = isset( $_POST['tournamentId'] ) ? intval( $_POST['tournamentId'] ) : null;
				$season        = isset( $_POST['season'] ) ? sanitize_text_field( wp_unslash( $_POST['season'] ) ) : null;
				$player_id     = isset( $_POST['playerId'] ) ? intval( $_POST['playerId'] ) : null;
				$contactno     = isset( $_POST['contactno'] ) ? sanitize_text_field( wp_unslash( $_POST['contactno'] ) ) : '';
				$contactemail  = isset( $_POST['contactemail'] ) ? sanitize_text_field( wp_unslash( $_POST['contactemail'] ) ) : '';
				$btm           = isset( $_POST['btm'] ) ? intval( $_POST['btm'] ) : '';
				$comments      = isset( $_POST['commentDetails'] ) ? sanitize_textarea_field( wp_unslash( $_POST['commentDetails'] ) ) : '';
				// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$events   = isset( $_POST['event'] ) ? wp_unslash( $_POST['event'] ) : array();
				$partners = isset( $_POST['partner'] ) ? wp_unslash( $_POST['partner'] ) : array();
				// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$validator = $validator->events_entry( $events );
				foreach ( $events as $event ) {
					$event = get_event( $event );
					if ( substr( $event->type, 1, 1 ) === 'D' ) {
						$partner_id = isset( $partners[ $event->id ] ) ? $partners[ $event->id ] : 0;
						$field_ref  = $event->id;
						$field_name = $event->name;
						$validator  = $validator->partner( $partner_id, $field_ref, $field_name, $event );
					}
				}
				$validator      = $validator->telephone( $contactno );
				$validator      = $validator->email( $contactemail );
				$validator      = $validator->btm( $btm );
				$affiliatedclub = isset( $_POST['affiliatedclub'] ) ? sanitize_text_field( wp_unslash( $_POST['affiliatedclub'] ) ) : '';
				$validator      = $validator->club( $affiliatedclub );
				$acceptance     = isset( $_POST['acceptance'] ) ? sanitize_text_field( wp_unslash( $_POST['acceptance'] ) ) : '';
				$validator      = $validator->entry_acceptance( $acceptance );
				$validator      = $validator->tournament( $tournament_id );
				if ( $tournament_id ) {
					$tournament = get_tournament( $tournament_id );
					$validator  = $validator->tournament_open( $tournament->closing_date );
				}
			}
		}
		if ( ! $validator->error ) {
			$player->update_btm( $btm );
			$player->update_contact( $contactno, $contactemail );
			$player_name        = $player->display_name;
			$club               = get_club( $affiliatedclub );
			$email_to           = $player->display_name . ' <' . $player->email . '>';
			$email_from         = $racketmanager->get_confirmation_email( 'tournament' );
			$email_subject      = $racketmanager->site_name . ' - ' . $tournament->name . ' Tournament Entry';
			$tournament_entries = array();
			$i                  = 0;
			foreach ( $events as $i => $event_id ) {
				$tournament_entry               = array();
				$partner                        = '';
				$partner_name                   = '';
				$partner_id                     = '';
				$new_team                       = false;
				$event                          = get_event( $event_id );
				$tournament_entry['event_name'] = $event->name;
				if ( ! empty( $event->primary_league ) ) {
					$league = get_league( $event->primary_league );
				} else {
					$leagues = $event->get_leagues();
					$league  = get_league( $leagues[0] );
				}
				$team_name = $player_name;
				if ( substr( $event->type, 1, 1 ) === 'D' ) {
					$partner_id                  = isset( $partners[ $event->id ] ) ? $partners[ $event->id ] : 0;
					$partner                     = get_player( $partner_id );
					$partner_name                = $partner->fullname;
					$team_name                  .= ' / ' . $partner_name;
					$tournament_entry['partner'] = $partner_name;
				}
				$team = get_team( $team_name );
				if ( ! $team && substr( $event->type, 1, 1 ) === 'D' ) {
					if ( '' !== $partner_name ) {
						$team_name2 = $partner_name . ' / ' . $player_name;
						$team_id    = get_team( $team_name2 );
						if ( ! $team_id ) {
							$new_team = true;
						}
					} else {
						$new_team = true;
					}
				}
				if ( $new_team ) {
					$team                 = new \stdClass();
					$team->player1        = $player_name;
					$team->player1_id     = isset( $_POST['playerId'] ) ? sanitize_text_field( wp_unslash( $_POST['playerId'] ) ) : 0;
					$team->partner_name   = $player_id;
					$team->player2_id     = $partner_id;
					$team->type           = $league->type;
					$team->status         = 'P';
					$team->affiliatedclub = $affiliatedclub;
					$team                 = new Racketmanager_Team( $team );
				} else {
					$team->update_player( $player_name, $player_id, $partner_name, $partner_id, $affiliatedclub );
				}
				$team->set_event( $league->event_id, $player_id, $contactno, $contactemail );
				$league->add_team( $team->id, $season );
				$tournament_entries[ $i ] = $tournament_entry;
			}
			$action_url                          = $racketmanager->site_url . '/tournaments/entry-form/' . seo_url( $tournament->name ) . '/';
			$tournament_link                     = '<a href="' . $racketmanager->site_url . '/tournament/' . seo_url( $tournament->name ) . '/">' . $tournament->name . '</a>';
			$headers                             = array();
			$secretary_email                     = __( 'Tournament Secretary', 'racketmanager' ) . ' <' . $email_from . '>';
			$headers[]                           = 'From: ' . $secretary_email;
			$headers[]                           = 'Cc: ' . $secretary_email;
			$template                            = 'tournament-entry';
			$template_args['tournament_name']    = $tournament->name;
			$template_args['tournament_link']    = $tournament_link;
			$template_args['action_url']         = $action_url;
			$template_args['tournament_entries'] = $tournament_entries;
			$template_args['organisation']       = $racketmanager->site_name;
			$template_args['season']             = $season;
			$template_args['contactno']          = $contactno;
			$template_args['contactemail']       = $contactemail;
			$template_args['player']             = $player;
			$template_args['club']               = $club->name;
			$template_args['comments']           = $comments;
			$template_args['contact_email']      = $email_from;
			$racketmanager->email_entry_form( $template, $template_args, $email_to, $email_subject, $headers );
			$msg = __( 'Tournament entry complete', 'racketmanager' );
		} else {
			$msg = __( 'Errors in tournament entry form', 'racketmanager' );
		}
		//phpcs:enable WordPress.Security.NonceVerification.Missing
		if ( $validator->error ) {
			array_push( $return, $msg, $validator->error_msg, $validator->error_field );
			wp_send_json_error( $return, '500' );
		} else {
			wp_send_json_success( $msg );
		}
	}
	/**
	 * Ajax Response to get captain information
	 */
	public function get_team_event_info() {
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
			$team_info = new \stdClass();
			$team_id   = isset( $_POST['team'] ) ? sanitize_text_field( wp_unslash( $_POST['team'] ) ) : '';
			$event_id  = isset( $_POST['event'] ) ? sanitize_text_field( wp_unslash( $_POST['event'] ) ) : '';

			$event = get_event( $event_id );
			$team  = $event->get_team_info( $team_id );
			if ( $team ) {
				$team_info->captain    = addslashes( $team->captain );
				$team_info->captain_id = $team->captain_id;
				$team_info->user_email = $team->contactemail;
				$team_info->contactno  = $team->contactno;
				$team_info->match_day  = $team->match_day;
				$team_info->match_time = $team->match_time;
				$team_info->message    = __( 'Team information updated', 'racketmanager' );
			}
		}
		if ( $valid ) {
			wp_send_json_success( $team_info );
		} else {
			wp_send_json_error( $message, '500' );
		}
	}
	/**
	 * Cup entry request
	 *
	 * @see templates/cup_entry.php
	 */
	public function cup_entry_request() {
		$return    = array();
		$msg       = '';
		$validator = new Racketmanager_Entry_Form_Validator();

		//phpcs:disable WordPress.Security.NonceVerification.Missing
		$validator = $validator->nonce( 'cup-entry' );
		if ( ! $validator->error ) {
			if ( ! is_user_logged_in() ) {
				$validator = $validator->logged_in_entry();
			} else {
				$season         = isset( $_POST['season'] ) ? sanitize_text_field( wp_unslash( $_POST['season'] ) ) : '';
				$competition_id = isset( $_POST['competitionId'] ) ? sanitize_text_field( wp_unslash( $_POST['competitionId'] ) ) : '';
				$affiliatedclub = isset( $_POST['affiliatedClub'] ) ? sanitize_text_field( wp_unslash( $_POST['affiliatedClub'] ) ) : '';
				//phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$events        = isset( $_POST['event'] ) ? wp_unslash( $_POST['event'] ) : array();
				$teams         = isset( $_POST['team'] ) ? wp_unslash( $_POST['team'] ) : array();
				$captains      = isset( $_POST['captain'] ) ? wp_unslash( $_POST['captain'] ) : array();
				$captain_ids   = isset( $_POST['captainId'] ) ? wp_unslash( $_POST['captainId'] ) : array();
				$contactnos    = isset( $_POST['contactno'] ) ? wp_unslash( $_POST['contactno'] ) : array();
				$contactemails = isset( $_POST['contactemail'] ) ? wp_unslash( $_POST['contactemail'] ) : array();
				$matchdays     = isset( $_POST['matchday'] ) ? wp_unslash( $_POST['matchday'] ) : array();
				$matchtimes    = isset( $_POST['matchtime'] ) ? wp_unslash( $_POST['matchtime'] ) : array();
				//phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$comments             = isset( $_POST['commentDetails'] ) ? sanitize_textarea_field( wp_unslash( $_POST['commentDetails'] ) ) : '';
				$club_entry           = new \stdClass();
				$club_entry->club     = $affiliatedclub;
				$club_entry->season   = $season;
				$club_entry->comments = $comments;
				if ( $competition_id ) {
					$competition = get_competition( $competition_id );
					if ( ! $competition ) {
						$validator = $validator->competition( $competition );
					}
					$club_entry->competition = $competition;
				}

				$validator = $validator->club( $affiliatedclub );
				$validator = $validator->events_entry( $events );
				foreach ( $events as $event_id ) {
					$event      = get_event( $event_id );
					$team       = isset( $teams[ $event->id ] ) ? $teams[ $event->id ] : 0;
					$field_ref  = $event->id;
					$field_name = $event->name;
					$validator  = $validator->teams( $team, $field_ref, $field_name );
					if ( ! empty( $team ) ) {
						$captain      = isset( $captains[ $event->id ] ) ? $captains[ $event->id ] : 0;
						$captain_id   = isset( $captain_ids[ $event->id ] ) ? $captain_ids[ $event->id ] : 0;
						$contactno    = isset( $contactnos[ $event->id ] ) ? $contactnos[ $event->id ] : '';
						$contactemail = isset( $contactemails[ $event->id ] ) ? $contactemails[ $event->id ] : '';
						$match_day    = isset( $matchdays[ $event->id ] ) ? $matchdays[ $event->id ] : '';
						$matchtime    = isset( $matchtimes[ $event->id ] ) ? $matchtimes[ $event->id ] : '';
						$field_ref    = $event->id;
						$field_name   = $event->name;
						$validator    = $validator->match_day( $match_day, $field_ref, $field_name );
						$validator    = $validator->match_time( $matchtime, $field_ref, $field_name );
						$validator    = $validator->captain( $captain, $contactno, $contactemail, $field_ref, $field_name );

						$event_entry             = new \stdClass();
						$event_entry->id         = $event->id;
						$event_entry->name       = $event->name;
						$event_entry->team_id    = $team;
						$event_entry->match_day  = $match_day;
						$event_entry->match_time = $matchtime;
						$event_entry->captain_id = $captain_id;
						$event_entry->captain    = $captain;
						$event_entry->telephone  = $contactno;
						$event_entry->email      = $contactemail;
						$club_entry->events[]    = $event_entry;
					}
				}
				$acceptance = isset( $_POST['acceptance'] ) ? sanitize_text_field( wp_unslash( $_POST['acceptance'] ) ) : '';
				$validator  = $validator->entry_acceptance( $acceptance );
			}
		}
		if ( ! $validator->error ) {
			$club = get_club( $affiliatedclub );
			$club->cup_entry( $club_entry );
			$msg = __( 'Cup entry complete', 'racketmanager' );
		} else {
			$msg = __( 'Errors in cup entry form', 'racketmanager' );
		}
		//phpcs:enable WordPress.Security.NonceVerification.Missing
		if ( $validator->error ) {
			array_push( $return, $msg, $validator->error_msg, $validator->error_field );
			wp_send_json_error( $return, '500' );
		} else {
			wp_send_json_success( $msg );
		}
	}
	/**
	 * League entry request
	 *
	 * @see templates/leagueentry.php
	 */
	public function league_entry_request() {
		$return        = array();
		$msg           = '';
		$validator     = new Racketmanager_Entry_Form_Validator();
		$courts_needed = array();

		check_admin_referer( 'league-entry' );

		if ( ! is_user_logged_in() ) {
			$validator = $validator->logged_in_entry();
		} else {
			$season         = isset( $_POST['season'] ) ? sanitize_text_field( wp_unslash( $_POST['season'] ) ) : '';
			$competition_id = isset( $_POST['competitionId'] ) ? sanitize_text_field( wp_unslash( $_POST['competitionId'] ) ) : '';
			$validator      = $validator->competition( $competition_id );
			$affiliatedclub = isset( $_POST['affiliatedClub'] ) ? sanitize_text_field( wp_unslash( $_POST['affiliatedClub'] ) ) : '';
			$validator      = $validator->club( $affiliatedclub );
			$events         = isset( $_POST['event'] ) ? array_map( 'intval', $_POST['event'] ) : array();
			$validator      = $validator->events_entry( $events );
			// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$team_event           = isset( $_POST['teamEvent'] ) ? wp_unslash( $_POST['teamEvent'] ) : array();
			$team_event_titles    = isset( $_POST['teamEventTitle'] ) ? wp_unslash( $_POST['teamEventTitle'] ) : array();
			$team_event_league    = isset( $_POST['teamEventLeague'] ) ? wp_unslash( $_POST['teamEventLeague'] ) : array();
			$competition_events   = explode( ',', isset( $_POST['competition_events'] ) ? sanitize_text_field( wp_unslash( $_POST['competition_events'] ) ) : '' );
			$captains             = isset( $_POST['captain'] ) ? wp_unslash( $_POST['captain'] ) : array();
			$captain_ids          = isset( $_POST['captainId'] ) ? wp_unslash( $_POST['captainId'] ) : array();
			$contactnos           = isset( $_POST['contactno'] ) ? wp_unslash( $_POST['contactno'] ) : array();
			$contactemails        = isset( $_POST['contactemail'] ) ? wp_unslash( $_POST['contactemail'] ) : array();
			$matchdays            = isset( $_POST['matchday'] ) ? wp_unslash( $_POST['matchday'] ) : array();
			$matchtimes           = isset( $_POST['matchtime'] ) ? wp_unslash( $_POST['matchtime'] ) : array();
			$comments             = isset( $_POST['commentDetails'] ) ? sanitize_textarea_field( wp_unslash( $_POST['commentDetails'] ) ) : '';
			$num_courts_available = isset( $_POST['numCourtsAvailable'] ) ? intval( $_POST['numCourtsAvailable'] ) : '';
			$validator            = $validator->num_courts_available( $num_courts_available );

			$club_entry           = new \stdClass();
			$club_entry->club     = $affiliatedclub;
			$club_entry->season   = $season;
			$club_entry->comments = $comments;
			if ( $competition_id ) {
				$competition = get_competition( $competition_id );
				if ( ! $competition ) {
					$validator = $validator->competition( $competition );
				}
				if ( ! empty( $competition->match_day_restriction ) ) {
					$match_day_restriction = true;
				} else {
					$match_day_restriction = false;
				}
				$weekend_allowed         = isset( $competition->match_day_weekends ) ? true : false;
				$club_entry->competition = $competition;
				$competition_days        = array();
				for ( $i = 0; $i < 7; ++$i ) {
					$competition_days['teams'][ $i ]     = array();
					$competition_days['available'][ $i ] = array();
				}
				$weekend_matches = array();
			}

			// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			foreach ( $events as $event_id ) {
				$pos = array_search( strval( $event_id ), $competition_events, true );
				if ( false !== $pos ) {
					unset( $competition_events[ $pos ] );
				}
				$event = get_event( $event_id );
				$week  = isset( $event->offset ) ? $event->offset : '0';
				if ( ! isset( $courts_needed[ $week ] ) ) {
					$courts_needed[ $week ] = array();
				}
				$weekend_matches[ $event->type ] = 0;
				$event_days                      = isset( $event->match_days_allowed ) ? $event->match_days_allowed : array();
				if ( ! empty( $event_days ) ) {
					foreach ( $event_days as $event_day => $value ) {
						if ( ! isset( $competition_days['teams'][ $event_day ][ $event->type ] ) ) {
							$competition_days['teams'][ $event_day ][ $event->type ] = 0;
						}
					}
				}
				$event_entry       = new \stdClass();
				$event_entry->id   = $event->id;
				$event_entry->name = $event->name;

				$teams      = isset( $team_event[ $event->id ] ) ? $team_event[ $event->id ] : array();
				$field_ref  = $event->id;
				$field_name = $event->name;
				$validator  = $validator->teams( $teams, $field_ref, $field_name );
				if ( ! empty( $teams ) ) {
					// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					$event_teams = explode( ',', isset( $_POST['event_teams'][ $event->id ] ) ? wp_unslash( $_POST['event_teams'][ $event->id ] ) : '' );
					// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					foreach ( $teams as $team_id ) {
						$pos = array_search( $team_id, $event_teams, true );
						if ( false !== $pos ) {
							unset( $event_teams[ $pos ] );
						}
						$team_event_title = isset( $team_event_titles[ $event->id ][ $team_id ] ) ? $team_event_titles[ $event->id ][ $team_id ] : '';
						$captain          = isset( $captains[ $event->id ][ $team_id ] ) ? $captains[ $event->id ][ $team_id ] : '';
						$captain_id       = isset( $captain_ids[ $event->id ][ $team_id ] ) ? $captain_ids[ $event->id ][ $team_id ] : 0;
						$contactno        = isset( $contactnos[ $event->id ][ $team_id ] ) ? $contactnos[ $event->id ][ $team_id ] : '';
						$contactemail     = isset( $contactemails[ $event->id ][ $team_id ] ) ? $contactemails[ $event->id ][ $team_id ] : '';
						$match_day        = isset( $matchdays[ $event->id ][ $team_id ] ) ? $matchdays[ $event->id ][ $team_id ] : '';
						$match_time       = isset( $matchtimes[ $event->id ][ $team_id ] ) ? $matchtimes[ $event->id ][ $team_id ] : '';
						$league_id        = isset( $team_event_league[ $event->id ][ $team_id ] ) ? $team_event_league[ $event->id ][ $team_id ] : null;
						$field_ref        = $event->id . '-' . $team_id;
						$field_name       = $team_event_title;
						$validator        = $validator->match_day( $match_day, $field_ref, $field_name, $match_day_restriction, $event_days );
						$validator        = $validator->match_time( $match_time, $field_ref, $field_name );
						$validator        = $validator->captain( $captain, $contactno, $contactemail, $field_ref, $field_name );
						if ( $match_day_restriction && $weekend_allowed && ( '5' === $match_day || '6' === $match_day ) ) {
							if ( empty( $weekend_matches[ $event->type ] ) ) {
								++$weekend_matches[ $event->type ];
							} else {
								$validator = $validator->weekend_match( $field_ref );
							}
						}
						if ( ! $validator->error ) {
							++$competition_days['teams'][ $match_day ][ $event->type ];
							$competition_days['available'][ $match_day ] = $num_courts_available / $event->num_rubbers;
							if ( strlen( $match_time ) === 5 ) {
								$match_time = $match_time . ':00';
							}
							if ( ! isset( $courts_needed[ $week ][ $match_day ] ) ) {
								$courts_needed[ $week ][ $match_day ] = array();
							} elseif ( ! isset( $courts_needed[ $week ][ $match_day ][ $match_time ] ) ) {
								foreach ( $courts_needed[ $week ][ $match_day ] as $schedule_time => $value ) {
									$validator = $validator->match_overlap( $match_time, $schedule_time, $field_ref, $field_name );
								}
							}
							if ( isset( $courts_needed[ $week ][ $match_day ][ $match_time ] ) ) {
								$courts_needed[ $week ][ $match_day ][ $match_time ]['teams']  += 1;
								$courts_needed[ $week ][ $match_day ][ $match_time ]['courts'] += $event->num_rubbers;
							} else {
								$courts_needed[ $week ][ $match_day ][ $match_time ]['teams']  = 1;
								$courts_needed[ $week ][ $match_day ][ $match_time ]['courts'] = $event->num_rubbers;
							}
							$team_entry             = new \stdClass();
							$team_entry->id         = $team_id;
							$team_entry->match_day  = $match_day;
							$team_entry->match_time = $match_time;
							$team_entry->captain_id = $captain_id;
							$team_entry->captain    = $captain;
							$team_entry->telephone  = $contactno;
							$team_entry->email      = $contactemail;
							$team_entry->existing   = $league_id;

							$event_entry->team[] = $team_entry;
						}
					}
					if ( ! empty( $event_teams ) ) {
						$event_entry->withdrawn_teams = $event_teams;
					}
					$club_entry->event[] = $event_entry;
				}
			}
			if ( ! empty( $competition_events ) ) {
				$club_entry->withdrawn_events = $competition_events;
			}
			if ( ! empty( $num_courts_available ) ) {
				$club_entry->num_courts_available = $num_courts_available;
				foreach ( $courts_needed as $week ) {
					foreach ( $week as $match_day => $match_day_value ) {
						foreach ( $match_day_value as $match_time => $court_data ) {
							$validator = $validator->court_needs( $num_courts_available, $court_data, $match_day, $match_time );
						}
					}
				}
				if ( ! $validator->error && $match_day_restriction && $weekend_allowed && ! empty( $weekend_matches ) ) {
					foreach ( $weekend_matches as $event_type => $team_count ) {
						if ( $team_count ) {
							$i = 0;
							foreach ( $competition_days['teams'] as $match_day => $value ) {
								if ( isset( $value[ $event_type ] ) && $i < 5 ) {
									$num_teams[ $match_day ] = array_sum( $value );
									if ( $num_teams[ $match_day ] ) {
										$free_slots = $num_teams[ $match_day ] / 2 / $competition_days['available'][ $i ];
										$validator  = $validator->free_slots( $free_slots );
									}
								}
								++$i;
							}
						}
					}
				}
			}
			$acceptance = isset( $_POST['acceptance'] ) ? sanitize_text_field( wp_unslash( $_POST['acceptance'] ) ) : '';
			$validator  = $validator->entry_acceptance( $acceptance );
		}
		if ( ! $validator->error ) {
			$club = get_club( $affiliatedclub );
			$club->league_entry( $club_entry );
			$msg = __( 'League entry complete', 'racketmanager' );
		} else {
			$msg = __( 'Errors in league entry form', 'racketmanager' );
		}
		if ( $validator->error ) {
			array_push( $return, $msg, $validator->error_msg, $validator->error_field );
			wp_send_json_error( $return, '500' );
		} else {
			wp_send_json_success( $msg );
		}
	}
	/**
	 * Build screen to allow printing of match cards
	 */
	public function print_match_card() {
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
			$match_id = isset( $_POST['matchId'] ) ? intval( $_POST['matchId'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$match    = get_match( $match_id );
			if ( function_exists( '\sponsor_level_cat_func' ) ) {
				$sponsor_html = \sponsor_level_cat_func(
					array(
						'columns' => 1,
						'title'   => 'no',
						'bio'     => 'no',
						'link'    => 'no',
					),
					''
				);
			} else {
				$sponsor_html = '';
			}
			if ( isset( $match->league->num_rubbers ) && $match->league->num_rubbers > 0 ) {
				$match->rubbers = $match->get_rubbers();
				$template       = 'match-card-rubbers';
			} else {
				$template = 'match-card';
			}
			$template_args['match']       = $match;
			$template_args['sponsorhtml'] = $sponsor_html;
			$shortcode                    = new RacketManager_Shortcodes();
			$output                       = $shortcode->load_template(
				$template,
				$template_args,
			);
		}
		if ( $valid ) {
			wp_send_json_success( $output );
		} else {
			wp_send_json_error( $message, '500' );
		}
	}
}
