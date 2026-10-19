<?php
/**
 * Invoice Presenter
 *
 * @package RacketManager
 * @subpackage Presentation/Presenters
 */

declare( strict_types=1 );

namespace Racketmanager\Presentation\Presenters;

use NumberFormatter;
use Racketmanager\Admin\View_Models\Error_Bag;
use Racketmanager\Admin\View_Models\Finances_Charge_Item_View_Model;
use Racketmanager\Admin\View_Models\Finances_Charge_Page_View_Model;
use Racketmanager\Admin\View_Models\Finances_Charges_Page_View_Model;
use Racketmanager\Admin\View_Models\Finances_Club_Charge_Event_Item_View_Model;
use Racketmanager\Admin\View_Models\Finances_Club_Charge_Item_View_Model;
use Racketmanager\Admin\View_Models\Finances_Invoice_Item_View_Model;
use Racketmanager\Admin\View_Models\Finances_Invoice_Page_View_Model;
use Racketmanager\Admin\View_Models\Finances_Invoices_Page_View_Model;
use Racketmanager\Domain\Enums\Invoice_Status;
use Racketmanager\Domain\Value_Objects\Money;
use Racketmanager\Presentation\View_Models\Club_Invoices_List_View_Model;
use Racketmanager\Presentation\View_Models\Invoice_Item_View_Model;
use Racketmanager\Presentation\View_Models\Invoice_Summary_View_Model;
use Racketmanager\Presentation\View_Models\Invoice_View_Model;
use Racketmanager\Util\Util;
use Racketmanager\Util\Util_Lookup;
use function __;
use function class_exists;
use function function_exists;
use function get_locale;
use function is_iterable;
use function is_object;
use function is_scalar;
use function method_exists;
use function mysql2date;
use function numfmt_create;
use function numfmt_format_currency;
use function sprintf;
use function str_replace;
use function strtoupper;
use function trim;
use function ucfirst;
use function ucwords;

class Invoice_Presenter {

