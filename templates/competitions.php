<?php
/**
 * Template for favourites
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

?>
<div class="container">
	<div class="module module--card">
		<div class="module__banner">
			<h2 class="module__title"><?php echo esc_html( $type ); ?></h2>
		</div>
		<div class="module__content">
			<div class="module-container">
				<ul class="list list--grid list--bordered">
					<?php
					if ( empty( $competitions ) ) {
						?>
						<?php // translators: %s: type. ?>
						<li class="list__item col-12 text-center"><?php printf( esc_html__( 'No %s found', 'racketmanager' ), esc_html( strtolower( $type ) ) ); ?></li>
						<?php
					} else {
						foreach ( $competitions as $key => $competition ) {
							switch ( $competition->type ) {
								case 'league':
									$image = 'images/bootstrap-icons.svg#table';
									break;
								case 'tournament':
								case 'cup':
									$image = 'images/lta-icons.svg#icon-bracket';
									break;
								default:
									$image = null;
									break;
							}
							if ( 'tournament' === $competition->type ) {
								$competition_link = '/tournament/' . seo_url( $competition->name ) . '/';
							} else {
								$competition_link = '/' . seo_url( $competition->name ) . '/';
							}
							?>
							<li class="list__item col-12 col-sm-6">
								<div class="media">
									<div class="media__wrapper">
										<div class="media__img">
											<svg width="16" height="16" class="media__img-element--icon">
												<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . $image ); ?>"></use>
											</svg>
										</div>
										<div class="media__content">
											<h4 class="media__title">
												<a class="nav--link media__link" href="<?php echo esc_attr( $competition_link ); ?>">
													<span class="nav-link__value"><?php echo esc_html( $competition->name ); ?></span>
												</a>
											</h4>
											<div class="media__content-subinfo">
												<?php
												if ( ! empty( $competition->venue_name ) ) {
													?>
													<small class="media__subheading">
														<span class="nav--link">
															<span class="nav-link__value">
																<?php echo esc_html( $competition->venue_name ); ?>
															</span>
														</span>
													</small>
													<?php
												}
												?>
												<?php
												if ( ! empty( $competition->date_start ) && ! empty( $competition->date_end ) ) {
													?>
												<small class="media__subheading">
													<span class="nav--link">
														<span class="nav-link__value">
															<?php racketmanager_the_svg( 'icon-calendar' ); ?>
															<?php echo esc_html( mysql2date( 'j M Y', $competition->date_start ) ); ?> <?php esc_html_e( 'to', 'racketmanager' ); ?> <?php echo esc_html( mysql2date( 'j M Y', $competition->date_end ) ); ?>
														</span>
													</span>
												</small>
													<?php
												}
												?>
											</div>
										</div>
									</div>
									<ul class="media__icons">
										<li class="media__icons-item">
										</li>
									</ul>
								</div>
							</li>
							<?php
						}
					}
					?>
				</ul>
			</div>
		</div>
	</div>
</div>
