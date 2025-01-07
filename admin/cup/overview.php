<?php
/**
 * Cup overview administration panel
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
						<td class="col-6"><?php echo esc_html( $cup_season->venue_name ); ?></td>
					</tr>
					<tr>
						<td class="col-6 col-md-3"><?php esc_html_e( 'Events', 'racketmanager' ); ?></td>
						<td class="col-auto"><?php echo esc_html( count( $competition->events ) ); ?></td>
					</tr>
					<tr>
						<td class="col-6 col-md-3"><?php esc_html_e( 'Entries', 'racketmanager' ); ?></td>
						<td class="col-auto"><?php echo esc_html( count( $cup_season->entries ) ); ?></td>
					</tr>
					<tr>
						<td class="col-6 col-md-3"><?php esc_html_e( 'Code', 'racketmanager' ); ?></td>
						<td class="col-auto"><?php echo esc_html( $competition->competition_code ); ?></td>
					</tr>
					<tr>
						<td class="col-6 col-md-3"><?php esc_html_e( 'Grade', 'racketmanager' ); ?></td>
						<td class="col-auto"><?php echo isset( $cup_season->grade ) ? esc_html( $cup_season->grade ) : null; ?></td>
					</tr>
				</tbody>
			</table>
		</div>
		<div class="col-12 col-md-6">
			<table class="table table-borderless">
				<tbody>
					<tr>
						<td class="col-6 col-md-3"><?php esc_html_e( 'Entry open', 'racketmanager' ); ?></td>
						<td class="col-auto"><?php echo isset( $cup_season->dateOpen ) ? esc_html( $cup_season->dateOpen ) : null; ?></td>
					</tr>
					<tr>
						<td class="col-6 col-md-3"><?php esc_html_e( 'Entry closed', 'racketmanager' ); ?></td>
						<td class="col-auto"><?php echo isset( $cup_season->closing_date ) ? esc_html( $cup_season->closing_date ) : null; ?></td>
					</tr>
					<tr>
						<td class="col-6 col-md-3"><?php esc_html_e( 'Start', 'racketmanager' ); ?></td>
						<td class="col-auto"><?php echo isset( $cup_season->dateStart ) ? esc_html( $cup_season->dateStart ) : null; ?></td>
					</tr>
					<tr>
						<td class="col-6 col-md-3"><?php esc_html_e( 'End', 'racketmanager' ); ?></td>
						<td class="col-auto"><?php echo isset( $cup_season->dateEnd ) ? esc_html( $cup_season->dateEnd ) : null; ?></td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
	<div class="row mb-3">
		<div class="col-auto">
			<a role="button" class="btn btn-primary" href="admin.php?page=racketmanager-cups&amp;view=modify&amp;competition_id=<?php echo esc_html( $competition->id ); ?>&season=<?php echo esc_html( $season ); ?>"><?php esc_html_e( 'Edit cup season', 'racketmanager' ); ?></a>
			<?php
			if ( $cup_season->is_open ) {
				?>
				<button class="btn btn-secondary" onclick="Racketmanager.notifyCupEntryOpen(event, '<?php echo esc_html( $competition->id ); ?>');"><?php esc_html_e( 'Notify open', 'racketmanager' ); ?></button>
				<?php
			}
			if ( ! empty( $competition->competition_code ) && $competition->is_complete ) {
				?>
				<a href="/index.php?competition_id=<?php echo esc_html( $competition->id ); ?>&season=<?php echo esc_html( $season ); ?>&racketmanager_export=report_results" class="btn btn-info" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_html_e( 'Report results', 'racketmanager' ); ?>" >
					<span class="nav-link__value">
						<?php esc_html_e( 'Report results', 'racketmanager' ); ?>
					</span>
				</a>
				<?php
			}
			?>
		</div>
	</div>
	<div class="alert_rm" id="alert-cups" style="display:none;">
		<div class="alert__body">
			<div class="alert__body-inner" id="alert-cups-response">
			</div>
		</div>
	</div>
