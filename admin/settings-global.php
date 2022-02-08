<?php

if ( !current_user_can( 'manage_racketmanager' ) ) {
	echo '<p style="text-align: center;">'.__("You do not have sufficient permissions to access this page.").'</p>';
} else {
	$menu_page_url = admin_url('options-general.php?page=racketmanager-settings');

	?>
	<script type='text/javascript'>
	jQuery(function() {
		jQuery("#tabs.form").tabs({
			active: <?php echo $tab ?>
		});
	});
</script>

<form action='' method='post' name='settings'>
	<?php wp_nonce_field( 'racketmanager_manage-global-league-options' ); ?>

	<div class='wrap'>
		<h1><?php _e( 'Racketmanager Global Settings', 'racketmanager' ) ?></h1>
		<div class="settings-blocks form" id="tabs">
			<input type="hidden" class="active-tab" name="active-tab" value="<?php echo $tab ?>" ?>

			<ul id="tablist" style="display: none;">
				<li><a href="#rosters"><?php _e( 'Rosters', 'racketmanager' ) ?></a></li>
				<li><a href="#players"><?php _e( 'Player Checks', 'racketmanager' ) ?></a></li>
				<li><a href="#match-results"><?php _e( 'Match Results', 'racketmanager' ) ?></a></li>
				<li><a href="#colors"><?php _e( 'Color Scheme', 'racketmanager' ) ?></a></li>
			</ul>

			<div id="rosters" class="settings-block-container">
				<h2><?php _e('Rosters', 'racketmanager') ?></h2>
				<?php include(RACKETMANAGER_PATH . '/admin/includes/settings/rosters.php'); ?>
			</div>

			<div id="players" class="settings-block-container">
				<h2><?php _e('Player Checks', 'racketmanager') ?></h2>
				<?php include(RACKETMANAGER_PATH . '/admin/includes/settings/players.php'); ?>
			</div>

			<div id="match-results" class="settings-block-container">
				<h2><?php _e('Match Results', 'racketmanager') ?></h2>
				<?php include(RACKETMANAGER_PATH . '/admin/includes/settings/results.php'); ?>
			</div>

			<div id="colors" class="settings-block-container">
				<h2><?php _e( 'Color Scheme', 'racketmanager' ) ?></h2>
				<?php include(RACKETMANAGER_PATH . '/admin/includes/settings/colors.php'); ?>
			</div>

		</div>

		<input type='hidden' name='page_options' value='color_headers,color_rows,color_rows_alt,color_rows_ascend,color_rows_descend,color_rows_relegation' />
		<p class='submit'><input type='submit' name='updateRacketManager' value='<?php _e( 'Save Preferences', 'racketmanager' ) ?>' class='button button-primary' /></p>
	</div>
</form>

<?php } ?>
