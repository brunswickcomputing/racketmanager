<?php
/**
 * Charge administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

$racketmanager_tab = 'charges';?>
<div class="container">
	<div class="row justify-content-end">
		<div class="col-auto racketmanager_breadcrumb">
			<a href="admin.php?page=racketmanager-finances"><?php esc_html_e( 'RacketManager Finances', 'racketmanager' ); ?></a> &raquo; <?php echo esc_html( $form_title ); ?>
		</div>
	</div>
	<div class="row mb-3">
		<h1><?php echo esc_html( $form_title ); ?></h1>
		<form method="post" enctype="multipart/form-data" name="charges_edit" class="form-control">
			<?php wp_nonce_field( 'racketmanager_manage-charges', 'racketmanager_nonce' ); ?>
			<div class="form-floating mb-3">
				<?php $competitions = $racketmanager->get_competitions(); ?>
				<select class="form-select" size="1" name="competition_id" id="competition_id" >
					<option><?php esc_html_e( 'Select competition', 'racketmanager' ); ?></option>
					<?php
					foreach ( $competitions as $competition ) {
						?>
						<option value="<?php echo esc_attr( $competition->id ); ?>" <?php selected( $competition->id, $charges->competition_id ); ?>><?php echo esc_html( $competition->name ); ?></option>
						<?php
					}
					?>
				</select>
				<label for="competition_id"><?php esc_html_e( 'Type', 'racketmanager' ); ?></label>
			</div>
			<div class="form-floating mb-3">
				<select class="form-select" size="1" name="season" id="season" >
					<option><?php esc_html_e( 'Select season', 'racketmanager' ); ?></option>
					<?php
					$racketmanager_seasons = $racketmanager->get_seasons( 'DESC' );
					foreach ( $racketmanager_seasons as $racketmanager_season ) {
						?>
						<option value="<?php echo esc_html( $racketmanager_season->name ); ?>" <?php selected( $racketmanager_season->name, isset( $charges->season ) ? $charges->season : '' ); ?>><?php echo esc_html( $racketmanager_season->name ); ?></option>
					<?php } ?>
				</select>
				<label for="type"><?php esc_html_e( 'Season', 'racketmanager' ); ?></label>
			</div>
			<div class="form-floating mb-3">
				<select class="form-select" size="1" name="status" id="status" >
					<option><?php esc_html_e( 'Select type', 'racketmanager' ); ?></option>
					<option value="draft" <?php selected( 'draft', $charges->status ); ?>><?php esc_html_e( 'Draft', 'racketmanager' ); ?></option>
					<option value="final" <?php selected( 'final', $charges->status ); ?>><?php esc_html_e( 'Final', 'racketmanager' ); ?></option>
				</select>
				<label for="status"><?php esc_html_e( 'Status', 'racketmanager' ); ?></label>
			</div>
			<div class="form-floating mb-3">
				<input type="date" class="form-control" name="date" id="date" value="<?php echo esc_html( $charges->date ); ?>" size="20" />
				<label for="date"><?php esc_html_e( 'Date', 'racketmanager' ); ?></label>
			</div>
			<div class="form-floating mb-3">
				<input type="number" class="form-control" name="feeClub" id="feeClub" value="<?php echo esc_html( $charges->fee_club ); ?>" size="20" />
				<label for="feeClub"><?php esc_html_e( 'Club Fee', 'racketmanager' ); ?></label>
			</div>
			<div class="form-floating mb-3">
				<input type="number" class="form-control" name="feeTeam" id="feeTeam" value="<?php echo esc_html( $charges->fee_team ); ?>" size="20" />
				<label for="feeTeam"><?php esc_html_e( 'Team Fee', 'racketmanager' ); ?></label>
			</div>
			<?php do_action( 'racketmanager_charges_edit_form', $charges ); ?>

			<input type="hidden" name="charges_id" id="charges_id" value="<?php echo esc_html( $charges->id ); ?>" />
			<input type="hidden" name="updateCharges" value="charges" />

			<?php if ( $edit ) { ?>
				<input type="hidden" name="editCharges" value="charges" />
			<?php } else { ?>
				<input type="hidden" name="addCharges" value="charges" />
			<?php } ?>
			<div class="mb-3">
				<button type="submit" name="saveCharges" class="btn btn-primary"><?php echo esc_html( $form_action ); ?></button>
			</div>
		</form>
	</div>
	<div class="row mb-3">
		<?php
		if ( $charges->id ) {
			$racketmanager_fmt = numfmt_create( get_locale(), \NumberFormatter::CURRENCY );
			?>
				<h2><?php esc_html_e( 'Club charges', 'racketmanager' ); ?></h2>
				<?php
				$racketmanager_club_charges = $charges->get_club_entries();
				if ( $racketmanager_club_charges ) {
					?>
					<form action="admin.php?page=racketmanager-finances" method="post" enctype="multipart/form-data" name="clubcharges" class="form-control">
						<div class="row fw-bold">
							<div class="col-5"><?php esc_html_e( 'Club', 'racketmanager' ); ?></div>
							<div class="col-2"><?php esc_html_e( 'Number of Teams', 'racketmanager' ); ?></div>
							<div class="col-2"><?php esc_html_e( 'Fee', 'racketmanager' ); ?></div>
						</div>
						<?php foreach ( $racketmanager_club_charges as $racketmanager_club_charge ) { ?>
							<div class="row mt-3">
								<div class="col-5"><?php echo esc_html( $racketmanager_club_charge->name ); ?></div>
								<div class="col-2"><?php echo esc_html( $racketmanager_club_charge->num_teams ); ?></div>
								<div class="col-2"><?php echo esc_html( numfmt_format_currency( $racketmanager_fmt, $racketmanager_club_charge->fee, 'GBP' ) ); ?></div>
								<div class="col-3">
									<?php if ( 'final' === $charges->status ) { ?>
										<a href="admin.php?page=racketmanager-finances&amp;subpage=invoice&amp;club=<?php echo esc_html( $racketmanager_club_charge->id ); ?>&amp;charge=<?php echo esc_html( $charges->id ); ?>&amp;tab=racketmanager-charges" class="btn btn-secondary"><?php esc_html_e( 'View Invoice', 'racketmanager' ); ?></a>
									<?php } ?>
								</div>
								<?php foreach ( $racketmanager_club_charge->events as $racketmanager_event ) { ?>
									<div class="col-2"></div>
									<div class="col-3"><?php echo esc_html( Racketmanager_Util::get_event_type( $racketmanager_event->type ) ); ?></div>
									<div class="col-2"><?php echo esc_html( $racketmanager_event->count ); ?></div>
									<div class="col-2"><?php echo esc_html( numfmt_format_currency( $racketmanager_fmt, $racketmanager_event->fee, 'GBP' ) ); ?></div>
									<div class="col-3"></div>
								<?php } ?>
							</div>
						<?php } ?>
						<div class="mb-3">
							<input type="hidden" name="charges_id" id="charges_id" value="<?php echo esc_html( $charges->id ); ?>" />
							<button type="submit" name="generateInvoices" class="btn btn-primary"><?php esc_html_e( 'Generate Invoices', 'racketmanager' ); ?></button>
						</div>
					</form>
				<?php } ?>
			<?php
		}
		?>
	</div>
	<div class="mb-3">
		<a href="admin.php?page=racketmanager-finances&amp;tab=<?php echo esc_html( $racketmanager_tab ); ?>" class="btn btn-secondary"><?php esc_html_e( 'Back to charges', 'racketmanager' ); ?></a>
	</div>
</div>
