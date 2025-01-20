<?php
/**
 * Cup season administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

$is_invalid = false;
$breadcrumb = '<a href="admin.php?page=racketmanager-' . $competition->type . 's">' . ucfirst( $competition->type ) . 's</a> &raquo; <a href="admin.php?page=racketmanager-' . $competition->type . 's&amp;view=seasons&amp;competition_id=' . $competition->id . '">' . $competition->name . '</a> &raquo; ';
if ( empty( $cup_season->name ) ) {
	$add_season    = true;
	$modify_season = false;
	$action_form   = 'admin.php?page=racketmanager-cups&amp;view=modify&amp;competition_id=' . $competition->id;
	$action_text   = __( 'Add season', 'racketmanager' );
} else {
	$add_season    = false;
	$modify_season = true;
	$action_form   = 'admin.php?page=racketmanager-cups&amp;view=modify&amp;competition_id=' . $competition->id . '&amp;season=' . $cup_season->name;
	$action_text   = __( 'Modify season', 'racketmanager' );
	$breadcrumb   .= '<a href="admin.php?page=racketmanager-' . $competition->type . 's&amp;view=season&amp;competition_id=' . $competition->id . '&amp;season=' . $season . '">' . $season . '</a> &raquo; ';
}
$breadcrumb .= $action_text;
?>
<div class='container'>
	<div class='row justify-content-end'>
		<div class='col-auto racketmanager_breadcrumb'>
			<?php echo $breadcrumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	</div>
	<h1><?php echo esc_html( $form_title ); ?></h1>
	<form action="<?php echo esc_html( $action_form ); ?>" method='post' enctype='multipart/form-data' name='season_edit'>
		<?php
		if ( $modify_season ) {
			wp_nonce_field( 'racketmanager_manage-season' );
		} else {
			wp_nonce_field( 'racketmanager_add-season' );
		}
		?>
		<div class="form-control mb-3">
			<legend><?php esc_html_e( 'Details', 'racketmanager' ); ?></legend>
			<div class="row g-3">
				<div class="col-md-6">
					<div class="form-floating mb-3">
						<?php
						if ( ! empty( $racketmanager->error_fields ) && is_numeric( array_search( 'season', $racketmanager->error_fields, true ) ) ) {
							$is_invalid = true;
							$msg_id     = array_search( 'season', $racketmanager->error_fields, true );
							$msg        = isset( $racketmanager->error_messages[ $msg_id ] ) ? $racketmanager->error_messages[ $msg_id ] : null;
						}
						if ( $modify_season ) {
							?>
							<input class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="season" id="season" readonly value="<?php echo esc_attr( $cup_season->name ); ?>" />
							<?php
						} else {
							?>
							<select class="form-select <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="season" id="season">
								<option disabled <?php selected( null, empty( $cup_season->name ) ? null : $cup_season->name ); ?>><?php esc_html_e( 'Select season', 'racketmanager' ); ?></option>
								<?php
								$seasons = $this->get_seasons( 'DESC' );
								foreach ( $seasons as $season_option ) {
									?>
									<option value="<?php echo esc_html( $season_option->name ); ?>" <?php selected( $season_option->name, isset( $cup_season->name ) ? $cup_season->name : '' ); ?> <?php disabled( isset( $competition->seasons[ $season_option->name ] ) ); ?>><?php echo esc_html( $season_option->name ); ?></option>
								<?php } ?>
							</select>
							<?php
						}
						?>
						<label for="season" class="form-label"><?php esc_html_e( 'Season', 'racketmanager' ); ?></label>
						<?php
						if ( $is_invalid ) {
							?>
							<div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
							<?php
							$is_invalid = false;
							$msg        = null;
						}
						?>
					</div>
				</div>
			</div>
			<div class="row g-3">
				<div class="col-md-6">
					<div class="form-floating mb-3">
						<?php
						if ( ! empty( $racketmanager->error_fields ) && is_numeric( array_search( 'venue', $racketmanager->error_fields, true ) ) ) {
							$is_invalid = true;
							$msg_id     = array_search( 'venue', $racketmanager->error_fields, true );
							$msg        = isset( $racketmanager->error_messages[ $msg_id ] ) ? $racketmanager->error_messages[ $msg_id ] : null;
						}
						?>
						<select class="form-select <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="venue" id="venue" >
							<option disabled <?php selected( null, empty( $cup_season->venue ) ? null : $cup_season->venue ); ?>><?php esc_html_e( 'Select venue', 'racketmanager' ); ?></option>
							<?php
							foreach ( $clubs as $club ) {
								?>
								<option value="<?php echo esc_html( $club->id ); ?>" <?php selected( $club->id, empty( $cup_season->venue ) ? null : $cup_season->venue ); ?>><?php echo esc_html( $club->name ); ?></option>
								<?php
							}
							?>
						</select>
						<label for="venue" class="form-label"><?php esc_html_e( 'Venue', 'racketmanager' ); ?></label>
						<?php
						if ( $is_invalid ) {
							?>
							<div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
							<?php
							$is_invalid = false;
							$msg        = null;
						}
						?>
					</div>
				</div>
				<div class="col-md-6">
					<div class="form-floating mb-3">
						<?php
						if ( ! empty( $racketmanager->error_fields ) && is_numeric( array_search( 'competition_code', $racketmanager->error_fields, true ) ) ) {
							$is_invalid = true;
							$msg_id     = array_search( 'competition_code', $racketmanager->error_fields, true );
							$msg        = isset( $racketmanager->error_messages[ $msg_id ] ) ? $racketmanager->error_messages[ $msg_id ] : null;
						}
						?>
						<input type="text" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="competition_code" id="competition_code" value="<?php echo isset( $cup_season->competition_code ) ? esc_html( $cup_season->competition_code ) : null; ?>" />
						<label for="competition_code" class="form-label"><?php esc_html_e( 'Competition code', 'racketmanager' ); ?></label>
						<?php
						if ( $is_invalid ) {
							?>
							<div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
							<?php
							$is_invalid = false;
							$msg        = null;
						}
						?>
					</div>
				</div>
			</div>
		</div>
		<div class="form-control mb-3">
			<legend><?php esc_html_e( 'Configuration', 'racketmanager' ); ?></legend>
			<div class="row mb-3">
				<div class="col-md-4">
					<div class="form-floating mb-3">
						<?php
						$grades = Racketmanager_Util::get_event_grades();
						if ( ! empty( $racketmanager->error_fields ) && is_numeric( array_search( 'grade', $racketmanager->error_fields, true ) ) ) {
							$is_invalid = true;
							$msg_id     = array_search( 'grade', $racketmanager->error_fields, true );
							$msg        = isset( $racketmanager->error_messages[ $msg_id ] ) ? $racketmanager->error_messages[ $msg_id ] : null;
						}
						?>
						<select class="form-select <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" size="1" name="grade" id="grade" >
							<option disabled <?php selected( null, empty( $cup_season->grade ) ? null : $cup_season->grade ); ?>><?php esc_html_e( 'Select grade', 'racketmanager' ); ?></option>
							<?php
							foreach ( $grades as $grade => $grade_desc ) {
								?>
								<option value="<?php echo esc_html( $grade ); ?>" <?php selected( $grade, empty( $cup_season->grade ) ? null : $cup_season->grade ); ?>><?php echo esc_html__( 'Grade', 'racketmanager' ) . ' ' . esc_html( $grade ); ?></option>
								<?php
							}
							?>
						</select>
						<label for="grade" class="form-label"><?php esc_html_e( 'Grade', 'racketmanager' ); ?></label>
						<?php
						if ( $is_invalid ) {
							?>
							<div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
							<?php
							$is_invalid = false;
							$msg        = null;
						}
						?>
					</div>
				</div>
				<div class="col-md-4">
					<?php
					if ( ! empty( $racketmanager->error_fields ) && is_numeric( array_search( 'fixedMatchDates', $racketmanager->error_fields, true ) ) ) {
						$is_invalid = true;
						$msg_id     = array_search( 'fixedMatchDates', $racketmanager->error_fields, true );
						$msg        = isset( $racketmanager->error_messages[ $msg_id ] ) ? $racketmanager->error_messages[ $msg_id ] : null;
					}
					?>
					<legend class="form-check-label <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>"><?php esc_html_e( 'Fixed match dates', 'racketmanager' ); ?></legend>
					<div class="form-check form-check-inline">
						<input type="radio" class="form-check-input <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="fixedMatchDates" id="fixedMatchDatesTrue" value="true"
						<?php
						if ( isset( $cup_season->fixed_match_dates ) ) {
							echo ( true === $cup_season->fixed_match_dates ) ? ' checked' : '';
						}
						?>
						/>
						<label class="form-check-label" for="fixedMatchDatesTrue"><?php esc_html_e( 'True', 'racketmanager' ); ?></label>
					</div>
					<div class="form-check form-check-inline">
						<input type="radio" class="form-check-input <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="fixedMatchDates" id="fixedMatchDatesFalse" value="false"
						<?php
						if ( isset( $cup_season->fixed_match_dates ) ) {
							echo ( false === $cup_season->fixed_match_dates ) ? ' checked' : '';
						}
						?>
						/>
						<label class="form-check-label" for="fixedMatchDatesFalse"><?php esc_html_e( 'False', 'racketmanager' ); ?></label>
					</div>
					<?php
					if ( $is_invalid ) {
						?>
						<div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
						<?php
						$is_invalid = false;
						$msg        = null;
					}
					?>
				</div>
				<div class="col-md-4">
					<?php
					if ( ! empty( $racketmanager->error_fields ) && is_numeric( array_search( 'homeAway', $racketmanager->error_fields, true ) ) ) {
						$is_invalid = true;
						$msg_id     = array_search( 'homeAway', $racketmanager->error_fields, true );
						$msg        = isset( $racketmanager->error_messages[ $msg_id ] ) ? $racketmanager->error_messages[ $msg_id ] : null;
					}
					?>
					<legend class="form-check-label <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>"><?php esc_html_e( 'Fixtures', 'racketmanager' ); ?></legend>
					<div class="form-check form-check-inline">
						<input type="radio" class="form-check-input <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="homeAway" id="homeAwayTrue" value="true"
						<?php
						if ( isset( $cup_season->home_away ) ) {
							echo ( true === $cup_season->home_away ) ? ' checked' : '';
						}
						?>
						/>
						<label class="form-check-label" for="homeAwayTrue"><?php esc_html_e( 'Two Legs', 'racketmanager' ); ?></label>
					</div>
					<div class="form-check form-check-inline">
						<input type="radio" class="form-check-input <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="homeAway" id="homeAwayFalse" value="false"
						<?php
						if ( isset( $cup_season->home_away ) ) {
							echo ( false === $cup_season->home_away ) ? ' checked' : '';
						}
						?>
						/>
						<label class="form-check-label" for="homeAwayFalse"><?php esc_html_e( 'Single leg', 'racketmanager' ); ?></label>
					</div>
					<?php
					if ( $is_invalid ) {
						?>
						<div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
						<?php
						$is_invalid = false;
						$msg        = null;
					}
					?>
				</div>
			</div>
		</div>
		<div class="form-control mb-3">
			<legend><?php esc_html_e( 'Dates', 'racketmanager' ); ?></legend>
			<div class="row g-3">
				<div class="col-md-6">
					<div class="form-floating mb-3">
						<?php
						if ( ! empty( $racketmanager->error_fields ) && is_numeric( array_search( 'date_open', $racketmanager->error_fields, true ) ) ) {
							$is_invalid = true;
							$msg_id     = array_search( 'date_open', $racketmanager->error_fields, true );
							$msg        = isset( $racketmanager->error_messages[ $msg_id ] ) ? $racketmanager->error_messages[ $msg_id ] : null;
						}
						?>
						<input type="date" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="dateOpen" id="dateOpen" value="<?php echo isset( $cup_season->date_open ) ? esc_html( $cup_season->date_open ) : null; ?>" size="20" />
						<label for="dateOpen" class="form-label"><?php esc_html_e( 'Opening Date', 'racketmanager' ); ?></label>
						<?php
						if ( $is_invalid ) {
							?>
							<div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
							<?php
							$is_invalid = false;
							$msg        = null;
						}
						?>
					</div>
				</div>
				<div class="col-md-6">
					<div class="form-floating mb-3">
						<?php
						if ( ! empty( $racketmanager->error_fields ) && is_numeric( array_search( 'date_closing', $racketmanager->error_fields, true ) ) ) {
							$is_invalid = true;
							$msg_id     = array_search( 'date_closing', $racketmanager->error_fields, true );
							$msg        = isset( $racketmanager->error_messages[ $msg_id ] ) ? $racketmanager->error_messages[ $msg_id ] : null;
						}
						?>
						<input type="date" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="dateClose" id="dateClose" value="<?php echo isset( $cup_season->date_closing ) ? esc_html( $cup_season->date_closing ) : null; ?>" size="20" />
						<label for="dateClose" class="form-label"><?php esc_html_e( 'Closing Date', 'racketmanager' ); ?></label>
						<?php
						if ( $is_invalid ) {
							?>
							<div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
							<?php
							$is_invalid = false;
							$msg        = null;
						}
						?>
					</div>
				</div>
			</div>
			<div class="row g-3">
				<div class="col-md-6">
					<div class="form-floating mb-3">
						<?php
						if ( ! empty( $racketmanager->error_fields ) && is_numeric( array_search( 'date_start', $racketmanager->error_fields, true ) ) ) {
							$is_invalid = true;
							$msg_id     = array_search( 'date_start', $racketmanager->error_fields, true );
							$msg        = isset( $racketmanager->error_messages[ $msg_id ] ) ? $racketmanager->error_messages[ $msg_id ] : null;
						}
						?>
						<input type="date" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="dateStart" id="dateStart" value="<?php echo isset( $cup_season->date_start ) ? esc_html( $cup_season->date_start ) : null; ?>" size="20" />
						<label for="dateStart" class="form-label"><?php esc_html_e( 'Start Date', 'racketmanager' ); ?></label>
						<?php
						if ( $is_invalid ) {
							?>
							<div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
							<?php
							$is_invalid = false;
							$msg        = null;
						}
						?>
					</div>
				</div>
				<div class="col-md-6">
					<div class="form-floating mb-3">
						<?php
						if ( ! empty( $racketmanager->error_fields ) && is_numeric( array_search( 'date_end', $racketmanager->error_fields, true ) ) ) {
							$is_invalid = true;
							$msg_id     = array_search( 'date_end', $racketmanager->error_fields, true );
							$msg        = isset( $racketmanager->error_messages[ $msg_id ] ) ? $racketmanager->error_messages[ $msg_id ] : null;
						}
						?>
						<input type="date" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="dateEnd" id="dateEnd" value="<?php echo isset( $cup_season->date_end ) ? esc_html( $cup_season->date_end ) : null; ?>" size="20" />
						<label for="dateEnd" class="form-label"><?php esc_html_e( 'End Date', 'racketmanager' ); ?></label>
						<?php
						if ( $is_invalid ) {
							?>
							<div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
							<?php
							$is_invalid = false;
							$msg        = null;
						}
						?>
					</div>
				</div>
			</div>
		</div>

		<input type="hidden" name="competition_id" id="competition_id" value="<?php echo esc_html( $competition->id ); ?>" />
		<input type="hidden" name="updateLeague" value="cup" />

		<?php
		if ( $edit ) {
			?>
			<input type="hidden" name="editSeason" value="cup" />
			<?php
		} else {
			?>
			<input type="hidden" name="addSeason" value="cup" />
			<?php
		}
		?>
		<input type="submit" name="action" value="<?php echo esc_html( $form_action ); ?>" class="btn btn-primary" />
	</form>

</div>
