<?php
/**
 * Template for wtn report email
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var object $club */
/** @var int $player_count */
/** @var int $error_count */
/** @var string $paragraph */
/** @var array $vars */
/** @var object|null $vm */
$club          = $vars['club'] ?? $vm->club ?? $club ?? null;
$player_count  = $vars['player_count'] ?? $vm->player_count ?? $player_count ?? 0;
$error_count   = $vars['error_count'] ?? $vm->error_count ?? $error_count ?? 0;
$email_subject = $vars['email_subject'] ?? $vm->email_subject ?? $email_subject ?? __( 'WTN Report', 'racketmanager' );
require 'email-header.php';
$paragraph_text = __( 'A WTN update process has completed.', 'racketmanager' );
require $paragraph;
if ( $club ) {
    /* translators: %s: club name */
    $paragraph_text = sprintf( __( 'The process was run for %s.', 'racketmanager' ), $club->get_shortcode() );
    require $paragraph;
}
/* translators: %s: number of players */
$paragraph_text = sprintf( __( '%s players were processed.', 'racketmanager' ), $player_count );
require $paragraph;
/* translators: %s: number of errors */
$paragraph_text = sprintf( __( 'There were %s errors.', 'racketmanager' ), $error_count );
require $paragraph;
require 'components/closing.php';
?>
<?php require 'email-footer.php'; ?>