    /**
     * Present full Invoice View Model.
     *
     * @param object|null $invoice
     * @param array<string, mixed> $billing
     * @param string $organisation_name
     * @param string $site_url
     * @param string $date_format
     * @param string|null $return_url
     * @param string $details
     * @param bool $is_user_logged_in
     * @param string $login_url
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
    ): Invoice_View_Model {
        $invoice_number = '';
        if ( is_object( $invoice ) ) {
            if ( isset( $invoice->invoice ) && is_object( $invoice->invoice ) && method_exists( $invoice->invoice, 'get_invoice_number' ) ) {
                $invoice_number = (string) $invoice->invoice->get_invoice_number();
            } elseif ( isset( $invoice->invoice_number ) ) {
                $invoice_number = (string) $invoice->invoice_number;
            }
        }
        if ( '' === $invoice_number && isset( $billing['invoiceNumber'] ) ) {
            $invoice_number = (string) $billing['invoiceNumber'];
        }

        $id     = is_object( $invoice ) && isset( $invoice->id )
            ? (int) $invoice->id
            : ( is_object( $invoice ) && isset( $invoice->invoice ) && isset( $invoice->invoice->id ) ? (int) $invoice->invoice->id : null );
        $status = is_object( $invoice ) && isset( $invoice->status ) ? (string) $invoice->status : '';

        $status_enum    = ( is_object( $invoice ) && method_exists( $invoice, 'get_status_enum' ) )
            ? $invoice->get_status_enum()
            : ( is_object( $invoice ) && isset( $invoice->invoice ) && method_exists( $invoice->invoice, 'get_status_enum' )
                ? $invoice->invoice->get_status_enum()
                : Invoice_Status::try_from_value( '' !== $status ? $status : null ) );
        $is_paid        = $status_enum ? $status_enum->is_paid() : ( 'paid' === strtolower( $status ) );
        $status_display = $is_paid ? strtoupper( $status ) : null;
        $title          = '' !== $invoice_number
            ? ( __( 'Invoice', 'racketmanager' ) . ' ' . $invoice_number . ( '' !== $status ? ' (' . $status . ')' : '' ) )
            : '';

        $raw_date       = is_object( $invoice ) && isset( $invoice->date ) ? (string) $invoice->date : null;
        $date_formatted = ! empty( $raw_date ) && function_exists( 'mysql2date' )
            ? (string) mysql2date( $date_format, $raw_date )
            : (string) ( $raw_date ?? '' );

        $raw_date_due       = is_object( $invoice ) && isset( $invoice->date_due ) ? (string) $invoice->date_due : null;
        $date_due_formatted = ! empty( $raw_date_due ) && function_exists( 'mysql2date' )
            ? (string) mysql2date( $date_format, $raw_date_due )
            : (string) ( $raw_date_due ?? '' );

        if ( ! empty( $raw_date_due ) && $raw_date_due === $raw_date ) {
            $due_text = __( 'Payment due on receipt', 'racketmanager' );
        } elseif ( ! empty( $date_due_formatted ) ) {
            $due_text = __( 'Payment Due', 'racketmanager' ) . ': ' . $date_due_formatted;
        } else {
            $due_text = '';
        }

        $purchase_order         = is_object( $invoice ) && isset( $invoice->purchase_order ) && ! empty( $invoice->purchase_order )
            ? (string) $invoice->purchase_order
            : null;
        $purchase_order_display = null !== $purchase_order
            ? ( __( 'Purchase Order', 'racketmanager' ) . ': ' . strtoupper( $purchase_order ) )
            : null;

        $billable_name              = is_object( $invoice ) && isset( $invoice->billable_name ) ? (string) $invoice->billable_name : '';
        $billable_address           = is_object( $invoice ) && isset( $invoice->billable_address ) && ! empty( $invoice->billable_address )
            ? (string) $invoice->billable_address
            : null;
        $billable_address_formatted = null !== $billable_address
            ? str_replace( ',', '<br />', $billable_address )
            : null;

        $charge_name_raw = is_object( $invoice ) && isset( $invoice->charge_name ) ? (string) $invoice->charge_name : '';
        $charge_name     = '' !== $charge_name_raw ? ucfirst( $charge_name_raw ) : '';

        $raw_billing_address = isset( $billing['billingAddress'] ) ? (string) $billing['billingAddress'] : '';
        $billing_address     = '' !== $raw_billing_address ? str_replace( ',', '<br />', $raw_billing_address ) : '';
        $billing_email       = isset( $billing['billingEmail'] ) ? (string) $billing['billingEmail'] : '';
        $billing_telephone   = isset( $billing['billingTelephone'] ) ? (string) $billing['billingTelephone'] : '';
        $billing_url         = trim( str_replace( [ 'https://', 'http://' ], '', $site_url ), '/' );

        $currency_code = isset( $billing['billingCurrency'] ) && '' !== trim( (string) $billing['billingCurrency'] )
            ? (string) $billing['billingCurrency']
            : ( class_exists( Util::class ) ? Util::get_currency_code() : 'GBP' );

        $details_obj   = is_object( $invoice ) && isset( $invoice->details ) && is_object( $invoice->details ) ? $invoice->details : null;
        $has_breakdown = null !== $details_obj && ( isset( $details_obj->events ) || isset( $details_obj->fee_competition ) );

        $fee_competition_formatted = ( null !== $details_obj && ! empty( $details_obj->fee_competition ) )
            ? $this->format_currency( $details_obj->fee_competition, $currency_code )
            : null;
        $fee_events_unit_formatted = ( null !== $details_obj && isset( $details_obj->fee_events ) )
            ? $this->format_currency( $details_obj->fee_events, $currency_code )
            : null;

        $items = [];
        if ( null !== $details_obj && ! empty( $details_obj->events ) && is_iterable( $details_obj->events ) ) {
            foreach ( $details_obj->events as $event ) {
                if ( isset( $event->name ) ) {
                    $desc = (string) $event->name;
                } elseif ( isset( $event->type ) && class_exists( Util_Lookup::class ) ) {
                    $desc = Util_Lookup::get_event_type( $event->type );
                } else {
                    $desc = '';
                }
                $quantity   = $event->count ?? 1;
                $unit_price = $fee_events_unit_formatted ?? '';
                $net_price  = isset( $event->fee ) ? $this->format_currency( $event->fee, $currency_code ) : '';

                $items[] = new Invoice_Item_View_Model(
                    description: $desc,
                    quantity: $quantity,
                    unit_price_formatted: $unit_price,
                    net_price_formatted: $net_price,
                );
            }
        }

        $fee_total_raw = null !== $details_obj && isset( $details_obj->fee )
            ? $details_obj->fee
            : ( is_object( $invoice ) && isset( $invoice->amount ) ? $invoice->amount : 0 );
        $fee_total_formatted = $this->format_currency( $fee_total_raw, $currency_code );

        $has_paid             = null !== $details_obj && ! empty( $details_obj->paid );
        $paid_formatted       = null;
        $amount_due_formatted = null;
        if ( $has_paid ) {
            $paid_formatted = $this->format_currency( $details_obj->paid, $currency_code );
            if ( empty( $details_obj->total ) ) {
                $amount_due = (float) ( $details_obj->fee ?? 0 ) - (float) ( $details_obj->paid ?? 0 );
            } else {
                $amount_due = (float) $details_obj->total;
            }
            $amount_due_formatted = $this->format_currency( $amount_due, $currency_code );
        }

        $bank_name         = isset( $billing['bankName'] ) ? (string) $billing['bankName'] : null;
        $sort_code         = isset( $billing['sortCode'] ) ? (string) $billing['sortCode'] : null;
        $account_number    = isset( $billing['accountNumber'] ) ? (string) $billing['accountNumber'] : null;
        $payment_reference = $invoice_number;

        return new Invoice_View_Model(
            id: $id,
            invoice_number: $invoice_number,
            title: $title,
            status: $status,
            status_display: $status_display,
            is_paid: $is_paid,
            date: $raw_date,
            date_formatted: $date_formatted,
            date_due: $raw_date_due,
            date_due_formatted: $date_due_formatted,
            due_text: $due_text,
            purchase_order: $purchase_order,
            purchase_order_display: $purchase_order_display,
            billable_name: $billable_name,
            billable_address: $billable_address,
            billable_address_formatted: $billable_address_formatted,
            charge_name: $charge_name,
            organisation_name: $organisation_name,
            billing_address: $billing_address,
            billing_email: $billing_email,
            billing_url: $billing_url,
            billing_telephone: $billing_telephone,
            has_breakdown: $has_breakdown,
            fee_competition_formatted: $fee_competition_formatted,
            fee_events_unit_formatted: $fee_events_unit_formatted,
            items: $items,
            fee_total_formatted: $fee_total_formatted,
            has_paid: $has_paid,
            paid_formatted: $paid_formatted,
            amount_due_formatted: $amount_due_formatted,
            bank_name: $bank_name,
            sort_code: $sort_code,
            account_number: $account_number,
            payment_reference: $payment_reference,
            is_user_logged_in: $is_user_logged_in,
            login_url: $login_url,
            return_url: $return_url,
            details: $details,
        );
    }

    /**
     * Present single club invoice detail for club/invoice.php template.
     */
    public function present_club_invoice_detail(
        object $invoice,
        object $club,
        ?string $return_url = null,
        string $details = '',
        bool $is_user_logged_in = true,
        string $login_url = '/wp-login.php'
    ): Invoice_View_Model {
        return $this->present_invoice(
            invoice: $invoice,
            return_url: $return_url,
            details: $details,
            is_user_logged_in: $is_user_logged_in,
            login_url: $login_url
        );
    }

