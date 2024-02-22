<?php
/**
 * Competition settings standings administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

$forwin           = 0;
$fordraw          = 0;
$forloss          = 0;
$forwin_overtime  = 0;
$forloss_overtime = 0;
// Manual point rule.
if ( is_array( $competition->point_rule ) ) {
	$forwin                  = $competition->point_rule['forwin'];
	$forwin_overtime         = $competition->point_rule['forwin_overtime'];
	$fordraw                 = $competition->point_rule['fordraw'];
	$forloss                 = $competition->point_rule['forloss'];
	$forloss_overtime        = $competition->point_rule['forloss_overtime'];
	$competition->point_rule = 'user';
}
?>
<div class="container mt-3">

	<form action="" method="post" class="form-control">
		<?php wp_nonce_field( 'racketmanager_manage-competition-options', 'racketmanager_nonce' ); ?>

		<input type="hidden" class="active-tab" name="active-tab" value="<?php echo esc_html( $tab ); ?>" ?>

		<div class="container">
			<!-- Nav tabs -->
			<ul class="nav nav-tabs" id="myTab" role="tablist">
				<li class="nav-item" role="presentation">
					<button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="leaggeneralues" aria-selected="true"><?php esc_html_e( 'General', 'racketmanager' ); ?></button>
				</li>
				<li class="nav-item" role="presentation">
					<button class="nav-link" id="standings-tab" data-bs-toggle="tab" data-bs-target="#standings" type="button" role="tab" aria-controls="standings" aria-selected="false"><?php esc_html_e( 'Standings Table', 'racketmanager' ); ?></button>
				</li>
				<?php
				if ( 'league' === $competition->type ) {
					?>
					<li class="nav-item" role="presentation">
						<button class="nav-link" id="availability-tab" data-bs-toggle="tab" data-bs-target="#availability" type="button" role="tab" aria-controls="availability" aria-selected="false"><?php esc_html_e( 'Availability', 'racketmanager' ); ?></button>
					</li>
					<?php
				}
				?>

			</ul>
			<!-- Tab panes -->
			<div class="tab-content">
				<div class="tab-pane active show fade" id="general" role="tabpanel" aria-labelledby="general-tab">
					<h2><?php esc_html_e( 'General', 'racketmanager' ); ?></h2>
					<?php require 'include/settings-general.php'; ?>
				</div>
				<div class="tab-pane fade" id="standings" role="tabpanel" aria-labelledby="standings-tab">
					<h2><?php esc_html_e( 'Standings Table', 'racketmanager' ); ?></h2>
					<?php require 'include/settings-standings.php'; ?>
				</div>
				<div class="tab-pane fade" id="availability" role="tabpanel" aria-labelledby="availability-tab">
					<h2><?php esc_html_e( 'Availability', 'racketmanager' ); ?></h2>
					<?php require 'include/settings-availability.php'; ?>
				</div>
			</div>
		</div>

		<input type="hidden" name="competition_id" value="<?php echo esc_html( $competition->id ); ?>" />
		<input type="submit" name="updateSettings" value="<?php esc_html_e( 'Save Settings', 'racketmanager' ); ?>" class="btn btn-primary" />
	</form>
</div>
