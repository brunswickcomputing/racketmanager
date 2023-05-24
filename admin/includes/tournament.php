<?php
?>
<div class="container">
	<div class="row justify-content-end">
		<div class="col-auto racketmanager_breadcrumb">
			<a href="admin.php?page=racketmanager-tournaments"><?php _e( 'RacketManager Tournaments', 'racketmanager' ) ?></a> &raquo; <?php echo $form_title ?>
		</div>
	</div>
	<h1><?php printf(  $form_title ); ?></h1>
	<form action="admin.php?page=racketmanager-tournaments<?php if ( $tournament->id !== '' ) { ?>&amp;tournament_id=<?php echo $tournament->id ?> <?php } ?>" method="post" enctype="multipart/form-data" name="tournament_edit" class="form-control">

		<?php if ( $edit ) { ?>
			<?php wp_nonce_field( 'racketmanager_manage-tournament' ) ?>
		<?php } else { ?>
			<?php wp_nonce_field( 'racketmanager_add-tournament' ) ?>
		<?php } ?>

		<div class="form-floating mb-3">
				<input type="text" class="form-control" id="tournament" name="tournament" value="<?php echo $tournament->name ?>" size="30" placeholder="<?php _e( 'Add tournament', 'racketmanager' ) ?>" />
				<label for="tournament"><?php _e( 'Name', 'racketmanager' ) ?></label>
		</div>
		<div class="form-floating mb-3">
				<select class="form-select" size="1" name="type" id="type" >
					<option><?php _e( 'Select type' , 'racketmanager') ?></option>
					<option value="summer" <?php selected( 'summer', $tournament->type ) ?>><?php _e( 'Summer', 'racketmanager') ?></option>
					<option value="winter" <?php selected( 'winter', $tournament->type ) ?>><?php _e( 'Winter', 'racketmanager') ?></option>
				</select>
				<label for="type"><?php _e( 'Type', 'racketmanager' ) ?></label>
		</div>
		<div class="form-floating mb-3">
				<select class="form-select" size="1" name="season" id="season" >
					<option><?php _e( 'Select season' , 'racketmanager') ?></option>
					<?php $seasons = $racketmanager->getSeasons( "DESC" );
					foreach ( $seasons AS $season ) { ?>
						<option value="<?php echo $season->name ?>" <?php selected( $season->name, isset($tournament->season) ? $tournament->season : '' ) ?>><?php echo $season->name ?></option>
					<?php } ?>
				</select>
				<label for="type"><?php _e( 'Season', 'racketmanager' ) ?></label>
		</div>
		<div class="form-floating mb-3">
				<input type="date" class="form-control" name="date" id="date" value="<?php echo $tournament->date ?>" size="20" />
				<label for="date"><?php _e( 'Date', 'racketmanager' ) ?></label>
		</div>
		<div class="form-floating mb-3">
				<input type="date" class="form-control" name="closingdate" id="closingdate" value="<?php echo $tournament->closingdate ?>" size="20" />
				<label for="closingdate"><?php _e( 'Closing Date', 'racketmanager' ) ?></label>
		</div>
		<div class="form-floating mb-3">
				<select class="form-select" size="1" name="venue" id="venue" >
					<option><?php _e( 'Select venue' , 'racketmanager') ?></option>
					<?php foreach ( $clubs AS $club ) { ?>
						<option value="<?php echo $club->id ?>"<?php if ( isset($tournament->venue) ) selected($tournament->venue, $club->id) ?>><?php echo $club->name ?></option>
					<?php } ?>
				</select>
				<label for="venue"><?php _e( 'Venue', 'racketmanager' ) ?></label>
		</div>
		<div class="form-floating mb-3">
				<input type="time" class="form-control" name="starttime" id="starttime" value="<?php echo $tournament->starttime ?>" size="20" />
				<label for="starttime"><?php _e( 'Start Time', 'racketmanager' ) ?></label>
		</div>
		<div class="form-floating mb-3">
				<input type="number" class="form-control" name="numcourts" id="numcourts" value="<?php echo $tournament->numcourts ?>" />
				<label for="numcourts"><?php _e( 'Number of courts', 'racketmanager' ) ?></label>
		</div>
		<?php do_action( 'tournament_edit_form', $tournament ) ?>

		<input type="hidden" name="tournament_id" id="tournament_id" value="<?php echo $tournament->id ?>" />
		<input type="hidden" name="updateLeague" value="tournament" />

		<?php if ( $edit ) { ?>
			<input type="hidden" name="editTournament" value="tournament" />
		<?php } else { ?>
			<input type="hidden" name="addTournament" value="tournament" />
		<?php } ?>

		<input type="submit" name="action" value="<?php echo $form_action ?>" class="btn btn-primary" />
	</form>

</div>
