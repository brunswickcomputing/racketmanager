<div class="row justify-content-center">
  <div class="col-12 col-md-9">
    <h1><?php _e( 'Member Account', 'racketmanager' ); ?></h1>

    <?php if ( isset( $userData['message'] ) )  {
      if ( isset($userData['error']) ) {
        $class = 'login-error';
      } else {
        $class = 'login-info';
      } ?>
      <div id="profile-message" class="<?php echo $class ?>"><?php echo $userData['message']; ?></div>
    <?php } ?>

    <form name="memberaccountform" id="memberaccountform" action="<?php echo site_url( 'member-account' ); ?>" method="post" autocomplete="off">
      <?php wp_nonce_field( 'member_account_nonce', 'member_account_nonce_field' ); ?>

      <fieldset>
        <h2><?php _e( 'Personal Information', 'racketmanager' ) ?></h2>
        <div class="form-floating mb-3 <?php if (isset($userData['user_name_error'])) echo 'is-invalid' ?>">
          <input type="email" required="required" placeholder="<?php _e( 'Email Address', 'racketmanager' ) ?>" name="username" id="username" class="form-control" value="<?php echo $userData['user_name'] ?>" <?php if (isset($userData['user_name']) && $userData['user_name'] != '' ) echo 'readonly' ?> />
          <label for="username"><?php _e( 'Username', 'racketmanager' ) ?></label>
          <?php if (isset($userData['user_name_error'])) echo '<span class="form-error">'.$userData["user_name_error"].'</span>' ?>
        </div>
        <div class="form-floating mb-3 <?php if (isset($userData['first_name_error'])) echo 'is-invalid' ?>">
          <input type="text" autocomplete='given-name' placeholder="<?php _e( 'First Name', 'racketmanager' ) ?>" name="firstname" id="firstname" class="form-control" value="<?php echo $userData['first_name'] ?>" />
          <label for="firstname"><?php _e( 'First Name', 'racketmanager' ) ?></label>
          <?php if (isset($userData['first_name_error'])) echo '<span class="form-error">'.$userData["first_name_error"].'</span>' ?>
        </div>
        <div class="form-floating mb-3 <?php if (isset($userData['last_name_error'])) echo 'is-invalid' ?>">
          <input type="text" autocomplete='family-name' placeholder="<?php _e( 'Last Name', 'racketmanager' ) ?>" name="lastname" id="lastname" class="form-control" value="<?php echo $userData['last_name'] ?>" />
          <label for="lastname"><?php _e( 'Last Name', 'racketmanager' ) ?></label>
          <?php if (isset($userData['last_name_error'])) echo '<span class="form-error">'.$userData["last_name_error"].'</span>' ?>
        </div>
        <div class="form-floating mb-3 <?php if (isset($userData['contactno_error'])) echo 'is-invalid' ?>">
          <input type="tel" autocomplete='tel' placeholder="<?php _e( 'Telephone Number', 'racketmanager' ) ?>" name="contactno" id="contactno" class="form-control" value="<?php echo $userData['contactno'] ?>" />
          <label for="contactno"><?php _e( 'Telephone Number', 'racketmanager' ) ?></label>
          <?php if (isset($userData['contactno_error'])) echo '<span class="form-error">'.$userData["contactno_error"].'</span>' ?>
        </div>

        <div class="form-group mb-3 <?php if (isset($userData['gender_error'])) echo 'field_error' ?>">
          <label><?php _e( 'Gender', 'racketmanager' ) ?></label>
          <div class="form-check">
            <input type="radio" class="form-check-input" required="required" id="genderMale" name="gender" value="M"<?php echo ($userData['gender'] == 'M') ? 'checked' : '' ?> />
            <label for="genderMale" class="form-check-label"><?php _e('Male', 'racketmanager') ?></label>
          </div>
          <div class="form-check">
            <input type="radio" class="form-check-input" id="genderFemale" name="gender" value="F" <?php echo ($userData['gender'] == 'F') ? 'checked' : '' ?> />
            <label for="genderFemale" class="form-check-label"><?php _e('Female', 'racketmanager') ?></label>
          </div>
          <?php if (isset($userData['gender_error'])) echo '<span class="form-error">'.$userData["gender_error"].'</span>' ?>
        </div>
        <div class="form-floating mb-3 <?php if (isset($userData['btm_error'])) echo 'is-invalid' ?>">
          <input type="tel" placeholder="<?php _e( 'BTM Number', 'racketmanager' ) ?>" name="btm" id="btm" class="form-control" value="<?php echo $userData['btm'] ?>" />
          <label for="btm"><?php _e( 'BTM Number', 'racketmanager' ) ?></label>
          <?php if (isset($userData['btm_error'])) echo '<span class="form-error">'.$userData["btm_error"].'</span>' ?>
        </div>
      </fieldset>

      <fieldset>
        <h2><?php _e( 'Change Password', 'racketmanager' ) ?></h2>
        <p><?php _e('When both password fields are left empty, your password will not change', 'racketmanager'); ?></p>
        <div class="form-floating mb-3 <?php if (isset($userData['password_error'])) echo 'is-invalid' ?>">
          <input type="password" placeholder="<?php _e( 'Password', 'racketmanager' ) ?>" name="password" id="password" class="form-control password" size="20" value="" autocomplete="off" />
          <i class="passwordShow racketmanager-svg-icon">
            <?php racketmanager_the_svg('icon-eye') ?>
          </i>
          <label for="password"><?php _e( 'Password', 'racketmanager' ) ?></label>
          <?php if (isset($userData['password_error'])) echo '<span class="form-error">'.$userData["password_error"].'</span>' ?>
        </div>
        <div class="form-floating mb-3 <?php if (isset($userData['rePassword_error'])) echo 'is-invalid' ?>">
          <input type="password" placeholder="<?php _e( 'Re-enter password', 'racketmanager' ) ?>" name="rePassword" id="rePassword" class="form-control password" size="20" value="" autocomplete="off" />
          <i class="passwordShow racketmanager-svg-icon">
            <?php racketmanager_the_svg('icon-eye') ?>
          </i>
          <label for="rePassword"><?php _e( 'Confirm password', 'racketmanager' ) ?></label>
          <?php if (isset($userData['rePassword_error'])) echo '<span class="form-error">'.$userData["rePassword_error"].'</span>' ?>
        </div>
        <div class="form-group">
          <span id="password-strength"></span>
        </div>
      </fieldset>

      <div class="mb-3">
        <input type="submit" name="submit" id="memberaccount-button"
        class="button" value="<?php _e( 'Update Details', 'racketmanager' ); ?>" />
        <input name="action" type="hidden" id="action" value="update-user" />
      </div>
    </form>
  </div>
</div>
