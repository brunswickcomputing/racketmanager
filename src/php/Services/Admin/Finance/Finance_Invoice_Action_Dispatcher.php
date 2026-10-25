<?php
/**
 * Finance invoice action dispatcher
 *
 * @package RacketManager
 * @subpackage Services/Admin/Finance
 */

declare( strict_types=1 );

namespace Racketmanager\Services\Admin\Finance;

use Racketmanager\Application\Finance\Commands\Action_Invoice_Command;
use Racketmanager\Application\Finance\Commands\Action_Invoice_Handler;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Invalid_Argument_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Invoice_Not_Found_Exception;
use Racketmanager\Exceptions\Invoice_Not_Updated_Exception;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Finance_Service;

final readonly class Finance_Invoice_Action_Dispatcher {
    public function __construct(
        private Finance_Service $finance_service,
        private Action_Guard_Interface $action_guard,
        private Action_Invoice_Handler $action_invoice_handler,
    ) {
    }

    /**
     * @param array<string, mixed> $query Typically $_GET
     * @param array<string, mixed> $post  Typically $_POST
     */
    public function handle( array $query, array $post ): Action_Result_DTO {
        if ( isset( $post['doActionInvoices'] ) && isset( $post['action'] ) ) {
            return $this->handle_bulk_action_invoices( $query, $post );
        }

        if ( isset( $post['saveInvoice'] ) ) {
            return $this->handle_save_invoice( $query, $post );
        }

        return new Action_Result_DTO();
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     */
    private function handle_bulk_action_invoices( array $query, array $post ): Action_Result_DTO {
        $view = isset( $query['view'] ) ? sanitize_text_field( wp_unslash( strval( $query['view'] ) ) ) : 'club-invoices';

        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_invoices-bulk', 'edit_leagues' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
                query_params: array( 'view' => $view ),
            );
        }

        $invoices = isset( $post['invoice'] ) ? array_map( 'intval', (array) $post['invoice'] ) : array();
        $action   = isset( $post['action'] ) ? sanitize_text_field( wp_unslash( strval( $post['action'] ) ) ) : '';

        if ( empty( $invoices ) || '' === $action ) {
            return new Action_Result_DTO(
                message: __( 'No invoices selected', 'racketmanager' ),
                message_type: Admin_Message_Type::WARNING,
                query_params: array( 'view' => $view ),
            );
        }

        $messages      = array();
        $message_error = false;

        foreach ( $invoices as $invoice_id ) {
            try {
                $command    = Action_Invoice_Command::create( $invoice_id, $action );
                $msg        = $this->action_invoice_handler->handle( $command );
                $messages[] = $msg;
            } catch ( Invoice_Not_Found_Exception|Invalid_Argument_Exception $e ) {
                $messages[]    = $e->getMessage();
                $message_error = true;
            }
        }

        $message = implode( '<br>', $messages );

        return new Action_Result_DTO(
            message: $message,
            message_type: $message_error ? Admin_Message_Type::ERROR : Admin_Message_Type::SUCCESS,
            query_params: array( 'view' => $view ),
        );
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     */
    private function handle_save_invoice( array $query, array $post ): Action_Result_DTO {
        $invoice_id = isset( $post['invoice_id'] ) && '' !== strval( $post['invoice_id'] )
            ? intval( $post['invoice_id'] )
            : ( isset( $query['invoice'] ) ? intval( $query['invoice'] ) : 0 );

        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_manage-invoice', 'edit_leagues' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
                query_params: array( 'view' => 'invoice', 'invoice' => $invoice_id ),
            );
        }

        if ( empty( $invoice_id ) ) {
            return new Action_Result_DTO(
                message: __( 'Invalid invoice', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
                query_params: array( 'view' => 'club-invoices' ),
            );
        }

        $form_data      = $this->sanitize_form_data( $post );
        $status         = isset( $post['status'] ) ? sanitize_text_field( wp_unslash( strval( $post['status'] ) ) ) : '';
        $purchase_order = isset( $post['purchaseOrder'] ) ? sanitize_text_field( wp_unslash( strval( $post['purchaseOrder'] ) ) ) : '';

        if ( '' === $status ) {
            return new Action_Result_DTO(
                message: __( 'Please select an invoice status', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
                query_params: array( 'view' => 'invoice', 'invoice' => $invoice_id ),
                flash_data: array(
                    'errors'    => array( 'status' => __( 'Please select an invoice status', 'racketmanager' ) ),
                    'form_data' => $form_data,
                ),
            );
        }

        try {
            $result = $this->finance_service->amend_invoice( $invoice_id, $status, $purchase_order );
            if ( is_wp_error( $result ) ) {
                $errors = array();
                foreach ( $result->get_error_codes() as $code ) {
                    $errors[ $code ] = $result->get_error_message( $code );
                }
                return new Action_Result_DTO(
                    message: __( 'Error updating invoice', 'racketmanager' ),
                    message_type: Admin_Message_Type::ERROR,
                    query_params: array( 'view' => 'invoice', 'invoice' => $invoice_id ),
                    flash_data: array(
                        'errors'    => $errors,
                        'form_data' => $form_data,
                    ),
                );
            }

            return new Action_Result_DTO(
                message: __( 'Invoice updated', 'racketmanager' ),
                message_type: Admin_Message_Type::SUCCESS,
                query_params: array( 'view' => 'invoice', 'invoice' => $invoice_id ),
            );
        } catch ( Invoice_Not_Found_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
                query_params: array( 'view' => 'invoice', 'invoice' => $invoice_id ),
            );
        } catch ( Invoice_Not_Updated_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::WARNING,
                query_params: array( 'view' => 'invoice', 'invoice' => $invoice_id ),
            );
        }
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
        $whitelist = array( 'invoice_id', 'status', 'purchaseOrder' );
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
