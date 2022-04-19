<?php
/**
* Competitions main page administration panel
*
*/
namespace ns;
?>
<div class="container mb-3">
  <h1><?php _e( ucfirst($type), 'racketmanager') ?> <?php _e( ucfirst($competitionType), 'racketmanager') ?> <?php _e( 'Competitions', 'racketmanager' ) ?><?php if ($season) { echo ' - '.$season; } ?></h1>
  <?php include('includes/competitions.php'); ?>
</div>
