<?php
/**
 * Event Settings general administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

$tab_name = 'general';
?>
<div class="form-control">
	<div class="row gx-3 mb-3">
		<div class="form-floating">
			<?php
			if ( ! empty( $racketmanager->error_fields ) && is_numeric( array_search( 'name', $racketmanager->error_fields, true ) ) ) {
				$error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
				$is_invalid = true;
				$msg_id     = array_search( 'name', $racketmanager->error_fields, true );
				$msg        = isset( $racketmanager->error_messages[ $msg_id ] ) ? $racketmanager->error_messages[ $msg_id ] : null;
			}
			?>
			<input type="text" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="event_name" id="event_name" value="<?php echo isset( $event->name ) ? esc_html( $event->name ) : null; ?>"  placeholder="<?php esc_html_e( 'Event name', 'racketmanager' ); ?>" />
			<label for="event_name"><?php esc_html_e( 'Event name', 'racketmanager' ); ?></label>
			<?php
			if ( $is_invalid ) {
				?>
				<div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
				<?php
				$is_invalid = false;
				$msg        = null;
			}
			?>
		</div>	
	</div>
	<div class="row gx-3 mb-3">
		<div class="col-md-4 mb-3 mb-md-0">
			<div class="form-floating">
				<?php
				$types = Racketmanager_Util::get_event_types();
				if ( ! empty( $racketmanager->error_fields ) && is_numeric( array_search( 'type', $racketmanager->error_fields, true ) ) ) {
					$error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
					$is_invalid = true;
					$msg_id     = array_search( 'type', $racketmanager->error_fields, true );
					$msg        = isset( $racketmanager->error_messages[ $msg_id ] ) ? $racketmanager->error_messages[ $msg_id ] : null;
				}
				?>
				<select class="form-select <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="type" id="type" onchange="Racketmanager.setEventName(event)">
					<option disabled <?php selected( null, empty( $event->type ) ? null : $event->type ); ?>><?php esc_html_e( 'Select type', 'racketmanager' ); ?></option>
					<?php
					foreach ( $types as $event_type => $type_desc ) {
						?>
						<option value="<?php echo esc_html( $event_type ); ?>" <?php selected( $event_type, empty( $event->type ) ? null : $event->type ); ?>><?php echo esc_html( ucfirst( $type_desc ) ); ?></option>
						<?php
					}
					?>
				</select>
				<label for="type" class="form-label"><?php esc_html_e( 'Type', 'racketmanager' ); ?></label>
				<?php
				if ( $is_invalid ) {
					?>
					<div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
					<?php
					$is_invalid = false;
					$msg        = null;
				}
				?>
			</div>
		</div>
		<div class="col-md-4 mb-3 mb-md-0">
			<div class="form-floating">
				<?php
				$age_limits = Racketmanager_Util::get_age_limits();
				if ( ! empty( $racketmanager->error_fields ) && is_numeric( array_search( 'age_limit', $racketmanager->error_fields, true ) ) ) {
					$error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
					$is_invalid = true;
					$msg_id     = array_search( 'age_limit', $racketmanager->error_fields, true );
					$msg        = isset( $racketmanager->error_messages[ $msg_id ] ) ? $racketmanager->error_messages[ $msg_id ] : null;
				}
				?>
				<select class="form-select <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name='age_limit' id='age_limit' onchange="Racketmanager.setEventName(event)">
					<option disabled <?php selected( null, empty( $event->config->age_limit ) ? null : $event->config->age_limit ); ?>><?php esc_html_e( 'Select age limit', 'racketmanager' ); ?></option>
					<?php
					foreach ( $age_limits as $key => $label ) {
						?>
						<option value="<?php echo esc_attr( $key ); ?>"<?php selected( $key, empty( $event->config->age_limit ) ? null : $event->config->age_limit ); ?>><?php echo esc_html( $label ); ?></option>
						<?php
					}
					?>
				</select>
				<label for='age_limit'><?php esc_html_e( 'Age limit', 'racketmanager' ); ?></label>
				<?php
				if ( $is_invalid ) {
					?>
					<div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
					<?php
					$is_invalid = false;
					$msg        = null;
				}
				?>
			</div>
		</div>
		<div class="col-md-4">
			<div class="form-floating">
				<?php
				if ( ! empty( $racketmanager->error_fields ) && is_numeric( array_search( 'age_offset', $racketmanager->error_fields, true ) ) ) {
					$error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
					$is_invalid = true;
					$msg_id     = array_search( 'age_offset', $racketmanager->error_fields, true );
					$msg        = isset( $racketmanager->error_messages[ $msg_id ] ) ? $racketmanager->error_messages[ $msg_id ] : null;
				}
				?>
				<select class="form-select <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="age_offset" id="age_offset" >
					<option value='0' <?php selected( '0', empty( $event->config->age_offset ) ? null : $event->config->age_offset ); ?>><?php esc_html_e( 'None', 'racketmanager' ); ?></option>
					<option value='5' <?php selected( '5', empty( $event->config->age_offset ) ? null : $event->config->age_offset ); ?>><?php esc_html_e( '5 years for ladies', 'racketmanager' ); ?></option>
				</select>
				<label for="age_offset" class="form-label"><?php esc_html_e( 'Mixed age offset', 'racketmanager' ); ?></label>
				<?php
				if ( $is_invalid ) {
					?>
					<div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
					<?php
					$is_invalid = false;
					$msg        = null;
				}
				?>
			</div>
		</div>
	</div>
	<div class="row gx-3 mb-3">
		<div class="col-md-4 mb-3 mb-md-0">
			<div class="form-floating">
				<?php
				$scoring_types = Racketmanager_Util::get_scoring_types();
				$scoring       = empty( $event->config->scoring ) ? null : $event->config->scoring;
				if ( empty( $scoring ) ) {
					$scoring = isset( $competition->scoring ) ? $competition->scoring : null;
				}
				if ( ! empty( $racketmanager->error_fields ) && is_numeric( array_search( 'scoring', $racketmanager->error_fields, true ) ) ) {
					$error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
					$is_invalid = true;
					$msg_id     = array_search( 'scoring', $racketmanager->error_fields, true );
					$msg        = isset( $racketmanager->error_messages[ $msg_id ] ) ? $racketmanager->error_messages[ $msg_id ] : null;
				}
				?>
				<select class="form-select <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name='scoring' id='scoring'>
					<option disabled <?php selected( null, empty( $scoring ) ? null : $scoring ); ?>><?php esc_html_e( 'Select scoring type', 'racketmanager' ); ?></option>
					<?php
					foreach ( $scoring_types as $key => $label ) {
						?>
						<option value="<?php echo esc_attr( $key ); ?>"<?php selected( $key, empty( $scoring ) ? null : $scoring ); ?>><?php echo esc_html( $label ); ?></option>
						<?php
					}
					?>
				</select>
				<label for='scoring'><?php esc_html_e( 'Scoring format', 'racketmanager' ); ?></label>
				<?php
				if ( $is_invalid ) {
					?>
					<div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
					<?php
					$is_invalid = false;
					$msg        = null;
				}
				?>
			</div>
		</div>
		<div class="col-md-4 mb-3 mb-md-0">
			<div class="form-floating">
				<?php
				$num_sets = empty( $event->config->num_sets ) ? null : $event->config->num_sets;
				if ( empty( $num_sets ) ) {
					$num_sets = isset( $competition->num_sets ) ? $competition->num_sets : null;
				}
				if ( ! empty( $racketmanager->error_fields ) && is_numeric( array_search( 'num_sets', $racketmanager->error_fields, true ) ) ) {
					$error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
					$is_invalid = true;
					$msg_id     = array_search( 'num_sets', $racketmanager->error_fields, true );
					$msg        = isset( $racketmanager->error_messages[ $msg_id ] ) ? $racketmanager->error_messages[ $msg_id ] : null;
				}
				?>
				<input type="number" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="num_sets" id="num_sets" value="<?php echo isset( $num_sets ) ? esc_html( $num_sets ) : null; ?>" placeholder="<?php esc_html_e( 'Number of sets', 'racketmanager' ); ?>" />
				<label for="num_sets"><?php esc_html_e( 'Number of sets', 'racketmanager' ); ?></label>
				<?php
				if ( $is_invalid ) {
					?>
					<div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
					<?php
					$is_invalid = false;
					$msg        = null;
				}
				?>
			</div>
		</div>
		<?php
		if ( $competition->is_player_entry ) {
			?>
			<input type="hidden" name="num_rubbers" id="num_rubbers" value="<?php echo isset( $event->num_rubbers ) ? esc_html( $event->num_rubbers ) : null; ?>" />
			<?php
		} else {
			?>
			<div class="col-md-4">
				<div class="form-floating">
					<?php
					$num_rubbers = empty( $event->config->num_rubbers ) ? null : $event->config->num_rubbers;
					if ( empty( $num_rubbers ) ) {
						$num_rubbers = isset( $competition->num_rubbers ) ? $competition->num_rubbers : null;
					}
					if ( ! empty( $racketmanager->error_fields ) && is_numeric( array_search( 'num_rubbers', $racketmanager->error_fields, true ) ) ) {
						$error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
						$is_invalid = true;
						$msg_id     = array_search( 'num_rubbers', $racketmanager->error_fields, true );
						$msg        = isset( $racketmanager->error_messages[ $msg_id ] ) ? $racketmanager->error_messages[ $msg_id ] : null;
					}
					?>
					<input type="number" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="num_rubbers" id="num_rubbers" value="<?php echo isset( $num_rubbers ) ? esc_html( $num_rubbers ) : null; ?>"  placeholder="<?php esc_html_e( 'Number of sets', 'racketmanager' ); ?>" />
					<label for="num_sets"><?php esc_html_e( 'Number of rubbers', 'racketmanager' ); ?></label>
					<?php
					if ( $is_invalid ) {
						?>
						<div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
						<?php
						$is_invalid = false;
						$msg        = null;
					}
					?>
				</div>
			</div>
			<?php
		}
		?>
	</div>
</div>
