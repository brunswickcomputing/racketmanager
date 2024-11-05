<?php
/**
 * Competition settings standings administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

?>
<?php
if ( ! $league->event->competition->is_championship ) {
	?>
	<div class="alignright">
		<form action="admin.php" method="get">
			<input type="hidden" name="page" value="racketmanager" />
			<input type="hidden" name="subpage" value="show-league" />
			<input type="hidden" name="league_id" value="<?php echo esc_html( $league->id ); ?>" />
			<input type="hidden" name="season" value="<?php echo esc_html( $season ); ?>" />
			<?php echo $league->get_standings_selection(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<input type="submit" class="btn btn-secondary" value="<?php esc_html_e( 'Show', 'racketmanager' ); ?>" />
		</form>
	</div>
	<?php
}
?>
<form id="teams-filter" action="" method="post" name="standings">
	<input type="hidden" name="js-active" value="0" class="js-active" />
	<input type="hidden" name="league-tab" value="preliminary" />
	<input type="hidden" name="season" value="<?php echo esc_html( $season ); ?>" />
	<?php wp_nonce_field( 'racketmanager_teams-bulk', 'racketmanager_nonce' ); ?>
	<?php $sport = ( isset( $league->sport ) ? $league->sport : '' ); ?>
	<div class="tablenav">
		<!-- Bulk Actions -->
		<select name="action" size="1">
			<option value="-1" selected="selected"><?php esc_html_e( 'Bulk Actions', 'racketmanager' ); ?></option>
			<option value="delete"><?php esc_html_e( 'Delete', 'racketmanager' ); ?></option>
			<option value="withdraw"><?php esc_html_e( 'Withdraw', 'racketmanager' ); ?></option>
		</select>
		<input type="submit" value="<?php esc_html_e( 'Apply', 'racketmanager' ); ?>" name="doaction" id="doaction" class="btn btn-secondary action" />
	</div>
	<table id="standings" class="table table-striped table-borderless" aria-describedby="<?php esc_html_e( 'Standings table', 'racketmanager' ); ?>">
		<thead>
			<tr>
				<th scope="col" class="check-column">
					<input type="checkbox" id="check-all-teams" onclick="Racketmanager.checkAll(document.getElementById('teams-filter'));" />
				</th>
				<th class="column-num" scope="column"><?php esc_html_e( 'Rank', 'racketmanager' ); ?></th>
				<?php
				if ( ! $league->event->competition->is_championship ) {
					?>
					<th class="column-num" scope="column">&#160;</th><?php } ?>
				<th scope="column"><?php esc_html_e( 'Team', 'racketmanager' ); ?></th>
				<?php
				if ( $league->event->competition->is_championship ) {
					?>
					<th scope="column"><?php esc_html_e( 'Rating', 'racketmanager' ); ?></th>
					<?php
				} else {
					if ( ! empty( $league->groups ) ) {
						?>
						<th class="column-num" scope="column"><?php esc_html_e( 'Group', 'racketmanager' ); ?></th>
						<?php
					}
					if ( isset( $league->standings['pld'] ) && 1 === $league->standings['pld'] ) {
						?>
						<th class="column-num" scope="column"><?php esc_html_e( 'Pld', 'racketmanager' ); ?></th>
						<?php
					}
					if ( isset( $league->standings['won'] ) && 1 === $league->standings['won'] ) {
						?>
						<th class="column-num" scope="column"><?php esc_html_e( 'W', 'racketmanager' ); ?></th>
						<?php
					}
					if ( isset( $league->standings['tie'] ) && 1 === $league->standings['tie'] ) {
						?>
						<th class="column-num" scope="column"><?php esc_html_e( 'T', 'racketmanager' ); ?></th>
						<?php
					}
					if ( isset( $league->standings['lost'] ) && 1 === $league->standings['lost'] ) {
						?>
						<th class="column-num" scope="column"><?php esc_html_e( 'L', 'racketmanager' ); ?></th>
						<?php
					}
					if ( isset( $league->standings['winPercent'] ) && 1 === $league->standings['winPercent'] ) {
						?>
						<th class="column-num" scope="column"><?php esc_html_e( 'PCT', 'racketmanager' ); ?></th>
						<?php
					}
					if ( ! empty( $league->standings['sets'] ) ) {
						?>
						<th class="column-num" scope="column">
							<?php esc_html_e( 'Sets', 'racketmanager' ); ?>
						</th>
						<?php
					}
					if ( ! empty( $league->standings['games'] ) ) {
						?>
						<th class="column-num" scope="column">
							<?php esc_html_e( 'Games', 'racketmanager' ); ?>
						</th>
						<?php
					}
					?>
					<th class="column-num" scope="column"><?php esc_html_e( 'Pts', 'racketmanager' ); ?></th>
					<th class="column-num" scope="column"><?php esc_html_e( '+/- Points', 'racketmanager' ); ?></th>
					<th class="column-num" scope="column"><?php esc_html_e( 'ID', 'racketmanager' ); ?></th>
					<?php
				}
				?>
			</tr>
		</thead>
		<tbody id="the-list-standings" class="lm-form-table standings-table
			<?php
			if ( 'manual' === $league->event->competition->team_ranking ) {
				echo ' sortable';
			}
			?>
		">
			<?php
			$class = '';
			foreach ( $teams as $i => $team ) {
				$class = null;
				if ( $league->is_championship && $i < $league->championship->num_seeds ) {
					$class = 'seeded';
				}
				?>
				<tr class="<?php echo esc_html( $class ); ?>" id="team_<?php echo esc_html( $team->id ); ?>">
					<th scope="row" class="check-column">
						<input type="hidden" name="team_id[<?php echo esc_html( $team->id ); ?>]" value="<?php echo esc_html( $team->id ); ?>" />
						<input type="checkbox" value="<?php echo esc_html( $team->id ); ?>" name="team[<?php echo esc_html( $team->id ); ?>]" />
					</th>
					<td class="column-num">
						<?php
						if ( 'manual' === $league->event->competition->team_ranking ) {
							?>
							<input type="text" name="rank[<?php echo esc_html( $team->id ); ?>]" size="2" id="rank_<?php echo esc_html( $team->id ); ?>" class="rank-input" value="<?php echo esc_html( $team->rank ); ?>" /><input type="hidden" name="table_id[<?php echo esc_html( $team->table_id ); ?>]" value="<?php echo esc_html( $team->table_id ); ?>" />
							<?php
						} else {
							?>
							<?php echo esc_html( $i + 1 );// team rank. ?>
							<?php
						}
						?>
					</td>
					<?php
					if ( ! $league->event->competition->is_championship ) {
						?>
						<td class="column-num">
							<?php echo esc_html( $team->status ); ?>
						</td>
					<?php } ?>
					<td>
						<a href="admin.php?page=racketmanager&amp;subpage=team&amp;league_id=<?php echo esc_html( $league->id ); ?>&amp;edit=<?php echo esc_html( $team->id ); ?>">
							<?php
							if ( $team->is_withdrawn ) {
								$title_text = $team->title . ' ' . __( 'has withdrawn', 'racketmanager' );
								?>
								<s aria-label="<?php echo esc_attr( $title_text ); ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="<?php echo esc_attr( $title_text ); ?>">
								<?php
							}
							if ( 1 === $team->home ) {
								echo '<strong>' . esc_html( $team->title ) . '</strong>';
							} else {
								echo esc_html( $team->title );
							}
							?>
							<?php
							if ( $team->is_withdrawn ) {
								?>
								</s>
								<?php
							}
							?>
						</a>
					</td>
					<?php
					if ( ! empty( $league->groups ) && $league->event->competition->is_championship ) {
						?>
						<td class="column-num"><?php echo esc_html( $team->group ); ?></td>
						<?php
					}
					if ( $league->event->competition->is_championship ) {
						?>
						<td class="column-num"><?php echo esc_html( $team->profile ); ?></td>
						<input type="hidden" name="rating_points[<?php echo esc_html( $team->id ); ?>]" value="<?php echo esc_html( $team->profile ); ?>" />
						<?php
					}
					if ( ! $league->event->competition->is_championship ) {
						if ( 'manual' !== $league->point_rule ) {
							if ( isset( $league->standings['pld'] ) && 1 === $league->standings['pld'] ) {
								?>
								<td class="column-num"><?php echo esc_html( $team->done_matches ); ?></td>
								<?php
							}
							if ( isset( $league->standings['won'] ) && 1 === $league->standings['won'] ) {
								?>
								<td class="column-num"><?php echo esc_html( $team->won_matches ); ?></td>
								<?php
							}
							if ( isset( $league->standings['tie'] ) && 1 === $league->standings['tie'] ) {
								?>
								<td class="column-num"><?php echo esc_html( $team->draw_matches ); ?></td>
								<?php
							}
							if ( isset( $league->standings['lost'] ) && 1 === $league->standings['lost'] ) {
								?>
								<td class="column-num"><?php echo esc_html( $team->lost_matches ); ?></td>
								<?php
							}
							if ( isset( $league->standings['winPercent'] ) && 1 === $league->standings['winPercent'] ) {
								?>
								<td class="column-num"><?php echo esc_html( $team->win_percent ); ?></td>
								<?php
							}
						} else {
							?>
							<td class="column-num">
								<?php
								if ( 1 === $league->standings['pld'] ) {
									?>
									<input type="text" size="2" name="num_done_matches[<?php echo esc_html( $team->id ); ?>]" value="<?php echo esc_html( $team->done_matches ); ?>" />
								<?php } else { ?>
									<input type="hidden" name="num_done_matches[<?php echo esc_html( $team->id ); ?>]" value="0" />
								<?php } ?>
							</td>
							<td class="column-num">
								<?php
								if ( 1 === $league->standings['won'] ) {
									?>
									<input type="text" size="2" name="num_won_matches[<?php echo esc_html( $team->id ); ?>]" value="<?php echo esc_html( $team->won_matches ); ?>" />
								<?php } else { ?>
									<input type="hidden" name="num_won_matches[<?php echo esc_html( $team->id ); ?>]" value="0" />
								<?php } ?>
							</td>
							<td class="column-num">
								<?php
								if ( 1 === $league->standings['tie'] ) {
									?>
									<input type="text" size="2" name="num_draw_matches[<?php echo esc_html( $team->id ); ?>]" value="<?php echo esc_html( $team->draw_matches ); ?>" />
								<?php } else { ?>
									<input type="hidden" name="num_draw_matches[<?php echo esc_html( $team->id ); ?>]" value="0" />
								<?php } ?>
							</td>
							<td class="column-num">
								<?php
								if ( 1 === $league->standings['lost'] ) {
									?>
									<input type="text" size="2" name="num_lost_matches[<?php echo esc_html( $team->id ); ?>]" value="<?php echo esc_html( $team->lost_matches ); ?>" />
								<?php } else { ?>
									<input type="hidden" name="num_lost_matches[<?php echo esc_html( $team->id ); ?>]" value="0" />
								<?php } ?>
							</td>
							<?php
						}
						if ( ! empty( $league->standings['sets'] ) ) {
							?>
							<td class="column-num">
								<?php echo esc_html( $team->sets_won . '-' . $team->sets_allowed ); ?>
							</td>
							<?php
						}
						if ( ! empty( $league->standings['games'] ) ) {
							?>
							<td class="column-num">
								<?php echo esc_html( $team->games_won . '-' . $team->games_allowed ); ?>
							</td>
							<?php
						}
						?>
						<?php do_action( 'racketmanager_standings_columns_' . $league->sport, $team, $league->point_rule ); ?>
						<?php $league->display_standings_columns( $team, $league->point_rule ); ?>
						<td class="column-num">
							<?php
							if ( 'manual' !== $league->point_rule ) {
								?>
								<?php echo esc_html( sprintf( $league->point_format, $team->points_plus, $team->points_minus ) ); ?>
								<?php
							} else {
								?>
								<input type="text" size="2" name="points_plus[<?php echo esc_html( $team->id ); ?>]" value="<?php echo esc_html( $team->points_plus ); ?>" /> : <input type="text" size="2" name="points_minus[<?php echo esc_html( $team->id ); ?>]" value="<?php echo esc_html( $team->points_minus ); ?>" />
								<?php
							}
							?>
						</td>
						<td class="column-num">
							<input type="text" size="3" style="text-align: center;" id="add_points_<?php echo esc_html( $team->id ); ?>" name="add_points[<?php echo esc_html( $team->id ); ?>]" value="<?php echo esc_html( $team->add_points ); ?>" onblur="Racketmanager.saveAddPoints(this.value, <?php echo esc_html( $team->id ); ?>, <?php echo esc_html( $league->id ); ?>, <?php echo esc_html( $season ); ?> )" />
							<span class="loading" id="loading_<?php echo esc_html( $team->id ); ?>"></span>
							<span id="feedback_<?php echo esc_html( $team->id ); ?>"></span>
						</td>
						<td class="column-num">
							<?php echo esc_html( $team->id ); ?>
						</td>
						<?php
					}
					?>
				</tr>
				<?php
			}
			?>
		</tbody>
	</table>
	<?php
	if ( isset( $league->event->competition->team_ranking ) && 'manual' === $league->event->competition->team_ranking && $league->event->competition->is_championship ) {
		?>
		<script type='text/javascript'>
		</script>
		<?php
	}
	if ( isset( $league->point_rule ) && 'manual' === $league->point_rule ) {
		?>
		<input type="hidden" name="updateLeague" value="teams_manual" />
		<input type="submit" value="<?php esc_html_e( 'Save Standings', 'racketmanager' ); ?>" class="btn btn-primary" />
		<?php
	}
	if ( isset( $league->event->competition->team_ranking ) && 'manual' === $league->event->competition->team_ranking ) {
		?>
		<div class="mb-3">
			<input type="submit" name="saveRanking" value="<?php esc_html_e( 'Save Ranking', 'racketmanager' ); ?>" class="btn btn-primary" />
		</div>
		<div class="mb-3">
			<input type="submit" name="randomRanking" value="<?php esc_html_e( 'Random Rank', 'racketmanager' ); ?>" class="btn btn-secondary" />
			<input type="submit" name="ratingPointsRanking" value="<?php esc_html_e( 'Rating Points Rank', 'racketmanager' ); ?>" class="btn btn-secondary" />
		</div>
		<?php
	}
	if ( isset( $league->event->competition->team_ranking ) && 'manual' !== $league->event->competition->team_ranking ) {
		?>
		<input type="submit" name="updateRanking" value="<?php esc_html_e( 'Update Ranking', 'racketmanager' ); ?>" class="btn btn-primary" />
		<?php
	}
	?>
</form>
