<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}

racketmanager_uninstall_plugin();

public static function racketmanager_uninstall_plugin() {
  global $wpdb;

  global $wpdb;
  $tablePrefix = $wpdb->prefix.'racketmanager%';
  $tables = $wpdb->get_results($wpdb->prepare("show tables from {$wpdb->dbname} like '%s'", $tablePrefix ));
  foreach ($tables as $table) {
    foreach ($table as $t) {
      $wpdb->query($wpdb->prepare( "DROP TABLE '%s'", $t) );
    }
  }

  delete_option( 'leaguemanager' );

  /*
  * Remove Capabilities
  */
  $role = get_role('administrator');
  if ( $role !== null ) {
    $role->remove_cap('racketmanager_settings');
    $role->remove_cap('view_leagues');
    $role->remove_cap('edit_leagues');
    $role->remove_cap('edit_league_settings');
    $role->remove_cap('del_leagues');
    $role->remove_cap('edit_seasons');
    $role->remove_cap('del_seasons');
    $role->remove_cap('edit_teams');
    $role->remove_cap('del_teams');
    $role->remove_cap('edit_matches');
    $role->remove_cap('del_matches');
    $role->remove_cap('update_results');
    $role->remove_cap('export_leagues');
    $role->remove_cap('import_leagues');
    $role->remove_cap('manage_racketmanager');

    // old rules
    $role->remove_cap('racketmanager');
    $role->remove_cap('racket_manager'); // temporary rule
  }

  $role = get_role('editor');
  if ( $role !== null ) {
    $role->remove_cap('view_leagues');

    // old rules
    $role->remove_cap('racketmanager');
  }
}
