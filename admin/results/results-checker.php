<!-- Results Checker -->

<form id="results-checker-filter" method="post" action="" class="form-control">
	<?php wp_nonce_field( 'results-checker-bulk' ) ?>

	<div class="tablenav">
		<!-- Bulk Actions -->
		<select name="action" size="1">
			<option value="-1" selected="selected"><?php _e('Bulk Actions') ?></option>
			<option value="approve"><?php _e('Approve')?></option>
			<option value="handle"><?php _e('Handle')?></option>
			<option value="delete"><?php _e('Delete')?></option>
		</select>
		<input type="submit" value="<?php _e('Apply'); ?>" name="doResultsChecker" id="doResultsChecker" class="btn btn-secondary action" />
		<select name="filterResultsChecker" size="1">
			<option value="-1" selected="selected"><?php _e('Filter results') ?></option>
			<option value="all"><?php _e('All')?></option>
			<option value="outstanding" <?php if ( $resultsCheckFilter == 'outstanding' ) echo 'selected' ?>><?php _e('Outstanding')?></option>
		</select>
		<input type="submit" value="<?php _e('Apply'); ?>" name="doFilterResultsChecker" id="doFilterResultsChecker" class="btn btn-secondary action" />
	</div>

	<div class="container">
		<div class="row table-header">
			<div class="col-12 col-md-auto check-column"><input type="checkbox" onclick="Racketmanager.checkAll(document.getElementById('results-checker-filter'));" /></div>
			<div class="col-12 col-md-1"><?php _e( 'Date', 'racketmanager' ) ?></div>
			<div class="col-12 col-md-1"><?php _e( 'League', 'racketmanager' ) ?></div>
			<div class="col-12 col-md-2"><?php _e( 'Match', 'racketmanager' ) ?></div>
			<div class="col-12 col-md-1"><?php _e( 'Team', 'racketmanager' ) ?></div>
			<div class="col-12 col-md-1"><?php _e( 'Player', 'racketmanager' ) ?></div>
			<div class="col-12 col-md-2"><?php _e( 'Description', 'racketmanager' ) ?></div>
			<div class="col-12 col-md-1"><?php _e( 'Status', 'racketmanager' ) ?></div>
			<div class="col-12 col-md-1"><?php _e( 'Updated Date', 'racketmanager' ) ?></div>
			<div class="col-12 col-md-1"><?php _e( 'Updated User', 'racketmanager' ) ?></div>
		</div>

		<?php
		if ( $resultsCheckers ) {
			$class = '';
			foreach ($resultsCheckers AS $resultsChecker) {
				$class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
				<div class="row table-row <?php echo $class ?>">
					<div class="col-12 col-md-auto check-column">
						<input type="checkbox" value="<?php echo $resultsChecker->id ?>" name="resultsChecker[<?php echo $resultsChecker->id ?>]" />
					</div>
					<div class="col-12 col-md-1"><?php echo $resultsChecker->date ?></div>
					<div class="col-12 col-md-1"><a href="admin.php?page=racketmanager&subpage=show-league&league_id=<?php echo $resultsChecker->league->id ?>" title="<?php _e( 'Go to league', 'racketmanager' ) ?>"><?php echo $resultsChecker->league->title ?></a></div>
					<div class="col-12 col-md-2"><?php echo $resultsChecker->match->match_title ?></div>
					<div class="col-12 col-md-1"><?php echo $resultsChecker->team ?></div>
					<div class="col-12 col-md-1"><?php echo $resultsChecker->player ?></div>
					<div class="col-12 col-md-2"><?php echo $resultsChecker->description ?></div>
					<div class="col-12 col-md-1"><?php echo $resultsChecker->status ?></div>
					<div class="col-12 col-md-1"><?php echo $resultsChecker->updated_date ?></div>
					<div class="col-12 col-md-1"><?php echo $resultsChecker->updated_user_name ?></div>
				</div>
			<?php }
		} else { ?>
			<div class="col-auto"><?php _e('No player checks found', 'racketmanager') ?></div>
		<?php } ?>
	</div>
</form>
