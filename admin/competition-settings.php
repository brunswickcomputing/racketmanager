<?php
	if ( !current_user_can( 'manage_leaguemanager' ) ) {
	echo '<p style="text-align: center;">'.__("You do not have sufficient permissions to access this page.").'</p>';

	} else {
	$tab = 0;
	$options = get_option('leaguemanager');
	//$league = $leaguemanager->getCurrentLeague();
	$competition = $leaguemanager->getCompetition( intval($_GET['competition_id']) );
	if ( isset($_POST['updateSettings']) ) {
		check_admin_referer('leaguemanager_manage-competition-options');

		$settings = (array)$_POST['settings'];

		// Set textdomain
		$options['textdomain'] = (string)$settings['sport'];
		update_option('leaguemanager', $options);

		if ( $settings['point_rule'] == 'user' && isset($_POST['forwin']) && is_numeric($_POST['forwin']) )
			$settings['point_rule'] = array( 'forwin' => intval($_POST['forwin']), 'fordraw' => intval($_POST['fordraw']), 'forloss' => intval($_POST['forloss']), 'forwin_overtime' => intval($_POST['forwin_overtime']), 'forloss_overtime' => intval($_POST['forloss_overtime']) );

		$settings['standings']['pld'] = isset($settings['standings']['pld']) ? 1 : 0;
		$settings['standings']['won'] = isset($settings['standings']['won']) ? 1 : 0;
		$settings['standings']['tie'] = isset($settings['standings']['tie']) ? 1 : 0;
		$settings['standings']['lost'] = isset($settings['standings']['lost']) ? 1 : 0;
		
		$this->editCompetition( intval($_POST['competition_id']), $_POST['competition_title'], $settings );
		$this->printMessage();
		
		$options = get_option('leaguemanager');
		$competition = $leaguemanager->getCompetition( intval($_GET['competition_id']) );
		
		// Set active tab
		$tab = intval($_POST['active-tab']);
	}
	
	$forwin = $fordraw = $forloss = $forwin_overtime = $forloss_overtime = 0;
	// Manual point rule
	if ( is_array($competition->point_rule) ) {
		$forwin = $competition->point_rule['forwin'];
		$forwin_overtime = $competition->point_rule['forwin_overtime'];
		$fordraw = $competition->point_rule['fordraw'];
		$forloss = $competition->point_rule['forloss'];
		$forloss_overtime = $competition->point_rule['forloss_overtime'];
		$competition->point_rule = 'user';
	}
?>

<script type='text/javascript'>
	jQuery(function() {
		jQuery("#tabs.form").tabs({
			active: <?php echo $tab ?>
		});
	});
</script>
<div class="wrap">

	<form action="" method="post">
		<?php wp_nonce_field( 'leaguemanager_manage-competition-options' ) ?>

		<div class="theme-settings-blocks form" id="tabs">
			<input type="hidden" class="active-tab" name="active-tab" value="<?php echo $tab ?>" ?>
			
			<ul id="tablist" style="display: none";>
				<li><a href="#general"><?php _e( 'General', 'leaguemanager' ) ?></a></li>
				<li><a href="#standings"><?php _e( 'Standings Table', 'leaguemanager' ) ?></a></li>
				<li><a href="#advanced"><?php _e( 'Advanced', 'leaguemanager' ) ?></a></li>
			</ul>
			
			<div id='general' class='settings-block-container'>
				<h2><?php _e( 'General', 'leaguemanager' ) ?></h2>
				<div class="settings-block">
					<table class="lm-form-table">
						<tr valign="top">
							<th scope="row"><label for="competition_title"><?php _e( 'Title', 'leaguemanager' ) ?></label></th>
							<td><input type="text" name="competition_title" id="competition_title" value="<?php echo $competition->name ?>" size="30" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="sport"><?php _e( 'Sport', 'leaguemanager' ) ?></label></th>
							<td>
								<select size="1" name="settings[sport]" id="sport">
									<?php foreach ( $leaguemanager->getLeagueTypes() AS $id => $title ) : ?>
										<option value="<?php echo $id ?>"<?php selected( $id, $competition->sport ) ?>><?php echo $title ?></option>
									<?php endforeach; ?>
								</select>
								<span class="setting-description"><?php printf( __( "Check the <a href='%s'>Documentation</a> for details", 'leaguemanager'), admin_url() . 'admin.php?page=leaguemanager-doc' ) ?></span>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="point_rule"><?php _e( 'Point Rule', 'leaguemanager' ) ?></label></th>
							<td>
								<select size="1" name="settings[point_rule]" id="point_rule" onchange="Leaguemanager.checkPointRule(<?php echo $forwin ?>, <?php echo $forwin_overtime ?>, <?php echo $fordraw ?>, <?php echo $forloss ?>, <?php echo $forloss_overtime ?>)">
								<?php foreach ( $this->getPointRules() AS $id => $point_rule ) : ?>
								<option value="<?php echo $id ?>"<?php selected( $id, $competition->point_rule ) ?>><?php echo $point_rule ?></option>
								<?php endforeach; ?>
								</select>
								<span class="setting-description"><?php printf( __("For details on point rules see the <a href='%s'>Documentation</a>", 'leaguemanager'), admin_url() . 'admin.php?page=leaguemanager-doc' ) ?></span>
								<div id="point_rule_manual" style="display: block;">
								<?php if ( $competition->point_rule == 'user' ) : ?>
									<div id="point_rule_manual_content">
										<input type='text' name='forwin' id='forwin' value='<?php echo $forwin ?>' size='2' />
										<input type='text' name='forwin_overtime' id='forwin_overtime' value='<?php echo $forwin_overtime ?>' size='2' />
										<input type='text' name='fordraw' id='fordraw' value='<?php echo $fordraw ?>' size='2' />
										<input type='text' name='forloss' id='forloss' value='<?php echo $forloss ?>' size='2' />
										<input type='text' name='forloss_overtime' id='forloss_overtime' value='<?php echo $forloss_overtime ?>' size='2' />
										&#160;<span class='setting-description'><?php _e( 'Order: win, win overtime, tie, loss, loss overtime', 'leaguemanager' ) ?></span>
									</div>
								<?php endif; ?>
								</div>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="point_format"><?php _e( 'Point Format', 'leaguemanager' ) ?></label></th>
							<td>
								<select size="1" name="settings[point_format]" id="point_format" >
								<?php foreach ( $this->getPointFormats() AS $id => $format ) : ?>
								<option value="<?php echo $id ?>"<?php selected ( $id, $competition->point_format ) ?>><?php echo $format ?></option>
								<?php endforeach; ?>
								</select>
								<select size="1" name="settings[point_format2]" id="point_format2" >
								<?php foreach ( $this->getPointFormats() AS $id => $format ) : ?>
								<option value="<?php echo $id ?>"<?php selected ( $id, $competition->point_format2 ); ?>><?php echo $format ?></option>
								<?php endforeach; ?>
								</select>
								&#160;<span class="setting-description"><?php _e( 'Point formats for primary and seconday points (e.g. Goals)', 'leaguemanager' ) ?></span>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="team_ranking"><?php _e( 'Team Ranking', 'leaguemanager' ) ?></label></th>
							<td>
								<select size="1" name="settings[team_ranking]" id="team_ranking" >
									<option value="auto"<?php selected( 'auto', $competition->team_ranking  ) ?>><?php _e( 'Automatic', 'leaguemanager' ) ?></option>
									<option value="manual"<?php selected( 'manual', $competition->team_ranking  ) ?>><?php _e( 'Manual', 'leaguemanager' ) ?></option>
								</select>
								<!--&#160;<span class="setting-description"><?php _e( 'Team Ranking via Drag & Drop probably will only work in Firefox', 'leaguemanager' ) ?></span>-->
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="mode"><?php _e( 'Mode', 'leaguemanager' ) ?></label></th>
							<td>
								<select size="1" name="settings[mode]" id="mode">
								<?php foreach ( $this->getModes() AS $id => $mode ) : ?>
									<option value="<?php echo $id ?>"<?php selected( $id, $competition->mode ) ?>><?php echo $mode ?></option>
								<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="default_start_time"><?php _e( 'Default Match Start Time', 'leaguemanager' ) ?></label></th>
							<td>
								<select size="1" name="settings[default_match_start_time][hour]">
								<?php for ( $hour = 0; $hour <= 23; $hour++ ) : ?>
									<option value="<?php echo str_pad($hour, 2, 0, STR_PAD_LEFT) ?>"<?php selected( $hour, $competition->default_match_start_time['hour'] ) ?>><?php echo str_pad($hour, 2, 0, STR_PAD_LEFT) ?></option>
								<?php endfor; ?>
								</select>
								<select size="1" name="settings[default_match_start_time][minutes]">
								<?php for ( $minute = 0; $minute <= 60; $minute++ ) : ?>
									<?php if ( 0 == $minute % 5 && 60 != $minute ) : ?>
									<option value="<?php  echo str_pad($minute, 2, 0, STR_PAD_LEFT) ?>"<?php selected( $minute, $competition->default_match_start_time['minutes'] ) ?>><?php echo str_pad($minute, 2, 0, STR_PAD_LEFT) ?></option>
								<?php endif; ?>
								<?php endfor; ?>
								</select>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="num_matches_per_page"><?php _e( 'Matches per page', 'leaguemanager' ) ?></label></th>
							<td><input type="number" step="1" min="0" class="small-text" name="settings[num_matches_per_page]" id="num_matches_per_page" value="<?php echo $competition->num_matches_per_page ?>" size="2" />&#160;<span class="setting-description"><?php _e( 'Number of matches to show per page', 'leaguemanager' ) ?></span></td>
						</tr>
					</table>
				</div>
			</div>
			
			<div id='standings' class='settings-block-container'>
				<h2><?php _e( 'Standings Table', 'leaguemanager' ) ?></h2>
				<div class="settings-block">
					<table class="lm-form-table">
						<tr valign="top">
							<th scope="row"><label for="standings_table"><?php _e( 'Standings Table Display', 'leaguemanager' ) ?></label></th>
							<td>
								<p><input type="checkbox" name="settings[standings][pld]" id="standings_pld" value="1" <?php checked(1, $competition->standings['pld']) ?> /><label for="standings_pld" style="margin-left: 0.5em;"><?php _e( 'Played Games', 'leaguemanager' ) ?></label></p>
								<p><input type="checkbox" name="settings[standings][won]" id="standings_won" value="1" <?php checked(1, $competition->standings['won']) ?> /><label for="standings_won" style="margin-left: 0.5em;"><?php _e( 'Won Games', 'leaguemanager' ) ?></label></p>
								<p><input type="checkbox" name="settings[standings][tie]" id="standings_tie" value="1" <?php checked(1, $competition->standings['tie']) ?> /><label for="standings_tie" style="margin-left: 0.5em;"><?php _e('Tie Games', 'leaguemanager' ) ?></label></p>
								<p><input type="checkbox" name="settings[standings][lost]" id="standings_lost" value="1" <?php checked(1, $competition->standings['lost']) ?> /><label for="standings_lost" style="margin-left: 0.5em;"><?php _e( 'Lost Games', 'leaguemanager' ) ?></label></p>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="teams_ascend"><?php _e( 'Teams Ascend', 'leaguemanager' ) ?></label></th>
							<td><input type="number" step="1" min="0" class="small-text" name="settings[num_ascend]" id="teams_ascend" value="<?php echo $competition->num_ascend ?>" size="2" />&#160;<span class="setting-description"><?php _e( 'Number of Teams that ascend into higher league', 'leaguemanager' ) ?></span></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="teams_descend"><?php _e( 'Teams Descend', 'leaguemanager' ) ?></label></th>
							<td><input type="number" step="1" min="0" class="small-text" name="settings[num_descend]" id="teams_descend" value="<?php echo $competition->num_descend ?>" size="2" />&#160;<span class="setting-description"><?php _e( 'Number of Teams that descend into lower league', 'leaguemanager' ) ?></span></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="teams_relegation"><?php _e( 'Teams Relegation', 'leaguemanager' ) ?></label></th>
							<td><input type="number" step="1" min="0" class="small-text" name="settings[num_relegation]" id="teams_relegation" value="<?php echo $competition->num_relegation ?>" size="2" />&#160;<span class="setting-description"><?php _e( 'Number of Teams that need to go into relegation', 'leaguemanager' ) ?></span></td>
						</tr>
					</table>
				</div>
			</div>
				
			<div id='advanced' class="settings-block-container">
				<h2><?php _e( 'Advanced', 'leaguemanager' ) ?></h2>
				<div class="settings-block">
					<table class="lm-form-table">
						<?php do_action( 'league_settings_'.$competition->sport, $competition ); ?>
						<?php do_action( 'league_settings_'.$competition->mode, $competition ); ?>
						<?php do_action( 'league_settings', $competition ); ?>
					</table>
				</div>
			</div>
		</div>
		<input type="hidden" name="competition_id" value="<?php echo $competition->id ?>" />
		<p class="submit"><input type="submit" name="updateSettings" value="<?php _e( 'Save Preferences', 'leaguemanager' ) ?>" class="button button-primary" /></p>
	</form>
</div>

<?php } ?>
