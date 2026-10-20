<?php

namespace Racketmanager\Infrastructure\Wordpress\Ajax;

use Racketmanager\Application\Account\Commands\Add_Favourite_Command;
use Racketmanager\Application\Account\Commands\Delete_Messages_Command;
use Racketmanager\Application\Account\Commands\Login_Command;
use Racketmanager\Application\Account\Commands\Reset_Password_Command;
use Racketmanager\Application\Account\Commands\Update_Account_Command;
use Racketmanager\Infrastructure\Security\Security_Service_Interface;
use Racketmanager\Infrastructure\Wordpress\Response\Json_Response_Factory_Interface;
use Racketmanager\Infrastructure\Wordpress\Response\Response;
use Racketmanager\Repositories\Interfaces\Message_Repository_Interface;
use Racketmanager\Repositories\Interfaces\User_Repository_Interface;
use Racketmanager\Repositories\Message_Repository;
use Racketmanager\Repositories\User_Repository;
use Racketmanager\Util\Util;
use stdClass;
use function Racketmanager\get_message;
use function Racketmanager\get_user;
use function Racketmanager\show_alert;
use function Racketmanager\show_message;

/**
 * Adapter for Account AJAX requests.
 */
class Account_Ajax_Adapter {
    private const NONCE_FIELD = 'racketmanager_nonce';
    private const DEFAULT_NONCE_FIELD = 'security';
    private const DEFAULT_NONCE_ACTION = 'ajax-nonce';

    public function __construct(
        private Security_Service_Interface $security_service,
        private Json_Response_Factory_Interface $response_factory,
        private ?Message_Repository_Interface $message_repository = null,
        private ?User_Repository_Interface $user_repository = null
    ) {
        $this->message_repository = $this->message_repository ?? new Message_Repository();
        $this->user_repository    = $this->user_repository ?? new User_Repository();
    }

    public function logged_out(): Response {
        $msg = __( 'Must be logged in to access this feature', 'racketmanager' );

        return $this->response_factory->create_error_response(
            array(
                $msg,
                array(),
                array(),
            ),
            401
        );
    }

    public function login( array $request = [] ): Response {
        $details = $this->verify_nonce_details( self::DEFAULT_NONCE_FIELD, self::DEFAULT_NONCE_ACTION, $request );
        if ( $details !== null ) {
            $details->status = 403;
            $details->msg    = __( 'Login failed', 'racketmanager' );

            return $this->response_factory->create_error_response( $details, 403 );
        }

        $command = Login_Command::from_post( $request );
        $info    = array(
            'user_login'    => $command->user_login,
            'user_password' => $command->user_password,
            'remember'      => true,
        );
        $user = wp_signon( $info, true );

        if ( is_wp_error( $user ) ) {
            $details           = new stdClass();
            $details->error    = true;
            $details->err_flds = array();
            $details->err_msgs = array();
            foreach ( $user->errors as $field => $error ) {
                $details->err_flds[] = Util::get_error_field( $field );
                $details->err_msgs[] = Util::get_error_message( $field );
            }
            $details->status = 401;
            $details->msg    = __( 'Login failed', 'racketmanager' );

            return $this->response_factory->create_error_response( $details, 401 );
        }

        $redirect = $command->redirect_to ?: home_url();
        $redirect = wp_validate_redirect( $redirect, home_url() );

        return $this->response_factory->create_success_response( $redirect );
    }

    public function add_favourite( array $request = [] ): Response {
        $security_error = $this->nonce_failure_response( self::DEFAULT_NONCE_FIELD, self::DEFAULT_NONCE_ACTION, null, $request );
        if ( $security_error ) {
            return $this->response_factory->create_error_response( $security_error->get_content()->msg, 500 );
        }

        $userid          = get_current_user_id();
        $command         = Add_Favourite_Command::from_post( $request, $userid );
        $meta_key        = 'favourite-' . $command->type;
        $meta            = get_user_meta( $userid, $meta_key );
        $favourite_found = array_search( strval( $command->id ), $meta, true );

        $validator         = new stdClass();
        $validator->error  = false;
        $validator->status = 200;
        if ( ! is_numeric( $favourite_found ) ) {
            add_user_meta( $userid, $meta_key, $command->id );
            $validator->msg    = __( 'Favourite added', 'racketmanager' );
            $validator->action = 'add';
        } else {
            delete_user_meta( $userid, $meta_key, $command->id );
            $validator->msg    = __( 'Favourite removed', 'racketmanager' );
            $validator->action = 'del';
        }

        return $this->response_factory->create_success_response( $validator );
    }

    public function get_user_message( array $request = [] ): Response {
        $details = $this->verify_nonce_details( self::DEFAULT_NONCE_FIELD, self::DEFAULT_NONCE_ACTION, $request );
        if ( $details !== null ) {
            return $this->response_factory->create_error_response( $details->msg, (int) $details->status );
        }

        $message_id        = isset( $request['message_id'] ) ? (int) $request['message_id'] : 0;
        $message_dtl       = get_message( $message_id );
        $validator         = new stdClass();
        $validator->status = $message_dtl?->status;
        $validator->output = show_message( $message_id );

        return $this->response_factory->create_success_response( $validator );
    }

