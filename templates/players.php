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

	<?php if ( $matches ) { ?>
		<table class='leaguemanager player stats table' summary='' title='<?php echo __( 'Player Stats', 'leaguemanager' )." ".$league->title ?>'>
			<thead>
				<tr>
					<th class='player' scope="col"><?php _e( 'Player', 'leaguemanager' ) ?></th>
					<th class="team" scope="col"><?php _e( 'Team', 'leaguemanager' ) ?></th>
					<th scope="col"><?php _e( 'Won', 'leaguemanager' ) ?></th>
					<th scope="col"><?php _e( 'Lost', 'leaguemanager' ) ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $players AS $player ) { ?>

					<tr class='<?php echo $match->class ?>'>
						<td><?php echo $player->firstname; echo $player->surname; ?></td>
						<td><?php echo $player->team_title ?></td>
						<td><?php echo $player->won ?></td>
						<td><?php echo $player->lost ?></td>
					</tr>

				<?php } ?>
			</tbody>
		</table>

	<?php } ?>
