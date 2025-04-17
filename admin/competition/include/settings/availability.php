<?php
/**
 * Competition Settings availability administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

?>
<div class="form-control">
	<table class="table table-striped align-middle" aria-describedby="<?php esc_html_e( 'Court availability', 'racketmanager' ); ?>">
		<thead class="table-dark">
			<tr>
				<th scope="row"><?php esc_html_e( 'Club', 'racketmanager' ); ?></th>
				<th scope="row"><?php esc_html_e( 'Number of Courts', 'racketmanager' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			$clubs = $this->get_clubs(
				array(
					'type' => 'affiliated',
				)
			);
			foreach ( $clubs as $club ) {
				?>
				<tr>
					<td><?php echo esc_html( $club->name ); ?></td>
					<td>
                        <label for="num_courts_available-[<?php echo esc_html( $club->id ); ?>]"></label><input type="number" step="1" min="0" class="small-text" name="num_courts_available[<?php echo esc_html( $club->id ); ?>]" id="num_courts_available-[<?php echo esc_html( $club->id ); ?>]" value="<?php echo isset( $competition->config->num_courts_available[ $club->id ] ) ? esc_html( $competition->config->num_courts_available[ $club->id ] ) : null; ?>" />
					</td>
				</tr>
				<?php
			}
			?>
		</tbody>
	</table>
</div>
