<?php
/**
 * Template for individual player header
 *
 * @package Racketmanager/Templates/Includes
 */

namespace Racketmanager;

?>
	<div class="page-subhead">
		<div class="media">
			<div class="media__wrapper">
				<div class="media__img">
					<span class="profile-icon">
						<span class="profile-icon__abbr">
							<?php
							$player_initials = substr( $player->firstname, 0, 1 ) . substr( $player->surname, 0, 1 );
							echo esc_html( $player_initials );
							?>
						</span>
					</span>
				</div>
				<div class="media__content">
					<h3 class="media__title">
						<?php echo esc_html( $player->display_name ); ?>
						<?php
						if ( ! empty( $player->btm ) ) {
							?>
							<span class="media__title-aside"><?php echo esc_html( $player->btm ); ?></span>
							<?php
						}
						?>
					</h3>
						<span class="media__subheading">
							<?php
							if ( isset( $player->club_name ) ) {
								?>
								<span><?php echo esc_html( $tournament_player->club_name ); ?></span>
								<?php
							}
							?>
						</span>
				</div>
				<div class="media__aside">
					<div class="progress-bar-container">
						<?php
						if ( $player->played ) {
							?>
							<div class="clearfix">
								<span class="pull-left"><?php esc_html_e( 'Win-Loss', 'racketmanager' ); ?></span>
								<span class="pull-right"><?php echo esc_html( $player->matches_won ) . '-' . esc_html( $player->matches_lost ) . ' (' . esc_html( $player->played ) . ')'; ?></span>
							</div>
							<div class="progress">
								<div class="progress-bar bg-success" role="progress-bar" style="width: <?php echo esc_html( $player->win_pct ); ?>%" aria-valuenow="<?php echo esc_html( $player->win_pct ); ?>" aria-valuemin="0" aria-valuemax="100" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo esc_html( $player->win_pct ) . ' ' . esc_html__( 'won', 'racketmanager' ); ?>%"></div>
							</div>
							<?php
						}
						?>
					</div>
				</div>
			</div>
		</div>
	</div>
