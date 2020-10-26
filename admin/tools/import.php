<div class="wrap narrow">
	<h1><?php _e('LeagueManager Import') ?></h1>
	
	<p><?php _e( 'Choose a CSV file to upload and import data from', 'leaguemanager') ?></p>
	
	<form action="" method="post" enctype="multipart/form-data">
		<?php wp_nonce_field( 'leaguemanager_import-datasets' ) ?>
		
		<table class="lm-form-table">
			<tr>
				<th><label for="mode"><?php _e('Data', 'leaguemanager'); ?></label></th>
				<td>
					<select size="1" name="mode" id="mode">
						<option><?php _e( 'Select', 'leaguemanager') ?></option>
						<option value="teams"><?php _e( 'Teams', 'leaguemanager' ) ?></option>
						<option value="table"><?php _e( 'Table', 'leaguemanager' ) ?></option>
						<option value="fixtures"><?php _e( 'Fixtures', 'leaguemanager') ?></option>
						<option value="players"><?php _e( 'Players', 'leaguemanager') ?></option>
						<option value="roster"><?php _e( 'Roster', 'leaguemanger') ?></option>
						<option value="results"><?php _e( 'Results', 'leaguemanager') ?></option>
						<option value="matches"><?php _e( 'Matches', 'leaguemanager' ) ?></option>
					</select>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="leaguemanager_import"><?php _e('File','leaguemanager') ?></label></th><td><input type="file" name="leaguemanager_import" id="leaguemanager_import" size="40"/></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="delimiter"><?php _e('Delimiter','leaguemanager') ?></label></th><td><input type="text" name="delimiter" id="delimiter" value="TAB" size="3" /><p><?php _e('For tab delimited files use TAB as delimiter', 'leaguemanager') ?></td>
			</tr>
            <tr>
                <th><label for="competition_id"><?php _e('Competition', 'leaguemanager'); ?></label></th>
                <td>
                    <?php if ( $competitions = parent::getCompetitions() ) { ?>
                    <select size="1" name="competition_id" id="competition_id" onChange='Leaguemanager.getLeagueDropdown(this.value)'>
                        <option><?php _e( 'Select Competition', 'leaguemanager') ?></option>
                    <?php foreach ( $competitions AS $competition ) { ?>
                        <option value="<?php echo $competition->id ?>"><?php echo $competition->name ?></option>
                    <?php } ?>
                    </select>
                    <?php } ?>
                </td>
            </tr>
			<tr>
				<th scope="row"><label for="league_id"><?php _e( 'League', 'leaguemanager' ) ?></label></th>
				<td id="leagues">
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="league_id"><?php _e( 'Affiliated Club', 'leaguemanager' ) ?></label></th>
				<td>
<?php if ( $clubs = parent::getClubs( ) ) { ?>
					<select size="1" name="club_id" id="club_id">
						<option><?php _e( 'Select affiliated club', 'leaguemanager' ) ?></option>
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
	
	<p><?php printf(__( "The required structure of the file to import is described in the <a href='%s'>Documentation</a>", 'leaguemanager' ), 'admin.php?page=leaguemanager-doc' ) ?></p>
</div>
