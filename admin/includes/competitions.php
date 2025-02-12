<?php
/**
 * Competition matches administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

$age_group_select               = isset( $_GET['age_group'] ) ? sanitize_text_field( wp_unslash( $_GET['age_group'] ) ) : '';
$competition_query['age_group'] = isset( $age_group_select ) ? $age_group_select : null;
$orderby['age_group']           = 'ASC';
$orderby['type']                = 'ASC';
$orderby['name']                = 'ASC';
$competition_query['orderby']   = $orderby;
$competitions                   = $this->get_competitions( $competition_query );
$age_groups                     = Racketmanager_Util::get_age_groups();
$page_name                      = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : 'racketmanager';
?>
<div class="container">
	<div class="row justify-content-between mb-3">
		<form id="competitions-list-filter" method="get" action="" class="form-control">
			<input type="hidden" name="page" value="<?php echo esc_attr( $page_name ); ?>" />
			<div class="col-auto">
				<select class="form-select-1" name="age_group" id="age_group">
					<option value=""><?php esc_html_e( 'All age groups', 'racketmanager' ); ?></option>
					<?php
					foreach ( $age_groups as $age_group => $age_group_desc ) {
						?>
						<option value="<?php echo esc_attr( $age_group ); ?>" <?php selected( $age_group, $age_group_select ); ?>><?php echo esc_html( $age_group_desc ); ?></option>
						<?php
					}
					?>
				</select>
				<button class="btn btn-primary"><?php esc_html_e( 'Filter', 'racketmanager' ); ?></button>
			</div>
		</form>
	</div>
</div>
<div class="form-control" id="competitions">
	<form id="competitions-filter" method="post" action="">
		<?php wp_nonce_field( 'competitions-bulk' ); ?>
		<div class="tablenav">
			<!-- Bulk Actions -->
			<select name="action" size="1">
				<option value="-1" selected disabled><?php esc_html_e( 'Bulk Actions', 'racketmanager' ); ?></option>
				<option value="delete"><?php esc_html_e( 'Delete', 'racketmanager' ); ?></option>
			</select>
			<input type="submit" value="<?php esc_html_e( 'Apply', 'racketmanager' ); ?>" name="docompdel" id="docompdel" class="btn btn-secondary action" />
		</div>
		<table class="table table-striped">
			<thead class="table-dark">
				<tr>
					<th class="check-column"><input type="checkbox" id="check-all-competitions" onclick="Racketmanager.checkAll(document.getElementById('competitions-filter'));" /></div>
					<th class="d-none d-md-table-cell">ID</th>
					<th class=""><?php esc_html_e( 'Competition', 'racketmanager' ); ?></th>
					<th class="centered"><?php esc_html_e( 'Age Group', 'racketmanager' ); ?></th>
					<th class="centered"><?php esc_html_e( 'Type', 'racketmanager' ); ?></th>
					<th class="d-none d-md-table-cell text-center"><?php esc_html_e( 'Number of Seasons', 'racketmanager' ); ?></th>
					<th class="d-none d-md-table-cell text-center"><?php esc_html_e( 'Events', 'racketmanager' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( $competitions as $competition ) {
					?>
					<tr>
						<td class="check-column">
							<input type="checkbox" value="<?php echo esc_html( $competition->id ); ?>" name="competition[<?php echo esc_html( $competition->id ); ?>]" />
						</div>
						<td class="d-none d-md-table-cell">
							<?php echo esc_html( $competition->id ); ?>
						</td>
						<td class="">
							<a href="admin.php?page=racketmanager-<?php echo esc_attr( $competition->type ); ?>s&amp;view=config&amp;competition_id=<?php echo esc_html( $competition->id ); ?>">
								<?php echo esc_html( $competition->name ); ?>
							</a>
						</td>
						<td class="centered">
							<?php echo esc_html( ucfirst( $competition->age_group ) ); ?>
						</td>
						<td class="centered">
							<?php echo esc_html( ucfirst( $competition->type ) ); ?>
						</td>
						<td class="d-none d-md-table-cell text-center">
							<?php echo esc_html( $competition->num_seasons ); ?>
						</td>
						<td class="d-none d-md-table-cell text-center">
							<?php echo esc_html( $competition->num_events ); ?>
						</td>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>
	</form>
</div>
