<?php
/**
 * Template for competition clubs
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

if ( empty( $competition_player ) ) {
	if ( ! empty( $competition->players ) ) {
		?>
		<div class="module module--card">
			<div class="module__banner">
				<h3 class="module__title"><?php esc_html_e( 'Players', 'racketmanager' ); ?></h3>
			</div>
			<div class="module__content">
				<div class="module-container">
					<ul class="player-list ">
						<?php
						foreach ( $competition->players as $key => $players ) {
							?>
							<li class="player-list__cat" id="<?php echo esc_html( $key ); ?>">
								<div class="list-divider"><?php echo esc_html( $key ); ?></div>
								<ul class="row player-list-letter">
									<?php
									foreach ( $players as $player ) {
										?>
										<li class="alphabet-list-item col-12 col-sm-6 col-md-4">
											<a href="/leagues/<?php echo esc_html( seo_url( $competition->name ) ); ?>/<?php echo esc_attr( $competition->current_season['name'] ); ?>/player/<?php echo esc_attr( seo_url( $player ) ); ?>/">
												<?php echo esc_html( $player ); ?>
											</a>
										</li>
									<?php } ?>
								</ul>
							</li>
							<?php
						}
						?>
					</ul>
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
			<h3 class="module__title"><?php echo esc_html( $competition_player->display_name ); ?></h3>
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
