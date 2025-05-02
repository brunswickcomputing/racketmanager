<?php
/**
 * Index administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

use Racketmanager\Racketmanager_Util as util;
/** @var bool   $is_invalid */
/** @var string $msg */
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
			<div class="row gx-3 mb-3">
				<div class="col-md-6">
					<div class="form-floating">
						<?php
						if ( ! empty( $racketmanager->error_fields ) && is_numeric( array_search( 'competition_name', $racketmanager->error_fields, true ) ) ) {
							$is_invalid = true;
							$msg_id     = array_search( 'competition_name', $racketmanager->error_fields, true );
							$msg        = $racketmanager->error_messages[$msg_id] ?? null;
						}
						?>
						<input class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" placeholder="<?php esc_html_e( 'Enter name for new competition', 'racketmanager' ); ?>" type="text" name="competition_name" id="competition_name" value="" size="30" />
						<label for="competition_name"><?php esc_html_e( 'Competition name', 'racketmanager' ); ?></label>
						<?php
						if ( ! empty( $is_invalid ) ) {
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
			<div class="row gx-3 mb-3">
				<div class="col-md-6 mb-3 mb-md-0">
					<div class="form-floating">
						<?php
						if ( ! empty( $racketmanager->error_fields ) && is_numeric( array_search( 'type', $racketmanager->error_fields, true ) ) ) {
							$is_invalid = true;
							$msg_id     = array_search( 'type', $racketmanager->error_fields, true );
							$msg        = $racketmanager->error_messages[$msg_id] ?? null;
						}
						?>
                        <select class="form-select <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="type" id="type">
                            <option disabled selected><?php esc_html_e( 'Select type', 'racketmanager' ); ?></option>
                            <?php
                            foreach ( Util::get_competition_types() as $competition_type => $competition_type_desc ) {
                                ?>
                                <option value="<?php echo esc_html( $competition_type ); ?>"><?php echo esc_html( ucfirst( $competition_type_desc ) ); ?></option>
                                <?php
                            }
                            ?>
                        </select>
						<label for="type"><?php esc_html_e( 'Type', 'racketmanager' ); ?></label>
						<?php
						if ( ! empty( $is_invalid ) ) {
							?>
							<div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
							<?php
							$is_invalid = false;
							$msg        = null;
						}
						?>
					</div>
				</div>
				<div class="col-md-6">
					<div class="form-floating">
						<?php
						if ( ! empty( $racketmanager->error_fields ) && is_numeric( array_search( 'age_group', $racketmanager->error_fields, true ) ) ) {
							$is_invalid = true;
							$msg_id     = array_search( 'age_group', $racketmanager->error_fields, true );
							$msg        = $racketmanager->error_messages[$msg_id] ?? null;
						}
						?>
						<select class="form-select <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="age_group" id="age_group">
							<option disabled selected><?php esc_html_e( 'Select age group', 'racketmanager' ); ?></option>
							<?php
							foreach ( Util::get_age_groups() as $age_group => $age_group_desc ) {
								?>
								<option value="<?php echo esc_html( $age_group ); ?>"><?php echo esc_html( $age_group_desc ); ?></option>
								<?php
							}
							?>
						</select>
						<label for="age_group"><?php esc_html_e( 'Age Group', 'racketmanager' ); ?></label>
						<?php
						if ( ! empty( $is_invalid ) ) {
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
			<input type="hidden" name="addCompetition" value="competition" />
			<input type="submit" name="addCompetition" value="<?php esc_html_e( 'Add Competition', 'racketmanager' ); ?>" class="btn btn-primary" />

		</form>

	</div>
</div>
