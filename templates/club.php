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
if ( current_user_can( 'manage_leaguemanager' ) ) {
    $userCanUpdateClub = true;
} else {
    if ( $club->matchsecretary !=null && $club->matchsecretary == $userid ) {
        $userCanUpdateClub = true;
    }
}
?>

        <h1 class="title-post"><?php echo $club->name ?></h1>
        <div class="entry-content">
            <div class="tm-team-content">
                <div id="club-info">
                    <div class="team">
                        <form id="clubUpdateFrm" action="" method="post">
                            <?php wp_nonce_field( 'club-update' ) ?>
                            <input type="hidden" id="clubId" name="clubId" value="<?php echo $club->id ?>" />
                        <?php if ($club->contactno !=null || $userCanUpdateClub) { ?>
                            <div class="form-group">
                                <label for "clubContactNo"><?php _e( 'Contact Number', 'leaguemanager' ) ?></label>
                                <div class="input">
                                    <input type="tel" class="form-control" id="clubContactNo" name="clubContactNo" value="<?php echo $club->contactno ?>" <?php disabled($userCanUpdateClub, false) ?> />
                                </div>
                            </div>
                        <?php } ?>
                        <?php if ($club->facilities !=null || $userCanUpdateClub) { ?>
                            <div class="form-group">
                                <label for "facilities"><?php _e( 'Facilities', 'leaguemanager' ) ?></label>
                                <div class="input">
                                    <input type="text" class="form-control" id="facilities" name="facilities" value="<?php echo $club->facilities ?>" <?php disabled($userCanUpdateClub, false) ?> />
                                </div>
                            </div>
                        <?php } ?>
                        <?php if ($club->founded !=null || $userCanUpdateClub) { ?>
                            <div class="form-group">
                                <label for "founded"><?php _e( 'Founded', 'leaguemanager' ) ?></label>
                                <div class="input">
                                    <input type="number" class="form-control" id="founded" name="founded" value="<?php echo $club->founded ?>" <?php disabled($userCanUpdateClub, false) ?> />
                                </div>
                            </div>
                        <?php } ?>
                        <?php if ($club->matchsecretary !=null || $userCanUpdateClub) { ?>
                            <div class="form-group">
                                <label for "matchSecretaryName"><?php _e( 'Match Secretary', 'leaguemanager' ) ?></label>
                                <div class="input">
                                    <input type="text" class="form-control" id="matchSecretaryName" name="matchSecretaryName" value="<?php echo $club->matchSecretaryName ?>" <?php disabled($userCanUpdateClub, false) ?> />
                                    <input type="hidden" id="matchSecretaryId" name="matchSecretaryId" value="<?php echo $club->matchsecretary ?>" />
                                </div>
                            </div>
                        <?php if ( is_user_logged_in() ) { ?>
                            <?php if ($club->matchSecretaryEmail !=null || $userCanUpdateClub) { ?>
                            <div class="form-group">
                                <label for "matchSecretaryEmail"><?php _e( 'Match Secretary Email', 'leaguemanager' ) ?></label>
                                <div class="input">
                                    <input type="email" class="form-control" id="matchSecretaryEmail" name="matchSecretaryEmail" value="<?php echo $club->matchSecretaryEmail ?>" <?php disabled($userCanUpdateClub, false) ?> />
                                </div>
                            </div>
                            <?php }
                            if ($club->matchSecretaryContactNo !=null || $userCanUpdateClub) { ?>
                            <div class="form-group">
                               <label for "matchSecretaryContactNo"><?php _e( 'Match Secretary Contact', 'leaguemanager' ) ?></label>
                               <div class="input">
                                   <input type="tel" class="form-control" id="matchSecretaryContactNo" name="matchSecretaryContactNo" value="<?php echo $club->matchSecretaryContactNo ?>" <?php disabled($userCanUpdateClub, false) ?> />
                               </div>
                            </div>
                            <?php } ?>
                        <?php } else { ?>
                            <div class="form-group">
                                <div class="contact-login-msg">You need to <a href="<?php echo wp_login_url( $_SERVER['REQUEST_URI'] ); ?>">login</a> to access match secretary contact details</div>
                            </div>
                        <?php } ?>
                        <?php } ?>
                        <?php if ($club->website != null || $userCanUpdateClub) { ?>
                            <div class="form-group">
                                <label for "website"><?php _e( 'Website', 'leaguemanager' ) ?></label>
                                <div class="input">
                                    <input type="url" class="form-control" id="website" name="website" value="<?php echo $club->website ?>" <?php disabled($userCanUpdateClub, false) ?> />
                                </div>
                            </div>
                        <?php } ?>
                        <?php if ( $userCanUpdateClub ) { ?>
                            <button class="btn" type="button" id="updateClubSubmit" name="updateClubSubmit" onclick="Leaguemanager.updateClub(this)"><?php _e( 'Update details', 'leaguemanager' ) ?></button>
                            <div class="updateResponse" id="updateClub" name="updateClub"></div>
                        <?php } ?>
                        </form>
                    </div>
                </div>
                <script>
                jQuery(document).ready(function() {
                                  // Accordion
                                       jQuery("#club-players").accordion({ header: "h3",active: false, collapsible: true, heightStyle: content });
                                  }); //end of document ready
                </script>
                <div id="club-players">
                    <h2 class="roster-header"><?php _e( 'Players', 'leaguemanager' ) ?></h2>
                <?php if ( $userCanUpdateClub ) { ?>
                    <div id="rosterUpdate" class="">
                        <h3 class="header"><?php _e( 'Add player', 'leaguemanager' ) ?></h3>
                        <form id="rosterRequestFrm" action="" method="post" onsubmit="return checkSelect(this)">
                            <?php wp_nonce_field( 'roster-request' ) ?>

                            <input type="hidden" name="affiliatedClub" id="affiliatedClub" value="<?php echo $club->id ?>" />
                            <fieldset>
                                <div class="form-group">
                                    <label for="firstName"><?php _e( 'First name', 'leaguemanager' ) ?></label>
                                    <div class="input">
                                        <input required="required" type="text" class="form-control" id="firstName" name="firstName" size="30" class="form-control" />
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="surname"><?php _e( 'Surname', 'leaguemanager' ) ?></label>
                                    <div class="input">
                                        <input required="required" type="text" class="form-control" id="surname" name="surname" size="30" class="form-control" />
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label><?php _e( 'Gender', 'leaguemanager' ) ?></label>
                                    <div class="form-check">
                                        <input required="required" type="radio" id="genderMale" name="gender" value="M" class="form-check-input" />
                                        <label for="genderMale" class="form-check-label"><?php _e( 'Male', 'leaguemanager' ) ?></label>
                                    </div>
                                    <div class="form-check">
                                        <input type="radio" id="genderFemale" name="gender" value="F" class="form-check-input" "/>
                                        <label for="genderFemale" class="form-check-label"><?php _e( 'Female', 'leaguemanager' ) ?></label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="btm"><?php _e( 'BTM', 'leaguemanager' ) ?></label>
                                    <div class="input">
                                        <input type="number" class="form-control" placeholder="<?php _e( 'Enter BTM number', 'leaguemanager' ) ?>" name="btm" id="btm" size="11" class="form-control" />
                                    </div>
                                </div>
                            </fieldset>
                            <button class="btn" type="button" id="rosterUpdateSubmit" onclick="Leaguemanager.rosterRequest(this)"><?php _e( 'Add player', 'leaguemanager' ) ?></button>
                            <div id="updateResponse"></div>
                        </form>
                    <?php if ( $rosterRequests ) {?>
                        <h3 class="header"><?php _e( 'Pending players', 'leaguemanager' ) ?></h3>
                        <table class="widefat noborder" summary="" title="LeagueManager Pending Club Players">
                            <thead>
                                <tr>
                                    <th scope="col"><?php _e( 'Name', 'leaguemanager' ) ?></th>
                                    <th scope="col" class="colspan"><?php _e( 'Gender', 'leaguemanager') ?></th>
                                    <th scope="col" class="colspan"><?php _e( 'BTM', 'leaguemanager') ?></th>
                                    <th scope="col" class="colspan"><?php _e( 'Requested Date', 'leaguemanager') ?></th>
                                    <th scope="col" class="colspan"><?php _e( 'Requested By', 'leaguemanager') ?></th>
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
                <?php }
                    } ?>
                    <h3 class="header"><?php _e( 'Ladies', 'leaguemanager') ?></h3>
                    <div id="roster-ladies" class="">
                        <?php if ( $rosters ) { ?>
                        <form id="roster-ladies-remove" method="post" action="">
                        <?php wp_nonce_field( 'roster-remove' ) ?>
                            <table class="playerlist noborder" summary="" title="LeagueManager Club Ladies Players">
                                <thead>
                                    <tr>
                                        <th scope="col" class="check-column">
                                            <?php if ( $userCanUpdateClub ) { ?>
                                            <button class="btn" type="button" id="rosterRemoveSubmit" onclick="Leaguemanager.rosterRemove('#roster-ladies-remove')"><?php _e( 'Remove', 'leaguemanager') ?></button>
                                            <?php } ?>
                                        </th>
                                        <th scope="col"><?php _e( 'Name', 'leaguemanager' ) ?></th>
                                        <th scope="col" class="colspan"><?php _e( 'Created Date', 'leaguemanager') ?></th>
                                        <th scope="col" class="colspan"><?php _e( 'Created By', 'leaguemanager') ?></th>
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
                    <h3 class="header"><?php _e( 'Men', 'leaguemanager') ?></h3>
                    <div id="roster-men" class="">
                        <?php if ( $rosters ) { ?>
                        <form id="roster-men-remove" method="post" action="">
                        <?php wp_nonce_field( 'roster-remove' ) ?>

                            <table class="playerlist noborder" summary="" title="LeagueManager Club Mens Players">
                                <thead>
                                    <tr>
                                        <th scope="col" class="check-column">
                                        <?php if ( $userCanUpdateClub ) { ?>
                                            <button class="btn" type="button" id="rosterRemoveSubmit" onclick="Leaguemanager.rosterRemove('#roster-men-remove')"><?php _e( 'Remove', 'leaguemanager') ?></button>
                                        <?php } ?>
                                        </th>
                                        <th scope="col"><?php _e( 'Name', 'leaguemanager' ) ?></th>
                                        <th scope="col" class="colspan"><?php _e( 'Created Date', 'leaguemanager') ?></th>
                                        <th scope="col" class="colspan"><?php _e( 'Created By', 'leaguemanager') ?></th>
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
                <script>
                jQuery(document).ready(function() {
                                  // Accordion
                                       jQuery("#club-teams").accordion({ header: "h3",active: false, collapsible: true, heightStyle: content });
                                  }); //end of document ready
                </script>
                <div id="club-teams" class="team">
                    <?php $shortCode = $club->shortcode;
                        $competitions = $leaguemanager->getCompetitions(array('type'=>'league'));
                        if ( $competitions ) { ?>
                            <h2 class="teams-header"><?php _e( 'Teams', 'leaguemanager') ?></h2>
                            <div class="competition-list jquery-ui-accordion">
                        <?php foreach ($competitions AS $competition) {
                            $competition = get_competition($competition->id);
                            $teams = $competition->getTeamsInfo(array( 'affiliatedclub' => $club->id, 'orderby' => array("title" => "ASC") ));
                            if ( $teams ) { ?>
                                <div class="club-teams" id="competition-<?php echo $competition->id ?>">
                                    <h3 class="header"><?php echo $competition->name ?></h3>
                                    <div class="jquery-ui-tabs">
                                        <ul class="tablist ui-tabs-nav">
                                            <li><a href="#club-teams"><?php _e( 'Teams', 'leaguemanager') ?></a></li>
                                            <li><a href="#club-players"><?php _e( 'Players', 'leaguemanager') ?></a></li>
                                        </ul>
                                        <div id="club-teams" class="jquery-ui-tab">
                                        <?php foreach ($teams AS $team ) { ?>
                                            <div class="team" id="<?php echo $team->title ?>">
                                                <h4 class="title"><?php echo $team->title ?></h4>
                                                <form id="team-captain-update-<?php echo $competition->id ?>-<?php echo $team->id ?>-Frm" action="" method="post">
                                                <?php wp_nonce_field( 'team-captain-update' ) ?>
                                                <input type="hidden" id="team_id" name="team_id" value="<?php echo $team->id ?>" />
                                                <input type="hidden" id="competition_id" name="competition_id" value="<?php echo $competition->id ?>" />
                                                <?php if ( !empty($team->captain) || $userCanUpdateClub ) { ?>
                                                <div class="form-group">
                                                            <label for "captain-<?php echo $competition->id ?>-<?php echo $team->id ?>"><?php _e( 'Captain', 'leaguemanager' ) ?></label>
                                                            <div class="input">
                                                                <input type="text" class="teamcaptain form-control" id="captain-<?php echo $competition->id ?>-<?php echo $team->id ?>" name="captain-<?php echo $competition->id ?>-<?php echo $team->id ?>" value="<?php echo $team->captain ?>" <?php disabled($userCanUpdateClub, false) ?> />
                                                                <input type="hidden" id="captainId-<?php echo $competition->id ?>-<?php echo $team->id ?>" name="captainId-<?php echo $competition->id ?>-<?php echo $team->id ?>" value="<?php echo $team->captainId ?>" />
                                                            </div>
                                                </div>
                                                <?php } ?>
                                                <?php if ( is_user_logged_in() ) { ?>
                                                    <?php if ( !empty($team->contactno) || $userCanUpdateClub ) { ?>
                                                            <div class="form-group">
                                                                <label for "contactno-<?php echo $competition->id ?>-<?php echo $team->id ?>"><?php _e( 'Contact Number', 'leaguemanager' ) ?></label>
                                                                <div class="input">
                                                                    <input type="tel" class="form-control" id="contactno-<?php echo $competition->id ?>-<?php echo $team->id ?>" name="contactno-<?php echo $competition->id ?>-<?php echo $team->id ?>" value="<?php echo $team->contactno ?>" <?php disabled($userCanUpdateClub, false) ?> />
                                                                </div>
                                                            </div>
                                                    <?php } ?>
                                                    <?php if ( !empty($team->contactemail) || $userCanUpdateClub ) { ?>
                                                            <div class="form-group">
                                                                <label for "contactemail-<?php echo $competition->id ?>-<?php echo $team->id ?>"><?php _e( 'Contact Email', 'leaguemanager' ) ?></label>
                                                                <div class="input">
                                                                    <input type="email" class="form-control" id="contactemail-<?php echo $competition->id ?>-<?php echo $team->id ?>" name="contactemail-<?php echo $competition->id ?>-<?php echo $team->id ?>" value="<?php echo $team->contactemail ?>" size="30" <?php disabled($userCanUpdateClub, false) ?> />
                                                                </div>
                                                            </div>
                                                    <?php } ?>
                                                <?php } ?>
                                                <?php if ( $userCanUpdateClub ) { ?>
                                                <button class="btn" type="button" id="teamUpdateSubmit-<?php echo $competition->id ?>-<?php echo $team->id ?>" name="teamUpdateSubmit-<?php echo $competition->id ?>-<?php echo $team->id ?>" onclick="Leaguemanager.teamCaptainUpdate(this)">Update details</button>
                                                <div class="updateResponse" id="updateTeamResponse-<?php echo $competition->id ?>-<?php echo $team->id ?>" name="updateTeamResponse-<?php echo $competition->id ?>-<?php echo $team->id ?>"></div>
                                                <?php } ?>
                                                </form>
                                            </div>
                                        <?php  } ?>
                                        </div>
                                        <div id="club-players" class="jquery-ui-tab">
                                            <?php $season = $competition->getSeasonCompetition(); ?>
                                            <table class="playerstats" summary="" title="LeagueManager Player Stats">
                                                <thead>
                                                    <tr>
                                                        <th rowspan="2" scope="col"><?php _e( 'Name', 'leaguemanager' ) ?></th>
                                                        <th colspan="<?php echo $season['num_match_days'] ?>" scope="colgroup" class="colspan"><?php _e( 'Match Day', 'leaguemanager') ?></th>
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
                                    </div>
                                </div>
                            <?php }
                             } ?>
                            </div>
                        <?php } ?>
                    </div>
                </div>
               <?php $address = $club->address;
                   $latitude = $club->latitude;
                   $longitude = $club->longitude;
                   if ( $address != null ) { ?>
                <div class="club-address">
                    <div class="form-group">
                        <label for "address"><?php _e( 'Address', 'leaguemanager' ); ?></label>
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
                <?php wp_link_pages( array(
                                         'before' => '<div class="page-links">' . __( 'Pages:', 'leaguemanager' ),
                                         'after'  => '</div>',
                                         )
                                  ); ?>
                </div>
        </div><!-- .entry-content -->
