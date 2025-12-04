<?php
/**
 * Club roles main page administration panel
 *
 * @package Racketmanager/Templates/Admin
 */

namespace Racketmanager;

use Racketmanager\Util\Util_Lookup;

/** @var object $club */
/** @var int    $club_id */
/** @var array  $roles */
/** @var array  $club_players */
?>
<div class="container">
    <div class="row justify-content-end">
        <div class="col-auto racketmanager_breadcrumb">
            <a href="/wp-admin/admin.php?page=racketmanager-clubs"><?php esc_html_e( 'Clubs', 'racketmanager' ); ?></a> &raquo; <?php echo esc_html( $club->shortcode ); ?>  &raquo; <?php esc_html_e( 'Roles', 'racketmanager' ); ?>
        </div>
    </div>
    <h1><?php esc_html_e( 'Roles', 'racketmanager' ); ?> - <?php echo esc_html( $club->name ); ?></h1>

    <!-- View Roles -->
    <div class="mb-3">
        <form id="roles-filter" method="post" action="" class="form-control">
            <?php wp_nonce_field( 'racketmanager_roles-bulk', 'racketmanager_nonce' ); ?>
            <div class="row gx-3 mb-3 align-items-center">
                <!-- Bulk Actions -->
                <div class="col-auto">
                    <label>
                        <select class="form-select" name="action">
                            <option value="-1" selected="selected"><?php esc_html_e( 'Bulk Actions', 'racketmanager' ); ?></option>
                            <option value="delete"><?php esc_html_e( 'Delete', 'racketmanager' ); ?></option>
                        </select>
                    </label>
                </div>
                <div class="col-auto">
                    <button name="delRole" id="delRole" class="btn btn-secondary"><?php esc_html_e( 'Apply', 'racketmanager' ); ?></button>
                </div>
            </div>
            <table class="table table-striped">
                <thead class="table-dark">
                    <tr>
                        <th class="col-1 check-column"><label for="selectAll" class="visually-hidden"><?php esc_html_e( 'Check all', 'racketmanager' ); ?></label><input type="checkbox" name="selectAll" id="selectAll" onclick="Racketmanager.checkAll(document.getElementById('roles-filter'));" /></th>
                        <th class="col-1 column-num">ID</th>
                        <th class="col-3"><?php esc_html_e( 'Role', 'racketmanager' ); ?></th>
                        <th class="col-3"><?php esc_html_e( 'User', 'racketmanager' ); ?></th>
                    </tr>
                </thead>
                <?php
                if ( $roles ) {
                    ?>
                    <tbody>
                        <?php
                        foreach ( $roles as $role_type ) {
                            foreach ( $role_type as $role ) {
                                ?>
                                <tr>
                                    <td class="col-1 check-column">
                                        <label for="role-<?php echo esc_html( $role->id ); ?>" class="visually-hidden"><?php esc_html_e( 'Check', 'racketmanager' ); ?></label><input type="checkbox" value="<?php echo esc_html( $role->id ); ?>" name="role[<?php echo esc_html( $role->id ); ?>]" id="role-<?php echo esc_html( $role->id ); ?>" />
                                    </td>
                                    <td class="col-1 column-num"><?php echo esc_html( $role->id ); ?></td>
                                    <td class="col-3 team-name"><?php echo esc_html( $role->role->desc ); ?></td>
                                    <td class="col-3"><?php echo esc_html( $role->user->display_name ); ?></td>
                                </tr>
                                <?php
                            }
                        }
                        ?>
                    </tbody>
                    <?php
                }
                ?>
            </table>
        </form>
    </div>
    <!-- Add New Role -->
    <div class="mb-3">
        <h3><?php esc_html_e( 'Add Role', 'racketmanager' ); ?></h3>
        <form action="" method="post" class="form-control">
            <?php wp_nonce_field( 'racketmanager_add-club-role', 'racketmanager_nonce' ); ?>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <select class="form-select" size='1' required="required" name='role_id' id='role_id'>
                            <option disabled selected><?php esc_html_e( 'Select role', 'racketmanager' ); ?></option>
                            <?php
                            $club_roles = Util_Lookup::get_club_roles();
                            foreach ( $club_roles as $key => $club_role ) {
                                ?>
                                <option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $club_role->desc ); ?></option>
                                <?php
                            }
                            ?>
                        </select>
                        <label for="role_id"><?php esc_html_e( 'Role', 'racketmanager' ); ?></label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <select class="form-select" size='1' required="required" name='user_id' id='user_id'>
                            <option disabled selected><?php esc_html_e( 'Select user', 'racketmanager' ); ?></option>
                            <?php
                            foreach ( $club_players as $player) {
                                ?>
                                <option value="<?php echo esc_attr( $player->player_id ); ?>"><?php echo esc_html( $player->display_name ); ?></option>
                                <?php
                            }
                            ?>
                        </select>
                        <label for="user_id"><?php esc_html_e( 'User', 'racketmanager' ); ?></label>
                    </div>
                </div>
            </div>
            <input type="hidden" name="club_id" value=<?php echo esc_html( $club->id ); ?> />
            <input type="hidden" name="addRole" value="role" />
            <input type="submit" name="addRole" value="<?php esc_html_e( 'Add Role', 'racketmanager' ); ?>" class="btn btn-primary" />

        </form>
    </div>
</div>
