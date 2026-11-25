<?php
declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Ajax;

use Racketmanager\Application\Club\Commands\Club_Player_Request_Command;
use Racketmanager\Application\Club\Commands\Club_Players_Remove_Command;
use Racketmanager\Application\Club\Commands\Set_Club_Role_Command;
use Racketmanager\Application\Club\Commands\Update_Club_Command;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Already_Registered_Exception;
use Racketmanager\Exceptions\Registration_Not_Found_Exception;
use Racketmanager\Application\Ports\Security_Service_Interface;
use Racketmanager\Application\Ports\Json_Response_Factory_Interface;
use Racketmanager\Infrastructure\Wordpress\Response\Response;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Club_Role_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Repositories\Interfaces\User_Repository_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Validator\Validator_Club;
use stdClass;
use function Racketmanager\show_alert;
use function Racketmanager\show_club_role_modal;

/**
 * Adapter for Club AJAX requests.
 */
class Club_Ajax_Adapter {
    private const string NONCE_FIELD          = 'racketmanager_nonce';
    private const string DEFAULT_NONCE_FIELD  = 'security';
    private const string DEFAULT_NONCE_ACTION = 'ajax-nonce';

    /**
     * Initialises the service with required dependencies for security, response handling,
     * club management, registration, user access, role access, and optional repositories.
     *
     * @param Security_Service_Interface       $security_service Security service used for authorization and authentication checks.
     * @param Json_Response_Factory_Interface  $response_factory Factory used to create JSON responses.
     * @param Club_Service                     $club_service Service used to manage club-related operations.
     * @param Registration_Service             $registration_service Service used to handle registration workflows.
     * @param User_Repository_Interface        $user_repository Repository used to access user data.
     * @param Club_Role_Repository_Interface   $club_role_repository Repository used to access club role data.
     * @param Club_Repository_Interface|null   $club_repository Optional repository used to access club data.
     * @param Player_Repository_Interface|null $player_repository Optional repository used to access player data.
     *
     * @return void
     */
    public function __construct(
        private readonly Security_Service_Interface $security_service,
        private readonly Json_Response_Factory_Interface $response_factory,
        private readonly Club_Service $club_service,
        private readonly Registration_Service $registration_service,
        private readonly User_Repository_Interface $user_repository,
        private readonly Club_Role_Repository_Interface $club_role_repository,
        private readonly ?Club_Repository_Interface $club_repository = null,
        private readonly ?Player_Repository_Interface $player_repository = null
    ) {
    }

    /**
     * Creates a club validator using the configured role, player, user, and club repositories.
     *
     * @return Validator_Club Validator instance used to validate club-related data and operations.
     */
    private function create_validator(): Validator_Club {
        return new Validator_Club(
            $this->club_role_repository,
            $this->player_repository,
            $this->user_repository,
            $this->club_repository
        );
    }

    /**
     * Creates an unauthorised error response for requests made by users who are not logged in.
     *
     * @return Response Error response indicating that authentication is required.
     */
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

    /**
     * Creates an error response for unauthenticated access to a protected feature.
     *
     * @return Response Error response containing a logged-out alert message and HTTP 401 status.
     */
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

