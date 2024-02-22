<?php
/**
 *
 * Template page to display club team matches
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

?>
<div class="tab-pane fade" id="matches-<?php echo esc_html( $event->id ); ?>" role="tabpanel" aria-labelledby="matches-tab-<?php echo esc_html( $event->id ); ?>">
												<?php $season = $event->get_season_event(); ?>
												<table class="mt-3" title="RacketManager Club matches" aria-describedby="<?php esc_html_e( 'Club matches', 'racketmanager' ); ?>">
													<thead>
														<tr>
															<th scope="col"><?php esc_html_e( 'Match date', 'racketmanager' ); ?></th>
															<th scope="col"><?php esc_html_e( 'Match', 'racketmanager' ); ?></th>
															<th scope="col"><?php esc_html_e( 'League', 'racketmanager' ); ?></th>
														</tr>
													</thead>
													<tbody id="the-list">
														<?php
														$matches = $racketmanager->get_matches(
															array(
																'event_id' => $event->id,
																'season'   => $season['name'],
																'affiliatedClub' => $club->id,
																'orderby' => array(
																	'match_day' => 'ASC',
																	'date' => 'ASC',
																	'league_id' => 'ASC',
																	'home_team' => 'ASC',
																),
															)
														);
														if ( $matches ) {
															$class     = '';
															$match_day = '';
															foreach ( $matches as $match ) {
																$class = ( 'alternate' === $class ) ? '' : 'alternate';
																if ( $match_day !== $match->match_day ) {
																	$match_day = $match->match_day;
																	?>
																	<tr class="<?php echo esc_html( $class ); ?>">
																		<td colspan="3"><?php echo esc_html( __( 'Match Day', 'racketmanager' ) . ' ' . $match_day ); ?></td>
																	</tr>
																<?php } ?>
																<tr class="<?php echo esc_html( $class ); ?>">
																	<td><?php echo esc_html( $match->date ); ?></td>
																	<td><?php echo esc_html( $match->match_title ); ?></td>
																	<td><?php echo esc_html( $match->league->title ); ?></td>
																</tr>
															<?php } ?>
														<?php } ?>
													</tbody>
												</table>
											</div>
