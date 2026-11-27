<?php
/**
 * Tournament partner removed email template
 *
 * @package Racketmanager/Templates/Email
 *
 * Inputs:
 * - tournament        object with is_open, date_closing_display
 * - tournament_name   string tournament name
 * - tournament_link   optional HTML link to the tournament (falls back to escaped tournament_name)
 * - action_url        optional entry form URL, shown when tournament is open
 * - partner_name      removed partner (addressee)
 * - player_name       removing player
 * - event_names       array or comma-delimited string of event names
 * - refund_amount     float|string refund due (shown when > 0)
 * - remaining_events  array of remaining events (empty when withdrawn from all events)
 * - contact_email     optional secretary contact email
 * - organisation      organisation name used in the closing
 */

namespace Racketmanager;

/** @var object $tournament */
/** @var string $tournament_name */
/** @var string $tournament_link */
/** @var string $action_url */
/** @var string $partner_name */
/** @var string $player_name */
/** @var array|string $event_names */
/** @var float|string $refund_amount */
/** @var array $remaining_events */
/** @var string $contact_email */
/** @var string $organisation */
/** @var string $salutation */
/** @var string $paragraph */
/** @var string $contact */
/** @var string $closing */
/** @var string $title */
/** @var string $hr */

$tournament_name = $tournament_name ?? '';
if ( empty( $email_subject ) ) {
	$email_subject = __( 'Tournament Partner Removal', 'racketmanager' ) . ( ! empty( $tournament_name ) ? ' - ' . ucfirst( $tournament_name ) : '' );
}
require 'email-header.php';

$title_text  = __( 'Partner removal notification', 'racketmanager' );
$title_level = '1';
require $title;

$salutation_link = $partner_name;
require $salutation;

if ( is_array( $event_names ) ) {
	$events_formatted = implode( ', ', $event_names );
} else {
	$events_formatted = (string) $event_names;
}
if ( '' === $events_formatted ) {
	$events_formatted = __( 'doubles', 'racketmanager' );
}

$t_link = ! empty( $tournament_link ) ? $tournament_link : esc_html( $tournament_name );

/* translators: 1: removing player name, 2: event name(s), 3: tournament link */
$paragraph_text  = sprintf( __( 'You have been removed as a doubles partner by %1$s for %2$s in the %3$s tournament.', 'racketmanager' ), esc_html( $player_name ), esc_html( $events_formatted ), $t_link );
$paragraph_imbed = true;
require $paragraph;
$paragraph_imbed = false;

if ( ! empty( $remaining_events ) ) {
	$paragraph_text = __( 'Your entries for other events in this tournament remain active.', 'racketmanager' );
	require $paragraph;
} else {
	/* translators: %s: tournament link */
	$paragraph_text  = sprintf( __( 'You have now been withdrawn from the %s tournament.', 'racketmanager' ), $t_link );
	$paragraph_imbed = true;
	require $paragraph;
	$paragraph_imbed = false;
}

if ( ! empty( $refund_amount ) && (float) $refund_amount > 0 ) {
	$formatted_refund = get_currency_amount( (string) $refund_amount );
	/* translators: %s: refund amount */
	$paragraph_text = sprintf( __( 'A refund of %s is due for your entry fees and will be processed by the tournament organizers.', 'racketmanager' ), $formatted_refund );
	require $paragraph;
}

if ( ! empty( $tournament->is_open ) ) {
	if ( ! empty( $tournament->date_closing_display ) ) {
		/* translators: %s: tournament closing date */
		$paragraph_text = sprintf( __( 'If you would like to enter the tournament again, please do so before the entry deadline of %s.', 'racketmanager' ), $tournament->date_closing_display );
	} else {
		$paragraph_text = __( 'If you would like to enter the tournament again, please do so before the entry deadline.', 'racketmanager' );
	}
	$paragraph_imbed = true;
	require $paragraph;
	$paragraph_imbed  = false;
	$action_link_text = __( 'Entry form', 'racketmanager' );
	require 'components/action-link.php';
}

require $hr;
if ( ! empty( $contact_email ) ) {
	require $contact;
}
require $closing;
require 'email-footer.php';
