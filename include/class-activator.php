<?php
defined( 'ABSPATH' ) or die( "Access denied !" );

/**
 * Class for tasks to do during plugin activation and deactivation phases
 *
 * @package racketmanager
 * @subpackage admin
 * @since 1.0.0
 * @author Paul Moffat
 *
 */

class Racketmanager_Activator {

  /**
	* Constructor
	*/
	public function setup() {
    register_activation_hook( RACKETMANAGER_PLUGIN_FILE, array(&$this, 'activate') );
		register_deactivation_hook( RACKETMANAGER_PLUGIN_FILE, array(&$this, 'deactivate') );
  }

  /**
  * Activate plugin
  */
  public function activate() {
    $options = get_option('leaguemanager');
    if ( !$options ) {
      $colorWhite = '#FFFFFF';
      $options = array();
      $options['version'] = RACKETMANAGER_VERSION;
      $options['dbversion'] = RACKETMANAGER_DBVERSION;
      $options['textdomain'] = 'default';
      $options['colors']['headers'] = '#dddddd';
      $options['colors']['rows'] = array( 'main' => $colorWhite, 'alternate' => '#efefef', 'ascend' => $colorWhite, 'descend' => $colorWhite, 'relegate' => $colorWhite);

      add_option( 'leaguemanager', $options, '', 'yes' );
    }

    // create directory
    wp_mkdir_p(Racketmanager_Util::getFilePath());

    /*
    * Set Capabilities
    */
    $role = get_role('administrator');
    if ( $role !== null ) {
      $role->add_cap('view_leagues');
      $role->add_cap('racketmanager_settings');
      $role->add_cap('edit_leagues');
      $role->add_cap('edit_league_settings');
      $role->add_cap('del_leagues');
      $role->add_cap('edit_seasons');
      $role->add_cap('del_seasons');
      $role->add_cap('edit_teams');
      $role->add_cap('del_teams');
      $role->add_cap('edit_matches');
      $role->add_cap('del_matches');
      $role->add_cap('update_results');
      $role->add_cap('export_leagues');
      $role->add_cap('import_leagues');
      $role->add_cap('manage_racketmanager');

      // old rules
      $role->add_cap('racketmanager');
      $role->add_cap('racket_manager');
    }

    $role = get_role('editor');
    if ( $role !== null ) {
      $role->add_cap('racket_manager');
    }

    $this->addPages();

    $this->install();
  }

  public function addPages() {
		$this->createLoginPages();
		$this->createBasicPages();
	}

	/**
	* Create login pages
	*/
	public function createLoginPages() {
		// Information needed for creating the plugin's login/account pages
		$pageDefinitions = array(
			'member-login' => array(
				'title' => __( 'Sign In', 'racketmanager' ),
				'page_template' => 'No title',
				'content' => '[custom-login-form]'
			),
			'member-account' => array(
				'title' => __( 'Your Account', 'racketmanager' ),
				'page_template' => 'Member account',
				'content' => '[account-info]'
			),
			'member-password-lost' => array(
				'title' => __( 'Forgot Your Password?', 'racketmanager' ),
				'page_template' => 'No title',
				'content' => '[custom-password-lost-form]'
			),
			'member-password-reset' => array(
				'title' => __( 'Pick a New Password', 'racketmanager' ),
				'page_template' => 'No title',
				'content' => '[custom-password-reset-form]'
			)
		);
		Racketmanager_Util::addRacketManagerPage($pageDefinitions);
	}

	/**
	* Create basic pages
	*/
	public function createBasicPages() {
		// Information needed for creating the plugin's basic pages
		$pageDefinitions = array(
			'daily-matches-page' => array(
				'title' => __( 'Daily Matches', 'racketmanager' ),
				'page_template' => 'No title',
				'content' => '[dailymatches]'
			),
			'latest-results-page' => array(
				'title' => __( 'Latest Results', 'racketmanager' ),
				'page_template' => 'No title',
				'content' => '[latestresults]'
			),
			'clubs-page' => array(
				'title' => __( 'Clubs', 'racketmanager' ),
				'page_template' => 'No title',
				'content' => '[clubs]'
			),
			'club-page' => array(
				'title' => __( 'Club', 'racketmanager' ),
				'page_template' => 'No title',
				'content' => '[club]'
			),
			'match-page' => array(
				'title' => __( 'Match', 'racketmanager' ),
				'page_template' => 'No title',
				'content' => '[match]'
			)
		);

		Racketmanager_Util::addRacketManagerPage($pageDefinitions);

	}

