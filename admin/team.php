<script type="javascript">
Leaguemanager.reInit();
</script>
<?php
    if ( !current_user_can( 'manage_leaguemanager' ) ) {
        echo '<p style="text-align: center;">'.__("You do not have sufficient permissions to access this page.").'</p>';
	} else {
		$edit = false;
		if ( isset( $_GET['edit'] ) ) {
			$edit = true;
			$team = $leaguemanager->getTeamDtls(intval($_GET['edit']));
			if ( !isset($team->roster['id']) ) $team->roster = array('id' => '', 'cat_id' => '');
			
			$league_id = intval($_GET['league_id']);
			$form_title = __( 'Edit Team', 'leaguemanager' );
			$form_action = __( 'Update', 'leaguemanager' );
		} else {
			$form_title = __( 'Add Team', 'leaguemanager' );
			$form_action = __( 'Add', 'leaguemanager' );
			$league_id = intval($_GET['league_id']);
			$team = (object)array( 'title' => '', 'home' => 0, 'id' => '', 'logo' => '', 'website' => '', 'captain' => '', 'contactno' => '', 'contactemail' => '', 'stadium' => '', 'match_day' => '', 'match_time' => '', 'roster' => array('id' => '', 'cat_id' => '' ) );
		}
		$league = $leaguemanager->getLeague( $league_id );
		$season = isset($_GET['season']) ? htmlspecialchars(strip_tags($_GET['season'])) : '';
		$matchdays = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
		
		if ( is_plugin_active('wp-clubs/wp-clubs.php') ) {
			$clubs = getClubs();
		} else {
			$clubs = array();
		}
	
		if ( !wp_mkdir_p( $leaguemanager->getImagePath() ) ) {
			echo "<div class='error'><p>".sprintf( __( 'Unable to create directory %s. Is its parent directory writable by the server?' ), $leaguemanager->getImagePath() )."</p></div>";
		}
?>

	<div class="wrap league-block">
		<p class="leaguemanager_breadcrumb"><a href="admin.php?page=leaguemanager"><?php _e( 'LeagueManager', 'leaguemanager' ) ?></a> &raquo; <a href="admin.php?page=leaguemanager&amp;subpage=show-league&amp;league_id=<?php echo $league->id ?>"><?php echo $league->title ?></a> &raquo; <?php echo $form_title ?></p>
		<h1><?php printf( "%s &mdash; %s",  $league->title, $form_title ); ?></h1>

		<form action="admin.php?page=leaguemanager&amp;subpage=show-league&amp;league_id=<?php echo $league_id ?>&amp;season=<?php echo $season ?>" method="post" enctype="multipart/form-data" name="team_edit">
		
			<?php wp_nonce_field( 'leaguemanager_manage-teams' ) ?>

			<table class="lm-form-table">
			<tr valign="top">
				<th scope="row" style="width: 225px;"><label for="team"><?php _e( 'Team', 'leaguemanager' ) ?></label></th>
				<td>
					<input type="text" id="team" name="team" value="<?php echo $team->title ?>" size="30" />
<?php if ( !$edit ) { ?>

					<div id="teams_db" style="display: none; overflow: auto; width: 300px; height: 80px;"><div>
					<select size="1" name="team_db_select" id="team_db_select" style="display: block; margin: 0.5em auto;">
						<option value=""><?php _e( 'Choose Team', 'leaguemanager' ) ?></option>
						<?php $this->teamsDropdownCleaned() ?>
					</select>

					<div style='text-align: center; margin-top: 1em;'><input type="button" value="<?php _e('Insert', 'leaguemanager') ?>" class="button-secondary" onClick="Leaguemanager.getTeamFromDatabase(); return false;" />&#160;<input type="button" value="<?php _e('Cancel', 'leaguemanager') ?>" class="button-secondary" onClick="tb_remove();" /></div>
					</div></div>

					<a class="thickbox" href="#TB_inline&amp;width=300&amp;height=80&amp;inlineId=teams_db" title="<?php _e( 'Add Team from Database', 'leaguemanager' ) ?>"><img src="<?php echo LEAGUEMANAGER_URL ?>/admin/icons/database.png" alt="<?php _e( 'Add Team from Database', 'leaguemanager' ) ?>" title="<?php _e( 'Add Team from Database', 'leaguemanager' ) ?>" style="vertical-align: middle;" /></a>
<?php } ?>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="logo"><?php _e( 'Logo', 'leaguemanager' ) ?></label></th>
				<td>
					<div id="logo_library" style="display: none; overflow: auto;">
						<p style="text-align: center;">http://<input type="text" id="logo_library_url" size="30" /></p>
						<div style='text-align: center; margin-top: 1em;'><input type="button" value="<?php _e('Insert', 'leaguemanager') ?>" class="button-secondary" onClick="Leaguemanager.insertLogoFromLibrary(); return false;" />&#160;<input type="button" value="<?php _e('Cancel', 'leaguemanager') ?>" class="button-secondary" onClick="tb_remove();" /></div>
					</div>
					<div class="alignright" id="logo_db_box">
						<?php if ( '' != $team->logo ) : ?>
						<img id="logo_image" src="<?php echo $leaguemanager->getImageUrl($team->logo, false, 'thumb'); ?>" />
						<?php endif; ?>
					</div>

					<input type="file" name="logo" id="logo" size="35"/>&#160;<a class="thickbox" href="#TB_inline&amp;width=350&amp;height=100&amp;inlineId=logo_library" title="<?php _e( 'Add Logo from Url', 'leaguemanager' ) ?>"><img src="<?php echo LEAGUEMANAGER_URL ?>/admin/icons/image.png" alt="<?php _e( 'Add Logo from Url', 'leaguemanager' ) ?>" title="<?php _e( 'Add Logo from Url', 'leaguemanager' ) ?>" style="vertical-align: middle;" /></a>

					<p><?php _e( 'Supported file types', 'leaguemanager' ) ?>: <?php echo implode( ',',$this->getSupportedImageTypes() ); ?></p>
					
<?php if ( '' != $team->logo ) { ?>
					<p style="float: left;"><label for="overwrite_image"><?php _e( 'Overwrite existing image', 'leaguemanager' ) ?></label><input type="checkbox" id="overwrite_image" name="overwrite_image" value="1" style="margin-left: 1em;" /><label for="del_logo"><?php _e( 'Delete Logo', 'leaguemanager' ) ?></label><input type="checkbox" id="del_logo" name="del_logo" value="1" style="margin-left: 1em;" /></p>
<?php } ?>
					<input type="hidden" name="logo_db" id="logo_db" value="<?php echo $team->logo ?>" />
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="captain"><?php _e( 'Captain', 'leaguemanager' ) ?></label></th><td><input type="text" name="captain" id="coach" value="<?php echo $team->captain ?>" size="40" /></td>
			</tr>
            <tr valign="top">
                <th scope="row"><label for="contactno"><?php _e( 'Contact Number', 'leaguemanager' ) ?></label></th><td><input type="tel" name="contactno" id="contactno" value="<?php echo $team->contactno ?>" size="20" /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="contactemail"><?php _e( 'Contact Email', 'leaguemanager' ) ?></label></th><td><input type="email" name="contactemail" id="contactemail" value="<?php echo $team->contactemail ?>" size="60" /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="matchtime"><?php _e( 'Match Time', 'leaguemanager' ) ?></label></th>
				<td><input type="time" name="matchtime" id="matchtime" value="<?php echo $team->match_time ?>" size="5" /></td>
            </tr>
			</tr>
                <tr valign="top">
                <th scope="row"><label for="matchday"><?php _e( 'Match Day', 'leaguemanager' ) ?></label></th>
                <td>
                    <select size="1" name="matchday" id="matchday" >
						<option><?php _e( 'Select match day' , 'leaguemanager') ?></option>
<?php foreach ( $matchdays AS $matchday ) { ?>
                        <option value="<?php echo $matchday ?>"<?php if(isset($team->match_day)) selected($matchday, $team->match_day ) ?>><?php echo $matchday ?></option>
                        <?php } ?>
                    </select>
                </td>
            </tr>

			<tr valign="top">
				<th scope="row"><label for="stadium"><?php _e( 'Stadium', 'leaguemanager' ) ?></label></th><td><input type="text" name="stadium" id="stadium" value="<?php echo $team->stadium ?>" size="50" /></td>
			</tr>
                <tr valign="top">
                <th scope="row"><label for="affiliatedclub"><?php _e( 'Affiliated Club', 'leaguemanager' ) ?></label></th>
                <td>
                    <select size="1" name="affiliatedclub" id="affiliatedclub" >
						<option><?php _e( 'Select club' , 'leaguemanager') ?></option>
<?php foreach ( $clubs AS $club ) { ?>
                        <option value="<?php echo $club['id'] ?>"<?php if(isset($team->affiliatedclub)) selected($club['id'], $team->affiliatedclub ) ?>><?php echo $club['name'] ?></option>
                        <?php } ?>
                    </select>
                </td>
            </tr>

			<?php do_action( 'team_edit_form', $team ) ?>
			<?php do_action( 'team_edit_form_'.(isset($league->sport) ? ($league->sport) : '' ), $team ) ?>
			</table>

			<input type="hidden" name="team_id" value="<?php echo $team->id ?>" />
			<input type="hidden" name="league_id" value="<?php echo $league_id ?>" />
			<input type="hidden" name="updateLeague" value="team" />
			<input type="hidden" name="season" value="<?php echo $season ?>" />

			<p class="submit"><input type="submit" value="<?php echo $form_action ?>" class="button button-primary" /></p>
		</form>
	</div>
<?php } ?>
