<?php
/**
 * Event Settings general administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

$event->num_sets    = isset( $event->num_sets ) ? $event->num_sets : '';
$event->num_rubbers = isset( $event->num_rubbers ) ? $event->num_rubbers : '';
$event->type        = isset( $event->type ) ? $event->type : '';
$event->scoring     = isset( $event->scoring ) ? $event->scoring : 'TB';
$event->offset      = isset( $event->offset ) ? $event->offset : '0';

?>
<div class="form-floating mb-3">
	<input type="text" class="form-control" name="event_title" id="event_title" value="<?php echo esc_html( $event->name ); ?>" size="30" />
	<label for="event_title"><?php esc_html_e( 'Event name', 'racketmanager' ); ?></label>
</div>
		<div class="form-floating mb-3 col-12 col-xl-2">
			<input class="form-control" type='number' name='settings[num_sets]' id='num_sets' value='<?php echo esc_html( $event->num_sets ); ?>' size='3' />
			<label for='num_sets'><?php esc_html_e( 'Number of Sets', 'racketmanager' ); ?></label>
		</div>
		<div class="form-floating mb-3 col-12 col-xl-2">
			<input class="form-control" type='number' name='settings[num_rubbers]' id='num_rubbers' value='<?php echo esc_html( $event->num_rubbers ); ?>' size='3' />
			<label for='num_rubbers'><?php esc_html_e( 'Number of Rubbers', 'racketmanager' ); ?></label>
		</div>
		<div class="form-check form-check-inline mb-3">
			<input class="form-check-input" type="radio" name='settings[reverse_rubbers]' id='reverse_rubbers_false' value="0" <?php checked( '0', $event->reverse_rubbers ); ?>>
			<label for='reverse_rubbers_false'><?php esc_html_e( 'Single rubbers', 'racketmanager' ); ?></label>
		</div>
		<div class="form-check form-check-inline mb-3">
			<input class="form-check-input" type="radio" name='settings[reverse_rubbers]' id='reverse_rubbers_true' value= "1" <?php checked( '1', $event->reverse_rubbers ); ?>>
			<label for='reverse_rubbers_true'><?php esc_html_e( 'Reverse rubbers', 'racketmanager' ); ?></label>
		</div>
		<div class="form-floating mb-3 col-12 col-xl-2">
			<select class="form-select" size='1' name='settings[type]' id='type'>
				<?php
				$event_types = Racketmanager_Util::get_event_types();
				foreach ( $event_types as $key => $event_type ) {
					?>
					<option value="<?php echo esc_attr( $key ); ?>"<?php selected( $event->type, $key ); ?>><?php echo esc_html( $event_type ); ?></option>
					<?php
				}
				?>
			</select>
			<label for='type'><?php esc_html_e( 'Type', 'racketmanager' ); ?></label>
		</div>
		<div class="form-floating mb-3 col-12 col-xl-2">
			<select class="form-select" size='1' name='settings[scoring]' id='scoring'>
				<?php
				foreach ( Racketmanager_Util::get_scoring_types() as $i => $scoring_type ) {
					?>
					<option value="<?php echo esc_html( $i ); ?>"<?php selected( $i, $event->scoring ); ?>><?php echo esc_html( $scoring_type ); ?></option>
					<?php
				}
				?>
			</select>
			<label for='scoring'><?php esc_html_e( 'Scoring Format', 'racketmanager' ); ?></label>
		</div>
		<div class="form-floating mb-3 col-12 col-xl-2">
			<select class="form-select" size='1' name='settings[offset]' id='offset'>
				<option value='0' <?php selected( $event->offset, '0' ); ?>><?php esc_html_e( 'Week 0', 'racketmanager' ); ?></option>
				<option value='1' <?php selected( $event->offset, '1' ); ?>><?php esc_html_e( 'Week 1', 'racketmanager' ); ?></option>
				<option value='2' <?php selected( $event->offset, '2' ); ?>><?php esc_html_e( 'Week 2', 'racketmanager' ); ?></option>
			</select>
			<label for='offset'><?php esc_html_e( 'Offset week', 'racketmanager' ); ?></label>
		</div>
		<div class="form-floating mb-3 col-12 col-xl-2">
			<select class="form-select" size='1' name='settings[age_limit]' id='age_limit'>
				<option value='open' <?php selected( $event->age_limit, 'open' ); ?>><?php esc_html_e( 'Open', 'racketmanager' ); ?></option>
				<option value='40' <?php selected( $event->age_limit, '40' ); ?>><?php esc_html_e( '40+', 'racketmanager' ); ?></option>
				<option value='45' <?php selected( $event->age_limit, '45' ); ?>><?php esc_html_e( '45+', 'racketmanager' ); ?></option>
				<option value='50' <?php selected( $event->age_limit, '50' ); ?>><?php esc_html_e( '50+', 'racketmanager' ); ?></option>
				<option value='55' <?php selected( $event->age_limit, '55' ); ?>><?php esc_html_e( '55+', 'racketmanager' ); ?></option>
				<option value='60' <?php selected( $event->age_limit, '60' ); ?>><?php esc_html_e( '60+', 'racketmanager' ); ?></option>
				<option value='65' <?php selected( $event->age_limit, '65' ); ?>><?php esc_html_e( '65+', 'racketmanager' ); ?></option>
				<option value='8' <?php selected( $event->age_limit, '8' ); ?>><?php esc_html_e( '8U', 'racketmanager' ); ?></option>
				<option value='9' <?php selected( $event->age_limit, '9' ); ?>><?php esc_html_e( '9U', 'racketmanager' ); ?></option>
				<option value='10' <?php selected( $event->age_limit, '10' ); ?>><?php esc_html_e( '10U', 'racketmanager' ); ?></option>
				<option value='11' <?php selected( $event->age_limit, '11' ); ?>><?php esc_html_e( '11U', 'racketmanager' ); ?></option>
				<option value='12' <?php selected( $event->age_limit, '12' ); ?>><?php esc_html_e( '12U', 'racketmanager' ); ?></option>
				<option value='14' <?php selected( $event->age_limit, '14' ); ?>><?php esc_html_e( '14U', 'racketmanager' ); ?></option>
				<option value='16' <?php selected( $event->age_limit, '16' ); ?>><?php esc_html_e( '16U', 'racketmanager' ); ?></option>
				<option value='18' <?php selected( $event->age_limit, '18' ); ?>><?php esc_html_e( '18U', 'racketmanager' ); ?></option>
			</select>
			<label for='age_limit'><?php esc_html_e( 'Age limit', 'racketmanager' ); ?></label>
		</div>
		<div class="form-floating mb-3 col-12 col-xl-2">
			<select class="form-select" size='1' name='settings[age_offset]' id='age_offset'>
				<option value='none' <?php selected( $event->age_offset, 'none' ); ?>><?php esc_html_e( 'None', 'racketmanager' ); ?></option>
				<option value='5' <?php selected( $event->age_offset, '5' ); ?>><?php esc_html_e( '5 years for ladies', 'racketmanager' ); ?></option>
			</select>
			<label for='age_offset'><?php esc_html_e( 'Mixed age offset', 'racketmanager' ); ?></label>
		</div>
		<div class="form-group">
			<div class="form-label">
				<?php esc_html_e( 'Match days allowed', 'racketmanager' ); ?>
			</div>
			<?php
			$match_days = Racketmanager_Util::get_match_days();
			foreach ( $match_days as $key => $label ) {
				?>
				<div class="form-check">
					<input type="checkbox" class="form-check-input" name="settings[match_days_allowed][<?php echo esc_html( $key ); ?>]" id="match_days_allowed_<?php echo esc_html( $key ); ?>" value="1" <?php isset( $event->match_days_allowed[ $key ] ) ? checked( 1, $event->match_days_allowed[ $key ] ) : null; ?> />
					<label for="match_days_allowed_<?php echo esc_html( $key ); ?>" class="form-check-label"><?php echo esc_html( $label ); ?></label>
				</div>
				<?php
			}
			?>
		</div>

		<?php