  /**
	* Install plugin
	*/
	public function install() {
		global $wpdb;
		include_once( ABSPATH.'/wp-admin/includes/upgrade.php' );

		$charsetCollate = '';
		if ( $wpdb->has_cap( 'collation' ) ) {
			if ( ! empty($wpdb->charset) ) {
				$charsetCollate = "DEFAULT CHARACTER SET $wpdb->charset";
			}
			if ( ! empty($wpdb->collate) ) {
				$charsetCollate .= " COLLATE $wpdb->collate";
			}
		}

		$createLeaguesSql = "CREATE TABLE {$wpdb->racketmanager} ( `id` int( 11 ) NOT NULL AUTO_INCREMENT, `settings` longtext NOT NULL, `seasons` longtext NOT NULL, `competition_id` int( 11) NOT null default 0, PRIMARY KEY ( `id` )) $charsetCollate;";
		maybe_create_table( $wpdb->racketmanager, $createLeaguesSql );

		$createMatchesSql = "CREATE TABLE {$wpdb->racketmanager_matches} ( `id` int( 11 ) NOT NULL AUTO_INCREMENT , `group` varchar( 30 ) NOT NULL default '', `date` datetime NOT NULL, `home_team` varchar( 255 ) NOT NULL default 0, `away_team` varchar( 255 ) NOT NULL default 0, `match_day` tinyint( 4 ) NOT NULL default '0', `location` varchar( 100 ) NOT NULL default '', `league_id` int( 11 ) NOT NULL default '0', `season` varchar( 255 ) NOT NULL default '', `home_points` varchar( 30 ) NULL default NULL, `away_points` varchar( 30 ) NULL default NULL, `winner_id` int( 11 ) NOT NULL default '0', `loser_id` int( 11 ) NOT NULL default '0', `post_id` int( 11 ) NOT NULL default '0', `final` varchar( 150 ) NOT NULL default '', `custom` longtext NOT NULL, `updated_user` int( 11 ) NULL, `updated` datetime NULL, `confirmed` varchar( 1 ) NULL, `home_captain` int( 11 ) NULL, `away_captain` int( 11 ) NULL, `comments` varchar( 500 ) NULL, PRIMARY KEY ( `id` ), INDEX( `league_id` )) $charsetCollate;";
		maybe_create_table( $wpdb->racketmanager_matches, $createMatchesSql );

		$createRubbersSql = "CREATE TABLE {$wpdb->racketmanager_rubbers} ( `id` int( 11 ) NOT NULL AUTO_INCREMENT , `group` varchar( 30 ) NOT NULL default '', `date` datetime NOT NULL, `match_id` int( 11 ) NOT NULL default '0', `rubber_number` int( 1 ) NOT NULL default 0, `home_player_1` int( 11 ) NULL default NULL, `home_player_2` int( 11 ) NULL default NULL, `away_player_1` int( 11 ) NULL default NULL, `away_player_2` int( 11 ) NULL default NULL, `home_points` varchar( 30 ) NULL default NULL, `away_points` varchar( 30 ) NULL default NULL, `winner_id` int( 11 ) NOT NULL default '0', `loser_id` int( 11 ) NOT NULL default '0', `post_id` int( 11 ) NOT NULL default '0', `final` varchar( 150 ) NOT NULL default '', `type` varchar( 2 ) NULL default NULL, `custom` longtext NOT NULL, PRIMARY KEY ( `id` ), INDEX( `home_player_1` ), INDEX( `home_player_2` ), INDEX( `away_player_1` ), INDEX( `away_player_2` ), INDEX( `match_id` )) $charsetCollate;";
		maybe_create_table( $wpdb->racketmanager_rubbers, $createRubbersSql );

		$createClubPlayersSql = "CREATE TABLE {$wpdb->racketmanager_club_players} (  `removed_date` date NULL, `removed_user` int( 11 ) NULL, `updated` int( 1 ) NOT NULL, `system_record` VARCHAR(1) NULL DEFAULT NULL, `created_date` date NULL, `created_user` int( 11 ) NULL, PRIMARY KEY ( `id` )) $charsetCollate;";
		maybe_create_table( $wpdb->racketmanager_club_players, $createClubPlayersSql );

		$createCompetitionsSql = "CREATE TABLE {$wpdb->racketmanager_competitions} ( `id` int( 11 ) NOT NULL AUTO_INCREMENT, `name` varchar( 255 ) NOT NULL default '', `num_sets` int( 1 ) NOT NULL default 0, `num_rubbers` int( 1 ) NOT NULL default 0, `type` varchar( 2 ) NOT NULL default '', `settings` longtext NOT NULL, `seasons` longtext NOT NULL, `competitiontype` varchar( 255 ) NOT NULL default '', PRIMARY KEY ( `id` )) $charsetCollate;";
		maybe_create_table( $wpdb->racketmanager_competitions, $createCompetitionsSql );

		$createTableSql = "CREATE TABLE {$wpdb->racketmanager_table} ( `id` int( 11 ) NOT NULL AUTO_INCREMENT , `team_id` int( 11 ) NOT NULL, `league_id` int( 11 ) NOT NULL, `season` varchar( 255 ) NOT NULL default '', `points_plus` float NOT NULL default '0', `points_minus` float NOT NULL default '0', `points2_plus` int( 11 ) NOT NULL default '0', `points2_minus` int( 11 ) NOT NULL default '0', `add_points` float NOT NULL default '0', `done_matches` int( 11 ) NOT NULL default '0', `won_matches` int( 11 ) NOT NULL default '0', `draw_matches` int( 11 ) NOT NULL default '0', `lost_matches` int( 11 ) NOT NULL default '0', `diff` int( 11 ) NOT NULL default '0', `group` varchar( 30 ) NOT NULL default '', `rank` int( 11 ) NOT NULL default '0', `profile` int( 11 ) NOT NULL default '0', `status` varchar( 50 ) NOT NULL default '&#8226;', `custom` longtext NOT NULL, PRIMARY KEY ( `id` )) $charsetCollate;";
		maybe_create_table( $wpdb->racketmanager_table, $createTableSql );

		$createTeamsSql = "CREATE TABLE {$wpdb->racketmanager_teams} ( `id` int( 11 ) NOT NULL AUTO_INCREMENT, `title` varchar( 100 ) NOT NULL default '', `captain` varchar( 255 ) NOT NULL default '', `contactno` varchar( 255 ) NOT NULL default '', `contactemail` varchar( 255 ) NOT NULL default '', `affiliatedclub` int( 11 ) NOT NULL default 0, `match_day` varchar( 25 ) NOT NULL default '', `match_time` time NULL, `stadium` varchar( 150 ) NOT NULL default '', `home` tinyint( 1 ) NOT NULL default '0', `roster` longtext NOT NULL default '', `profile` int( 11 ) NOT NULL default '0', `custom` longtext NOT NULL, `type` varchar( 2 ) NOT NULL default '', PRIMARY KEY ( `id` )) $charsetCollate;";
		maybe_create_table( $wpdb->racketmanager_teams, $createTeamsSql );

		$createTeamCompetitionSql = "CREATE TABLE {$wpdb->racketmanager_team_competition} ( `id` int( 11 ) NOT NULL AUTO_INCREMENT , `team_id` int( 11 ) NOT NULL default 0, `competition_id` int( 11 ) NOT NULL default 0, `captain` varchar( 255 ) NOT NULL default '', `contactno` varchar( 255 ) NOT NULL default '', `contactemail` varchar( 255 ) NOT NULL default '', `match_day` varchar( 25 ) NOT NULL default '', `match_time` time NULL, PRIMARY KEY ( `id` ), INDEX( `team_id` ), INDEX( `competition_id` ) ) $charsetCollate;";
		maybe_create_table( $wpdb->racketmanager_team_competition, $createTeamCompetitionSql );

		$createPlayerRequestsSql = "CREATE TABLE {$wpdb->racketmanager_club_player_requests} ( `id` int( 11 ) NOT NULL AUTO_INCREMENT, `affiliatedclub` int( 11 ) NOT NULL default 0, `first_name` varchar( 255 ) NOT NULL default '', `surname` varchar( 255 ) NOT NULL default '', `gender` varchar( 1 ) NOT NULL default '', `btm` int( 11 ) NULL , `email` varchar( 255 ) NULL,  player_id` int( 11 ) NOT NULL default 0, `requested_date` date NULL, `requested_user` int( 11 ), `completed_date` date NULL, `completed_user` int( 11 ) NULL, PRIMARY KEY ( `id` )) $charsetCollate;";
		maybe_create_table( $wpdb->racketmanager_club_player_requests, $createPlayerRequestsSql );

		$createClubsSql = "CREATE TABLE {$wpdb->racketmanager_clubs} ( `id` int( 11 ) NOT NULL AUTO_INCREMENT, `name` varchar( 100 ) NOT NULL default '', `website` varchar( 100 ) NOT NULL default '', `type` varchar( 20 ) NOT NULL default '', `address` varchar( 255 ) NOT NULL default '', `latitude` varchar( 20 ) NOT NULL default '', `longitude` varchar( 20 ) NOT NULL default '', `contactno` varchar( 20 ) NOT NULL default '', `founded` int( 4 ) NULL, `facilities` varchar( 255 ) NOT NULL default '', `shortcode` varchar( 20 ) NOT NULL default '', `matchsecretary` int( 11 ) NULL, PRIMARY KEY ( `id` )) $charsetCollate;";
		maybe_create_table( $wpdb->racketmanager_clubs, $createClubsSql );

		$createSeasonsSql = "CREATE TABLE {$wpdb->racketmanager_seasons} ( `id` int( 11 ) NOT NULL AUTO_INCREMENT, `name` varchar( 100 ) NOT NULL default '', PRIMARY KEY ( `id` )) $charsetCollate;";
		maybe_create_table( $wpdb->racketmanager_seasons, $createSeasonsSql );

		$createCompetitionsSeasonsSql = "CREATE TABLE {$wpdb->racketmanager_competitions_seasons} ( `id` int( 11 ) NOT NULL AUTO_INCREMENT, `competition_id` int( 11 ) NOT NULL, `season_id` int( 11 ) NOT NULL, PRIMARY KEY ( `id` )) $charsetCollate;";
		maybe_create_table( $wpdb->racketmanager_competitions_seasons, $createCompetitionsSeasonsSql );

		$createResultsCheckerSql = "CREATE TABLE {$wpdb->racketmanager_results_checker} ( `id` int( 11 ) NOT NULL AUTO_INCREMENT , `league_id` int( 11 ) NOT NULL default '0', `match_id` int( 11 ) NOT NULL default '0', `team_id` int( 11 ) NULL, `player_id` int( 11 ) NULL, `description` varchar( 255 ) NULL, `status` int( 1 ) NULL, `updated_user` int( 11 ) NULL, `updated_date` datetime NULL, PRIMARY KEY ( `id` )) $charsetCollate;";
		maybe_create_table( $wpdb->racketmanager_results_checker, $createResultsCheckerSql );

		$createTournamentsSql = "CREATE TABLE {$wpdb->racketmanager_tournaments} ( `id` int( 11 ) NOT NULL AUTO_INCREMENT, `name` varchar( 100 ) NOT NULL default '', `type` varchar( 100 ) NOT NULL default '', `season` varchar( 255 ) NOT NULL default '', `venue` int( 11 ) NULL, `date` date NULL, `closingdate` date NOT NULL, `tournamentsecretary` int( 11 ) NULL, numcourts int( 1) NULL, starttime time NULL, timeincrement time NULL, orderofplay longtext NULL, (PRIMARY KEY ( `id` )) $charsetCollate;";
		maybe_create_table( $wpdb->racketmanager_tournaments, $createTournamentsSql );

		$createChargesSql = "CREATE TABLE {$wpdb->racketmanager_charges} ( `id` int( 11 ) NOT NULL AUTO_INCREMENT, `competitionType` varchar(255), `type` varchar( 100 ) NOT NULL default '', `season` varchar( 255 ) NOT NULL default '', `date` date NULL, `status` varchar( 50 ) NOT NULL default '', ADD `feeClub` decimal(10,2), ADD `feeTeam` decimal(10,2), PRIMARY KEY ( `id` )) $charset_collate;";
		maybe_create_table( $wpdb->racketmanager_charges, $createChargesSql );

		$createInvoicesSql = "CREATE TABLE {$wpdb->racketmanager_invoices} ( `id` int( 11 ) NOT NULL AUTO_INCREMENT, `charge_id` int( 11 ) NOT NULL, `club_id` int( 11 ) NOT NULL, `invoiceNumber` int( 11 ) NOT NULL, `status` varchar( 50 ) NOT NULL, `date` date, `date_due` date, PRIMARY KEY ( `id` )) $charset_collate;";
		maybe_create_table( $wpdb->racketmanager_invoices, $createInvoicesSql );
	}

  /**
  * Deactivate plugin
  */
  public function deactivate() {
	}

}
