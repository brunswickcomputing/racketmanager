<?php

?>
<div class="container">

	<h1><?php _e( 'Racketmanager Competitions', 'racketmanager' ) ?></h1>

	<div id="competitions-table" class="league-block-container mb-3">
		<script type='text/javascript'>
		jQuery(document).ready(function(){
			activaTab('<?php echo $tab ?>');
		});
		</script>
		<div class="container">
			<!-- Nav tabs -->
			<ul class="nav nav-pills" id="myTab" role="tablist">
				<li class="nav-item" role="presentation">
					<button class="nav-link active" id="competitionscup-tab" data-bs-toggle="pill" data-bs-target="#competitionscup" type="button" role="tab" aria-controls="competitionscup" aria-selected="true"><?php _e( 'Cups', 'racketmanager' ) ?></button>
				</li>
				<li class="nav-item" role="presentation">
					<button class="nav-link" id="competitionsleague-tab" data-bs-toggle="pill" data-bs-target="#competitionsleague" type="button" role="tab" aria-controls="competitionsleague" aria-selected="false"><?php _e( 'Leagues', 'racketmanager' ) ?></button>
				</li>
				<li class="nav-item" role="presentation">
					<button class="nav-link" id="competitionstournament-tab" data-bs-toggle="pill" data-bs-target="#competitionstournament" type="button" role="tab" aria-controls="competitionstournament" aria-selected="false"><?php _e( 'Tournaments', 'racketmanager' ) ?></button>
				</li>

			</ul>
			<!-- Tab panes -->
			<div class="tab-content">

				<?php $competitionTypes = $this->getCompetitionTypes();
				foreach ( $competitionTypes AS $competitionType ) {
					$season = '';
					$type = '';
					$standalone = false;
					$competitionQuery = array( 'type' => $competitionType );
					include('includes/competitions.php');
				} ?>

			</div>
		</div>
	</div>

	<div class="container">
		<h3><?php _e( 'Add Competition', 'racketmanager' ) ?></h3>
		<!-- Add New Competition -->
		<form action="" method="post" class="form-control">
			<?php wp_nonce_field( 'racketmanager_add-competition' ) ?>
			<div class="form-group">
				<label for="competition_name"><?php _e( 'Competition', 'racketmanager' ) ?></label>
				<div class="input">
					<input required="required" placeholder="<?php _e( 'Enter name for new competition', 'racketmanager') ?>" type="text" name="competition_name" id="competition_name" value="" size="30" />
				</div>
			</div>
			<div class="form-group">
				<label for='num_sets'><?php _e('Number of Sets', 'racketmanager') ?></label>
				<div class="input">
					<input required="required" placeholder="<?php _e( 'How many sets', 'racketmanager') ?>" type='number' name='num_sets' id='num_sets' value='' size='3' />
				</div>
			</div>
			<div class="form-group">
				<label for='num_rubbers'><?php _e('Number of Rubbers', 'racketmanager') ?></label>
				<div class="input">
					<input required="required" placeholder="<?php _e( 'How many rubbers', 'racketmanager') ?>" type='number' name='num_rubbers' id='num_rubbers' value='' size='3' />
				</div>
			</div>
			<div class="form-group">
				<label for='competition_type'><?php _e('Competition Type', 'racketmanager') ?></label>
				<div class="input">
					<select size='1' required="required" name='competition_type' id='competition_type'>
						<option><?php _e( 'Select', 'racketmanager') ?></option>
						<option value='WS' <?php if ( isset($competition->type)) ($competition->type == 'WS' ? 'selected' : '') ?>>
							<?php _e( 'Ladies Singles', 'racketmanager') ?>
						</option>
						<option value='WD' <?php if ( isset($competition->type)) ($competition->type == 'WD' ? 'selected' : '') ?>>
							<?php _e( 'Ladies Doubles', 'racketmanager') ?>
						</option>
						<option value='MD' <?php if ( isset($competition->type)) ($competition->type == 'MD' ? 'selected' : '') ?>>
							<?php _e( 'Mens Doubles', 'racketmanager') ?>
						</option>
						<option value='MS' <?php if ( isset($competition->type)) ($competition->type == 'MS' ? 'selected' : '') ?>>
							<?php _e( 'Mens Singles', 'racketmanager') ?>
						</option>
						<option value='XD' <?php if ( isset($competition->type)) ($competition->type == 'XD' ? 'selected' : '') ?>>
							<?php _e( 'Mixed Doubles', 'racketmanager') ?>
						</option>
						<option value='LD' <?php if ( isset($competition->type)) ($competition->type == 'LD' ? 'selected' : '') ?>>
							<?php _e( 'The League', 'racketmanager') ?>
						</option>
					</select>
				</div>
			</div>
			<div class="form-group">
				<label for="competitiontype"><?php _e( 'Mode', 'racketmanager' ) ?></label>
				<div class="input">
					<select size="1" name="competitiontype" id="competitiontype">
						<option><?php _e( 'Select', 'racketmanager') ?></option>
						<?php foreach ( $this->getCompetitionTypes() AS $id => $competitionType ) { ?>
							<option value="<?php echo $id ?>"><?php echo ucfirst($competitionType) ?></option>
						<?php } ?>
					</select>
				</div>
			</div>
			<input type="hidden" name="addCompetition" value="competition" />
			<input type="submit" name="addCompetition" value="<?php _e( 'Add Competition','racketmanager' ) ?>" class="btn btn-primary" />

		</form>

	</div>
</div>
