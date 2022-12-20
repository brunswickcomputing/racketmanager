<?php
?>
<div class="container league-block">
	<div class="row justify-content-end">
		<div class="col-auto racketmanager_breadcrumb">
			<a href="admin.php?page=racketmanager-clubs"><?php _e( 'RacketManager', 'racketmanager' ) ?></a> &raquo; <?php echo $form_title ?>
		</div>
	</div>
	<h1><?php printf(  $form_title ); ?></h1>

	<form action="admin.php?page=racketmanager-clubs<?php if ( $clubId !== '' ) { ?>&amp;club_id=<?php echo $clubId ?> <?php } ?>" method="post" enctype="multipart/form-data" name="club_edit" class="form-control">

		<?php if ( $edit ) { ?>
			<?php wp_nonce_field( 'racketmanager_manage-club' ) ?>
		<?php } else { ?>
			<?php wp_nonce_field( 'racketmanager_add-club' ) ?>
		<?php } ?>

		<div class="form-floating mb-3">
			<input type="text" class="form-control" id="club" name="club" value="<?php echo $club->name ?>" size="30" placeholder="<?php _e( 'Add Club', 'racketmanager' ) ?>" />
			<label for="team"><?php _e( 'Club', 'racketmanager' ) ?></label>
		</div>
		<div class="form-floating mb-3">
			<select class="form-select" size="1" name="type" id="type" >
				<option><?php _e( 'Select type' , 'racketmanager') ?></option>
				<option value="Affiliated" <?php selected( 'Affiliated', $club->type ) ?>><?php _e( 'Affiliated', 'racketmanager') ?></option>
			</select>
			<label for="type"><?php _e( 'Type', 'racketmanager' ) ?></label>
		</div>
		<div class="form-floating mb-3">
				<input type="text" class="form-control" name="shortcode" id="shortcode"  value="<?php echo $club->shortcode ?>" size="20" placeholder="<?php _e( 'Enter shortcode', 'racketmanager' ) ?>" />
				<label for="shortcode"><?php _e( 'Shortcode', 'racketmanager' ) ?></label>
		</div>
		<?php if ( $edit ) { ?>
			<div class="form-floating mb-3">
					<input type="text" class="form-control" name="matchSecretaryName" id="matchSecretaryName" autocomplete="name off" value="<?php echo $club->matchSecretaryName ?>" size="40" /><input type="hidden" name="matchsecretary" id="matchsecretary" value="<?php echo $club->matchsecretary ?>" />
					<label for="matchSecretaryName"><?php _e( 'Match secretary', 'racketmanager' ) ?></label>
			</div>
			<div class="form-floating mb-3">
					<input type="tel" class="form-control" name="matchSecretaryContactNo" id="matchSecretaryContactNo" autocomplete="tel" value="<?php echo $club->matchSecretaryContactNo ?>" size="20" placeholder="<?php _e( 'Enter contact number', 'racketmanager' ) ?>" />
					<label for="matchSecretaryContactNo"><?php _e( 'Match secretary contact', 'racketmanager' ) ?></label>
			</div>
			<div class="form-floating mb-3">
					<input type="email" class="form-control" name="matchSecretaryEmail" id="matchSecretaryEmail" autocomplete="email" value="<?php echo $club->matchSecretaryEmail ?>" size="60" placeholder="<?php _e( 'Enter contact email', 'racketmanager' ) ?>" />
					<label for="matchSecretaryEmail"><?php _e( 'Match secretary email', 'racketmanager' ) ?></label>
			</div>
		<?php } ?>
		<div class="form-floating mb-3">
				<input type="tel" class="form-control" name="contactno" id="contactno" autocomplete="tel" value="<?php echo $club->contactno ?>" size="20" placeholder="<?php _e( 'Enter contact number', 'racketmanager' ) ?>" />
				<label for="contactno"><?php _e( 'Contact Number', 'racketmanager' ) ?></label>
		</div>
		<div class="form-floating mb-3">
				<input type="url" class="form-control" name="website" id="website"  value="<?php echo $club->website ?>" size="60" placeholder="<?php _e( 'Enter club web address', 'racketmanager' ) ?>" />
				<label for="website"><?php _e( 'Website', 'racketmanager' ) ?></label>
		</div>
		<div class="form-floating mb-3">
				<input type="number" class="form-control" name="founded" id="founded"  value="<?php echo $club->founded ?>" size="4" placeholder="<?php _e( 'Enter founded year', 'racketmanager' ) ?>" />
				<label for="founded"><?php _e( 'Founded', 'racketmanager' ) ?></label>
		</div>
		<div class="form-floating mb-3">
				<input type="text" class="form-control" name="facilities" id="facilities"  value="<?php echo $club->facilities ?>" size="60" placeholder="<?php _e( 'Enter club facilities', 'racketmanager' ) ?>" />
				<label for="facilities"><?php _e( 'Facilities', 'racketmanager' ) ?></label>
		</div>
		<div class="form-floating mb-3">
				<input type="text" class="form-control" name="address" id="address"  value="<?php echo $club->address ?>" size="100" />
				<input type="hidden" name="latitude" id="latitude"  value="<?php echo $club->latitude ?>" size="20" />
				<input type="hidden" name="longitude" id="longitude"  value="<?php echo $club->longitude ?>" size="20" />
				<label for="address"><?php _e( 'Address', 'racketmanager' ) ?></label>
		</div>
		<?php do_action( 'club_edit_form', $club ) ?>

		<input type="hidden" name="club_id" id="club_id" value="<?php echo $club->id ?>" />
		<input type="hidden" name="updateLeague" value="club" />

		<?php if ( $edit ) { ?>
			<input type="hidden" name="editClub" value="club" />
		<?php } else { ?>
			<input type="hidden" name="addClub" value="club" />
		<?php } ?>

		<p class="submit"><input type="submit" name="action" value="<?php echo $form_action ?>" class="btn btn-primary" /></p>
	</form>

</div>
