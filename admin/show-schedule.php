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
				<div class="col-1 column-num">ID</div>
				<div class="col-4"><?php _e( 'Name', 'racketmanager' ) ?></div>
			</div>
			<?php foreach ( $competitions AS $competition ) {
				$competition = get_competition($competition);
				$matchCount = $racketmanager->getMatches(array('count' => true, 'competitionId' => $competition->id, 'season' => $competition->getSeason()));
				$matchCompletionCount = $racketmanager->getMatches(array('count' => true, 'competitionId' => $competition->id, 'season' => $competition->getSeason(), 'time' => 'latest'));
				$class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
				<div class="row table-row <?php echo $class ?>">
					<div class="col-1 check-column">
						<input type="checkbox" value="<?php echo $competition->id ?>" name="competition[<?php echo $competition->id ?>]" />
					</div>
					<div class="col-1 column-num"><?php echo $competition->id ?></div>
					<div class="col-3"><a href="admin.php?page=racketmanager&amp;subpage=show-competition&amp;competition_id=<?php echo $competition->id ?>"><?php echo $competition->name ?></a></div>
					<?php if ( $matchCount != 0 ) { ?>
						<div class="col-3 col-md-auto">
							<a class="btn btn-secondary" href="admin.php?page=racketmanager&amp;subpage=show-competition&amp;competition_id=<?php echo $competition->id ?>&amp;view=matches"><?php _e('View matches', 'racketmanager') ?></a>
						</div>
						<?php if ( $matchCompletionCount == 0 ) { ?>
							<div class="col-3 col-md-auto ms-3">
			          <a class="btn btn-secondary" onclick="Racketmanager.sendFixtures('<?php echo ($competition->id) ?>');">
			          <?php _e( 'Send fixtures', 'racketmanager' ) ?></a>
			        </div>
		        	<div class="col-auto"><span id="notifyMessage-<?php echo $competition->id ?>"></span></div>
						<?php } ?>
					<?php } ?>
				</div>
			<?php } ?>
		</div>
		<input type="submit" value="<?php _e('Schedule', 'racketmanager'); ?>" name="doScheduleCompetitions" id="doScheduleCompetitions" class="btn btn-primary action" />
	</form>
</div>
