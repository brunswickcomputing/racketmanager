<?php
/**
 * Competition matches administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

use Racketmanager\Admin\View_Models\Index_Page_View_Model;
use Racketmanager\Util\Util_Lookup;

if ( isset( $vm ) && $vm instanceof Index_Page_View_Model ) {
    $competitions     = $vm->competitions;
    $age_groups       = $vm->age_groups;
    $age_group_select = $vm->age_group_select;
    $page_name        = $vm->page_name;
} else {
    /** @var string $competition_type */
    $age_group_select = isset( $_GET['age_group'] ) ? sanitize_text_field( wp_unslash( $_GET['age_group'] ) ) : '';
    $competitions     = $this->competition_service->find_competitions_with_summary( $age_group_select, $competition_type ?? null );
    $age_groups       = Util_Lookup::get_age_groups();
    $page_name        = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : 'racketmanager';
}
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
                    $comp_id           = is_object( $competition ) && method_exists( $competition, 'get_id' ) ? $competition->get_id() : ( is_object( $competition ) ? ( $competition->id ?? 0 ) : ( $competition['id'] ?? 0 ) );
                    $comp_name         = is_object( $competition ) && method_exists( $competition, 'get_name' ) ? $competition->get_name() : ( is_object( $competition ) ? ( $competition->name ?? '' ) : ( $competition['name'] ?? '' ) );
                    $comp_type         = is_object( $competition ) && method_exists( $competition, 'get_type' ) ? $competition->get_type() : ( is_object( $competition ) ? ( $competition->type ?? '' ) : ( $competition['type'] ?? '' ) );
                    $comp_age_group    = is_object( $competition ) && method_exists( $competition, 'get_age_group' ) ? $competition->get_age_group() : ( is_object( $competition ) ? ( $competition->age_group ?? null ) : ( $competition['age_group'] ?? null ) );
                    $comp_season_count = is_object( $competition ) && method_exists( $competition, 'get_season_count' ) ? $competition->get_season_count() : ( is_object( $competition ) ? ( $competition->season_count ?? 0 ) : ( $competition['season_count'] ?? 0 ) );
                    $comp_event_count  = is_object( $competition ) && method_exists( $competition, 'get_event_count' ) ? $competition->get_event_count() : ( is_object( $competition ) ? ( $competition->event_count ?? 0 ) : ( $competition['event_count'] ?? 0 ) );

                    if ( 'tournament' === $comp_type ) {
                        $page_link = 'config';
                    } else {
                        $page_link = 'seasons';
                    }
                    ?>
                    <tr>
                        <td class="check-column">
                            <label for="competition-<?php echo esc_html( (string) $comp_id ); ?>" class="visually-hidden"><?php esc_html_e( 'Check', 'racketmanager' ); ?></label><input type="checkbox" value="<?php echo esc_html( (string) $comp_id ); ?>" name="competition[<?php echo esc_html( (string) $comp_id ); ?>]" id="competition-<?php echo esc_html( (string) $comp_id ); ?>" />
                        </td>
                        <td class="d-none d-md-table-cell">
                            <?php echo esc_html( (string) $comp_id ); ?>
                        </td>
                        <td class="">
                            <a href="/wp-admin/admin.php?page=racketmanager-<?php echo esc_attr( (string) $comp_type ); ?>s&amp;view=<?php echo esc_attr( $page_link ); ?>&amp;competition_id=<?php echo esc_html( (string) $comp_id ); ?>">
                                <?php echo esc_html( (string) $comp_name ); ?>
                            </a>
                        </td>
                        <td class="centered">
                            <?php echo esc_html( null !== $comp_age_group && '' !== $comp_age_group ? ucfirst( (string) $comp_age_group ) : '' ); ?>
                        </td>
                        <td class="centered">
                            <?php echo esc_html( '' !== (string) $comp_type ? ucfirst( (string) $comp_type ) : '' ); ?>
                        </td>
                        <td class="d-none d-md-table-cell text-center">
                            <?php echo esc_html( (string) $comp_season_count ); ?>
                        </td>
                        <td class="d-none d-md-table-cell text-center">
                            <?php echo esc_html( (string) $comp_event_count ); ?>
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
    </form>
</div>
