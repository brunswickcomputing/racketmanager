<?php

namespace Racketmanager\Infrastructure\Wordpress\Ajax;

use Racketmanager\Domain\DTO\Club\Club_Player_Request_Command;
use Racketmanager\Domain\DTO\Club\Club_Players_Remove_Command;
use Racketmanager\Domain\DTO\Club\Set_Club_Role_Command;
use Racketmanager\Domain\DTO\Club\Update_Club_Command;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Already_Registered_Exception;
use Racketmanager\Exceptions\Registration_Not_Found_Exception;
use Racketmanager\Infrastructure\Security\Security_Service_Interface;
use Racketmanager\Infrastructure\Wordpress\Response\Json_Response_Factory_Interface;
use Racketmanager\Infrastructure\Wordpress\Response\Response;
use Racketmanager\Repositories\Interfaces\User_Repository_Interface;
use Racketmanager\Repositories\User_Repository;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Validator\Validator_Club;
use stdClass;
use function Racketmanager\get_club_role;
use function Racketmanager\show_alert;
use function Racketmanager\show_club_role_modal;

/**
 * Adapter for Club AJAX requests.
 */
class Club_Ajax_Adapter {
    private const NONCE_FIELD = 'racketmanager_nonce';
    private const DEFAULT_NONCE_FIELD = 'security';
    private const DEFAULT_NONCE_ACTION = 'ajax-nonce';

