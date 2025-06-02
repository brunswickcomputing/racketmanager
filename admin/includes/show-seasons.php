<?php
/**
 * Competition seasons administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

/** @var object $competition */
/** @var int $competition_id */
?>
<div class="container">
	<div class="alert_rm" id="alert-season" style="display:none;">
		<div class="alert__body">
			<div class="alert__body-inner" id="alert-season-response">
			</div>
		</div>
	</div>
	<div class="container">
		<div class="row justify-content-end">
			<div class="col-auto racketmanager_breadcrumb">
				<a href="/wp-admin/admin.php?page=racketmanager-<?php echo esc_html( $competition->type ); ?>s"><?php echo esc_html( ucfirst( $competition->type ) ); ?>s</a> &raquo; <?php echo esc_html( $competition->name ); ?>
			</div>
		</div>
		<div class="row justify-content-between">
			<div class="col-auto">
				<h1><?php echo esc_html( $competition->name ); ?></h1>
			</div>
		</div>
		<div class="row mb-3">
			<nav class="navbar navbar-expand-lg bg-body-tertiary">
				<ul class="nav nav-pills">
					<li class="nav-item">
						<div class="nav-link active" href="#" role="tab"><?php esc_html_e( 'Seasons', 'racketmanager' ); ?></div>
					</li>
					<li class="nav-item">
						<a class="nav-link" href="/wp-admin/admin.php?page=racketmanager-<?php echo esc_html( $competition->type ); ?>s&view=config&competition_id=<?php echo esc_attr( $competition->id ); ?>" type="button" role="tab"><?php esc_html_e( 'Configuration', 'racketmanager' ); ?></a>
					</li>
				</ul>
			</nav>
		</div>
	</div>
	<form id="seasons-filter" action="" method="post" class="form-control mb-3">
		<?php wp_nonce_field( 'seasons-bulk', 'racketmanager_nonce' ); ?>
		<input type="hidden" name="competition_id" value="<?php echo esc_html( $competition_id ); ?>" />
        <div class="row gx-3 mb-3 align-items-center">
            <!-- Bulk Actions -->
            <div class="col-auto">
                <label>
                    <select class="form-select" name="action">
                        <option value="-1" selected><?php esc_html_e( 'Bulk Actions', 'racketmanager' ); ?></option>
                        <option value="delete"><?php esc_html_e( 'Delete', 'racketmanager' ); ?></option>
                    </select>
                </label>
            </div>
            <div class="col-auto">
                <button name="doActionSeason" id="doActionSeason" class="btn btn-secondary"><?php esc_html_e( 'Apply', 'racketmanager' ); ?></button>
            </div>
        </div>
		<table class="table table-striped">
			<thead class="table-dark">
				<tr>
					<th class="check-column"><label for="check-all-seasons"></label><input type="checkbox" id="check-all-seasons" onclick="Racketmanager.checkAll(document.getElementById('seasons-filter'));" /></th>
					<th><?php esc_html_e( 'Season', 'racketmanager' ); ?></th>
					<th><?php esc_html_e( 'Start', 'racketmanager' ); ?></th>
					<th><?php esc_html_e( 'End', 'racketmanager' ); ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( array_reverse( $competition->seasons ) as $season ) {
					$key   = $season['name'];
					?>
					<tr>
						<td class="check-column"><label for="del_season-<?php echo esc_html( $key ); ?>"></label><input type="checkbox" value="<?php echo esc_html( $key ); ?>" name="del_season[<?php echo esc_html( $key ); ?>]" id="del_season-<?php echo esc_html( $key ); ?>" /></td>
						<td><a href="/wp-admin/admin.php?page=racketmanager-<?php echo esc_attr( $competition->type ); ?>s&amp;view=overview&amp;competition_id=<?php echo esc_html( $competition->id ); ?>&amp;season=<?php echo esc_html( $key ); ?>"><?php echo esc_html( $season['name'] ); ?></a></td>
						<td><?php echo empty( $season['date_start'] ) ? null : esc_html( $season['date_start'] ); ?></td>
						<td><?php echo empty( $season['date_end'] ) ? null : esc_html( $season['date_end'] ); ?></td>
						<td>
							<?php
							$today = gmdate( 'Y-m-d' );
							if ( ! empty( $season['date_end'] ) && $today > $season['date_end'] ) {
								$competition_code = $season->competition_code ?? null;
								if ( empty( $competition_code ) ) {
									$competition_code = empty( $competition->competition_code ) ? null : $competition->competition_code;
								}
								if ( ! empty( $competition_code ) ) {
									?>
									<a href="/index.php?competition_id=<?php echo esc_html( $competition->id ); ?>&season=<?php echo esc_html( $key ); ?>&competition_code=<?php echo esc_attr( $competition_code ); ?>&racketmanager_export=report_results" class="btn btn-secondary btn-sm" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_html_e( 'Report results', 'racketmanager' ); ?>" >
										<span class="nav-link__value text-uppercase">
											<?php esc_html_e( 'Report results', 'racketmanager' ); ?>
										</span>
									</a>
									<?php
								}
							} elseif ( ! empty( $season['date_closing'] ) && $today <= $season['date_closing'] && ! empty( $season['date_open'] ) && $today >= $season['date_open'] ) {
								?>
								<button class="btn btn-info btn-sm text-uppercase notifyOpen" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_html_e( 'Notify open', 'racketmanager' ); ?>" data-competition-id="<?php echo esc_attr( $competition->id ); ?>" data-season="<?php echo esc_attr( $key ); ?>">
									<?php esc_html_e( 'Notify open', 'racketmanager' ); ?>
								</button>
								<?php
							}
							?>
						</td>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>
	</form>
	<div class="mb-3">
		<!-- Add New Season -->
		<a href="/wp-admin/admin.php?page=racketmanager-<?php echo esc_attr( $competition->type ); ?>s&amp;view=modify&amp;competition_id=<?php echo esc_attr( $competition->id ); ?>" class="btn btn-primary submit"><?php esc_html_e( 'Add Season', 'racketmanager' ); ?></a>
	</div>
</div>
<script type="text/javascript">
    const notifyOpen = document.querySelectorAll('.notifyOpen');
    notifyOpen.forEach(el => el.addEventListener('click', function (e) {
        let competitionId = this.dataset.competitionId;
        let season = this.dataset.season;
        Racketmanager.notify_open(e, competitionId, season)
    }));
</script>
