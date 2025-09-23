<?php
/**
 * Template for wtn report email
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var object $club */
/** @var array $players */
/** @var array $messages */
/** @var string $paragraph */
require 'email-header.php';
$paragraph_text = __( 'A WTN update process has completed.', 'racketmanager' );
/** @var TYPE_NAME $paragraph */
require $paragraph;
if ( $club ) {
    /* translators: %s: club name */
    $paragraph_text = sprintf( __( 'The process was run for %s.', 'racketmanager' ), $club->shortcode );
    require $paragraph;
}
/* translators: %s: number of players */
$paragraph_text = sprintf( __( '%s players were processed.', 'racketmanager' ), count( $players ) );
require $paragraph;
/* translators: %s: number of errors */
$paragraph_text = sprintf( __( 'There were %s errors.', 'racketmanager' ), count( $messages ) );
require $paragraph;
require 'components/closing.php';
?>
<?php require 'email-footer.php'; ?>
