<?php
/**
 * Tournaments planner administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

$num_matches = count( $final_matches );
if ( 0 === intval( $tournament->num_courts ) ) {
	$num_courts    = 1;
	$max_schedules = 0;
} else {
	$num_courts    = $tournament->num_courts;
	$max_schedules = ceil( $num_matches / $num_courts ) + 1;
}
if ( '01:00:00' === $tournament->time_increment ) {
	$max_schedules = $max_schedules * 2;
}
$column_width = floor( 12 / $num_courts );
$match_length = strtotime( $tournament->time_increment );
if ( ! is_array( $tournament->orderofplay ) || count( $tournament->orderofplay ) !== intval( $tournament->num_courts ) ) {
	for ( $i = 0; $i < $tournament->num_courts; $i++ ) {
		$orderofplay[ $i ]['court']     = 'Court ' . ( $i + 1 );
		$orderofplay[ $i ]['starttime'] = $tournament->starttime;
		$orderofplay[ $i ]['matches']   = array();
	}
} else {
	$orderofplay = $tournament->orderofplay;
}
?>
<script type='text/javascript'>
jQuery(document).ready(function(){
	activaTab('<?php echo esc_html( $tab ); ?>');
});
</script>
<div class="container">
	<div class='row justify-content-end'>
		<div class='col-auto racketmanager_breadcrumb'>
			<a href='admin.php?page=racketmanager-tournaments'><?php esc_html_e( 'RacketManager Tournaments', 'racketmanager' ); ?></a> &raquo; <a href='admin.php?page=racketmanager-tournaments&amp;view=tournament&amp;tournament=<?php echo esc_attr( $tournament->id ); ?>&amp;season=<?php echo esc_attr( $tournament->season ); ?>'><?php echo esc_html( $tournament->name ); ?></a> &raquo; <?php esc_html_e( 'Tournament Planner', 'racketmanager' ); ?>
		</div>
	</div>
	<h1><?php echo esc_html( $tournament->name ); ?> - <?php esc_html_e( 'Plan', 'racketmanager' ); ?></h1>
	<div class="row mb-3">
		<div class="col-12 col-md-6">
			<table class="table table-borderless">
				<tbody>
					<tr>
						<td class="col-6 col-md-3"><?php esc_html_e( 'Venue', 'racketmanager' ); ?></td>
						<td class="col-6"><?php echo esc_html( $tournament->venue_name ); ?></td>
					</tr>
					<tr>
						<td class="col-6 col-md-3"><?php esc_html_e( 'Date', 'racketmanager' ); ?></td>
						<td class="col-auto"><?php echo esc_html( $tournament->date ); ?></td>
					</tr>
					<tr>
						<td class="col-6 col-md-3"><?php esc_html_e( 'Matches', 'racketmanager' ); ?></td>
						<td class="col-auto"><?php echo esc_html( count( $final_matches ) ); ?></td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
	<ul class="nav nav-pills">
		<li class="nav-item">
			<button class="nav-link" id="matches-tab" data-bs-toggle="tab" data-bs-target="#matches" type="button" role="tab" aria-controls="matches" aria-selected="true"><?php esc_html_e( 'Matches', 'racketmanager' ); ?></button>
		</li>
		<li class="nav-item">
			<button class="nav-link" id="config-tab" data-bs-toggle="tab" data-bs-target="#config" type="button" role="tab" aria-controls="config" aria-selected="true"><?php esc_html_e( 'Config', 'racketmanager' ); ?></button>
		</li>
	</ul>
	<div class="tab-content">
		<div class="tab-pane fade" id="config" role="tabpanel" aria-labelledby="config-tab">
			<form id="tournamentDetails" class="form-control" method="POST">
				<?php wp_nonce_field( 'racketmanager_tournament' ); ?>
				<input type="hidden" name="tournamentId" value=<?php echo esc_html( $tournament->id ); ?> />
				<div class="row g-3">
					<div class="col">
						<div class="form-floating mb-3">
							<input type="time" class="form-control" name="starttime" id="starttime" value="<?php echo esc_html( $tournament->starttime ); ?>" size="20" />
							<label for="starttime"><?php esc_html_e( 'Start Time', 'racketmanager' ); ?></label>
						</div>
					</div>
					<div class="col">
						<div class="form-floating mb-3">
							<input type="time" class="form-control" name="timeincrement" id="timeincrement" value="<?php echo esc_html( $tournament->time_increment ); ?>" size="20" />
							<label for="timeincrement"><?php esc_html_e( 'Time Increment', 'racketmanager' ); ?></label>
						</div>
					</div>
				</div>
				<div class="row g-3">
					<div class="col-12 col-md-6">
						<div class="form-floating mb-3">
							<input type="number" class="form-control" name="numcourts" id="numcourts" value="<?php echo esc_html( $tournament->num_courts ); ?>" />
							<label for="numcourts"><?php esc_html_e( 'Number of courts', 'racketmanager' ); ?></label>
						</div>
					</div>
				</div>
				<div class="mb-3">
					<button type="submit" name="saveTournament" class="btn btn-primary"><?php esc_html_e( 'Save tournament', 'racketmanager' ); ?></button>
				</div>
			</form>
		</div>
		<div class="tab-pane fade" id="matches" role="tabpanel" aria-labelledby="matches-tab">
			<h2><?php esc_html_e( 'Final matches', 'racketmanager' ); ?></h2>
			<div class="col-2 col-sm-1"></div>
			<div class="col-10 col-sm-11">
				<div class="row text-center">
					<?php
					foreach ( $final_matches as $match ) {
						if ( ! is_numeric( $match->home_team ) || ! is_numeric( $match->away_team ) ) {
							$btn_type = 'warning';
						} else {
							$btn_type = 'success';
						}
						if ( is_numeric( $match->home_team ) ) {
							$home_match_title = $match->teams['home']->title;
						} else {
							$home_match_title = $match->prev_home_match->match_title;
						}
						if ( is_numeric( $match->away_team ) ) {
							$away_match_title = $match->teams['away']->title;
						} else {
							$away_match_title = $match->prev_away_match->match_title;
						}
						?>
						<div class="col-3 mb-3">
							<div class="btn btn-<?php echo esc_attr( $btn_type ); ?> final-match" name="match-<?php echo esc_html( $match->id ); ?>" id="match-<?php echo esc_html( $match->id ); ?>" draggable="true">
								<div class="fw-bold">
									<?php echo esc_html( $match->league->title ); ?>
								</div>
								<div <?php echo is_numeric( $match->home_team ) ? null : 'class="fst-italic"'; ?>>
									<?php echo esc_html( $home_match_title ); ?>
								</div>
								<?php
								if ( is_numeric( $match->home_team ) ) {
									?>
									<div class="fst-italic">(<?php echo esc_html( $match->teams['home']->club->shortcode ); ?>)</div>
									<?php
								}
								?>
								<div>
									<?php esc_html_e( 'vs', 'racketmanager' ); ?>
								</div>
								<div <?php echo is_numeric( $match->away_team ) ? null : 'class="fst-italic"'; ?>>
									<?php echo esc_html( $away_match_title ); ?>
								</div>
								<?php
								if ( is_numeric( $match->away_team ) ) {
									?>
									<div class="fst-italic">(<?php echo esc_html( $match->teams['away']->club->shortcode ); ?>)</div>
									<?php
								}
								?>
							</div>
						</div>
						<?php
					}
					?>
				</div>
			</div>
			<?php
			if ( $max_schedules ) {
				?>
				<h2><?php esc_html_e( 'Schedule', 'racketmanager' ); ?></h2>
				<form id="tournament-planner" method="post" action="">
					<?php wp_nonce_field( 'racketmanager_tournament-planner' ); ?>
					<input type="hidden" name="numFinals" value=<?php echo esc_html( $num_matches ); ?> />
					<input type="hidden" name="numCourts" value=<?php echo esc_html( $tournament->num_courts ); ?> />
					<input type="hidden" name="startTime" value=<?php echo esc_html( $tournament->starttime ); ?> />
					<input type="hidden" name="tournamentId" value=<?php echo esc_html( $tournament->id ); ?> />
					<div class="row text-center mb-3">
						<div class="col-2 col-sm-1"><?php esc_html_e( 'Time', 'racketmanager' ); ?></div>
						<div class="col-10 col-sm-11">
							<div class="row">
								<?php
								for ( $i = 0; $i < $tournament->num_courts; $i++ ) {
									?>
									<div class="col-<?php echo esc_html( $column_width ); ?>">
										<div class="form-group mb-2">
											<input type="text" class="form-control" name="court[<?php echo esc_html( $i ); ?>]" value="<?php echo esc_html( $orderofplay[ $i ]['court'] ); ?>" />
										</div>
										<div class="form-group">
											<input type="time" class="form-control" name="starttime[<?php echo esc_html( $i ); ?>]" value="<?php echo esc_html( $orderofplay[ $i ]['starttime'] ); ?>" />
										</div>
									</div>
									<?php
								}
								?>
							</div>
						</div>
					</div>
					<div class="mb-3">
						<?php
						$teams       = array( 'home', 'away' );
						$start_time  = strtotime( $tournament->starttime );
						$time_offset = 0;
						for ( $i = 0; $i < $max_schedules; $i++ ) {
							$scheduled_players = array();
							$player_warnings   = array();
							?>
							<div class="row align-items-center text-center mb-3">
								<div class="col-2 col-sm-1">
									<?php echo esc_html( gmdate( 'H:i', $start_time ) ); ?>
								</div>
								<div class="col-10 col-sm-11">
									<div class="row">
										<?php
										for ( $c = 0; $c < $tournament->num_courts; $c++ ) {
											if ( isset( $orderofplay[ $c ]['matches'][ $i ] ) ) {
												$match_players = array();
												$match_id      = ( $orderofplay[ $c ]['matches'][ $i ] );
												$match         = get_match( $match_id );
												if ( $match ) {
													$match_players = match_add_players( $match_players, $match );
													if ( ! empty( $match->prev_home_match ) ) {
														$prev_match = get_match( $match->prev_home_match->id );
														if ( $prev_match ) {
															$match_players = match_add_players( $match_players, $prev_match );
														}
													}
													if ( ! empty( $match->prev_away_match ) ) {
														$prev_match = get_match( $match->prev_away_match->id );
														if ( $prev_match ) {
															$match_players = match_add_players( $match_players, $prev_match );
														}
													}
													foreach ( $match_players as $player_id ) {
														$player_found = array_search( $player_id, $scheduled_players, true );
														if ( false !== $player_found ) {
															$player = get_player( $player_id );
															if ( $player ) {
																$player_warnings[] = $player->fullname;
															}
														}
														$scheduled_players[] = $player_id;
													}
												}
											} else {
												$match_id = null;
											}
											?>
											<div class="col-<?php echo esc_html( $column_width ); ?> tournament-match" name="schedule[<?php echo esc_html( $c ); ?>][<?php echo esc_html( $i ); ?>]" id="schedule-<?php echo esc_html( $c ); ?>-<?php echo esc_html( $i ); ?>">
												<input type="hidden" class="matchId" name="match[<?php echo esc_html( $c ); ?>][<?php echo esc_html( $i ); ?>]" id="match-<?php echo esc_html( $c ); ?>-<?php echo esc_html( $i ); ?>" value="<?php echo esc_html( $match_id ); ?>" />
												<input type="hidden" class="" name="matchtime[<?php echo esc_html( $c ); ?>][<?php echo esc_html( $i ); ?>]" id="matchtime-<?php echo esc_html( $c ); ?>-<?php echo esc_html( $i ); ?>" value="<?php echo esc_html( $time_offset ); ?>" />
											</div>
											<?php
										}
										?>
									</div>
								</div>
								<?php
								if ( $player_warnings ) {
									?>
									<div class="mb-3 mt-3">
										<span class="fw-bold"><?php esc_html_e( 'Potential clashes', 'racketmanager' ); ?></span>
										<?php
										foreach ( $player_warnings as $player_warning ) {
											?>
											<div class="">
												<span><?php echo esc_html( $player_warning ); ?></span>
											</div>
											<?php
										}
										?>
									</div>
									<?php
								}
								?>
							</div>
							<?php
							$start_time  = $start_time + $match_length;
							$time_offset = $time_offset + $match_length;
						}
						?>
					</div>
					<div class="mb-3">
						<button class="btn btn-primary" name="saveTournamentPlan" id="saveTournamentPlan"><?php esc_html_e( 'Save schedule', 'racketmanager' ); ?></button>
						<button class="btn btn-secondary" name="resetTournamentPlan" id="resetTournamentPlan"><?php esc_html_e( 'Reset schedule', 'racketmanager' ); ?></button>
					</div>
				</form>
				<?php
			}
			?>
		</div>
</div>
<?php
wp_register_script( 'racketmanager-draggable', plugins_url( '/js/draggable.js', __DIR__ ), array(), RACKETMANAGER_VERSION, true );
wp_enqueue_script( 'racketmanager-draggable' );
?>
