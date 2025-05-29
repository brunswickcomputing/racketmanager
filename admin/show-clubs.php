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
                    <button name="doClubDel" id="doClubDel" class="btn btn-secondary"><?php esc_html_e( 'Apply', 'racketmanager' ); ?></button>
                    <button name="doSchedulePlayerRatings" id="doSchedulePlayerRatings" class="btn btn-primary action"><?php esc_html_e( 'Schedule Player Ratings', 'racketmanager' ); ?></button>
                </div>
            </div>
			<div class="container">
				<div class="row table-header">
					<div class="col-1 col-md-1 check-column"><label for="checkAll"></label><input type="checkbox" id="checkAll" onclick="Racketmanager.checkAll(document.getElementById('clubs-filter'));" /></div>
					<div class="d-none d-md-inline col-md-1 column-num">ID</div>
					<div class="col-11 col-md-3"><?php esc_html_e( 'Name', 'racketmanager' ); ?></div>
					<div class="col-12 col-md-3"><?php esc_html_e( 'Match Secretary', 'racketmanager' ); ?></div>
				</div>
				<?php
				$clubs = $this->get_clubs();
				$class = '';
				foreach ( $clubs as $club ) {
					$club  = get_club( $club );
					$class = ( 'alternate' === $class ) ? '' : 'alternate';
					?>
					<div class="row table-row <?php echo esc_html( $class ); ?>">
						<div class="col-1 col-md-1 check-column">
                            <label for="club-<?php echo esc_html( $club->id ); ?>"></label><input type="checkbox" value="<?php echo esc_html( $club->id ); ?>" name="club[<?php echo esc_html( $club->id ); ?>]" id="club-<?php echo esc_html( $club->id ); ?>" />
						</div>
						<div class="d-none d-md-inline col-1 col-md-1 column-num"><?php echo esc_html( $club->id ); ?></div>
						<div class="col-11 col-md-3 club-name"><a href="/wp-admin/admin.php?page=racketmanager&amp;subpage=club&amp;club_id=<?php echo esc_html( $club->id ); ?> "><?php echo esc_html( $club->name ); ?></a></div>
						<div class="d-none d-md-inline col-12 col-md-3"><?php echo esc_html( $club->match_secretary_name ); ?></div>
						<div class="col-auto"><a href="/wp-admin/admin.php?page=racketmanager-clubs&amp;view=players&amp;club_id=<?php echo esc_html( $club->id ); ?> " class="btn btn-secondary"><?php esc_html_e( 'Players', 'racketmanager' ); ?></a></div>
						<div class="col-auto"><a href="/wp-admin/admin.php?page=racketmanager-clubs&amp;view=teams&amp;club_id=<?php echo esc_html( $club->id ); ?> " class="btn btn-secondary"><?php esc_html_e( 'Teams', 'racketmanager' ); ?></a></div>
					</div>
				    <?php
                }
                ?>
    		</div>
        </form>
	</div>
	<div class="mb-3">
		<!-- Add New Club -->
		<a href="/wp-admin/admin.php?page=racketmanager&amp;subpage=club" class="btn btn-primary submit"><?php esc_html_e( 'Add Club', 'racketmanager' ); ?></a>
	</div>
</div>
