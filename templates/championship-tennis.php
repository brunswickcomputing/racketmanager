<?php
/**
Template page for Championship

The following variables are usable:

$league: contains data of current league
$championship: championship object
$finals: data for finals

You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
*/
?>
<div class="jquery-ui-tabs">
	<ul class="tablist">
		<li><a href="#results"><?php _e( 'Results', 'racketmanager' ) ?></a></li>
		<li><a href="#matches"><?php _e( 'Matches', 'racketmanager' ) ?></a></li>
		<li><a href="#teams"><?php _e( 'Teams', 'racketmanager' ) ?></a></li>
		<?php if ( !isset($league->entryType) || $league->entryType != 'player' ) { ?>
			<li><a href="#players"><?php _e( 'Players', 'racketmanager' ) ?></a></li>
		<?php } ?>
	</ul>
	<!-- Results Overview -->
	<div id="results" class="jquery-ui-tab">
		<div class="">
			<?php foreach ( $finals AS $final ) { ?>

				<div class="round-header"><?php echo $final->name; ?></div>
			<?php } ?>
		</div>
		<div id="tournament-bracket">

			<?php foreach ( $finals AS $final ) { ?>

				<ul class="round">
					<li class="spacer">&nbsp;</li>
					<?php foreach ( (array)$final->matches AS $no => $match ) { ?>
						<li class="game game-top<?php if ( isset($match->home_team) && $match->home_team == $match->winner_id ) { echo ' winner'; if ( $final->name == 'Final' ) { $champion = $match->teams['home']->title; }} ?>"><?php if ( isset($match->teams['home']) && is_numeric($match->home_team) ) { echo str_replace('/','<br/>',$match->teams['home']->title); } else { echo '&nbsp;'; } ?></li>
						<li class="game game-spacer"><?php if ( $match->score != '' ) { echo $match->score; } ?> </li>
						<li class="game game-bottom<?php if ( isset($league->entryType) && $league->entryType == 'player' ) if ( isset($league->type) && substr($league->type,1,1) == 'D' )  echo ' doubles'; if ( isset($match->away_team) && $match->away_team == $match->winner_id ) { echo ' winner';if ( $final->name == 'Final' ) { $champion=$match->teams['away']->title; }} ?>"><?php if ( isset($match->teams['away']) && is_numeric($match->away_team) ) echo str_replace('/','<br/>',$match->teams['away']->title); else echo '&nbsp;'; ?></li>
						<li class="spacer">&nbsp;</li>
					<?php } ?>
				</ul>
				<?php if ( isset($champion) ) { ?>
					<ul class="round">
						<li class="spacer">&nbsp;</li>
						<li class="game game-top winner"><?php echo str_replace('/','<br/>',$champion) ?></li>
						<li class="spacer">&nbsp;</li>
					</ul>
				<?php } ?>

			<?php } ?>

		</div>
	</div>
	<!-- Match Overview -->
	<div id="matches" class="jquery-ui-tab">
		<div class="jquery-ui-tabs">
			<ul class="tablist">
				<?php foreach ( $finals AS $final ) { ?>
					<li><a href="#final-<?php echo $final->key ?>"><?php echo $final->name ?></a></li>
				<?php } ?>
			</ul>

			<?php foreach ( $finals AS $final ) { ?>
				<div id="final-<?php echo $final->key ?>">
					<?php $matches = $final->matches; ?>
					<?php include('matches-tennis-scores.php'); ?>
				</div>
			<?php } ?>
			<?php include('matches-tennis-modal.php'); ?>
		</div>
	</div>
	<!-- Teamlist -->
	<div id="teams" class="jquery-ui-tab">
		<?php racketmanager_teams( $league->id, array('season' => $league->current_season['name'], 'template' => 'list') ) ?>
	</div>
	<?php if ( !isset($league->entryType) || $league->entryType != 'player' ) { ?>
		<!-- Players -->
		<div id="players" class="jquery-ui-tab">
			<?php racketmanager_players( $league->id, array('season' => $league->current_season['name']) ) ?>
		</div>
	<?php } ?>
</div>