    public function delete_message( array $request = [] ): Response {
        $details = $this->verify_nonce_details( self::DEFAULT_NONCE_FIELD, self::DEFAULT_NONCE_ACTION, $request );
        if ( $details !== null ) {
            return $this->response_factory->create_error_response( $details->msg, (int) $details->status );
        }

        $message_id = isset( $request['message_id'] ) ? (int) $request['message_id'] : 0;
        if ( ! $message_id ) {
            return $this->response_factory->create_error_response( __( 'No message id found in request', 'racketmanager' ), 404 );
        }

        $message_dtl = $this->message_repository->find( $message_id );
        if ( ! $message_dtl ) {
            return $this->response_factory->create_error_response( __( 'Message not found', 'racketmanager' ), 404 );
        }

        $success            = $this->message_repository->delete( $message_id );
        $alert_class        = $success ? 'success' : 'danger';
        $alert_text         = $success ? __( 'Message deleted', 'racketmanager' ) : __( 'Unable to delete message', 'racketmanager' );
        $validator          = new stdClass();
        $validator->output  = show_alert( $alert_text, $alert_class );
        $validator->success = $success;

        return $this->response_factory->create_success_response( $validator );
    }

    public function delete_messages( array $request = [] ): Response {
        $details = $this->verify_nonce_details( self::NONCE_FIELD, 'racketmanager_delete-messages', $request );
        if ( $details !== null ) {
            return $this->response_factory->create_error_response( $details, (int) $details->status );
        }

        $userid  = get_current_user_id();
        $command = Delete_Messages_Command::from_post( $request, $userid );

        if ( ! isset( $command->message_type ) ) {
            $details           = new stdClass();
            $details->error    = true;
            $details->msg      = __( 'You must select the type of messages to delete', 'racketmanager' );
            $details->status   = 401;

            return $this->response_factory->create_error_response( $details, 401 );
        }

        if ( ! $userid ) {
            $details           = new stdClass();
            $details->error    = true;
            $details->msg      = __( 'Userid not found', 'racketmanager' );
            $details->status   = 404;

            return $this->response_factory->create_error_response( $details, 404 );
        }

        $user = $this->user_repository->find( $userid );
        if ( ! $user ) {
            $details           = new stdClass();
            $details->error    = true;
            $details->msg      = __( 'User not found', 'racketmanager' );
            $details->status   = 404;

            return $this->response_factory->create_error_response( $details, 404 );
        }

        $message_type_name  = is_numeric( $command->message_type ) ? Util::get_message_type( (int) $command->message_type ) : (string) $command->message_type;
        $success            = $this->message_repository->delete_by_user_and_type( (int) $user->id, (string) $command->message_type );
        $alert_class        = $success ? 'success' : ( 0 === $success ? 'warning' : 'danger' );
        $alert_text         = $success ? __( 'Messages deleted', 'racketmanager' ) : ( 0 === $success ? __( 'No messages to delete', 'racketmanager' ) : __( 'Unable to delete messages', 'racketmanager' ) );
        $validator          = new stdClass();
        $validator->output  = show_alert( $alert_text, $alert_class );
        $validator->success = $success;
        $validator->type    = $message_type_name;

        return $this->response_factory->create_success_response( $validator );
    }

    public function reset_password( array $request = [] ): Response {
        $details = $this->verify_nonce_details( self::NONCE_FIELD, 'reset_password', $request );
        if ( $details !== null ) {
            $return = array(
                __( 'Unable to request password reset', 'racketmanager' ),
                array( __( 'Form has expired. Please refresh the page and resubmit', 'racketmanager' ) ),
                array( '' ),
            );

            return $this->response_factory->create_error_response( $return, 500 );
        }

        $command = Reset_Password_Command::from_post( $request );
        if ( ! $command->user_login ) {
            $return = array(
                __( 'Unable to request password reset', 'racketmanager' ),
                array( __( 'Email address not supplied', 'racketmanager' ) ),
                array( 'user_login' ),
            );

            return $this->response_factory->create_error_response( $return, 500 );
        }

        $reset = retrieve_password( $command->user_login );
        if ( is_wp_error( $reset ) ) {
            $return = array(
                __( 'Unable to request password reset', 'racketmanager' ),
                array( $reset->get_error_message() ),
                array( 'user_login' ),
            );

            return $this->response_factory->create_error_response( $return, 500 );
        }

        $return = array( __( 'Check your email for a link to reset your password', 'racketmanager' ) );

        return $this->response_factory->create_success_response( $return );
    }

