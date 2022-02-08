<?php
/**
* Results administration panel
*
*/
namespace ns;
?>
<script type='text/javascript'>
jQuery(function() {
  jQuery("#tabs-competitions").tabs({
    active: <?php echo $comptab ?>
  });
});
</script>
<div id="tabs-competitions" class="racketmanager-blocks">
  <ul id="tablist">
    <li><a href="#competitions-Cup"><?php _e( 'Cups', 'racketmanager' ) ?></a></li>
    <li><a href="#competitions-League"><?php _e( 'Leagues', 'racketmanager' ) ?></a></li>
    <li><a href="#competitions-Tournament"><?php _e( 'Tournaments', 'racketmanager' ) ?></a></li>
  </ul>
  <?php $competitionTypes = array('Cup','League','Tournament');
  foreach ( $competitionTypes AS $competitionType ) { ?>
    <div id="competitions-<?php echo $competitionType ?>" class="league-block-container">
      <?php if ( $competitionType == 'League' ) { $competitionType = ''; }
      $matchCapability = 'matchCapability'.$competitionType;
      $resultEntry = 'resultEntry'.$competitionType;
      $resultConfirmation = 'resultConfirmation'.$competitionType;
      $resultConfirmationEmail = 'resultConfirmationEmail'.$competitionType;
      ?>
      <div class="settings-block">
        <table class='lm-form-table'>
          <tr valign='top'>
            <th scope='row'><label for="<?php echo $matchCapability ?>"><?php _e( 'Minimum level to update results', 'racketmanager' ) ?></label></th>
            <td>
              <select id="role" name="<?php echo $matchCapability ?>">
                <option value="none" <?php if (isset($options[$matchCapability]) && $options[$matchCapability] == "none") echo 'selected="selected"'?>><?php _e('None', 'racketmanager') ?></option>
                <option value="captain" <?php if (isset($options[$matchCapability]) && $options[$matchCapability] == "captain") echo 'selected="selected"'?>><?php _e('Captain', 'racketmanager') ?></option>
                <option value="roster" <?php if (isset($options[$matchCapability]) && $options[$matchCapability] == "roster") echo 'selected="selected"'?>><?php _e('Roster', 'racketmanager') ?></option>
              </select>
            </td>
          </tr>
          <tr valign='top'>
            <th scope='row'><label for="<?php echo $resultEntry ?>"><?php _e( 'Result Entry', 'racketmanager' ) ?></label></th>
            <td>
              <select id="<?php echo $resultEntry ?>" name="<?php echo $resultEntry ?>">
                <option value="none" <?php if (isset($options[$resultEntry]) && $options[$resultEntry] == "none") echo 'selected="selected"'?>><?php _e('None', 'racketmanager') ?></option>
                <option value="home" <?php if (isset($options[$resultEntry]) && $options[$resultEntry] == "home") echo 'selected="selected"'?>><?php _e('Home', 'racketmanager') ?></option>
                <option value="either" <?php if (isset($options[$resultEntry]) && $options[$resultEntry] == "either") echo 'selected="selected"'?>><?php _e('Either', 'racketmanager') ?></option>
              </select>
            </td>
          </tr>
          <tr valign='top'>
            <th scope='row'><label for="<?php echo $resultConfirmation ?>"><?php _e( 'Result Confirmation', 'racketmanager' ) ?></label></th>
            <td>
              <select id="<?php echo $resultConfirmation ?>" name="<?php echo $resultConfirmation ?>">
                <option value="none" <?php if (isset($options[$resultConfirmation]) && $options[$resultConfirmation] == "none") echo 'selected="selected"'?>><?php _e('None', 'racketmanager') ?></option>
                <option value="auto" <?php if (isset($options[$resultConfirmation]) && $options[$resultConfirmation] == "admin") echo 'selected="selected"'?>><?php _e('Automatic', 'racketmanager') ?></option>
              </select>
            </td>
          </tr>
          <tr valign='top'>
            <th scope='row'><label for="<?php echo $resultConfirmationEmail ?>"><?php _e( 'Notification Email Address', 'racketmanager' ) ?></label></th><td><input type="email"  name="<?php echo $resultConfirmationEmail ?>" id="<?php echo $resultConfirmationEmail ?>" value='<?php echo isset($options[$resultConfirmationEmail]) ? $options[$resultConfirmationEmail] : '' ?>' /></td>
          </tr>
        </table>
      </div>
    </div>

  <?php } ?>

</div>
