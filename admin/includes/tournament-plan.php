<?php
/**
 * Tournaments planner administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

$num_matches = count( $final_matches );
if ( 0 === $tournament->num_courts ) {
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
if ( ! is_array( $tournament->orderofplay ) || count( $tournament->orderofplay ) !== $tournament->num_courts ) {
	for ( $i = 0; $i < $tournament->num_courts; $i++ ) {
		$orderofplay[ $i ]['court']     = 'Court ' . ( $i + 1 );
		$orderofplay[ $i ]['starttime'] = $tournament->starttime;
		$orderofplay[ $i ]['matches']   = array();
	}
} else {
	$orderofplay = $tournament->orderofplay;
}
?>
<div class="container">
	<div class="row justify-content-end">
		<div class="col-auto racketmanager_breadcrumb">
			<a href="admin.php?page=racketmanager-tournaments"><?php esc_html_e( 'RacketManager Tournaments', 'racketmanager' ); ?></a> &raquo; <?php esc_html_e( 'Tournament Planner', 'racketmanager' ); ?>
		</div>
	</div>
	<div class="row">
		<h1><?php esc_html_e( 'Tournament Planner', 'racketmanager' ); ?> - <?php echo esc_html( $tournament->name ); ?></h1>
	</div>
	<form id="tournamentDetails" class="form-control" method="POST">
		<?php wp_nonce_field( 'racketmanager_tournament' ); ?>
		<input type="hidden" name="tournamentId" value=<?php echo esc_html( $tournament->id ); ?> />
		<div class="form-floating mb-3">
			<input type="text" class="form-control" name="venue" id="venue" readonly value="<?php echo esc_html( $tournament->venue_name ); ?>">
			<label for="venue"><?php esc_html_e( 'Venue', 'racketmanager' ); ?></label>
		</div>
		<div class="form-floating mb-3">
			<input type="date" class="form-control" name="date" id="date" readonly value="<?php echo esc_html( $tournament->date ); ?>" size="20" />
			<label for="date"><?php esc_html_e( 'Date', 'racketmanager' ); ?></label>
		</div>
		<div class="form-floating mb-3">
			<input type="time" class="form-control" name="starttime" id="starttime" value="<?php echo esc_html( $tournament->starttime ); ?>" size="20" />
			<label for="starttime"><?php esc_html_e( 'Start Time', 'racketmanager' ); ?></label>
		</div>
		<div class="form-floating mb-3">
			<input type="time" class="form-control" name="timeincrement" id="timeincrement" value="<?php echo esc_html( $tournament->time_increment ); ?>" size="20" />
			<label for="timeincrement"><?php esc_html_e( 'Time Increment', 'racketmanager' ); ?></label>
		</div>
		<div class="form-floating mb-3">
			<input type="number" class="form-control" name="numcourts" id="numcourts" value="<?php echo esc_html( $tournament->num_courts ); ?>" />
			<label for="numcourts"><?php esc_html_e( 'Number of courts', 'racketmanager' ); ?></label>
		</div>
		<div class="mb-3">
			<button type="submit" name="saveTournament" class="btn btn-primary"><?php esc_html_e( 'Save tournament', 'racketmanager' ); ?></button>
		</div>
	</form>
	<div class="row">
		<h2><?php esc_html_e( 'Final matches', 'racketmanager' ); ?></h2>
		<div class="col-2 col-sm-1"></div>
		<div class="col-10 col-sm-11">
			<div class="row text-center">
				<?php foreach ( $final_matches as $match ) { ?>
					<div class="col-3 mb-3">
						<div class="btn
						<?php
						if ( ! is_numeric( $match->home_team ) || ! is_numeric( $match->away_team ) ) {
							echo ' ' . esc_html( 'btn-warning' ) . ' ';
						} else {
							echo ' ' . esc_html( 'btn-success' ) . ' ';
						}
						?>
						final-match" name="match-<?php echo esc_html( $match->id ); ?>" id="match-<?php echo esc_html( $match->id ); ?>" draggable="true">
							<div class="fw-bold">
								<?php echo esc_html( $match->league->title ); ?>
							</div>
							<div
							<?php
							if ( ! is_numeric( $match->home_team ) ) {
								echo ' ' . esc_html( 'class="fst-italic"' );
							}
							?>
							>
								<?php
								if ( is_numeric( $match->home_team ) ) {
									echo esc_html( $match->teams['home']->title );
								} else {
									echo esc_html( $match->prev_home_match->match_title );
								}
								?>
							</div>
							<?php if ( is_numeric( $match->home_team ) ) { ?>
								<div class="fst-italic">(<?php echo esc_html( $match->teams['home']->club->shortcode ); ?>)</div>
							<?php } ?>
							<div>
								<?php esc_html_e( 'vs', 'racketmanager' ); ?>
							</div>
							<div
							<?php
							if ( ! is_numeric( $match->away_team ) ) {
								echo ' ' . esc_html( 'class="fst-italic"' );
							}
							?>
							>
								<?php
								if ( is_numeric( $match->away_team ) ) {
									echo esc_html( $match->teams['away']->title );
								} else {
									echo esc_html( $match->prev_away_match->match_title );
								}
								?>
							</div>
							<?php if ( is_numeric( $match->away_team ) ) { ?>
								<div class="fst-italic">(<?php echo esc_html( $match->teams['away']->club->shortcode ); ?>)</div>
							<?php } ?>
						</div>
					</div>
				<?php } ?>
			</div>
		</div>
		<?php if ( $max_schedules ) { ?>
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
							<?php for ( $i = 0; $i < $tournament->num_courts; $i++ ) { ?>
								<div class="col-<?php echo esc_html( $column_width ); ?>">
									<div class="form-group mb-2">
										<input type="text" class="form-control" name="court[<?php echo esc_html( $i ); ?>]" value="<?php echo esc_html( $orderofplay[ $i ]['court'] ); ?>" />
									</div>
									<div class="form-group">
										<input type="time" class="form-control" name="starttime[<?php echo esc_html( $i ); ?>]" value="<?php echo esc_html( $orderofplay[ $i ]['starttime'] ); ?>" />
									</div>
								</div>
							<?php } ?>
						</div>
					</div>
				</div>
				<div class="mb-3">
					<?php
					$start_time  = strtotime( $tournament->starttime );
					$time_offset = 0;
					for ( $i = 0; $i < $max_schedules; $i++ ) {
						?>
						<div class="row align-items-center text-center mb-3">
							<div class="col-2 col-sm-1">
								<?php echo esc_html( gmdate( 'H:i', $start_time ) ); ?>
							</div>
							<div class="col-10 col-sm-11">
								<div class="row">
									<?php for ( $c = 0; $c < $tournament->num_courts; $c++ ) { ?>
										<div class="col-<?php echo esc_html( $column_width ); ?> tournament-match" name="schedule[<?php echo esc_html( $c ); ?>][<?php echo esc_html( $i ); ?>]" id="schedule-<?php echo esc_html( $c ); ?>-<?php echo esc_html( $i ); ?>">
											<input type="hidden" class="matchId" name="match[<?php echo esc_html( $c ); ?>][<?php echo esc_html( $i ); ?>]" id="match-<?php echo esc_html( $c ); ?>-<?php echo esc_html( $i ); ?>" value="
											<?php
											if ( isset( $orderofplay[ $c ]['matches'][ $i ] ) ) {
												echo esc_html( $orderofplay[ $c ]['matches'][ $i ] );
											}
											?>
											" />
											<input type="hidden" class="" name="matchtime[<?php echo esc_html( $c ); ?>][<?php echo esc_html( $i ); ?>]" id="matchtime-<?php echo esc_html( $c ); ?>-<?php echo esc_html( $i ); ?>" value="<?php echo esc_html( $time_offset ); ?>" />
										</div>
									<?php } ?>
								</div>
							</div>
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
		<?php } ?>
	</div>
</div>
<?php wp_register_script( 'racketmanager-draggable', plugins_url( '/js/draggable.js', dirname( __FILE__ ) ), array(), RACKETMANAGER_VERSION, true );
wp_enqueue_script( 'racketmanager-draggable' );
?>
