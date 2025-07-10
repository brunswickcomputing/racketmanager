<?php
/**
 * Template for result notification email
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var object $match */
/** @var string $time_period */
/** @var bool   $confirmation_required */
$competition_name = $match->league->title;
$match_date       = $match->match_date;
$email_subject    = __( 'Match Result', 'racketmanager' ) . ' - ' . $competition_name;
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
                                                                    if ( ! empty( $override ) ) {
                                                                        $message_detail = 'The approval of this result was outstanding';
                                                                        ?>
                                                                        <?php
                                                                        if ( $time_period ) {
                                                                            $message_detail .= ' for more than ' . $time_period . ' hours after the result was entered';
                                                                        }
                                                                        $message_detail .= '.';
                                                                        ?>
                                                                        <p><?php echo esc_html( $message_detail ); ?></p>
                                                                        <p>The entered result of this match has therefore been confirmed.</p>
                                                                        <?php
                                                                    } elseif ( ! empty( $outstanding ) ) {
                                                                        $message_detail = 'The approval of this result is outstanding';
                                                                        ?>
                                                                        <?php
                                                                        if ( $time_period ) {
                                                                            $message_detail .= ' more than ' . $time_period . ' hours after the result was entered';
                                                                        }
                                                                        $message_detail .= '.';
                                                                        ?>
                                                                        <p><?php echo esc_html( $message_detail ); ?></p>
                                                                        <p>Please either approval or challenge the result as soon as possible.</p>
                                                                        <?php
                                                                    } elseif ( isset( $errors ) && $errors ) {
                                                                        ?>
                                                                        <p>The result of this match has been confirmed and updated.</p>
                                                                        <p>There are player checks that need actioning.</p>
                                                                        <?php
                                                                    } elseif ( isset( $complete ) && $complete ) {
                                                                        ?>
                                                                        <p>The result of this match has been confirmed and updated.</p>
                                                                        <p>There is no further action required.</p>
                                                                        <?php
                                                                    } elseif ( isset( $challenge ) && $challenge ) {
                                                                        $message_detail = 'The result of this match has been challenged.';
                                                                        ?>
                                                                        <p><?php echo esc_html( $message_detail ); ?></p>
                                                                        <?php
                                                                    } else {
                                                                        $message_detail = 'The result of this match has been entered';
                                                                        if ( $confirmation_required ) {
                                                                            $message_detail .= ' and requires action';
                                                                        }
                                                                        $message_detail .= '.';
                                                                        ?>
                                                                        <p><?php echo esc_html( $message_detail ); ?></p>
                                                                        <?php
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
            <?php $action_link_text = __( 'View result', 'racketmanager' ); ?>
            <?php require 'components/action-link.php'; ?>
            <?php
            if ( ! empty( $from_email ) ) {
                $contact_email = $from_email;
                require 'components/contact.php';
            }
            ?>
            <?php require 'components/closing.php'; ?>
            <?php require 'components/link-text.php'; ?>
<?php
require 'email-footer.php';
