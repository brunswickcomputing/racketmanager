<?php
/**
 *
 * Template page to display single club
 *
 * @package Racketmanager/Templates
 *
 * The following variables are usable:
 *
 *  $club: club object
 *  $club_players: club Players object
 *
 * You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
 */

namespace Racketmanager;

/** @var boolean $standalone */
/** @var object $club */
/** @var boolean $user_can_update_club */
require RACKETMANAGER_PATH . 'templates/includes/club-header.php';
?>
<div class="module module--card">
	<?php
	if ( $standalone ) {
		?>
		<div class="module__banner">
			<h3 class="module__title"><?php esc_html_e( 'About', 'racketmanager' ); ?></h3>
		</div>
		<?php
	}
	?>
	<div class="module__content">
		<div class="module-container">
			<div class="team">
				<form id="clubUpdateFrm" action="" method="post">
					<?php wp_nonce_field( 'club-update', 'racketmanager_nonce' ); ?>
					<input type="hidden" id="club_id" name="club_id" value="<?php echo esc_html( $club->id ); ?>" />
					<div class="form-control mb-3">
						<legend><?php esc_html_e( 'Match secretary details', 'racketmanager' ); ?></legend>
						<?php
						if ( $club->matchsecretary || $user_can_update_club ) {
							?>
							<div class="row g-3">
								<div class="mb-3">
									<div class="form-floating">
										<input type="text" class="form-control" id="matchSecretary" name="matchSecretary" value="<?php echo esc_html( $club->match_secretary_name ); ?>" <?php disabled( $user_can_update_club, false ); ?> />
										<label for="matchSecretary"><?php esc_html_e( 'Name', 'racketmanager' ); ?></label>
										<input type="hidden" id="matchSecretaryId" name="matchSecretaryId" value="<?php echo esc_html( $club->matchsecretary ); ?>" />
										<div id="matchSecretaryFeedback" class="invalid-tooltip"></div>
									</div>
								</div>
							</div>
							<div class="row g-3">
								<?php
								if ( is_user_logged_in() ) {
									?>
									<?php
									if ( null !== $club->match_secretary_email || $user_can_update_club ) {
										?>
										<div class="col-md-6 mb-3">
											<div class="form-floating mb-1">
												<input type="email" class="form-control" id="matchSecretaryEmail" name="matchSecretaryEmail" value="<?php echo esc_html( $club->match_secretary_email ); ?>" <?php disabled( $user_can_update_club, false ); ?> />
												<label for="matchSecretaryEmail"><?php esc_html_e( 'Email', 'racketmanager' ); ?></label>
												<div id="matchSecretaryEmailFeedback" class="invalid-tooltip"></div>
											</div>
										</div>
										<?php
									}
									?>
									<?php
									if ( null !== $club->match_secretary_contact_no || $user_can_update_club ) {
										?>
										<div class="col-md-6 mb-3">
											<div class="form-floating mb-1">
												<input type="tel" class="form-control" id="matchSecretaryContactNo" name="matchSecretaryContactNo" value="<?php echo esc_html( $club->match_secretary_contact_no ); ?>" <?php disabled( $user_can_update_club, false ); ?> />
												<label for="matchSecretaryContactNo"><?php esc_html_e( 'Telephone', 'racketmanager' ); ?></label>
												<div id="matchSecretaryContactNoFeedback" class="invalid-tooltip"></div>
											</div>
										</div>
										<?php
									}
									?>
									<?php
								} else {
									?>
									<div class="form-floating mb-3">
										<div class="contact-login-msg">You need to <a href="<?php echo esc_html( wp_login_url() ); ?>">login</a> to access match secretary contact details</div>
									</div>
									<?php
								}
								?>
							</div>
							<?php
						}
						?>
					</div>
					<fieldset class="form-control mb-3">
						<legend><?php esc_html_e( 'Contact details', 'racketmanager' ); ?></legend>
						<div class="row g-3">
							<div class="col-md-6 mb-3">
								<?php
								if ( $club->contactno || $user_can_update_club ) {
									?>
									<div class="form-floating mb-1">
										<input type="tel" class="form-control" id="clubContactNo" name="clubContactNo" value="<?php echo esc_html( $club->contactno ); ?>" <?php disabled( $user_can_update_club, false ); ?> />
										<label for="clubContactNo"><?php esc_html_e( 'Telephone', 'racketmanager' ); ?></label>
										<div id="clubContactNoFeedback" class="invalid-tooltip"></div>
									</div>
									<?php
								}
								?>
							</div>
							<div class="col-md-6 mb-3">
								<?php
								if ( $club->website || $user_can_update_club ) {
									?>
									<div class="form-floating mb-1">
										<input type="url" class="form-control" id="website" name="website" value="<?php echo esc_html( $club->website ); ?>" <?php disabled( $user_can_update_club, false ); ?> />
										<label for="website"><?php esc_html_e( 'Website', 'racketmanager' ); ?></label>
										<div id="websiteFeedback" class="invalid-tooltip"></div>
									</div>
									<?php
								}
								?>
							</div>
						</div>
						<div class="row g-3">
							<div class="mb-3">
								<?php
								if ( $club->address || $user_can_update_club ) {
									?>
									<div class="form-floating mb-1">
										<input type="text" class="form-control" id="address" name="address" autocomplete="off" placeholder="<?php esc_html_e( 'Club address', 'racketmanager' ); ?>"  <?php disabled( $user_can_update_club, false ); ?> value="<?php echo esc_html( $club->address ); ?>" />
										<label for="address"><?php esc_html_e( 'Address', 'racketmanager' ); ?></label>
										<div id="addressFeedback" class="invalid-tooltip"></div>
									</div>
									<?php
								}
								?>
							</div>
						</div>
					</fieldset>
					<fieldset class="form-control mb-3">
						<legend><?php esc_html_e( 'Information', 'racketmanager' ); ?></legend>
						<div class="row g-3">
							<div class="col-md-6 mb-3">
								<?php
								if ( $club->founded || $user_can_update_club ) {
									?>
									<div class="form-floating mb-1">
										<input type="number" class="form-control" id="founded" name="founded" value="<?php echo esc_html( $club->founded ); ?>" <?php disabled( $user_can_update_club, false ); ?> />
										<label for="founded"><?php esc_html_e( 'Founded', 'racketmanager' ); ?></label>
										<div id="foundedFeedback" class="invalid-tooltip"></div>
									</div>
									<?php
								}
								?>
							</div>
							<div class="col-md-6 mb-3">
								<?php
								if ( $club->facilities || $user_can_update_club ) {
									?>
									<div class="form-floating mb-1">
										<input type="text" class="form-control" id="facilities" name="facilities" value="<?php echo esc_html( $club->facilities ); ?>" <?php disabled( $user_can_update_club, false ); ?> />
										<label for="facilities"><?php esc_html_e( 'Facilities', 'racketmanager' ); ?></label>
										<div id="facilitiesFeedback" class="invalid-tooltip"></div>
									</div>
									<?php
								}
								?>
							</div>
						</div>
					</fieldset>
					<div id="club"></div>
					<div id="clubFeedback" class="invalid-tooltip"></div>
					<?php
					if ( $user_can_update_club ) {
						?>
						<button class="btn mb-3" type="button" id="updateClubSubmit" name="updateClubSubmit" onclick="Racketmanager.updateClub(this)"><?php esc_html_e( 'Update details', 'racketmanager' ); ?></button>
						<div class="updateResponse" id="updateClub" name="updateClub"></div>
						<div id="clubUpdateResponse" class="alert_rm" style="display: none;">
							<div class="alert__body">
								<div class="alert__body-inner">
									<span id="clubUpdateResponseText"></span>
								</div>
							</div>
						</div>
						<?php
					}
					?>
				</form>
			</div>
		</div>
	</div>
