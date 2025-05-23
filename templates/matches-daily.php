<?php
/**
 * Template page for the specific match date match table in tennis
 *
 * @package Racketmanager/Templates
 *
 * The following variables are usable:
 *  $matches_list: contains all matches for current league
 *
 * You can check the content of a variable when you insert the tag <?php var_dump($variable)
 */

namespace Racketmanager;

/** @var string $match_date */
/** @var array  $matches_list */
global $wp_query;
$post_id = $wp_query->post->ID; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
?>
	<div class="module module--card">
		<div class="module__banner">
			<h1 class="module__title"><?php esc_html_e( 'Daily matches', 'racketmanager' ); ?></h1>
		</div>
		<div class="module__content">
			<div class="module-container">
				<div id="racketmanager_match_selections" class="module">
					<form method="get" action="<?php echo esc_html( get_permalink( $post_id ) ); ?>" id="racketmanager_daily_matches">
						<?php wp_nonce_field( 'matches-daily' ); ?>
						<input type="hidden" name="page_id" value="<?php echo esc_html( $post_id ); ?>" />

						<div class="form-group mb-3">
                            <label class="visually-hidden" for="match_date"></label><input type="date" name="match_date" id="match_date" class="form-control match-date" value="<?php echo esc_html( $match_date ); ?>" />
						</div>
					</form>
				</div>
				<div class="module">
					<?php
					if ( $matches_list ) {
						?>
						<?php
						$matches_key = 'league';
						require RACKETMANAGER_PATH . 'templates/includes/matches-team-list-group.php';
						?>
						<?php
					} else {
						?>
						<p><?php esc_html_e( 'No Matches on selected day', 'racketmanager' ); ?></p>
						<?php
					}
					?>
				</div>
			</div>
		</div>
	</div>
