<?php
/**
 * Template for tournament events
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

?>
<div class="container">
<?php
if ( empty( $event ) ) {
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
					foreach ( $tournament->events as $event ) {
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
								<a href="/tournament/<?php echo esc_html( seo_url( $tournament->name ) ); ?>/event/<?php echo esc_html( seo_url( $event->name ) ); ?>">
									<?php echo esc_html( $event->name ); ?>
								</a>
							</div>
							<div class="col-3" name="<?php esc_html_e( 'Draw size', 'racketmanager' ); ?>">
								<?php
								if ( ! empty( $event->teams ) ) {
									echo esc_html( count( $event->teams ) );
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
	<?php
} else {
	?>
	<div class="module module--card">
		<div class="module__banner">
			<h3 class="module__title">
				<?php echo esc_html( $event->name ); ?>
				<?php
				$competition    = $event;
				$favourite_type = 'competition';
				$favourite_id   = $event->id;
				require RACKETMANAGER_PATH . 'templates/includes/favourite.php';
				?>
			</h3>
		</div>
		<div class="module__content">
			<div class="module-container">
				<dl>
					<dt><?php esc_html_e( 'Draw', 'racketmanager' ); ?></dt>
					<dd>
						<a href="/tournament/<?php echo esc_html( seo_url( $tournament->name ) ); ?>/draw/<?php echo esc_html( seo_url( $event->name ) ); ?>">
							<?php echo esc_html( $event->name ); ?>
						</a>
					</dd>
				</dl>
			</div>
		</div>
	</div>
	<div class="module module--card">
		<div class="module__banner">
			<h3 class="module__title"><?php echo esc_html__( 'Entries', 'racketmanager' ) . ' (' . esc_html( count( $event->teams ) ) . ')'; ?></h4>
		</div>
		<div class="module__content">
			<div class="module-container">
				<div class="col-12 col-md-6 col-lg-3">
					<div class="row mb-2 row-header">
						<div class="col-12">
							<?php esc_html_e( 'Player', 'racketmanager' ); ?>
						</div>
					</div>
					<?php
					foreach ( $event->teams as $entry ) {
						?>
						<div class="row row-list">
							<div class="col-12" name="<?php esc_html_e( 'Player', 'racketmanager' ); ?>">
								<?php
								if ( ! empty( $entry->player ) ) {
									foreach ( $entry->player as $player ) {
										?>
										<div class="team-player">
											<a href="/tournament/<?php echo esc_html( seo_url( $tournament->name ) ); ?>/players/<?php echo esc_html( seo_url( $player ) ); ?>">
												<?php echo esc_html( wp_unslash( $player ) ); ?>
											</a>
										</div>
										<?php
									}
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
	<?php
}
?>
</div>
