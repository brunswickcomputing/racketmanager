<?php
/**
 * Template for tournament entry partner notification when player has no email
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var string $addressee */
/** @var object $tournament */
/** @var string $partner_name */
/** @var string $from_email */
/** @var string $salutation */
/** @var string $paragraph */
/** @var string $contact */
/** @var string $closing */
require 'email-header.php';
$salutation_link = $addressee;
require $salutation;

/* translators: 1: partner name, 2: tournament name */
$paragraph_text = sprintf( __( 'You have entered %1$s as your partner in the %2$s Tournament, but they have not yet confirmed their entry and do not have an email address registered.', 'racketmanager' ), $partner_name, ucfirst( $tournament->name ) );
require $paragraph;

$paragraph_text = __( 'Please contact your partner so that they can confirm their entry details and pay.', 'racketmanager' );
require $paragraph;

$action_link_text = __( 'View tournament', 'racketmanager' );
require 'components/action-link.php';

if ( ! empty( $from_email ) ) {
    $contact_email = $from_email;
    require $contact;
}
require $closing;
require 'components/link-text.php';
require 'email-footer.php';
