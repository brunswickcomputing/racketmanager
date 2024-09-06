<?php
/**
 * Template for player list
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

?>
<div class="module module--card">
	<div class="module__banner">
		<h3 class="module__title"><?php esc_html_e( 'Players', 'racketmanager' ); ?></h3>
	</div>
	<div class="module__content">
		<div class="module-container">
			<ul class="player-list ">
				<?php
				foreach ( $player_list as $key => $players ) {
					?>
					<li class="player-list__cat" id="<?php echo esc_html( $key ); ?>">
						<div class="list-divider"><?php echo esc_html( $key ); ?></div>
						<ul class="row player-list-letter">
							<?php
							foreach ( $players as $player ) {
								?>
								<li class="alphabet-list-item col-12 col-sm-6 col-md-4">
									<div class="media__content">
										<h5 class="media__title">
											<a class="nav--link media__link" href="<?php echo esc_attr( $player_link ); ?><?php echo esc_attr( seo_url( $player->display_name ) ); ?>/">
												<span class="nav-link__value"><?php echo esc_html( $player->index ); ?></span>
											</a>
										</h5>
										<div class="media__content-subinfo">
											<div class="media__subheading-wrapper">
												<small class="media__subheading">
													<span class="nav--link">
														<span class="nav-link__value"></span>
													</span>
												</small>
											</div>
										</div>
									</div>
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
