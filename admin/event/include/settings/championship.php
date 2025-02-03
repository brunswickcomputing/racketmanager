<?php
/**
 * Event Settings chmapionship administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

$tab_name = 'championship';
if ( ! isset( $event->config->primary_league ) ) {
	$event->config->primary_league = null;
}
$leagues = $event->get_leagues();
?>
<div class="form-control">
	<div class="row">
		<div class="col-md-3">
			<?php
			if ( ! empty( $racketmanager->error_fields ) && is_numeric( array_search( 'primary_league', $racketmanager->error_fields, true ) ) ) {
				$error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
				$is_invalid = true;
				$msg_id     = array_search( 'primary_league', $racketmanager->error_fields, true );
				$msg        = isset( $racketmanager->error_messages[ $msg_id ] ) ? $racketmanager->error_messages[ $msg_id ] : null;
			}
			?>
			<div class="form-floating mb-3">
				<select class="form-select" name="primary_league" id="primary_league">;
					<option value=""><?php esc_html_e( 'Select', 'racketmanager' ); ?></option>
						<?php
						foreach ( $leagues as $league ) {
							?>
							<option value="<?php echo esc_html( $league->id ); ?>" <?php selected( $event->config->primary_league, $league->id ); ?> ><?php echo esc_html( $league->title ); ?></option>
							<?php
						}
						?>
				</select>
				<label for='primary_league'><?php esc_html_e( 'Primary League', 'racketmanager' ); ?></label>
			</div>
			<?php
			if ( $is_invalid ) {
				?>
				<div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
				<?php
				$is_invalid = false;
				$msg        = null;
			}
			?>
		</div>
	</div>
</div>
