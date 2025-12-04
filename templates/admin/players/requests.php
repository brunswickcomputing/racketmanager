<?php
/**
 *
 * Template page to club player requests
 *
 * @package Racketmanager/Templates/Admin
 */

namespace Racketmanager;

/** @var array $clubs */
/** @var int $club_id */
/** @var string $status */
/** @var array $player_requests */
$tooltip_left = 'data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="';
?>
<!-- Club Player Request Filter -->
<form id="club-player-request-filter" method="get" action="" class="form-control mb-3">
    <input type="hidden" name="page" value="racketmanager-players" />
    <input type="hidden" name="view" value="requests" />
    <div class="col-auto">
        <label for="club" class="visually-hidden"><?php esc_html_e( 'Club selection', 'racketmanager' ); ?></label>
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
        <label for="status" class="visually-hidden"><?php esc_html_e( 'Status selection', 'racketmanager' ); ?></label>
        <select class="select" name="status" id="status">
            <option value="all" <?php echo 'all' === $status ? 'selected' : ''; ?>><?php esc_html_e( 'All', 'racketmanager' ); ?></option>
            <option value="outstanding" <?php echo 'outstanding' === $status ? 'selected' : ''; ?>><?php esc_html_e( 'Outstanding', 'racketmanager' ); ?></option>
        </select>
        <button class="btn btn-primary"><?php esc_html_e( 'Filter', 'racketmanager' ); ?></button>
    </div>
</form>

<form id="club-player-request-filter" method="post" action="" class="form-control">
    <?php wp_nonce_field( 'racketmanager_club-player-request-bulk', 'racketmanager_nonce' ); ?>

    <div class="mb-3">
        <!-- Bulk Actions -->
        <label>
            <select name="action" id="action" class="form-control">
                <option value="-1" selected="selected"><?php esc_html_e( 'Bulk Actions', 'racketmanager' ); ?></option>
                <option value="approve"><?php esc_html_e( 'Approve', 'racketmanager' ); ?></option>
                <option value="delete"><?php esc_html_e( 'Delete', 'racketmanager' ); ?></option>
            </select>
        </label>
        <input type="submit" value="<?php esc_html_e( 'Apply', 'racketmanager' ); ?>" name="doPlayerRequest" id="doPlayerRequest" class="btn btn-secondary action" />
    </div>
    <table class="table table-striped">
        <thead class="table-dark">
            <tr>
                <th class="check-column"><label for="checkALL" class="visually-hidden"><?php esc_html_e( 'Check all', 'racketmanager' ); ?></label><input type="checkbox" name="checkAll" id="checkALL" onclick="Racketmanager.checkAll(document.getElementById('club-player-request-filter'));" /></th>
                <th><?php esc_html_e( 'ID', 'racketmanager' ); ?></th>
                <th><?php esc_html_e( 'Club', 'racketmanager' ); ?></th>
                <th><?php esc_html_e( 'Name', 'racketmanager' ); ?></th>
                <th><?php esc_html_e( 'Gender', 'racketmanager' ); ?></th>
                <th><?php esc_html_e( 'LTA Tennis Number', 'racketmanager' ); ?></th>
                <th><?php esc_html_e( 'Requested', 'racketmanager' ); ?></th>
                <th><?php esc_html_e( 'Completed', 'racketmanager' ); ?></th>
                <th><?php esc_html_e( 'Removed', 'racketmanager' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ( $player_requests as $request ) {
                ?>
                <tr>
                    <td class="check-column"><label for="playerRequest-<?php echo esc_html( $request->registration_id ); ?>" class="visually-hidden"><?php esc_html_e( 'Check', 'racketmanager' ); ?></label><input type="checkbox" value="<?php echo esc_html( $request->registration_id ); ?>" name="playerRequest[<?php echo esc_html( $request->registration_id ); ?>]" id="playerRequest-<?php echo esc_html( $request->registration_id ); ?>" /></td>
                    <td><?php echo esc_html( $request->user_id ); ?></td>
                    <td><?php echo esc_html( $request->club_name ); ?></td>
                    <td><?php echo esc_html( $request->display_name ); ?></td>
                    <td><?php echo esc_html( $request->gender ); ?></td>
                    <td><?php echo esc_html( $request->btm ); ?></td>
                    <?php
                    $tooltip = null;
                    if ( ! empty( $request->registered_by_user ) ) {
                        $tooltip = $tooltip_left . esc_html__( 'Registered by', 'racketmanager' ) . ' ' . esc_html( $request->registered_by_user ) . '"';
                    }
                    ?>
                    <td <?php echo empty( $tooltip ) ? null : $tooltip; ?>><?php echo esc_html( $request->registration_date ); ?></td>
                    <?php
                    $tooltip = null;
                    if ( ! empty( $request->approved_by_user ) ) {
                        $tooltip = $tooltip_left . esc_html__( 'Approved by', 'racketmanager' ) . ' ' . esc_html( $request->approved_by_user ) . '"';
                    }
                    ?>
                    <td <?php echo empty( $tooltip ) ? null : $tooltip; ?>><?php echo esc_html( $request->approval_date ); ?></td>
                    <?php
                    $tooltip = null;
                    if ( ! empty( $request->removed_by_user ) ) {
                        $tooltip = $tooltip_left . esc_html__( 'Removed by', 'racketmanager' ) . ' ' . esc_html( $request->removed_by_user ) . '"';
                    }
                    ?>
                    <td <?php echo empty( $tooltip ) ? null : $tooltip; ?>><?php echo esc_html( $request->removal_date ); ?></td>
                </tr>
                <?php
            }
            ?>
        </tbody>
    </table>
</form>
