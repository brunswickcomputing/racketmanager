<div class="settings-block">
  <table class='lm-form-table'>
    <tr valign='top'>
      <th scope='row'><label for='rosterLeadTime'><?php _e( 'Roster Lead Time (days)', 'racketmanager' ) ?></label></th><td><input type="number"  name='rosterLeadTime' id='rosterLeadTime' value='<?php echo isset($options['rosterLeadTime']) ? $options['rosterLeadTime'] : '' ?>' /></td>
    </tr>
    <tr valign='top'>
      <th scope='row'><label for='playedRounds'><?php _e( 'End of season eligibility (Match Days)', 'racketmanager' ) ?></label></th><td><input type="number"  name='playedRounds' id='playedRounds' value='<?php echo isset($options['playedRounds']) ? $options['playedRounds'] : '' ?>' /></td>
    </tr>
    <tr valign='top'>
      <th scope='row'><label for='playerLocked'><?php _e( 'How many matches lock a player', 'racketmanager' ) ?></label></th><td><input type="number"  name='playerLocked' id='playerLocked' value='<?php echo isset($options['playerLocked']) ? $options['playerLocked'] : '' ?>' /></td>
    </tr>
  </table>
</div>
