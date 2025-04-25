<?php
/**
 * Template for password reset form
 *
 * @package Racketmanager/Templates/Forms
 */

namespace Racketmanager;

$password_error     = false;
$password_2_error   = false;
$password_message   = '';
$error_found        = false;
/** @var string $login_error_msg */
/** @var array $vars */
if ( count( $vars['errors'] ) > 0 ) {
	$error_found     = true;
	$login_error_msg = __( 'Error in password reset', 'racketmanager' );

	foreach ( $vars['error_codes'] as $i => $error_code ) {
		if ( 'password_reset_empty' === $error_code ) {
			$password_error   = true;
			$password_message = __( 'New password must be entered', 'racketmanager' );
		}
		if ( 'password_reset_mismatch' === $error_code ) {
			$password_error   = true;
			$password_2_error = true;
			$password_message = __( 'Passwords do not match', 'racketmanager' );
		}
		if ( 'form_has_timedout' === $error_code ) {
			$login_error_msg .= '<br />' . $vars['errors'][ $i ];
		}
	}
}
?>
<div class="row justify-content-center">
	<div id="tabs-login" class="col-12">
		<h1><?php esc_html_e( 'Reset Password', 'racketmanager' ); ?></h1>
		<form name="reset-pass-form" id="reset-pass-form" action="<?php echo esc_url( site_url( 'wp-login.php?action=resetpass' ) ); ?>" method="post" autocomplete="off">
			<?php wp_nonce_field( 'racketmanager_reset-password', 'racketmanager_nonce' ); ?>
			<input type="hidden" id="user_login" name="rp_login" value="<?php echo esc_attr( $vars['login'] ); ?>" />
			<input type="hidden" name="rp_key" value="<?php echo esc_attr( $vars['key'] ); ?>" />
			<?php
			if ( $error_found ) {
				?>
				<div class="alert_rm mt-3 alert--danger" id="loginAlert">
					<div class="alert__body">
						<div class="alert__body-inner" id="loginAlertResponse">
							<?php echo wp_kses( $login_error_msg, array( 'br' => array() ) ); ?>
						</div>
					</div>
				</div>
				<?php
			}
			?>
			<div class="mb-3"><?php echo esc_html( wp_get_password_hint() ); ?></div>
			<div class="form-floating mb-3">
				<input class="form-control password
				<?php
				if ( $password_error ) {
					echo ' is-invalid';
				}
				?>
				" type="password" placeholder="<?php esc_html_e( 'New password', 'racketmanager' ); ?>" name="password" id="password" size="20" value="" autocomplete="off" aria-describedby="passwordFeedback" />
				<i class="passwordShow racketmanager-svg-icon">
					<?php racketmanager_the_svg( 'icon-eye' ); ?>
				</i>
				<label class="" for="password"><?php esc_html_e( 'New password', 'racketmanager' ); ?></label>
				<div id="passwordFeedback" class="
					<?php
					if ( $password_error ) {
						echo 'invalid-feedback';
					}
					?>
					">
					<?php
					if ( $password_error ) {
						echo esc_html( $password_message );
					}
					?>
				</div>
			</div>
			<div class="form-floating mb-3">
				<input class="form-control password
				<?php
				if ( $password_2_error ) {
					echo ' is-invalid';
				}
				?>
				" type="password" placeholder="<?php esc_html_e( 'Repeat new password', 'racketmanager' ); ?>" name="rePassword" id="rePassword" size="20" value="" autocomplete="off"  aria-describedby="password2Feedback"/>
				<i class="passwordShow racketmanager-svg-icon">
					<?php racketmanager_the_svg( 'icon-eye' ); ?>
				</i>
				<label class="" for="rePassword"><?php esc_html_e( 'Repeat new password', 'racketmanager' ); ?></label>
				<div id="password2Feedback" class="
					<?php
					if ( $password_2_error ) {
						echo 'invalid-feedback';
					}
					?>
					">
				</div>
			</div>
			<div class="reset-pass-submit">
				<button id="resetpassButton" class="btn btn-primary"><?php esc_html_e( 'Reset Password', 'racketmanager' ); ?></button>
			</div>
			<div class="form-group mt-3">
				<span id="password-strength"></span>
			</div>
		</form>
	</div>
</div>
