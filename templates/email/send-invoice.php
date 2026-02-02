<?php
/**
 * Template for sending invoice by email
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var bool   $resend */
/** @var object $invoice */
/** @var string $salutation */
/** @var string $contact */
/** @var string $closing */
/** @var string $addressee */
require 'email-header.php';
?>
            <?php $salutation_link = $addressee; ?>
            <?php require $salutation; ?>
            <!-- introduction -->
            <div style="font-size: 16px; color: #000; background-color: #fff; padding: 0 20px;">
                <table align="center" style="display: block;" role="presentation" cellspacing="0" cellpadding="0">
                    <tbody>
                        <tr>
                            <td role="presentation" cellspacing="0" cellpadding="0" bgcolor="#fff">
                                <table style="width: 100%; border-collapse: collapse;" role="presentation" cellspacing="0" cellpadding="0">
                                    <tbody>
                                        <tr>
                                            <td style="font-weight: 400; min-width: 5px; width: 600px; height: 0;" role="presentation" cellspacing="0" cellpadding="0" align="left" bgcolor="#fff" valign="top">
                                                <table width="100%" style="height: 100%;" role="presentation" cellspacing="0" cellpadding="0">
                                                    <tbody>
                                                        <tr>
                                                            <td style="min-width: 5px; font-weight: 400;" role="presentation" cellspacing="0" cellpadding="0" align="left" bgcolor="#fff" valign="top">
                                                                <div style="font-size: 16px; color: #000; background-color: transparent; margin: 10px;">
                                                                    <p style="line-height: 1.25; mso-line-height-rule: at-least; margin: 0 0 20px; padding: 0;">
                                                                        Please find attached
                                                                        <?php
                                                                        if ( $resend ) {
                                                                            echo ' another copy of ';
                                                                        }
                                                                        ?>
                                                                        your invoice for the <?php echo esc_html( ucfirst( $invoice->charge_name ) ); ?>.
                                                                    </p>
                                                                    <p style="line-height: 1.25; mso-line-height-rule: at-least; margin: 0 0 20px; padding: 0;">
                                                                        Could you please check your details and notify me of errors?
                                                                    </p>
                                                                </div>
                                                            </td>
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
            <?php $action_link_text = __( 'View invoice', 'racketmanager' ); ?>
            <?php require 'components/action-link.php'; ?>
            <?php
            if ( ! empty( $from_email ) ) {
                $contact_email = $from_email;
                require $contact;
            }
            ?>
            <?php require $closing; ?>
            <?php require 'components/link-text.php'; ?>
<?php
require 'email-footer.php';
