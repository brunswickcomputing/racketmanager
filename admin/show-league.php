<?php
/**
 * League main page administration panel
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

?>
<script type='text/javascript'>
	jQuery(document).ready(function() {
		activaTab('<?php echo esc_html( $tab ); ?>');
	});
</script>
<div class="container">
	<div class="row justify-content-end">
		<div class="col-auto racketmanager_breadcrumb">
			<a href="admin.php?page=racketmanager-<?php echo esc_attr( $league->event->competition->type ); ?>s"><?php echo esc_html( ucfirst( $league->event->competition->type ) ); ?>s</a> &raquo; <a href="admin.php?page=racketmanager-<?php echo esc_attr( $league->event->competition->type ); ?>s&amp;view=seasons&amp;competition_id=<?php echo esc_html( $league->event->competition->id ); ?>"><?php echo esc_html( $league->event->competition->name ); ?></a> &raquo; <a href="admin.php?page=racketmanager-<?php echo esc_html( $league->event->competition->type ); ?>s&amp;view=overview&amp;competition_id=<?php echo esc_attr( $league->event->competition->id ); ?>&amp;season=<?php echo esc_attr( $season ); ?>"><?php echo esc_html( $season ); ?></a> &raquo; <a href="admin.php?page=racketmanager-<?php echo esc_attr( $league->event->competition->type ); ?>s&amp;view=event&amp;event_id=<?php echo esc_html( $league->event->id ); ?>&amp;season=<?php echo esc_attr( $league->current_season['name'] ); ?>"><?php echo esc_html( $league->event->name ); ?></a> &raquo; <?php echo esc_html( $league->title ); ?>
		</div>
	</div>
	<h1><?php echo esc_html( $league->title ); ?></h1>
	<!-- League Menu -->
	<div>
		<div class="row justify-content-between">
			<div class="col-auto">
				<?php foreach ( $this->get_menu() as $key => $menu_item ) { ?>
					<?php if ( isset( $menu_item['show'] ) && $menu_item['show'] ) { ?>
						<a class="btn btn-secondary" href="admin.php?page=racketmanager&amp;subpage=<?php echo esc_html( $key ); ?>&amp;league_id=<?php echo esc_html( $league->id ); ?>&amp;season=<?php echo esc_html( $season ); ?>&amp;group=<?php echo esc_html( $group ); ?>"><?php echo esc_html( $menu_item['title'] ); ?></a>
					<?php } ?>
				<?php } ?>
			</div>
			<?php
			if ( ! empty( $league->event->seasons ) ) {
				?>
				<!-- Season Dropdown -->
				<div class="col-auto">
					<form action="admin.php" method="get" class="form-control">
						<input type="hidden" name="page" value="racketmanager-<?php echo esc_attr( $league->event->competition->type ); ?>s" />
						<input type="hidden" name="view" value="league" />
						<input type="hidden" name="league_id" value="<?php echo esc_html( $league->id ); ?>" />
						<label for="season" style="vertical-align: middle;"><?php esc_html_e( 'Season', 'racketmanager' ); ?></label>
						<select size="1" name="season" id="season">
							<?php
							foreach ( $league->event->seasons as $season_entry ) {
								?>
								<option value="<?php echo esc_html( $season_entry['name'] ); ?>"
								<?php
								if ( strval( $season_entry['name'] ) === $season ) {
									echo ' selected="selected"';
								}
								?>
								>
								<?php echo esc_html( $season_entry['name'] ); ?>
								</option>
								<?php
							}
							?>
						</select>
						<button class="btn btn-secondary" type="submit">
							<?php echo esc_html_e( 'Show', 'racketmanager' ); ?>
						</button>
					</form>
				</div>
				<?php
			}
			?>
		</div>
	</div>

	<?php
	if ( 'championship' === $league_mode ) {
		$league->championship->display_admin_page();
	} else {
		?>
		<div>
			<!-- Nav tabs -->
			<ul class="nav nav-tabs" id="myTab" role="tablist">
				<li class="nav-item" role="presentation">
					<button class="nav-link" id="standings-tab" data-bs-toggle="tab" data-bs-target="#standings" type="button" role="tab" aria-controls="standings" aria-selected="true"><?php esc_html_e( 'Standings', 'racketmanager' ); ?></button>
				</li>
				<li class="nav-item" role="presentation">
					<button class="nav-link" id="crosstable-tab" data-bs-toggle="tab" data-bs-target="#crosstable" type="button" role="tab" aria-controls="crosstable" aria-selected="false"><?php esc_html_e( 'Crosstable', 'racketmanager' ); ?></button>
				</li>
				<li class="nav-item" role="presentation">
					<button class="nav-link" id="matches-tab" data-bs-toggle="tab" data-bs-target="#matches" type="button" role="tab" aria-controls="matches" aria-selected="false"><?php esc_html_e( 'Match Plan', 'racketmanager' ); ?></button>
				</li>
			</ul>
			<!-- Tab panes -->
			<div class="tab-content">
				<div class="tab-pane fade" id="standings" role="tabpanel" aria-labelledby="standings-tab">
					<h2><?php esc_html_e( 'Standings', 'racketmanager' ); ?></h2>
					<?php include_once RACKETMANAGER_PATH . 'admin/league/standings.php'; ?>
				</div>
				<div class="tab-pane fade" id="crosstable" role="tabpanel" aria-labelledby="crosstable-tab">
					<h2><?php esc_html_e( 'Crosstable', 'racketmanager' ); ?></h2>
					<?php include_once RACKETMANAGER_PATH . 'admin/league/crosstable.php'; ?>
				</div>
				<div class="tab-pane fade" id="matches" role="tabpanel" aria-labelledby="matches-tab">
					<h2><?php esc_html_e( 'Matches', 'racketmanager' ); ?></h2>
					<?php include_once RACKETMANAGER_PATH . 'admin/league/matches.php'; ?>
				</div>
			</div>
		</div>
		<?php
		}
	?>
</div>
