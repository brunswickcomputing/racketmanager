<?php
/*
Plugin Name: Racketmanager
Plugin URI: http://wordpress.org/extend/plugins/leaguemanager/
Description: Manage and present sports league results.
Version: 6.0.2
Author: Paul Moffat

Copyright 2008-2021  Paul Moffat (email: paul@paarcs.com)
Kolja Schleich  (email : kolja.schleich@googlemail.com)
LaMonte Forthun (email : lamontef@collegefundsoftware.com, lamontef@yahoo.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
* RacketManager is a feature-rich racket management plugin supporting various different sport types including
* - tennis
*
* @author Paul Moffat
* @package RacketManager
* @version 6.0.2
* @copyright 2008-2021
* @license GPL-3
*/

/**
* Main class to implement RacketManager
*
*/
class RacketManager {
	/**
	* plugin version
	*
	* @var string
	*/
	private $version = '6.0.2';

	/**
	* database version
	*
	* @var string
	*/
	private $dbversion = '6.0.0';

	/**
	* The array of templates that this plugin tracks.
	*/
	protected $templates;

	/**
	* constructor
	*
	* @param none
	* @return void
	*/
	public function __construct() {
		global $wpdb;

		$wpdb->show_errors();
		$this->loadOptions();
		$this->defineConstants();
		$this->defineTables();
		$this->loadTextdomain();
		$this->loadLibraries();

		register_activation_hook(__FILE__, array(&$this, 'activate') );

		if (function_exists('register_uninstall_hook')) {
			register_uninstall_hook(__FILE__, array('RacketManagerLoader', 'uninstall'));
		}

		add_action( 'widgets_init', array(&$this, 'registerWidget') );

		add_action('wp_enqueue_scripts', array(&$this, 'loadStyles'), 5 );
		add_action('wp_enqueue_scripts', array(&$this, 'loadScripts') );

		add_action( 'wp_loaded', array(&$this, 'add_racketmanager_templates') );

		add_filter( 'wp_privacy_personal_data_exporters', array(&$this, 'register_privacy_data_exporter') );

	}

	public function add_racketmanager_templates() {
		// Add your templates to this array.
		$this->templates = array(
			'templates/template_notitle.php' => 'No Title',
			'templates/template_member_account.php' => 'Member Account'
		);

		// Add a filter to the wp 4.7 version attributes metabox
		add_filter( 'theme_page_templates', array( $this, 'racketmanager_templates_as_option' ) );

		// Add a filter to the save post to inject our template into the page cache
		add_filter( 'wp_insert_post_data', array( $this, 'racketmanager_post_templates' ) );

		// Add a filter to the template include to determine if the page has our
		// template assigned and return it's path
		add_filter(	'template_include',	array( $this, 'racketmanager_load_template') );

		add_filter( 'archive_template', array( $this, 'racketmanager_archive_template') );

	}

	/**
	* Adds our templates to the page dropdown
	*
	*/
	public function racketmanager_templates_as_option( $posts_templates ) {
		return array_merge( $posts_templates, $this->templates );
	}

	/**
	* Adds our templates to the pages cache in order to trick WordPress
	* into thinking the template file exists where it doens't really exist.
	*/
	public function racketmanager_post_templates( $atts ) {

		// Create the key used for the themes cache
		$cache_key = 'page_templates-' . md5( get_theme_root() . '/' . get_stylesheet() );

		// Retrieve the cache list.
		// If it doesn't exist, or it's empty prepare an array
		$pageTemplates = wp_get_theme()->get_page_templates();
		if ( empty( $pageTemplates ) ) {
			$pageTemplates = array();
		}

		// New cache, therefore remove the old one
		wp_cache_delete( $cache_key , 'themes');

		// Now add our template to the list of templates by merging our templates
		// with the existing templates array from the cache.
		$pageTemplates = array_merge( $pageTemplates, $this->templates );

		// Add the modified cache to allow WordPress to pick it up for listing
		// available templates
		wp_cache_add( $cache_key, $pageTemplates, 'themes', 1800 );

		return $atts;

	}

	/**
	* Checks if the template is assigned to the page
	*/
	public function racketmanager_load_template( $template ) {

		// Get global post
		global $post;

		// Return template if post is empty
		if ( ! $post ) {
			return $template;
		}

		// Return default template if we don't have a custom one defined
		if ( ! isset( $this->templates[get_post_meta($post->ID, '_wp_page_template', true)] ) ) {
			return $template;
		}

		$file = plugin_dir_path( __FILE__ ). get_post_meta($post->ID, '_wp_page_template', true);

		// Just to be safe, we check if the file exist first
		if ( file_exists( $file ) ) {
			return $file;
		} else {
			echo $file;
		}

		// Return template
		return $template;

	}

	/**
	* load specific archive templates
	*/
	public function racketmanager_archive_template( $template ) {
		global $post;

		if ( is_category('rules') ) {
			$template = plugin_dir_path( __FILE__ ).'templates/pages/category-rules.php';
		}
		return $template;
	}

	public function racketmanager_privacy_exporter( $email_address, $page = 1 ) {
		$page = (int) $page;

		$data_to_export = array();

		$user = get_user_by( 'email', $email_address );
		if ( ! $user ) {
			return array(
				'data' => array(),
				'done' => true,
			);
		}

		$user_meta = get_user_meta( $user->ID );

		$user_prop_to_export = array(
			'gender'           => __( 'User Gender' ),
			'BTM'              => __( 'User BTM' ),
			'remove_date'      => __( 'User Removed Date' ),
			'contactno'        => __( 'User Contact Number' ),
		);

		$user_data_to_export = array();

		foreach ( $user_prop_to_export as $key => $name ) {
			$value = '';

			switch ( $key ) {
				case 'gender':
				case 'BTM':
				case 'remove_date':
				case 'contactno':
				$value = isset($user_meta[ $key ][0]) ? $user_meta[ $key ][0] : '';
				break;
			}

			if ( ! empty( $value ) ) {
				$user_data_to_export[] = array(
					'name'  => $name,
					'value' => $value,
				);
			}
		}

		$data_to_export[] = array(
			'group_id'    => 'user',
			'group_label' => __( 'User' ),
			'item_id'     => "user-{$user->ID}",
			'data'        => $user_data_to_export,
		);
		return array(
			'data' => $data_to_export,
			'done' => true,
		);
	}

	/**
	* register Widget
	*/
	public function registerWidget() {
		register_widget('RacketManagerWidget');
	}

	/**
	* define constants
	*
	*/
	private function defineConstants() {
		define( 'RACKETMANAGER_VERSION', $this->version );
		define( 'RACKETMANAGER_DBVERSION', $this->dbversion );
		define( 'RACKETMANAGER_URL', rtrim(esc_url(plugin_dir_url(__FILE__)), "/") ); // remove trailing slash as the plugin has been coded without it
		define( 'RACKETMANAGER_PATH', dirname(__FILE__) );
	}

	/**
	* define database tables
	*
	*/
	private function defineTables() {
		global $wpdb;
		$wpdb->racketmanager = $wpdb->prefix . 'racketmanager_leagues';
		$wpdb->racketmanager_table = $wpdb->prefix . 'racketmanager_table';
		$wpdb->racketmanager_teams = $wpdb->prefix . 'racketmanager_teams';
		$wpdb->racketmanager_matches = $wpdb->prefix . 'racketmanager_matches';
		$wpdb->racketmanager_rubbers = $wpdb->prefix . 'racketmanager_rubbers';
		$wpdb->racketmanager_roster = $wpdb->prefix . 'racketmanager_roster';
		$wpdb->racketmanager_competitions = $wpdb->prefix . 'racketmanager_competitions';
		$wpdb->racketmanager_team_competition = $wpdb->prefix . 'racketmanager_team_competition';
		$wpdb->racketmanager_roster_requests = $wpdb->prefix . 'racketmanager_roster_requests';
		$wpdb->racketmanager_clubs = $wpdb->prefix . 'racketmanager_clubs';
		$wpdb->racketmanager_seasons = $wpdb->prefix . 'racketmanager_seasons';
		$wpdb->racketmanager_competitions_seasons = $wpdb->prefix . 'racketmanager_competitions_seasons';
		$wpdb->racketmanager_results_checker = $wpdb->prefix . 'racketmanager_results_checker';
		$wpdb->racketmanager_tournaments = $wpdb->prefix . 'racketmanager_tournaments';
	}

