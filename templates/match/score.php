<?php
/**
 * Template for match score
 *
 * @package Racketmanager/Templates/Match
 */

namespace Racketmanager;

/** @var object $match */
/** @var string $score_class */
/** @var string $link_title */
/** @var float  $score_team_1 */
/** @var float  $score_team_2 */
/** @var bool   $home_away */
?>
<a href="<?php echo esc_html( $match->link ); ?>">
    <span class="score <?php echo esc_attr( $score_class ); ?>" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo esc_attr( $link_title ); ?>">
        <span class="is-team-1"><?php echo esc_html( sprintf( '%g', $score_team_1 ) ); ?></span>
        <?php
        if ( $home_away ) {
            ?>
            <span class="score-separator">-</span>
            <span class="is-team-2"><?php echo esc_html( sprintf( '%g', $score_team_2 ) ); ?></span>
            <?php
        }
        ?>
    </span>
</a>
