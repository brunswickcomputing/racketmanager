<?php
/**
 * Overview administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

/** @var object $competition */
/** @var string $season */
/** @var object $current_season */
$current_season = empty( $cup_season ) ? $current_season : $cup_season;
?>
	<div class="row mb-3">
		<div class="col-12 col-md-6">
			<table class="table table-borderless">
				<tbody>
					<?php
					if ( isset( $current_season->venue ) ) {
						?>
						<tr>
							<td class="col-6 col-md-3"><?php esc_html_e( 'Venue', 'racketmanager' ); ?></td>
							<td class="col-6"><?php echo esc_html( $current_season->venue_name ); ?></td>
						</tr>
						<?php
					}
					?>
					<tr>
						<td class="col-6 col-md-3"><?php esc_html_e( 'Events', 'racketmanager' ); ?></td>
						<td class="col-auto"><?php echo esc_html( count( $competition->events ) ); ?></td>
					</tr>
					<tr>
						<td class="col-6 col-md-3"><?php esc_html_e( 'Entries', 'racketmanager' ); ?></td>
						<td class="col-auto"><?php echo esc_html( count( $current_season->entries ) ); ?></td>
					</tr>
					<tr>
						<td class="col-6 col-md-3"><?php esc_html_e( 'Code', 'racketmanager' ); ?></td>
						<td class="col-auto"><?php echo esc_html( $competition->competition_code ); ?></td>
					</tr>
					<tr>
						<td class="col-6 col-md-3"><?php esc_html_e( 'Grade', 'racketmanager' ); ?></td>
						<td class="col-auto"><?php echo isset( $current_season->grade ) ? esc_html( $current_season->grade ) : null; ?></td>
					</tr>
				</tbody>
			</table>
		</div>
		<div class="col-12 col-md-6">
			<table class="table table-borderless">
				<tbody>
					<tr>
						<td class="col-6 col-md-3"><?php esc_html_e( 'Entry open', 'racketmanager' ); ?></td>
						<td class="col-auto"><?php echo isset( $current_season->date_open ) ? esc_html( $current_season->date_open ) : null; ?></td>
					</tr>
					<tr>
						<td class="col-6 col-md-3"><?php esc_html_e( 'Entry closed', 'racketmanager' ); ?></td>
						<td class="col-auto"><?php echo isset( $current_season->date_closing ) ? esc_html( $current_season->date_closing ) : null; ?></td>
					</tr>
					<tr>
						<td class="col-6 col-md-3"><?php esc_html_e( 'Start', 'racketmanager' ); ?></td>
						<td class="col-auto"><?php echo isset( $current_season->date_start ) ? esc_html( $current_season->date_start ) : null; ?></td>
					</tr>
					<tr>
						<td class="col-6 col-md-3"><?php esc_html_e( 'End', 'racketmanager' ); ?></td>
						<td class="col-auto"><?php echo isset( $current_season->date_end ) ? esc_html( $current_season->date_end ) : null; ?></td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
	<div class="row mb-3">
		<div class="col-auto">
			<a role="button" class="btn btn-primary" href="/wp-admin/admin.php?page=racketmanager-<?php echo esc_attr( $competition->type ); ?>s&amp;view=modify&amp;competition_id=<?php echo esc_html( $competition->id ); ?>&season=<?php echo esc_html( $season ); ?>"><?php esc_html_e( 'Edit season', 'racketmanager' ); ?></a>
			<?php
//			if ( $current_season->is_open ) {
				?>
				<button class="btn btn-secondary" id="notifyOpen" data-competition-id="<?php echo esc_attr( $competition->id ); ?>" data-season="<?php echo esc_attr( $season ); ?>"><?php esc_html_e( 'Notify open', 'racketmanager' ); ?></button>
				<?php
//			}
			if ( ! empty( $competition->competition_code ) && $competition->is_complete ) {
				?>
				<a href="/index.php?competition_id=<?php echo esc_html( $competition->id ); ?>&season=<?php echo esc_html( $season ); ?>&racketmanager_export=report_results" class="btn btn-secondary" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_html_e( 'Report results', 'racketmanager' ); ?>" >
					<span class="nav-link__value">
						<?php esc_html_e( 'Report results', 'racketmanager' ); ?>
					</span>
				</a>
				<?php
			}
			?>
		</div>
	</div>
	<div class="alert_rm" id="alert-season" style="display:none;">
		<div class="alert__body">
			<div class="alert__body-inner" id="alert-season-response">
			</div>
		</div>
	</div>
<script type="text/javascript">
    document.getElementById('notifyOpen').addEventListener('click', function (e) {
        let competitionId = this.dataset.competitionId;
        let season = this.dataset.season;
        Racketmanager.notify_open(e, competitionId, season)
    });
</script>