    public function update_account( array $request = [] ): Response {
        $details = $this->verify_nonce_details( self::NONCE_FIELD, 'member_account', $request );
        if ( $details !== null ) {
            return $this->response_factory->create_error_response( $details, (int) $details->status );
        }

        $current_user = wp_get_current_user();
        $user         = $this->user_repository->find( $current_user->ID );
        if ( ! $user ) {
            $details           = new stdClass();
            $details->error    = true;
            $details->msg      = __( 'User not found', 'racketmanager' );
            $details->status   = 404;

            return $this->response_factory->create_error_response( $details, 404 );
        }

        $command = Update_Account_Command::from_post( $request );

        $valid   = true;
        $err_fld = array();
        $err_msg = array();
        if ( empty( $command->username ) ) {
            $valid     = false;
            $err_fld[] = 'username';
            $err_msg[] = Util::get_error_message( 'empty_username' );
        }
        if ( empty( $command->firstname ) ) {
            $valid     = false;
            $err_fld[] = 'firstname';
            $err_msg[] = Util::get_error_message( 'firstname_field_empty' );
        }
        if ( empty( $command->lastname ) ) {
            $valid     = false;
            $err_fld[] = 'lastname';
            $err_msg[] = Util::get_error_message( 'lastname_field_empty' );
        }
        if ( empty( $command->gender ) ) {
            $valid     = false;
            $err_fld[] = 'gender';
            $err_msg[] = Util::get_error_message( 'gender_field_empty' );
        }
        if ( empty( $command->btm ) ) {
            global $racketmanager;
            $player_options = isset( $racketmanager ) ? $racketmanager->get_options( 'rosters' ) : [];
            if ( isset( $player_options['btm'] ) && '1' === $player_options['btm'] ) {
                $valid     = false;
                $err_fld[] = 'btm';
                $err_msg[] = Util::get_error_message( 'btm_field_empty' );
            }
        }
        if ( $command->password !== $command->re_password ) {
            $valid     = false;
            $err_fld[] = 'password';
            $err_msg[] = Util::get_error_message( 'password_reset_mismatch' );
        }

        $validator = new stdClass();

        if ( ! $valid ) {
            $validator->err_flds = $err_fld;
            $validator->err_msgs = $err_msg;
            $validator->error    = true;
            $validator->status   = 401;
            $validator->msg      = __( 'Errors found', 'racketmanager' );

            return $this->response_factory->create_error_response( $validator, 401 );
        }

        $user->email         = $command->username ?? '';
        $user->user_email    = $command->username ?? '';
        $user->firstname     = $command->firstname ?? '';
        $user->surname       = $command->lastname ?? '';
        $user->contactno     = $command->contactno;
        $user->gender        = $command->gender;
        $user->btm           = $command->btm;
        $user->year_of_birth = $command->year_of_birth;
        $user->password      = (string) ( $command->password ?? '' );
        $user->re_password   = (string) ( $command->re_password ?? '' );
        $user->opt_ins       = $this->normalize_opt_ins( $command->opt_ins );

        $saved = $this->user_repository->save( $user );

        $validator->msg   = $saved ? __( 'User updated', 'racketmanager' ) : __( 'No updates', 'racketmanager' );
        $validator->class = $saved ? 'success' : 'warning';

        return $this->response_factory->create_success_response( $validator );
    }

    /**
     * @param mixed $opt_ins
     * @return array<int, string>
     */
    private function normalize_opt_ins( mixed $opt_ins ): array {
        if ( ! is_array( $opt_ins ) || [] === $opt_ins ) {
            return array();
        }
        $choices = array_map( 'strval', array_keys( Util::get_email_opt_ins() ) );
        $normalized = array();
        if ( array_is_list( $opt_ins ) ) {
            foreach ( $opt_ins as $value ) {
                $value = (string) $value;
                if ( in_array( $value, $choices, true ) ) {
                    $normalized[] = $value;
                }
            }
            return array_values( array_unique( $normalized ) );
        }
        foreach ( $choices as $choice ) {
            if ( ! empty( $opt_ins[ $choice ] ) ) {
                $normalized[] = $choice;
            }
        }
        return $normalized;
    }

    private function nonce_failure_response( string $field, string $action, ?string $override_msg = null, array $request = [] ): ?Response {
        $details = $this->verify_nonce_details( $field, $action, $request );
        if ( $details === null ) {
            return null;
        }
        if ( $override_msg !== null ) {
            $details->msg = $override_msg;
        }

        return $this->response_factory->create_error_response( $details, $details->status );
    }

    private function verify_nonce_details( string $field, string $action, array $request = [] ): ?stdClass {
        $nonce_set = isset( $request[ $field ] );
        $nonce     = $request[ $field ] ?? '';
        $nonce     = is_string( $nonce ) ? sanitize_text_field( wp_unslash( $nonce ) ) : '';
        if ( $nonce !== '' && $this->security_service->verify_nonce( $nonce, $action ) ) {
            return null;
        }

        $details           = new stdClass();
        $details->error    = true;
        $details->err_flds = array();
        $details->err_msgs = array();
        $details->status   = 403;
        $details->msg      = ! $nonce_set
            ? __( 'There was a problem with your request. Please try again or refresh the page', 'racketmanager' )
            : __( 'Sorry, the action could not be completed. The link or form you are using has expired or is invalid. Please try again, or go back to the previous page and try again', 'racketmanager' );

        return $details;
    }
}
