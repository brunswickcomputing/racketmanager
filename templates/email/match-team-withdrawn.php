<?php
/**
 * Withdrawn team match email body
 *
 * @package Racketmanager/Templates/Email
 */

namespace Racketmanager;

/** @var string $event */
/** @var string $round */
/** @var string $paragraph */
/** @var string $contact */
/** @var string $closing */
/** @var string $title */
require 'email-header.php';
$title_text = sprintf(
/* translators: %1$s: league name %2$s: round name */
    __( '%1$s %2$s', 'racketmanager' ),
    $event,
    $round,
);
$title_level = '1';
$title_align = 'center';
require $title;
$title_align = '';
$title_text = __( 'Match Update', 'racketmanager' );
$title_level = '2';
$title_align = 'center';
require $title;
$title_align = '';
if ( isset( $is_tournament ) ) {
    $paragraph_text = __( 'Dear player', 'racketmanager' );
} else {
    $paragraph_text = __( 'Dear captain', 'racketmanager' );
}
require $paragraph;
/* translators: %1$s: team name %2$s: league name */
$paragraph_text = __( 'Unfortunately your opponent has withdrawn from the event.', 'racketmanager' );
require $paragraph;
/* translators: %s: team name */
$paragraph_text = __( 'You have been advanced to the next round.', 'racketmanager' );
require $paragraph;
/* translators: %s: team name */
$paragraph_text = __( 'You will receive a notification of your next match in due course.', 'racketmanager' );
require $paragraph;
if ( ! empty( $email_from ) ) {
    $contact_email = $email_from;
    require $contact;
}
require $closing;
require 'email-footer.php';
