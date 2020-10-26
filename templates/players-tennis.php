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
		<table class='leaguemanager playerstats' summary='' title='<?php echo __( 'Player Stats', 'leaguemanager' )." ".$league->title ?>'>
			<thead>
				<tr>
					<th class='player' scope="col"><?php _e( 'Player', 'leaguemanager' ) ?></th>
					<th class='team' scope="col"><?php _e( 'Team', 'leaguemanager' ) ?></th>
					<th class='numstat' scope="col"><?php _e( 'Played', 'leaguemanager' ) ?></th>
					<th class='numstat' scope="col"><?php _e( 'Won', 'leaguemanager' ) ?></th>
					<th class='numstat' scope="col"><?php _e( 'Lost', 'leaguemanager' ) ?></th>
					<th class='numstat' scope="col"><?php _e( 'Sets Won', 'leaguemanager' ) ?></th>
					<th class='numstat' scope="col"><?php _e( 'Sets Conceded', 'leaguemanager' ) ?></th>
					<th class='numstat' scope="col"><?php _e( 'Games Won', 'leaguemanager' ) ?></th>
					<th class='numstat' scope="col"><?php _e( 'Games Conceded', 'leaguemanager' ) ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $playerstats AS $playerStat ) { ?>

					<tr class=''>
						<td><?php echo $playerStat['playername'] ?></td>
						<td><?php echo $playerStat['team'] ?></td>
						<td><?php echo $playerStat['played'] ?></td>
						<td><?php echo $playerStat['won'] ?></td>
						<td><?php echo $playerStat['lost'] ?></td>
						<td><?php echo $playerStat['setsWon'] ?></td>
						<td><?php echo $playerStat['setsConceded'] ?></td>
						<td><?php echo $playerStat['gamesWon'] ?></td>
						<td><?php echo $playerStat['gamesConceded'] ?></td>
					</tr>

				<?php } ?>
			</tbody>
		</table>

	<?php } ?>
