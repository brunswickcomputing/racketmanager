<?php
/**
 * Template for a tournament match whose details could not be loaded
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

?>
<div class="match tournament-match match--unavailable">
	<div class="match__body">
		<p class="match__unavailable"><?php esc_html_e( 'Match details unavailable', 'racketmanager' ); ?></p>
	</div>
</div>
