<?php

	if ( !current_user_can( 'manage_leaguemanager' ) ) {
	echo '<p style="text-align: center;">'.__("You do not have sufficient permissions to access this page.").'</p>';
	} else {
		if ( isset($_GET['regenerate_thumbnails']) ) {
			$this->regenerateThumbnails();
			$tab = 0;
		}
		
		if ( isset($_GET['cleanUnusedFiles']) ) {
			$this->cleanUnusedMediaFiles();
			$tab = 0;
		}
		$menu_page_url = admin_url('options-general.php?page=leaguemanager-settings');

?>
<script type='text/javascript'>
	jQuery(function() {
		jQuery("#tabs.form").tabs({
			active: <?php echo $tab ?>
		});
	});
</script>

<form action='' method='post' name='settings'>
<?php wp_nonce_field( 'leaguemanager_manage-global-league-options' ); ?>

<div class='wrap'>
	<h1><?php _e( 'Leaguemanager Global Settings', 'leaguemanager' ) ?></h1>
	<div class="settings-blocks form" id="tabs">
		<input type="hidden" class="active-tab" name="active-tab" value="<?php echo $tab ?>" ?>
		
		<ul id="tablist" style="display: none;">
            <li><a href="#match-results"><?php _e( 'Match Results', 'leaguemanager' ) ?></a></li>
			<li><a href="#logos"><?php _e( 'Logos', 'leaguemanager' ) ?></a></li>
			<li><a href="#colors"><?php _e( 'Color Scheme', 'leaguemanager' ) ?></a></li>
			<li><a href="#dashboard-widget"><?php _e( 'Dashboard Widget Support News', 'leaguemanager' ) ?></a></li>
		</ul>
		
		<div id="match-results" class="settings-block-container">
			<h2><?php _e('Match Results', 'leaguemanager') ?></h2>
			<div class="settings-block">
				<table class='lm-form-table'>
				<tr valign='top'>
					<th scope='row'><label for='matchCapability'><?php _e( 'Minimum level to update results', 'leaguemanager' ) ?></label></th>
                    <td>
                        <select id="role" name="matchCapability">
                            <option value="captain" <?php if (isset($options['matchCapability']) && $options['matchCapability'] == "captain") echo 'selected="selected"'?>><?php _e('Captain', 'leaguemanager') ?></option>
                        <option value="roster" <?php if (isset($options['matchCapability']) && $options['matchCapability'] == "roster") echo 'selected="selected"'?>><?php _e('Roster', 'leaguemanager') ?></option>
                        <option value="none" <?php if (isset($options['matchCapability']) && $options['matchCapability'] == "none") echo 'selected="selected"'?>><?php _e('None', 'leaguemanager') ?></option>
                        </select>
                    </td>
				</tr>
				<tr valign='top'>
					<th scope='row'><label for='resultEntry'><?php _e( 'Result Entry', 'leaguemanager' ) ?></label></th>
                    <td>
                        <select id="role" name="resultEntry">
                            <option value="home" <?php if (isset($options['resultEntry']) && $options['resultEntry'] == "home") echo 'selected="selected"'?>><?php _e('Home', 'leaguemanager') ?></option>
                        <option value="either" <?php if (isset($options['resultEntry']) && $options['resultEntry'] == "either") echo 'selected="selected"'?>><?php _e('Either', 'leaguemanager') ?></option>
                        </select>
                    </td>
				</tr>
				<tr valign='top'>
					<th scope='row'><label for='resultConfirmation'><?php _e( 'Result Confirmation', 'leaguemanager' ) ?></label></th>
                    <td>
                        <select id="role" name="resultConfirmation">
                            <option value="auto" <?php if (isset($options['resultConfirmation']) && $options['resultConfirmation'] == "admin") echo 'selected="selected"'?>><?php _e('Automatic', 'leaguemanager') ?></option>
                        <option value="none" <?php if (isset($options['resultConfirmation']) && $options['resultConfirmation'] == "none") echo 'selected="selected"'?>><?php _e('None', 'leaguemanager') ?></option>
                        </select>
                    </td>
				</tr>
				</table>
			</div>
		</div>

		<div id="logos" class="settings-block-container">
			<h2><?php _e( 'Logos', 'leaguemanager' ) ?></h2>
			<div class="settings-block">
				<table class="lm-form-table">
					<tr valign="top">
						<th scope="row">
							<label for="thumb_size"><?php _e( 'Tiny size', 'leaguemanager' ) ?></label>
						</th>
						<td>
							<label for="tiny_width"><?php _e( 'Max Width' ) ?>&#160;</label>
							<input type="number" step="1" min="0" class="small-text" name="tiny_width" id="tiny_width" value="<?php echo $options['logos']['tiny_size']['width'] ?>" />
							<label for="tiny_height"><?php _e( 'Max Height' ) ?>&#160;</label>
							<input type="number" step="1" min="0" class="small-text" name="tiny_height" id="tiny_height" value="<?php echo $options['logos']['tiny_size']['height'] ?>" />
							<p>
								<input type="checkbox" value="1" name="crop_image_tiny" <?php checked( 1, $options['logos']['crop_image']['tiny'] ) ?> id="crop_image_tiny" />
								<label for="crop_image_tiny"><?php _e( 'Crop image to exact dimensions', 'leaguemanager') ?></label>
							</p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="thumb_size"><?php _e( 'Thumbnail size', 'leaguemanager' ) ?></label>
						</th>
						<td>
							<label for="thumb_width"><?php _e( 'Max Width' ) ?>&#160;</label>
							<input type="number" step="1" min="0" class="small-text" name="thumb_width" id="thumb_width" value="<?php echo $options['logos']['thumb_size']['width'] ?>" />
							<label for="thumb_height"><?php _e( 'Max Height' ) ?>&#160;</label>
							<input type="number" step="1" min="0" class="small-text" name="thumb_height" id="thumb_height" value="<?php echo $options['logos']['thumb_size']['height'] ?>" />
							<p>
								<input type="checkbox" value="1" name="crop_image_thumb" <?php checked( 1, $options['logos']['crop_image']['thumb'] ) ?> id="crop_image_thumb" />
								<label for="crop_image_thumb"><?php _e( 'Crop image to exact dimensions', 'leaguemanager') ?></label>
							</p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="large_size"><?php _e( 'Large size', 'leaguemanager' ) ?></label>
						</th>
						<td>
							<label for="large_width"><?php _e( 'Max Width' ) ?>&#160;</label>
							<input type="number" step="1" min="0" class="small-text" id="large_width" name="large_width" value="<?php echo $options['logos']['large_size']['width'] ?>" />
							<label for="large_height"><?php _e( 'Max Height' ) ?>&#160;</label>
							<input type="number" step="1" min="0" class="small-text" id="large_height" name="large_height" value="<?php echo $options['logos']['large_size']['height'] ?>" />
							<p>
								<input type="checkbox" value="1" name="crop_image_large" <?php checked( 1, $options['logos']['crop_image']['large'] ) ?> id="crop_image_large" />
								<label for="crop_image_large"><?php _e( 'Crop image to exact dimensions', 'leaguemanager') ?></label>
							</p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="regenerate_thumbnails"><?php _e( 'Regenerate Thumbnails', 'leaguemanager' ) ?>
						</th>
						<td>
							<a href="<?php echo $menu_page_url ?>&amp;regenerate_thumbnails" class="button button-secondary"><?php _e( 'Regenerate Thumbnails Now', 'leaguemanager' ) ?></a>
							<p class="setting-description"><?php _e( 'This will re-create all thumbnail images of this project. Depending on the number of images it could take some time.', 'leaguemanager' ) ?></p>
						</td>
					</tr>
				</table>
					
				<p><a href="<?php echo $menu_page_url ?>&amp;cleanUnusedFiles" class="button-secondary"><?php _e( 'List unused media files', 'leaguemanager' ) ?></a></p>
			</div>
		</div>
			
		<div id="colors" class="settings-block-container">
			<h2><?php _e( 'Color Scheme', 'leaguemanager' ) ?></h2>
			<div class="settings-block">
				<table class='lm-form-table'>
				<tr valign='top'>
					<th scope='row'><label for='color_headers'><?php _e( 'Table Headers', 'leaguemanager' ) ?></label></th><td><input type='text' name='color_headers' id='color_headers' value='<?php echo ( isset($options['colors']['headers']) ? ($options['colors']['headers']) : '' ) ?>' size='7' class="leaguemanager-colorpicker color" /><span class="colorbox" style="background-color: <?php echo $options['colors']['headers'] ?>"></span></td>
				</tr>
				<tr valign='top'>
					<th scope='row'><label for='color_rows'><?php _e( 'Table Rows', 'leaguemanager' ) ?></label></th>
					<td>
						<p class='table_rows'><input type='text' name='color_rows_alt' id='color_rows_alt' value='<?php echo (isset($options['colors']['rows']['alternate']) ? ($options['colors']['rows']['alternate']) : '' ) ?>' size='7' class="leaguemanager-colorpicker color" /><span class="colorbox" style="background-color: <?php echo $options['colors']['rows']['alternate'] ?>"></span></p>
						<p class='table_rows'><input type='text' name='color_rows' id='color_rows' value='<?php echo ( isset($options['colors']['rows']['main']) ? ($options['colors']['rows']['main']) : '' ) ?>' size='7' class="leaguemanager-colorpicker color" /><span class="colorbox" style="background-color: <?php echo $options['colors']['rows']['main'] ?>"></span></p>
					</td>
				</tr>
				<tr valign='top'>
					<th scope='row'><label for='color_rows_ascend'><?php _e( 'Teams Ascend', 'leaguemanager' ) ?></label></th><td><input type='text' name='color_rows_ascend' id='color_rows_ascend' value='<?php echo ( isset($options['colors']['rows']['ascend']) ? ($options['colors']['rows']['ascend']) : '' ) ?>' size='7' class="leaguemanager-colorpicker color" /><span class="colorbox" style="background-color: <?php echo $options['colors']['rows']['ascend'] ?>"></span></td>
				</tr>
				<tr valign='top'>
					<th scope='row'><label for='color_rows_descend'><?php _e( 'Teams Descend', 'leaguemanager' ) ?></label></th><td><input type='text' name='color_rows_descend' id='color_rows_descend' value='<?php echo ( isset($options['colors']['rows']['descend']) ? ($options['colors']['rows']['descend']) : '' ) ?>' size='7' class="leaguemanager-colorpicker color" /><span class="colorbox" style="background-color: <?php echo $options['colors']['rows']['descend'] ?>"></span></td>
				</tr>
				<tr valign='top'>
					<th scope='row'><label for='color_rows_relegation'><?php _e( 'Teams Relegation', 'leaguemanager' ) ?></label></th><td><input type='text' name='color_rows_relegation' id='color_rows_relegation' value='<?php echo ( isset($options['colors']['rows']['relegation']) ? ($options['colors']['rows']['relegation']) : '' ) ?>' size='7' class="leaguemanager-colorpicker color" /><span class="colorbox" style="background-color: <?php echo $options['colors']['rows']['relegation'] ?>"></span></td>
				</tr>
				<tr valign='top'>
					<th scope='row'><label for='color_rows'><?php _e( 'Box Header', 'projectmanager' ) ?></label></th>
					<td>
						<p class='table_rows'><input type='text' name='color_boxheader1' id='color_boxheader1' value='<?php echo $options['colors']['boxheader'][0] ?>' size='7' class="leaguemanager-colorpicker color" /><span class="colorbox" style="background-color: <?php echo $options['colors']['boxheader'][0] ?>"></span></p>				<p class='table_rows'><input type='text' name='color_boxheader2' id='color_boxheader2' value='<?php echo $options['colors']['boxheader'][1] ?>' size='7' class="leaguemanager-colorpicker color" /><span class="colorbox" style="background-color: <?php echo $options['colors']['boxheader'][1] ?>"></span></p>
					</td>
				</tr>
				</table>
			</div>
		</div>
	
		<div id="dashboard-widget" class="settings-block-container">
			<h2><?php _e('Dashboard Widget Support News', 'leaguemanager') ?></h2>
			<div class="settings-block">
				<table class='lm-form-table'>
				<tr valign='top'>
					<th scope='row'><label for='dashboard_num_items'><?php _e( 'Number of Support Threads', 'leaguemanager' ) ?></label></th><td><input type="number" step="1" min="0" class="small-text" name='dashboard[num_items]' id='dashboard_num_items' value='<?php echo $options['dashboard_widget']['num_items'] ?>' size='2' /></td>
				</tr>
				<tr valign='top'>
					<th scope='row'><label for='dashboard_show_author'><?php _e( 'Show Author', 'leaguemanager' ) ?></label></th><td><input type='checkbox' name='dashboard[show_author]' id='dashboard_show_author'<?php checked($options['dashboard_widget']['show_author'], 1) ?> /></td>
				</tr>
				<tr valign='top'>
					<th scope='row'><label for='dashboard_show_date'><?php _e( 'Show Date', 'leaguemanager' ) ?></label></th><td><input type='checkbox' name='dashboard[show_date]' id='dashboard_show_date'<?php checked($options['dashboard_widget']['show_date'], 1) ?> /></td>
				</tr>
					<tr valign='top'>
					<th scope='row'><label for='dashboard_show_summary'><?php _e( 'Show Summary', 'leaguemanager' ) ?></label></th><td><input type='checkbox' name='dashboard[show_summary]' id='dashboard_show_summary'<?php checked($options['dashboard_widget']['show_summary'], 1) ?> /></td>
				</tr>
				</table>
			</div>
		</div>

	</div>
	
	<input type='hidden' name='page_options' value='color_headers,color_rows,color_rows_alt,color_rows_ascend,color_rows_descend,color_rows_relegation' />
	<p class='submit'><input type='submit' name='updateLeagueManager' value='<?php _e( 'Save Preferences', 'leaguemanager' ) ?>' class='button button-primary' /></p>
</div>
</form>
	
<?php } ?>
