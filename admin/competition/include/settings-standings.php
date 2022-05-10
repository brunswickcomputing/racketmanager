<div class="form-group">
  <div class="form-label">
    <label><?php _e( 'Standings Table Display', 'racketmanager' ) ?></label>
  </div>
  <?php $i=0; foreach ( $this->getStandingsDisplayOptions() AS $key => $label ) { $i++; ?>
    <div class="form-check">
      <input type="checkbox" class="form-check-input" name="settings[standings][<?php echo $key ?>]" id="standings_<?php echo $key ?>" value="1" <?php checked(1, $competition->standings[$key]) ?> />
      <label for="standings_<?php echo $key ?>" class="form-check-label"><?php echo $label ?></label>
    </div>
  <?php } ?>
</div>
<div class="form-floating mb-3 col-2">
  <input class="form-control" type="number" step="1" min="0" class="small-text" name="settings[num_ascend]" id="teams_ascend" value="<?php echo $competition->num_ascend ?>" size="2" />
  <label for="teams_ascend"><?php _e( 'Teams Ascend', 'racketmanager' ) ?></label>
  <div class="form-hint">
    <?php _e( 'Number of Teams that ascend into higher league', 'racketmanager' ) ?>
  </div>
</div>
<div class="form-floating mb-3 col-2">
  <input class="form-control" type="number" step="1" min="0" class="small-text" name="settings[num_descend]" id="teams_descend" value="<?php echo $competition->num_descend ?>" size="2" />
  <label for="teams_descend"><?php _e( 'Teams Descend', 'racketmanager' ) ?></label>
  <div class="form-hint">
    <?php _e( 'Number of Teams that descend into lower league', 'racketmanager' ) ?>
  </div>
</div>
<div class="form-floating mb-3 col-2">
  <input class="form-control" type="number" step="1" min="0" class="small-text" name="settings[num_relegation]" id="teams_relegation" value="<?php echo $competition->num_relegation ?>" size="2" />
  <label for="teams_relegation"><?php _e( 'Teams Relegation', 'racketmanager' ) ?></label>
  <div class="form-hint">
    <?php _e( 'Number of Teams that need to go into relegation', 'racketmanager' ) ?>
  </div>
</div>
