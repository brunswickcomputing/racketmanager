<?php
/**
 * Template for match result pending notification email
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var string $organisation */
/** @var int    $time_period */
/** @var object $match */
/** @var string $contact */
/** @var string $closing */
/** @var array $vars */
/** @var object|null $vm */
$organisation     = $vars['organisation'] ?? $vm->organisation ?? $organisation ?? '';
$time_period      = $vars['time_period'] ?? $vm->time_period ?? $time_period ?? 0;
$match            = $vars['match'] ?? $vm->match ?? $match ?? null;
$action_url       = $vars['action_url'] ?? $vm->action_url ?? $action_url ?? '';
$from_email       = $vars['from_email'] ?? $vm->from_email ?? $from_email ?? '';
$contact_email    = $vars['contact_email'] ?? $vm->contact_email ?? $contact_email ?? $from_email;

$competition_name = $vars['competition_name'] ?? $vm->competition_name ?? $competition_name ?? ( $match->league->title ?? '' );
$match_date       = $vars['match_date'] ?? $vm->match_date ?? $match_date ?? ( $match->match_date ?? '' );
$email_subject    = $vars['email_subject'] ?? $vm->email_subject ?? $email_subject ?? ( __( 'Match Result Pending', 'racketmanager' ) . ' - ' . $competition_name . ' - ' . $organisation );
if ( $time_period ) {
    $days_period = $time_period / 24;
} else {
    $days_period = 1;
}
?>
<?php require 'email-header.php'; ?>
            <?php require 'components/match-heading.php'; ?>
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
                                                                    <?php
                                                                    $message_detail = 'The result of this match is outstanding';
                                                                    if ( $time_period ) {
                                                                        $message_detail .= ' more than ' . $days_period . ' days after the match was due to be played';
                                                                    }
                                                                    $message_detail .= '.';
                                                                    ?>
                                                                    <p><?php echo esc_html( $message_detail ); ?></p>
                                                                    <p>Please provide the result as soon as possible.</p>
                                                                    <?php
                                                                    if ( $time_period ) {
                                                                        echo '<p>Failure to do so may result in the elimination of both teams.</p>';
                                                                    }
                                                                    ?>
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
            <?php $action_link_text = __( 'Enter result', 'racketmanager' ); ?>
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
