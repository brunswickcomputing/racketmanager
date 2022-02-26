<div class="wrap">
	<p class="racketmanager_breadcrumb">
		<a href="admin.php?page=racketmanager"><?php _e( 'RacketManager', 'racketmanager' ) ?></a> &raquo;
		<a href="admin.php?page=racketmanager&amp;subpage=show-competition&amp;competition_id=<?php echo $competition->id ?>"><?php echo $competition->name ?></a> &raquo;
		<a href="admin.php?page=racketmanager&amp;subpage=show-league&amp;league_id=<?php echo $league->id ?>"><?php echo $league->title ?></a> &raquo;
		<?php echo $form_title ?>
	</p>
	<h1><?php printf( "%s - %s",  $league->title, $form_title ); ?></h1>
	<?php if ( has_action( 'racketmanager_edit_match_'.$league->sport ) ) {
		do_action( 'racketmanager_edit_match_'.$league->sport, $league, $teams, $season, $max_matches, $matches, $submit_title, $mode );
	} else { ?>
		<form action="admin.php?page=racketmanager&amp;subpage=show-league&amp;league_id=<?php echo $league->id?>&amp;season=<?php echo $season ?><?php if (isset($finalkey)) echo '&amp;final=' . $finalkey . '&amp;league-tab=1'; ?>" method="post">
			<?php wp_nonce_field( 'racketmanager_manage-matches' ) ?>
			<?php if ( !$edit ) { ?><p class="match_info"><?php _e( 'Note: Matches with different Home and Guest Teams will be added to the database.', 'racketmanager' ) ?></p><?php } ?>

				<table class="widefat">
					<thead>
						<tr>
							<th scope="col"><?php _e( 'Id', 'racketmanager') ?></th>
							<?php if ( $bulk || $is_finals || ($mode=="add") || ($mode=="edit") ) { ?>
								<th scope="col"><?php _e( 'Date', 'racketmanager' ) ?></th>
							<?php } ?>
							<?php if ( (isset($match->final_round) && $match->final_round != null) || $is_finals ) { ?>
							<?php } else { ?>
								<th scope="col"><?php _e( 'Day', 'racketmanager' ) ?></th>
							<?php } ?>
							<th scope="col"><?php if ( $cup ) { _e( 'Team', 'racketmanager' ); } else { _e( 'Home', 'racketmanager' ); } ?></th>
							<th scope="col"><?php if ( $cup ) { _e( 'Team', 'racketmanager' ); } else { _e( 'Away', 'racketmanager' ); } ?></th>
							<th scope="col"><?php _e( 'Location','racketmanager' ) ?></th>
							<?php if ( isset($league->entryType) && $league->entryType == 'player' ) {
							} else { ?>
								<th scope="col"><?php _e( 'Begin','racketmanager' ) ?></th>
							<?php } ?>
							<?php do_action('edit_matches_header_'.$league->sport) ?>
							<?php if ( $singleCupGame ) { ?>
								<th scope="col"></th>
							<?php } ?>
						</tr>
					</thead>
					<tbody id="the-list" class="lm-form-table">
						<?php for ( $i = 0; $i < $max_matches; $i++ ) { $class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
							<tr class="<?php echo $class; ?>">
								<td><?php if (isset($matches[$i]->id)) echo $matches[$i]->id ?></td>
								<?php if ( $bulk || $is_finals || ($mode=="add") || $mode == "edit" ) { ?>
									<td><input type="date" name="mydatepicker[<?php echo $i ?>]" id="mydatepicker[<?php echo $i ?>]" class="" value="<?php if(isset($matches[$i]->date)) echo ( substr($matches[$i]->date, 0, 10) ) ?>" onChange="Racketmanager.setMatchDate(this.value, <?php echo $i ?>, <?php echo $max_matches ?>, '<?php echo $mode ?>');" /></td>
								<?php } ?>
								<?php if ( (isset($match->final_round) && $match->final_round != null) || $is_finals ) { ?>
								<?php } else { ?>
									<td>
										<select size="1" name="match_day[<?php echo $i ?>]" id="match_day_<?php echo $i ?>" onChange="Racketmanager.setMatchDayPopUp(this.value, <?php echo $i ?>, <?php echo $max_matches ?>, '<?php echo $mode ?>');">
											<?php for ($d = 1; $d <= $league->current_season['num_match_days']; $d++) { ?>
												<option value="<?php echo $d ?>"<?php if(isset($match_day) && $d == $match_day) echo ' selected="selected"' ?>><?php echo $d ?></option>
											<?php } ?>
										</select>
									</td>
								<?php } ?>
								<!-- Home team pop up -->
								<td>
									<?php if ( $singleCupGame ) { ?>
										<input type="text" disabled name="home_team_title[<?php echo $i ?>]" id="home_team_title_<?php echo $i ?>" value="<?php echo $home_title ?>" />
										<input type="hidden" name="home_team[<?php echo $i ?>]" id="home_team_<?php echo $i ?>" value="<?php echo $matches[$i]->home_team ?>" />
									<?php } else { ?>
										<select size="1" name="home_team[<?php echo $i ?>]" id="home_team_<?php echo $i ?>" <?php if ( !$finalkey ) { echo 'onChange="Racketmanager.insertHomeStadium(document.getElementById(\'home_team_'.$i.'\').value, '.$i.');"'; } ?>>
											<?php $myTeam = 0; ?>
											<?php foreach ( $teams AS $team ) { ?>
												<option value="<?php echo $team->id ?>"<?php if(isset($matches[$i]->home_team)) selected($team->id, $matches[$i]->home_team ) ?>><?php echo $team->title ?></option>
												<?php if ( $myTeam==0 ) { $myHomeTeam = $team->id; } ?>
												<?php $myTeam++; ?>
											<?php } ?>
										</select>
									<?php } ?>
									<?php if ( $cup ) { ?>
										<input type="radio" name="custom[<?php echo $i ?>][host]" id="team_host[<?php echo $i ?>]" value="home" <?php if ( isset($matches[$i]->custom['host']) ) { echo ($matches[$i]->custom['host'] == 'home') ? 'checked' : ''; } ?> />
										<?php } ?>
									</td>
									<!-- Away team pop up -->
									<td>
										<?php if ( $singleCupGame ) { ?>
											<input type="text" disabled name="away_team_title[<?php echo $i ?>]" id="away_team_title_<?php echo $i ?>" value="<?php echo $away_title ?>" />
											<input type="hidden" name="away_team[<?php echo $i ?>]" id="away_team_<?php echo $i ?>" value="<?php echo $matches[$i]->away_team ?>" />
										<?php } else { ?>

											<?php if ( 1 == $non_group ) {  ?>

												<select size="1" name="away_team[<?php echo $i ?>]" id="away_team_<?php echo $i ?>" <?php if ( !$finalkey ) { echo 'onChange="Racketmanager.insertHomeStadium(document.getElementById(\'home_team_'.$i.'\').value, '.$i.');"'; } ?>>

													<?php foreach ( $teams AS $team ) { ?>
														<?php if ( isset($matches[$i]->away_team) ) { ?>
															<option value="<?php echo $team->id ?>"<?php if(isset($matches[$i]->away_team)) selected( $team->id, $matches[$i]->away_team ) ?>><?php echo $team->title ?></option>
														<?php } elseif ( $team->id == $myHomeTeam ) { ?>
															<!-- BUILD THE 'SELECTED' ITEM IN THE POP-UP -->
															<option value="<?php echo $team->id ?>" selected='selected'><?php echo $team->title ?></option>

														<?php } else { ?>
															<option value="<?php echo $team->id ?>"><?php echo $team->title ?></option>
														<?php }
													} ?>
												</select>
											<?php } else { ?>
												<select size="1" name="away_team[<?php echo $i ?>]" id="away_team_<?php echo $i ?>" <?php if ( !$finalkey ) { echo 'onChange="Racketmanager.insertHomeStadium(document.getElementById(\'home_team_'.$i.'\').value, '.$i.');"'; } ?>>
													<?php foreach ( $teams AS $team ) { ?>
														<option value="<?php echo $team->id ?>"<?php if(isset($matches[$i]->away_team)) selected( $team->id, $matches[$i]->away_team ) ?>><?php echo  $team->title ?></option>
													<?php } ?>
												</select>
											<?php } ?>

										<?php } ?>
										<?php if ( $cup ) { ?>
											<input type="radio" name="custom[<?php echo $i ?>][host]" id="team_host[<?php echo $i ?>]" value="away" <?php if ( isset($matches[$i]->custom['host']) ) { echo ($matches[$i]->custom['host'] == 'away') ? 'checked' : ''; } ?> />
											<?php } ?>
										</td>
										<td><input type="text" name="location[<?php echo $i ?>]" id="location[<?php echo $i ?>]" size="20" value="<?php if(isset($matches[$i]->location)) echo $matches[$i]->location ?>" size="30" /></td>
										<?php if ( isset($league->entryType) && $league->entryType == 'player' ) {

										} else { ?>
											<td>
												<select size="1" name="begin_hour[<?php echo $i ?>]">
													<?php for ( $hour = 0; $hour <= 23; $hour++ ) { ?>
														<option value="<?php echo (isset($hour)) ? str_pad($hour, 2, 0, STR_PAD_LEFT) : 00 ?>"<?php (isset($matches[$i]->hour)) ? selected( $hour, $matches[$i]->hour ) : '' ?>><?php echo (isset($hour)) ? str_pad($hour, 2, 0, STR_PAD_LEFT) : 00 ?></option>
													<?php } ?>
												</select>
												<select size="1" name="begin_minutes[<?php echo $i ?>]">
													<?php for ( $minute = 0; $minute <= 60; $minute++ ) { ?>
														<?php if ( 0 == $minute % 5 && 60 != $minute ) { ?>
															<option value="<?php echo (isset($minute)) ? str_pad($minute, 2, 0, STR_PAD_LEFT) : 00 ?>"<?php (isset($matches[$i]->minutes)) ? selected( $minute, $matches[$i]->minutes ) : '' ?>><?php echo (isset($minute)) ? str_pad($minute, 2, 0, STR_PAD_LEFT) : 00 ?></option>
														<?php } ?>
													<?php } ?>
												</select>
											</td>
										<?php } ?>
										<?php do_action('edit_matches_columns_'.$league->sport, (isset($matches[$i]) ? $matches[$i] : ''), $league, $season, (isset($teams) ? $teams : ''), $i) ?>
										<?php if ( $singleCupGame ) { ?>
											<td>
												<input type="button" value="<?php _e('Notify teams', 'racketmanager') ?>" class="btn btn-secondary" onclick="Racketmanager.notifyTeams(<?php echo $matches[$i]->id ?>)" /><span class="notifymessage" id="notifyMessage-<?php echo $matches[$i]->id ?>"></span>
											</td>
										<?php } ?>
									</tr>
									<input type="hidden" name="match[<?php echo $i ?>]" value="<?php if (isset($matches[$i]->id)) echo $matches[$i]->id; else echo ""; ?>" />
								<?php } ?>
							</tbody>
						</table>

						<input type="hidden" name="mode" value="<?php echo $mode ?>" />
						<input type="hidden" name="league_id" value="<?php echo $league->id ?>" />
						<input type="hidden" name="num_rubbers" value="<?php echo $league->num_rubbers ?>" />
						<input type="hidden" name="season" value="<?php echo $season ?>" />
						<input type="hidden" name="final" value="<?php echo $finalkey ?>" />
						<input type="hidden" name="updateLeague" value="match" />

						<p class="submit"><input type="submit" value="<?php echo $submit_title ?>" class="button button-primary" /></p>
					</form>
				<?php } ?>

			</div>
