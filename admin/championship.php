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
				<option value="<?php echo $g ?>"<?php selected($g, $group) ?>><?php printf(__('Group %s','leaguemanager'), $g) ?></option>
            <?php } ?>
			</select>
			<input type="hidden" name="jquery-ui-tab" value="<?php echo $tab ?>" class="jquery_ui_tab_index" />
			<input type="submit" class="button-secondary" value="<?php _e( 'Show', 'leaguemanager' ) ?>" />
		</form>
	</div>
<?php } ?>
	<div id="tabs" class="championship-blocks">
		<ul id="tablist" style="display: none">
			<li><a href="#finalresults"><?php _e( 'Final Results', 'leaguemanager' ) ?></a></li>
			<li><a href="#finals"><?php _e( 'Finals', 'leaguemanager' ) ?></a></li>
			<li><a href="#preliminary"><?php _e( 'Preliminary Rounds', 'leaguemanager' ) ?></a></li>
		</ul>

		<div id="finalresults" class="championship-block-container">
            <h2><?php _e( 'Final Results', 'leaguemanager' ) ?></h2>
            <?php include('championship/finalresults.php'); ?>
		</div>

		<div id="finals" class="championship-block-container">
			<h2><?php printf(__( 'Finals &#8211; %s', 'leaguemanager' ), $league->championship->getFinalName()) ?></h2>
            <?php include('championship/finals.php'); ?>
		</div>

        <div id="preliminary" class="championship-block-container">
            <h2><?php _e( 'Team Ranking', 'leaguemanager' ) ?></h2>
            <?php include('championship/preliminary.php'); ?>
        </div>
	</div>
</div>
