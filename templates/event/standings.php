<?php
/**
 * Template for event standings
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var object $event */
?>
<div class="module module--card">
	<div class="module__banner">
		<h3 class="module__title"><?php esc_html_e( 'Standings', 'racketmanager' ); ?></h3>
	</div>
	<div class="module__content">
		<div class="module-container">
			<div id="leagues">
				<?php
				foreach ( $event->leagues as $league ) {
					?>
					<!-- Standings Table -->
					<div id="standings-archive">
						<h4 class="header">
							<?php
							$href = '/' . __( 'league', 'racketmanager' ) . '/' . seo_url( $league->title ) . '/';
							if ( $event->is_box ) {
								$href .= __( 'round', 'racketmanager' ) . '-';
							}
							$href .= $event->current_season['name'] . '/';
							?>
							<a href="<?php echo esc_url( $href ); ?>">
								<?php echo esc_html( $league->title ); ?>
							</a>
							<?php
							$favourite_type = 'league';
							$favourite_id   = $league->id;
							require RACKETMANAGER_PATH . 'templates/includes/favourite.php';
							?>
						</h4>
						<?php
						racketmanager_league_standings(
							$league->id,
							array(
								'season'   => $event->current_season['name'],
								'template' => '',
							)
						);
						?>
					</div>
					<?php
				}
				?>
			</div>
		</div>
	</div>
</div>
