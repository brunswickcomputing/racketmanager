<?php
/**
 * AJAX Front end response methods

 * @package    RacketManager
 * @subpackage RacketManager_AJAX
 */

namespace Racketmanager;

use DateMalformedStringException;
use JetBrains\PhpStorm\NoReturn;
use stdClass;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

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
		add_action( 'wp_ajax_nopriv_racketmanager_club_player_request', array( &$this, 'logged_out' ) );
		add_action( 'wp_ajax_racketmanager_club_players_remove', array( &$this, 'club_player_remove' ) );
		add_action( 'wp_ajax_nopriv_racketmanager_club_players_remove', array( &$this, 'logged_out' ) );

		add_action( 'wp_ajax_racketmanager_update_team', array( &$this, 'update_team' ) );
		add_action( 'wp_ajax_nopriv_racketmanager_update_team', array( &$this, 'logged_out' ) );
		add_action( 'wp_ajax_racketmanager_update_club', array( &$this, 'update_club' ) );
		add_action( 'wp_ajax_nopriv_racketmanager_update_club', array( &$this, 'logged_out' ) );
		add_action( 'wp_ajax_racketmanager_update_player', array( &$this, 'update_player' ) );
		add_action( 'wp_ajax_nopriv_racketmanager_update_player', array( &$this, 'logged_out' ) );

		add_action( 'wp_ajax_racketmanager_get_team_info', array( &$this, 'get_team_event_info' ) );
		add_action( 'wp_ajax_nopriv_racketmanager_get_team_info', array( &$this, 'logged_out' ) );
		add_action( 'wp_ajax_racketmanager_cup_entry', array( &$this, 'cup_entry_request' ) );
		add_action( 'wp_ajax_nopriv_racketmanager_cup_entry', array( &$this, 'logged_out' ) );
		add_action( 'wp_ajax_racketmanager_league_entry', array( &$this, 'league_entry_request' ) );
		add_action( 'wp_ajax_nopriv_racketmanager_league_entry', array( &$this, 'logged_out' ) );
		add_action( 'wp_ajax_racketmanager_tournament_entry', array( &$this, 'tournament_entry_request' ) );
		add_action( 'wp_ajax_nopriv_racketmanager_tournament_entry', array( &$this, 'logged_out' ) );
		add_action( 'wp_ajax_racketmanager_tournament_payment_create', array( &$this, 'tournament_payment_create' ) );
		add_action( 'wp_ajax_nopriv_racketmanager_tournament_payment_create', array( &$this, 'logged_out' ) );
		add_action( 'wp_ajax_racketmanager_update_payment', array( &$this, 'update_payment' ) );
		add_action( 'wp_ajax_nopriv_racketmanager_update_payment', array( &$this, 'logged_out' ) );

		add_action( 'wp_ajax_racketmanager_match_card', array( &$this, 'print_match_card' ) );
		add_action( 'wp_ajax_nopriv_racketmanager_match_card', array( &$this, 'print_match_card' ) );
		add_action( 'wp_ajax_racketmanager_match_rubber_status', array( &$this, 'match_rubber_status' ) );
		add_action( 'wp_ajax_nopriv_racketmanager_match_rubber_status', array( &$this, 'logged_out_modal' ) );
		add_action( 'wp_ajax_racketmanager_set_match_rubber_status', array( &$this, 'set_match_rubber_status' ) );
		add_action( 'wp_ajax_nopriv_racketmanager_set_match_rubber_status', array( &$this, 'logged_out' ) );
		add_action( 'wp_ajax_racketmanager_match_status', array( &$this, 'match_status' ) );
		add_action( 'wp_ajax_nopriv_racketmanager_match_status', array( &$this, 'logged_out_modal' ) );
		add_action( 'wp_ajax_racketmanager_set_match_status', array( &$this, 'set_match_status' ) );
		add_action( 'wp_ajax_nopriv_racketmanager_set_match_status', array( &$this, 'logged_out' ) );
		add_action( 'wp_ajax_racketmanager_get_message', array( &$this, 'get_message' ) );
		add_action( 'wp_ajax_nopriv_racketmanager_get_message', array( &$this, 'logged_out' ) );
		add_action( 'wp_ajax_racketmanager_delete_message', array( &$this, 'delete_message' ) );
		add_action( 'wp_ajax_nopriv_racketmanager_delete_message', array( &$this, 'logged_out' ) );
		add_action( 'wp_ajax_racketmanager_delete_messages', array( &$this, 'delete_messages' ) );
		add_action( 'wp_ajax_nopriv_racketmanager_deletes_message', array( &$this, 'logged_out' ) );
		add_action( 'wp_ajax_racketmanager_match_option', array( &$this, 'show_match_option' ) );
		add_action( 'wp_ajax_nopriv_racketmanager_match_option', array( &$this, 'logged_out_modal' ) );
		add_action( 'wp_ajax_racketmanager_set_match_date', array( &$this, 'set_match_date' ) );
		add_action( 'wp_ajax_nopriv_racketmanager_set_match_date', array( &$this, 'logged_out' ) );
		add_action( 'wp_ajax_racketmanager_switch_home_away', array( &$this, 'switch_home_away' ) );
		add_action( 'wp_ajax_nopriv_racketmanager_switch_home_away', array( &$this, 'logged_out' ) );
		add_action( 'wp_ajax_nopriv_racketmanager_reset_password', array( &$this, 'reset_password' ) );
		add_action( 'wp_ajax_racketmanager_search_players', array( &$this, 'search_players' ) );
		add_action( 'wp_ajax_nopriv_racketmanager_search_players', array( &$this, 'search_players' ) );
		add_action( 'wp_ajax_racketmanager_team_partner', array( &$this, 'team_partner' ) );
		add_action( 'wp_ajax_nopriv_racketmanager_team_partner', array( &$this, 'logged_out_modal' ) );
		add_action( 'wp_ajax_racketmanager_validate_partner', array( &$this, 'validate_partner' ) );
		add_action( 'wp_ajax_nopriv_racketmanager_validate_partner', array( &$this, 'logged_out_modal' ) );
		add_action( 'wp_ajax_racketmanager_get_tab_data', array( &$this, 'tab_data' ) );
		add_action( 'wp_ajax_nopriv_racketmanager_get_tab_data', array( &$this, 'tab_data' ) );
		add_action( 'wp_ajax_racketmanager_reset_match_result', array( &$this, 'reset_match_result' ) );
		add_action( 'wp_ajax_nopriv_racketmanager_reset_match_result', array( &$this, 'logged_out' ) );
		add_action( 'wp_ajax_racketmanager_tournament_withdrawal', array( &$this, 'tournament_withdrawal' ) );
		add_action( 'wp_ajax_nopriv_racketmanager_tournament_withdrawal', array( &$this, 'logged_out_modal' ) );
		add_action( 'wp_ajax_racketmanager_confirm_tournament_withdrawal', array( &$this, 'confirm_tournament_withdrawal' ) );
		add_action( 'wp_ajax_nopriv_racketmanager_confirm_tournament_withdrawal', array( &$this, 'logged_out_modal' ) );
		add_action( 'wp_ajax_nopriv_racketmanager_login', array( &$this, 'login' ) );
		add_action( 'wp_ajax_racketmanager_show_team_order_players', array( &$this, 'show_team_order_players' ) );
		add_action( 'wp_ajax_nopriv_racketmanager_show_team_order_players', array( &$this, 'show_team_order_players' ) );
		add_action( 'wp_ajax_racketmanager_validate_team_order', array( &$this, 'validate_team_order' ) );
		add_action( 'wp_ajax_nopriv_racketmanager_validate_team_order', array( &$this, 'validate_team_order' ) );
		add_action( 'wp_ajax_nopriv_racketmanager_team_edit_modal', array( &$this, 'logged_out_modal' ) );
		add_action( 'wp_ajax_racketmanager_team_edit_modal', array( &$this, 'show_team_edit_modal' ) );
		add_action( 'wp_ajax_racketmanager_get_event_team_match_dropdown', array( &$this, 'get_event_team_match_dropdown' ) );
		add_action( 'wp_ajax_nopriv_racketmanager_get_event_team_match_dropdown', array( &$this, 'get_event_team_match_dropdown' ) );
	}
	/**
	 * Add item as favourite
	 */
	public function add_favourite(): void {
		$return = new stdClass();
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
	public function club_player_request(): void {
		global $racketmanager;
		$return      = array();
		$msg         = '';
		$error_field = array();
		$error_msg   = array();
		if ( check_admin_referer( 'club-player-request' ) ) {
			$player_valid = $racketmanager->validatePlayer();
			if ( $player_valid[0] ) {
				$new_player = $player_valid[1];
				if ( isset( $_POST['club'] ) ) {
					$club = get_club( intval( $_POST['club'] ) );
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
	public function club_player_remove(): void {
		$return      = array();
		$error       = false;
		$error_field = array();
		$error_msg   = array();
		$deleted     = 0;
		if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'club-player-remove' ) ) {
			$error         = true;
			$error_field[] = '';
			$error_msg[]   = __( 'Form has expired. Please refresh the page and resubmit', 'racketmanager' );
		} elseif ( isset( $_POST['clubPlayer'] ) ) {
			//phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			$club_players = $_POST['clubPlayer'];
			foreach ( $club_players as $club_player_id ) {
				$club_player = get_club_player( $club_player_id );
				if ( $club_player ) {
					$club_player->remove();
					++$deleted;
				}
			}
		}
		if ( $error ) {
			$msg = __( 'Unable to remove player', 'racketmanager' );
			array_push( $return, $msg, $error_msg, $error_field );
			wp_send_json_error( $return, '500' );
		} elseif ( $deleted ) {
			$msg = _n( 'Player removed', 'Players removed', $deleted, 'racketmanager' );
			wp_send_json_success( $msg );
		} else {
			$msg = __( 'No players selected for removal', 'racketmanager' );
			wp_send_json_success( $msg );
        }
	}
	/**
	 * Update Team
	 *
	 * @see templates/team.php
	 */
	public function update_team(): void {
		$return      = array();
		$error       = false;
		$error_field = array();
		$error_msg   = array();
        $captain_id = null;
        $contactno  = null;
        $contactemail = null;
        $match_day = null;
        $matchtime = null;
        $msg = null;
		if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'team-update' ) ) {
			$error         = true;
			$error_field[] = 'team';
			$error_msg[]   = __( 'Form has expired. Please refresh the page and resubmit', 'racketmanager' );
		} else {
			$event_id = empty( $_POST['event_id'] ) ? null : intval( $_POST['event_id'] );
			$team_id  = empty( $_POST['team_id'] ) ? null : intval( $_POST['team_id'] );
			if ( $event_id ) {
				$event_id = intval( $_POST['event_id'] );
			} else {
				$error         = true;
				$error_field[] = 'team';
				$error_msg[]   = __( 'Event not selected', 'racketmanager' );
			}
			if ( $team_id ) {
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
	public function update_club(): void {
		$updates     = false;
		$return      = array();
		$error       = false;
		$error_field = array();
		$error_msg   = array();
        $club_id = null;
        $match_secretary_id = null;
        $match_secretary_contact_no = null;
        $match_secretary_email = null;
        $address = null;
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
				$match_secretary_id = intval( $_POST['matchSecretaryId'] );
			} else {
				$error         = true;
				$error_field[] = 'matchSecretary';
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
	public function update_player(): void {
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
	public function tournament_entry_request(): void {
		$return     = array();
        $tournament = null;
        $tournament_events = array();
        $events       = array();
        $partners     = array();
        $player_id = null;
        $club_id = null;
        $btm = null;
        $contactno = null;
        $contactemail = null;
        $comments = null;
        $paid_fee = null;
        $entry_fee = null;
        $message_type = null;
        $payment_required = false;
        $return_link = null;
		$validator  = new Racketmanager_Validator_Entry_Form();
		//phpcs:disable WordPress.Security.NonceVerification.Missing
		$validator = $validator->nonce( 'tournament-entry' );
		if ( ! $validator->error ) {
			if ( ! is_user_logged_in() ) {
				$validator = $validator->logged_in_entry();
			} else {
				$tournament_id = isset( $_POST['tournamentId'] ) ? intval( $_POST['tournamentId'] ) : null;
				$season        = isset( $_POST['season'] ) ? sanitize_text_field( wp_unslash( $_POST['season'] ) ) : null;
				$player_id     = isset( $_POST['playerId'] ) ? intval( $_POST['playerId'] ) : null;
				$contactno     = isset( $_POST['contactno'] ) ? sanitize_text_field( wp_unslash( $_POST['contactno'] ) ) : '';
				$contactemail  = isset( $_POST['contactemail'] ) ? sanitize_text_field( wp_unslash( $_POST['contactemail'] ) ) : '';
				$btm           = isset( $_POST['btm'] ) ? intval( $_POST['btm'] ) : '';
				$comments      = isset( $_POST['commentDetails'] ) ? sanitize_textarea_field( wp_unslash( $_POST['commentDetails'] ) ) : '';
				$validator     = $validator->player( $player_id );
				$validator     = $validator->telephone( $contactno );
				$validator     = $validator->email( $contactemail, $player_id );
				$validator     = $validator->btm( $btm, $player_id );
				$club_id       = isset( $_POST['clubId'] ) ? sanitize_text_field( wp_unslash( $_POST['clubId'] ) ) : '';
				$validator     = $validator->club( $club_id );
				// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$events            = isset( $_POST['event'] ) ? wp_unslash( $_POST['event'] ) : array();
				$partners          = isset( $_POST['partnerId'] ) ? wp_unslash( $_POST['partnerId'] ) : array();
				$tournament_events = isset( $_POST['tournamentEvents'] ) ? explode( ',', wp_unslash( $_POST['tournamentEvents'] ) ) : null;
				$entry_fee         = isset( $_POST['priceCostTotal'] ) ? floatval( $_POST['priceCostTotal'] ) : null;
				$paid_fee          = isset( $_POST['pricePaidTotal'] ) ? floatval( $_POST['pricePaidTotal'] ) : null;
				// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$validator  = $validator->tournament( $tournament_id );
				if ( $tournament_id ) {
					$tournament = get_tournament( $tournament_id );
					$validator  = $validator->tournament_open( $tournament );
				}
				$validator = $validator->events_entry( $events, $tournament->num_entries );
				foreach ( $events as $event ) {
					$event = get_event( $event );
					if ( substr( $event->type, 1, 1 ) === 'D' ) {
						$partner_id = $partners[$event->id] ?? 0;
						$field_ref  = $event->id;
						$field_name = $event->name;
						$validator  = $validator->partner( $partner_id, $field_ref, $field_name, $event, $season, $player_id, $tournament->date );
					}
				}
				$acceptance = isset( $_POST['acceptance'] ) ? sanitize_text_field( wp_unslash( $_POST['acceptance'] ) ) : '';
				$validator  = $validator->entry_acceptance( $acceptance );
			}
		}
		if ( ! $validator->error ) {
			$tournament_entry               = new stdClass();
			$tournament_entry->all_events   = $tournament_events;
			$tournament_entry->events       = $events;
			$tournament_entry->partners     = $partners;
			$tournament_entry->player_id    = $player_id;
			$tournament_entry->club_id      = $club_id;
			$tournament_entry->btm          = $btm;
			$tournament_entry->contactno    = $contactno;
			$tournament_entry->contactemail = $contactemail;
			$tournament_entry->comments     = $comments;
			$tournament_entry->paid         = $paid_fee;
			$tournament_entry->fee          = $entry_fee;
			$entry_status                   = $tournament->set_player_entry( $tournament_entry );
			switch ( $entry_status ) {
				case 1:
					$msg              = __( 'Tournament entered and payment outstanding', 'racketmanager' );
					$return_link      = '/entry-form/' . seo_url( $tournament->name ) . '-tournament/payment/';
					$payment_required = true;
					$message_type     = 'success';
					break;
				case 2:
					$msg              = __( 'Tournament entry complete and refund outstanding', 'racketmanager' );
					$message_type     = 'warning';
					break;
				case 3:
					$msg              = __( 'No updates to tournament entry', 'racketmanager' );
					$message_type     = 'info';
					break;
				case 4:
					$msg              = __( 'Tournament entered and payment outstanding for player', 'racketmanager' );
					$message_type     = 'success';
					break;
				default:
					$msg              = __( 'Tournament entry complete', 'racketmanager' );
					$message_type     = 'success';
					break;
			}
		} else {
			$msg = __( 'Errors in tournament entry form', 'racketmanager' );
		}
		//phpcs:enable WordPress.Security.NonceVerification.Missing
		if ( $validator->error ) {
			array_push( $return, $msg, $validator->error_msg, $validator->error_field );
			wp_send_json_error( $return, '500' );
		} else {
			array_push( $return, $msg, $message_type, $payment_required, $return_link );
			wp_send_json_success( $return );
		}
	}
	/**
	 * Function to create payment intent onStripe
	 */
	public function tournament_payment_create(): void {
		global $racketmanager;
        $msg = null;
        $tournament_entry = null;
        $invoice = null;
		$valid               = true;
		$tournament_entry_id = isset( $_POST['tournament_entry'] ) ? intval( $_POST['tournament_entry'] ) : null;
		$invoice_id          = isset( $_POST['invoiceId'] ) ? intval( $_POST['invoiceId'] ) : null;
		if ( $tournament_entry_id ) {
			$tournament_entry = get_tournament_entry( $tournament_entry_id );
			if ( ! $tournament_entry ) {
				$valid = false;
				$msg   = __( 'Tournament entry not found', 'racketmanager' );
			}
		} else {
			$valid = false;
			$msg   = __( 'Tournament entry id not present', 'racketmanager' );
		}
		if ( $invoice_id ) {
			$invoice = get_invoice( $invoice_id );
			if ( ! $invoice ) {
				$valid = false;
				$msg   = __( 'Payment request not found', 'racketmanager' );
			}
		} else {
			$valid = false;
			$msg   = __( 'Payment request id not found', 'racketmanager' );
		}
		if ( ! $valid ) {
			wp_send_json_error( $msg, '500' );
		}
		$args        = array();
		$player      = get_player( $tournament_entry->player_id );
		$description = $player?->display_name;
		$tournament  = get_tournament( $tournament_entry->tournament_id );
		if ( $tournament ) {
			$description .= ' - ' . $tournament->name;
		}
		$args['description']                 = $description;
		$args['amount']                      = $invoice->amount * 100;
		$args['currency']                    = $racketmanager->currency_code;
		$args['payment_method_types']        = array('card');
		$args['statement_descriptor_suffix'] = $tournament->name;
		$stripe_details                      = new Racketmanager_Stripe();
		$stripe                              = new StripeClient( $stripe_details->api_secret_key );
		try {
			// Create a PaymentIntent with amount and currency
			$paymentIntent = $stripe->paymentIntents->create( $args );
			if ( $paymentIntent ) {
				$reference = $paymentIntent->id;
				$invoice?->set_payment_reference($reference);
			}
			$client_secret = $paymentIntent->client_secret;
			wp_send_json_success( $client_secret );
		} catch ( ApiErrorException $e ) {
			wp_send_json_error( ['error' => $e->getMessage()], '500' );
		}
	}
	/**
	 * Update payment to complete
	 */
	public function update_payment(): void {
		$return = $this->check_security_token();
		if ( ! isset( $return->error ) ) {
			$payment_reference = isset( $_POST['paymentReference'] ) ? sanitize_text_field( wp_unslash( $_POST['paymentReference'] ) ) : null;
			$payment_status    = isset( $_post['paymentStatus'] ) ? sanitize_text_field( wp_unslash( $_POST['paymentStatus'] ) ) : null;
			$stripe = new Racketmanager_Stripe();
			if ( $payment_reference ) {
				$stripe->update_payment( $payment_reference, $payment_status );
			}
		}
	}
	/**
	 * Ajax Response to get captain information
	 */
	public function get_team_event_info(): void {
        $team_info = null;
		$return    = $this->check_security_token();
		if ( ! isset( $return->error ) ) {
			$team_info = new stdClass();
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
		if ( ! isset( $return->error ) ) {
			wp_send_json_error( $return->msg, '500' );
		} else {
			wp_send_json_success( $team_info );
		}
	}
	/**
	 * Cup entry request
	 *
	 * @see templates/cup_entry.php
	 */
	public function cup_entry_request(): void {
        $start_times = array();
        $club_id     = null;
        $club_entry  = null;
		$return      = array();
		$validator   = new Racketmanager_Validator_Entry_Form();
		//phpcs:disable WordPress.Security.NonceVerification.Missing
		$validator = $validator->nonce( 'cup-entry' );
		if ( ! $validator->error ) {
			if ( ! is_user_logged_in() ) {
				$validator = $validator->logged_in_entry();
			} else {
				$season         = isset( $_POST['season'] ) ? sanitize_text_field( wp_unslash( $_POST['season'] ) ) : '';
				$competition_id = isset( $_POST['competitionId'] ) ? sanitize_text_field( wp_unslash( $_POST['competitionId'] ) ) : '';
				$club_id        = isset( $_POST['clubId'] ) ? sanitize_text_field( wp_unslash( $_POST['clubId'] ) ) : '';
				//phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$events         = isset( $_POST['event'] ) ? wp_unslash( $_POST['event'] ) : array();
				$teams          = isset( $_POST['team'] ) ? wp_unslash( $_POST['team'] ) : array();
				$captains       = isset( $_POST['captain'] ) ? wp_unslash( $_POST['captain'] ) : array();
				$captain_ids    = isset( $_POST['captainId'] ) ? wp_unslash( $_POST['captainId'] ) : array();
				$contact_nos    = isset( $_POST['contactno'] ) ? wp_unslash( $_POST['contactno'] ) : array();
				$contact_emails = isset( $_POST['contactemail'] ) ? wp_unslash( $_POST['contactemail'] ) : array();
				$matchdays      = isset( $_POST['matchday'] ) ? wp_unslash( $_POST['matchday'] ) : array();
				$match_times    = isset( $_POST['matchtime'] ) ? wp_unslash( $_POST['matchtime'] ) : array();
				//phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$comments             = isset( $_POST['commentDetails'] ) ? sanitize_textarea_field( wp_unslash( $_POST['commentDetails'] ) ) : '';
				$club_entry           = new stdClass();
				$club_entry->club     = $club_id;
				$club_entry->season   = $season;
				$club_entry->comments = $comments;
				if ( $competition_id ) {
					$competition = get_competition( $competition_id );
					if ( $competition ) {
						if ( ! empty( $competition->min_start_time_weekday ) && ! empty( $competition->max_start_time_weekday ) ) {
							$start_times['weekday']['min'] = $competition->min_start_time_weekday;
							$start_times['weekday']['max'] = $competition->max_start_time_weekday;
						}
						if ( ! empty( $competition->min_start_time_weekend ) && ! empty( $competition->max_start_time_weekend ) ) {
							$start_times['weekend']['min'] = $competition->min_start_time_weekend;
							$start_times['weekend']['max'] = $competition->max_start_time_weekend;
						}
					} else {
						$validator = $validator->competition( $competition );
					}
					$club_entry->competition = $competition;
				}

				$validator = $validator->club( $club_id );
				$validator = $validator->events_entry( $events );
				foreach ( $events as $event_id ) {
					$event      = get_event( $event_id );
					$team       = $teams[$event->id] ?? 0;
					$field_ref  = $event->id;
					$field_name = $event->name;
					$validator  = $validator->teams( $team, $field_ref, $field_name );
					if ( ! empty( $team ) ) {
						$captain      = $captains[$event->id] ?? 0;
						$captain_id   = $captain_ids[$event->id] ?? 0;
						$contactno    = $contact_nos[$event->id] ?? '';
						$contactemail = $contact_emails[$event->id] ?? '';
						$match_day    = $matchdays[$event->id] ?? '';
						$matchtime    = $match_times[$event->id] ?? '';
						$validator    = $validator->match_day( $match_day, $field_ref );
						$validator    = $validator->match_time( $matchtime, $field_ref, $match_day, $start_times );
						$validator    = $validator->captain( $captain, $contactno, $contactemail, $field_ref, $field_name );

						$event_entry             = new stdClass();
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
			$club = get_club( $club_id );
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
	 * @throws DateMalformedStringException
	 * @see templates/leagueentry.php
	 */
	public function league_entry_request(): void {
		$return                = array();
		$validator             = new Racketmanager_Validator_Entry_Form();
		$club_id               = null;
		$club_entry            = null;
		$courts_needed         = array();
		$match_day_restriction = null;
		$weekend_allowed       = null;
        $start_times           = array();
		$competition_days      = array();
		check_admin_referer( 'league-entry' );
		if ( ! is_user_logged_in() ) {
			$validator = $validator->logged_in_entry();
		} else {
			$season         = isset( $_POST['season'] ) ? sanitize_text_field( wp_unslash( $_POST['season'] ) ) : '';
			$competition_id = isset( $_POST['competitionId'] ) ? sanitize_text_field( wp_unslash( $_POST['competitionId'] ) ) : '';
			$validator      = $validator->competition( $competition_id );
			$club_id        = isset( $_POST['clubId'] ) ? sanitize_text_field( wp_unslash( $_POST['clubId'] ) ) : '';
			$validator      = $validator->club( $club_id );
			$events         = isset( $_POST['event'] ) ? array_map( 'intval', $_POST['event'] ) : array();
			$validator      = $validator->events_entry( $events );
			// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$team_event           = isset( $_POST['teamEvent'] ) ? wp_unslash( $_POST['teamEvent'] ) : array();
			$team_event_titles    = isset( $_POST['teamEventTitle'] ) ? wp_unslash( $_POST['teamEventTitle'] ) : array();
			$team_event_league    = isset( $_POST['teamEventLeague'] ) ? wp_unslash( $_POST['teamEventLeague'] ) : array();
			$competition_events   = explode( ',', isset( $_POST['competition_events'] ) ? sanitize_text_field( wp_unslash( $_POST['competition_events'] ) ) : '' );
			$captains             = isset( $_POST['captain'] ) ? wp_unslash( $_POST['captain'] ) : array();
			$captain_ids          = isset( $_POST['captainId'] ) ? wp_unslash( $_POST['captainId'] ) : array();
			$contact_nos           = isset( $_POST['contactno'] ) ? wp_unslash( $_POST['contactno'] ) : array();
			$contact_emails        = isset( $_POST['contactemail'] ) ? wp_unslash( $_POST['contactemail'] ) : array();
			$matchdays            = isset( $_POST['matchday'] ) ? wp_unslash( $_POST['matchday'] ) : array();
			$match_times           = isset( $_POST['matchtime'] ) ? wp_unslash( $_POST['matchtime'] ) : array();
			$comments             = isset( $_POST['commentDetails'] ) ? sanitize_textarea_field( wp_unslash( $_POST['commentDetails'] ) ) : '';
			$num_courts_available = isset( $_POST['numCourtsAvailable'] ) ? intval( $_POST['numCourtsAvailable'] ) : 0;
			$validator            = $validator->num_courts_available( $num_courts_available );

			$club_entry           = new stdClass();
			$club_entry->club     = $club_id;
			$club_entry->season   = $season;
			$club_entry->comments = $comments;
			if ( $competition_id ) {
				$competition = get_competition( $competition_id );
				if ( ! $competition ) {
					$validator = $validator->competition( $competition );
				}
				if ( empty( $competition->match_day_restriction ) ) {
					$match_day_restriction  = false;
				} else {
					$match_day_restriction = true;
				}
				$weekend_allowed = isset( $competition->match_day_weekends );
				if ( ! empty( $competition->min_start_time_weekday ) && ! empty( $competition->max_start_time_weekday ) ) {
					$start_times['weekday']['min'] = $competition->min_start_time_weekday;
					$start_times['weekday']['max'] = $competition->max_start_time_weekday;
				}
				if ( ! empty( $competition->min_start_time_weekend ) && ! empty( $competition->max_start_time_weekend ) ) {
					$start_times['weekend']['min'] = $competition->min_start_time_weekend;
					$start_times['weekend']['max'] = $competition->max_start_time_weekend;
				}
				$club_entry->competition = $competition;
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
				$week  = $event->offset ?? '0';
				if ( ! isset( $courts_needed[ $week ] ) ) {
					$courts_needed[ $week ] = array();
				}
				$weekend_matches[ $event->type ] = 0;
				$event_days                      = $event->match_days_allowed ?? array();
				if ( $match_day_restriction && ! empty( $event_days ) ) {
					foreach ( $event_days as $event_day => $value ) {
						if ( ! isset( $competition_days['teams'][ $event_day ][ $event->type ] ) ) {
							$competition_days['teams'][ $event_day ][ $event->type ] = 0;
						}
					}
				}
				$event_entry       = new stdClass();
				$event_entry->id   = $event->id;
				$event_entry->name = $event->name;

				$teams      = $team_event[$event->id] ?? array();
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
							array_splice( $event_teams, $pos, 1 );
						}
						$team_event_title = $team_event_titles[ $event->id ][ $team_id ] ?? '';
						$captain          = $captains[ $event->id ][ $team_id] ?? '';
						$captain_id       = $captain_ids[ $event->id ][ $team_id ] ?? 0;
						$contactno        = $contact_nos[ $event->id ][ $team_id ] ?? '';
						$contactemail     = $contact_emails[ $event->id ][ $team_id ] ?? '';
						$match_day        = $matchdays[ $event->id ] [$team_id ] ?? '';
						$match_time       = $match_times[ $event->id ][ $team_id ] ?? '';
						$league_id        = $team_event_league[ $event->id ][ $team_id ] ?? null;
						$field_ref        = $event->id . '-' . $team_id;
						$field_name       = $team_event_title;
						$validator        = $validator->match_day( $match_day, $field_ref, $match_day_restriction, $event_days );
						$validator        = $validator->match_time( $match_time, $field_ref, $match_day, $start_times );
						$validator        = $validator->captain( $captain, $contactno, $contactemail, $field_ref, $field_name );
						if ( $match_day_restriction && $weekend_allowed && ( '5' === $match_day || '6' === $match_day ) ) {
							if ( empty( $weekend_matches[ $event->type ] ) ) {
								++$weekend_matches[ $event->type ];
							} else {
								$validator = $validator->weekend_match( $field_ref );
							}
						}
						if ( ! $validator->error ) {
							if ( $match_day_restriction ) {
								++$competition_days['teams'][ $match_day ][ $event->type ];
								$competition_days['available'][ $match_day ] = $num_courts_available / $event->num_rubbers;
							}
							if ( strlen( $match_time ) === 5 ) {
								$match_time = $match_time . ':00';
							}
							if ( ! isset( $courts_needed[ $week ][ $match_day ] ) ) {
								$courts_needed[ $week ][ $match_day ] = array();
							} elseif ( ! isset( $courts_needed[ $week ][ $match_day ][ $match_time ] ) ) {
								foreach ( $courts_needed[ $week ][ $match_day ] as $schedule_time => $value ) {
									$validator = $validator->match_overlap( $match_time, $schedule_time, $field_ref );
								}
							}
							if ( isset( $courts_needed[ $week ][ $match_day ][ $match_time ] ) ) {
								$courts_needed[ $week ][ $match_day ][ $match_time ]['teams']  += 1;
								$courts_needed[ $week ][ $match_day ][ $match_time ]['courts'] += $event->num_rubbers;
							} else {
								$courts_needed[ $week ][ $match_day ][ $match_time ]['teams']  = 1;
								$courts_needed[ $week ][ $match_day ][ $match_time ]['courts'] = $event->num_rubbers;
							}
							$team_entry             = new stdClass();
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
			$club = get_club( $club_id );
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
	public function print_match_card(): void {
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
			if ( isset( $match->league->num_rubbers ) && $match->league->num_rubbers > 0 ) {
				$match->rubbers = $match->get_rubbers();
				$template       = 'match-card-rubbers';
			} else {
				$template = 'match-card';
			}
			$sponsor_html                  = '';
			$template_args['match']        = $match;
			$template_args['sponsor_html'] = $sponsor_html;
			$shortcode                     = new RacketManager_Shortcodes();
			$output                        = $shortcode->load_template(
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
	/**
	 * Build screen to allow match status to be captured
	 */
	#[NoReturn] public function match_status(): void {
        $output = null;
		$return = $this->check_security_token();
		if ( ! isset( $return->error ) ) {
			$match_id = isset( $_POST['match_id'] ) ? intval( $_POST['match_id'] ) : 0;
			$modal    = isset( $_POST['modal'] ) ? sanitize_text_field( wp_unslash( $_POST['modal'] ) ) : null;
			$match    = get_match( $match_id );
			if ( $match ) {
				$status = isset( $_POST['match_status'] ) ? sanitize_text_field( wp_unslash( $_POST['match_status'] ) ) : null;
				if ( empty( $status ) ) {
					if ( $match->is_walkover ) {
						if ( 'home' === $match->walkover ) {
							$status = 'walkover_player1';
						} else {
							$status = 'walkover_player2';
						}
					} elseif ( $match->is_retired ) {
						if ( 'home' === $match->retired ) {
							$status = 'retired_player1';
						} else {
							$status = 'retired_player2';
						}
					} elseif ( $match->is_shared ) {
						$status = 'share';
					} else {
						$status = null;
					}
				}
				$home_name      = $match->teams['home']->title;
				$away_name      = $match->teams['away']->title;
				$select         = array();
				$option         = new stdClass();
				$option->value  = 'walkover_player2';
				$option->select = 'walkover_player2';
				/* translators: %s: Home team name */
				$option->desc   = sprintf( __( 'Match not played - %s did not show', 'racketmanager' ), $home_name );
				$select[]       = $option;
				$option         = new stdClass();
				$option->value  = 'walkover_player1';
				$option->select = 'walkover_player1';
				/* translators: %s: Away team name */
				$option->desc = sprintf( __( 'Match not played - %s did not show', 'racketmanager' ), $away_name );
				$select[]     = $option;
				if ( $match->league->event->competition->is_player_entry ) {
					$option         = new stdClass();
					$option->value  = 'retired_player1';
					$option->select = 'retired_player1';
					/* translators: %s: Home team name */
					$option->desc   = sprintf( __( 'Retired - %s', 'racketmanager' ), $home_name );
					$select[]       = $option;
					$option         = new stdClass();
					$option->value  = 'retired_player2';
					$option->select = 'retired_player2';
					/* translators: %s: Away team name */
					$option->desc = sprintf( __( 'Retired - %s', 'racketmanager' ), $away_name );
					$select[]     = $option;
				}
				$option         = new stdClass();
				$option->value  = 'cancelled';
				$option->select = 'cancelled';
				$option->desc   = __( 'Cancelled', 'racketmanager' );
				$select[]       = $option;
				$option         = new stdClass();
				$option->value  = 'share';
				$option->select = 'share';
				$option->desc   = __( 'Not played', 'racketmanager' );
				$select[]       = $option;
				if ( $match->league->event->competition->is_team_entry ) {
					$option         = new stdClass();
					$option->value  = 'abandoned';
					$option->select = 'abandoned';
					$option->desc   = __( 'Abandoned', 'racketmanager' );
					$select[]       = $option;
				}
				$option         = new stdClass();
				$option->value  = 'none';
				$option->select = 'None';
				$option->desc   = __( 'Reset', 'racketmanager' );
				$select[]       = $option;
				ob_start();
				?>
				<div class="modal-dialog modal-dialog-centered modal-lg">
					<div class="modal-content">
						<form id="match-status" class="" action="#" method="post">
							<?php wp_nonce_field( 'match-status', 'racketmanager_nonce' ); ?>
							<input type="hidden" name="match_id" value="<?php echo esc_attr( $match->id ); ?>" />
							<input type="hidden" name="home_team" value="<?php echo esc_attr( $match->home_team ); ?>" />
							<input type="hidden" name="away_team" value="<?php echo esc_attr( $match->away_team ); ?>" />
							<input type="hidden" name="modal" value="<?php echo esc_attr( $modal ); ?>" />
							<div class="modal-header modal__header">
								<h4 class="modal-title"><?php esc_html_e( 'Match status', 'racketmanager' ); ?></h4>
								<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
							</div>
							<div class="modal-body">
								<div class="container-fluid">
									<div id="matchStatusResponse" class="alert_rm alert--danger" style="display: none;">
										<div class="alert__body">
											<div class="alert__body-inner">
												<span id="matchStatusResponseText"></span>
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-sm-6">
                                            <label>
                                                <select class="form-select" name="match_status" id="match_status">
                                                    <option value="" disabled selected><?php esc_html_e( 'Status', 'racketmanager' ); ?></option>
													<?php
													foreach ( $select as $option ) {
														?>
                                                        <option value="<?php echo esc_attr( $option->value ); ?>" <?php selected( $option->select, $status ); ?>><?php echo esc_html( $option->desc ); ?></option>
														<?php
													}
													?>
                                                </select>
                                            </label>
										</div>
										<div class="col-sm-6">
											<ul class="list list--naked">
												<li class="list__item">
													<dl>
														<dt class=""><?php esc_html_e( 'Match not played and one team did not show', 'racketmanager' ); ?></dt>
														<dd class=""><?php esc_html_e( 'The match has not started and at least one team cannot play.', 'racketmanager' ); ?></dd>
													</dl>
												</li>
												<?php
												if ( ! $match->league->num_rubbers ) {
													?>
														<li class="list__item">
															<dl>
																<dt class=""><?php esc_html_e( 'Retired', 'racketmanager' ); ?></dt>
																<dd class=""><?php esc_html_e( 'A player retired from a match in progress.', 'racketmanager' ); ?></dd>
															</dl>
														</li>
														<?php
												}
												?>
												<li class="list__item">
													<dl>
														<dt class=""><?php esc_html_e( 'Cancelled', 'racketmanager' ); ?></dt>
														<dd class=""><?php esc_html_e( 'Not played (and will not be played - no points awarded)', 'racketmanager' ); ?></dd>
													</dl>
												</li>
												<li class="list__item">
													<dl>
														<dt class=""><?php esc_html_e( 'Not played', 'racketmanager' ); ?></dt>
														<dd class=""><?php esc_html_e( 'Not played (and will not be played)', 'racketmanager' ); ?></dd>
													</dl>
												</li>
												<?php
												if ( $match->league->event->competition->is_team_entry ) {
													?>
													<li class="list__item">
														<dl>
															<dt class=""><?php esc_html_e( 'Abandoned', 'racketmanager' ); ?></dt>
															<dd class=""><?php esc_html_e( 'The match is partially played (and will not be finished)', 'racketmanager' ); ?></dd>
														</dl>
													</li>
													<?php
												}
												?>
												<li class="list__item">
													<dl>
														<dt class=""><?php esc_html_e( 'Reset', 'racketmanager' ); ?></dt>
														<dd class=""><?php esc_html_e( 'Clear match status', 'racketmanager' ); ?></dd>
													</dl>
												</li>
											</ul>
										</div>
									</div>
								</div>
							</div>
							<div class="modal-footer">
								<button type="button" class="btn btn-plain" data-bs-dismiss="modal"><?php esc_html_e( 'Cancel', 'racketmanager' ); ?></button>
								<button type="button" class="btn btn-primary" onclick="Racketmanager.setMatchStatus(this)"><?php esc_html_e( 'Save', 'racketmanager' ); ?></button>
							</div>
						</form>
					</div>
				</div>
				<?php
				$output = ob_get_contents();
				ob_end_clean();
			} else {
				$return->error  = true;
				$return->msg    = __( 'Match not found', 'racketmanager' );
				$return->status = 404;
			}
		}
		if ( isset( $return->error ) ) {
			$output = $this->modal_error( $return->msg );
			status_header( $return->status );
		}
		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		wp_die();
	}
	/**
	 * Set match status
	 */
	public function set_match_status(): void {
		$return         = array();
		$err_msg        = array();
		$err_field      = array();
		$valid          = true;
		$msg            = null;
        $match_id       = null;
        $match_status   = null;
        $status_message = null;
        $status_class   = null;
        $match          = null;
		if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'match-status' ) ) {
			$valid       = false;
			$err_field[] = '';
			$err_msg[]   = __( 'Form has expired. Please refresh the page and resubmit', 'racketmanager' );
		}
		if ( $valid ) {
			$modal = isset( $_POST['modal'] ) ? sanitize_text_field( wp_unslash( $_POST['modal'] ) ) : null;
			if ( $modal ) {
				$match_id = isset( $_POST['match_id'] ) ? intval( $_POST['match_id'] ) : null;
				if ( $match_id ) {
					$match        = get_match( $match_id );
					$match_status = isset( $_POST['match_status'] ) ? sanitize_text_field( wp_unslash( $_POST['match_status'] ) ) : null;
					if ( $match_status ) {
						$match_status_values = explode( '_', $match_status );
						$status_value        = $match_status_values[0];
						$player_ref = $match_status_values[1] ?? null;
						switch ( $status_value ) {
							case 'walkover':
							case 'retired':
								if ( 'player1' !== $player_ref && 'player2' !== $player_ref ) {
									$valid       = false;
									$err_field[] = 'score_status';
									$err_msg[]   = __( 'Score status team selection not valid', 'racketmanager' );
								}
								break;
							case 'none':
							case 'abandoned':
							case 'cancelled':
							case 'share':
								break;
							default:
								$valid       = false;
								$err_field[] = 'match_status';
								$err_msg[]   = __( 'Match status not valid', 'racketmanager' );
								break;
						}
						if ( $valid ) {
							$status_dtls    = $this->set_status_details( $match_status, $match->home_team, $match->away_team );
							$status_message = $status_dtls->message;
							$status_class   = $status_dtls->class;
							$match_status   = $status_dtls->status;
						}
					} else {
						$valid       = false;
						$err_field[] = 'match_status';
						$err_msg[]   = __( 'No match status selected', 'racketmanager' );
					}
				} else {
					$valid       = false;
					$err_field[] = 'match_status';
					$err_msg[]   = __( 'Match id not supplied', 'racketmanager' );
				}
			} else {
				$valid       = false;
				$err_field[] = 'match_status';
				$err_msg[]   = __( 'Modal name not supplied', 'racketmanager' );
			}
		}
		if ( $valid ) {
			array_push( $return, $msg, $match_id, $match_status, $status_message, $status_class, $modal, $match->num_rubbers );
			wp_send_json_success( $return );
		} else {
			$msg = __( 'Unable to set match status', 'racketmanager' );
			array_push( $return, $msg, $err_msg, $err_field );
			wp_send_json_error( $return, '500' );
		}
	}
	/**
	 * Build screen to match rubber status to be captured
	 */
	#[NoReturn] public function match_rubber_status(): void {
        $output = null;
		$return = $this->check_security_token();
		if ( ! isset( $return->error ) ) {
			$rubber_id = isset( $_POST['rubber_id'] ) ? intval( $_POST['rubber_id'] ) : 0;
			$modal     = isset( $_POST['modal'] ) ? sanitize_text_field( wp_unslash( $_POST['modal'] ) ) : null;
			$rubber    = get_rubber( $rubber_id );
			if ( $rubber ) {
				$status         = isset( $_POST['score_status'] ) ? sanitize_text_field( wp_unslash( $_POST['score_status'] ) ) : null;
				$match          = get_match( $rubber->match_id );
				$home_name      = $match->teams['home']->title;
				$away_name      = $match->teams['away']->title;
				$select         = array();
				$option         = new stdClass();
				$option->value  = 'walkover_player2';
				$option->select = 'walkover_player2';
				/* translators: %s: Home team name */
				$option->desc   = sprintf( __( 'Match not played - %s did not show', 'racketmanager' ), $home_name );
				$select[]       = $option;
				$option         = new stdClass();
				$option->value  = 'walkover_player1';
				$option->select = 'walkover_player1';
				/* translators: %s: Away team name */
				$option->desc   = sprintf( __( 'Match not played - %s did not show', 'racketmanager' ), $away_name );
				$select[]       = $option;
				$option         = new stdClass();
				$option->value  = 'retired_player1';
				$option->select = 'retired_player1';
				/* translators: %s: Home team name */
				$option->desc   = sprintf( __( 'Retired - %s', 'racketmanager' ), $home_name );
				$select[]       = $option;
				$option         = new stdClass();
				$option->value  = 'retired_player2';
				$option->select = 'retired_player2';
				/* translators: %s: Away team name */
				$option->desc   = sprintf( __( 'Retired - %s', 'racketmanager' ), $away_name );
				$select[]       = $option;
				$option         = new stdClass();
				$option->value  = 'abandoned';
				$option->select = 'abandoned';
				$option->desc   = __( 'Abandoned', 'racketmanager' );
				$select[]       = $option;
				$option         = new stdClass();
				$option->value  = 'share';
				$option->select = 'share';
				$option->desc   = __( 'Not played', 'racketmanager' );
				$select[]       = $option;
				$option         = new stdClass();
				$option->value  = 'none';
				$option->select = 'None';
				$option->desc   = __( 'Reset', 'racketmanager' );
				$select[]       = $option;
				$option         = new stdClass();
				$option->value  = 'invalid_player1';
				$option->select = 'invalid_player1';
				/* translators: %s: Home team name */
				$option->desc   = sprintf( __( 'Invalid player - %s', 'racketmanager' ), $home_name );
				$select[]       = $option;
				$option         = new stdClass();
				$option->value  = 'invalid_player2';
				$option->select = 'invalid_player2';
				/* translators: %s: Away team name */
				$option->desc   = sprintf( __( 'Invalid player - %s', 'racketmanager' ), $away_name );
				$select[]       = $option;
				$option         = new stdClass();
				$option->value  = 'invalid_players';
				$option->select = 'invalid_players';
				$option->desc   = __( 'Invalid player on both teams', 'racketmanager' );
				$select[]       = $option;
				if ( $match ) {
					ob_start();
					?>
					<div class="modal-dialog modal-dialog-centered modal-lg">
						<div class="modal-content">
							<form id="match-rubber-status" class="" action="#" method="post">
								<?php wp_nonce_field( 'match-rubber-status', 'racketmanager_nonce' ); ?>
								<input type="hidden" name="rubber_id" value="<?php echo esc_attr( $rubber->id ); ?>" />
								<input type="hidden" name="rubber_number" value="<?php echo esc_attr( $rubber->rubber_number ); ?>" />
								<input type="hidden" name="home_team" value="<?php echo esc_attr( $match->home_team ); ?>" />
								<input type="hidden" name="away_team" value="<?php echo esc_attr( $match->away_team ); ?>" />
								<input type="hidden" name="modal" value="<?php echo esc_attr( $modal ); ?>" />
								<div class="modal-header modal__header">
									<h4 class="modal-title"><?php esc_html_e( 'Score status', 'racketmanager' ); ?> - <?php echo esc_html( $rubber->title ); ?></h4>
									<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
								</div>
								<div class="modal-body">
									<div class="container-fluid">
										<div id="scoreStatusResponse" class="alert_rm alert--danger" style="display: none;">
											<div class="alert__body">
												<div class="alert__body-inner">
													<span id="scoreStatusResponseText"></span>
												</div>
											</div>
										</div>
										<div class="row">
											<div class="col-sm-6">
                                                <label>
                                                    <select class="form-select" name="score_status" id="score_status">
                                                        <option value="" disabled selected><?php esc_html_e( 'Status', 'racketmanager' ); ?></option>
														<?php
														foreach ( $select as $option ) {
															?>
                                                            <option value="<?php echo esc_attr( $option->value ); ?>" <?php selected( $option->select, $status ); ?>><?php echo esc_html( $option->desc ); ?></option>
															<?php
														}
														?>
                                                    </select>
                                                </label>
                                                <div id="score_statusFeedback" class="invalid-feedback"></div>
											</div>
											<div class="col-sm-6">
												<ul class="list list--naked">
													<li class="list__item">
														<dl>
															<dt class=""><?php esc_html_e( 'Walkover', 'racketmanager' ); ?></dt>
															<dd class=""><?php esc_html_e( 'The match has not started and at least one team cannot play.', 'racketmanager' ); ?></dd>
														</dl>
													</li>
													<li class="list__item">
														<dl>
															<dt class=""><?php esc_html_e( 'Retired', 'racketmanager' ); ?></dt>
															<dd class=""><?php esc_html_e( 'A player retired from a match in progress.', 'racketmanager' ); ?></dd>
														</dl>
													</li>
													<li class="list__item">
														<dl>
															<dt class=""><?php esc_html_e( 'Abandoned', 'racketmanager' ); ?></dt>
															<dd class=""><?php esc_html_e( 'The match is partially played (and will not be finished)', 'racketmanager' ); ?></dd>
														</dl>
													</li>
													<li class="list__item">
														<dl>
															<dt class=""><?php esc_html_e( 'Not played', 'racketmanager' ); ?></dt>
															<dd class=""><?php esc_html_e( 'Not played (and will not be played)', 'racketmanager' ); ?></dd>
														</dl>
													</li>
												</ul>
											</div>
										</div>
									</div>
								</div>
								<div class="modal-footer">
									<button type="button" class="btn btn-plain" data-bs-dismiss="modal"><?php esc_html_e( 'Cancel', 'racketmanager' ); ?></button>
									<button type="button" class="btn btn-primary" onclick="Racketmanager.setMatchRubberStatus(this)"><?php esc_html_e( 'Save', 'racketmanager' ); ?></button>
								</div>
							</form>
						</div>
					</div>
					<?php
					$output = ob_get_contents();
					ob_end_clean();
				} else {
					$return->error  = true;
					$return->msg    = __( 'Match not found', 'racketmanager' );
					$return->status = 404;
				}
			} else {
				$return->error  = true;
				$return->msg    = __( 'Rubber not found', 'racketmanager' );
				$return->status = 404;
			}
		}
		if ( ! isset( $return->error ) ) {
			$output = $this->modal_error( $return->msg );
			status_header( $return->status );
		}
		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		wp_die();
	}
	/**
	 * Set match rubber status
	 */
	public function set_match_rubber_status(): void {
		$return         = array();
		$err_msg        = array();
		$err_field      = array();
		$valid          = true;
		$msg            = null;
		$score_status   = null;
		$status_message = null;
		$status_class   = null;
		$rubber_number  = null;
		if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'match-rubber-status' ) ) {
			$valid       = false;
			$err_field[] = '';
			$err_msg[]   = __( 'Form has expired. Please refresh the page and resubmit', 'racketmanager' );
		}
		if ( $valid ) {
			$modal = isset( $_POST['modal'] ) ? sanitize_text_field( wp_unslash( $_POST['modal'] ) ) : null;
			if ( $modal ) {
				$rubber_number = isset( $_POST['rubber_number'] ) ? intval( $_POST['rubber_number'] ) : null;
				if ( $rubber_number ) {
					$score_status = isset( $_POST['score_status'] ) ? sanitize_text_field( wp_unslash( $_POST['score_status'] ) ) : null;
					$home_team    = isset( $_POST['home_team'] ) ? intval( $_POST['home_team'] ) : null;
					$away_team    = isset( $_POST['away_team'] ) ? intval( $_POST['away_team'] ) : null;
					if ( $score_status ) {
						$score_status_values = explode( '_', $score_status );
						$status_value        = $score_status_values[0];
						$player_ref = $score_status_values[1] ?? null;
						switch ( $status_value ) {
							case 'walkover':
							case 'retired':
								if ( 'player1' !== $player_ref && 'player2' !== $player_ref ) {
									$valid       = false;
									$err_field[] = 'score_status';
									$err_msg[]   = __( 'Score status team selection not valid', 'racketmanager' );
								}
								break;
							case 'share':
							case 'none':
							case 'invalid':
							case 'abandoned':
								break;
							default:
								$valid       = false;
								$err_field[] = 'score_status';
								$err_msg[]   = __( 'Score status not valid', 'racketmanager' );
								break;
						}
						if ( $valid ) {
							$status_dtls    = $this->set_status_details( $score_status, $home_team, $away_team );
							$status_message = $status_dtls->message;
							$status_class   = $status_dtls->class;
							$score_status   = $status_dtls->status;
						}
					} else {
						$valid       = false;
						$err_field[] = 'score_status';
						$err_msg[]   = __( 'No score status selected', 'racketmanager' );
					}
				} else {
					$valid       = false;
					$err_field[] = 'score_status';
					$err_msg[]   = __( 'Rubber number not supplied', 'racketmanager' );
				}
			} else {
				$valid       = false;
				$err_field[] = 'score_status';
				$err_msg[]   = __( 'Modal name not supplied', 'racketmanager' );
			}
		}
		if ( $valid ) {
			array_push( $return, $msg, $rubber_number, $score_status, $status_message, $status_class, $modal );
			wp_send_json_success( $return );
		} else {
			$msg = __( 'Unable to set score status', 'racketmanager' );
			array_push( $return, $msg, $err_msg, $err_field );
			wp_send_json_error( $return, '500' );
		}
	}
	/**
	 * Function to set match or rubber status details
	 *
	 * @param string $status status value.
	 * @param int $home_team home team id.
	 * @param int $away_team away_team id.
	 */
	public function set_status_details( string $status, int $home_team, int $away_team ): object {
		$status_message = array();
		$status_class   = array();
		$status_values  = explode( '_', $status );
		$status_value   = $status_values[0];
		$player_ref     = $status_values[1] ?? null;
		$winner        = null;
		$loser         = null;
		$score_message = null;
		switch ( $status_value ) {
			case 'walkover':
				$score_message = __( 'Walkover', 'racketmanager' );
				if ( 'player2' === $player_ref ) {
					$winner = $away_team;
					$loser  = $home_team;
				} elseif ( 'player1' === $player_ref ) {
					$winner = $home_team;
					$loser  = $away_team;
				}
				break;
			case 'retired':
				$score_message = __( 'Retired', 'racketmanager' );
				if ( 'player1' === $player_ref ) {
					$winner = $away_team;
					$loser  = $home_team;
				} elseif ( 'player2' === $player_ref ) {
					$winner = $home_team;
					$loser  = $away_team;
				}
				break;
			case 'invalid':
				$score_message = __( 'Invalid player', 'racketmanager' );
				if ( 'player1' === $player_ref ) {
					$winner = $away_team;
					$loser  = $home_team;
				} elseif ( 'player2' === $player_ref ) {
					$winner = $home_team;
					$loser  = $away_team;
				}
				break;
			case 'share':
				$score_message = __( 'Not played', 'racketmanager' );
				break;
			case 'abandoned':
				$score_message = __( 'Abandoned', 'racketmanager' );
				break;
			case 'cancelled':
				$score_message = __( 'Cancelled', 'racketmanager' );
				break;
			case 'none':
				$status = '';
				break;
			default:
				break;
		}
		if ( $winner ) {
			$status_message[ $winner ] = '';
			$status_message[ $loser ]  = $score_message;
			$status_class[ $winner ]   = 'winner';
			$status_class[ $loser ]    = 'loser';
		} elseif ( 'share' === $status_value || 'cancelled' === $status_value ) {
			$status_message[ $home_team ] = $score_message;
			$status_message[ $away_team ] = $score_message;
			$status_class[ $home_team ]   = 'tie';
			$status_class[ $away_team ]   = 'tie';
		} elseif ( 'abandoned' === $status_value ) {
			$status_message[ $home_team ] = $score_message;
			$status_message[ $away_team ] = $score_message;
			$status_class[ $home_team ]   = '';
			$status_class[ $away_team ]   = '';
		} elseif ( 'invalid' === $status_value ) {
			$status_message[ $home_team ] = $score_message;
			$status_message[ $away_team ] = $score_message;
			$status_class[ $home_team ]   = 'tie';
			$status_class[ $away_team ]   = 'tie';
		} else {
			$status_message[ $home_team ] = '';
			$status_message[ $away_team ] = '';
			$status_class[ $home_team ]   = '';
			$status_class[ $away_team ]   = '';
		}
		$status_dtls          = new stdClass();
		$status_dtls->message = $status_message;
		$status_dtls->class   = $status_class;
		$status_dtls->status  = $status;
		return $status_dtls;
	}
	/**
	 * Build screen to show message
	 */
	public function get_message(): void {
        $output      = null;
        $message_dtl = null;
		$return      = $this->check_security_token();
		if ( ! isset( $return->error ) ) {
			$message_id = isset( $_POST['message_id'] ) ? intval( $_POST['message_id'] ) : 0;
			if ( ! $message_id ) {
				$return->error = true;
				$return->msg   = __( 'No message id found in request', 'racketmanager' );
			} else {
				$message_dtl = get_message( $message_id );
				if ( $message_dtl ) {
					if ( '1' === $message_dtl->status ) {
						$status = '0';
						$message_dtl->set_status( $status );
					}
					ob_start();
					?>
					<div class="message_header">
						<div class="message_header_wrapper">
							<dl class="list list--flex">
								<div class="list__item">
									<dt class="list__label"><?php esc_html_e( 'From', 'racketmanager' ); ?></dt>
									<dd class="list__value">
										<?php
										if ( $message_dtl->from_name ) {
											echo esc_html( $message_dtl->from_name ) . ' ';
										}
										echo '[<a href="mailto:' . esc_attr( $message_dtl->from_email ) . '">' . esc_html( $message_dtl->from_email ) . '</a>]';
										?>
									</dd>
								</div>
								<div class="list__item">
									<dt class="list__label"><?php esc_html_e( 'Subject', 'racketmanager' ); ?></dt>
									<dd class="list__value">
										<?php echo esc_html( $message_dtl->subject ); ?>
									</dd>
								</div>
							</dl>
						</div>
						<div class="suffix_wrapper">
							<div class="time"><?php echo esc_html( mysql2date( 'd-m-Y G:i:s', $message_dtl->date ) ); ?></div>
							<div class="message-button"><a id="deleteMessage" data-msg-id="<?php echo esc_attr( $message_dtl->id ); ?>" class="btn btn-primary"><?php esc_html_e( 'Delete', 'racketmanager' ); ?></a></div>
						</div>
					</div>
					<div class="message_body ratio" style="--bs-aspect-ratio: 100%;">
						<?php $frame_source = $message_dtl->message_object; ?>
						<iframe title="<?php esc_html_e( 'Message details', 'racketmanager' ); ?>" srcdoc='<?php echo $frame_source; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>'></iframe>
					</div>
                    <script type="text/javascript">
                        document.getElementById('deleteMessage').addEventListener('click', function (e) {
                            let msgId = this.dataset.msgId;
                            Racketmanager.deleteMessage(e, msgId);
                        });
                    </script>
					<?php
					$output = ob_get_contents();
					ob_end_clean();
				} else {
					$return->error = true;
					$return->msg   = __( 'Message not found', 'racketmanager' );
				}
			}
		}
		if ( isset( $return->error ) ) {
			wp_send_json_error( $return->msg, 500 );
		} else {
			$return           = array();
			$return['output'] = $output;
			$return['status'] = $message_dtl->status;
			wp_send_json_success( $return );
		}
	}
	/**
	 * Delete message
	 */
	public function delete_message(): void {
		$output  = null;
		$success = null;
		$return  = $this->check_security_token();
		if ( ! isset( $return->error ) ) {
			$message_id = isset( $_POST['message_id'] ) ? intval( $_POST['message_id'] ) : 0;
			if ( ! $message_id ) {
				$return->error = true;
				$return->msg   = __( 'No message id found in request', 'racketmanager' );
			} else {
				$message_dtl = get_message( $message_id );
				if ( $message_dtl ) {
					$success = $message_dtl->delete();
					if ( $success ) {
						$alert_class = 'success';
						$alert_text  = __( 'Message deleted', 'racketmanager' );
					} else {
						$alert_class = 'danger';
						$alert_text  = __( 'Unable to delete message', 'racketmanager' );
					}
					ob_start();
					?>
					<div class="alert_rm alert--<?php echo esc_attr( $alert_class ); ?>">
						<div class="alert__body">
							<div class="alert__body-inner">
								<span><?php echo esc_html( $alert_text ); ?></span>
							</div>
						</div>
					</div>
					<?php
					$output = ob_get_contents();
					ob_end_clean();
				} else {
					$return->error = true;
					$return->msg   = __( 'Message not found', 'racketmanager' );
				}
			}
		}
		if ( isset( $return->error ) ) {
			wp_send_json_error( $return->msg, 500 );
		} else {
			$return            = array();
			$return['output']  = $output;
			$return['success'] = $success;
			wp_send_json_success( $return );
		}
	}
	/**
	 * Delete messages
	 */
	public function delete_messages(): void {
		$output            = null;
		$success           = null;
		$valid             = true;
		$message           = null;
		$message_type_name = null;
		if ( isset( $_POST['racketmanager_nonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_delete-messages' ) ) {
				$valid   = false;
				$message = __( 'Security token invalid', 'racketmanager' );
			}
		} else {
			$valid   = false;
			$message = __( 'No security token found in request', 'racketmanager' );
		}
		if ( $valid ) {
			$message_type = isset( $_POST['message_type'] ) ? sanitize_text_field( wp_unslash( $_POST['message_type'] ) ) : null;
			if ( ! isset( $message_type ) ) {
				$valid   = false;
				$message = __( 'You must select the type of messages to delete', 'racketmanager' );
			} else {
				$message_type_name = Racketmanager_Util::get_message_type( $message_type );
				$userid            = get_current_user_id();
				if ( $userid ) {
					$user = get_user( $userid );
					if ( $user ) {
						$success = $user->delete_messages( $message_type );
						if ( $success ) {
							$alert_class = 'success';
							$alert_text  = __( 'Messages deleted', 'racketmanager' );
						} elseif ( 0 === $success ) {
							$alert_class = 'warning';
							$alert_text  = __( 'No messages to delete', 'racketmanager' );
						} else {
							$alert_class = 'danger';
							$alert_text  = __( 'Unable to delete messages', 'racketmanager' );
						}
						ob_start();
						?>
						<div class="alert_rm alert--<?php echo esc_attr( $alert_class ); ?>">
							<div class="alert__body">
								<div class="alert__body-inner">
									<span><?php echo esc_html( $alert_text ); ?></span>
								</div>
							</div>
						</div>
						<?php
						$output = ob_get_contents();
						ob_end_clean();
					} else {
						$valid   = false;
						$message = __( 'User not found', 'racketmanager' );
					}
				} else {
					$valid   = false;
					$message = __( 'User not found', 'racketmanager' );
				}
			}
		}
		if ( $valid ) {
			$return            = array();
			$return['output']  = $output;
			$return['success'] = $success;
			$return['type']    = $message_type_name;
			wp_send_json_success( $return );
		} else {
			wp_send_json_error( $message, '500' );
		}
	}
	/**
	 * Build screen to show selected match option
	 */
	#[NoReturn] public function show_match_option(): void {
		$valid   = true;
		$message = null;
        $status  = null;
        $output  = null;
		if ( isset( $_POST['security'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), 'ajax-nonce' ) ) {
				$valid   = false;
				$message = __( 'Security token invalid', 'racketmanager' );
				$status  = 403;
			}
		} else {
			$valid   = false;
			$message = __( 'No security token found in request', 'racketmanager' );
			$status  = 403;
		}
		if ( $valid ) {
			$match_id = isset( $_POST['match_id'] ) ? intval( $_POST['match_id'] ) : 0;
			$modal    = isset( $_POST['modal'] ) ? sanitize_text_field( wp_unslash( $_POST['modal'] ) ) : null;
			$match    = get_match( $match_id );
			if ( $match ) {
				$option = isset( $_POST['option'] ) ? sanitize_text_field( wp_unslash( $_POST['option'] ) ) : null;
				switch ( $option ) {
					case 'schedule_match':
						$title  = __( '(Re)schedule match', 'racketmanager' );
						$button = __( 'Save', 'racketmanager' );
						$action = 'setMatchDate';
						break;
					case 'adjust_team_score':
						$title  = __( 'Adjust team score', 'racketmanager' );
						$button = __( 'Change Results', 'racketmanager' );
						$action = 'adjustTeamScore';
						break;
					case 'switch_home':
						$title  = __( 'Switch home and away', 'racketmanager' );
						$button = __( 'Switch', 'racketmanager' );
						$action = 'switchHomeAway';
						break;
					case 'reset_match_result':
						$title  = __( 'Reset match result', 'racketmanager' );
						$button = __( 'Save', 'racketmanager' );
						$action = 'resetMatchResult';
						break;
					default:
						$valid   = false;
						$message = __( 'Invalid match option', 'racketmanager' );
						$title   = __( 'Unknown option', 'racketmanager' );
                        $action  = null;
						$status  = 403;
						break;
				}
				ob_start();
				?>
				<div class="modal-dialog modal-dialog-centered modal-lg">
					<div class="modal-content">
						<form id="match-option" class="" action="#" method="post">
							<?php wp_nonce_field( 'match-option', 'racketmanager_nonce' ); ?>
							<input type="hidden" name="match_id" value="<?php echo esc_attr( $match->id ); ?>" />
							<input type="hidden" name="home_team" value="<?php echo esc_attr( $match->home_team ); ?>" />
							<input type="hidden" name="away_team" value="<?php echo esc_attr( $match->away_team ); ?>" />
							<input type="hidden" name="modal" value="<?php echo esc_attr( $modal ); ?>" />
							<div class="modal-header modal__header">
								<h4 class="modal-title"><?php echo esc_html( $title ); ?></h4>
								<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
							</div>
							<div class="modal-body">
								<div class="container">
									<?php
									$show_header = true;
									require RACKETMANAGER_PATH . 'templates/includes/matches-teams-match.php';
									if ( 'schedule_match' === $option ) {
										if ( empty( $match->start_time ) ) {
											$date_input_type = 'date';
											$date_input      = substr( $match->date, 0, 10 );
										} else {
											$date_input_type = 'datetime-local';
											$date_input      = $match->date;
										}
										?>
										<div class="strike mb-3">
											<span><?php esc_html_e( '(Re)schedule match', 'racketmanager' ); ?></span>
										</div>
										<div class="mb-3">
											<label for="schedule-date" class="visually-hidden"><?php esc_html_e( 'New date', 'racketmanager' ); ?></label>
											<input type="<?php echo esc_attr( $date_input_type ); ?>" class="form-control" id="schedule-date" name="schedule-date" value="<?php echo esc_html( $date_input ); ?>" />
										</div>
										<div class="alert_rm" id="matchDateAlert" style="display:none;">
											<div class="alert__body">
												<div class="alert__body-inner" id="alertMatchDateResponse">
												</div>
											</div>
										</div>
										<?php
									} elseif ( 'switch_home' === $option ) {
										?>
										<div class="mb-3">
											<span><?php esc_html_e( 'Switch home and away?', 'racketmanager' ); ?></span>
										</div>
										<?php
									} elseif ( 'reset_match_result' === $option ) {
										?>
										<div class="strike mb-3">
											<span><?php esc_html_e( 'Reset match result', 'racketmanager' ); ?></span>
										</div>
										<div class="mb-3">
											<p class="text-center"><?php esc_html_e( 'This will remove scores and winner/loser', 'racketmanager' ); ?>.</p>
										</div>
										<div class="alert_rm" id="resetMatchAlert" style="display:none;">
											<div class="alert__body">
												<div class="alert__body-inner" id="alertResetMatchResponse">
												</div>
											</div>
										</div>
										<?php
									}
									?>
								</div>
							</div>
							<div class="modal-footer">
								<button type="button" class="btn btn-plain" data-bs-dismiss="modal"><?php esc_html_e( 'Cancel', 'racketmanager' ); ?></button>
								<?php
								if ( ! empty( $button ) ) {
									?>
									<button type="button" class="btn btn-primary" id="actionButton" data-action="<?php echo esc_attr( $action ); ?>" data-is-tournament="<?php echo esc_attr( $match->league->event->competition->is_tournament ); ?>"><?php echo esc_html( $button ); ?></button>
									<?php
								}
								?>
							</div>
						</form>
					</div>
				</div>
                <script type="text/javascript">
                    document.getElementById('actionButton').addEventListener('click', function (e) {
                        let action = this.dataset.action;
                        let isTournament = this.dataset.isTournament;
                        if (action === 'setMatchDate') {
                            Racketmanager.setMatchDate(e, this, isTournament);
                        } else if (action === 'switchHomeAway' ) {
                            Racketmanager.switchHomeAway(e, this, isTournament);
                        } else if (action === 'resetMatchResult' ) {
                            Racketmanager.resetMatchResult(e, this, isTournament);
                        }
                    });
                </script>
				<?php
				$output = ob_get_contents();
				ob_end_clean();
			} else {
				$valid   = false;
				$message = __( 'Match not found', 'racketmanager' );
				$status  = 404;
			}
		}
		if ( ! $valid ) {
			$output = $this->modal_error( $message );
			status_header( $status );
		}
		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		wp_die();
	}
	/**
	 * Set match date function
	 *
	 * @return void
	 */
	public function set_match_date(): void {
		$return                 = array();
		$err_msg                = array();
		$err_field              = array();
		$valid                  = true;
		$msg                    = null;
        $match_id               = null;
        $schedule_date          = null;
        $schedule_date_formated = null;
		if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'match-option' ) ) {
			$valid       = false;
			$err_field[] = '';
			$err_msg[]   = __( 'Form has expired. Please refresh the page and resubmit', 'racketmanager' );
		}
		if ( $valid ) {
			$modal = isset( $_POST['modal'] ) ? sanitize_text_field( wp_unslash( $_POST['modal'] ) ) : null;
			if ( $modal ) {
				$match_id = isset( $_POST['match_id'] ) ? intval( $_POST['match_id'] ) : null;
				if ( $match_id ) {
					$match = get_match( $match_id );
					if ( $match ) {
						$schedule_date = isset( $_POST['schedule-date'] ) ? sanitize_text_field( wp_unslash( $_POST['schedule-date'] ) ) : null;
						if ( $schedule_date ) {
							if ( strlen( $schedule_date ) === 10 ) {
								$schedule_date          = substr( $schedule_date, 0, 10 );
								$match_date             = substr( $match->date, 0, 10 );
								$schedule_date_formated = mysql2date( 'D j M', $schedule_date );
							} else {
								$schedule_date          = substr( $schedule_date, 0, 10 ) . ' ' . substr( $schedule_date, 11, 5 );
								$match_date             = $match->date;
								$schedule_date_formated = mysql2date( 'j F Y H:i', $schedule_date );
							}
							if ( $schedule_date === $match_date ) {
								$valid       = false;
								$err_field[] = 'schedule-date';
								$err_msg[]   = __( 'Date not changed', 'racketmanager' );
							} else {
								$match         = $match->update_match_date( $schedule_date, $match->date );
								$match->status = 5;
								$match->set_status( $match->status );
								$msg = __( 'Match schedule updated', 'racketmanager' );
							}
						} else {
							$valid       = false;
							$err_field[] = 'schedule-date';
							$err_msg[]   = __( 'New date not set', 'racketmanager' );
						}
					} else {
						$valid       = false;
						$err_field[] = 'schedule-date';
						$err_msg[]   = __( 'Match not found', 'racketmanager' );
					}
				} else {
					$valid       = false;
					$err_field[] = 'schedule-date';
					$err_msg[]   = __( 'Match id not supplied', 'racketmanager' );
				}
			} else {
				$valid       = false;
				$err_field[] = 'schedule-date';
				$err_msg[]   = __( 'Modal name not supplied', 'racketmanager' );
			}
		}
		if ( $valid ) {
			array_push( $return, $msg, $modal, $match_id, $schedule_date, $schedule_date_formated );
			wp_send_json_success( $return );
		} else {
			$msg = __( 'Unable to update match schedule', 'racketmanager' );
			array_push( $return, $msg, $err_msg, $err_field );
			wp_send_json_error( $return, '500' );
		}
	}
	/**
	 * Switch home and away teams function
	 *
	 * @return void
	 */
	public function switch_home_away(): void {
		$return    = array();
		$err_msg   = array();
		$err_field = array();
		$valid     = true;
		$msg       = null;
		$match_id  = null;
		$match     = null;
		if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'match-option' ) ) {
			$valid       = false;
			$err_field[] = '';
			$err_msg[]   = __( 'Form has expired. Please refresh the page and resubmit', 'racketmanager' );
		}
		if ( $valid ) {
			$modal = isset( $_POST['modal'] ) ? sanitize_text_field( wp_unslash( $_POST['modal'] ) ) : null;
			if ( $modal ) {
				$match_id = isset( $_POST['match_id'] ) ? intval( $_POST['match_id'] ) : null;
				if ( $match_id ) {
					$match = get_match( $match_id );
					if ( $match ) {
						$old_home   = $match->home_team;
						$old_away   = $match->away_team;
						$match_date = $match->league->event->seasons[ $match->season ]['match_dates'][ $match->match_day - 1 ];
						if ( $match_date ) {
							$match->update_match_date( $match_date );
							$match->set_teams( $old_away, $old_home );
							$msg = __( 'Home and away teams switched', 'racketmanager' );
						} else {
							$valid       = false;
							$err_field[] = 'schedule-date';
							$err_msg[]   = __( 'Match day not found', 'racketmanager' );
						}
					} else {
						$valid       = false;
						$err_field[] = 'schedule-date';
						$err_msg[]   = __( 'Match not found', 'racketmanager' );
					}
				} else {
					$valid       = false;
					$err_field[] = 'schedule-date';
					$err_msg[]   = __( 'Match id not supplied', 'racketmanager' );
				}
			} else {
				$valid       = false;
				$err_field[] = 'schedule-date';
				$err_msg[]   = __( 'Modal name not supplied', 'racketmanager' );
			}
		}
		if ( $valid ) {
			array_push( $return, $msg, $modal, $match_id, $match->link );
			wp_send_json_success( $return );
		} else {
			$msg = __( 'Unable to update match schedule', 'racketmanager' );
			array_push( $return, $msg, $err_msg, $err_field );
			wp_send_json_error( $return, '500' );
		}
	}
	/**
	 * Logged-out user for modal function
	 *
	 * @return void
	 */
	public function logged_out_modal(): void {
		$return = array();
		$msg    = __( 'Must be logged in to access this feature', 'racketmanager' );
		$output = $this->modal_error( $msg );
		array_push( $return, $msg, $output );
		wp_send_json_error( $return, '401' );
	}
	/**
	 * Modal error function
	 *
	 * @param string $msg message to display.
	 *
	 * @return string output html modal
	 */
	private function modal_error( string $msg ): string {
		ob_start();
		?>
		<div class="modal-dialog modal-dialog-centered modal-lg">
			<div class="modal-content">
				<div class="modal-header modal__header modal-danger">
					<h4 class="modal-title"><?php esc_html_e( 'Error', 'racketmanager' ); ?></h4>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<div class="alert_rm alert--danger">
						<div class="alert__body">
							<div class="alert__body-inner">
								<span><?php echo esc_html( $msg ); ?></span>
							</div>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-plain" data-bs-dismiss="modal"><?php esc_html_e( 'Cancel', 'racketmanager' ); ?></button>
				</div>
			</div>
		</div>
		<?php
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}
	/**
	 * Reset password function
	 *
	 * @return void
	 */
	public function reset_password(): void {
		$return    = array();
		$err_msg   = array();
		$err_field = array();
		$valid     = true;
		$msg       = null;
		if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'reset_password' ) ) {
			$valid       = false;
			$err_field[] = '';
			$err_msg[]   = __( 'Form has expired. Please refresh the page and resubmit', 'racketmanager' );
		}
		if ( $valid ) {
			$user_login = isset( $_POST['user_login'] ) ? sanitize_text_field( wp_unslash( $_POST['user_login'] ) ) : null;
			if ( $user_login ) {
				$reset = retrieve_password( $user_login );
				if ( is_wp_error( $reset ) ) {
					$valid       = false;
					$err_msg[]   = $reset->get_error_message();
					$err_field[] = 'user_login';
				} else {
					$msg = __( 'Check your email for a link to reset your password', 'racketmanager' );
				}
			} else {
				$valid       = false;
				$err_field[] = 'user_login';
				$err_msg[]   = __( 'Email address not supplied', 'racketmanager' );
			}
		}
		if ( $valid ) {
			$return[] = $msg;
			wp_send_json_success( $return );
		} else {
			$msg = __( 'Unable to request password reset', 'racketmanager' );
			array_push( $return, $msg, $err_msg, $err_field );
			wp_send_json_error( $return, '500' );
		}
	}
	/**
	 * Search players function
	 *
	 * @return void
	 */
	#[NoReturn] public function search_players(): void {
        $search_results = null;
		$return         = $this->check_security_token();
		if ( ! isset( $return->error ) ) {
			$search_string = isset( $_GET['search_string'] ) ? sanitize_text_field( wp_unslash( $_GET['search_string'] ) ) : null;
			if ( $search_string ) {
				$search_results = racketmanager_player_search( $search_string );
			} else {
				$return->error = true;
				$return->msg   = __( 'Search string not supplied', 'racketmanager' );
			}
		}
		if ( ! isset( $return->error ) ) {
			echo $search_results; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			ob_start();
			?>
			<div class="alert_rm alert--danger">
				<div class="alert__body">
					<div class="alert__body-inner">
						<span><?php echo esc_html( $return->msg ); ?></span>
					</div>
				</div>
			</div>
			<?php
			$output = ob_get_contents();
			ob_end_clean();
			echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		wp_die();
	}
	/**
	 * Build screen to show team partner
	 */
	public function team_partner(): void {
        $output         = null;
        $partner_gender = null;
		$return         = $this->check_security_token();
		if ( ! isset( $return->error ) ) {
			$event_id  = isset( $_POST['eventId'] ) ? intval( $_POST['eventId'] ) : 0;
			$player_id = isset( $_POST['playerId'] ) ? intval( $_POST['playerId'] ) : null;
			$modal     = isset( $_POST['modal'] ) ? sanitize_text_field( wp_unslash( $_POST['modal'] ) ) : null;
			$gender    = isset( $_POST['gender'] ) ? sanitize_text_field( wp_unslash( $_POST['gender'] ) ) : null;
			$season    = isset( $_POST['season'] ) ? intval( $_POST['season'] ) : null;
			$date_end  = isset( $_POST['dateEnd'] ) ? intval( $_POST['dateEnd'] ) : null;
			$event     = get_event( $event_id );
			if ( $event ) {
				if ( 'M' === $gender ) {
					if ( str_starts_with($event->type, 'M') || str_starts_with($event->type, 'B')) {
						$partner_gender = 'M';
					} else {
						$partner_gender = 'F';
					}
				} elseif ( 'F' === $gender ) {
					if ( str_starts_with($event->type, 'W') | str_starts_with($event->type, 'G')) {
						$partner_gender = 'F';
					} else {
						$partner_gender = 'M';
					}
				}
				$partner_name = null;
				$partner_btm  = null;
				$partner_id   = isset( $_POST['partnerId'] ) ? intval( $_POST['partnerId'] ) : null;
				if ( $partner_id ) {
					$partner = get_player( $partner_id );
					if ( $partner ) {
						$partner_name = $partner->display_name;
						$partner_btm  = $partner->btm;
					}
				}
				ob_start();
				?>
				<div class="modal-dialog modal-dialog-centered modal-lg">
					<div class="modal-content">
						<form id="team-partner" class="" action="#" method="post">
							<?php wp_nonce_field( 'team-partner', 'racketmanager_nonce' ); ?>
                            <input type="hidden" name="playerId" value="<?php echo esc_attr( $player_id ); ?>" />
							<input type="hidden" name="eventId" value="<?php echo esc_attr( $event->id ); ?>" />
							<input type="hidden" name="dateEnd" value="<?php echo esc_attr( $date_end ); ?>" />
							<input type="hidden" name="season" value="<?php echo esc_attr( $season ); ?>" />
							<input type="hidden" name="modal" value="<?php echo esc_attr( $modal ); ?>" />
							<div class="modal-header modal__header">
								<h4 class="modal-title"><?php echo esc_html__( 'Doubles partner', 'racketmanager' ) . ': ' . esc_html( $event->name ); ?></h4>
								<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
							</div>
							<div class="modal-body ui-front">
								<div class="container-fluid">
									<div id="partnerResponse" class="alert_rm alert--danger" style="display: none;">
										<div class="alert__body">
											<div class="alert__body-inner">
												<span id="partnerResponseText"></span>
											</div>
										</div>
									</div>
									<div class="row">
										<div class="">
											<p><?php esc_html_e( 'Specify your partner.', 'racketmanager' ); ?></p>
										</div>
										<div class="form-floating">
											<input type="text" class="form-control partner-name" id="partner" name="partner" value="<?php echo esc_attr( $partner_name ); ?>" />
											<label for="partner"><?php esc_html_e( 'Partner name', 'racketmanager' ); ?></label>
											<div id="partnerFeedback" class="invalid-feedback"></div>
										</div>
										<div class="form-floating">
											<input type="text" class="form-control partner-btm" id="partnerBTM" name="partnerBTM" value="<?php echo esc_attr( $partner_btm ); ?>" />
											<label for="partnerBTM"><?php esc_html_e( 'Partner LTA Number', 'racketmanager' ); ?></label>
											<div id="partnerBTM-feedback" class="invalid-feedback"></div>
										</div>
										<input type="hidden" name="partnerId" id="partnerId" value="<?php echo esc_html( $partner_id ); ?>" />
										<input type="hidden" id="partnerGender" value="<?php echo esc_html( $partner_gender ); ?>" />
									</div>
								</div>
							</div>
							<div class="modal-footer">
								<button type="button" class="btn btn-plain" data-bs-dismiss="modal"><?php esc_html_e( 'Cancel', 'racketmanager' ); ?></button>
								<button type="button" class="btn btn-primary" onclick="Racketmanager.partnerSave(this)"><?php esc_html_e( 'Save', 'racketmanager' ); ?></button>
							</div>
						</form>
					</div>
				</div>
				<?php
				$output = ob_get_contents();
				ob_end_clean();
			} else {
				$return->error = true;
				$return->msg   = __( 'Event not found', 'racketmanager' );
			}
		}
		if ( isset( $return->error ) ) {
			wp_send_json_error( $return->msg, 500 );
		} else {
			wp_send_json_success( $output );
		}
	}
	/**
	 * Validate tournament partner function
	 */
	public function validate_partner(): void {
		$valid        = true;
		$return       = array();
        $partner_name = null;
		$validator = new Racketmanager_Validator_Entry_Form();
		if ( isset( $_POST['racketmanager_nonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'team-partner' ) ) {
				$valid                    = false;
				$validator->error_field[] = 'partner';
				$validator->error_msg[]   = __( 'Security token invalid', 'racketmanager' );
			}
		} else {
			$valid                    = false;
			$validator->error_field[] = 'partner';
			$validator->error_msg[]   = __( 'No security token found in request', 'racketmanager' );
		}
		if ( $valid ) {
			$event_id   = isset( $_POST['eventId'] ) ? intval( $_POST['eventId'] ) : 0;
			$modal      = isset( $_POST['modal'] ) ? sanitize_text_field( wp_unslash( $_POST['modal'] ) ) : null;
			$player_id  = isset( $_POST['playerId'] ) ? intval( $_POST['playerId'] ) : null;
			$partner_id = isset( $_POST['partnerId'] ) ? intval( $_POST['partnerId'] ) : null;
			$season     = isset( $_POST['season'] ) ? intval( $_POST['season'] ) : null;
			$date_end   = isset( $_POST['dateEnd'] ) ? intval( $_POST['dateEnd'] ) : null;
			$event      = get_event( $event_id );
			if ( $event ) {
				if ( $partner_id ) {
					$partner = get_player( $partner_id );
					if ( $partner ) {
						$validator = $validator->partner( $partner_id, $event_id, null, $event, $season, $player_id, $date_end );
						if ( ! $validator->error ) {
							$partner_name = $partner->display_name;
						} else {
							$valid = false;
						}
					} else {
						$valid                    = false;
						$validator->error_field[] = 'partner';
						$validator->error_msg[]   = __( 'Partner not found', 'racketmanager' );
					}
				} else {
					$valid                    = false;
					$validator->error_field[] = 'partner';
					$validator->error_msg[]   = __( 'Partner id not found', 'racketmanager' );
				}
			} else {
				$valid                    = false;
				$validator->error_field[] = 'partner';
				$validator->error_msg[]   = __( 'Event not found', 'racketmanager' );
			}
		}
		if ( $valid ) {
			array_push( $return, $modal, $partner_id, $partner_name, $event_id );
			wp_send_json_success( $return );
		} else {
			$msg = __( 'Error with partner', 'racketmanager' );
			array_push( $return, $msg, $validator->error_msg, $validator->error_field );
			wp_send_json_error( $return, '500' );
		}
	}
	/**
	 * Retrieve tab data function
	 *
	 * @return void
	 */
	#[NoReturn] public function tab_data(): void {
        $target = null;
        $output = null;
		$return = $this->check_security_token();
		if ( ! isset( $return->error ) ) {
			$target_ref = isset( $_POST['target'] ) ? sanitize_text_field( wp_unslash( $_POST['target'] ) ) : null;
			if ( $target_ref ) {
				$target_id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
				switch ( $target_ref ) {
					case 'competition':
						$target = get_competition( $target_id );
						break;
					case 'event':
						$target = get_event( $target_id );
						break;
					case 'league':
						$target = get_league( $target_id );
						break;
					case 'tournament':
						$target = get_tournament( $target_id );
						break;
					default:
						$return->error = true;
						$return->msg   = __( 'Invalid target', 'racketmanager' );
				}
				if ( ! isset( $return->error ) ) {
					if ( $target ) {
						$tab = isset( $_POST['tab'] ) ? sanitize_text_field( wp_unslash( $_POST['tab'] ) ) : null;
						if ( $tab ) {
							$args    = array();
							$link_id = isset( $_POST['link_id'] ) ? sanitize_text_field( wp_unslash( $_POST['link_id'] ) ) : null;
							if ( ! is_null( $link_id ) ) {
								$args[ $tab ] = $link_id;
							}
							$season = isset( $_POST['season'] ) ? intval( $_POST['season'] ) : null;
							if ( $season ) {
								$args['season'] = $season;
							}
							$function_name = 'Racketmanager\racketmanager_' . $target_ref . '_' . $tab;
							if ( function_exists( $function_name ) ) {
								ob_start();
								$function_name( $target->id, $args );
								$output = ob_get_contents();
								ob_end_clean();
							} else {
								$return->error = true;
								$return->msg   = __( 'Tab not valid', 'racketmanager' );
							}
						} else {
							$return->error = true;
							$return->msg   = __( 'Tab not found', 'racketmanager' );
						}
					} else {
						$return->error = true;
						$return->msg   = __( 'Target not found', 'racketmanager' );
					}
				}
			} else {
				$return->error = true;
				$return->msg   = __( 'Target ref not found', 'racketmanager' );
			}
		}
		if ( isset( $return->error ) ) {
			echo esc_html( $return->msg );
		} else {
			echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		wp_die();
	}
	/**
	 * Reset match function
	 *
	 * @return void
	 */
	public function reset_match_result(): void {
		$return    = array();
		$err_msg   = array();
		$err_field = array();
		$valid     = true;
		$msg       = null;
        $match_id  = null;
		if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'match-option' ) ) {
			$valid       = false;
			$err_field[] = '';
			$err_msg[]   = __( 'Form has expired. Please refresh the page and resubmit', 'racketmanager' );
		}
		if ( $valid ) {
			$modal = isset( $_POST['modal'] ) ? sanitize_text_field( wp_unslash( $_POST['modal'] ) ) : null;
			if ( $modal ) {
				$match_id = isset( $_POST['match_id'] ) ? intval( $_POST['match_id'] ) : null;
				if ( $match_id ) {
					$match = get_match( $match_id );
					if ( $match ) {
						$match->reset_result();
						$msg   = __( 'Match result reset', 'racketmanager' );
					} else {
						$valid     = false;
						$err_msg[] = __( 'Match not found', 'racketmanager' );
					}
				} else {
					$valid     = false;
					$err_msg[] = __( 'Match id not supplied', 'racketmanager' );
				}
			} else {
				$valid     = false;
				$err_msg[] = __( 'Modal name not supplied', 'racketmanager' );
			}
		}
		if ( $valid ) {
			array_push( $return, $msg, $modal, $match_id );
			wp_send_json_success( $return );
		} else {
			$msg = __( 'Unable to reset match', 'racketmanager' );
			array_push( $return, $msg, $err_msg, $err_field );
			wp_send_json_error( $return, '500' );
		}
	}
	/**
	 * Tournament withdrawal modal
	 *
	 * @return void
	 */
	#[NoReturn] public function tournament_withdrawal(): void {
        $output = null;
		$return = $this->check_security_token();
		if ( ! isset( $return->error ) ) {
			$tournament_id = isset( $_POST['tournamentId'] ) ? intval( $_POST['tournamentId'] ) : null;
			$modal    = isset( $_POST['modal'] ) ? sanitize_text_field( wp_unslash( $_POST['modal'] ) ) : null;
			if ( $tournament_id ) {
				$tournament = get_tournament( $tournament_id );
				if ( $tournament ) {
					$player_id      = isset( $_POST['playerId'] ) ? intval( $_POST['playerId'] ) : null;
                    if ( $player_id ) {
						$player         = get_player( $player_id );
						$events_entered = $tournament->get_players(
							array(
								'count' => true,
								'player' => $player_id,
							)
						);
						if ( ! $events_entered ) {
							$msg = __( 'You are not currently entered into any event.', 'racketmanager' );
						} else {
							$msg = null;
						}
						ob_start();
						?>
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content">
                                <form id="tournament-withdrawal" class="" action="#" method="post">
									<?php wp_nonce_field( 'team-partner', 'racketmanager_nonce' ); ?>
                                    <input type="hidden" name="tournamentId" value="<?php echo esc_attr( $tournament->id ); ?>" />
                                    <input type="hidden" name="playerId" value="<?php echo esc_attr( $player->id ); ?>" />
                                    <input type="hidden" name="modal" value="<?php echo esc_attr( $modal ); ?>" />
                                    <div class="modal-header modal__header">
                                        <h4 class="modal-title"><?php esc_html_e( 'Withdraw', 'racketmanager' ) ; ?></h4>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body ui-front">
                                        <div class="container-fluid">
                                            <div id="withdrawResponse" class="alert_rm alert--danger" <?php echo $events_entered ? 'style="display: none;"' : null; ?>>
                                                <div class="alert__body">
                                                    <div class="alert__body-inner">
                                                        <span id="withdrawResponseText"><?php echo esc_html( $msg ); ?></span>
                                                    </div>
                                                </div>
                                            </div>
											<?php
											if ( $events_entered ) {
												?>
                                                <div class="row">
                                                    <div class="">
                                                        <p><?php esc_html_e( 'You will be withdrawn from all events if you proceed.', 'racketmanager' ); ?></p>
                                                    </div>
                                                </div>
												<?php
											}
											?>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-plain" data-bs-dismiss="modal"><?php esc_html_e( 'Cancel', 'racketmanager' ); ?></button>
										<?php
										if ( $events_entered ) {
											?>
                                            <button type="button" class="btn btn-primary" onclick="Racketmanager.confirmTournamentWithdraw(this)"><?php esc_html_e( 'Withdraw', 'racketmanager' ); ?></button>
											<?php
										}
										?>
                                    </div>
                                </form>
                            </div>
                        </div>
						<?php
						$output = ob_get_contents();
						ob_end_clean();
                    } else {
						$return->error = true;
						$return->msg   = __( 'Player id not found', 'racketmanager' );
					}
				} else {
					$return->error = true;
					$return->msg   = __( 'Tournament not found', 'racketmanager' );
				}
			} else {
				$return->error = true;
				$return->msg   = __( 'Tournament id not found', 'racketmanager' );
			}
		}
		if ( isset( $return->error ) ) {
			$output = $return->msg;
		}
		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		wp_die();
	}
	/**
	 * Tournament withdrawal modal
	 *
	 * @return void
	 */
	public function confirm_tournament_withdrawal(): void {
        $output = null;
		$return = $this->check_security_token();
		if ( ! isset( $return->error ) ) {
			$tournament_id = isset( $_POST['tournamentId'] ) ? intval( $_POST['tournamentId'] ) : null;
			if ( $tournament_id ) {
				$tournament = get_tournament( $tournament_id );
				if ( $tournament ) {
					$player_id      = isset( $_POST['playerId'] ) ? intval( $_POST['playerId'] ) : null;
                    if ( $player_id ) {
						$refund_amount  = $tournament->withdraw_player_entry( $player_id );
						if ( $refund_amount ) {
							$output = __( 'Tournament withdrawal successful and refund will be issued when tournament starts', 'racketmanager' );
						} else {
							$output = __( 'Tournament withdrawal successful', 'racketmanager' );
						}
					} else {
						$return->error = true;
						$return->msg   = __( 'Player id not found', 'racketmanager' );
					}
				} else {
					$return->error = true;
					$return->msg   = __( 'Tournament not found', 'racketmanager' );
				}
			} else {
				$return->error = true;
				$return->msg   = __( 'Tournament id not found', 'racketmanager' );
			}
		}
		if ( isset( $return->error ) ) {
			wp_send_json_error( $return->msg, 500 );
		} else {
			wp_send_json_success( $output );
		}
	}
	/**
	 * Login function
	 *
	 * @return void
	 */
	public function login(): void {
		$return    = array();
		$err_msg   = array();
		$err_field = array();
		$valid     = true;
		if ( isset( $_POST['security'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), 'ajax-nonce' ) ) {
				$valid     = false;
				$err_msg[] = __( 'Security token invalid', 'racketmanager' );
			}
		} else {
			$valid     = false;
			$err_msg[] = __( 'No security token found in request', 'racketmanager' );
		}
		if ( $valid ) {
			$info                  = array();
			$info['user_login']    = isset( $_POST['log'] ) ? sanitize_text_field( wp_unslash( $_POST['log'] ) ) : null;
			$info['user_password'] = isset( $_POST['pwd'] ) ? sanitize_text_field( wp_unslash( $_POST['pwd'] ) ) : null;
			$info['remember']      = true;
			$user           = wp_signon( $info, true );
			if ( is_wp_error( $user ) ) {
				foreach ( $user->errors as $field => $error ) {
					$err_field[] = Racketmanager_Util::get_error_field( $field );
					$err_msg[] = Racketmanager_Util::get_error_message( $field );
				}
				$valid  = false;
				$status = 401;
			}
		} else {
			$status = 403;
		}
		if ( $valid ) {
			$redirect = isset( $_POST['redirect_to'] ) ? sanitize_url( $_POST['redirect_to'] ) : home_url();
			$redirect = wp_validate_redirect( $redirect, home_url() );
			wp_send_json_success( $redirect );
		} else {
			$msg = __( 'Login failed', 'racketmanager' );
			array_push( $return, $msg, $err_msg, $err_field );
			wp_send_json_error( $return, $status );
		}
	}
	/**
	 * Show team order players function
	 *
	 * @return void
	 */
	#[NoReturn] public function show_team_order_players(): void {
        $club   = null;
        $event  = null;
        $teams  = null;
		$return = $this->check_security_token();
		if ( ! isset( $return->error ) ) {
			$club_id  = isset( $_POST['clubId'] ) ? sanitize_text_field( wp_unslash( $_POST['clubId'] ) ) : null;
			$event_id = isset( $_POST['eventId'] ) ? sanitize_text_field( wp_unslash( $_POST['eventId'] ) ) : null;
			if ( $club_id ) {
				$club = get_club( $club_id );
				if ( ! $club ) {
					$return->error = true;
					$return->msg   = __( 'Club not found', 'racketmanager' );
				}
			} else {
				$return->error = true;
				$return->msg   = __( 'Club id not supplied', 'racketmanager' );
			}
			if ( $event_id ) {
				$event = get_event( $event_id );
				if ( $event ) {
					$team_args = array();
					$team_args['season'] = $event->current_season['name'];
					$team_args['club']   = $club->id;
					$teams               = $event->get_teams( $team_args );
				} else {
					$return->error = true;
					$return->msg   = __( 'Event not found', 'racketmanager' );
				}
			} else {
				$return->error = true;
				$return->msg   = __( 'Event id not supplied', 'racketmanager' );
			}
		}
		if ( ! isset( $return->error ) ) {
			$user_can_update = false;
			if ( is_user_logged_in() ) {
				if ( current_user_can( 'manage_racketmanager' ) ) {
					$user_can_update = true;
				} else {
					$user   = wp_get_current_user();
					$userid = $user->ID;
					if ( $club->matchsecretary === $userid ) {
						$user_can_update = true;
					} elseif ( $club->is_player_captain( $userid ) ) {
						$user_can_update = true;
					}
				}
			}
			$age_limit  = isset( $event->age_limit ) ? sanitize_text_field( wp_unslash( $event->age_limit ) ) : null;
			$age_offset = isset( $event->age_offset ) ? intval( $event->age_offset ) : null;
			switch ( $event->type ) {
				case 'BD':
				case 'MD':
					$club_players['m'] = $club->get_players(
						array(
							'gender'     => 'M',
							'age_limit'  => $age_limit,
							'age_offset' => $age_offset,
						)
					);
					break;
				case 'GD':
				case 'WD':
					$club_players['f'] = $club->get_players(
						array(
							'gender'     => 'F',
							'age_limit'  => $age_limit,
							'age_offset' => $age_offset,
						)
					);
					break;
				case 'XD':
				case 'LD':
					$club_players['m'] = $club->get_players(
						array(
							'gender'     => 'M',
							'age_limit'  => $age_limit,
							'age_offset' => $age_offset,
						)
					);
					$club_players['f'] = $club->get_players(
						array(
							'gender'     => 'F',
							'age_limit'  => $age_limit,
							'age_offset' => $age_offset,
						)
					);
					break;
				default:
					$club_players['m'] = array();
					$club_players['f'] = array();
			}
			$template                      = 'team-players-list';
			$template_args['event']        = $event;
			$template_args['club']         = $club;
			$template_args['teams']        = $teams;
			$template_args['matches']      = array();
			$template_args['club_players'] = $club_players;
			$template_args['can_update']   = $user_can_update;
			$shortcode                     = new RacketManager_Shortcodes();
			$output                        = $shortcode->load_template(
				$template,
				$template_args,
			);
		} else {
			$shortcode = new RacketManager_Shortcodes();
			$output    = $shortcode->return_error( $return->msg );
		}
		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		wp_die();
	}
	/**
	 * Validate team order
	 *
	 * @return void
	 */
	public function validate_team_order(): void {
		$valid    = true;
		$err_msg  = array();
		$err_fld  = array();
		$status   = 403;
		$return   = array();
        $team_id  = null;
        $match_id = null;
        $set_team = null;
        $rubber   = null;
        $event    = null;
		if ( isset( $_POST['security'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), 'ajax-nonce' ) ) {
				$valid     = false;
				$err_msg[] = __( 'Security token invalid', 'racketmanager' );
				$err_fld[] = 'club_id';
			}
		} else {
			$valid     = false;
			$err_msg[] = __( 'No security token found in request', 'racketmanager' );
			$err_fld[] = 'club_id';
		}
		if ( $valid ) {
			$status = 401;
			$club_id  = isset( $_POST['clubId'] ) ? intval( $_POST['clubId'] ) : null;
			$event_id = isset( $_POST['eventId'] ) ? intval( $_POST['eventId'] ) : null;
			$team_id  = isset( $_POST['teamId'] ) ? intval( $_POST['teamId'] ) : null;
			$match_id = isset( $_POST['matchId'] ) ? intval( $_POST['matchId'] ) : null;
			$set_team = isset( $_POST['setTeam'] ) ? sanitize_text_field( wp_unslash( $_POST['setTeam'] ) ) : null;
			if ( $club_id ) {
				$club = get_club( $club_id );
				if ( ! $club ) {
					$valid     = false;
					$err_msg[] = __( 'Club not found', 'racketmanager' );
					$err_fld[] = 'club_id';
				}
			} else {
				$valid   = false;
				$err_msg[] = __( 'Club id not supplied', 'racketmanager' );
				$err_fld[] = 'club_id';
			}
			if ( $event_id ) {
				$event = get_event( $event_id );
				if ( ! $event ) {
					$valid     = false;
					$err_msg[] = __( 'Event not found', 'racketmanager' );
					$err_fld[] = 'event_id';
				}
			} else {
				$valid     = false;
				$err_msg[] = __( 'Event id not supplied', 'racketmanager' );
				$err_fld[] = 'event_id';
			}
		}
		if ( $valid ) {
			$rubber_nums = isset( $_POST['rubber_num'] ) ? wp_unslash( $_POST['rubber_num'] ) : null;
			$players     = isset( $_POST['players'] ) ? wp_unslash( $_POST['players'] ) : null;
			$wtns        = isset( $_POST['wtn'] ) ? wp_unslash( $_POST['wtn'] ) : null;
            $rubbers     = array();
			foreach ( $rubber_nums as $rubber_num ) {
				$new_rubber             = new stdClass();
				$new_rubber->num        = $rubber_num;
				$new_rubber->players    = $players[ $rubber_num ];
				$new_rubber->wtn        = $wtns[ $rubber_num ];
				$new_rubber->status     = null;
				$rubbers[ $rubber_num ] = $new_rubber;
			}
			$match_type    = substr( $event->type, 1, 1 );
			$match_players = array();
			foreach ( $rubbers as $rubber ) {
				$team_wtn = 0;
				foreach( $rubber->players as $player_ref => $player_id ) {
					if ( $player_id ) {
						$player = get_club_player( $player_id );
						if ( $player ) {
							$player_found = in_array( $player_id, $match_players, true );
							if ( $player_found ) {
								$valid     = false;
								$err_fld[] = 'players_' . $rubber->num . '_' . $player_ref;
								$err_msg[] = __( 'Player already selected', 'racketmanager' );
							} else {
								$team_wtn       += empty( $player->player->wtn[ $match_type ] ) ? 40.9 : $player->player->wtn[ $match_type ];
								$match_players[] = $player_id;
							}
						}
					} else {
						$valid     = false;
						$err_fld[] = 'players_' . $rubber->num . '_' . $player_ref;
						$err_msg[] = __( 'Player not selected', 'racketmanager' );
					}
				}
				$rubber->wtn = round( $team_wtn, 1 );
			}
			$rubbers[ $rubber->num ] = $rubber;
		}
		if ( $valid ) {
			$valid_order = true;
			foreach( $rubbers as $rubber_num => $rubber ) {
				if ( isset( $rubbers[ $rubber_num + 1 ] ) ) {
					if ( $rubber->wtn <= $rubbers[ $rubber_num + 1 ]->wtn ) {
						$rubber->status       = 'W';
						$rubber->status_class = 'winner';
					} else {
						$valid_order          = false;
						$rubber->status       = 'L';
						$rubber->status_class = 'loser';
					}
				}
				if ( isset( $rubbers[ $rubber_num - 1 ] ) ) {
					if ( $rubber->wtn >= $rubbers[ $rubber_num - 1 ]->wtn ) {
						if ( 'L' !== $rubber->status ) {
							$rubber->status       = 'W';
							$rubber->status_class = 'winner';
						}
					} else {
						$valid_order          = false;
						$rubber->status       = 'L';
						$rubber->status_class = 'loser';
					}
				}
			}
			$rubbers[ $rubber->num ] = $rubber;
		}
		if ( $valid ) {
			if ( $valid_order ) {
				if ( $set_team ) {
					$updates = false;
					if ( $match_id ) {
						$match = get_match( $match_id );
						if ( $match ) {
							if ( $team_id ) {
								if ( $team_id === intval( $match->home_team ) ) {
									$opponent = 'home';
								} elseif ( $team_id === intval( $match->away_team ) ) {
									$opponent = 'away';
								} else {
									$opponent = null;
								}
								if ( $opponent ) {
									$match_rubbers = $match->get_rubbers();
									foreach ( $match_rubbers as $match_rubber ) {
										$rubber = $rubbers[$match_rubber->rubber_number] ?? null;
										if ( $rubber ) {
											$rubber_players[ $opponent ] = $rubber->players;
											$match_rubber->set_players( $rubber_players );
											$updates = true;
										}
									}
								}
							}
						}
					}
					if ( $updates ) {
						$msg = __( 'Team players set', 'racketmanager' );
					} else {
						$msg = __( 'Valid playing order but unable to set team', 'racketmanager' );
					}
				} else {
					$msg = __( 'Valid playing order', 'racketmanager' );
				}
			} else {
				$msg = __( 'Invalid playing order', 'racketmanager' );
			}
			array_push( $return, $rubbers, $msg, $valid_order );
			wp_send_json_success( $return );
		} else {
			$msg    = __( 'Unable to validate match', 'racketmanager' );
			array_push( $return, $msg, $err_msg, $err_fld );
			wp_send_json_error( $return, $status );
		}
	}
	/**
	 * Build screen to show team edit
	 */
	#[NoReturn] public function show_team_edit_modal(): void {
        $event_team = null;
        $event      = null;
        $team       = null;
        $modal      = null;
		$return  = $this->check_security_token();
		if ( ! isset( $return->error ) ) {
			$team_id  = isset( $_POST['teamId'] ) ? intval( $_POST['teamId'] ) : null;
			$event_id = isset( $_POST['eventId'] ) ? intval( $_POST['eventId'] ) : null;
			$modal    = isset( $_POST['modal'] ) ? sanitize_text_field( wp_unslash( $_POST['modal'] ) ) : null;
			if ( $team_id ) {
				$team = get_team( $team_id );
				if ( $team ) {
					if ( $event_id ) {
						$event = get_event( $event_id );
						if ( $event ) {
							$event_team = $event->get_team_info( $team_id );
							if ( ! $event_team ) {
								$return->error  = true;
								$return->msg    = __( 'Event team not found', 'racketmanager' );
								$return->status = 404;
							}
						} else {
							$return->error  = true;
							$return->msg    = __( 'Event not found', 'racketmanager' );
							$return->status = 404;
						}
					} else {
						$return->error  = true;
						$return->msg    = __( 'Event id not found', 'racketmanager' );
						$return->status = 404;
					}
				} else {
					$return->error  = true;
					$return->msg    = __( 'Team not found', 'racketmanager' );
					$return->status = 404;
				}
			} else {
				$return->error  = true;
				$return->msg    = __( 'Team id not found', 'racketmanager' );
				$return->status = 404;
			}
		}
		if ( ! isset( $return->error ) ) {
			$matchdays = Racketmanager_Util::get_weekdays();
			ob_start();
			?>
			<div class="modal-dialog modal-dialog-centered modal-lg">
				<div class="modal-content">
					<form id="team-update" class="" action="#" method="post">
						<?php wp_nonce_field( 'team-update', 'racketmanager_nonce' ); ?>
						<input type="hidden" name="team_id" value="<?php echo esc_attr( $team->id ); ?>" />
						<input type="hidden" name="event_id" value="<?php echo esc_attr( $event->id ); ?>" />
						<input type="hidden" name="modal" value="<?php echo esc_attr( $modal ); ?>" />
						<input type="hidden" name="club" id="club" value="<?php echo esc_attr( $team->club_id ); ?>" />
						<div class="modal-header modal__header">
							<h4 class="modal-title"><?php esc_html_e( 'Edit team', 'racketmanager' ) ; ?></h4>
							<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
						</div>
						<div class="modal-body ui-front">
							<div class="container-fluid">
								<div id="teamUpdateResponse-<?php echo esc_attr( $event->id ); ?>-<?php echo esc_attr( $team->id ); ?>" class="alert_rm alert--danger" style="display: none;">
									<div class="alert__body">
										<div class="alert__body-inner">
											<span id="teamUpdateResponseText-<?php echo esc_attr( $event->id ); ?>-<?php echo esc_attr( $team->id ); ?>"></span>
										</div>
									</div>
								</div>
							<fieldset class="form-control mb-3">
								<legend><?php esc_html_e( 'Captain', 'racketmanager' ); ?></legend>
								<div class="row">
									<div class="form-floating mb-3">
										<input type="text" class="teamcaptain form-control" id="captain-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" name="captain-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" value="<?php echo esc_html( $event_team->captain ); ?>" />
										<input type="hidden" id="captainId-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" name="captainId-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" value="<?php echo esc_html( $event_team->captain_id ); ?>" />
										<label for="captain-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>"><?php esc_html_e( 'Captain', 'racketmanager' ); ?></label>
										<div id="captain-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>-feedback" class="invalid-feedback"></div>
									</div>
								</div>
								<div class="row g-3">
									<div class="col-sm-6 mb-3">
										<div class="form-floating">
											<input type="tel" class="form-control" id="contactno-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" name="contactno-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" value="<?php echo esc_html( $event_team->contactno ); ?>" />
											<label for="contactno-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>"><?php esc_html_e( 'Contact Number', 'racketmanager' ); ?></label>
											<div id="contactno-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>-feedback" class="invalid-feedback"></div>
										</div>
									</div>
									<div class="col-sm-6 mb-3">
										<div class="form-floating">
											<input type="email" class="form-control" id="contactemail-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" name="contactemail-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" value="<?php echo esc_html( $event_team->contactemail ); ?>" size="30" />
											<label for="contactemail-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>"><?php esc_html_e( 'Contact Email', 'racketmanager' ); ?></label>
											<div id="contactemail-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>-feedback" class="invalid-feedback"></div>
										</div>
									</div>
								</div>
							</fieldset>
							<fieldset class="form-control mb-3">
								<legend><?php esc_html_e( 'Match times', 'racketmanager' ); ?></legend>
								<div class="row g-3">
									<div class="col-sm-6 mb-3">
										<div class="form-floating">
											<select class="form-select" size="1" name="matchday-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" id="matchday-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" >
												<option><?php esc_html_e( 'Select match day', 'racketmanager' ); ?></option>
												<?php
												foreach ( $matchdays as $key => $matchday ) {
													?>
													<option value="<?php echo esc_html( $key ); ?>" <?php selected( $matchday, empty( $event_team->match_day ) ? null : $event_team->match_day ); ?>><?php echo esc_html( $matchday ); ?></option>
													<?php
												}
												?>
											</select>
											<div id="matchday-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>-feedback" class="invalid-feedback"></div>
											<label for="matchday-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>"><?php esc_html_e( 'Match Day', 'racketmanager' ); ?></label>
										</div>
									</div>
									<div class="col-sm-6 mb-3">
										<div class="form-floating">
											<input type="time" class="form-control" id="matchtime-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" name="matchtime-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" value="<?php echo esc_html( $event_team->match_time ); ?>" size="30" />
											<label for="matchtime-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>"><?php esc_html_e( 'Match Time', 'racketmanager' ); ?></label>
											<div id="matchtime-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>-feedback" class="invalid-feedback"></div>
										</div>
									</div>
								</div>
							</fieldset>
						</div>
						<div class="modal-footer">
                            <button type="button" class="btn btn-plain" data-bs-dismiss="modal"><?php esc_html_e( 'Cancel', 'racketmanager' ); ?></button>
                            <button class="btn btn-primary" type="button" id="teamUpdateSubmit-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" name="teamUpdateSubmit-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" onclick="Racketmanager.updateTeam(this)">
                                <?php esc_html_e( 'Update details', 'racketmanager' ); ?>
                            </button>
                        </div>
					</form>
				</div>
			</div>
			<?php
			$output = ob_get_contents();
			ob_end_clean();
		} else {
			$output = $this->modal_error( $return->msg );
			if ( ! empty( $return->status ) ) {
				status_header( $return->status );
			}
		}
		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		wp_die();
	}
	/**
	 * Get event team matches dropdown
	 *
	 * @return void
	 */
	#[NoReturn] public function get_event_team_match_dropdown(): void {
        $team    = null;
        $event   = null;
		$return  = $this->check_security_token();
		if ( ! isset( $return->error ) ) {
			$team_id  = isset( $_POST['teamId'] ) ? intval( $_POST['teamId'] ) : null;
			$event_id = isset( $_POST['eventId'] ) ? intval( $_POST['eventId'] ) : null;
			if ( $team_id ) {
				$team = get_team( $team_id );
				if ( ! $team ) {
					$return->error = true;
					$return->msg   = __( 'Team not found', 'racketmanager' );
				}
			} else {
				$return->error = true;
				$return->msg   = __( 'Team id not supplied', 'racketmanager' );
			}
			if ( $event_id ) {
				$event = get_event( $event_id );
				if ( ! $event ) {
					$return->error = true;
					$return->msg   = __( 'Event not found', 'racketmanager' );
				}
			} else {
				$return->error = true;
				$return->msg   = __( 'Event id not supplied', 'racketmanager' );
			}
		}
		if ( ! isset( $return->error ) ) {
			$match_args = array();
			$match_args['season']  = $event->current_season['name'];
			$match_args['team_id'] = $team->id;
			$match_args['pending'] = true;
			$matches               = $event->get_matches( $match_args );
			ob_start();
			?>
			<select class="form-select" size="1" name="matchId" id="matchId" onChange="Racketmanager.show_set_team_button()">
				<option value="" disabled selected><?php esc_html_e( 'Select match', 'racketmanager' ); ?></option>
				<?php
				foreach ( $matches as $match ) {
					?>
					<option value="<?php echo esc_attr( $match->id ); ?>"><?php echo esc_html( $match->match_title ); ?></option>
					<?php
				}
				?>
			</select>
			<label for="matchId"><?php esc_html_e( 'Match', 'racketmanager' ); ?></label>
			<?php
			$output = ob_get_contents();
			ob_end_clean();
		} else {
			$shortcode = new RacketManager_Shortcodes();
			$output    = $shortcode->return_error( $return->msg );
		}
		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		wp_die();
	}
}
