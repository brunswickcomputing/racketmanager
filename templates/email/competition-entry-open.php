<?php
/**
 * Template for competition entry open email
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var string $addressee */
/** @var string $competition */
/** @var object $season_dtls */
/** @var bool   $is_championship */
global $racketmanager;
require 'email-header.php';
$salutation_link = $addressee;
require 'components/salutation.php';
if ( empty( $days_remaining ) ) {
    /* translators: %1$s: competition name %2$s: closing date */
    $paragraph_text = sprintf( __( 'The entry form for the %1$s is now available. The closing date for entries is %2$s.', 'racketmanager' ), ucfirst( $competition ), mysql2date( $racketmanager->date_format, $season_dtls->date_closing ) );
} else {
    /* translators: %1$s: days remaining %2$s: competition name */
    $paragraph_text = sprintf( __( 'There are now less than %1$s days left before the closing date of the %2$s.', 'racketmanager' ), $days_remaining, ucfirst( $competition ) );
}
require 'components/paragraph.php';
if ( ! empty( $season_dtls->date_start ) ) {
    /* translators: $s: start date */
    $paragraph_text = sprintf( __( 'The competition will run from %s', 'racketmanager' ), mysql2date( $racketmanager->date_format, $season_dtls->date_start ) );
    if ( ! empty( $season_dtls->date_end ) ) {
        /* translators: $s: end date */
        $paragraph_text .= ' ' . sprintf( __( 'until % s', 'racketmanager' ), mysql2date( $racketmanager->date_format, $season_dtls->date_end ) );
    }
    $paragraph_text .= '.';
    require 'components/paragraph.php';
}

if ( ! empty( $season_dtls->venue_name ) && $is_championship ) {
    /* translators: $s: venue name */
    $paragraph_text = ' ' . sprintf( __( 'Finals day will held at %s. ', 'racketmanager' ), $season_dtls->venue_name );
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
require 'components/link-text.php';
require 'email-footer.php';
