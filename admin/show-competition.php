<?php
	$tab = 0;
	$competition_id = $_GET['competition_id'];
	$competition = $leaguemanager->getCurrentCompetition();
	$league_id = false;
	$league_title = "";
	$season_id = false;
	$season_data = array('name' => '', 'num_match_days' => '');
	$club_id = 0;

	if ( isset($_POST['addLeague']) && !isset($_POST['deleteit']) ) {
		check_admin_referer('leaguemanager_add-league');
		if ( empty($_POST['league_id'] ) ){
			$this->addLeague( htmlspecialchars($_POST['league_title']), intval($_POST['competition_id']) );
		} else {
			$this->editLeague( intval($_POST['league_id']), htmlspecialchars($_POST['league_title']), intval($_POST['competition_id']) );
		}
		$this->printMessage();
	} elseif ( isset($_GET['editleague']) ) {
		$league_id = htmlspecialchars($_GET['editleague']);
		$league = $leaguemanager->getLeague($league_id);
		$league_title = $league->title;
	} elseif ( isset($_POST['saveSeason']) || isset($_GET['editseason'])) {
			$tab = 2;
		if ( !empty($_POST['season']) ) {
			if ( empty($_POST['season_id']) ) {
				$this->addSeason( htmlspecialchars($_POST['season']), intval($_POST['num_match_days']), intval($_POST['competition_id']) );
			} else {
				$this->editSeason( intval($_POST['season_id']), htmlspecialchars($_POST['season']), intval($_POST['num_match_days']), intval($_POST['competition_id']) );
			}
		} else {
			if ( isset($_GET['editseason']) ) {
				$season_id = htmlspecialchars($_GET['editseason']);
				$season_data = $competition->seasons[$season_id];
			}
		}
	} elseif ( isset($_POST['doactionseason']) ) {
		check_admin_referer('seasons-bulk');
		$competition = $leaguemanager->getCurrentCompetition();
		if ( 'delete' == $_POST['action'] ) {
			$this->delCompetitionSeason( $_POST['del_season'], $competition->id );
		}
	} elseif ( isset($_POST['doactionleague']) && $_POST['action'] == 'delete' ) {
		check_admin_referer('leagues-bulk');
		foreach ( $_POST['league'] AS $league_id )
			$this->delLeague( intval($league_id) );
	} elseif ( isset($_GET['statsseason']) && $_GET['statsseason'] == 'Show' ) {
		if ( isset($_GET['club_id']) ) {
			$club_id = intval($_GET['club_id']);
		}
		$tab = 1;
	}

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
			<?php include('competition-leagues.php'); ?>
		</div>
		<div id="player-stats" class="league-block-container">
			<h2 class="header"><?php _e( 'Players Stats', 'leaguemanager' ) ?></h2>
			<?php include('player-stats.php'); ?>
		</div>
		<div id="seasons-table" class="league-block-container">
			<h2 class="header"><?php _e( 'Seasons', 'leaguemanager' ) ?></h2>
			<?php include('competition-seasons.php'); ?>
		</div>

		<div id="settings" class="league-block-container">
			<h2 class="settings"><?php _e( 'Settings', 'leaguemanager' ) ?></h2>
			<?php include('competition-settings.php'); ?>
		</div>

	</div>
</div>
