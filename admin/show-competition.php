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
	<p class="racketmanager_breadcrumb"><a href="index.php?page=racketmanager"><?php _e( 'RacketManager', 'racketmanager' ) ?></a> &raquo; <?php echo $competition->name ?></p>

	<h1><?php echo $competition->name ?></h1>

	<div id="tabs" class="competition-blocks">
		<ul id="tablist" style="display: none;">
			<li><h2><a href="#leagues-table"><?php _e( 'Leagues', 'racketmanager' ) ?></a></h2></li>
			<li><h2><a href="#player-stats"><?php _e( 'Players Stats', 'racketmanager' ) ?></a></h2></li>
			<li><h2><a href="#seasons-table"><?php _e( 'Seasons', 'racketmanager' ) ?></a></h2></li>
			<li><h2><a href="#settings"><?php _e( 'Settings', 'racketmanager' ) ?></a></h2></li>
			<?php if ( $competition->competitiontype == 'league' ) { ?>
				<li><h2><a href="#constitution"><?php _e( 'Constitution', 'racketmanager' ) ?></a></h2></li>
			<?php } ?>
		</ul>

		<div id="leagues-table" class="league-block-container">
			<?php include('competition/leagues.php'); ?>
		</div>
		<div id="player-stats" class="league-block-container">
			<?php include(RACKETMANAGER_PATH . '/admin/includes/player-stats.php'); ?>
		</div>
		<div id="seasons-table" class="league-block-container">
			<?php include('competition/seasons.php'); ?>
		</div>

		<div id="settings" class="league-block-container">
			<?php include('competition/settings.php'); ?>
		</div>
		<?php if ( $competition->competitiontype == 'league' ) { ?>
			<div id="constitution" class="league-block-container">
				<?php include('competition/constitution.php'); ?>
			</div>
		<?php } ?>

	</div>
</div>
