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
if ( empty( $current_club ) ) {
	$current_club = null;
}
if ( empty( $league->team->id ) ) {
	$current_team = null;
} else {
	$current_team = $league->team->id;
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
$selected_match = false;
foreach ( $matches as $match ) {
	$user_can_update_array = $racketmanager->is_match_update_allowed( $match->teams['home'], $match->teams['away'], $match->league->event->competition->type, $match->confirmed );
	$user_can_update       = $user_can_update_array[0];
	$match_link            = $match->link;
	$match_status_class    = null;
	$match_status_text     = null;
	$score_class           = null;
	$match_pending         = false;
	$highlight_match       = false;
	$selected_match        = false;
	if ( empty( $match->winner_id ) ) {
		$score_class   = 'is-not-played';
		$match_pending = true;
	} elseif ( ! empty( $current_club ) || ! empty( $current_team ) ) {
		$opponents = array( 'home', 'away' );
		foreach ( $opponents as $opponent ) {
			if ( ( ! empty( $current_club ) && $match->teams[ $opponent ]->club->id === $current_club ) || ( ! empty( $current_team ) && $match->teams[ $opponent ]->id === $current_team ) ) {
				$highlight_match = true;
				if ( $match->teams[ $opponent ]->id === $match->winner_id ) {
					$match_status_class = 'winner';
					$match_status_text  = 'W';
				} elseif ( $match->teams[ $opponent ]->id === $match->loser_id ) {
					$match_status_class = 'loser';
					$match_status_text  = 'L';
				} elseif ( '-1' === $match->loser_id ) {
					$match_status_class = 'tie';
					$match_status_text  = 'T';
				} else {
					$match_status_class = '';
					$match_status_text  = '';
				}
			}
		}
	}
	if ( ! $highlight_match && is_user_logged_in() ) {
		$player_args           = array();
		$player_args['count']  = true;
		$player_args['player'] = get_current_user_id();
		$player_args['team']   = $match->home_team;
		$player_args['active'] = true;
		$player_selected       = $racketmanager->get_club_players( $player_args );
		if ( $player_selected ) {
			$selected_match = true;
		} else {
			$player_args['team'] = $match->away_team;
			$player_selected     = $racketmanager->get_club_players( $player_args );
			if ( $player_selected ) {
				$selected_match = true;
			}
		}
	}
	?>
	<ul class="match-group">
		<li class="match-group__item">
			<?php require RACKETMANAGER_PATH . 'templates/includes/matches-teams-match.php'; ?>
		</li>
	</ul>
	<?php
}
