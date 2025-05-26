<div class="container">
  <a href="#top" class="alignright top-link"><?php _e( 'Top', 'racketmanager' ) ?></a>
  <!-- Nav tabs -->
  <ul class="nav nav-pills" id="myTab" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active show" id="tournamentlist-tab" data-bs-toggle="pill" data-bs-target="#tournamentlist" type="button" role="tab" aria-controls="tournamentlist" aria-selected="true"><?php _e( 'Tournament List', 'racketmanager' ) ?></button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="tournamentadd-tab" data-bs-toggle="pill" data-bs-target="#tournamentadd" type="button" role="tab" aria-controls="tournamentadd" aria-selected="true"><?php _e( 'Add Tournament', 'racketmanager' ) ?></button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="tournamentcompetitions-tab" data-bs-toggle="pill" data-bs-target="#tournamentcompetitions" type="button" role="tab" aria-controls="tournamentcompetitions" aria-selected="false"><?php _e( 'Tournament Competitions', 'racketmanager' ) ?></button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="tournamentopen-tab" data-bs-toggle="pill" data-bs-target="#tournamentopen" type="button" role="tab" aria-controls="tournamentopen" aria-selected="false"><?php _e( 'Notify', 'racketmanager' ) ?></button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="orderofplay-tab" data-bs-toggle="pill" data-bs-target="#orderofplay" type="button" role="tab" aria-controls="orderofplay" aria-selected="false"><?php _e( 'Order of Play', 'racketmanager' ) ?></button>
    </li>
  </ul>
  <!-- Tab panes -->
  <div class="container tab-content">
    <div class="tab-pane active show fade" id="tournamentlist" role="tabpanel" aria-labelledby="tournamentlist-tab">
      <h3 class="header"><?php _e( 'Tournament List', 'racketmanager' ) ?></h3>
      <p>The tournaments page shows all the tournaments that have been created.</p>
      <p>Tournaments can also be deleted from this page.</p>
      <p>Clicking on the tournament name in the list of tournaments displays the edit screen.</p>
    </div>
    <div class="tab-pane fade" id="tournamentadd" role="tabpanel" aria-labelledby="tournamentadd-tab">
      <h3 class="header"><?php _e( 'Add Tournament', 'racketmanager' ) ?></h3>
      <div>Clicking the "Add Tournament" button takes the user to a screen where the new tournament details can be entered. The required fields are <ul><li>Name</li><li>Type (Summer or Winter)</li><li>Season</li><li>Closing date for entries</li><li>Tournament secretary</li></ul></div>
      <div>Typing in the tournament secretary name will automatically populate a list of user.</div<p>Clicking on the name will automatically populate the tournament secretary name along with any contact number and email address associated.</p>
      <div>Entering or amending these details will update the associated player record when the <button class="btn btn-primary">Update</button> button is pressed.</div>
      <div>Additionally, the following information may be entered<ul><li>Host Club</li><li>Finals Date</li><li>Start time</li><li>Number of courts</li></ul></div>
    </div>
    <div class="tab-pane fade" id="tournamentcompetitions" role="tabpanel" aria-labelledby="tournamentcompetitions-tab">
      <h3 class="header"><?php _e( 'Tournament Competitions', 'racketmanager' ) ?></h3>
      <p>Clicking on the "Competitions" button takes the user to a page showing the competitions for the tournament.</p>
      <p>Competitions are added to the tournament by pressing the <button class="btn btn-secondary">Add Competitions</button> button.</p>
    </div>
    <div class="tab-pane fade" id="tournamentopen" role="tabpanel" aria-labelledby="tournamentopen-tab">
      <h3 class="header"><?php _e( 'Notify', 'racketmanager' ) ?></h3>
      <p>This link is only shown for tournaments where the closing date is in the future.</p>
      <p>Clicking on the <button class="btn btn-primary">Notify open</button> button sends an email to match secretaries for all clubs with a link to the tournament entry page. A confirmation message is displayed once the emails have been sent.</p>
    </div>
    <div class="tab-pane fade" id="orderofplay" role="tabpanel" aria-labelledby="orderofplay-tab">
      <h3 class="header"><?php _e( 'Order of Play', 'racketmanager' ) ?></h3>
      <p>This link is only shown for competitions in progress.</p>
      <p>Clicking here allows order of play for finals day to be set.</p>
      <p>The number of courts, time gap and start time for the tournament can be updated. Each court can also have its own start time.</p>
      <p>Final matches are displayed showing players and clubs. If a semi-final has not yet completed, the semi-finalist names are shown. In the case, the match is highlighted to show it is incomplete.</p>
      <p>The matches can be dragged and dropped into free slots in the schedule area. Only one match can be dropped into a time slot for each court.</p>
      <p>Pressing <button class="btn btn-primary">Save schedule</button> saves the order of play.</p>
      <p>Pressing <button class="btn btn-secondary">Reset schedule</button> removes all matches from the schedule.</p>
    </div>
  </div>
</div>
