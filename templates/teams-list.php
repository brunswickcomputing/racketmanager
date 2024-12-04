<?php
/**
 * Template page for Team List
 *
 * @package Racketmanager/Templates
 *
 * The following variables are usable:
 *  $league league object
 *  $teams: all teams of league
 *
 * You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
 */

namespace Racketmanager;

if ( ! empty( $event ) ) {
	$object    = $event;
	$item_link = '/' . $event->competition->type . '/' . seo_url( $event->name ) . '/' . $curr_season;
} elseif ( ! empty( $league ) ) {
	$object    = $league;
	$item_link = '/' . $league->event->competition->type . '/' . seo_url( $league->title ) . '/' . $league->current_season['name'];
}
if ( empty( $object->team ) ) {
	?>
	<div class="module module--card">
		<div class="module__banner">
			<h3 class="module__title"><?php esc_html_e( 'Teams', 'racketmanager' ); ?></h3>
		</div>
		<div class="module__content">
			<div class="module-container">
				<?php
				if ( $object->teams ) {
					$teams = $object->teams;
					?>
					<ol class="list list--bordered">
						<?php
						foreach ( $teams as $team ) {
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
					esc_html_e( 'No teams found', 'racketmanager' );
				}
				?>
			</div>
		</div>
	</div>
	<?php
} else {
	require 'team-details.php';
}
?>
