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
						$total_stats = array();
						$stat_types  = array( 'winner', 'loser', 'draw' );
						foreach ( $stat_types as $stat_type ) {
							$total_stats[ $stat_type ] = 0;
							if ( ! empty( $player->statistics['played'][ $stat_type ] ) ) {
								foreach ( $player->statistics['played'][ $stat_type ] as $stats ) {
									if ( is_array( $stats ) ) {
										$total_stats[ $stat_type ] += array_sum( $stats );
									} else {
										$total_stats[ $stat_type ] += $stats;
									}
								}
							}
						}
						$matches_won  = $total_stats['winner'];
						$matches_lost = $total_stats['loser'];
						$matches_tie  = $total_stats['draw'];
						$played       = $matches_won + $matches_lost + $matches_tie;
						if ( $played ) {
							$win_pct = ceil( ( $matches_won / $played ) * 100 );
							?>
							<div class="clearfix">
								<span class="pull-left"><?php esc_html_e( 'Win-Loss', 'racketmanager' ); ?></span>
								<span class="pull-right"><?php echo esc_html( $matches_won ) . '-' . esc_html( $matches_lost ) . ' (' . esc_html( $played ) . ')'; ?></span>
							</div>
							<div class="progress">
								<div class="progress-bar bg-success" role="progress-bar" style="width: <?php echo esc_html( $win_pct ); ?>%" aria-valuenow="<?php echo esc_html( $win_pct ); ?>" aria-valuemin="0" aria-valuemax="100" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo esc_html( $win_pct ) . ' ' . esc_html__( 'won', 'racketmanager' ); ?>%"></div>
							</div>
							<?php
						}
						?>
					</div>
				</div>
			</div>
		</div>
	</div>
