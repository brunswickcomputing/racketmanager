<div class="container">
    <!-- Nav tabs -->
    <ul class="nav nav-pills" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active show" id="clubslist-tab" data-bs-toggle="pill" data-bs-target="#clubslist"
                    type="button" role="tab" aria-controls="clubslist"
                    aria-selected="true"><?php _e( 'Clubs List', 'racketmanager' ) ?></button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="clubadd-tab" data-bs-toggle="pill" data-bs-target="#clubadd" type="button"
                    role="tab" aria-controls="clubadd"
                    aria-selected="true"><?php _e( 'Add Club', 'racketmanager' ) ?></button>
        </li>

        <li class="nav-item" role="presentation">
            <button class="nav-link" id="clubplayers-tab" data-bs-toggle="pill" data-bs-target="#clubplayers"
                    type="button" role="tab" aria-controls="clubplayers"
                    aria-selected="false"><?php _e( 'Players', 'racketmanager' ) ?></button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="clubteams-tab" data-bs-toggle="pill" data-bs-target="#clubteams" type="button"
                    role="tab" aria-controls="clubteams"
                    aria-selected="false"><?php _e( 'Teams', 'racketmanager' ) ?></button>
        </li>
    </ul>
    <!-- Tab panes -->
    <div class="container tab-content">
        <div class="tab-pane active show fade" id="clubslist" role="tabpanel" aria-labelledby="clubslist-tab">
            <h3 class="header"><?php _e( 'Clubs List', 'racketmanager' ) ?></h3>
            <p>The clubs page of Racketmanager lists all the registered clubs. The match secretary for each club is shown on the main screen.</p>
            <p>There are links to view the registered players and teams of each club.</p>
            <p>Clicking on the tournament name in the list of tournaments displays the edit screen.</p>
            <h4 id="Deleting clubs"><?php _e( 'Deleting clubs', 'racketmanager' ) ?></h4>
            <p>Clubs can also be deleted from this page. However, if the club still has teams attached, the club is prevented from deletion.</p>
            <h4 id="Editing club details"><?php _e( 'Editing club details', 'racketmanager' ) ?></h4>
            <p>Clicking on the club name in the list of clubs displays the edit screen.</p>
            <p>In addition to the fields available when adding a club, the match secretary can be entered or amended.</p>
            <p>Typing in the match secretary name will automatically populate a list of players registered with the club.</p
            <p>Clicking on the player name will automatically populate the match secretary name along with any contact number and email address associated with the player.</p>
            <p>Entering or amending these details will update the associated player record when the "Update" button is pressed.</p>
        </div>
        <div class="tab-pane fade" id="clubadd" role="tabpanel" aria-labelledby="clubadd-tab">
            <h3 class="header"><?php _e( 'Add Club', 'racketmanager' ) ?></h3>
            <p>Clicking the "Add Club" button takes the user to a screen where the new club details can be entered. The required fields are:</p>
            <ul>
                <li>Name</li>
                <li>Type (currently only affiliated is available)</li>
                <li>Shortcode (used in team names)</li>
                <li>Facilities</li>
                <li>Address (which can be entered or selected from a map view)</li>
            </ul>
            <p>Additionally, the following information may be entered:</p>
            <ul>
                <li>Contact Number</li>
                <li>Website</li>
                <li>Year Founded</li>
            </ul>
        </div>
        <div class="tab-pane fade" id="clubplayers" role="tabpanel" aria-labelledby="clubplayers-tab">
            <h3 class="header"><?php _e( 'Players', 'racketmanager' ) ?></h3>
            <p>The players page within a club allows the players registered for that club to be viewed.</p>
            <h4 id="Adding players"><?php _e( 'Adding players', 'racketmanager' ) ?></h4>
            <p>A player to be added to the registered players for the club on this page. Player details are entered
                (first name, surname, gender and, optionally, LTA Tennis Number). Pressing the "Add Player" button
                causes the player to be added to the registered players for the club. The central player record is
                created if it does not already exist.</p>
            <h4 id="Removing players"><?php _e( 'Removing players', 'racketmanager' ) ?></h4>
            <p>From the list of registered players, individuals can be removed from the club records by clicking the
                checkbox and then selecting "Delete" from the "Bulk Actions" dropdown. Pressing the "Apply" button will
                remove the selected player from the list of registered players for the club. Any player who was
                previously on registered for the club who has subsequently been removed is also listed but there is no
                checkbox available. Additionally, the date that the player was removed from the list of registered
                players is shown, along with the user who actioned the removal request.</p>
            <p>It is possible to add and remove a player from the list of registered players multiple times.</p>
        </div>
        <div class="tab-pane fade" id="clubteams" role="tabpanel" aria-labelledby="clubteams-tab">
            <h3 class="header"><?php _e( 'Teams', 'racketmanager' ) ?></h3>
            <p>The teams page within a club lists all the teams that are linked to that club.</p>
            <h4 id="Deleting teams"><?php _e( 'Deleting teams', 'racketmanager' ) ?></h4>
            <p>Teams can also be deleted from this page. However, if the team is in a league, the team is prevented from
                deletion.</p>
            <h4 id="Adding teams"><?php _e( 'Adding teams', 'racketmanager' ) ?></h4>
            <p>New teams can also be created on this screen. Selecting the type of team (singles/doubles,
                ladies/mens/mixed) and pressing the "Add team" button will create the team.</p>
            <p>The name is automatically generated from the club short code, team type and the next sequence number
                based on existing teams for the club.</p>
        </div>
    </div>
</div>
