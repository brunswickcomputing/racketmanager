<?php
/**
 * Tournament entry email body
 *
 * @package Racketmanager/Templates/Email
 */

namespace Racketmanager;

/** @var string $tournament_name */
/** @var object $player */
/** @var string $tournament_link */
/** @var array $tournament_entries */
/** @var string $title */
/** @var string $paragraph */
/** @var string $hr */
/** @var string $salutation */
/** @var string $contact */
/** @var string $closing */
$email_subject = __( 'Tournament Entry', 'racketmanager' ) . ' - ' . ucfirst( $tournament_name );
require 'email-header.php';
$title_text  = __( 'Entry confirmation', 'racketmanager' );
$title_level = '1';
require $title;
$salutation_link = $player->fullname;
require $salutation;
/* translators: $s: tournament link */
$paragraph_text  = sprintf( __( 'Thank you for your entry for the %s tournament. You will find confirmation of your entry below.', 'racketmanager' ), $tournament_link );
$paragraph_imbed = true;
require $paragraph;
$paragraph_imbed = false;
$paragraph_text = __( 'Click the following button if you want to view or change your entry if necessary.', 'racketmanager' );
require $paragraph;
$action_link_text = __( 'View entry', 'racketmanager' );
require 'components/action-link.php';
require $hr;
$title_text  = __( 'Entry Details', 'racketmanager' );
$title_level = '2';
require $title;
$title_text  = __( 'Personal Details', 'racketmanager' );
$title_level = '3';
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
                                    <td style="width: 150px; font-size: 14px; font-weight: 500;"><?php esc_html_e( 'First name', 'racketmanager' ); ?>:</td>
                                    <td><?php echo esc_html( $player->firstname ); ?></td>
                                </tr>
                                <tr style="line-height: 22px;">
                                    <td style="width: 150px; font-size: 14px; font-weight: 500;"><?php esc_html_e( 'Last name', 'racketmanager' ); ?>:</td>
                                    <td><?php echo esc_html( $player->surname ); ?></td>
                                </tr>
                                <tr style="line-height: 22px;">
                                    <td style="width: 150px; font-size: 14px; font-weight: 500;"><?php esc_html_e( 'Telephone', 'racketmanager' ); ?>:</td>
                                    <td><?php echo esc_html( $player->contactno ); ?></td>
                                </tr>
                                <tr style="line-height: 22px;">
                                    <td style="width: 150px; font-size: 14px; font-weight: 500;"><?php esc_html_e( 'LTA Number', 'racketmanager' ); ?>:</td>
                                    <td><?php echo esc_html( $player->btm ); ?></td>
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
$title_text  = __( 'Events', 'racketmanager' );
$title_level = '3';
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
                                <?php
                                $t = 1;
                                foreach ( $tournament_entries as $tournament_entry ) {
                                    ?>
                                    <tr style="line-height: 22px;">
                                        <td style="width: 150px; font-size: 14px; font-weight: 500; vertical-align: top;"><?php esc_html_e( 'Event', 'racketmanager' ); ?> <?php echo esc_html( $t ); ?>:</td>
                                        <td>
                                            <?php echo esc_html( $tournament_entry['event_name'] ); ?>
                                            <?php
                                            if ( isset( $tournament_entry['partner'] ) ) {
                                                ?>
                                                <br>
                                                <?php echo esc_html( $tournament_entry['partner'] ); ?>
                                                <?php
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                    <?php
                                    ++ $t;
                                }
                                ?>
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
if ( ! empty( $comments ) ) {
    require $hr;
    $title_text  = __( 'Additional comments', 'racketmanager' );
    $title_level = '3';
    require $title;
    $paragraph_text = $comments;
    require $paragraph;
}
require $hr;
$paragraph_text = __( 'You will be notified when the draws have taken place.', 'racketmanager' );
require $paragraph;
if ( ! empty( $contact_email ) ) {
    require $contact;
}
require $closing;
require 'email-footer.php';
