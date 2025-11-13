<?php
/**
 * Import administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

global $racketmanager;
/** @var array $clubs */
?>
<div class="container">
	<h1><?php esc_html_e( 'RacketManager Import', 'racketmanager' ); ?></h1>

	<p><?php esc_html_e( 'Choose a file to upload and import data from', 'racketmanager' ); ?></p>

	<form action="" method="post" enctype="multipart/form-data">
		<?php wp_nonce_field( 'racketmanager_import-datasets', 'racketmanager_nonce' ); ?>

		<div class="form-group mb-3">
			<input class="form-control" type="file" name="racketmanager_import" id="racketmanager_import" size="40" placeholder="File name" />
		</div>
		<div class="form-floating mb-3">
			<input class="form-control" type="text" name="delimiter" id="delimiter" value="TAB" size="3" placeholder="TAB" />
			<label for="delimiter"><?php esc_html_e( 'Delimiter', 'racketmanager' ); ?></label>
		</div>
		<p><?php esc_html_e( 'For tab delimited files use TAB as delimiter', 'racketmanager' ); ?></p>
		<div class="form-floating mb-3">
			<select class="form-select" size="1" name="mode" id="mode" onChange='Racketmanager.getImportOption(this.value)'>
				<option><?php esc_html_e( 'Select', 'racketmanager' ); ?></option>
				<option value="table"><?php esc_html_e( 'Table', 'racketmanager' ); ?></option>
				<option value="fixtures"><?php esc_html_e( 'Fixtures', 'racketmanager' ); ?></option>
				<option value="players"><?php esc_html_e( 'Players', 'racketmanager' ); ?></option>
				<option value="clubplayers"><?php esc_html_e( 'Club Players', 'racketmanager' ); ?></option>
			</select>
			<label for="mode"><?php esc_html_e( 'Type of data', 'racketmanager' ); ?></label>
		</div>
		<div id="competitions" class="form-floating mb-3" style="display:none">
			<?php
			$competitions = $racketmanager->get_competitions(
				array(
					'orderby' => array(
						'type' => 'ASC',
						'name' => 'ASC',
					),
				)
			);
			if ( $competitions ) {
				?>
				<select class="form-select" size="1" name="competition_id" id="competition_id" onChange='Racketmanager.getEventDropdown(this.value)'>
					<option><?php esc_html_e( 'Select Competition', 'racketmanager' ); ?></option>
					<?php
                    foreach ( $competitions as $competition ) {
                        ?>
						<option value="<?php echo esc_html( $competition->id ); ?>"><?php echo esc_html( $competition->name ); ?></option>
					    <?php
                    }
                    ?>
				</select>
			    <?php
            }
            ?>
			<label for="competition_id"><?php esc_html_e( 'Competition', 'racketmanager' ); ?></label>
		</div>
		<div id="events" class="form-floating mb-3" style="display:none">
		</div>
		<div id="leagues" class="form-floating mb-3" style="display:none">
		</div>
		<div id="seasons" class="form-floating mb-3" style="display:none">
		</div>
		<div id="clubs" class="form-floating mb-3" style="display:none">
			<?php
            if ( $clubs ) {
				?>
				<select class="form-select" size="1" name="club" id="club">
					<option><?php esc_html_e( 'Select club', 'racketmanager' ); ?></option>
					<?php
                    foreach ( $clubs as $club ) {
                        ?>
						<option value="<?php echo esc_html( $club->id ); ?>"><?php echo esc_html( $club->name ); ?></option>
					    <?php
                    }
                    ?>
				</select>
			    <?php
            }
            ?>
			<label for="club"><?php esc_html_e( 'Club', 'racketmanager' ); ?></label>
		</div>
		<div class="mb-3">
			<input type="submit" name="import" value="<?php esc_html_e( 'Upload file and import', 'racketmanager' ); ?>" class="btn btn-primary" />
		</div>
	</form>
	<p>
		<?php echo esc_html__( 'The required structure of the file to import is described in the', 'racketmanager' ) . ' <a href="../../../../../../wp-admin/admin.php?page=racketmanager-doc"> ' . esc_html__( 'Documentation', 'racketmanager' ) . '</a>'; ?>
    </p>
</div>
