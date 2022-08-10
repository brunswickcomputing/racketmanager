<?php
$class = '';
?>
<div class="container">
	<h1><?php _e('Schedule matches', 'racketmanager') ?></h1>
	<form action="" method="post" enctype="multipart/form-data" name="scheduleCompetitions" id="scheduleCompetitions" class="form-control">
		<div class="tablenav">
			<!-- Bulk Actions -->
			<select name="action" size="1">
				<option value="-1" selected="selected"><?php _e('Bulk Actions') ?></option>
				<option value="delete"><?php _e('Delete')?></option>
			</select>
			<input type="submit" value="<?php _e('Apply'); ?>" name="doDeleteCompetitionMatches" id="doDeleteCompetitionMatches" class="btn btn-secondary action" />
		</div>
		<div class="container mb-3">
			<div class="row table-header">
				<div class="col-1 check-column"><input type="checkbox" onclick="Racketmanager.checkAll(document.getElementById('competitions_add'));" /></div>
				<div class="col-2 column-num">ID</div>
				<div class="col-4"><?php _e( 'Name', 'racketmanager' ) ?></div>
			</div>
			<?php foreach ( $competitions AS $competition ) {
				$competition = get_competition($competition);
				$class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
				<div class="row table-row <?php echo $class ?>">
					<div class="col-1 check-column">
						<input type="checkbox" value="<?php echo $competition->id ?>" name="competition[<?php echo $competition->id ?>]" />
					</div>
					<div class="col-2 column-num"><?php echo $competition->id ?></div>
					<div class="col-4"><?php echo $competition->name ?></div>
				</div>
			<?php } ?>
		</div>
		<input type="submit" value="<?php _e('Schedule', 'racketmanager'); ?>" name="doScheduleCompetitions" id="doScheduleCompetitions" class="btn btn-primary action" />
	</form>
</div>
