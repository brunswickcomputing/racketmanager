<?php
/**
 * Event schedule administration panel
 *
 * @package Racketmanager/Admin
 */

namespace Racketmanager;

$class = '';
?>
<div class="container">
	<h1><?php esc_html_e( 'Schedule matches', 'racketmanager' ); ?></h1>
	<form action="" method="post" enctype="multipart/form-data" name="scheduleEvents" id="scheduleEvents" class="form-control">
		<?php wp_nonce_field( 'racketmanager_schedule-matches', 'racketmanager_nonce' ); ?>
		<div class="tablenav">
			<!-- Bulk Actions -->
			<select name="action" size="1">
				<option value="-1" selected="selected"><?php esc_html_e( 'Bulk Actions', 'racketmanager' ); ?></option>
				<option value="delete"><?php esc_html_e( 'Delete', 'racketmanager' ); ?></option>
			</select>
			<input type="submit" value="<?php esc_html_e( 'Apply', 'racketmanager' ); ?>" name="doDeleteEventMatches" id="doDeleteEventMatches" class="btn btn-secondary action" />
		</div>
		<div class="container mb-3">
			<div class="row table-header">
				<div class="col-1 check-column"><input type="checkbox" id="check-all-events" onclick="Racketmanager.checkAll(document.getElementById('scheduleEvents'));" /></div>
				<div class="col-1 column-num">ID</div>
				<div class="col-4"><?php esc_html_e( 'Name', 'racketmanager' ); ?></div>
			</div>
			<?php
			foreach ( $competitions as $competition ) {
				foreach ( $competition->events as $event ) {
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
					?>
					<div class="row table-row <?php echo esc_html( $class ); ?>">
						<div class="col-1 check-column">
							<input type="checkbox" value="<?php echo esc_html( $event->id ); ?>" name="event[<?php echo esc_html( $event->id ); ?>]" />
						</div>
						<div class="col-1 column-num"><?php echo esc_html( $event->id ); ?></div>
						<div class="col-3"><a href="admin.php?page=racketmanager&amp;subpage=show-event&amp;event_id=<?php echo esc_html( $event->id ); ?>"><?php echo esc_html( $event->name ); ?></a></div>
						<?php if ( 0 !== $match_count ) { ?>
							<div class="col-3 col-md-auto">
								<a class="btn btn-secondary" href="admin.php?page=racketmanager&amp;subpage=show-event&amp;event_id=<?php echo esc_html( $event->id ); ?>&amp;view=matches"><?php esc_html_e( 'View matches', 'racketmanager' ); ?></a>
							</div>
							<?php if ( 0 !== $match_completion_count ) { ?>
								<div class="col-3 col-md-auto ms-3">
									<a class="btn btn-secondary" onclick="Racketmanager.sendFixtures('<?php echo esc_html( $event->id ); ?>');">
								<?php esc_html_e( 'Send fixtures', 'racketmanager' ); ?></a>
						</div>
						<div class="col-auto"><span id="notifyMessage-<?php echo esc_html( $event->id ); ?>"></span></div>
							<?php } ?>
						<?php } ?>
					</div>
					<?php
				}
			}
			?>
		</div>
		<input type="submit" value="<?php esc_html_e( 'Schedule', 'racketmanager' ); ?>" name="doScheduleEvents" id="doScheduleEvents" class="btn btn-primary action" />
	</form>
</div>
