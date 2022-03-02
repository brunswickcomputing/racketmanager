<?php

?>
<div class="container">

	<h1><?php _e( 'Racketmanager Competitions', 'racketmanager' ) ?></h1>

	<div id="competitions-table" class="league-block-container">
		<?php include('main/competitions.php'); ?>
	</div>
	<?php include(RACKETMANAGER_PATH . '/admin/includes/match-modal.php'); ?>
</div>
