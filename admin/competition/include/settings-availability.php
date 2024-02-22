<?php
/**
 * Competition Settings availability administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

?>
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
		foreach ( $clubs as $key => $club ) {
			if ( isset( $competition->num_courts_available[ $club->id ] ) ) {
				$courts = $competition->num_courts_available[ $club->id ];
			} else {
				$courts = '';
			}
			?>
			<tr>
				<td><?php echo esc_html( $club->name ); ?></td>
				<td>
					<input type="number" step="1" min="0" class="small-text" name="settings[num_courts_available][<?php echo esc_html( $club->id ); ?>]" id="numCourtsAvailable[<?php echo esc_html( $club->id ); ?>]" value="<?php echo esc_html( $courts ); ?>" size="2" />
				</td>
			</tr>
		<?php } ?>
	</tbody>
</table>
