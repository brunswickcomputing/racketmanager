<script type='text/javascript'>
jQuery(document).ready(function(){
  activaTab('<?php echo $tab ?>');
});
</script>

<div class="container">
	<?php if ( isset($league->groups) && !empty($league->groups) ) { ?>
		<div class="alignright" style="margin-right: 1em;">
			<form action="admin.php" method="get" style="display: inline;">
				<input type="hidden" name="page" value="<?php echo htmlspecialchars($_GET['page']) ?>" />
				<input type="hidden" name="subpage" value="<?php echo htmlspecialchars($_GET['subpage']) ?>" />
				<input type="hidden" name="league_id" value="<?php echo $league->id ?>" />
				<select name="group" size="1">
					<?php foreach ( $league->championship->getGroups() AS $key => $g ) { ?>
						<option value="<?php echo $g ?>"<?php selected($g, $group) ?>><?php printf(__('Group %s','racketmanager'), $g) ?></option>
					<?php } ?>
				</select>
				<input type="hidden" name="league-tab" value="<?php echo $tab ?>" />
				<input type="submit" class="button-secondary" value="<?php _e( 'Show', 'racketmanager' ) ?>" />
			</form>
		</div>
	<?php } ?>
	<!-- Nav tabs -->
	<ul class="nav nav-pills" id="myTab" role="tablist">
		<li class="nav-item" role="presentation">
			<button class="nav-link" id="finalresults-tab" data-bs-toggle="pill" data-bs-target="#finalresults" type="button" role="tab" aria-controls="finalresults" aria-selected="true"><?php _e( 'Final Results', 'racketmanager' ) ?></button>
		</li>
		<li class="nav-item" role="presentation">
			<button class="nav-link" id="finals-tab" data-bs-toggle="pill" data-bs-target="#finals" type="button" role="tab" aria-controls="finals" aria-selected="false"><?php _e( 'Finals', 'racketmanager' ) ?></button>
		</li>
		<li class="nav-item" role="presentation">
			<button class="nav-link" id="preliminary-tab" data-bs-toggle="pill" data-bs-target="#preliminary" type="button" role="tab" aria-controls="preliminary" aria-selected="false"><?php _e( 'Preliminary Rounds', 'racketmanager' ) ?></button>
		</li>
	</ul>
	<!-- Tab panes -->
	<div class="tab-content">
		<div class="tab-pane fade" id="finalresults" role="tabpanel" aria-labelledby="finalresults-tab">
			<h2><?php _e( 'Final Results', 'racketmanager' ) ?></h2>
			<?php include('championship/finalresults.php'); ?>
		</div>
		<div class="tab-pane fade" id="finals" role="tabpanel" aria-labelledby="finals-tab">
			<h2><?php echo $league->championship->getFinalName() ?></h2>
			<?php include('championship/finals.php'); ?>
		</div>
		<div class="tab-pane fade" id="preliminary" role="tabpanel" aria-labelledby="preliminary-tab">
			<h2><?php _e( 'Preliminary Rounds', 'racketmanager' ) ?></h2>
			<?php include('championship/preliminary.php'); ?>
		</div>
	</div>
</div>
