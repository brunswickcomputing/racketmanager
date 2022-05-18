<?php
/**
* Results administration panel
*
*/
namespace ns;
?>
<div class="container">
  <!-- Nav tabs -->
  <ul class="nav nav-tabs mb-3" id="myTab" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="competitions-cup-tab" data-bs-toggle="tab" data-bs-target="#competitions-cup" type="button" role="tab" aria-controls="competitions-cup" aria-selected="true"><?php _e( 'Cups', 'racketmanager' ) ?></button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="competitions-league-tab" data-bs-toggle="tab" data-bs-target="#competitions-league" type="button" role="tab" aria-controls="competitions-league" aria-selected="false"><?php _e( 'Leagues', 'racketmanager' ) ?></button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="competitions-tournament-tab" data-bs-toggle="tab" data-bs-target="#competitions-tournament" type="button" role="tab" aria-controls="competitions-tournament" aria-selected="false"><?php _e( 'Tournaments', 'racketmanager' ) ?></button>
    </li>
  </ul>
  <!-- Tab panes -->
  <div class="tab-content">
    <?php $competitionTypes = $this->getCompetitionTypes();
    $i = 0;
    foreach ( $competitionTypes AS $competitionType ) { $i ++; ?>
      <div id="competitions-<?php echo $competitionType ?>" class="tab-pane <?php if ( $i == 1 )  { echo 'active show';} ?> fade" role="tabpanel" aria-labelledby="competitions-<?php echo $competitionType ?>-tab">

        <div class="form-control">
          <div class="form-floating mb-3">
            <select class="form-select" id="role" name="<?php echo $competitionType ?>[matchCapability]">
              <option value="none" <?php if (isset($options[$competitionType]['matchCapability']) && $options[$competitionType]['matchCapability'] == "none") echo 'selected="selected"'?>><?php _e('None', 'racketmanager') ?></option>
              <option value="captain" <?php if (isset($options[$competitionType]['matchCapability']) && $options[$competitionType]['matchCapability'] == "captain") echo 'selected="selected"'?>><?php _e('Captain', 'racketmanager') ?></option>
              <option value="roster" <?php if (isset($options[$competitionType]['matchCapability']) && $options[$competitionType]['matchCapability'] == "roster") echo 'selected="selected"'?>><?php _e('Roster', 'racketmanager') ?></option>
            </select>
            <label for="<?php echo $competitionType ?>[matchCapability]"><?php _e( 'Minimum level to update results', 'racketmanager' ) ?></label>
          </div>
          <div class="form-floating mb-3">
            <select class="form-select" id="<?php echo $competitionType.'-resultEntry' ?>" name="<?php echo $competitionType ?>[resultEntry]">
              <option value="none" <?php if (isset($options[$competitionType]['resultEntry']) && $options[$competitionType]['resultEntry'] == "none") echo 'selected="selected"'?>><?php _e('None', 'racketmanager') ?></option>
              <option value="home" <?php if (isset($options[$competitionType]['resultEntry']) && $options[$competitionType]['resultEntry'] == "home") echo 'selected="selected"'?>><?php _e('Home', 'racketmanager') ?></option>
              <option value="either" <?php if (isset($options[$competitionType]['resultEntry']) && $options[$competitionType]['resultEntry'] == "either") echo 'selected="selected"'?>><?php _e('Either', 'racketmanager') ?></option>
            </select>
            <label for="<?php echo $competitionType ?>[resultEntry]"><?php _e( 'Result Entry', 'racketmanager' ) ?></label>
          </div>
          <div class="form-floating mb-3">
            <select class="form-select" id="<?php echo $competitionType.'-resultConfirmation' ?>" name="<?php echo $competitionType ?>[resultConfirmation]">
              <option value="none" <?php if (isset($options[$competitionType]['resultConfirmation']) && $options[$competitionType]['resultConfirmation'] == "none") echo 'selected="selected"'?>><?php _e('None', 'racketmanager') ?></option>
              <option value="auto" <?php if (isset($options[$competitionType]['resultConfirmation']) && $options[$competitionType]['resultConfirmation'] == "admin") echo 'selected="selected"'?>><?php _e('Automatic', 'racketmanager') ?></option>
            </select>
            <label for="<?php echo $competitionType ?>[resultConfirmation]"><?php _e( 'Result Confirmation', 'racketmanager' ) ?></label>
          </div>
          <div class="form-floating mb-3">
            <input type="email" class="form-control" name="<?php echo $competitionType ?>[resultConfirmationEmail]" id="<?php echo $competitionType ?>.'-resultConfirmationEmail'" value='<?php echo isset($options[$competitionType]['resultConfirmationEmail']) ? $options[$competitionType]['resultConfirmationEmail'] : '' ?>' />
            <label for="<?php echo $competitionType ?>[resultConfirmationEmail]"><?php _e( 'Notification Email Address', 'racketmanager' ) ?></label>
          </div>
          <div class="form-floating mb-3">
            <select class="form-select" id="<?php echo $competitionType ?>.'-resultNotification'" name="<?php echo $competitionType ?>[resultNotification]">
              <option value="none" <?php if (isset($options[$competitionType]['resultNotification']) && $options[$competitionType]['resultNotification'] == "none") echo 'selected="selected"'?>><?php _e('None', 'racketmanager') ?></option>
              <option value="captain" <?php if (isset($options[$competitionType]['resultNotification']) && $options[$competitionType]['resultNotification'] == "captain") echo 'selected="selected"'?>><?php _e('Captain', 'racketmanager') ?></option>
              <option value="secretary" <?php if (isset($options[$competitionType]['resultNotification']) && $options[$competitionType]['resultNotification'] == "secretary") echo 'selected="selected"'?>><?php _e('Match Secretary', 'racketmanager') ?></option>
            </select>
            <label for="<?php echo $competitionType ?>[resultNotification]"><?php _e( 'Result Notification', 'racketmanager' ) ?></label>
          </div>
        </div>
      </div>
    <?php } ?>
  </div>
</div>
