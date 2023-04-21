<form action="" method="post" class="form-control">
    <?php wp_nonce_field( 'racketmanager_manage-player' ) ?>
    <div class="form-floating mb-3">
        <input class="form-control <?php if ( isset($formValid) && !$formValid && is_numeric(array_search('firstname',$errorFields))) { echo 'is-invalid'; } ?>" required placeholder="<?php _e( 'Enter first name', 'racketmanager') ?>" type="text" name="firstname" id="firstname" value="<?php if ( isset($player->firstname) ) { echo $player->firstname; } ?>" size="30" />
        <label for="firstname"><?php _e( 'First Name', 'racketmanager' ) ?></label>
    </div>
    <div class="form-floating mb-3">
        <input class="form-control <?php if ( isset($formValid) && !$formValid && is_numeric(array_search('surName',$errorFields))) { echo 'is-invalid'; } ?>"  required placeholder="<?php _e( 'Enter surname', 'racketmanager') ?>" type="text" name="surname" id="surname" value="<?php if ( isset($player->surname) ) { echo $player->surname; } ?>" size="30" />
        <label for="surname"><?php _e( 'Surname', 'racketmanager' ) ?></label>
    </div>
    <div class="form-group">
        <label><?php _e('Gender', 'racketmanager') ?></label>
        <div class="form-check">
            <input class="form-check-input <?php if ( isset($formValid) && !$formValid && is_numeric(array_search('gender',$errorFields))) { echo 'is-invalid'; } ?>" type="radio" required name="gender" id="genderMale" value="M" <?php if ( isset($player->gender) && $player->gender == 'M' ) { echo 'checked'; } ?> />
            <label for "genderMale" class="form-check-label"><?php _e('Male', 'racketmanager') ?></label>
        </div>
        <div class="form-check">
            <input class="form-check-input <?php if ( isset($formValid) && !$formValid && is_numeric(array_search('gender',$errorFields))) { echo 'is-invalid'; } ?>" type="radio" required name="gender" id="genderFemale" value="F" <?php if ( isset($player->gender) && $player->gender == 'F' ) { echo 'checked'; } ?> />
            <label for "genderFemale" class="form-check-label"><?php _e('Female', 'racketmanager') ?></label>
        </div>
    </div>
    <div class="form-floating mb-3">
        <input type="number" class="form-control <?php if ( isset($formValid) && !$formValid && is_numeric(array_search('btm',$errorFields))) { echo 'is-invalid'; } ?>" placeholder="<?php _e( 'Enter LTA Tennis Number', 'racketmanager') ?>" name="btm" id="btm" value="<?php if ( isset($player->btm) ) {  echo $player->btm; } ?>" size="11" />
        <label for="btm"><?php _e('LTA Tennis Number', 'racketmanager') ?></label>
    </div>
    <div class="form-floating mb-3">
        <input type="email" class="form-control <?php if ( isset($formValid) && !$formValid && is_numeric(array_search('email',$errorFields))) { echo 'is-invalid'; } ?>" placeholder="<?php _e( 'Enter email address', 'racketmanager') ?>" name="email" id="email" value="<?php  if ( isset($player->email) ) { echo $player->email; } ?>" />
        <label for="email"><?php _e('Email address', 'racketmanager') ?></label>
    </div>
    <?php if ( isset($player_id) ) { ?>
        <div class="form-check">
            <input class="form-check-input <?php if ( isset($formValid) && !$formValid && is_numeric(array_search('email',$errorFields))) { echo 'is-invalid'; } ?>" type="checkbox" name="locked" id="locked" value="Locked" <?php if ( isset($player->locked) && $player->locked ) { echo 'checked'; } ?> />
            <label for "locked" class="form-check-label"><?php _e('Locked', 'racketmanager') ?></label>
        </div>
    <?php } ?>
    <?php if ( isset($club_id) ) { ?>
        <input type="hidden" name="club_Id" id="club_Id" value="<?php echo $club_id ?>" />
    <?php } ?>
    <?php if ( isset($player_id) ) { ?>
        <input type="hidden" name="player_id" id="player_id" value="<?php echo $player->id ?>" />
        <input type="submit" name="updatePlayer" value="<?php _e( 'Update Player','racketmanager' ) ?>" class="btn btn-primary" />
    <?php } else { ?>
        <input type="submit" name="addPlayer" value="<?php _e( 'Add Player','racketmanager' ) ?>" class="btn btn-primary" />
    <?php } ?>
</form>
