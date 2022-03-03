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
<div class="container">
  <!-- Nav tabs -->
  <ul class="nav nav-pills" id="myTab" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="competitions-cup-tab" data-bs-toggle="pill" data-bs-target="#competitions-cup" type="button" role="tab" aria-controls="competitions-cup" aria-selected="true"><?php _e( 'Cups', 'racketmanager' ) ?></button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="competitions-league-tab" data-bs-toggle="pill" data-bs-target="#competitions-league" type="button" role="tab" aria-controls="competitions-league" aria-selected="false"><?php _e( 'Leagues', 'racketmanager' ) ?></button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="competitions-tournament-tab" data-bs-toggle="pill" data-bs-target="#competitions-tournament" type="button" role="tab" aria-controls="competitions-tournament" aria-selected="false"><?php _e( 'Tournaments', 'racketmanager' ) ?></button>
    </li>
  </ul>
  <!-- Tab panes -->
  <div class="tab-content">
  <?php $competitionTypes = $this->getCompetitionTypes();
  $i = 0;
  foreach ( $competitionTypes AS $competitionType ) { $i ++; ?>
    <div id="competitions-<?php echo $competitionType ?>" class="tab-pane <?php if ( $i == 1 )  { echo 'active show';} ?> fade" role="tabpanel" aria-labelledby="competitions-<?php echo $competitionType ?>-tab">
      <?php $competitionType = ucfirst($competitionType);
      if ( $competitionType == 'League' ) { $competitionType = ''; }
      $matchCapability = 'matchCapability'.$competitionType;
      $resultEntry = 'resultEntry'.$competitionType;
      $resultConfirmation = 'resultConfirmation'.$competitionType;
      $resultConfirmationEmail = 'resultConfirmationEmail'.$competitionType;
      ?>
      <div class="form-control">
          <div class="form-group">
            <div class="form-label">
              <label for="<?php echo $matchCapability ?>"><?php _e( 'Minimum level to update results', 'racketmanager' ) ?></label>
            </div>
            <div class="form-input">
              <select id="role" name="<?php echo $matchCapability ?>">
                <option value="none" <?php if (isset($options[$matchCapability]) && $options[$matchCapability] == "none") echo 'selected="selected"'?>><?php _e('None', 'racketmanager') ?></option>
                <option value="captain" <?php if (isset($options[$matchCapability]) && $options[$matchCapability] == "captain") echo 'selected="selected"'?>><?php _e('Captain', 'racketmanager') ?></option>
                <option value="roster" <?php if (isset($options[$matchCapability]) && $options[$matchCapability] == "roster") echo 'selected="selected"'?>><?php _e('Roster', 'racketmanager') ?></option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <div class="form-label">
              <label for="<?php echo $resultEntry ?>"><?php _e( 'Result Entry', 'racketmanager' ) ?></label>
            </div>
            <div class="form-input">
              <select id="<?php echo $resultEntry ?>" name="<?php echo $resultEntry ?>">
                <option value="none" <?php if (isset($options[$resultEntry]) && $options[$resultEntry] == "none") echo 'selected="selected"'?>><?php _e('None', 'racketmanager') ?></option>
                <option value="home" <?php if (isset($options[$resultEntry]) && $options[$resultEntry] == "home") echo 'selected="selected"'?>><?php _e('Home', 'racketmanager') ?></option>
                <option value="either" <?php if (isset($options[$resultEntry]) && $options[$resultEntry] == "either") echo 'selected="selected"'?>><?php _e('Either', 'racketmanager') ?></option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <div class="form-label">
              <label for="<?php echo $resultConfirmation ?>"><?php _e( 'Result Confirmation', 'racketmanager' ) ?></label>
            </div>
            <div class="form-input">
              <select id="<?php echo $resultConfirmation ?>" name="<?php echo $resultConfirmation ?>">
                <option value="none" <?php if (isset($options[$resultConfirmation]) && $options[$resultConfirmation] == "none") echo 'selected="selected"'?>><?php _e('None', 'racketmanager') ?></option>
                <option value="auto" <?php if (isset($options[$resultConfirmation]) && $options[$resultConfirmation] == "admin") echo 'selected="selected"'?>><?php _e('Automatic', 'racketmanager') ?></option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <div class="form-label">
              <label for="<?php echo $resultConfirmationEmail ?>"><?php _e( 'Notification Email Address', 'racketmanager' ) ?></label>
            </div>
            <div class="form-input">
              <input type="email"  name="<?php echo $resultConfirmationEmail ?>" id="<?php echo $resultConfirmationEmail ?>" value='<?php echo isset($options[$resultConfirmationEmail]) ? $options[$resultConfirmationEmail] : '' ?>' />
            </div>
          </div>
      </div>
    </div>


  <?php } ?>
    </div>
</div>
