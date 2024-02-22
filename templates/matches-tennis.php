<?php
/**
 * Template page for the match table in tennis
 *
 * @package Racketmanager/Templates
 *
 * The following variables are usable:
 *  $league: contains data of current league
 *  $matches: contains all matches for current league
 *  $teams: contains teams of current league in an assosiative array
 *  $season: current season
 *
 * You can check the content of a variable when you insert the tag <?php var_dump($variable)
 */

namespace Racketmanager;

?>
<div class="module module--card">
	<div class="module__banner">
		<h3 class="module__title"><?php esc_html_e( 'Matches', 'racketmanager' ); ?></h3>
	</div>
	<div class="module__content">
		<div class="module-container">
			<?php
			if ( is_single_match() ) {
				the_single_match();
			} else {
				include 'matches-selections.php';
				if ( $matches ) {
					$show_header = false;
					if ( -1 === $league->match_day ) {
						$matches_list = $matches;
						$matches_key  = 'match_day';
						require RACKETMANAGER_PATH . 'templates/includes/matches-team-list-group.php';
					} else {
						require RACKETMANAGER_PATH . 'templates/includes/matches-team-list.php';
					}
				}
			}
			?>
		</div>
	</div>
</div>
