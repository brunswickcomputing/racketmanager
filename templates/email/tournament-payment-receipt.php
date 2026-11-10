<?php
/**
 * Tournament payment receipt email body
 *
 * @package Racketmanager/Templates/Email
 */

namespace Racketmanager;

/** @var string $tournament_name */
/** @var object $player */
/** @var string $tournament_link */
/** @var object $invoice */
/** @var object|null $tournament */
/** @var string $action_url */
/** @var string $title */
/** @var string $paragraph */
/** @var string $hr */
/** @var string $salutation */
/** @var string $contact */
/** @var string $closing */
/** @var string $contact_email */

$email_subject = __( 'Payment Receipt', 'racketmanager' ) . ' - ' . ucfirst( $tournament_name );
require 'email-header.php';
$title_text  = __( 'Payment confirmation', 'racketmanager' );
$title_level = '1';
require $title;
$salutation_link = $player->fullname ?? $player->display_name;
require $salutation;
/* translators: $s: tournament link */
$paragraph_text  = sprintf( __( 'Thank you for your payment for the %s tournament. Your payment has been received and confirmed.', 'racketmanager' ), $tournament_link );
$paragraph_imbed = true;
require $paragraph;
$paragraph_imbed = false;
$paragraph_text  = __( 'Click the button below to view your invoice.', 'racketmanager' );
require $paragraph;
$action_link_text = __( 'View invoice', 'racketmanager' );
require 'components/action-link.php';
require $hr;
$title_text  = __( 'Payment Details', 'racketmanager' );
$title_level = '2';
require $title;
?>
<div style="font-size: 16px; color: #000; background-color: #fff; padding: 0 20px;">
    <table align="center" style="display: block;" role="presentation" cellspacing="0" cellpadding="0">
        <tbody>
        <tr>
            <td role="presentation" cellspacing="0" cellpadding="0" bgcolor="#fff">
                <table style="width: 100%; border-collapse: collapse;" role="presentation" cellspacing="0" cellpadding="0">
                    <tbody>
                    <tr>
                        <td style="font-weight: 400; min-width: 5px; width: 600px; height: 0;" role="presentation" cellspacing="0" cellpadding="0" align="left" bgcolor="#fff" valign="top">
                            <table width="100%" style="height: 100%; text-align: left; margin-left: 10px;" role="presentation" cellspacing="0" cellpadding="0">
                                <tbody>
                                <tr style="line-height: 22px;">
                                    <td style="width: 150px; font-size: 14px; font-weight: 500;"><?php esc_html_e( 'Tournament', 'racketmanager' ); ?>:</td>
                                    <td><?php echo esc_html( $tournament_name ); ?></td>
                                </tr>
                                <tr style="line-height: 22px;">
                                    <td style="width: 150px; font-size: 14px; font-weight: 500;"><?php esc_html_e( 'Receipt / Invoice', 'racketmanager' ); ?>:</td>
                                    <td>#<?php echo esc_html( ! empty( $invoice->invoice_number ) ? $invoice->invoice_number : ( $invoice->id ?? '' ) ); ?></td>
                                </tr>
                                <?php if ( ! empty( $invoice->payment_reference ) ) : ?>
                                <tr style="line-height: 22px;">
                                    <td style="width: 150px; font-size: 14px; font-weight: 500;"><?php esc_html_e( 'Payment Reference', 'racketmanager' ); ?>:</td>
                                    <td><?php echo esc_html( $invoice->payment_reference ); ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr style="line-height: 22px;">
                                    <td style="width: 150px; font-size: 14px; font-weight: 500;"><?php esc_html_e( 'Date', 'racketmanager' ); ?>:</td>
                                    <td><?php echo esc_html( ! empty( $invoice->date ) && function_exists( 'mysql2date' ) ? mysql2date( get_option( 'date_format', 'd/m/Y' ), $invoice->date ) : gmdate( 'd/m/Y' ) ); ?></td>
                                </tr>
                                <tr style="line-height: 22px;">
                                    <td style="width: 150px; font-size: 14px; font-weight: 500;"><?php esc_html_e( 'Amount Paid', 'racketmanager' ); ?>:</td>
                                    <td><strong><?php if ( function_exists( '\Racketmanager\the_currency_amount' ) ) { the_currency_amount( $invoice->amount ); } elseif ( function_exists( 'the_currency_amount' ) ) { the_currency_amount( $invoice->amount ); } else { echo esc_html( $invoice->amount ); } ?></strong></td>
                                </tr>
                                <tr style="line-height: 22px;">
                                    <td style="width: 150px; font-size: 14px; font-weight: 500;"><?php esc_html_e( 'Status', 'racketmanager' ); ?>:</td>
                                    <td><span style="color: #006800; font-weight: 600;"><?php esc_html_e( 'Paid', 'racketmanager' ); ?></span></td>
                                </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </td>
        </tr>
        </tbody>
    </table>
</div>
<?php
require $hr;
require $paragraph;
if ( ! empty( $contact_email ) ) {
    require $contact;
}
require $closing;
require 'email-footer.php';
