<div class="wrap narrow">
	<h1><?php _e('LeagueManager Export', 'leaguemanager') ?></h1>
	<p><?php _e( 'Here you can export teams and matches for a specific league.', 'leaguemanager' ) ?></p>
	<p><?php _e('Once you&#8217;ve saved the download file, you can use the Import function on another WordPress blog to import this blog.'); ?></p>
	<form action="" method="post">
		<input type="hidden" name="exportkey" value="<?php echo $options['exportkey'] ?>" />
		<?php wp_nonce_field( 'leaguemanager_export-datasets' ) ?>
		<h3><?php _e('Options'); ?></h3>
		<table class="lm-form-table">
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
			<th><label for="season"><?php _e('Season', 'leaguemanager'); ?></label></th>
			<td>
                <select size="1" name="season" id="season" >
                    <option><?php _e( 'Select season' , 'leaguemanager') ?></option>
<?php $seasons = parent::getSeasons( "DESC" );
foreach ( $seasons AS $season ) { ?>
                    <option value="<?php echo $season->name ?>"><?php echo $season->name ?></option>
<?php } ?>
                </select>
			</td>
		</tr>
		<tr>
			<th><label for="league_id"><?php _e('League', 'leaguemanager'); ?></label></th>
			<td id="leagues">
			</td>
		</tr>
		<tr>
			<th><label for="mode"><?php _e('Data', 'leaguemanager'); ?></label></th>
			<td>
				<select size="1" name="mode" id="mode">
                    <option><?php _e( 'Select export type', 'leaguemanager') ?></option>
					<option value="teams"><?php _e( 'Teams', 'leaguemanager' ) ?></option>
					<option value="tables"><?php _e( 'Tables', 'leaguemanager' ) ?></option>
					<option value="matches"><?php _e( 'Matches', 'leaguemanager' ) ?></option>
				</select>
			</td>
		</tr>
		</table>
		<p class="submit"><input type="submit" name="leaguemanager_export" value="<?php _e('Download File'); ?>" class="button button-primary" /></p>
	</form>
</div>
