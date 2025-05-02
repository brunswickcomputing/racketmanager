<?php
/**
 * Admin screen for cup entries.
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
					<th><?php esc_html_e( 'Entries', 'racketmanager' ); ?> <?php echo empty( $cup_season->entries ) ? null : '(' . count( $cup_season->entries ) . ')'; ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				if ( empty( $cup_season->entries ) ) {
					?>
					<tr><td><?php esc_html_e( 'No entries found', 'racketmanager' ); ?></td></tr>
					<?php
				} else {
					foreach ( $cup_season->entries as $club ) {
						?>
						<tr>
							<td><a href="/entry-form/<?php echo esc_attr( seo_url( $competition->name ) ); ?>/<?php echo esc_attr( $season ); ?>/<?php echo esc_attr( seo_url( $club->shortcode ) ); ?>/"><?php echo esc_html( $club->shortcode ); ?></a></td>
						</tr>
						<?php
					}
				}
				?>
			</tbody>
		</table>
	</div>
</div>
