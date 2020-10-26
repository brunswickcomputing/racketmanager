<?php
?>

<script type='text/javascript'>
jQuery(function() {
	   jQuery("#tabs").tabs({
							active: <?php echo $tab ?>
							});
	   });
</script>
<div class="wrap"  style="margin-bottom: 1em;">
	<p class="leaguemanager_breadcrumb"><a href="index.php?page=leaguemanager"><?php _e( 'LeagueManager', 'leaguemanager' ) ?></a> &raquo; <?php echo $competition->name ?></p>

	<h1><?php echo $competition->name ?></h1>

	<div id="tabs" class="competition-blocks">
		<ul id="tablist" style="display: none;">
			<li><a href="#leagues-table"><?php _e( 'Leagues', 'leaguemanager' ) ?></a></li>
			<li><a href="#player-stats"><?php _e( 'Players Stats', 'leaguemanager' ) ?></a></li>
			<li><a href="#seasons-table"><?php _e( 'Seasons', 'leaguemanager' ) ?></a></li>
			<li><a href="#settings"><?php _e( 'Settings', 'leaguemanager' ) ?></a></li>
		</ul>

		<div id="leagues-table" class="league-block-container">
			<h2 class="header"><?php _e( 'Leagues', 'leaguemanager' ) ?></h2>
			<?php include('competition/leagues.php'); ?>
		</div>
		<div id="player-stats" class="league-block-container">
			<h2 class="header"><?php _e( 'Players Stats', 'leaguemanager' ) ?></h2>
			<?php include(LEAGUEMANAGER_PATH . '/admin/includes/player-stats.php'); ?>
		</div>
		<div id="seasons-table" class="league-block-container">
			<h2 class="header"><?php _e( 'Seasons', 'leaguemanager' ) ?></h2>
			<?php include('competition/seasons.php'); ?>
		</div>

		<div id="settings" class="league-block-container">
			<h2 class="settings"><?php _e( 'Settings', 'leaguemanager' ) ?></h2>
			<?php include('competition/settings.php'); ?>
		</div>

	</div>
</div>
