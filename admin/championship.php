<script type='text/javascript'>
jQuery(function() {
	jQuery("#tabs").tabs({
		activate: function(event ,ui){
			jQuery(".jquery_ui_tab_index").val(ui.newTab.index());
		},
		active: <?php echo $tab ?>
	});
});
</script>

<div class="wrap">
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
				<input type="hidden" name="league-tab" value="<?php echo $tab ?>" class="jquery_ui_tab_index" />
				<input type="submit" class="button-secondary" value="<?php _e( 'Show', 'racketmanager' ) ?>" />
			</form>
		</div>
	<?php } ?>
	<div id="tabs" class="championship-blocks">
		<ul id="tablist" style="display: none">
			<li><h2><a href="#finalresults"><?php _e( 'Final Results', 'racketmanager' ) ?></a></h2></li>
			<li><h2><a href="#finals"><?php _e( 'Finals', 'racketmanager' ) ?></a></h2></li>
			<li><h2><a href="#preliminary"><?php _e( 'Preliminary Rounds', 'racketmanager' ) ?></a></h2></li>
		</ul>

		<div id="finalresults" class="championship-block-container">
			<?php include('championship/finalresults.php'); ?>
		</div>

		<div id="finals" class="championship-block-container">
			<h3><?php echo $league->championship->getFinalName() ?></h3>
			<?php include('championship/finals.php'); ?>
		</div>

		<div id="preliminary" class="championship-block-container">
			<?php include('championship/preliminary.php'); ?>
		</div>
	</div>
</div>
