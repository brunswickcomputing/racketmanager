<div class="wrap">
	<p class="leaguemanager_breadcrumb">
		<a href="admin.php?page=leaguemanager"><?php _e( 'LeagueManager', 'leaguemanager' ) ?></a> &raquo;
		<a href="admin.php?page=leaguemanager&amp;subpage=show-competition&amp;competition_id=<?php echo $competition->id ?>"><?php echo $competition->name ?></a> &raquo;
		<a href="admin.php?page=leaguemanager&amp;subpage=show-league&amp;league_id=<?php echo $league->id ?>"><?php echo $league->title ?></a> &raquo;
		<?php echo $form_title ?>
	</p>
	<h1><?php printf( "%s &mdash; %s",  $league->title, $form_title ); ?></h1>
<?php if ( has_action( 'leaguemanager_edit_match_'.$league->sport ) ) { ?>
		<?php do_action( 'leaguemanager_edit_match_'.$league->sport, $league, $teams, $season, $max_matches, $matches, $submit_title, $mode ) ?>
<?php } else { ?>
<form action="admin.php?page=leaguemanager&amp;subpage=show-league&amp;league_id=<?php echo $league->id?>&amp;season=<?php echo $season ?><?php if (isset($finalkey)) echo '&amp;final=' . $finalkey . '&amp;jquery-ui-tab=1'; ?><?php if(isset($group)) echo '&amp;group=' . $group; ?>" method="post">
			<?php wp_nonce_field( 'leaguemanager_manage-matches' ) ?>
<?php if ( !$is_finals ) { ?>
			<table class="lm-form-table">

	<?php if ( $cup && isset($group) ) { ?>
				<tr valign="top">
					<th scope="row"><input type="hidden" name="group" id="group" value="<?php echo $group ?>" /></th>
				</tr>
	<?php } ?>
			</table>
<?php } ?>

			<p class="match_info"><?php if ( !$edit ) { ?><?php _e( 'Note: Matches with different Home and Guest Teams will be added to the database.', 'leaguemanager' ) ?><?php } ?></p>

			<table class="widefat">
				<thead>
					<tr>
                        <th scope="col"><?php _e( 'Id', 'leaguemanager') ?></th>
<?php if ( $bulk || $is_finals || ($mode=="add") || ($mode=="edit") ) { ?>
						<th scope="col"><?php _e( 'Date', 'leaguemanager' ) ?></th>
<?php } ?>
<?php if ( (isset($match->final) && $match->final != null) || $is_finals ) { ?>
<?php } else { ?>
						<th scope="col"><?php _e( 'Day', 'leaguemanager' ) ?></th>
<?php } ?>
						<th scope="col"><?php _e( 'Home', 'leaguemanager' ) ?></th>
						<th scope="col"><?php _e( 'Guest', 'leaguemanager' ) ?></th>
						<th scope="col"><?php _e( 'Location','leaguemanager' ) ?></th>
<?php if ( isset($league->entryType) && $league->entryType == 'player' ) {
    
} else { ?>
						<th scope="col"><?php _e( 'Begin','leaguemanager' ) ?></th>
<?php } ?>
                        <?php do_action('edit_matches_header_'.$league->sport) ?>
					</tr>
				</thead>
				<tbody id="the-list" class="lm-form-table">
<?php for ( $i = 0; $i < $max_matches; $i++ ) { $class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
				<tr class="<?php echo $class; ?>">
                    <td><?php if (isset($matches[$i]->id)) echo $matches[$i]->id ?></td>
	<?php if ( $bulk || $is_finals || ($mode=="add") || $mode == "edit" ) { ?>
                    <td><input type="date" name="mydatepicker[<?php echo $i ?>]" id="mydatepicker[<?php echo $i ?>]" class="" value="<?php if(isset($matches[$i]->date)) echo ( substr($matches[$i]->date, 0, 10) ) ?>" onChange="Leaguemanager.setMatchDate(this.value, <?php echo $i ?>, <?php echo $max_matches ?>, '<?php echo $mode ?>');" /></td>
	<?php } ?>
<?php if ( (isset($match->final) && $match->final != null) || $is_finals ) { ?>
<?php } else { ?>
					<td>
						<select size="1" name="match_day[<?php echo $i ?>]" id="match_day_<?php echo $i ?>" onChange="Leaguemanager.setMatchDayPopUp(this.value, <?php echo $i ?>, <?php echo $max_matches ?>, '<?php echo $mode ?>');">
	<?php for ($d = 1; $d <= $league->current_season['num_match_days']; $d++) { ?>
							<option value="<?php echo $d ?>"<?php if(isset($match_day) && $d == $match_day) echo ' selected="selected"' ?>><?php echo $d ?></option>
	<?php } ?>
						</select>
					</td>
<?php } ?>
<!-- Home team pop up, only shows teams in a Group if set for 'Championship' -->
					<td>
<?php if ( $singleCupGame ) { ?>
                            <input type="text" name="home_team_title[<?php echo $i ?>]" id="home_team_title_<?php echo $i ?>" value="<?php echo $home_title ?>" />
                            <input type="hidden" name="home_team[<?php echo $i ?>]" id="home_team_<?php echo $i ?>" value="<?php echo $matches[$i]->home_team ?>" />
<?php } else { ?>
                            <select size="1" name="home_team[<?php echo $i ?>]" id="home_team_<?php echo $i ?>" <?php if ( !$finalkey ) { echo 'onChange="Leaguemanager.insertHomeStadium(document.getElementById(\'home_team_'.$i.'\').value, '.$i.');"'; } ?>>
						<?php $myTeam = 0; ?>
<?php foreach ( $teams AS $team ) { ?>
							<option value="<?php echo $team->id ?>"<?php if(isset($matches[$i]->home_team)) selected($team->id, $matches[$i]->home_team ) ?>><?php echo $team->title ?></option>
                        	<?php if ( $myTeam==0 ) { $myHomeTeam = $team->id; } ?>
    						<?php $myTeam++; ?>
<?php } ?>
						</select>
<?php } ?>
					</td>
<!-- Away team pop up, shows all teams in the league only if 'Allow non-group' check is set, otherwise only show teams in group, if set for 'Championship' -->
					<td>
<?php if ( $singleCupGame ) { ?>
                            <input type="text" disabled name="away_team_title[<?php echo $i ?>]" id="away_team_title_<?php echo $i ?>" value="<?php echo $away_title ?>" />
                            <input type="hidden" name="away_team[<?php echo $i ?>]" id="away_team_<?php echo $i ?>" value="<?php echo $matches[$i]->away_team ?>" />
<?php } else { ?>

                        <?php if ( 1 == $non_group ) {  ?>

                            <select size="1" name="away_team[<?php echo $i ?>]" id="away_team_<?php echo $i ?>" <?php if ( !$finalkey ) { echo 'onChange="Leaguemanager.insertHomeStadium(document.getElementById(\'home_team_'.$i.'\').value, '.$i.');"'; } ?>>

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
                            <select size="1" name="away_team[<?php echo $i ?>]" id="away_team_<?php echo $i ?>" <?php if ( !$finalkey ) { echo 'onChange="Leaguemanager.insertHomeStadium(document.getElementById(\'home_team_'.$i.'\').value, '.$i.');"'; } ?>>
<?php foreach ( $teams AS $team ) { ?>
                                <option value="<?php echo $team->id ?>"<?php if(isset($matches[$i]->away_team)) selected( $team->id, $matches[$i]->away_team ) ?>><?php echo  $team->title ?></option>
<?php } ?>
                            </select>
                        <?php } ?>

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

