<?php
/**
Template page for a player

The following variables are usable:

$club: contains data of current club
$player: contains the player details

You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
*/
$userCanUpdate = false;
if ( is_user_logged_in() ) {
  $user = wp_get_current_user();
  $userid = $user->ID;
  if ( current_user_can( 'manage_racketmanager' ) ) {
    $userCanUpdate = true;
  } else {
    if ( $player->ID !=null && $player->ID == $userid ) {
		$userCanUpdate = true;
	} elseif ( $club->matchsecretary !=null && $club->matchsecretary == $userid ) {
      	$userCanUpdate = true;
	} else {
		$userCanUpdate = false;
    }
  }
}
?>
<h1 class="club-name">
  <?php echo $club->name.' - '.$player->fullname ?>
</h1>
<div class="entry-content">
	<form id="playerUpdateFrm" action="" method="post">
		<?php wp_nonce_field( 'player-update' ) ?>
		<input type="hidden" id="playerId" name="playerId" value="<?php echo $player->ID ?>" />
		<?php if ($player->firstname !=null || $userCanUpdate) { ?>
			<div class="form-floating mb-3">
				<input type="text" class="form-control" id="firstname" name="firstname" value="<?php echo $player->firstname ?>" <?php disabled($userCanUpdate, false) ?> />
				<label for "firstname"><?php _e( 'First name', 'racketmanager' ) ?></label>
				<div id="firstnameFeedback" class="invalid-feedback"></div>
			</div>
		<?php } ?>
		<?php if ($player->surname !=null || $userCanUpdate) { ?>
			<div class="form-floating mb-3">
				<input type="text" class="form-control" id="surname" name="surname" value="<?php echo $player->surname ?>" <?php disabled($userCanUpdate, false) ?> />
				<label for "surname"><?php _e( 'Surname', 'racketmanager' ) ?></label>
				<div id="surnameFeedback" class="invalid-feedback"></div>
			</div>
		<?php } ?>
		<?php if ($player->gender !=null || $userCanUpdate) { ?>
			<div class="form-group mb-3">
				<label><?php _e('Gender', 'racketmanager') ?></label>
				<div class="form-check">
					<input class="form-check-input" type="radio" required name="gender" id="genderMale" value="M" <?php if ( isset($player->gender) && $player->gender == 'M' ) { echo 'checked'; } ?> <?php disabled($userCanUpdate, false) ?> />
					<label for "genderMale" class="form-check-label"><?php _e('Male', 'racketmanager') ?></label>
				</div>
				<div class="form-check">
					<input class="form-check-input" type="radio" required name="gender" id="genderFemale" value="F" <?php if ( isset($player->gender) && $player->gender == 'F' ) { echo 'checked'; } ?> <?php disabled($userCanUpdate, false) ?> />
					<label for "genderFemale" class="form-check-label"><?php _e('Female', 'racketmanager') ?></label>
				</div>
			</div>
		<?php } ?>
		<?php if ($player->btm !=null || $userCanUpdate) { ?>
			<div class="form-floating mb-3">
				<input type="number" class="form-control" id="btm" name="btm" value="<?php echo $player->btm ?>" <?php disabled($userCanUpdate, false) ?> />
				<label for "btm"><?php _e( 'LTA Tennis Number', 'racketmanager' ) ?></label>
				<div id="btmFeedback" class="invalid-feedback"></div>
			</div>
		<?php } ?>
		<?php if ($player->email !=null || $userCanUpdate) { ?>
			<div class="form-floating mb-3">
				<input type="email" class="form-control" id="email" name="email" value="<?php echo $player->email ?>" <?php disabled($userCanUpdate, false) ?> />
				<label for "email"><?php _e( 'Email address', 'racketmanager' ) ?></label>
				<div id="emailFeedback" class="invalid-feedback"></div>
			</div>
		<?php } ?>
		<?php if ( $userCanUpdate ) { ?>
			<button class="btn" type="button" id="updatePlayerSubmit" name="updatePlayerSubmit" onclick="Racketmanager.updatePlayer(this)"><?php _e( 'Update details', 'racketmanager' ) ?></button>
			<div class="updateResponse" id="updatePlayer" name="updatePlayer"></div>
		<?php } ?>
	</form>
	<a href="/clubs/<?php echo sanitize_title($club->name) ?>/#players"<button class="btn btn-secondary" type="button" id="updatePlayerSubmit" name="updatePlayerSubmit"><?php _e( 'Return to club', 'racketmanager' ) ?></button></a>
</div>
