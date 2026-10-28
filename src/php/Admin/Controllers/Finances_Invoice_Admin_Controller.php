<?php
declare( strict_types=1 );
/**
 * Finances Invoice Admin Controller
 *
 * Handles editing and viewing individual finance invoices.
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\Presenters\Admin_Message_Mapper;
use Racketmanager\Admin\View_Models\Error_Bag;
use Racketmanager\Admin\View_Models\Finances_Invoice_Page_View_Model;
use Racketmanager\Exceptions\Invoice_Not_Found_Exception;
use Racketmanager\Presentation\Presenters\Invoice_Presenter;
use Racketmanager\Services\Admin\Finance\Finance_Invoice_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Util\Util_Messages;
use function Racketmanager\show_invoice;

final readonly class Finances_Invoice_Admin_Controller {

    public function __construct(
        private Finance_Service $finance_service,
        private Finance_Invoice_Action_Dispatcher $finance_invoice_dispatcher,
        private Action_Guard_Interface $action_guard,
        private Invoice_Presenter $invoice_presenter,
    ) {
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     * @param array<string, mixed> $flash_data
     *
     * @return array{view_model?: Finances_Invoice_Page_View_Model, redirect?: string, message?: string, message_type?: bool|string, flash_data?: array<string, mixed>}
     * @throws Invoice_Not_Found_Exception
     */
    public function invoice_page( array $query, array $post, array $flash_data = array() ): array {
        if ( isset( $post['saveInvoice'] ) ) {
            $result = $this->finance_invoice_dispatcher->handle( $query, $post );

            $extra_args = array();
            if ( null !== $result->tab_override && '' !== $result->tab_override ) {
                $extra_args['tab'] = $result->tab_override;
            }
            $extra_args = array_merge( $extra_args, $result->query_params );

            $redirect = Admin_Redirect_Url_Builder::finances_screen( $query, $extra_args );

            return array(
                'redirect'     => $redirect,
                'message'      => $result->message,
                'message_type' => Admin_Message_Mapper::to_legacy( $result->message_type ),
                'flash_data'   => $result->flash_data,
            );
        }

        $this->action_guard->assert_capability( 'edit_leagues' );

        $errors = is_array( $flash_data['errors'] ?? null ) ? new Error_Bag( $flash_data['errors'] ) : new Error_Bag();

        $charge_id  = isset( $query['charge'] ) ? intval( $query['charge'] ) : null;
        $club_id    = isset( $query['club'] ) ? intval( $query['club'] ) : null;
        $invoice_id = isset( $query['invoice'] ) ? intval( $query['invoice'] ) : null;

        if ( $charge_id && $club_id ) {
            $found = $this->finance_service->get_invoice_by_charge_and_club( $charge_id, $club_id );
            $invoice = $this->finance_service->get_full_invoice_details( $found->get_id() );
        } elseif ( $invoice_id ) {
            $invoice = $this->finance_service->get_full_invoice_details( $invoice_id );
        } else {
            throw new Invoice_Not_Found_Exception( Util_Messages::invoice_not_found() );
        }

        $form_data = $flash_data['form_data'] ?? array();
        if ( ! empty( $form_data ) ) {
            if ( isset( $form_data['purchaseOrder'] ) && isset( $invoice->invoice ) && method_exists( $invoice->invoice, 'set_purchase_order' ) ) {
                $invoice->invoice->set_purchase_order( sanitize_text_field( strval( $form_data['purchaseOrder'] ) ) );
            }
            if ( isset( $form_data['status'] ) && isset( $invoice->invoice ) && method_exists( $invoice->invoice, 'set_status' ) ) {
                $invoice->invoice->set_status( sanitize_text_field( strval( $form_data['status'] ) ) );
            }
        }

        $invoice_view = show_invoice( $invoice->invoice->get_id() );

        $vm = $this->invoice_presenter->present_admin_invoice_page(
            invoice: $invoice,
            invoice_view: $invoice_view,
            errors: $errors,
        );

        return array(
            'view_model' => $vm,
        );
    }
}
