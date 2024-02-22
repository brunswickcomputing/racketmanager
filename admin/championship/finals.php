<?php
/**
 * Finals administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

global $racketmanager, $wp;
// phpcs:disable WordPress.Security.NonceVerification.Recommended
$page    = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : null; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
$subpage = isset( $_GET['subpage'] ) ? sanitize_text_field( wp_unslash( $_GET['subpage'] ) ) : null;
// phpcs:enable WordPress.Security.NonceVerification.Recommended
?>
<div class="championship-block">
	<div class="row tablenav">
		<form action="" method="get" class="col-auto">
			<input type="hidden" name="page" value="<?php echo esc_html( $page ); ?>" />
			<input type="hidden" name="subpage" value="<?php echo esc_html( $subpage ); ?>" />
			<input type="hidden" name="league_id" value="<?php echo esc_html( $league->id ); ?>" />
			<input type="hidden" name="season" value="<?php echo esc_html( $league->current_season['name'] ); ?>" />

			<select size="1" name="final" id="final">
				<?php foreach ( $league->championship->get_finals() as $final ) { ?>
					<option value="<?php echo esc_html( $final['key'] ); ?>" <?php selected( $league->championship->get_current_final_key(), $final['key'] ); ?>><?php echo esc_html( $final['name'] ); ?></option>
				<?php } ?>
			</select>
			<input type="hidden" name="league-tab" value="matches" />
			<input type="submit" class="btn btn-secondary" value="<?php esc_html_e( 'Show', 'racketmanager' ); ?>" />
		</form>
		<form action="" method="get" class="col-auto">
			<input type="hidden" name="page" value="<?php echo esc_html( $page ); ?>" />
			<input type="hidden" name="subpage" value="match" />
			<input type="hidden" name="league_id" value="<?php echo esc_html( $league->id ); ?>" />
			<input type="hidden" name="season" value="<?php echo esc_html( $league->current_season['name'] ); ?>" />

			<!-- Bulk Actions -->
			<select name="mode" size="1">
				<option value="-1" selected="selected"><?php esc_html_e( 'Actions', 'racketmanager' ); ?></option>
				<option value="add"><?php esc_html_e( 'Add Matches', 'racketmanager' ); ?></option>
				<option value="edit"><?php esc_html_e( 'Edit Matches', 'racketmanager' ); ?></option>
			</select>

			<select size="1" name="final" id="final1">
				<?php foreach ( $league->championship->get_finals() as $final ) { ?>
					<option value="<?php echo esc_html( $final['key'] ); ?>"><?php echo esc_html( $final['name'] ); ?></option>
				<?php } ?>
			</select>
			<input type="hidden" name="league-tab" value="matches" />
			<input type="submit" class="btn btn-secondary" value="<?php esc_html_e( 'Go', 'racketmanager' ); ?>" />
		</form>
	</div>

	<?php $final = $league->championship->get_finals( 'current' ); ?>
	<?php
	$matches = $league->get_matches(
		array(
			'final'   => ( ! empty( $final['key'] ) ? $final['key'] : '' ),
			'orderby' => array( 'id' => 'ASC' ),
		)
	);
	?>

	<form method="post" action="">
		<?php wp_nonce_field( 'racketmanager_update-finals', 'racketmanager_nonce' ); ?>
		<input type="hidden" name="league_id" value="<?php echo esc_html( $league->id ); ?>" />
		<input type="hidden" name="season" value="<?php echo esc_html( $league->current_season['name'] ); ?>" />
		<input type="hidden" name="round" value="<?php echo esc_html( $final['round'] ); ?>" />
		<input type="hidden" name="league-tab" value="matches" />
		<input type="hidden" name="action" value="updateFinalResults" />

		<?php
		if ( $matches ) {
			?>
			<table class="widefat" aria-describedby="<?php esc_html_e( 'Finals', 'racketmanager' ); ?>">
				<thead>
					<tr>
						<th><?php esc_html_e( '#', 'racketmanager' ); ?></th>
						<th><?php esc_html_e( 'ID', 'racketmanager' ); ?></th>
						<th><?php esc_html_e( 'Date', 'racketmanager' ); ?></th>
						<th style="text-align: center;"><?php esc_html_e( 'Match', 'racketmanager' ); ?></th>
						<th><?php esc_html_e( 'Location', 'racketmanager' ); ?></th>
						<?php
						if ( ! isset( $league->event->competition->entry_type ) || 'player' !== $league->event->competition->entry_type ) {
							?>
							<th><?php esc_html_e( 'Begin', 'racketmanager' ); ?></th>
							<?php
						}
						if ( isset( $league->num_rubbers ) && $league->num_rubbers > 0 ) {
							?>
							<th><?php echo esc_html__( 'Rubbers', 'racketmanager' ); ?></th>
							<?php
						} else {
							?>
							<th colspan="<?php echo esc_html( $league->num_sets ); ?>" style="text-align: center;"><?php echo esc_html__( 'Sets', 'racketmanager' ); ?></th>
							<?php
						}
						?>
						<th class="score"><?php esc_html_e( 'Score', 'racketmanager' ); ?></th>
					</tr>
				</thead>
				<tbody id="the-list-<?php echo esc_html( $final['key'] ); ?>" class="lm-form-table">
					<?php
					$m = 1; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
					foreach ( $matches as $match ) {
						$class = ( 'alternate' === $class ) ? '' : 'alternate';
						?>
						<tr class="<?php echo esc_html( $class ); ?>">
							<td>
								<?php echo esc_html( $m ); ?><input type="hidden" name="matches[<?php echo esc_html( $match->id ); ?>]" value="<?php echo esc_html( $match->id ); ?>" /><input type="hidden" name="home_team[<?php echo esc_html( $match->id ); ?>]" value="<?php echo esc_html( $match->home_team ); ?>" /><input type="hidden" name="away_team[<?php echo esc_html( $match->id ); ?>]" value="<?php echo esc_html( $match->away_team ); ?>" />
							</td>
							<td>
								<?php echo esc_html( $match->id ); ?>
							</td>
							<td>
								<?php echo ( isset( $match->date ) ) ? esc_html( mysql2date( $racketmanager->date_format, $match->date ) ) : 'N/A'; ?>
							</td>
							<td class="match-title">
								<a href="admin.php?page=racketmanager&amp;subpage=match&amp;league_id=<?php echo esc_html( $league->id ); ?>&amp;edit=<?php echo esc_html( $match->id ); ?>&amp;season=<?php echo esc_html( $match->season ); ?>">
									<?php echo esc_html( $match->get_title() ); ?>
								</a>
							</td>
							<td>
								<?php echo ( isset( $match->location ) ) ? esc_html( $match->location ) : 'N/A'; ?>
							</td>
							<?php
							if ( ! isset( $league->event->competition->entry_type ) || 'player' !== $league->event->competition->entry_type ) {
								?>
								<td>
									<?php echo ( isset( $match->hour ) ) ? esc_html( mysql2date( $racketmanager->time_format, $match->date ) ) : 'N/A'; ?>
								</td>
								<?php
							}
							if ( ! empty( $league->num_rubbers ) ) {
								if ( is_numeric( $match->home_team ) && is_numeric( $match->away_team ) ) {
									?>
									<td>
										<button type="button" class="btn btn-secondary" id="<?php echo esc_html( $match->id ); ?>'" onclick="Racketmanager.showRubbers(this)"><?php echo esc_html__( 'View Rubbers', 'racketmanager' ); ?>
										</button>
									</td>
									<?php
								}
							} else {
								for ( $i = 1; $i <= $league->num_sets; $i++ ) {
									if ( ! isset( $match->sets[ $i ] ) ) {
										$match->sets[ $i ] = array(
											'player1'  => '',
											'player2'  => '',
											'tiebreak' => '',
										);
									}
									if ( ! isset( $match->sets[ $i ]['tiebreak'] ) ) {
										$match->sets[ $i ]['tiebreak'] = '';
									}
									?>
									<td>
										<input class="points" type="text" size="2" id="set_<?php echo esc_html( $match->id ); ?>_<?php echo esc_html( $i ); ?>_player1" name="custom[<?php echo esc_html( $match->id ); ?>][sets][<?php echo esc_html( $i ); ?>][player1]" value="<?php echo esc_html( $match->sets[ $i ]['player1'] ); ?>" />
										&nbsp;:&nbsp;
										<input class="points" type="text" size="2" id="set_<?php echo esc_html( $match->id ); ?>_<?php echo esc_html( $i ); ?>_player2" name="custom[<?php echo esc_html( $match->id ); ?>][sets][<?php echo esc_html( $i ); ?>][player2]" value="<?php echo esc_html( $match->sets[ $i ]['player2'] ); ?>" />
										<br>
										<input class="points tie-break" type="text" size="2" id="set_<?php echo esc_html( $match->id ); ?>_<?php echo esc_html( $i ); ?>_tiebreak" name="custom[<?php echo esc_html( $match->id ); ?>][sets][<?php echo esc_html( $i ); ?>][tiebreak]" value="<?php echo esc_html( $match->sets[ $i ]['tiebreak'] ); ?>" />
									</td>
									<?php
								}
							}
							?>
							<td class="score">
								<input class="points" type="text" size="2" style="text-align: center;" id="home_points-<?php echo esc_html( $match->id ); ?>" name="home_points[<?php echo esc_html( $match->id ); ?>]" value="<?php echo ( isset( $match->home_points ) ) ? esc_html( sprintf( '%g', $match->home_points ) ) : ''; ?>" /> : <input class="points" type="text" size="2" style="text-align: center;" id="away_points-<?php echo esc_html( $match->id ); ?>" name="away_points[<?php echo esc_html( $match->id ); ?>]" value="<?php echo ( isset( $match->away_points ) ) ? esc_html( sprintf( '%g', $match->away_points ) ) : ''; ?>" />
							</td>
						</tr>
						<?php
						++$m;
					}
					?>
				</tbody>
			</table>
			<button class="btn btn-primary"><?php esc_html_e( 'Save Results', 'racketmanager' ); ?></button>
			<?php
		}
		?>
	</form>
</div>
<?php require RACKETMANAGER_PATH . 'admin/league/match-modal.php'; ?>
