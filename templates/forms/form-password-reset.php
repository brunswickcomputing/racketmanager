<?php
$passwordErr = false;
$password2Err = false;
$passwordMsg = '';
$password2Msg = '';
$error = false;
if ( count( $vars['errors'] ) > 0 ) {
  $error = true;
  foreach ( $vars['error_codes'] as $error ) {
    if ( $error == 'password_reset_empty' ) {
      $passwordErr = true;
      $passwordMsg = __( 'New password must be entered', 'racketmanager' );
    }
    if ( $error == 'password_reset_mismatch' ) {
      $passwordErr = true;
      $password2Err = true;
      $passwordMsg = __( 'Passwords do not match', 'racketmanager' );
    }
  }
}
?>
<div class="row justify-content-center">
  <div id="tabs-login" class="col-12 col-md-9 col-lg-6">
    <h1><?php _e('Change Password', 'racketmanager'); ?></h1>
    <form name="resetpassform" id="resetpassform" action="<?php echo site_url( 'wp-login.php?action=resetpass' ); ?>" method="post" autocomplete="off">
      <input type="hidden" id="user_login" name="rp_login" value="<?php echo esc_attr( $vars['login'] ); ?>" autocomplete="off" />
      <input type="hidden" name="rp_key" value="<?php echo esc_attr( $vars['key'] ); ?>" />

      <?php if ( $error ) { ?>
        <p class="login-error">
          <?php _e( 'Error in password reset', 'racketmanager' ); ?>
        </p>
      <?php } ?>

      <div class="mb-3"><?php echo wp_get_password_hint(); ?></div>
      <div class="form-floating mb-3">
        <input class="form-control password <?php if ( $passwordErr ) { echo 'is-invalid'; } ?>" type="password" placeholder="<?php _e( 'New password', 'racketmanager' ) ?>" name="password" id="password" size="20" value="" autocomplete="off" aria-describedby="passwordFeedback" />
        <i class="passwordShow racketmanager-svg-icon">
          <?php racketmanager_the_svg('icon-eye') ?>
        </i>
        <label class="" for="password"><?php _e( 'New password', 'racketmanager' ) ?></label>
        <div id="passwordFeedback" class="<?php if ( $passwordErr ) { echo 'invalid-feedback'; } ?>">
          <?php if ( $passwordErr ) { echo $passwordMsg; } ?>
        </div>
      </div>
      <div class="form-floating mb-3">
        <input class="form-control password <?php if ( $password2Err ) { echo 'is-invalid'; } ?>" type="password" placeholder="<?php _e( 'Repeat new password', 'racketmanager' ) ?>" name="rePassword" id="rePassword" size="20" value="" autocomplete="off"  aria-describedby="password2Feedback"/>
        <i class="passwordShow racketmanager-svg-icon">
          <?php racketmanager_the_svg('icon-eye') ?>
        </i>
        <label class="" for="rePassword"><?php _e( 'Repeat new password', 'racketmanager' ) ?></label>
        <div id="password2Feedback" class="<?php if ( $password2Err ) { echo 'invalid-feedback'; } ?>">
          <?php if ( $password2Msg ) { echo $password2Msg; } ?>
        </div>
      </div>
      <div class="form-group mb-3">
        <span id="password-strength"></span>
      </div>

      <p class="resetpass-submit">
        <input type="submit" name="submit" id="resetpassButton"
        class="button" value="<?php _e( 'Reset Password', 'racketmanager' ); ?>" />
      </p>
    </form>
  </div>
</div>
