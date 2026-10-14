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
use Racketmanager\Domain\Charge;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Charge_Not_Deleted_Exception;
use Racketmanager\Exceptions\Charge_Not_Found_Exception;
use Racketmanager\Exceptions\Charge_Not_Updated_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Invoice_Not_Created_Exception;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Util\Util_Messages;
use stdClass;

final readonly class Finance_Charge_Action_Dispatcher {

    public function __construct(
        private Finance_Service $finance_service,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * @param array<string, mixed> $query Typically $_GET
     * @param array<string, mixed> $post  Typically $_POST
     */
    public function handle( array $query, array $post ): Action_Result_DTO {
        if ( isset( $post['doChargesDel'] ) && isset( $post['action'] ) && 'delete' === $post['action'] ) {
            return $this->handle_delete_charges( $post );
        }

        if ( isset( $post['generateInvoices'] ) ) {
            return $this->handle_generate_invoices( $post );
        }

        if ( isset( $post['saveCharge'] ) ) {
            return $this->handle_save_charge( $query, $post );
        }

        return new Action_Result_DTO();
    }

    /**
     * @param array<string, mixed> $post
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
            } catch ( Charge_Not_Found_Exception|Charge_Not_Deleted_Exception $e ) {
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
     * @param array<string, mixed> $post
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

        $charge_id = isset( $post['chargesId'] ) ? intval( $post['chargesId'] ) : ( isset( $post['charges_id'] ) ? intval( $post['charges_id'] ) : null );
        if ( empty( $charge_id ) ) {
            return new Action_Result_DTO(
                message: __( 'Invalid charge', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
                query_params: array( 'view' => 'charges' ),
            );
        }

        try {
            $result = $this->finance_service->send_invoices_for_charge( $charge_id, true );
            if ( $result ) {
                return new Action_Result_DTO(
                    message: __( 'Invoices generated and sent', 'racketmanager' ),
                    message_type: Admin_Message_Type::SUCCESS,
                    query_params: array( 'view' => 'charge', 'charges' => $charge_id ),
                );
            }

            return new Action_Result_DTO(
                message: __( 'Error on invoice sending', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
                query_params: array( 'view' => 'charge', 'charges' => $charge_id ),
            );
        } catch ( Charge_Not_Found_Exception|Invoice_Not_Created_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
                query_params: array( 'view' => 'charge', 'charges' => $charge_id ),
            );
        }
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
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
            $charge_id = $charge_input->id ?? ( isset( $query['charges'] ) ? intval( $query['charges'] ) : 0 );
            try {
                $result = $this->finance_service->amend_charge( $charge_input, (int) $charge_id );
                if ( is_wp_error( $result ) ) {
                    $errors = array();
                    foreach ( $result->get_error_codes() as $code ) {
                        $errors[ $code ] = $result->get_error_message( $code );
                    }
                    return new Action_Result_DTO(
                        message: __( 'Error updating charge', 'racketmanager' ),
                        message_type: Admin_Message_Type::ERROR,
                        query_params: array( 'view' => 'charge', 'charges' => $charge_id ),
                        flash_data: array(
                            'errors'    => $errors,
                            'form_data' => $form_data,
                        ),
                    );
                }

                return new Action_Result_DTO(
                    message: __( 'Charge updated', 'racketmanager' ),
                    message_type: Admin_Message_Type::SUCCESS,
                    query_params: array( 'view' => 'charge', 'charges' => $charge_id ),
                );
            } catch ( Charge_Not_Updated_Exception $e ) {
                return new Action_Result_DTO(
                    message: $e->getMessage(),
                    message_type: Admin_Message_Type::WARNING,
                    query_params: array( 'view' => 'charge', 'charges' => $charge_id ),
                );
            } catch ( Charge_Not_Found_Exception $e ) {
                return new Action_Result_DTO(
                    message: $e->getMessage(),
                    message_type: Admin_Message_Type::ERROR,
                    query_params: array( 'view' => 'charge', 'charges' => $charge_id ),
                );
            }
        }

        if ( isset( $post['addCharge'] ) ) {
            try {
                $result = $this->finance_service->add_charge( $charge_input );
                if ( is_wp_error( $result ) ) {
                    $errors = array();
                    foreach ( $result->get_error_codes() as $code ) {
                        $errors[ $code ] = $result->get_error_message( $code );
                    }
                    return new Action_Result_DTO(
                        message: __( 'Error adding charge', 'racketmanager' ),
                        message_type: Admin_Message_Type::ERROR,
                        query_params: array( 'view' => 'charge' ),
                        flash_data: array(
                            'errors'    => $errors,
                            'form_data' => $form_data,
                        ),
                    );
                }

                $new_id = $result instanceof Charge ? $result->get_id() : ( is_numeric( $result ) ? (int) $result : null );

                return new Action_Result_DTO(
                    message: __( 'Charge added', 'racketmanager' ),
                    message_type: Admin_Message_Type::SUCCESS,
                    query_params: $new_id ? array( 'view' => 'charge', 'charges' => $new_id ) : array( 'view' => 'charges' ),
                );
            } catch ( Exception $e ) {
                return new Action_Result_DTO(
                    message: $e->getMessage(),
                    message_type: Admin_Message_Type::ERROR,
                    query_params: array( 'view' => 'charge' ),
                );
            }
        }

        return new Action_Result_DTO(
            message: __( 'Invalid action', 'racketmanager' ),
            message_type: Admin_Message_Type::ERROR,
            query_params: array( 'view' => 'charges' ),
        );
    }

    /**
     * @param array<string, mixed> $post
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
     * Recursively sanitize input values
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
     * @param array<string, mixed> $post
     * @return array<string, mixed>
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
