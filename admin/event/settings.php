<?php
/**
 * Event settings standings administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

?>
<div class="mt-3">

	<form action="" method="post" class="form-control">
		<?php wp_nonce_field( 'racketmanager_manage-event-options', 'racketmanager_nonce' ); ?>

		<input type="hidden" class="active-tab" name="active-tab" value="<?php echo esc_html( $tab ); ?>" ?>

		<div>
			<!-- Nav tabs -->
			<ul class="nav nav-tabs" id="myTab" role="tablist">
				<li class="nav-item" role="presentation">
					<button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true"><?php esc_html_e( 'General', 'racketmanager' ); ?></button>
				</li>
				<li class="nav-item" role="presentation">
					<button class="nav-link" id="standings-tab" data-bs-toggle="tab" data-bs-target="#standings" type="button" role="tab" aria-controls="standings" aria-selected="false"><?php esc_html_e( 'Standings', 'racketmanager' ); ?></button>
				</li>
				<li class="nav-item" role="presentation">
					<button class="nav-link" id="advanced-tab" data-bs-toggle="tab" data-bs-target="#advanced" type="button" role="tab" aria-controls="advanced" aria-selected="false"><?php esc_html_e( 'Advanced', 'racketmanager' ); ?></button>
				</li>
			</ul>
			<!-- Tab panes -->
			<div class="tab-content">
				<div class="tab-pane active show fade" id="general" role="tabpanel" aria-labelledby="general-tab">
					<h2><?php esc_html_e( 'General', 'racketmanager' ); ?></h2>
					<?php require 'include/settings-general.php'; ?>
				</div>
				<div class="tab-pane fade" id="standings" role="tabpanel" aria-labelledby="standings-tab">
					<h2><?php esc_html_e( 'Standings', 'racketmanager' ); ?></h2>
					<?php require 'include/settings-standings.php'; ?>
				</div>
				<div class="tab-pane fade" id="advanced" role="tabpanel" aria-labelledby="advanced-tab">
					<h2><?php esc_html_e( 'Advanced', 'racketmanager' ); ?></h2>
					<?php require 'include/settings-advanced.php'; ?>
				</div>
			</div>
		</div>

		<input type="hidden" name="event_id" value="<?php echo esc_html( $event->id ); ?>" />
		<input type="submit" name="updateSettings" value="<?php esc_html_e( 'Save Settings', 'racketmanager' ); ?>" class="btn btn-primary" />
	</form>
</div>
