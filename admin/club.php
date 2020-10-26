<script type="javascript">
Leaguemanager.reInit();
</script>
<!-- <script type="text/javascript" src="/wp-includes/js/jquery/jquery.js"></script> -->
<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?libraries=places&key=AIzaSyBOw-540qtgbXJNz0D-zMcii1eAFYO1P1Y"></script>
<script type="text/javascript" src="<?php echo plugins_url('/admin/js/locationpicker.jquery.js', dirname(__FILE__)) ?>"></script>
<script type="text/javascript" src="<?php echo plugins_url('/admin/js/locationpicker.js', dirname(__FILE__)) ?>"></script>
<?php
    if ( !current_user_can( 'manage_leaguemanager' ) ) {
        echo '<p style="text-align: center;">'.__("You do not have sufficient permissions to access this page.").'</p>';
	} else {
		$edit = false;
        $noleague = true;
        $league_id = '';
        $season = '';
        if ( isset( $_GET['club_id'] ) ) {
            $clubId = $_GET['club_id'];
			$edit = true;
			$club = $leaguemanager->getClub(array( 'id' => $clubId ));
			
			$form_title = __( 'Edit Club', 'leaguemanager' );
			$form_action = __( 'Update', 'leaguemanager' );
		} else {
            $clubId = '';
			$form_title = __( 'Add Club', 'leaguemanager' );
			$form_action = __( 'Add', 'leaguemanager' );
			$club = (object)array( 'name' => '', 'type' => '', 'id' => '', 'logo' => '', 'website' => '', 'matchsecretary' => '', 'matchSecretaryName' => '', 'contactno' => '', 'matchSecretaryContactno' => '', 'matchSecretaryEmail' => '', 'shortcode' => '', 'founded' => '', 'facilities' => '', 'address' => '', 'latitude' => '', 'longitude' => '' );
		}
	
		if ( !wp_mkdir_p( $leaguemanager->getImagePath() ) ) {
			echo "<div class='error'><p>".sprintf( __( 'Unable to create directory %s. Is its parent directory writable by the server?' ), $leaguemanager->getImagePath() )."</p></div>";
		}
?>
	<div class="wrap league-block">
        <p class="leaguemanager_breadcrumb"><a href="admin.php?page=leaguemanager"><?php _e( 'LeagueManager', 'leaguemanager' ) ?></a> &raquo; <?php echo $form_title ?></p>
        <h1><?php printf(  $form_title ); ?></h1>


<form action="admin.php?page=leaguemanager&amp;view=clubs<?php if ( $clubId !== '' ) { ?>&amp;club_id=<?php echo $clubId ?> <?php } ?>" method="post" enctype="multipart/form-data" name="club_edit">

<?php if ( $edit ) { ?>
            <?php wp_nonce_field( 'leaguemanager_manage-club' ) ?>
<?php } else { ?>
            <?php wp_nonce_field( 'leaguemanager_add-club' ) ?>
<?php } ?>

			<table class="lm-form-table">
			<tr valign="top">
				<th scope="row" style="width: 225px;"><label for="team"><?php _e( 'Club', 'leaguemanager' ) ?></label></th>
				<td>
					<input type="text" id="club" name="club" value="<?php echo $club->name ?>" size="30" placeholder="<?php _e( 'Add Club', 'leaguemanager' ) ?>""/>
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
						<?php if ( '' != $club->logo ) { ?>
						<img id="logo_image" src="<?php echo $leaguemanager->getImageUrl($club->logo, false, 'thumb'); ?>" />
                        <?php } ?>
					</div>

					<input type="file" name="logo" id="logo" size="35"/>&#160;<a class="thickbox" href="#TB_inline&amp;width=350&amp;height=100&amp;inlineId=logo_library" title="<?php _e( 'Add Logo from Url', 'leaguemanager' ) ?>"><img src="<?php echo LEAGUEMANAGER_URL ?>/admin/icons/image.png" alt="<?php _e( 'Add Logo from Url', 'leaguemanager' ) ?>" title="<?php _e( 'Add Logo from Url', 'leaguemanager' ) ?>" style="vertical-align: middle;" /></a>

					<p><?php _e( 'Supported file types', 'leaguemanager' ) ?>: <?php echo implode( ',',$this->getSupportedImageTypes() ); ?></p>
					
<?php if ( '' != $club->logo ) { ?>
					<p style="float: left;"><label for="overwrite_image"><?php _e( 'Overwrite existing image', 'leaguemanager' ) ?></label><input type="checkbox" id="overwrite_image" name="overwrite_image" value="1" style="margin-left: 1em;" /><label for="del_logo"><?php _e( 'Delete Logo', 'leaguemanager' ) ?></label><input type="checkbox" id="del_logo" name="del_logo" value="1" style="margin-left: 1em;" /></p>
<?php } ?>
					<input type="hidden" name="logo_db" id="logo_db" value="<?php echo $club->logo ?>" />
				</td>
			</tr>
            <tr valign="top">
                <th scope="row"><label for="type"><?php _e( 'Type', 'leaguemanager' ) ?></label></th>
                <td>
                    <select size="1" name="type" id="type" >
						<option><?php _e( 'Select type' , 'leaguemanager') ?></option>
                        <option value="Affiliated" <?php selected( 'Affiliated', $club->type ) ?>><?php _e( 'Affiliated', 'leaguemanager') ?></option>
                    </select>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row"><label for="shortcode"><?php _e( 'Shortcode', 'leaguemanager' ) ?></label></th><td><input type="text" name="shortcode" id="shortcode"  value="<?php echo $club->shortcode ?>" size="20" /></td>
            </tr>
<?php if ( $edit ) { ?>
			<tr valign="top">
				<th scope="row"><label for="matchSecretaryName"><?php _e( 'Match secretary', 'leaguemanager' ) ?></label></th><td><input type="text" name="matchSecretaryName" id="matchSecretaryName" autocomplete="name off" value="<?php echo $club->matchSecretaryName ?>" size="40" /><input type="hidden" name="matchsecretary" id="matchsecretary" value="<?php echo $club->matchsecretary ?>" /></td>
			</tr>
            <tr valign="top">
                <th scope="row"><label for="matchSecretaryContactno"><?php _e( 'Match secretary contact', 'leaguemanager' ) ?></label></th><td><input type="tel" name="matchSecretaryContactno" id="matchSecretaryContactno" autocomplete="tel" value="<?php echo $club->matchSecretaryContactno ?>" size="20" /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="matchSecretaryEmail"><?php _e( 'Match secretary email', 'leaguemanager' ) ?></label></th><td><input type="email" name="matchSecretaryEmail" id="matchSecretaryEmail" autocomplete="email" value="<?php echo $club->matchSecretaryEmail ?>" size="60" /></td>
            </tr>
<?php } ?>
            <tr valign="top">
                <th scope="row"><label for="contactno"><?php _e( 'Contact Number', 'leaguemanager' ) ?></label></th><td><input type="tel" name="contactno" id="contactno" autocomplete="tel" value="<?php echo $club->contactno ?>" size="20" /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="website"><?php _e( 'Website', 'leaguemanager' ) ?></label></th><td><input type="url" name="website" id="website"  value="<?php echo $club->website ?>" size="60" /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="founded"><?php _e( 'Founded', 'leaguemanager' ) ?></label></th><td><input type="number" name="founded" id="founded"  value="<?php echo $club->founded ?>" size="4" /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="facilities"><?php _e( 'Facilities', 'leaguemanager' ) ?></label></th><td><input type="text" name="facilities" id="facilities"  value="<?php echo $club->facilities ?>" size="60" /></td>
            </tr>
            <tr valign="top">
                <th scope="row" rowspan="3"><label for="address"><?php _e( 'Address', 'leaguemanager' ) ?></label></th><td><input type="text" name="address" id="address"  value="<?php echo $club->address ?>" size="100" /></td><td><input type="hidden" name="latitude" id="latitude"  value="<?php echo $club->latitude ?>" size="20" /></td><td><input type="hidden" name="longitude" id="longitude"  value="<?php echo $club->longitude ?>" size="20" /></td>
            </tr>
            <tr valign="top">
                <td id="club-location-picker"></td>
            </tr>
            <tr valign="top">
                <td>Drag the marker to the clubs location</td>
            </tr>
			<?php do_action( 'club_edit_form', $club ) ?>
			</table>

			<input type="hidden" name="club_id" id="club_id" value="<?php echo $club->id ?>" />
			<input type="hidden" name="updateLeague" value="club" />

<?php if ( $edit ) { ?>
            <input type="hidden" name="editClub" value="club" />
<?php } else { ?>
            <input type="hidden" name="addClub" value="club" />
<?php } ?>

			<p class="submit"><input type="submit" name="action" value="<?php echo $form_action ?>" class="button button-primary" /></p>
		</form>

	</div>
<?php } ?>
