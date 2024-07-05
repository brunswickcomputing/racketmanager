<?php
/**
 * Competition administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

?>
<script type='text/javascript'>
jQuery(document).ready(function(){
	activaTab('<?php echo esc_html( $tab ); ?>');
});
</script>
<div class="container">
	<div class="row justify-content-end">
		<div class="col-auto racketmanager_breadcrumb">
			<?php
			if ( empty( $tournament ) ) {
				?>
				<a href="admin.php?page=racketmanager-<?php echo esc_html( $competition->type ); ?>s"><?php echo esc_html( ucfirst( $competition->type ) ); ?>s</a> &raquo; <?php echo esc_html( $competition->name ); ?>
				<?php
			} else {
				?>
				<a href="admin.php?page=racketmanager-tournaments"><?php esc_html_e( 'Tournaments', 'racketmanager' ); ?></a> &raquo; <?php echo esc_html( $tournament->name ); ?>
				<?php
			}
			?>
		</div>
	</div>
	<div class="row justify-content-between">
		<div class="col-auto">
			<?php
			if ( empty( $tournament ) ) {
				?>
				<h1><?php echo esc_html( $competition->name ); ?></h1>
				<?php
			} else {
				?>
				<h1><?php echo esc_html( $page_title ); ?></h1>
				<?php
			}
			?>
		</div>
	<?php
	if ( ! empty( $competition->seasons ) && empty( $tournament ) ) {
		?>
		<!-- Season Dropdown -->
		<div class="col-auto mb-3">
			<form action="admin.php" method="get" class="form-control">
				<input type="hidden" name="page" value="racketmanager" />
				<input type="hidden" name="subpage" value="show-competition" />
				<input type="hidden" name="competition_id" value="<?php echo esc_html( $competition->id ); ?>" />
				<label for="season" style="vertical-align: middle;"><?php esc_html_e( 'Season', 'racketmanager' ); ?></label>
				<select size="1" name="season" id="season">
					<?php foreach ( $competition->seasons as $competition_season ) { ?>
						<option value="<?php echo esc_html( htmlspecialchars( $competition_season['name'] ) ); ?>" <?php selected( $competition_season['name'], $season ); ?>>
							<?php echo esc_html( $competition_season['name'] ); ?>
						</option>
					<?php } ?>
				</select>
				<button type="submit"  class="btn btn-secondary">
					<?php esc_html_e( 'Show', 'racketmanager' ); ?>
				</button>
			</form>
		</div>
		<?php
	}
	?>
</div>

	<?php $this->printMessage(); ?>
	<div class="">
		<nav class="navbar navbar-expand-lg bg-body-tertiary">
			<div class="">
				<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
					<span class="navbar-toggler-icon"></span>
				</button>
				<div class="collapse navbar-collapse mt-3" id="navbarSupportedContent">
					<!-- Nav tabs -->
					<ul class="nav nav-pills" id="myTab" role="tablist">
						<li class="nav-item" role="presentation">
							<button class="nav-link" id="events-tab" data-bs-toggle="tab" data-bs-target="#events" type="button" role="tab" aria-controls="events" aria-selected="true"><?php esc_html_e( 'Events', 'racketmanager' ); ?></button>
						</li>
						<?php
						if ( empty( $tournament ) ) {
							?>
							<li class="nav-item" role="presentation">
								<button class="nav-link" id="seasons-tab" data-bs-toggle="tab" data-bs-target="#seasons" type="button" role="tab" aria-controls="seasons" aria-selected="false"><?php esc_html_e( 'Seasons', 'racketmanager' ); ?></button>
							</li>
							<?php
							if ( 'league' === $competition->type ) {
								?>
								<li class="nav-item" role="presentation">
									<button class="nav-link" id="schedule-tab" data-bs-toggle="tab" data-bs-target="#schedule" type="button" role="tab" aria-controls="schedule" aria-selected="false"><?php esc_html_e( 'Schedule', 'racketmanager' ); ?></button>
								</li>
								<?php
							}
							?>
							<?php
							if ( current_user_can( 'manage_racketmanager' ) ) {
								?>
								<li class="nav-item" role="presentation">
									<button class="nav-link" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings" type="button" role="tab" aria-controls="settings" aria-selected="false"><?php esc_html_e( 'Settings', 'racketmanager' ); ?></button>
								</li>
								<?php
							}
							?>
							<?php
						}
						?>
						<li class="nav-item" role="presentation">
							<a class="nav-link" id="contact-tab" type="button" href="admin.php?page=racketmanager&subpage=contact&competition_id=<?php echo esc_attr( $competition->id ); ?>&season=<?php echo esc_attr( $season ); ?>"><?php esc_html_e( 'Contact', 'racketmanager' ); ?></a>
						</li>
					</ul>
				</div>
			</div>
		</nav>
		<!-- Tab panes -->
		<div class="tab-content">
			<div class="tab-pane" id="events" role="tabpanel" aria-labelledby="events-tab">
				<h2><?php esc_html_e( 'Events', 'racketmanager' ); ?></h2>
				<?php require_once 'competition/events.php'; ?>
			</div>
			<?php
			if ( empty( $tournament ) ) {
				?>
				<div class="tab-pane fade" id="seasons" role="tabpanel" aria-labelledby="seasons-tab">
					<h2><?php esc_html_e( 'Seasons', 'racketmanager' ); ?></h2>
					<?php require_once 'competition/seasons.php'; ?>
				</div>
				<?php
				if ( 'league' === $competition->type ) {
					?>
					<div class="tab-pane fade" id="schedule" role="tabpanel" aria-labelledby="schedule-tab">
						<h2><?php esc_html_e( 'Schedule', 'racketmanager' ); ?></h2>
						<?php require_once 'competition/schedule.php'; ?>
					</div>
					<?php
				}
				?>
				<?php
				if ( current_user_can( 'manage_racketmanager' ) ) {
					?>
					<div class="tab-pane fade" id="settings" role="tabpanel" aria-labelledby="settings-tab">
						<?php include_once 'competition/settings.php'; ?>
					</div>
					<?php
				}
				?>
				<?php
			}
			?>
		</div>
	</div>
