<div class="container">
	<form id="leagues-filter" method="post" action="" class="form-control mb-3">
		<?php wp_nonce_field( 'leagues-bulk' ) ?>

		<input type="hidden" name="competition_id" value="<?php echo $competition_id ?>" />
		<div class="tablenav">
			<!-- Bulk Actions -->
			<select name="action" size="1">
				<option value="-1" selected="selected"><?php _e('Bulk Actions') ?></option>
				<option value="delete"><?php _e('Delete')?></option>
			</select>
			<input type="submit" value="<?php _e('Apply'); ?>" name="doactionleague" id="doactionleague" class="btn btn-secondary action" />
		</div>

		<div class="container">
			<div class="row table-header">
				<div class="col-2 col-lg-1 check-column"><input type="checkbox" onclick="Racketmanager.checkAll(document.getElementById('leagues-filter'));" /></div>
				<div class="d-none d-lg-1 col-1 column-num">ID</div>
				<div class="col-4"><?php _e( 'League', 'racketmanager' ) ?></div>
				<div class="col-3 col-lg-1 column-num"><?php _e( 'Teams', 'racketmanager' ) ?></div>
				<div class="col-3 col-lg-1 column-num"><?php _e( 'Matches', 'racketmanager' ) ?></div>
			</div>

			<?php
			if ( $leagues = $competition->getLeagues() ) {
				$class = '';
				foreach ( $leagues AS $league ) {
					$league = get_league($league);
					$class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
					<div class="row table-row <?php echo $class ?>">
						<div class="col-2 col-lg-1 check-column"><input type="checkbox" value="<?php echo $league->id ?>" name="league[<?php echo $league->id ?>]" /></div>
						<div class="d-none d-lg-1 col-1 column-num"><?php echo $league->id ?></div>
						<div class="col-4"><a href="admin.php?page=racketmanager&amp;subpage=show-league&amp;league_id=<?php echo $league->id ?>"><?php echo $league->title ?></a></div>
						<div class="col-3 col-lg-1 column-num"><?php echo $league->num_teams_total ?></div>
						<div class="col-3 col-lg-1 column-num"><?php echo $league->num_matches_total ?></div>
						<div class="d-none d-lg-1 col-auto"><a href="admin.php?page=racketmanager&amp;subpage=show-competition&amp;competition_id=<?php echo $competition->id ?>&amp;editleague=<?php echo $league->id ?>"><?php _e( 'Edit', 'racketmanager' ) ?></a></div>
					</div>
				<?php } ?>
			<?php } ?>
		</form>
	</div>

	<!-- Add New League -->
	<?php if ( !$league_id ) {
		$action = __( 'Add League', 'racketmanager' );
	} else {
		$action = __( 'Update League', 'racketmanager' );
	} ?>

	<h3><?php echo $action ?></h3>
	<form action="" method="post" class="form-control">
		<?php wp_nonce_field( 'racketmanager_add-league' ) ?>
		<input type="hidden" name="competition_id" value="<?php echo $competition_id ?>" />
		<input type="hidden" name="league_id" value="<?php echo $league_id ?>" />
		<div class="form-floating mb-3">
			<input type="text" class="form-control" required="required" placeholder="<?php _e( 'Enter new league name', 'racketmanager') ?>"name="league_title" id="league_title" value="<?php echo $league_title ?>" size="30" />
			<label for="league_title"><?php _e( 'League name', 'racketmanager' ) ?></label>
		</div>
		<div class="form-group mb-3">
			<input type="submit" name="addLeague" value="<?php echo $action ?>" class="btn btn-primary" />
		</div>
	</form>
</div>
