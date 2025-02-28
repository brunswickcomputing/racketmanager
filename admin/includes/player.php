<?php
/**
 * RacketManager Admin player page
 *
 * @author Paul Moffat
 * @package Racketmanager_admin
 */

namespace Racketmanager;

$is_invalid = false;
?>
<form action="" method="post">
	<?php wp_nonce_field( 'racketmanager_manage-player', 'racketmanager_nonce' ); ?>
	<div class="form-control mb-3">
		<legend><?php esc_html_e( 'Personal details', 'racketmanager' ); ?></legend>
		<div class="row gx-3">
			<div class="col-md-6">
				<div class="form-floating mb-3">
					<?php
					if ( isset( $form_valid ) && ! $form_valid && is_numeric( array_search( 'firstname', $error_fields, true ) ) ) {
						$is_invalid = true;
						$msg_id     = array_search( 'firstname', $error_fields, true );
						$msg        = isset( $error_messages[ $msg_id ] ) ? $error_messages[ $msg_id ] : null;
					}
					?>
					<input class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" placeholder="<?php esc_html_e( 'Enter first name', 'racketmanager' ); ?>" type="text" name="firstname" id="firstname" <?php echo isset( $player->firstname ) ? ' value="' . esc_html( $player->firstname ) . '"' : null; ?>/>
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
			</div>
			<div class="col-md-6">
				<div class="form-floating mb-3">
					<?php
					if ( isset( $form_valid ) && ! $form_valid && is_numeric( array_search( 'surname', $error_fields, true ) ) ) {
						$is_invalid = true;
						$msg_id     = array_search( 'surname', $error_fields, true );
						$msg        = isset( $error_messages[ $msg_id ] ) ? $error_messages[ $msg_id ] : null;
					}
					?>
					<input class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" placeholder="<?php esc_html_e( 'Enter surname', 'racketmanager' ); ?>" type="text" name="surname" id="surname" <?php echo isset( $player->surname ) ? ' value="' . esc_html( $player->surname ) . '"' : null; ?>/>
					<label for="surname"><?php esc_html_e( 'Surname', 'racketmanager' ); ?></label>
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
		<div class="row g-3">
			<div class="col-md-6 mb-3">
				<div class="form-label"><?php esc_html_e( 'Gender', 'racketmanager' ); ?></div>
				<div class="form-check form-check-inline">
					<?php
					if ( isset( $form_valid ) && ! $form_valid && is_numeric( array_search( 'gender', $error_fields, true ) ) ) {
						$is_invalid = true;
						$msg_id     = array_search( 'gender', $error_fields, true );
						$msg        = isset( $error_messages[ $msg_id ] ) ? $error_messages[ $msg_id ] : null;
					}
					?>
					<input class="form-check-input <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" type="radio" name="gender" id="gender_male" value="M" <?php echo isset( $player->gender ) && 'M' === $player->gender ? ' ' . esc_html( RACKETMANAGER_CHECKED ) : null; ?>/>
					<label for="gender_male" class="form-check-label"><?php esc_html_e( 'Male', 'racketmanager' ); ?></label>
				</div>
				<div class="form-check form-check-inline">
					<input class="form-check-input <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" type="radio" name="gender" id="gender_female" value="F" <?php echo isset( $player->gender ) && 'F' === $player->gender ? ' ' . esc_html( RACKETMANAGER_CHECKED ) : null; ?>/>
					<label for="gender_female" class="form-check-label"><?php esc_html_e( 'Female', 'racketmanager' ); ?></label>
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
		<div class="row gx-3">
			<div class="col-md-6">
				<div class="form-floating mb-3">
					<?php
					if ( isset( $form_valid ) && ! $form_valid && is_numeric( array_search( 'btm', $error_fields, true ) ) ) {
						$is_invalid = true;
						$msg_id     = array_search( 'btm', $error_fields, true );
						$msg        = isset( $error_messages[ $msg_id ] ) ? $error_messages[ $msg_id ] : null;
					}
					?>
					<input type="number" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" placeholder="<?php esc_html_e( 'Enter LTA Tennis Number', 'racketmanager' ); ?>" name="btm" id="btm" <?php echo isset( $player->btm ) ? ' value = "' . esc_html( $player->btm ) . '"' : null; ?>/>
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
			</div>
			<div class="col-md-6">
				<div class="form-floating mb-3">
					<?php
					if ( isset( $form_valid ) && ! $form_valid && is_numeric( array_search( 'year_of_birth', $error_fields, true ) ) ) {
						$is_invalid = true;
						$msg_id     = array_search( 'year_of_birth', $error_fields, true );
						$msg        = isset( $error_messages[ $msg_id ] ) ? $error_messages[ $msg_id ] : null;
					}
					?>
					<select class="form-select <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="year_of_birth" id="year_of_birth" placeholder="<?php esc_html_e( 'Enter year of birth', 'racketmanager' ); ?>">
						<option value=""><?php esc_html_e( 'Enter year of birth', 'racketmanager' ); ?></option>
						<?php
						$current_year = gmdate( 'Y' );
						$start_year   = $current_year - 5;
						$end_year     = $start_year - 100;
						for ( $i = $start_year; $i > $end_year; $i-- ) {
							?>
							<option value="<?php echo esc_attr( $i ); ?>" <?php isset( $player->year_of_birth ) ? selected( $i, $player->year_of_birth ) : null; ?>><?php echo esc_html( $i ); ?></option>
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
	</div>
	<?php
	if ( isset( $player ) ) {
		$match_types = Racketmanager_Util::get_match_types();
		?>
		<div class="form-control mb-3">
			<legend><?php esc_html_e( 'Ratings', 'racketmanager' ); ?></legend>
			<div class="row gx-3">
				<div class="col-md-6">
					<div class="form-floating mb-3">
						<legend class="fs-6"><?php esc_html_e( 'L&W', 'racketmanager' ); ?></legend>
						<?php
						$rating         = isset( $player->rating ) ? $player->rating : null;
						$rating_display = '';
						foreach ( $match_types as $match_type => $description ) {
							if ( ! empty( $rating_display ) ) {
								$rating_display .= ' - ';
							}
							$rating_display .= '[' . $rating[ $match_type ] . ']';
						}
						echo ' ' . esc_html( $rating_display );
						?>
					</div>
				</div>
				<div class="col-md-6">
					<div class="form-floating mb-3">
						<legend class="fs-6"><?php esc_html_e( 'WTN', 'racketmanager' ); ?></legend>
						<?php
						$wtn            = $player->wtn;
						$wtn_display = '';
						foreach ( $match_types as $match_type => $description ) {
							if ( ! empty( $wtn_display ) ) {
								$wtn_display .= ' - ';
							}
							$wtn_display .= '[' . $wtn[ $match_type ] . ']';
						}
						echo ' ' . esc_html( $wtn_display );
						?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
	?>
	<div class="form-control mb-3">
		<legend><?php esc_html_e( 'Contact details', 'racketmanager' ); ?></legend>
		<div class="row gx-3">
			<div class="col-md-6">
				<div class="form-floating mb-3">
					<?php
					if ( isset( $form_valid ) && ! $form_valid && is_numeric( array_search( 'email', $error_fields, true ) ) ) {
						$is_invalid = true;
						$msg_id     = array_search( 'email', $error_fields, true );
						$msg        = isset( $error_messages[ $msg_id ] ) ? $error_messages[ $msg_id ] : null;
					}
					?>
					<input type="email" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" placeholder="<?php esc_html_e( 'Enter email address', 'racketmanager' ); ?>" name="email" id="email" <?php echo isset( $player->email ) ? ' value = "' . esc_html( $player->email ) . '" ' : null; ?>/>
					<label for="email"><?php esc_html_e( 'Email address', 'racketmanager' ); ?></label>
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
			<div class="col-md-6">
				<div class="form-floating mb-3">
					<?php
					if ( isset( $form_valid ) && ! $form_valid && is_numeric( array_search( 'contactno', $error_fields, true ) ) ) {
						$is_invalid = true;
						$msg_id     = array_search( 'contactno', $error_fields, true );
						$msg        = isset( $error_messages[ $msg_id ] ) ? $error_messages[ $msg_id ] : null;
					}
					?>
					<input type="tel" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" placeholder="<?php esc_html_e( 'Enter telephone number', 'racketmanager' ); ?>" name="contactno" id="contactno" <?php echo isset( $player->contactno ) ? ' value = "' . esc_html( $player->contactno ) . '" ' : null; ?>/>
					<label for="contactno"><?php esc_html_e( 'Telephone number', 'racketmanager' ); ?></label>
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
	</div>
	<?php
	if ( isset( $player_id ) ) {
		?>
		<div class="form-control mb-3">
			<legend><?php esc_html_e( 'System details', 'racketmanager' ); ?></legend>
			<div class="row gx-3">
				<div class="col-md-6">
					<div class="form-check">
						<?php
						if ( isset( $form_valid ) && ! $form_valid && is_numeric( array_search( 'locked', $error_fields, true ) ) ) {
							$is_invalid = true;
							$msg_id     = array_search( 'locked', $error_fields, true );
							$msg        = isset( $error_messages[ $msg_id ] ) ? $error_messages[ $msg_id ] : null;
						}
						?>
						<input class="form-check-input <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" type="checkbox" name="locked" id="locked" value="Locked" <?php echo isset( $player->locked ) && $player->locked ? ' ' . esc_html( RACKETMANAGER_CHECKED ) : null; ?>>
						<label for="locked" class="form-check-label"><?php esc_html_e( 'Locked', 'racketmanager' ); ?></label>
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
		</div>
		<input type="hidden" name="page_referrer" value="<?php echo esc_attr( $page_referrer ); ?>" />
		<?php
	}
	if ( isset( $club_id ) ) {
		?>
		<input type="hidden" name="club_Id" id="club_Id" value="<?php echo esc_html( $club_id ); ?>" />
		<?php
	}
	if ( isset( $player_id ) ) {
		?>
		<input type="hidden" name="playerId" id="playerId" value="<?php echo esc_html( $player->id ); ?>" />
		<input type="submit" name="updatePlayer" value="<?php esc_html_e( 'Update Player', 'racketmanager' ); ?>" class="btn btn-primary" />
		<input type="submit" name="setWTN" value="<?php esc_html_e( 'Set WTN', 'racketmanager' ); ?>" class="btn btn-secondary" />
		<?php
	} else {
		?>
		<input type="submit" name="addPlayer" value="<?php esc_html_e( 'Add Player', 'racketmanager' ); ?>" class="btn btn-primary" />
		<?php
	}
	?>
</form>
