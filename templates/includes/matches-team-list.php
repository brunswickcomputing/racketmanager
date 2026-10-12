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
    $is_update_allowed  = method_exists( $match, 'is_update_allowed' )
        ? $match->is_update_allowed()
        : ( $match->is_update_allowed ?? ( isset( $racketmanager->container ) && $racketmanager->container->has( 'fixture_service' )
            ? $racketmanager->container->get( 'fixture_service' )->is_update_allowed( $match )
            : (object) array( 'user_can_update' => false ) ) );
    $user_can_update    = $is_update_allowed->user_can_update ?? false;
    $match_link         = $match->link ?? ( method_exists( $match, 'get_link' ) ? $match->get_link() : '' );
    $match_status_class = null;
    $match_status_text  = null;
    $score_class        = null;
    $highlight_match    = false;
    $selected_match     = false;
    $is_pending         = ! empty( $match->is_pending ) || ( method_exists( $match, 'is_pending' ) && $match->is_pending() );
    if ( $is_pending ) {
        $score_class = 'is-not-played';
    } elseif ( ! empty( $current_club ) || ! empty( $current_team ) ) {
        $opponents = array( 'home', 'away' );
        $winner_id = method_exists( $match, 'get_winner_id' ) ? $match->get_winner_id() : ( $match->winner_id ?? null );
        $loser_id  = method_exists( $match, 'get_loser_id' ) ? $match->get_loser_id() : ( $match->loser_id ?? null );
        $teams     = method_exists( $match, 'get_teams' ) ? $match->get_teams() : ( $match->teams ?? array() );
        foreach ( $opponents as $opponent ) {
            $opponent_team = $teams[ $opponent ] ?? null;
            if ( ( ! empty( $current_club ) && ! empty( $opponent_team->club ) && $opponent_team->club->id === $current_club ) || ( ! empty( $current_team ) && ! empty( $opponent_team->id ) && $opponent_team->id === $current_team ) ) {
                $highlight_match = true;
                if ( ! empty( $opponent_team->id ) && $opponent_team->id === $winner_id ) {
                    $match_status_class = 'winner';
                    $match_status_text  = 'W';
                } elseif ( ! empty( $opponent_team->id ) && $opponent_team->id === $loser_id ) {
                    $match_status_class = 'loser';
                    $match_status_text  = 'L';
                } elseif ( '-1' === (string) $loser_id ) {
                    $match_status_class = 'tie';
                    $match_status_text  = 'T';
                } else {
                    $match_status_class = '';
                    $match_status_text  = '';
                }
            }
        }
    }
    if ( ! $highlight_match && is_user_logged_in() && isset( $racketmanager ) && method_exists( $racketmanager, 'is_player_in_club' ) ) {
        $player_args           = array();
        $player_args['player'] = get_current_user_id();
        $player_args['team']   = method_exists( $match, 'get_home_team' ) ? $match->get_home_team() : ( $match->home_team ?? null );
        $player_args['active'] = true;
        $player_selected       = $racketmanager->is_player_in_club( $player_args );
        if ( $player_selected ) {
            $selected_match = true;
        } else {
            $player_args['team'] = method_exists( $match, 'get_away_team' ) ? $match->get_away_team() : ( $match->away_team ?? null );
            $player_selected     = $racketmanager->is_player_in_club( $player_args );
            if ( $player_selected ) {
                $selected_match = true;
            }
        }
    }
    ?>
    <ul class="match-group">
        <li class="match-group__item">
            <?php
            $match_league           = method_exists( $match, 'get_league' ) ? $match->get_league() : ( $match->league ?? null );
            $competition_type       = $match_league?->event?->competition?->type ?? null;
            $match_competition_type = $competition_type instanceof \BackedEnum ? $competition_type->value : (string) ( $competition_type ?? '' );
            if ( 'tournament' === $match_competition_type ) {
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
