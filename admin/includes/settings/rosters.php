<div class="settings-block">
  <table class='lm-form-table'>
    <tr valign='top'>
      <th scope='row'><label for='rosterConfirmation'><?php _e( 'Roster Confirmation', 'racketmanager' ) ?></label></th>
      <td>
        <select id="rosterConfirmation" name="rosterConfirmation">
          <option value="auto" <?php if (isset($options['rosterConfirmation']) && $options['rosterConfirmation'] == "admin") echo 'selected="selected"'?>><?php _e('Automatic', 'racketmanager') ?></option>
          <option value="none" <?php if (isset($options['rosterConfirmation']) && $options['rosterConfirmation'] == "none") echo 'selected="selected"'?>><?php _e('None', 'racketmanager') ?></option>
        </select>
      </td>
    </tr>
    <tr valign='top'>
      <th scope='row'><label for='rosterConfirmationEmail'><?php _e( 'Notification Email Address', 'racketmanager' ) ?></label></th><td><input type="email"  name='rosterConfirmationEmail' id='rosterConfirmationEmail' value='<?php echo isset($options['rosterConfirmationEmail']) ? $options['rosterConfirmationEmail'] : '' ?>' /></td>
    </tr>
  </table>
</div>
