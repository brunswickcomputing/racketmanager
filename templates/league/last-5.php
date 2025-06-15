<?php
/**
 * Template for last 5 matches
 *
 * @package Racketmanager/Templates/League
 */

namespace Racketmanager;

/** @var array $matches */
/** @var int   $team_id */
?>
<ul class="list--inline list">
    <?php
    foreach ( $matches as $match ) {
        if ( $team_id === $match->winner_id ) {
            $match_status_class = 'winner';
            $match_status_text  = 'W';
        } elseif ( $team_id === $match->loser_id ) {
            $match_status_class = 'loser';
            $match_status_text  = 'L';
        } elseif ( '-1' === $match->winner_id && '-1' === $match->loser_id ) {
            $match_status_class = 'tie';
            $match_status_text  = 'T';
        } else {
            $match_status_class = 'unknown';
            $match_status_text  = '?';
        }
        ?>
        <li class="list__item">
            <span class="match__status <?php echo esc_attr( $match_status_class ); ?>"  data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo esc_attr( $match->tooltip_title ); ?>">
                <?php echo esc_html( $match_status_text ); ?>
            </span>
        </li>
        <?php
    }
    ?>
</ul>
