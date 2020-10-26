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
				<li><a href="#results"><?php _e( 'Results', 'leaguemanager' ) ?></a></li>
				<li><a href="#matches"><?php _e( 'Matches', 'leaguemanager' ) ?></a></li>
				<li><a href="#teams"><?php _e( 'Teams', 'leaguemanager' ) ?></a></li>
<?php if ( !isset($league->entryType) || $league->entryType != 'player' ) { ?>
				<li><a href="#players"><?php _e( 'Players', 'leaguemanager' ) ?></a></li>
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
                        <li class="game game-top<?php if ( $match->homeTeam && $match->home_team == $match->winner_id ) { echo ' winner'; if ( $final->name == 'Final' ) { $champion=$match->homeTeam->title; }} ?>"><?php if ( $match->homeTeam && $match->home_team != -1 ) echo str_replace('/','<br/>',$match->homeTeam->title); elseif ( $match->homeTeam && $match->home_team == -1 ) echo 'Bye'; else echo '&nbsp;'; ?></li>
                        <li class="game game-spacer"><?php if ( $match->score != '-:-' ) echo $match->score ?></li>
                        <li class="game game-bottom<?php if ( isset($league->entryType) && $league->entryType == 'player' ) if ( isset($league->type) && substr($league->type,1,1) == 'D' )  echo ' doubles'; if ( $match->awayTeam && $match->away_team == $match->winner_id ) { echo ' winner';if ( $final->name == 'Final' ) { $champion=$match->awayTeam->title; }} ?>"><?php if ( $match->awayTeam && $match->away_team != -1 ) echo str_replace('/','<br/>',$match->awayTeam->title); elseif ( $match->awayTeam && $match->away_team == -1 ) echo 'Bye'; else echo '&nbsp;'; ?></li>
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
                </div>
			</div>
			<!-- Teamlist -->
			<div id="teams" class="jquery-ui-tab">
				<?php leaguemanager_teams( $league->id, array('season' => $league->season, 'template' => 'list') ) ?>
			</div>
<?php if ( !isset($league->entryType) || $league->entryType != 'player' ) { ?>
			<!-- Players -->
			<div id="players" class="jquery-ui-tab">
				<?php leaguemanager_players( $league->id, array('season' => $league->season) ) ?>
			</div>
<?php } ?>
        </div>
