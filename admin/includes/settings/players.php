<?php
/**
 * Player options administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

?>
<div class="form-control">
	<div class="form-control mb-3">
		<legend class="form-check-label"><?php esc_html_e( 'Age Limit Check', 'racketmanager' ); ?></legend>
		<div class="form-check form-check-inline">
			<input type="radio" class="form-check-input" name="ageLimitCheck" id="ageLimitCheckTrue" value="true"
			<?php
			if ( isset( $options['checks']['ageLimitCheck'] ) ) {
				echo ( 'true' === $options['checks']['ageLimitCheck'] ) ? ' checked' : '';
			}
			?>
			/>
			<label class="form-check-label" for="ageLimitCheckTrue"><?php esc_html_e( 'True', 'racketmanager' ); ?></label>
		</div>
		<div class="form-check form-check-inline">
			<input type="radio" class="form-check-input" name="ageLimitCheck" id="ageLimitCheckFalse" value="false"
			<?php
			if ( isset( $options['checks']['ageLimitCheck'] ) ) {
				echo ( 'false' === $options['checks']['ageLimitCheck'] ) ? ' checked' : '';
			}
			?>
			/>
			<label class="form-check-label" for="ageLimitCheckFalse"><?php esc_html_e( 'False', 'racketmanager' ); ?></label>
		</div>
	</div>
	<div class="form-control mb-3">
		<legend class="form-check-label"><?php esc_html_e( 'Lead Time Check', 'racketmanager' ); ?></legend>
		<div class="form-check form-check-inline">
			<input type="radio" class="form-check-input" name="leadTimeCheck" id="leadTimeCheckTrue" value="true"
			<?php
			if ( isset( $options['checks']['leadTimeCheck'] ) ) {
				echo ( 'true' === $options['checks']['leadTimeCheck'] ) ? ' checked' : '';
			}
			?>
			/>
			<label class="form-check-label" for="leadTimeCheckTrue"><?php esc_html_e( 'True', 'racketmanager' ); ?></label>
		</div>
		<div class="form-check form-check-inline">
			<input type="radio" class="form-check-input" name="leadTimeCheck" id="leadTimeCheckFalse" value="false"
			<?php
			if ( isset( $options['checks']['leadTimeCheck'] ) ) {
				echo ( 'false' === $options['checks']['leadTimeCheck'] ) ? ' checked' : '';
			}
			?>
			/>
			<label class="form-check-label" for="leadTimeCheckFalse"><?php esc_html_e( 'False', 'racketmanager' ); ?></label>
		</div>
	</div>
	<div class="form-control mb-3">
		<legend class="form-check-label"><?php esc_html_e( 'Player Rating Check', 'racketmanager' ); ?></legend>
		<div class="form-check form-check-inline">
			<input type="checkbox" class="form-check-input" name="ratingCheck" id="ratingCheckTrue" value="true" <?php checked( true, empty( $options['checks']['ratingCheck'] ) ? false : true ); ?> />
			<label class="form-check-label" for="ratingCheckTrue"><?php esc_html_e( 'True', 'racketmanager' ); ?></label>
		</div>
	</div>
	<div class="form-control mb-3">
		<legend class="form-check-label"><?php esc_html_e( 'Player WTN Check', 'racketmanager' ); ?></legend>
		<div class="form-check form-check-inline">
			<input type="checkbox" class="form-check-input" name="wtnCheck" id="wtnCheckTrue" value="true" <?php checked( true, empty( $options['checks']['wtn_check'] ) ? false : true ); ?> />
			<label class="form-check-label" for="wtnCheckTrue"><?php esc_html_e( 'True', 'racketmanager' ); ?></label>
		</div>
	</div>
	<div class="form-floating mb-3">
		<input type="number" class="form-control" name='playerLeadTime' id='playerLeadTime' value='<?php echo esc_html( isset( $options['checks']['rosterLeadTime'] ) ? $options['checks']['rosterLeadTime'] : '' ); ?>' />
		<label for='playerLeadTime'><?php esc_html_e( 'Player Registration Lead Time (hours)', 'racketmanager' ); ?></label>
	</div>
	<div class="form-floating mb-3">
		<input type="number" class="form-control" name='playedRounds' id='playedRounds' value='<?php echo esc_html( isset( $options['checks']['playedRounds'] ) ? $options['checks']['playedRounds'] : '' ); ?>' />
		<label for='playedRounds'><?php esc_html_e( 'End of season eligibility (Match Days)', 'racketmanager' ); ?></label>
	</div>
	<div class="form-floating mb-3">
		<input type="number" class="form-control" name='playerLocked' id='playerLocked' value='<?php echo esc_html( isset( $options['checks']['playerLocked'] ) ? $options['checks']['playerLocked'] : '' ); ?>' />
		<label for='playerLocked'><?php esc_html_e( 'How many matches lock a player', 'racketmanager' ); ?></label>
	</div>
</div>