</div>
<?php
if ( $standalone ) {
	latest_results( $club->id, array( 'header_level' => 3 ) );
}
?>
<?php
if ( $standalone ) {
	?>
	<?php
	if ( $user_can_update_club ) {
		?>
		 <div class="module module--card">
			 <div class="module__content">
				 <div class="module-container">
					 <a href="/clubs/<?php echo esc_attr( seo_url( $club->shortcode ) ); ?>/invoices/" role="button">
						 <h3 class="module__title"><?php esc_html_e( 'Invoices', 'racketmanager' ); ?></h3>
					 </a>
				 </div>
			 </div>
		 </div>
		<?php
	}
	?>
	<div class="module module--card">
		<div class="module__content">
			<div class="module-container">
				<a href="/clubs/<?php echo esc_attr( seo_url( $club->shortcode ) ); ?>/players/" role="button">
					<h3 class="module__title"><?php esc_html_e( 'Players', 'racketmanager' ); ?></h3>
				</a>
			</div>
		</div>
	</div>
	<div class="module module--card">
		<div class="module__content">
			<div class="module-container">
				<a href="/clubs/<?php echo esc_attr( seo_url( $club->shortcode ) ); ?>/competitions/" role="button">
					<h3 class="module__title"><?php esc_html_e( 'Competitions', 'racketmanager' ); ?></h3>
				</a>
			</div>
		</div>
	</div>
	<?php
}
?>
<?php
if ( $standalone && ! empty( $club->address ) && ! empty( $google_maps_key ) ) {
	?>
	<div class="module module--card">
		<div class="module__banner">
			<h3 class="module__title"><?php esc_html_e( 'Map', 'racketmanager' ); ?></h3>
		</div>
		<div class="module__content">
			<div class="module-container">
				<fieldset class="club-address">
					<legend class="d-none"><?php esc_html_e( 'Map', 'racketmanager' ); ?></legend>
					<iframe title="<?php esc_html_e( 'Club map', 'racketmanager' ); ?>" width="100%" height="320" id="clubMap" name="clubMap" src="https://www.google.com/maps/embed/v1/search?key=<?php echo esc_html( $google_maps_key ); ?>&amp;zoom=15&amp;maptype=roadmap&amp;q=<?php echo esc_html( $club->address ); ?>" allowfullscreen></iframe>
				</fieldset>
			</div>
		</div>
	</div>
	<?php
}
?>
