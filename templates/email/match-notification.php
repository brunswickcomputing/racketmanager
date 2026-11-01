<?php
/**
 * Template for email notification for match
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var object $match */
/** @var object $tournament */
/** @var string $round */
/** @var string $draw_link */
/** @var array  $teams */
/** @var string $rules_link */
/** @var string $tournament_link */
/** @var string $paragraph */
/** @var string $contact */
/** @var string $closing */
/** @var string $title */
/** @var string $hr */
/** @var array $vars */
/** @var object|null $vm */
$match           = $vars['match'] ?? $vm->match ?? $match ?? null;
$tournament      = $vars['tournament'] ?? $vm->tournament ?? $tournament ?? null;
$round           = $vars['round'] ?? $vm->round ?? $round ?? '';
$draw_link       = $vars['draw_link'] ?? $vm->draw_link ?? $draw_link ?? '';
$teams           = $vars['teams'] ?? $vm->teams ?? $teams ?? [];
$rules_link      = $vars['rules_link'] ?? $vm->rules_link ?? $rules_link ?? '';
$tournament_link = $vars['tournament_link'] ?? $vm->tournament_link ?? $tournament_link ?? '';
$action_url      = $vars['action_url'] ?? $vm->action_url ?? $action_url ?? '';
$email_from      = $vars['email_from'] ?? $vm->email_from ?? $email_from ?? ( $vars['from_email'] ?? $vm->from_email ?? '' );
$contact_email   = $vars['contact_email'] ?? $vm->contact_email ?? $contact_email ?? $email_from;

$tournament_name       = $vars['tournament_name'] ?? $vm->tournament_name ?? $tournament_name ?? ( $tournament->name ?? '' );
$match_date            = $vars['match_date'] ?? $vm->match_date ?? $match_date ?? ( $match->match_date ?? '' );
$default_match_subject = __( 'Next match confirmation', 'racketmanager' ) . ' - ' . ucfirst( (string) $tournament_name );
$email_subject         = ( ! empty( $vars['email_subject'] ) ) ? $vars['email_subject'] : ( ( ! empty( $vm->email_subject ) ) ? $vm->email_subject : ( ( ! empty( $email_subject ) ) ? $email_subject : $default_match_subject ) );
require 'email-header.php';
$title_text = sprintf(
    /* translators: %1$s: league name %2$s: round name */
    __( '%1$s %2$s Match Confirmation', 'racketmanager' ),
    $match->league->title,
    $round,
);
$title_level = '1';
$title_align = 'center';
require $title;
$title_align = '';
$paragraph_text = sprintf(
    /* translators: %1$s: draw link %2$s: tournament link */
    __( 'Please find below details of your next match in the %1$s event in the %2$s tournament.', 'racketmanager' ),
    $draw_link,
    $tournament_link,
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
$paragraph_text = __( 'Match to be played by', 'racketmanager' ) . ' ' . $match_date;
require $paragraph;
$opponents = array( 'home', 'away' );
foreach ( $opponents as $opponent ) {
    $title_text  = $teams[ $opponent ]->title;
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
                                                foreach ( $teams[ $opponent ]->player as $player ) {
                                                    ?>
                                                    <tr style="line-height: 22px;">
                                                        <td style="width: 50%; font-weight: normal;"><?php echo esc_html( $player->display_name ); ?></td>
                                                        <td><?php echo esc_html( $teams[ $opponent ]->club ); ?></td>
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
require $hr;
$title_text  = __( 'Contact Details', 'racketmanager' );
$title_level = '2';
require $title;
$opponents = array( 'home', 'away' );
foreach ( $opponents as $opponent ) {
    $title_text  = $teams[ $opponent ]->title;
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
                                                foreach ( $teams[ $opponent ]->player as $player ) {
                                                    if ( $player->contactno ) {
                                                        ?>
                                                        <tr style="line-height: 22px;">
                                                            <td style="width: 50%; font-weight: normal;"><?php echo esc_html( $player->display_name ); ?></td>
                                                            <td><a href="tel:<?php echo esc_html( $player->contactno ); ?>"><?php echo esc_html( $player->contactno ); ?></a></td>
                                                        </tr>
                                                        <?php
                                                    }
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
require $hr;
$title_text  = __( 'Rules', 'racketmanager' );
$title_level = '2';
require $title;
$paragraph_text  = __( 'The rules for the tournament can be found', 'racketmanager' ) . ' <a href="' . $rules_link . '">' . __( 'here', 'racketmanager' ) . '</a>.';
$paragraph_imbed = true;
require $paragraph;
$paragraph_imbed = false;
require $hr;
if ( ! empty( $email_from ) ) {
    $contact_email = $email_from;
    require $contact;
}
require $closing;
require 'components/link-text.php';
require 'email-footer.php';
