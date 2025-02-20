<?php
/**
 * Template for tournament entry open email
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

require 'email-header.php';
$salutation_link = $addressee;
require 'components/salutation.php';
if ( 'club' === $type ) {
	$paragraph_format = 'bold';
	$paragraph_text = __( 'Please pass this information on to your members.', 'racketmanager' );
	require 'components/paragraph.php';
	$paragraph_format = null;
}
if ( empty( $days_remaining ) ) {
	/* translators: %1$s: tournament name %2$s: closing date */
	$paragraph_text = sprintf( __( 'The entry form for the %1$s Tournament is now available. The closing date for entries is %2$s.', 'racketmanager' ), ucfirst( $tournament->name ), $tournament->date_closing_display );
	require 'components/paragraph.php';
} else {
	/* translators: %1$s: days remaining %2$s: competition name %3$s: closing date */
	$paragraph_text = sprintf( __( 'There are now less than %1$s days left before the %2$s Tournament closes on %3$s.', 'racketmanager' ), $days_remaining, ucfirst( $tournament->name ), $tournament->date_closing_display );
	require 'components/paragraph.php';
	$paragraph_text = __( 'You have played in a previous tournament but you have not yet entered this one.', 'racketmanager' );
	require 'components/paragraph.php';
}
if ( ! empty( $tournament->date_start_display ) && ! empty( $tournament->date_display ) ) {
	/* translators: %1$s: start date %2$s: end date */
	$paragraph_text = sprintf( __( 'The tournament will run from %1$s to %2$s.', 'racketmanager' ), $tournament->date_start_display, $tournament->date_display );
	require 'components/paragraph.php';
}
if ( ! empty( $tournament->venue_name ) ) {
	/* translators: %s Venue */
	$paragraph_text = sprintf( __( 'Finals day will held at %s.', 'racketmanager' ), $tournament->venue_name );
	require 'components/paragraph.php';
}
$paragraph_text = __( 'Click the button below to take you directly to the entry form.', 'racketmanager' );
require 'components/paragraph.php';
$action_link_text = __( 'Entry Form', 'racketmanager' );
require 'components/action-link.php';
if ( ! empty( $from_email ) ) {
	$contact_email = $from_email;
	require 'components/contact.php';
}
require 'components/closing.php';
switch ( $type ) {
	case 'player':
	case 'reminder':
		$paragraph_text = sprintf(
			/* translators: %1$s: account link */
			__( 'You have been sent this email as you have played in a previous tournament. If you prefer to no longer receive tournament notifications, please update your preferences using this %1$s.', 'racketmanager' ),
			$account_link,
		);
		$paragraph_format = 'italic-small';
		$paragraph_imbed  = true;
		require 'components/paragraph.php';
		$paragraph_imbed  = false;
		$paragraph_format = null;
		break;
	default:
		break;
}
require 'components/link-text.php';
require 'email-footer.php';
