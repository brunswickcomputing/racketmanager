<?php
/**
Template page to display single club

The following variables are usable:


$club: club object

You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
*/
?>

<?php foreach ($clubs AS $club) { ?>
  <h2 class="title-post"><a href="/club/<?php echo sanitize_title($club->shortcode) ?>/"><?php echo $club->name ?></a></h2>
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
            if ($club->matchSecretaryContactNo !=null) { ?>
              <dt>Match Secretary Contact:</dt><dd><?php echo $club->matchSecretaryContactNo ?></dd>
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
      <div id="club-teams" class="team">
        <?php
        $shortCode = $club->shortcode;
        $competitions = $racketmanager->getCompetitions(array('type'=>'league'));
        if ( $competitions ) { ?>
          <h2 class="teams-header">Teams</h2>
          <div id="competition-list-<?php echo $club->id ?>" class="competition-list accordion accordion-flush">
            <?php foreach ($competitions AS $competition) {
              $competition = get_competition($competition);
              $teams = $competition->getTeamsInfo(array('affiliatedclub' => $club->id, 'orderby' => array("title" => "ASC") ));
              if ( $teams ) {
                ?>
                <div class="accordion-item">
                  <h3 class="header accordion-header" id="comp-<?php echo $competition->id ?>-club-<?php echo $club->id ?>">
                    <button class="accordion-button collapsed frontend" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo $competition->id ?>-club-<?php echo $club->id ?>" aria-expanded="false" aria-controls="collapse-<?php echo $competition->id ?>-club-<?php echo $club->id ?>">
                      <?php echo $competition->name ?>
                    </button>
                  </h3>
                  <div id="collapse-<?php echo $competition->id ?>-club-<?php echo $club->id ?>" class="accordion-collapse collapse" aria-labelledby="comp-<?php echo $competition->id ?>-club-<?php echo $club->id ?>" data-bs-parent="#competition-list-<?php echo $club->id ?>">
                    <div class="accordion-body">
                      <div class="row team">
                        <?php foreach ($teams AS $team ) { ?>
                          <h4><?php echo $team->title ?></h4>
                          <dl class="team">
                            <?php if ( !empty($team->captain) ) { ?>
                              <dt><?php _e( 'Captain', 'racketmanager' ) ?></dt><dd><?php echo $team->captain ?></dd>
                            <?php } ?>
                            <?php if ( is_user_logged_in() ) { ?>
                              <?php if ( !empty($team->contactno) ) { ?>
                                <dt><?php _e( 'Contact Number', 'racketmanager' ) ?></dt><dd><?php echo $team->contactno ?></dd>
                              <?php } ?>
                              <?php if ( !empty($team->contactemail) ) { ?>
                                <dt><?php _e( 'Contact Email', 'racketmanager' ) ?></dt><dd><?php echo $team->contactemail ?></dd>
                              <?php } ?>
                            <?php } ?>
                          </dl>
                        <?php } ?>
                      </div>
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
