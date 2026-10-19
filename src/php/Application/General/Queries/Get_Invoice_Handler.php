<?php
declare( strict_types=1 );

namespace Racketmanager\Application\General\Queries;

use Racketmanager\Exceptions\Invoice_Not_Found_Exception;
use Racketmanager\Gateways\Option\Option_Gateway_Interface;
use Racketmanager\Presentation\Presenters\Invoice_Presenter;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Settings_Service;
use function __;
use function htmlspecialchars_decode;
use function is_object;
use function method_exists;
use function trim;
use function wp_specialchars_decode;

class Get_Invoice_Handler {
    public function __construct(
        private readonly Finance_Service $finance_service,
        private readonly Settings_Service $settings_service,
        private readonly Option_Gateway_Interface $option_gateway,
        private readonly Invoice_Presenter $invoice_presenter
    ) {}

    public function handle( Get_Invoice_Query $query ): Shortcode_View_Model {
        if ( empty( $query->id ) ) {
            return Shortcode_View_Model::for_error( __( 'Invoice not found', 'racketmanager' ) );
        }

        try {
            $invoice = $this->finance_service->get_full_invoice_details( $query->id );
        } catch ( Invoice_Not_Found_Exception $e ) {
            return Shortcode_View_Model::for_error( $e->getMessage() );
        }

        $billing           = $this->settings_service->get_category( 'billing', [] ) ?: [];
        $currency_code     = isset( $billing['billingCurrency'] ) && '' !== trim( (string) $billing['billingCurrency'] )
            ? (string) $billing['billingCurrency']
            : $this->finance_service->get_currency()->value;
        $billing['billingCurrency'] = $currency_code;
        $raw_org_name      = (string) $this->option_gateway->get_option( 'blogname', '' );
        $organisation_name = function_exists( 'wp_specialchars_decode' )
            ? wp_specialchars_decode( $raw_org_name, ENT_QUOTES )
            : htmlspecialchars_decode( $raw_org_name, ENT_QUOTES );

        $invoice_number = '';
        if ( is_object( $invoice ) ) {
            if ( isset( $invoice->invoice ) && is_object( $invoice->invoice ) && method_exists( $invoice->invoice, 'get_invoice_number' ) ) {
                $invoice_number = $invoice->invoice->get_invoice_number();
            } elseif ( isset( $invoice->invoice_number ) ) {
                $invoice_number = (string) $invoice->invoice_number;
            }
        }

        $site_url          = (string) ( $this->option_gateway->get_option( 'siteurl', '' ) ?: $this->option_gateway->get_option( 'home', '' ) );
        $date_format       = (string) $this->option_gateway->get_option( 'date_format', 'd-m-Y' );
        $is_user_logged_in = function_exists( 'is_user_logged_in' ) ? is_user_logged_in() : false;
        $login_url         = function_exists( 'wp_login_url' ) ? wp_login_url() : '/wp-login.php';

        $invoice_vm = $this->invoice_presenter->present_invoice(
            invoice: $invoice,
            billing: $billing,
            organisation_name: $organisation_name,
            site_url: $site_url,
            date_format: $date_format,
            is_user_logged_in: $is_user_logged_in,
            login_url: $login_url
        );

        $filename = ! empty( $query->template ) ? 'invoice-' . $query->template : 'invoice';

        return Shortcode_View_Model::for_template( $filename, [
            'organisation_name' => $organisation_name,
            'invoice'           => $invoice_vm,
            'billing'           => $billing,
            'invoice_number'    => $invoice_number,
        ] );
    }
}
