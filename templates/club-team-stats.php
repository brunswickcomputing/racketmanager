<?php
/**
 *
 * Template page to display club team player stats
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

?>
<div class="tab-pane fade" id="players-<?php echo esc_html( $event->id ); ?>" role="tabpanel" aria-labelledby="players-tab-<?php echo esc_html( $event->id ); ?>">
												<?php $season = $event->get_season_event(); ?>
												<table class="playerstats" title="RacketManager Player Stats" aria-describedby="<?php esc_html_e( 'Club Player Statistics', 'racketmanager' ); ?>">
													<thead>
														<tr>
															<th rowspan="2" scope="col" class="playername"><?php esc_html_e( 'Name', 'racketmanager' ); ?></th>
															<th colspan="<?php echo esc_html( $season['num_match_days'] ); ?>" scope="colgroup" class="colspan"><?php esc_html_e( 'Match Day', 'racketmanager' ); ?></th>
														</tr>
														<tr>
															<?php
															$matchdaystatsdummy = array();
															for ( $day = 1; $day <= $season['num_match_days']; $day++ ) {
																$matchdaystatsdummy[ $day ] = array();
																?>
																<th scope="col" class="matchday"><?php echo esc_html( $day ); ?></th>
															<?php } ?>
														</tr>
													</thead>
													<tbody id="the-list">
														<?php
														$playerstats = $event->get_player_stats(
															array(
																'season' => $season['name'],
																'club'   => $club->id,
															)
														);
														if ( $playerstats ) {
															$class = '';
															foreach ( $playerstats as $playerstat ) {
																?>
																<?php $class = ( 'alternate' === $class ) ? '' : 'alternate'; ?>
																<tr class="<?php echo esc_html( $class ); ?>">
																	<th class="playername" scope="row"><?php echo esc_html( $playerstat->fullname ); ?></th>
																	<?php
																	$match_day_stats = $matchdaystatsdummy;
																	$prev_match_day  = 0;
																	$i               = 0;
																	foreach ( $playerstat->matchdays as $matches ) {
																		if ( ! $prev_match_day === $matches->match_day ) {
																			$i = 0;
																		}
																		if ( $matches->match_winner === $matches->team_id ) {
																			$matchresult = 'Won';
																		} else {
																			$matchresult = 'Lost';
																		}
																		$matchresult                                  = $matches->match_winner === $matches->team_id ? 'Won' : 'Lost';
																		$rubberresult                                 = $matches->rubber_winner === $matches->team_id ? 'Won' : 'Lost';
																		$match_day_stats[ $matches->match_day ][ $i ] = array(
																			'team' => $matches->team_title,
																			'pair' => $matches->rubber_number,
																			'matchresult' => $matchresult,
																			'rubberresult' => $rubberresult,
																		);
																		$prev_match_day                               = $matches->match_day;
																		++$i;
																	}
																	foreach ( $match_day_stats as $daystat ) {
																		$dayshow    = '';
																		$stat_title = '';
																		foreach ( $daystat as $stat ) {
																			if ( isset( $stat['team'] ) ) {
																					$stat_title .= $matchresult . ' match & ' . $rubberresult . ' rubber ';
																					$team        = str_replace( $short_code, '', $stat['team'] );
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
																	$match_day_stats = $matchdaystatsdummy;
																	?>
																</tr>
															<?php } ?>
														<?php } ?>
													</tbody>
												</table>
											</div>
