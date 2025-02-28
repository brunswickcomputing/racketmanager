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
	<input type="hidden" name="page" value="<?php echo 'racketmanager-players'; ?>" />
	<input type="hidden" name="tab" value="<?php echo 'playerrequest'; ?>" />
	<div class="col-auto">
		<select class="select" name="club" id="club">
			<option value="all"><?php esc_html_e( 'All clubs', 'racketmanager' ); ?></option>
			<?php
			foreach ( $clubs as $club ) {
				?>
				<option value="<?php echo esc_html( $club->id ); ?>" <?php echo $club->id === $club_id ? 'selected' : ''; ?>><?php echo esc_html( $club->shortcode ); ?></option>
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

	<div class="container">
		<div class="row table-header">
			<div class="col-1 check-column"><input type="checkbox" name="checkAll" onclick="Racketmanager.checkAll(document.getElementById('club-player-request-filter'));" /></div>
			<div class="col-1 column-num">ID</div>
			<div class="col-2"><?php esc_html_e( 'Club', 'racketmanager' ); ?></div>
			<div class="col-1"><?php esc_html_e( 'First Name', 'racketmanager' ); ?></div>
			<div class="col-1"><?php esc_html_e( 'Surame', 'racketmanager' ); ?></div>
			<div class="col-1"><?php esc_html_e( 'Gender', 'racketmanager' ); ?></div>
			<div class="col-1"><?php esc_html_e( 'LTA Tennis Number', 'racketmanager' ); ?></div>
			<div class="col-auto"><?php esc_html_e( 'Requested', 'racketmanager' ); ?></div>
			<div class="col-auto"><?php esc_html_e( 'Completed', 'racketmanager' ); ?></div>
		</div>
		<?php
		foreach ( $player_requests as $player_request ) {
			?>
			<div class="row table-row <?php echo esc_html( $player_request->class ); ?>">
				<div class="col-1 check-column">
					<input type="checkbox" value="<?php echo esc_html( $player_request->id ); ?>" name="playerRequest[<?php echo esc_html( $player_request->id ); ?>]" />
				</div>
				<div class="col-1 column-num"><?php echo esc_html( $player_request->id ); ?><input type="hidden" id="club_id[<?php echo esc_html( $player_request->id ); ?>]" name="club_id[<?php echo esc_html( $player_request->id ); ?>]" value="<?php echo esc_html( $club->id ); ?>"/></div>
				<div class="col-2"><?php echo esc_html( $player_request->club_name ); ?></div>
				<div class="col-1"><?php echo esc_html( $player_request->first_name ); ?></div>
				<div class="col-1"><?php echo esc_html( $player_request->surname ); ?></div>
				<div class="col-1"><?php echo esc_html( $player_request->gender ); ?></div>
				<div class="col-1"><?php echo esc_html( $player_request->btm ); ?></div>
				<div class="col-auto" title="<?php echo esc_html( $player_request->requested_user ); ?>"><?php echo esc_html( $player_request->requested_date ); ?></div>
				<div class="col-auto" 
				<?php
				if ( ! empty( $player_request->completed_user ) ) {
					echo 'title="' . esc_html__( 'Created by', 'racketmanager' ) . ' ' . esc_html( $player_request->completed_user ) . '"';  }
				?>
				"><?php echo esc_html( $player_request->completed_date ); ?></div>
			</div>
			<?php
		}
		?>
	</div>
</form>
