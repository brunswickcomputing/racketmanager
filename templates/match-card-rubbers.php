<?php
/**
 * Match card template for rubbers
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

$points_span = 2 + intval( $match->league->num_sets );
?>
		<div id="matchrubbers" class="rubber-block">
			<div id="matchheader">
				<div class="leaguetitle"><?php echo esc_html( $match->league->title ); ?></div>
				<div class="matchdate"><?php echo esc_html( substr( $match->date, 0, 10 ) ); ?></div>
				<div class="matchday">
					<?php
					if ( 'championship' === $match->league->mode ) {
						echo esc_html( $match->league->championship->get_final_name( $match->final_round ) );
					} else {
						echo 'Week' . esc_html( $match->match_day );
					}
					?>
				</div>
				<div class="matchtitle"><?php echo esc_html( $match->match_title ); ?></div>
			</div>
			<form id="match-rubbers" action="#" method="post">
				<?php wp_nonce_field( 'rubbers-match' ); ?>

				<table class="widefat" aria-describedby="?php esc_html_e( 'Match card', 'racketmanager' ); ?>">
					<thead>
						<tr>
							<th style="text-align: center;"><?php esc_html_e( 'Pair', 'racketmanager' ); ?></th>
							<th style="text-align: center;" colspan="1"><?php esc_html_e( 'Home Team', 'racketmanager' ); ?></th>
							<th style="text-align: center;" colspan="<?php echo esc_html( $match->league->num_sets ); ?>"><?php esc_html_e( 'Sets', 'racketmanager' ); ?></th>
							<th style="text-align: center;" colspan="1"><?php esc_html_e( 'Away Team', 'racketmanager' ); ?></th>
						</tr>
					</thead>
					<tbody class="rtbody rubber-table" id="the-list-rubbers-<?php echo esc_html( $match->id ); ?>" >

						<?php
						$match->rubbers = $match->get_rubbers();
						$r              = 0;

						foreach ( $match->rubbers as $match->rubber ) {
							?>
							<tr class="rtr">
								<td rowspan="3" class="rtd centered">
									<?php echo isset( $match->rubber->rubber_number ) ? esc_html( $match->rubber->rubber_number ) : ''; ?>
								</td>
								<td class="rtd">
									<input class="player" name="homeplayer1[<?php echo esc_html( $r ); ?>]" id="homeplayer1_<?php echo esc_html( $r ); ?>" />
								</td>

								<?php for ( $i = 1; $i <= $match->league->num_sets; $i++ ) { ?>
									<td rowspan="2" class="rtd">
										<input class="points" type="text" size="2" id="set_<?php echo esc_html( $r ); ?>_<?php echo esc_html( $i ); ?>_player1" name="custom[<?php echo esc_html( $r ); ?>][sets][<?php echo esc_html( $i ); ?>][player1]" />
										:
										<input class="points" type="text" size="2" id="set_<?php echo esc_html( $r ); ?>_<?php echo esc_html( $i ); ?>_player2" name="custom[<?php echo esc_html( $r ); ?>][sets][<?php echo esc_html( $i ); ?>][player2]" />
									</td>
								<?php } ?>

								<td class="rtd">
									<input class="player" name="awayplayer1[<?php echo esc_html( $r ); ?>]" id="awayplayer1_<?php echo esc_html( $r ); ?>" />
								</td>
							</tr>
							<tr class="rtr">
								<td class="rtd">
									<input class="player" name="homeplayer2[<?php echo esc_html( $r ); ?>]" id="homeplayer2_<?php echo esc_html( $r ); ?>" />
								</td>
								<td class="rtd">
									<input class="player" name="awayplayer2[<?php echo esc_html( $r ); ?>]" id="awayplayer2_<?php echo esc_html( $r ); ?>">
								</td>
							</tr>
							<tr>
								<td colspan="<?php echo esc_html( $points_span ); ?>" class="rtd" style="text-align: center;">
									<input class="points" type="text" size="2" id="home_points-<?php echo esc_html( $r ); ?>" name="home_points[<?php echo esc_html( $r ); ?>]" />
									:
									<input class="points" type="text" size="2" id="away_points-<?php echo esc_html( $r ); ?>" name="away_points[<?php echo esc_html( $r ); ?>]" />
								</td>
							</tr>
							<?php
							$r ++;
						}
						?>
						<tr>
							<td class="rtd centered">
							</td>
							<td class="rtd">
								<input class="player" name="homesig" id="homesig" placeholder="Home Captain Signature" />
							</td>
							<td colspan="<?php echo intval( $match->league->num_sets ); ?>" class="rtd" style="text-align: center;">
								<input class="points" type="text" size="2" id="home_points-<?php echo esc_html( $r ); ?>" name="home_points[<?php echo esc_html( $r ); ?>]" />
								:
								<input class="points" type="text" size="2" id="away_points-<?php echo esc_html( $r ); ?>" name="away_points[<?php echo esc_html( $r ); ?>]" />
							</td>
							<td class="rtd">
								<input class="player" name="awaysig" id="awaysig" placeholder="Away Captain Signature" />
							</td>
						</tr>
					</tbody>
				</table>
			</form>
			<?php echo $sponsorhtml; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
