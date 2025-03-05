<?php
/**
 *
 * Template page to club player requests
 *
 * @package Racketmanager/Templates/Admin
 */

namespace Racketmanager;

$clubs = $racketmanager->get_clubs();
?>
<!-- Club Player Request Filter -->
<form id="club-player-request-filter" method="get" action="" class="form-control mb-3">
	<input type="hidden" name="page" value="racketmanager-players" />
	<input type="hidden" name="view" value="requests" />
	<div class="col-auto">
		<select class="select" name="club" id="club">
			<option value="all"><?php esc_html_e( 'All clubs', 'racketmanager' ); ?></option>
			<?php
			foreach ( $clubs as $club ) {
				?>
				<option value="<?php echo esc_html( $club->id ); ?>" <?php echo intval( $club->id ) === $club_id ? 'selected' : ''; ?>><?php echo esc_html( $club->shortcode ); ?></option>
				<?php
			}
			?>
		</select>
		<select class="select" name="status">
			<option value="all" <?php echo 'all' === $status ? 'selected' : ''; ?>><?php esc_html_e( 'All', 'racketmanager' ); ?></option>
			<option value="outstanding" <?php echo 'outstanding' === $status ? 'selected' : ''; ?>><?php esc_html_e( 'Outstanding', 'racketmanager' ); ?></option>
		</select>
		<button class="btn btn-primary"><?php esc_html_e( 'Filter', 'racketmanager' ); ?></button>
	</div>
</form>

<form id="club-player-request-filter" method="post" action="" class="form-control">
	<?php wp_nonce_field( 'club-player-request-bulk' ); ?>

	<div class="mb-3">
		<!-- Bulk Actions -->
		<select name="action" size="1">
			<option value="-1" selected="selected"><?php esc_html_e( 'Bulk Actions', 'racketmanager' ); ?></option>
			<option value="approve"><?php esc_html_e( 'Approve', 'racketmanager' ); ?></option>
			<option value="delete"><?php esc_html_e( 'Delete', 'racketmanager' ); ?></option>
		</select>
		<input type="submit" value="<?php esc_html_e( 'Apply', 'racketmanager' ); ?>" name="doplayerrequest" id="doplayerrequest" class="btn btn-secondary action" />
	</div>
	<table class="table table-striped">
		<thead class="table-dark">
			<tr>
				<th class="check-column"><input type="checkbox" name="checkAll" onclick="Racketmanager.checkAll(document.getElementById('club-player-request-filter'));" /></th>
				<th><?php esc_html_e( 'ID', 'racketmanager' ); ?></th>
				<th><?php esc_html_e( 'Club', 'racketmanager' ); ?></th>
				<th><?php esc_html_e( 'First Name', 'racketmanager' ); ?></th>
				<th><?php esc_html_e( 'Surame', 'racketmanager' ); ?></th>
				<th><?php esc_html_e( 'Gender', 'racketmanager' ); ?></th>
				<th><?php esc_html_e( 'LTA Tennis Number', 'racketmanager' ); ?></th>
				<th><?php esc_html_e( 'Requested', 'racketmanager' ); ?></th>
				<th><?php esc_html_e( 'Completed', 'racketmanager' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			foreach ( $player_requests as $request ) {
				?>
				<tr>
					<td class="check-column"><input type="checkbox" value="<?php echo esc_html( $request->id ); ?>" name="playerRequest[<?php echo esc_html( $request->id ); ?>]" /></<td>
					<td><input type="hidden" id="club_id[<?php echo esc_html( $request->id ); ?>]" name="club_id[<?php echo esc_html( $request->id ); ?>]" value="<?php echo esc_html( $club->id ); ?>"/></<td>
					<td><?php echo esc_html( $request->club->shortcode ); ?></<td>
					<td><?php echo esc_html( $request->player->firstname ); ?></<td>
					<td><?php echo esc_html( $request->player->surname ); ?></<td>
					<td><?php echo esc_html( $request->player->gender ); ?></<td>
					<td><?php echo esc_html( $request->player->btm ); ?></<td>
					<td <?php echo empty( $request->requested_user ) ? null : 'title="' . esc_html__( 'Requested by', 'racketmanager' ) . ' ' . esc_html( $request->requested_user_name ) . '"'; ?>><?php echo esc_html( $request->requested_date ); ?></<td>
					<td <?php echo empty( $request->created_user ) ? null : 'title="' . esc_html__( 'Created by', 'racketmanager' ) . ' ' . esc_html( $request->created_user_name ) . '"'; ?>><?php echo esc_html( $request->created_date ); ?></<td>
				</tr>
				<?php
			}
			?>
		</tbody>
	</table>
</form>
