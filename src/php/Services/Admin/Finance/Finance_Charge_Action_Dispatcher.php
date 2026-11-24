<?php
/**
 * Finance charge action dispatcher
 *
 * @package RacketManager
 * @subpackage Services/Admin/Finance
 */

declare( strict_types=1 );

namespace Racketmanager\Services\Admin\Finance;

use Exception;
use Racketmanager\Application\Finance\Commands\Generate_Charge_Invoices_Command;
use Racketmanager\Application\Finance\Commands\Generate_Charge_Invoices_Handler;
use Racketmanager\Domain\Finance\Charge;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Charge_Not_Deleted_Exception;
use Racketmanager\Exceptions\Charge_Not_Found_Exception;
use Racketmanager\Exceptions\Charge_Not_Updated_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Invoice_Not_Created_Exception;
use Racketmanager\Application\Admin\Ports\Action_Guard_Interface;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Util\Util_Messages;
use stdClass;
use WP_Error;

/**
 * Dispatches finance charge administration actions and converts their outcomes into action result DTOs.
 *
 * Handles charge deletion, invoice generation, and charge create/update submissions. Validates the
 * requested action, enforces nonce and capability checks, delegates business operations to finance
 * services and handlers, and returns user-facing messages, redirect query parameters, and flash data
 * for validation failures.
 */
