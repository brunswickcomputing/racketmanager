<?php
	$tab = 0;
	$club_id = isset($_GET['club_id']) ? $_GET['club_id'] : 0;
	if ( isset($_POST['addCompetition']) ) {
		check_admin_referer('leaguemanager_add-competition');
		$this->addCompetition( $_POST['competition_name'], $_POST['num_rubbers'], $_POST['num_sets'], $_POST['competition_type'] );
		$this->printMessage();
	} elseif ( isset($_POST['docompdel']) && $_POST['action'] == 'delete' ) {
		check_admin_referer('competitions-bulk');
		foreach ( $_POST['competition'] AS $competition_id ) {
			$this->delCompetition( intval($competition_id) );
		}
	} elseif ( isset($_POST['dorosterdel']) && $_POST['action'] == 'delete' ) {
		check_admin_referer('roster-bulk');
		foreach ( $_POST['roster'] AS $roster_id ) {
			$this->delRoster( intval($roster_id) );
		}
		$tab = 1;
	} elseif ( isset($_POST['doplayerdel']) && $_POST['action'] == 'delete' ) {
		check_admin_referer('player-bulk');
		foreach ( $_POST['player'] AS $player_id ) {
			$this->delPlayer( intval($player_id) );
		}
		$tab = 2;
	} elseif ( isset($_POST['addPlayer']) ) {
		check_admin_referer('leaguemanager_add-player');
		$this->addPlayer( $_POST['firstname'], $_POST['surname'], $_POST['gender'], $_POST['btm'], 'true');
		$this->printMessage();
		$tab = 2;
	} elseif ( isset($_POST['addRoster']) ) {
		check_admin_referer('leaguemanager_add-roster');
		if (isset($_POST['club_id']) && (!$_POST['club_id'] == 0)) {
			$this->addPlayerIdToRoster( $_POST['club_id'], $_POST['player_id'] );
		}
		$this->printMessage();
		$tab = 1;
	} elseif ( isset($_GET['view']) && $_GET['view'] == 'roster' ) {
		$club_id = $_GET['club_id'];
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

	<h1><?php _e( 'Leaguemanager', 'leaguemanager' ) ?></h1>

	<div id="tabs" class="leaguemanager-blocks">
		<ul id="tablist" style="display: none;">
			<li><a href="#competitions-table"><?php _e( 'Competitions', 'leaguemanager' ) ?></a></li>
			<li><a href="#roster-table"><?php _e( 'Rosters', 'leaguemanager' ) ?></a></li>
			<li><a href="#player-table"><?php _e( 'Players', 'leaguemanager' ) ?></a></li>
		</ul>

		<div id="competitions-table" class="league-block-container">
			<h2 class="header"><?php _e( 'Competitions', 'leaguemanager' ) ?></h2>
			<?php include('competitions.php'); ?>
		</div>
		<div id="roster-table" class="league-block-container">
			<h2 class="header"><?php _e( 'Rosters', 'leaguemanager' ) ?></h2>
			<?php include('rosters.php'); ?>
		</div>
		<div id="player-table" class="league-block-container">
			<h2 class="header"><?php _e( 'Players', 'leaguemanager' ) ?></h2>
			<?php include('players.php'); ?>
		</div>
	</div>
</div>
