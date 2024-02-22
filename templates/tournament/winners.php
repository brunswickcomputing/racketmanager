<?php
/**
 * Template for tournament winners
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

if ( ! empty( $winners ) ) {
	?>
	<div class="container">
		<div class="module module--card">
			<div class="module__banner">
				<h3 class="module__title"><?php esc_html_e( 'Winners', 'racketmanager' ); ?></h3>
			</div>
			<div class="module__content">
				<div class="module-container">
					<?php require RACKETMANAGER_PATH . 'templates/includes/winners-body.php'; ?>
				</div>
			</div>
		</div>
	</div>
	<?php
}
?>
