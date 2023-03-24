<div class="form-control">
  <div class="form-floating mb-3">
    <input type="text" class="form-control" name='walkoverFemale' id='walkoverFemale' value='<?php echo isset($options['player']['walkover']['female']) ? $options['player']['walkover']['female'] : '' ?>' />
    <label for='walkoverFemale'><?php _e( 'Walkover player female', 'racketmanager' ) ?></label>
  </div>
  <div class="form-floating mb-3">
    <input type="text" class="form-control" name='walkoverMale' id='walkoverMale' value='<?php echo isset($options['player']['walkover']['male']) ? $options['player']['walkover']['male'] : '' ?>' />
    <label for='walkoverMale'><?php _e( 'Walkover player male', 'racketmanager' ) ?></label>
  </div>
  <div class="form-floating mb-3">
    <input type="text" class="form-control" name='noplayerFemale' id='noplayerFemale' value='<?php echo isset($options['player']['noplayer']['female']) ? $options['player']['noplayer']['female'] : '' ?>' />
    <label for='noplayerFemale'><?php _e( 'Missing player female', 'racketmanager' ) ?></label>
  </div>
  <div class="form-floating mb-3">
    <input type="text" class="form-control" name='noplayerMale' id='noplayerMale' value='<?php echo isset($options['player']['noplayer']['male']) ? $options['player']['noplayer']['male'] : '' ?>' />
    <label for='noplayerMale'><?php _e( 'Missing player male', 'racketmanager' ) ?></label>
  </div>
  <div class="form-floating mb-3">
    <input type="text" class="form-control" name='shareFemale' id='shareFemale' value='<?php echo isset($options['player']['share']['female']) ? $options['player']['share']['female'] : '' ?>' />
    <label for='shareFemale'><?php _e( 'Share player female', 'racketmanager' ) ?></label>
  </div>
  <div class="form-floating mb-3">
    <input type="text" class="form-control" name='shareMale' id='shareMale' value='<?php echo isset($options['player']['share']['male']) ? $options['player']['share']['male'] : '' ?>' />
    <label for='shareMale'><?php _e( 'Share player male', 'racketmanager' ) ?></label>
  </div>
  <div class="form-floating mb-3">
    <input type="text" class="form-control" name='unregisteredFemale' id='unregisteredFemale' value='<?php echo isset($options['player']['unregistered']['female']) ? $options['player']['unregistered']['female'] : '' ?>' />
    <label for='unregisteredFemale'><?php _e( 'Unregistered player female', 'racketmanager' ) ?></label>
  </div>
  <div class="form-floating mb-3">
    <input type="text" class="form-control" name='unregisteredMale' id='unregisteredMale' value='<?php echo isset($options['player']['unregistered']['male']) ? $options['player']['unregistered']['male'] : '' ?>' />
    <label for='unregisteredMale'><?php _e( 'Unregistered player male', 'racketmanager' ) ?></label>
  </div>
  <div class="form-floating mb-3">
    <input type="number" class="form-control" name='walkoverPointsRubber' id='walkoverPointsRubber' value='<?php echo isset($options['player']['walkover']['rubber']) ? $options['player']['walkover']['rubber'] : '' ?>' />
    <label for='walkoverPointsRubber'><?php _e( 'Walkover points per rubber', 'racketmanager' ) ?></label>
  </div>
  <div class="form-floating mb-3">
    <input type="number" class="form-control" name='walkoverPointsMatch' id='walkoverPointsMatch' value='<?php echo isset($options['player']['walkover']['match']) ? $options['player']['walkover']['match'] : '' ?>' />
    <label for='walkoverPointsMatch'><?php _e( 'Walkover points per match', 'racketmanager' ) ?></label>
  </div>
  <div class="form-floating mb-3">
    <input type="number" step = "0.1" class="form-control" name='sharePoints' id='sharePoints' value='<?php echo isset($options['player']['share']['rubber']) ? $options['player']['share']['rubber'] : '' ?>' />
    <label for='sharePoints'><?php _e( 'Share points', 'racketmanager' ) ?></label>
  </div>
</div>
