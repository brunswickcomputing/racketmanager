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
				foreach ( $competition->seasons as $key => $season ) {
					$class = ( 'alternate' === $class ) ? '' : 'alternate'
					?>
					<div class="row table-row <?php echo esc_html( $class ); ?>">
						<div class="col-1 me-1 check-column"><input type="checkbox" value="<?php echo esc_html( $key ); ?>" name="del_season[<?php echo esc_html( $key ); ?>]" /></div>
						<div class="col-3 col-lg-1"><a href="admin.php?page=racketmanager&amp;subpage=show-season&amp;competition_id=<?php echo esc_html( $competition->id ); ?>&amp;season=<?php echo esc_html( $key ); ?>"><?php echo esc_html( $season['name'] ); ?></a></div>
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
						$today = gmdate( 'Y-m-d' );
						if ( ! empty( $season['dateEnd'] ) && $today > $season['dateEnd'] ) {
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
						} elseif ( ! empty( $season['closing_date'] ) && $today <= $season['closing_date'] && ! empty( $season['dateOpen'] ) && $today >= $season['dateOpen'] ) {
							?>
							<div class="col-auto">
								<button href="" /index.php?competition_id=<?php echo esc_html( $competition->id ); ?>&season=<?php echo esc_html( $key ); ?> class="btn btn-secondary btn-sm" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_html_e( 'Notify open', 'racketmanager' ); ?>" onclick="Racketmanager.notify_open(event, <?php echo esc_html( $competition->id ) . ',' . esc_html( $key ); ?> )">
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

	<h3><?php esc_html_e( 'Add Season', 'racketmanager' ); ?></h3>
	<form action="" method="post"  class="form-control">
		<?php wp_nonce_field( 'racketmanager_add-season', 'racketmanager_nonce' ); ?>
		<input type="hidden" name="competition_id" value="<?php echo esc_html( $competition_id ); ?>" />
			<div class="form-floating mb-3">
				<?php if ( $season_id ) { ?>
					<input type="number" class="form-control" name="season" id="season" value="<?php echo esc_html( $season_data['name'] ); ?>" size="4" />
				<?php } else { ?>
					<select size="1" name="season" id="season" class="form-select">
						<option><?php esc_html_e( 'Select season', 'racketmanager' ); ?></option>
						<?php
						$seasons = $this->get_seasons( 'DESC' );
						foreach ( $seasons as $season ) {
							?>
							<option value="<?php echo esc_html( $season->name ); ?>"><?php echo esc_html( $season->name ); ?></option>
						<?php } ?>
					</select>
				<?php } ?>
				<label for="season"><?php esc_html_e( 'Season', 'racketmanager' ); ?></label>
			</div>
			<div class="form-floating mb-3">
				<input type="number" class="form-control" min="1" step="1" class="small-text" name="num_match_days" id="num_match_days" value="<?php echo esc_html( $season_data['num_match_days'] ); ?>" size="2" />
				<label for="num_match_days">
				<?php
				if ( $competition->is_championship ) {
					esc_html_e( 'Number of rounds', 'racketmanager' );
				} else {
					esc_html_e( 'Number of match days', 'racketmanager' );
				}
				?>
				</label>
			</div>
		<input type="hidden" name="season_id" value="<?php echo esc_html( $season_id ); ?>" />
		<input type="submit" name="saveSeason" class="btn btn-primary" value="<?php esc_html_e( 'Add Season', 'racketmanager' ); ?>" />
	</form>
</div>
