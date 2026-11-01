<?php
/**
 * Tournament withdrawal email body
 *
 * @package Racketmanager/Templates/Email
 */

namespace Racketmanager;

/** @var string $tournament_name */
/** @var object $player */
/** @var string $tournament_link */
/** @var object $tournament */
/** @var \Racketmanager\Presentation\View_Models\Tournament_Date_View_Model $tournament_dates */
/** @var string $salutation */
/** @var string $paragraph */
/** @var string $contact */
/** @var string $closing */
/** @var string $title */
/** @var array $vars */
/** @var object|null $vm */
$tournament_name  = $vars['tournament_name'] ?? $vm->tournament_name ?? $tournament_name ?? '';
$player           = $vars['player'] ?? $vm->player ?? $player ?? null;
$tournament_link  = $vars['tournament_link'] ?? $vm->tournament_link ?? $tournament_link ?? '';
$tournament       = $vars['tournament'] ?? $vm->tournament ?? $tournament ?? null;
$tournament_dates = $vars['tournament_dates'] ?? $vm->tournament_dates ?? $tournament_dates ?? null;
$action_url       = $vars['action_url'] ?? $vm->action_url ?? $action_url ?? '';
$contact_email    = $vars['contact_email'] ?? $vm->contact_email ?? $contact_email ?? '';
$email_subject    = $vars['email_subject'] ?? $vm->email_subject ?? $email_subject ?? ( __( 'Tournament Withdrawal', 'racketmanager' ) . ' - ' . ucfirst( (string) $tournament_name ) );
require 'email-header.php';
$title_text  = __( 'Withdrawal confirmation', 'racketmanager' );
$title_level = '1';
require $title;
$salutation_link = is_object( $player ) && method_exists( $player, 'get_fullname' ) ? $player->get_fullname() : ( $player->display_name ?? '' );
require $salutation;
/* translators: $s: tournament link */
$paragraph_text  = sprintf( __( 'You have now been withdrawn from the %s tournament.', 'racketmanager' ), $tournament_link );
$paragraph_imbed = true;
require $paragraph;
$paragraph_imbed = false;
if ( $tournament->is_open ) {
    /* translators: $s: tournament closing date */
    $paragraph_text  = sprintf( __( 'If you would like to enter the tournament again, please do so before the entry deadline of %s.', 'racketmanager' ), $tournament_dates->date_closing_display );
    $paragraph_imbed = true;
    require $paragraph;
    $paragraph_imbed  = false;
    $action_link_text = __( 'Entry form', 'racketmanager' );
    require 'components/action-link.php';
}
require 'components/hr.php';
if ( ! empty( $contact_email ) ) {
    require $contact;
}
require $closing;
require 'email-footer.php';