    /**
     * Updates an existing club from request data after nonce verification and validation.
     *
     * @param array $request Request data containing the club identifier, contact details, facilities, founded year, website, address, and match secretary details.
     *
     * @return Response JSON response indicating whether the club was updated, nothing changed, or validation/security failed.
     */
    public function update_club( array $request = [] ): Response {
        $security_error = $this->nonce_failure_response( self::NONCE_FIELD, 'club-update', __( 'Serious error in club update', 'racketmanager' ), $request );
        if ( $security_error ) {
            return $security_error;
        }

        $command   = Update_Club_Command::from_post( $request );
        $validator = $this->create_validator();
        $validator = $validator->club( null !== $command->club_id ? (string) $command->club_id : null );
        $validator = $validator->address( $command->address );
        $validator = $validator->telephone( $command->match_secretary_contact_no, 'matchSecretaryContactNo', true );
        $validator = $validator->email( $command->match_secretary_email, $command->match_secretary_id, true, 'matchSecretaryEmail', true );
        $validator = $validator->match_secretary( $command->match_secretary_id, 'matchSecretaryName' );
        if ( ! empty( $validator->error ) ) {
            $return      = $validator->get_details();
            $return->msg = __( 'Error in club update', 'racketmanager' );

            return $this->response_factory->create_error_response( $return, $return->status );
        }

        $club         = $this->club_service->get_club( $command->club_id );
        $club_updated = clone $club;
        $club_updated->set_contact_no( $command->contact_no );
        $club_updated->set_facilities( $command->facilities );
        $club_updated->set_founded( $command->founded );
        $club_updated->set_website( $command->website );
        $club_updated->set_address( $command->address );
        $club_updated->set_match_secretary( $command->match_secretary() );
        $club_updated = $this->club_service->update_club( $club->id, $club_updated );

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

    /**
     * Handles a club player registration request, validates security nonce data,
     * attempts to register the current user as a player for the requested club,
     * and returns a success or error response based on the registration outcome.
     *
     * @param array $request Request data used to create the club player registration command and perform validation.
     *
     * @return Response Response indicating whether the player registration request succeeded or failed.
     */
    public function club_player_request( array $request = [] ): Response {
        $security_error = $this->nonce_failure_response( self::NONCE_FIELD, 'club-player-request', null, $request );
        if ( $security_error ) {
            return $security_error;
        }

        $command   = Club_Player_Request_Command::from_post( $request, wp_get_current_user()->ID );
        $validator = $this->create_validator();
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

    /**
     * Removes one or more players from a club registration request after validating the nonce.
     *
     * @param array $request Request data containing the nonce and player registration identifiers to remove.
     *
     * @return Response Success response indicating whether players were removed or no players were selected.
     */
    public function club_player_remove( array $request = [] ): Response {
        $security_error = $this->nonce_failure_response( self::NONCE_FIELD, 'club-player-remove', null, $request );
        if ( $security_error ) {
            return $security_error;
        }

        $command = Club_Players_Remove_Command::from_post( $request, wp_get_current_user()->ID );
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

    /**
     * Displays a club role modal after validating the request nonce and preparing request data.
     *
     * @param array $request Request data containing the club role identifier and modal name.
     *
     * @return Response Raw response containing the rendered club role modal, or a security error response when nonce validation fails.
     */
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

    /**
     * Sets or updates a club role assignment from request data, including nonce validation,
     * command validation, optional user reassignment, and contact detail updates.
     *
     * @param array $request Request data containing nonce, club role, club, user, contact number, and email values.
     *
     * @return Response JSON response indicating whether the club role was updated, no changes were made, or validation/security failed.
     */
    public function set_club_role( array $request = [] ): Response {
        $security_error = $this->nonce_failure_response( self::NONCE_FIELD, 'racketmanager_club-role-update', null, $request );
        if ( $security_error ) {
            return $security_error;
        }

        $command   = Set_Club_Role_Command::from_post( $request );
        $validator = $this->create_validator();
        $validator = $validator->club( null !== $command->club_id ? (string) $command->club_id : null );
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
        $club_role = $this->club_role_repository->find( (int) $command->club_role_id );
        if ( $club_role && $club_role->user->id !== $command->user_id ) {
            $club_role = $this->club_service->reassign_role_user( $command->club_role_id, $command->user_id );
            if ( $club_role ) {
                $updates = true;
            }
        }
        if ( $user ) {
            $user_updates = $this->user_repository->update_contact(
                $user,
                $command->contact_no ?? '',
                $command->email ?? ''
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

    /**
     * Creates an error response for a failed nonce verification.
     *
     * @param string      $field The request field containing the nonce value.
     * @param string      $action The nonce action to verify against.
     * @param string|null $override_msg Optional message to use instead of the default failure message.
     * @param array       $request Optional request data used during nonce verification.
     *
     * @return Response|null Error response when nonce verification fails, or null when verification succeeds.
     */
    private function nonce_failure_response( string $field, string $action, ?string $override_msg = null, array $request = [] ): ?Response {
        $details = $this->verify_nonce_details( $field, $action, $request );
        if ( null === $details ) {
            return null;
        }
        if ( null !== $override_msg ) {
            $details->msg = $override_msg;
        }

        return $this->response_factory->create_error_response( $details, $details->status );
    }

    /**
     * Creates an HTML alert response for a failed nonce verification.
     *
     * @param string $field The request field containing the nonce value.
     * @param string $action The nonce action to verify against.
     * @param array  $request Optional request data used during nonce verification.
     *
     * @return Response|null Raw HTML response when nonce verification fails, or null when verification succeeds.
     */
    private function nonce_failure_html_response( string $field, string $action, array $request = [] ): ?Response {
        $details = $this->verify_nonce_details( $field, $action, $request );
        if ( null === $details ) {
            return null;
        }

        $output = show_alert( (string) $details->msg, 'danger', 'modal' );

        return $this->response_factory->create_raw_response( $output, (int) $details->status );
    }

    /**
     * Verifies nonce request details and builds failure information when validation fails.
     *
     * @param string $field The request field expected to contain the nonce value.
     * @param string $action The nonce action to validate against.
     * @param array  $request Optional request data containing the nonce field.
     *
     * @return stdClass|null Failure details when the nonce is missing or invalid, or null when verification succeeds.
     */
    private function verify_nonce_details( string $field, string $action, array $request = [] ): ?stdClass {
        $nonce_set = isset( $request[ $field ] );
        $nonce     = $request[ $field ] ?? '';
        $nonce     = is_string( $nonce ) ? sanitize_text_field( wp_unslash( $nonce ) ) : '';
        if ( '' !== $nonce && $this->security_service->verify_nonce( $nonce, $action ) ) {
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
