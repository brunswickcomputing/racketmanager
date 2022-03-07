<table class="lm-form-table">
  <tr valign="top">
    <th scope="row"><label for="competition_title"><?php _e( 'Title', 'racketmanager' ) ?></label></th>
    <td><input type="text" name="competition_title" id="competition_title" value="<?php echo $competition->name ?>" size="30" /></td>
  </tr>
  <tr valign="top">
    <th scope="row"><label for="sport"><?php _e( 'Sport', 'racketmanager' ) ?></label></th>
    <td>
      <select size="1" name="settings[sport]" id="sport">
        <?php foreach ( $racketmanager->getLeagueTypes() AS $id => $title ) : ?>
          <option value="<?php echo $id ?>"<?php selected( $id, $competition->sport ) ?>><?php echo $title ?></option>
        <?php endforeach; ?>
      </select>
      <span class="setting-description"><?php printf( __( "Check the <a href='%s'>Documentation</a> for details", 'racketmanager'), admin_url() . 'admin.php?page=racketmanager-doc' ) ?></span>
    </td>
  </tr>
  <tr valign="top">
    <th scope="row"><label for="point_rule"><?php _e( 'Point Rule', 'racketmanager' ) ?></label></th>
    <td>
      <select size="1" name="settings[point_rule]" id="point_rule" onchange="Racketmanager.checkPointRule(<?php echo $forwin ?>, <?php echo $forwin_overtime ?>, <?php echo $fordraw ?>, <?php echo $forloss ?>, <?php echo $forloss_overtime ?>)">
      <?php foreach ( $this->getPointRules() AS $id => $point_rule ) : ?>
      <option value="<?php echo $id ?>"<?php selected( $id, $competition->point_rule ) ?>><?php echo $point_rule ?></option>
      <?php endforeach; ?>
      </select>
      <span class="setting-description"><?php printf( __("For details on point rules see the <a href='%s'>Documentation</a>", 'racketmanager'), admin_url() . 'admin.php?page=racketmanager-doc' ) ?></span>
      <div id="point_rule_manual" style="display: block;">
      <?php if ( $competition->point_rule == 'user' ) { ?>
        <div id="point_rule_manual_content">
          <input type='text' name='forwin' id='forwin' value='<?php echo $forwin ?>' size='2' />
          <input type='text' name='forwin_overtime' id='forwin_overtime' value='<?php echo $forwin_overtime ?>' size='2' />
          <input type='text' name='fordraw' id='fordraw' value='<?php echo $fordraw ?>' size='2' />
          <input type='text' name='forloss' id='forloss' value='<?php echo $forloss ?>' size='2' />
          <input type='text' name='forloss_overtime' id='forloss_overtime' value='<?php echo $forloss_overtime ?>' size='2' />
          &#160;<span class='setting-description'><?php _e( 'Order: win, win overtime, tie, loss, loss overtime', 'racketmanager' ) ?></span>
        </div>
      <?php } ?>
      </div>
    </td>
  </tr>
  <tr valign="top">
    <th scope="row"><label for="point_format"><?php _e( 'Point Format', 'racketmanager' ) ?></label></th>
    <td>
      <select size="1" name="settings[point_format]" id="point_format" >
      <?php foreach ( $this->getPointFormats() AS $id => $format ) : ?>
      <option value="<?php echo $id ?>"<?php selected ( $id, $competition->point_format ) ?>><?php echo $format ?></option>
      <?php endforeach; ?>
      </select>
      <select size="1" name="settings[point_format2]" id="point_format2" >
      <?php foreach ( $this->getPointFormats() AS $id => $format ) : ?>
      <option value="<?php echo $id ?>"<?php selected ( $id, $competition->point_format2 ); ?>><?php echo $format ?></option>
      <?php endforeach; ?>
      </select>
      &#160;<span class="setting-description"><?php _e( 'Point formats for primary and seconday points (e.g. Goals)', 'racketmanager' ) ?></span>
    </td>
  </tr>
  <tr valign="top">
    <th scope="row"><label for="team_ranking"><?php _e( 'Team Ranking', 'racketmanager' ) ?></label></th>
    <td>
      <select size="1" name="settings[team_ranking]" id="team_ranking" >
        <option value="auto"<?php selected( 'auto', $competition->team_ranking  ) ?>><?php _e( 'Automatic', 'racketmanager' ) ?></option>
        <option value="manual"<?php selected( 'manual', $competition->team_ranking  ) ?>><?php _e( 'Manual', 'racketmanager' ) ?></option>
      </select>
      <!--&#160;<span class="setting-description"><?php _e( 'Team Ranking via Drag & Drop probably will only work in Firefox', 'racketmanager' ) ?></span>-->
    </td>
  </tr>
  <tr valign="top">
    <th scope="row"><label for="mode"><?php _e( 'Mode', 'racketmanager' ) ?></label></th>
    <td>
      <select size="1" name="settings[mode]" id="mode">
      <?php foreach ( $this->getModes() AS $id => $mode ) : ?>
        <option value="<?php echo $id ?>"<?php selected( $id, $competition->mode ) ?>><?php echo $mode ?></option>
      <?php endforeach; ?>
      </select>
    </td>
  </tr>
  <tr valign="top">
    <th scope="row"><label for="entryType"><?php _e( 'Entry Type', 'racketmanager' ) ?></label></th>
    <td>
      <select size="1" name="settings[entryType]" id="entryType">
                      <?php foreach ( $this->getentryTypes() AS $id => $entryType )  { ?>
                          <option value="<?php echo $id ?>"<?php selected( $id, isset($competition->entryType) ? $competition->entryType : '' ) ?>><?php echo $entryType ?></option>
                      <?php } ?>
      </select>
    </td>
  </tr>
  <tr valign="top">
    <th scope="row"><label for="default_start_time"><?php _e( 'Default Match Start Time', 'racketmanager' ) ?></label></th>
    <td>
      <select size="1" name="settings[default_match_start_time][hour]">
      <?php for ( $hour = 0; $hour <= 23; $hour++ ) : ?>
        <option value="<?php echo str_pad($hour, 2, 0, STR_PAD_LEFT) ?>"<?php selected( $hour, $competition->default_match_start_time['hour'] ) ?>><?php echo str_pad($hour, 2, 0, STR_PAD_LEFT) ?></option>
      <?php endfor; ?>
      </select>
      <select size="1" name="settings[default_match_start_time][minutes]">
      <?php for ( $minute = 0; $minute <= 60; $minute++ ) : ?>
        <?php if ( 0 == $minute % 5 && 60 != $minute ) { ?>
        <option value="<?php  echo str_pad($minute, 2, 0, STR_PAD_LEFT) ?>"<?php selected( $minute, $competition->default_match_start_time['minutes'] ) ?>><?php echo str_pad($minute, 2, 0, STR_PAD_LEFT) ?></option>
      <?php } ?>
      <?php endfor; ?>
      </select>
    </td>
  </tr>
  <tr valign="top">
    <th scope="row"><label for="num_matches_per_page"><?php _e( 'Matches per page', 'racketmanager' ) ?></label></th>
    <td><input type="number" step="1" min="0" class="small-text" name="settings[num_matches_per_page]" id="num_matches_per_page" value="<?php echo $competition->num_matches_per_page ?>" size="2" />&#160;<span class="setting-description"><?php _e( 'Number of matches to show per page', 'racketmanager' ) ?></span></td>
  </tr>
</table>
