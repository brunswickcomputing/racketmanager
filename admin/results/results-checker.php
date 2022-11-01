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
			<div class="col-2 col-md-auto check-column"><input type="checkbox" onclick="Racketmanager.checkAll(document.getElementById('results-checker-filter'));" /></div>
			<div class="col-5 col-sm-2 col-lg-1"><?php _e( 'Date', 'racketmanager' ) ?></div>
			<div class="col-12 col-sm-2 col-lg-3"><?php _e( 'Match', 'racketmanager' ) ?></div>
			<div class="col-6 col-md-2"><?php _e( 'Team', 'racketmanager' ) ?></div>
			<div class="col-6 col-sm-2"><?php _e( 'Player', 'racketmanager' ) ?></div>
			<div class="col-12 col-md-2"><?php _e( 'Description', 'racketmanager' ) ?></div>
			<?php if ( $resultsCheckFilter != 'outstanding' ) { ?>
				<div class="d-none d-md-block col-md-3 col-lg-6"></div>
				<div class="col-4 col-md-3 col-lg-2"><?php _e( 'Status', 'racketmanager' ) ?></div>
				<div class="col-4 col-md-3 col-lg-2"><?php _e( 'Updated Date', 'racketmanager' ) ?></div>
				<div class="col-4 col-md-3 col-lg-2"><?php _e( 'Updated User', 'racketmanager' ) ?></div>
			<?php } ?>
		</div>

		<?php
		if ( $resultsCheckers ) {
			$class = '';
			foreach ($resultsCheckers AS $resultsChecker) {
				$class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
				<div class="row table-row <?php echo $class ?>">
					<div class="col-2 col-md-auto check-column">
						<input type="checkbox" value="<?php echo $resultsChecker->id ?>" name="resultsChecker[<?php echo $resultsChecker->id ?>]" />
					</div>
					<div class="col-5 col-sm-2 col-lg-1"><?php echo mysql2date('Y-m-d', $resultsChecker->match->date) ?></div>
					<div class="col-12 col-md-2 col-lg-3"><a href="admin.php?page=racketmanager-results&amp;subpage=match&amp;match_id=<?php echo $resultsChecker->match_id ?>&amp;referrer=resultschecker"><?php echo $resultsChecker->match->match_title ?></a></div>
					<div class="col-auto col-md-2"><?php echo $resultsChecker->team ?></div>
					<div class="col-auto col-sm-2"><?php echo $resultsChecker->player ?></div>
					<div class="col-12 col-md-3"><?php echo $resultsChecker->description ?></div>
					<?php if ( $resultsCheckFilter != 'outstanding' ) { ?>
						<div class="d-none d-md-block col-md-3 col-lg-6"></div>
						<div class="col-4 col-md-3 col-lg-2"><?php echo $resultsChecker->status ?></div>
						<div class="col-4 col-md-3 col-lg-2"><?php echo $resultsChecker->updated_date ?></div>
						<div class="col-4 col-md-3 col-lg-2"><?php echo $resultsChecker->updated_user_name ?></div>
					<?php } ?>
				</div>
			<?php }
		} else { ?>
			<div class="col-auto my-3"><?php _e('No player checks found', 'racketmanager') ?></div>
		<?php } ?>
	</div>
</form>
