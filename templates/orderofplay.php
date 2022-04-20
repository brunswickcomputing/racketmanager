<?php
/**
Template page for the Winners

The following variables are usable:

$winners: array of all winners
$curr_season: current season
$tournaments: array of all tournaments

You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
*/
global $wp_query, $racketmanager_shortcodes;
$postID = isset($wp_query->post->ID) ? $wp_query->post->ID : "";
$columnWidth = floor(12 / $currTournament->numcourts) ;
?>
<div id="orderofplay">
	<h1><?php echo $currTournament->name ?> <?php _e('Finals Day Order of Play', 'racketmanager'); ?></h1>
	<div id="racketmanager_archive_selections" class="">
		<form method="get" action="<?php echo get_permalink($postID); ?>" id="racketmanager_orderofplay">
			<input type="hidden" name="page_id" value="<?php echo $postID ?>" />
			<input type="hidden" name="season" id="season" value="<?php echo $season ?>" />
			<select size="1" name="tournament" id="tournament">
				<option value=""><?php _e( 'Tournament', 'racketmanager' ) ?></option>
				<?php foreach ( $tournaments AS $tournament ) { ?>
					<option value="<?php echo $tournament->name ?>"<?php if ( $tournament->name == $currTournament->name ) echo ' selected="selected"' ?>><?php echo $tournament->name ?></option>
				<?php } ?>
			</select>
			<input type="submit" class="submit" value="<?php _e( 'Show' ) ?>" />
		</form>
	</div>

	<h2><?php echo $currTournament->venueName ?></h2>
	<?php if ( !empty($orderofplay) ) { ?>
		<div class="row text-center mb-3">
			<div class="col-2 col-sm-1"><?php _e('Time', 'racketmanager') ?></div>
			<div class="col-10 col-sm-11">
				<div class="row">
					<?php for ($i=0; $i < $currTournament->numcourts; $i++) { ?>
						<div class="col-<?php echo $columnWidth?>">
							<?php echo $orderofplay[$i]['name']; ?>
						</div>
					<?php } ?>
				</div>
			</div>
		</div>
		<!-- Standings Table -->
		<div id="winners-list">
			<div class="mb-3">
				<?php	$startTime = strtotime($currTournament->starttime);
				for ($i=0; $i < count($matchTimes); $i++) { ?>
					<div class="row align-items-center text-center mb-3">
						<div class="col-2 col-sm-1">
							<?php echo $matchTimes[$i]; ?>
						</div>
						<div class="col-10 col-sm-11">
							<div class="row align-items-center">
								<?php for ($c=0; $c < $currTournament->numcourts; $c++) {
									$scheduledMatch = $orderofplay[$c]['matches'][$i]; ?>
									<div class="col-<?php echo $columnWidth?>">
										<?php if ( isset($scheduledMatch->id) ) { ?>
											<div class="tournament-match btn btn-success">
												<div class="league">
													<?php echo $scheduledMatch->league; ?>
												</div>
												<div class="team <?php if ( isset($scheduledMatch->winner) && $scheduledMatch->team1Id == $scheduledMatch->winner ) { echo 'winner'; } ?>">
													<?php echo $scheduledMatch->team1; ?>
												</div>
												<div class="team <?php if ( isset($scheduledMatch->winner) && $scheduledMatch->team2Id == $scheduledMatch->winner ) { echo 'winner'; } ?>">
													<?php echo $scheduledMatch->team2; ?>
												</div>
											</div>
										<?php } ?>
									</div>
								<?php } ?>
							</div>
						</div>
					</div>
				<?php } ?>
			</div>
		</div>
	</div>
<?php } else { ?>
	<?php _e('No finals day order of play available', 'racketmanager'); ?>
<?php } ?>
