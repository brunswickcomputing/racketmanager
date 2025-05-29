<?php
/**
 * Clubs main page administration panel
 *
 * @package  Racketmanager/Templates
 */

namespace Racketmanager;

?>
<div class="container">
	<h1><?php esc_html_e( 'Clubs', 'racketmanager' ); ?></h1>

	<div class="form-control mb-3">
		<form id="teams-filter" method="post" action="">
			<?php wp_nonce_field( 'clubs-bulk' ); ?>
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
                        <td class="check-column"><label for="checkAll"></label><input type="checkbox" id="checkAll" onclick="Racketmanager.checkAll(document.getElementById('clubs-filter'));" /></td>
                        <td class="d-none d-md-table-cell column-num">ID</td>
                        <td class=""><?php esc_html_e( 'Name', 'racketmanager' ); ?></td>
                        <td class="d-none d-md-table-cell"><?php esc_html_e( 'Match Secretary', 'racketmanager' ); ?></td>
                        <td class="col-auto"></td>
                        <td class="col-auto"></td>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $clubs = $this->get_clubs();
                    $class = '';
                    foreach ( $clubs as $club ) {
                        $club  = get_club( $club );
                        $class = ( 'alternate' === $class ) ? '' : 'alternate';
                        ?>
                        <tr class="">
                            <td class="check-column">
                                <label for="club-<?php echo esc_html( $club->id ); ?>"></label><input type="checkbox" value="<?php echo esc_html( $club->id ); ?>" name="club[<?php echo esc_html( $club->id ); ?>]" id="club-<?php echo esc_html( $club->id ); ?>" />
                            </td>
                            <td class="d-none d-md-table-cell column-num"><?php echo esc_html( $club->id ); ?></td>
                            <td class="club-name"><a href="/wp-admin/admin.php?page=racketmanager&amp;subpage=club&amp;club_id=<?php echo esc_html( $club->id ); ?> "><?php echo esc_html( $club->name ); ?></a></td>
                            <td class="d-none d-md-table-cell"><?php echo esc_html( $club->match_secretary_name ); ?></td>
                            <td class="col-auto"><a href="/wp-admin/admin.php?page=racketmanager-clubs&amp;view=players&amp;club_id=<?php echo esc_html( $club->id ); ?> " class="btn btn-secondary"><?php esc_html_e( 'Players', 'racketmanager' ); ?></a></td>
                            <td class="col-auto"><a href="/wp-admin/admin.php?page=racketmanager-clubs&amp;view=teams&amp;club_id=<?php echo esc_html( $club->id ); ?> " class="btn btn-secondary"><?php esc_html_e( 'Teams', 'racketmanager' ); ?></a></td>
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
		<a href="/wp-admin/admin.php?page=racketmanager&amp;subpage=club" class="btn btn-primary submit"><?php esc_html_e( 'Add Club', 'racketmanager' ); ?></a>
	</div>
</div>
