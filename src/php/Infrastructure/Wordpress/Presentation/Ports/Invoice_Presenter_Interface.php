<?php
/**
 * Invoice presenter port
 *
 * @package RacketManager
 * @subpackage Infrastructure/Wordpress/Presentation/Ports
 */

declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Presentation\Ports;

use Racketmanager\Domain\Value_Objects\Money;
use Racketmanager\Presentation\View_Models\Club_Invoices_List_View_Model;
use Racketmanager\Presentation\View_Models\Invoice_View_Model;

/**
 * Interface for presenting invoice view models and formatting currency.
 */
interface Invoice_Presenter_Interface {

    /**
     * Present full Invoice View Model.
     *
     * @param object|null          $invoice           Invoice object.
     * @param array<string, mixed> $billing           Billing data.
     * @param string               $organisation_name Organisation name.
     * @param string               $site_url          Site URL.
     * @param string               $date_format       Date format.
     * @param string|null          $return_url        Return URL.
     * @param string               $details           Details.
     * @param bool                 $is_user_logged_in Whether user is logged in.
     * @param string               $login_url         Login URL.
     *
     * @return Invoice_View_Model
     */
    public function present_invoice(
        ?object $invoice = null,
        array $billing = [],
        string $organisation_name = '',
        string $site_url = '',
        string $date_format = 'd-m-Y',
        ?string $return_url = null,
        string $details = '',
        bool $is_user_logged_in = false,
        string $login_url = '/wp-login.php'
    ): Invoice_View_Model;

    /**
     * Present single club invoice detail for club/invoice.php template.
     *
     * @param object      $invoice           Invoice object.
     * @param object      $club              Club object.
     * @param string|null $return_url        Return URL.
     * @param string      $details           Details.
     * @param bool        $is_user_logged_in Whether user is logged in.
     * @param string      $login_url         Login URL.
     *
     * @return Invoice_View_Model
     */
    public function present_club_invoice_detail(
        object $invoice,
        object $club,
        ?string $return_url = null,
        string $details = '',
        bool $is_user_logged_in = true,
        string $login_url = '/wp-login.php'
    ): Invoice_View_Model;

    /**
     * Present club invoices list view model.
     *
     * @param array       $invoices      Invoices array.
     * @param string|null $currency_code Currency code.
     * @param string      $date_format   Date format.
     *
     * @return Club_Invoices_List_View_Model
     */
    public function present_club_invoices_list(
        array $invoices,
        ?string $currency_code = null,
        string $date_format = 'd-m-Y'
    ): Club_Invoices_List_View_Model;

    /**
     * Format a currency amount according to current locale or NumberFormatter.
     *
     * @param float|int|string|Money|null $amount        Amount value.
     * @param string|null                 $currency_code Currency code.
     *
     * @return string
     */
    public function format_currency( float|int|string|Money|null $amount, ?string $currency_code = null ): string;
}
