<?php
?>

<script type='text/javascript'>
jQuery(function() {
	jQuery("#tabs").tabs({
		active: <?php echo $tab ?>
	});
});
</script>
<div class="container">
	<p class="racketmanager_breadcrumb"><a href="index.php?page=racketmanager"><?php _e( 'RacketManager', 'racketmanager' ) ?></a> &raquo; <?php echo $competition->name ?></p>

	<h1><?php echo $competition->name ?></h1>

	<div id="tabs" class="competition-blocks">
		<ul id="tablist" style="display: none;">
			<li><a href="#leagues-table"><?php _e( 'Leagues', 'racketmanager' ) ?></a></li>
			<li><a href="#player-stats"><?php _e( 'Players Stats', 'racketmanager' ) ?></a></li>
			<li><a href="#seasons-table"><?php _e( 'Seasons', 'racketmanager' ) ?></a></li>
			<li><a href="#settings"><?php _e( 'Settings', 'racketmanager' ) ?></a></li>
			<?php if ( $competition->competitiontype == 'league' ) { ?>
				<li><a href="#constitution"><?php _e( 'Constitution', 'racketmanager' ) ?></a></li>
			<?php } ?>
		</ul>

		<div id="leagues-table" class="league-block-container">
			<h2><?php _e( 'Leagues', 'racketmanager' ) ?></h2>
			<?php include('competition/leagues.php'); ?>
		</div>
		<div id="player-stats" class="league-block-container">
			<h2><?php _e( 'Player Statistics', 'racketmanager' ) ?></h2>
			<?php include(RACKETMANAGER_PATH . '/admin/includes/player-stats.php'); ?>
		</div>
		<div id="seasons-table" class="league-block-container">
			<h2><?php _e( 'Seasons', 'racketmanager' ) ?></h2>
			<?php include('competition/seasons.php'); ?>
		</div>

		<div id="settings" class="league-block-container">
			<h2><?php _e( 'Settings', 'racketmanager' ) ?></h2>
			<?php include('competition/settings.php'); ?>
		</div>
		<?php if ( $competition->competitiontype == 'league' ) { ?>
			<div id="constitution" class="league-block-container">
				<?php include('competition/constitution.php'); ?>
			</div>
		<?php } ?>

	</div>
</div>
