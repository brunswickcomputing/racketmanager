<?php
/**
 * Template page for the tennis match scores
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var array $matches */
?>
<div class="tournament-matches">
	<?php
	foreach ( $matches as $no => $match ) {
		?>
		<?php require RACKETMANAGER_PATH . 'templates/tournament/match.php'; ?>
		<?php
	}
	?>
</div>

<?php the_matches_pagination(); ?>
