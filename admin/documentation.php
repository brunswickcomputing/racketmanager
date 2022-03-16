<?php
/**
* RacketManager Documentation
*
*/

if ( !current_user_can( 'racket_manager' ) ) {
  echo '<p style="text-align: center;">'.__("You do not have sufficient permissions to access this page.").'</p>';
} else {
  ?>

  <div class="container">
    <!-- Nav tabs -->
    <ul class="nav nav-pills" id="myTab" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active show" id="competitions-tab" data-bs-toggle="pill" data-bs-target="#competitions" type="button" role="tab" aria-controls="competitions" aria-selected="true"><?php _e( 'Competitions', 'racketmanager' ) ?></button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="leagues-tab" data-bs-toggle="pill" data-bs-target="#leagues" type="button" role="tab" aria-controls="leagues" aria-selected="false"><?php _e( 'Leagues', 'racketmanager' ) ?></button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="cups-tab" data-bs-toggle="pill" data-bs-target="#cups" type="button" role="tab" aria-controls="cups" aria-selected="false"><?php _e( 'Cups', 'racketmanager' ) ?></button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="tournaments-tab" data-bs-toggle="pill" data-bs-target="#tournaments" type="button" role="tab" aria-controls="tournaments" aria-selected="false"><?php _e( 'Tournaments', 'racketmanager' ) ?></button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="clubs-tab" data-bs-toggle="pill" data-bs-target="#clubs" type="button" role="tab" aria-controls="clubs" aria-selected="false"><?php _e( 'Clubs', 'racketmanager' ) ?></button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="results-tab" data-bs-toggle="pill" data-bs-target="#results" type="button" role="tab" aria-controls="results" aria-selected="false"><?php _e( 'Results', 'racketmanager' ) ?></button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="admin-tab" data-bs-toggle="pill" data-bs-target="#admin" type="button" role="tab" aria-controls="admin" aria-selected="false"><?php _e( 'Administration', 'racketmanager' ) ?></button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="settings-tab" data-bs-toggle="pill" data-bs-target="#settings" type="button" role="tab" aria-controls="settings" aria-selected="false"><?php _e( 'Settings', 'racketmanager' ) ?></button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="import-tab" data-bs-toggle="pill" data-bs-target="#import" type="button" role="tab" aria-controls="import" aria-selected="false"><?php _e( 'Import', 'racketmanager' ) ?></button>
      </li>
    </ul>
    <!-- Tab panes -->
    <div class="container tab-content">
      <div class="tab-pane active show fade" id="competitions" role="tabpanel" aria-labelledby="competitions-tab">
        <h2><?php _e( 'Competitions', 'racketmanager' ) ?></h2>
        <div class="container">
          <a href="#top" class="alignright top-link"><?php _e( 'Top', 'racketmanager' ) ?></a>
          <p>The main page of Racketmanager shows an overview of the competitions in the database together with a few statistics on the number of seasons, leagues and format. At the beginning it is necessary to add at least one season for which the number of match days is also specified. Furthermore the competition preferences need to be set. You can chose the sport type and point rules.</p>
          <h3 id="Types"><?php _e( 'Competition Types', 'racketmanager' ) ?></h3>
          <p>There are three types of competitions available.</p>
          <h4 id="Cups"><?php _e( 'Cups', 'racketmanager' ) ?></h4>
          <p>A cup competition has a mode of Championship and an entry team of Team.</p>
          <h4 id="Leagues"><?php _e( 'Leagues', 'racketmanager' ) ?></h4>
          <p>A league competition has a mode of default (entry type is not required but equates to Team).</p>
          <h4 id="Tournaments"><?php _e( 'Tournaments', 'racketmanager' ) ?></h4>
          <p>A tournament competition has a mode of Championship and an entry team of Player.</p>
          <h3 id="Leagues"><?php _e( 'Leagues', 'racketmanager' ) ?></h3>
          <p>The leagues page of Racketmanager show an overview of the leagues for a competition together with a few statistics on the number of teams and matches for the latest season.</p>
          <p>Player statistics are also available to view which show which players have played in the league on each match day.</p>
          <p>Clicking the league title takes the user to the individual league page. Here there are three tabs and options to add multiple teams, a single team  and matches. The "Add Teams" button should be used to add existing teams to the league. The "Add Team" button should only be used to add a new team to "Tournaments".</p>
          <h4 id="Standings"><?php _e( 'Standings', 'racketmanager' ) ?></h4>
          <p>Clicking the team name allows captain details to be entered for the team along with home match day and start time. The captain details are automatically pulled from teh club roster as the captain name is typed. Selecting the captain allows the contact details to be updated and saved.</p>
          <p>Points can be adjusted by entering a value in the "+/- points" field. This the cumulative total of point adjustments.</p>
          <p>The "Update Ranking" button applies the latest shows the updated rankings.</p>
          <h4 id="Crosstable"><?php _e( 'Crosstable', 'racketmanager' ) ?></h4>
          <p>This tab shows the scores or future date and time for matches between teams in the league.</p>
          <h4 id="Match Plan"><?php _e( 'Match Plan', 'racketmanager' ) ?></h4>
          <p>This tab by default shows the matches for the latest match day. Other match days can be selected from the filter.</p>
          <p>If the matches consist of multiple rubbers, clicking the "View Rubbers" button will display the rubbers. These can then be completed and the match result saved. This automatically calculated the points and stores these, updating the standings table as well.</p>
          <h3 id="Cups/Tournaments"><?php _e( 'Cups/Tournaments', 'racketmanager' ) ?></h3>
          <p>For leagues in these types of competitions, the tabs are different from a standard league. The "Add Matches" button should not be used for these competitions.</p>
          <h4 id="Final Results"><?php _e( 'Final Results', 'racketmanager' ) ?></h4>
          <p>This tab shows the matches for each round along with scores.</p>
          <h4 id="Final"><?php _e( 'Final', 'racketmanager' ) ?></h4>
          <p>This tab is where the matches are created. Each round show be created starting with the first and ending with the final. The first round matches will consist of "Team Rank x" names along with "Bye". The "Team Rank" refers to the teams in the "Prelimary Rounds" tab.</p>
          <p>For subsequent rounds the teams will be "Winner of <em>round</em> x".</p>
          <h5>Match Results</h5>
          <p>If the competition has rubbers then the "View Rubbers" button should be pressed to enter the rubber players and scores. However, it is possible to just enter the match scores for these matches.</p>
          <p>Where no rubbers are involved the set scores are entered directly into the relevant fields.</p>
          <p>There is usually no need to enter match scores as these will be automatically calculated from the rubber result or set scores.</p>
          <p>Matches with a "Bye" for a team do not need a score to be entered.</p>
          <p>If there is a walkover for a match the score should be entered as 2-0.</p>
          <p>Pressing the "Save Results" button will update the matches and automatically advance the winner team to the next round.</p>
          <p>Once matches have both teams advanced, clicking on the match name will allow the "Location" to be updated to indicate the "Home" team</p>
          <h4 id="Preliminary"><?php _e( 'Preliminary Rounds', 'racketmanager' ) ?></h4>
          <p>This tab is where the teams are entered. For <em>existing</em> teams, the "Add Teams" button should be used to enable multiple teams to be selected and added to the league. For "Tournaments" only, the "Add Team" button should be used to create a <em>new</em> team. Once the teams have been added to the league, they can arranged manually using "drag and drop" and clicking "Save Ranking" or automatically by clicking "Random Ranking".</p>
          <p>Once the matches have been created and the administrator is happy with the ranking, the "Proceed to Final Rounds" button should be pressed. On the first round matches, this will replace "Team Ranking x" with the name of the team in that position in the ranking.</p>
          <p>Further changes to rankings after "Proceed to Final Rounds" has been pressed will have no affect on the matches. If changes are required the matches will need to be updated to have "Team Ranking x" before proceeding again.</p>
        </div>
      </div>
      <div class="tab-pane fade" id="leagues" role="tabpanel" aria-labelledby="leagues-tab">
        <h2><?php _e( 'Leagues', 'racketmanager' ) ?></h2>
        <div class="container">
          <a href="#top" class="alignright top-link"><?php _e( 'Top', 'racketmanager' ) ?></a>
          <p>The leagues page shows all the league competitions that have been created.</p>
          <p>Clicking on cup name takes the user to the league competition page.</p>
        </div>
      </div>
      <div class="tab-pane fade" id="cups" role="tabpanel" aria-labelledby="cups-tab">
        <h2><?php _e( 'Cups', 'racketmanager' ) ?></h2>
        <div class="container">
          <a href="#top" class="alignright top-link"><?php _e( 'Top', 'racketmanager' ) ?></a>
          <p>The cups page shows all the cup competitions that have been created.</p>
          <p>Clicking on cup name takes the user to the cup competition page.</p>
          <h3 id="notifycups"><?php _e( 'Notify Entry Open', 'racketmanager' ) ?></h3>
          <p>Match secretaries for all clubs can be notified when cup entries are allowed.</p>
          <p>Selecting the season and cup type (summer or winter) and then pressing the "Notify cup entry open" button sends an email to match secretaries for all clubs with a link the the cup entry form.</p>
        </div>
      </div>
      <div class="tab-pane fade" id="tournaments" role="tabpanel" aria-labelledby="tournaments-tab">
        <h2><?php _e( 'Tournaments', 'racketmanager' ) ?></h2>
        <div class="container">
          <a href="#top" class="alignright top-link"><?php _e( 'Top', 'racketmanager' ) ?></a>
          <p>The tournaments page shows all the tournaments that have been created.</p>
          <h3 id="Deleting tournaments"><?php _e( 'Deleting tournaments', 'racketmanager' ) ?></h3>
          <p>Tournaments can also be deleted from this page.</p>
          <h3 id="Adding tournaments"><?php _e( 'Adding tournaments', 'racketmanager' ) ?></h3>
          <p>Clicking the "Add Tournament" button takes the user to a screen where the new tournament details can be entered. The required fields are <ul><li>Name</li><li>Type (Summer or Winter)</li><li>Season</li><li>Closing date for entries</li><li>Tournament secretary</li></ul></p>
          <p>Typing in the tournament secretary name will automatically populate a list of user.</p<p>Clicking on the name will automatically populate the tournament secretary name along with any contact number and email address associated.</p>
          <p>Entering or amending these details will update the associated player record when the "Update" button is pressed.</p>
          <p>Additionally, the following information may be entered<ul><li>Host Club</li><li>Website</li><li>Finals Date</li></ul></p>
          <h3 id="Editing tournament details"><?php _e( 'Editing tournament details', 'racketmanager' ) ?></h3>
          <p>Clicking on the tournament name in the list of tournaments displays the edit screen.</p>
          <h3 id="tournamentcompetitions"><?php _e( 'Tournament Competitions', 'racketmanager' ) ?></h3>
          <p>Clicking on the "Competitions" button takes the user to a page showing the competitions for the tournament.</p>
          <h3 id="tournamentopen"><?php _e( 'Notify', 'racketmanager' ) ?></h3>
          <p>Clicking on the "Notify" button sends an email to match secretaries for all clubs with a link to the tournament entry page.</p>
        </div>
      </div>
      <div class="tab-pane fade" id="clubs" role="tabpanel" aria-labelledby="clubs-tab">
        <h2><?php _e( 'Clubs', 'racketmanager' ) ?></h2>
        <div class="container">
          <a href="#top" class="alignright top-link"><?php _e( 'Top', 'racketmanager' ) ?></a>
          <p>The clubs page of Racketmanager lists all the registered clubs. The match secretary for each club is shown on the main screen.</p>
          <p>There are links to view the registered players and teams of each club.</p>
          <h3 id="Deleting clubs"><?php _e( 'Deleting clubs', 'racketmanager' ) ?></h3>
          <p>Clubs can also be deleted from this page. However, if the club still has teams attached, the club is prevented from deletion.</p>
          <h3 id="Adding clubs"><?php _e( 'Adding clubs', 'racketmanager' ) ?></h3>
          <p>Clicking the "Add Club" button takes the user to a screen where the new club details can be entered. The required fields are <ul><li>Name</li><li>Type (currently only affiliated is available)</li><li>Shortcode (used in team names)</li><li>Facilites</li><li>Address (which can be entered or selected from a map view)</li></ul></p>
          <p>Additionally, the following information may be entered<ul><li>Contact Number</li><li>Website</li><li>Year Founded</li></ul></p>
          <h3 id="Editing club details"><?php _e( 'Editing club details', 'racketmanager' ) ?></h3>
          <p>Clicking on the club name in the list of clubs displays the edit screen.</p>
          <p>In addition to the fields available when adding a club, the match secretary can be entered or amended.</p>
          <p>Typing in the match secretary name will automatically populate a list of players registered with the club.</p<p>Clicking on the player name will automatically populate the match secretary name along with any contact number and email address associated with the player.</p>
          <p>Entering or amending these details will update the associated player record when the "Update" button is pressed.</p>
        </div>
        <div class="container">
          <a href="#top" class="alignright top-link"><?php _e( 'Top', 'racketmanager' ) ?></a>
          <h3 id="rosters"><?php _e( 'Players', 'racketmanager' ) ?></h3>
          <p>The players page within a club allows the players registered for that club to be viewed.</p>
          <h4 id="Adding players"><?php _e( 'Adding players', 'racketmanager' ) ?></h4>
          <p>A player to be added to the registered players for the club on this page. Player details are entered (first name, surname, gender and, optionally, BTM). Pressing the "Add Player" button causes the player to be added to the registered players for the club. The centreal player record is created if it does not already exist.</p>
          <h4 id="Removing players"><?php _e( 'Removing players', 'racketmanager' ) ?></h4>
          <p>From the list of registered players, individuals can be removed from the club records by clicking the checkbox and then selecting "Delete" from the "Bulk Actions" dropdown. Pressing the "Apply" button will remove the selected player from the list of registered players for the club. Any player who was previously on registered for the club who has subsequently been removed is also listed but there is no checkbox available. Additionally, the date that the player was removed from the list of registered players is shown, along with the user who actioned the removal request.</p>
          <p>It is possible to add and remove a player from the list of registered players multiple times.</p>
        </div>
        <div class="container">
          <a href="#top" class="alignright top-link"><?php _e( 'Top', 'racketmanager' ) ?></a>
          <h3 id="teams"><?php _e( 'Teams', 'racketmanager' ) ?></h3>
          <p>The teams page within a club lists all the teams that are linked to that club.</p>
          <h4 id="Deleting teams"><?php _e( 'Deleting teams', 'racketmanager' ) ?></h4>
          <p>Teams can also be deleted from this page. However, if the team is in a league, the team is prevented from deletion.</p>
          <h4 id="Adding teams"><?php _e( 'Adding teams', 'racketmanager' ) ?></h4>
          <p>New teams can also be created on this screen. Selecting the type of team (singles/doubles, ladies/mens/mixed) and pressing the "Add team" button will create the team.</p>
          <p>The name is automatically generated from the club short code, team type and the next sequence number based on existing teams for the club.</p>
        </div>
      </div>
      <div class="tab-pane fade" id="results" role="tabpanel" aria-labelledby="results-tab">
        <h2><?php _e( 'Results', 'racketmanager' ) ?></h2>
        <div class="container">
          <h3 class="header"><?php _e( 'Results Checker', 'racketmanager' ) ?></h3>
          <a href="#top" class="alignright top-link"><?php _e( 'Top', 'racketmanager' ) ?></a>
          <p>The results checker page of Racketmanager shows any player checks that have been failed whenever a match result is input.</p>
          <p>For any of these checks to be applied, the relevant <a href="<?php echo get_admin_url() ?>admin.php?page=racketmanager-settings">setting</a> must be entered on the "Player Checks" tab.</p>
          <dl>
            <dt><?php _e( 'Roster lead time', 'racketmanager' ) ?></dt>
            <dd>This checks how long a player must be registered before they are eligible to play.</dd>
            <dt><?php _e( 'End of season eligibility', 'racketmanager' ) ?></dt>
            <dd>This checks how many rounds at the end of the season do not allow new players to be registered.</dd>
            <dt><?php _e( 'Locked players', 'racketmanager' ) ?></dt>
            <dd>This checks how many matches a player may play for a higher team before they are locked to that team.</dd>
            <dt><?php _e( 'Approval/Deletion', 'racketmanager' ) ?></dt>
            <dd>Each check can be either marked as approved or can be deleted.</dd>
          </dl>
          <p>If the match result is updated, any records for the match and player are regenerated.</p>
          <p>However, if a player is swapped for another player in the match result, the original player entry will remain in the list of result checks. It can then be deleted if required.</p>
        </div>
        <div class="container">
          <h3 class="header"><?php _e( 'Results', 'racketmanager' ) ?></h3>
          <a href="#top" class="alignright top-link"><?php _e( 'Top', 'racketmanager' ) ?></a>
          <p>The results page of Racketmanager shows any results that have been entered by users that need administration approval.</p>
          <p>The ability for users to enter match results themselves is controlled by the <a href="<?php echo get_admin_url() ?>admin.php?page=racketmanager-settings">settings</a> on the "Match Results" tab.</p>
          <p>If an email address is set on this screen, an email notification will be sent to this address whenever a match result is entered by users.</p>
          <dl>
            <dt><?php _e( 'Minimum level to update results', 'racketmanager' ) ?></dt>
            <dd>There are three options that control who can enter results:</dd>
            <dl>
              <dt><?php _e( 'None', 'racketmanager' ) ?></dt>
              <dd>Results are not able to be entered by users.</dd>
              <dt><?php _e( 'Captain', 'racketmanager' ) ?></dt>
              <dd>Captains of the team involved in the match are able to enter the results.</dd>
              <dt><?php _e( 'Roster', 'racketmanager' ) ?></dt>
              <dd>Any player registered with the club of the team involved in the match are able to enter the results.</dd>
            </dl>
            <dt><?php _e( 'Result Entry', 'racketmanager' ) ?></dt>
            <dd>There are two options that control who can enter results:</dd>
            <dl>
              <dt><?php _e( 'Home', 'racketmanager' ) ?></dt>
              <dd>Results must be entered by the home team with approval by the away team.</dd>
              <dt><?php _e( 'Either', 'racketmanager' ) ?></dt>
              <dd>Results can be entered by either the home or away team. Approval is required by the alternative team.</dd>
            </dl>
            <dt><?php _e( 'Result Confirmation', 'racketmanager' ) ?></dt>
            <dd>There are two options that control how match result confirmation is handled:</dd>
            <dl>
              <dt><?php _e( 'None', 'racketmanager' ) ?></dt>
              <dd>Match results must be confirmed by the league administrator. Rubber results are available to view as these have already been entered.</dd>
              <dt><?php _e( 'Automatic', 'racketmanager' ) ?></dt>
              <dd>Match results are automatically updated from the result entry on the frontend. If this value is set, the only matches that are shown on this screen are where the opposing sides disagree with the result.</dd>
            </dl>
          </dl>
        </div>
      </div>
      <div class="tab-pane fade" id="admin" role="tabpanel" aria-labelledby="admin-tab">
        <h2><?php _e( 'Administration', 'racketmanager' ) ?></h2>
        <div class="container">
          <a href="#top" class="alignright top-link"><?php _e( 'Top', 'racketmanager' ) ?></a>
          <p>The adminstrations page of Racketmanager allows general administration functions to be performed.</p>
          <h3 class="header"><?php _e( 'Player Requests', 'racketmanager' ) ?></h3>
          <p>The player request page shows requests to register players for clubs.</p>
          <p>These requests are generated from the website club screen by match secretaries.</p>
          <p>The page lists the club, player, match secretary and date requested along with any completed date and user who completed the request.</p>
          <p>The <a href="<?php echo get_admin_url() ?>admin.php?page=racketmanager-settings">Roster Confirmation setting</a> on the "Rosters" tab can be set to either "none" or "automatic". If an email address is set on this screen, an email notification will be sent to this address whenever a player request is made.</p>
          <dl>
            <dt><?php _e( 'Automatic Confirmation', 'racketmanager' ) ?></dt>
            <dd>With this set, the player will be automatically added to the club roster (and added to the list of players if a new player).</dd>
          </dl>
          <h4 id="Updates"><?php _e( 'Approval/Deletion', 'racketmanager' ) ?></h4>
          <p>Player requests can be approved individually by selecting the checkbox next to the request.</p>
          <p>Additionally, bulk approvals can be performed by selecting the checkbox in the header.</p>
          <p>Player requests can also be deleted. If these have been actioned then deleting the request has no impact on the registered players for the club.</p>
          <h3 class="header"><?php _e( 'Players', 'racketmanager' ) ?></h3>
          <p>The player page of Racketmanager allows a new player to be added to the system. Required fields are <ul><li>First name</li><li>Surname</li><li>Gender</li></ul></p>
          <p>Optionally, the BTM (British Tennis Membership) number can be provided.</p>
          <p>The page also lists all existing players registered.</p>
          <h3 class="header"><?php _e( 'Seasons', 'racketmanager' ) ?></h3>
          <p>The seasons page of Racketmanager lists all current seasons and allows new seasons to be defined.</p>
          <p>To add a new season, all the is required is the season name.</p>
          <h4 id="Existing Season"><?php _e( 'Existing Seasons', 'racketmanager' ) ?></h4>
          <p>For each season, it is possible to add this season to multiple competitions in one easy way. Clicking on the "Add Competitions" button dispays a list of competitions (grouped into "Cups", "Leagues" and "Tournaments"). Firstly, the number of matchdays must be entered at the top of the screen. Clicking on the "checkbox" next to the competition name and pressing "Apply" will add the selected season to each competition. If the checkbox is not present, then the selected season is already added to the competition.</p>
        </div>
      </div>
      <div class="tab-pane fade" id="settings" role="tabpanel" aria-labelledby="settings-tab">
        <h2><?php _e( 'Settings', 'racketmanager' ) ?></h2>
        <div class="container">
          <a href="#top" class="alignright top-link"><?php _e( 'Top', 'racketmanager' ) ?></a>
          <p>The settings page holds the settings for the following:</p>
          <dl>
            <dt>Rosters</dt>
            <dd>Setting for whether player requests made by match secretaries are automatically approved or require manual actioning. The email address which is notified when a player request is made is also held here.</dd>
            <dt>Player Checks</dt>
            <dd>Settings for checks against a player when a league result is entered.</dd>
            <dt>Match Results</dt>
            <dd>Settings for each competition type on whether results can be entered via the website.</dd>
            <dt>Color Scheme</dt>
            <dd>Settings for highlighting league positions.</dd>
          </dl>
          <p>Clicking on "Save preferences" saves these settings to the database.</p>
        </div>
      </div>
      <div class="tab-pane fade" id="import" role="tabpanel" aria-labelledby="import-tab">
        <h2><?php _e( 'Import', 'racketmanager' ) ?></h2>
        <div class="container">
          <a href="#top" class="alignright top-link"><?php _e( 'Top', 'racketmanager' ) ?></a>
          <p>The import page allows data to be imported to the website from uploaded files. The types of data allowed are:</p>
          <dl>
            <dt>Table</dt>
            <dd>Teams can be uploaded to a league for a specific season. The competition is chosen and then the league.</dd>
            <dt>Fixtures</dt>
            <dd>Fixtures can be uploaded to a league for a specific season. The competition is chosen and then the league.</dd>
            <dt>Rosters</dt>
            <dd>Players for a club can be uploaded from a file. Any player not yet registered will be added.</dd>
            <dt>Players</dt>
            <dd>Players can be uploaded from a file.</dd>
          </dl>
          <p>Clicking on "Save preferences" saves these settings to the database.</p>
        </div>
      </div>
    </div>
  </div>

</div>

<?php } ?>
