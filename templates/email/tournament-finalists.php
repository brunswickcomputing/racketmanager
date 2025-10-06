<?php
/**
 * Contact tournament finalists email body
 *
 * @package Racketmanager/Templates/Email
 */

namespace Racketmanager;

/** @var string $addressee */
/** @var string $salutation */
/** @var string $closing */
/** @var string $paragraph */
/** @var string $contact */
/** @var string $organisation */
/** @var object $tournament */
/** @var array $rounds */
require 'email-header.php';
$salutation_link = $addressee;
require $salutation;
$paragraph_text = sprintf( __('Congratulations on reaching the final of the %s %s Tournament.', 'racketmanager'), $organisation, $tournament->name );
require $paragraph;
$paragraph_text = __( 'The order of play is now available to view.' , 'racketmanager' );
require $paragraph;
$action_link_text = __( 'Order of play', 'racketmanager' );
require 'components/action-link.php';
$paragraph_text = __( 'If you are playing doubles, please ensure that your playing partner is also aware of the start times of matches.', 'racketmanager' );
require $paragraph;
$paragraph_text = sprintf( __( 'There are %d rounds of matches scheduled, starting at %s with the last round scheduled for %s.', 'racketmanager' ), count( $rounds ), reset( $rounds ), end( $rounds ) );
require $paragraph;
if ( ! empty( $tournament->information->referee ) ) {
    $referee = ', ' . $tournament->information->referee . ', ';
} else {
    $referee = '';
}
$paragraph_text = sprintf( __( 'Please arrange to arrive at least 15 minutes prior to your scheduled match time. Report to the tournament referee%s when you arrive.', 'racketmanager' ), $referee );
require $paragraph;
if ( ! empty( $tournament->information->match_format ) ) {
    $paragraph_text = $tournament->information->match_format;
    require $paragraph;
}
if ( ! empty( $tournament->information->photography ) ) {
    $paragraph_text = $tournament->information->photography;
    require $paragraph;
}
if ( ! empty( $tournament->information->parking ) ) {
    $paragraph_text = $tournament->information->parking;
    require $paragraph;
}
if ( ! empty( $tournament->information->catering ) ) {
    $paragraph_text = $tournament->information->catering;
    require $paragraph;
}
if ( ! empty( $tournament->information->spectatora ) ) {
    $paragraph_text = $tournament->information->spectatora;
    require $paragraph;
}
if ( ! empty( $contact_email ) ) {
    require $contact;
}
require $closing;
require 'components/link-text.php';
require 'email-footer.php';
