<?php
/**
Template page for the todays match table in tennis

The following variables are usable:
	
	$league: contains data of current league
	$matches: contains all matches for current league
	$teams: contains teams of current league in an assosiative array
	$season: current season
	
	You can check the content of a variable when you insert the tag <?php var_dump($variable)
*/
?>
<?php if (isset($_GET['match']) ) { ?>
	<?php leaguemanager_match(intval($_GET['match'])); ?>
<?php } else { ?>

	<?php if ( $matches ) { ?>

		<table class='leaguemanager matchtable' summary='' title='<?php echo __( 'Todays Match Plan', 'leaguemanager' ) ?>'>
			<thead>
				<tr>
					<th class='match'><?php _e( 'Match', 'leaguemanager' ) ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $matches AS $match ) { ?>
				<tr class='<?php echo $match->class ?>'>
					<td class='match'>
						<?php echo $match->start_time." ".$match->location ?><br /><?php echo $match->title ?>
					</td>
				</tr>

			<?php } ?>
			</tbody>
		</table>

		<div class="tablenav">
			<div class="tablenav-pages">
				<?php echo $league->pagination ?>
			</div>
		</div>
	<?php } else { ?>
		<p><?php echo __( 'No Matches Today', 'leaguemanager' ) ?></p>
	<?php }?>

<?php } ?>
