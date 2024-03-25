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
		<div class="form-floating mb-3 col-12 col-xl-2">
			<select class="form-select" size='1' name='settings[type]' id='type'>
				<option value='WS' <?php selected( $event->type, 'WS' ); ?>><?php esc_html_e( 'Ladies Singles', 'racketmanager' ); ?></option>
				<option value='WD' <?php selected( $event->type, 'WD' ); ?>><?php esc_html_e( 'Ladies Doubles', 'racketmanager' ); ?></option>
				<option value='MS' <?php selected( $event->type, 'MS' ); ?>><?php esc_html_e( 'Mens Singles', 'racketmanager' ); ?></option>
				<option value='MD' <?php selected( $event->type, 'MD' ); ?>><?php esc_html_e( 'Mens Doubles', 'racketmanager' ); ?></option>
				<option value='XD' <?php selected( $event->type, 'XD' ); ?>><?php esc_html_e( 'Mixed Doubles', 'racketmanager' ); ?></option>
				<option value='LD' <?php selected( $event->type, 'LD' ); ?>><?php esc_html_e( 'The League', 'racketmanager' ); ?></option>
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
