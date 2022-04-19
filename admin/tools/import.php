<div class="container">
	<h1><?php _e('RacketManager Import') ?></h1>

	<p><?php _e( 'Choose a file to upload and import data from', 'racketmanager') ?></p>

	<form action="" method="post" enctype="multipart/form-data">
		<?php wp_nonce_field( 'racketmanager_import-datasets' ) ?>

		<div class="form-group mb-3">
			<input class="form-control" type="file" name="racketmanager_import" id="racketmanager_import" size="40" placeholder="File name" />
		</div>
		<div class="form-floating mb-3">
			<input class="form-control" type="text" name="delimiter" id="delimiter" value="TAB" size="3" placeholder="TAB" />
			<label for="delimiter"><?php _e('Delimiter','racketmanager') ?></label>
		</div>
		<p><?php _e('For tab delimited files use TAB as delimiter', 'racketmanager') ?></p>
		<div class="form-floating mb-3">
			<select class="form-select" size="1" name="mode" id="mode" onChange='Racketmanager.getImportOption(this.value)'>
				<option><?php _e( 'Select', 'racketmanager') ?></option>
				<option value="table"><?php _e( 'Table', 'racketmanager' ) ?></option>
				<option value="fixtures"><?php _e( 'Fixtures', 'racketmanager') ?></option>
				<option value="players"><?php _e( 'Players', 'racketmanager') ?></option>
				<option value="roster"><?php _e( 'Roster', 'leaguemanger') ?></option>
				<option value="results"><?php _e( 'Results', 'racketmanager') ?></option>
				<option value="matches"><?php _e( 'Matches', 'racketmanager' ) ?></option>
			</select>
			<label for="mode"><?php _e('Type of data', 'racketmanager'); ?></label>
		</div>
		<div id="competitions" class="form-floating mb-3" style="display:none">
			<?php if ( $competitions = parent::getCompetitions() ) { ?>
				<select class="form-select" size="1" name="competition_id" id="competition_id" onChange='Racketmanager.getLeagueDropdown(this.value)'>
					<option><?php _e( 'Select Competition', 'racketmanager') ?></option>
					<?php foreach ( $competitions AS $competition ) { ?>
						<option value="<?php echo $competition->id ?>"><?php echo $competition->name ?></option>
					<?php } ?>
				</select>
			<?php } ?>
			<label for="competition_id"><?php _e('Competition', 'racketmanager'); ?></label>
		</div>
		<div id="leagues" class="form-floating">
		</div>
		<div id="clubs" class="form-floating mb-3" style="display:none">
			<?php if ( $clubs = parent::getClubs( ) ) { ?>
				<select class="form-select" size="1" name="affiliatedClub" id="affiliatedClub">
					<option><?php _e( 'Select affiliated club', 'racketmanager' ) ?></option>
					<?php foreach ( $clubs AS $club ) { ?>
						<option value="<?php echo $club->id ?>"><?php echo $club->name ?></option>
					<?php } ?>
				</select>
			<?php } ?>
			<label for="club_id"><?php _e('Affiliated Club', 'racketmanager'); ?></label>
		</div>
		<div class="mb-3">
			<input type="submit" name="import" value="<?php _e( 'Upload file and import' ); ?>" class="btn btn-primary" />
		</div>
	</form>
	<p><?php printf(__( "The required structure of the file to import is described in the <a href='%s'>Documentation</a>", 'racketmanager' ), 'admin.php?page=racketmanager-doc' ) ?></p>
</div>
