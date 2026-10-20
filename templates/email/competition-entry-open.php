<?php
/**
 * Template for competition entry open email
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var string|null $addressee */
/** @var string|null $competition */
/** @var object|null $season_dtls */
/** @var bool|null   $is_championship */
/** @var string|null $salutation */
/** @var string|null $paragraph */
/** @var string|null $contact */
/** @var string|null $closing */
/** @var string|null $days_remaining */
/** @var string|null $from_email */
/** @var string|null $date_closing_display */
/** @var string|null $date_start_display */
/** @var string|null $date_end_display */

$closing_date_display = (string) ( $date_closing_display ?? ( $season_dtls->date_closing_display ?? '' ) );
$start_date_display   = (string) ( $date_start_display ?? ( $season_dtls->date_start_display ?? '' ) );
$end_date_display     = (string) ( $date_end_display ?? ( $season_dtls->date_end_display ?? '' ) );

require 'email-header.php';
$salutation_link = $addressee ?? '';
require $salutation;
if ( empty( $days_remaining ) ) {
    /* translators: %1$s: competition name %2$s: closing date */
    $paragraph_text = sprintf( __( 'The entry form for the %1$s is now available. The closing date for entries is %2$s.', 'racketmanager' ), ucfirst( (string) ( $competition ?? '' ) ), $closing_date_display );
} else {
    /* translators: %1$s: days remaining %2$s: competition name */
    $paragraph_text = sprintf( __( 'There are now less than %1$s days left before the closing date of the %2$s.', 'racketmanager' ), (string) $days_remaining, ucfirst( (string) ( $competition ?? '' ) ) );
}
require $paragraph;
if ( ! empty( $start_date_display ) ) {
    /* translators: $s: start date */
    $paragraph_text = sprintf( __( 'The competition will run from %s', 'racketmanager' ), $start_date_display );
    if ( ! empty( $end_date_display ) ) {
        /* translators: $s: end date */
        $paragraph_text .= ' ' . sprintf( __( 'until % s', 'racketmanager' ), $end_date_display );
    }
    $paragraph_text .= '.';
    require $paragraph;
}

if ( ! empty( $season_dtls->venue_name ) && ! empty( $is_championship ) ) {
    /* translators: $s: venue name */
    $paragraph_text = ' ' . sprintf( __( 'Finals day will held at %s. ', 'racketmanager' ), $season_dtls->venue_name );
    require $paragraph;
}
$paragraph_text = __( 'Click the button below to take you directly to the entry form.', 'racketmanager' );
require $paragraph;
$action_link_text = __( 'Entry Form', 'racketmanager' );
require 'components/action-link.php';
if ( ! empty( $from_email ) ) {
    $contact_email = $from_email;
    require $contact;
}
require $closing;
require 'components/link-text.php';
require 'email-footer.php';
