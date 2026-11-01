<?php
/**
 * Withdrawn league team email body
 *
 * @package Racketmanager/Templates/Email
 */

namespace Racketmanager;

/** @var object $team */
/** @var object $league */
/** @var string $paragraph */
/** @var string $contact */
/** @var string $closing */
$team       = $team ?? $vm->team ?? null;
$league     = $league ?? $vm->league ?? null;
$email_from = $email_from ?? $vm->email_from ?? null;
require 'email-header.php';
$paragraph_text = __( 'Dear Captain', 'racketmanager' );
require $paragraph;
/* translators: %1$s: team name %2$s: league name */
$paragraph_text = sprintf( __( 'Unfortunately %1$s have withdrawn from the %2$s league.', 'racketmanager' ), $team->title, $league->title );
require $paragraph;
/* translators: %s: team name */
$paragraph_text = sprintf( __( 'All unplayed matches involving %s have been cancelled.', 'racketmanager' ), $team->title );
require $paragraph;
/* translators: %s: team name */
$paragraph_text = sprintf( __( 'All points from any completed match involving %s have been removed.', 'racketmanager' ), $team->title );
require $paragraph;
if ( ! empty( $email_from ) ) {
    $contact_email = $email_from;
    require $contact;
}
require $closing;
require 'email-footer.php';
