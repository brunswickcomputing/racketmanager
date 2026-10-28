<?php
/**
 * Template for admin seasons section
 *
 * @package Racketmanager/Templates/Admin
 */

namespace Racketmanager;

use Racketmanager\Admin\View_Models\Seasons_Page_View_Model;

$vm      = isset( $vm ) && ( $vm instanceof Seasons_Page_View_Model ) ? $vm : new Seasons_Page_View_Model( $seasons ?? array() );
$seasons = $vm->seasons;
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
                        <th class="check-column"><label for="checkAllSeasons" class="visually-hidden"><?php esc_html_e( 'Check all', 'racketmanager' ); ?></label><input type="checkbox" id="checkAllSeasons" data-action="check-all" data-target="seasons-filter" /></th>
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
                                    <label for="season-<?php echo esc_html( $season->get_id() ); ?>" class="visually-hidden"><?php esc_html_e( 'Check', 'racketmanager' ); ?></label><input type="checkbox" value="<?php echo esc_html( $season->get_id() ); ?>" name="season[<?php echo esc_html( $season->get_id() ); ?>]" id="season-<?php echo esc_html( $season->get_id() ); ?>" />
                                </td>
                                <td class="column-num"><?php echo esc_html( $season->get_id() ); ?></td>
                                <td class=""><?php echo esc_html( $season->get_name() ); ?></td>
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
            $is_invalid  = $vm->errors->has( 'seasonName' );
            $msg         = $vm->errors->message( 'seasonName' );
            $season_name = $vm->season_name ?? '';
            ?>
            <input class="form-control <?php echo $is_invalid ? esc_attr( RACKETMANAGER_IS_INVALID ) : null; ?>" placeholder="<?php esc_html_e( 'Enter season name', 'racketmanager' ); ?>" type="text" name="seasonName" id="seasonName" value="<?php echo esc_attr( $season_name ); ?>"  />
            <label for="seasonName"><?php esc_html_e( 'Name', 'racketmanager' ); ?></label>
            <?php
            if ( $is_invalid ) {
                ?>
                <div class="invalid-feedback"><?php echo esc_html( $msg ?? '' ); ?></div>
                <?php
            }
            ?>
        </div>
        <input type="hidden" name="addSeason" value="season" />
        <button type="submit" class="btn btn-primary"><?php esc_html_e( 'Add Season', 'racketmanager' ); ?></button>
    </form>
</div>
