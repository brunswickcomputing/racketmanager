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

	<h1><?php _e( 'Racketmanager', 'racketmanager' ) ?></h1>

	<div id="tabs" class="racketmanager-blocks">
		<ul id="tablist" style="display: none;">
			<li><a href="#competitions-table"><?php _e( 'Competitions', 'racketmanager' ) ?></a></li>
			<li><a href="#player-table"><?php _e( 'Players', 'racketmanager' ) ?></a></li>
			<li><a href="#rosterrequest-table"><?php _e( 'Roster Request', 'racketmanager' ) ?></a></li>
		</ul>

		<div id="competitions-table" class="league-block-container">
			<h2 class="header"><?php _e( 'Competitions', 'racketmanager' ) ?></h2>
			<?php include('main/competitions.php'); ?>
		</div>
		<div id="rosterrequest-table" class="league-block-container">
			<h2 class="header"><?php _e( 'Roster Request', 'racketmanager' ) ?></h2>
			<?php include('main/roster-requests.php'); ?>
		</div>
		<div id="player-table" class="league-block-container">
			<h2 class="header"><?php _e( 'Players', 'racketmanager' ) ?></h2>
			<?php include('main/players.php'); ?>
		</div>
		<?php include(RACKETMANAGER_PATH . '/admin/includes/match-modal.php'); ?>
	</div>
</div>
