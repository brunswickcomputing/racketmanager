<?php
/**
 *
 * Template page to player errors
 *
 * @package Racketmanager/Templates/Admin
 */

namespace Racketmanager;

/** @var string $status */
/** @var array $player_errors */
?>
<!-- Club Player Request Filter -->
<form method="get" action="" class="form-control mb-3">
	<input type="hidden" name="page" value="racketmanager-players" />
	<input type="hidden" name="view" value="errors" />
	<div class="col-auto">
        <label>
            <select class="select" name="status" id="status">
                <option value="all" <?php echo 'all' === $status ? 'selected' : ''; ?>><?php esc_html_e( 'All', 'racketmanager' ); ?></option>
                <option value="no_player" <?php echo 'no_player' === $status ? 'selected' : ''; ?>><?php esc_html_e( 'No player', 'racketmanager' ); ?></option>
                <option value="no_wtn" <?php echo 'no_wtn' === $status ? 'selected' : ''; ?>><?php esc_html_e( 'No WTN', 'racketmanager' ); ?></option>
            </select>
        </label>
		<button class="btn btn-primary"><?php esc_html_e( 'Filter', 'racketmanager' ); ?></button>
	</div>
</form>
<form id="player-error-filter" method="post" action="" class="form-control">
	<?php wp_nonce_field( 'club-player-request-bulk' ); ?>

	<div class="mb-3">
		<!-- Bulk Actions -->
        <label>
            <select name="action" size="1">
                <option value="-1" selected="selected"><?php esc_html_e( 'Bulk Actions', 'racketmanager' ); ?></option>
                <option value="approve"><?php esc_html_e( 'Approve', 'racketmanager' ); ?></option>
                <option value="delete"><?php esc_html_e( 'Delete', 'racketmanager' ); ?></option>
            </select>
        </label>
        <input type="submit" value="<?php esc_html_e( 'Apply', 'racketmanager' ); ?>" name="doPlayerRequest" id="doPlayerRequest" class="btn btn-secondary action" />
	</div>

	<div>
		<table class="table table-striped">
			<thead class="table-dark">
			<tr>
				<th><label for="checkAll"></label><input type="checkbox" name="checkAll" id="checkAll" onclick="Racketmanager.checkAll(document.getElementById('player-error-filter'));" /></th>
				<th><?php esc_html_e( 'Name', 'racketmanager' ); ?></th>
				<th><?php esc_html_e( 'LTA Tennis number', 'racketmanager' ); ?></th>
				<th><?php esc_html_e( 'Message', 'racketmanager' ); ?></th>
			</tr>
			</thead>
			<tbody>
				<?php
				foreach ( $player_errors as $player_error ) {
					?>
					<tr>
						<td><label for="playerRequest-<?php echo esc_html( $player_error->id ); ?>"></label><input type="checkbox" value="<?php echo esc_html( $player_error->id ); ?>" name="playerRequest[<?php echo esc_html( $player_error->id ); ?>]" id="playerRequest-<?php echo esc_html( $player_error->id ); ?>" /></td>
						<td>
						<a href="/wp-admin/admin.php?page=racketmanager-players&amp;view=player&amp;player_id=<?php echo esc_attr( $player_error->player_id ); ?>">
							<?php echo esc_html( $player_error->player->display_name ); ?>
						</a>
						</td>
						<td><?php echo esc_html( $player_error->player->btm ); ?></td>
						<td><?php echo esc_html( $player_error->message ); ?></td>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>
	</div>
</form>
