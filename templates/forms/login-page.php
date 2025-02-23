<?php
/**
 * Login page template
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

?>
<div class="alert_rm mt-3" id="loginAlert" style="display:none;">
	<div class="alert__body">
		<div class="alert__body-inner" id="loginAlertResponse">
		</div>
	</div>
</div>
<form method="post" action="<?php echo esc_url( wp_login_url() ); ?>">
	<?php wp_nonce_field( 'racketmanager_login', 'racketmanager_login_nonce' ); ?>
	<div class="mt-3">
		<div class="form-floating mb-3">
			<input type="text" class="form-control <?php echo empty( $username_error ) ? null : 'is-invalid'; ?>" placeholder="<?php esc_html_e( 'Email', 'racketmanager' ); ?>" name="log" id="user_login" aria-describedby="usernameFeedback" >
			<label class="" for="user_login"><?php esc_html_e( 'Email', 'racketmanager' ); ?></label>
			<div id="user_loginFeedback" class="invalid-feedback">
				<?php
				if ( $username_error ) {
					echo esc_html( $username_message );
				}
				?>
			</div>
		</div>
		<div class="form-floating mb-3">
			<input type="password" class="form-control password <?php echo empty( $password_error ) ? null : 'is-invalid'; ?>" name="pwd" placeholder="<?php esc_html_e( 'Password', 'racketmanager' ); ?>" id="user_pass" aria-describedby="passwordFeedback" />
			<label class="" for="user_pass"><?php esc_html_e( 'Password', 'racketmanager' ); ?></label>
			<i class="passwordShow racketmanager-svg-icon">
				<?php racketmanager_the_svg( 'icon-eye' ); ?>
			</i>
			<div id="user_passFeedback" class="invalid-feedback">
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
			<a class="forgot-password" href="<?php echo esc_url( wp_lostpassword_url() ); ?>" data-bs-toggle="modal" data-bs-target="#resetPasswordModal">
				<?php esc_html_e( 'Forgot your password?', 'racketmanager' ); ?>
			</a>
		</div>
		<div class="col-6">
			<button type="submit" class="float-end" //onclick="Racketmanager.login(event)">
				<?php esc_html_e( 'Login', 'racketmanager' ); ?>
			</button>
			<?php
			if ( isset( $vars['redirect'] ) ) {
				?>
				<input type="hidden" id="redirect_to" name="redirect_to" value="<?php echo esc_url( $vars['redirect'] ); ?>" />
				<?php
			}
			?>
		</div>
	</div>
</form>
<!-- Modal -->
<div class="modal fade" id="resetPasswordModal" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<form id="lostpasswordform" action="<?php echo esc_url( wp_lostpassword_url() ); ?>" method="post">
				<?php wp_nonce_field( 'reset_password', 'racketmanager_nonce' ); ?>
				<div class="modal-header modal__header">
					<h2 class="modal-title"><?php esc_html_e( 'Reset password', 'racketmanager' ); ?></h2>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<div class="form-info"><?php esc_html_e( "Enter your email address and click 'Send'. You will receive a link to create a new password", 'racketmanager' ); ?></div>
					<div class="alert_rm" id="resetAlert" style="display:none;">
						<div class="alert__body">
							<div class="alert__body-inner" id="resetAlertResponse">
							</div>
						</div>
					</div>
					<div class="form-floating mb-3">
						<input type="email" class="form-control <?php echo empty( $email_error ) ? null : 'is-invalid'; ?>" placeholder="<?php esc_html_e( 'Email', 'racketmanager' ); ?>" name="user_login" id="user_login" aria-describedby="emailFeedback" />
						<label class="" for="user_login"><?php esc_html_e( 'Email', 'racketmanager' ); ?></label>
						<?php
						if ( $email_error ) {
							?>
							<div id="emailFeedback" class="<?php echo empty( $email_error ) ? null : 'invalid-feedback'; ?>">
								<?php echo esc_html( $email_message ); ?>
							</div>
							<?php
						}
						?>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-plain" data-bs-dismiss="modal"><?php esc_html_e( 'Cancel', 'racketmanager' ); ?></button>
					<button type="button" class="btn btn-primary" onclick="Racketmanager.resetPassword(this)"><?php esc_html_e( 'Send', 'racketmanager' ); ?></button>
				</div>
			</form>
		</div>
	</div>
</div>
