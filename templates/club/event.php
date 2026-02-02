<?php
/**
 *
 * Template page to event for a club
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var object $club */
/** @var array $current_season */
$event          = $club->event;
$playerstats    = $club->player_stats;
$rounds         = array();
$primary_league = null;
$header_level   = 1;
$curr_season    = $current_season['name'];
require RACKETMANAGER_PATH . 'templates/includes/club-header.php';
$header_level = 2;
require RACKETMANAGER_PATH . 'templates/includes/event-header.php';
if ( $event->is_championship ) {
    if ( isset( $event->primary_league ) ) {
        $primary_league = get_league( $event->primary_league );
    } else {
        $primary_league = get_league( array_key_first( $event->league_index ) );
    }
    $num_cols = $primary_league->championship->num_rounds;
    $i        = 1;
    foreach ( array_reverse( $primary_league->championship->get_finals() ) as $final ) {
        $rounds[ $i ] = $final;
        ++$i;
    }
} else {
    $num_cols = $current_season['num_match_days'] ?? 0;
}
?>
<div class="module module--card">
    <div class="module__banner">
        <h3 class="media__title">
            <span><?php esc_html_e( 'Player Matches', 'racketmanager' ); ?></span>
        </h3>
    </div>
    <div class="module__content">
        <div class="module-container">
            <table class="playerstats" title="RacketManager Player Stats" aria-describedby="<?php esc_html_e( 'Club Player Statistics', 'racketmanager' ); ?>">
                <thead>
                    <th><?php esc_html_e( 'Player', 'racketmanager' ); ?></th>
                    <?php
                    $match_day_stats_dummy = array();
                    for ( $day = 1; $day <= $num_cols; $day++ ) {
                        $match_day_stats_dummy[ $day ] = array();
                        ?>
                        <th class="matchday">
                            <?php
                            if ( $event->is_championship ) {
                                echo esc_html( $rounds[ $day ]['name'] );
                            } else {
                                echo esc_html( $day );
                            }
                            ?>
                        </th>
                        <?php
                    }
                    ?>
                </thead>
                <tbody id="the-list">
                    <?php
                    if ( $playerstats ) {
                        $class = '';
                        foreach ( $playerstats as $playerstat ) {
                            ?>
                            <?php $class = ( 'alternate' === $class ) ? '' : 'alternate'; ?>
                            <tr class="<?php echo esc_html( $class ); ?>">
                                <th class="player-name" scope="row">
                                    <a href="/<?php echo esc_attr( $event->competition->type ); ?>s/<?php echo esc_attr( seo_url( $event->name ) ); ?>/<?php echo esc_attr( $event->current_season['name'] ); ?>/player/<?php echo esc_attr( seo_url( $playerstat->fullname ) ); ?>/">
                                        <span class="nav__link"><?php echo esc_html( $playerstat->fullname ); ?></span>
                                    </a>
                                </th>
                                <?php
                                $match_day_stats = $match_day_stats_dummy;
                                $prev_match_day  = 0;
                                $i               = 0;
                                $prev_round      = '';
                                $match_result    = null;
                                $rubber_result   = null;
                                foreach ( $playerstat->matchdays as $matches ) {
                                    if ( ( $event->is_championship && ! $prev_round === $matches->final_round ) || ( ! $event->is_championship && ! $prev_match_day === $matches->match_day ) ) {
                                        $i = 0;
                                    }
                                    if ( $matches->match_winner === $matches->team_id ) {
                                        $match_result = __( 'Won', 'racketmanager' );
                                    } elseif ( $matches->match_loser === $matches->team_id ) {
                                        $match_result = __( 'Lost', 'racketmanager' );
                                    } else {
                                        $match_result = __( 'Drew', 'racketmanager' );
                                    }
                                    if ( $matches->rubber_winner === $matches->team_id ) {
                                        $rubber_result = __( 'Won', 'racketmanager' );
                                    } elseif ( $matches->rubber_loser === $matches->team_id ) {
                                        $rubber_result = __( 'Lost', 'racketmanager' );
                                    } else {
                                        $rubber_result = __( 'Drew', 'racketmanager' );
                                    }
                                    $player_line = array(
                                        'team'         => $matches->team_title,
                                        'pair'         => $matches->rubber_number,
                                        'match_result'  => $match_result,
                                        'rubber_result' => $rubber_result,
                                    );
                                    if ( $event->is_championship ) {
                                        $d                           = $primary_league->championship->get_finals( $matches->final_round )['round'];
                                        $match_day_stats[ $d ][ $i ] = $player_line;
                                    } else {
                                        $match_day_stats[ $matches->match_day ][ $i ] = $player_line;
                                    }
                                    $prev_match_day = $matches->match_day;
                                    $prev_round     = $matches->final_round;
                                    ++$i;
                                }
                                foreach ( $match_day_stats as $day_stat ) {
                                    $day_show    = '';
                                    $stat_title = '';
                                    foreach ( $day_stat as $stat ) {
                                        if ( isset( $stat['team'] ) ) {
                                                $stat_title .= $match_result . ' match & ' . $rubber_result . ' rubber ';
                                                $team        = str_replace( $club->shortcode, '', $stat['team'] );
                                                $pair        = $stat['pair'];
                                                $day_show    .= $team . '<br />Pair' . $pair . '<br />';
                                        }
                                    }
                                    if ( '' === $day_show ) {
                                        echo '<td class="matchday" title=""></td>';
                                    } else {
                                        echo '<td class="matchday" title="' . esc_html( $stat_title ) . '">' . $day_show . '</td>'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                    }
                                }
                                ?>
                            </tr>
                            <?php
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
