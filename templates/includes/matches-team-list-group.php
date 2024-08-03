<?php
/**
 * Template for list of teams matches
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

global $racketmanager;
if ( empty( $matches_key ) ) {
	$matches_key = null;
}
if ( ! isset( $show_header ) ) {
	$show_header = true;
	if ( 'league' === $matches_key ) {
		$show_header = false;
	}
}
if ( ! isset( $by_date ) ) {
	$by_date = false;
}
foreach ( $matches_list as $key => $matches ) {
	?>
	<h4 class="module-divider">
		<span class="module-divider__body">
			<?php
			if ( empty( $matches_key ) ) {
				echo esc_html( mysql2date( $racketmanager->date_format, $key ) );
			} elseif ( 'match_day' === $matches_key ) {
				echo esc_html__( 'Match Day', 'racketmanager' ) . ' ' . esc_html( $key );
			} else {
				echo esc_html( $key );
			}
			?>
		</span>
	</h4>
	<?php
	require 'matches-team-list.php';
}
