<div class="container">
  <!--suppress HtmlUnknownAnchorTarget -->
    <a href="#top" class="alignright top-link"><?php _e( 'Top', 'racketmanager' ) ?></a>
  <p>The main page of Racketmanager shows an overview of the competitions in the database together with a few statistics on the number of seasons, leagues and format. At the beginning it is necessary to add at least one season for which the number of match days is also specified. Furthermore, the competition preferences need to be set. You can choose the sport type and point rules.</p>
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
  <p>This tab is where the matches are created. Each round show be created starting with the first and ending with the final. The first round matches will consist of "Team Rank x" names along with "Bye". The "Team Rank" refers to the teams in the "Preliminary Rounds" tab.</p>
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
  <p>This tab is where the teams are entered. For <em>existing</em> teams, the "Add Teams" button should be used to enable multiple teams to be selected and added to the league. For "Tournaments" only, the "Add Team" button should be used to create a <em>new</em> team. Once the teams have been added to the league, they can arrange manually using "drag and drop" and clicking "Save Ranking" or automatically by clicking "Random Ranking".</p>
  <p>Once the matches have been created and the administrator is happy with the ranking, the "Proceed to Final Rounds" button should be pressed. On the first round matches, this will replace "Team Ranking x" with the name of the team in that position in the ranking.</p>
  <p>Further changes to rankings after "Proceed to Final Rounds" has been pressed will have no effect on the matches. If changes are required the matches will need to be updated to have "Team Ranking x" before proceeding again.</p>
</div>
