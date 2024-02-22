<?php
/**
 * Form to allow input of match scores
 *
 * @package Racketmanager/Templates;
 */

namespace Racketmanager;

$user_can_update = $user_can_update_array[0];
$user_message    = $user_can_update_array[3];
$tabbase         = 0;
?>
	<div id="matchrubbers">
		<div id="matchheader">
			<div class="row justify-content-between" id="match-header-1">
				<div class="col-auto leaguetitle"><?php echo esc_html( $match->league->title ); ?></div>
				<div class="col-auto matchday">
				<?php
				if ( 'championship' === $match->league->mode ) {
					echo esc_html( $match->league->championship->get_final_name( $match->final_round ) );
				} else {
					echo esc_html( 'Week' . $match->match_day );
				}
				?>
				</div>
				<div class="col-auto matchdate"><?php echo esc_html( substr( $match->date, 0, 10 ) ); ?></div>
			</div>
			<div class="row justify-content-center" id="match-header-2">
				<?php if ( 'championship' !== $match->league->mode ) { ?>
				<div class="col-auto matchtitle"><?php echo esc_html( $match->match_title ); ?></div>
				<?php } ?>
			</div>
		</div>
		<form id="match-view" action="#" method="post" onsubmit="return checkSelect(this)">
			<?php wp_nonce_field( 'scores-match', 'racketmanager_nonce' ); ?>

			<input type="hidden" name="current_league_id" id="current_league_id" value="<?php echo esc_html( $match->league_id ); ?>" />
			<input type="hidden" name="current_match_id" id="current_match_id" value="<?php echo esc_html( $match->id ); ?>" />
			<input type="hidden" name="current_season" id="current_season" value="<?php echo esc_html( $match->season ); ?>" />
			<input type="hidden" name="home_team" value="<?php echo esc_html( $match->home_team ); ?>" />
			<input type="hidden" name="away_team" value="<?php echo esc_html( $match->away_team ); ?>" />
			<input type="hidden" name="match_type" value="<?php echo esc_html( $match->type ); ?>" />
			<input type="hidden" name="match_round" value="<?php echo esc_html( $match->round ); ?>" />

			<div class="row mb-3">
				<div class="col-4 text-center"><strong><?php esc_html_e( 'Team', 'racketmanager' ); ?></strong></div>
				<div class="col-4 text-center"><strong><?php esc_html_e( 'Sets', 'racketmanager' ); ?></strong></div>
				<div class="col-4 text-center"><strong><?php esc_html_e( 'Team', 'racketmanager' ); ?></strong></div>
			</div>
			<div class="row align-items-center mb-3">
				<div class="col-4 text-center">
					<?php echo esc_html( $match->teams['home']->title ); ?>
				</div>
				<div class="col-4 align-self-center">
					<div class="row text-center mb-1">
						<?php
						for ( $i = 1; $i <= $match->league->num_sets; $i++ ) {
							if ( ! isset( $match->sets[ $i ] ) ) {
								$match->sets[ $i ] = array(
									'player1' => '',
									'player2' => '',
								);
							}
							$colspan  = 12 / $match->league->num_sets;
							$tabindex = $tabbase + 10 + $i;
							?>
							<div class="col-<?php echo esc_html( $colspan ); ?> col-sm-12 col-lg-<?php echo esc_html( $colspan ); ?>">
								<input tabindex="<?php echo esc_html( $tabindex ); ?>" class="points" type="text" size="2" id="set_<?php echo esc_html( $i ); ?>_player1" name="custom[sets][<?php echo esc_html( $i ); ?>][player1]" value="<?php echo esc_html( $match->sets[ $i ]['player1'] ); ?>" />
								-
								<?php $tabindex = $tabbase + 11 + $i; ?>
								<input tabindex="<?php echo esc_html( $tabindex ); ?>" class="points" type="text" size="2" id="set_<?php echo esc_html( $i ); ?>_player2" name="custom[sets][<?php echo esc_html( $i ); ?>][player2]" value="<?php echo esc_html( $match->sets[ $i ]['player2'] ); ?>" />
							</div>
							<?php
						}
						?>
					</div>
				</div>
				<div class="col-4 text-center">
					<?php echo esc_html( $match->teams['away']->title ); ?>
				</div>
			</div>
			<div class="row text-center mb-3">
				<div class="col-12">
					<input class="points" type="text" size="2" readonly id="home_points" name="home_points" value="<?php echo esc_html( isset( $match->home_points ) ? $match->home_points : '' ); ?>" />
					<input class="points" type="text" size="2" readonly id="away_points" name="away_points" value="<?php echo esc_html( isset( $match->away_points ) ? $match->away_points : '' ); ?>" />
				</div>
			</div>
			<div class="form-floating">
				<textarea class="form-control result-comments" tabindex="490" placeholder="Leave a comment here" name="resultConfirmComments" id="resultConfirmComments"><?php echo esc_html( $match->comments['result'] ); ?></textarea>
				<label for="resultConfirmComments"><?php esc_html_e( 'Comments', 'racketmanager' ); ?></label>
			</div>
			<div class="mb-3">
				<?php
				if ( isset( $match->updated_user ) ) {
					?>
					<div class="row">
						<div class="col-auto">
							Updated By:
						</div>
						<div class="col-auto">
							<?php echo esc_html( $racketmanager->get_player_name( $match->updated_user ) ); ?>
						</div>
					</div>
					<?php
					if ( isset( $match->updated ) ) {
						?>
						<div class="row">
							<div class="col-auto">
								On:
							</div>
							<div class="col-auto">
								<?php echo esc_html( $match->updated ); ?>
							</div>
						</div>
						<?php
					}
					?>
					<?php
				}
				?>
			</div>
			<?php
			if ( $user_can_update ) {
				if ( current_user_can( 'update_results' ) || 'P' === $match->confirmed || null === $match->confirmed ) {
					?>
					<div class="row mb-3">
						<div class="col-12">
						<input type="hidden" name="updateMatch" id="updateMatch" value="results" />
						<button tabindex="500" class="button button-primary" type="button" id="updateMatchResults" onclick="Racketmanager.updateMatchResults(this)">Update Result</button>
						</div>
					</div>
					<div class="row mb-3">
						<div id="updateResponse" class="updateResponse"></div>
					</div>
					<?php
				} else {
					?>
					<div class="row mb-3">
						<div class="col-12 updateResponse message-error">
							<?php esc_html_e( 'Updates not allowed', 'racketmanager' ); ?>
						</div>
					</div>
					<?php
				}
			} else {
				?>
				<div class="row mb-3 justify-content-center">
					<div class="col-auto">
						<?php if ( 'notLoggedIn' === $user_message ) { ?>
						You need to <a href="<?php echo esc_html( wp_login_url( wp_get_current_url() ) ); ?>">login</a> to update the result.
							<?php
						} else {
							esc_html_e( 'User not allowed to update result', 'racketmanager' );
						}
						?>
					</div>
				</div>
				<?php
			}
			?>
		</form>
	</div>
	<?php
