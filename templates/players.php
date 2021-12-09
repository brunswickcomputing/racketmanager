<?php
/**
Template page for the players

The following variables are usable:

$league: contains data of current league
$playerss: contains all players for current league
$season: current season

You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
*/
?>

	<?php if ( $playerstats ) { ?>
		<table class='racketmanager player stats table' summary='' title='<?php echo __( 'Player Stats', 'racketmanager' )." ".$league->title ?>'>
			<thead>
				<tr>
					<th class='player' scope="col"><?php _e( 'Player', 'racketmanager' ) ?></th>
					<th class="team" scope="col"><?php _e( 'Team', 'racketmanager' ) ?></th>
					<th scope="col"><?php _e( 'Won', 'racketmanager' ) ?></th>
					<th scope="col"><?php _e( 'Lost', 'racketmanager' ) ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $playerstats AS $player ) { ?>

					<tr>
						<td><?php echo $player['playername'] ?></td>
						<td><?php echo $player['team'] ?></td>
						<td><?php echo $player['won'] ?></td>
						<td><?php echo $player['lost'] ?></td>
					</tr>

				<?php } ?>
			</tbody>
		</table>

	<?php } ?>
