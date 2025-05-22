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
$email_subject = __( 'Tournament Withdrawal', 'racketmanager' ) . ' - ' . ucfirst( $tournament_name );
require 'email-header.php';
$title_text  = __( 'Withdrawal confirmation', 'racketmanager' );
$title_level = '1';
require 'components/title.php';
$salutation_link = $player->fullname;
require 'components/salutation.php';
/* translators: $s: tournament link */
$paragraph_text  = sprintf( __( 'You have now been withdrawn from the %s tournament.', 'racketmanager' ), $tournament_link );
$paragraph_imbed = true;
require 'components/paragraph.php';
$paragraph_imbed = false;
if ( $tournament->is_open ) {
	/* translators: $s: tournament closing date */
	$paragraph_text  = sprintf( __( 'If you would like to enter the tournament again, please do so before the entry deadline of %s.', 'racketmanager' ), $tournament->date_closing_display );
	$paragraph_imbed = true;
	require 'components/paragraph.php';
	$paragraph_imbed  = false;
	$action_link_text = __( 'Entry form', 'racketmanager' );
	require 'components/action-link.php';
}
require 'components/hr.php';
if ( ! empty( $contact_email ) ) {
	require 'components/contact.php';
}
require 'components/closing.php';
require 'email-footer.php';
