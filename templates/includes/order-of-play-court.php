<?php
/**
 * Template for tournament order of play for a court
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var array $court_times */
foreach ( $court_times as $matches ) {
    ?>
    <div class="match-group__item-wrapper<?php echo empty( $is_expanded ) ? null : ' is-expanded'; ?>">
    <?php
    foreach ( $matches as $final_match ) {
        global $racketmanager;
        $fixture_repo = isset( $racketmanager->container ) && $racketmanager->container->has( 'fixture_repository' ) ? $racketmanager->container->get( 'fixture_repository' ) : null;
        $match        = $fixture_repo ? $fixture_repo->find_by_id( (int) $final_match->id ) : ( function_exists( 'Racketmanager\get_match' ) ? get_match( $final_match->id ) : null );
        if ( ! $match ) {
            continue;
        }
        $match_teams  = method_exists( $match, 'get_teams' ) ? $match->get_teams() : ( $match->teams ?? array() );
        $league_obj   = method_exists( $match, 'get_league' ) ? $match->get_league() : ( $match->league ?? null );
        $league_title = $league_obj ? ( $league_obj->title ?? '' ) : '';
        ?>
            <div class="match-group__item">
                <div class="match__header-title">
                    <span><?php echo esc_html( $league_title ); ?></span>
                </div>
                <?php
                if ( is_numeric( $match->home_team ) ) {
                    $home_match_title = $match_teams['home']->title ?? '';
                } else {
                    $home_match_title = isset( $match->prev_home_match->match_title ) ? $match->prev_home_match->match_title : ( $match_teams['home']->title ?? '' );
                }
                if ( is_numeric( $match->away_team ) ) {
                    $away_match_title = $match_teams['away']->title ?? '';
                } else {
                    $away_match_title = isset( $match->prev_away_match->match_title ) ? $match->prev_away_match->match_title : ( $match_teams['away']->title ?? '' );
                }
                ?>
                <div class="match__body-title<?php echo is_numeric( $match->home_team ) ? null : ' is_pending'; ?>">
                    <?php echo esc_html( $home_match_title ); ?>
                </div>
                <div class="team-separator"><?php esc_html_e( 'vs', 'racketmanager' ); ?></div>
                <div class="match__body-title<?php echo is_numeric( $match->away_team ) ? null : ' is_pending'; ?>">
                    <?php echo esc_html( $away_match_title ); ?>
                </div>
            </div>
        <?php
    }
    ?>
    </div>
<?php
}

