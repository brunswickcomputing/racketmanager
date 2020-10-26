<?php
/**
Template page to display single club

The following variables are usable:
 

	$club: club object

	You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
*/
?>

            <h1 class="title-post"><?php echo $club->name ?></h1>
            <div class="entry-content">
                <div class="tm-team-content">
                    <div id="club-info">
                        <dl class="team">
                        <?php
                            if ($club->founded !=null) { ?>
                                <dt>Founded:</dt><dd><?php echo $club->founded ?></dd>
                            <?php } ?>

                        <?php
                            if ($club->facilities !=null) { ?>
                                <dt>Facilities:</dt><dd><?php echo $club->facilities; ?></dd>
                            <?php } ?>

                        <?php
                            if ($club->contactno !=null) { ?>
                                <dt>Contact Number:</dt><dd><?php echo $club->contactno; ?></dd>
                            <?php } ?>

                        <?php

                            if ($club->matchsecretary !=null) { ?>
                                <dt>Match Secretary:</dt><dd><?php echo $club->matchSecretaryName; ?></dd>
                                <?php if ($club->matchSecretaryEmail !=null) { ?>
                                    <dt>Match Secretary Email:</dt><dd><?php echo $club->matchSecretaryEmail; ?></dd>
                                <?php }
                                if ($club->matchSecretaryContactno !=null) { ?>
                                    <dt>Match Secretary Contact:</dt><dd><?php echo $club->matchSecretaryContactno ?></dd>
                                <?php } ?>
                            <?php } ?>

                        <?php
                            if ($club->website != null) { ?>
                                <dt>Website:</dt><dd><a href="<?php echo esc_url($club->website); ?>"><?php echo esc_url($club->website); ?></a></dd>
                            <?php } ?>
                        </dl>
                    </div>
                    <script>
                    jQuery(document).ready(function() {
                                      // Accordion
                                           jQuery("#club-roster").accordion({ header: "h3",active: false, collapsible: true, heightStyle: content });
                                      
                                      }); //end of document ready
                    </script>
                    <div id="club-roster">
                        <h2 class="roster-header">Players</h2>
<?php
    $userid = get_current_user_id();
    $userCanUpdateRoster = false;
    if ( current_user_can( 'manage_leaguemanager' ) ) {
        $userCanUpdateRoster = true;
    } else {
        if ( $club->matchsecretary !=null && $club->matchsecretary = $userid ) {
            $userCanUpdateRoster = true;
        }
    }
    if ( $userCanUpdateRoster ) { ?>
        <div id="rosterUpdate" class="">
            <h3 class="header">Add player</h3>
                <form id="rosterRequestFrm" action="#" method="post" onsubmit="return checkSelect(this)">
                    <?php wp_nonce_field( 'roster-request' ) ?>

                    <input type="hidden" name="affiliatedClub" id="affiliatedClub" value="<?php echo $club->id ?>" />
                    <fieldset>
                        <div class="form-group">
                            <label for="firstName">First name</label>
                            <div class="input">
                                <input required="required" type="text" id="firstName" name="firstName" size="30" class="form-control" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="surname">Surname</label>
                            <div class="input">
                                <input required="required" type="text" id="surname" name="surname" size="30" class="form-control" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Gender</label>
                            <div class="form-check">
                                <input required="required" type="radio" id="genderMale" name="gender" value="M" class="form-check-input" />
                                <label for="genderMale" class="form-check-label">Male</label>
                            </div>
                            <div class="form-check">
                                <input type="radio" id="genderFemale" name="gender" value="F" class="form-check-input" "/>
                                <label for="genderFemale" class="form-check-label">Female</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="btm">BTM</label>
                            <div class="input">
                                <input type="number" placeholder="Enter BTM number" name="btm" id="btm" size="11" class="form-control" />
                            </div>
                        </div>
                    </fieldset>
                    <button class="btn" type="button" id="rosterUpdateSubmit" onclick="Leaguemanager.rosterRequest(this)">Add player</button>
                    <div id="updateResponse"></div>
                </form>
        </div>
    <?php }
