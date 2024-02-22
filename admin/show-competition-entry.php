<?php
/**
 * Template for competition entry notification
 *
 * @package Racketmanager/Templates/Admin
 *
 * uses @competition_type
 */

namespace Racketmanager;

?>
<div class="container">
	<h3>
		<?php
		/* translators: %s: competition type */
		printf( esc_html__( 'Notify %s Entry Open', 'racketmanager' ), esc_attr( ucfirst( $competition_type ) ) );
		?>
	</h3>
	<div class="form-control">
		<form action="" method="post">
			<?php wp_nonce_field( 'racketmanager_notify-' . $competition_type . '-open', 'racketmanager_nonce' ); ?>
			<div class="form-group">
				<label class="form-label" for="type"><?php esc_html_e( 'Season', 'racketmanager' ); ?></label>
				<div class="form-input">
					<select size="1" name="season" id="season" >
						<?php
						$seasons = $this->get_seasons( 'DESC' );
						foreach ( $seasons as $season ) {
							?>
							<option value="<?php echo esc_attr( $season->name ); ?>"><?php echo esc_html( $season->name ); ?></option>
							<?php
						}
						?>
					</select>
				</div>
			</div>
			<div class="form-group">
				<label class="form-label" for="competition_id"><?php esc_html_e( 'Competition', 'racketmanager' ); ?></label>
				<div class="form-input">
					<select size="1" name="competition_id" id="competition_id">
						<option disabled selected><?php esc_html_e( 'Select competition', 'racketmanager' ); ?></option>
						<?php
						foreach ( $competitions as $competition ) {
							?>
							<option value="<?php echo esc_attr( $competition->id ); ?>"><?php echo esc_html( $competition->name ); ?></option>
							<?php
						}
						?>
					</select>
				</div>
			</div>
			<input type="hidden" name="notifyOpen" value="open" />
			<button type="submit" class="btn btn-primary"><?php esc_html_e( 'Notify clubs', 'racketmanager' ); ?></button>
		</form>
	</div>
</div>
