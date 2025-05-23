<?php
/**
 * Competition seasons administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

/** @var object $competition */
/** @var string $tab */
if ( empty( $tournament ) ) {
	$breadcrumb_link = '<a href="/wp-admin/admin.php?page=racketmanager-' . $competition->type . 's&amp;view=seasons&amp;competition_id=' . $competition->id . '">' . $competition->name . '</a>';
	$add_link        = '';
} else {
	$breadcrumb_link = '<a href="/wp-admin/admin.php?page=racketmanager-' . $competition->type . 's&amp;view=tournament&amp;tournament=' . $tournament->id . '">' . $tournament->name . '</a>';
	$add_link        = '&amp;tournament=' . $tournament->id;
}
?>
<div>
	<div class="alert_rm" id="alert-season" style="display:none;">
		<div class="alert__body">
			<div class="alert__body-inner" id="alert-season-response">
			</div>
		</div>
	</div>
<div class="container">
	<div class="row justify-content-end">
		<div class="col-auto racketmanager_breadcrumb">
			<a href="/wp-admin/admin.php?page=racketmanager-<?php echo esc_attr( $competition->type ); ?>s"><?php echo esc_html( ucfirst( $competition->type ) ); ?>s</a> &raquo; <?php echo ( $breadcrumb_link ); ?> &raquo; <?php esc_html_e( 'Configuration', 'racketmanager' ); ?>
		</div>
	</div>
	<div class="row justify-content-between">
		<div class="col-auto">
			<h1><?php echo esc_html( $competition->name ); ?></h1>
		</div>
		<div class="">
			<form action="" method="post" class="">
				<?php wp_nonce_field( 'racketmanager_manage-competition-config', 'racketmanager_nonce' ); ?>
				<input type="hidden" class="active-tab" name="active-tab" value="<?php echo esc_attr( $tab ); ?>" />
				<input type="hidden" class="mode" name="mode" value="<?php echo esc_attr( $competition->config->mode ); ?>" />
				<div class="mb-3">
					<nav class="navbar navbar-expand-lg bg-body-tertiary">
						<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar<?php echo esc_attr( $competition->id ); ?>" aria-controls="navbar<?php echo esc_attr( $competition->type ); ?>" aria-expanded="false" aria-label="Toggle navigation">
							<span class="navbar-toggler-icon"></span>
						</button>
						<div class="collapse navbar-collapse" id="navbar<?php echo esc_attr( $competition->id ); ?>">
							<ul class="nav nav-pills">
								<li class="nav-item">
									<button class="nav-link" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true"><?php esc_html_e( 'General', 'racketmanager' ); ?></button>
								</li>
								<li class="nav-item">
									<button class="nav-link" id="constitution-tab" data-bs-toggle="tab" data-bs-target="#constitution" type="button" role="tab" aria-controls="constitution" aria-selected="true"><?php esc_html_e( 'Constitution', 'racketmanager' ); ?></button>
								</li>
								<li class="nav-item">
									<button class="nav-link" id="matches-tab" data-bs-toggle="tab" data-bs-target="#matches" type="button" role="tab" aria-controls="matches" aria-selected="true"><?php esc_html_e( 'Matches', 'racketmanager' ); ?></button>
								</li>
								<li class="nav-item">
									<button class="nav-link" id="fixtures-tab" data-bs-toggle="tab" data-bs-target="#fixtures" type="button" role="tab" aria-controls="fixtures" aria-selected="true"><?php esc_html_e( 'Fixtures', 'racketmanager' ); ?></button>
								</li>
								<li class="nav-item">
									<button class="nav-link" id="display-tab" data-bs-toggle="tab" data-bs-target="#display" type="button" role="tab" aria-controls="display" aria-selected="true"><?php esc_html_e( 'Display', 'racketmanager' ); ?></button>
								</li>
								<li class="nav-item">
									<button class="nav-link" id="events-tab" data-bs-toggle="tab" data-bs-target="#events" type="button" role="tab" aria-controls="events" aria-selected="true"><?php esc_html_e( 'Events', 'racketmanager' ); ?></button>
								</li>
								<li class="nav-item" role="presentation">
									<button class="nav-link" id="rules-tab" data-bs-toggle="tab" data-bs-target="#rules" type="button" role="tab" aria-controls="rules" aria-selected="false"><?php esc_html_e( 'Rules', 'racketmanager' ); ?></button>
								</li>
								<?php
								if ( 'league' === $competition->type ) {
									?>
									<li class="nav-item">
										<button class="nav-link" id="availability-tab" data-bs-toggle="tab" data-bs-target="#availability" type="button" role="tab" aria-controls="availability" aria-selected="true"><?php esc_html_e( 'Availability', 'racketmanager' ); ?></button>
									</li>
									<?php
								}
								?>
							</ul>
						</div>
					</nav>
				</div>
				<div class="mb-3">
					<input type="hidden" name="competition_id" value="<?php echo esc_html( $competition->id ); ?>" />
					<button name="updateCompetitionConfig" class="btn btn-primary"><?php esc_html_e( 'Save Settings', 'racketmanager' ); ?></button>
				</div>
				<div class="tab-content">
					<div class="tab-pane fade" id="general" role="tabpanel" aria-labelledby="general-tab">
						<h2><?php esc_html_e( 'General', 'racketmanager' ); ?></h2>
						<?php require RACKETMANAGER_PATH . 'admin/competition/include/settings/general.php'; ?>
					</div>
					<div class="tab-pane fade" id="constitution" role="tabpanel" aria-labelledby="constitution-tab">
						<h2><?php esc_html_e( 'Constitution', 'racketmanager' ); ?></h2>
						<?php require RACKETMANAGER_PATH . 'admin/competition/include/settings/constitution.php'; ?>
					</div>
					<div class="tab-pane fade" id="matches" role="tabpanel" aria-labelledby="matches-tab">
						<h2><?php esc_html_e( 'Matches', 'racketmanager' ); ?></h2>
						<?php require RACKETMANAGER_PATH . 'admin/competition/include/settings/matches.php'; ?>
					</div>
					<div class="tab-pane fade" id="fixtures" role="tabpanel" aria-labelledby="fixtures-tab">
						<h2><?php esc_html_e( 'Fixtures', 'racketmanager' ); ?></h2>
						<?php require RACKETMANAGER_PATH . 'admin/competition/include/settings/fixtures.php'; ?>
					</div>
					<div class="tab-pane fade" id="display" role="tabpanel" aria-labelledby="display-tab">
						<h2><?php esc_html_e( 'Display', 'racketmanager' ); ?></h2>
						<?php require RACKETMANAGER_PATH . 'admin/competition/include/settings/display.php'; ?>
					</div>
					<div class="tab-pane fade" id="events" role="tabpanel" aria-labelledby="events-tab">
						<h2><?php esc_html_e( 'Events', 'racketmanager' ); ?></h2>
						<?php require RACKETMANAGER_PATH . 'admin/competition/include/settings/events.php'; ?>
					</div>
					<div class="tab-pane fade" id="rules" role="tabpanel" aria-labelledby="rules-tab">
						<h2><?php esc_html_e( 'Rules', 'racketmanager' ); ?></h2>
						<?php require RACKETMANAGER_PATH . 'admin/competition/include/settings/rules.php'; ?>
					</div>
					<?php
					if ( 'league' === $competition->type ) {
						?>
						<div class="tab-pane fade" id="availability" role="tabpanel" aria-labelledby="availability-tab">
							<h2><?php esc_html_e( 'Availability', 'racketmanager' ); ?></h2>
							<?php require RACKETMANAGER_PATH . 'admin/competition/include/settings/availability.php'; ?>
						</div>
						<?php
					}
					?>
				</div>
			</form>
		</div>
		<?php
		if ( ! empty( $error_tab ) ) {
			$tab = $error_tab; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
		?>
		<script type='text/javascript'>
		jQuery(document).ready(function(){
			activaTab('<?php echo esc_html( $tab ); ?>');
		});
		</script>
	</div>
</div>
