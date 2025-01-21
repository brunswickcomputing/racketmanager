<?php
/**
 * Competition Settings general administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

use Racketmanager\Racketmanager_Util as util;
?>
<div class="form-floating mb-3">
	<input type="text" class="form-control" name="competition_title" id="competition_title" value="<?php echo esc_html( $competition->name ); ?>" size="30" />
	<label for="competition_title"><?php esc_html_e( 'Competition name', 'racketmanager' ); ?></label>
</div>
<div class="form-floating mb-3">
	<select class="form-select" size="1" name="settings[type]" id="settings-type">
		<option><?php esc_html_e( 'Select', 'racketmanager' ); ?></option>
		<?php
		foreach ( Util::get_competition_types() as $racketmanager_id => $competition_type ) {
			?>
			<option value="<?php echo esc_html( $racketmanager_id ); ?>" <?php selected( $competition_type, $competition->type ); ?>><?php echo esc_html( ucfirst( $competition_type ) ); ?></option>
			<?php
		}
		?>
	</select>
	<label for="settings-type"><?php esc_html_e( 'Type', 'racketmanager' ); ?></label>
</div>
<div class="form-floating mb-3">
	<input type="text" class="form-control" name="settings[competition_code]" id="competition_code" value="<?php echo empty( $competition->competition_code ) ? '' : esc_html( $competition->competition_code ); ?>" />
	<label for="competition_code">
		<?php esc_html_e( 'Competition code', 'racketmanager' ); ?>
	</label>
</div>
<div class="form-floating mb-3 col-12 col-xl-2">
	<select class="form-select" size="1" name="settings[sport]" id="sport">
		<?php
		foreach ( Racketmanager_Util::get_sports() as $i => $name ) {
			?>
			<option value="<?php echo esc_html( $i ); ?>"<?php selected( $i, $competition->sport ); ?>><?php echo esc_html( $name ); ?></option>
		<?php } ?>
	</select>
	<label for="sport"><?php esc_html_e( 'Sport', 'racketmanager' ); ?></label>
	<div class="form-hint">
		<?php
		/* translators: %s: admin url */
		printf( __( "Check the <a href='%s'>Documentation</a> for details", 'racketmanager' ), admin_url() . 'admin.php?page=racketmanager-doc' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
	</div>
</div>
<div class="form-floating mb-3 col-12 col-xl-2">
	<select class="form-select" size="1" name="settings[point_rule]" id="point_rule">
		<?php
		foreach ( Racketmanager_Util::get_point_rules() as $i => $point_rule ) {
			?>
			<option value="<?php echo esc_html( $i ); ?>"<?php selected( $i, $competition->point_rule ); ?>><?php echo esc_html( $point_rule ); ?></option>
			<?php
		}
		?>
	</select>
	<label for="point_rule"><?php esc_html_e( 'Point Rule', 'racketmanager' ); ?></label>
	<div class="form-hint">
		<?php
		/* translators: %s: admin url */
		printf( __( "Check the <a href='%s'>Documentation</a> for details", 'racketmanager' ), admin_url() . 'admin.php?page=racketmanager-doc' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
	</div>
</div>
<div class="form-floating mb-3 col-12 col-xl-2">
	<div class="form-label">
		<label for="point_format"><?php esc_html_e( 'Point Format', 'racketmanager' ); ?></label>
	</div>
	<div class="form-input">
		<select size="1" name="settings[point_format]" id="point_format" >
			<?php
			foreach ( Racketmanager_Util::get_point_formats() as $i => $format ) {
				?>
				<option value="<?php echo esc_html( $i ); ?>"<?php selected( $i, $competition->point_format ); ?>><?php echo esc_html( $format ); ?></option>
				<?php
			}
			?>
		</select>
		<select size="1" name="settings[point_format2]" id="point_format2" >
			<?php foreach ( Racketmanager_Util::get_point_formats() as $i => $format ) { ?>
				<option value="<?php echo esc_html( $i ); ?>"<?php selected( $i, $competition->point_format2 ); ?>><?php echo esc_html( $format ); ?></option>
			<?php } ?>
		</select>
	</div>
	<div class="form-hint">
		<?php esc_html_e( 'Point formats for primary and seconday points (e.g. Goals)', 'racketmanager' ); ?>
	</div>
</div>
<div class="form-floating mb-3 col-12 col-xl-2">
	<select class="form-select" size="1" name="settings[team_ranking]" id="team_ranking" >
		<option value="auto"<?php selected( 'auto', $competition->team_ranking ); ?>><?php esc_html_e( 'Automatic', 'racketmanager' ); ?></option>
		<option value="manual"<?php selected( 'manual', $competition->team_ranking ); ?>><?php esc_html_e( 'Manual', 'racketmanager' ); ?></option>
	</select>
	<label for="team_ranking"><?php esc_html_e( 'Team Ranking', 'racketmanager' ); ?></label>
</div>
<div class="form-floating mb-3 col-12 col-xl-2">
	<select class="form-select" size="1" name="settings[mode]" id="mode">
		<?php
		foreach ( Racketmanager_Util::get_modes() as $i => $competition_mode ) {
			?>
				<option value="<?php echo esc_html( $i ); ?>"<?php selected( $i, $competition->mode ); ?>><?php echo esc_html( $competition_mode ); ?></option>
			<?php
		}
		?>
	</select>
	<label for="mode"><?php esc_html_e( 'Mode', 'racketmanager' ); ?></label>
</div>
<div class="form-floating mb-3 col-12 col-xl-2">
	<select class="form-select" size="1" name="settings[entry_type]" id="entry_type">
		<?php
		foreach ( Racketmanager_Util::get_entry_types() as $i => $entry_type ) {
			?>
			<option value="<?php echo esc_html( $i ); ?>"<?php selected( $i, $competition->entry_type ); ?>><?php echo esc_html( $entry_type ); ?></option>
			<?php
		}
		?>
	</select>
	<label for="entry_type"><?php esc_html_e( 'Entry Type', 'racketmanager' ); ?></label>
</div>
<div class="form-label">
	<?php esc_html_e( 'Default start time', 'racketmanager' ); ?>
</div>
<div class="row mb-3">
	<div class="form-floating col-6 col-xl-1">
		<select class="form-select" size="1" name="settings[default_match_start_time][hour]" id="settings[default_match_start_time][hour]">
			<?php
			for ( $hour = 0; $hour <= 23; $hour++ ) {
				?>
				<option value="<?php echo esc_html( str_pad( $hour, 2, 0, STR_PAD_LEFT ) ); ?>"<?php selected( $hour, $competition->default_match_start_time['hour'] ); ?>><?php echo esc_html( str_pad( $hour, 2, 0, STR_PAD_LEFT ) ); ?></option>
				<?php
			}
			?>
		</select>
		<label for="settings[default_match_start_time][hour]"><?php esc_html_e( 'Hour', 'racketmanager' ); ?></label>
	</div>
	<div class="form-floating col-6 col-xl-1">
		<select class="form-select" size="1" name="settings[default_match_start_time][minutes]" id="settings[default_match_start_time][minutes]">
			<?php
			for ( $minute = 0; $minute <= 60; $minute++ ) {
				?>
				<?php
				if ( 0 === $minute % 5 && 60 !== $minute ) {
					?>
						<option value="<?php echo esc_html( str_pad( $minute, 2, 0, STR_PAD_LEFT ) ); ?>"<?php selected( $minute, $competition->default_match_start_time['minutes'] ); ?>><?php echo esc_html( str_pad( $minute, 2, 0, STR_PAD_LEFT ) ); ?></option>
				<?php } ?>
			<?php } ?>
		</select>
		<label for="settings[default_match_start_time][minutes]"><?php esc_html_e( 'Minute', 'racketmanager' ); ?></label>
	</div>
</div>
<div class="form-floating mb-3 col-12 col-xl-2">
	<input type="number" step="1" min="0" class="form-control" name="settings[num_matches_per_page]" id="num_matches_per_page" value="<?php echo esc_html( $competition->num_matches_per_page ); ?>" size="2" />
	<label for="num_matches_per_page"><?php esc_html_e( 'Matches per page', 'racketmanager' ); ?></label>
	<div class="form-hint">
		<?php esc_html_e( 'Number of matches to show per page', 'racketmanager' ); ?>
	</div>
</div>
<div class="form-check mb-3 col-12 col-xl-2">
	<input type="checkbox" class="form-check-input" name="settings[match_day_restriction]" id="match_day_restriction" <?php isset( $competition->match_day_restriction ) ? checked( 'true', $competition->match_day_restriction ) : ''; ?> value="true" />
	<label class="form-check-label" for="match_day_restriction"><?php esc_html_e( 'Match day restriction', 'racketmanager' ); ?></label>
</div>
<div class="form-check mb-3 col-12 col-xl-2">
	<input type="checkbox" class="form-check-input" name="settings[match_day_weekends]" id="match_day_weekends" <?php isset( $competition->match_day_weekends ) ? checked( 'true', $competition->match_day_weekends ) : ''; ?> value="true" />
	<label class="form-check-label" for="match_day_weekends"><?php esc_html_e( 'Weekend match days', 'racketmanager' ); ?></label>
</div>
