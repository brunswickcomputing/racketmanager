<?php
/**
 *
 * Template page to display club players
 *
 * @package Racketmanager/Templates
 *
 * The following variables are usable:
 *
 *  $players: array of player objects
 *
 * You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
 */

namespace Racketmanager;

?>
<details id="club-players">
		<summary>
			<h2 class="club-players-header"><?php esc_html_e( 'Players', 'racketmanager' ); ?></h2>
		</summary>
		<div id="players" class="accordion accordion-flush">
			<?php
			if ( $user_can_add_player ) {
				?>
				<div class="accordion-item">
					<h3 class="accordion-header" id="heading-addplayer">
						<button class="accordion-button collapsed frontend" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-addplayer" aria-expanded="false" aria-controls="collapse-addplayer">
							<?php esc_html_e( 'Add player', 'racketmanager' ); ?>
						</button>
					</h3>
					<div id="collapse-addplayer" class="accordion-collapse collapse" aria-labelledby="heading-addplayer" data-bs-parent="#players">
						<div class="accordion-body">
							<form id="playerRequestFrm" action="" method="post" onsubmit="return checkSelect(this)">
								<?php wp_nonce_field( 'club-player-request' ); ?>
								<input type="hidden" name="affiliatedClub" id="affiliatedClub" value="<?php echo esc_html( $club->id ); ?>" />
								<div class="form-floating mb-3">
									<input required="required" type="text" class="form-control" id="firstname" name="firstname" size="30" class="form-control" placeholder="First name" aria-describedby="firstnameFeedback" />
									<label for="firstname"><?php esc_html_e( 'First name', 'racketmanager' ); ?></label>
									<div id="firstnameFeedback" class="invalid-feedback"></div>
								</div>
								<div class="form-floating mb-3">
									<input required="required" type="text" class="form-control" id="surname" name="surname" size="30" class="form-control" placeholder="Surname" aria-describedby="surnameFeedback" />
									<label for="surname"><?php esc_html_e( 'Surname', 'racketmanager' ); ?></label>
									<div id="surnameFeedback" class="invalid-feedback"></div>
								</div>
								<div class="form-floating mb-3">
									<fieldset>
										<legend id="gender"><?php esc_html_e( 'Gender', 'racketmanager' ); ?></legend>
										<div class="form-check">
											<input required="required" type="radio" id="genderMale" name="gender" value="M" class="form-check-input" />
											<label for="genderMale" class="form-check-label"><?php esc_html_e( 'Male', 'racketmanager' ); ?></label>
										</div>
										<div class="form-check">
											<input type="radio" id="genderFemale" name="gender" value="F" class="form-check-input" />
											<label for="genderFemale" class="form-check-label"><?php esc_html_e( 'Female', 'racketmanager' ); ?></label>
										</div>
										<div id="genderFeedback" class="invalid-feedback"></div>
									</fieldset>
								</div>
								<div class="form-floating mb-3">
									<input type="number" class="form-control" placeholder="<?php esc_html_e( 'Enter LTA Tennis Number', 'racketmanager' ); ?>" name="btm" id="btm" size="11" class="form-control" aria-describedby="btmFeedback" />
									<label for="btm"><?php esc_html_e( 'LTA Tennis Number', 'racketmanager' ); ?></label>
									<div id="btmFeedback" class="invalid-feedback"></div>
								</div>
								<div class="form-floating mb-3">
									<input type="email" class="form-control" placeholder="<?php esc_html_e( 'Enter email address', 'racketmanager' ); ?>" name="email" id="email" class="form-control" aria-describedby="emailFeedback" autocomplete="off" />
									<label for="email"><?php esc_html_e( 'Email address', 'racketmanager' ); ?></label>
									<div id="emailFeedback" class="invalid-feedback"></div>
								</div>
								<button class="btn mb-3" type="button" cid="clubPlayerUpdateSubmit" onclick="Racketmanager.club_player_request(this)"><?php esc_html_e( 'Add player', 'racketmanager' ); ?></button>
								<div id="updateResponse"></div>
							</form>
						</div>
					</div>
				</div>
				<?php if ( $player_requests ) { ?>
					<div class="accordion-item">
						<h3 class="accordion-header" id="heading-pendingplayer">
							<button class="accordion-button collapsed frontend" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-pendingplayer" aria-expanded="false" aria-controls="collapse-pendingplayer">
								<?php esc_html_e( 'Pending players', 'racketmanager' ); ?>
							</button>
						</h3>
						<div id="collapse-pendingplayer" class="accordion-collapse collapse" aria-labelledby="heading-pendingplayer" data-bs-parent="#players">
						<div class="accordion-body">
							<table class="widefat noborder" title="RacketManager Pending Club Players" aria-describedby="<?php esc_html_e( 'Club pending players', 'racketmanager' ); ?>">
								<thead>
									<tr>
										<th scope="col"><?php esc_html_e( 'Name', 'racketmanager' ); ?></th>
										<th scope="col" class="colspan"><?php esc_html_e( 'Gender', 'racketmanager' ); ?></th>
										<th scope="col" class="colspan"><?php esc_html_e( 'LTA Tennis Number', 'racketmanager' ); ?></th>
										<th scope="col" class="colspan"><?php esc_html_e( 'Requested Date', 'racketmanager' ); ?></th>
										<th scope="col" class="colspan"><?php esc_html_e( 'Requested By', 'racketmanager' ); ?></th>
									</tr>
								</thead>
								<tbody id="pendingClubPlayers">
									<?php $class = ''; ?>
									<?php
									foreach ( $player_requests as $player_request ) {
										?>
										<?php $class = ( 'alternate' === $class ) ? '' : 'alternate'; ?>
										<tr class="<?php echo esc_html( $class ); ?>">
											<th scope="row"><?php echo esc_html( $player_request->first_name . ' ' . $player_request->surname ); ?></th>
											<td><?php echo esc_html( $player_request->gender ); ?></td>
											<td><?php echo esc_html( $player_request->btm ); ?></td>
											<td><?php echo esc_html( $player_request->requested_date ); ?></td>
											<td><?php echo esc_html( $player_request->requested_user ); ?></td>
										</tr>
									<?php } ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
					<?php
				}
			}
			?>
			<?php
			$genders[ __( 'ladies', 'racketmanager' ) ] = 'F';
			$genders[ __( 'men', 'racketmanager' ) ]    = 'M';
			foreach ( $genders as $key => $gender ) {
				?>
				<div id="playerRemove"></div>
				<div class="accordion-item">
					<h3 class="accordion-header" id="heading-<?php echo esc_html( $key ); ?>">
						<button class="accordion-button collapsed frontend" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo esc_html( $key ); ?>" aria-expanded="false" aria-controls="collapse-<?php echo esc_html( $key ); ?>">
							<?php echo esc_html( $key ); ?>
						</button>
					</h3>
					<div id="collapse-<?php echo esc_html( $key ); ?>" class="accordion-collapse collapse" aria-labelledby="heading-<?php echo esc_html( $key ); ?>" data-bs-parent="#players">
						<div class="accordion-body">
							<?php
							if ( $club_players ) {
								?>
								<form id="club-player-<?php echo esc_html( $key ); ?>-remove" method="post" action="">
									<?php wp_nonce_field( 'club-player-remove', 'racketmanager_nonce' ); ?>
									<table class="playerlist noborder" aria-describedby="<?php echo esc_html( $club->name . ' ' . $key ); ?> Players">
										<thead>
											<tr>
												<th scope="col" class="check-column">
													<?php
													if ( $user_can_update_club ) {
														?>
														<button class="btn" type="button" id="clubPlayerRemoveSubmit" onclick="Racketmanager.clubPlayerRemove('#club-player-<?php echo esc_html( $key ); ?>-remove')">
															<?php esc_html_e( 'Remove', 'racketmanager' ); ?>
														</button>
													<?php } ?>
												</th>
												<th scope="col"><?php esc_html_e( 'Name', 'racketmanager' ); ?></th>
												<th scope="col" class="colspan"><?php esc_html_e( 'LTA Tennis Number', 'racketmanager' ); ?></th>
												<th scope="col" class="colspan"><?php esc_html_e( 'Email', 'racketmanager' ); ?></th>
												<th scope="col" class="colspan"><?php esc_html_e( 'Created Date', 'racketmanager' ); ?></th>
											</tr>
										</thead>
										<tbody id="Club <?php echo esc_html( $key ); ?> Players">
											<?php $class = ''; ?>
											<?php
											foreach ( $club_players as $club_player ) {
												if ( $club_player->gender === $gender ) {
													$class = ( 'alternate' === $class ) ? '' : 'alternate';
													?>
													<tr class="<?php echo esc_html( $class ); ?>" id="club_player-<?php echo esc_html( $club_player->roster_id ); ?>">
														<th scope="row" class="check-column">
															<?php
															if ( $user_can_update_club ) {
																?>
																<input type="checkbox" class="checkbox" value="<?php echo esc_html( $club_player->roster_id ); ?>" name="clubPlayer[<?php echo esc_html( $club_player->roster_id ); ?>]" />
															<?php } ?>
														</th>
														<td><a href="<?php echo esc_html( seo_url( $club_player->fullname ) ); ?>"><?php echo esc_html( $club_player->fullname ); ?></a></td>
														<td><?php echo esc_html( $club_player->btm ); ?></td>
														<td><?php echo esc_html( $club_player->email ); ?></td>
														<td
															<?php
															if ( ! empty( $club_player->created_user_name ) ) {
																echo 'title="' . esc_html( __( 'Created by', 'racketmanager' ) ) . ' ' . esc_html( $club_player->created_user_name ) . '"';
															}
															?>
															>
															<?php echo esc_html( substr( $club_player->created_date, 0, 10 ) ); ?>
														</td>
													</tr>
													<?php
												}
											}
											?>
										</tbody>
									</table>
								</form>
							<?php } ?>
						</div>
					</div>
				</div>
			<?php } ?>
		</div>
	</details>