?>
                        <?php $rosters = $leaguemanager->getRoster(array( 'club' => $club->id, 'inactive' => "Y", 'type' => 'real', 'cache' => false)); ?>
                        <div class="roster-list jquery-ui-accordion">
                            <h3 class="header">Ladies</h3>
                            <div id="roster-ladies" class="">
                                <?php if ( $rosters ) {
                                    $class = ''; ?>
                                    <dl class="roster">
                                    <?php foreach ($rosters AS $roster ) {
                                       if ( $roster->gender == "F" ) {
                                           $class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
                                        <dd class="<?php echo $class ?>"><?php echo $roster->fullname ?></dd>
                                    <?php }
                                        } ?>
                                    </dl>
                                <?php } ?>
                            </div>
                            <h3 class="header">Men</h3>
                            <div id="roster-men" class="">
                                <?php if ( $rosters ) {
                                   $class = ''; ?>
                                   <dl class="roster">
                                   <?php foreach ($rosters AS $roster ) {
                                       if ( $roster->gender == "M" ) {
                                           $class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
                                        <dd class="<?php echo $class ?>"><?php echo $roster->fullname ?></dd>
                                    <?php }
                                        } ?>
                                    </dl>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                    <script>
                    jQuery(document).ready(function() {
                                      // Accordion
                                           jQuery("#team-info").accordion({ header: "h3",active: false, collapsible: true, heightStyle: content });
                                      
                                      }); //end of document ready
                    </script>
                    <div id="team-info" class="team">
                        <?php
                            $shortCode = $club->shortcode;
                            $competitions = $leaguemanager->getCompetitions('league');
                            if ( $competitions ) { ?>
                                <h2 class="teams-header">Teams</h2>
                                <div class="competition-list jquery-ui-accordion">
                                <?php foreach ($competitions AS $competition) {
                                    $teams = $leaguemanager->getTeamsInfo(array('competition_id' => $competition->id, 'affiliatedclub' => $club->id, 'orderby' => array("title" => "ASC") ));
                                    if ( $teams ) {
                                        ?>
                                            <div class="club-teams" id="competition-<?php echo $competition->id ?>">
                                                <h3 class="header"><?php echo $competition->name ?></h3>
                                                <div class="jquery-ui-tabs">
                                                    <ul class="tablist ui-tabs-nav">
                                                        <li><a href="#club-teams">Teams</a></li>
                                                        <li><a href="#club-players">Players</a></li>
                                                    </ul>
                                                    <div id="club-teams" class="jquery-ui-tab">
                                                        <div class="team">
                                                            <?php foreach ($teams AS $team ) { ?>
                                                            <dl class="team">
                                                            <dd><?php echo $team->title ?></dd>
<?php if ( !empty($team->captain) ) { ?>
            <dt><?php _e( 'Captain', 'leaguemanager' ) ?></dt><dd><?php echo $team->captain ?></dd>
<?php } ?>
<?php if ( is_user_logged_in() ) { ?>
    <?php if ( !empty($team->contactno) ) { ?>
            <dt><?php _e( 'Contact Number', 'leaguemanager' ) ?></dt><dd><?php echo $team->contactno ?></dd>
    <?php } ?>
    <?php if ( !empty($team->contactemail) ) { ?>
            <dt><?php _e( 'Contact Email', 'leaguemanager' ) ?></dt><dd><?php echo $team->contactemail ?></dd>
    <?php } ?>
<?php } ?>
                                                            </dl>
                                                            <?php } ?>
                                                        </div>
                                                    </div>
                                                    <div id="club-players" class="jquery-ui-tab">
                                                        <?php $season = $leaguemanager->getSeasonCompetition($competition); ?>
                                                        <table class="widefat playerstats" summary="" title="LeagueManager Player Stats">
                                                            <thead>
                                                                <tr>
                                                                    <th rowspan="2" scope="col"><?php _e( 'Name', 'leaguemanager' ) ?></th>
                                                                    <th colspan="<?php echo $season['num_match_days'] ?>" scope="colgroup" class="colspan"><?php _e( 'Match Day', 'leaguemanager') ?></th>
                                                                </tr>
                                                                <tr>
                                                        <?php
                                                            $matchdaystatsdummy = array();
                                                            for ( $day = 1; $day <= $season['num_match_days']; $day++ ) {
                                                                $matchdaystatsdummy[$day] = array();
                                                            ?>
                                                                    <th scope="col" class="matchday"><?php echo $day ?></th>
                                                        <?php } ?>
                                                                </tr>
                                                            </thead>
                                                            <tbody id="the-list">
                                                    <?php if ( $playerstats = $leaguemanager->getPlayerStats(array('competition' => $competition->id, 'season' => $season['name'], 'club' => $club->id ))  ) { $class = ''; ?>
                                                        <?php foreach ( $playerstats AS $playerstat ) { ?>
                                                                <?php $class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
                                                                <tr class="<?php echo $class ?>">

                                                                    <th ><?php echo $playerstat->fullname ?></th>

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

                            <?php }
                        ?>
                    </div>
                </div>
               <?php
                    
                    $address = $club->address;
                    $latitude = $club->latitude;
                    $longitude = $club->longitude;
                    
                    if ( $address != null ) { ?>

                        <div class="sp-template sp-template-event-venue">
                            <table class="sp-data-table sp-event-venue">
                                <thead>
                                    <tr>
                                        <th><?php _e( 'Address', 'sydney' ); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="sp-event-venue-address-row">
                                        <td><?php echo $address; ?></td>
                                    </tr>
                                    <?php
                                        if ( $latitude != null && $longitude != null ) {
                                            $zoom = 15;
                                            $maptype = 'roadmap';
                                            ?>
                                            <tr class="sp-event-venue-map-row">
                                                <td>
                                                    <iframe class="sp-google-map" width="100%" height="320" frameborder="0" style="border:0"
                                                        src="https://www.google.com/maps/embed/v1/search?key=AIzaSyDUiLHqXfZMMfuo5jnp7jyBmQhkWkHupvQ&amp;q=<?php echo $address; ?>&amp;center=<?php echo $latitude; ?>,<?php echo $longitude; ?>&amp;zoom=<?php echo $zoom; ?>&amp;maptype=<?php echo $maptype; ?>" allowfullscreen>
                                                    </iframe>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    <?php } ?>
                <div id="clubdesc">
<!--                    <?php //echo $club->desc; ?> -->
                    <?php
                        wp_link_pages( array(
                                             'before' => '<div class="page-links">' . __( 'Pages:', 'sydney' ),
                                             'after'  => '</div>',
                                             )
                                      );
                    ?>
                </div>
            </div><!-- .entry-content -->
