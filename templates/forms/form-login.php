<?php
/**
 * Form for login and registration control
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

global $wp_query;
// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
$post_id = $wp_query->post->ID;
$tab     = 'login';
$action  = isset( $_GET[ ( 'action' ) ] ) ? sanitize_text_field( wp_unslash( $_GET[ ( 'action' ) ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
if ( isset( $action ) && 'register' === $action ) {
	$tab = 'registration';
}
// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited
?>
<script type='text/javascript'>
var tab = '<?php echo esc_html( $tab ); ?>;'
var hash = window.location.hash.substr(1);
if (hash == 'teams') tab = 'teams';
jQuery(function() {
	activaTab('<?php echo esc_html( $tab ); ?>');
});
</script>
<div class="row justify-content-center">
	<div id="tabs-login" class="col-12 col-md-9 col-lg-6">
	<!-- Nav tabs -->
	<ul class="nav nav-tabs frontend" id="loginTabs" role="tablist">
		<li class="nav-item" role="presentation">
			<button class="nav-link" id="login-tab" data-bs-toggle="pill" data-bs-target="#login" type="button" role="tab" aria-controls="login" aria-selected="true"><?php esc_html_e( 'Login', 'racketmanager' ); ?></button>
		</li>
		<?php
		if ( '1' === get_option( 'users_can_register' ) ) {
			?>
			<li class="nav-item" role="presentation">
				<button class="nav-link" id="registration-tab" data-bs-toggle="pill" data-bs-target="#registration" type="button" role="tab" aria-controls="registration" aria-selected="true"><?php esc_html_e( 'Sign Up', 'racketmanager' ); ?></button>
			</li>
		<?php } ?>
	</ul>
	<?php
	$username_error    = false;
	$password_error    = false;
	$username_message  = '';
	$password_message  = '';
	$other_error       = false;
	$email_error       = false;
	$firstname_error   = false;
	$surname_error     = false;
	$recaptcha_error   = false;
	$email_message     = '';
	$firstname_message = '';
	$surname_message   = '';
	$recaptcha_message = '';
	if ( count( $vars['errors'] ) > 0 ) {
		?>
		<?php
		foreach ( $vars['error_codes'] as $error_code ) {
			switch ( $error_code ) {
				case 'empty_username':
					$username_error   = true;
					$username_message = __( 'Username must be provided', 'racketmanager' );
					break;
				case 'empty_password':
					$password_error   = true;
					$password_message = __( 'Password must be provided', 'racketmanager' );
					break;
				case 'incorrect_password':
					$password_error   = true;
					$password_message = __( 'Password not correct', 'racketmanager' );
					break;
				case 'email':
					$email_error   = true;
					$email_message = __( 'Email address must be specified', 'racketmanager' );
					break;
				case 'email_exists':
					$email_error   = true;
					$email_message = __( 'An account exists with this email address', 'racketmanager' );
					break;
				case 'captcha':
					$recaptcha_error   = true;
					$recaptcha_message = __( 'Google reCAPTCHA verification failed', 'racketmanager' );
					break;
				case 'first_name':
					$firstname_error   = true;
					$firstname_message = __( 'First name must be specified', 'racketmanager' );
					break;
				case 'last_name':
					$surname_error   = true;
					$surname_message = __( 'Last name must be specified', 'racketmanager' );
					break;
				case 'invalidkey':
					$other_error   = true;
					$error_message = __( 'Password reset link has expired', 'racketmanager' );
					break;
				case 'security':
					$other_error   = true;
					$error_message = __( 'Form has expired. Please refresh the page and resubmit.', 'racketmanager' );
					break;
				default:
					break;
			}
			?>
		<?php } ?>
		<p class="login-error">
			<?php
			/* translators: %s: tab */
			printf( esc_html( __( 'Error in %s', 'racketmanager' ) ), esc_html( $tab ) );
			?>
			<?php
			if ( $other_error ) {
				echo esc_html( $error_message );
			}
			?>
		</p>
	<?php } ?>
	<?php if ( isset( $vars['logged_out'] ) && $vars['logged_out'] ) { ?>
		<p class="login-info">
		<?php esc_html_e( 'You have signed out. Would you like to sign in again?', 'racketmanager' ); ?>
		</p>
	<?php } ?>
	<?php if ( isset( $vars['registered'] ) && $vars['registered'] ) { ?>
		<p class="login-info">
		<?php esc_html_e( 'You have successfully registered. We have emailed your password to the email address you entered.', 'racketmanager' ); ?>
		</p>
	<?php } ?>
	<?php if ( isset( $vars['password_updated'] ) && $vars['password_updated'] ) { ?>
		<p class="login-info">
		<?php esc_html_e( 'Your password has been changed. You can sign in now.', 'racketmanager' ); ?>
		</p>
	<?php } ?>
	<!-- Tab panes -->
	<div class="tab-content">
		<div class="tab-pane fade" id="login" role="tabpanel" aria-labelledby="login-tab">
		<?php require 'login-page.php'; ?>
		</div>
		<div class="tab-pane fade" id="registration" role="tabpanel" aria-labelledby="registration-tab">
		<?php require 'register-page.php'; ?>
		</div>
	</div>
	</div>
</div>
