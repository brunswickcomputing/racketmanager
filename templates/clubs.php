<?php
/**
Template page to display single club

The following variables are usable:
 

	$club: club object

	You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
*/
?>

<?php foreach ($clubs AS $club) { ?>
            <h2 class="title-post"><a href="/club/<?php echo sanitize_title($club->name) ?>/"><?php echo $club->name ?></a></h2>
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
                        <?php
                            if ($club->address != null) { ?>
                                <dt>Address:</dt><dd><?php echo $club->address ?></dd>
                        <?php } ?>
                        </dl>
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
                                        </div>
                                    <?php }

                                        } ?>
                                    </div>

                            <?php }
                        ?>
                    </div>
                </div>
    

            </div><!-- .entry-content -->
<?php } ?>
