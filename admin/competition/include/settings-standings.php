<?php
/**
 * RacketManager Admin competitions standings settings page
 *
 * @author Paul Moffat
 * @package Racketmanager_admin
 */

namespace Racketmanager;

?>
<div class="form-group">
	<?php
	$i = 0;
	foreach ( Racketmanager_Util::get_standings_display_options() as $key => $label ) {
		++$i;
		?>
		<div class="form-check">
			<input type="checkbox" class="form-check-input" name="settings[standings][<?php echo esc_html( $key ); ?>]" id="standings_<?php echo esc_html( $key ); ?>" value="1" <?php checked( 1, $competition->standings[ $key ] ); ?> />
			<label for="standings_<?php echo esc_html( $key ); ?>" class="form-check-label"><?php echo esc_html( $label ); ?></label>
		</div>
		<?php
	}
	?>
</div>
<div class="form-floating mb-3 col-12 col-xl-2">
	<input class="form-control" type="number" step="1" min="0" class="small-text" name="settings[num_ascend]" id="teams_ascend" value="<?php echo esc_html( $competition->num_ascend ); ?>" size="2" />
	<label for="teams_ascend"><?php esc_html_e( 'Teams Ascend', 'racketmanager' ); ?></label>
	<div class="form-hint">
		<?php esc_html_e( 'Number of Teams that ascend into higher league', 'racketmanager' ); ?>
	</div>
</div>
<div class="form-floating mb-3 col-12 col-xl-2">
	<input class="form-control" type="number" step="1" min="0" class="small-text" name="settings[num_descend]" id="teams_descend" value="<?php echo esc_html( $competition->num_descend ); ?>" size="2" />
	<label for="teams_descend"><?php esc_html_e( 'Teams Descend', 'racketmanager' ); ?></label>
	<div class="form-hint">
		<?php esc_html_e( 'Number of Teams that descend into lower league', 'racketmanager' ); ?>
	</div>
</div>
<div class="form-floating mb-3 col-12 col-xl-2">
	<input class="form-control" type="number" step="1" min="0" class="small-text" name="settings[num_relegation]" id="teams_relegation" value="<?php echo esc_html( $competition->num_relegation ); ?>" size="2" />
	<label for="teams_relegation"><?php esc_html_e( 'Teams Relegation', 'racketmanager' ); ?></label>
	<div class="form-hint">
		<?php esc_html_e( 'Number of Teams that need to go into relegation', 'racketmanager' ); ?>
	</div>
</div>
