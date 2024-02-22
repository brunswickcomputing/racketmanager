<?php
/**
 * Template for favourites
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

?>
<div class="row justify-content-center">
	<div class="col-12 col-md-9">
		<h1><?php esc_html_e( 'My favourites', 'racketmanager' ); ?></h1>
		<div>
			<!-- Nav tabs -->
			<ul class="nav nav-tabs frontend" id="myTab" role="tablist">
				<?php
				$i = 0;
				foreach ( $favourite_types as $favourite_type ) {
					?>
					<li class="nav-item" role="presentation">
						<button class="nav-link
							<?php
							if ( 0 === $i ) {
								echo ' active';
							}
							?>
							" id="favourite_type-<?php echo esc_html( $favourite_type['name'] ); ?>-tab" data-bs-toggle="pill" data-bs-target="#favourite_type-<?php echo esc_html( $favourite_type['name'] ); ?>" type="button" role="tab" aria-controls="favourite_type-<?php echo esc_html( $favourite_type['name'] ); ?>" aria-selected="true"><?php echo esc_html( $favourite_type['name'] ); ?></button>
					</li>
					<?php
					$i ++;
				}
				?>
			</ul>
			<!-- Tab panes -->
			<div class="tab-content">
				<?php
				$i = 0;
				foreach ( $favourite_types as $favourite_type ) {
					?>
					<div class="tab-pane fade
						<?php
						if ( 0 === $i ) {
							echo 'show active';
						}
						?>
						" id="favourite_type-<?php echo esc_html( $favourite_type['name'] ); ?>" role="tabpanel" aria-labelledby="favourite_type-<?php echo esc_html( $favourite_type['name'] ); ?>-tab">
						<?php foreach ( $favourite_type['favourites'] as $key => $favourite ) { ?>
							<div>
								<h4 class="header"><a href="/<?php echo esc_html( $favourite_type['name'] ); ?>s/<?php echo esc_html( seo_url( $favourite->name ) ); ?>"><?php echo esc_html( $favourite->name ); ?></a></h4>
								<?php
								if ( is_user_logged_in() ) {
									$is_favourite = $racketmanager->is_user_favourite( $favourite_type['name'], $favourite->id );
									if ( $is_favourite ) {
										$link_title = __( 'Remove favourite', 'racketmanager' );
									} else {
										$link_title = __( 'Add favourite', 'racketmanager' );
									}
									?>
									<div class="fav-icon">
										<a href="" id="fav-<?php echo esc_html( $favourite->id ); ?>" title="<?php echo esc_html( $link_title ); ?>" data-js="add-favourite" data-type="<?php echo esc_html( $favourite_type['name'] ); ?>" data-favourite="<?php echo esc_html( $favourite->id ); ?>">
											<i class="fav-icon-svg racketmanager-svg-icon
											<?php
											if ( $is_favourite ) {
												echo ' fav-icon-svg-selected';
											}
											?>
											">
												<?php racketmanager_the_svg( 'icon-star' ); ?>
											</i>
										</a>
										<div class="fav-msg" id="fav-msg-<?php echo esc_html( $favourite->id ); ?>"></div>
									</div>
								<?php } ?>
							</div>
						<?php } ?>
					</div>
					<?php
					$i ++;
				}
				?>
			</div>
		</div>
	</div>
</div>
