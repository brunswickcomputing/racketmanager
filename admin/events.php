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
		<script type='text/javascript'>
		jQuery(document).ready(function(){
			activaTab('<?php echo esc_html( $tab ); ?>');
		});
		</script>
		<div class="container">
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
			<!-- Tab panes -->
			<div class="tab-content">

				<?php
				$racketmanager_competition_types = Util::get_competition_types();
				foreach ( $racketmanager_competition_types as $competition_type ) {
					$season            = '';
					$type              = ''; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
					$standalone        = false;
					$competition_query = array( 'type' => $competition_type );
					include 'includes/competitions.php';
				}
				?>

			</div>
		</div>
	</div>

	<div class="container">
		<h3><?php esc_html_e( 'Add Competition', 'racketmanager' ); ?></h3>
		<!-- Add New Competition -->
		<form action="" method="post" class="form-control">
			<?php wp_nonce_field( 'racketmanager_add-competition' ); ?>
			<div class="form-floating mb-3">
				<input class="form-control" required="required" placeholder="<?php esc_html_e( 'Enter name for new competition', 'racketmanager' ); ?>" type="text" name="competition_name" id="competition_name" value="" size="30" />
				<label for="competition_name"><?php esc_html_e( 'Competition name', 'racketmanager' ); ?></label>
			</div>
			<div class="form-floating mb-3">
				<input class="form-control" required="required" placeholder="<?php esc_html_e( 'How many sets', 'racketmanager' ); ?>" type='number' name='num_sets' id='num_sets' value='' size='3' />
				<label for='num_sets'><?php esc_html_e( 'Number of Sets', 'racketmanager' ); ?></label>
			</div>
			<div class="form-floating mb-3">
				<input class="form-control" required="required" placeholder="<?php esc_html_e( 'How many rubbers', 'racketmanager' ); ?>" type='number' name='num_rubbers' id='num_rubbers' value='' size='3' />
				<label for='num_rubbers'><?php esc_html_e( 'Number of Rubbers', 'racketmanager' ); ?></label>
			</div>
			<div class="form-floating mb-3">
				<select class="form-select" size='1' required="required" name='type' id='type'>
					<option><?php esc_html_e( 'Select', 'racketmanager' ); ?></option>
					<?php // phpcs:ignore Squiz.ControlStructures.ControlSignature.NewlineAfterOpenBrace ?>
					<option value='WS' <?php if ( isset( $competition->type ) ) { 'WS' === $competition->type ? 'selected' : ''; } ?>>
						<?php esc_html_e( 'Ladies Singles', 'racketmanager' ); ?>
					</option>
					<?php // phpcs:ignore Squiz.ControlStructures.ControlSignature.NewlineAfterOpenBrace ?>
					<option value='WD' <?php if ( isset( $competition->type ) ) { 'WD' === $competition->type ? 'selected' : ''; } ?>>
						<?php esc_html_e( 'Ladies Doubles', 'racketmanager' ); ?>
					</option>
					<?php // phpcs:ignore Squiz.ControlStructures.ControlSignature.NewlineAfterOpenBrace ?>
					<option value='MD' <?php if ( isset( $competition->type ) ) { 'MD' === $competition->type ? 'selected' : ''; } ?>>
						<?php esc_html_e( 'Mens Doubles', 'racketmanager' ); ?>
					</option>
					<?php // phpcs:ignore Squiz.ControlStructures.ControlSignature.NewlineAfterOpenBrace ?>
					<option value='MS' <?php if ( isset( $competition->type ) ) { 'MS' === $competition->type ? 'selected' : ''; } ?>>
						<?php esc_html_e( 'Mens Singles', 'racketmanager' ); ?>
					</option>
					<?php // phpcs:ignore Squiz.ControlStructures.ControlSignature.NewlineAfterOpenBrace ?>
					<option value='XD' <?php if ( isset( $competition->type ) ) { 'XD' === $competition->type ? 'selected' : ''; } ?>>
						<?php esc_html_e( 'Mixed Doubles', 'racketmanager' ); ?>
					</option>
					<?php // phpcs:ignore Squiz.ControlStructures.ControlSignature.NewlineAfterOpenBrace ?>
					<option value='LD' <?php if ( isset( $competition->type ) ) { 'LD' === $competition->type ? 'selected' : ''; } ?>>
						<?php esc_html_e( 'The League', 'racketmanager' ); ?>
					</option>
				</select>
				<label for='type'><?php esc_html_e( 'Competition Type', 'racketmanager' ); ?></label>
			</div>
			<div class="form-floating mb-3">
				<select class="form-select" size="1" name="competition_type" id="competition_type">
					<option><?php esc_html_e( 'Select', 'racketmanager' ); ?></option>
					<?php foreach ( Util::get_competition_types() as $racketmanager_id => $competition_type ) { ?>
						<option value="<?php echo esc_html( $racketmanager_id ); ?>"><?php echo esc_html( ucfirst( $competition_type ) ); ?></option>
					<?php } ?>
				</select>
				<label for="competition_type"><?php esc_html_e( 'Mode', 'racketmanager' ); ?></label>
			</div>
			<input type="hidden" name="addCompetition" value="competition" />
			<input type="submit" name="addCompetition" value="<?php esc_html_e( 'Add Competition', 'racketmanager' ); ?>" class="btn btn-primary" />

		</form>

	</div>
</div>