    /**
     * Present invoices list for club/invoices.php template.
     *
     * @param array<int, object> $invoices
     * @param string|null        $currency_code
     * @param string             $date_format
     * @return Club_Invoices_List_View_Model
     */
    public function present_club_invoices_list(
        array $invoices,
        ?string $currency_code = null,
        string $date_format = 'd-m-Y'
    ): Club_Invoices_List_View_Model {
        $currency     = ! empty( $currency_code ) ? $currency_code : ( class_exists( Util::class ) ? Util::get_currency_code() : 'GBP' );
        $items        = [];
        $total_amount = 0.0;

        foreach ( $invoices as $inv ) {
            $amt = isset( $inv->amount ) && null !== $inv->amount && '' !== $inv->amount ? (float) $inv->amount : null;
            if ( null !== $amt ) {
                $total_amount += $amt;
            }

            $raw_due   = isset( $inv->date_due ) ? (string) $inv->date_due : '';
            $due_fmt   = ! empty( $raw_due ) && function_exists( 'mysql2date' ) ? (string) mysql2date( $date_format, $raw_due ) : $raw_due;
            $raw_name  = isset( $inv->charge_name ) ? (string) $inv->charge_name : '';

            $items[] = new Invoice_Summary_View_Model(
                id: (int) ( $inv->id ?? 0 ),
                invoice_number: $inv->invoice_number ?? '',
                charge_name: '' !== $raw_name ? ucfirst( $raw_name ) : '',
                amount: $amt,
                amount_formatted: $this->format_currency( $amt, $currency ),
                status: (string) ( $inv->status ?? '' ),
                date_due: $raw_due,
                date_due_formatted: $due_fmt,
                link: isset( $inv->id ) ? ( (string) $inv->id . '/' ) : null
            );
        }

        $total_formatted = $this->format_currency( $total_amount, $currency );

        return new Club_Invoices_List_View_Model(
            items: $items,
            total_amount_formatted: $total_formatted,
            total_amount: $total_amount
        );
    }