    public function __construct(
        private Security_Service_Interface $security_service,
        private Json_Response_Factory_Interface $response_factory,
        private Club_Service $club_service,
        private Registration_Service $registration_service,
        private ?User_Repository_Interface $user_repository = null
    ) {
        $this->user_repository = $this->user_repository ?? new User_Repository();
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

    public function logged_out_modal(): Response {
        $msg    = __( 'Must be logged in to access this feature', 'racketmanager' );
        $output = show_alert( $msg, 'danger', 'modal' );

        return $this->response_factory->create_error_response(
            array(
                $msg,
                $output,
            ),
            401
        );
    }

    public function update_club( array $request = [] ): Response {
        $security_error = $this->nonce_failure_response( self::NONCE_FIELD, 'club-update', __( 'Serious error in club update', 'racketmanager' ), $request );
        if ( $security_error ) {
            return $security_error;
        }

        $command   = Update_Club_Command::from_post( $request );
        $validator = new Validator_Club();
        $validator = $validator->club( $command->club_id !== null ? (string) $command->club_id : null );
        $validator = $validator->address( $command->address );
        $validator = $validator->telephone( $command->match_secretary_contact_no, 'matchSecretaryContactNo', true );
        $validator = $validator->email( $command->match_secretary_email, $command->match_secretary_id, true, 'matchSecretaryEmail', true );
        $validator = $validator->match_secretary( $command->match_secretary_id, 'matchSecretaryName' );
        if ( ! empty( $validator->error ) ) {
            $return      = $validator->get_details();
            $return->msg = __( 'Error in club update', 'racketmanager' );

            return $this->response_factory->create_error_response( $return, $return->status );
        }

        $club                          = $this->club_service->get_club( $command->club_id );
        $club_updated                  = clone $club;
        $club_updated->contactno       = $command->contact_no;
        $club_updated->facilities      = $command->facilities;
        $club_updated->founded         = $command->founded;
        $club_updated->website         = $command->website;
        $club_updated->address         = $command->address;
        $club_updated->match_secretary = $command->match_secretary();
        $club_updated                  = $this->club_service->update_club( $club->id, $club_updated );

        if ( $club_updated ) {
            $return = array(
                'msg'    => __( 'Club updated', 'racketmanager' ),
                'status' => 'success',
            );
        } else {
            $return = array(
                'msg'    => __( 'Nothing to update', 'racketmanager' ),
                'status' => 'warning',
            );
        }

        return $this->response_factory->create_success_response( $return );
    }

    public function club_player_request( array $request = [] ): Response {
        $security_error = $this->nonce_failure_response( self::NONCE_FIELD, 'club-player-request', null, $request );
        if ( $security_error ) {
            return $security_error;
        }

        $command   = Club_Player_Request_Command::from_post( $request, (int) wp_get_current_user()->ID );
        $validator = new Validator_Club();
        try {
            $response = $this->registration_service->register_player_to_club( $command->club_id, $command->user_id, $request );
            if ( is_wp_error( $response ) ) {
                $validator->error    = true;
                $validator->status   = 401;
                $validator->err_flds = $response->get_error_codes();
                $validator->err_msgs = $response->get_error_messages();
                $validator->msg      = __( 'Error in player registration', 'racketmanager' );
            } else {
                $validator->msg = $response;
            }
        } catch ( Club_Not_Found_Exception | Player_Already_Registered_Exception | Registration_Not_Found_Exception $e ) {
            $validator->error  = true;
            $validator->status = 401;
            $validator->msg    = $e->getMessage();
        }

        if ( empty( $validator->error ) ) {
            return $this->response_factory->create_success_response( $validator );
        }

        $return = $validator->get_details();

        return $this->response_factory->create_error_response( $return, $return->status );
    }

    public function club_player_remove( array $request = [] ): Response {
        $security_error = $this->nonce_failure_response( self::NONCE_FIELD, 'club-player-remove', null, $request );
        if ( $security_error ) {
            return $security_error;
        }

        $command = Club_Players_Remove_Command::from_post( $request, (int) wp_get_current_user()->ID );
        $deleted = 0;
        foreach ( $command->club_player_ids as $club_player_id ) {
            $this->registration_service->remove_registration( $club_player_id, $command->user_id );
            ++$deleted;
        }

        if ( $deleted ) {
            $msg = _n( 'Player removed', 'Players removed', $deleted, 'racketmanager' );
        } else {
            $msg = __( 'No players selected for removal', 'racketmanager' );
        }

        return $this->response_factory->create_success_response( $msg );
    }

    public function show_club_role_modal( array $request = [] ): Response {
        $security_error = $this->nonce_failure_html_response( self::DEFAULT_NONCE_FIELD, self::DEFAULT_NONCE_ACTION, $request );
        if ( $security_error ) {
            return $security_error;
        }

        $club_role_id = isset( $request['clubRoleId'] ) ? intval( $request['clubRoleId'] ) : null;
        $modal        = isset( $request['modal'] ) ? sanitize_text_field( wp_unslash( $request['modal'] ) ) : null;
        $output       = show_club_role_modal( (int) $club_role_id, array( 'modal' => $modal ) );

        return $this->response_factory->create_raw_response( $output );
    }

    public function set_club_role( array $request = [] ): Response {
        $security_error = $this->nonce_failure_response( self::NONCE_FIELD, 'racketmanager_club-role-update', null, $request );
        if ( $security_error ) {
            return $security_error;
        }

        $command   = Set_Club_Role_Command::from_post( $request );
        $validator = new Validator_Club();
        $validator = $validator->club( $command->club_id !== null ? (string) $command->club_id : null );
        $validator = $validator->club_role( $command->club_role_id );
        $validator = $validator->user( $command->user_id );
        $validator = $validator->telephone( $command->contact_no, 'contactno', true );
        $validator = $validator->email( $command->email, $command->user_id, true, 'contactemail', true );
        if ( ! empty( $validator->error ) ) {
            $return = $validator->get_details();
            if ( empty( $return->msg ) ) {
                $return->msg = __( 'Unable to set club role', 'racketmanager' );
            }

            return $this->response_factory->create_error_response( $return, $return->status );
        }

        $updates   = false;
        $user      = $this->user_repository->find( (int) $command->user_id );
        $club_role = get_club_role( $command->club_role_id );
        if ( $club_role->user->id !== $command->user_id ) {
            $club_role = $this->club_service->reassign_role_user( $command->club_role_id, $command->user_id );
            if ( $club_role ) {
                $updates = true;
            }
        }
        if ( $user ) {
            $user_updates = $this->user_repository->update_contact(
                $user,
                (string) ( $command->contact_no ?? '' ),
                (string) ( $command->email ?? '' )
            );
            if ( $user_updates ) {
                $updates = true;
            }
        }

        $return         = new stdClass();
        $return->msg    = $updates ? __( 'Club role updated', 'racketmanager' ) : __( 'No updates', 'racketmanager' );
        $return->status = $updates ? 'success' : 'warning';

        return $this->response_factory->create_success_response( $return );
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

    private function nonce_failure_html_response( string $field, string $action, array $request = [] ): ?Response {
        $details = $this->verify_nonce_details( $field, $action, $request );
        if ( $details === null ) {
            return null;
        }

        $output = show_alert( (string) $details->msg, 'danger', 'modal' );

        return $this->response_factory->create_raw_response( $output, (int) $details->status );
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
