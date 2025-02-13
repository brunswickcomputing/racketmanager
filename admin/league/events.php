<?php
/**
 *
 * League events administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

?>
	<div class="row">
		<div class="col-12">
			<table class="table table-striped">
				<thead class="table-dark">
					<tr>
						<th><?php esc_html_e( 'Event', 'racketmanager' ); ?></th>
						<th><?php esc_html_e( 'Type', 'racketmanager' ); ?></th>
						<th><?php esc_html_e( 'Age', 'racketmanager' ); ?></th>
						<th><?php esc_html_e( 'Entries', 'racketmanager' ); ?></th>
						<th><?php esc_html_e( 'Leagues', 'racketmanager' ); ?></th>
						<th><?php esc_html_e( 'Teams', 'racketmanager' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $competition->events as $event ) {
						$args['count']  = true;
						$args['season'] = $season;
						$args['status'] = 1;
						$num_leagues    = $event->get_leagues( $args );
						$num_teams      = $event->get_teams( $args );
						$num_entries    = $event->get_clubs( $args );
						$age_limit      = empty( $event->age_limit ) ? 0 : $event->age_limit;
						$age_offset     = null;
						if ( is_numeric( $age_limit ) ) {
							if ( isset( $event->age_offset ) ) {
								$age_offset = is_numeric( $event->age_offset ) ? $age_limit - intval( $event->age_offset ) : null;
							}
							if ( empty( $age_offset ) ) {
								$age_offset = null;
							} else {
								$age_offset = '(' . $age_offset . ')';
							}
						}
						?>
						<tr>
							<td><a href="admin.php?page=racketmanager-<?php echo esc_attr( $competition->type ); ?>s&view=event&competition_id=<?php echo esc_attr( $competition->id ); ?>&event_id=<?php echo esc_attr( $event->id ); ?>&season=<?php echo esc_attr( $season ); ?>"><?php echo esc_html( $event->name ); ?></div></a></td>
							<td><?php echo esc_html( Racketmanager_Util::get_event_type( $event->type ) ); ?></td>
							<td><?php echo esc_html( Racketmanager_Util::get_age_limit( $age_limit ) ) . esc_html( $age_offset ); ?></td>
							<td><?php echo esc_html( $num_entries ); ?></td>
							<td><?php echo esc_html( $num_leagues ); ?></td>
							<td><?php echo esc_html( $num_teams ); ?></td>
						</tr>
						<?php
					}
					?>
				</tbody>
			</table>
		</div>
	</div>
