<?php
/**
 * Players main page administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

/** @var string $tab */
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
				<button class="nav-link" id="errors-tab" data-bs-toggle="tab" data-bs-target="#errors" type="button" role="tab" aria-controls="errors" aria-selected="false"><?php _e( 'Player Errors', 'racketmanager' ) ?></button>
			</li>
			<li class="nav-item" role="presentation">
				<button class="nav-link" id="request-tab" data-bs-toggle="tab" data-bs-target="#request" type="button" role="tab" aria-controls="request" aria-selected="false"><?php _e( 'Player Requests', 'racketmanager' ) ?></button>
			</li>
			<li class="nav-item" role="presentation">
				<button class="nav-link" id="players-tab" data-bs-toggle="tab" data-bs-target="#players" type="button" role="tab" aria-controls="players" aria-selected="false"><?php _e( 'Players', 'racketmanager' ) ?></button>
			</li>
		</ul>

		<!-- Tab panes -->
		<div class="tab-content">
			<div class="tab-pane fade" id="errors" role="tabpanel" aria-labelledby="errors-tab">
				<h2 class="header"><?php _e( 'Player Errors', 'racketmanager' ) ?></h2>
				<?php include('players/errors.php'); ?>
			</div>
			<div class="tab-pane fade" id="request" role="tabpanel" aria-labelledby="request-tab">
				<h2 class="header"><?php _e( 'Player Requests', 'racketmanager' ) ?></h2>
				<?php include('players/requests.php'); ?>
			</div>
			<div class="tab-pane fade" id="players" role="tabpanel" aria-labelledby="players-tab">
				<h2 class="header"><?php _e( 'Players', 'racketmanager' ) ?></h2>
				<?php include('players/players.php'); ?>
			</div>
		</div>
	</div>
</div>
