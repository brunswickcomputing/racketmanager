<?php
/**
 * Register Page
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

?>
<div>
	<form id="signupForm" method="post" action="<?php echo esc_url( wp_registration_url() ); ?>">
		<?php wp_nonce_field( 'racketmanager_register', 'racketmanager_register_nonce' ); ?>
		<script>
			function submitForm() {
				var signupForm = document.getElementById("signupForm");
				signupForm.submit();
			}
		</script>
		<div class="mt-3">
			<div class="form-floating mb-3">
				<input class="form-control <?php echo empty( $email_error ) ? null : 'is-invalid'; ?>" type="email" placeholder="<?php esc_html_e( 'Email', 'racketmanager' ); ?>" name="email" id="email" aria-describedby="emailFeedback" autocomplete="true" />
				<label class="" for="email">
					<?php esc_html_e( 'Email', 'racketmanager' ); ?>
				</label>
				<div id="emailFeedback" class="invalid-feedback">
					<?php
					if ( $email_error ) {
						echo esc_html( $email_message );
					}
					?>
				</div>
			</div>
			<div class="form-floating mb-3">
				<input class="form-control <?php echo empty( $firstname_error ) ? null : 'is-invalid'; ?>" type="text" placeholder="<?php esc_html_e( 'First name', 'racketmanager' ); ?>" name="first_name" id="first_name" aria-describedby="firstnameFeedback" autocomplete="true" />
				<label class="" for="first_name">
					<?php esc_html_e( 'First name', 'racketmanager' ); ?>
				</label>
				<div id="first_nameFeedback" class="invalid-feedback">
					<?php
					if ( $firstname_error ) {
						echo esc_html( $firstname_message );
					}
					?>
				</div>
			</div>
			<div class="form-floating mb-3">
				<input class="form-control <?php echo empty( $surname_error ) ? null : 'is-invalid'; ?>" type="text" placeholder="<?php esc_html_e( 'Last name', 'racketmanager' ); ?>" name="last_name" id="last_name" aria-describedby="surnameFeedback" autocomplete="family-name" />
				<label class="" for="last_name">
					<?php esc_html_e( 'Last name', 'racketmanager' ); ?>
				</label>
				<div id="surnameFeedback" class="invalid-feedback">
					<?php
					if ( $surname_error ) {
						echo esc_html( $surname_message );
					}
					?>
				</div>
			</div>
		</div>
		<div class="row">
			<?php
			if ( $vars['recaptcha_site_key'] ) {
				?>
				<div class="recaptcha-container col-6">
					<div class="g-recaptcha  <?php echo empty( $recaptcha_error ) ? null : 'is-invalid'; ?>" data-sitekey="<?php echo esc_html( $vars['recaptcha_site_key'] ); ?>" data-size="invisible" aria-describedby="recaptchaFeedback" data-badge="inline" data-bind="recaptchaSubmit"	data-callback="submitForm">
					</div>
					<div id="recaptchaFeedback" class="invalid-feedback">
						<?php
						if ( $recaptcha_error ) {
							echo esc_html( $recaptcha_message );
						}
						?>
					</div>
				</div>
				<?php
			}
			?>
			<div class="register-submit col-6">
				<button type="submit" name="btnSubmit" class="register-button float-end" id="recaptchaSubmit">
					<?php esc_html_e( 'Register', 'racketmanager' ); ?>
				</button>
			</div>
		</div>
	</form>
</div>
<div class="form-row">
	<?php esc_html_e( 'Note: Your password will be generated automatically and sent to your email address.', 'racketmanager' ); ?>
</div>
