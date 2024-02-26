<?php
/**
 * Crosstable template
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

?>
<div class="module module--card">
	<div class="module__banner">
		<h3 class="module__title"><?php esc_html_e( 'Crosstable', 'racketmanager' ); ?></h3>
	</div>
	<div class="module__content">
		<div class="module-container">
			<?php
			if ( have_teams() ) {
				?>
				<?php $rank = 0; ?>
				<div class="table-responsive">
					<table class='table table-striped table-borderless align-middle' aria-describedby='<?php esc_html_e( 'Crosstable', 'racketmanager' ); ?> <?php the_league_title(); ?>'>
						<thead class="">
							<tr>
								<th colspan='2' class="team" scope="col"><?php esc_html_e( 'Club', 'racketmanager' ); ?></th>
								<?php
								$num_teams = get_num_teams_total();
								for ( $i = 1; $i <= $num_teams; $i++ ) {
									?>
									<th class="fixture" scope="col"><?php echo esc_html( $i ); ?></th>
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
								<tr>
									<th scope="row" class="rank"><?php the_team_rank(); ?></th>
									<td><?php the_team_name(); ?></td>
									<?php
									for ( $i = 1; $i <= $num_teams; $i++ ) {
										?>
										<td><?php the_crosstable_field( $i ); ?></td>
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
