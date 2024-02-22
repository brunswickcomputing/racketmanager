<?php
/**
 * Event administration panel
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
				<a href="admin.php?page=racketmanager&subpage=show-competition&competition_id=<?php echo esc_attr( $event->competition->id ); ?>"><?php echo esc_html( $event->competition->name ); ?></a> &raquo; <?php echo esc_html( $event->name ); ?>
				<?php
			} else {
				?>
				<a href="admin.php?page=racketmanager-tournaments"><?php esc_html_e( 'Tournaments', 'racketmanager' ); ?></a> &raquo; <a href="admin.php?page=racketmanager&subpage=show-competition&competition_id=<?php echo esc_attr( $event->competition->id ); ?>&season=<?php echo esc_attr( $season ); ?>&tournament=<?php echo esc_attr( $tournament->id ); ?>"><?php echo esc_html( $tournament->name ); ?></a> &raquo; <?php echo esc_html( $event->name ); ?>
				<?php
			}
			?>
		</div>
	</div>
	<div class="row justify-content-between">
		<div class="col-auto">
			<h1><?php echo esc_html( $event->name ); ?></h1>
		</div>
	<?php if ( ! empty( $event->seasons ) && empty( $tournament ) ) { ?>
		<!-- Season Dropdown -->
		<div class="col-auto">
			<form action="admin.php" method="get" class="form-control">
				<input type="hidden" name="page" value="racketmanager" />
				<input type="hidden" name="subpage" value="show-event" />
				<input type="hidden" name="event_id" value="<?php echo esc_html( $event->id ); ?>" />
				<label for="season" style="vertical-align: middle;"><?php esc_html_e( 'Season', 'racketmanager' ); ?></label>
				<select size="1" name="season" id="season">
					<?php foreach ( $event->seasons as $event_season ) { ?>
						<option value="<?php echo esc_html( htmlspecialchars( $event_season['name'] ) ); ?>" <?php selected( $event_season['name'], $season ); ?>>
							<?php echo esc_html( $event_season['name'] ); ?>
						</option>
					<?php } ?>
				</select>
				<button type="submit"  class="btn btn-secondary">
					<?php esc_html_e( 'Show', 'racketmanager' ); ?>
				</button>
			</form>
		</div>
	<?php } ?>
</div>

	<?php $this->printMessage(); ?>
	<div class="container">
		<?php
		if ( empty( $tournament ) ) {
			?>
			<!-- Nav tabs -->
			<ul class="nav nav-tabs" id="myTab" role="tablist">
				<li class="nav-item" role="presentation">
					<button class="nav-link" id="leagues-tab" data-bs-toggle="tab" data-bs-target="#leagues" type="button" role="tab" aria-controls="leagues" aria-selected="true"><?php esc_html_e( 'Leagues', 'racketmanager' ); ?></button>
				</li>
				<?php if ( 'tournament' !== $event->competition->type ) { ?>
					<li class="nav-item" role="presentation">
						<button class="nav-link" id="playerstats-tab" data-bs-toggle="tab" data-bs-target="#playerstats" type="button" role="tab" aria-controls="playerstats" aria-selected="false"><?php esc_html_e( 'Players Stats', 'racketmanager' ); ?></button>
					</li>
				<?php } ?>
				<li class="nav-item" role="presentation">
					<button class="nav-link" id="seasons-tab" data-bs-toggle="tab" data-bs-target="#seasons" type="button" role="tab" aria-controls="seasons" aria-selected="false"><?php esc_html_e( 'Seasons', 'racketmanager' ); ?></button>
				</li>
				<?php if ( current_user_can( 'manage_racketmanager' ) ) { ?>
					<li class="nav-item" role="presentation">
						<button class="nav-link" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings" type="button" role="tab" aria-controls="settings" aria-selected="false"><?php esc_html_e( 'Settings', 'racketmanager' ); ?></button>
					</li>
				<?php } ?>
				<?php if ( 'league' === $event->competition->type ) { ?>
					<li class="nav-item" role="presentation">
						<button class="nav-link" id="constitution-tab" data-bs-toggle="tab" data-bs-target="#constitution" type="button" role="tab" aria-controls="constitution" aria-selected="false"><?php esc_html_e( 'Constitution', 'racketmanager' ); ?></button>
					</li>
				<?php } ?>
				<?php if ( 'league' === $event->competition->type ) { ?>
					<li class="nav-item" role="presentation">
						<button class="nav-link" id="matches-tab" data-bs-toggle="tab" data-bs-target="#matches" type="button" role="tab" aria-controls="matches" aria-selected="false"><?php esc_html_e( 'Matches', 'racketmanager' ); ?></button>
					</li>
				<?php } ?>

			</ul>
			<!-- Tab panes -->
			<div class="tab-content">
			<?php
		}
		?>
		<div class="tab-pane" id="leagues" role="tabpanel" aria-labelledby="leagues-tab">
			<h2><?php esc_html_e( 'Leagues', 'racketmanager' ); ?></h2>
			<?php require_once 'event/leagues.php'; ?>
		</div>
		<?php
		if ( empty( $tournament ) ) {
			?>
			<?php if ( 'tournament' !== $event->competition->type ) { ?>
				<div class="tab-pane fade" id="playerstats" role="tabpanel" aria-labelledby="playerstats-tab">
					<h2><?php esc_html_e( 'Player Statistics', 'racketmanager' ); ?></h2>
					<?php include_once 'event/player-stats.php'; ?>
				</div>
			<?php } ?>
			<div class="tab-pane fade" id="seasons" role="tabpanel" aria-labelledby="seasons-tab">
				<h2><?php esc_html_e( 'Seasons', 'racketmanager' ); ?></h2>
				<?php require_once 'event/seasons.php'; ?>
			</div>
			<?php if ( current_user_can( 'manage_racketmanager' ) ) { ?>
				<div class="tab-pane fade" id="settings" role="tabpanel" aria-labelledby="settings-tab">
					<?php include_once 'event/settings.php'; ?>
				</div>
			<?php } ?>
			<?php if ( 'league' === $event->competition->type ) { ?>
				<div class="tab-pane fade" id="constitution" role="tabpanel" aria-labelledby="constitution-tab">
					<div id="constitution" class="league-block-container">
						<?php include_once 'event/constitution.php'; ?>
					</div>
				</div>
			<?php } ?>
			<?php if ( 'league' === $event->competition->type ) { ?>
				<div class="tab-pane fade" id="matches" role="tabpanel" aria-labelledby="matches-tab">
					<div id="matches" class="league-block-container">
						<?php include_once 'event/matches.php'; ?>
					</div>
				</div>
			<?php } ?>
			</div>
			<?php
		}
		?>
	</div>
