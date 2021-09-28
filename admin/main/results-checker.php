<!-- Results Checker -->

<form id="results-checker-filter" method="post" action="">
	<?php wp_nonce_field( 'results-checker-bulk' ) ?>

    <div class="tablenav">
		<!-- Bulk Actions -->
		<select name="action" size="1">
			<option value="-1" selected="selected"><?php _e('Bulk Actions') ?></option>
      <option value="approve"><?php _e('Approve')?></option>
			<option value="handle"><?php _e('Handle')?></option>
			<option value="delete"><?php _e('Delete')?></option>
		</select>
		<input type="submit" value="<?php _e('Apply'); ?>" name="doResultsChecker" id="doResultsChecker" class="button-secondary action" />
	</div>

	<table class="widefat" summary="" title="<?php _e( 'LeagueManager Results Checker', 'leaguemanager') ?>">
		<thead>
		<tr>
			<th scope="col" class="check-column"><input type="checkbox" onclick="Leaguemanager.checkAll(document.getElementById('results-checker-filter'));" /></th>
			<th scope="col" class="num">ID</th>
      <th scope="col"><?php _e( 'Date', 'leaguemanager' ) ?></th>
			<th scope="col"><?php _e( 'League', 'leaguemanager' ) ?></th>
      <th scope="col"><?php _e( 'Match', 'leaguemanager' ) ?></th>
      <th scope="col"><?php _e( 'Team', 'leaguemanager' ) ?></th>
			<th scope="col"><?php _e( 'Player', 'leaguemanager' ) ?></th>
			<th scope="col"><?php _e( 'Description', 'leaguemanager' ) ?></th>
      <th scope="col"><?php _e( 'Status', 'leaguemanager' ) ?></th>
      <th scope="col"><?php _e( 'Updated Date', 'leaguemanager' ) ?></th>
      <th scope="col"><?php _e( 'Updated User', 'leaguemanager' ) ?></th>
		</tr>
		<tbody id="the-list">

<?php
    $resultsCheckers = $this->getResultsChecker();
    $class = '';
    foreach ($resultsCheckers AS $resultsChecker) {
        $class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
        <tr class="<?php echo $class ?>">
            <th scope="row" class="check-column">
                <input type="checkbox" value="<?php echo $resultsChecker->id ?>" name="resultsChecker[<?php echo $resultsChecker->id ?>]" />
            </th>
            <td><?php echo $resultsChecker->id ?></td>
            <td><?php echo $resultsChecker->date ?></td>
            <td><a href="admin.php?page=leaguemanager&subpage=show-league&league_id=<?php echo $resultsChecker->league->id ?>" title="<?php _e( 'Go to league', 'leaguemanager' ) ?>"><?php echo $resultsChecker->league->title ?></a></td>
            <td><?php echo $resultsChecker->match->match_title ?></td>
            <td><?php echo $resultsChecker->team ?></td>
            <td><?php echo $resultsChecker->player ?></td>
            <td><?php echo $resultsChecker->description ?></td>
            <td><?php echo $resultsChecker->status ?></td>
            <td><?php echo $resultsChecker->updated_date ?></td>
            <td><?php echo $resultsChecker->updated_user_name ?></td>
        </tr>
    <?php } ?>
		</tbody>
	</table>
</form>
