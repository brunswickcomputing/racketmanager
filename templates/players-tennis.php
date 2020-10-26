<?php
/**
Template page for the match table

The following variables are usable:

$league: contains data of current league
$matches: contains all matches for current league
$teams: contains teams of current league in an associative array
$season: current season

You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
*/
?>

	<?php if ( $playerstats ) { ?>
		<table class='leaguemanager playerstats' id='playerstats' summary='' title='<?php echo __( 'Player Stats', 'leaguemanager' )." ".$league->title ?>'>
			<thead>
				<tr>
					<th class='player' scope="col"><?php _e( 'Player', 'leaguemanager' ) ?></th>
					<th class='team' scope="col"><?php _e( 'Team', 'leaguemanager' ) ?></th>
					<th class='numstat' scope="col"><?php _e( 'Played', 'leaguemanager' ) ?></th>
					<th class='numstat' scope="col"><?php _e( 'Won', 'leaguemanager' ) ?></th>
					<th class='numstat' scope="col"><?php _e( 'Lost', 'leaguemanager' ) ?></th>
					<th class='numstat' scope="col"><?php _e( 'Sets Won', 'leaguemanager' ) ?></th>
					<th class='numstat' scope="col"><?php _e( 'Sets Lost', 'leaguemanager' ) ?></th>
                    <th class="numstat" scope="col"><?php _e( 'Sets Diff', 'leaguemanager' ) ?></th>
					<th class='numstat' scope="col"><?php _e( 'Games Won', 'leaguemanager' ) ?></th>
					<th class='numstat' scope="col"><?php _e( 'Games Lost', 'leaguemanager' ) ?></th>
                    <th class="numstat" scope="col"><?php _e( 'Games Diff', 'leaguemanager' ) ?></th>
					<th class='numstat' scope="col"><?php _e( 'Win %', 'leaguemanager' ) ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $playerstats AS $playerStat ) { ?>

					<tr class=''>
						<td title="<?php _e( 'Player', 'leaguemanager' ) ?>"><?php echo $playerStat['playername'] ?></td>
						<td title="<?php _e( 'Team', 'leaguemanager' ) ?>"><?php echo $playerStat['team'] ?></td>
						<td title="<?php _e( 'Played', 'leaguemanager' ) ?>"><?php echo $playerStat['played'] ?></td>
						<td title="<?php _e( 'Won', 'leaguemanager' ) ?>"><?php echo $playerStat['won'] ?></td>
						<td title="<?php _e( 'Lost', 'leaguemanager' ) ?>"><?php echo $playerStat['lost'] ?></td>
						<td title="<?php _e( 'Sets Won', 'leaguemanager' ) ?>"><?php echo $playerStat['setsWon'] ?></td>
						<td title="<?php _e( 'Sets Lost', 'leaguemanager' ) ?>"><?php echo $playerStat['setsConceded'] ?></td>
                        <td title="<?php _e( 'Sets Diff', 'leaguemanager' ) ?>"><?php echo $playerStat['setsDiff'] ?></td>
						<td title="<?php _e( 'Games Won', 'leaguemanager' ) ?>"><?php echo $playerStat['gamesWon'] ?></td>
						<td title="<?php _e( 'Games Lost', 'leaguemanager' ) ?>"><?php echo $playerStat['gamesConceded'] ?></td>
                        <td title="<?php _e( 'Games Diff', 'leaguemanager' ) ?>"><?php echo $playerStat['gamesDiff'] ?></td>
						<td title="<?php _e( 'Win %', 'leaguemanager' ) ?>"><?php echo round($playerStat['winpct'],1) ?>%</td>
					</tr>

				<?php } ?>
			</tbody>
		</table>

	<?php } ?>
