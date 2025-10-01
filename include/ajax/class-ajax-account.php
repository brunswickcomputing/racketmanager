<?php
/**
 * AJAX Front end account response methods

 * @package    RacketManager
 * @subpackage RacketManager_Ajax_Account
 */

namespace Racketmanager\ajax;

use Racketmanager\Util;
use function Racketmanager\get_message;
use function Racketmanager\get_user;
use function Racketmanager\show_alert;

/**
 * Implement AJAX front end account responses.
 *
 * @author Paul Moffat
 */
class Ajax_Account extends Ajax {
    /**
     * Register ajax actions.
     */
    public function __construct() {
        parent::__construct();
        add_action( 'wp_ajax_racketmanager_add_favourite', array( &$this, 'add_favourite' ) );
        add_action( 'wp_ajax_racketmanager_get_message', array( &$this, 'get_message' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_get_message', array( &$this, 'logged_out' ) );
        add_action( 'wp_ajax_racketmanager_delete_message', array( &$this, 'delete_message' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_delete_message', array( &$this, 'logged_out' ) );
        add_action( 'wp_ajax_racketmanager_delete_messages', array( &$this, 'delete_messages' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_deletes_message', array( &$this, 'logged_out' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_reset_password', array( &$this, 'reset_password' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_login', array( &$this, 'login' ) );
        add_action( 'wp_ajax_racketmanager_update_account', array( &$this, 'update_account' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_update_account', array( &$this, 'login' ) );
    }
    /**
     * Login function
     *
     * @return void
     */
    public function login(): void {
        $return = $this->check_security_token();
        if ( empty( $return->error ) ) {
            $info                  = array();
            $info['user_login']    = isset( $_POST['log'] ) ? sanitize_text_field( wp_unslash( $_POST['log'] ) ) : null;
            $info['user_password'] = isset( $_POST['pwd'] ) ? sanitize_text_field( wp_unslash( $_POST['pwd'] ) ) : null;
            $info['remember']      = true;
            $user                  = wp_signon( $info, true );
            if ( is_wp_error( $user ) ) {
                foreach ( $user->errors as $field => $error ) {
                    $return->err_flds[] = Util::get_error_field( $field );
                    $return->err_msgs[] = Util::get_error_message( $field );
                }
                $return->error  = true;
                $return->status = 401;
            }
        } else {
            $return->status = 403;
        }
        if ( empty( $return->error ) ) {
            $redirect = isset( $_POST['redirect_to'] ) ? sanitize_url( $_POST['redirect_to'] ) : home_url();
            $redirect = wp_validate_redirect( $redirect, home_url() );
            wp_send_json_success( $redirect );
        } else {
            $return->msg = __( 'Login failed', 'racketmanager' );
            wp_send_json_error( $return, $return->status );
        }
    }
    /**
     * Add item as favourite
     */
    public function add_favourite(): void {
        $return = $this->check_security_token();
        if ( empty( $return->error ) ) {
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
        if ( empty( $return->error ) ) {
            wp_send_json_success( $return );
        } else {
            wp_send_json_error( $return->msg, 500 );
        }
    }
    /**
     * Delete message
     */
    public function delete_message(): void {
        $return = $this->check_security_token();
        if ( empty( $return->error ) ) {
            $message_id = isset( $_POST['message_id'] ) ? intval( $_POST['message_id'] ) : 0;
            if ( ! $message_id ) {
                $return->error = true;
                $return->msg    = __( 'No message id found in request', 'racketmanager' );
                $return->status = 404;
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
                    $return->output  = show_alert( $alert_text, $alert_class );
                    $return->success = $success;
                    wp_send_json_success( $return );
                } else {
                    $return->error  = true;
                    $return->msg    = __( 'Message not found', 'racketmanager' );
                    $return->status = 404;
                }
            }
            wp_send_json_error( $return->msg, $return->status );
        }
    }
    /**
     * Delete messages
     */
    public function delete_messages(): void {
        $user         = null;
        $userid       = null;
        $message_type = null;
        $return = $this->check_security_token( 'racketmanager_nonce', 'racketmanager_delete-messages');
        if ( empty( $return->error ) ) {
            $message_type = isset( $_POST['message_type'] ) ? sanitize_text_field( wp_unslash( $_POST['message_type'] ) ) : null;
            if ( ! isset( $message_type ) ) {
                $return->error  = true;
                $return->msg    = __( 'You must select the type of messages to delete', 'racketmanager' );
                $return->status = 401;
            }
        }
        if ( empty( $return->error ) ) {
            $userid = get_current_user_id();
            if ( ! $userid ) {
                $return->error  = true;
                $return->msg    = __( 'Userid not found', 'racketmanager' );
                $return->status = 404;
            }
        }
        if ( empty( $return->error ) ) {
            $user = get_user( $userid );
            if ( ! $user ) {
                $return->error  = true;
                $return->msg    = __( 'User not found', 'racketmanager' );
                $return->status = 404;
            }
        }
        if ( ! empty( $return->error ) ) {
            wp_send_json_error( $return, $return->status );
        }
        $message_type_name = Util::get_message_type( $message_type );
        $success           = $user->delete_messages( $message_type );
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
        $return->output  = show_alert( $alert_text, $alert_class );
        $return->success = $success;
        $return->type    = $message_type_name;
        wp_send_json_success( $return );
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
     * Update account
     *
     * @return void
     */
    public function update_account(): void {
        $return    = $this->check_security_token( 'racketmanager_nonce', 'member_account' );
        if ( empty( $return->error ) ) {
            $current_user = wp_get_current_user();
            $user         = get_user( $current_user->ID );
            $user_update  = $this->get_updated_user_details( $user );
            $user         = $user->update( $user_update );
            $return->msg  = $user->message;
            if ( empty( $user->err_flds ) ) {
                $return->class = $user->update_result;
            } else {
                $return->err_flds = $user->err_flds;
                $return->err_msgs = $user->err_msgs;
                $return->error = true;
                $return->status = 401;
            }
        }
        if ( empty( $return->error ) ) {
            wp_send_json_success( $return );
        } else {
            wp_send_json_error( $return, $return->status );
        }
    }

    /**
     * Get updated user details from request
     *
     * @param object $original_user
     *
     * @return object
     */
    private function get_updated_user_details( object $original_user ): object {
        $user                = clone $original_user;
        $user->email         = isset( $_POST['username'] ) ? sanitize_email( wp_unslash( $_POST['username'] ) ) : null;
        $user->firstname     = isset( $_POST['firstname'] ) ? sanitize_text_field( wp_unslash( $_POST['firstname'] ) ) : null;
        $user->surname       = isset( $_POST['lastname'] ) ? sanitize_text_field( wp_unslash( $_POST['lastname'] ) ) : null;
        $user->contactno     = isset( $_POST['contactno'] ) ? sanitize_text_field( wp_unslash( $_POST['contactno'] ) ) : null;
        $user->gender        = isset( $_POST['gender'] ) ? sanitize_text_field( wp_unslash( $_POST['gender'] ) ) : null;
        $user->btm           = empty( $_POST['btm'] ) ? null : intval( $_POST['btm'] );
        $user->year_of_birth = empty( $_POST['year_of_birth'] ) ? null : intval( $_POST['year_of_birth'] );
        $user->password      = isset( $_POST['password'] ) ? sanitize_text_field( wp_unslash( $_POST['password'] ) ) : null;
        $user->re_password   = isset( $_POST['rePassword'] ) ? sanitize_text_field( wp_unslash( $_POST['rePassword'] ) ) : null;
        $user->opt_ins       = isset( $_POST['opt_in'] ) ? wp_unslash( $_POST['opt_in'] ) : array();
        return $user;
    }
}
