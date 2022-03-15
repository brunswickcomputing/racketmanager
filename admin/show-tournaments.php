<?php
/**
* Tournaments main page administration panel
*
*/
namespace ns;
?>
<div class="container">
	<h1><?php _e( 'Tournaments', 'racketmanager' ) ?></h1>

	<form id="tournaments-filter" method="post" action="">
		<?php wp_nonce_field( 'tournaments-bulk' ) ?>
		<div class="tablenav">
			<!-- Bulk Actions -->
			<select name="action" size="1">
				<option value="-1" selected="selected"><?php _e('Bulk Actions') ?></option>
				<option value="delete"><?php _e('Delete')?></option>
			</select>
			<input type="submit" value="<?php _e('Apply'); ?>" name="doTournamentDel" id="doTournamentDel" class="btn btn-secondary action" />
		</div>
		<div class="container">
			<div class="row table-header">
				<div class="col-12 col-md-auto check-column"><input type="checkbox" onclick="Racketmanager.checkAll(document.getElementById('tournaments-filter'));" /></div>
				<div class="col-12 col-md-2"><?php _e( 'Name', 'racketmanager' ) ?></div>
				<div class="col-12 col-md-1"><?php _e( 'Season', 'racketmanager' ) ?></div>
				<div class="col-12 col-md-2"><?php _e( 'Venue', 'racketmanager' ) ?></div>
				<div class="col-12 col-md-1"><?php _e( 'Date', 'racketmanager' ) ?></div>
			</div>
			<?php if ( $tournaments = $racketmanager->getTournaments( array( 'orderby' => array('date' => 'desc', 'name' => 'asc')) ) ) {
				$class = ''; ?>
				<?php foreach ( $tournaments AS $tournament ) {
					$class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
					<div class="row table-row <?php echo $class ?>">
						<div class="col-12 col-md-auto check-column">
							<input type="checkbox" value="<?php echo $tournament->id ?>" name="tournament[<?php echo $tournament->id ?>]" />
						</div>
						<div class="col-12 col-md-2"><a href="admin.php?page=racketmanager&amp;subpage=tournament&amp;tournament_name=<?php echo $tournament->name ?> "><?php echo $tournament->name ?></a></div>
						<div class="col-12 col-md-1"><?php echo $tournament->season ?></div>
						<div class="col-12 col-md-2"><?php echo $tournament->venueName ?></div>
						<div class="col-12 col-md-1"><?php echo $tournament->date ?></div>
						<div class="col-12 col-md-2"><a href="admin.php?page=racketmanager&amp;subpage=show-competitions&amp;season=<?php echo $tournament->season ?>&amp;type=<?php echo $tournament->type ?>&amp;competitiontype=tournament" class="btn btn-secondary"><?php _e( 'Competitions', 'racketmanager' ) ?></a></div>
						<?php if ( $tournament->open ) { ?>
							<div class="col-12 col-md-auto"><a class="btn btn-secondary" onclick="Racketmanager.notifyTournamentEntryOpen('<?php echo seoUrl($tournament->name) ?>');"><?php _e( 'Notify open', 'racketmanager' ) ?></a></div>
							<div class="col-12 col-md-auto"><span id="notifyMessage-<?php echo seoURL($tournament->name) ?>"></span></div>
						<?php } ?>
					</div>
				<?php } ?>
			<?php } ?>
		</form>
	</div>
	<!-- Add New Tournament -->
	<a href="admin.php?page=racketmanager&amp;subpage=tournament" name="addTournament" class="btn btn-primary submit"><?php _e( 'Add Tournament','racketmanager' ) ?></a>
</div>
