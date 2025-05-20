<?php
/**
 * Template for competitions list
 *
 * @package Racketmanager/Templates/Admin
 */

namespace Racketmanager;

$query_type = 'all';
$query_type = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : null; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
if ( isset( $_GET['tournament'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$form_action = 'admin.php?page=racketmanager&subpage=show-competitions&tournament=' . sanitize_text_field( wp_unslash( $_GET['tournament'] ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
} else {
	$form_action = 'admin.php?page=racketmanager-admin';
}
?>
<div class="container league-block">
	<div class="row justify-content-end">
	<div class="col-auto racketmanager_breadcrumb">
		<?php if ( ! isset( $_GET['tournament'] ) ) {  // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<a href="admin.php?page=racketmanager"><?php esc_html_e( 'RacketManager', 'racketmanager' ); ?></a> &raquo; <?php echo esc_html( $season->name ); ?> &raquo; <?php echo 'Add Competitions to Season'; ?>
		<?php } else { ?>
		<a href="admin.php?page=racketmanager-tournaments"><?php esc_html_e( 'RacketManager Tournaments', 'racketmanager' ); ?></a> &raquo; <?php echo esc_html( $tournament->name ); ?> &raquo; <?php echo 'Add Competitions to Tournament'; ?>
		<?php } ?>
	</div>
	</div>
	<h1>
	<?php
	if ( isset( $_GET['tournament'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		esc_html_e( 'Add Competitions to Tournament', 'racketmanager' );
	} else {
		echo sprintf( '%s - %s', esc_html( $season->name ), 'Add Competitions to Season' );
	}
	?>
	</h1>
	<div class="container">
	<legend>Select Competitions to Add</legend>
	<form action="<?php echo esc_html( $form_action ); ?>" method="post" enctype="multipart/form-data" name="competitions_add" id="competitions_add">
		<?php wp_nonce_field( 'racketmanager_add-seasons-competitions-bulk', 'racketmanager_nonce' ); ?>
		<input type="hidden" name="season_id" value="<?php echo esc_html( $season->id ); ?>" />
		<input type="hidden" name="season" value="<?php echo esc_html( $season->name ); ?>" />
		<?php if ( isset( $_GET['tournament'] ) ) {  // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		<input type="hidden" name="tournament" value="<?php echo intval( $_GET['tournament'] );  // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>" />
		<?php } ?>
		<?php if ( 'all' === $query_type ) { ?>
		<div id="matchDays">
			<label for="num_match_days"><?php esc_html_e( 'Number of Match Days', 'racketmanager' ); ?></label>
			<input type="number" min="1" step="1" required="required" class="small-text" name="num_match_days" id="num_match_days" size="2" />
		</div>
		<?php } else { ?>
		<input type="hidden" name="num_match_days" id="num_match_days" />
		<?php } ?>
		<div class="container">
		<?php if ( 'all' === $query_type ) { ?>
			<!-- Nav tabs -->
			<ul class="nav nav-pills" id="myTab" role="tablist">
			<li class="nav-item" role="presentation">
				<button class="nav-link active" id="competitionscup-tab" data-bs-toggle="pill" data-bs-target="#competitionscup" type="button" role="tab" aria-controls="competitionscup" aria-selected="true"><?php esc_html_e( 'Cups', 'racketmanager' ); ?></button>
			</li>
			<li class="nav-item" role="presentation">
				<button class="nav-link" id="competitionsleague-tab" data-bs-toggle="pill" data-bs-target="#competitionsleague" type="button" role="tab" aria-controls="competitionsleague" aria-selected="false"><?php esc_html_e( 'Leagues', 'racketmanager' ); ?></button>
			</li>
			<li class="nav-item" role="presentation">
				<button class="nav-link" id="competitionstournament-tab" data-bs-toggle="pill" data-bs-target="#competitionstournament" type="button" role="tab" aria-controls="competitionstournament" aria-selected="false"><?php esc_html_e( 'Tournaments', 'racketmanager' ); ?></button>
			</li>
			</ul>
		<?php } ?>
		<!-- Tab panes -->
		<div class="tab-content">
			<div class="tablenav">
			<!-- Bulk Actions -->
			<select name="action" size="1">
				<option value="addCompetitionsToSeason"><?php esc_html_e( 'Add', 'racketmanager' ); ?></option>
			</select>
			<input type="submit" value="<?php esc_html_e( 'Apply', 'racketmanager' ); ?>" name="doAddCompetitionsToSeason" id="doAddCompetitionsToSeason" class="btn btn-primary action" />
			</div>
			<div class="container">
			<div class="row table-header">
				<div class="col-1 check-column"><input type="checkbox" onclick="Racketmanager.checkAll(document.getElementById('competitions_add'));" /></div>
				<div class="col-2 column-num">ID</div>
				<div class="col-4"><?php esc_html_e( 'Name', 'racketmanager' ); ?></div>
			</div>
			</div>

			<?php
			$competition_types = Racketmanager_Util::get_competition_types();
			$i                 = 0;
			foreach ( $competition_types as $competition_type ) {
				if ( 'all' === $query_type || $query_type === $competition_type ) {
					$i ++;
					?>
				<div id="competitions<?php echo esc_html( $competition_type ); ?>" class="tab-pane table-pane fade
					<?php
					if ( 1 === $i ) {
						echo ' active show';
					}
					?>
				" role="tabpanel" aria-labelledby="competitions<?php echo esc_html( $competition_type ); ?>-tab">
				<div class="container">
						<?php
						$competition_query = array( 'type' => $competition_type );
						if ( isset( $_GET['tournamenttype'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
							$competition_query['name'] = sanitize_text_field( wp_unslash( $_GET['tournamenttype'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
						}
						$competitions = $racketmanager->get_competitions( $competition_query );
						$class        = '';
						?>
						<?php
						foreach ( $competitions as $competition ) {
							$competition = get_competition( $competition );
							$class       = ( 'alternate' === $class ) ? '' : 'alternate';
							?>
					<div class="row table-row <?php echo esc_html( $class ); ?>">
						<div class="col-1 check-column">
							<?php if ( ! is_numeric( array_search( $season->name, array_column( $competition->seasons, 'name' ), true ) ) ) { ?>
							<input type="checkbox" value="<?php echo esc_html( $competition->id ); ?>" name="competition[<?php echo esc_html( $competition->id ); ?>]" />
						<?php } ?>
						</div>
						<div class="col-2 column-num"><?php echo esc_html( $competition->id ); ?></div>
						<div class="col-4"><?php echo esc_html( $competition->name ); ?></div>
					</div>
							<?php } ?>
				</div>
				</div>
				<?php } ?>
			<?php } ?>
		</div>
		</div>
	</form>
	</div>
</div>
