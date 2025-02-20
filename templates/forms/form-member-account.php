<?php
/**
 * Member account form.
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

$is_invalid   = false;
?>
<div class="container">
	<div class="module module--card">
		<div class="module__banner">
			<h2 class="module__title"><?php esc_html_e( 'Member Account', 'racketmanager' ); ?></h2>
		</div>
		<div class="module__content">
			<div class="module-container">
				<?php
				if ( ! empty( $user->message ) ) {
					if ( empty( $user->update_result ) ) {
						$alert_class = 'info';
					} else {
						$alert_class = $user->update_result;
					}
					?>
					<div class="alert_rm mb-3 alert--<?php echo esc_attr( $alert_class ); ?>" id="userAlert">
						<div class="alert__body">
							<div class="alert__body-inner" id="userAlertResponse">
								<?php echo esc_html( $user->message ); ?>
							</div>
						</div>
					</div>
					<?php
				}
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
						<div class="row gx-3">
							<div class="form-floating col-md-6 mb-3">
								<?php
								if ( isset( $user->err_flds ) && is_numeric( array_search( 'firstname', $user->err_flds, true ) ) ) {
									$is_invalid = true;
									$msg_id     = array_search( 'firstname', $user->err_flds, true );
									$msg        = isset( $user->err_msgs[ $msg_id ] ) ? $user->err_msgs[ $msg_id ] : null;
								}
								?>
								<input type="text" autocomplete='given-name' placeholder="<?php esc_html_e( 'First Name', 'racketmanager' ); ?>" name="firstname" id="firstname" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" value="<?php echo esc_html( $user->firstname ); ?>" />
								<label for="firstname"><?php esc_html_e( 'First Name', 'racketmanager' ); ?></label>
								<?php
								if ( $is_invalid ) {
									?>
									<div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
									<?php
									$is_invalid = false;
									$msg        = null;
								}
								?>
							</div>
							<div class="form-floating col-md-6 mb-3">
								<?php
								if ( isset( $user->err_flds ) && is_numeric( array_search( 'lastname', $user->err_flds, true ) ) ) {
									$is_invalid = true;
									$msg_id     = array_search( 'lastname', $user->err_flds, true );
									$msg        = isset( $user->err_msgs[ $msg_id ] ) ? $user->err_msgs[ $msg_id ] : null;
								}
								?>
								<input type="text" autocomplete='family-name' placeholder="<?php esc_html_e( 'Last Name', 'racketmanager' ); ?>" name="lastname" id="lastname" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" value="<?php echo esc_html( $user->surname ); ?>" />
								<label for="lastname"><?php esc_html_e( 'Last Name', 'racketmanager' ); ?></label>
								<?php
								if ( $is_invalid ) {
									?>
									<div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
									<?php
									$is_invalid = false;
									$msg        = null;
								}
								?>
							</div>
						</div>
						<fieldset class="form-floating mb-3">
							<?php
							if ( isset( $user->err_flds ) && is_numeric( array_search( 'gender', $user->err_flds, true ) ) ) {
								$is_invalid = true;
								$msg_id     = array_search( 'gender', $user->err_flds, true );
								$msg        = isset( $user->err_msgs[ $msg_id ] ) ? $user->err_msgs[ $msg_id ] : null;
							}
							?>
							<legend class="<?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>"><?php esc_html_e( 'Gender', 'racketmanager' ); ?></legend>
							<div class="form-check form-check-inline">
								<input type="radio" class="form-check-input <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" id="genderMale" name="gender" value="M" <?php checked( 'M', $user->gender ); ?> />
								<label for="genderMale" class="form-check-label"><?php esc_html_e( 'Male', 'racketmanager' ); ?></label>
							</div>
							<div class="form-check form-check-inline">
								<input type="radio" class="form-check-input <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" id="genderFemale" name="gender" value="F" <?php checked( 'F', $user->gender ); ?> />
								<label for="genderFemale" class="form-check-label"><?php esc_html_e( 'Female', 'racketmanager' ); ?></label>
							</div>
							<?php
							if ( $is_invalid ) {
								?>
								<div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
								<?php
								$is_invalid = false;
								$msg        = null;
							}
							?>
						</fieldset>
						<div class="row gx-3">
							<div class="form-floating col-md-6 mb-3">
								<?php
								if ( isset( $user->err_flds ) && is_numeric( array_search( 'btm', $user->err_flds, true ) ) ) {
									$is_invalid = true;
									$msg_id     = array_search( 'btm', $user->err_flds, true );
									$msg        = isset( $user->err_msgs[ $msg_id ] ) ? $user->err_msgs[ $msg_id ] : null;
								}
								?>
								<input type="tel" placeholder="<?php esc_html_e( 'LTA Tennis Number', 'racketmanager' ); ?>" name="btm" id="btm" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" value="<?php echo esc_html( $user->btm ); ?>" />
								<label for="btm"><?php esc_html_e( 'LTA Tennis Number', 'racketmanager' ); ?></label>
								<?php
								if ( $is_invalid ) {
									?>
									<div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
									<?php
									$is_invalid = false;
									$msg        = null;
								}
								?>
							</div>
							<div class="form-floating col-md-6 mb-3">
								<?php
								if ( isset( $user->err_flds ) && is_numeric( array_search( 'year_of_birth', $user->err_flds, true ) ) ) {
									$is_invalid = true;
									$msg_id     = array_search( 'year_of_birth', $user->err_flds, true );
									$msg        = isset( $user->err_msgs[ $msg_id ] ) ? $user->err_msgs[ $msg_id ] : null;
								}
								?>
								<select class="form-select <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="year_of_birth" id="year_of_birth">
									<option value=""><?php esc_html_e( 'Enter year of birth', 'racketmanager' ); ?></option>
									<?php
									$current_year = gmdate( 'Y' );
									$start_year   = $current_year - 5;
									$end_year     = $start_year - 100;
									for ( $i = $start_year; $i > $end_year; $i-- ) {
										?>
										<option value="<?php echo esc_attr( $i ); ?>" <?php selected( $i, $user->year_of_birth ); ?>><?php echo esc_html( $i ); ?></option>
										<?php
									}
									?>
								</select>
								<label for="year_of_birth"><?php esc_html_e( 'Year of birth', 'racketmanager' ); ?></label>
								<?php
								if ( $is_invalid ) {
									?>
									<div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
									<?php
									$is_invalid = false;
									$msg        = null;
								}
								?>
							</div>
						</div>
					</div>
					<div class="form-control mb-3">
						<legend><?php esc_html_e( 'Contact details', 'racketmanager' ); ?></legend>
						<div class="row gx-3">
							<div class="form-floating col-md-6 mb-3">
								<?php
								if ( isset( $user->err_flds ) && is_numeric( array_search( 'username', $user->err_flds, true ) ) ) {
									$is_invalid = true;
									$msg_id     = array_search( 'username', $user->err_flds, true );
									$msg        = isset( $user->err_msgs[ $msg_id ] ) ? $user->err_msgs[ $msg_id ] : null;
								}
								?>
								<input type="email" placeholder="<?php esc_html_e( 'Email Address', 'racketmanager' ); ?>" name="username" id="username" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" value="<?php echo esc_html( $user->email ); ?>" />
								<label for="username"><?php esc_html_e( 'Username', 'racketmanager' ); ?></label>
								<?php
								if ( $is_invalid ) {
									?>
									<div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
									<?php
									$is_invalid = false;
									$msg        = null;
								}
								?>
							</div>
							<div class="form-floating col-md-6 mb-3">
								<?php
								if ( isset( $user->err_flds ) && is_numeric( array_search( 'contactno', $user->err_flds, true ) ) ) {
									$is_invalid = true;
									$msg_id     = array_search( 'contactno', $user->err_flds, true );
									$msg        = isset( $user->err_msgs[ $msg_id ] ) ? $user->err_msgs[ $msg_id ] : null;
								}
								?>
								<input type="tel" autocomplete='tel' placeholder="<?php esc_html_e( 'Telephone Number', 'racketmanager' ); ?>" name="contactno" id="contactno" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" value="<?php echo esc_html( $user->contactno ); ?>" />
								<label for="contactno"><?php esc_html_e( 'Telephone Number', 'racketmanager' ); ?></label>
								<?php
								if ( $is_invalid ) {
									?>
									<div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
									<?php
									$is_invalid = false;
									$msg        = null;
								}
								?>
							</div>
						</div>
					</div>
					<?php
					if ( ! empty( $opt_in_choices ) ) {
						?>
						<div class="form-control mb-3">
							<legend><?php esc_html_e( 'Contact preferences', 'racketmanager' ); ?></legend>
							<div class="row gx-3">
								<div class="form-floating col-md-6 mb-3">
									<?php
									foreach ( $opt_in_choices as $opt_in_choice => $opt_in_desc ) {
										?>
										<div class="form-check">
											<input type="checkbox" class="form-check-input" id="opt_in_<?php echo esc_attr( $opt_in_choice ); ?>" name="opt_in[<?php echo esc_attr( $opt_in_choice ); ?>]" value="1" <?php checked( true, in_array( strval( $opt_in_choice ), $user->opt_ins, true ) ); ?> />
											<label for="opt_in_<?php echo esc_attr( $opt_in_choice ); ?>" class="form-check-label"><?php echo esc_html( $opt_in_desc ); ?></label>
										</div>
										<?php
									}
									?>
								</div>
							</div>
						</div>
						<?php
					}
					?>
					<div class="form-control mb-3">
						<legend><?php esc_html_e( 'Account', 'racketmanager' ); ?></legend>
						<p class="info-msg"><?php esc_html_e( 'When both password fields are left empty, your password will not change', 'racketmanager' ); ?></p>
						<div class="row gx-3">
							<div class="form-floating col-md-6 mb-3">
								<?php
								if ( isset( $user->err_flds ) && is_numeric( array_search( 'password', $user->err_flds, true ) ) ) {
									$is_invalid = true;
									$msg_id     = array_search( 'password', $user->err_flds, true );
									$msg        = isset( $user->err_msgs[ $msg_id ] ) ? $user->err_msgs[ $msg_id ] : null;
								}
								?>
								<input type="password" placeholder="<?php esc_html_e( 'Password', 'racketmanager' ); ?>" name="password" id="password" class="form-control password <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" size="20" value="" autocomplete="off" />
								<i class="passwordShow racketmanager-svg-icon">
								<?php racketmanager_the_svg( 'icon-eye' ); ?>
								</i>
								<label for="password"><?php esc_html_e( 'Password', 'racketmanager' ); ?></label>
								<?php
								if ( $is_invalid ) {
									?>
									<div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
									<?php
								}
								?>
							</div>
							<div class="form-floating col-md-6 mb-3">
								<input type="password" placeholder="<?php esc_html_e( 'Re-enter password', 'racketmanager' ); ?>" name="rePassword" id="rePassword" class="form-control password <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" value="" autocomplete="off" />
								<i class="passwordShow racketmanager-svg-icon">
								<?php racketmanager_the_svg( 'icon-eye' ); ?>
								</i>
								<label for="rePassword"><?php esc_html_e( 'Confirm password', 'racketmanager' ); ?></label>
								<?php
								if ( $is_invalid ) {
									$is_invalid = false;
									$msg        = null;
								}
								?>
							</div>
							<div class="form-group">
								<span id="password-strength" style="display: none";></span>
							</div>
						</div>
					</div>
					<div class="">
						<button name="submit" id="memberaccount-button" class="btn btn-primary"><?php esc_html_e( 'Update Details', 'racketmanager' ); ?></button>
						<input name="action" type="hidden" id="action" value="update-user" />
					</div>
				</form>
			</div>
		</div>
	</div>
	</div>
</div>
