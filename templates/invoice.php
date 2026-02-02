<?php
/**
 * Invoice template
 *
 * @package Racketmanager
 */

namespace Racketmanager;

use Racketmanager\Util\Util_Lookup;

/** @var array  $billing */
/** @var object $invoice */
/** @var string $organisation_name */
/** @var object $target */
global $racketmanager;
if ( ! isset( $invoice_number ) ) {
    $invoice_number = $billing['invoiceNumber']; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
}
?>
<?php
if ( is_user_logged_in() ) {
    ?>
    <style>
    .invoice-item-detail {
        width: 40%;
        display: inline;
    }
    .invoice-item-quantity {
        width: 20%;
        text-align: right;
    }
    .invoice-item-unit-price {
        width: 20%;
        text-align: right;
    }
    .invoice-item-net-price {
        width: 20%;
        text-align: right;
    }
    div#invoice-item {
        display: flex;
        line-height: 2em;
    }
    .invoice-total-desc {
        width: 80%;
        text-align: right;
    }
    div#invoice-totals {
        display: flex;
        font-weight: bold;
    }
    #invoice-amount #header-row {
        display: flex;
        font-weight: bold;
    }
    div#invoice-info {
        margin: .7cm 0 20px;
        float: right;
        text-align: right;
        width: 50%;
    }
    #invoice-header {
        border-bottom: 1px solid #006800;
        margin-top: 1.5cm;
        overflow: hidden;
        padding-bottom: 10px;
    }
    div#invoice {
        width: 660px;
    }
    div#client-details {
        margin: 0.7cm 0 20px;
        float: left;
        width: 50%;
    }
    #invoice h2 {
        font-size: 14pt;
        margin: 10px 0;
        font-weight: normal;
        text-transform: uppercase;
    }
    #payment-details strong {
        float: left;
        font-weight: normal;
        width: 12em;
    }
    #invoice #payment-details {
        font-size: 10pt;
        line-height: 14pt;

    }
    #invoice-info h2 {
        font-weight: normal;
        margin: 0;
    }
    #invoice-header h2, #client-details h2, #invoice-item h2 {
        margin: 0;
    }
    #invoice #invoice-info h2, #invoice #company-address div.email, #invoice #client-details h2, #invoice #invoice-header h2, #invoice #payment-details h2, #invoice #invoice-item h2 {
        color: #006800;
    }
    #invoice {
        border-left: 60px solid #006800;
        padding: 0 1cm 1cm;
    }
    .page-style {
        background-color: #fff;
        border: solid 1px #b4bcc1;
        box-shadow: 0 1px 2px 0 rgb(0 0 0 / 20%);
        margin: 0 auto 40px;
        max-width: 764px;
        padding: 40px;
        position: relative;
    }
    #invoice-header #company-address {
        font-size: 11pt;
        line-height: 14pt;
        text-align: right;
    }
    #invoice #client-details, #invoice-info p {
        font-size: 10pt;
        line-height: 14pt;
    }
    #invoice-amount {
        font-size: 10pt;
        line-height: 14pt;
        clear: both;
    }
    #invoice-amount #header-row, #invoice-totals {
        background: #006800;
        color: #FFFFFF;
        padding: 3px;
    }
    .invoice-due {
        font-style: italic;
    }
    </style>
    <!-- START MAIN CONTENT AREA -->
    <div id="invoice" class="page-style">
        <div id="invoice-header">
            <div id="company-address">
                <div class="org"><h2><?php echo esc_html( $organisation_name ); ?></h2></div>
                <div class="address">
                    <div class="street-address"><?php echo str_replace( ',', '<br />', $billing['billingAddress'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
                    <div class="email"><?php echo esc_html( $billing['billingEmail'] ); ?></div>
                    <div class="url"><?php echo esc_html( trim( str_replace( array( 'https://' ), '', $racketmanager->site_url ), '/' ) ); ?></div>
                    <div class="tel"><?php echo esc_html( $billing['billingTelephone'] ); ?></div>
                </div>
            </div>
        </div>
        <div id="invoice-info">
            <div class="invoice-no">
                <h2><?php esc_html_e( 'Invoice', 'racketmanager' ); ?>&nbsp;<?php echo esc_html( $invoice_number ); ?></h2>
            </div>
            <div class="invoice-date"><?php echo esc_html( mysql2date( $racketmanager->date_format, $invoice->date ) ); ?></div>
            <div class="invoice-due">
            <?php
            if ( $invoice->date_due === $invoice->date ) {
                esc_html_e( 'Payment due on receipt', 'racketmanager' );
            } else {
                echo esc_html( __( 'Payment Due', 'racketmanager' ) . ': ' . mysql2date( $racketmanager->date_format, $invoice->date_due ) );
            }
            if ( $invoice->purchase_order ) {
                ?>
                <div class="text-end"><?php echo esc_html( __( 'Purchase Order', 'racketmanager' ) . ': ' . strtoupper( $invoice->purchase_order ) ); ?></div>
                <?php
            }
            if ( 'paid' === $invoice->status ) {
                ?>
                <div class="text-end"><?php echo esc_html( strtoupper( $invoice->status ) ); ?></div>
                <?php
            }
            ?>
            </div>
        </div>
        <div id="client-details">
            <div class="org"><h2><?php echo esc_html( $invoice->billable_name ); ?></h2></div>
            <?php
            if ( ! empty( $invoice->billable_address ) ) {
                ?>
                <div class="address">
                    <div class="street-address"><?php echo str_replace( ',', '<br />', $invoice->billable_address ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
                </div>
                <?php
            }
            ?>
        </div>
        <div id="invoice-amount">
            <div id="header-row">
                <div class="invoice-item-detail"><?php echo esc_html( ucwords( __( 'description', 'racketmanager' ) ) ); ?></div>
                <div class="invoice-item-quantity"><?php echo esc_html( ucwords( __( 'quantity', 'racketmanager' ) ) ); ?></div>
                <div class="invoice-item-unit-price"><?php echo esc_html( ucwords( __( 'unit price', 'racketmanager' ) ) ); ?></div>
                <div class="invoice-item-net-price"><?php echo esc_html( ucwords( __( 'net total', 'racketmanager' ) ) ); ?></div>
            </div>
            <div id="invoice-items">
                <div id="invoice-item">
                    <h2 class="invoice-item-detail"><?php echo esc_html( ucfirst( $invoice->charge_name ) ); ?></h2>
                </div>
                <?php
                if ( '0.00' !== $invoice->details->fee_competition ) {
                    ?>
                    <div id="invoice-item">
                        <div class="invoice-item-detail"><?php echo esc_html( ucwords( __( 'Competition entry fee', 'racketmanager' ) ) ); ?></div>
                        <div class="invoice-item-quantity"></div>
                        <div class="invoice-item-unit-price"><?php the_currency_amount( $invoice->details->fee_competition ); ?></div>
                        <div class="invoice-item-net-price"><?php the_currency_amount( $invoice->details->fee_competition ); ?></div>
                    </div>
                    <?php
                }
                ?>
                <?php
                foreach ( $invoice->details->events as $racketmanager_event ) {
                    ?>
                    <div id="invoice-item">
                        <div class="invoice-item-detail"><?php echo esc_html( Util_Lookup::get_event_type( $racketmanager_event->type ) ); ?></div>
                        <div class="invoice-item-quantity"><?php echo esc_html( $racketmanager_event->count ); ?></div>
                        <div class="invoice-item-unit-price"><?php the_currency_amount( $invoice->details->fee_events ); ?></div>
                        <div class="invoice-item-net-price"><?php the_currency_amount( $racketmanager_event->fee ); ?></div>
                    </div>
                    <?php
                }
                ?>
            </div>
            <div id="invoice-totals">
                <div class="invoice-total-desc">Total</div>
                <div class="invoice-item-net-price"><?php the_currency_amount( $invoice->details->fee ); ?></div>
            </div>
            <?php
            if ( ! empty( $invoice->details->paid ) ) {
                $amount_due = $invoice->details->fee - $invoice->details->paid;
                ?>
                <div id="invoice-totals">
                    <div class="invoice-total-desc">Paid</div>
                    <div class="invoice-item-net-price"><?php the_currency_amount( $invoice->details->paid ); ?></div>
                </div>
                <div id="invoice-totals">
                <div class="invoice-total-desc"><?php esc_html_e( 'Due', 'racketmanager' ); ?></div>
                    <div class="invoice-item-net-price"><?php the_currency_amount( $amount_due ); ?></div>
                </div>
                <?php
            }
            ?>
            <div id="payment-details">
                <h2><?php esc_html_e( 'Payment Details', 'racketmanager' ); ?></h2>
                <div id="bank-name">
                    <?php echo esc_html( $billing['bankName'] ); ?>
                </div>
                <div id="sort-code">
                    <strong><?php esc_html_e( 'Sort Code', 'racketmanager' ); ?></strong>
                    <?php echo esc_html( $billing['sortCode'] ); ?>
                </div>
                <div id="account-number">
                    <strong><?php esc_html_e( 'Account Number', 'racketmanager' ); ?></strong>
                    <?php echo esc_html( $billing['accountNumber'] ); ?>
                </div>
                <div id="payment-reference">
                    <strong><?php esc_html_e( 'Payment Reference', 'racketmanager' ); ?></strong>
                    <?php echo esc_html( $invoice_number ); ?>
                </div>
            </div>
        </div>
    </div>
    <?php
} else {
    ?>
    <p class="contact-login-msg">You need to <a href="<?php echo esc_html( wp_login_url() ); ?>">login</a> to view invoices</p>
    <?php
}
?>
