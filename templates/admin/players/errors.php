<?php
/**
 *
 * Template page to player errors
 *
 * @package Racketmanager/Templates/Admin
 */

namespace Racketmanager;

/** @var \Racketmanager\Admin\View_Models\Player_Errors_Page_View_Model $vm */
$player_errors = $vm->player_errors;
$status        = $vm->status;
$errors        = $vm->errors;
?>
<!-- Club Player Request Filter -->
<form method="get" action="<?php echo esc_url( manage_url( 'players', 'errors' ) ); ?>" class="form-control mb-3">
    <?php if ( ! is_manage_portal_context() ) : ?>
        <input type="hidden" name="page" value="racketmanager-players" />
        <input type="hidden" name="view" value="errors" />
    <?php endif; ?>
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
<form id="player-error-filter" method="post" action="<?php echo esc_url( manage_url( 'players', 'errors' ) ); ?>" class="form-control">
    <?php wp_nonce_field( 'racketmanager_player-error-bulk', 'racketmanager_nonce' ); ?>

    <div class="mb-3">
        <!-- Bulk Actions -->
        <label>
            <select name="action" size="1">
                <option value="-1" selected="selected"><?php esc_html_e( 'Bulk Actions', 'racketmanager' ); ?></option>
                <option value="delete"><?php esc_html_e( 'Delete', 'racketmanager' ); ?></option>
            </select>
        </label>
        <input type="submit" value="<?php esc_html_e( 'Apply', 'racketmanager' ); ?>" name="doPlayerErrorBulk" id="doPlayerErrorBulk" class="btn btn-secondary action" />
    </div>

    <div>
        <table class="table table-striped">
            <thead class="table-dark">
            <tr>
                <th><label for="checkAll" class="visually-hidden"><?php esc_html_e( 'Check all', 'racketmanager' ); ?></label><input type="checkbox" name="checkAll" id="checkAll" data-action="check-all" data-target="player-error-filter" /></th>
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
                        <td><label for="playerError-<?php echo esc_html( $player_error->id ); ?>" class="visually-hidden"><?php esc_html_e( 'Check', 'racketmanager' ); ?></label><input type="checkbox" value="<?php echo esc_html( $player_error->id ); ?>" name="playerError[<?php echo esc_html( $player_error->id ); ?>]" id="playerError-<?php echo esc_html( $player_error->id ); ?>" /></td>
                        <td>
                        <a href="<?php echo esc_url( manage_url( 'players', 'player', array( 'player_id' => (string) $player_error->player_id ) ) ); ?>">
                            <?php echo esc_html( $player_error->player_name ); ?>
                        </a>
                        </td>
                        <td><?php echo esc_html( $player_error->btm ); ?></td>
                        <td><?php echo esc_html( $player_error->message ); ?></td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
    </div>
</form>
