<?php
/**
* Club Player main page administration panel
*
*/
namespace ns;
?>
<div class="container">
	<div class="row justify-content-end">
		<div class="col-auto racketmanager_breadcrumb">
			<a href="admin.php?page=racketmanager-clubs"><?php _e( 'Clubs', 'racketmanager' ) ?></a> &raquo; <?php _e( 'Players', 'racketmanager' ) ?>
		</div>
	</div>
	<h1><?php _e( 'Player', 'racketmanager' ) ?> - <?php echo $roster->fullname ?></h1>

	<!-- View Rosters -->
	<div class="mb-3">
		<form action="admin.php?page=racketmanager-clubs&view=roster&club_id=<?php echo $club_id ?>" method="post" class="form-control">
			<?php wp_nonce_field( 'racketmanager_edit-roster' ) ?>
			<div class="form-floating mb-3">
				<input class="form-control" required="required" placeholder="<?php _e( 'Enter first name', 'racketmanager') ?>" type="text" name="firstname" id="firstname" value="<?php echo $roster->firstname ?>" size="30" />
				<label for="firstname"><?php _e( 'First Name', 'racketmanager' ) ?></label>
			</div>
			<div class="form-floating mb-3">
				<input class="form-control" required="required"  placeholder="<?php _e( 'Enter surname', 'racketmanager') ?>" type="text" name="surname" id="surname" value="<?php echo $roster->surname ?>" size="30" />
				<label for="surname"><?php _e( 'Surname', 'racketmanager' ) ?></label>
			</div>
			<div class="form-group">
				<label><?php _e('Gender', 'racketmanager') ?></label>
				<div class="form-check">
					<input class="form-check-input" type="radio" required="required" name="gender" id="genderMale" value="M" <?php if ($roster->gender == 'M') { echo 'checked'; } ?> />
					<label for "genderMale" class="form-check-label"><?php _e('Male', 'racketmanager') ?></label>
				</div>
				<div class="form-check">
					<input class="form-check-input" type="radio" required="required" name="gender" id="genderFemale" value="F" <?php if ($roster->gender == 'F') { echo 'checked'; } ?> />
					<label for "genderFemale" class="form-check-label"><?php _e('Female', 'racketmanager') ?></label>
				</div>
			</div>
			<div class="form-floating mb-3">
				<input type="number" class="form-control" placeholder="<?php _e( 'Enter BTM number', 'racketmanager') ?>" name="btm" id="gender" value="<?php echo $roster->btm ?>" size="11" />
				<label for="btm"><?php _e('BTM', 'racketmanager') ?></label>
			</div>
			<div class="form-floating mb-3">
				<input type="email" placeholder="<?php _e( 'Enter email address', 'racketmanager') ?>" name="email" id="email" class="form-control" value="<?php echo $roster->email ?>" />
				<label for="email"><?php _e('Email address', 'racketmanager') ?></label>
			</div>
			<div class="form-check">
				<input class="form-check-input" type="checkbox" name="locked" id="locked" value="Locked" <?php if ($roster->locked ) { echo 'checked'; } ?> />
				<label for "locked" class="form-check-label"><?php _e('Locked', 'racketmanager') ?></label>
			</div>
			<input type="hidden" name="club_Id" id="club_Id" value="<?php echo $club_id ?>" />
			<input type="hidden" name="roster_id" id="roster_id" value="<?php echo $roster->id ?>" />
			<input type="hidden" name="player_id" id="player_id" value="<?php echo $roster->player_id ?>" />
			<input type="submit" name="editRosterPlayer" value="<?php _e( 'Edit Player','racketmanager' ) ?>" class="btn btn-primary" />

		</form>
	</div>

</div>
