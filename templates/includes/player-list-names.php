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
			<?php
			if ( ! empty( $player_list ) ) {
				?>
				<ul class="player-list ">
					<?php
					foreach ( $player_list as $key => $players ) {
						$alphabet_key[] = $key;
						?>
						<li class="player-list__cat" id="<?php echo esc_html( $key ); ?>">
							<div class="list-divider sticky is-sticky"><?php echo esc_html( $key ); ?></div>
							<ul class="row player-list-letter">
								<?php
								foreach ( $players as $player ) {
									$url_link = $player_link . seo_url( $player->display_name ) . '/';
									if ( ! empty( $tournament ) ) {
										$onclick = "onclick=Racketmanager.tabDataLink(event,'tournament'," . $tournament->id . ",'','" . $url_link . "'," . $player->id . ",'players')";
									} elseif ( ! empty( $competition ) ) {
										$onclick = "onclick=Racketmanager.tabDataLink(event,'competition'," . $competition->id . ',' . $competition->current_season['name'] . ",'" . $url_link . "'," . $player->id . ",'players')";
									} elseif ( ! empty( $event ) ) {
										$onclick = "onclick=Racketmanager.tabDataLink(event,'event'," . $event->id . ',' . $event->current_season['name'] . ",'" . $url_link . "'," . $player->id . ",'players')";
									} else {
										$onclick = null;
									}
									?>
									<li class="alphabet-list-item col-12 col-sm-6 col-md-4">
										<div class="media__content">
											<h5 class="media__title">
												<a class="nav--link media__link" href="<?php echo esc_attr( $url_link ); ?>" <?php echo esc_attr( $onclick ); ?>>
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
				<div class="alphabet__wrapper">
					<ul class="alphabet">
						<?php
						$alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
						for ( $i = 0; $i < 26; ++$i ) {
							$alphabet_char = substr( $alphabet, $i, 1 );
							if ( false === array_search( $alphabet_char, $alphabet_key, true ) ) {
								$alphabet_class = 'is-disabled';
							} else {
								$alphabet_class = null;
							}
							?>
							<li class="alphabet__char">
								<a href="#<?php echo esc_attr( $alphabet_char ); ?>" class="alphabet__char-href <?php echo esc_attr( $alphabet_class ); ?>"><?php echo esc_attr( $alphabet_char ); ?></a>
							</li>
							<?php
						}
						?>
					</ul>
				</div>
				<?php
			} else {
				esc_html_e( 'No players found', 'racketmanager' );
			}
			?>
		</div>
	</div>
</div>
