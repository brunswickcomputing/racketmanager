<?php
/**
 * Template for Final results
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

?>
<div class="championship-block">
	<div class="container draw">
		<div class="row">
			<?php
			$class = null;
			foreach ( $league->championship->get_finals() as $key => $final ) {
				$class = ( 'alternate' === $class ) ? '' : 'alternate';
				?>
				<div class="finalround <?php echo esc_html( $class ); ?>">
					<div class="roundName">
						<?php echo esc_html( $final['name'] ); ?>
					</div>
					<div class="container roundmatches">
						<?php
						if ( $final['num_matches'] < 4 ) {
							$sm_size = $final['num_matches'];
							$lg_size = $sm_size;
						} else {
							$sm_size = 2;
							$lg_size = 4;
						}

						?>
						<div class="row row-cols-1 row-cols-sm-<?php echo esc_html( $sm_size ); ?> row-cols-lg-<?php echo esc_html( $lg_size ); ?> finalmatches justify-content-center">
							<?php
							$matches = $league->get_matches(
								array(
									'final'   => $final['key'],
									'orderby' => array( 'id' => 'ASC' ),
								)
							);
							foreach ( $matches as $i => $match ) {
								?>
								<div class="finalmatch">
									<div class="row">
										<?php
										if ( isset( $match ) ) {
											$home_class = '';
											$away_class = '';
											$home_tip   = '';
											$away_tip   = '';
											if ( $match->winner_id === $match->teams['home']->id ) {
												$home_class = 'winner';
												$home_tip   = 'Match winner';
											} elseif ( $match->winner_id === $match->teams['away']->id ) {
												$away_class = 'winner';
												$away_tip   = 'Match winner';
											} elseif ( isset( $match->host ) ) {
												if ( 'home' === $match->host ) {
													$home_class = 'host';
													$home_tip   = 'Home team';
												} elseif ( 'away' === $match->host ) {
													$away_class = 'host';
													$away_tip   = 'Home team';
												}
											}
											$home_team = $match->teams['home']->title;
											$away_team = $match->teams['away']->title;
											?>
											<div title="<?php echo esc_html( $home_tip ); ?>" class="col-5 col-sm-5 team team-left <?php echo esc_html( $home_class ); ?>">
												<?php echo esc_html( $home_team ); ?>
											</div>
											<div class="col-2 col-sm-2 score">
												<?php
												if ( null !== $match->home_points && null !== $match->away_points ) {
													$match->score = sprintf( '%d:%d', $match->home_points, $match->away_points );
													?>
													<strong><?php echo esc_html( $match->score ); ?></strong>
												<?php } else { ?>
													-
												<?php } ?>
											</div>
											<div title="<?php echo esc_html( $away_tip ); ?>" class="col-5 col-sm-5 team team-right <?php echo esc_html( $away_class ); ?>">
												<?php echo esc_html( $away_team ); ?>
											</div>
										</div>
									<?php } ?>
								</div>
							<?php } ?>
						</div>
					</div>
				</div>
			<?php } ?>
		</div>
	</div>
</div>
