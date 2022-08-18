<?php
/**
Template page to display single club

The following variables are usable:


$club: club object
$rosters: rosters object

You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
*/
$user = wp_get_current_user();
$userid = $user->ID;
$userCanUpdateClub = false;
if ( current_user_can( 'manage_racketmanager' ) ) {
  $userCanUpdateClub = true;
} else {
  if ( $club->matchsecretary !=null && $club->matchsecretary == $userid ) {
    $userCanUpdateClub = true;
  }
}
?>

<h1 class="club-name">
  <?php echo $club->name ?>
  <?php if ( is_user_logged_in() ) {
    $isFavourite = $racketmanager->userFavourite('club', $club->id); ?>
    <div class="fav-icon">
      <a href="" id="fav-<?php echo $club->id ?>" title="<?php if ( $isFavourite) { _e( 'Remove favourite', 'racketmanager' ); } else { _e( 'Add favourite', 'racketmanager'); } ?>" data-js="add-favourite" data-type="club" data-favourite="<?php echo $club->id ?>">
        <i class="fav-icon-svg racketmanager-svg-icon <?php if ( $isFavourite ) { echo 'fav-icon-svg-selected'; } ?>">
          <?php racketmanager_the_svg('icon-star') ?>
        </i>
      </a>
      <div class="fav-msg" id="fav-msg-<?php echo $club->id ?>"></div>
    </div>
  <?php } ?>