    /**
     * Present charges list for admin/finances/charges.php template.
     *
     * @param array<int, object>    $charges
     * @param array<int, object>    $competitions
     * @param array<int, object>    $seasons
     * @param int|string|null       $competition_id
     * @param string|null           $season
     * @param string                $tab
     * @param string|null           $currency_code
     * @param Error_Bag             $errors
     * @return Finances_Charges_Page_View_Model
     */
    public function present_admin_charges_list(
        array $charges,
        array $competitions,
        array $seasons,
        int|string|null $competition_id = null,
        ?string $season = null,
        string $tab = 'charges',
        ?string $currency_code = null,
        Error_Bag $errors = new Error_Bag()
    ): Finances_Charges_Page_View_Model {
        $currency     = ! empty( $currency_code ) ? $currency_code : ( class_exists( Util::class ) ? Util::get_currency_code() : 'GBP' );
        $items        = [];
        $total_amount = 0.0;

        foreach ( $charges as $charge ) {
            $id        = is_object( $charge ) && isset( $charge->id ) ? (int) $charge->id : 0;
            $name      = is_object( $charge ) && isset( $charge->name ) ? (string) $charge->name : '';
            $status    = is_object( $charge ) && isset( $charge->status ) ? (string) $charge->status : '';
            $raw_total = is_object( $charge ) && isset( $charge->total ) ? $charge->total : null;
            $total_val = null !== $raw_total && '' !== $raw_total ? (float) $raw_total : null;

            if ( null !== $total_val && 0.0 !== $total_val ) {
                $total_amount += $total_val;
                $formatted_total = $this->format_currency( $total_val, $currency );
            } else {
                $formatted_total = '-';
            }

            $items[] = new Finances_Charge_Item_View_Model(
                id: $id,
                name: $name,
                status: $status,
                total: $total_val,
                total_formatted: $formatted_total,
                edit_url: '/wp-admin/admin.php?page=racketmanager-finances&view=charge&charges=' . $id
            );
        }

        $total_formatted = $this->format_currency( $total_amount, $currency );

        return new Finances_Charges_Page_View_Model(
            charges: $items,
            competitions: $competitions,
            seasons: $seasons,
            competition_id: $competition_id,
            season: $season,
            racketmanager_tab: $tab,
            total_amount_formatted: $total_formatted,
            total_amount: $total_amount,
            errors: $errors
        );
    }

