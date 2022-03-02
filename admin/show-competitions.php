<?php
/**
* Competitions main page administration panel
*
*/
namespace ns;
?>
<div class="container">
  <p class="racketmanager_breadcrumb">
    <a href="admin.php?page=racketmanager"><?php _e( 'RacketManager', 'racketmanager' ) ?></a>
  </p>
  <h1><?php _e( ucfirst($type), 'racketmanager') ?> <?php _e( ucfirst($competitionType), 'racketmanager') ?> <?php _e( 'Competitions', 'racketmanager' ) ?><?php if ($season) { echo ' - '.$season; } ?></h1>
  <?php include('main/show-competition.php'); ?>
</div>
