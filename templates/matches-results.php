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

/** @var string $header_level */
/** @var array  $matches_list */
?>
<div class="module module--card">
	<div class="module__banner">
		<h<?php echo esc_attr( $header_level ); ?> class="module__title"><?php esc_html_e( 'Latest Results', 'racketmanager' ); ?></h<?php echo esc_attr( $header_level ); ?>>
	</div>
	<div class="module__content">
		<div class="module-container">
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
					<p><?php esc_html_e( 'No recent results', 'racketmanager' ); ?></p>
					<?php
				}
				?>
			</div>
		</div>
	</div>
</div>