	/**
	* load libraries
	*
	*/
	private function loadLibraries() {
		global $racketmanager_shortcodes, $racketmanager_login;

		// Objects
		require_once (dirname (__FILE__) . '/lib/club.php');
		require_once (dirname (__FILE__) . '/lib/championship.php');
		require_once (dirname (__FILE__) . '/lib/competition.php');
		require_once (dirname (__FILE__) . '/lib/league.php');
		require_once (dirname (__FILE__) . '/lib/leagueteam.php');
		require_once (dirname (__FILE__) . '/lib/match.php');
		require_once (dirname (__FILE__) . '/lib/svg-icons.php');
		require_once (dirname (__FILE__) . '/lib/team.php');

		/*
		* load sports libraries
		*/
		// First read files in racketmanager sports directory, then overwrite with sports files in user stylesheet directory
		$files = array_merge($this->readDirectory(RACKETMANAGER_PATH."/sports"), $this->readDirectory(get_stylesheet_directory() . "/sports"));

		// load files
		foreach ( $files AS $file ) {
			require_once($file);
		}

		// Global libraries
		require_once (dirname (__FILE__) . '/lib/ajax.php');
		require_once (dirname (__FILE__) . '/lib/login.php');
		require_once (dirname (__FILE__) . '/lib/shortcodes.php');
		require_once (dirname (__FILE__) . '/lib/widget.php');

		// template tags & functions
		require_once(dirname(__FILE__) . '/template-tags.php');
		require_once (dirname (__FILE__) . '/functions.php');

		$racketmanager_ajax = new RacketManagerAJAX();

		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		$racketmanager_shortcodes = new RacketManagerShortcodes();
		$racketmanager_login = new RacketManagerLogin();
	}

	/**
	* get standings display options
	*
	* @return array
	*/
	public function getStandingsDisplayOptions() {
		$options = array(
			'status' => __( 'Team Status', 'racketmanager' ),
			'team_link' => __( 'Include Link to team page', 'racketmanager' ),
			'pld' => __( 'Played Games', 'racketmanager' ),
			'won' => __( 'Won Games', 'racketmanager' ),
			'tie' => __('Tie Games', 'racketmanager' ),
			'lost' => __( 'Lost Games', 'racketmanager' ),
			'winPercent' => __( 'Win Percentage', 'racketmanager' ),
			'last5' => __( 'Last 5 Matches', 'racketmanager' )
		);

		/**
		* Fires when standings options are generated
		*
		* @param array $options
		* @return array
		* @category wp-filter
		*/
		return apply_filters('competition_standings_options', $options);

	}

	/**
	* read files in directory
	*
	* @param string $dir
	* @return array
	*/
	public function readDirectory($dir) {
		$files = array();

		if ( file_exists($dir)  && ( $handle = opendir($dir) ) ) {
			while ( false !== ($file = readdir($handle)) ) {
				$file_info = pathinfo($dir.'/'.$file);
				$file_type = (isset($file_info['extension'])) ? $file_info['extension'] : '';
				if ( $file != "." && $file != ".." && !is_dir($file) && substr($file, 0,1) != "."  && $file_type == 'php' )  {
					$files[$file] = $dir.'/'.$file;
				}
			}
		}

		return $files;
	}

	/**
	* load options
	*
	*/
	private function loadOptions() {
		$this->options = get_option('leaguemanager');
	}

	/**
	* get options
	*
	* @param boolean $index (optional)
	*/
	public function getOptions($index = false) {
		if ( $index ) {
			return $this->options[$index];
		} else {
			return $this->options;
		}
	}

	/**
	* load textdomain
	*
	*/
	private function loadTextdomain() {
		global $racketmanager;

		$textdomain = $this->getOptions('textdomain');
		if ( !empty($textdomain) ) {
			$locale = get_locale();
			$path = dirname(__FILE__) . '/languages';
			$domain = 'racketmanager';
			$mofile = $path . '/'. $domain . '-' . $textdomain . '-' . $locale . '.mo';

			if ( file_exists($mofile) ) {
				load_textdomain($domain, $mofile);
				return true;
			}
		}

		load_plugin_textdomain( 'racketmanager', false, 'racketmanager/languages' );
	}

	/**
	* load Javascript
	*
	*/
	public function loadScripts() {
		wp_register_script( 'datatables', 'https://cdn.datatables.net/v/ju/dt-1.11.3/fh-3.2.0/datatables.min.js', array('jquery') );
		wp_register_script( 'racketmanager', RACKETMANAGER_URL.'/racketmanager.js', array('jquery', 'jquery-ui-core', 'jquery-ui-accordion', 'jquery-ui-autocomplete', 'jquery-ui-tabs', 'jquery-effects-core', 'jquery-effects-slide', 'sack', 'thickbox'), RACKETMANAGER_VERSION );
		wp_enqueue_script('racketmanager');
		?>
		<script type="text/javascript">
		//<![CDATA[
		RacketManagerAjaxL10n = {
			blogUrl: "<?php bloginfo( 'wpurl' ); ?>",
			pluginUrl: "<?php echo RACKETMANAGER_URL; ?>",
			requestUrl: "<?php echo admin_url( 'admin-ajax.php' ) ?>",
			Edit: "<?php _e("Edit"); ?>",
			Post: "<?php _e("Post"); ?>",
			Save: "<?php _e("Save"); ?>",
			Cancel: "<?php _e("Cancel"); ?>",
			pleaseWait: "<?php _e("Please wait..."); ?>",
			Revisions: "<?php _e("Page Revisions"); ?>",
			Time: "<?php _e("Insert time"); ?>",
			Options: "<?php _e("Options") ?>",
			Delete: "<?php _e('Delete') ?>"
		}
		//]]>
		</script>
		<?php
	}

	/**
	* load CSS styles
	*
	*/
	public function loadStyles() {
		wp_enqueue_style('thickbox');
		wp_enqueue_style('racketmanager-print', RACKETMANAGER_URL . "/css/print.css", false, RACKETMANAGER_VERSION, 'print');
		wp_enqueue_style('racketmanager-modal', RACKETMANAGER_URL . "/css/modal.css", false, RACKETMANAGER_VERSION, 'screen');
		wp_enqueue_style('racketmanager', RACKETMANAGER_URL . "/css/style.css", false, RACKETMANAGER_VERSION, 'screen');

		wp_register_style('jquery-ui', RACKETMANAGER_URL . "/css/jquery/jquery-ui.min.css", false, '1.11.4', 'all');
		wp_register_style('jquery-ui-structure', RACKETMANAGER_URL . "/css/jquery/jquery-ui.structure.min.css", array('jquery-ui'), '1.11.4', 'all');
		wp_register_style('jquery-ui-theme', RACKETMANAGER_URL . "/css/jquery/jquery-ui.theme.min.css", array('jquery-ui', 'jquery-ui-structure'), '1.11.4', 'all');
		wp_register_style('jquery-ui-autocomplete', RACKETMANAGER_URL . "/css/jquery/jquery-ui.autocomplete.min.css", array('jquery-ui', 'jquery-ui-autocomplete'), '1.11.4', 'all');
		wp_register_style('datatables-style', 'https://cdn.datatables.net/v/ju/dt-1.11.3/fh-3.2.0/datatables.min.css');

		wp_enqueue_style('jquery-ui-structure');
		wp_enqueue_style('jquery-ui-theme');

		ob_start();
		require_once(RACKETMANAGER_PATH.'/css/colors.css.php');
		$css = ob_get_contents();
		ob_end_clean();

		wp_add_inline_style( 'racketmanager', $css );
	}

	/**
	* get upload directory
	*
	* @param string|false $file
	* @return string upload path
	*/
	public function getFilePath( $file = false ) {
		$base = WP_CONTENT_DIR.'/uploads/leagues';

		if ( $file ) {
			return $base .'/'. basename($file);
		} else {
			return $base;
		}
	}

	/**
	* Activate plugin
	*/
	public function activate() {
		$options = get_option( 'leaguemanager' );
		if ( !options ) {
			$options = array();
			$options['version'] = $this->version;
			$options['dbversion'] = $this->dbversion;
			$options['textdomain'] = 'default';
			$options['colors']['headers'] = '#dddddd';
			$options['colors']['rows'] = array( 'main' => '#ffffff', 'alternate' => '#efefef', 'ascend' => '#ffffff', 'descend' => '#ffffff', 'relegate' => '#ffffff');

			add_option( 'leaguemanager', $options, '', 'yes' );
		}

		// create directory
		wp_mkdir_p($this->getFilePath());

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

		$this->add_pages();

		$this->install();
	}

	public function add_pages() {
		$this->create_login_pages();
		$this->create_basic_pages();
	}

