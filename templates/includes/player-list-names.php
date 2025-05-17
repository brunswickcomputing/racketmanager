<?php
/**
 * Template for player list
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var string $player_link */
$alphabet_key = array();
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
										$data_season    = null;
										$data_type      = 'tournament';
										$data_type_id   = $tournament->id;
										$link_class     = 'tabDataLink';
									} elseif ( ! empty( $competition ) ) {
										$data_season    = $competition->current_season['name'];
										$data_type      = 'competition';
										$data_type_id   = $competition->id;
										$link_class     = 'tabDataLink';
									} elseif ( ! empty( $event ) ) {
										$data_season    = $event->current_season['name'];
										$data_type      = 'event';
										$data_type_id   = $event->id;
										$link_class     = 'tabDataLink';
									} else {
										$data_season    = null;
										$data_type      = null;
										$data_type_id   = null;
										$link_class     = null;
									}
									?>
									<li class="alphabet-list-item col-12 col-sm-6 col-md-4">
										<div class="media__content">
											<h5 class="media__title">
												<a class="nav--link media__link <?php echo esc_attr( $link_class ); ?>" href="<?php echo esc_attr( $url_link ); ?>" data-type="<?php echo esc_attr( $data_type ); ?>" data-type-id="<?php echo esc_attr( $data_type_id ); ?>" data-season="<?php echo esc_attr( $data_season ); ?>" data-link="<?php echo esc_attr( $url_link ); ?>" data-link-id="<?php echo esc_attr( $player->id ); ?>" data-link-type="players">
													<span class="nav-link__value"><?php echo esc_html( $player->index ); ?></span>
												</a>
											</h5>
											<?php
											if ( ! empty( $player->club ) ) {
												?>
												<div class="media__content-subinfo">
													<div class="media__subheading-wrapper">
														<small class="media__subheading">
															<span class="nav--link">
																<span class="nav-link__value"><?php echo esc_html( $player->club->shortcode ); ?></span>
															</span>
														</small>
													</div>
												</div>
												<?php
											}
											?>
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
						$alphabet = range('A', 'Z');
                        foreach ( $alphabet as $alphabet_char ) {
	                        if ( ! in_array( $alphabet_char, $alphabet_key, true ) ) {
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
