<?php
?>
<!-- <script type="text/javascript" src="/wp-includes/js/jquery/jquery.js"></script> -->
<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?libraries=places&key=AIzaSyBOw-540qtgbXJNz0D-zMcii1eAFYO1P1Y"></script>
<script type="text/javascript" src="<?php echo plugins_url('/js/locationpicker.jquery.js', dirname(__FILE__)) ?>"></script>
<script type="text/javascript" src="<?php echo plugins_url('/js/locationpicker.js', dirname(__FILE__)) ?>"></script>
	<div class="wrap league-block">
        <p class="leaguemanager_breadcrumb"><a href="admin.php?page=leaguemanager"><?php _e( 'LeagueManager', 'leaguemanager' ) ?></a> &raquo; <?php echo $form_title ?></p>
        <h1><?php printf(  $form_title ); ?></h1>


        <form action="admin.php?page=leaguemanager&amp;view=clubs<?php if ( $clubId !== '' ) { ?>&amp;club_id=<?php echo $clubId ?> <?php } ?>" method="post" enctype="multipart/form-data" name="club_edit">

<?php if ( $edit ) { ?>
            <?php wp_nonce_field( 'leaguemanager_manage-club' ) ?>
<?php } else { ?>
            <?php wp_nonce_field( 'leaguemanager_add-club' ) ?>
<?php } ?>

			<div class="form-group">
				<label for="team"><?php _e( 'Club', 'leaguemanager' ) ?></label>
                <div class="input">
					<input type="text" id="club" name="club" value="<?php echo $club->name ?>" size="30" placeholder="<?php _e( 'Add Club', 'leaguemanager' ) ?>""/>
				</div>
			</div>
            <div class="form-group">
                <label for="type"><?php _e( 'Type', 'leaguemanager' ) ?></label>
                <div class="input">
                    <select size="1" name="type" id="type" >
						<option><?php _e( 'Select type' , 'leaguemanager') ?></option>
                        <option value="Affiliated" <?php selected( 'Affiliated', $club->type ) ?>><?php _e( 'Affiliated', 'leaguemanager') ?></option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="shortcode"><?php _e( 'Shortcode', 'leaguemanager' ) ?></label>
                <div class="input">
                    <input type="text" name="shortcode" id="shortcode"  value="<?php echo $club->shortcode ?>" size="20" />
                </div>
            </div>
<?php if ( $edit ) { ?>
            <div class="form-group">
				<label for="matchSecretaryName"><?php _e( 'Match secretary', 'leaguemanager' ) ?></label>
                <div class="input">
                    <input type="text" name="matchSecretaryName" id="matchSecretaryName" autocomplete="name off" value="<?php echo $club->matchSecretaryName ?>" size="40" /><input type="hidden" name="matchsecretary" id="matchsecretary" value="<?php echo $club->matchsecretary ?>" />
                </div>
            </div>
            <div class="form-group">
                <label for="matchSecretaryContactNo"><?php _e( 'Match secretary contact', 'leaguemanager' ) ?></label>
                <div class="input">
                    <input type="tel" name="matchSecretaryContactNo" id="matchSecretaryContactNo" autocomplete="tel" value="<?php echo $club->matchSecretaryContactNo ?>" size="20" />
                </div>
            </div>
            <div class="form-group">
                <label for="matchSecretaryEmail"><?php _e( 'Match secretary email', 'leaguemanager' ) ?></label>
                <div class="input">
                    <input type="email" name="matchSecretaryEmail" id="matchSecretaryEmail" autocomplete="email" value="<?php echo $club->matchSecretaryEmail ?>" size="60" />
                </div>
            </div>
<?php } ?>
            <div class="form-group">
                <label for="contactno"><?php _e( 'Contact Number', 'leaguemanager' ) ?></label>
                <div class="input">
                    <input type="tel" name="contactno" id="contactno" autocomplete="tel" value="<?php echo $club->contactno ?>" size="20" />
                </div>
            </div>
            <div class="form-group">
                <label for="website"><?php _e( 'Website', 'leaguemanager' ) ?></label>
                <div class="input">
                    <input type="url" name="website" id="website"  value="<?php echo $club->website ?>" size="60" />
                </div>
            </div>
            <div class="form-group">
                <label for="founded"><?php _e( 'Founded', 'leaguemanager' ) ?></label>
                <div class="input">
                    <input type="number" name="founded" id="founded"  value="<?php echo $club->founded ?>" size="4" />
                </div>
            </div>
            <div class="form-group">
                <label for="facilities"><?php _e( 'Facilities', 'leaguemanager' ) ?></label>
                <div class="input">
                    <input type="text" name="facilities" id="facilities"  value="<?php echo $club->facilities ?>" size="60" />
                </div>
            </div>
            <div class="form-group">
                <label for="address"><?php _e( 'Address', 'leaguemanager' ) ?></label>
                <div class="input">
                    <input type="text" name="address" id="address"  value="<?php echo $club->address ?>" size="100" />
                    <input type="hidden" name="latitude" id="latitude"  value="<?php echo $club->latitude ?>" size="20" />
                    <input type="hidden" name="longitude" id="longitude"  value="<?php echo $club->longitude ?>" size="20" />
                </div>
                <div class="input" id="club-location-picker"></div>
                <div class="input">Drag the marker to the clubs location</div>
            </div>
			<?php do_action( 'club_edit_form', $club ) ?>

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
