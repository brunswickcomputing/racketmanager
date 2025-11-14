<?php
/**
 * AJAX Club response methods (PSR-4 relocated)
 *
 * @package    RacketManager
 * @subpackage RacketManager_Ajax_Club
 */

namespace Racketmanager\Ajax;

use JetBrains\PhpStorm\NoReturn;
use Racketmanager\Services\Validator\Validator_Club;
use stdClass;
use function Racketmanager\get_club;
use function Racketmanager\get_club_player;
use function Racketmanager\get_club_role;
use function Racketmanager\get_user;
use function Racketmanager\show_alert;
use function Racketmanager\show_club_role_modal;

/**
 * Implement AJAX front end responses.
 *
 * @author Paul Moffat
 */
class Ajax_Club extends Ajax {
    /**
     * Register ajax actions.
     */
    public function __construct() {
        parent::__construct();
        add_action( 'wp_ajax_racketmanager_update_club', array( &$this, 'update_club' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_update_club', array( &$this, 'logged_out' ) );
        add_action( 'wp_ajax_racketmanager_club_player_request', array( &$this, 'club_player_request' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_club_player_request', array( &$this, 'logged_out' ) );
        add_action( 'wp_ajax_racketmanager_club_players_remove', array( &$this, 'club_player_remove' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_club_players_remove', array( &$this, 'logged_out' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_club_role_modal', array( &$this, 'logged_out_modal' ) );
        add_action( 'wp_ajax_racketmanager_club_role_modal', array( &$this, 'show_club_role_modal' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_set_club_role', array( &$this, 'logged_out_modal' ) );
        add_action( 'wp_ajax_racketmanager_set_club_role', array( &$this, 'set_club_role' ) );
    }
    /**
     * Update Club
     *
     * @see templates/club.php
     */
    public function update_club(): void {
        $club_id         = null;
        $address         = null;
        $contactno       = null;
        $facilities      = null;
        $founded         = null;
        $website         = null;
        $match_secretary = null;
        $validator       = new Validator_Club();
        $validator       = $validator->check_security_token( 'racketmanager_nonce', 'club-update' );
        if ( empty( $validator->error ) ) {
            $club_id                    = isset( $_POST['club_id'] ) ? intval( $_POST['club_id'] ) : null;
            $contactno                  = isset( $_POST['clubContactNo'] ) ? sanitize_text_field( wp_unslash( $_POST['clubContactNo'] ) ) : null;
            $facilities                 = isset( $_POST['facilities'] ) ? sanitize_text_field( wp_unslash( $_POST['facilities'] ) ) : null;
            $founded                    = isset( $_POST['founded'] ) ? intval( $_POST['founded'] ) : null;
            $match_secretary            = new stdClass();
            $match_secretary->id        = isset( $_POST['matchSecretaryId'] ) ? intval( $_POST['matchSecretaryId'] ) : null;
            $match_secretary->contactno = isset( $_POST['matchSecretaryContactNo'] ) ? sanitize_text_field( wp_unslash( $_POST['matchSecretaryContactNo'] ) ) : null;
            $match_secretary->email     = isset( $_POST['matchSecretaryEmail'] ) ? sanitize_text_field( wp_unslash( $_POST['matchSecretaryEmail'] ) ) : null;
            $website                    = isset( $_POST['website'] ) ? sanitize_text_field( wp_unslash( $_POST['website'] ) ) : null;
            $address                    = isset( $_POST['address'] ) ? sanitize_text_field( wp_unslash( $_POST['address'] ) ) : null;
            // validate inputs.
            $validator = $validator->club( $club_id );
            $validator = $validator->address( $address );
            $validator = $validator->match_secretary( $match_secretary->id, 'matchSecretaryName' );
            $validator = $validator->telephone( $match_secretary->contactno, 'matchSecretaryContactNo', true );
            $validator = $validator->email( $match_secretary->email, $match_secretary->id, true, 'matchSecretaryEmail', true );
        }
        if ( empty( $validator->error ) ) {
            $club                          = get_club( $club_id );
            $club_updated                  = clone $club;
            $club_updated->contactno       = $contactno;
            $club_updated->facilities      = $facilities;
            $club_updated->founded         = $founded;
            $club_updated->website         = $website;
            $club_updated->address         = $address;
            $club_updated->match_secretary = $match_secretary;
            $club_updated                  = $this->club_service->update_club( $club->id, $club_updated );
            if ( $club_updated ) {
                $return['msg'] = __( 'Club updated', 'racketmanager' );
                $return['status'] = 'success';
            } else {
                $return['msg']    = __( 'Nothing to update', 'racketmanager' );
                $return['status'] = 'warning';
            }
            wp_send_json_success( $return );
        } else {
            $return      = $validator->get_details();
            $return->msg = __( 'Error in club update', 'racketmanager' );
            wp_send_json_error( $return, $return->status );
        }
    }
    /**
     * Save club player requests
     *
     * @see templates/club.php
     */
    public function club_player_request(): void {
        global $racketmanager;
        $validator = new Validator_Club();
        $validator = $validator->check_security_token( 'racketmanager_nonce', 'club-player-request' );
        if ( empty( $validator->error ) ) {
            $player_valid = $racketmanager->validate_player();
            if ( $player_valid[0] ) {
                $new_player = $player_valid[1];
                $club_id    = isset( $_POST['club'] ) ? intval( $_POST['club'] ) : null;
                $validator  = $validator->club( $club_id, 'surname' );
                if ( empty( $validator->error ) ) {
                    $club     = get_club( intval( $_POST['club'] ) );
                    $register = $club->register_player( $new_player );
                    if ( ! empty( $register->error ) ) {
                        $validator->error  = true;
                        $validator->status = 401;
                    }
                    $validator->msg = $register->msg;
                }
            } else {
                $validator->error    = true;
                $validator->status   = 401;
                $validator->err_flds = $player_valid[1];
                $validator->err_msgs = $player_valid[2];
                $validator->msg      = __( 'Error in player registration', 'racketmanager' );
            }
        }
        if ( empty( $validator->error ) ) {
            wp_send_json_success( $validator );
        } else {
            $return = $validator->get_details();
            wp_send_json_error( $return, $return->status );
        }
    }

    /**
     * Remove player from roster
     *
     * @see templates/club.php
     */
    public function club_player_remove(): void {
        $deleted   = 0;
        $validator = new Validator_Club();
        $validator = $validator->check_security_token( 'racketmanager_nonce', 'club-player-remove' );
        if ( empty( $validator->error ) ) {
            //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
            $club_players = empty( $_POST['clubPlayer'] ) ? array() : $_POST['clubPlayer'];
            foreach ( $club_players as $club_player_id ) {
                $club_player = get_club_player( $club_player_id );
                if ( $club_player ) {
                    $club_player->remove();
                    ++$deleted;
                }
            }
        }
        if ( empty( $validator->error ) ) {
            if ( $deleted ) {
                $msg = _n( 'Player removed', 'Players removed', $deleted, 'racketmanager' );
            } else {
                $msg = __( 'No players selected for removal', 'racketmanager' );
            }
            wp_send_json_success( $msg );
        } else {
            $return = $validator->get_details();
            wp_send_json_error( $return, $return->status );
        }
    }

    /**
     * Load club role modal
     */
    #[NoReturn]
    public function show_club_role_modal(): void {
        $return = $this->check_security_token();
        if ( empty( $return->error ) ) {
            $club_role_id = isset( $_POST['clubRoleId'] ) ? intval( $_POST['clubRoleId'] ) : null;
            $modal      = isset( $_POST['modal'] ) ? sanitize_text_field( wp_unslash( $_POST['modal'] ) ) : null;
            $output     = show_club_role_modal( $club_role_id, array( 'modal' => $modal ) );
        } else {
            $output = show_alert( $return->msg, 'danger', 'modal' );
            if ( ! empty( $return->status ) ) {
                status_header( $return->status );
            }
        }
        echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        wp_die();
    }

    /**
     * Set club role
     */
    public function set_club_role(): void {
        $user_id      = null;
        $club_role_id = null;
        $contact_no   = null;
        $email        = null;
        $updates      = false;
        $validator      = new Validator_Club();
        $validator      = $validator->check_security_token( 'racketmanager_nonce', 'club-role-update' );
        if ( empty( $validator->error ) ) {
            $club_id      = isset( $_POST['clubId'] ) ? intval( $_POST['clubId'] ) : null;
            $role_id      = isset( $_POST['roleId'] ) ? intval( $_POST['roleId'] ) : null;
            $user_id      = isset( $_POST['userId'] ) ? intval( $_POST['userId'] ) : null;
            $club_role_id = isset( $_POST['clubRoleId'] ) ? intval( $_POST['clubRoleId'] ) : null;
            $contact_no   = isset( $_POST['contactno'] ) ? sanitize_text_field( wp_unslash( $_POST['contactno'] ) ) : null;
            $email        = isset( $_POST['contactemail'] ) ? sanitize_text_field( wp_unslash( $_POST['contactemail'] ) ) : null;
            $validator    = $validator->club( $club_id );
            $validator    = $validator->club_role( $club_role_id );
            $validator    = $validator->user( $user_id );
            $validator    = $validator->telephone( $contact_no, 'contactno', true );
            $validator    = $validator->email( $email, $user_id, true, 'contactemail', true );
        }
        if ( empty( $validator->error ) ) {
            $user      = get_user( $user_id );
            $club_role = get_club_role( $club_role_id );
            if ( $club_role->user->id !== $user_id ) {
                $club_role = $this->club_service->reassign_role_user( $club_role_id, $user_id );
                if ( $club_role ) {
                    $updates = true;
                }
            }
            $user_updates = $user->update_contact( $contact_no, $email );
            if ( $user_updates ) {
                $updates = true;
            }
            if ( $updates ) {
                $msg = __( 'Club role updated', 'racketmanager' );
                $status = 'success';
            } else {
                $msg = __( 'No updates', 'racketmanager' );
                $status = 'warning';
            }
            $return         = new stdClass();
            $return->msg    = $msg;
            $return->status = $status;
            wp_send_json_success( $return );
        }
        $return = $validator->get_details();
        if ( empty( $return->msg ) ) {
            $return->msg = __( 'Unable to set club role', 'racketmanager' );
        }
        wp_send_json_error( $return, $return->status );
    }
}
