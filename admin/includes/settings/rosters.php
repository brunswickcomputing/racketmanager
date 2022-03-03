<div class="form-control">
  <div class="form-group">
    <div class="form-label">
      <label for='rosterConfirmation'><?php _e( 'Roster Confirmation', 'racketmanager' ) ?></label>
    </div>
    <div class="form-input">
      <select id="rosterConfirmation" name="rosterConfirmation">
        <option value="auto" <?php if (isset($options['rosterConfirmation']) && $options['rosterConfirmation'] == "admin") echo 'selected="selected"'?>><?php _e('Automatic', 'racketmanager') ?></option>
        <option value="none" <?php if (isset($options['rosterConfirmation']) && $options['rosterConfirmation'] == "none") echo 'selected="selected"'?>><?php _e('None', 'racketmanager') ?></option>
      </select>
    </div>
  </div>
  <div class="form-group">
    <div class="form-label">
      <label for='rosterConfirmationEmail'><?php _e( 'Notification Email Address', 'racketmanager' ) ?></label>
    </div>
    <div class="form-input">
      <input type="email"  name='rosterConfirmationEmail' id='rosterConfirmationEmail' value='<?php echo isset($options['rosterConfirmationEmail']) ? $options['rosterConfirmationEmail'] : '' ?>' />
    </div>
  </div>
</div>
