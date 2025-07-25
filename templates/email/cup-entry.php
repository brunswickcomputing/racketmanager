<?php
/**
 * Cup entry email
 *
 * @package Racketmanager/Templates/Email
 */

namespace Racketmanager;

/** @var string $competition_name */
/** @var string $season */
/** @var string $club */
/** @var array  $cup_entries */
/** @var string $salutation */
/** @var string $paragraph */
/** @var string $contact */
/** @var string $closing */
/** @var string $title */
/** @var string $hr */
$email_subject = __( 'Cup Entry', 'racketmanager' ) . ' - ' . ucfirst( $competition_name ) . ' - ' . $season;
require 'email-header.php';
$title_text  = __( 'Entry confirmation', 'racketmanager' );
$title_level = '1';
require $title;
$salutation_link = $club;
require $salutation;
/* translators: $s: competition name */
$paragraph_text = sprintf( __( 'Thank you for your entry for the %s. You will find confirmation of your entry below.', 'racketmanager' ), $competition_name );
require $paragraph;
require $hr;
$title_text  = __( 'Entry Details', 'racketmanager' );
$title_level = '2';
require $title;
$title_text  = __( 'Events', 'racketmanager' );
$title_level = '3';
require $title;
foreach ( $cup_entries as $event_entry ) {
$title_text  = $event_entry['event'];
$title_level = '4';
require $title;
?>
<div style="font-size: 14px; color: #000; background-color: #fff; padding: 0 20px;">
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
                                                <td style="width: 40%; font-size: 14px; font-weight: 500; vertical-align: top;"><h5 style="font-size:14px; display:inline;"><?php echo esc_html( $event_entry['teamName'] ); ?></h5>:</td>
                                                <td>
                                                    <?php echo esc_html( $event_entry['matchday'] ); ?> <?php esc_html_e( 'at', 'racketmanager' ); ?> <?php echo esc_html( $event_entry['matchtime'] ); ?>
                                                    <br>
                                                    <?php echo esc_html( $event_entry['captain'] ); ?>
                                                    <?php
                                                    if ( $event_entry['contactno'] > '' ) {
                                                        ?>
                                                        <br>
                                                        <?php echo esc_html( $event_entry['contactno'] ); ?>
                                                        <?php
                                                    }
                                                    ?>
                                                    <?php
                                                    if ( $event_entry['contactemail'] > '' ) {
                                                        ?>
                                                        <br>
                                                        <?php echo esc_html( $event_entry['contactemail'] ); ?>
                                                        <?php
                                                    }
                                                    ?>
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
<?php
}
if ( ! empty( $comments ) ) {
require $hr;
$title_text  = __( 'Additional comments', 'racketmanager' );
$title_level = '3';
require $title;
$paragraph_text = $comments;
require $paragraph;
}
require $hr;
$paragraph_text = __( 'Captains will be notified when the draws have taken place.', 'racketmanager' );
require $paragraph;
if ( ! empty( $contact_email ) ) {
    require $contact;
}
?>
<?php require $closing;
require 'email-footer.php';
