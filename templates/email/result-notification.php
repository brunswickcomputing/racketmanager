<?php
/**
 * Template for result notification email
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var object $match */
/** @var string $time_period */
/** @var bool   $confirmation_required */
/** @var string $confirmation_timeout */
/** @var string $contact */
/** @var string $closing */
$competition_name = $match->league->title;
$match_date       = $match->match_date;
$email_subject    = __( 'Match Result', 'racketmanager' ) . ' - ' . $competition_name;
require 'email-header.php';
require 'components/match-heading.php';
if ( ! empty( $override ) ) {
    $message_detail = __( 'The approval of this result was outstanding', 'racketmanager' );
    if ( $time_period ) {
        $message_detail .= sprintf( __(' for more than %s hours after the result was entered' , 'racketmanager'), $time_period );
    }
    $message_detail .= '.';
    $paragraph_text  = $message_detail;
    require 'components/paragraph.php';
    $paragraph_text = __( 'The entered result of this match has therefore been confirmed.', 'racketmanager' );
    require 'components/paragraph.php';
} elseif ( ! empty( $outstanding ) ) {
    $message_detail = __('The approval of this result is outstanding', 'racketmanager' );
    if ( $time_period ) {
        $message_detail .= sprinft( __(' more than %s hours after the result was entered', 'racketmanager' ), $time_period );
    }
    $message_detail .= '.';
    $paragraph_text  = $message_detail;
    require 'components/paragraph.php';
    $paragraph_text = __( 'Please either approval or challenge the result as soon as possible.', 'racketmanager' );
    require 'components/paragraph.php';
} elseif ( isset( $errors ) && $errors ) {
    $paragraph_text = __( 'The result of this match has been confirmed and updated.', 'racketmanager' );
    require 'components/paragraph.php';
    $paragraph_text = __( 'There are player checks that need actioning.', 'racketmanager' );
    require 'components/paragraph.php';
} elseif ( isset( $complete ) && $complete ) {
    $paragraph_text = __( 'The result of this match has been confirmed and updated.', 'racketmanager' );
    require 'components/paragraph.php';
    $paragraph_text = __( 'There is no further action required.', 'racketmanager' );
    require 'components/paragraph.php';
} elseif ( isset( $challenge ) && $challenge ) {
    $paragraph_text = __( 'The result of this match has been challenged.', 'racketmanager' );
    require 'components/paragraph.php';
} else {
    $message_detail = __( 'The result of this match has been entered', 'racketmanager' );
    if ( $confirmation_required ) {
        $message_detail .= __( ' and requires action', 'racketmanager' );
    }
    if ( ! empty( $confirmation_timeout ) ) {
        $message_detail .= sprintf( __( '; it will be automatically confirmed in %s hours', 'racketmanager' ), $confirmation_timeout );
    }
    $message_detail .= '.';
    $paragraph_text  = $message_detail;
    require 'components/paragraph.php';
    if ( ! empty( $confirmation_timeout ) ) {
        $paragraph_text = __( 'If you wish to confirm or challenge the result, please do so as soon as possible.', 'racketmanager' );
    }
    require 'components/paragraph.php';
}
$action_link_text = __( 'View result', 'racketmanager' );
require 'components/action-link.php';
if ( ! empty( $from_email ) ) {
    $contact_email = $from_email;
    require $contact;
}
require $closing;
require 'components/link-text.php';
require 'email-footer.php';
