<?php
/**
 * Index administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

use Racketmanager\Racketmanager_Util as util;
?>
<div class="container">
	<h1><?php esc_html_e( 'Racketmanager Competitions', 'racketmanager' ); ?></h1>
	<div id="competitions-table" class="league-block-container mb-3">
		<div class="">
			<?php
			$competition_query = array();
			require 'includes/competitions.php';
			?>
		</div>
	</div>
	<div class="">
		<h3><?php esc_html_e( 'Add Competition', 'racketmanager' ); ?></h3>
		<!-- Add New Competition -->
		<form action="" method="post" class="form-control">
			<?php wp_nonce_field( 'racketmanager_add-competition', 'racketmanager_nonce' ); ?>
			<div class="form-floating mb-3">
				<input class="form-control" required="required" placeholder="<?php esc_html_e( 'Enter name for new competition', 'racketmanager' ); ?>" type="text" name="competition_name" id="competition_name" value="" size="30" />
				<label for="competition_name"><?php esc_html_e( 'Competition name', 'racketmanager' ); ?></label>
			</div>
			<div class="form-floating mb-3">
				<select class="form-select" size="1" name="type" id="type">
					<option><?php esc_html_e( 'Select', 'racketmanager' ); ?></option>
					<?php
					foreach ( Util::get_competition_types() as $racketmanager_id => $competition_type ) {
						?>
						<option value="<?php echo esc_html( $racketmanager_id ); ?>"><?php echo esc_html( ucfirst( $competition_type ) ); ?></option>
						<?php
					}
					?>
				</select>
				<label for="competition_type"><?php esc_html_e( 'Type', 'racketmanager' ); ?></label>
			</div>
			<input type="hidden" name="addCompetition" value="competition" />
			<input type="submit" name="addCompetition" value="<?php esc_html_e( 'Add Competition', 'racketmanager' ); ?>" class="btn btn-primary" />

		</form>

	</div>
</div>
