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

	<h1><?php _e( 'Leaguemanager', 'leaguemanager' ) ?></h1>

	<div id="tabs" class="leaguemanager-blocks">
		<ul id="tablist" style="display: none;">
			<li><a href="#competitions-table"><?php _e( 'Competitions', 'leaguemanager' ) ?></a></li>
            <li><a href="#seasons-table"><?php _e( 'Seasons', 'leaguemanager' ) ?></a></li>
			<li><a href="#roster-table"><?php _e( 'Rosters', 'leaguemanager' ) ?></a></li>
			<li><a href="#player-table"><?php _e( 'Players', 'leaguemanager' ) ?></a></li>
            <li><a href="#rosterrequest-table"><?php _e( 'Roster Request', 'leaguemanager' ) ?></a></li>
            <li><a href="#teams-table"><?php _e( 'Teams', 'leaguemanager' ) ?></a></li>
            <li><a href="#clubs-table"><?php _e( 'Clubs', 'leaguemanager' ) ?></a></li>
            <li><a href="#results-table"><?php _e( 'Results', 'leaguemanager' ) ?></a></li>
            <li><a href="#results-checker-table"><?php _e( 'Results Checker', 'leaguemanager' ) ?></a></li>
		</ul>

		<div id="competitions-table" class="league-block-container">
			<h2 class="header"><?php _e( 'Competitions', 'leaguemanager' ) ?></h2>
			<?php include('competitions.php'); ?>
		</div>
        <div id="seasons-table" class="league-block-container">
            <h2 class="header"><?php _e( 'Seasons', 'leaguemanager' ) ?></h2>
            <?php include('seasons.php'); ?>
        </div>
		<div id="roster-table" class="league-block-container">
			<h2 class="header"><?php _e( 'Rosters', 'leaguemanager' ) ?></h2>
			<?php include('rosters.php'); ?>
		</div>
        <div id="rosterrequest-table" class="league-block-container">
            <h2 class="header"><?php _e( 'Roster Request', 'leaguemanager' ) ?></h2>
            <?php include('roster-requests.php'); ?>
        </div>
		<div id="player-table" class="league-block-container">
			<h2 class="header"><?php _e( 'Players', 'leaguemanager' ) ?></h2>
			<?php include('players.php'); ?>
		</div>
		<div id="teams-table" class="league-block-container">
			<h2 class="header"><?php _e( 'Teams', 'leaguemanager' ) ?></h2>
			<?php include('teams.php'); ?>
		</div>
        <div id="clubs-table" class="league-block-container">
            <h2 class="header"><?php _e( 'Clubs', 'leaguemanager' ) ?></h2>
            <?php include('clubs.php'); ?>
        </div>
		<div id="results-table" class="league-block-container">
			<h2 class="header"><?php _e( 'Results', 'leaguemanager' ) ?></h2>
			<?php include('results.php'); ?>
		</div>
        <div id="results-checker-table" class="league-block-container">
            <h2 class="header"><?php _e( 'Results Checker', 'leaguemanager' ) ?></h2>
            <?php include('results-checker.php'); ?>
        </div>
        <?php include('match-modal.php'); ?>
	</div>
</div>
