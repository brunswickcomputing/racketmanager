<?php
/**
 *
 * Template page for the specific match results table
 *
 * @package Racketmanager/Templates
 *
 * The following variables are usable:
 *  $matches_list: contains all matches for current league
 *
 * You can check the content of a variable when you insert the tag <?php var_dump($variable)
 */

namespace Racketmanager;

?>
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
	<p><?php echo esc_html_e( 'No recent results', 'racketmanager' ); ?></p>
	<?php
}
?>
