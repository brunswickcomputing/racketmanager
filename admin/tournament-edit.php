<?php
/**
 * Tournament administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

?>
<div class='container'>
	<div class='row justify-content-end'>
		<div class='col-auto racketmanager_breadcrumb'>
			<a href='admin.php?page=racketmanager-tournaments'><?php esc_html_e( 'RacketManager Tournaments', 'racketmanager' ); ?></a> &raquo; <?php echo empty( $tournament->name ) ? '' : '<a href="admin.php?page=racketmanager-tournaments&amp;view=tournament&amp;tournament=' . esc_attr( $tournament->id ) . '&amp;season=' . esc_attr( $tournament->season ) . '">' . esc_html( $tournament->name ) . '</a> &raquo'; ?><?php echo esc_html( $form_title ); ?>
		</div>
	</div>
	<h1><?php echo esc_html( $form_title ); ?></h1>
	<?php
	if ( empty( $tournament->id ) ) {
		$action_form = 'admin.php?page=racketmanager-tournaments';
	} else {
		$action_form = 'admin.php?page=racketmanager-tournaments&amp;view=modify&amp;tournament=' . $tournament->id;
	}
	?>
	<form action="<?php echo esc_html( $action_form ); ?>" method='post' enctype='multipart/form-data' name='tournament_edit'>
		<?php
		if ( $edit ) {
			wp_nonce_field( 'racketmanager_manage-tournament' );
		} else {
			wp_nonce_field( 'racketmanager_add-tournament' );
		}
		?>
		<div class="form-control mb-3">
			<legend><?php esc_html_e( 'Details', 'racketmanager' ); ?></legend>
			<div class="row">
				<div class="form-floating mb-3">
					<input type="text" class="form-control" id="tournament" name="tournament" value="<?php echo esc_html( $tournament->name ); ?>" size="30" placeholder="<?php esc_html_e( 'Add tournament', 'racketmanager' ); ?>" />
					<label class="form-label" for="tournament"><?php esc_html_e( 'Name', 'racketmanager' ); ?></label>
				</div>
			</div>
			<div class="row g-3">
				<div class="col-md-6">
					<div class="form-floating mb-3">
						<select class="form-select" size="1" name="competition_id" id="competition_id" >
							<option><?php esc_html_e( 'Select competition', 'racketmanager' ); ?></option>
							<?php
							foreach ( $competitions as $competition ) {
								?>
								<option value="<?php echo esc_attr( $competition->id ); ?>" <?php selected( $competition->id, $tournament->competition_id ); ?>><?php echo esc_html( $competition->name ); ?></option>
								<?php
							}
							?>
						</select>
						<label for="competition_id" class="form-label"><?php esc_html_e( 'Competition', 'racketmanager' ); ?></label>
					</div>
				</div>
				<div class="col-md-6">
					<div class="form-floating mb-3">
						<select class="form-select" size="1" name="season" id="season" >
							<option><?php esc_html_e( 'Select season', 'racketmanager' ); ?></option>
							<?php
							$seasons = $this->get_seasons( 'DESC' );
							foreach ( $seasons as $season ) {
								?>
								<option value="<?php echo esc_html( $season->name ); ?>" <?php selected( $season->name, isset( $tournament->season ) ? $tournament->season : '' ); ?>><?php echo esc_html( $season->name ); ?></option>
							<?php } ?>
						</select>
						<label for="season" class="form-label"><?php esc_html_e( 'Season', 'racketmanager' ); ?></label>
					</div>
				</div>
			</div>
			<div class="row g-3">
				<div class="col-md-6">
					<div class="form-floating mb-3">
						<select class="form-select" size="1" name="venue" id="venue" >
							<option><?php esc_html_e( 'Select venue', 'racketmanager' ); ?></option>
							<?php foreach ( $clubs as $club ) { ?>
								<option value="<?php echo esc_html( $club->id ); ?>"
									<?php
									if ( isset( $tournament->venue ) ) {
										selected( $tournament->venue, $club->id );
									}
									?>
								><?php echo esc_html( $club->name ); ?></option>
							<?php } ?>
						</select>
						<label for="venue" class="form-label"><?php esc_html_e( 'Venue', 'racketmanager' ); ?></label>
					</div>
				</div>
				<div class="col-md-6">
					<div class="form-floating mb-3">
						<input type="text" class="form-control" name="competition_code" id="competition_code" value="<?php echo esc_html( $tournament->competition_code ); ?>" />
						<label for="competition_code" class="form-label"><?php esc_html_e( 'Competition code', 'racketmanager' ); ?></label>
					</div>
				</div>
			</div>
		</div>
		<div class="form-control mb-3">
			<legend><?php esc_html_e( 'Dates', 'racketmanager' ); ?></legend>
			<div class="row g-3">
				<div class="col-md-6">
					<div class="form-floating mb-3">
						<input type="date" class="form-control" name="date_open" id="date_open" value="<?php echo esc_html( $tournament->date_open ); ?>" size="20" />
						<label for="date_open" class="form-label"><?php esc_html_e( 'Opening Date', 'racketmanager' ); ?></label>
					</div>
				</div>
				<div class="col-md-6">
					<div class="form-floating mb-3">
						<input type="date" class="form-control" name="closingdate" id="closingdate" value="<?php echo esc_html( $tournament->closing_date ); ?>" size="20" />
						<label for="closingdate" class="form-label"><?php esc_html_e( 'Closing Date', 'racketmanager' ); ?></label>
					</div>
				</div>
			</div>
			<div class="row g-3">
				<div class="col-md-6">
					<div class="form-floating mb-3">
						<input type="date" class="form-control" name="date_start" id="date_start" value="<?php echo esc_html( $tournament->date_start ); ?>" size="20" />
						<label for="date_start" class="form-label"><?php esc_html_e( 'Start Date', 'racketmanager' ); ?></label>
					</div>
				</div>
				<div class="col-md-6">
					<div class="form-floating mb-3">
						<input type="date" class="form-control" name="date" id="date" value="<?php echo esc_html( $tournament->date ); ?>" size="20" />
						<label for="date" class="form-label"><?php esc_html_e( 'End Date', 'racketmanager' ); ?></label>
					</div>
				</div>
			</div>
		</div>
		<?php do_action( 'racketmanager_tournament_edit_form', $tournament ); ?>

		<input type="hidden" name="tournament_id" id="tournament_id" value="<?php echo esc_html( $tournament->id ); ?>" />
		<input type="hidden" name="updateLeague" value="tournament" />

		<?php
		if ( $edit ) {
			?>
			<input type="hidden" name="editTournament" value="tournament" />
			<?php
		} else {
			?>
			<input type="hidden" name="addTournament" value="tournament" />
			<?php
		}
		?>
		<input type="submit" name="action" value="<?php echo esc_html( $form_action ); ?>" class="btn btn-primary" />
	</form>

</div>