</h1>
<div class="entry-content">
  <div class="team">
    <form id="clubUpdateFrm" action="" method="post">
      <?php wp_nonce_field( 'club-update' ) ?>
      <input type="hidden" id="clubId" name="clubId" value="<?php echo $club->id ?>" />
      <?php if ($club->contactno !=null || $userCanUpdateClub) { ?>
        <div class="form-floating mb-3">
          <input type="tel" class="form-control" id="clubContactNo" name="clubContactNo" value="<?php echo $club->contactno ?>" <?php disabled($userCanUpdateClub, false) ?> />
          <label for "clubContactNo"><?php _e( 'Contact Number', 'racketmanager' ) ?></label>
        </div>
      <?php } ?>
      <?php if ($club->facilities !=null || $userCanUpdateClub) { ?>
        <div class="form-floating mb-3">
          <input type="text" class="form-control" id="facilities" name="facilities" value="<?php echo $club->facilities ?>" <?php disabled($userCanUpdateClub, false) ?> />
          <label for "facilities"><?php _e( 'Facilities', 'racketmanager' ) ?></label>
        </div>
      <?php } ?>
      <?php if ($club->founded !=null || $userCanUpdateClub) { ?>
        <div class="form-floating mb-3">
          <input type="number" class="form-control" id="founded" name="founded" value="<?php echo $club->founded ?>" <?php disabled($userCanUpdateClub, false) ?> />
          <label for "founded"><?php _e( 'Founded', 'racketmanager' ) ?></label>
        </div>
      <?php } ?>
      <?php if ($club->matchsecretary !=null || $userCanUpdateClub) { ?>
        <div class="form-floating mb-3">
          <input type="text" class="form-control" id="matchSecretaryName" name="matchSecretaryName" value="<?php echo $club->matchSecretaryName ?>" <?php disabled($userCanUpdateClub, false) ?> />
          <label for "matchSecretaryName"><?php _e( 'Match Secretary', 'racketmanager' ) ?></label>
          <input type="hidden" id="matchSecretaryId" name="matchSecretaryId" value="<?php echo $club->matchsecretary ?>" />
        </div>
        <?php if ( is_user_logged_in() ) { ?>
          <?php if ($club->matchSecretaryEmail !=null || $userCanUpdateClub) { ?>
            <div class="form-floating mb-3">
              <input type="email" class="form-control" id="matchSecretaryEmail" name="matchSecretaryEmail" value="<?php echo $club->matchSecretaryEmail ?>" <?php disabled($userCanUpdateClub, false) ?> />
              <label for "matchSecretaryEmail"><?php _e( 'Match Secretary Email', 'racketmanager' ) ?></label>
            </div>
          <?php }
          if ($club->matchSecretaryContactNo !=null || $userCanUpdateClub) { ?>
            <div class="form-floating mb-3">
              <input type="tel" class="form-control" id="matchSecretaryContactNo" name="matchSecretaryContactNo" value="<?php echo $club->matchSecretaryContactNo ?>" <?php disabled($userCanUpdateClub, false) ?> />
              <label for "matchSecretaryContactNo"><?php _e( 'Match Secretary Contact', 'racketmanager' ) ?></label>
            </div>
          <?php } ?>
        <?php } else { ?>
          <div class="form-floating mb-3">
            <div class="contact-login-msg">You need to <a href="<?php echo wp_login_url( $_SERVER['REQUEST_URI'] ); ?>">login</a> to access match secretary contact details</div>
          </div>
        <?php } ?>
      <?php } ?>
      <?php if ($club->website != null || $userCanUpdateClub) { ?>
        <div class="form-floating mb-3">
          <input type="url" class="form-control" id="website" name="website" value="<?php echo $club->website ?>" <?php disabled($userCanUpdateClub, false) ?> />
          <label for "website"><?php _e( 'Website', 'racketmanager' ) ?></label>
        </div>
      <?php } ?>
      <?php if ( $userCanUpdateClub ) { ?>
        <button class="btn" type="button" id="updateClubSubmit" name="updateClubSubmit" onclick="Racketmanager.updateClub(this)"><?php _e( 'Update details', 'racketmanager' ) ?></button>
        <div class="updateResponse" id="updateClub" name="updateClub"></div>
      <?php } ?>
    </form>
  </div>
  <details id="results">
    <summary>
      <h2 class="results-header"><?php _e( 'Latest results', 'racketmanager' ) ?></h2>
    </summary>
    <?php racketmanager_results( $club->id, array() ) ?>
  </details>
  <details id="club-players">
    <summary>
      <h2 class="roster-header"><?php _e( 'Players', 'racketmanager' ) ?></h2>
    </summary>
    <div id="players" class="accordion accordion-flush">
      <?php if ( $userCanUpdateClub ) { ?>
        <div class="accordion-item">
          <h3 class="accordion-header" id="heading-addplayer">
            <button class="accordion-button collapsed frontend" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-addplayer" aria-expanded="false" aria-controls="collapse-addplayer">
              <?php _e( 'Add player', 'racketmanager' ) ?>
            </button>
          </h3>
          <div id="collapse-addplayer" class="accordion-collapse collapse" aria-labelledby="heading-addplayer" data-bs-parent="#players">
            <div class="accordion-body">
              <form id="rosterRequestFrm" action="" method="post" onsubmit="return checkSelect(this)">
                <?php wp_nonce_field( 'roster-request' ) ?>

                <input type="hidden" name="affiliatedClub" id="affiliatedClub" value="<?php echo $club->id ?>" />
                <fieldset>
                  <div class="form-floating mb-3">
                    <input required="required" type="text" class="form-control" id="firstName" name="firstName" size="30" class="form-control" placeholder="First name" aria-describedby="firstNameFeedback" />
                    <label for="firstName"><?php _e( 'First name', 'racketmanager' ) ?></label>
                    <div id="firstNameFeedback" class="invalid-feedback"></div>
                  </div>
                  <div class="form-floating mb-3">
                    <input required="required" type="text" class="form-control" id="surname" name="surname" size="30" class="form-control" placeholder="Surname" aria-describedby="surnameFeedback" />
                    <label for="surname"><?php _e( 'Surname', 'racketmanager' ) ?></label>
                    <div id="surnameFeedback" class="invalid-feedback"></div>
                  </div>
                  <div class="form-group mb-3">
                    <label id="gender"><?php _e( 'Gender', 'racketmanager' ) ?></label>
                    <div class="form-check">
                      <input required="required" type="radio" id="genderMale" name="gender" value="M" class="form-check-input" />
                      <label for="genderMale" class="form-check-label"><?php _e( 'Male', 'racketmanager' ) ?></label>
                    </div>
                    <div class="form-check">
                      <input type="radio" id="genderFemale" name="gender" value="F" class="form-check-input" />
                      <label for="genderFemale" class="form-check-label"><?php _e( 'Female', 'racketmanager' ) ?></label>
                    </div>
                    <div id="genderFeedback" class="invalid-feedback"></div>
                  </div>
                  <div class="form-floating mb-3">
                    <input type="number" class="form-control" placeholder="<?php _e( 'Enter BTM number', 'racketmanager' ) ?>" name="btm" id="btm" size="11" class="form-control" aria-describedby="btmFeedback" />
                    <label for="btm"><?php _e( 'BTM', 'racketmanager' ) ?></label>
                    <div id="btmFeedback" class="invalid-feedback"></div>
                  </div>
                  <div class="form-floating mb-3">
                    <input type="email" class="form-control" placeholder="<?php _e( 'Enter email address', 'racketmanager' ) ?>" name="email" id="email" class="form-control" aria-describedby="emailFeedback" />
                    <label for="email"><?php _e( 'Email address', 'racketmanager' ) ?></label>
                    <div id="emailFeedback" class="invalid-feedback"></div>
                  </div>
                </fieldset>
                <button class="btn" type="button" id="rosterUpdateSubmit" onclick="Racketmanager.rosterRequest(this)"><?php _e( 'Add player', 'racketmanager' ) ?></button>
                <div id="updateResponse"></div>
              </form>
            </div>
          </div>
        </div>
        <?php if ( $rosterRequests ) {?>
          <div class="accordion-item">
            <h3 class="accordion-header" id="heading-pendingplayer">
              <button class="accordion-button collapsed frontend" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-pendingplayer" aria-expanded="false" aria-controls="collapse-pendingplayer">
                <?php _e( 'Pending players', 'racketmanager' ) ?>
              </button>
            </h3>
            <div id="collapse-pendingplayer" class="accordion-collapse collapse" aria-labelledby="heading-pendingplayer" data-bs-parent="#players">
              <div class="accordion-body">
                <table class="widefat noborder" summary="" title="RacketManager Pending Club Players">
                  <thead>
                    <tr>
                      <th scope="col"><?php _e( 'Name', 'racketmanager' ) ?></th>
                      <th scope="col" class="colspan"><?php _e( 'Gender', 'racketmanager') ?></th>
                      <th scope="col" class="colspan"><?php _e( 'BTM', 'racketmanager') ?></th>
                      <th scope="col" class="colspan"><?php _e( 'Requested Date', 'racketmanager') ?></th>
                      <th scope="col" class="colspan"><?php _e( 'Requested By', 'racketmanager') ?></th>
                    </tr>
                  </thead>
                  <tbody id="pendingRosters">
                    <?php $class=''; ?>
                    <?php foreach ($rosterRequests as $rosterRequest) { ?>
                      <?php $class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
                      <tr class="<?php echo $class ?>">
                        <th scope="row"><?php echo $rosterRequest->first_name . ' ' . $rosterRequest->surname; ?></th>
                        <td><?php echo $rosterRequest->gender; ?></td>
                        <td><?php echo $rosterRequest->btm; ?></td>
                        <td><?php echo $rosterRequest->requested_date; ?></td>
                        <td><?php echo $rosterRequest->requestedUser; ?></td>
                      </tr>
                    <?php } ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        <?php }
      } ?>
      <div class="accordion-item">
        <h3 class="accordion-header" id="heading-ladies">
          <button class="accordion-button collapsed frontend" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-ladies" aria-expanded="false" aria-controls="collapse-ladies">
            <?php _e( 'Ladies', 'racketmanager' ) ?>
          </button>
        </h3>
        <div id="collapse-ladies" class="accordion-collapse collapse" aria-labelledby="heading-ladies" data-bs-parent="#players">
          <div class="accordion-body">
            <?php if ( $rosters ) { ?>
              <form id="roster-ladies-remove" method="post" action="">
                <?php wp_nonce_field( 'roster-remove' ) ?>
                <table class="playerlist noborder" summary="" title="RacketManager Club Ladies Players">
                  <thead>
                    <tr>
                      <th scope="col" class="check-column">
                        <?php if ( $userCanUpdateClub ) { ?>
                          <button class="btn" type="button" id="rosterRemoveSubmit" onclick="Racketmanager.rosterRemove('#roster-ladies-remove')"><?php _e( 'Remove', 'racketmanager') ?></button>
                        <?php } ?>
                      </th>
                      <th scope="col"><?php _e( 'Name', 'racketmanager' ) ?></th>
                      <th scope="col" class="colspan"><?php _e( 'Created Date', 'racketmanager') ?></th>
                      <th scope="col" class="colspan"><?php _e( 'Created By', 'racketmanager') ?></th>
                    </tr>
                  </thead>
                  <tbody id="Club Ladies Players">
                    <?php $class = ''; ?>
                    <?php foreach ($rosters AS $roster ) {
                      if ( $roster->gender == "F" ) {
                        $class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
                        <tr class="<?php echo $class ?>" id="roster-<?php echo $roster->roster_id ?>">
                          <th scope="row" class="check-column">
                            <?php if ( $userCanUpdateClub ) { ?>
                              <input type="checkbox" value="<?php echo $roster->roster_id ?>" name="roster[<?php echo $roster->roster_id ?>]" />
                            <?php } ?>
                          </th>
                          <td><?php echo $roster->fullname; ?></td>
                          <td><?php echo $roster->created_date; ?></td>
                          <td><?php echo $roster->createdUserName; ?></td>
                        </tr>
                      <?php }
                    } ?>
                  </tbody>
                </table>
              </form>
            <?php } ?>
          </div>
        </div>
      </div>
      <div class="accordion-item">
        <h3 class="accordion-header" id="heading-men">
          <button class="accordion-button collapsed frontend" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-men" aria-expanded="false" aria-controls="collapse-men">
            <?php _e( 'Men', 'racketmanager' ) ?>
          </button>
        </h3>
        <div id="collapse-men" class="accordion-collapse collapse" aria-labelledby="heading-men" data-bs-parent="#players">
          <div class="accordion-body">
            <?php if ( $rosters ) { ?>
              <form id="roster-men-remove" method="post" action="">
                <?php wp_nonce_field( 'roster-remove' ) ?>

                <table class="playerlist noborder" summary="" title="RacketManager Club Mens Players">
                  <thead>
                    <tr>
                      <th scope="col" class="check-column">
                        <?php if ( $userCanUpdateClub ) { ?>
                          <button class="btn" type="button" id="rosterRemoveSubmit" onclick="Racketmanager.rosterRemove('#roster-men-remove')"><?php _e( 'Remove', 'racketmanager') ?></button>
                        <?php } ?>
                      </th>
                      <th scope="col"><?php _e( 'Name', 'racketmanager' ) ?></th>
                      <th scope="col" class="colspan"><?php _e( 'Created Date', 'racketmanager') ?></th>
                      <th scope="col" class="colspan"><?php _e( 'Created By', 'racketmanager') ?></th>
                    </tr>
                  </thead>
                  <tbody id="Club Mens Players">

                    <?php $class = ''; ?>
                    <?php foreach ($rosters AS $roster ) {
                      if ( $roster->gender == "M" ) {
                        $class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
                        <tr class="<?php echo $class ?>" id="roster-<?php echo $roster->roster_id ?>">
                          <th scope="row" class="check-column">
                            <?php if ( $userCanUpdateClub ) { ?>
                              <input type="checkbox" value="<?php echo $roster->roster_id ?>" name="roster[<?php echo $roster->roster_id ?>]" />
                            <?php } ?>
                          </th>
                          <td><?php echo $roster->fullname; ?></td>
                          <td><?php echo $roster->created_date; ?></td>
                          <td><?php echo $roster->createdUserName; ?></td>
                        </tr>
                      <?php }
                    } ?>
                  </tbody>
                </table>
              </form>
            <?php } ?>
          </div>
        </div>
      </div>
    </div>
  </details>
  <?php $shortCode = $club->shortcode;
  $competitions = $racketmanager->getCompetitions(array('type'=>'league'));
  $matchdays = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
  if ( $competitions ) { ?>
    <details id="club-teams">
      <summary>
        <h2 class="teams-header"><?php _e( 'Teams', 'racketmanager') ?></h2>
      </summary>
      <div class="competition-list accordion accordion-flush">
        <?php foreach ($competitions AS $competition) {
          $competition = get_competition($competition->id);
          $teams = $competition->getTeamsInfo(array( 'affiliatedclub' => $club->id, 'orderby' => array("title" => "ASC") ));
          if ( $teams ) { ?>
            <div class="accordion-item">
              <h3 class="header accordion-header" id="comp-<?php echo $competition->id ?>-club-<?php echo $club->id ?>">
                <button class="accordion-button collapsed frontend" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo $competition->id ?>-club-<?php echo $club->id ?>" aria-expanded="false" aria-controls="collapse-<?php echo $competition->id ?>-club-<?php echo $club->id ?>">
                  <?php echo $competition->name ?>
                </button>
              </h3>
              <div id="collapse-<?php echo $competition->id ?>-club-<?php echo $club->id ?>" class="accordion-collapse collapse" aria-labelledby="comp-<?php echo $competition->id ?>-club-<?php echo $club->id ?>" data-bs-parent="#competition-list-<?php echo $club->id ?>">
                <div class="accordion-body">
                  <div class="row team">
                    <!-- Nav tabs -->
                    <ul class="nav nav-tabs frontend" id="myTab" role="tablist">
                    	<li class="nav-item active" role="presentation">
                    		<button class="nav-link" id="teams-tab-<?php echo $competition->id ?>" data-bs-toggle="tab" data-bs-target="#teams-<?php echo $competition->id ?>" type="button" role="tab" aria-controls="teams-<?php echo $competition->id ?>" aria-selected="true"><?php _e( 'Teams', 'racketmanager' ) ?></button>
                    	</li>
                      <li class="nav-item" role="presentation">
                    		<button class="nav-link" id="players-tab-<?php echo $competition->id ?>" data-bs-toggle="tab" data-bs-target="#players-<?php echo $competition->id ?>" type="button" role="tab" aria-controls="players-<?php echo $competition->id ?>" aria-selected="true"><?php _e( 'Players', 'racketmanager' ) ?></button>
                    	</li>
                      <li class="nav-item" role="presentation">
                    		<button class="nav-link" id="matches-tab-<?php echo $competition->id ?>" data-bs-toggle="tab" data-bs-target="#matches-<?php echo $competition->id ?>" type="button" role="tab" aria-controls="matches-<?php echo $competition->id ?>" aria-selected="true"><?php _e( 'Matches', 'racketmanager' ) ?></button>
                    	</li>
                    </ul>
                    <!-- Tab panes -->
                    <div class="tab-content">
                    	<div class="tab-pane fade active show" id="teams-<?php echo $competition->id ?>" role="tabpanel" aria-labelledby="teams-tab-<?php echo $competition->id ?>">
                        <?php foreach ($teams AS $team ) { ?>
                          <div class="team" id="<?php echo $team->title ?>">
                            <h4 class="title"><?php echo $team->title ?></h4>
                            <form id="team-update-<?php echo $competition->id ?>-<?php echo $team->id ?>-Frm" action="" method="post" class="form-control">
                              <?php wp_nonce_field( 'team-update' ) ?>
                              <input type="hidden" id="team_id" name="team_id" value="<?php echo $team->id ?>" />
                              <input type="hidden" id="competition_id" name="competition_id" value="<?php echo $competition->id ?>" />
                              <?php if ( !empty($team->captain) || $userCanUpdateClub ) { ?>
                                <div class="form-floating mb-3">
                                    <input type="text" class="teamcaptain form-control" id="captain-<?php echo $competition->id ?>-<?php echo $team->id ?>" name="captain-<?php echo $competition->id ?>-<?php echo $team->id ?>" value="<?php echo $team->captain ?>" <?php disabled($userCanUpdateClub, false) ?> />
                                    <input type="hidden" id="captainId-<?php echo $competition->id ?>-<?php echo $team->id ?>" name="captainId-<?php echo $competition->id ?>-<?php echo $team->id ?>" value="<?php echo $team->captainId ?>" />
                                    <label for "captain-<?php echo $competition->id ?>-<?php echo $team->id ?>"><?php _e( 'Captain', 'racketmanager' ) ?></label>
                                </div>
                              <?php } ?>
                              <?php if ( is_user_logged_in() ) { ?>
                                <?php if ( !empty($team->contactno) || $userCanUpdateClub ) { ?>
                                  <div class="form-floating mb-3">
                                      <input type="tel" class="form-control" id="contactno-<?php echo $competition->id ?>-<?php echo $team->id ?>" name="contactno-<?php echo $competition->id ?>-<?php echo $team->id ?>" value="<?php echo $team->contactno ?>" <?php disabled($userCanUpdateClub, false) ?> />
                                      <label for "contactno-<?php echo $competition->id ?>-<?php echo $team->id ?>"><?php _e( 'Contact Number', 'racketmanager' ) ?></label>
                                  </div>
                                <?php } ?>
                                <?php if ( !empty($team->contactemail) || $userCanUpdateClub ) { ?>
                                  <div class="form-floating mb-3">
                                      <input type="email" class="form-control" id="contactemail-<?php echo $competition->id ?>-<?php echo $team->id ?>" name="contactemail-<?php echo $competition->id ?>-<?php echo $team->id ?>" value="<?php echo $team->contactemail ?>" size="30" <?php disabled($userCanUpdateClub, false) ?> />
                                      <label for "contactemail-<?php echo $competition->id ?>-<?php echo $team->id ?>"><?php _e( 'Contact Email', 'racketmanager' ) ?></label>
                                  </div>
                                <?php } ?>
                              <?php } ?>
                              <?php if ( !empty($team->match_day) ) { ?>
                                <div class="form-floating mb-3">
                                    <?php if ( $userCanUpdateClub ) { ?>
                                      <select class="form-select" size="1" name="matchday-<?php echo $competition->id ?>-<?php echo $team->id ?>" id="matchday-<?php echo $competition->id ?>-<?php echo $team->id ?>" >
                                        <option><?php _e( 'Select match day' , 'racketmanager') ?></option>
                                        <?php foreach ( $matchdays AS $matchday ) { ?>
                                          <option value="<?php echo $matchday ?>"<?php if(isset($team->match_day)) selected($matchday, $team->match_day ) ?> <?php disabled($userCanUpdateClub, false) ?>><?php echo $matchday ?></option>
                                        <?php } ?>
                                      </select>
                                    <?php } else { ?>
                                      <input type="text" class="form-control" id="matchday-<?php echo $competition->id ?>-<?php echo $team->id ?>" name="matchday-<?php echo $competition->id ?>-<?php echo $team->id ?>" value="<?php echo $team->match_day ?>" <?php disabled($userCanUpdateClub, false) ?> />
                                    <?php } ?>
                                    <label for "match_day-<?php echo $competition->id ?>-<?php echo $team->id ?>"><?php _e( 'Match Day', 'racketmanager' ) ?></label>
                                </div>
                              <?php } ?>
                              <?php if ( !empty($team->match_time) || $userCanUpdateClub ) { ?>
                                <div class="form-floating mb-3">
                                    <input type="time" class="form-control" id="matchtime-<?php echo $competition->id ?>-<?php echo $team->id ?>" name="matchtime-<?php echo $competition->id ?>-<?php echo $team->id ?>" value="<?php echo $team->match_time ?>" size="30" <?php disabled($userCanUpdateClub, false) ?> />
                                    <label for "matchtime-<?php echo $competition->id ?>-<?php echo $team->id ?>"><?php _e( 'Match Time', 'racketmanager' ) ?></label>
                                </div>
                              <?php } ?>
                              <?php if ( $userCanUpdateClub ) { ?>
                                <button class="btn  mb-3" type="button" id="teamUpdateSubmit-<?php echo $competition->id ?>-<?php echo $team->id ?>" name="teamUpdateSubmit-<?php echo $competition->id ?>-<?php echo $team->id ?>" onclick="Racketmanager.teamUpdate(this)">Update details</button>
                                <div class="updateResponse" id="updateTeamResponse-<?php echo $competition->id ?>-<?php echo $team->id ?>" name="updateTeamResponse-<?php echo $competition->id ?>-<?php echo $team->id ?>"></div>
                              <?php } ?>
                            </form>
                          </div>
                        <?php  } ?>
                    	</div>
                      <div class="tab-pane fade" id="players-<?php echo $competition->id ?>" role="tabpanel" aria-labelledby="players-tab-<?php echo $competition->id ?>">
                          <?php $season = $competition->getSeasonCompetition(); ?>
                          <table class="playerstats" summary="" title="RacketManager Player Stats">
                            <thead>
                              <tr>
                                <th rowspan="2" scope="col"><?php _e( 'Name', 'racketmanager' ) ?></th>
                                <th colspan="<?php echo $season['num_match_days'] ?>" scope="colgroup" class="colspan"><?php _e( 'Match Day', 'racketmanager') ?></th>
                              </tr>
                              <tr>
                                <?php $matchdaystatsdummy = array();
                                for ( $day = 1; $day <= $season['num_match_days']; $day++ ) {
                                  $matchdaystatsdummy[$day] = array(); ?>
                                  <th scope="col" class="matchday"><?php echo $day ?></th>
                                <?php } ?>
                              </tr>
                            </thead>
                            <tbody id="the-list">
                              <?php if ( $playerstats = $competition->getPlayerStats(array( 'season' => $season['name'], 'club' => $club->id ))  ) { $class = ''; ?>
                              <?php foreach ( $playerstats AS $playerstat ) { ?>
                                <?php $class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
                                <tr class="<?php echo $class ?>">
                                  <th class="playername"><?php echo $playerstat->fullname ?></th>
                                  <?php $matchdaystats = $matchdaystatsdummy;
                                  $prevMatchDay = $i = 0;
                                  foreach ( $playerstat->matchdays AS $matches) {
                                    if ( !$prevMatchDay == $matches->match_day ) {
                                      $i = 0;
                                    }
                                    if ( $matches->match_winner == $matches->team_id ) {
                                      $matchresult = 'Won';
                                    } else {
                                      $matchresult = 'Lost';
                                    }
                                    $matchresult = $matches->match_winner == $matches->team_id ? 'Won' : 'Lost';
                                    $rubberresult = $matches->rubber_winner == $matches->team_id ? 'Won' : 'Lost';
                                    $matchdaystats[$matches->match_day][$i] = array('team' => $matches->team_title, 'pair' => $matches->rubber_number, 'matchresult' => $matchresult, 'rubberresult' => $rubberresult);
                                    $prevMatchDay = $matches->match_day;
                                    $i++;
                                  }
                                  foreach ( $matchdaystats AS $daystat ) {
                                    $dayshow = '';
                                    $title = '';
                                    foreach ( $daystat AS $stat ) {
                                      if ( isset($stat['team']) ) {
                                        $title        .= $matchresult.' match & '.$rubberresult.' rubber ';
                                        $team        = str_replace($shortCode,'',$stat['team']);
                                        $pair        = $stat['pair'];
                                        $dayshow    .= $team.'<br />Pair'.$pair.'<br />';
                                      }
                                    }
                                    if ( $dayshow == '' ) {
                                      echo '<td class="matchday" title=""></td>';
                                    } else {
                                      echo '<td class="matchday" title="'.$title.'">'.$dayshow.'</td>';
                                    }
                                  }
                                  $matchdaystats = $matchdaystatsdummy; ?>
                                </tr>
                              <?php } ?>
                            <?php } ?>
                          </tbody>
                        </table>
                      </div>
                      <div class="tab-pane fade" id="matches-<?php echo $competition->id ?>" role="tabpanel" aria-labelledby="matches-tab-<?php echo $competition->id ?>">
                        <?php $season = $competition->getSeasonCompetition(); ?>
                        <table class="mt-3" summary="" title="RacketManager Club matches">
                          <thead>
                            <tr>
                              <th scope="col"><?php _e( 'Match date', 'racketmanager' ) ?></th>
                              <th scope="col"><?php _e( 'Match', 'racketmanager' ) ?></th>
                              <th scope="col"><?php _e( 'League', 'racketmanager' ) ?></th>
                            </tr>
                          </thead>
                          <tbody id="the-list">
                            <?php if ( $matches = $racketmanager->getMatches(array('competition_id' => $competition->id, 'season' => $season['name'], 'affiliatedClub' => $club->id, 'orderby' => array('match_day' => 'ASC', 'date' => 'ASC', 'league_id' => 'ASC', 'home_team' => 'ASC'))) ) {
                              $class = '';
                              $matchDay = ''; ?>
                            <?php foreach ( $matches AS $match ) { ?>
                              <?php $class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
                              <?php if ( $matchDay != $match->match_day) {
                                $matchDay = $match->match_day; ?>
                                <tr class="<?php echo $class ?>">
                                  <td colspan="3"><?php echo __('Match Day', 'racketmanager').' '.$matchDay ?></td>
                                </tr>
                              <?php } ?>
                              <tr class="<?php echo $class ?>">
                                <td><?php echo $match->date ?></td>
                        				<td><?php echo $match->match_title ?></td>
                        				<td><?php echo $match->league->title ?></td>
                              </tr>
                            <?php } ?>
                          <?php } ?>
                        </tbody>
                      </table>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          <?php } ?>
        <?php } ?>
      </div>
    </details>
  <?php } ?>
<?php $address = $club->address;
$latitude = $club->latitude;
$longitude = $club->longitude;
if ( $address != null ) { ?>
  <div class="club-address">
    <div class="form-group">
      <label for "address"><?php _e( 'Address', 'racketmanager' ); ?></label>
      <div class="input">
        <?php if ( $latitude != null && $longitude != null ) {
          $zoom = 15;
          $maptype = 'roadmap'; ?>
          <iframe class="sp-google-map" width="100%" height="320" frameborder="0" style="border:0" src="https://www.google.com/maps/embed/v1/search?key=AIzaSyDUiLHqXfZMMfuo5jnp7jyBmQhkWkHupvQ&amp;q=<?php echo $address; ?>&amp;center=<?php echo $latitude; ?>,<?php echo $longitude; ?>&amp;zoom=<?php echo $zoom; ?>&amp;maptype=<?php echo $maptype; ?>" allowfullscreen>
          </iframe>
        </div>
      <?php } else { ?>
        <input type="text" class="form-control" id="address" name="address" disabled value="<?php echo $club->address ?>" />
      <?php } ?>
    </div>
  </div>
<?php } ?>
<div id="clubdesc">
  <!--                    <?php //echo $club->desc; ?> -->
</div>
</div><!-- .entry-content -->
