<?php
/**
 * Competition Settings availability administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

ksort( $rules_options );
?>
<div class="form-control">
	<?php
	foreach ( $rules_options as $option => $value ) {
		?>
		<div class="form-check">
			<input type="checkbox" class="form-check-input" name="rules[<?php echo esc_html( $option ); ?>]" id="rules-<?php echo esc_html( $option ); ?>" value="1" <?php empty( $competition->config->rules[ $option ] ) ? null : checked( 1, $competition->config->rules[ $option ] ); ?> <?php echo empty( $value ) ? 'disabled' : null; ?> />
			<label class="form-check-label" for="rules-<?php echo esc_html( $option ); ?>"><?php echo esc_html( ucfirst( $option ) ); ?></label>
		</div>
		<?php
	}
	?>
</div>
