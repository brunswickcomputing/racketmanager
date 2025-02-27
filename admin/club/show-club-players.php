<?php
/**
 * Club Players main page administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

?>
<div class="container">
	<div class="row justify-content-end">
		<div class="col-auto racketmanager_breadcrumb">
			<a href="admin.php?page=racketmanager-clubs"><?php esc_html_e( 'Clubs', 'racketmanager' ); ?></a> &raquo; <?php echo esc_html( $club->shortcode ); ?>  &raquo;  <?php esc_html_e( 'Players', 'racketmanager' ); ?>
		</div>
	</div>
	<h1><?php esc_html_e( 'Players', 'racketmanager' ); ?> - <?php echo esc_html( $club->name ); ?></h1>

	<!-- Add player -->
	<div class="mb-3">
		<!-- Add Player -->
		<h2><?php esc_html_e( 'Add Player', 'racketmanager' ); ?></h2>
		<?php require_once RACKETMANAGER_PATH . '/admin/includes/player.php'; ?>
	</div>

	<div class="mb-3">
		<h2><?php esc_html_e( 'View Players', 'racketmanager' ); ?></h2>
		<form id="players-filter" method="get" action="" class="form-control mb-3">
			<input type="hidden" name="page" value="<?php echo esc_html( 'racketmanager-clubs' ); ?>" />
			<input type="hidden" name="view" value="<?php echo esc_html( 'players' ); ?>" />
			<input type="hidden" name="club_id" value="<?php echo esc_html( $club->id ); ?>" />
			<select class="" name="active" id="active">
				<option value="" <?php echo ( '' === $active ) ? 'selected' : ''; ?>><?php esc_html_e( 'All players', 'racketmanager' ); ?></option>
				<option value="true" <?php echo ( 'true' === $active ) ? 'selected' : ''; ?>><?php esc_html_e( 'Active', 'racketmanager' ); ?></option>
			</select>
			<select class="" name="gender" id="gender">
				<option value="" <?php echo ( '' === $gender ) ? 'selected' : ''; ?>><?php esc_html_e( 'All genders', 'racketmanager' ); ?></option>
				<option value="F" <?php echo ( 'F' === $gender ) ? 'selected' : ''; ?>><?php esc_html_e( 'Female', 'racketmanager' ); ?></option>
				<option value="M" <?php echo ( 'M' === $gender ) ? 'selected' : ''; ?>><?php esc_html_e( 'Male', 'racketmanager' ); ?></option>
			</select>
			<button class="btn btn-secondary"><?php esc_html_e( 'Filter', 'racketmanager' ); ?></button>
		</form>
		<form id="players-action" method="post" action="" class="form-control">
			<?php wp_nonce_field( 'club-players-bulk' ); ?>

			<div class="tablenav">
				<!-- Bulk Actions -->
				<select name="action" size="1">
					<option value="-1" selected="selected"><?php esc_html_e( 'Bulk Actions', 'racketmanager' ); ?></option>
					<option value="delete"><?php esc_html_e( 'Remove', 'racketmanager' ); ?></option>
				</select>
				<input type="hidden" name="club_id" value="<?php echo esc_html( $club->id ); ?>" />
				<input type="submit" value="<?php esc_html_e( 'Apply', 'racketmanager' ); ?>" name="doClubPlayerdel" id="doClubPlayerdel" class="btn btn-primary action" />
				<input type="submit" value="<?php esc_html_e( 'Player Ratings', 'racketmanager' ); ?>" name="doPlayerRatings" id="doPlayerRatings" class="btn btn-primary action" />
			</div>

			<div class="container">
				<div class="row table-header">
					<div class="col-1 col-md-1 check-column"><input type="checkbox" onclick="Racketmanager.checkAll(document.getElementById('players-action'));" /></div>
					<div class="col-4 col-md-2"><?php esc_html_e( 'Name', 'racketmanager' ); ?></div>
					<div class="col-4 col-md-2"><?php esc_html_e( 'Rating', 'racketmanager' ); ?></div>
					<div class="col-2 col-md-1"><?php esc_html_e( 'LTA Tennis Number', 'racketmanager' ); ?></div>
				</div>
				<?php
				if ( $club_id ) {
					$club = get_club( $club_id );
					if ( $players ) {
						$class = '';
						foreach ( $players as $player ) {
							$class = ( 'alternate' === $class ) ? '' : 'alternate';
							?>
							<div class="row table-row <?php echo esc_html( $class ); ?>">
								<div class="col-1 col-md-1 check-column">
									<?php
									if ( ! isset( $player->removed_date ) ) {
										?>
										<input type="checkbox" value="<?php echo esc_html( $player->roster_id ); ?>" name="clubPlayer[<?php echo esc_html( $player->roster_id ); ?>]" />
										<?php
									}
									?>
								</div>
								<div class="col-4 col-md-2">
									<?php
									if ( ! isset( $player->removed_date ) ) {
										echo '<a href="admin.php?page=racketmanager-clubs&amp;view=player&amp;club_id=' . esc_html( $club->id ) . '&amp;player_id=' . esc_html( $player->player_id ) . '">';
									}
									echo esc_html( $player->fullname );
									if ( ! isset( $player->removed_date ) ) {
										echo '</a>';
									}
									?>
								</div>
								<div class="col-4 col-md-2">
									<?php
									$rating         = $player->rating;
									$match_types    = Racketmanager_Util::get_match_types();
									$rating_display = '';
									foreach ( $match_types as $match_type => $description ) {
										if ( ! empty( $rating_display ) ) {
											$rating_display .= ' - ';
										}
										$rating_display .= '[' . $rating[ $match_type ] . ']';
									}
									echo ' ' . esc_html( $rating_display );
									?>
									<?php
									echo '<br>';
									$wtn            = $player->wtn;
									$wtn_display = '';
									foreach ( $match_types as $match_type => $description ) {
										if ( ! empty( $wtn_display ) ) {
											$wtn_display .= ' - ';
										}
										$wtn_display .= '[' . $wtn[ $match_type ] . ']';
									}
									echo ' ' . esc_html( $wtn_display );
									?>
								</div>
								<div class="col-2 col-md-1"><?php echo esc_html( $player->btm ); ?></div>
							</div>
							<?php
						}
						?>
						<?php
					}
					?>
					<?php
				}
				?>
			</div>
		</form>
	</div>
</div>
