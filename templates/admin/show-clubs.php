<?php
/**
 * Clubs main page administration panel
 *
 * @package  Racketmanager/Templates
 */

namespace Racketmanager;

/** @var array $clubs */
?>
<div class="container">
    <h1><?php esc_html_e( 'Clubs', 'racketmanager' ); ?></h1>

    <div class="form-control mb-3">
        <form id="clubs-filter" method="post" action="">
            <?php wp_nonce_field( 'clubs-bulk', 'racketmanager_nonce' ); ?>
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
                    <button name="doClubDel" id="doClubDel" class="btn btn-secondary"><?php esc_html_e( 'Apply', 'racketmanager' ); ?></button>
                    <button name="doSchedulePlayerRatings" id="doSchedulePlayerRatings" class="btn btn-primary action"><?php esc_html_e( 'Schedule Player Ratings', 'racketmanager' ); ?></button>
                </div>
            </div>
            <table class="table table-striped">
                <thead class="table-dark">
                    <tr>
                        <th class="check-column"><label for="checkAll" class="visually-hidden"><?php esc_html_e( 'Check all', 'racketmanager' ); ?></label><input type="checkbox" id="checkAll" onclick="Racketmanager.checkAll(document.getElementById('clubs-filter'));" /></th>
                        <th class="d-none d-md-table-cell column-num">ID</th>
                        <th class=""><?php esc_html_e( 'Name', 'racketmanager' ); ?></th>
                        <th class="d-none d-md-table-cell"><?php esc_html_e( 'Match Secretary', 'racketmanager' ); ?></th>
                        <th class="col-auto"></th>
                        <th class="col-auto"></th>
                        <th class="col-auto"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $class = '';
                    foreach ( $clubs as $club ) {
                        $club  = get_club( $club );
                        $class = ( 'alternate' === $class ) ? '' : 'alternate';
                        ?>
                        <tr class="">
                            <td class="check-column">
                                <label for="club-<?php echo esc_html( $club->id ); ?>" class="visually-hidden"><?php esc_html_e( 'Check', 'racketmanager' ); ?></label><input type="checkbox" value="<?php echo esc_html( $club->id ); ?>" name="club[<?php echo esc_html( $club->id ); ?>]" id="club-<?php echo esc_html( $club->id ); ?>" />
                            </td>
                            <td class="d-none d-md-table-cell column-num"><?php echo esc_html( $club->id ); ?></td>
                            <td class="club-name"><a href="/wp-admin/admin.php?page=racketmanager-clubs&amp;view=club&amp;club_id=<?php echo esc_html( $club->id ); ?> "><?php echo esc_html( $club->name ); ?></a></td>
                            <td class="d-none d-md-table-cell"><?php echo isset( $club->match_secretary->display_name ) ? esc_html( $club->match_secretary->display_name ) : null; ?></td>
                            <td class="col-auto"><a href="/wp-admin/admin.php?page=racketmanager-clubs&amp;view=players&amp;club_id=<?php echo esc_html( $club->id ); ?> " class="btn btn-secondary"><?php esc_html_e( 'Players', 'racketmanager' ); ?></a></td>
                            <td class="col-auto"><a href="/wp-admin/admin.php?page=racketmanager-clubs&amp;view=teams&amp;club_id=<?php echo esc_html( $club->id ); ?> " class="btn btn-secondary"><?php esc_html_e( 'Teams', 'racketmanager' ); ?></a></td>
                            <td class="col-auto"><a href="/wp-admin/admin.php?page=racketmanager-clubs&amp;view=roles&amp;club_id=<?php echo esc_html( $club->id ); ?> " class="btn btn-secondary"><?php esc_html_e( 'Roles', 'racketmanager' ); ?></a></td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </form>
    </div>
    <div class="mb-3">
        <!-- Add New Club -->
        <a href="/wp-admin/admin.php?page=racketmanager-clubs&amp;view=club" class="btn btn-primary submit"><?php esc_html_e( 'Add Club', 'racketmanager' ); ?></a>
    </div>
</div>
