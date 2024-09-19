<?php
/**
 *
 * Template page for a player titles
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

?>
			<div class="module module--card">
				<div class="module__content">
					<div class="module__banner">
						<h4 class="module__title"><?php esc_html_e( 'Titles/Finals', 'racketmanager' ); ?></h4>
					</div>
					<div class="module-container titles">
						<?php
						foreach ( $player->titles as $season => $tournaments ) {
							?>
							<h5 class="module-divider">
								<span class="module-divider__body">
									<?php echo esc_html( $season ); ?>
								</span>
							</h5>
							<div class="module-container">
								<?php
								foreach ( $tournaments as $tournament => $matches ) {
									?>
									<h6 class="module-divider">
										<span class="module-divider__body">
											<a class="nav--link media__link" href="<?php echo '/tournament/' . esc_attr( seo_url( $tournament ) ); ?>">
												<span class="nav-link__value"><?php echo esc_html( $tournament ); ?></span>
											</a>
										</span>
									</h6>
									<ul class="list list--naked list-bordered">
										<?php
										foreach ( $matches as $match ) {
											if ( $match->team_id === $match->winner_id ) {
												$class = 'winner';
											} else {
												$class = 'runner-up';
											}
											?>
											<li class="list__item">
												<div class="media">
													<div class="media__wrapper">
														<div class="media__img">
															<svg width="16" height="16" class="media__img-element--icon <?php echo esc_attr( $class ); ?>">
																<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#trophy-fill' ); ?>"></use>
															</svg>
														</div>
														<div class="media__content">
															<h7 class="media__title">
																<a class="nav--link media__link" href="<?php echo '/tournament/' . esc_attr( seo_url( $match->tournament ) ) . '/draw/' . esc_attr( seo_url( $match->draw ) ) . '/'; ?>">
																	<span class="nav-link__value"><?php echo esc_html( $match->title ); ?></span>
																</a>
															</h7>
														</div>
													</div>
												</div>
											</li>
											<?php
										}
										?>
									</ul>
									<?php
								}
								?>
							</div>
							<?php
						}
						?>
					</div>
				</div>
			</div>
