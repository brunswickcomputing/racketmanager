<?php
/**
 * Admin screen for entries.
 *
 * @package Racketmanager/Templates/Admin
 */

namespace Racketmanager;

/** @var object $competition */
/** @var string $season */
?>
<div class="row">
	<div class="col-12">
		<table class="table table-striped">
			<thead class="table-dark">
				<tr>
					<th><?php esc_html_e( 'Entries', 'racketmanager' ); ?> <?php echo empty( $current_season->entries ) ? null : '(' . count( $current_season->entries ) . ')'; ?></th>
					<th><?php esc_html_e( 'Teams', 'racketmanager' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				if ( empty( $current_season->entries ) ) {
					?>
					<tr><td><?php esc_html_e( 'No entries found', 'racketmanager' ); ?></td></tr>
					<?php
				} else {
					foreach ( $current_season->entries as $club ) {
						?>
						<tr>
							<td><a href="/entry-form/<?php echo esc_attr( seo_url( $competition->name ) ); ?>/<?php echo esc_attr( $season ); ?>/<?php echo esc_attr( seo_url( $club->shortcode ) ); ?>/"><?php echo esc_html( $club->shortcode ); ?></a></td>
							<td><?php echo esc_html( $club->team_count ); ?></td>
						</tr>
						<?php
					}
				}
				?>
			</tbody>
		</table>
	</div>
</div>
