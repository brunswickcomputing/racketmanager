<?php
$tab = "charges";?>
<div class="container">
	<div class="row justify-content-end">
		<div class="col-auto racketmanager_breadcrumb">
			<a href="admin.php?page=racketmanager-finances"><?php _e( 'RacketManager Finances', 'racketmanager' ) ?></a> &raquo; <?php echo $form_title ?>
		</div>
	</div>
	<div class="row mb-3">
		<h1><?php printf(  $form_title ); ?></h1>
		<form method="post" enctype="multipart/form-data" name="charges_edit" class="form-control">

			<?php if ( $edit ) { ?>
				<?php wp_nonce_field( 'racketmanager_manage-charges' ) ?>
			<?php } else { ?>
				<?php wp_nonce_field( 'racketmanager_add-charges' ) ?>
			<?php } ?>

			<div class="form-floating mb-3">
				<select class="form-select" size="1" name="competitionType" id="competitionType" >
					<option><?php _e( 'Select competition type' , 'racketmanager') ?></option>
					<option value="league" <?php selected( 'league', $charges->competitionType ) ?>><?php _e( 'League', 'racketmanager') ?></option>
					<option value="cup" <?php selected( 'cup', $charges->competitionType ) ?>><?php _e( 'Cup', 'racketmanager') ?></option>
				</select>
				<label for="competitionType"><?php _e( 'Competition Type', 'racketmanager' ) ?></label>
			</div>
			<div class="form-floating mb-3">
				<select class="form-select" size="1" name="type" id="type" >
					<option><?php _e( 'Select type' , 'racketmanager') ?></option>
					<option value="summer" <?php selected( 'summer', $charges->type ) ?>><?php _e( 'Summer', 'racketmanager') ?></option>
					<option value="winter" <?php selected( 'winter', $charges->type ) ?>><?php _e( 'Winter', 'racketmanager') ?></option>
				</select>
				<label for="type"><?php _e( 'Type', 'racketmanager' ) ?></label>
			</div>
			<div class="form-floating mb-3">
				<select class="form-select" size="1" name="season" id="season" >
					<option><?php _e( 'Select season' , 'racketmanager') ?></option>
					<?php $seasons = $racketmanager->getSeasons( "DESC" );
					foreach ( $seasons AS $season ) { ?>
						<option value="<?php echo $season->name ?>" <?php selected( $season->name, isset($charges->season) ? $charges->season : '' ) ?>><?php echo $season->name ?></option>
					<?php } ?>
				</select>
				<label for="type"><?php _e( 'Season', 'racketmanager' ) ?></label>
			</div>
			<div class="form-floating mb-3">
				<select class="form-select" size="1" name="status" id="type" >
					<option><?php _e( 'Select type' , 'racketmanager') ?></option>
					<option value="draft" <?php selected( 'draft', $charges->status ) ?>><?php _e( 'Draft', 'racketmanager') ?></option>
					<option value="final" <?php selected( 'final', $charges->status ) ?>><?php _e( 'Final', 'racketmanager') ?></option>
				</select>
				<label for="status"><?php _e( 'Status', 'racketmanager' ) ?></label>
			</div>
			<div class="form-floating mb-3">
				<input type="date" class="form-control" name="date" id="date" value="<?php echo $charges->date ?>" size="20" />
				<label for="date"><?php _e( 'Date', 'racketmanager' ) ?></label>
			</div>
			<div class="form-floating mb-3">
				<input type="number" class="form-control" name="feeClub" id="feeClub" value="<?php echo $charges->feeClub ?>" size="20" />
				<label for="feeClub"><?php _e( 'Club Fee', 'racketmanager' ) ?></label>
			</div>
			<div class="form-floating mb-3">
				<input type="number" class="form-control" name="feeTeam" id="feeTeam" value="<?php echo $charges->feeTeam ?>" size="20" />
				<label for="feeTeam"><?php _e( 'Team Fee', 'racketmanager' ) ?></label>
			</div>
			<?php do_action( 'charges_edit_form', $charges ) ?>

			<input type="hidden" name="charges_id" id="charges_id" value="<?php echo $charges->id ?>" />
			<input type="hidden" name="updateCharges" value="charges" />

			<?php if ( $edit ) { ?>
				<input type="hidden" name="editCharges" value="charges" />
			<?php } else { ?>
				<input type="hidden" name="addCharges" value="charges" />
			<?php } ?>
			<div class="mb-3">
				<button type="submit" name="saveCharges" class="btn btn-primary"><?php echo $form_action ?></button>
			</div>
		</form>
	</div>
	<div class="row mb-3">
		<?php
			if ( $charges->id ) {
				$fmt = numfmt_create( get_locale(), NumberFormatter::CURRENCY );
				?>
				<h2><?php _e( 'Club charges', 'racketmanager' ) ?></h2>
				<?php if ( $clubCharges = $charges->getClubEntries() ) { ?>
					<form action="admin.php?page=racketmanager-finances" method="post" enctype="multipart/form-data" name="clubcharges" class="form-control">
						<div class="row fw-bold">
							<div class="col-5"><?php _e('Club', 'racketmanager') ?></div>
							<div class="col-2"><?php _e('Number of Teams', 'racketmanager') ?></div>
							<div class="col-2"><?php _e('Fee', 'racketmanager') ?></div>
						</div>
						<?php foreach ($clubCharges as $clubCharge) { ?>
							<div class="row mt-3">
								<div class="col-5"><?php echo $clubCharge->name ?></div>
								<div class="col-2"><?php echo $clubCharge->numTeams ?></div>
								<div class="col-2"><?php echo numfmt_format_currency($fmt, $clubCharge->fee, 'GBP') ?></div>
								<div class="col-3"><a href="admin.php?page=racketmanager-finances&amp;subpage=invoice&amp;club=<?php echo $clubCharge->id ?>&amp;charge=<?php echo $charges->id ?>" class="btn btn-secondary"><?php _e('View Invoice', 'racketmanager') ?></a></div>
								<?php foreach ($clubCharge->competitions as $competition) { ?>
									<div class="col-2"></div>
									<div class="col-3"><?php echo Racketmanager_Util::getCompetitionType($competition->type) ?></div>
									<div class="col-2"><?php echo $competition->count ?></div>
									<div class="col-2"><?php echo numfmt_format_currency($fmt, $competition->fee, 'GBP') ?></div>
									<div class="col-3"></div>
								<?php } ?>
							</div>
						<?php } ?>
						<div class="mb-3">
							<input type="hidden" name="charges_id" id="charges_id" value="<?php echo $charges->id ?>" />
							<button type="submit" name="generateInvoices" class="btn btn-primary"><?php _e('Generate Invoices', 'racketmanager') ?></button>
						</div>
					</form>
				<?php } ?>
			<?php }
		?>
	</div>
	<div class="mb-3">
    <a href="admin.php?page=racketmanager-finances&amp;tab=<?php echo $tab ?>" class="btn btn-secondary"><?php _e('Back to charges', 'racketmanager'); ?></a>
  </div>
</div>
