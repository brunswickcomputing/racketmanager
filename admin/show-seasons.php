<?php
/**
 * Template for admin seasons section
 *
 * @package Racketmanager/Templates/Admin
 */

namespace Racketmanager;

/** @var array $seasons */
?>
<div class="container">

    <h1><?php esc_html_e( 'Seasons', 'racketmanager' ); ?></h1>

    <div class="mb-3">
        <form id="seasons-filter" method="post" action="" class="form-control">
            <?php wp_nonce_field( 'racketmanager_seasons-bulk', 'racketmanager_nonce' ); ?>
            <div class="row g-3 mb-3 align-items-center">
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
                    <button name="doSeasonDel" id="doSeasonDel" class="btn btn-secondary"><?php esc_html_e( 'Apply', 'racketmanager' ); ?></button>
                </div>
            </div>
            <table class="table table-striped">
                <thead class="table-dark">
                    <tr>
                        <th class="check-column"><label for="checkAllSeasons" class="visually-hidden"><?php esc_html_e( 'Check all', 'racketmanager' ); ?></label><input type="checkbox" id="checkAllSeasons" onclick="Racketmanager.checkAll(document.getElementById('seasons-filter'));" /></th>
                        <th class="column-num">ID</th>
                        <th class=""><?php esc_html_e( 'Name', 'racketmanager' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ( $seasons ) {
                        foreach ( $seasons as $season ) {
                            ?>
                            <tr>
                                <td class="check-column">
                                    <label for="season-<?php echo esc_html( $season->id ); ?>" class="visually-hidden"><?php esc_html_e( 'Check', 'racketmanager' ); ?></label><input type="checkbox" value="<?php echo esc_html( $season->id ); ?>" name="season[<?php echo esc_html( $season->id ); ?>]" id="season-<?php echo esc_html( $season->id ); ?>" />
                                </td>
                                <td class="column-num"><?php echo esc_html( $season->id ); ?></td>
                                <td class=""><?php echo esc_html( $season->name ); ?></td>
                            </tr>
                            <?php
                        }
                    }
                    ?>
                </tbody>
            </table>
        </form>
    </div>
    <!-- Add New Season -->
    <h2><?php esc_html_e( 'Add Season', 'racketmanager' ); ?></h2>
    <form action="" method="post" class="form-control">
        <?php wp_nonce_field( 'racketmanager_add-season', 'racketmanager_nonce' ); ?>
        <div class="form-floating mb-3">
            <?php
            $is_invalid = false;
            $msg        = null;
            if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'season', $validator->err_flds, true ) ) ) {
                $is_invalid = true;
                $msg_id     = array_search( 'season', $validator->err_flds, true );
                $msg        = $validator->err_msgs[ $msg_id ] ?? null;
            }
            ?>
            <input class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" placeholder="<?php esc_html_e( 'Enter season name', 'racketmanager' ); ?>" type="text" name="seasonName" id="seasonName" value=""  />
            <label for="seasonName"><?php esc_html_e( 'Name', 'racketmanager' ); ?></label>
            <?php
            if ( $is_invalid ) {
                ?>
                <div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
                <?php
            }
            ?>
        </div>
        <input type="hidden" name="addSeason" value="season" />
        <button type="submit" class="btn btn-primary"><?php esc_html_e( 'Add Season', 'racketmanager' ); ?></button>
    </form>
</div>
