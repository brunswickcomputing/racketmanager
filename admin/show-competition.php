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
	<p class="racketmanager_breadcrumb"><a href="admin.php?page=racketmanager"><?php _e( 'RacketManager', 'racketmanager' ) ?></a> &raquo; <?php echo $competition->name ?></p>

	<h1><?php echo $competition->name ?></h1>

	<div class="container">
		<!-- Nav tabs -->
		<ul class="nav nav-pills" id="myTab" role="tablist">
			<li class="nav-item" role="presentation">
				<button class="nav-link active" id="leagues-tab" data-bs-toggle="pill" data-bs-target="#leagues" type="button" role="tab" aria-controls="leagues" aria-selected="true"><?php _e( 'Leagues', 'racketmanager' ) ?></button>
			</li>
			<li class="nav-item" role="presentation">
				<button class="nav-link" id="playerstats-tab" data-bs-toggle="pill" data-bs-target="#playerstats" type="button" role="tab" aria-controls="playerstats" aria-selected="false"><?php _e( 'Players Stats', 'racketmanager' ) ?></button>
			</li>
			<li class="nav-item" role="presentation">
				<button class="nav-link" id="seasons-tab" data-bs-toggle="pill" data-bs-target="#seasons" type="button" role="tab" aria-controls="seasons" aria-selected="false"><?php _e( 'Seasons', 'racketmanager' ) ?></button>
			</li>
			<?php if ( current_user_can( 'manage_racketmanager' ) ) { ?>
				<li class="nav-item" role="presentation">
					<button class="nav-link" id="settings-tab" data-bs-toggle="pill" data-bs-target="#settings" type="button" role="tab" aria-controls="settings" aria-selected="false"><?php _e( 'Settings', 'racketmanager' ) ?></button>
				</li>
			<?php } ?>
			<?php if ( $competition->competitiontype == 'league' ) { ?>
				<li class="nav-item" role="presentation">
					<button class="nav-link" id="constitution-tab" data-bs-toggle="pill" data-bs-target="#constitution" type="button" role="tab" aria-controls="constitution" aria-selected="false"><?php _e( 'Constitution', 'racketmanager' ) ?></button>
			</li>
			<?php } ?>

		</ul>
		<!-- Tab panes -->
		<div class="tab-content">
			<div class="tab-pane active show fade" id="leagues" role="tabpanel" aria-labelledby="leagues-tab">
				<h2><?php _e( 'Leagues', 'racketmanager' ) ?></h2>
				<?php include('competition/leagues.php'); ?>
			</div>
			<div class="tab-pane fade" id="playerstats" role="tabpanel" aria-labelledby="playerstats-tab">
				<h2><?php _e( 'Player Statistics', 'racketmanager' ) ?></h2>
				<?php include(RACKETMANAGER_PATH . '/admin/includes/player-stats.php'); ?>
			</div>
			<div class="tab-pane fade" id="seasons" role="tabpanel" aria-labelledby="seasons-tab">
				<h2><?php _e( 'Seasons', 'racketmanager' ) ?></h2>
				<?php include('competition/seasons.php'); ?>
			</div>
			<?php if ( current_user_can( 'manage_racketmanager' ) ) { ?>
				<div class="tab-pane fade" id="settings" role="tabpanel" aria-labelledby="settings-tab">
					<h2><?php _e( 'Settings', 'racketmanager' ) ?></h2>
					<?php include('competition/settings.php'); ?>
				</div>
			<?php } ?>
			<?php if ( $competition->competitiontype == 'league' ) { ?>
				<div class="tab-pane fade" id="constitution" role="tabpanel" aria-labelledby="constitution-tab">
					<div id="constitution" class="league-block-container">
						<?php include('competition/constitution.php'); ?>
				</div>
			<?php } ?>
		</div>
	</div>
</div>