    /**
     * Present invoices list for admin/finances/invoices.php template.
     *
     * @param array<int, object>    $invoices
     * @param array<int, object>    $charges
     * @param array<int, object>    $clubs
     * @param string                $tab
     * @param int|string|null       $club_id
     * @param int|string|null       $charge_id
     * @param string                $status
     * @param string|null           $currency_code
     * @param string                $date_format
     * @param Error_Bag             $errors
     * @return Finances_Invoices_Page_View_Model
     */
    public function present_admin_invoices_list(
        array $invoices,
        array $charges,
        array $clubs = [],
        string $tab = 'club-invoices',
        int|string|null $club_id = null,
        int|string|null $charge_id = null,
        string $status = 'open',
        ?string $currency_code = null,
        string $date_format = 'Y-m-d',
        Error_Bag $errors = new Error_Bag()
    ): Finances_Invoices_Page_View_Model {
        $currency      = ! empty( $currency_code ) ? $currency_code : ( class_exists( Util::class ) ? Util::get_currency_code() : 'GBP' );
        $items         = [];
        $total_amount  = 0.0;
        $billable_type = 'club-invoices' === $tab ? __( 'Club', 'racketmanager' ) : __( 'Player', 'racketmanager' );

        foreach ( $invoices as $inv ) {
            $id            = is_object( $inv ) && isset( $inv->id ) ? (int) $inv->id : 0;
            $inv_number    = is_object( $inv ) && isset( $inv->invoice_number ) ? $inv->invoice_number : '';
            $raw_name      = is_object( $inv ) && isset( $inv->charge_name ) ? (string) $inv->charge_name : '';
            $charge_name   = '' !== $raw_name ? ucfirst( $raw_name ) : '';
            $billable_name = is_object( $inv ) && isset( $inv->billable_name ) ? (string) $inv->billable_name : '';
            $raw_amt       = is_object( $inv ) && isset( $inv->amount ) ? $inv->amount : null;
            $amt_val       = null !== $raw_amt && '' !== $raw_amt ? (float) $raw_amt : null;
            $inv_status    = is_object( $inv ) && isset( $inv->status ) ? (string) $inv->status : '';
            $date_due      = is_object( $inv ) && isset( $inv->date_due ) ? (string) $inv->date_due : '';
            $due_formatted = ! empty( $date_due ) && function_exists( 'mysql2date' ) ? (string) mysql2date( $date_format, $date_due ) : $date_due;

            if ( null !== $amt_val ) {
                $total_amount += $amt_val;
            }

            $items[] = new Finances_Invoice_Item_View_Model(
                id: $id,
                invoice_number: $inv_number,
                charge_name: $charge_name,
                billable_name: $billable_name,
                amount: $amt_val,
                amount_formatted: $this->format_currency( $amt_val, $currency ),
                status: $inv_status,
                date_due: $date_due,
                date_due_formatted: $due_formatted,
                edit_url: '/wp-admin/admin.php?page=racketmanager-finances&view=invoice&invoice=' . $id
            );
        }

        $total_formatted = $this->format_currency( $total_amount, $currency );

        return new Finances_Invoices_Page_View_Model(
            invoices: $items,
            charges: $charges,
            clubs: $clubs,
            racketmanager_tab: $tab,
            club_id: $club_id,
            charge_id: $charge_id,
            status: $status,
            billable_type: $billable_type,
            total_amount_formatted: $total_formatted,
            total_amount: $total_amount,
            errors: $errors
        );
    }

