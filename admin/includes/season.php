<?php
/**
 * Competition season administration panel
 *
 * @package Racketmanager/Admin
 */

namespace Racketmanager;

?>
<div class="container">
	<div class="row justify-content-end">
		<div class="col-auto racketmanager_breadcrumb">
			<?php
			if ( empty( $event ) ) {
				?>
				<a href="admin.php?page=racketmanager"><?php esc_html_e( 'RacketManager', 'racketmanager' ); ?></a> &raquo; <a href="admin.php?page=racketmanager&amp;subpage=show-competition&competition_id=<?php echo esc_html( $competition->id ); ?>"><?php echo esc_html( $competition->name ); ?></a> &raquo; <?php esc_html_e( 'Season', 'racketmanager' ); ?>
				<?php
			} else {
				?>
				<a href="admin.php?page=racketmanager"><?php esc_html_e( 'RacketManager', 'racketmanager' ); ?></a> &raquo; <a href="admin.php?page=racketmanager&amp;subpage=show-competition&amp;competition_id=<?php echo esc_html( $event->competition->id ); ?>&amp;season=<?php echo esc_html( $season_id ); ?>"><?php echo esc_html( $event->competition->name ); ?></a> &raquo; <a href="admin.php?page=racketmanager&amp;subpage=show-event&event_id=<?php echo esc_html( $event->id ); ?>&amp;season=<?php echo esc_html( $season_id ); ?>"><?php echo esc_html( $event->name ); ?></a> &raquo; <?php esc_html_e( 'Season', 'racketmanager' ); ?>
				<?php
			}
			?>
		</div>
	</div>
	<?php
	if ( ! empty( $event->is_box ) ) {
		$link_title   = __( 'Round', 'racketmanager' );
		$button_title = __( 'Update round', 'racketmanager' );
	} else {
		$link_title   = __( 'Season', 'racketmanager' );
		$button_title = __( 'Update season', 'racketmanager' );
	}
	?>
	<h1><?php echo esc_html( __( 'Update', 'racketmanager' ) ) . ' ' . esc_html( $object->name ) . ' - ' . esc_html( $link_title ) . ' ' . esc_html( $season_id ); ?></h1>
	<form action="" method="post"  class="form-control mb-3">
		<?php wp_nonce_field( 'racketmanager_update-season', 'racketmanager_nonce' ); ?>
		<?php
		if ( empty( $event->is_box ) || ! $event->is_box ) {
			?>
			<div class="form-floating mb-3">
				<input type="number" class="form-control" min="1" step="1" name="num_match_days" id="num_match_days" value="<?php echo esc_html( $season_data['num_match_days'] ); ?>" size="2" />
				<label for="num_match_days">
				<?php
				if ( $object->is_championship ) {
					esc_html_e( 'Number of rounds', 'racketmanager' );
				} else {
					esc_html_e( 'Number of match days', 'racketmanager' );
				}
				?>
				</label>
			</div>
			<?php
		}
		?>
		<?php
		if ( empty( $event ) ) {
			?>
			<div class="form-floating mb-3">
				<input type="text" class="form-control" name="competition_code" id="competition_code" value="<?php echo empty( $season_data['competition_code'] ) ? '' : esc_html( $season_data['competition_code'] ); ?>" />
				<label for="competition_code">
					<?php esc_html_e( 'Competition code', 'racketmanager' ); ?>
				</label>
			</div>
			<?php
		}
		?>
		<div class="form-control mb-3">
			<fieldset class="mb-1">
				<legend class="form-check-label"><?php esc_html_e( 'Status', 'racketmanager' ); ?></legend>
				<div class="form-check form-check-inline">
					<input type="radio" class="form-check-input" name="status" id="statusLive" value="live"
					<?php
					if ( isset( $season_data['status'] ) ) {
						echo ( 'live' === $season_data['status'] ) ? ' checked' : '';
					} else {
						echo ' checked';
					}
					?>
					/>
					<label class="form-check-label" for="statusLive"><?php esc_html_e( 'Live', 'racketmanager' ); ?></label>
				</div>
				<div class="form-check form-check-inline">
					<input type="radio" class="form-check-input" name="status" id="statusDraft" value="draft"
					<?php
					if ( isset( $season_data['status'] ) ) {
						echo ( 'draft' === $season_data['status'] ) ? ' checked' : '';
					}
					?>
					/>
					<label class="form-check-label" for="statusDraft"><?php esc_html_e( 'Draft', 'racketmanager' ); ?></label>
				</div>
				<?php
				if ( ! empty( $event ) && 'league' === $object->competition->type ) {
					?>
					<div class="fst-italic">
						<?php esc_html_e( 'Setting the status to live will cause the constitution to be confirmed', 'racketmanager' ); ?>
					</div>
					<?php
				}
				?>
			</fieldset>
		</div>
		<?php
		if ( isset( $event ) && ( ! empty( $event->is_box ) || $event->is_box ) ) {
			?>
			<div class="form-floating mb-3">
				<select name="homeAway" id="homeAway" class="form-select">
					<option disabled <?php echo isset( $season_data['homeAway'] ) ? '' : 'selected'; ?>><?php esc_html_e( 'Choose format', 'racketmanager' ); ?></option>
					<option value="false"<?php isset( $season_data['homeAway'] ) ? selected( 'false', $season_data['homeAway'] ) : ''; ?>><?php esc_html_e( 'Round Robin', 'racketmanager' ); ?></option>
					<option value="true"<?php isset( $season_data['homeAway'] ) ? selected( 'true', $season_data['homeAway'] ) : ''; ?>><?php echo esc_html__( 'Round Robin', 'racketmanager' ) . ' - ' . esc_html__( 'Home and Away', 'racketmanager' ); ?></option>
				</select>
				<label for="homeAway"><?php esc_html_e( 'Format', 'racketmanager' ); ?></label>
			</div>
			<div class="row g-3">
				<div class="col">
					<div class="form-floating mb-3">
						<input type="date" class="form-control" name="matchDate[0]" id="matchDate-0" value="<?php echo esc_html( $season_data['matchDates'][0] ); ?>"/>
						<label for="matchDate-0"><?php esc_html_e( 'Start Date', 'racketmanager' ); ?></label>
					</div>
				</div>
				<div class="col">
					<div class="form-floating mb-3">
						<input type="date" class="form-control" name="matchDate[1]" id="matchDate-1" value="<?php echo esc_html( $season_data['matchDates'][1] ); ?>"/>
						<label for="matchDate-1"><?php esc_html_e( 'End Date', 'racketmanager' ); ?></label>
					</div>
				</div>
			</div>
			<div class="form-floating mb-3">
				<input type="date" class="form-control" name="date_closing" id="date_closing" value="<?php echo esc_html( $season_data['closing_date'] ); ?>"/>
				<label for="date_closing"><?php esc_html_e( 'Closing Date', 'racketmanager' ); ?></label>
			</div>
			<?php
		} else {
			?>
			<?php
			if ( empty( $event ) ) {
				?>
				<div class="form-floating mb-3">
					<input type="date" class="form-control" name="date_closing" id="date_closing" value="<?php echo isset( $season_data['closing_date'] ) ? esc_html( $season_data['date_closing'] ) : ''; ?>" size="2" />
					<label for="date_closing">
						<?php esc_html_e( 'Closing date', 'racketmanager' ); ?>
					</label>
				</div>
				<?php
			}
			?>
			<div class="form-control mb-3">
				<fieldset class="mb-1">
					<legend class="form-check-label"><?php esc_html_e( 'Fixtures', 'racketmanager' ); ?></legend>
					<div class="form-check form-check-inline">
						<input type="radio" class="form-check-input" name="homeAway" id="homeAwayTrue" value="true"
						<?php
						if ( isset( $season_data['homeAway'] ) ) {
							echo ( 'true' === $season_data['homeAway'] ) ? ' checked' : '';
						}
						?>
						/>
						<label class="form-check-label" for="homeAwayTrue"><?php esc_html_e( 'Home and Away', 'racketmanager' ); ?></label>
					</div>
				</fieldset>
				<div class="form-check form-check-inline">
					<input type="radio" class="form-check-input" name="homeAway" id="homeAwayFalse" value="false"
					<?php
					if ( isset( $season_data['homeAway'] ) ) {
						echo ( 'false' === $season_data['homeAway'] ) ? ' checked' : '';
					}
					?>
					/>
					<label class="form-check-label" for="homeAwayFalse"><?php esc_html_e( 'Home only', 'racketmanager' ); ?></label>
				</div>
			</div>
			<?php
			for ( $i = 0; $i < $season_data['num_match_days']; $i++ ) {
				?>
				<div class="form-floating mb-3">
					<?php
					$match_day = $i + 1;
					if ( isset( $season_data['matchDates'][ $i ] ) ) {
						$form_mode = 'update';
					} else {
						$form_mode = 'add';
					}
					?>
					<input type="date" class="form-control" name="matchDate[<?php echo esc_html( $i ); ?>]" id="matchDate-<?php echo esc_html( $i ); ?>" value="<?php echo isset( $season_data['matchDates'][ $i ] ) ? esc_html( $season_data['matchDates'][ $i ] ) : ''; ?>" onChange="Racketmanager.setMatchDays(this.value, <?php echo esc_html( $i ); ?>, <?php echo esc_html( $season_data['num_match_days'] ); ?>, '<?php echo esc_html( $form_mode ); ?>');" />
					<label for="matchDate-<?php echo esc_html( $i ); ?>"><?php echo esc_html( __( 'Match Day', 'racketmanager' ) ) . ' ' . esc_html( $match_day ); ?></label>
				</div>
				<?php
			}
			?>
			<?php
		}
		?>
		<?php
		if ( empty( $event ) ) {
			?>
			<input type="hidden" name="competitionId" value="<?php echo esc_html( $object->id ); ?>" />
			<?php
		} else {
			?>
			<input type="hidden" name="eventId" value="<?php echo esc_html( $object->id ); ?>" />
			<?php
		}
		$is_box = empty( $event->is_box ) ? 0 : $event->is_box;
		?>
		<input type="hidden" name="is_box" value="<?php echo esc_html( $is_box ); ?>" />
		<input type="hidden" name="seasonId" value="<?php echo esc_html( $season_id ); ?>" />
		<input type="submit" name="saveSeason" class="btn btn-primary mb-3" value="<?php echo esc_html( $button_title ); ?>" />
	</form>
</div>
