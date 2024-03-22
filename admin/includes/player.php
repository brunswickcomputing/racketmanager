<?php
/**
 * RacketManager Admin player page
 *
 * @author Paul Moffat
 * @package Racketmanager_admin
 */

?>
<form action="" method="post" class="form-control">
	<?php wp_nonce_field( 'racketmanager_manage-player' ); ?>
	<div class="form-floating mb-3">
		<input class="form-control
		<?php
		if ( isset( $form_valid ) && ! $form_valid && is_numeric( array_search( 'firstname', $error_fields, true ) ) ) {
			echo ' ' . esc_html( RACKETMANAGER_IS_INVALID );
		}
		?>
		" required placeholder="<?php esc_html_e( 'Enter first name', 'racketmanager' ); ?>" type="text" name="firstname" id="firstname"
<?php
if ( isset( $player->firstname ) ) {
			echo ' value="' . esc_html( $player->firstname ) . '"';
}
?>
/>
		<label for="firstname"><?php esc_html_e( 'First Name', 'racketmanager' ); ?></label>
	</div>
	<div class="form-floating mb-3">
		<input class="form-control
		<?php
		if ( isset( $form_valid ) && ! $form_valid && is_numeric( array_search( 'surName', $error_fields, true ) ) ) {
			echo ' ' . esc_html( RACKETMANAGER_IS_INVALID );
		}
		?>
		"  required placeholder="<?php esc_html_e( 'Enter surname', 'racketmanager' ); ?>" type="text" name="surname" id="surname"
<?php
if ( isset( $player->surname ) ) {
	echo ' value="' . esc_html( $player->surname ) . '"';
}
?>
/>
		<label for="surname"><?php esc_html_e( 'Surname', 'racketmanager' ); ?></label>
	</div>
	<div class="form-group">
		<label><?php esc_html_e( 'Gender', 'racketmanager' ); ?></label>
		<div class="form-check">
			<input class="form-check-input
			<?php
			if ( isset( $form_valid ) && ! $form_valid && is_numeric( array_search( 'gender', $error_fields, true ) ) ) {
				echo ' ' . esc_html( RACKETMANAGER_IS_INVALID );
			}
			?>
			" type="radio" required name="gender" id="gender_male" value="M"
<?php
if ( isset( $player->gender ) && 'M' === $player->gender ) {
	echo ' ' . esc_html( RACKETMANAGER_CHECKED );
}
?>
/>
			<label for "gender_male" class="form-check-label"><?php esc_html_e( 'Male', 'racketmanager' ); ?></label>
		</div>
		<div class="form-check">
			<input class="form-check-input
			<?php
			if ( isset( $form_valid ) && ! $form_valid && is_numeric( array_search( 'gender', $error_fields, true ) ) ) {
				echo ' ' . esc_html( RACKETMANAGER_IS_INVALID );
			}
			?>
			" type="radio" required name="gender" id="gender_female" value="F"
<?php
if ( isset( $player->gender ) && 'F' === $player->gender ) {
	echo ' ' . esc_html( RACKETMANAGER_CHECKED );
}
?>
/>
			<label for "gender_female" class="form-check-label"><?php esc_html_e( 'Female', 'racketmanager' ); ?></label>
		</div>
	</div>
	<div class="form-floating mb-3">
		<input type="number" class="form-control
		<?php
		if ( isset( $form_valid ) && ! $form_valid && is_numeric( array_search( 'btm', $error_fields, true ) ) ) {
			echo ' ' . esc_html( RACKETMANAGER_IS_INVALID );
		}
		?>
		" placeholder="<?php esc_html_e( 'Enter LTA Tennis Number', 'racketmanager' ); ?>" name="btm" id="btm"
<?php
if ( isset( $player->btm ) ) {
	echo ' value = "' . esc_html( $player->btm ) . '"';
}
?>
/>
		<label for="btm"><?php esc_html_e( 'LTA Tennis Number', 'racketmanager' ); ?></label>
	</div>
	<div class="form-floating mb-3">
		<input type="email" class="form-control
		<?php
		if ( isset( $form_valid ) && ! $form_valid && is_numeric( array_search( 'email', $error_fields, true ) ) ) {
			echo ' ' . esc_html( RACKETMANAGER_IS_INVALID );
		}
		?>
		" placeholder="<?php esc_html_e( 'Enter email address', 'racketmanager' ); ?>" name="email" id="email"
<?php
if ( isset( $player->email ) ) {
	echo ' value = "' . esc_html( $player->email ) . '" ';
}
?>
/>
		<label for="email"><?php esc_html_e( 'Email address', 'racketmanager' ); ?></label>
	</div>
	<div class="form-floating mb-3">
		<input type="tel" class="form-control
		<?php
		if ( isset( $form_valid ) && ! $form_valid && is_numeric( array_search( 'email', $error_fields, true ) ) ) {
			echo ' ' . esc_html( RACKETMANAGER_IS_INVALID );
		}
		?>
		" placeholder="<?php esc_html_e( 'Enter telephone number', 'racketmanager' ); ?>" name="contactno" id="contactno"
<?php
if ( isset( $player->contactno ) ) {
	echo ' value = "' . esc_html( $player->contactno ) . '" ';
}
?>
/>
		<label for="contactno"><?php esc_html_e( 'Telephone number', 'racketmanager' ); ?></label>
	</div>
	<?php if ( isset( $player_id ) ) { ?>
		<div class="form-check">
			<input class="form-check-input
			<?php
			if ( isset( $form_valid ) && ! $form_valid && is_numeric( array_search( 'email', $error_fields, true ) ) ) {
				echo ' ' . esc_html( RACKETMANAGER_IS_INVALID );
			}
			?>
			" type="checkbox" name="locked" id="locked" value="Locked"
		<?php
		if ( isset( $player->locked ) && $player->locked ) {
			echo ' ' . esc_html( RACKETMANAGER_CHECKED );
		}
		?>
>
			<label for "locked" class="form-check-label"><?php esc_html_e( 'Locked', 'racketmanager' ); ?></label>
		</div>
	<?php } ?>
	<?php if ( isset( $club_id ) ) { ?>
		<input type="hidden" name="club_Id" id="club_Id" value="<?php echo esc_html( $club_id ); ?>" />
	<?php } ?>
	<?php if ( isset( $player_id ) ) { ?>
		<input type="hidden" name="player_id" id="player_id" value="<?php echo esc_html( $player->id ); ?>" />
		<input type="submit" name="updatePlayer" value="<?php esc_html_e( 'Update Player', 'racketmanager' ); ?>" class="btn btn-primary" />
	<?php } else { ?>
		<input type="submit" name="addPlayer" value="<?php esc_html_e( 'Add Player', 'racketmanager' ); ?>" class="btn btn-primary" />
	<?php } ?>
</form>
