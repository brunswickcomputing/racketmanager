<?php
/**
 * leaguemanager_upgrade() - update routine for older version
 * 
 * @return Success Message
 */
function leaguemanager_upgrade() {
	global $wpdb, $leaguemanager, $lmLoader;
	
	$options = get_option( 'leaguemanager' );
	$installed = $options['dbversion'];
	
	echo __('Upgrade database structure...', 'leaguemanager');
	$wpdb->show_errors();

	$lmLoader->install();

	if (version_compare($installed, '5.1.7', '<')) {

		$wpdb->query( "ALTER TABLE {$wpdb->leaguemanager_teams} ADD `system_record` VARCHAR(1) NULL DEFAULT NULL AFTER `removed_date` ");
	
    }
	/*
	* Update version and dbversion
	*/
	$options['dbversion'] = LEAGUEMANAGER_DBVERSION;
	$options['version'] = LEAGUEMANAGER_VERSION;
	
	update_option('leaguemanager', $options);
	echo __('finished', 'leaguemanager') . "<br />\n";
	$wpdb->hide_errors();
	return;
}


/**
* leaguemanager_upgrade_page() - This page showsup , when the database version doesn't fit to the script LEAGUEMANAGER_DBVERSION constant.
* 
* @return Upgrade Message
*/
function leaguemanager_upgrade_page()  {	
	$filepath    = admin_url() . 'admin.php?page=' . htmlspecialchars($_GET['page']);

	if (isset($_GET['upgrade']) && $_GET['upgrade'] == 'now') {
		leaguemanager_do_upgrade($filepath);
		return;
	}
?>
	<div class="wrap">
		<h2><?php _e('Upgrade LeagueManager', 'leaguemanager') ;?></h2>
		<p><?php _e('Your database for LeagueManager is out-of-date, and must be upgraded before you can continue.', 'leaguemanager'); ?>
		<p><?php _e('The upgrade process may take a while, so please be patient.', 'leaguemanager'); ?></p>
		<h3><a class="button" href="<?php echo $filepath;?>&amp;upgrade=now"><?php _e('Start upgrade now', 'leaguemanager'); ?>...</a></h3>
	</div>
	<?php
}


/**
 * leaguemanager_do_upgrade() - Proceed the upgrade routine
 * 
 * @param mixed $filepath
 * @return void
 */
function leaguemanager_do_upgrade($filepath) {
	global $wpdb;
?>
<div class="wrap">
	<h2><?php _e('Upgrade LeagueManager', 'leaguemanager') ;?></h2>
	<p><?php leaguemanager_upgrade();?></p>
	<p><?php _e('Upgrade successful', 'leaguemanager') ;?></p>
	<h3><a class="button" href="<?php echo $filepath;?>"><?php _e('Continue', 'leaguemanager'); ?>...</a></h3>
</div>
<?php
}


/**
 * display upgrade page for 2.9.2
 */
function leaguemanager_upgrade_292() {
	global $leaguemanager;

	if ( isset($_POST['set_season']) ) {
		$new_league = empty($_POST['new_league']) ? false : $_POST['new_league'];
		$old_season = empty($_POST['old_season']) ? false : $_POST['old_season'];

		if ( !empty($_POST['season']) ) {
			move_league_to_season( $_POST['league'], $_POST['season'], $new_league, $old_season );
			$leaguemanager->setMessage( __( 'Successfully set Season for Matches and Teams', 'leaguemanager') );
		} else {
			$leaguemanager->setMessage( __( 'Season was empty', 'leaguemanager' ), true );
		}
		$leaguemanager->printMessage();
	}

	$leagues = $leaguemanager->getLeagues();
?>
<div class="wrap">
<h2><?php _e( 'Upgrade to Version 2.9.2', 'leaguemanager' ) ?></h2>

<form action="" method="post">
<table class="lm-form-table">
<tr>
	<th scope="row"><label for="league"><?php _e( 'League', 'leaguemanager' ) ?></label></th>
	<td>
		<select id="league" name="league" size="1">
			<?php foreach ( $leagues AS $league ) : ?>
			<option value="<?php echo $league->id ?>"><?php echo $league->title ?></option>
			<?php endforeach; ?>
		</select>
	</td>
</tr>
<tr>
	<th scope="row"><label for="season"><?php _e( 'Season', 'leaguemanager' ) ?></label></th>
	<td><input type="text" name="season" id="season" size="10" /></td>
</tr>
<tr>
	<th scope="row"><label for="new_league"><?php _e( 'New League', 'leaguemanager' ) ?></label></th>
	<td>
		<select id="new_league" name="new_league" size="1">
			<option value=""><?php _e( 'Keep League', 'leaguemanager' ) ?></option>
			<?php foreach ( $leagues AS $league ) : ?>
			<option value="<?php echo $league->id ?>"><?php echo $league->title ?></option>
			<?php endforeach; ?>
		</select>
	</td>
</tr>
<tr>
	<th scope="row"><label for="old_season"><?php _e( 'Old Season', 'leaguemanager' ) ?></label></th>
	<td><input type="text" name="old_season" id="old_season" size="10" /></td>
</tr>
</table>
<p class="submit"><input type="submit" name="set_season" value="<?php _e( 'Submit' ) ?>" /></p>
</form>
</div>
<?php
}

?>
