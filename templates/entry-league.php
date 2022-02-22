<?php
/**
Template page to display a league entry form

The following variables are usable:

$competitions: competitions object
$season: season name
$type: competition type
$mensTeams: male teams object
$ladiesTeams: female teams object
$mixedTeams: mixed teams object
$constitutionsLadies : female constitutions object
$constitutionsMens : mens constitutions object
$constitutionsMixed : mixed constitutions object

You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
*/
?>
<h1 class="title-post"><?php echo ucfirst($type) ?> League <?php echo $season ?> Entry Form</h1>
<h2><?php echo $club->name ?></h2>
<div class="entry-content">
  <?php if ( is_user_logged_in() ) { ?>
    <form id="form-leagueentry" action="" method="post">
      <?php wp_nonce_field( 'league-entry' ) ?>
      <div class="form-group">
        <fieldset class="form-fieldset">
          <legend class="fieldset-legend">
            <h3>Which events would you like to enter?</h3>
          </legend>
          <div id="event-hint" class="hint">
            Select all events that you would like to enter.
          </div>
          <div class="form-checkboxes">
            <?php $leagueCompetitions = array();
            foreach ($competitions AS $competition) {
              $leagueCompetitions[] = $competition->id; ?>
              <div class="form-checkboxes__item competitionList">
                <input class="form-checkboxes__input competitionId" id="competition[<?php echo $competition->id ?>]" name="competition[<?php echo $competition->id ?>]" type="checkbox" value=<?php echo $competition->id ?> aria-controls="conditional-competition-<?php echo $competition->id ?>">
                <label class="form-label form-checkboxes__label" for="competition[<?php echo $competition->id ?>]">
                  <?php echo $competition->name ?>
                </label>
              </div>

              <div class="form-checkboxes__conditional form-checkboxes__conditional--hidden" id="conditional-competition-<?php echo $competition->id ?>">
                <div id="event-hint" class="hint">
                  Select all teams that you would like to enter.
                </div>
                <?php $competitionTeams = array();
                foreach($competition->competitionTeams AS $competitionTeam) {
                  $competitionTeams[] = $competitionTeam->teamId; ?>
                  <div class="form-checkboxes__item teamCompetitionList">
                    <input class="form-checkboxes__input teamCompetitionId" id="teamCompetition[<?php echo $competition->id ?>][<?php echo $competitionTeam->teamId ?>]" name="teamCompetition[<?php echo $competition->id ?>][<?php echo $competitionTeam->teamId ?>]" type="checkbox" value=<?php echo $competitionTeam->teamId ?> aria-controls="conditional-team-competition-<?php echo $competition->id ?>-<?php echo $competitionTeam->teamId ?>">

                    <label class="form-label form-checkboxes__label" for="teamCompetition[<?php echo $competition->id ?>][<?php echo $competitionTeam->teamId ?>]">
                      <?php echo $competitionTeam->title ?>
                    </label>
                    <input type="hidden" value="<?php echo $competitionTeam->title ?>" name="teamCompetitionTitle[<?php echo $competition->id ?>][<?php echo $competitionTeam->teamId ?>]" />
                    <input type="hidden" value="<?php echo $competitionTeam->leagueId ?>" name="teamCompetitionLeague[<?php echo $competition->id ?>][<?php echo $competitionTeam->teamId ?>]" />
                  </div>
                  <div class="form-checkboxes__conditional form-checkboxes__conditional--hidden" id="conditional-team-competition-<?php echo $competition->id ?>-<?php echo $competitionTeam->teamId ?>">
                    <div class="form-group match-time">
                      <label class="form-label" for="matchday[<?php echo $competition->id ?>][<?php echo $competitionTeam->teamId ?>]"><?php _e( 'Match Day', 'racketmanager') ?></label>
                      <div class="input">
                        <input type="text" class="form-control" name="matchday[<?php echo $competition->id ?>][<?php echo $competitionTeam->teamId ?>]" id="matchday-<?php echo $competition->id ?>-<?php echo $competitionTeam->teamId ?>" value="<?php echo $competitionTeam->teamInfo->match_day ?>" />
                      </div>
                    </div>
                    <div class="form-group match-time">
                      <label class="form-label" for="matchtime[<?php echo $competition->id ?>][<?php echo $competitionTeam->teamId ?>]"><?php _e( 'Match Time', 'racketmanager') ?></label>
                      <div class="input">
                        <input type="time" class="form-control" name="matchtime[<?php echo $competition->id ?>][<?php echo $competitionTeam->teamId ?>]" id="matchtime-<?php echo $competition->id ?>-<?php echo $competitionTeam->teamId ?>" value="<?php echo $competitionTeam->teamInfo->match_time ?>" />
                      </div>
                    </div>
                    <div>
                      <div class="form-group">
                        <label class="form-label" for="captain[<?php echo $competition->id ?>]"><?php _e( 'Captain', 'racketmanager') ?></label>
                        <div class="input">
                          <input type="text" class="form-control teamcaptain" name="captain[<?php echo $competition->id ?>][<?php echo $competitionTeam->teamId ?>]" id="captain-<?php echo $competition->id ?>-<?php echo $competitionTeam->teamId ?>" value="<?php echo $competitionTeam->teamInfo->captain ?>" />
                          <input type="hidden" name="captainId[<?php echo $competition->id ?>][<?php echo $competitionTeam->teamId ?>]" id="captainId-<?php echo $competition->id ?>-<?php echo $competitionTeam->teamId ?>" value="<?php echo $competitionTeam->teamInfo->captainId ?>" />
                        </div>
                      </div>
                      <div class="form-group">
                        <label class="form-label" for="contactno[<?php echo $competition->id ?>][<?php echo $competitionTeam->teamId ?>]"><?php _e( 'Contact Number', 'racketmanager') ?></label>
                        <div class="input">
                          <input type="tel" class="form-control" name="contactno[<?php echo $competition->id ?>][<?php echo $competitionTeam->teamId ?>]" id="contactno-<?php echo $competition->id ?>-<?php echo $competitionTeam->teamId ?>" value="<?php echo $competitionTeam->teamInfo->contactno ?>" />
                        </div>
                      </div>
                      <div class="form-group">
                        <label class="form-label" for="contactemail[<?php echo $competition->id ?>][<?php echo $competitionTeam->teamId ?>]"><?php _e( 'Contact Email', 'racketmanager') ?></label>
                        <div class="input">
                          <input type="email" class="form-control" name="contactemail[<?php echo $competition->id ?>][<?php echo $competitionTeam->teamId ?>]" id="contactemail-<?php echo $competition->id ?>-<?php echo $competitionTeam->teamId ?>" value="<?php echo $competitionTeam->teamInfo->contactemail ?>" />
                        </div>
                      </div>
                    </div>
                  </div>
                <?php } ?>
                <input type="hidden" name="competitionTeams[<?php echo $competition->id ?>]" id="competitionTeams[<?php echo $competition->id ?>]" value="<?php echo implode(',',$competitionTeams) ?>" />
              </div>
            <?php } ?>
          </div>
        </fieldset>
      </div>
      <div class="form-group">
        <label class="form-label" for="numCourtsAvailable">
          <?php _e('How many courts are available for league matches?', 'racketmanager') ?>
        </label>
        <div class="input">
          <input type="number" id="numCourtsAvailable" name="numCourtsAvailable" />
        </div>
      </div>
      <div>
        <h3>Notes</h3>
        <ol>
          <li>See regulations 9-32 re League Rules.</li>
          <li>Summer leagues</li>
          <ul>
            <li>No weekend home days allowed.</li>
            <li>Please try to make mixed Mondays and Tuesdays.</li>
          </ul>
          <li>Winter leagues</li>
          <ul>
            <li>Please avoid weekend matches between 12:00 and 14:00.</li>
          </ul>
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
        <input type="hidden" name="leagueCompetitions" id="leagueCompetitions" value="<?php echo implode(',',$leagueCompetitions) ?>" />
        <input type="hidden" name="affiliatedClub" id="affiliatedClub" value="<?php echo $club->id ?>" />
        <input type="hidden" name="season" value="<?php echo $season ?>" />
        <input type="hidden" name="leagueSeason" value="<?php echo $type ?>" />
        <button class="btn" type="button" id="leagueEntrySubmit" name="leagueEntrySubmit" onclick="Racketmanager.leagueEntryRequest(this)">Enter Leagues</button>
        <div class="updateResponse" id="leagueEntryResponse" name="leagueEntryResponse"></div>
      </div>
    </form>
  <?php } else { ?>
    <p class="contact-login-msg">You need to <a href="<?php echo wp_login_url(); ?>">login</a> to enter leagues</p>
  <?php } ?>
</div><!-- .entry-content -->
