<?php
$forwin = $fordraw = $forloss = $forwin_overtime = $forloss_overtime = 0;
// Manual point rule
if ( is_array($competition->point_rule) ) {
	$forwin = $competition->point_rule['forwin'];
	$forwin_overtime = $competition->point_rule['forwin_overtime'];
	$fordraw = $competition->point_rule['fordraw'];
	$forloss = $competition->point_rule['forloss'];
	$forloss_overtime = $competition->point_rule['forloss_overtime'];
	$competition->point_rule = 'user';
}
?>
<div class="container mt-3">

	<form action="" method="post" class="form-control">
		<?php wp_nonce_field( 'racketmanager_manage-competition-options' ) ?>

		<input type="hidden" class="active-tab" name="active-tab" value="<?php echo $tab ?>" ?>

		<div class="container">
			<!-- Nav tabs -->
			<ul class="nav nav-tabs" id="myTab" role="tablist">
				<li class="nav-item" role="presentation">
					<button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="leaggeneralues" aria-selected="true"><?php _e( 'General', 'racketmanager' ) ?></button>
				</li>
				<li class="nav-item" role="presentation">
					<button class="nav-link" id="standings-tab" data-bs-toggle="tab" data-bs-target="#standings" type="button" role="tab" aria-controls="standings" aria-selected="false"><?php _e( 'Standings Table', 'racketmanager' ) ?></button>
				</li>
				<?php if ( current_user_can( 'manage_racketmanager' ) ) { ?>
					<li class="nav-item" role="presentation">
						<button class="nav-link" id="advanced-tab" data-bs-toggle="tab" data-bs-target="#advanced" type="button" role="tab" aria-controls="advanced" aria-selected="false"><?php _e( 'Advanced', 'racketmanager' ) ?></button>
					</li>
				<?php } ?>
				<?php if ( $competition->competitiontype == 'league' ) { ?>
					<li class="nav-item" role="presentation">
						<button class="nav-link" id="availability-tab" data-bs-toggle="tab" data-bs-target="#availability" type="button" role="tab" aria-controls="availability" aria-selected="false"><?php _e( 'Availability', 'racketmanager' ) ?></button>
					</li>
				<?php } ?>

			</ul>
			<!-- Tab panes -->
			<div class="tab-content">
				<div class="tab-pane active show fade" id="general" role="tabpanel" aria-labelledby="general-tab">
					<h2><?php _e( 'General', 'racketmanager' ) ?></h2>
					<?php include('include/settings-general.php'); ?>
				</div>
				<div class="tab-pane fade" id="standings" role="tabpanel" aria-labelledby="standings-tab">
					<h2><?php _e( 'Standings Table', 'racketmanager' ) ?></h2>
					<?php include('include/settings-standings.php'); ?>
				</div>
				<div class="tab-pane fade" id="advanced" role="tabpanel" aria-labelledby="advanced-tab">
					<h2><?php _e( 'Advanced', 'racketmanager' ) ?></h2>
					<?php include('include/settings-advanced.php'); ?>
				</div>
				<div class="tab-pane fade" id="availability" role="tabpanel" aria-labelledby="availability-tab">
					<h2><?php _e( 'Availability', 'racketmanager' ) ?></h2>
					<?php include('include/settings-availability.php'); ?>
				</div>
			</div>
		</div>

		<input type="hidden" name="competition_id" value="<?php echo $competition->id ?>" />
		<input type="submit" name="updateSettings" value="<?php _e( 'Save Settings', 'racketmanager' ) ?>" class="btn btn-primary" />
	</form>
</div>