	/**
	* Create login pages
	*/
	public function create_login_pages() {
		// Information needed for creating the plugin's login/account pages
		$page_definitions = array(
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
		$this->addRacketManagerPage($page_definitions);
	}

	/**
	* Create basic pages
	*/
	public function create_basic_pages() {
		// Information needed for creating the plugin's basic pages
		$page_definitions = array(
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

		$this->addRacketManagerPage($page_definitions);

	}

	/**
	* Add pages to database
	*/
	public function addRacketManagerPage( $page_definitions ) {

		foreach ( $page_definitions as $slug => $page ) {

			// Check that the page doesn't exist already
			if ( ! is_page($slug) ) {
				$pageTemplate = $page['page_template'];
				if ( $pageTemplate ) {
					$template = array_search( $pageTemplate, $this->templates );
					if ( $template ) {
						$pageTemplate = $template;
					}
				}
				// Add the page using the data from the array above
				$page = array(
					'post_content'   => $page['content'],
					'post_name'      => $slug,
					'post_title'     => $page['title'],
					'post_status'    => 'publish',
					'post_type'      => 'page',
					'ping_status'    => 'closed',
					'comment_status' => 'closed',
					'page_template' => $pageTemplate,
				);
				if ( $page_id = wp_insert_post( $page ) ) {
					$pageName = sanitize_title_with_dashes($page['post_title']);
					$option = 'racketmanager_page_'.$pageName.'_id';
					// Only update this option if `wp_insert_post()` was successful
					update_option( $option, $page_id );
				}
			}
		}
	}

	/**
	* deleteRacketmanagerPage
	*
	* @pageName string $name
	* @return none
	*/
	public function deleteRacketmanagerPage( $pageName ) {

		$option = 'racketmanager_page_'.$pageName.'_id';
		$pageId = intval( get_option( $option ) );

		// Force delete this so the Title/slug "Menu" can be used again.
		if ( $pageId ) {
			wp_delete_post( $pageId, true );
			delete_option($option);
		}

	}


	/**
	* Install plugin
	*/
	public function install() {
		global $wpdb;
		include_once( ABSPATH.'/wp-admin/includes/upgrade.php' );

		$charset_collate = '';
		if ( $wpdb->has_cap( 'collation' ) ) {
			if ( ! empty($wpdb->charset) )
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
			if ( ! empty($wpdb->collate) )
			$charset_collate .= " COLLATE $wpdb->collate";
		}

		$create_leagues_sql = "CREATE TABLE {$wpdb->racketmanager} ( `id` int( 11 ) NOT NULL AUTO_INCREMENT, `settings` longtext NOT NULL, `seasons` longtext NOT NULL, `competition_id` int( 11) NOT null default 0, PRIMARY KEY ( `id` )) $charset_collate;";
		maybe_create_table( $wpdb->racketmanager, $create_leagues_sql );

		$create_matches_sql = "CREATE TABLE {$wpdb->racketmanager_matches} ( `id` int( 11 ) NOT NULL AUTO_INCREMENT , `group` varchar( 30 ) NOT NULL default '', `date` datetime NOT NULL, `home_team` varchar( 255 ) NOT NULL default 0, `away_team` varchar( 255 ) NOT NULL default 0, `match_day` tinyint( 4 ) NOT NULL default '0', `location` varchar( 100 ) NOT NULL default '', `league_id` int( 11 ) NOT NULL default '0', `season` varchar( 255 ) NOT NULL default '', `home_points` varchar( 30 ) NULL default NULL, `away_points` varchar( 30 ) NULL default NULL, `winner_id` int( 11 ) NOT NULL default '0', `loser_id` int( 11 ) NOT NULL default '0', `post_id` int( 11 ) NOT NULL default '0', `final` varchar( 150 ) NOT NULL default '', `custom` longtext NOT NULL, `updated_user` int( 11 ) NULL, `updated` datetime NULL, `confirmed` varchar( 1 ) NULL, `home_captain` int( 11 ) NULL, `away_captain` int( 11 ) NULL, PRIMARY KEY ( `id` ), INDEX( `league_id` )) $charset_collate;";
		maybe_create_table( $wpdb->racketmanager_matches, $create_matches_sql );

		$create_rubbers_sql = "CREATE TABLE {$wpdb->racketmanager_rubbers} ( `id` int( 11 ) NOT NULL AUTO_INCREMENT , `group` varchar( 30 ) NOT NULL default '', `date` datetime NOT NULL, `match_id` int( 11 ) NOT NULL default '0', `rubber_number` int( 1 ) NOT NULL default 0, `home_player_1` int( 11 ) NULL default NULL, `home_player_2` int( 11 ) NULL default NULL, `away_player_1` int( 11 ) NULL default NULL, `away_player_2` int( 11 ) NULL default NULL, `home_points` varchar( 30 ) NULL default NULL, `away_points` varchar( 30 ) NULL default NULL, `winner_id` int( 11 ) NOT NULL default '0', `loser_id` int( 11 ) NOT NULL default '0', `post_id` int( 11 ) NOT NULL default '0', `final` varchar( 150 ) NOT NULL default '', `custom` longtext NOT NULL, PRIMARY KEY ( `id` ), INDEX( `home_player_1` ), INDEX( `home_player_2` ), INDEX( `away_player_1` ), INDEX( `away_player_2` ), INDEX( `match_id` )) $charset_collate;";
		maybe_create_table( $wpdb->racketmanager_rubbers, $create_rubbers_sql );

		$create_roster_sql = "CREATE TABLE {$wpdb->racketmanager_roster} (  `removed_date` date NULL, `removed_user` int( 11 ) NULL, `updated` int( 1 ) NOT NULL, `system_record` VARCHAR(1) NULL DEFAULT NULL, `created_date` date NULL, `created_user` int( 11 ) NULL, PRIMARY KEY ( `id` )) $charset_collate;";
		maybe_create_table( $wpdb->racketmanager_roster, $create_roster_sql );

		$create_competitions_sql = "CREATE TABLE {$wpdb->racketmanager_competitions} ( `id` int( 11 ) NOT NULL AUTO_INCREMENT, `name` varchar( 255 ) NOT NULL default '', `num_sets` int( 1 ) NOT NULL default 0, `num_rubbers` int( 1 ) NOT NULL default 0, `type` varchar( 2 ) NOT NULL default '', `settings` longtext NOT NULL, `seasons` longtext NOT NULL, `competitiontype` varchar( 255 ) NOT NULL default '', PRIMARY KEY ( `id` )) $charset_collate;";
		maybe_create_table( $wpdb->racketmanager_competitions, $create_competitions_sql );

		$create_table_sql = "CREATE TABLE {$wpdb->racketmanager_table} ( `id` int( 11 ) NOT NULL AUTO_INCREMENT , `team_id` int( 11 ) NOT NULL, `league_id` int( 11 ) NOT NULL, `season` varchar( 255 ) NOT NULL default '', `points_plus` float NOT NULL default '0', `points_minus` float NOT NULL default '0', `points2_plus` int( 11 ) NOT NULL default '0', `points2_minus` int( 11 ) NOT NULL default '0', `add_points` float NOT NULL default '0', `done_matches` int( 11 ) NOT NULL default '0', `won_matches` int( 11 ) NOT NULL default '0', `draw_matches` int( 11 ) NOT NULL default '0', `lost_matches` int( 11 ) NOT NULL default '0', `diff` int( 11 ) NOT NULL default '0', `group` varchar( 30 ) NOT NULL default '', `rank` int( 11 ) NOT NULL default '0', `profile` int( 11 ) NOT NULL default '0', `status` varchar( 50 ) NOT NULL default '&#8226;', `custom` longtext NOT NULL, PRIMARY KEY ( `id` )) $charset_collate;";
		maybe_create_table( $wpdb->racketmanager_table, $create_table_sql );

		$create_teams_sql = "CREATE TABLE {$wpdb->racketmanager_teams} ( `id` int( 11 ) NOT NULL AUTO_INCREMENT, `title` varchar( 100 ) NOT NULL default '', `captain` varchar( 255 ) NOT NULL default '', `contactno` varchar( 255 ) NOT NULL default '', `contactemail` varchar( 255 ) NOT NULL default '', `affiliatedclub` int( 11 ) NOT NULL default 0, `match_day` varchar( 25 ) NOT NULL default '', `match_time` time NULL, `stadium` varchar( 150 ) NOT NULL default '', `home` tinyint( 1 ) NOT NULL default '0', `roster` longtext NOT NULL default '', `profile` int( 11 ) NOT NULL default '0', `custom` longtext NOT NULL, `type` varchar( 2 ) NOT NULL default '', PRIMARY KEY ( `id` )) $charset_collate;";
		maybe_create_table( $wpdb->racketmanager_teams, $create_teams_sql );

		$create_team_competition_sql = "CREATE TABLE {$wpdb->racketmanager_team_competition} ( `id` int( 11 ) NOT NULL AUTO_INCREMENT , `team_id` int( 11 ) NOT NULL default 0, `competition_id` int( 11 ) NOT NULL default 0, `captain` varchar( 255 ) NOT NULL default '', `contactno` varchar( 255 ) NOT NULL default '', `contactemail` varchar( 255 ) NOT NULL default '', `match_day` varchar( 25 ) NOT NULL default '', `match_time` time NULL, PRIMARY KEY ( `id` ), INDEX( `team_id` ), INDEX( `competition_id` ) ) $charset_collate;";
		maybe_create_table( $wpdb->racketmanager_team_competition, $create_team_competition_sql );

		$create_roster_requests_sql = "CREATE TABLE {$wpdb->racketmanager_roster_requests} ( `id` int( 11 ) NOT NULL AUTO_INCREMENT, `affiliatedclub` int( 11 ) NOT NULL default 0, `first_name` varchar( 255 ) NOT NULL default '', `surname` varchar( 255 ) NOT NULL default '', `gender` varchar( 1 ) NOT NULL default '', `btm` int( 11 ) NULL , `player_id` int( 11 ) NOT NULL default 0, `requested_date` date NULL, `requested_user` int( 11 ), `completed_date` date NULL, `completed_user` int( 11 ) NULL, PRIMARY KEY ( `id` )) $charset_collate;";
		maybe_create_table( $wpdb->racketmanager_roster_requests, $create_roster_requests_sql );

		$create_clubs_sql = "CREATE TABLE {$wpdb->racketmanager_clubs} ( `id` int( 11 ) NOT NULL AUTO_INCREMENT, `name` varchar( 100 ) NOT NULL default '', `website` varchar( 100 ) NOT NULL default '', `type` varchar( 20 ) NOT NULL default '', `address` varchar( 255 ) NOT NULL default '', `latitude` varchar( 20 ) NOT NULL default '', `longitude` varchar( 20 ) NOT NULL default '', `contactno` varchar( 20 ) NOT NULL default '', `founded` int( 4 ) NULL, `facilities` varchar( 255 ) NOT NULL default '', `shortcode` varchar( 20 ) NOT NULL default '', `matchsecretary` int( 11 ) NULL, PRIMARY KEY ( `id` )) $charset_collate;";
		maybe_create_table( $wpdb->racketmanager_clubs, $create_clubs_sql );

		$create_seasons_sql = "CREATE TABLE {$wpdb->racketmanager_seasons} ( `id` int( 11 ) NOT NULL AUTO_INCREMENT, `name` varchar( 100 ) NOT NULL default '', PRIMARY KEY ( `id` )) $charset_collate;";
		maybe_create_table( $wpdb->racketmanager_seasons, $create_seasons_sql );

		$create_competitions_seasons_sql = "CREATE TABLE {$wpdb->racketmanager_competitions_seasons} ( `id` int( 11 ) NOT NULL AUTO_INCREMENT, `competition_id` int( 11 ) NOT NULL, `season_id` int( 11 ) NOT NULL, PRIMARY KEY ( `id` )) $charset_collate;";
		maybe_create_table( $wpdb->racketmanager_competitions_seasons, $create_competitions_seasons_sql );

		$create_results_checker_sql = "CREATE TABLE {$wpdb->racketmanager_results_checker} ( `id` int( 11 ) NOT NULL AUTO_INCREMENT , `league_id` int( 11 ) NOT NULL default '0', `match_id` int( 11 ) NOT NULL default '0', `team_id` int( 11 ) NULL, `player_id` int( 11 ) NULL, `description` varchar( 255 ) NULL, `status` int( 1 ) NULL, `updated_user` int( 11 ) NULL, `updated_date` datetime NULL, PRIMARY KEY ( `id` )) $charset_collate;";
		maybe_create_table( $wpdb->racketmanager_results_checker, $create_results_checker_sql );

		$create_tournaments_sql = "CREATE TABLE {$wpdb->racketmanager_tournaments} ( `id` int( 11 ) NOT NULL AUTO_INCREMENT, `name` varchar( 100 ) NOT NULL default '', `type` varchar( 100 ) NOT NULL default '', `season` varchar( 255 ) NOT NULL default '', `venue` int( 11 ) NULL, `date` date NULL, `closingdate` date NOT NULL, `tournamentsecretary` int( 11 ) NULL, PRIMARY KEY ( `id` )) $charset_collate;";
		maybe_create_table( $wpdb->racketmanager_tournaments, $create_tournaments_sql );

	}


	/**
	* Uninstall Plugin
	*/
	static function uninstall() {
		global $wpdb, $racketmanager;

		$wpdb->query( "DROP TABLE {$wpdb->racketmanager_roster_requests}" );
		$wpdb->query( "DROP TABLE {$wpdb->racketmanager_roster}" );
		$wpdb->query( "DROP TABLE {$wpdb->racketmanager_results_checker}" );
		$wpdb->query( "DROP TABLE {$wpdb->racketmanager_rubbers}" );
		$wpdb->query( "DROP TABLE {$wpdb->racketmanager_matches}" );
		$wpdb->query( "DROP TABLE {$wpdb->racketmanager_table}" );
		$wpdb->query( "DROP TABLE {$wpdb->racketmanager_team_competition}" );
		$wpdb->query( "DROP TABLE {$wpdb->racketmanager_teams}" );
		$wpdb->query( "DROP TABLE {$wpdb->racketmanager}" );
		$wpdb->query( "DROP TABLE {$wpdb->racketmanager_competitions_seasons}" );
		$wpdb->query( "DROP TABLE {$wpdb->racketmanager_competitions}" );
		$wpdb->query( "DROP TABLE {$wpdb->racketmanager_seasons}" );
		$wpdb->query( "DROP TABLE {$wpdb->racketmanager_clubs}" );

		delete_option( 'racketmanager' );

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

	/**
	* set message
	*
	* @param string $message
	* @param boolean $error triggers error message if true
	*/
	public function setMessage( $message, $error = false ) {
		$this->error = $error;
		$this->message = $message;
	}

	/**
	* print formatted message
	*/
	public function printMessage() {
		if (!empty($this->message)) {
			if ( $this->error )
			echo "<div class='error'><p>".$this->message."</p></div>";
			else
			echo "<div id='message' class='updated fade'><p><strong>".$this->message."</strong></p></div>";
		}
		$this->message = '';
	}

	/**
	* get league types
	*
	* @param none
	* @return array
	*/
	public function getLeagueTypes() {
		$types = array( 'default' => __('Default', 'racketmanager') );
		/**
		* Add custom league types
		*
		* @param array $types
		* @return array
		* @category wp-filter
		*/
		$types = apply_filters('racketmanager_sports', $types);
		asort($types);

		return $types;
	}

	/**
	* get seasons
	*
	* @return array
	*/
	public function getSeasons( $order = "ASC" ) {
		global $wpdb;

		$orderBy_string = "`name` ".$order;
		$orderBy = $orderBy_string;
		$seasons = $wpdb->get_results("SELECT `name`, `id` FROM {$wpdb->racketmanager_seasons} ORDER BY $orderBy" );
		$i = 0;
		foreach ( $seasons AS $season ) {
			$seasons[$i]->id = $season->id;
			$seasons[$i]->name = stripslashes($season->name);

			$this->seasons[$season->id] = $seasons[$i];
			$i++;
		}
		return $seasons;
	}

	/**
	* get season
	*
	* @return array
	*/
	public function getSeasonDB( $args = array() ) {
		global $wpdb;

		$defaults = array( 'id' => false, 'name' => false );
		$args = array_merge($defaults, $args);
		extract($args, EXTR_SKIP);

		$search_terms = array();
		if ( $id ) {
			$search_terms[] = $wpdb->prepare("`id` = '%d'", intval($id));
		}
		if ( $name ) {
			$search_terms[] = $wpdb->prepare("`name` = '%s'", $name);
		}
		$search = "";

		if (count($search_terms) > 0) {
			$search = " WHERE ";
			$search .= implode(" AND ", $search_terms);
		}

		$sql = "SELECT `id`, `name` FROM {$wpdb->racketmanager_seasons} $search ORDER BY `name`";

		$season = wp_cache_get( md5($sql), 'seasons' );
		if ( !$season ) {
			$season = $wpdb->get_results( $sql );
			wp_cache_set( md5($sql), $season, 'seasons' );
		}

		if (!isset($season[0])) return false;

		return $season[0];

	}

	/**
	* get tournaments from database
	*
	* @param none
	* @param string $search
	* @return array
	*/
	public function getTournaments( $type=false, $offset=0, $limit=99999999 ) {
		global $wpdb;

		if (!$type) {
			$sql = $wpdb->prepare( "SELECT `id`, `name`, `type`, `season`, `venue`, `date`, `closingdate`, `tournamentsecretary` FROM {$wpdb->racketmanager_tournaments} ORDER BY `name` DESC LIMIT %d, %d",  intval($offset), intval($limit) );
		} else {
			$sql = $wpdb->prepare( "SELECT `id`, `name`, `type`, `season`, `venue`, `date`, `closingdate`, `tournamentsecretary` FROM {$wpdb->racketmanager_tournaments} WHERE `type` = '%s'ORDER BY `name` DESC LIMIT %d, %d",  $type, intval($offset), intval($limit) );
		}

		$tournaments = wp_cache_get( md5($sql), 'tournaments' );
		if ( !$tournaments ) {
			$tournaments = $wpdb->get_results( $sql );
			wp_cache_set( md5($sql), $tournaments, 'tournaments' );
		}

		$i = 0;
		foreach ( $tournaments AS $i => $tournament ) {

			if ( $tournament->date == "0000-00-00" ) $tournament->date = '';
			if ( $tournament->venue == 0 ) {
				$tournament->venue = '';
				$tournament->venueName = '';
			} else {
				$tournament->venueName = get_club($tournament->venue)->name;
			}
			if ( $tournament->tournamentsecretary != '0' ) {
				$tournamentSecretaryDtls = get_userdata($tournament->tournamentsecretary);
				$tournament->tournamentSecretaryName = $tournamentSecretaryDtls->display_name;
				$tournament->tournamentSecretaryEmail = $tournamentSecretaryDtls->user_email;
				$tournament->tournamentSecretaryContactNo = get_user_meta($tournament->tournamentsecretary, 'contactno', true);
			} else {
				$tournament->tournamentSecretaryName = '';
				$tournament->tournamentSecretaryEmail = '';
				$tournament->tournamentSecretaryContactNo = '';
			}

			$tournaments[$i] = $tournament;
		}

		return $tournaments;
	}

	/**
	* get tournament from database
	*
	* @param int $tournament_id
	* @return array
	*/
	public function getTournament( $tournament_name ) {
		global $wpdb;

		$sql = $wpdb->prepare( "SELECT `id`, `name`, `type`, `season`, `venue`, `date`, `closingdate`, `tournamentsecretary` FROM {$wpdb->racketmanager_tournaments} WHERE `name` = '%s'",  $tournament_name );

		$tournament = wp_cache_get( md5($sql), 'tournaments' );
		if ( !$tournament ) {
			$tournament = $wpdb->get_row( $sql );
			wp_cache_set( md5($sql), $tournament, 'tournaments' );
		}

		if ( $tournament->date == "0000-00-00" ) $tournament->date = '';
		if ( $tournament->venue == 0 ) {
			$tournament->venue = '';
			$tournament->venueName = '';
		} else {
			$tournament->venueName = get_club($tournament->venue)->name;
		}

		if ( $tournament->tournamentsecretary != '0' ) {
			$tournamentSecretaryDtls = get_userdata($tournament->tournamentsecretary);
			$tournament->tournamentSecretaryName = $tournamentSecretaryDtls->display_name;
			$tournament->tournamentSecretaryEmail = $tournamentSecretaryDtls->user_email;
			$tournament->tournamentSecretaryContactNo = get_user_meta($tournament->tournamentsecretary, 'contactno', true);
		} else {
			$tournament->tournamentSecretaryName = '';
			$tournament->tournamentSecretaryEmail = '';
			$tournament->tournamentSecretaryContactNo = '';
		}

		return $tournament;
	}

	/**
	* get open tournaments from database
	*
	* @param none
	* @param string $search
	* @return array
	*/
	public function getOpenTournaments( $type ) {
		global $wpdb;

		$sql = $wpdb->prepare( "SELECT `id`, `name`, `type`, `season`, `venue`, DATE_FORMAT(`date`, '%%Y-%%m-%%d') AS date, DATE_FORMAT(`closingdate`, '%%Y-%%m-%%d') AS closingdate, `tournamentsecretary` FROM {$wpdb->racketmanager_tournaments} WHERE `type` = '%s' AND `closingdate` >= CURDATE() ORDER BY `id` ASC ",  $type );

		$tournaments = wp_cache_get( md5($sql), 'tournaments' );
		if ( !$tournaments ) {
			$tournaments = $wpdb->get_results( $sql );
			wp_cache_set( md5($sql), $tournaments, 'tournaments' );
		}

		$date_format = get_option('date_format');
		$i = 0;
		foreach ( $tournaments AS $i => $tournament ) {

			$tournament->date = ( substr($tournament->date, 0, 10) == '0000-00-00' ) ? 'TBC' : mysql2date($date_format, $tournament->date);
			$tournament->closingdate = ( substr($tournament->closingdate, 0, 10) == '0000-00-00' ) ? 'N/A' : mysql2date($date_format, $tournament->closingdate);

			if ( $tournament->date == "0000-00-00" ) $tournament->date = '';
			if ( $tournament->venue == 0 ) {
				$tournament->venue = '';
				$tournament->venueName = '';
			} else {
				$tournament->venueName = get_club($tournament->venue)->name;
			}
			if ( $tournament->tournamentsecretary != '0' ) {
				$tournamentSecretaryDtls = get_userdata($tournament->tournamentsecretary);
				$tournament->tournamentSecretaryName = $tournamentSecretaryDtls->display_name;
				$tournament->tournamentSecretaryEmail = $tournamentSecretaryDtls->user_email;
				$tournament->tournamentSecretaryContactNo = get_user_meta($tournament->tournamentsecretary, 'contactno', true);
			} else {
				$tournament->tournamentSecretaryName = '';
				$tournament->tournamentSecretaryEmail = '';
				$tournament->tournamentSecretaryContactNo = '';
			}

			$tournaments[$i] = $tournament;
		}

		return $tournaments;
	}

	/**
	* get clubs from database
	*
	* @param none
	* @param string $search
	* @return array
	*/
	public function getClubs( $offset=0, $limit=99999999 ) {
		global $wpdb;

		$sql = $wpdb->prepare( "SELECT `id`, `name`, `website`, `type`, `address`, `latitude`, `longitude`, `contactno`, `founded`, `facilities`, `shortcode`, `matchsecretary` FROM {$wpdb->racketmanager_clubs} ORDER BY `name` ASC LIMIT %d, %d",  intval($offset), intval($limit) );

		$clubs = wp_cache_get( md5($sql), 'clubs' );
		if ( !$clubs ) {
			$clubs = $wpdb->get_results( $sql );
			wp_cache_set( md5($sql), $clubs, 'clubs' );
		}

		$i = 0;
		foreach ( $clubs AS $i => $club ) {
			$club = get_club($club);

			$clubs[$i] = $club;
		}

		return $clubs;
	}

	/**
	* get competitions from database
	*
	* @param int $competition_id (default: false)
	* @param string $search
	* @return array
	*/
	public function getCompetitions( $args = array() ) {
		global $wpdb;

		$defaults = array( 'offset' => 0, 'limit' => 99999999, 'type' => false, 'name' => false, 'season' => false, 'orderby' => array("name" => "ASC") );
		$args = array_merge($defaults, $args);
		extract($args, EXTR_SKIP);

		$search_terms = array();
		if ( $name ) {
			$name = $wpdb->esc_like(stripslashes($name)).'%';
			$search_terms[] = $wpdb->prepare("`name` like '%s'", $name);
		}

		if ( $type ) {
			$search_terms[] = $wpdb->prepare("`competitiontype` = '%s'", $type);
		}

		$search = "";
		if (count($search_terms) > 0) {
			$search = " WHERE ";
			$search .= implode(" AND ", $search_terms);
		}

		$orderby_string = ""; $i = 0;
		foreach ($orderby AS $order => $direction) {
			if (!in_array($direction, array("DESC", "ASC", "desc", "asc"))) $direction = "ASC";
			$orderby_string .= "`".$order."` ".$direction;
			if ($i < (count($orderby)-1)) $orderby_string .= ",";
			$i++;
		}
		$orderby = $orderby_string;

		$competitions = $wpdb->get_results($wpdb->prepare( "SELECT `name`, `id`, `num_sets`, `num_rubbers`, `type`, `settings`, `seasons`, `competitiontype` FROM {$wpdb->racketmanager_competitions} $search ORDER BY $orderby LIMIT %d, %d", intval($offset), intval($limit) ));
		$i = 0;
		foreach ( $competitions AS $i => $competition ) {
			$competition->name = stripslashes($competition->name);
			$competition->num_rubbers = $competition->num_rubbers;
			$competition->num_sets = $competition->num_sets;
			$competition->type = $competition->type;
			$competition->competitiontype = $competition->competitiontype;
			$competition->seasons = maybe_unserialize($competition->seasons);
			$competition->settings = maybe_unserialize($competition->settings);

			$competition = (object)array_merge((array)$competition, $competition->settings);

			if ( $season ) {
				if ( array_search($season,array_column($competition->seasons, 'name') ,true) ) {
					$competitions[$i] = $competition;
				} else {
					unset($competitions[$i]);
				}
			} else {
				$competitions[$i] = $competition;
			}
		}
		return $competitions;
	}

	/**
	* get Team ID for given string
	*
	* @param string $title
	* @return int
	*/
	public function getTeamID( $title ) {
		global $wpdb;

		$team = $wpdb->get_results( $wpdb->prepare("SELECT `id` FROM {$wpdb->racketmanager_teams} WHERE `title` = '%s'", $title) );
		if (!isset($team[0]))
		return 0;
		else return $team[0]->id;
	}

	/**
	* add Team to Table
	*
	* @param string $title
	* @return int
	*/
	public function addTeamtoTable( $leagueId, $teamId, $season , $custom = array(), $message = true) {
		global $wpdb, $racketmanager;

		$tableId = $this->checkTableEntry( $leagueId, $teamId, $season );
		if ( $tableId ) {
			$messageText = 'Team already in table';
		} else {
			$sql = "INSERT INTO {$wpdb->racketmanager_table} (`team_id`, `season`, `custom`, `league_id`) VALUES ('%d', '%s', '%s', '%d')";
			$wpdb->query( $wpdb->prepare ( $sql, $teamId, $season, maybe_serialize($custom), $leagueId) );
			$tableId = $wpdb->insert_id;
			$messageText = 'Table entry added';
		}
		if ( $message )
		$this->setMessage( __($messageText,'racketmanager') );

		return $tableId;
	}

	/**
	* check for table entry
	*
	* @param int $league_id
	* @param string $team_id
	* @param string $season
	* @return $num_teams
	*/
	public function checkTableEntry( $league_id, $team_id, $season ) {
		global $wpdb;

		$query = $wpdb->prepare ( "SELECT `id` FROM {$wpdb->racketmanager_table} WHERE `team_id` = '%d' AND `season` = '%s' AND `league_id` = '%d'", $team_id, $season, $league_id);
		return $wpdb->get_var( $query );
	}

	/**
	* add player team
	*
	* @param int $player1Id
	* @param string $player1
	* @param int $player2Id
	* @param string $player2
	* @param string $contactno
	* @param string $contactemail
	* @param string $affiliatedclub
	* @param int $league_id
	* @return $team_id
	*/
	public function addPlayerTeam( $player1, $player1Id, $player2, $player2Id, $contactno, $contactemail, $affiliatedclub, $league_id ) {
		global $wpdb, $racketmanager;

		$league = get_league($league_id);
		$type = $league->type;
		if ( $type == 'LD' ) $type = 'XD';
		$status = "P";
		if ( $player2Id == 0 ) {
			$title = $player1;
			$roster = array($player1Id);
		} else {
			$title = $player1.' / '.$player2;
			$roster = array($player1Id, $player2Id);
		}
		$sql = "INSERT INTO {$wpdb->racketmanager_teams} (`title`, `affiliatedclub`, `roster`, `status`, `type` ) VALUES ('%s', '%d', '%s', '%s', '%s')";
		$wpdb->query( $wpdb->prepare ( $sql, $title, $affiliatedclub, maybe_serialize($roster), $status, $type ) );
		$team_id = $wpdb->insert_id;
		$captain = $racketmanager->getRosterEntry($player1Id)->player_id;

		return $team_id;
	}

	/**
	* add team to competition
	*
	* @param int $league_id
	* @param int $competition_id
	* @param string $title
	* @param string $captain
	* @param string $contactno
	* @param string $contactemail
	* @param int $matchday
	* @param int $matchtime
	* @return $team_competition_id
	*/
	public function addTeamCompetition( $team_id, $competition_id, $captain = NULL, $contactno = NULL, $contactemail = NULL, $matchday = '', $matchtime = NULL ) {
		global $wpdb;

		$sql = "INSERT INTO {$wpdb->racketmanager_team_competition} (`team_id`, `competition_id`, `captain`, `match_day`, `match_time`) VALUES ('%d', '%d', '%d', '%s', '%s')";
		$wpdb->query( $wpdb->prepare ( $sql, $team_id, $competition_id, $captain, $matchday, $matchtime ) );
		$team_competition_id = $wpdb->insert_id;
		if ( isset($captain) && $captain != '' ) {
			$currentContactNo = get_user_meta( $captain, 'contactno', true);
			$currentContactEmail = get_userdata($captain)->user_email;
			if ($currentContactNo != $contactno ) {
				update_user_meta( $captain, 'contactno', $contactno );
			}
			if ($currentContactEmail != $contactemail ) {
				$userdata = array();
				$userdata['ID'] = $captain;
				$userdata['user_email'] = $contactemail;
				$user_id = wp_update_user( $userdata );
				if ( is_wp_error($user_id) ) {
					error_log('Unable to update user email '.$captain.' - '.$contactemail);
				}
			}
		}

		return $team_competition_id;
	}

	/**
	* gets roster from database
	*
	* @param array $query_args
	* @return array
	*/
	public function getRoster( $args, $output = 'OBJECT' ) {
		global $wpdb;

		$defaults = array( 'count' => false, 'team' => false, 'club' => false, 'player' => false, 'gender' => false, 'inactive' => false, 'cache' => true, 'type' => false, 'orderby' => array("display_name" => "ASC" ));
		$args = array_merge($defaults, (array)$args);
		extract($args, EXTR_SKIP);

		//$cachekey = md5(implode(array_map(function($entry) { if(is_array($entry)) { return implode($entry); } else { return $entry; } }, $args)) . $output);

		$search_terms = array();
		if ($team) {
			$search_terms[] = $wpdb->prepare("`affiliatedclub` in (select `affiliatedclub` from {$wpdb->racketmanager_teams} where `id` = '%d')", intval($team));
		}

		if ($club) {
			$search_terms[] = $wpdb->prepare("`affiliatedclub` = '%d'", intval($club));
		}

		if ($player) {
			$search_terms[] = $wpdb->prepare("`player_id` = '%d'", intval($player));
		}

		if ($gender) {
			//            $search_terms[] = $wpdb->prepare("`gender` = '%s'", htmlspecialchars(strip_tags($gender)));
		}

		if ($type) {
			$search_terms[] = "`system_record` IS NULL";
		}

		if ($inactive) {
			$search_terms[] = "`removed_date` IS NULL";
		}

		$search = "";
		if (count($search_terms) > 0) {
			$search = implode(" AND ", $search_terms);
		}

		$orderby_string = ""; $i = 0;
		foreach ($orderby AS $order => $direction) {
			if (!in_array($direction, array("DESC", "ASC", "desc", "asc"))) $direction = "ASC";
			$orderby_string .= "`".$order."` ".$direction;
			if ($i < (count($orderby)-1)) $orderby_string .= ",";
			$i++;
		}
		$order = $orderby_string;

		$offset = 0;

		if ( $count ) {
			$sql = "SELECT COUNT(ID) FROM {$wpdb->racketmanager_roster}";
			if ( $search != "") $sql .= " WHERE $search";
			$cachekey = md5($sql);
			if ( isset($this->num_players[$cachekey]) && $cache && $count )
			return intval($this->num_players[$cachekey]);

			$this->num_players[$cachekey] = $wpdb->get_var($sql);
			return $this->num_players[$cachekey];
		}

		$sql = "SELECT A.`id` as `roster_id`, B.`ID` as `player_id`, `display_name` as fullname, `affiliatedclub`, A.`removed_date`, A.`removed_user`, A.`created_date`, A.`created_user` FROM {$wpdb->racketmanager_roster} A INNER JOIN {$wpdb->users} B ON A.`player_id` = B.`ID`" ;
		if ( $search != "") $sql .= " WHERE $search";
		if ( $order != "") $sql .= " ORDER BY $order";

		$rosters = wp_cache_get( md5($sql), 'rosters' );
		if ( !$rosters ) {
			$rosters = $wpdb->get_results( $sql );
			wp_cache_set( md5($sql), $rosters, 'rosters' );
		}

		$i = 0;
		$class = '';
		foreach ( $rosters AS $roster ) {
			$class = ( 'alternate' == $class ) ? '' : 'alternate';
			$rosters[$i]->class = $class;

			$rosters[$i] = (object)(array)$roster;

			$rosters[$i]->affiliatedclub = $roster->affiliatedclub;
			$rosters[$i]->roster_id = $roster->roster_id;
			$rosters[$i]->player_id = $roster->player_id;
			$rosters[$i]->fullname = $roster->fullname;
			$rosters[$i]->gender = get_user_meta($roster->player_id, 'gender', true );
			$rosters[$i]->type = get_user_meta($roster->player_id, 'racketmanager_type', true );
			$rosters[$i]->removed_date = $roster->removed_date;
			$rosters[$i]->removed_user = $roster->removed_user;
			if ( $roster->removed_user ) {
				$rosters[$i]->removedUserName = get_userdata($roster->removed_user)->display_name;
			} else {
				$rosters[$i]->removedUserName = '';
			}
			$rosters[$i]->btm = get_user_meta($roster->player_id, 'btm', true );;
			$rosters[$i]->created_date = $roster->created_date;
			$rosters[$i]->created_user = $roster->created_user;
			if ( $roster->created_user ) {
				$rosters[$i]->createdUserName = get_userdata($roster->created_user)->display_name;
			} else {
				$rosters[$i]->createdUserName = '';
			}
			if ( $gender && $gender != $rosters[$i]->gender ) {
				unset($rosters[$i]);
			}

			$i++;
		}

		return $rosters;
	}

	/**
	* gets single roster entry from database
	*
	* @param array $query_args
	* @return array
	*/
	public function getRosterEntry( $roster_id, $cache = true ) {
		global $wpdb;

		$sql = "SELECT B.`ID` as `player_id`, B.`display_name` AS `fullname`, A.`system_record`, `affiliatedclub`, A.`removed_date`, A.`removed_user`, A.`created_date`, A.`created_user` FROM {$wpdb->racketmanager_roster} A INNER JOIN {$wpdb->users} B ON A.`player_id` = B.`ID` WHERE A.`id`= '".intval($roster_id)."'";

		$roster = wp_cache_get( md5($sql), 'rosterentry' );
		if ( !$roster || !$cache ) {
			$roster = $wpdb->get_row( $sql );
			wp_cache_set( md5($sql), $roster, 'rosterentry' );
		}

		return $roster;
	}

	/**
	* delete Roster
	*
	* @param int $team_id
	* @return boolean
	*/
	public function delRoster( $roster_id ) {
		global $wpdb;

		$userid = get_current_user_id();
		$wpdb->query( $wpdb->prepare("UPDATE {$wpdb->racketmanager_roster} SET `removed_date` = NOW(), `removed_user` = %d WHERE `id` = '%d'", $userid, $roster_id) );
		$this->setMessage( __('Roster deleted', 'racketmanager') );

		return true;
	}

	/**
	* get list of players
	*
	* @param array $query_args
	* @return array
	*/
	public function getPlayers( $args ) {
		$defaults = array( 'player_id' => false, 'btm' => false, 'firstname' => false, 'surname' => false, 'cache' => true, 'orderby' => array("fullname" => "ASC"  ) );
		$args = array_merge($defaults, (array)$args);
		extract($args, EXTR_SKIP);
		$cachekey = md5(implode(array_map(function($entry) { if(is_array($entry)) { return implode($entry); } else { return $entry; } }, $args)) );
		$search_terms = array();
		if ($player_id) {
			$search_terms[] = $wpdb->prepare("`player_id` = '%d'", intval($player_id));
		}

		if ($btm) {
			$search_terms[] = $wpdb->prepare("`btm` = '%d'", intval($btm));
		}

		if ($firstname) {
			$search_terms[] = $wpdb->prepare("`firstname` = '%s'", htmlspecialchars(strip_tags($firstname)));
		}

		if ($surname) {
			$search_terms[] = $wpdb->prepare("`surname` = '%s'", htmlspecialchars(strip_tags($surname)));
		}

		$search = "";
		if (count($search_terms) > 0) {
			$search = implode(" AND ", $search_terms);
		}

		$orderby_string = ""; $i = 0;
		foreach ($orderby AS $order => $direction) {
			if (!in_array($direction, array("DESC", "ASC", "desc", "asc"))) $direction = "ASC";
			if ($this->databaseColumnExists("player", $order)) {
				$orderby_string .= "`".$order."` ".$direction;
				if ($i < (count($orderby)-1)) $orderby_string .= ",";
			}
			$i++;
		}
		$order = $orderby_string;

		// use cached object
		if ( isset($this->players[$cachekey]) && $cache ) {
			return $this->players[$cachekey];
		}

		$players = get_users( 'orderby=displayname' );
		if ( !$players ) return false;

		$i = 0;
		foreach ( $players AS $player ) {

			$players[$i] = (object)(array)$player;
			$players[$i]->id = $player->ID;
			$players[$i]->fullname = $player->display_name;
			$players[$i]->firstname = get_user_meta($player->ID, 'first_name', true );
			$players[$i]->lastname = get_user_meta($player->ID, 'last_name', true );
			$players[$i]->gender = get_user_meta($player->ID, 'gender', true );
			$players[$i]->removed_date = get_user_meta($player->ID, 'remove_date', true );
			$players[$i]->btm = get_user_meta($player->ID, 'btm', true );;
			$players[$i]->created_date = $player->user_registered;

			$i++;
		}

		$this->players[$cachekey] = $players;
		return $this->players[$cachekey];
	}

	/**
	* get single player
	*
	* @param array $query_args
	* @return array
	*/
	public function getPlayer( $args ) {
		$defaults = array( 'player_id' => false, 'fullname' => false, 'cache' => true );
		$args = array_merge($defaults, (array)$args);
		extract($args, EXTR_SKIP);

		$search_terms = array();
		if ($player_id) {
			$player = get_user_by( 'id', $player_id );
		}

		if ($fullname) {
			$player = get_user_by( 'slug', sanitize_title($fullname) );
		}

		if ( !$player ) return false;

		$player = (object)(array)$player;

		$this->player[$player->ID] = $player;
		return $this->player[$player->ID];
	}

	/**
	* get player name
	*
	* @param int $playerId
	* @return string | false
	*/
	public function getPlayerName( $playerId ) {
		$player = get_userdata( $playerId );
		if ( !$player ) return false;

		return $player->display_name;
	}

	/**
	* add new player
	*
	* @param string $firstname
	* @param string $surname
	* @param string $gender
	* @param int $btm
	* @param boolean $message (optional)
	* @return int | false
	*/
	public function addPlayer( $firstname, $surname, $gender, $btm, $message = true ) {

		if ( !current_user_can('edit_teams') ) {
			$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
			return false;
		}

		$userdata = array();
		$userdata['first_name'] = $firstname;
		$userdata['last_name'] = $surname;
		$userdata['display_name'] = $firstname.' '.$surname;
		$userdata['user_login'] = $firstname.'.'.$surname;
		$userdata['user_pass'] = $userdata['user_login'].'1';
		$user_id = wp_insert_user( $userdata );
		if ( ! is_wp_error( $user_id ) ) {
			update_user_meta($user_id, 'show_admin_bar_front', false );
			update_user_meta($user_id, 'gender', $gender);
			if ( isset($btm) ) {
				update_user_meta($user_id, 'btm', $btm);
			}
		}

		if ( $message )
		$this->setMessage( __('Player added', 'racketmanager') );

		return $user_id;
	}

	/**
	* get matches without using league object
	*
	* @param array $match_args
	* @return array $matches
	*/
	public function getMatches( $match_args ) {
		global $wpdb;

		$defaults = array( 'league_id' => false, 'season' => false, 'orderby' => array("date" => "ASC", "id" => "ASC"), 'competition_id' => false, 'confirmed' => false, 'match_date' => false, 'competition_type' => false, 'time' => false, 'history' => false, 'affiliatedClub' => false );
		$match_args = array_merge($defaults, (array)$match_args);
		extract($match_args, EXTR_SKIP);

		$sql = "SELECT `group`, `home_team`, `away_team`, DATE_FORMAT(`date`, '%Y-%m-%d %H:%i') AS date, DATE_FORMAT(`date`, '%e') AS day, DATE_FORMAT(`date`, '%c') AS month, DATE_FORMAT(`date`, '%Y') AS year, DATE_FORMAT(`date`, '%H') AS `hour`, DATE_FORMAT(`date`, '%i') AS `minutes`, `match_day`, `location`, `league_id`, `home_points`, `away_points`, `winner_id`, `loser_id`, `post_id`, `season`, `id`, `custom`, `confirmed`, `home_captain`, `away_captain` FROM {$wpdb->racketmanager_matches} WHERE 1 = 1";

		if ( $match_date ) {
			$sql .= " AND DATEDIFF('". htmlspecialchars(strip_tags($match_date))."', `date`) = 0";
		}
		if ( $competition_type ) {
			$sql .= " AND `league_id` in (select `id` from {$wpdb->racketmanager} WHERE `competition_id` in (SELECT `id` FROM {$wpdb->racketmanager_competitions} WHERE `competitiontype` = '".$competition_type."'))";
		}

		if ( $competition_id ) {
			$sql .= " AND `league_id` in (select `id` from {$wpdb->racketmanager} WHERE `competition_id` = '".$competition_id."')";
		}

		if ( $league_id ) {
			$sql .= " AND `league_id`  = '".$league_id."'";
		}

		if ( $confirmed ) {
			$sql .= " AND `confirmed` in ('P','A','C')";
		}

		// get only finished matches with score for time 'latest'
		if ( $time == 'latest' ) {
			$home_points = $away_points = false;
			$sql .= " AND (`home_points` != '' OR `away_points` != '')";
		}

		// get only updated matches in specified period for history
		if ( $history ) {
			$sql .= " AND `updated` >= NOW() - INTERVAL ".$history." DAY";
		}

		if ( $affiliatedClub ) {
			$sql .= " AND (`home_team` IN (SELECT `id` FROM {$wpdb->racketmanager_teams} WHERE `affiliatedclub` = ".$affiliatedClub.") OR `away_team` IN (SELECT `id` FROM {$wpdb->racketmanager_teams} WHERE `affiliatedclub` = ".$affiliatedClub."))";
		}

		$sql .= " ORDER BY `league_id` ASC";

		// get matches
		$matches = $wpdb->get_results($sql);
		$class = '';

		foreach ( $matches AS $i => $match ) {

			$class = ( 'alternate' == $class ) ? '' : 'alternate';
			$match = get_match($match);
			$match->class = $class;
			$matches[$i] = $match;
		}

		return $matches;
	}

	/**
	* show winners
	*
	* @param string $season
	* @param string $seasonType
	* @return void
	*/
	public function getWinners( $season, $seasonType ) {
		global $racketmanager, $wpdb;

		$seasonType = $wpdb->esc_like(stripslashes($seasonType)).'%';

		$sql = "SELECT l.`title` ,wt.`title` AS `winner` ,lt.`title` AS `loser` FROM {$wpdb->racketmanager_matches} m, {$wpdb->racketmanager} l, {$wpdb->racketmanager_competitions} c, {$wpdb->racketmanager_teams} wt, {$wpdb->racketmanager_teams} lt WHERE `league_id` = l.`id` AND l.`competition_id` = c.`id` AND c.`competitiontype` = 'tournament' AND c.`name` like '%s' AND m.`final` = 'FINAL' AND m.`season` = '%d' AND m.`winner_id` = wt.`id` AND m.`loser_id` = lt.`id` order by 1";

		$sql = $wpdb->prepare($sql, $seasonType, $season);
		$winners = $wpdb->get_results($sql);

		if ( !$winners ) return false;

		$i = 0;
		foreach ( $winners AS $winner ) {

			$winners[$i] = (object)(array)$winner;
			$winners[$i]->league = $winner->title;
			$winners[$i]->winner = $winner->winner;
			$winners[$i]->loser = $winner->loser;

			$i++;
		}

		return $winners;

	}

	/**
	* send mail
	*
	* @param string $to
	* @param string $subject
	* @param string $message
	* @return none
	*/
	public function lm_mail($to, $subject, $message) {
		$headers = array('Content-Type: text/html; charset=UTF-8');
		wp_mail($to, $subject, $message, $headers);

		return;
	}

	/**
	* check if database column exists
	*
	* @param string $table
	* @param string $column
	* @return boolean
	*/
	public function databaseColumnExists($table, $column) {
		global $wpdb;

		if ($table == "teams")
		$table = $wpdb->racketmanager_teams;
		elseif ($table == "table")
		$table = $wpdb->racketmanager_table;
		elseif ($table == "matches")
		$table = $wpdb->racketmanager_matches;
		elseif ($table == "roster")
		$table = $wpdb->racketmanager_roster;
		elseif ($table == "leagues")
		$table = $wpdb->racketmanager;
		elseif ($table == "seasons")
		$table = $wpdb->racketmanager_seasons;
		elseif ($table == "competititons")
		$table = $wpdb->racketmanager_competititons;
		else
		return false;

		$sql = $wpdb->prepare("SHOW COLUMNS FROM {$table} LIKE %s", $column);

		$res = wp_cache_get( md5($sql), 'racketmanager' );

		if ( !$res ) {
			$res = $wpdb->query( $sql );
			wp_cache_set( md5($sql), $res, 'racketmanager' );
		}
		$res = ( $res == 1 ) ? true : false;
		return $res;
	}

	/**
	* update player contact details
	*
	* @param int $player
	* @param string $contactno
	* @param string $contactemail
	* @return boolean
	*/
	public function updatePlayerDetails( $player, $contactNo, $contactEmail ) {
		$currentContactNo = get_user_meta( $player, 'contactno', true);
		$currentContactEmail = get_userdata($player)->user_email;
		if ($currentContactNo != $contactNo ) {
			update_user_meta( $player, 'contactno', $contactNo );
		}
		if ($currentContactEmail != $contactEmail ) {
			$userdata = array();
			$userdata['ID'] = $player;
			$userdata['user_email'] = $contactEmail;
			$userId = wp_update_user( $userdata );
			if ( is_wp_error($userId) ) {
				$error_msg = $userId->get_error_message();
				error_log('Unable to update user email '.$player.' - '.$contactEmail.' - '.$error_msg);
				return false;
			}
		}
		return true;
	}

	/**
	* update club
	*
	* @param int $club_id
	* @param string $name
	* @param string $type
	* @param string $shortcode
	* @param int $matchsecretary
	* @param string $matchSecretaryContactNo
	* @param string $matchSecretaryEmail
	* @param string $contactno
	* @param string $website
	* @param string $founded
	* @param string $facilities
	* @param string $address
	* @param string $latitude
	* @param string $longitude
	* @return boolean
	*/
	public function updateClub( $club_id, $name, $type, $shortcode, $matchsecretary, $matchSecretaryContactNo, $matchSecretaryEmail, $contactno, $website, $founded, $facilities, $address, $latitude, $longitude ) {
		global $wpdb;

		$wpdb->query( $wpdb->prepare ( "UPDATE {$wpdb->racketmanager_clubs} SET `name` = '%s', `type` = '%s', `shortcode` = '%s',`matchsecretary` = '%d', `contactno` = '%s', `website` = '%s', `founded`= '%s', `facilities` = '%s', `address` = '%s', `latitude` = '%s', `longitude` = '%s' WHERE `id` = %d", $name, $type, $shortcode, $matchsecretary, $contactno, $website, $founded, $facilities, $address, $latitude, $longitude, $club_id ) );

		if ( $matchsecretary != '') {
			$currentContactNo = get_user_meta( $matchsecretary, 'contactno', true);
			$currentContactEmail = get_userdata($matchsecretary)->user_email;
			if ($currentContactNo != $matchSecretaryContactNo ) {
				update_user_meta( $matchsecretary, 'contactno', $contactNo );
			}
			if ($currentContactEmail != $matchSecretaryEmail ) {
				$userdata = array();
				$userdata['ID'] = $matchsecretary;
				$userdata['user_email'] = $matchSecretaryEmail;
				$userId = wp_update_user( $userdata );
				if ( is_wp_error($userId) ) {
					$error_msg = $userId->get_error_message();
					error_log('Unable to update user email '.$matchsecretary.' - '.$matchSecretaryEmail.' - '.$error_msg);
				}
			}
		}
	}

	/**
	* add entry to results checker for errors on match result
	*
	* @param match $match
	* @param int $team
	* @param int $player
	* @param string $error
	* @return none
	*/
	public function addResultCheck( $match, $team, $player, $error ) {
		global $wpdb;

		$wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->racketmanager_results_checker} (`league_id`, `match_id`, `team_id`, `player_id`, `description`) values ( %d, %d, %d, %d, '%s') ", $match->league_id, $match->id, $team, $player, $error ) );

	}

	/**
	* check user allowed to update match
	*
	* @param array $home_team
	* @param array $away_team
	* @return boolean
	*/
	public function getMatchUpdateAllowed($homeTeam, $awayTeam) {
		$options = $this->getOptions();
		$userid = get_current_user_id();
		$userCanUpdate = false;

		if ( $userid ) {
			if ( !current_user_can( 'manage_racketmanager' ) ) {
				if ( $options['matchCapability'] == 'roster' ) {
					$club = get_club($homeTeam->affiliatedclub);
					$homeRoster = $club->getRoster( array( 'count' => true, 'player' => $userid, 'inactive' => true ) );
					if ( $homeRoster != 0 ) {
						$userCanUpdate = true;
					} elseif ( $options['resultEntry'] == 'either' ) {
						$club = get_club($awayTeam->affiliatedclub);
						$awayRoster = $club->getRoster( array( 'count' => true, 'player' => $userid, 'inactive' => true ) );
						if ( $awayRoster != 0 ) {
							$userCanUpdate = true;
						}
					}
				} elseif ( $options['matchCapability'] == 'captain' ) {
					if ( $userid == $homeTeam->captainId || ( $options['resultEntry'] == 'either' && $userid == $awayTeam->captainId ) ) {
						$userCanUpdate = true;
					}
				}
			} else {
				$userCanUpdate = true;
			}
		}

		return $userCanUpdate;
	}
}

global $racketmanager;
if ( is_admin() ) {
	require_once (dirname (__FILE__) . '/admin/admin.php');
	$racketmanager = new RacketManagerAdmin();
} else {
	$racketmanager = new RacketManager();
}

// suppress output
if ( isset($_POST['racketmanager_export']) )
ob_start();
?>
