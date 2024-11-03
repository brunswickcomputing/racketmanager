<?php
/**
 * Member account form.
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

?>
<div class="container">
	<div class="module module--card">
		<div class="module__banner">
			<h2 class="module__title"><?php esc_html_e( 'Member Account', 'racketmanager' ); ?></h2>
		</div>
		<div class="module__content">
			<div class="module-container">
				<?php
				if ( isset( $user_data['message'] ) ) {
					if ( isset( $user_data['error'] ) ) {
						$class = 'login-error';
					} else {
						$class = 'login-info';
					}
					?>
					<div id="profile-message" class="<?php echo esc_html( $class ); ?>"><?php echo esc_html( $user_data['message'] ); ?></div>
					<?php
				}
				?>
				<form name="memberaccountform" id="memberaccountform" action="<?php echo esc_url( site_url( 'member-account' ) ); ?>" method="post" autocomplete="off">
					<?php wp_nonce_field( 'member_account', 'racketmanager_nonce' ); ?>
					<div class="form-control mb-3">
						<legend><?php esc_html_e( 'Personal details', 'racketmanager' ); ?></legend>
						<div class="row g-3">
							<div class="form-floating col-md-6 mb-3" <?php echo ( isset( $user_data['first_name_error'] ) ) ? ' is-invalid' : ''; ?>">
								<input type="text" autocomplete='given-name' placeholder="<?php esc_html_e( 'First Name', 'racketmanager' ); ?>" name="firstname" id="firstname" class="form-control" value="<?php echo esc_html( $user_data['first_name'] ); ?>" />
								<label for="firstname"><?php esc_html_e( 'First Name', 'racketmanager' ); ?></label>
								<?php
								if ( isset( $user_data['first_name_error'] ) ) {
									echo '<span class="form-error">' . esc_html( $user_data['first_name_error'] ) . '</span>';
								}
								?>
							</div>
							<div class="form-floating col-md-6 mb-3" <?php echo ( isset( $user_data['last_name_error'] ) ) ? ' is-invalid' : ''; ?>">
								<input type="text" autocomplete='family-name' placeholder="<?php esc_html_e( 'Last Name', 'racketmanager' ); ?>" name="lastname" id="lastname" class="form-control <?php echo ( isset( $user_data['last_name_error'] ) ) ? ' is-invalid' : ''; ?>" value="<?php echo esc_html( $user_data['last_name'] ); ?>" />
								<label for="lastname"><?php esc_html_e( 'Last Name', 'racketmanager' ); ?></label>
								<?php
								if ( isset( $user_data['last_name_error'] ) ) {
									echo '<span class="form-error">' . esc_html( $user_data['last_name_error'] ) . '</span>';
								}
								?>
							</div>
						</div>
						<fieldset class="form-floating mb-3">
							<legend><?php esc_html_e( 'Gender', 'racketmanager' ); ?></legend>
							<div class="form-check form-check-inline">
								<input type="radio" class="form-check-input <?php echo ( isset( $user_data['gender_error'] ) ) ? ' is-invalid' : ''; ?>" id="genderMale" name="gender" value="M"<?php echo ( 'M' === $user_data['gender'] ) ? 'checked' : ''; ?> />
								<label for="genderMale" class="form-check-label"><?php esc_html_e( 'Male', 'racketmanager' ); ?></label>
							</div>
							<div class="form-check form-check-inline">
								<input type="radio" class="form-check-input <?php echo ( isset( $user_data['gender_error'] ) ) ? ' is-invalid' : ''; ?>" id="genderFemale" name="gender" value="F" <?php echo ( 'F' === $user_data['gender'] ) ? 'checked' : ''; ?> />
								<label for="genderFemale" class="form-check-label"><?php esc_html_e( 'Female', 'racketmanager' ); ?></label>
							</div>
							<?php
							if ( isset( $user_data['gender_error'] ) ) {
								echo '<span class="form-error">' . esc_html( $user_data['gender_error'] ) . '</span>';
							}
							?>
						</fieldset>
						<div class="row g-3">
							<div class="form-floating col-md-6 mb-3">
								<input type="tel" placeholder="<?php esc_html_e( 'LTA Tennis Number', 'racketmanager' ); ?>" name="btm" id="btm" class="form-control <?php echo ( isset( $user_data['btm_error'] ) ) ? ' is-invalid' : ''; ?>" value="<?php echo esc_html( $user_data['btm'] ); ?>" />
								<label for="btm"><?php esc_html_e( 'LTA Tennis Number', 'racketmanager' ); ?></label>
								<?php
								if ( isset( $user_data['btm_error'] ) ) {
									echo '<span class="form-error">' . esc_html( $user_data['btm_error'] ) . '</span>';
								}
								?>
							</div>
							<div class="form-floating col-md-6 mb-3">
								<select class="form-select <?php echo ( isset( $user_data['year_of_birth_error'] ) ) ? ' is-invalid' : ''; ?>" name="year_of_birth" id="year_of_birth">
									<option value=""><?php esc_html_e( 'Enter year of birth', 'racketmanager' ); ?></option>
									<?php
									$current_year = gmdate( 'Y' );
									$start_year   = $current_year - 5;
									$end_year     = $start_year - 100;
									for ( $i = $start_year; $i > $end_year; $i-- ) {
										?>
										<option value="<?php echo esc_attr( $i ); ?>" <?php selected( $i, $user_data['year_of_birth'] ); ?>><?php echo esc_html( $i ); ?></option>
										<?php
									}
									?>
								</select>
								<label for="year_of_birth"><?php esc_html_e( 'Year of birth', 'racketmanager' ); ?></label>
								<?php
								if ( isset( $user_data['year_of_birth_error'] ) ) {
									echo '<span class="form-error">' . esc_html( $user_data['year_of_birth_error'] ) . '</span>';
								}
								?>
							</div>
						</div>
					</div>
					<div class="form-control mb-3">
						<legend><?php esc_html_e( 'Contact details', 'racketmanager' ); ?></legend>
						<div class="row g-3">
							<div class="form-floating col-md-6 mb-3">
								<input type="email" required="required" placeholder="<?php esc_html_e( 'Email Address', 'racketmanager' ); ?>" name="username" id="username" class="form-control <?php echo ( isset( $user_data['user_name_error'] ) ) ? ' is-invalid' : ''; ?>" value="<?php echo esc_html( $user_data['user_name'] ); ?>" />
								<label for="username"><?php esc_html_e( 'Username', 'racketmanager' ); ?></label>
								<?php
								if ( isset( $user_data['user_name_error'] ) ) {
									echo '<span class="form-error">' . esc_html( $user_data['user_name_error'] ) . '</span>';
								}
								?>
							</div>
							<div class="form-floating col-md-6 mb-3">
								<input type="tel" autocomplete='tel' placeholder="<?php esc_html_e( 'Telephone Number', 'racketmanager' ); ?>" name="contactno" id="contactno" class="form-control <?php echo ( isset( $user_data['contactno_error'] ) ) ? ' is-invalid' : ''; ?>" value="<?php echo esc_html( $user_data['contactno'] ); ?>" />
								<label for="contactno"><?php esc_html_e( 'Telephone Number', 'racketmanager' ); ?></label>
								<?php
								if ( isset( $user_data['contactno_error'] ) ) {
									echo '<span class="form-error">' . esc_html( $user_data['contactno_error'] ) . '</span>';
								}
								?>
							</div>
						</div>
					</div>
					<div class="form-control mb-3">
						<legend><?php esc_html_e( 'Account', 'racketmanager' ); ?></legend>
						<p class="info-msg"><?php esc_html_e( 'When both password fields are left empty, your password will not change', 'racketmanager' ); ?></p>
						<div class="row g-3">
							<div class="form-floating col-md-6 mb-3">
								<input type="password" placeholder="<?php esc_html_e( 'Password', 'racketmanager' ); ?>" name="password" id="password" class="form-control password <?php echo ( isset( $user_data['password_error'] ) ) ? ' is-invalid' : ''; ?>" size="20" value="" autocomplete="off" />
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
							<div class="form-floating col-md-6 mb-3">
								<input type="password" placeholder="<?php esc_html_e( 'Re-enter password', 'racketmanager' ); ?>" name="rePassword" id="rePassword" class="form-control password <?php echo ( isset( $user_data['rePassword_error'] ) ) ? ' is-invalid' : ''; ?>" size="20" value="" autocomplete="off" />
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
								<span id="password-strength" style="display: none";></span>
							</div>
						</div>
					</div>
					<div class="mb-3">
						<button name="submit" id="memberaccount-button" class="btn btn-primary"><?php esc_html_e( 'Update Details', 'racketmanager' ); ?></button>
						<input name="action" type="hidden" id="action" value="update-user" />
					</div>
				</form>
			</div>
		</div>
	</div>
	</div>
</div>
