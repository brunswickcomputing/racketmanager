<?php
/**
 * Template for league entry email.
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var string $competition_name */
/** @var string $season */
/** @var string $club */
/** @var array $event_entries */
/** @var string $title */
/** @var string $paragraph */
/** @var string $hr */
/** @var string $salutation */
/** @var string $contact */
/** @var string $closing */
$email_subject = __( 'League Entry', 'racketmanager' ) . ' - ' . ucfirst( $competition_name ) . ' - ' . $season;
require 'email-header.php';
?>
            <?php
            $title_text  = __( 'Entry confirmation', 'racketmanager' );
            $title_level = '1';
            require $title;
            ?>
            <?php
            $salutation_link = $club;
            require $salutation;
            ?>
            <?php
            /* translators: $s: competition name */
            $paragraph_text = sprintf( __( 'Thank you for your entry for the %s. You will find confirmation of your entry below.', 'racketmanager' ), $competition_name );
            require $paragraph;
            ?>
            <?php require $hr; ?>
            <?php
            $title_text  = __( 'Entry Details', 'racketmanager' );
            $title_level = '2';
            require $title;
            ?>
            <?php
            $title_text  = __( 'Club Details', 'racketmanager' );
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
                                                            <td style="width: 150px; font-size: 14px; font-weight: 500;"><?php esc_html_e( 'Number of courts', 'racketmanager' ); ?>:</td>
                                                            <td><?php echo esc_html( $event_entries['num_courts_available'] ); ?></td>
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
            <?php require $hr; ?>
            <?php
            $title_text  = __( 'Events', 'racketmanager' );
            $title_level = '3';
            require $title;
            ?>
            <?php
            foreach ( $event_entries['events'] as $event_entry ) {
                ?>
                <?php
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
                                                            <?php
                                                            foreach ( $event_entry['teams'] as $league_entry ) {
                                                                ?>
                                                                <tr style="line-height: 22px;">
                                                                    <td style="width: 150px; font-size: 14px; font-weight: 500; vertical-align: top;"><h5 style="font-size:14px; display:inline;"><?php echo esc_html( $league_entry['teamName'] ); ?></h5>:</td>
                                                                    <td>
                                                                        <?php echo esc_html( $league_entry['matchday'] ); ?> <?php esc_html_e( 'at', 'racketmanager' ); ?> <?php echo esc_html( $league_entry['matchtime'] ); ?>
                                                                        <br>
                                                                        <?php echo esc_html( $league_entry['captain'] ); ?>
                                                                        <?php
                                                                        if ( $league_entry['contactno'] > '' ) {
                                                                            ?>
                                                                            <br>
                                                                            <?php echo esc_html( $league_entry['contactno'] ); ?>
                                                                            <?php
                                                                        }
                                                                        ?>
                                                                        <?php
                                                                        if ( $league_entry['contactemail'] > '' ) {
                                                                            ?>
                                                                            <br>
                                                                            <?php echo esc_html( $league_entry['contactemail'] ); ?>
                                                                            <?php
                                                                        }
                                                                        ?>
                                                                    </td>
                                                                </tr>
                                                                <?php
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
            }
            ?>
            <?php
            if ( ! empty( $comments ) ) {
                require $hr;
                $title_text  = __( 'Additional comments', 'racketmanager' );
                $title_level = '3';
                require $title;
                $paragraph_text = $comments;
                require $paragraph;
            }
            ?>
            <?php require $hr; ?>
            <?php
            $paragraph_text = __( 'Captains will be notified when the leagues have been finalised and fixtures for the season are available.', 'racketmanager' );
            require $paragraph;
            ?>
            <?php
            if ( ! empty( $contact_email ) ) {
                require $contact;
            }
            ?>
            <?php require $closing; ?>
<?php
require 'email-footer.php';
