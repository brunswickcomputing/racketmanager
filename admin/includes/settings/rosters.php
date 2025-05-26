<?php
/**
 * Club player options administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

?>
<div class="form-control">
	<fieldset class="form-control mb-3">
		<legend class="form-check-label"><?php esc_html_e( 'Age Limit Check', 'racketmanager' ); ?></legend>
		<div class="form-check form-check-inline">
			<input type="radio" class="form-check-input" name="clubPlayerAgeLimitCheck" id="clubPlayerAgeLimitCheckTrue" value="true"
			<?php
			if ( isset( $options['rosters']['ageLimitCheck'] ) ) {
				echo ( 'true' === $options['rosters']['ageLimitCheck'] ) ? ' checked' : '';
			}
			?>
			/>
			<label class="form-check-label" for="clubPlayerAgeLimitCheckTrue"><?php esc_html_e( 'True', 'racketmanager' ); ?></label>
		</div>
		<div class="form-check form-check-inline">
			<input type="radio" class="form-check-input" name="clubPlayerAgeLimitCheck" id="clubPlayerAgeLimitCheckFalse" value="false"
			<?php
			if ( isset( $options['rosters']['ageLimitCheck'] ) ) {
				echo ( 'false' === $options['rosters']['ageLimitCheck'] ) ? ' checked' : '';
			}
			?>
			/>
			<label class="form-check-label" for="clubPlayerAgeLimitCheckFalse"><?php esc_html_e( 'False', 'racketmanager' ); ?></label>
		</div>
	</fieldset>
	<fieldset class="form-control mb-3">
		<legend class="form-check-label"><?php esc_html_e( 'LTA Tennis Number', 'racketmanager' ); ?></legend>
		<div class="form-check form-check-inline">
			<input class="form-check-input" type="radio" id="btmRequired" name="btmRequired" value="1"
			<?php
			if ( isset( $options['rosters']['btm'] ) ) {
				echo ( '1' === $options['rosters']['btm'] ) ? ' checked' : '';
			}
			?>
			/>
			<label for='btmRequired'><?php esc_html_e( 'Required', 'racketmanager' ); ?></label>
		</div>
		<div class="form-check form-check-inline">
			<input class="form-check-input" type="radio" id="btmOptional" name="btmRequired" value="0"
			<?php
			if ( isset( $options['rosters']['btm'] ) ) {
				echo ( '0' === $options['rosters']['btm'] ) ? ' checked' : '';
			}
			?>
			/>
			<label for='btmOptional'><?php esc_html_e( 'Optional', 'racketmanager' ); ?></label>
		</div>
	</fieldset>
	<div class="form-floating mb-3">
		<select class="form-select" id="clubPlayerEntry" name="clubPlayerEntry">
			<option value="secretary"
			<?php
			if ( isset( $options['rosters']['rosterEntry'] ) ) {
				echo ( 'secretary' === $options['rosters']['rosterEntry'] ) ? ' selected' : '';
			}
			?>
			>
				<?php esc_html_e( 'Match Secretary', 'racketmanager' ); ?>
			</option>
			<option value="captain"
			<?php
			if ( isset( $options['rosters']['rosterEntry'] ) ) {
				echo ( 'captain' === $options['rosters']['rosterEntry'] ) ? ' selected' : '';
			}
			?>
			>
				<?php esc_html_e( 'Captain', 'racketmanager' ); ?>
			</option>
		</select>
		<label for='clubPlayerEntry'><?php esc_html_e( 'Entry', 'racketmanager' ); ?></label>
	</div>
	<div class="form-floating mb-3">
		<select class="form-select" id="confirmation" name="confirmation">
			<option value="auto"
			<?php
			if ( isset( $options['rosters']['rosterConfirmation'] ) ) {
				echo ( 'admin' === $options['rosters']['rosterConfirmation'] ) ? ' selected' : '';
			}
			?>
			>
				<?php esc_html_e( 'Automatic', 'racketmanager' ); ?>
			</option>
			<option value="none"
			<?php
			if ( isset( $options['rosters']['rosterConfirmation'] ) ) {
				echo ( 'none' === $options['rosters']['rosterConfirmation'] ) ? ' selected' : '';
			}
			?>
			>
				<?php esc_html_e( 'None', 'racketmanager' ); ?>
			</option>
		</select>
		<label for='confirmation'><?php esc_html_e( 'Confirmation', 'racketmanager' ); ?></label>
	</div>
	<div class="form-floating mb-3">
		<input type="email" class="form-control" name='confirmationEmail' id='confirmationEmail' value='<?php echo isset( $options['rosters']['rosterConfirmationEmail'] ) ? esc_html( $options['rosters']['rosterConfirmationEmail'] ) : ''; ?>' />
		<label for='confirmationEmail'><?php esc_html_e( 'Notification Email Address', 'racketmanager' ); ?></label>
	</div>
</div>