final readonly class Finance_Charge_Action_Dispatcher {
    /**
     * Initialise the instance with required finance, action guard, and charge invoice generation services.
     *
     * @param Finance_Service                  $finance_service Service used for finance-related operations.
     * @param Action_Guard_Interface           $action_guard Guard used to authorize or validate actions.
     * @param Generate_Charge_Invoices_Handler $generate_charge_invoices_handler Handler used to generate charge invoices.
     *
     * @return void
     */
    public function __construct(
        private Finance_Service $finance_service,
        private Action_Guard_Interface $action_guard,
        private Generate_Charge_Invoices_Handler $generate_charge_invoices_handler,
    ) {
    }

    /**
     * Handles charge-related form actions based on the submitted request data.
     *
     * @param array $query Query parameters associated with the request.
     * @param array $post Posted form data used to determine and process the requested action.
     *
     * @return Action_Result_DTO Result of the processed action, or an empty result when no action matches.
     */
    public function handle( array $query, array $post ): Action_Result_DTO {
        if ( isset( $post['doChargesDel'] ) && isset( $post['action'] ) && 'delete' === $post['action'] ) {
            $response = $this->handle_delete_charges( $post );
        }

        if ( isset( $post['generateInvoices'] ) ) {
            $response = $this->handle_generate_invoices( $post );
        }

        if ( isset( $post['saveCharge'] ) ) {
            $response = $this->handle_save_charge( $query, $post );
        }

        return $response ?? new Action_Result_DTO();
    }

    /**
     * Handles bulk deletion of charges from submitted post data.
     *
     * @param array $post Submitted request data containing selected charge IDs.
     *
     * @return Action_Result_DTO Result containing the admin message, message type, and redirect query parameters.
     */
    private function handle_delete_charges( array $post ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_charges-bulk', 'del_teams' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
                query_params: array( 'view' => 'charges' ),
            );
        }

        $charges = isset( $post['charge'] ) ? array_map( 'intval', (array) $post['charge'] ) : array();
        if ( empty( $charges ) ) {
            return new Action_Result_DTO(
                message: __( 'No charges were found to delete', 'racketmanager' ),
                message_type: Admin_Message_Type::WARNING,
                query_params: array( 'view' => 'charges' ),
            );
        }

        $messages      = array();
        $message_error = false;

        foreach ( $charges as $charge_id ) {
            try {
                $deleted = $this->finance_service->remove_charge( $charge_id );
                if ( $deleted ) {
                    $messages[] = Util_Messages::charge_deleted( $charge_id );
                } else {
                    $messages[]    = Util_Messages::charge_not_deleted( $charge_id );
                    $message_error = true;
                }
            } catch ( Charge_Not_Found_Exception | Charge_Not_Deleted_Exception $e ) {
                $messages[]    = $e->getMessage();
                $message_error = true;
            }
        }

        $message = implode( '<br>', $messages );

        return new Action_Result_DTO(
            message: $message,
            message_type: $message_error ? Admin_Message_Type::ERROR : Admin_Message_Type::SUCCESS,
            query_params: array( 'view' => 'charges' ),
        );
    }

    /**
     * Handles the bulk generation and sending of invoices for a charge request.
     *
     * @param array $post Posted form data used to build the invoice generation command.
     *
     * @return Action_Result_DTO Result containing the admin message type, message text, and redirect query parameters.
     */
    private function handle_generate_invoices( array $post ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_charges-bulk', 'edit_teams' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
                query_params: array( 'view' => 'charges' ),
            );
        }

        $command = Generate_Charge_Invoices_Command::from_post( $post );
        if ( empty( $command->charge_id ) ) {
            return new Action_Result_DTO(
                message: __( 'Invalid charge', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
                query_params: array( 'view' => 'charges' ),
            );
        }

        try {
            $result = $this->generate_charge_invoices_handler->handle( $command );
            if ( $result ) {
                $response = new Action_Result_DTO(
                    message: __( 'Invoices generated and sent', 'racketmanager' ),
                    message_type: Admin_Message_Type::SUCCESS,
                    query_params: array(
                        'view'    => 'charge',
                        'charges' => $command->charge_id,
                    ),
                );
            } else {
                $response = new Action_Result_DTO(
                    message: __( 'Error on invoice sending', 'racketmanager' ),
                    message_type: Admin_Message_Type::ERROR,
                    query_params: array(
                        'view'    => 'charge',
                        'charges' => $command->charge_id,
                    ),
                );
            }
        } catch ( Charge_Not_Found_Exception | Invoice_Not_Created_Exception $e ) {
            $response = new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
                query_params: array(
                    'view'    => 'charge',
                    'charges' => $command->charge_id,
                ),
            );
        }

        return $response;
    }

    /**
     * Handles saving a charge by validating permissions, sanitising submitted data, and adding or updating the charge.
     *
     * @param array $query Query parameters used to identify the charge or target view.
     * @param array $post Submitted form data for the charge action.
     *
     * @return Action_Result_DTO Result containing the admin message, message type, redirect query parameters, and optional flash data.
     */
    private function handle_save_charge( array $query, array $post ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_manage-charges', 'edit_teams' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
                query_params: array( 'view' => 'charges' ),
            );
        }

        $form_data    = $this->sanitize_form_data( $post );
        $charge_input = $this->get_charge_input( $post );

        if ( isset( $post['editCharge'] ) ) {
            $charge_id = (int) ( $charge_input->id ?? ( isset( $query['charges'] ) ? (int) $query['charges'] : 0 ) );
            $response  = $this->edit_charge( $charge_input, $charge_id, $form_data );
        }

        if ( isset( $post['addCharge'] ) ) {
            $response = $this->add_charge( $charge_input, $form_data );
        }

        return $response ?? new Action_Result_DTO(
            message: __( 'Invalid action', 'racketmanager' ),
            message_type: Admin_Message_Type::ERROR,
            query_params: array( 'view' => 'charges' ),
        );
    }

    /**
     * Updates an existing charge and produces the corresponding action result DTO.
     *
     * @param stdClass $charge_input Sanitized charge input data.
     * @param int      $charge_id    Identifier of the charge being amended.
     * @param array    $form_data    Sanitized form fields for flash recovery on validation failure.
     *
     * @return Action_Result_DTO Result of the amendment attempt.
     */
    private function edit_charge( stdClass $charge_input, int $charge_id, array $form_data ): Action_Result_DTO {
        $redirect_params = array(
            'view'    => 'charge',
            'charges' => $charge_id,
        );

        try {
            $result = $this->finance_service->amend_charge( $charge_input, $charge_id );
            if ( is_wp_error( $result ) ) {
                $response = new Action_Result_DTO(
                    message: __( 'Error updating charge', 'racketmanager' ),
                    message_type: Admin_Message_Type::ERROR,
                    query_params: $redirect_params,
                    flash_data: array(
                        'errors'    => $this->extract_wp_errors( $result ),
                        'form_data' => $form_data,
                    ),
                );
            } else {
                $response = new Action_Result_DTO(
                    message: __( 'Charge updated', 'racketmanager' ),
                    message_type: Admin_Message_Type::SUCCESS,
                    query_params: $redirect_params,
                );
            }
        } catch ( Charge_Not_Updated_Exception $e ) {
            $response = new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::WARNING,
                query_params: $redirect_params,
            );
        } catch ( Charge_Not_Found_Exception $e ) {
            $response = new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
                query_params: $redirect_params,
            );
        }

        return $response;
    }

    /**
     * Persists a newly added charge and produces the corresponding action result DTO.
     *
     * @param stdClass $charge_input Sanitized charge input data.
     * @param array    $form_data    Sanitized form fields for flash recovery on validation failure.
     *
     * @return Action_Result_DTO Result of the charge creation attempt.
     */
    private function add_charge( stdClass $charge_input, array $form_data ): Action_Result_DTO {
        try {
            $result = $this->finance_service->add_charge( $charge_input );
            if ( is_wp_error( $result ) ) {
                return new Action_Result_DTO(
                    message: __( 'Error adding charge', 'racketmanager' ),
                    message_type: Admin_Message_Type::ERROR,
                    query_params: array( 'view' => 'charge' ),
                    flash_data: array(
                        'errors'    => $this->extract_wp_errors( $result ),
                        'form_data' => $form_data,
                    ),
                );
            }

            if ( $result instanceof Charge ) {
                $new_id = $result->get_id();
            } else {
                $new_id = is_numeric( $result ) ? (int) $result : null;
            }

            return new Action_Result_DTO(
                message: __( 'Charge added', 'racketmanager' ),
                message_type: Admin_Message_Type::SUCCESS,
                query_params: $new_id ? array(
                    'view'    => 'charge',
                    'charges' => $new_id,
                ) : array( 'view' => 'charges' ),
            );
        } catch ( Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
                query_params: array( 'view' => 'charge' ),
            );
        }
    }

    /**
     * Extracts an associative code-to-message mapping from a WordPress error instance.
     *
     * @param WP_Error $error WordPress error instance.
     *
     * @return array<string, string> Associative array of error codes and corresponding messages.
     */
    private function extract_wp_errors( WP_Error $error ): array {
        $errors = array();
        foreach ( $error->get_error_codes() as $code ) {
            $errors[ $code ] = $error->get_error_message( $code );
        }

        return $errors;
    }
    /**
     * Builds a sanitised charge input object from submitted post data.
     *
     * @param array $post Submitted charge data.
     *
     * @return stdClass Sanitized charge input object.
     */
    private function get_charge_input( array $post ): stdClass {
        $charge                  = new stdClass();
        $charge->id              = isset( $post['charges_id'] ) && '' !== strval( $post['charges_id'] ) ? intval( $post['charges_id'] ) : null;
        $charge->competition_id  = empty( $post['competition_id'] ) ? null : intval( $post['competition_id'] );
        $charge->season          = isset( $post['season'] ) && '' !== strval( $post['season'] ) ? sanitize_text_field( wp_unslash( strval( $post['season'] ) ) ) : null;
        $charge->status          = isset( $post['status'] ) && '' !== strval( $post['status'] ) ? sanitize_text_field( wp_unslash( strval( $post['status'] ) ) ) : null;
        $charge->date            = isset( $post['date'] ) && '' !== strval( $post['date'] ) ? sanitize_text_field( wp_unslash( strval( $post['date'] ) ) ) : null;
        $charge->fee_competition = isset( $post['feeClub'] ) && '' !== strval( $post['feeClub'] ) ? floatval( $post['feeClub'] ) : null;
        $charge->fee_event       = isset( $post['feeTeam'] ) && '' !== strval( $post['feeTeam'] ) ? floatval( $post['feeTeam'] ) : null;

        return $charge;
    }

    /**
     * Recursively Sanitises scalar values and array keys/values.
     *
     * Converts scalar values to strings, unslashes them, and sanitises them as text.
     * Arrays are traversed recursively, with scalar keys sanitised in the same manner.
     * Non-scalar, non-array values are converted to null.
     *
     * @param mixed $value Value to sanitize.
     *
     * @return mixed Sanitized scalar string, recursively sanitized array, or null for unsupported value types.
     */
    private function sanitize_recursive( mixed $value ): mixed {
        if ( is_array( $value ) ) {
            $cleaned = array();
            foreach ( $value as $k => $v ) {
                $clean_key             = is_scalar( $k ) ? sanitize_text_field( wp_unslash( strval( $k ) ) ) : $k;
                $cleaned[ $clean_key ] = $this->sanitize_recursive( $v );
            }
            return $cleaned;
        }
        if ( is_scalar( $value ) ) {
            return sanitize_text_field( wp_unslash( strval( $value ) ) );
        }
        return null;
    }

    /**
     * Sanitises allowed form data fields from a submitted post array.
     *
     * Only whitelisted fields are processed. Present fields are sanitised recursively
     * before being added to the returned array, while missing or non-whitelisted
     * fields are ignored.
     *
     * @param array $post Submitted form data keyed by field name.
     *
     * @return array Sanitized form data containing only allowed fields that were present in the input.
     */
    private function sanitize_form_data( array $post ): array {
        $whitelist = array( 'charges_id', 'competition_id', 'season', 'status', 'date', 'feeClub', 'feeTeam' );
        $sanitized = array();

        foreach ( $whitelist as $field ) {
            if ( ! isset( $post[ $field ] ) ) {
                continue;
            }
            $sanitized[ $field ] = $this->sanitize_recursive( $post[ $field ] );
        }

        return $sanitized;
    }
}
