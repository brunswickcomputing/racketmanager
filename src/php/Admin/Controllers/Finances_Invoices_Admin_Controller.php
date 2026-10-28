<?php
declare( strict_types=1 );
/**
 * Finances Invoices Admin Controller
 *
 * Handles display and bulk actions for club and player invoices.
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\Presenters\Admin_Message_Mapper;
use Racketmanager\Admin\View_Models\Finances_Invoices_Page_View_Model;
use Racketmanager\Presentation\Presenters\Invoice_Presenter;
use Racketmanager\Services\Admin\Finance\Finance_Invoice_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Finance_Service;

final readonly class Finances_Invoices_Admin_Controller {

    public function __construct(
        private Finance_Service $finance_service,
        private Club_Service $club_service,
        private Finance_Invoice_Action_Dispatcher $finance_invoice_dispatcher,
        private Action_Guard_Interface $action_guard,
        private Invoice_Presenter $invoice_presenter,
    ) {
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     * @param string               $tab 'club-invoices'|'player-invoices'
     *
     * @return array{view_model?: Finances_Invoices_Page_View_Model, redirect?: string, message?: string, message_type?: bool|string, flash_data?: array<string, mixed>}
     */
    public function invoices_page( array $query, array $post, string $tab = 'club-invoices' ): array {
        if ( isset( $post['doActionInvoices'] ) && isset( $post['action'] ) ) {
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

        $club_id   = isset( $query['club'] ) ? intval( $query['club'] ) : null;
        $charge_id = isset( $query['charge'] ) ? intval( $query['charge'] ) : null;
        $status    = isset( $query['status'] ) ? sanitize_text_field( wp_unslash( strval( $query['status'] ) ) ) : 'open';

        $args = array(
            'type' => ( 'club-invoices' === $tab ) ? 'club' : 'player',
        );
        if ( $club_id ) {
            $args['billable'] = $club_id;
        }
        if ( $status ) {
            $args['status'] = $status;
        }
        if ( $charge_id ) {
            $args['charge'] = $charge_id;
        }

        $finance_invoices = $this->finance_service->get_invoices_by_criteria( $args );
        $clubs            = ( 'club-invoices' === $tab ) ? (array) $this->club_service->get_clubs() : array();
        $charges          = $this->get_finance_charges_for_invoices( 'club-invoices' === $tab ? 'team' : 'player' );

        $currency_code = $this->finance_service->get_currency()->value;
        $vm            = $this->invoice_presenter->present_admin_invoices_list(
            invoices: $finance_invoices,
            charges: $charges,
            clubs: $clubs,
            tab: $tab,
            club_id: $club_id,
            charge_id: $charge_id,
            status: $status,
            currency_code: $currency_code,
        );

        return array(
            'view_model' => $vm,
        );
    }

    /**
     * Get charges for invoices
     *
     * @param string $type
     *
     * @return array
     */
    private function get_finance_charges_for_invoices( string $type ): array {
        $args = array(
            'entry'   => $type,
            'orderby' => array(
                'season'         => 'DESC',
                'competition_id' => 'ASC',
            ),
        );
        return $this->finance_service->get_charges_by_criteria( $args );
    }
}