    /**
     * Present charge detail for admin/finances/charge.php template.
     *
     * @param object|null           $charge
     * @param array<int, object>    $competitions
     * @param array<int, object>    $seasons
     * @param array<int, object>    $club_charges
     * @param bool                  $edit
     * @param string                $form_title
     * @param string                $form_action
     * @param Error_Bag             $errors
     * @param string|null           $currency_code
     * @return Finances_Charge_Page_View_Model
     */
    public function present_admin_charge_detail(
        ?object $charge,
        array $competitions,
        array $seasons,
        array $club_charges = [],
        bool $edit = false,
        string $form_title = '',
        string $form_action = '',
        Error_Bag $errors = new Error_Bag(),
        ?string $currency_code = null
    ): Finances_Charge_Page_View_Model {
        $currency        = ! empty( $currency_code ) ? $currency_code : ( class_exists( Util::class ) ? Util::get_currency_code() : 'GBP' );
        $presented_clubs = [];

        foreach ( $club_charges as $club_charge ) {
            $name      = is_object( $club_charge ) && isset( $club_charge->name ) ? (string) $club_charge->name : '';
            $num_teams = is_object( $club_charge ) && isset( $club_charge->num_teams ) ? $club_charge->num_teams : 0;
            $raw_fee   = is_object( $club_charge ) && isset( $club_charge->fee ) ? $club_charge->fee : null;
            $fee_val   = null !== $raw_fee && '' !== $raw_fee ? (float) $raw_fee : null;
            $fee_fmt   = $this->format_currency( $fee_val, $currency );

            $presented_events = [];
            if ( is_object( $club_charge ) && isset( $club_charge->events ) && is_iterable( $club_charge->events ) ) {
                foreach ( $club_charge->events as $event ) {
                    $ev_type    = is_object( $event ) && isset( $event->type ) ? $event->type : '';
                    $ev_type_nm = class_exists( Util_Lookup::class ) ? Util_Lookup::get_event_type( $ev_type ) : (string) $ev_type;
                    $ev_count   = is_object( $event ) && isset( $event->count ) ? $event->count : 0;
                    $ev_fee     = is_object( $event ) && isset( $event->fee ) ? $event->fee : null;
                    $ev_fee_val = null !== $ev_fee && '' !== $ev_fee ? (float) $ev_fee : null;
                    $ev_fee_fmt = $this->format_currency( $ev_fee_val, $currency );

                    $presented_events[] = new Finances_Club_Charge_Event_Item_View_Model(
                        type: $ev_type,
                        type_name: $ev_type_nm,
                        count: $ev_count,
                        fee: $ev_fee_val,
                        fee_formatted: $ev_fee_fmt
                    );
                }
            }

            $presented_clubs[] = new Finances_Club_Charge_Item_View_Model(
                name: $name,
                num_teams: $num_teams,
                fee: $fee_val,
                fee_formatted: $fee_fmt,
                events: $presented_events
            );
        }

        return new Finances_Charge_Page_View_Model(
            charge: $charge,
            competitions: $competitions,
            seasons: $seasons,
            club_charges: $presented_clubs,
            edit: $edit,
            form_title: $form_title,
            form_action: $form_action,
            errors: $errors
        );
    }

