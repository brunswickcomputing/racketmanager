<?php
/**
 * Template for list of teams matches
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var array $matches */
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
	$is_update_allowed  = $match->is_update_allowed();
	$user_can_update    = $is_update_allowed->user_can_update;
	$match_link         = $match->link;
	$match_status_class = null;
	$match_status_text  = null;
	$score_class        = null;
	$highlight_match    = false;
	$selected_match     = false;
	if ( $match->is_pending ) {
		$score_class = 'is-not-played';
	} elseif ( ! empty( $current_club ) || ! empty( $current_team ) ) {
		$opponents = array( 'home', 'away' );
		foreach ( $opponents as $opponent ) {
			if ( ( ! empty( $current_club ) && ! empty( $match->teams[ $opponent ]->club ) && $match->teams[ $opponent ]->club->id === $current_club ) || ( ! empty( $current_team ) && $match->teams[ $opponent ]->id === $current_team ) ) {
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
			<?php
			if ( 'tournament' === $match->league->event->competition->type ) {
				$match_display = 'list';
				require RACKETMANAGER_PATH . 'templates/tournament/match.php';
			} else {
				require RACKETMANAGER_PATH . 'templates/includes/matches-teams-match.php';
			}
			?>
		</li>
	</ul>
	<?php
}
