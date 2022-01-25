<?php

if ( !current_user_can( 'manage_racketmanager' ) ) {
	echo '<p style="text-align: center;">'.__("You do not have sufficient permissions to access this page.").'</p>';
} else {
	$menu_page_url = admin_url('options-general.php?page=racketmanager-settings');

	?>
	<script type='text/javascript'>
	jQuery(function() {
		jQuery("#tabs.form").tabs({
			active: <?php echo $tab ?>
		});
	});
</script>

<form action='' method='post' name='settings'>
	<?php wp_nonce_field( 'racketmanager_manage-global-league-options' ); ?>

	<div class='wrap'>
		<h1><?php _e( 'Racketmanager Global Settings', 'racketmanager' ) ?></h1>
		<div class="settings-blocks form" id="tabs">
			<input type="hidden" class="active-tab" name="active-tab" value="<?php echo $tab ?>" ?>

			<ul id="tablist" style="display: none;">
				<li><a href="#rosters"><?php _e( 'Rosters', 'racketmanager' ) ?></a></li>
				<li><a href="#players"><?php _e( 'Player Checks', 'racketmanager' ) ?></a></li>
				<li><a href="#match-results"><?php _e( 'Match Results', 'racketmanager' ) ?></a></li>
				<li><a href="#colors"><?php _e( 'Color Scheme', 'racketmanager' ) ?></a></li>
			</ul>

			<div id="rosters" class="settings-block-container">
				<h2><?php _e('Rosters', 'racketmanager') ?></h2>
				<div class="settings-block">
					<table class='lm-form-table'>
						<tr valign='top'>
							<th scope='row'><label for='rosterConfirmation'><?php _e( 'Roster Confirmation', 'racketmanager' ) ?></label></th>
							<td>
								<select id="rosterConfirmation" name="rosterConfirmation">
									<option value="auto" <?php if (isset($options['rosterConfirmation']) && $options['rosterConfirmation'] == "admin") echo 'selected="selected"'?>><?php _e('Automatic', 'racketmanager') ?></option>
									<option value="none" <?php if (isset($options['rosterConfirmation']) && $options['rosterConfirmation'] == "none") echo 'selected="selected"'?>><?php _e('None', 'racketmanager') ?></option>
								</select>
							</td>
						</tr>
						<tr valign='top'>
							<th scope='row'><label for='rosterConfirmationEmail'><?php _e( 'Notification Email Address', 'racketmanager' ) ?></label></th><td><input type="email"  name='rosterConfirmationEmail' id='rosterConfirmationEmail' value='<?php echo isset($options['rosterConfirmationEmail']) ? $options['rosterConfirmationEmail'] : '' ?>' /></td>
						</tr>
					</table>
				</div>
			</div>

			<div id="players" class="settings-block-container">
				<h2><?php _e('Player Checks', 'racketmanager') ?></h2>
				<div class="settings-block">
					<table class='lm-form-table'>
						<tr valign='top'>
							<th scope='row'><label for='rosterLeadTime'><?php _e( 'Roster Lead Time (days)', 'racketmanager' ) ?></label></th><td><input type="number"  name='rosterLeadTime' id='rosterLeadTime' value='<?php echo isset($options['rosterLeadTime']) ? $options['rosterLeadTime'] : '' ?>' /></td>
						</tr>
						<tr valign='top'>
							<th scope='row'><label for='playedRounds'><?php _e( 'End of season eligibility (Match Days)', 'racketmanager' ) ?></label></th><td><input type="number"  name='playedRounds' id='playedRounds' value='<?php echo isset($options['playedRounds']) ? $options['playedRounds'] : '' ?>' /></td>
						</tr>
						<tr valign='top'>
							<th scope='row'><label for='playerLocked'><?php _e( 'How many matches lock a player', 'racketmanager' ) ?></label></th><td><input type="number"  name='playerLocked' id='playerLocked' value='<?php echo isset($options['playerLocked']) ? $options['playerLocked'] : '' ?>' /></td>
						</tr>
					</table>
				</div>
			</div>

			<div id="match-results" class="settings-block-container">
				<h2><?php _e('Match Results', 'racketmanager') ?></h2>
				<div class="settings-block">
					<table class='lm-form-table'>
						<tr valign='top'>
							<th scope='row'><label for='matchCapability'><?php _e( 'Minimum level to update results', 'racketmanager' ) ?></label></th>
							<td>
								<select id="role" name="matchCapability">
									<option value="captain" <?php if (isset($options['matchCapability']) && $options['matchCapability'] == "captain") echo 'selected="selected"'?>><?php _e('Captain', 'racketmanager') ?></option>
									<option value="roster" <?php if (isset($options['matchCapability']) && $options['matchCapability'] == "roster") echo 'selected="selected"'?>><?php _e('Roster', 'racketmanager') ?></option>
									<option value="none" <?php if (isset($options['matchCapability']) && $options['matchCapability'] == "none") echo 'selected="selected"'?>><?php _e('None', 'racketmanager') ?></option>
								</select>
							</td>
						</tr>
						<tr valign='top'>
							<th scope='row'><label for='resultEntry'><?php _e( 'Result Entry', 'racketmanager' ) ?></label></th>
							<td>
								<select id="resultEntry" name="resultEntry">
									<option value="home" <?php if (isset($options['resultEntry']) && $options['resultEntry'] == "home") echo 'selected="selected"'?>><?php _e('Home', 'racketmanager') ?></option>
									<option value="either" <?php if (isset($options['resultEntry']) && $options['resultEntry'] == "either") echo 'selected="selected"'?>><?php _e('Either', 'racketmanager') ?></option>
								</select>
							</td>
						</tr>
						<tr valign='top'>
							<th scope='row'><label for='resultConfirmation'><?php _e( 'Result Confirmation', 'racketmanager' ) ?></label></th>
							<td>
								<select id="resultConfirmation" name="resultConfirmation">
									<option value="auto" <?php if (isset($options['resultConfirmation']) && $options['resultConfirmation'] == "admin") echo 'selected="selected"'?>><?php _e('Automatic', 'racketmanager') ?></option>
									<option value="none" <?php if (isset($options['resultConfirmation']) && $options['resultConfirmation'] == "none") echo 'selected="selected"'?>><?php _e('None', 'racketmanager') ?></option>
								</select>
							</td>
						</tr>
						<tr valign='top'>
							<th scope='row'><label for='resultConfirmationEmail'><?php _e( 'Notification Email Address', 'racketmanager' ) ?></label></th><td><input type="email"  name='resultConfirmationEmail' id='resultConfirmationEmail' value='<?php echo isset($options['resultConfirmationEmail']) ? $options['resultConfirmationEmail'] : '' ?>' /></td>
						</tr>
					</table>
				</div>
			</div>

			<div id="colors" class="settings-block-container">
				<h2><?php _e( 'Color Scheme', 'racketmanager' ) ?></h2>
				<div class="settings-block">
					<table class='lm-form-table'>
						<tr valign='top'>
							<th scope='row'><label for='color_headers'><?php _e( 'Table Headers', 'racketmanager' ) ?></label></th><td><input type='text' name='color_headers' id='color_headers' value='<?php echo ( isset($options['colors']['headers']) ? ($options['colors']['headers']) : '' ) ?>' size='7' class="racketmanager-colorpicker color" /><span class="colorbox" style="background-color: <?php echo $options['colors']['headers'] ?>"></span></td>
						</tr>
						<tr valign='top'>
							<th scope='row'><label for='color_rows'><?php _e( 'Table Rows', 'racketmanager' ) ?></label></th>
							<td>
								<p class='table_rows'><input type='text' name='color_rows_alt' id='color_rows_alt' value='<?php echo (isset($options['colors']['rows']['alternate']) ? ($options['colors']['rows']['alternate']) : '' ) ?>' size='7' class="racketmanager-colorpicker color" /><span class="colorbox" style="background-color: <?php echo $options['colors']['rows']['alternate'] ?>"></span></p>
								<p class='table_rows'><input type='text' name='color_rows' id='color_rows' value='<?php echo ( isset($options['colors']['rows']['main']) ? ($options['colors']['rows']['main']) : '' ) ?>' size='7' class="racketmanager-colorpicker color" /><span class="colorbox" style="background-color: <?php echo $options['colors']['rows']['main'] ?>"></span></p>
							</td>
						</tr>
						<tr valign='top'>
							<th scope='row'><label for='color_rows_ascend'><?php _e( 'Teams Ascend', 'racketmanager' ) ?></label></th><td><input type='text' name='color_rows_ascend' id='color_rows_ascend' value='<?php echo ( isset($options['colors']['rows']['ascend']) ? ($options['colors']['rows']['ascend']) : '' ) ?>' size='7' class="racketmanager-colorpicker color" /><span class="colorbox" style="background-color: <?php echo $options['colors']['rows']['ascend'] ?>"></span></td>
						</tr>
						<tr valign='top'>
							<th scope='row'><label for='color_rows_descend'><?php _e( 'Teams Descend', 'racketmanager' ) ?></label></th><td><input type='text' name='color_rows_descend' id='color_rows_descend' value='<?php echo ( isset($options['colors']['rows']['descend']) ? ($options['colors']['rows']['descend']) : '' ) ?>' size='7' class="racketmanager-colorpicker color" /><span class="colorbox" style="background-color: <?php echo $options['colors']['rows']['descend'] ?>"></span></td>
						</tr>
						<tr valign='top'>
							<th scope='row'><label for='color_rows_relegation'><?php _e( 'Teams Relegation', 'racketmanager' ) ?></label></th><td><input type='text' name='color_rows_relegation' id='color_rows_relegation' value='<?php echo ( isset($options['colors']['rows']['relegation']) ? ($options['colors']['rows']['relegation']) : '' ) ?>' size='7' class="racketmanager-colorpicker color" /><span class="colorbox" style="background-color: <?php echo $options['colors']['rows']['relegation'] ?>"></span></td>
						</tr>
						<tr valign='top'>
							<th scope='row'><label for='color_rows'><?php _e( 'Box Header', 'projectmanager' ) ?></label></th>
							<td>
								<p class='table_rows'><input type='text' name='color_boxheader1' id='color_boxheader1' value='<?php echo $options['colors']['boxheader'][0] ?>' size='7' class="racketmanager-colorpicker color" /><span class="colorbox" style="background-color: <?php echo $options['colors']['boxheader'][0] ?>"></span></p>				<p class='table_rows'><input type='text' name='color_boxheader2' id='color_boxheader2' value='<?php echo $options['colors']['boxheader'][1] ?>' size='7' class="racketmanager-colorpicker color" /><span class="colorbox" style="background-color: <?php echo $options['colors']['boxheader'][1] ?>"></span></p>
							</td>
						</tr>
					</table>
				</div>
			</div>

		</div>

		<input type='hidden' name='page_options' value='color_headers,color_rows,color_rows_alt,color_rows_ascend,color_rows_descend,color_rows_relegation' />
		<p class='submit'><input type='submit' name='updateRacketManager' value='<?php _e( 'Save Preferences', 'racketmanager' ) ?>' class='button button-primary' /></p>
	</div>
</form>

<?php } ?>