    /**
     * Present invoice edit page for admin/finances/invoice.php template.
     *
     * @param object    $invoice
     * @param string    $invoice_view
     * @param Error_Bag $errors
     * @return Finances_Invoice_Page_View_Model
     */
    public function present_admin_invoice_page(
        object $invoice,
        string $invoice_view = '',
        Error_Bag $errors = new Error_Bag()
    ): Finances_Invoice_Page_View_Model {
        $billable_type = '';
        if ( isset( $invoice->invoice ) && is_object( $invoice->invoice ) && isset( $invoice->invoice->billable_type ) ) {
            $billable_type = (string) $invoice->invoice->billable_type;
        } elseif ( isset( $invoice->billable_type ) ) {
            $billable_type = (string) $invoice->billable_type;
        }

        if ( 'club' === $billable_type ) {
            $view       = 'club-invoices';
            $breadcrumb = __( 'Club invoices', 'racketmanager' );
        } else {
            $view       = 'player-invoices';
            $breadcrumb = __( 'Player invoices', 'racketmanager' );
        }

        $inv_status = '';
        if ( isset( $invoice->invoice ) && is_object( $invoice->invoice ) && method_exists( $invoice->invoice, 'get_status' ) ) {
            $inv_status = (string) $invoice->invoice->get_status();
        } elseif ( isset( $invoice->status ) ) {
            $inv_status = (string) $invoice->status;
        }

        $inv_number = '';
        if ( isset( $invoice->invoice ) && is_object( $invoice->invoice ) && method_exists( $invoice->invoice, 'get_invoice_number' ) ) {
            $inv_number = (string) $invoice->invoice->get_invoice_number();
        } elseif ( isset( $invoice->invoice_number ) ) {
            $inv_number = (string) $invoice->invoice_number;
        }

        $purchase_order = '';
        if ( isset( $invoice->invoice ) && is_object( $invoice->invoice ) && method_exists( $invoice->invoice, 'get_purchase_order' ) ) {
            $purchase_order = (string) $invoice->invoice->get_purchase_order();
        } elseif ( isset( $invoice->purchase_order ) ) {
            $purchase_order = (string) $invoice->purchase_order;
        }

        $invoice_id = null;
        if ( isset( $invoice->invoice ) && is_object( $invoice->invoice ) && method_exists( $invoice->invoice, 'get_id' ) ) {
            $invoice_id = (int) $invoice->invoice->get_id();
        } elseif ( isset( $invoice->id ) ) {
            $invoice_id = (int) $invoice->id;
        }

        $charge_name   = isset( $invoice->charge_name ) ? (string) $invoice->charge_name : '';
        $billable_name = isset( $invoice->billable_name ) ? (string) $invoice->billable_name : '';

        $title    = __( 'Invoice', 'racketmanager' ) . ' ' . $inv_number . ' (' . $inv_status . ')';
        $subtitle = ucfirst( $charge_name ) . ' - ' . $billable_name;

        return new Finances_Invoice_Page_View_Model(
            invoice: $invoice,
            invoice_view: $invoice_view,
            errors: $errors,
            view: $view,
            breadcrumb: $breadcrumb,
            title: $title,
            subtitle: $subtitle,
            invoice_status: $inv_status,
            purchase_order: $purchase_order,
            invoice_id: $invoice_id
        );
    }

    /**
     * Format a currency amount according to current locale or NumberFormatter.
     */
    public function format_currency( float|int|string|Money|null $amount, ?string $currency_code = null ): string {
        if ( $amount instanceof Money ) {
            $currency_code = $currency_code ?? $amount->get_currency()->value;
            $amount        = $amount->get_amount();
        }
        if ( null === $amount || '' === $amount ) {
            $amount = 0.0;
        }
        $amount_float = (float) $amount;
        $code         = ! empty( $currency_code ) ? $currency_code : ( class_exists( Util::class ) ? Util::get_currency_code() : 'GBP' );

        $currency_fmt = class_exists( Util::class ) ? Util::get_currency_format() : null;
        if ( $currency_fmt ) {
            $formatted = numfmt_format_currency( $currency_fmt, $amount_float, $code );
            if ( false !== $formatted ) {
                return $formatted;
            }
        }

        $symbol = match ( $code ) {
            'EUR' => '€',
            'USD' => '$',
            default => '£',
        };

        return $symbol . number_format( $amount_float, 2 );
    }
}
