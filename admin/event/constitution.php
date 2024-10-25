<?php
/**
 * Constitution administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

if ( empty( $event->is_box ) && empty( $this->seasons ) ) {
	?>
	<p><?php esc_html_e( 'No seasons defined', 'racketmanager' ); ?>
	<?php
} elseif ( empty( $event->leagues ) ) {
	?>
	<p><?php esc_html_e( 'No leagues defined', 'racketmanager' ); ?>
	<?php
} elseif ( empty( $event->seasons ) ) {
	?>
	<p><?php esc_html_e( 'No pending seasons for event', 'racketmanager' ); ?>
	<?php
} else {
	$today              = gmdate( 'Y-m-d' );
	$latest_season_dtls = $event->current_season;
	$latest_season      = $latest_season_dtls['name'];
	if ( ! empty( $event->competition->seasons[ $latest_season ]['dateStart'] ) && $event->competition->seasons[ $latest_season ]['dateStart'] > $today ) {
		$updateable = true;
	} else {
		$updateable = false;
	}
	$latest_event_season = $event->current_season['name'];
	foreach ( array_reverse( $event->competition->seasons ) as $season ) {
		if ( isset( $season['dateEnd'] ) && $season['dateEnd'] < $today ) {
			$latest_event_season = $season['name'];
			break;
		}
	}
	$teams               = $event->get_constitution(
		array(
			'season'    => $latest_season,
			'oldseason' => $latest_event_season,
		)
	);
	$constitution_action = 'update';
	$constitution_exists = true;
	if ( ! $teams ) {
		$teams               = $event->build_constitution( array( 'season' => $latest_event_season ) );
		$constitution_action = 'insert';
		$constitution_exists = false;
	}
	$leagues         = $event->get_leagues();
	$standing_status = Racketmanager_Util::get_standing_status();
	?>
	<h2 class="header"><?php esc_html_e( 'Constitution', 'racketmanager' ); ?> - <?php echo esc_html( $latest_season ); ?></h2>
	<form id="teams-filter" method="post" action="">
		<div>
			<input type="submit" value="<?php esc_html_e( 'Save', 'racketmanager' ); ?>" name="saveconstitution" id="saveconstitution" class="btn btn-primary action" />
			<a id="addTeams" class="btn btn-secondary" href="admin.php?page=racketmanager&amp;subpage=teams&amp;league_id=<?php echo esc_html( end( $leagues )->id ); ?>&amp;season=<?php echo esc_html( $latest_season ); ?>&amp;view=constitution"><?php esc_html_e( 'Add Teams', 'racketmanager' ); ?></a>
			<?php
			if ( $constitution_exists ) {
				?>
				<input type="submit" value="<?php esc_html_e( 'Generate Matches', 'racketmanager' ); ?>" name="generate_matches" id="generate_matches" class="btn btn-secondary action" />
				<button id="emailConstitution" class="btn btn-secondary" onclick="Racketmanager.emailConstitution(event, <?php echo esc_attr( $event->id ); ?> )"><?php esc_html_e( 'Email Constitution', 'racketmanager' ); ?></button>
				<span class="notifymessage" id="notifyMessage-constitution"></span>
				<?php
			}
			?>
			<span id="notifyMessage"></span>
		</div>
		<?php wp_nonce_field( 'constitution-bulk', 'racketmanager_nonce' ); ?>

		<input type="hidden" name="js-active" value="0" class="js-active" />
		<input type="hidden" name="constitutionAction" value="<?php echo esc_html( $constitution_action ); ?>" />
		<input type="hidden" name="event_id" value="<?php echo esc_html( $event_id ); ?>" />
		<input type="hidden" name="latest_season" id="latest_season" value="<?php echo esc_html( $latest_season ); ?>" />
		<input type="hidden" name="latest_event_season" value="<?php echo esc_html( $latest_event_season ); ?>" />
		<div class="tablenav">
			<!-- Bulk Actions -->
			<select name="action" size="1">
				<option value="-1" selected="selected"><?php esc_html_e( 'Bulk Actions', 'racketmanager' ); ?></option>
				<option value="delete"><?php esc_html_e( 'Delete', 'racketmanager' ); ?></option>
			</select>
			<input type="submit" value="<?php esc_html_e( 'Apply', 'racketmanager' ); ?>" name="doactionconstitution" id="doactionconstitution" class="btn btn-secondary action" />
		</div>

		<table class="widefat" title="RacketManager" aria-label="constitution table">
			<thead>
				<tr>
					<th scope="col" class="check-column"><input type="checkbox" id="check-all-teams" onclick="Racketmanager.checkAll(document.getElementById('leagues-filter'));" /></th>
					<th scope="col"><?php esc_html_e( 'Previous League', 'racketmanager' ); ?></th>
					<th scope="col"><?php esc_html_e( 'New League', 'racketmanager' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Team', 'racketmanager' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Status', 'racketmanager' ); ?></th>
					<th scope="col" class="column-num"><?php esc_html_e( 'Previous Rank', 'racketmanager' ); ?></th>
					<th scope="col" class="column-num"><?php esc_html_e( 'Rank', 'racketmanager' ); ?></th>
					<th scope="col" class="column-num"><?php esc_html_e( 'Points', 'racketmanager' ); ?></th>
					<th scope="col" class="column-num"><?php esc_html_e( '+/- Points', 'racketmanager' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Entered', 'racketmanager' ); ?></th>
				</tr>
			</thead>
			<tbody id="the-list" class="standings-table sortable">
				<?php
				if ( $teams ) {
					$class = '';
					foreach ( $teams as $team ) {
						$class = ( 'alternate' === $class ) ? '' : 'alternate';
						?>
						<tr class="<?php echo esc_html( $class ); ?>">
							<th scope="row" class="check-column">
								<input type="checkbox" value="<?php echo esc_html( $team->table_id ); ?>" name="table[<?php echo esc_html( $team->table_id ); ?>]" />
								<input type="hidden" name="table_id[<?php echo esc_html( $team->table_id ); ?>]" value="<?php echo esc_html( $team->table_id ); ?>" />
							</th>
							<td>
								<?php echo esc_html( $team->old_league_title ); ?>
								<input type="hidden" name="original_league_id[<?php echo esc_html( $team->table_id ); ?>]" value=<?php echo esc_html( $team->old_league_id ); ?> />
							</td>
							<td>
								<select size=1 name="league_id[<?php echo esc_html( $team->table_id ); ?>]">
									<?php foreach ( $leagues as $i => $league ) { ?>
										<option value="<?php echo esc_html( $league->id ); ?>" <?php selected( $league->id, $team->league_id ); ?>><?php echo esc_html( $league->title ); ?></option>
									<?php } ?>
								</select>
							</td>
							<td>
								<?php echo esc_html( $team->title ); ?>
								<input type="hidden" name="team_id[<?php echo esc_html( $team->table_id ); ?>]" id="team_id[<?php echo esc_html( $team->table_id ); ?>]" value=<?php echo esc_html( $team->team_id ); ?> />
							</td>
							<td>
								<select size=1 name="status[<?php echo esc_html( $team->table_id ); ?>]">
									<option value="" <?php selected( '', $team->status ); ?>></option>
									<?php
									foreach ( $standing_status as $key => $value ) {
										?>
										<option value="<?php echo esc_html( $key ); ?>" <?php selected( $key, $team->status ); ?>><?php echo esc_html( $value ); ?></option>
										<?php
									}
									?>
								</select>
							</td>
							<td class="column-num">
								<?php echo esc_html( $team->old_rank ); ?>
								<input type="hidden" name="old_rank[<?php echo esc_html( $team->table_id ); ?>]" id="old_rank[<?php echo esc_html( $team->table_id ); ?>]" value=<?php echo esc_html( $team->old_rank ); ?> />
							</td>
							<td class="column-num">
								<input type="text" size="2" class="rank-input" name="rank[<?php echo esc_html( $team->table_id ); ?>]" id="rank[<?php echo esc_html( $team->table_id ); ?>]" value=<?php echo esc_html( $team->rank ); ?> />
							</td>
							<td class="column-num" name="points[<?php echo esc_html( $team->table_id ); ?>]">
								<?php echo esc_html( $team->points_plus + $team->add_points ); ?>
								<input type="hidden" name="points_plus[<?php echo esc_html( $team->table_id ); ?>]" value=<?php echo esc_html( $team->points_plus ); ?> />
							</td>
							<td class="column-num">
								<?php echo esc_html( $team->add_points ); ?>
								<input type="hidden" name="add_points[<?php echo esc_html( $team->table_id ); ?>]" value=<?php echo esc_html( $team->add_points ); ?> />
							</td>
							<td>
								<select size=1 name="profile[<?php echo esc_html( $team->table_id ); ?>]">
									<option value="0" <?php selected( '0', $team->profile ); ?>><?php esc_html_e( 'Pending', 'racketmanager' ); ?></option>
									<option value="1" <?php selected( '1', $team->profile ); ?>><?php esc_html_e( 'Confirmed', 'racketmanager' ); ?></option>
									<option value="2" <?php selected( '2', $team->profile ); ?>><?php esc_html_e( 'New team', 'racketmanager' ); ?></option>
									<option value="3" <?php selected( '3', $team->profile ); ?>><?php esc_html_e( 'Withdrawn', 'racketmanager' ); ?></option>
								</select>
							</td>
						</tr>
					<?php } ?>
				<?php } ?>
			</tbody>
		</table>
	</form>
	<?php
	if ( ! $updateable ) {
		?>
		<script>
		jQuery("#constitution").find("*").prop('disabled', true);
		jQuery("#constitution").addClass("disabledButton");
		</script>
		<?php
	}
}
?>
