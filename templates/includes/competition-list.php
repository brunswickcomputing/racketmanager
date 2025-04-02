<?php
/**
 * Template for competition list
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

foreach ( $competition_list as $key => $competition ) {
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
	if ( 'tournament' === $competition->type ) {
		$competition_name = $competition->name;
	} elseif ( empty( $competition->season ) ) {
		$competition_name = $competition->name;
	} else {
		$competition_link .= $competition->season . '/';
		$competition_name  = $competition->name . ' ' . $competition->season;
	}
	if ( ! empty( $player ) ) {
		$competition_link .= 'player/' . seo_url( $player->display_name ) . '/';
	}
	?>
	<li class="list__item col-12 <?php echo empty( $full_width ) ? 'col-sm-6' : null; ?>">
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
							<span class="nav-link__value"><?php echo esc_html( $competition_name ); ?></span>
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
				<div class="media__aside">
					<?php
					if ( $competition->is_open ) {
						?>
						<a href="/entry-form/<?php echo esc_attr( seo_url( $competition->name ) ); ?>/<?php echo esc_attr( $competition->current_season['name'] ); ?>/" class="btn btn-primary">
							<i class="racketmanager-svg-icon">
								<?php racketmanager_the_svg( 'icon-pencil' ); ?>
							</i>
							<span class="btn__text"><?php esc_html_e( 'Enter', 'racketmanager' ); ?></span>
						</a>
						<?php
					}
					?>
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
