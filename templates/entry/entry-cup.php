<?php
/**
Template page to display a cup entry form

The following variables are usable:

$competitions: competitions object
$season: season name
$type: competition type
$mensTeams: male teams object
$ladiesTeams: female teams object
$mixedTeams: mixed teams object

You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
*/
?>
<h1 class="title-post"><?php echo ucfirst($type) ?> Cup <?php echo $season ?> Entry Form</h1>
<h2><?php echo $club->name ?></h2>
<div class="entry-content">
  <?php if ( is_user_logged_in() ) { ?>
    <form id="form-cupentry" action="" method="post">
      <?php wp_nonce_field( 'cup-entry' ) ?>
      <div class="form-group">
        <fieldset class="form-fieldset">
          <legend class="fieldset-legend">
            <h3>Which events would you like to enter?</h3>
          </legend>
          <div id="event-hint" class="hint">
            Select all events that you would like to enter.
          </div>
          <div class="form-checkboxes">
            <?php foreach ($competitions AS $competition) { ?>
              <div class="form-checkboxes__item competitionList">
                <input class="form-checkboxes__input competitionId" id="competition[<?php echo $competition->id ?>]" name="competition[<?php echo $competition->id ?>]" type="checkbox" value=<?php echo $competition->id ?> aria-controls="conditional-competition-<?php echo $competition->id ?>">
                <label class="form-label form-checkboxes__label" for="competition[<?php echo $competition->id ?>]">
                  <?php echo $competition->name ?>
                </label>
              </div>
              <?php if ( $competition->type == 'MD') {
                $teamList = $mensTeams;
              } elseif ( $competition->type == 'WD') {
                $teamList = $ladiesTeams;
              } elseif ( $competition->type == 'XD') {
                $teamList = $mixedTeams;
              } ?>

              <div class="form-checkboxes__conditional form-checkboxes__conditional--hidden" id="conditional-competition-<?php echo $competition->id ?>">
                <label class="form-label" for="team[<?php echo $competition->id ?>]"><?php _e( 'Team', 'racketmanager' ) ?></label>
                <select size="1" class="cupteam" name="team[<?php echo $competition->id ?>]" id="team[<?php echo $competition->id ?>]" >
                  <option value="0"><?php _e( 'Select team' , 'racketmanager') ?></option>
                  <?php foreach ( $teamList AS $team ) { ?>
                    <option value="<?php echo $team->id ?>"><?php echo $team->title ?></option>
                  <?php } ?>
                </select>
                <div class="form-group match-time">
                  <label class="form-label" for="matchday[<?php echo $competition->id ?>]"><?php _e( 'Match Day', 'racketmanager') ?></label>
                  <div class="input">
                    <input type="text" class="form-control" name="matchday[<?php echo $competition->id ?>]" id="matchday-<?php echo $competition->id ?>" />
                  </div>
                </div>
                <div class="form-group match-time">
                  <label class="form-label" for="matchtime[<?php echo $competition->id ?>]"><?php _e( 'Match Time', 'racketmanager') ?></label>
                  <div class="input">
                    <input type="time" class="form-control" name="matchtime[<?php echo $competition->id ?>]" id="matchtime-<?php echo $competition->id ?>" />
                  </div>
                </div>
                <div>
                  <div class="form-group">
                    <label class="form-label" for="captain[<?php echo $competition->id ?>]"><?php _e( 'Captain', 'racketmanager') ?></label>
                    <div class="input">
                      <input type="text" class="form-control teamcaptain" name="captain[<?php echo $competition->id ?>]" id="captain-<?php echo $competition->id ?>" />
                      <input type="hidden" name="captainId[<?php echo $competition->id ?>]" id="captainId-<?php echo $competition->id ?>" />
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="form-label" for="contactno[<?php echo $competition->id ?>]"><?php _e( 'Contact Number', 'racketmanager') ?></label>
                    <div class="input">
                      <input type="tel" class="form-control" name="contactno[<?php echo $competition->id ?>]" id="contactno-<?php echo $competition->id ?>" />
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="form-label" for="contactemail[<?php echo $competition->id ?>]"><?php _e( 'Contact Email', 'racketmanager') ?></label>
                    <div class="input">
                      <input type="email" class="form-control" name="contactemail[<?php echo $competition->id ?>]" id="contactemail-<?php echo $competition->id ?>" />
                    </div>
                  </div>
                </div>
              </div>
            <?php } ?>
          </div>
        </fieldset>
      </div>
      <div>
        <h3>Notes</h3>
        <ol>
          <li>See regulations 33-45 re Inter Club Knockout Competition</li>
        </ol>
      </div>
      <div class="form-checkboxes">
        <div class="form-checkboxes__item">
          <input class="form-checkboxes__input" id="acceptance" name="acceptance" type="checkbox">
          <label class="form-label form-checkboxes__label" for="acceptance">
            <?php _e('I agree to abide by the rules of the competition', 'racketmanager') ?>
          </label>
        </div>
      </div>
      <div>
        <input type="hidden" name="affiliatedClub" id="affiliatedClub" value="<?php echo $club->id ?>" />
        <input type="hidden" name="season" value="<?php echo $season ?>" />
        <input type="hidden" name="cupSeason" value="<?php echo $type ?>" />
        <button class="btn" type="button" id="cupEntrySubmit" name="cupEntrySubmit" onclick="Racketmanager.cupEntryRequest(this)">Enter Cups</button>
        <div class="updateResponse" id="cupEntryResponse" name="cupEntryResponse"></div>
      </div>
    </form>
  <?php } else { ?>
    <p class="contact-login-msg">You need to <a href="<?php echo wp_login_url(); ?>">login</a> to enter cups</p>
  <?php } ?>
</div><!-- .entry-content -->
