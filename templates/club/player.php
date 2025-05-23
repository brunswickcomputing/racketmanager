<?php
/**
 *
 * Template page for a club player
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

/** @var object $club */
/** @var object $player */
/** @var bool $user_can_update */
$page_referrer = wp_get_referer();
if ( ! $page_referrer ) {
	$page_referrer = '/clubs/' . seo_url( $club->shortcode ) . '/players/';
}
?>
<?php require RACKETMANAGER_PATH . 'templates/includes/player-header.php'; ?>

<div class="module module--card">
	<div class="module__content">
		<div class="module-container">
			<div class="entry-content">
				<form id="playerUpdateFrm" action="" method="post">
					<?php wp_nonce_field( 'player-update', 'racketmanager_nonce' ); ?>
					<input type="hidden" id="playerId" name="playerId" value="<?php echo esc_html( $player->ID ); ?>" />
					<div class="form-control mb-3">
						<legend><?php esc_html_e( 'Personal details', 'racketmanager' ); ?></legend>
						<div class="row g-3">
							<?php
							if ( null !== $player->firstname || $user_can_update ) {
								?>
								<div class="form-floating col-md-6 mb-3">
									<input type="text" class="form-control" id="firstname" name="firstname" value="<?php echo esc_html( $player->firstname ); ?>" <?php disabled( $user_can_update, false ); ?> />
									<label for="firstname"><?php esc_html_e( 'First name', 'racketmanager' ); ?></label>
									<div id="firstnameFeedback" class="invalid-feedback"></div>
								</div>
								<?php
							}
							?>
							<?php
							if ( null !== $player->surname || $user_can_update ) {
								?>
								<div class="form-floating col-md-6 mb-3">
									<input type="text" class="form-control" id="surname" name="surname" value="<?php echo esc_html( $player->surname ); ?>" <?php disabled( $user_can_update, false ); ?> />
									<label for="surname"><?php esc_html_e( 'Surname', 'racketmanager' ); ?></label>
									<div id="surnameFeedback" class="invalid-feedback"></div>
								</div>
								<?php
							}
							?>
						</div>
						<?php
						if ( null !== $player->gender || $user_can_update ) {
							?>
							<fieldset class="form-floating mb-3">
								<legend><?php esc_html_e( 'Gender', 'racketmanager' ); ?></legend>
								<div class="form-check form-check-inline">
									<input class="form-check-input" type="radio" required name="gender" id="genderMale" value="M"
									<?php
									if ( isset( $player->gender ) && 'M' === $player->gender ) {
										echo ' checked';
									}
									?>
									<?php disabled( $user_can_update, false ); ?> />
									<label for="genderMale" class="form-check-label"><?php esc_html_e( 'Male', 'racketmanager' ); ?></label>
								</div>
								<div class="form-check form-check-inline">
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
							<?php
						}
						?>
						<div class="row g-3">
							<?php
							if ( null !== $player->btm || $user_can_update ) {
								?>
								<div class="form-floating col-md-6 mb-3">
									<input type="number" class="form-control" id="btm" name="btm" value="<?php echo esc_html( $player->btm ); ?>" <?php disabled( $user_can_update, false ); ?> />
									<label for="btm"><?php esc_html_e( 'LTA Tennis Number', 'racketmanager' ); ?></label>
									<div id="btmFeedback" class="invalid-feedback"></div>
								</div>
								<?php
							}
							?>
							<?php
							if ( is_user_logged_in() ) {
								if ( ! empty( $player->year_of_birth ) || $user_can_update ) {
									?>
									<div class="form-floating col-md-6 mb-3">
										<select class="form-select" id="year_of_birth" name="year_of_birth" <?php disabled( $user_can_update, false ); ?>>
											<option value=""><?php esc_html_e( 'Enter year of birth', 'racketmanager' ); ?></option>
											<?php
											$current_year = gmdate( 'Y' );
											$start_year   = $current_year - 5;
											$end_year     = $start_year - 100;
											for ( $i = $start_year; $i > $end_year; $i-- ) {
												?>
												<option value="<?php echo esc_attr( $i ); ?>" <?php selected( $i, $player->year_of_birth ); ?>><?php echo esc_html( $i ); ?></option>
												<?php
											}
											?>
										</select>
										<label for="year_of_birth"><?php esc_html_e( 'Year of birth', 'racketmanager' ); ?></label>
										<div id="year_of_birthFeedback" class="invalid-feedback"></div>
									</div>
									<?php
								}
							}
							?>
						</div>
					</div>
					<?php
					if ( is_user_logged_in() ) {
						?>
						<fieldset class="form-control mb-3">
							<legend><?php esc_html_e( 'Contact details', 'racketmanager' ); ?></legend>
							<div class="row g-3">
								<?php
								if ( null !== $player->email || $user_can_update ) {
									?>
									<div class="form-floating col-md-6 mb-3">
										<input type="email" class="form-control" id="email" name="email" autocomplete="off" value="<?php echo esc_html( $player->email ); ?>" <?php disabled( $user_can_update, false ); ?> />
										<label for="email"><?php esc_html_e( 'Email address', 'racketmanager' ); ?></label>
										<div id="emailFeedback" class="invalid-feedback"></div>
									</div>
									<?php
								}
								?>
								<?php
								if ( null !== $player->contactno || $user_can_update ) {
									?>
									<div class="form-floating col-md-6 mb-3">
										<input type="tel" class="form-control" id="contactno" name="contactno" autocomplete="off" value="<?php echo esc_html( $player->contactno ); ?>" <?php disabled( $user_can_update, false ); ?> />
										<label for="contactno"><?php esc_html_e( 'Telephone number', 'racketmanager' ); ?></label>
										<div id="contactnoFeedback" class="invalid-feedback"></div>
									</div>
									<?php
								}
								?>
							</div>
						</fieldset>
						<?php
					}
					?>
					<?php
					if ( ! empty( $player->created_date ) || ! empty( $player_created_user_name ) ) {
						?>
						<fieldset class="form-control mb-3">
							<legend><?php esc_html_e( 'System details', 'racketmanager' ); ?></legend>
							<div class="row g-3">
								<?php
								if ( ! empty( $player->created_date ) ) {
									?>
									<div class="form-floating col-md-6">
										<input type="text" disabled class="form-control" id="dateCreated" name="dateCreated" value="<?php echo esc_html( $player->created_date ); ?>" />
										<label for="dateCreated"><?php esc_html_e( 'Added on', 'racketmanager' ); ?></label>
									</div>
									<?php
								}
								?>
								<?php
								if ( ! empty( $player_created_user_name ) ) {
									?>
									<div class="form-floating col-md-6">
										<input type="text" disabled class="form-control" id="userCreated" name="userCreated" value="<?php echo esc_html( $player->created_user_name ); ?>" />
										<label for="userCreated"><?php esc_html_e( 'Added by', 'racketmanager' ); ?></label>
									</div>
									<?php
								}
								?>
							</div>
						</fieldset>
						<?php
					}
					?>
					<div class="row mb-3">
						<div class="match__buttons">
							<a href="<?php echo esc_attr( $page_referrer ); ?>" class="btn btn-secondary text-uppercase" type="button" id="updatePlayerSubmit"><?php esc_html_e( 'Return', 'racketmanager' ); ?></a>
							<?php
							if ( $user_can_update ) {
								?>
								<button class="btn btn-primary" type="button" id="updatePlayerSubmit" name="updatePlayerSubmit" onclick="Racketmanager.updatePlayer(this)"><?php esc_html_e( 'Update details', 'racketmanager' ); ?></button>
								<?php
							}
							?>
						</div>
					</div>
					<div id="playerUpdateResponse" class="alert_rm" style="display: none;">
						<div class="alert__body">
							<div class="alert__body-inner">
								<span id="playerUpdateResponseText"></span>
							</div>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
