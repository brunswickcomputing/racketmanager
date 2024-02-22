<?php
/**
 * Form to allow password to be reset
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

$email_error   = false;
$email_message = '';
$valid         = true;
if ( count( $vars['errors'] ) > 0 ) {
	$valid = false;
	foreach ( $vars['error_codes'] as $error_code ) {
		switch ( $error_code ) {
			case 'invalid_email':
				$email_error   = true;
				$email_message = __( 'No user found with that email address', 'racketmanager' );
				break;
			case 'invalidkey':
			case 'expiredkey':
				$email_error   = true;
				$email_message = __( 'The password reset link you used is no longer valid', 'racketmanager' );
				break;
			default:
				break;
		}
	}
}
?>
<div class="row justify-content-center">
	<div class="col-12 col-md-9 col-lg-6">
		<h1><?php echo esc_html_e( 'Recover Password', 'racketmanager' ); ?></h1>
		<?php if ( ! $valid ) { ?>
			<div class="login-error">
				<?php esc_html_e( 'Error in password reset', 'racketmanager' ); ?>
			</div>
		<?php } ?>
		<?php if ( isset( $vars['lost_password_sent'] ) && $vars['lost_password_sent'] ) { ?>
			<div class="login-info">
				<?php esc_html_e( 'Check your email for a link to reset your password', 'racketmanager' ); ?>
			</div>
		<?php } else { ?>
			<div class="form-info"><?php esc_html_e( "Don't worry, happens to the best of us", 'racketmanager' ); ?></div>
			<p><?php esc_html_e( "Enter your email address and we'll send you a link to create a new password", 'racketmanager' ); ?></p>

			<form id="lostpasswordform" action="<?php echo esc_url( wp_lostpassword_url() ); ?>" method="post">
				<div class="form-floating mb-3">
					<input type="email" class="form-control
					<?php
					if ( $email_error ) {
						echo ' is-invalid';
					}
					?>
					" placeholder="<?php esc_html_e( 'Email', 'racketmanager' ); ?>" name="user_login" id="user_login" aria-describedby="emailFeedback" />
					<label class="" for="user_login"><?php esc_html_e( 'Email', 'racketmanager' ); ?></label>
					<div id="emailFeedback" class="
					<?php
					if ( $email_error ) {
						echo 'invalid-feedback';
					}
					?>
					">
						<?php
						if ( $email_error ) {
							echo esc_html( $email_message );
						}
						?>
					</div>
				</div>
				<div class="form-floating mb-3">
					<input type="submit" name="submit" class="lostpassword-button" value="<?php esc_html_e( 'Email me a recovery link', 'racketmanager' ); ?>" />
				</div>
			</form>
		<?php } ?>

	</div>
</div>
