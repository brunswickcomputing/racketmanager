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

global $wp_query;
$post_id = $wp_query->post->ID; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
?>
<div id="racketmanager_match_selections container" class="">
	<form method="get" action="<?php echo esc_html( get_permalink( $post_id ) ); ?>" id="racketmanager_daily_matches">
		<?php wp_nonce_field( 'matches-daily' ); ?>
		<input type="hidden" name="page_id" value="<?php echo esc_html( $post_id ); ?>" />

		<div class="form-group mb-3">
			<input type="date" name="match_date" id="match_date" class="form-control match-date" value="<?php echo esc_html( $match_date ); ?>" />
		</div>
	</form>
</div>
<?php
if ( $matches_list ) {
	?>
	<div class="module module--card">
		<div class="module__content">
			<div class="module-container">
				<div class="module">
					<?php
					$matches_key = 'league';
					require RACKETMANAGER_PATH . 'templates/includes/matches-team-list-group.php';
					?>
				</div>
			</div>
		</div>
	</div>
	<?php
} else {
	?>
	<p><?php esc_html_e( 'No Matches on selected day', 'racketmanager' ); ?></p>
	<?php
}
?>
