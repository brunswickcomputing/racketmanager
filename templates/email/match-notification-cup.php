<?php
/**
 * Template for email notification for cup match
 *
 * @package Racketmanager/Templates/Email
 */

namespace Racketmanager;

global $racketmanager;
/** @var string $competition */
/** @var object $match */
/** @var string $round */
/** @var string $cup_link */
/** @var array  $teams */
/** @var string $rules_link */
/** @var string $paragraph */
/** @var string $contact */
/** @var string $closing */
/** @var string $title */
/** @var string $hr */
$competition_name = $competition;
$match_date    = $match->match_date;
$match_time    = mysql2date( $racketmanager->time_format, $match->date );
$email_subject = __( 'Match Details', 'racketmanager' ) . ' - ' . $round . ' - ' . $competition_name;
require 'email-header.php';
$title_text = sprintf(
    /* translators: %1$s: competition name %2$s: round name */
    __( '%1$s %2$s Match Confirmation', 'racketmanager' ),
    $match->league->title,
    $round,
);
$title_level = '1';
$title_align = 'center';
require $title;
$title_align = '';
$paragraph_text = sprintf(
    /* translators: $s: cup link */
    __( 'Please find below details of your next match in the %s.', 'racketmanager' ),
    $cup_link,
);
$paragraph_imbed = true;
require $paragraph;
$paragraph_imbed = false;
$paragraph_text = __( 'Click the following button to view the match.', 'racketmanager' );
require $paragraph;
$action_link_text = __( 'View match', 'racketmanager' );
require 'components/action-link.php';
require $hr;
$title_text  = __( 'Match Details', 'racketmanager' );
$title_level = '2';
require $title;
$paragraph_text = __( 'Match to be played on', 'racketmanager' ) . ' ' . $match_date . ' ' . __( 'at', 'racketmanager' ) . ' ' . $match_time . '.';
require $paragraph;
$opponents = array( 'home', 'away' );
foreach ( $opponents as $opponent ) {
    $team        = $teams[ $opponent ];
    $title_text  = $team->title . ' - ' . $team->name;
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
                                                    <td style="width: 150px; font-size: 14px; font-weight: 500;"><?php esc_html_e( 'Captain', 'racketmanager' ); ?>:</td>
                                                    <td><?php echo esc_html( $team->captain ); ?></td>
                                                </tr>
                                                <tr style="line-height: 22px;">
                                                    <td style="width: 150px; font-size: 14px; font-weight: 500;"><?php esc_html_e( 'Telephone', 'racketmanager' ); ?>:</td>
                                                    <td><?php echo esc_html( $team->captain_tel ); ?></td>
                                                </tr>
                                                <tr style="line-height: 22px;">
                                                    <td style="width: 150px; font-size: 14px; font-weight: 500;"><?php esc_html_e( 'Email', 'racketmanager' ); ?>:</td>
                                                    <td><?php echo esc_html( $team->captain_email ); ?></td>
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
require $hr;
$title_text  = __( 'Rules', 'racketmanager' );
$title_level = '2';
require $title;
$paragraph_text  = __( 'The rules for the cup can be found', 'racketmanager' ) . ' <a href="' . $rules_link . '">' . __( 'here', 'racketmanager' ) . '</a>.';
$paragraph_imbed = true;
require $paragraph;
$paragraph_imbed = false;
require $hr;
if ( ! empty( $email_from ) ) {
    $contact_email = $email_from;
    require $contact;
}
require $closing;
require 'email-footer.php';
