<?php
/**
 * Competition seasons administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

?>
<div>
	<div class="alert_rm" id="alert-season" style="display:none;">
		<div class="alert__body">
			<div class="alert__body-inner" id="alert-season-response">
			</div>
		</div>
	</div>
<div class="container">
	<div class="row justify-content-end">
		<div class="col-auto racketmanager_breadcrumb">
			<a href="admin.php?page=racketmanager-<?php echo esc_html( $competition->type ); ?>s"><?php echo esc_html( ucfirst( $competition->type ) ); ?>s</a> &raquo; <?php echo esc_html( $competition->name ); ?>
		</div>
	</div>
	<div class="row justify-content-between">
		<div class="col-auto">
			<h1><?php echo esc_html( $competition->name ); ?></h1>
		</div>
	</div>
	<form id="seasons-filter" action="" method="post" class="form-control mb-3">
		<?php wp_nonce_field( 'seasons-bulk', 'racketmanager_nonce' ); ?>

		<input type="hidden" name="competition_id" value="<?php echo esc_html( $competition_id ); ?>" />
		<div class="tablenav">
			<!-- Bulk Actions -->
			<select name="action" size="1">
				<option value="-1" selected="selected"><?php esc_html_e( 'Bulk Actions', 'racketmanager' ); ?></option>
				<option value="delete"><?php esc_html_e( 'Delete', 'racketmanager' ); ?></option>
			</select>
			<input type="submit" value="<?php esc_html_e( 'Apply', 'racketmanager' ); ?>" name="doactionseason" id="doactionseason" class="btn btn-secondary action" />
		</div>

		<div class=container>
			<div class="row table-header">
				<div class="col-1 me-1 check-column"><input type="checkbox" id="check-all-seasons" onclick="Racketmanager.checkAll(document.getElementById('seaons-filter'));" /></div>
				<div class="col-3 col-lg-1"><?php esc_html_e( 'Season', 'racketmanager' ); ?></div>
				<div class="col-2 col-lg-1"><?php esc_html_e( 'Match Days', 'racketmanager' ); ?></div>
				<div class="col-2 col-lg-1"><?php esc_html_e( 'Type', 'racketmanager' ); ?></div>
				<div class="col-auto"><?php esc_html_e( 'Status', 'racketmanager' ); ?></div>
			</div>
			<?php
			if ( ! empty( $competition->seasons ) ) {
				$class = '';
				foreach ( array_reverse( $competition->seasons ) as $season ) {
					$class = ( 'alternate' === $class ) ? '' : 'alternate';
					$key   = $season['name'];
					?>
					<div class="row table-row <?php echo esc_html( $class ); ?>">
						<div class="col-1 me-1 check-column"><input type="checkbox" value="<?php echo esc_html( $key ); ?>" name="del_season[<?php echo esc_html( $key ); ?>]" /></div>
						<div class="col-3 col-lg-1"><a href="admin.php?page=racketmanager-cups&amp;view=season&amp;competition_id=<?php echo esc_html( $competition->id ); ?>&amp;season=<?php echo esc_html( $key ); ?>"><?php echo esc_html( $season['name'] ); ?></a></div>
						<div class="col-2 col-lg-1"><?php echo esc_html( $season['num_match_days'] ); ?></div>
						<div class="col-2 col-lg-1">
							<?php
							if ( isset( $season['homeAway'] ) ) {
								if ( $season['homeAway'] ) {
									echo 'both';
								} else {
									echo 'home only';
								}
							}
							?>
						</div>
						<div class="col-auto">
							<?php
							if ( isset( $season['status'] ) ) {
								echo esc_html( $season['status'] );
							}
							?>
						</div>
						<?php
						if ( ! empty( $season['status'] ) && 'live' === $season['status'] ) {
							if ( ! empty( $competition->competition_code ) ) {
								?>
								<div class="col-auto">
									<a href="/index.php?competition_id=<?php echo esc_html( $competition->id ); ?>&season=<?php echo esc_html( $key ); ?>&racketmanager_export=report_results" class="btn btn-info btn-sm" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_html_e( 'Report results', 'racketmanager' ); ?>" >
										<span class="nav-link__value text-uppercase">
											<?php esc_html_e( 'Report results', 'racketmanager' ); ?>
										</span>
									</a>
								</div>
								<?php
							}
						} elseif ( ! empty( $season['closing_date'] ) && ! empty( $season['dateStart'] ) && ! empty( $season['dateStart'] ) ) {
							?>
							<div class="col-auto">
								<button href="" /index.php?competition_id=<?php echo esc_html( $competition->id ); ?>&season=<?php echo esc_html( $key ); ?> class="btn btn-info btn-sm" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_html_e( 'Notify open', 'racketmanager' ); ?>" onclick="Racketmanager.notify_open(event, <?php echo esc_html( $competition->id ) . ',' . esc_html( $key ); ?> )">
									<?php esc_html_e( 'Notify open', 'racketmanager' ); ?>
								</button>
								<span class="notifymessage" id="notifyMessage-<?php echo esc_html( $key ); ?>"></span>
							</div>
							<?php
						}
						?>
					</div>
				<?php } ?>
			<?php } ?>
		</form>
	</div>
	<div class="mb-3">
		<!-- Add New Season -->
		<a href="admin.php?page=racketmanager-cups&amp;view=modify&amp;competition_id=<?php echo esc_attr( $competition->id ); ?>" class="btn btn-primary submit"><?php esc_html_e( 'Add Season', 'racketmanager' ); ?></a>
	</div>
</div>
