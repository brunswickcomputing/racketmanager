<?php
/**
 * Competition matches administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

$tab_class = '';
if ( $standalone ) {
	$tab_class = ' active show ';
}
?>
<div class="tab-pane fade form-control <?php echo esc_html( $tab_class ); ?>" id="competitions<?php echo esc_html( $competition_type ); ?>" role="tabpanel" aria-labelledby="competitions<?php echo esc_html( $competition_type ); ?>-tab">
	<form id="competitions-filter" method="post" action="">
		<?php wp_nonce_field( 'competitions-bulk' ); ?>
		<?php
		if ( ! $type ) {
			?>
			<div class="tablenav">
				<!-- Bulk Actions -->
				<select name="action" size="1">
					<option value="-1" selected="selected"><?php esc_html_e( 'Bulk Actions', 'racketmanager' ); ?></option>
					<option value="delete"><?php esc_html_e( 'Delete', 'racketmanager' ); ?></option>
				</select>
				<input type="submit" value="<?php esc_html_e( 'Apply', 'racketmanager' ); ?>" name="docompdel" id="docompdel" class="btn btn-secondary action" />
			</div>
		<?php } ?>
		<div class="container">
			<div class="row table-header">
				<?php
				if ( ! $type ) {
					?>
				<div class="col-2 col-md-1 check-column"><input type="checkbox" id="check-all-competitions" onclick="Racketmanager.checkAll(document.getElementById('competitions-filter'));" /></div>
				<?php } ?>
				<div class="col-1 column-num">ID</div>
				<div class="col-5 col-md-3"><?php esc_html_e( 'Competition', 'racketmanager' ); ?></div>
				<?php if ( ! $type ) { ?>
					<div class="d-none d-md-block col-1 text-center"><?php esc_html_e( 'Number of Seasons', 'racketmanager' ); ?></div>
				<?php } ?>
				<div class="d-none d-md-block col-1 text-center"><?php esc_html_e( 'Leagues', 'racketmanager' ); ?></div>
				<div class="d-none d-md-block col-1 text-center"><?php esc_html_e( 'Number of Sets', 'racketmanager' ); ?></div>
				<div class="d-none d-md-block col-1 text-center"><?php esc_html_e( 'Number of Rubbers', 'racketmanager' ); ?></div>
					<div class="col-3 centered"><?php esc_html_e( 'Type', 'racketmanager' ); ?></div>
			</div>
			<?php
			$events = $this->get_events( $competition_query );
			$class        = '';
			foreach ( $events as $event ) {
				$competition = get_competition( $competition );
				$class       = ( 'alternate' === $class ) ? '' : 'alternate';
				?>
				<div class="row table-row <?php echo esc_html( $class ); ?>">
					<?php
					if ( ! $type ) {
						?>
						<div class="col-2 col-md-1 check-column">
							<input type="checkbox" value="<?php echo esc_html( $competition->id ); ?>" name="competition[<?php echo esc_html( $competition->id ); ?>]" />
						</div>
					<?php } ?>
					<div class="col-1 column-num">
						<?php echo esc_html( $competition->id ); ?>
					</div>
					<div class="col-5 col-md-3">
						<a href="admin.php?page=racketmanager&amp;subpage=show-competition&amp;competition_id=<?php echo esc_html( $competition->id ); ?>">
							<?php echo esc_html( $competition->name ); ?>
						</a>
					</div>
					<?php
					if ( ! $type ) {
						?>
						<div class="d-none d-md-block col-1 text-center">
							<?php echo esc_html( $competition->num_seasons ); ?>
						</div>
					<?php } ?>
					<div class="d-none d-md-block col-1 text-center">
						<?php echo esc_html( $competition->num_leagues ); ?>
					</div>
					<div class="d-none d-md-block col-1 text-center">
						<?php echo esc_html( $competition->num_sets ); ?>
					</div>
					<div class="d-none d-md-block col-1 text-center">
						<?php echo esc_html( $competition->num_rubbers ); ?>
					</div>
					<div class="col-3 centered">
						<?php
						switch ( $competition->type ) {
							case 'WS':
								esc_html_e( 'Ladies Singles', 'racketmanager' );
								break;
							case 'WD':
								esc_html_e( 'Ladies Doubles', 'racketmanager' );
								break;
							case 'MS':
								esc_html_e( 'Mens Singles', 'racketmanager' );
								break;
							case 'MD':
								esc_html_e( 'Mens Doubles', 'racketmanager' );
								break;
							case 'XD':
								esc_html_e( 'Mixed Doubles', 'racketmanager' );
								break;
							case 'LD':
								esc_html_e( 'The League', 'racketmanager' );
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
