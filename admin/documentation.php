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
        <?php include('documentation/competitions.php'); ?>
      </div>
      <div class="tab-pane fade" id="leagues" role="tabpanel" aria-labelledby="leagues-tab">
        <h2><?php _e( 'Leagues', 'racketmanager' ) ?></h2>
        <?php include('documentation/leagues.php'); ?>
      </div>
      <div class="tab-pane fade" id="cups" role="tabpanel" aria-labelledby="cups-tab">
        <h2><?php _e( 'Cups', 'racketmanager' ) ?></h2>
        <?php include('documentation/cups.php'); ?>
      </div>
      <div class="tab-pane fade" id="tournaments" role="tabpanel" aria-labelledby="tournaments-tab">
        <h2><?php _e( 'Tournaments', 'racketmanager' ) ?></h2>
        <?php include('documentation/tournaments.php'); ?>
      </div>
      <div class="tab-pane fade" id="clubs" role="tabpanel" aria-labelledby="clubs-tab">
        <h2><?php _e( 'Clubs', 'racketmanager' ) ?></h2>
        <?php include('documentation/clubs.php'); ?>
      </div>
      <div class="tab-pane fade" id="results" role="tabpanel" aria-labelledby="results-tab">
        <h2><?php _e( 'Results', 'racketmanager' ) ?></h2>
        <?php include('documentation/results.php'); ?>
      </div>
      <div class="tab-pane fade" id="admin" role="tabpanel" aria-labelledby="admin-tab">
        <h2><?php _e( 'Administration', 'racketmanager' ) ?></h2>
        <?php include('documentation/admin.php'); ?>
      </div>
      <div class="tab-pane fade" id="settings" role="tabpanel" aria-labelledby="settings-tab">
        <h2><?php _e( 'Settings', 'racketmanager' ) ?></h2>
        <?php include('documentation/settings.php'); ?>
      </div>
      <div class="tab-pane fade" id="import" role="tabpanel" aria-labelledby="import-tab">
        <h2><?php _e( 'Import', 'racketmanager' ) ?></h2>
        <?php include('documentation/import.php'); ?>
      </div>
    </div>
  </div>

</div>

<?php } ?>
