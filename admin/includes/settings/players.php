<div class="form-control">
      <div class="form-floating mb-3">
        <input type="number" class="form-control" name='rosterLeadTime' id='rosterLeadTime' value='<?php echo isset($options['rosterLeadTime']) ? $options['rosterLeadTime'] : '' ?>' />
        <label for='rosterLeadTime'><?php _e( 'Roster Lead Time (days)', 'racketmanager' ) ?></label>
      </div>
    <div class="form-floating mb-3">
        <input type="number" class="form-control" name='playedRounds' id='playedRounds' value='<?php echo isset($options['playedRounds']) ? $options['playedRounds'] : '' ?>' />
        <label for='playedRounds'><?php _e( 'End of season eligibility (Match Days)', 'racketmanager' ) ?></label>
    </div>
    <div class="form-floating mb-3">
        <input type="number" class="form-control" name='playerLocked' id='playerLocked' value='<?php echo isset($options['playerLocked']) ? $options['playerLocked'] : '' ?>' />
        <label for='playerLocked'><?php _e( 'How many matches lock a player', 'racketmanager' ) ?></label>
    </div>
</div>
