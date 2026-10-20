<?php
/**
 * Template for last 5 matches
 *
 * @package Racketmanager/Templates/League
 */

namespace Racketmanager;

/** @var array $matches */
/** @var int   $team_id */
/** @var array<int|string, string> $match_tooltips */
$match_tooltips = $match_tooltips ?? [];
?>
<ul class="list--inline list">
    <?php
    foreach ( $matches as $match ) {
        $winner_id = is_object( $match ) ? ( $match->winner_id ?? null ) : ( $match['winner_id'] ?? null );
        $loser_id  = is_object( $match ) ? ( $match->loser_id ?? null ) : ( $match['loser_id'] ?? null );
        $match_id  = is_object( $match ) ? ( $match->id ?? null ) : ( $match['id'] ?? null );

        if ( null !== $winner_id && (int) $team_id === (int) $winner_id ) {
            $match_status_class = 'winner';
            $match_status_text  = 'W';
        } elseif ( null !== $loser_id && (int) $team_id === (int) $loser_id ) {
            $match_status_class = 'loser';
            $match_status_text  = 'L';
        } elseif ( '-1' === (string) $winner_id && '-1' === (string) $loser_id ) {
            $match_status_class = 'tie';
            $match_status_text  = 'T';
        } else {
            $match_status_class = 'unknown';
            $match_status_text  = '?';
        }

        $tooltip = ( null !== $match_id && isset( $match_tooltips[ $match_id ] ) )
            ? $match_tooltips[ $match_id ]
            : ( ( is_object( $match ) && isset( $match->tooltip_title ) ) ? $match->tooltip_title : ( ( is_array( $match ) && isset( $match['tooltip_title'] ) ) ? $match['tooltip_title'] : '' ) );
        ?>
        <li class="list__item">
            <span class="match__status <?php echo esc_attr( $match_status_class ); ?>" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo esc_attr( $tooltip ); ?>">
                <?php echo esc_html( $match_status_text ); ?>
            </span>
        </li>
        <?php
    }
    ?>
</ul>
