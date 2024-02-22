<?php
/**
 * Team list administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

global $racketmanager;
if ( 'constitution' === $view ) {
	$page_title = __( 'Add Teams to Constitution', 'racketmanager' );
	$page_link  = $league->event->name;
	$breadcrumb = 'show-event&amp;event_id=' . $league->event_id;
} else {
	$page_title = __( 'Add Teams to League', 'racketmanager' );
	$page_link  = $league->title;
	$breadcrumb = 'show-league&amp;league_id=' . $league->id;
}
$main_title = $page_link . ' - ' . $page_title;
?>
<div class="container">
	<div class="row justify-content-end">
		<div class="col-auto racketmanager_breadcrumb">
			<a href="admin.php?page=racketmanager"><?php esc_html_e( 'RacketManager', 'racketmanager' ); ?></a> &raquo; <a href="admin.php?page=racketmanager&amp;subpage=<?php echo esc_html( $breadcrumb ); ?>"><?php echo esc_html( $page_link ); ?></a> &raquo; <?php echo esc_html( $page_title ); ?>
		</div>
	</div>
	<h1><?php echo esc_html( $main_title ); ?></h1>
	<form action="admin.php?page=racketmanager&amp;subpage=<?php echo esc_html( $breadcrumb ); ?>&amp;season=<?php echo esc_html( $season ); ?>" method="post" enctype="multipart/form-data" name="teams_add">
		<?php wp_nonce_field( 'racketmanager_add-teams-bulk', 'racketmanager_nonce' ); ?>
		<input type="hidden" name="event_id" value="<?php echo esc_html( $league->event->id ); ?>" />
		<input type="hidden" name="league_id" value="<?php echo esc_html( $league_id ); ?>" />
		<input type="hidden" name="season" value="<?php echo esc_html( $season ); ?>" />
		<legend>Select Teams to Add</legend>
		<div class="tablenav">
			<!-- Bulk Actions -->
			<select name="action" size="1">
				<option value="addTeamsToLeague"><?php esc_html_e( 'Add', 'racketmanager' ); ?></option>
			</select>
			<input type="submit" value="<?php esc_html_e( 'Apply', 'racketmanager' ); ?>" name="doAddTeamToLeague" id="doAddTeamToLeague" class="button action" />
		</div>
		<div class="container">
			<div class="row table-header">
				<div class="col-1 check-column"><input type="checkbox" onclick="Racketmanager.checkAll(document.getElementById('teams-filter'));" /></div>
				<div class="col-1 column-num">ID</div>
				<div class="col-3"><?php esc_html_e( 'Title', 'racketmanager' ); ?></div>
				<div class="col-3"><?php esc_html_e( 'Affiliated Club', 'racketmanager' ); ?></div>
				<div class="col-3"><?php esc_html_e( 'Stadium', 'racketmanager' ); ?></div>
			</div>
			<?php
			if ( $teams ) {
				$class = '';
				foreach ( $teams as $team ) {
					?>
					<?php $class = ( 'alternate' === $class ) ? '' : 'alternate'; ?>
					<div class="row table-row <?php echo esc_html( $class ); ?>">
						<div class="col-1 check-column">
							<input type="checkbox" value="<?php echo esc_html( $team->id ); ?>" name="team[<?php echo esc_html( $team->id ); ?>]" />
						</div>
						<div class="col-1 column-num"><?php echo esc_html( $team->id ); ?></div>
						<div class="col-3"><?php echo esc_html( $team->title ); ?></div>
						<div class="col-3"><?php echo esc_html( $team->club->shortcode ); ?></div>
						<div class="col-3"><?php echo esc_html( $team->stadium ); ?></div>
					</div>
					<?php
				}
			}
			?>
		</form>
	</div>
</div>
