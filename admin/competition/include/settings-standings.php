<table class="lm-form-table">
  <tr valign="top">
    <th scope="row"><label for="standings_table"><?php _e( 'Standings Table Display', 'racketmanager' ) ?></label></th>
                  <td>
                      <div class="alignleft">
<?php $i=0; foreach ( $this->getStandingsDisplayOptions() AS $key => $label ) { $i++; ?>
                      <p><input type="checkbox" name="settings[standings][<?php echo $key ?>]" id="standings_<?php echo $key ?>" value="1" <?php checked(1, $competition->standings[$key]) ?> /><label for="standings_<?php echo $key ?>"><?php echo $label ?></label></p>
                      <?php if ( $i == 9 ) echo "</div><div class='alignleft extra-col'>"; ?>
<?php } ?>
                      </div>
                  </td>
  </tr>
  <tr valign="top">
    <th scope="row"><label for="teams_ascend"><?php _e( 'Teams Ascend', 'racketmanager' ) ?></label></th>
    <td><input type="number" step="1" min="0" class="small-text" name="settings[num_ascend]" id="teams_ascend" value="<?php echo $competition->num_ascend ?>" size="2" />&#160;<span class="setting-description"><?php _e( 'Number of Teams that ascend into higher league', 'racketmanager' ) ?></span></td>
  </tr>
  <tr valign="top">
    <th scope="row"><label for="teams_descend"><?php _e( 'Teams Descend', 'racketmanager' ) ?></label></th>
    <td><input type="number" step="1" min="0" class="small-text" name="settings[num_descend]" id="teams_descend" value="<?php echo $competition->num_descend ?>" size="2" />&#160;<span class="setting-description"><?php _e( 'Number of Teams that descend into lower league', 'racketmanager' ) ?></span></td>
  </tr>
  <tr valign="top">
    <th scope="row"><label for="teams_relegation"><?php _e( 'Teams Relegation', 'racketmanager' ) ?></label></th>
    <td><input type="number" step="1" min="0" class="small-text" name="settings[num_relegation]" id="teams_relegation" value="<?php echo $competition->num_relegation ?>" size="2" />&#160;<span class="setting-description"><?php _e( 'Number of Teams that need to go into relegation', 'racketmanager' ) ?></span></td>
  </tr>
</table>
