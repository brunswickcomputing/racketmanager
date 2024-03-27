<?php
/**
 * Competition events administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

?>
<div class='container' >

	<form id='events-filter' method='post' action='' class='form-control mb-3'>
		<?php wp_nonce_field( 'racketmanager__events-bulk', 'racketmanager_nonce' ); ?>

		<input type="hidden" name="competition_id" value="<?php echo esc_html( $competition_id ); ?>" />
		<div class="tablenav">
			<!-- Bulk Actions -->
			<select name="action" size="1">
				<option value="-1" selected="selected"><?php esc_html_e( 'Bulk Actions', 'racketmanager' ); ?></option>
				<option value="delete"><?php esc_html_e( 'Delete', 'racketmanager' ); ?></option>
			</select>
			<input type="submit" value="<?php esc_html_e( 'Apply', 'racketmanager' ); ?>" name="doactionevent" id="doactionevent" class="btn btn-secondary action" />
		</div>

		<div class="container">
			<div class="row table-header">
				<div class="col-2 col-lg-1 check-column"><input type="checkbox" id="check-all-events" onclick="Racketmanager.checkAll(document.getElementById('events-filter'));" /></div>
				<div class="d-none d-lg-1 col-1 column-num">ID</div>
				<div class="col-4"><?php esc_html_e( 'Event', 'racketmanager' ); ?></div>
				<div class="col-3 col-lg-1 text-center"><?php esc_html_e( 'Number of sets', 'racketmanager' ); ?></div>
				<div class="col-3 col-lg-1 text-center"><?php esc_html_e( 'Number of rubbers', 'racketmanager' ); ?></div>
				<div class="col-3 centered"><?php esc_html_e( 'Type', 'racketmanager' ); ?></div>
			</div>

			<?php
			$events = $competition->get_events();
			if ( $events ) {
				$class = '';
				foreach ( $events as $event ) {
					$event      = get_event( $event );
					$class      = ( 'alternate' === $class ) ? '' : 'alternate';
					$event_link = 'admin.php?page=racketmanager&amp;subpage=show-event&amp;event_id=' . $event->id . '&amp;season=' . $season;
					if ( ! empty( $tournament ) ) {
						$event_link .= '&amp;tournament=' . $tournament->id;
					}
					?>
					<div class="row table-row <?php echo esc_html( $class ); ?>">
						<div class="col-2 col-lg-1 check-column"><input type="checkbox" value="<?php echo esc_html( $event->id ); ?>" name="event[<?php echo esc_html( $event->id ); ?>]" /></div>
						<div class="d-none d-lg-1 col-1 column-num"><?php echo esc_html( $event->id ); ?></div>
						<div class="col-4"><a href="<?php echo esc_html( $event_link ); ?>"><?php echo esc_html( $event->name ); ?></a></div>
						<div class="col-3 col-lg-1 text-center"><?php echo esc_html( $event->num_sets ); ?></div>
						<div class="col-3 col-lg-1 text-center"><?php echo esc_html( $event->num_rubbers ); ?></div>
						<div class="col-3 centered"><?php echo esc_html( Racketmanager_Util::get_event_type( $event->type ) ); ?>
						</div>
					</div>
				<?php } ?>
			<?php } ?>
		</form>
	</div>

	<?php
	if ( empty( $tournament ) ) {
		?>
		<!-- Add New Event -->
		<?php
		if ( ! $event_id ) {
			$form_action = __( 'Add Event', 'racketmanager' );
		} else {
			$form_action = __( 'Update Event', 'racketmanager' );
		}
		?>

		<h3><?php echo esc_html( $form_action ); ?></h3>
		<form action="admin.php?page=racketmanager&subpage=show-competition&competition_id=<?php echo esc_html( $competition_id ); ?>" method="post" class="form-control">
			<?php wp_nonce_field( 'racketmanager_add-event', 'racketmanager_nonce' ); ?>
			<input type="hidden" name="competition_id" value="<?php echo esc_html( $competition_id ); ?>" />
			<input type="hidden" name="event_id" value="<?php echo esc_html( $event_id ); ?>" />
			<div class="form-floating mb-3">
				<input type="text" class="form-control" required="required" placeholder="<?php esc_html_e( 'Enter new event name', 'racketmanager' ); ?>"name="event_title" id="event_title" value="<?php echo esc_html( $event_title ); ?>" size="30" />
				<label for="event_title"><?php esc_html_e( 'Event name', 'racketmanager' ); ?></label>
			</div>
			<div class="row g-1">
				<div class="col-md">
					<div class="form-floating mb-3">
						<input class="form-control" required="required" placeholder="<?php esc_html_e( 'How many sets', 'racketmanager' ); ?>" type='number' name='num_sets' id='num_sets' value='' size='3' />
						<label for='num_sets'><?php esc_html_e( 'Number of Sets', 'racketmanager' ); ?></label>
					</div>
				</div>
				<div class="col-md">
					<div class="form-floating mb-3">
						<input class="form-control" required="required" placeholder="<?php esc_html_e( 'How many rubbers', 'racketmanager' ); ?>" type='number' name='num_rubbers' id='num_rubbers' value='' size='3' />
						<label for='num_rubbers'><?php esc_html_e( 'Number of Rubbers', 'racketmanager' ); ?></label>
					</div>
				</div>
			</div>
			<div class="form-floating mb-3">
				<select class="form-select" size='1' required="required" name='type' id='type'>
					<option value=""><?php esc_html_e( 'Select event type', 'racketmanager' ); ?></option>
					<?php
					$event_types = Racketmanager_Util::get_event_types();
					foreach ( $event_types as $key => $event_type ) {
						?>
						<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $event_type ); ?></option>
						<?php
					}
					?>
				</select>
				<label for='type'><?php esc_html_e( 'Competition Type', 'racketmanager' ); ?></label>
			</div>
			<div class="form-group mb-3">
				<input type="submit" name="addEvent" value="<?php echo esc_html( $form_action ); ?>" class="btn btn-primary" />
			</div>
		</form>
		<?php
	}
	?>
</div>
