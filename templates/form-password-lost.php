<?php
$emailErr = false;
$emailMsg = '';
$error = false;
if ( count( $vars['errors'] ) > 0 ) {
  $error = true;
  foreach ( $vars['error_codes'] as $error ) {
    if ( $error == 'invalid_email' ) {
      $emailErr = true;
      $emailMsg = __( 'No user found with that email address', 'racketmanager' );
    }
    if ( $error == 'invalidkey' || $error == 'expiredkey' ) {
      $emailErr = true;
      $emailMsg = __( 'The password reset link you used is no longer valid', 'racketmanager' );
    }
  }
}
?>
<div class="row justify-content-center">
  <div class="col-12 col-md-9 col-lg-6">
    <?php if ( $error ) { ?>
      <p class="login-error">
        <?php _e( 'Error in password reset', 'racketmanager' ); ?>
      </p>
    <?php } ?>
    <?php if ( isset($vars['lost_password_sent']) && $vars['lost_password_sent'] ) { ?>
      <p class="login-info">
        <?php _e( 'Check your email for a link to reset your password', 'racketmanager' ); ?>
      </p>
    <?php } ?>

    <p><?php _e( "Enter the email address you use and we'll send you a link to create a new password.", 'racketmanager' ); ?></p>

    <form id="lostpasswordform" action="<?php echo wp_lostpassword_url(); ?>" method="post">
      <div class="form-floating mb-3">
        <input type="email" class="form-control <?php if ( $emailErr ) { echo 'is-invalid'; } ?>" placeholder="<?php _e( 'Email', 'racketmanager' ); ?>" name="user_login" id="user_login" aria-describedby="emailFeedback" />
        <label class="" for="user_login"><?php _e( 'Email', 'racketmanager' ); ?></label>
        <div id="emailFeedback" class="<?php if ( $emailErr ) { echo 'invalid-feedback'; } ?>">
          <?php if ( $emailErr ) { echo $emailMsg; } ?>
        </div>
      </div>
      <div class="form-floating mb-3">
        <input type="submit" name="submit" class="lostpassword-button" value="<?php _e( 'Reset Password', 'racketmanager' ); ?>" />
      </div>
    </form>
  </div>
</div>
