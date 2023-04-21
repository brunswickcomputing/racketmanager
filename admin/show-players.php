<?php
?>
<script type='text/javascript'>
jQuery(document).ready(function(){
  activaTab('<?php echo $tab ?>');
});
</script>
<div class="container">

  <h1><?php _e( 'Racketmanager Players', 'racketmanager' ) ?></h1>

  <div class="container">
    <!-- Nav tabs -->
    <ul class="nav nav-tabs" id="myTab" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="playerrequest-tab" data-bs-toggle="tab" data-bs-target="#playerrequest" type="button" role="tab" aria-controls="playerrequest" aria-selected="false"><?php _e( 'Player Request', 'racketmanager' ) ?></button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="players-tab" data-bs-toggle="tab" data-bs-target="#players" type="button" role="tab" aria-controls="players" aria-selected="false"><?php _e( 'Players', 'racketmanager' ) ?></button>
      </li>
    </ul>

    <!-- Tab panes -->
    <div class="tab-content">
      <div class="tab-pane fade" id="playerrequest" role="tabpanel" aria-labelledby="playerrequest-tab">
        <h2 class="header"><?php _e( 'Player Request', 'racketmanager' ) ?></h2>
        <?php include('players/club-player-requests.php'); ?>
      </div>
      <div class="tab-pane fade" id="players" role="tabpanel" aria-labelledby="players-tab">
        <h2 class="header"><?php _e( 'Players', 'racketmanager' ) ?></h2>
        <?php include('players/players.php'); ?>
      </div>
    </div>
  </div>
</div>
