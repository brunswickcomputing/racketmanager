<?php
global $wp_query;
$postID = $wp_query->post->ID;
$tab = 'login';
$action = isset($_GET[('action')]) ? $_GET[('action')] : '' ;
if ( isset($action) && $action == 'register' ) {
  $tab = 'registration';
}
?>
<script type='text/javascript'>
var tab = '<?php echo $tab ?>;'
var hash = window.location.hash.substr(1);
if (hash == 'teams') tab = 'teams';
jQuery(function() {
  activaTab('<?php echo $tab ?>');
});
</script>
<div class="row justify-content-center">
  <div id="tabs-login" class="col-12 col-md-9 col-lg-6">
    <!-- Nav tabs -->
    <ul class="nav nav-tabs frontend" id="loginTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="login-tab" data-bs-toggle="pill" data-bs-target="#login" type="button" role="tab" aria-controls="login" aria-selected="true"><?php _e( 'Login', 'racketmanager' ) ?></button>
      </li>
      <!--<li class="nav-item" role="presentation">
        <button class="nav-link" id="registration-tab" data-bs-toggle="pill" data-bs-target="#registration" type="button" role="tab" aria-controls="registration" aria-selected="true"><?php _e( 'Sign Up', 'racketmanager' ) ?></button>
      </li>-->
    </ul>
    <?php
    $usernameErr = false;
    $passwordErr = false;
    $usernameMsg = '';
    $passwordMsg = '';
    $emailErr = false;
    $firstNameErr = false;
    $surnameErr = false;
    $recaptchaErr = false;
    $emailMsg = '';
    $firstNameMsg = '';
    $surnameMsg = '';
    $recaptchaMsg = '';
    if ( count( $vars['errors'] ) > 0 ) { ?>
      <?php foreach ( $vars['error_codes'] as $error ) {
        if ( $error == 'empty_username' ) {
          $usernameErr = true;
          $usernameMsg = __( 'Username must be provided', 'racketmanager' );
        }
        if ( $error == 'empty_password' ) {
          $passwordErr = true;
          $passwordMsg = __( 'Password must be provided', 'racketmanager' );
        }
        if ( $error == 'incorrect_password' ) {
          $passwordErr = true;
          $passwordMsg = __( 'Password not correct', 'racketmanager' );
        }
        if ( $error == 'email' ) {
          $emailErr = true;
          $emailMsg = __( 'Email address must be specified', 'racketmanager' );
        }
        if ( $error == 'email_exists' ) {
          $emailErr = true;
          $emailMsg = __( 'An account exists with this email address', 'racketmanager' );
        }
        if ( $error == 'captcha' ) {
          $recaptchaErr = true;
          $recaptchaMsg = __( 'Google reCAPTCHA verification failed', 'racketmanager' );
        }
        if ( $error == 'first_name' ) {
          $firstNameErr = true;
          $firstNameMsg = __( 'First name must be specified', 'racketmanager' );
        }
        if ( $error == 'last_name' ) {
          $surnameErr = true;
          $surnameMsg = __( 'Last name must be specified', 'racketmanager' );
        }
        if ( $error == 'invalidkey' ) {
          $errorErr = true;
          $errorMsg = __( 'Password reset link has expired', 'racketmanager' );
        }
        ?>
      <?php } ?>
      <p class="login-error">
        <?php echo sprintf(__( 'Error in %s', 'racketmanager' ), $tab); ?>
        <?php if ( $errorErr ) {
          echo $errorMsg;
        } ?>
      </p>
    <?php } ?>
    <?php if ( isset($vars['logged_out']) && $vars['logged_out'] ) { ?>
      <p class="login-info">
        <?php _e( 'You have signed out. Would you like to sign in again?', 'racketmanager' ); ?>
      </p>
    <?php } ?>
    <?php if ( isset($vars['registered']) && $vars['registered'] ) { ?>
      <p class="login-info">
        <?php _e( 'You have successfully registered. We have emailed your password to the email address you entered.', 'racketmanager' ); ?>
      </p>
    <?php } ?>
    <?php if ( isset($vars['password_updated']) && $vars['password_updated'] ) { ?>
      <p class="login-info">
        <?php _e( 'Your password has been changed. You can sign in now.', 'racketmanager' ); ?>
      </p>
    <?php } ?>
    <!-- Tab panes -->
    <div class="tab-content">
      <div class="tab-pane fade" id="login" role="tabpanel" aria-labelledby="login-tab">
        <?php include('forms/login-page.php'); ?>
      </div>
      <div class="tab-pane fade" id="registration" role="tabpanel" aria-labelledby="registration-tab">
        <?php include('forms/register-page.php'); ?>
      </div>
    </div>
  </div>
</div>
