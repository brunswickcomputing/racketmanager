<?php
/**
 * Display options administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

?>
<div class="form-control">
	<fieldset class="form-control mb-3">
		<legend class="form-check-label"><?php esc_html_e( 'WTN', 'racketmanager' ); ?></legend>
		<div class="form-check form-check-inline">
			<input type="checkbox" class="form-check-input" name="wtnDisplay" id="wtnDisplayTrue" value="true" <?php checked( true, !empty($options['display']['wtn'])); ?> />
			<label class="form-check-label" for="wtnDisplayTrue"><?php esc_html_e( 'True', 'racketmanager' ); ?></label>
		</div>
	</fieldset>
</div>
