<?php
/**
 * Competition events administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

global $racketmanager;
?>
<div>

	<form id='schedule-filter' method='post' action='' class='form-control mb-3'>
		<?php wp_nonce_field( 'racketmanager_schedule-matches', 'racketmanager_nonce' ); ?>

		<input type="hidden" name="competition_id" value="<?php echo esc_html( $competition_id ); ?>" />
		<div class="tablenav">
			<!-- Bulk Actions -->
			<select name="actionSchedule" size="1">
				<option value="-1" selected="selected"><?php esc_html_e( 'Bulk Actions', 'racketmanager' ); ?></option>
				<option value="schedule"><?php esc_html_e( 'Schedule matches', 'racketmanager' ); ?></option>
				<option value="delete"><?php esc_html_e( 'Delete matches', 'racketmanager' ); ?></option>
			</select>
			<input type="submit" value="<?php esc_html_e( 'Apply', 'racketmanager' ); ?>" name="scheduleAction" id="scheduleAction" class="btn btn-secondary action" />
		</div>

		<div class="container">
			<div class="row table-header">
				<div class="col-2 col-lg-1 check-column"><input type="checkbox" id="check-all-schedules" onclick="Racketmanager.checkAll(document.getElementById('schedule-filter'));" /></div>
				<div class="d-none d-lg-1 col-1 column-num">ID</div>
				<div class="col-4"><?php esc_html_e( 'Event', 'racketmanager' ); ?></div>
			</div>

			<?php
			$events = $competition->get_events();
			if ( $events ) {
				$class = '';
				foreach ( $events as $event ) {
					$event                  = get_event( $event );
					$match_count            = $racketmanager->get_matches(
						array(
							'count'    => true,
							'event_id' => $event->id,
							'season'   => $event->get_season(),
						)
					);
					$match_completion_count = $racketmanager->get_matches(
						array(
							'count'    => true,
							'event_id' => $event->id,
							'season'   => $event->get_season(),
							'time'     => 'latest',
						)
					);
					$class                  = ( 'alternate' === $class ) ? '' : 'alternate';
					$event_link             = 'admin.php?page=racketmanager&amp;subpage=show-event&amp;event_id=' . $event->id . '&amp;season=' . $event->get_season();
					if ( ! empty( $tournament ) ) {
						$event_link .= '&amp;tournament=' . $tournament->id;
					}
					?>
					<div class="row table-row <?php echo esc_html( $class ); ?>">
						<div class="col-2 col-lg-1 check-column"><input type="checkbox" value="<?php echo esc_html( $event->id ); ?>" name="event[<?php echo esc_html( $event->id ); ?>]" /></div>
						<div class="d-none d-lg-1 col-1 column-num"><?php echo esc_html( $event->id ); ?></div>
						<div class="col-4"><a href="<?php echo esc_html( $event_link ); ?>"><?php echo esc_html( $event->name ); ?></a></div>
						<?php
						if ( ! empty( $match_count ) ) {
							?>
							<div class="col-3 col-md-auto">
								<a class="btn btn-secondary" href="admin.php?page=racketmanager&amp;subpage=show-event&amp;event_id=<?php echo esc_html( $event->id ); ?>&amp;view=matches"><?php esc_html_e( 'View matches', 'racketmanager' ); ?></a>
							</div>
							<?php
							if ( empty( $match_completion_count ) ) {
								?>
								<div class="col-3 col-md-auto ms-3">
									<a class="btn btn-secondary" onclick="Racketmanager.sendFixtures('<?php echo esc_html( $event->id ); ?>');"><?php esc_html_e( 'Send fixtures', 'racketmanager' ); ?></a>
								</div>
								<div class="col-auto"><span id="notifyMessage-<?php echo esc_html( $event->id ); ?>"></span></div>
								<?php
							}
							?>
							<?php
						}
						?>
					</div>
					<?php
				}
			}
			?>
		</form>
	</div>
</div>
