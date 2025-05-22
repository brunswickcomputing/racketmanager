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
require 'email-header.php';
$paragraph_text = __( 'A WTN update process has completed.', 'racketmanager' );
require 'components/paragraph.php';
if ( $club ) {
	/* translators: %s: club name */
	$paragraph_text = sprintf( __( 'The process was run for %s.', 'racketmanager' ), $club->shortcode );
	require 'components/paragraph.php';
}
/* translators: %s: number of players */
$paragraph_text = sprintf( __( '%s players were processed.', 'racketmanager' ), count( $players ) );
require 'components/paragraph.php';
/* translators: %s: number of errors */
$paragraph_text = sprintf( __( 'There were %s errors.', 'racketmanager' ), count( $messages ) );
require 'components/paragraph.php';
require 'components/closing.php';
?>
<?php require 'email-footer.php'; ?>
