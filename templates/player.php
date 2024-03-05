<?php
/**
 *
 * Template page for a player
 *
 * @package Racketmanager/Templates
 *
 * The following variables are usable:
 *  $club: contains data of current club
 *  $player: contains the player details
 *
 * You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
 */

namespace Racketmanager;

$user_can_update = false;
if ( is_user_logged_in() ) {
	$user   = wp_get_current_user();
	$userid = $user->ID;
	if ( current_user_can( 'manage_racketmanager' ) ) {
		$user_can_update = true;
	} else {
		if ( null !== $player->ID && intval( $player->ID ) === $userid ) {
			$user_can_update = true;
		} elseif ( null !== $club->matchsecretary && intval( $club->matchsecretary ) === $userid ) {
			$user_can_update = true;
		} else {
			$user_can_update = false;
		}
	}
}
?>
<h1 class="club-name">
	<?php echo esc_html( $club->name ) . ' - ' . esc_html( $player->fullname ); ?>
</h1>
<div class="entry-content">
	<form id="playerUpdateFrm" action="" method="post">
		<?php wp_nonce_field( 'player-update', 'racketmanager_nonce' ); ?>
		<input type="hidden" id="playerId" name="playerId" value="<?php echo esc_html( $player->ID ); ?>" />
		<?php if ( null !== $player->firstname || $user_can_update ) { ?>
			<div class="form-floating mb-3">
				<input type="text" class="form-control" id="firstname" name="firstname" value="<?php echo esc_html( $player->firstname ); ?>" <?php disabled( $user_can_update, false ); ?> />
				<label for="firstname"><?php esc_html_e( 'First name', 'racketmanager' ); ?></label>
				<div id="firstnameFeedback" class="invalid-feedback"></div>
			</div>
		<?php } ?>
		<?php if ( null !== $player->surname || $user_can_update ) { ?>
			<div class="form-floating mb-3">
				<input type="text" class="form-control" id="surname" name="surname" value="<?php echo esc_html( $player->surname ); ?>" <?php disabled( $user_can_update, false ); ?> />
				<label for="surname"><?php esc_html_e( 'Surname', 'racketmanager' ); ?></label>
				<div id="surnameFeedback" class="invalid-feedback"></div>
			</div>
		<?php } ?>
		<?php if ( null !== $player->gender || $user_can_update ) { ?>
			<fieldset class="form-floating mb-3">
				<legend><?php esc_html_e( 'Gender', 'racketmanager' ); ?></legend>
				<div class="form-check">
					<input class="form-check-input" type="radio" required name="gender" id="genderMale" value="M"
					<?php
					if ( isset( $player->gender ) && 'M' === $player->gender ) {
						echo ' checked';
					}
					?>
					<?php disabled( $user_can_update, false ); ?> />
					<label for="genderMale" class="form-check-label"><?php esc_html_e( 'Male', 'racketmanager' ); ?></label>
				</div>
				<div class="form-check">
					<input class="form-check-input" type="radio" required name="gender" id="genderFemale" value="F"
					<?php
					if ( isset( $player->gender ) && 'F' === $player->gender ) {
						echo ' checked';
					}
					?>
					<?php disabled( $user_can_update, false ); ?> />
					<label for="genderFemale" class="form-check-label"><?php esc_html_e( 'Female', 'racketmanager' ); ?></label>
				</div>
			</fieldset>
		<?php } ?>
		<?php if ( null !== $player->btm || $user_can_update ) { ?>
			<div class="form-floating mb-3">
				<input type="number" class="form-control" id="btm" name="btm" value="<?php echo esc_html( $player->btm ); ?>" <?php disabled( $user_can_update, false ); ?> />
				<label for="btm"><?php esc_html_e( 'LTA Tennis Number', 'racketmanager' ); ?></label>
				<div id="btmFeedback" class="invalid-feedback"></div>
			</div>
		<?php } ?>
		<?php if ( null !== $player->email || $user_can_update ) { ?>
			<div class="form-floating mb-3">
				<input type="email" class="form-control" id="email" name="email" autocomplete="off" value="<?php echo esc_html( $player->email ); ?>" <?php disabled( $user_can_update, false ); ?> />
				<label for="email"><?php esc_html_e( 'Email address', 'racketmanager' ); ?></label>
				<div id="emailFeedback" class="invalid-feedback"></div>
			</div>
		<?php } ?>
		<?php if ( $user_can_update ) { ?>
			<button class="btn mb-3" type="button" id="updatePlayerSubmit" name="updatePlayerSubmit" onclick="Racketmanager.updatePlayer(this)"><?php esc_html_e( 'Update details', 'racketmanager' ); ?></button>
			<div class="updateResponse" id="updatePlayer" name="updatePlayer"></div>
		<?php } ?>
	</form>
	<a href="/clubs/<?php echo esc_html( sanitize_title( $club->shortcode ) ); ?>/#players"<button class="btn btn-secondary text-uppercase" type="button" id="updatePlayerSubmit" name="updatePlayerSubmit"><?php esc_html_e( 'Return to club', 'racketmanager' ); ?></button></a>
</div>
