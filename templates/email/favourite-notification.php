<?php
/**
 * Template for favourite notification email
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Team\Team;

/** @var object $user */
/** @var string $match_url */
/** @var Fixture $fixture */
/** @var Team $home_team */
/** @var Team $away_team */
/** @var string $salutation */
/** @var string $contact */
/** @var string $closing */
/** @var string $title */
/** @var array $vars */
/** @var object|null $vm */
$user          = $vars['user'] ?? $vm->user ?? $user ?? null;
$match_url     = $vars['match_url'] ?? $vm->match_url ?? $match_url ?? '';
$fixture       = $vars['fixture'] ?? $vm->fixture ?? $fixture ?? null;
$home_team     = $vars['home_team'] ?? $vm->home_team ?? $home_team ?? null;
$away_team     = $vars['away_team'] ?? $vm->away_team ?? $away_team ?? null;
$from_email    = $vars['from_email'] ?? $vm->from_email ?? $from_email ?? '';
$contact_email = $vars['contact_email'] ?? $vm->contact_email ?? $contact_email ?? $from_email;
$favourite_url = $vars['favourite_url'] ?? $vm->favourite_url ?? $favourite_url ?? '';
$email_subject = __( 'Match Result - Favourite Notification', 'racketmanager' );
require 'email-header.php';
?>
            <?php
            $title_text  = __( 'Favourite Result', 'racketmanager' );
            $title_level = '1';
            require $title;
            ?>
            <?php
            $salutation_link = $user->first_name;
            ?>
            <?php require $salutation; ?>
            <!-- introduction -->
            <div style="font-size: 16px; color: #000; background-color: #fff; padding: 0 20px;">
                <table align="center" style="display: block;" role="presentation" cellspacing="0" cellpadding="0">
                    <tbody>
                        <tr>
                            <td cellspacing="0" cellpadding="0" bgcolor="#fff">
                                <table style="width: 100%; border-collapse: collapse;" role="presentation" cellspacing="0" cellpadding="0">
                                    <tbody>
                                        <tr>
                                            <td style="font-weight: 400; min-width: 5px; width: 600px; height: 0;" cellspacing="0" cellpadding="0" align="left" bgcolor="#fff" valign="top">
                                                <table width="100%" style="height: 100%;" role="presentation" cellspacing="0" cellpadding="0">
                                                    <tbody>
                                                        <tr>
                                                            <td style="min-width: 5px; font-weight: 400;" cellspacing="0" cellpadding="0" align="center" bgcolor="#fff" valign="top">
                                                                <div style="font-size: 16px; color: #000; background-color: transparent; margin: 10px;">
                                                                    <div style="line-height: 1.25; mso-line-height-rule: at-least; margin: 0 0 20px; padding: 0;">
                                                                        <table class="body-action" aria-describedby="<?php esc_html_e( 'outside url wrapping action', 'racketmanager' ); ?>" role="presentation">
                                                                            <tr>
                                                                                <td class="align-right team"><a style="text-decoration: none; color: #006800;" href="<?php echo esc_html( $match_url ); ?>"><?php echo esc_html( $home_team?->get_name() ?? '' ); ?></a></td>
                                                                                <td class="align-center"><a style="text-decoration: none; color: #006800;" href="<?php echo esc_html( $match_url ); ?>"><?php echo esc_html( ( $fixture->get_home_points() ?? '0' ) . '-' . ( $fixture->get_away_points() ?? '0' ) ); ?></a></td>
                                                                                <td class="align-left team"><a style="text-decoration: none; color: #006800;" href="<?php echo esc_html( $match_url ); ?>"><?php echo esc_html( $away_team?->get_name() ?? '' ); ?></a></td>
                                                                            </tr>
                                                                        </table>
                                                                    </div>
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
            <?php
            if ( ! empty( $from_email ) ) {
                $contact_email = $from_email;
                require $contact;
            }
            ?>
            <?php require $closing; ?>
            <?php require 'components/fav-link-text.php'; ?>
<?php
require 'email-footer.php';
