<?php
/**
 * Season administration panel
 *
 * @package Racketmanager/Admin
 */

namespace Racketmanager;

?>
<!-- View Seasons -->
<div class="mb-3">
<form id="seasons-filter" method="post" action="" class="form-control">
	<?php wp_nonce_field( 'seasons-bulk', 'racketmanager_nonce' ); ?>
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
                <th class="check-column"><label for="check-all-seasons"></label><input type="checkbox" id="check-all-seasons" onclick="Racketmanager.checkAll(document.getElementById('seasons-filter'));" /></th>
                <th class="column-num">ID</th>
                <th class=""><?php esc_html_e( 'Name', 'racketmanager' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            $seasons = $this->get_seasons();
            if ( $seasons ) {
                foreach ( $seasons as $season ) {
                    ?>
                    <tr>
                        <td class="check-column">
                            <label for="season-<?php echo esc_html( $season->id ); ?>"></label><input type="checkbox" value="<?php echo esc_html( $season->id ); ?>" name="season[<?php echo esc_html( $season->id ); ?>]" id="season-<?php echo esc_html( $season->id ); ?>" />
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
    <?php wp_nonce_field( 'racketmanager_add-season' ); ?>
    <div class="form-floating mb-3">
        <input class="form-control" required="required" placeholder="<?php esc_html_e( 'Enter season name', 'racketmanager' ); ?>" type="text" name="seasonName" id="seasonName" value=""  />
        <label for="seasonName"><?php esc_html_e( 'Name', 'racketmanager' ); ?></label>
    </div>
    <input type="hidden" name="addSeason" value="season" />
    <input type="submit" name="addSeason" value="<?php esc_html_e( 'Add Season', 'racketmanager' ); ?>" class="btn btn-primary" />

</form>
