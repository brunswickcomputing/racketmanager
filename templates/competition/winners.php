<?php
/**
 * Template for competition winners
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

$winners = $competition->winners;
?>
<div class="module module--card">
	<div class="module__banner">
		<h3 class="module__title"><?php esc_html_e( 'Winners', 'racketmanager' ); ?></h3>
	</div>
	<div class="module__content">
		<div class="module-container">
			<?php
			if ( ! empty( $winners ) ) {
				require RACKETMANAGER_PATH . 'templates/includes/winners-body.php';
			} else {
				esc_html_e( 'No winners to display', 'racketmanager' );
			}
			?>
		</div>
	</div>
</div>
