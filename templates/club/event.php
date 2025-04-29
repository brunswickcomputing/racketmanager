<?php
/**
 *
 * Template page to event for a club
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

$event        = $club->event;
$season       = $event->current_season;
$playerstats  = $club->player_stats;
$header_level = 1;
require RACKETMANAGER_PATH . 'templates/includes/club-header.php';
$header_level = 2;
require RACKETMANAGER_PATH . 'templates/includes/event-header.php';
if ( $event->is_championship ) {
	$heading = 'Round';
	if ( isset( $event->primary_league ) ) {
		$primary_league = get_league( $event->primary_league );
	} else {
		$leagues        = $event->get_leagues();
		$primary_league = get_league( array_key_first( $event->league_index ) );
	}
	$num_cols = $primary_league->championship->num_rounds;
	$rounds   = array();
	$i        = 1;
	foreach ( array_reverse( $primary_league->championship->get_finals() ) as $final ) {
		$rounds[ $i ] = $final;
		++$i;
	}
} else {
	$heading  = __( 'Match Day', 'racketmanager' );
	$num_cols = $season['num_match_days'] ?? 0;
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
								<th class="playername" scope="row">
									<a href="/<?php echo esc_attr( $event->competition->type ); ?>s/<?php echo esc_attr( seo_url( $event->name ) ); ?>/<?php echo esc_attr( $event->current_season['name'] ); ?>/player/<?php echo esc_attr( seo_url( $playerstat->fullname ) ); ?>/">
										<span class="nav__link"><?php echo esc_html( $playerstat->fullname ); ?></span>
									</a>
								</th>
								<?php
								$match_day_stats = $match_day_stats_dummy;
								$prev_match_day  = 0;
								$i               = 0;
								$prev_round      = '';
								foreach ( $playerstat->matchdays as $matches ) {
									if ( ( $event->is_championship && ! $prev_round === $matches->final_round ) || ( ! $event->is_championship && ! $prev_match_day === $matches->match_day ) ) {
										$i = 0;
									}
									if ( $matches->match_winner === $matches->team_id ) {
										$matchresult = __( 'Won', 'racketmanager' );
									} elseif ( $matches->match_loser === $matches->team_id ) {
										$matchresult = __( 'Lost', 'racketmanager' );
									} else {
										$matchresult = __( 'Drew', 'racketmanager' );
									}
									if ( $matches->rubber_winner === $matches->team_id ) {
										$rubberresult = __( 'Won', 'racketmanager' );
									} elseif ( $matches->rubber_loser === $matches->team_id ) {
										$rubberresult = __( 'Lost', 'racketmanager' );
									} else {
										$rubberresult = __( 'Drew', 'racketmanager' );
									}
									$player_line = array(
										'team'         => $matches->team_title,
										'pair'         => $matches->rubber_number,
										'matchresult'  => $matchresult,
										'rubberresult' => $rubberresult,
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
								foreach ( $match_day_stats as $daystat ) {
									$dayshow    = '';
									$stat_title = '';
									foreach ( $daystat as $stat ) {
										if ( isset( $stat['team'] ) ) {
												$stat_title .= $matchresult . ' match & ' . $rubberresult . ' rubber ';
												$team        = str_replace( $club->shortcode, '', $stat['team'] );
												$pair        = $stat['pair'];
												$dayshow    .= $team . '<br />Pair' . $pair . '<br />';
										}
									}
									if ( '' === $dayshow ) {
										echo '<td class="matchday" title=""></td>';
									} else {
										echo '<td class="matchday" title="' . esc_html( $stat_title ) . '">' . $dayshow . '</td>'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									}
								}
								$match_day_stats = $match_day_stats_dummy;
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
