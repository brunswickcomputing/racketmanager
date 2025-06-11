<?php
/**
 *
 * Template page to competitions or a competition for a club
 *
 * @package Racketmanager/Templates
 *
 * The following variables are usable:
 *
 *  $club: club object
 */

namespace Racketmanager;

/** @var object $club */
require_once RACKETMANAGER_PATH . 'templates/includes/club-header.php';

foreach ( $club->competitions as $competition ) {
	?>
	<div class="module module--card">
		<div class="module__banner">
			<a href="/<?php echo esc_attr( seo_url( $competition->name ) ); ?>/<?php echo esc_attr( $competition->current_season['name'] ); ?>/club/<?php echo esc_attr( seo_url( $club->shortcode ) ); ?>/">
				<h3 class="module__title"><?php echo esc_html( $competition->name ); ?></h3>
			</a>
		</div>
		<div class="module__content">
			<div class="module-container">
				<ol class="list list--bordered">
				<?php
				foreach ( $competition->events as $event ) {
					if ( isset( $event->current_season['name'] ) ) {
						$event_link = '/clubs/' . seo_url( $club->shortcode ) . '/event/' . seo_url( $event->name ) . '/';
					} else {
						$event_link = null;
					}
					?>
					<li class="list__item">
						<?php
						if ( $event_link ) {
							?>
							<a href="<?php echo esc_attr( $event_link ); ?>">
							<?php
						}
						?>
						<h4 class="module__title"><?php echo esc_html( $event->name ); ?></h4>
						<?php
						if ( $event_link ) {
							?>
						</a>
							<?php
						}
						?>
						<?php
						if ( $event->teams ) {
							?>
							<ol class="list list--naked list--indent">
							<?php
							foreach ( $event->teams as $team ) {
								?>
								<li class="list__item">
                                    <?php require RACKETMANAGER_PATH . 'templates/includes/team.php'; ?>
								</li>
								<?php
							}
							?>
							</ol>
							<?php
						} else {
							esc_html_e( 'Club has no teams in this event', 'racketmanager' );
						}
						?>
					</li>
					<?php
				}
				?>
				</ol>
			</div>
		</div>
	</div>
	<?php
}
