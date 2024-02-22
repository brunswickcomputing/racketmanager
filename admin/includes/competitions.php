<?php
/**
 * Competition matches administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

?>
<div class="form-control" id="competitions">
	<form id="competitions-filter" method="post" action="">
		<?php wp_nonce_field( 'competitions-bulk' ); ?>
		<div class="tablenav">
			<!-- Bulk Actions -->
			<select name="action" size="1">
				<option value="-1" selected="selected"><?php esc_html_e( 'Bulk Actions', 'racketmanager' ); ?></option>
				<option value="delete"><?php esc_html_e( 'Delete', 'racketmanager' ); ?></option>
			</select>
			<input type="submit" value="<?php esc_html_e( 'Apply', 'racketmanager' ); ?>" name="docompdel" id="docompdel" class="btn btn-secondary action" />
		</div>
		<div class="container">
			<div class="row table-header">
				<div class="col-2 col-md-1 check-column"><input type="checkbox" id="check-all-competitions" onclick="Racketmanager.checkAll(document.getElementById('competitions-filter'));" /></div>
				<div class="col-1 column-num">ID</div>
				<div class="col-5 col-md-3"><?php esc_html_e( 'Competition', 'racketmanager' ); ?></div>
				<div class="d-none d-md-block col-1 text-center"><?php esc_html_e( 'Number of Seasons', 'racketmanager' ); ?></div>
				<div class="d-none d-md-block col-1 text-center"><?php esc_html_e( 'Events', 'racketmanager' ); ?></div>
				<div class="col-3 centered"><?php esc_html_e( 'Type', 'racketmanager' ); ?></div>
			</div>
			<?php
			$competitions = $this->get_competitions( $competition_query );
			$class        = '';
			foreach ( $competitions as $competition ) {
				$competition = get_competition( $competition );
				$class       = ( 'alternate' === $class ) ? '' : 'alternate';
				?>
				<div class="row table-row <?php echo esc_html( $class ); ?>">
					<div class="col-2 col-md-1 check-column">
						<input type="checkbox" value="<?php echo esc_html( $competition->id ); ?>" name="competition[<?php echo esc_html( $competition->id ); ?>]" />
					</div>
					<div class="col-1 column-num">
						<?php echo esc_html( $competition->id ); ?>
					</div>
					<div class="col-5 col-md-3">
						<a href="admin.php?page=racketmanager&amp;subpage=show-competition&amp;competition_id=<?php echo esc_html( $competition->id ); ?>">
							<?php echo esc_html( $competition->name ); ?>
						</a>
					</div>
					<div class="d-none d-md-block col-1 text-center">
						<?php echo esc_html( $competition->num_seasons ); ?>
					</div>
					<div class="d-none d-md-block col-1 text-center">
						<?php echo esc_html( $competition->num_events ); ?>
					</div>
					<div class="col-3 centered">
						<?php
						switch ( $competition->type ) {
							case 'league':
								esc_html_e( 'League', 'racketmanager' );
								break;
							case 'cup':
								esc_html_e( 'Cup', 'racketmanager' );
								break;
							case 'tournament':
								esc_html_e( 'Tournament', 'racketmanager' );
								break;
							default:
								esc_html_e( 'Unknown', 'racketmanager' );
						}
						?>
					</div>
				</div>
			<?php } ?>
		</div>
	</form>
</div>
