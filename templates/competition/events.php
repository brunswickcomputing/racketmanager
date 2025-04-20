<?php
/**
 * Template for competition events
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var object $competition */
?>
<div class="module module--card">
	<div class="module__banner">
		<h3 class="module__title"><?php esc_html_e( 'Events', 'racketmanager' ); ?></h3>
	</div>
	<div class="module__content">
		<div class="module-container">
			<div class="col-12 col-md-6">
				<div class="row mb-2 row-header">
					<div class="col-1"></div>
					<div class="col-8">
						<?php esc_html_e( 'Event', 'racketmanager' ); ?>
					</div>
					<div class="col-3">
						<?php esc_html_e( 'Entries', 'racketmanager' ); ?>
					</div>
				</div>
				<?php
				foreach ($competition->events as $event ) {
					?>
					<div class="row mb-2 row-list">
						<div class="col-1" name="<?php esc_html_e( 'Favourite', 'racketmanager' ); ?>">
						<?php
						$hidden         = true;
						$favourite_type = 'competition';
						$favourite_id   = $event->id;
						require RACKETMANAGER_PATH . 'templates/includes/favourite.php';
						?>
						</div>
						<div class="col-8" name="<?php esc_html_e( 'Event', 'racketmanager' ); ?>">
							<a href="/<?php echo esc_html( $competition->type ); ?>s/<?php echo esc_html( seo_url( $event->name ) ); ?>/<?php echo esc_html( $competition->current_season['name'] ); ?>/">
								<?php echo esc_html( $event->name ); ?>
							</a>
						</div>
						<div class="col-3" name="<?php esc_html_e( 'Entries', 'racketmanager' ); ?>">
							<?php
							if ( ! empty( $event->num_entries ) ) {
								echo esc_html( $event->num_entries );
							}
							?>
						</div>
					</div>
					<?php
				}
				?>
			</div>
		</div>
	</div>
</div>
