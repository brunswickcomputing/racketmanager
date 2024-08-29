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
			<h2 class="module__title"><?php esc_html_e( 'My favourites', 'racketmanager' ); ?></h2>
		</div>
		<div class="module__content">
			<div class="module-container">
				<div class="">
					<nav class="navbar navbar-expand-lg">
						<div class="container-fluid">
							<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
								<span class="navbar-toggler-icon"></span>
							</button>
							<div class="collapse navbar-collapse mt-3 justify-content-center" id="navbarSupportedContent">
								<!-- Nav tabs -->
								<ul class="nav nav-pills frontend" id="myTab" role="tablist">
									<?php
									$i = 0;
									foreach ( $favourite_types as $favourite_type ) {
										if ( 'competition' === $favourite_type['name'] ) {
											$favourite_type_name = 'tournament';
										} else {
											$favourite_type_name = $favourite_type['name'];
										}
										?>
										<li class="nav-item" role="presentation">
											<button class="nav-link
												<?php
												if ( 0 === $i ) {
													echo ' active';
												}
												?>
												" id="favourite_type-<?php echo esc_html( $favourite_type['name'] ); ?>-tab" data-bs-toggle="pill" data-bs-target="#favourite_type-<?php echo esc_html( $favourite_type['name'] ); ?>" type="button" role="tab" aria-controls="favourite_type-<?php echo esc_html( $favourite_type['name'] ); ?>" aria-selected="true"><?php echo esc_html( $favourite_type_name ); ?></button>
										</li>
										<?php
										++$i;
									}
									?>
								</ul>
							</div>
						</div>
					</nav>
				</div>
				<!-- Tab panes -->
				<div class="tab-content">
					<?php
					$i = 0;
					foreach ( $favourite_types as $favourite_type ) {
						$favourite_name = $favourite_type['name'];
						?>
						<div class="tab-pane fade <?php echo 0 === $i ? 'show active' : ''; ?>" id="favourite_type-<?php echo esc_html( $favourite_name ); ?>" role="tabpanel" aria-labelledby="favourite_type-<?php echo esc_html( $favourite_name ); ?>-tab">
							<div class="module module--card">
								<div class="module__content">
									<div class="module-container">
										<ul class="list list--grid list--bordered">
											<?php
											if ( empty( $favourite_type['favourites'] ) ) {
												?>
												<li class="list__item col-12 text-center"><?php esc_html_e( 'No favourite found', 'racketmanager' ); ?></li>
												<?php
											} else {
												foreach ( $favourite_type['favourites'] as $key => $favourite ) {
													if ( is_user_logged_in() ) {
														$is_favourite = $racketmanager->is_user_favourite( $favourite_name, $favourite->id );
														if ( $is_favourite ) {
															$link_title = __( 'Remove favourite', 'racketmanager' );
														} else {
															$link_title = __( 'Add favourite', 'racketmanager' );
														}
														?>
														<?php
													}
													switch ( $favourite_name ) {
														case 'league':
															$image    = 'images/bootstrap-icons.svg#table';
															$fav_link = '/league/' . seo_url( $favourite->detail->title ) . '/';
															break;
														case 'club':
															$fav_link = '/clubs/' . seo_url( $favourite->detail->shortcode ) . '/';
															$image    = 'images/lta-icons-extra.svg#icon-team';
															break;
														case 'team':
															$fav_link = '/clubs/' . seo_url( $favourite->detail->club->shortcode ) . '/#club-teams';
															$image    = 'images/lta-icons-extra.svg#icon-team';
															break;
														case 'competition':
															$fav_link = '/tournaments/' . seo_url( $favourite->detail->competition->name ) . '/' . seo_url( $favourite->detail->name ) . '/';
															$image    = 'images/lta-icons.svg#icon-bracket';
															break;
														default:
															break;
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
																		<a class="nav--link media__link" href="<?php echo esc_attr( $fav_link ); ?>">
																			<span class="nav-link__value"><?php echo esc_html( $favourite->name ); ?></span>
																		</a>
																	</h4>
																</div>
															</div>
															<ul class="media__icons">
																<li class="media__icons-item">
																	<?php
																	$favourite_type = $favourite_name;
																	$favourite_id   = $favourite->detail->id;
																	require RACKETMANAGER_PATH . '/templates/includes/favourite-button.php';
																	?>
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
						<?php
						++$i;
					}
					?>
				</div>
			</div>
		</div>
	</div>
</div>
