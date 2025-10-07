<?php
/**
 * Template for match result pending notification email
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var string $organisation */
/** @var int    $time_period */
/** @var string $timeout */
/** @var string $penalty */
/** @var object $match */
/** @var string $contact */
/** @var string $closing */
/** @var string $paragraph */
$competition_name = $match->league->title;
$match_date       = $match->match_date;
$email_subject    = __( 'Match Result Pending', 'racketmanager' ) . ' - ' . $competition_name . ' - ' . $organisation;
require 'email-header.php';
require 'components/match-heading.php';
$message_detail = __('The result of this match is outstanding', 'racketmanager' );
if ( $time_period ) {
    $message_detail .= sprintf( __(' more than %s hours after the match was due to be played', 'racketmanager' ), $time_period );
}
$message_detail .= '.';
$paragraph_text  = $message_detail;
require $paragraph;
$paragraph_text = __( 'Please provide the result as soon as possible.', 'racketmanager' );
require $paragraph;
$action_link_text = __( 'Enter result', 'racketmanager' );
require 'components/action-link.php';
if ( $timeout ) {
    $paragraph_text = sprintf( __('The result must be entered within %s hours of the match start date.', 'racketmanager' ), $timeout );
    require $paragraph;
    if ( $penalty ) {
        $paragraph_text = sprintf( __('Failure to enter the result within this timeframe will result in a %s point penalty.', 'racketmanager' ), $penalty );
        require $paragraph;
    }
}
if ( ! empty( $from_email ) ) {
    $contact_email = $from_email;
    require $contact;
}
require $closing;
require 'components/link-text.php';
require 'email-footer.php';
