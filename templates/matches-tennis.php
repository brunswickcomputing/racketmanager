<?php
/**
Template page for the match table in tennis

The following variables are usable:
	
	$league: contains data of current league
	$matches: contains all matches for current league
	$teams: contains teams of current league in an assosiative array
	$season: current season
	
	You can check the content of a variable when you insert the tag <?php var_dump($variable)
*/
?>
<?php if (isset($_GET['match_'.$league->id]) ) { ?>
	<?php leaguemanager_match(intval($_GET['match_'.$league->id])); ?>
<?php } else { ?>

	<?php include('matches-selections.php'); ?>
	
	<?php if ( $matches ) { ?>

		<table class='leaguemanager matchtable' summary='' title='<?php echo __( 'Match Plan', 'leaguemanager' )." ".$league->title ?>'>
			<thead>
				<tr>
					<th colspan="2" class='match'><?php _e( 'Match', 'leaguemanager' ) ?></th>
					<th class='score'><?php _e( 'Score', 'leaguemanager' ) ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $matches AS $match ) { ?>

				<tr class='match-row rubber-view <?php echo $match->class ?>'>
					<?php if ( isset($match->num_rubbers) ) {
						if ($match->winner_id != 0) { ?>
							<td class="angledir"><i class="fa fa-angle-down"></i></td>
						<?php } else { ?>
							<td><a href="#" class="fa fa-print " id="<?php echo $match->id ?>" onclick="Leaguemanager.showRubbers(event, this)"></a></td>
						<?php } ?>
					<?php } else { ?>
						<td class="angledir"></td>
					<?php } ?>
					<td class='match'>
						<?php echo $match->match_date." ".$match->start_time." ".$match->location ?><br /><?php echo $match->title ?> <?php echo $match->report ?>
					</td>
					<td class='score'>
						<?php if (isset($league->num_rubbers)) {
							echo $match->score;
						} elseif ( isset($match->sets) ) {
							$sets = array();
							foreach ( (array)$match->sets AS $j => $set ) {
								if ( $set['player1'] != "" && $set['player2'] != "" ) {
									if ( $match->winner_id == $match->away_team )
										$sets[] = sprintf($league->point_format2, $set['player2'], $set['player1']);
									else
										$sets[] = sprintf($league->point_format2, $set['player1'], $set['player2']);
								}
							}
							implode(", ", $sets);
						} ?>
					</td>

					</tr>
				<?php if ( isset($match->num_rubbers) && ($match->winner_id != 0) ) { ?>
				<tr class='match-rubber-row <?php echo $match->class ?>'>
					<td colspan="3">
						<table id='rubbers_<?php echo $match->id ?>'>
							<tbody>
								<?php foreach ($match->rubbers as $rubber) { ?>
									<tr class='rubber-row <?php echo $match->class ?>'>
										<td><?php echo $rubber->rubber_number ?></td>
										<td class='playername'><?php echo $rubber->home_player_1_name ?></td>
										<td class='playername'><?php echo $rubber->home_player_2_name ?></td>
										<?php if ( isset($rubber->sets) ) {
											foreach ($rubber->sets as $set) { ?>
												<?php if ( ($set['player1'] !== '') && ( $set['player2'] !== '' )) { ?>
													<td class='score'><?php echo $set['player1']?> - <?php echo $set['player2']?></td>
												<?php } else { ?>
													<td class='score'></td>
												<?php } ?>
											<?php } ?>
										<?php } ?>
										<td class='playername'><?php echo $rubber->away_player_1_name ?></td>
										<td class='playername'><?php echo $rubber->away_player_2_name ?></td>
									</tr>
								<?php } ?>
							</tbody>
						</table>
					</td>
				</tr>
						<?php } ?>


			<?php } ?>
			</tbody>
		</table>

		<div class="tablenav">
			<div class="tablenav-pages">
				<?php echo $league->pagination ?>
			</div>
		</div>
		<div id="showMatchRubbers" style="display:none"></div>

	<?php } ?>

<?php } ?>
