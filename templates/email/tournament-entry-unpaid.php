<?php
/**
 * Template for tournament unpaid entry reminder email
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var string $addressee */
/** @var object $tournament */
/** @var string $from_email */
/** @var string $salutation */
/** @var string $paragraph */
/** @var string $contact */
/** @var string $closing */
require 'email-header.php';
$salutation_link = $addressee;
require $salutation;

/* translators: %s: tournament name */
$paragraph_text = sprintf( __( 'You have entered the %s Tournament, but your payment has not yet been completed.', 'racketmanager' ), ucfirst( $tournament->name ) );
require $paragraph;

$paragraph_text = __( 'Please click the button below to complete your payment.', 'racketmanager' );
require $paragraph;

$action_link_text = __( 'Complete payment', 'racketmanager' );
require 'components/action-link.php';

if ( ! empty( $from_email ) ) {
    $contact_email = $from_email;
    require $contact;
}
require $closing;
require 'components/link-text.php';
require 'email-footer.php';
