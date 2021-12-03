<div class="wrap narrow">
	<h1><?php _e('RacketManager Import') ?></h1>
	
	<p><?php _e( 'Choose a CSV file to upload and import data from', 'racketmanager') ?></p>
	
	<form action="" method="post" enctype="multipart/form-data">
		<?php wp_nonce_field( 'racketmanager_import-datasets' ) ?>
		
		<table class="lm-form-table">
			<tr>
				<th><label for="mode"><?php _e('Data', 'racketmanager'); ?></label></th>
				<td>
					<select size="1" name="mode" id="mode">
						<option><?php _e( 'Select', 'racketmanager') ?></option>
						<option value="teams"><?php _e( 'Teams', 'racketmanager' ) ?></option>
						<option value="table"><?php _e( 'Table', 'racketmanager' ) ?></option>
						<option value="fixtures"><?php _e( 'Fixtures', 'racketmanager') ?></option>
						<option value="players"><?php _e( 'Players', 'racketmanager') ?></option>
						<option value="roster"><?php _e( 'Roster', 'leaguemanger') ?></option>
						<option value="results"><?php _e( 'Results', 'racketmanager') ?></option>
						<option value="matches"><?php _e( 'Matches', 'racketmanager' ) ?></option>
					</select>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="racketmanager_import"><?php _e('File','racketmanager') ?></label></th><td><input type="file" name="racketmanager_import" id="racketmanager_import" size="40"/></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="delimiter"><?php _e('Delimiter','racketmanager') ?></label></th><td><input type="text" name="delimiter" id="delimiter" value="TAB" size="3" /><p><?php _e('For tab delimited files use TAB as delimiter', 'racketmanager') ?></td>
			</tr>
            <tr>
                <th><label for="competition_id"><?php _e('Competition', 'racketmanager'); ?></label></th>
                <td>
                    <?php if ( $competitions = parent::getCompetitions() ) { ?>
                    <select size="1" name="competition_id" id="competition_id" onChange='Racketmanager.getLeagueDropdown(this.value)'>
                        <option><?php _e( 'Select Competition', 'racketmanager') ?></option>
                    <?php foreach ( $competitions AS $competition ) { ?>
                        <option value="<?php echo $competition->id ?>"><?php echo $competition->name ?></option>
                    <?php } ?>
                    </select>
                    <?php } ?>
                </td>
            </tr>
			<tr>
				<th scope="row"><label for="league_id"><?php _e( 'League', 'racketmanager' ) ?></label></th>
				<td id="leagues">
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="league_id"><?php _e( 'Affiliated Club', 'racketmanager' ) ?></label></th>
				<td>
<?php if ( $clubs = parent::getClubs( ) ) { ?>
					<select size="1" name="club_id" id="club_id">
						<option><?php _e( 'Select affiliated club', 'racketmanager' ) ?></option>
	<?php foreach ( $clubs AS $club ) { ?>
						<option value="<?php echo $club->id ?>"><?php echo $club->name ?></option>
	<?php } ?>
					</select>
<?php } ?>
				</td>
			</tr>
		</table>

		<p class="submit"><input type="submit" name="import" value="<?php _e( 'Upload file and import' ); ?>" class="button button-primary" /></p>
	</form>
	
	<p><?php printf(__( "The required structure of the file to import is described in the <a href='%s'>Documentation</a>", 'racketmanager' ), 'admin.php?page=racketmanager-doc' ) ?></p>
</div>
