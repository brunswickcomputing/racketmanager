<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Club\Queries;

use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Invoice_Not_Found_Exception;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Finance_Service;
use stdClass;
use function __;
use function function_exists;
use function Racketmanager\show_invoice;

/**
 * Handles the [club-invoices] query: invoice list or single invoice, with club ownership
 * and management permissions resolved here rather than in the template.
 */
class Get_Club_Invoices_Handler {
    public function __construct(
        private readonly Club_Service $club_service,
        private readonly Finance_Service $finance_service
    ) {}

    public function handle( Get_Club_Invoices_Query $query ): Shortcode_View_Model {
        try {
            $club = $this->club_service->get_club_by_shortcode( $query->club_name );
        } catch ( Club_Not_Found_Exception $e ) {
            return Shortcode_View_Model::error( $e->getMessage() );
        }

        $user_can_manage       = new stdClass();
        $user_can_manage->club = $query->is_admin
            || ( null !== $query->user_id && $this->club_service->is_user_match_secretary( $query->user_id, (int) $club->id ) );

        $invoices     = [];
        $invoice      = null;
        $template_ref = 'invoices';

        if ( ! empty( $query->invoice_ref ) ) {
            try {
                $invoice = $this->finance_service->get_invoice( (int) $query->invoice_ref );
            } catch ( Invoice_Not_Found_Exception $e ) {
                return Shortcode_View_Model::error( $e->getMessage() );
            }
            if ( $invoice->billable_id !== $club->id ) {
                return Shortcode_View_Model::error( __( 'Invoice not for this club', 'racketmanager' ) );
            }
            $invoice->details = function_exists( '\Racketmanager\show_invoice' ) ? show_invoice( $invoice->id ) : '';
            $template_ref     = 'invoice';
        } else {
            $invoices = $this->finance_service->get_invoices_by_criteria( [ 'billable' => $club->id ] );
        }

        return new Shortcode_View_Model(
            template: '' !== $query->template ? $template_ref . '-' . $query->template : $template_ref,
            vars: [
                'club'            => $club,
                'user_can_manage' => $user_can_manage,
                'invoices'        => $invoices,
                'invoice'         => $invoice,
            ],
            template_type: 'club'
        );
    }
}
