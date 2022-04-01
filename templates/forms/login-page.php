<form method="post" action="<?php echo wp_login_url(); ?>">
  <fieldset class="mt-3">
    <div class="form-floating mb-3">
      <input type="text" class="form-control <?php if ( $usernameErr ) { echo 'is-invalid'; } ?>" placeholder="<?php _e( 'Email', 'racketmanager' ); ?>" name="log" id="user_login" aria-describedby="usernameFeedback" >
      <label class="" for="user_login"><?php _e( 'Email', 'racketmanager' ); ?></label>
      <div id="usernameFeedback" class="<?php if ( $passwordErr ) { echo 'invalid-feedback'; } ?>">
        <?php if ( $usernameErr ) { echo $usernameMsg; } ?>
      </div>
    </div>
    <div class="form-floating mb-3">
      <input type="password" class="form-control password <?php if ( $passwordErr ) { echo 'is-invalid'; } ?>" name="pwd" placeholder="<?php _e( 'Password', 'racketmanager' ); ?>" id="user_pass" aria-describedby="passwordFeedback" />
      <label class="" for="user_pass"><?php _e( 'Password', 'racketmanager' ); ?></label>
      <i class="passwordShow racketmanager-svg-icon">
        <?php racketmanager_the_svg('icon-eye') ?>
      </i>
      <div id="passwordFeedback" class="<?php if ( $passwordErr ) { echo 'invalid-feedback'; } ?>">
        <?php if ( $passwordErr ) { echo $passwordMsg; } ?>
      </div>
    </div>
  </fieldset>
  <div class="row">
    <fieldset class="col-6">
      <a class="forgot-password" href="<?php echo wp_lostpassword_url(); ?>">
        <?php _e( 'Forgot your password?', 'racketmanager' ); ?>
      </a>
    </fieldset>
    <fieldset class="col-6">
      <p class="login-submit">
        <input type="submit" value="<?php _e( 'Login', 'racketmanager' ); ?>">
        <?php if ( isset($vars['redirect']) ) { ?>
          <input type="hidden" name="redirect_to" value="<?php echo esc_url( $vars['redirect'] ) ?>" />
        <?php } ?>
      </p>
    </fieldset>
  </div>
</form>
