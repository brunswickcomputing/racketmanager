<?php
/**
 * Template for tournament players
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

?>
<div class="container">
	<?php
	if ( empty( $tournament_player ) ) {
		if ( ! empty( $tournament->players ) ) {
			$player_list = $tournament->players;
			$player_link = '/tournament/' . seo_url( $tournament->name ) . '/players/';
			require RACKETMANAGER_PATH . 'templates/includes/player-list-names.php';
		}
	} else {
		require 'player.php';
	}
	?>
</div>
