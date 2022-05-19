<div class="form-control">
  <div class="form-floating mb-3">
      <select class="form-select" id="rosterConfirmation" name="rosterConfirmation">
        <option value="auto" <?php if (isset($options['rosters']['rosterConfirmation']) && $options['rosters']['rosterConfirmation'] == "admin") echo 'selected="selected"'?>><?php _e('Automatic', 'racketmanager') ?></option>
        <option value="none" <?php if (isset($options['rosters']['rosterConfirmation']) && $options['rosters']['rosterConfirmation'] == "none") echo 'selected="selected"'?>><?php _e('None', 'racketmanager') ?></option>
      </select>
      <label for='rosterConfirmation'><?php _e( 'Roster Confirmation', 'racketmanager' ) ?></label>
  </div>
  <div class="form-floating mb-3">
      <input type="email" class="form-control" name='rosterConfirmationEmail' id='rosterConfirmationEmail' value='<?php echo isset($options['rosters']['rosterConfirmationEmail']) ? $options['rosters']['rosterConfirmationEmail'] : '' ?>' />
      <label for='rosterConfirmationEmail'><?php _e( 'Notification Email Address', 'racketmanager' ) ?></label>
  </div>
</div>
