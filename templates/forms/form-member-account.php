<?php
/**
 * Member account form.
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

?>
<div class="row justify-content-center">
	<div class="col-12 col-md-9">
		<h1><?php esc_html_e( 'Member Account', 'racketmanager' ); ?></h1>

		<?php
		if ( isset( $user_data['message'] ) ) {
			if ( isset( $user_data['error'] ) ) {
				$class = 'login-error';
			} else {
				$class = 'login-info';
			}
			?>
			<div id="profile-message" class="<?php echo esc_html( $class ); ?>"><?php echo esc_html( $user_data['message'] ); ?></div>
		<?php } ?>

		<form name="memberaccountform" id="memberaccountform" action="<?php echo esc_url( site_url( 'member-account' ) ); ?>" method="post" autocomplete="off">
			<?php wp_nonce_field( 'member_account_nonce', 'member_account_nonce_field' ); ?>

			<div>
			<h2><?php esc_html_e( 'Personal Information', 'racketmanager' ); ?></h2>
			<div class="form-floating mb-3
			<?php
			if ( isset( $user_data['user_name_error'] ) ) {
				echo ' is-invalid';
			}
			?>
			">
				<input type="email" required="required" placeholder="<?php esc_html_e( 'Email Address', 'racketmanager' ); ?>" name="username" id="username" class="form-control" value="<?php echo esc_html( $user_data['user_name'] ); ?>"
					<?php
					if ( isset( $user_data['user_name'] ) && $user_data['user_name'] ) {
						echo ' readonly';
					}
					?>
				/>
				<label for="username"><?php esc_html_e( 'Username', 'racketmanager' ); ?></label>
				<?php
				if ( isset( $user_data['user_name_error'] ) ) {
					echo '<span class="form-error">' . esc_html( $user_data['user_name_error'] ) . '</span>';
				}
				?>
			</div>
			<div class="form-floating mb-3
			<?php
			if ( isset( $user_data['first_name_error'] ) ) {
				echo ' is-invalid';
			}
			?>
			">
				<input type="text" autocomplete='given-name' placeholder="<?php esc_html_e( 'First Name', 'racketmanager' ); ?>" name="firstname" id="firstname" class="form-control" value="<?php echo esc_html( $user_data['first_name'] ); ?>" />
				<label for="firstname"><?php esc_html_e( 'First Name', 'racketmanager' ); ?></label>
				<?php
				if ( isset( $user_data['first_name_error'] ) ) {
					echo '<span class="form-error">' . esc_html( $user_data['first_name_error'] ) . '</span>';
				}
				?>
			</div>
			<div class="form-floating mb-3
			<?php
			if ( isset( $user_data['last_name_error'] ) ) {
				echo ' is-invalid';
			}
			?>
			">
				<input type="text" autocomplete='family-name' placeholder="<?php esc_html_e( 'Last Name', 'racketmanager' ); ?>" name="lastname" id="lastname" class="form-control" value="<?php echo esc_html( $user_data['last_name'] ); ?>" />
				<label for="lastname"><?php esc_html_e( 'Last Name', 'racketmanager' ); ?></label>
				<?php
				if ( isset( $user_data['last_name_error'] ) ) {
					echo '<span class="form-error">' . esc_html( $user_data['last_name_error'] ) . '</span>';
				}
				?>
			</div>
			<div class="form-floating mb-3
			<?php
			if ( isset( $user_data['contactno_error'] ) ) {
				echo ' is-invalid';
			}
			?>
			">
				<input type="tel" autocomplete='tel' placeholder="<?php esc_html_e( 'Telephone Number', 'racketmanager' ); ?>" name="contactno" id="contactno" class="form-control" value="<?php echo esc_html( $user_data['contactno'] ); ?>" />
				<label for="contactno"><?php esc_html_e( 'Telephone Number', 'racketmanager' ); ?></label>
				<?php
				if ( isset( $user_data['contactno_error'] ) ) {
					echo '<span class="form-error">' . esc_html( $user_data['contactno_error'] ) . '</span>';
				}
				?>
			</div>

			<div class="form-group mb-3
			<?php
			if ( isset( $user_data['gender_error'] ) ) {
				echo ' field_error';
			}
			?>
			">
				<label><?php esc_html_e( 'Gender', 'racketmanager' ); ?></label>
				<div class="form-check">
				<input type="radio" class="form-check-input" required="required" id="genderMale" name="gender" value="M"<?php echo ( 'M' === $user_data['gender'] ) ? 'checked' : ''; ?> />
				<label for="genderMale" class="form-check-label"><?php esc_html_e( 'Male', 'racketmanager' ); ?></label>
				</div>
				<div class="form-check">
				<input type="radio" class="form-check-input" id="genderFemale" name="gender" value="F" <?php echo ( 'F' === $user_data['gender'] ) ? 'checked' : ''; ?> />
				<label for="genderFemale" class="form-check-label"><?php esc_html_e( 'Female', 'racketmanager' ); ?></label>
				</div>
				<?php
				if ( isset( $user_data['gender_error'] ) ) {
					echo '<span class="form-error">' . esc_html( $user_data['gender_error'] ) . '</span>';
				}
				?>
			</div>
			<div class="form-floating mb-3
			<?php
			if ( isset( $user_data['btm_error'] ) ) {
				echo ' is-invalid';
			}
			?>
			">
				<input type="tel" placeholder="<?php esc_html_e( 'LTA Tennis Number', 'racketmanager' ); ?>" name="btm" id="btm" class="form-control" value="<?php echo esc_html( $user_data['btm'] ); ?>" />
				<label for="btm"><?php esc_html_e( 'LTA Tennis Number', 'racketmanager' ); ?></label>
				<?php
				if ( isset( $user_data['btm_error'] ) ) {
					echo '<span class="form-error">' . esc_html( $user_data['btm_error'] ) . '</span>';
				}
				?>
			</div>
			</div>

			<div>
			<h2><?php esc_html_e( 'Change Password', 'racketmanager' ); ?></h2>
			<p><?php esc_html_e( 'When both password fields are left empty, your password will not change', 'racketmanager' ); ?></p>
			<div class="form-floating mb-3
			<?php
			if ( isset( $user_data['password_error'] ) ) {
				echo ' is-invalid';
			}
			?>
			">
				<input type="password" placeholder="<?php esc_html_e( 'Password', 'racketmanager' ); ?>" name="password" id="password" class="form-control password" size="20" value="" autocomplete="off" />
				<i class="passwordShow racketmanager-svg-icon">
				<?php racketmanager_the_svg( 'icon-eye' ); ?>
				</i>
				<label for="password"><?php esc_html_e( 'Password', 'racketmanager' ); ?></label>
				<?php
				if ( isset( $user_data['password_error'] ) ) {
					echo '<span class="form-error">' . esc_html( $user_data['password_error'] ) . '</span>';
				}
				?>
			</div>
			<div class="form-floating mb-3
			<?php
			if ( isset( $user_data['rePassword_error'] ) ) {
				echo ' is-invalid';
			}
			?>
			">
				<input type="password" placeholder="<?php esc_html_e( 'Re-enter password', 'racketmanager' ); ?>" name="rePassword" id="rePassword" class="form-control password" size="20" value="" autocomplete="off" />
				<i class="passwordShow racketmanager-svg-icon">
				<?php racketmanager_the_svg( 'icon-eye' ); ?>
				</i>
				<label for="rePassword"><?php esc_html_e( 'Confirm password', 'racketmanager' ); ?></label>
				<?php
				if ( isset( $user_data['rePassword_error'] ) ) {
					echo '<span class="form-error">' . esc_html( $user_data['rePassword_error'] ) . '</span>';
				}
				?>
			</div>
			<div class="form-group">
				<span id="password-strength"></span>
			</div>
			</div>

			<div class="mb-3">
			<input type="submit" name="submit" id="memberaccount-button"
			class="button" value="<?php esc_html_e( 'Update Details', 'racketmanager' ); ?>" />
			<input name="action" type="hidden" id="action" value="update-user" />
			</div>
		</form>
	</div>
</div>
