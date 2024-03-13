<?php
/**
 * Match administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

?>
<div class="container">
	<div class="row justify-content-end">
		<div class="col-auto racketmanager_breadcrumb">
			<a href="admin.php?page=racketmanager&amp;subpage=show-competition&amp;competition_id=<?php echo esc_html( $league->event->competition->id ); ?>"><?php echo esc_html( $league->event->competition->name ); ?></a> &raquo;
			<a href="admin.php?page=racketmanager&amp;subpage=show-event&amp;event_id=<?php echo esc_html( $league->event->id ); ?>"><?php echo esc_html( $league->event->name ); ?></a> &raquo;
			<a href="admin.php?page=racketmanager&amp;subpage=show-league&amp;league_id=<?php echo esc_html( $league->id ); ?>"><?php echo esc_html( $league->title ); ?></a> &raquo;
			<?php echo esc_html( $form_title ); ?>
		</div>
	</div>
	<h1><?php echo esc_html( $league->title ) . ' - ' . esc_html( $form_title ); ?></h1>
	<?php
	if ( $matches ) {
		$form_action = 'admin.php?page=racketmanager&amp;subpage=show-league&amp;league_id=' . $league->id . '&amp;season=' . $season;
		if ( isset( $match_day ) ) {
			$form_action .= '&amp;match_day=' . $match_day;
		}
		if ( isset( $finalkey ) && $finalkey > '' ) {
			$form_action .= '&amp;final=' . $finalkey . '&amp;league-tab=matches';
		}
		?>
		<form action="<?php echo esc_html( $form_action ); ?>" method='post'>
			<?php wp_nonce_field( 'racketmanager_manage-matches', 'racketmanager_nonce' ); ?>
			<?php
			if ( ! $edit ) {
				?>
				<p class="match_info"><?php esc_html_e( 'Note: Matches with different Home and Guest Teams will be added to the database.', 'racketmanager' ); ?></p>
				<?php
			}
			?>

			<table class="table table-striped table-borderless" aria-label="<?php esc_html_e( 'match edit', 'racketmanager' ); ?>">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Id', 'racketmanager' ); ?></th>
						<?php
						if ( $bulk || $is_finals || ( 'add' === $mode ) || ( 'edit' === $mode ) ) {
							?>
							<th scope="col"><?php esc_html_e( 'Date', 'racketmanager' ); ?></th>
							<?php
						}
						?>
						<?php
						if ( ! empty( $match->final_round ) || $is_finals ) {
							?>
							<?php
						} else {
							?>
							<th scope="col"><?php esc_html_e( 'Day', 'racketmanager' ); ?></th>
							<?php
						}
						?>
						<th scope="col">
							<?php
							if ( $cup ) {
								esc_html_e( 'Team', 'racketmanager' );
							} else {
								esc_html_e( 'Home', 'racketmanager' );
							}
							?>
						</th>
						<th scope="col">
							<?php
							if ( $cup ) {
								esc_html_e( 'Team', 'racketmanager' );
							} else {
								esc_html_e( 'Away', 'racketmanager' );
							}
							?>
						</th>
						<th scope="col"><?php esc_html_e( 'Location', 'racketmanager' ); ?></th>
						<?php
						if ( ! isset( $league->event->competition->entry_type ) || 'player' !== $league->event->competition->entry_type ) {
							?>
							<th scope="col"><?php esc_html_e( 'Begin', 'racketmanager' ); ?></th>
							<?php
						}
						?>
						<?php do_action( 'racketmanager_edit_matches_header_' . $league->sport ); ?>
						<?php
						if ( $single_cup_game ) {
							?>
							<th scope="col"></th>
							<?php
						}
						?>
					</tr>
				</thead>
				<tbody id="the-list" class="lm-form-table">
					<?php
					for ( $i = 0; $i < $max_matches; $i++ ) {
						?>
						<tr class="">
							<td>
								<?php
								if ( isset( $matches[ $i ]->id ) ) {
									echo esc_html( $matches[ $i ]->id );
								}
								?>
							</td>
							<?php
							if ( $bulk || $is_finals || ( 'add' === $mode ) || 'edit' === $mode ) {
								if ( isset( $matches[ $i ]->date ) ) {
									$date = ( substr( $matches[ $i ]->date, 0, 10 ) );
								} else {
									$date = '';
									if ( ! empty( $final['round'] ) ) {
										if ( $league->championship->is_consolation ) {
											$round_no = $final['round'];
										} else {
											$round_no = $final['round'] - 1;
										}
										if ( ! empty( $league->seasons[ $season ]['matchDates'][ $round_no ] ) ) {
											$date = $league->seasons[ $season ]['matchDates'][ $round_no ];
										}
									}
								}
								?>
								<td><input type="date" name="mydatepicker[<?php echo esc_html( $i ); ?>]" id="mydatepicker[<?php echo esc_html( $i ); ?>]" class="" value="<?php echo esc_html( $date ); ?>" onChange="Racketmanager.setMatchDate(this.value, <?php echo esc_html( $i ); ?>, <?php echo esc_html( $max_matches ); ?>, '<?php echo esc_html( $mode ); ?>');" /></td>
								<?php
							}
							?>
							<?php
							if ( ! empty( $matches[ $i ]->final_round ) || $is_finals ) {
								?>
								<?php
							} else {
								if ( empty( $match_day ) ) {
									if ( ! empty( $matches[ $i ]->match_day ) ) {
										$match_day = $match[ $i ]->match_day;
									} else {
										$match_day = null;
									}
								}
								?>
								<td>
									<select size="1" name="match_day[<?php echo esc_html( $i ); ?>]" id="match_day_<?php echo esc_html( $i ); ?>" onChange="Racketmanager.setMatchDayPopUp(this.value, <?php echo esc_html( $i ); ?>, <?php echo esc_html( $max_matches ); ?>, '<?php echo esc_html( $mode ); ?>');">
										<?php
										for ( $d = 1; $d <= $league->current_season['num_match_days']; $d++ ) {
											?>
											<option value="<?php echo esc_html( $d ); ?>"
												<?php
												if ( intval( $match_day ) === $d ) {
													echo ' selected';
												}
												?>
											><?php echo esc_html( $d ); ?></option>
											<?php
										}
										?>
									</select>
								</td>
								<?php
							}
							?>
							<!-- Home team pop up -->
							<td>
								<?php
								if ( $single_cup_game ) {
									?>
									<input type="text" disabled name="home_team_title[<?php echo esc_html( $i ); ?>]" id="home_team_title_<?php echo esc_html( $i ); ?>" value="<?php echo esc_html( $home_title ); ?>" />
									<input type="hidden" name="home_team[<?php echo esc_html( $i ); ?>]" id="home_team_<?php echo esc_html( $i ); ?>" value="<?php echo esc_html( $matches[ $i ]->home_team ); ?>" />
									<?php
								} else {
									?>
									<select size="1" name="home_team[<?php echo esc_html( $i ); ?>]" id="home_team_<?php echo esc_html( $i ); ?>"
										<?php
										if ( ! $finalkey ) {
											echo ' onChange="Racketmanager.insertHomeStadium(document.getElementById(\'home_team_' . esc_html( $i ) . '\').value, ' . esc_html( $i ) . ');"';
										}
										?>
									>
										<?php
										$my_team = 0;
										foreach ( $teams as $team ) {
											?>
											<option value="<?php echo esc_html( $team->id ); ?>"
												<?php
												if ( isset( $matches[ $i ]->home_team ) ) {
													selected( $team->id, $matches[ $i ]->home_team );
												}
												?>
											><?php echo esc_html( $team->title ); ?></option>
											<?php
											if ( 0 === $my_team ) {
												$my_home_team = $team->id;
											}
											?>
											<?php ++$my_team; ?>
											<?php
										}
										?>
									</select>
									<?php
								}
								?>
								<?php
								if ( $cup ) {
									?>
									<input type="radio" name="host[<?php echo esc_html( $i ); ?>]" id="team_host_home[<?php echo esc_html( $i ); ?>]" value="home"
										<?php
										if ( isset( $matches[ $i ]->host ) && 'home' === $matches[ $i ]->host ) {
											echo ' checked';
										}
										?>
									/>
									<?php
								}
								?>
							</td>
							<!-- Away team pop up -->
							<td>
								<?php
								if ( $single_cup_game ) {
									?>
									<input type="text" disabled name="away_team_title[<?php echo esc_html( $i ); ?>]" id="away_team_title_<?php echo esc_html( $i ); ?>" value="<?php echo esc_html( $away_title ); ?>" />
									<input type="hidden" name="away_team[<?php echo esc_html( $i ); ?>]" id="away_team_<?php echo esc_html( $i ); ?>" value="<?php echo esc_html( $matches[ $i ]->away_team ); ?>" />
									<?php
								} else {
									?>
									<?php
									if ( 1 === $non_group ) {
										?>
										<select size="1" name="away_team[<?php echo esc_html( $i ); ?>]" id="away_team_<?php echo esc_html( $i ); ?>"
											<?php
											if ( ! $finalkey ) {
												echo ' onChange="Racketmanager.insertHomeStadium(document.getElementById(\'home_team_' . esc_html( $i ) . '\').value, ' . esc_html( $i ) . ');"';
											}
											?>
										>
											<?php
											foreach ( $teams as $team ) {
												?>
												<?php
												if ( isset( $matches[ $i ]->away_team ) ) {
													?>
													<option value="<?php echo esc_html( $team->id ); ?>"
														<?php
														if ( isset( $matches[ $i ]->away_team ) ) {
															selected( $team->id, $matches[ $i ]->away_team );
														}
														?>
													><?php echo esc_html( $team->title ); ?></option>
													<?php
												} elseif ( $team->id === $my_home_team ) {
													?>
													<!-- BUILD THE 'SELECTED' ITEM IN THE POP-UP -->
													<option value="<?php echo esc_html( $team->id ); ?>" selected='selected'><?php echo esc_html( $team->title ); ?></option>
													<?php
												} else {
													?>
													<option value="<?php echo esc_html( $team->id ); ?>"><?php echo esc_html( $team->title ); ?></option>
													<?php
												}
											}
											?>
										</select>
										<?php
									} else {
										?>
										<select size="1" name="away_team[<?php echo esc_html( $i ); ?>]" id="away_team_<?php echo esc_html( $i ); ?>"
											<?php
											if ( ! $finalkey ) {
												echo ' onChange="Racketmanager.insertHomeStadium(document.getElementById(\'home_team_' . esc_html( $i ) . '\').value, ' . esc_html( $i ) . ');"';
											}
											?>
										>
											<?php
											foreach ( $teams as $team ) {
												?>
												<option value="<?php echo esc_html( $team->id ); ?>"
													<?php
													if ( isset( $matches[ $i ]->away_team ) ) {
														selected( $team->id, $matches[ $i ]->away_team );
													}
													?>
												><?php echo esc_html( $team->title ); ?></option>
												<?php
											}
											?>
										</select>
										<?php
									}
									?>
									<?php
								}
								?>
								<?php
								if ( $cup ) {
									?>
									<input type="radio" name="host[<?php echo esc_html( $i ); ?>]" id="team_host_away[<?php echo esc_html( $i ); ?>]" value="away"
										<?php
										if ( isset( $matches[ $i ]->host ) && 'away' === $matches[ $i ]->host ) {
											echo ' checked';
										}
										?>
									/>
									<?php
								}
								?>
							</td>
							<td>
								<?php
								if ( isset( $matches[ $i ]->location ) ) {
									$location = ( $matches[ $i ]->location );
								} else {
									$location = '';
								}
								?>
								<input type="text" name="location[<?php echo esc_html( $i ); ?>]" id="location_<?php echo esc_html( $i ); ?>" size="20" value="<?php echo esc_html( $location ); ?>" size="30" />
							</td>
							<?php
							if ( ! isset( $league->event->competition->entry_type ) || 'player' !== $league->event->competition->entry_type ) {
								?>
								<td>
									<select size="1" name="begin_hour[<?php echo esc_html( $i ); ?>]">
										<?php
										for ( $hour = 0; $hour <= 23; $hour++ ) {
											?>
											<option value="<?php echo esc_html( ( isset( $hour ) ) ? str_pad( $hour, 2, 0, STR_PAD_LEFT ) : 00 ); ?>"<?php ( isset( $matches[ $i ]->hour ) ) ? selected( $hour, $matches[ $i ]->hour ) : ''; ?>><?php echo esc_html( ( isset( $hour ) ) ? str_pad( $hour, 2, 0, STR_PAD_LEFT ) : 00 ); ?></option>
											<?php
										}
										?>
									</select>
									<select size="1" name="begin_minutes[<?php echo esc_html( $i ); ?>]">
										<?php
										for ( $minute = 0; $minute <= 60; $minute++ ) {
											?>
											<?php
											if ( 0 === $minute % 5 && 60 !== $minute ) {
												?>
												<option value="<?php echo esc_html( ( isset( $minute ) ) ? str_pad( $minute, 2, 0, STR_PAD_LEFT ) : 00 ); ?>"<?php ( isset( $matches[ $i ]->minutes ) ) ? selected( $minute, $matches[ $i ]->minutes ) : ''; ?>><?php echo esc_html( ( isset( $minute ) ) ? str_pad( $minute, 2, 0, STR_PAD_LEFT ) : 00 ); ?></option>
												<?php
											}
											?>
											<?php
										}
										?>
									</select>
								</td>
								<?php
							}
							?>
							<?php do_action( 'racketmanager_edit_matches_columns_' . $league->sport, ( isset( $matches[ $i ] ) ? $matches[ $i ] : '' ), $league, $season, ( isset( $teams ) ? $teams : '' ), $i ); ?>
							<?php
							if ( $single_cup_game ) {
								?>
								<td>
									<input type="button" value="<?php esc_html_e( 'Notify teams', 'racketmanager' ); ?>" class="btn btn-secondary" onclick="Racketmanager.notifyTeams(<?php echo esc_html( $matches[ $i ]->id ); ?>)" /><span class="notifymessage" id="notifyMessage-<?php echo esc_html( $matches[ $i ]->id ); ?>"></span>
								</td>
								<?php
							}
							?>
						</tr>
						<input type="hidden" name="match[<?php echo esc_html( $i ); ?>]" value="<?php echo esc_html( isset( $matches[ $i ]->id ) ? $matches[ $i ]->id : '' ); ?>" />
						<?php
					}
					?>
				</tbody>
			</table>

			<input type="hidden" name="mode" value="<?php echo esc_html( $mode ); ?>" />
			<input type="hidden" name="league_id" value="<?php echo esc_html( $league->id ); ?>" />
			<input type="hidden" name="num_rubbers" value="<?php echo esc_html( $league->num_rubbers ); ?>" />
			<input type="hidden" name="season" value="<?php echo esc_html( $season ); ?>" />
			<input type="hidden" name="final" value="<?php echo esc_html( $finalkey ); ?>" />
			<input type="hidden" name="updateLeague" value="match" />

			<p class="submit"><input type="submit" value="<?php echo esc_html( $submit_title ); ?>" class="btn btn-primary" /></p>
			<div id="feedback" class="feedback">
			</div>
		</form>
		<?php
	} else {
		?>
		<?php esc_html_e( 'No matches found', 'racketmanager' ); ?>
		<?php
	}
	?>
</div>
