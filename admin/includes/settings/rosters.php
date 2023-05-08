<div class="form-control">
  <div class="mb-3">
    <label><?php _e( 'LTA Tennis Number', 'racketmanager' ) ?></label>
    <div class="form-check">
      <input class="form-check-input" type="radio" id="btmRequired" name="btmRequired" value="1" <?php if ( isset($options['rosters']['btm']) && $options['rosters']['btm'] == '1' ) { echo 'checked'; } ?>>
      <label for='btmRequired'><?php _e( 'Required', 'racketmanager' ) ?></label>
    </div>
    <div class="form-check">
      <input class="form-check-input" type="radio" id="btmOptional" name="btmRequired" value="0" <?php if ( isset($options['rosters']['btm']) && $options['rosters']['btm'] == '0' ) { echo 'checked'; } ?>>
      <label for='btmOptional'><?php _e( 'Optional', 'racketmanager' ) ?></label>
    </div>
  </div>
  <div class="form-floating mb-3">
    <select class="form-select" id="clubPlayerEntry" name="clubPlayerEntry">
      <option value="secretary" <?php if (isset($options['rosters']['rosterEntry']) && $options['rosters']['rosterEntry'] == "secretary") echo 'selected="selected"'?>><?php _e('Match Secretary', 'racketmanager') ?></option>
      <option value="captain" <?php if (isset($options['rosters']['rosterEntry']) && $options['rosters']['rosterEntry'] == "captain") echo 'selected="selected"'?>><?php _e('Captain', 'racketmanager') ?></option>
    </select>
    <label for='clubPlayerEntry'><?php _e( 'Entry', 'racketmanager' ) ?></label>
  </div>
  <div class="form-floating mb-3">
    <select class="form-select" id="confirmation" name="confirmation">
      <option value="auto" <?php if (isset($options['rosters']['rosterConfirmation']) && $options['rosters']['rosterConfirmation'] == "admin") echo 'selected="selected"'?>><?php _e('Automatic', 'racketmanager') ?></option>
      <option value="none" <?php if (isset($options['rosters']['rosterConfirmation']) && $options['rosters']['rosterConfirmation'] == "none") echo 'selected="selected"'?>><?php _e('None', 'racketmanager') ?></option>
    </select>
    <label for='confirmation'><?php _e( 'Confirmation', 'racketmanager' ) ?></label>
  </div>
  <div class="form-floating mb-3">
    <input type="email" class="form-control" name='confirmationEmail' id='confirmationEmail' value='<?php echo isset($options['rosters']['rosterConfirmationEmail']) ? $options['rosters']['rosterConfirmationEmail'] : '' ?>' />
    <label for='confirmationEmail'><?php _e( 'Notification Email Address', 'racketmanager' ) ?></label>
  </div>
</div>
