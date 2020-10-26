<?php
global $championship;

$finalkey = isset($_GET['final']) ? htmlspecialchars($_GET['final']) : $championship->getFinalKeys(1);

$league = $leaguemanager->getLeague( intval($_GET['league_id']) );
$season = $leaguemanager->getSeason( $league );
$num_first_round = $championship->getNumTeamsFirstRound();
$class = 'alternate';
if ( empty($group) ) {
    if ( isset($league->groups) ) {
        $group_tmp = ((array)explode(";", $league->groups));
        $group = $group_tmp[0];
    } else {
        $group = '';
    }
}

if ( isset($_POST['startFinals']) ) {
	$championship->startFinalRounds($league->id);
}

if ( isset($_POST['updateFinalResults']) ) {
		$custom = isset($_POST['custom']) ? $_POST['custom'] : false;
		$championship->updateResults(intval($_POST['league_id']), $_POST['matches'], $_POST['home_points'], $_POST['away_points'], $_POST['home_team'], $_POST['away_team'], $custom, $_POST['round'], $_POST['season']);
}
$tab = 0;
if (isset($_GET['jquery-ui-tab'])) $tab = intval($_GET['jquery-ui-tab']);
if (isset($_POST['jquery-ui-tab'])) $tab = intval($_POST['jquery-ui-tab']);

?>
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
	<!--<p class="leaguemanager_breadcrumb"><a href="admin.php?page=leaguemanager"><?php _e( 'LeagueManager', 'leaguemanager' ) ?></a> &raquo; <a href="admin.php?page=leaguemanager&amp;subpage=show-league&amp;league_id=<?php echo $league->id ?>"><?php echo $league->title ?></a> &raquo; <?php _e( 'Championship Finals', 'leaguemanager') ?></p>-->

<?php if ( isset($league->groups) ) { ?>
	<div class="alignright" style="margin-right: 1em;">
		<form action="admin.php" method="get" style="display: inline;">
			<input type="hidden" name="page" value="<?php echo htmlspecialchars($_GET['page']) ?>" />
			<input type="hidden" name="subpage" value="<?php echo htmlspecialchars($_GET['subpage']) ?>" />
			<input type="hidden" name="league_id" value="<?php echo $league->id ?>" />
			<select name="group" size="1">
			<?php foreach ( (array)explode(";", $league->groups) AS $key => $g ) { ?>
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
            <?php include('finalresults.php'); ?>
		</div>
		
		<div id="finals" class="championship-block-container">
			<h2><?php printf(__( 'Finals &#8211; %s', 'leaguemanager' ), $championship->getFinalName($finalkey)) ?></h2>
            <?php include('finals.php'); ?>
		</div>
		
		<div id='preliminary' class="championship-block-container">
			<h2><?php _e( 'Team Ranking', 'leaguemanager' ) ?></h2>
            <?php include('preliminary.php'); ?>
		</div>
	</div>
</div>
