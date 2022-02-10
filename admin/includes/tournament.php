<?php
?>
<!-- <script type="text/javascript" src="/wp-includes/js/jquery/jquery.js"></script> -->
<div class="wrap league-block">
	<p class="racketmanager_breadcrumb"><a href="admin.php?page=racketmanager"><?php _e( 'RacketManager', 'racketmanager' ) ?></a> &raquo; <?php echo $form_title ?></p>
	<h1><?php printf(  $form_title ); ?></h1>


	<form action="admin.php?page=racketmanager&amp;view=tournaments<?php if ( $tournament->id !== '' ) { ?>&amp;tournament_id=<?php echo $tournament->id ?> <?php } ?>" method="post" enctype="multipart/form-data" name="tournament_edit">

		<?php if ( $edit ) { ?>
			<?php wp_nonce_field( 'racketmanager_manage-tournament' ) ?>
		<?php } else { ?>
			<?php wp_nonce_field( 'racketmanager_add-tournament' ) ?>
		<?php } ?>

		<div class="form-group">
			<label for="tournament"><?php _e( 'Name', 'racketmanager' ) ?></label>
			<div class="input">
				<input type="text" id="tournament" name="tournament" value="<?php echo $tournament->name ?>" size="30" placeholder="<?php _e( 'Add tournament', 'racketmanager' ) ?>""/>
			</div>
		</div>
		<div class="form-group">
			<label for="type"><?php _e( 'Type', 'racketmanager' ) ?></label>
			<div class="input">
				<select size="1" name="type" id="type" >
					<option><?php _e( 'Select type' , 'racketmanager') ?></option>
					<option value="summer" <?php selected( 'summer', $tournament->type ) ?>><?php _e( 'Summer', 'racketmanager') ?></option>
					<option value="winter" <?php selected( 'winter', $tournament->type ) ?>><?php _e( 'Winter', 'racketmanager') ?></option>
				</select>
			</div>
		</div>
		<div class="form-group">
			<label for="type"><?php _e( 'Season', 'racketmanager' ) ?></label>
			<div class="input">
				<select size="1" name="season" id="season" >
					<option><?php _e( 'Select season' , 'racketmanager') ?></option>
					<?php $seasons = $racketmanager->getSeasons( "DESC" );
					foreach ( $seasons AS $season ) { ?>
						<option value="<?php echo $season->name ?>" <?php selected( $season->name, $tournament->season ) ?>><?php echo $season->name ?></option>
					<?php } ?>
				</select>
			</div>
		</div>
		<div class="form-group">
			<label for="venue"><?php _e( 'Venue', 'racketmanager' ) ?></label>
			<div class="input">
				<select size="1" name="venue" id="venue" >
					<option><?php _e( 'Select venue' , 'racketmanager') ?></option>
					<?php foreach ( $clubs AS $club ) { ?>
						<option value="<?php echo $club->id ?>"<?php if ( isset($tournament->venue) ) selected($tournament->venue, $club->id) ?>><?php echo $club->name ?></option>
					<?php } ?>
				</select>
			</div>
		</div>
		<div class="form-group">
			<label for="date"><?php _e( 'Date', 'racketmanager' ) ?></label>
			<div class="input">
				<input type="date" name="date" id="date" value="<?php echo $tournament->date ?>" size="20" />
			</div>
		</div>
		<div class="form-group">
			<label for="closingdate"><?php _e( 'Closing Date', 'racketmanager' ) ?></label>
			<div class="input">
				<input type="date" name="closingdate" id="closingdate" value="<?php echo $tournament->closingdate ?>" size="20" />
			</div>
		</div>
		<div class="form-group">
			<label for="tournamentSecretaryName"><?php _e( 'Tournament secretary', 'racketmanager' ) ?></label>
			<div class="input">
				<input type="text" name="tournamentSecretaryName" id="tournamentSecretaryName" autocomplete="name off" value="<?php echo $tournament->tournamentSecretaryName ?>" size="40" /><input type="hidden" name="tournamentSecretary" id="tournamentSecretary" value="<?php echo $tournament->tournamentsecretary ?>" />
			</div>
		</div>
		<div class="form-group">
			<label for="tournamentSecretaryContactNo"><?php _e( 'Tournament secretary contact', 'racketmanager' ) ?></label>
			<div class="input">
				<input type="tel" name="tournamentSecretaryContactNo" id="tournamentSecretaryContactNo" autocomplete="tel" value="<?php echo $tournament->tournamentSecretaryContactNo ?>" size="20" />
			</div>
		</div>
		<div class="form-group">
			<label for="tournamentSecretaryEmail"><?php _e( 'Tournament secretary email', 'racketmanager' ) ?></label>
			<div class="input">
				<input type="email" name="tournamentSecretaryEmail" id="tournamentSecretaryEmail" autocomplete="email" value="<?php echo $tournament->tournamentSecretaryEmail ?>" size="60" />
			</div>
		</div>
		<?php do_action( 'tournament_edit_form', $tournament ) ?>

		<input type="hidden" name="tournament_id" id="tournament_id" value="<?php echo $tournament->id ?>" />
		<input type="hidden" name="updateLeague" value="tournament" />

		<?php if ( $edit ) { ?>
			<input type="hidden" name="editTournament" value="tournament" />
		<?php } else { ?>
			<input type="hidden" name="addTournament" value="tournament" />
		<?php } ?>

		<p class="submit"><input type="submit" name="action" value="<?php echo $form_action ?>" class="button button-primary" /></p>
	</form>

</div>
