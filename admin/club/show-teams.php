<?php
/**
 * Teams main page administration panel
 *
 * @package Racketmanager/Templates/Admin
 */

namespace Racketmanager;

?>
<div class="container">
	<div class="row justify-content-end">
		<div class="col-auto racketmanager_breadcrumb">
			<a href="admin.php?page=racketmanager-clubs"><?php esc_html_e( 'Clubs', 'racketmanager' ); ?></a> &raquo; <?php esc_html_e( 'Teams', 'racketmanager' ); ?>
		</div>
	</div>
	<h1><?php esc_html_e( 'Teams', 'racketmanager' ); ?> - <?php echo esc_html( $club->name ); ?></h1>

	<!-- View Teams -->
	<div class="mb-3">
		<form id="teams-filter" method="post" action="" class="form-control">
			<?php wp_nonce_field( 'teams-bulk' ); ?>

			<div class="tablenav">
				<!-- Bulk Actions -->
				<select name="action" size="1">
					<option value="-1" selected="selected"><?php esc_html_e( 'Bulk Actions', 'racketmanager' ); ?></option>
					<option value="delete"><?php esc_html_e( 'Delete', 'racketmanager' ); ?></option>
				</select>
				<input type="submit" value="<?php esc_html_e( 'Apply', 'racketmanager' ); ?>" name="doteamdel" id="doteamdel" class="btn btn-secondary action" />
			</div>

			<div class="container">
				<div class="row table-header">
					<div class="col-1 check-column"><input type="checkbox" name="selectAll" onclick="Racketmanager.checkAll(document.getElementById('teams-filter'));" /></div>
					<div class="col-1 column-num">ID</div>
					<div class="col-3"><?php esc_html_e( 'Title', 'racketmanager' ); ?></div>
					<div class="col-3"><?php esc_html_e( 'Stadium', 'racketmanager' ); ?></div>
				</div>
				<?php
				$club  = get_club( $club_id );
				$teams = $club->get_teams();
				$class = '';
				foreach ( $teams as $team ) {
					$class = ( 'alternate' === $class ) ? '' : 'alternate';
					?>
					<div class="row table-row <?php echo esc_html( $class ); ?>">
						<div class="col-1 check-column">
							<input type="checkbox" value="<?php echo esc_html( $team->id ); ?>" name="team[<?php echo esc_html( $team->id ); ?>]" />
						</div>
						<div class="col-1 column-num"><?php echo esc_html( $team->id ); ?></div>
						<div class="col-3 teamname">
							<a href="admin.php?page=racketmanager&amp;subpage=team&amp;edit=<?php echo esc_html( $team->id ); ?>
							<?php
							if ( ! empty( $team->affiliatedclub ) ) {
								?>
								&amp;club_id=<?php echo esc_html( $team->affiliatedclub ); ?>
								<?php
							}
							?>
							">
								<?php echo esc_html( $team->title ); ?>
							</a>
						</div>
						<div class="col-3"><?php echo esc_html( $team->stadium ); ?></div>
					</div>
				<?php } ?>
			</div>
		</form>
	</div>
	<!-- Add New Team -->
	<div class="mb-3">
		<h3><?php esc_html_e( 'Add Team', 'racketmanager' ); ?></h3>
		<form action="" method="post" class="form-control">
			<?php wp_nonce_field( 'racketmanager_add-team' ); ?>
			<div class="form-floating mb-3">
				<select class="form-select" size='1' required="required" name='team_type' id='team_type'>
					<option value=""><?php esc_html_e( 'Select event type', 'racketmanager' ); ?></option>
					<?php
					$event_types = Racketmanager_Util::get_event_types();
					foreach ( $event_types as $key => $event_type ) {
						?>
						<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $event_type ); ?></option>
						<?php
					}
					?>
				</select>
				<label for="team_type"><?php esc_html_e( 'Type', 'racketmanager' ); ?></label>
			</div>
			<input type="hidden" name="club" value=<?php echo esc_html( $club->id ); ?> />
			<input type="hidden" name="addTeam" value="team" />
			<input type="submit" name="addTeam" value="<?php esc_html_e( 'Add Team', 'racketmanager' ); ?>" class="btn btn-primary" />

		</form>
	</div>
</div>
