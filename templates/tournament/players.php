<?php
/**
 * Template for tournament players
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

?>
<div class="container">
	<?php
	if ( empty( $tournament_player ) ) {
		if ( ! empty( $tournament->players ) ) {
			?>
			<div class="module module--card">
				<div class="module__banner">
					<h3 class="module__title"><?php esc_html_e( 'Players', 'racketmanager' ); ?></h3>
				</div>
				<div class="module__content">
					<div class="module-container">
						<div id="tournament-players">
							<ul class="player-list ">
								<?php
								foreach ( $tournament->players as $key => $players ) {
									?>
									<li class="player-list__cat" id="<?php echo esc_html( $key ); ?>">
										<div class="list-divider"><?php echo esc_html( $key ); ?></div>
										<ul class="row player-list-letter">
											<?php
											foreach ( $players as $tournament_player ) {
												?>
												<li class="alphabet-list-item col-12 col-sm-6 col-md-4">
													<a href="/tournament/<?php echo esc_html( seo_url( $tournament->name ) ); ?>/players/<?php echo esc_html( seo_url( $tournament_player->display_name ) ); ?>/">
														<?php echo esc_html( $tournament_player->index ); ?>
													</a>
												</li>
											<?php } ?>
										</ul>
									</li>
								<?php } ?>
							</ul>
						</div>
						<div class="col-12 col-md-6 col-lg-3">
						</div>
					</div>
				</div>
			</div>
			<?php
		}
	} else {
		require 'player.php';
	}
	?>
</div>
