<?php
/**
 * RacketManager Documentation
 *
 */

    if ( !current_user_can( 'racket_manager' ) ) {
    echo '<p style="text-align: center;">'.__("You do not have sufficient permissions to access this page.").'</p>';
    } else {
?>

<div class="wrap racketmanager_documentation">
    <h1 id="top"><?php _e( 'RacketManager Documentation', 'racketmanager' ) ?></h1>

    <div class="documentation-blocks jquery-ui-accordion" id="tabs">

        <ul id="tablist" style="display: none;">
            <li><a href="#competitions-table"><?php _e( 'Competitions', 'racketmanager' ) ?></a></li>
            <li><a href="#seasons-table"><?php _e( 'Seasons', 'racketmanager' ) ?></a></li>
            <li><a href="#roster-table"><?php _e( 'Rosters', 'racketmanager' ) ?></a></li>
            <li><a href="#player-table"><?php _e( 'Players', 'racketmanager' ) ?></a></li>
            <li><a href="#rosterrequest-table"><?php _e( 'Roster Request', 'racketmanager' ) ?></a></li>
            <li><a href="#teams-table"><?php _e( 'Teams', 'racketmanager' ) ?></a></li>
            <li><a href="#clubs-table"><?php _e( 'Clubs', 'racketmanager' ) ?></a></li>
            <li><a href="#results-table"><?php _e( 'Results', 'racketmanager' ) ?></a></li>
            <li><a href="#results-checker-table"><?php _e( 'Results Checker', 'racketmanager' ) ?></a></li>
            <li><a href="#tournaments-table"><?php _e( 'Tournaments', 'racketmanager' ) ?></a></li>
        </ul>

        <div id="competitions-table" class="league-block-container">
            <h2 class="header"><?php _e( 'Competitions', 'racketmanager' ) ?></h2>
            <div class="documentation-block content">
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
                <p>This tab shows the matches for each round along with scores.<p>
                <h4 id="Final"><?php _e( 'Final', 'racketmanager' ) ?></h4>
                <p>This tab is where the matches are created. Each round show be created starting with the first and ending with the final. The first round matches will consist of "Team Rank x" names along with "Bye". The "Team Rank" refers to the teams in the "Prelimary Rounds" tab.</p>
                <p>For subsequent rounds the teams will be "Winner of <em>round</em> x".</p>
                <h5>Match Results</h5>
                <p>If the competition has rubbers then the "View Rubbers" button should be pressed to enter the rubber players and scores. However, it is possible to just enter the match scores for these matches.<p>
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
        <div id="seasons-table" class="league-block-container">
            <h2 class="header"><?php _e( 'Seasons', 'racketmanager' ) ?></h2>
            <div class="documentation-block content">
                <a href="#top" class="alignright top-link"><?php _e( 'Top', 'racketmanager' ) ?></a>
                <p>The seasons page of Racketmanager lists all current seasons and allows new seasons to be defined.</p>
                <p>To add a new season, all the is required is the season name.</p>
                <h4 id="Existing Season"><?php _e( 'Existing Seasons', 'racketmanager' ) ?></h4>
                <p>For each season, it is possible to add this season to multiple competitions in one easy way. Clicking on the "Add Competitions" button dispays a list of competitions (grouped into "Cups", "Leagues" and "Tournaments"). Firstly, the number of matchdays must be entered at the top of the screen. Clicking on the "checkbox" next to the competition name and pressing "Apply" will add the selected season to each competition. If the checkbox is not present, then the selected season is already added to the competition.</p>
            </div>
        </div>
        <div id="roster-table" class="league-block-container">
            <h2 class="header"><?php _e( 'Rosters', 'racketmanager' ) ?></h2>
            <div class="documentation-block content">
                <a href="#top" class="alignright top-link"><?php _e( 'Top', 'racketmanager' ) ?></a>
                <p>The rosters page of Racketmanager allows the players registered for a particular club to be viewed. The club is selected from the dropdown list and the "View Roster" button is pressed.</p>
                <h4 id="Adding players"><?php _e( 'Adding players', 'racketmanager' ) ?></h4>
                <p>A player to be added to a club roster on this page. The player is selected from the dropdown list of existing players, the club for who the player to be added in selected and the "Add Roster" button is pressed.</p>
                <h4 id="Removing players"><?php _e( 'Removing players', 'racketmanager' ) ?></h4>
                <p>From the list of registered players, individuals can be removed from the club records by clicking the checkbox and then selecting "Delete" from the "Bulk Actions" dropdown. Pressing the "Apply" button will remove the selected player from the club roster. Any player who was previously on the club roster who has subsequently been removed is also listed but there is no checkbox available. Additionally, the date that the player was removed from the roster is shown, along with the user who actioned the removal request.<p>
                <p>It is possible to add and remove a player from a roster multiple times.</p>
            </div>
        </div>
        <div id="rosterrequest-table" class="league-block-container">
            <h2 class="header"><?php _e( 'Roster Request', 'racketmanager' ) ?></h2>
            <div class="documentation-block content">
                <a href="#top" class="alignright top-link"><?php _e( 'Top', 'racketmanager' ) ?></a>
                <p>The roster request page of Racketmanager shows requests to add players to clubs.</p>
                <p>These requests are generated from the club screen by match secretaries.</p>
                <p>The page lists the club, player, match secretary and date requested along with any completed date and user who completed the request.</p>
                <p>The <a href="<?php echo get_admin_url() ?>admin.php?page=racketmanager-settings">Roster Confirmation setting</a> on the "Rosters" tab can be set to either "none" or "automatic". If an email address is set on this screen, an email notification will be sent to this address whenever a roster request is made.</p>
                <h4 id="Automatic"><?php _e( 'Automatic Confirmation', 'racketmanager' ) ?></h4>
                <p>With this set, the player will be automatically added to the club roster (and added to the list of players if a new player).</p>
                <h3 id="Updates"><?php _e( 'Approval/Deletion', 'racketmanager' ) ?></h3>
                <p>Roster requests can be approved individually by selecting the checkbox next to the request.</p>
                <p>Additionally, bulk approvals can be performed by selecting the checkbox in the header.</p>
                <p>Roster requests can also be deleted. If these have been actioned then deleting the request has no impact on the club roster.<p>
            </div>
        </div>
        <div id="player-table" class="league-block-container">
            <h2 class="header"><?php _e( 'Players', 'racketmanager' ) ?></h2>
            <div class="documentation-block content">
                <a href="#top" class="alignright top-link"><?php _e( 'Top', 'racketmanager' ) ?></a>
                <p>The player page of Racketmanager allows a new player to be added to the system. Required fields are <ul><li>First name</li><li>Surname</li><li>Gender<li></ul>
                <p>Optionally, the BTM (British Tennis Membership) number can be provided.</p>
                <p>The page also lists all existing players registered.</p>
            </div>
        </div>
        <div id="teams-table" class="league-block-container">
            <h2 class="header"><?php _e( 'Teams', 'racketmanager' ) ?></h2>
            <div class="documentation-block content">
                <a href="#top" class="alignright top-link"><?php _e( 'Top', 'racketmanager' ) ?></a>
                <p>The teams page of Racketmanager lists all the teams that are linked to clubs. It is possible to view only the teams for an individual club using the filter at the top of the screen.</p>
                <h4 id="Deleting teams"><?php _e( 'Deleting teams', 'racketmanager' ) ?></h4>
                <p>Teams can also be deleted from this page. However, if the team is in a league, the team is prevented from deletion.</p>
                <h4 id="Adding teams"><?php _e( 'Adding teams', 'racketmanager' ) ?></h4>
                <p>New teams can also be created on this screen. Selecting the club and type of team (singles/doubles, ladies/mens/mixed) and pressing the "Add team" button will create the team.</p>
                <p>The name is automatically generated from the club short code, team type and the next sequence number based on existing teams for the club.</p>
            </div>
        </div>
        <div id="clubs-table" class="league-block-container">
            <h2 class="header"><?php _e( 'Clubs', 'racketmanager' ) ?></h2>
            <div class="documentation-block content">
                <a href="#top" class="alignright top-link"><?php _e( 'Top', 'racketmanager' ) ?></a>
                <p>The clubs page of Racketmanager lists all the registered clubs. The match secretary for each club is shown on the main screen.</p>
                <p>There are links to view the rosters and teams of each club.</p>
                <h4 id="Deleting clubs"><?php _e( 'Deleting clubs', 'racketmanager' ) ?></h4>
                <p>Clubs can also be deleted from this page. However, if the club still has teams attached, the club is prevented from deletion.</p>
                <h4 id="Adding clubs"><?php _e( 'Adding clubs', 'racketmanager' ) ?></h4>
                <p>Clicking the "Add Club" button takes the user to a screen where the new club details can be entered. The required fields are <ul><li>Name</li><li>Type (currently only affiliated is available)</li><li>Shortcode (used in team names)</li><li>Facilites</li><li>Address (which can be entered or selected from a map view)</li></p>
                <p>Additionally, the following information may be entered<ul><li>Contact Number</li><li>Website</li><li>Year Founded</li><ul>
                <h4 id="Editing club details"><?php _e( 'Editing club details', 'racketmanager' ) ?></h4>
                <p>Clicking on the club name in the list of clubs displays the edit screen.</p>
                <p>In addition to the fields available when adding a club, the match secretary can be entered or amended.</p>
                <p>Typing in the match secretary name will automatically populate a list of players registered with the club.</p<p>Clicking on the player name will automatically populate the match secretary name along with any contact number and email address associated with the player.</p>
                <p>Entering or amending these details will update the associated player record when the "Update" button is pressed.</p>
            </div>
        </div>
        <div id="results-table" class="league-block-container">
            <h2 class="header"><?php _e( 'Results', 'racketmanager' ) ?></h2>
            <div class="documentation-block content">
                <a href="#top" class="alignright top-link"><?php _e( 'Top', 'racketmanager' ) ?></a>
                <p>The results page of Racketmanager shows any results that have been entered by users that need administration approval.</p>
                <p>The ability for users to enter match results themselves is controlled by the <a href="<?php echo get_admin_url() ?>admin.php?page=racketmanager-settings">settings</a> on the "Match Results" tab.</p>
                <p>If an email address is set on this screen, an email notification will be sent to this address whenever a match result is entered by users.</p>
                <h3 id="level"><?php _e( 'Minimum level to update results', 'racketmanager' ) ?></h4>
                <p>There are three options that control who can enter results:</p>
                <h4 id="None"><?php _e( 'None', 'racketmanager' ) ?></h4>
                <p>Results are not able to be entered by users.</p>
                <h4 id="Captain"><?php _e( 'Captain', 'racketmanager' ) ?></h4>
                <p>Captains of the team involved in the match are able to enter the results.</p>
                <h4 id="Roster"><?php _e( 'Roster', 'racketmanager' ) ?></h4>
                <p>Any player registered with the club of the team involved in the match are able to enter the results.</p>
                <h3 id="entry"><?php _e( 'Result Entry', 'racketmanager' ) ?></h4>
                <p>There are two options that control who can enter results:</p>
                <h4 id="Home"><?php _e( 'Home', 'racketmanager' ) ?></h4>
                <p>Results must be entered by the home team with approval by the away team.</p>
                <h4 id="Either"><?php _e( 'Either', 'racketmanager' ) ?></h4>
                <p>Results can be entered by either the home or away team. Approval is required by the alternative team.</p>
                <h3 id="confirmation"><?php _e( 'Result Confirmation', 'racketmanager' ) ?></h4>
                <p>There are two options that control how match result confirmation is handled:</p>
                <h4 id="None"><?php _e( 'None', 'racketmanager' ) ?></h4>
                <p>Match results must be confirmed by the league administrator. Rubber results are available to view as these have already been entered.</p>
                <h4 id="Automatic"><?php _e( 'Automatic', 'racketmanager' ) ?></h4>
                <p>Match results are automatically updated from the result entry on the frontend.</p>
                <p>If this value is set, the only matches that are shown on this screen are where the opposing sides disagree with the result.</p>
            </div>
        </div>
        <div id="results-checker-table" class="league-block-container">
            <h2 class="header"><?php _e( 'Results Checker', 'racketmanager' ) ?></h2>
            <div class="documentation-block content">
                <a href="#top" class="alignright top-link"><?php _e( 'Top', 'racketmanager' ) ?></a>
                <p>The results checker page of Racketmanager shows any player checks that have been failed whenever a match result is input.</p>
                <p>For any of these checks to be applied, the relevant <a href="<?php echo get_admin_url() ?>admin.php?page=racketmanager-settings">setting</a> must be entered on the "Player Checks" tab.</p>
                <h4 id="Roster lead time"><?php _e( 'Roster lead time', 'racketmanager' ) ?></h4>
                <p>This checks how long a player must be registered before they are eligible to play.</p>
                <h4 id="End of season eligibility"><?php _e( 'End of season eligibility', 'racketmanager' ) ?></h4>
                <p>This checks how many rounds at the end of the season do not allow new players to be registered.</p>
                <h4 id="Locked players"><?php _e( 'Locked players', 'racketmanager' ) ?></h4>
                <p>This checks how many matches a player may play for a higher team before they are locked to that team.</p>
                <h3 id="Updates"><?php _e( 'Approval/Deletion', 'racketmanager' ) ?></h3>
                <p>Each check can be either marked as approved or can be deleted.</p>
                <p>If the match result is updated, any records for the match and player are regenerated.</p>
                <p>However, if a player is swapped for another player in the match result, the original player entry will remain in the list of result checks. It can then be deleted if required.</p>
            </div>
        </div>
        <div id="tournaments-table" class="league-block-container">
            <h2 class="header"><?php _e( 'Tournaments', 'racketmanager' ) ?></h2>
            <div class="documentation-block content">
                <a href="#top" class="alignright top-link"><?php _e( 'Top', 'racketmanager' ) ?></a>
                <p>The tournaments page of Racketmanager shows all the tournaments that have been created.</p>
                <h4 id="Deleting tournaments"><?php _e( 'Deleting tournaments', 'racketmanager' ) ?></h4>
                <p>Tournaments can also be deleted from this page.</p>
                <h4 id="Adding tournaments"><?php _e( 'Adding tournaments', 'racketmanager' ) ?></h4>
                <p>Clicking the "Add Tournament" button takes the user to a screen where the new tournament details can be entered. The required fields are <ul><li>Name</li><li>Type (Summer or Winter)</li><li>Season</li><li>Closing date for entries</li><li>Tournament secretary</li></p>
                <p>Typing in the tournament secretary name will automatically populate a list of user.</p<p>Clicking on the name will automatically populate the tournament secretary name along with any contact number and email address associated.</p>
                <p>Entering or amending these details will update the associated player record when the "Update" button is pressed.</p>
                <p>Additionally, the following information may be entered<ul><li>Host Club</li><li>Website</li><li>Finals Date</li><ul>
                <h4 id="Editing tournament details"><?php _e( 'Editing tournament details', 'racketmanager' ) ?></h4>
                <p>Clicking on the tournament name in the list of tournaments displays the edit screen.</p>
            </div>
        </div>

    </div>
</div>

<?php } ?>
