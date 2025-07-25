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
/** @var string $salutation */
/** @var string $paragraph */
/** @var string $contact */
/** @var string $closing */
/** @var string $title */
$email_subject = __( 'Tournament Withdrawal', 'racketmanager' ) . ' - ' . ucfirst( $tournament_name );
require 'email-header.php';
$title_text  = __( 'Withdrawal confirmation', 'racketmanager' );
$title_level = '1';
require $title;
$salutation_link = $player->fullname;
require $salutation;
/* translators: $s: tournament link */
$paragraph_text  = sprintf( __( 'You have now been withdrawn from the %s tournament.', 'racketmanager' ), $tournament_link );
$paragraph_imbed = true;
require $paragraph;
$paragraph_imbed = false;
if ( $tournament->is_open ) {
    /* translators: $s: tournament closing date */
    $paragraph_text  = sprintf( __( 'If you would like to enter the tournament again, please do so before the entry deadline of %s.', 'racketmanager' ), $tournament->date_closing_display );
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
