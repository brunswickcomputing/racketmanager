<?php
/**
 * Tournament overview administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

?>
	<div class="row mb-3">
		<div class="col-12 col-md-6">
			<table class="table table-borderless">
				<tbody>
					<tr>
						<td class="col-6 col-md-3"><?php esc_html_e( 'Venue', 'racketmanager' ); ?></td>
						<td class="col-6"><?php echo esc_html( $tournament->venue_name ); ?></td>
					</tr>
					<tr>
						<td class="col-6 col-md-3"><?php esc_html_e( 'Events', 'racketmanager' ); ?></td>
						<td class="col-auto"><?php echo esc_html( count( $tournament->events ) ); ?></td>
					</tr>
					<tr>
						<td class="col-6 col-md-3"><?php esc_html_e( 'Entries', 'racketmanager' ); ?></td>
						<td class="col-auto"><?php echo esc_html( $tournament->entries ); ?></td>
					</tr>
				</tbody>
			</table>
		</div>
		<div class="col-12 col-md-6">
			<table class="table table-borderless">
				<tbody>
					<tr>
						<td class="col-6 col-md-3"><?php esc_html_e( 'Entry open', 'racketmanager' ); ?></td>
						<td class="col-auto"><?php echo esc_html( $tournament->date_open ); ?></td>
					</tr>
					<tr>
						<td class="col-6 col-md-3"><?php esc_html_e( 'Entry closed', 'racketmanager' ); ?></td>
						<td class="col-auto"><?php echo esc_html( $tournament->closing_date ); ?></td>
					</tr>
					<tr>
						<td class="col-6 col-md-3"><?php esc_html_e( 'Tournament start', 'racketmanager' ); ?></td>
						<td class="col-auto"><?php echo esc_html( $tournament->date_start ); ?></td>
					</tr>
					<tr>
						<td class="col-6 col-md-3"><?php esc_html_e( 'Tournament end', 'racketmanager' ); ?></td>
						<td class="col-auto"><?php echo esc_html( $tournament->date ); ?></td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
	<div class="row mb-3">
		<div class="col-auto">
			<a role="button" class="btn btn-primary" href="admin.php?page=racketmanager-tournaments&amp;view=modify&amp;tournament=<?php echo esc_html( $tournament->id ); ?> "><?php esc_html_e( 'Edit tournament', 'racketmanager' ); ?></a>
			<?php
			if ( $tournament->is_open ) {
				?>
				<button class="btn btn-secondary" onclick="Racketmanager.notifyTournamentEntryOpen(event, '<?php echo esc_html( $tournament->id ); ?>');"><?php esc_html_e( 'Notify open', 'racketmanager' ); ?></button>
				<?php
			}
			?>
		</div>
	</div>
	<div class="alert_rm" id="alert-tournaments" style="display:none;">
		<div class="alert__body">
			<div class="alert__body-inner" id="alert-tournaments-response">
			</div>
		</div>
	</div>
