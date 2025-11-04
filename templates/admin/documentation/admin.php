<div class="container">
  <!--suppress HtmlUnknownAnchorTarget, HtmlUnknownAnchorTarget -->
    <a href="#top" class="alignright top-link"><?php _e( 'Top', 'racketmanager' ) ?></a>
  <p>The administrations page of Racketmanager allows general administration functions to be performed.</p>
  <!-- Nav tabs -->
  <ul class="nav nav-pills" id="myTab" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active show" id="player-request-tab" data-bs-toggle="pill" data-bs-target="#player-request" type="button" role="tab" aria-controls="player-request" aria-selected="true"><?php _e( 'Player Requests', 'racketmanager' ) ?></button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="players-tab" data-bs-toggle="pill" data-bs-target="#players" type="button" role="tab" aria-controls="players" aria-selected="false"><?php _e( 'Players', 'racketmanager' ) ?></button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="seasons-tab" data-bs-toggle="pill" data-bs-target="#seasons" type="button" role="tab" aria-controls="seasons" aria-selected="false"><?php _e( 'Seasons', 'racketmanager' ) ?></button>
    </li>
  </ul>
  <!-- Tab panes -->
  <div class="container tab-content">
    <div class="tab-pane active show fade" id="player-request" role="tabpanel" aria-labelledby="player-request-tab">
      <h3 class="header"><?php _e( 'Player Requests', 'racketmanager' ) ?></h3>
      <p>The player request page shows requests to register players for clubs.</p>
      <p>These requests are generated from the website club screen by match secretaries.</p>
      <p>The page lists the club, player, match secretary and date requested along with any completed date and user who completed the request.</p>
      <p>The <a href="<?php echo get_admin_url() ?>admin.php?page=racketmanager-settings">Confirmation setting</a> on the "Club Players" tab can be set to either "none" or "automatic". If an email address is set on this screen, an email notification will be sent to this address whenever a player request is made.</p>
      <dl>
        <dt><?php _e( 'Automatic Confirmation', 'racketmanager' ) ?></dt>
        <dd>With this set, the player will be automatically added to the club's registered players (and added to the list of players if a new player).</dd>
      </dl>
      <h4 id="Updates"><?php _e( 'Approval/Deletion', 'racketmanager' ) ?></h4>
      <p>Player requests can be approved individually by selecting the checkbox next to the request.</p>
      <p>Additionally, bulk approvals can be performed by selecting the checkbox in the header.</p>
      <p>Player requests can also be deleted. If these have been actioned then deleting the request has no impact on the registered players for the club.</p>
    </div>
    <div class="tab-pane fade" id="players" role="tabpanel" aria-labelledby="players-tab">
      <h3 class="header"><?php _e( 'Players', 'racketmanager' ) ?></h3>
      <div>The player page of Racketmanager allows a new player to be added to the system. Required fields are <ul><li>First name</li><li>Surname</li><li>Gender</li></ul></div>
      <div>Optionally, the LTA Tennis Number can be provided.</div>
      <div>Optionally, the player's email address can be provided.</div>
      <div>The page also lists all existing players registered.</div>
    </div>
    <div class="tab-pane fade" id="seasons" role="tabpanel" aria-labelledby="seasons-tab">
      <h3 class="header"><?php _e( 'Seasons', 'racketmanager' ) ?></h3>
      <p>The seasons page of Racketmanager lists all current seasons and allows new seasons to be defined.</p>
      <p>To add a new season, all the is required is the season name.</p>
      <h4 id="Existing Season"><?php _e( 'Existing Seasons', 'racketmanager' ) ?></h4>
      <p>For each season, it is possible to add this season to multiple competitions in one easy way. Clicking on the "Add Competitions" button displays a list of competitions (grouped into "Cups", "Leagues" and "Tournaments"). Firstly, the number of matchdays must be entered at the top of the screen. Clicking on the "checkbox" next to the competition name and pressing "Apply" will add the selected season to each competition. If the checkbox is not present, then the selected season is already added to the competition.</p>
    </div>
  </div>
</div>
