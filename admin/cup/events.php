<?php
/**
 * Tournament events administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

?>
	<div class="row">
		<div class="col-12 col-md-6">
			<table class="table table-striped">
				<thead class="table-dark">
					<tr>
						<th><?php esc_html_e( 'Draw', 'racketmanager' ); ?></th>
						<th>
							<?php
								esc_html_e( 'Entries', 'racketmanager' );
							?>
						</th>
						<th>
							<?php
								esc_html_e( 'Draw Size', 'racketmanager' );
							?>
						</th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $competition->events as $event ) {
						$leagues = $event->get_leagues( array( 'season' => $season ) );
						foreach ( $event->leagues as $league ) {
							$league = get_league( $league );
							?>
							<tr>
								<td><a href="admin.php?page=racketmanager-cups&view=draw&competition_id=<?php echo esc_attr( $competition->id ); ?>&league=<?php echo esc_attr( $league->id ); ?>&season=<?php echo esc_attr( $season ); ?>"><?php echo esc_html( $league->title ); ?></div></a></td>
								<td><?php echo esc_html( $league->num_teams_total ); ?></td>
								<td>
									<?php
									if ( $league->is_championship ) {
										echo esc_html( $league->championship->num_teams_first_round );
									} else {
										$league->set_num_matches( true );
										echo esc_html( $league->num_matches_total );
									}
									?>
								</td>
							</tr>
							<?php
						}
					}
					?>
				</tbody>
			</table>
		</div>
	</div>
