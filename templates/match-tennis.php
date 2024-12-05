<?php
/**
 * Template page for a single match
 * The following variables are usable:
 * $match: contains data of displayed match
 * You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

global $wp_query, $racketmanager;
$post_id = $wp_query->post->ID; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
?>
<?php
if ( $match ) {
	?>
	<div id="match-header" class="team-match-header module module--dark module--card">
		<?php echo $racketmanager->show_match_header( $match ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
	<div class="module module--card">
		<div class="module__content">
			<div class="module-container">
				<div class="match" id="match-<?php echo esc_html( $match->id ); ?>">
					<?php
					if ( is_user_logged_in() ) {
						?>
						<div id="viewMatchRubbers">
							<?php require RACKETMANAGER_PATH . 'templates/includes/loading.php'; ?>
							<div id="showMatchRubbers">
								<?php echo $racketmanager->show_match_screen( $match ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</div>
						</div>
						<?php
					} else {
						?>
						<div class="row justify-content-center">
							<div class="col-auto">
								<?php esc_html_e( 'You need to ', 'racketmanager' ); ?><a href="<?php echo esc_url( wp_login_url( wp_get_current_url() ) ); ?>"><?php esc_html_e( 'login', 'racketmanager' ); ?></a> <?php esc_html_e( 'to enter match information', 'racketmanager' ); ?>
							</div>
						</div>
						<?php
					}
					?>
				</div>
			</div>
		</div>
	</div>
	<?php
}
?>
