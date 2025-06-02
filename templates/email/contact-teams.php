<?php
/**
 * Contact league teams email body
 *
 * @package Racketmanager/Templates/Email
 */

namespace Racketmanager;

/** @var string $intro */
/** @var array  $body */
/** @var string $closing */
/** @var string $object_type */
require 'email-header.php';
$title_align = 'center';
require 'components/title.php';
if ( isset( $tournament ) ) {
	$paragraph_text = __( 'Dear player', 'racketmanager' );
} else {
	$paragraph_text = __( 'Dear captain', 'racketmanager' );
}
require 'components/paragraph.php';
if ( $intro ) {
	$paragraph_text = $intro;
	require 'components/paragraph.php';
}
foreach ( $body as $i => $body_entry ) {
	if ( $body_entry ) {
		$paragraph_text = $body_entry;
		require 'components/paragraph.php';
	}
}
if ( $closing ) {
	$paragraph_text = $closing;
	require 'components/paragraph.php';
}
require 'components/closing.php';
require 'email-footer.php';
