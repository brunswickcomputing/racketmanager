<?php
/**
 * Function to uninstall plugin
 *
 * @package Racketmananger
 */

namespace Racketmanager;

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

racketmanager_uninstall_plugin();

/**
 * Uninstall plugin function
 *
 * @return void
 */
function racketmanager_uninstall_plugin(): void {
	global $wpdb;
	$table_prefix = $wpdb->prefix . 'racketmanager%';
	$tables       = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->prepare(
			"show tables from " . DB_NAME . " like %s",
			$table_prefix
		)
	);
	foreach ( $tables as $table ) {
		foreach ( $table as $t ) {
			$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					'DROP TABLE %s', //phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
					$t
				)
			);
		}
	}

	delete_option( 'racketmanager' );

	/*
	* Remove Capabilities
	*/
	$role = get_role( 'administrator' );
	if ( null !== $role ) {
		$role->remove_cap( 'racketmanager_settings' );
		$role->remove_cap( 'view_leagues' );
		$role->remove_cap( 'edit_leagues' );
		$role->remove_cap( 'edit_league_settings' );
		$role->remove_cap( 'del_leagues' );
		$role->remove_cap( 'edit_seasons' );
		$role->remove_cap( 'del_seasons' );
		$role->remove_cap( 'edit_teams' );
		$role->remove_cap( 'del_teams' );
		$role->remove_cap( 'edit_matches' );
		$role->remove_cap( 'del_matches' );
		$role->remove_cap( 'update_results' );
		$role->remove_cap( 'export_leagues' );
		$role->remove_cap( 'import_leagues' );
		$role->remove_cap( 'manage_racketmanager' );

		// old rules.
		$role->remove_cap( 'racketmanager' );
		$role->remove_cap( 'racket_manager' ); // temporary rule.
	}

	$role = get_role( 'editor' );
	if ( null !== $role ) {
		$role->remove_cap( 'view_leagues' );
		// old rules.
		$role->remove_cap( 'racketmanager' );
	}
}
