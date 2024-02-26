<?php
/**
 * Standings table template
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

?>
<div class="module module--card">
	<div class="module__banner">
		<h3 class="module__title"><?php esc_html_e( 'Standings', 'racketmanager' ); ?></h3>
	</div>
	<div class="module__content">
		<div class="module-container">
			<?php
			if ( have_teams() ) {
				?>
				<div class="table-responsive">
					<table class="table table-striped table-borderless align-middle" aria-describedby="<?php esc_html_e( 'Standings table', 'racketmanager' ) . ' ' . get_league_title(); ?>">
						<thead class="">
							<tr>
								<th class="num" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_attr_e( 'Position', 'racketmanager' ); ?>">
									<?php echo esc_html_e( 'Pos', 'racketmanager' ); ?>
								</th>
								<?php
								if ( show_standings( 'status' ) ) {
									?>
									<th class="num d-none d-md-table-cell">
										&#160;
									</th>
									<?php
								}
								?>
								<th class="team">
									<?php esc_html_e( 'Team', 'racketmanager' ); ?>
								</th>
								<?php
								if ( show_standings( 'pld' ) ) {
									?>
									<th class="num" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_attr_e( 'Played', 'racketmanager' ); ?>">
										<?php esc_html_e( 'Pld', 'racketmanager' ); ?>
									</th>
									<?php
								}
								?>
								<?php
								if ( show_standings( 'won' ) ) {
									?>
									<th class="num d-none d-md-table-cell" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_attr_e( 'Won', 'racketmanager' ); ?>">
										<?php esc_html_e( 'W', 'racketmanager' ); ?>
									</th>
									<?php
								}
								?>
								<?php
								if ( show_standings( 'tie' ) ) {
									?>
									<th class="num d-none d-md-table-cell" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_attr_e( 'Drawn', 'racketmanager' ); ?>">
										<?php esc_html_e( 'T', 'racketmanager' ); ?>
									</th>
									<?php
								}
								?>
								<?php
								if ( show_standings( 'lost' ) ) {
									?>
									<th class="num d-none d-md-table-cell" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_attr_e( 'Lost', 'racketmanager' ); ?>">
										<?php esc_html_e( 'L', 'racketmanager' ); ?>
									</th>
									<?php
								}
								?>
								<?php
								if ( show_standings( 'winPercent' ) ) {
									?>
									<th class="num d-none d-md-table-cell" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_attr_e( 'Win Percentage', 'racketmanager' ); ?>">
										<?php esc_html_e( 'PCT', 'racketmanager' ); ?>
									</th>
									<?php
								}
								?>
								<?php
								if ( show_standings( 'sets' ) ) {
									?>
									<th class='num' data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_attr_e( 'Sets', 'racketmanager' ); ?>">
										<?php esc_html_e( 'S', 'racketmanager' ); ?>
									</th>
									<?php
								}
								?>
								<?php
								if ( show_standings( 'games' ) ) {
									?>
									<th class="num" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_attr_e( 'Games', 'racketmanager' ); ?>">
										<?php esc_html_e( 'G', 'racketmanager' ); ?>
									</th>
									<?php
								}
								?>
								<th class="num d-none d-md-table-cell" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_attr_e( 'Adjusted Points', 'racketmanager' ); ?>">
									<?php esc_html_e( '+/-', 'racketmanager' ); ?>
								</th>
								<th class="num" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_attr_e( 'Points', 'racketmanager' ); ?>">
									<?php esc_html_e( 'Pts', 'racketmanager' ); ?>
								</th>
								<?php
								if ( show_standings( 'last5' ) ) {
									?>
									<th class="d-none d-md-table-cell" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_attr_e( 'History', 'racketmanager' ); ?>">
										<?php esc_html_e( 'Last 5', 'racketmanager' ); ?>
									</th>
									<?php
								}
								?>
							</tr>
						</thead>
						<tbody>
							<?php
							while ( have_teams() ) {
								the_team();
								?>
								<tr class="">
									<td class='num'>
										<span class="rank">
											<?php the_team_rank(); ?>
										</span>
									</td>
									<?php
									if ( show_standings( 'status' ) ) {
										?>
										<td class="num d-none d-md-table-cell" title="<?php the_team_status_text(); ?>">
											<i class="racketmanager-svg-icon">
												<?php racketmanager_the_svg( the_team_status_icon() ); ?>
											</i>
										</td>
										<?php
									}
									?>
									<td>
										<a href="/<?php echo esc_attr( $league->event->competition->type ); ?>/<?php echo esc_html( seo_url( $league->title ) ); ?>/<?php echo esc_attr( $league->current_season['name'] ); ?>/team/<?php echo esc_attr( seo_url( $team->title ) ); ?>/">
											<?php the_team_name(); ?>
										</a>
									</td>
									<?php
									if ( show_standings( 'pld' ) ) {
										?>
										<td class='num'>
											<?php num_done_matches(); ?>
										</td>
										<?php
									}
									?>
									<?php
									if ( show_standings( 'won' ) ) {
										?>
										<td class='num d-none d-md-table-cell'>
											<?php num_won_matches(); ?>
										</td>
										<?php
									}
									?>
									<?php
									if ( show_standings( 'tie' ) ) {
										?>
										<td class='num d-none d-md-table-cell'>
											<?php num_draw_matches(); ?>
										</td>
										<?php
									}
									?>
									<?php
									if ( show_standings( 'lost' ) ) {
										?>
										<td class='num d-none d-md-table-cell'>
											<?php num_lost_matches(); ?>
										</td>
										<?php
									}
									?>
									<?php
									if ( show_standings( 'winPercent' ) ) {
										?>
										<td class="num d-none d-md-table-cell">
											<?php win_percentage(); ?>
										</td>
										<?php
									}
									?>
									<?php
									if ( show_standings( 'sets' ) ) {
										?>
										<td class='num'>
											<?php num_sets(); ?>
										</td>
										<?php
									}
									?>
									<?php
									if ( show_standings( 'games' ) ) {
										?>
										<td class='num'>
											<?php num_games(); ?>
										</td>
										<?php
									}
									?>
									<td class='num d-none d-md-table-cell'>
										<?php the_team_points_adjust(); ?>
									</td>
									<td class='num'>
										<?php the_team_points(); ?>
									</td>
									<?php
									if ( show_standings( 'last5' ) ) {
										?>
										<td class="standing-form d-none d-md-table-cell">
											<?php the_last5_matches(); ?>
										</td>
										<?php
									}
									?>
								</tr>
								<?php
							}
							?>
						</tbody>
					</table>
				</div>
				<?php
			}
			?>
		</div>
	</div>
</div>
