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

$user_can_update_club = false;
$user_can_add_player  = false;
if ( ! empty( $club_list ) ) {
	$standalone = false;
} else {
	$standalone = true;
}
if ( ! empty( $standalone ) && is_user_logged_in() ) {
	$user   = wp_get_current_user();
	$userid = $user->ID;
	if ( current_user_can( 'manage_racketmanager' ) || ( null !== $club->matchsecretary && $userid === $club->matchsecretary ) ) {
		$user_can_update_club = true;
		$user_can_add_player  = true;
	} else {
		$options = $racketmanager->get_options( 'rosters' );
		if ( isset( $options['rosterEntry'] ) && 'captain' === $options['rosterEntry'] && $club->is_player_captain( $userid ) ) {
			$user_can_add_player = true;
		}
	}
}
?>
<?php
if ( $standalone ) {
	$heading_type = 'h1';
} else {
	$heading_type = 'h2';

}
?>
<div class="module module--card">
	<div class="module__banner">
		<<?php echo esc_html( $heading_type ); ?> class="club-name">
			<?php
			if ( ! $standalone ) {
				?>
				<a href="/clubs/<?php echo esc_html( sanitize_title( $club->shortcode ) ); ?>/">
			<?php } ?>
				<?php echo esc_html( $club->name ); ?>
			<?php
			if ( ! $standalone ) {
				?>
				</a>
			<?php } ?>
		<?php
		if ( is_user_logged_in() ) {
			$is_favourite = $racketmanager->is_user_favourite( 'club', $club->id );
			?>
			<div class="fav-icon">
				<a href="" id="fav-<?php echo esc_html( $club->id ); ?>" title="
				<?php
				if ( $is_favourite ) {
					esc_html_e( 'Remove favourite', 'racketmanager' );
				} else {
					esc_html_e( 'Add favourite', 'racketmanager' );
				}
				?>
				" data-js="add-favourite" data-type="club" data-favourite="<?php echo esc_html( $club->id ); ?>">
					<i class="fav-icon-svg racketmanager-svg-icon
					<?php
					if ( $is_favourite ) {
						echo esc_html( ' fav-icon-svg-selected' );
					}
					?>
					">
						<?php racketmanager_the_svg( 'icon-star' ); ?>
					</i>
				</a>
				<div class="fav-msg" id="fav-msg-<?php echo esc_html( $club->id ); ?>"></div>
			</div>
		<?php } ?>
		</<?php echo esc_html( $heading_type ); ?>>
	</div>
	<div class="module__content">
		<div class="module-container">
			<div class="entry-content">
				<div class="team">
					<form id="clubUpdateFrm" action="" method="post">
						<?php wp_nonce_field( 'club-update', 'racketmanager_nonce' ); ?>
						<input type="hidden" id="club_id" name="club_id" value="<?php echo esc_html( $club->id ); ?>" />
						<?php
						if ( $club->founded || $user_can_update_club ) {
							?>
							<div class="form-floating mb-1">
								<input type="number" class="form-control" id="founded" name="founded" value="<?php echo esc_html( $club->founded ); ?>" <?php disabled( $user_can_update_club, false ); ?> />
								<label for="founded"><?php esc_html_e( 'Founded', 'racketmanager' ); ?></label>
								<div id="founded-feedback" class="invalid-feedback"></div>
							</div>
						<?php } ?>
						<?php
						if ( $club->facilities || $user_can_update_club ) {
							?>
							<div class="form-floating mb-1">
								<input type="text" class="form-control" id="facilities" name="facilities" value="<?php echo esc_html( $club->facilities ); ?>" <?php disabled( $user_can_update_club, false ); ?> />
								<label for="facilities"><?php esc_html_e( 'Facilities', 'racketmanager' ); ?></label>
								<div id="facilities-feedback" class="invalid-feedback"></div>
							</div>
						<?php } ?>
						<?php
						if ( $club->contactno || $user_can_update_club ) {
							?>
							<div class="form-floating mb-1">
									<input type="tel" class="form-control" id="clubContactNo" name="clubContactNo" value="<?php echo esc_html( $club->contactno ); ?>" <?php disabled( $user_can_update_club, false ); ?> />
								<label for="clubContactNo"><?php esc_html_e( 'Contact Number', 'racketmanager' ); ?></label>
								<div id="clubContactNo-feedback" class="invalid-feedback"></div>
							</div>
						<?php } ?>
						<?php
						if ( $club->matchsecretary || $user_can_update_club ) {
							?>
							<div class="form-floating mb-1">
								<input type="text" class="form-control" id="matchSecretaryName" name="matchSecretaryName" value="<?php echo esc_html( $club->match_secretary_name ); ?>" <?php disabled( $user_can_update_club, false ); ?> />
								<label for="matchSecretaryName"><?php esc_html_e( 'Match Secretary', 'racketmanager' ); ?></label>
								<input type="hidden" id="matchSecretaryId" name="matchSecretaryId" value="<?php echo esc_html( $club->matchsecretary ); ?>" />
								<div id="matchSecretaryId-feedback" class="invalid-feedback"></div>
							</div>
							<?php if ( is_user_logged_in() ) { ?>
								<?php
								if ( null !== $club->match_secretary_email || $user_can_update_club ) {
									?>
									<div class="form-floating mb-1">
										<input type="email" class="form-control" id="matchSecretaryEmail" name="matchSecretaryEmail" value="<?php echo esc_html( $club->match_secretary_email ); ?>" <?php disabled( $user_can_update_club, false ); ?> />
										<label for="matchSecretaryEmail"><?php esc_html_e( 'Match Secretary Email', 'racketmanager' ); ?></label>
										<div id="matchSecretaryEmail-feedback" class="invalid-feedback"></div>
									</div>
								<?php } ?>
								<?php
								if ( null !== $club->match_secretary_contact_no || $user_can_update_club ) {
									?>
									<div class="form-floating mb-1">
										<input type="tel" class="form-control" id="matchSecretaryContactNo" name="matchSecretaryContactNo" value="<?php echo esc_html( $club->match_secretary_contact_no ); ?>" <?php disabled( $user_can_update_club, false ); ?> />
										<label for="matchSecretaryContactNo"><?php esc_html_e( 'Match Secretary Contact', 'racketmanager' ); ?></label>
										<div id="matchSecretaryContactNo-feedback" class="invalid-feedback"></div>
									</div>
								<?php } ?>
							<?php } else { ?>
								<div class="form-floating mb-1">
									<div class="contact-login-msg">You need to <a href="<?php echo esc_html( wp_login_url() ); ?>">login</a> to access match secretary contact details</div>
								</div>
							<?php } ?>
						<?php } ?>
						<?php
						if ( $club->website || $user_can_update_club ) {
							?>
							<div class="form-floating mb-1">
								<input type="url" class="form-control" id="website" name="website" value="<?php echo esc_html( $club->website ); ?>" <?php disabled( $user_can_update_club, false ); ?> />
								<label for="website"><?php esc_html_e( 'Website', 'racketmanager' ); ?></label>
								<div id="website-feedback" class="invalid-feedback"></div>
							</div>
						<?php } ?>
						<?php
						if ( $club->address || $user_can_update_club ) {
							?>
							<div class="form-floating mb-1">
								<input type="text" class="form-control" id="address" name="address" autocomplete="off" placeholder="<?php esc_html_e( 'Club address', 'racketmanager' ); ?>"  <?php disabled( $user_can_update_club, false ); ?> value="<?php echo esc_html( $club->address ); ?>" />
								<label for="address"><?php esc_html_e( 'Address', 'racketmanager' ); ?></label>
								<div id="address-feedback" class="invalid-feedback"></div>
							</div>
						<?php } ?>
						<div id="club"></div>
						<div id="club-feedback" class="invalid-feedback"></div>
						<?php
						if ( $user_can_update_club ) {
							?>
							<button class="btn mb-3" type="button" id="updateClubSubmit" name="updateClubSubmit" onclick="Racketmanager.updateClub(this)"><?php esc_html_e( 'Update details', 'racketmanager' ); ?></button>
							<div class="updateResponse" id="updateClub" name="updateClub"></div>
						<?php } ?>
					</form>
				</div>
				<?php
				if ( $standalone ) {
					?>
					<details id="results">
						<summary>
							<h2 class="results-header"><?php esc_html_e( 'Latest results', 'racketmanager' ); ?></h2>
						</summary>
						<?php racketmanager_results( $club->id, array() ); ?>
					</details>
				<?php } ?>
				<?php
				if ( $standalone ) {
					require_once 'club-players.php';
				}
				?>
				<?php
				$short_code = $club->shortcode;
				$events     = $racketmanager->get_events( array( 'competition_type' => 'league' ) );
				$matchdays  = Racketmanager_Util::get_weekdays();
				if ( $events ) {
					if ( $standalone ) {
						?>
					<details id="club-teams">
						<summary>
					<?php } else { ?>
					<div id="club-teams">
					<?php } ?>
							<h2 class="teams-header"><?php esc_html_e( 'Teams', 'racketmanager' ); ?></h2>
						<?php
						if ( $standalone ) {
							?>
						</summary>
						<?php } ?>
						<div class="event-list accordion accordion-flush">
							<?php
							foreach ( $events as $event ) {
								$event = get_event( $event->id );
								$teams = $event->get_teams_info(
									array(
										'affiliatedclub' => $club->id,
										'orderby'        => array( 'title' => 'ASC' ),
									)
								);
								if ( $teams ) {
									?>
									<div id="team"></div>
									<div id="team-feedback" class="invalid-feedback"></div>
									<div class="accordion-item">
										<h3 class="header accordion-header" id="comp-<?php echo esc_html( $event->id ); ?>-club-<?php echo esc_html( $club->id ); ?>">
											<button class="accordion-button collapsed frontend" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo esc_html( $event->id ); ?>-club-<?php echo esc_html( $club->id ); ?>" aria-expanded="false" aria-controls="collapse-<?php echo esc_html( $event->id ); ?>-club-<?php echo esc_html( $club->id ); ?>">
												<?php echo esc_html( $event->name ); ?>
											</button>
										</h3>
										<div id="collapse-<?php echo esc_html( $event->id ); ?>-club-<?php echo esc_html( $club->id ); ?>" class="accordion-collapse collapse" aria-labelledby="comp-<?php echo esc_html( $event->id ); ?>-club-<?php echo esc_html( $club->id ); ?>" data-bs-parent="#event-list-<?php echo esc_html( $club->id ); ?>">
										<div id="team-<?php echo esc_html( $event->id ); ?>"></div>
										<div id="team-<?php echo esc_html( $event->id ); ?>-feedback" class="invalid-feedback"></div>
											<div class="accordion-body">
												<div class="row team">
													<?php
													if ( $standalone ) {
														?>
														<!-- Nav tabs -->
														<ul class="nav nav-tabs frontend" id="myTab" role="tablist">
															<li class="nav-item active" role="presentation">
																<button class="nav-link" id="teams-tab-<?php echo esc_html( $event->id ); ?>" data-bs-toggle="tab" data-bs-target="#teams-<?php echo esc_html( $event->id ); ?>" type="button" role="tab" aria-controls="teams-<?php echo esc_html( $event->id ); ?>" aria-selected="true">
																	<?php esc_html_e( 'Teams', 'racketmanager' ); ?>
																</button>
															</li>
															<li class="nav-item" role="presentation">
																<button class="nav-link" id="players-tab-<?php echo esc_html( $event->id ); ?>" data-bs-toggle="tab" data-bs-target="#players-<?php echo esc_html( $event->id ); ?>" type="button" role="tab" aria-controls="players-<?php echo esc_html( $event->id ); ?>" aria-selected="true">
																	<?php esc_html_e( 'Players', 'racketmanager' ); ?>
																</button>
															</li>
															<li class="nav-item" role="presentation">
																<button class="nav-link" id="matches-tab-<?php echo esc_html( $event->id ); ?>" data-bs-toggle="tab" data-bs-target="#matches-<?php echo esc_html( $event->id ); ?>" type="button" role="tab" aria-controls="matches-<?php echo esc_html( $event->id ); ?>" aria-selected="true">
																	<?php esc_html_e( 'Matches', 'racketmanager' ); ?>
																</button>
															</li>
														</ul>
													<?php } ?>
													<!-- Tab panes -->
													<div class="tab-content">
														<div class="tab-pane fade active show" id="teams-<?php echo esc_html( $event->id ); ?>" role="tabpanel" aria-labelledby="teams-tab-<?php echo esc_html( $event->id ); ?>">
															<?php require 'club-teams.php'; ?>
														</div>
														<?php
														if ( $standalone ) {
															require 'club-team-stats.php';
														}
														?>
														<?php
														if ( $standalone ) {
															require 'club-team-matches.php';
														}
														?>
													</div>
												</div>
											</div>
										</div>
									</div>
								<?php } ?>
							<?php } ?>
						</div>
					<?php
					if ( $standalone ) {
						?>
					</details>
					<?php } else { ?>
					</div>
					<?php } ?>
				<?php } ?>
				<?php
				if ( $standalone && null !== $club->address && isset( $google_maps_key ) && '' !== $google_maps_key ) {
					?>
					<div class="club-address">
						<div class="mb-3">
							<legend class="d-none"><?php esc_html_e( 'Map', 'racketmanager' ); ?></legend>
							<iframe title="<?php esc_html_e( 'Club map', 'racketmanager' ); ?> class="sp-google-map" width="100%" height="320" id="clubMap" name="clubMap" src="https://www.google.com/maps/embed/v1/search?key=<?php echo esc_html( $google_maps_key ); ?>&amp;q=<?php echo esc_html( $club->address ); ?>&amp;zoom=15&amp;maptype=roadmap" allowfullscreen></iframe>
						</div>
					</div>
				<?php } ?>
			</div><!-- .entry-content -->
		</div>
	</div>
</div>
