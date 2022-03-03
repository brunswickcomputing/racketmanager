<div class="form-control">
    <div class="form-group">
      <div class="form-label">
        <label for='rosterLeadTime'><?php _e( 'Roster Lead Time (days)', 'racketmanager' ) ?></label>
      </div>
      <div class="form-input">
        <input type="number"  name='rosterLeadTime' id='rosterLeadTime' value='<?php echo isset($options['rosterLeadTime']) ? $options['rosterLeadTime'] : '' ?>' />
      </div>
    </div>
    <div class="form-group">
      <div class="form-label">
        <label for='playedRounds'><?php _e( 'End of season eligibility (Match Days)', 'racketmanager' ) ?></label>
      </div>
      <div class="form-input">
        <input type="number"  name='playedRounds' id='playedRounds' value='<?php echo isset($options['playedRounds']) ? $options['playedRounds'] : '' ?>' />
      </div>
    </div>
    <div class="form-group">
      <div class="form-label">
        <label for='playerLocked'><?php _e( 'How many matches lock a player', 'racketmanager' ) ?></label>
      </div>
      <div class="form-input">
        <input type="number"  name='playerLocked' id='playerLocked' value='<?php echo isset($options['playerLocked']) ? $options['playerLocked'] : '' ?>' />
      </div>
    </div>
</div>
