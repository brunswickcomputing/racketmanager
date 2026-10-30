<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Club\Queries;

use Racketmanager\Application\Club\DTOs\Club_Invoices_Result;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Invoice_Not_Found_Exception;
use Racketmanager\Application\Ports\Option_Gateway_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Settings_Service;
use stdClass;
use function __;
use function function_exists;
use function Racketmanager\show_invoice;
use function trim;

/**
 * Handles the [club-invoices] query: invoice list or single invoice, with club ownership
 * and management permissions resolved here rather than in the template.
 */
readonly class Get_Club_Invoices_Handler {
    public function __construct(
        private Club_Service $club_service,
        private Finance_Service $finance_service,
        private ?Settings_Service $settings_service = null,
        private ?Option_Gateway_Interface $option_gateway = null
    ) {}

    public function handle( Get_Club_Invoices_Query $query ): Club_Invoices_Result {
        try {
            $club = $this->club_service->get_club_by_shortcode( $query->club_name );

            $user_can_manage       = new stdClass();
            $user_can_manage->club = $query->is_admin
                                     || ( null !== $query->user_id && $this->club_service->is_user_match_secretary( $query->user_id, (int) $club->id ) );

            $invoice       = null;
            $invoices_data = null;
            $template_ref  = 'invoices';
            $currency_code = $this->resolve_currency_code();
            $date_format   = $this->option_gateway ? (string) $this->option_gateway->get_option( 'date_format', 'd-m-Y' ) : 'd-m-Y';

            if ( ! empty( $query->invoice_ref ) ) {
                $invoice = $this->finance_service->get_invoice( (int) $query->invoice_ref );
                if ( $invoice->billable_id !== $club->id ) {
                    return Club_Invoices_Result::error( __( 'Invoice not for this club', 'racketmanager' ) );
                }
                $template_ref = 'invoice';
            } else {
                $invoices_data = $this->finance_service->get_invoices_by_criteria( [ 'billable' => $club->id ] );
            }
        } catch ( Club_Not_Found_Exception|Invoice_Not_Found_Exception $e ) {
            return Club_Invoices_Result::error( $e->getMessage() );
        }

        return new Club_Invoices_Result(
            club: $club,
            user_can_manage: $user_can_manage,
            invoice: $invoice,
            invoices_data: $invoices_data,
            currency_code: $currency_code,
            date_format: $date_format,
            template_ref: $template_ref,
            template: $query->template
        );
    }

    /**
     * Currency configured in the 'billing' settings category, falling back to the
     * finance service default when settings are unavailable or the value is blank.
     */
    private function resolve_currency_code(): string {
        if ( null !== $this->settings_service ) {
            $billing             = $this->settings_service->get_category( 'billing' ) ?: [];
            $configured_currency = trim( (string) ( $billing['billingCurrency'] ?? '' ) );

            if ( '' !== $configured_currency ) {
                return $configured_currency;
            }
        }

        // Only consulted when no usable configured currency exists (keeps the original
        // short-circuit behaviour; test stubs of Finance_Service rely on this).
        return $this->finance_service->get_currency()->value;
    }

}
