<?php
/**
 * Template for competition clubs
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

if ( empty( $competition_club ) ) {
	if ( ! empty( $competition->clubs ) ) {
		?>
		<div class="module module--card">
			<div class="module__banner">
				<h3 class="module__title"><?php esc_html_e( 'Clubs', 'racketmanager' ); ?></h3>
			</div>
			<div class="module__content">
				<div class="module-container">
					<div class="col-12 col-md-12 col-lg-6">
						<div class="row mb-2 row-header">
							<div class="col-6">
								<?php esc_html_e( 'Club', 'racketmanager' ); ?>
							</div>
							<div class="col-3 text-end">
								<?php esc_html_e( 'Teams', 'racketmanager' ); ?>
							</div>
							<div class="col-3 text-end">
								<?php esc_html_e( 'Players', 'racketmanager' ); ?>
							</div>
						</div>
						<?php
						foreach ( $competition->clubs as $club ) {
							?>
							<div class="row mb-2 row-list">
								<div class="col-6" name="<?php esc_html_e( 'Club', 'racketmanager' ); ?>">
									<a href="/leagues/<?php echo esc_html( seo_url( $competition->name ) ); ?>/<?php echo esc_attr( $competition->current_season['name'] ); ?>/club/<?php echo esc_attr( seo_url( $club->shortcode ) ); ?>/">
										<?php echo esc_html( $club->name ); ?>
									</a>
								</div>
								<div class="col-3 text-end">
									<?php echo esc_html( $club->team_count ); ?>
								</div>
								<div class="col-3 text-end">
									<?php echo esc_html( $club->player_count ); ?>
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
	<?php
} else {
	?>
	<div class="module module--card">
		<div class="module__banner">
			<h3 class="module__title"><?php echo esc_html( $competition_club->name ); ?></h3>
		</div>
		<div class="module__content">
			<div class="module-container">
				<div class="module">
				</div>
			</div>
		</div>
	</div>
	<?php
}
?>
