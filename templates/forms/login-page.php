<?php
/**
 * Login page template
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

?>
<form method="post" action="<?php echo esc_url( wp_login_url() ); ?>">
	<?php wp_nonce_field( 'racketmanager_login', 'racketmanager_login_nonce' ); ?>
	<div class="mt-3">
		<div class="form-floating mb-3">
			<input type="text" class="form-control
				<?php
				if ( $username_error ) {
					echo ' is-invalid';
				}
				?>
				" placeholder="<?php esc_html_e( 'Email', 'racketmanager' ); ?>" name="log" id="user_login" aria-describedby="usernameFeedback" >
			<label class="" for="user_login"><?php esc_html_e( 'Email', 'racketmanager' ); ?></label>
			<div id="usernameFeedback" class="
				<?php
				if ( $password_error ) {
					echo 'invalid-feedback';
				}
				?>
				">
				<?php
				if ( $username_error ) {
					echo esc_html( $username_message );
				}
				?>
			</div>
		</div>
		<div class="form-floating mb-3">
			<input type="password" class="form-control password
				<?php
				if ( $password_error ) {
					echo ' is-invalid';
				}
				?>
				" name="pwd" placeholder="<?php esc_html_e( 'Password', 'racketmanager' ); ?>" id="user_pass" aria-describedby="passwordFeedback" />
			<label class="" for="user_pass"><?php esc_html_e( 'Password', 'racketmanager' ); ?></label>
			<i class="passwordShow racketmanager-svg-icon">
				<?php racketmanager_the_svg( 'icon-eye' ); ?>
			</i>
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
	</div>
	<div class="row">
		<div class="col-6">
			<a class="forgot-password" href="<?php echo esc_url( wp_lostpassword_url() ); ?>">
				<?php esc_html_e( 'Forgot your password?', 'racketmanager' ); ?>
			</a>
		</div>
		<div class="col-6">
			<button type="submit" class="float-end">
				<?php esc_html_e( 'Login', 'racketmanager' ); ?>
			</button>
			<?php if ( isset( $vars['redirect'] ) ) { ?>
				<input type="hidden" name="redirect_to" value="<?php echo esc_url( $vars['redirect'] ); ?>" />
			<?php } ?>
		</div>
	</div>
</form>
