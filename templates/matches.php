<?php
/**
 * Template page for the match table in tennis
 *
 * @package Racketmanager/Templates
 *
 * The following variables are usable:
 *  $league: contains data of current league
 *  $matches: contains all matches for current league
 *  $teams: contains teams of current league in an associative array
 *  $season: current season
 *
 * You can check the content of a variable when you insert the tag <?php var_dump($variable)
 */

namespace Racketmanager;

/** @var object $league */
/** @var array  $matches */
?>
<div class="module module--card">
    <div class="module__banner">
        <h3 class="module__title"><?php esc_html_e( 'Matches', 'racketmanager' ); ?></h3>
    </div>
    <div class="module__content">
        <div class="module-container">
            <?php
            if ( ! $league->event->is_box ) {
                include 'matches-selections.php';
            }
            if ( $matches ) {
                $show_header = false;
                if ( $league->event->competition->is_team_entry ) {
                    if ( -1 === $league->match_day ) {
                        $matches_list = $matches;
                        $matches_key  = 'match_day';
                        require RACKETMANAGER_PATH . 'templates/includes/matches-team-list-group.php';
                    } else {
                        require RACKETMANAGER_PATH . 'templates/includes/matches-team-list.php';
                    }
                } else {
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
                    <?php
                }
            } else {
                esc_html_e( 'No matches found', 'racketmanager' );
            }
            ?>
        </div>
    </div>
</div>
