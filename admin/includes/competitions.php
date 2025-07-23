<?php
/**
 * Competition matches administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

global $racketmanager;
$age_group_select               = isset( $_GET['age_group'] ) ? sanitize_text_field( wp_unslash( $_GET['age_group'] ) ) : '';
$competition_query['age_group'] = $age_group_select ?? null;
$orderby['age_group']           = 'ASC';
$orderby['type']                = 'ASC';
$orderby['name']                = 'ASC';
$competition_query['orderby']   = $orderby;
$competitions                   = $racketmanager->get_competitions( $competition_query );
$age_groups                     = Util::get_age_groups();
$page_name                      = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : 'racketmanager';
?>
<div class="container">
    <div class="row justify-content-between mb-3">
        <form id="competitions-list-filter" method="get" action="" class="form-control">
            <input type="hidden" name="page" value="<?php echo esc_attr( $page_name ); ?>" />
            <div class="col-auto">
                <label>
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
                </label>
                <button class="btn btn-primary"><?php esc_html_e( 'Filter', 'racketmanager' ); ?></button>
            </div>
        </form>
    </div>
</div>
<div class="form-control" id="competitions">
    <form id="competitions-filter" method="post" action="">
        <?php wp_nonce_field( 'racketmanager_competitions-bulk', 'racketmanager_nonce' ); ?>
        <div class="row gx-3 mb-3 align-items-center">
            <!-- Bulk Actions -->
            <div class="col-auto">
                <label>
                    <select class="form-select" name="action" id="action">
                        <option value="-1" selected="selected"><?php esc_html_e( 'Bulk Actions', 'racketmanager' ); ?></option>
                        <option value="delete"><?php esc_html_e( 'Delete', 'racketmanager' ); ?></option>
                    </select>
                </label>
            </div>
            <div class="col-auto">
                <button name="doCompDel" id="doCompDel" class="btn btn-secondary"><?php esc_html_e( 'Apply', 'racketmanager' ); ?></button>
            </div>
        </div>
        <table class="table table-striped">
            <thead class="table-dark">
                <tr>
                    <th class="check-column"><label for="check-all-competitions" class="visually-hidden"><?php esc_html_e( 'Check all', 'racketmanager' ); ?>></label><input type="checkbox" id="check-all-competitions" onclick="Racketmanager.checkAll(document.getElementById('competitions-filter'));" /></th>
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
                    if ( 'tournament' === $competition->type ) {
                        $page_link = 'config';
                    } else {
                        $page_link = 'seasons';
                    }
                    ?>
                    <tr>
                        <td class="check-column">
                            <label for="competition-<?php echo esc_html( $competition->id ); ?>" class="visually-hidden"><?php esc_html_e( 'Check', 'racketmanager' ); ?></label><input type="checkbox" value="<?php echo esc_html( $competition->id ); ?>" name="competition[<?php echo esc_html( $competition->id ); ?>]" id="competition-<?php echo esc_html( $competition->id ); ?>" />
                        </td>
                        <td class="d-none d-md-table-cell">
                            <?php echo esc_html( $competition->id ); ?>
                        </td>
                        <td class="">
                            <a href="/wp-admin/admin.php?page=racketmanager-<?php echo esc_attr( $competition->type ); ?>s&amp;view=<?php echo esc_attr( $page_link ); ?>&amp;competition_id=<?php echo esc_html( $competition->id ); ?>">
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
