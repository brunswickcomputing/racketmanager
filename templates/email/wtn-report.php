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
