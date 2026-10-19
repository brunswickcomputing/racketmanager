<?php
/**
 * Template for tournament order of play for a court
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

use Racketmanager\Presentation\View_Models\Order_Of_Play_Match_View_Model;

/** @var array $court_times */
foreach ( $court_times as $matches ) {
    ?>
    <div class="match-group__item-wrapper<?php echo empty( $is_expanded ) ? null : ' is-expanded'; ?>">
    <?php
    foreach ( $matches as $final_match ) {
        if ( ! ( $final_match instanceof Order_Of_Play_Match_View_Model ) ) {
            continue;
        }
        $league_title     = $final_match->league_title;
        $home_match_title = $final_match->home_match_title;
        $away_match_title = $final_match->away_match_title;
        $is_home_pending  = $final_match->is_home_pending;
        $is_away_pending  = $final_match->is_away_pending;
        ?>
            <div class="match-group__item">
                <div class="match__header-title">
                    <span><?php echo esc_html( $league_title ); ?></span>
                </div>
                <div class="match__body-title<?php echo $is_home_pending ? ' is_pending' : ''; ?>">
                    <?php echo esc_html( $home_match_title ); ?>
                </div>
                <div class="team-separator"><?php esc_html_e( 'vs', 'racketmanager' ); ?></div>
                <div class="match__body-title<?php echo $is_away_pending ? ' is_pending' : ''; ?>">
                    <?php echo esc_html( $away_match_title ); ?>
                </div>
            </div>
        <?php
    }
    ?>
    </div>
    <?php
}
