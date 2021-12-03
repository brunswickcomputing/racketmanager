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
		<table class='racketmanager playerstats' id='playerstats' summary='' title='<?php echo __( 'Player Stats', 'racketmanager' )." ".$league->title ?>'>
			<thead>
				<tr>
					<th class='player' scope="col"><?php _e( 'Player', 'racketmanager' ) ?></th>
					<th class='team' scope="col"><?php _e( 'Team', 'racketmanager' ) ?></th>
					<th class='numstat' scope="col"><?php _e( 'Played', 'racketmanager' ) ?></th>
					<th class='numstat' scope="col"><?php _e( 'Won', 'racketmanager' ) ?></th>
					<th class='numstat' scope="col"><?php _e( 'Lost', 'racketmanager' ) ?></th>
<?php if ( $league->num_sets % 2 == 0 ) { ?>
                    <th class='numstat' scope="col"><?php _e( 'Drawn', 'racketmanager' ) ?></th>
<?php } ?>
					<th class='numstat' scope="col"><?php _e( 'Sets Won', 'racketmanager' ) ?></th>
					<th class='numstat' scope="col"><?php _e( 'Sets Lost', 'racketmanager' ) ?></th>
                    <th class="numstat" scope="col"><?php _e( 'Sets Diff', 'racketmanager' ) ?></th>
					<th class='numstat' scope="col"><?php _e( 'Games Won', 'racketmanager' ) ?></th>
					<th class='numstat' scope="col"><?php _e( 'Games Lost', 'racketmanager' ) ?></th>
                    <th class="numstat" scope="col"><?php _e( 'Games Diff', 'racketmanager' ) ?></th>
					<th class='numstat' scope="col"><?php _e( 'Win %', 'racketmanager' ) ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $playerstats AS $playerStat ) { ?>

					<tr class=''>
						<td title="<?php _e( 'Player', 'racketmanager' ) ?>"><?php echo $playerStat['playername'] ?></td>
						<td title="<?php _e( 'Team', 'racketmanager' ) ?>"><?php echo $playerStat['team'] ?></td>
						<td title="<?php _e( 'Played', 'racketmanager' ) ?>"><?php echo $playerStat['played'] ?></td>
						<td title="<?php _e( 'Won', 'racketmanager' ) ?>"><?php echo $playerStat['won'] ?></td>
						<td title="<?php _e( 'Lost', 'racketmanager' ) ?>"><?php echo $playerStat['lost'] ?></td>
<?php if ( $league->num_sets % 2 == 0 ) { ?>
                        <td title="<?php _e( 'Drawn', 'racketmanager' ) ?>"><?php echo $playerStat['drawn'] ?></td>
<?php } ?>
						<td title="<?php _e( 'Sets Won', 'racketmanager' ) ?>"><?php echo $playerStat['setsWon'] ?></td>
						<td title="<?php _e( 'Sets Lost', 'racketmanager' ) ?>"><?php echo $playerStat['setsConceded'] ?></td>
                        <td title="<?php _e( 'Sets Diff', 'racketmanager' ) ?>"><?php echo $playerStat['setsDiff'] ?></td>
						<td title="<?php _e( 'Games Won', 'racketmanager' ) ?>"><?php echo $playerStat['gamesWon'] ?></td>
						<td title="<?php _e( 'Games Lost', 'racketmanager' ) ?>"><?php echo $playerStat['gamesConceded'] ?></td>
                        <td title="<?php _e( 'Games Diff', 'racketmanager' ) ?>"><?php echo $playerStat['gamesDiff'] ?></td>
						<td title="<?php _e( 'Win %', 'racketmanager' ) ?>"><?php echo round($playerStat['winpct'],1) ?>%</td>
					</tr>

				<?php } ?>
			</tbody>
		</table>

	<?php } ?>
