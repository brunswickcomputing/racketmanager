<?php
/*
Plugin Name: Racketmanager
Plugin URI: http://wordpress.org/extend/plugins/leaguemanager/
Description: Manage and present sports league results.
Version: 6.29.0
Author: Paul Moffat

Copyright 2008-2022  Paul Moffat (email: paul@paarcs.com)
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
* @version 6.29.0
* @copyright 2008-2022
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
	private $version = '6.29.0';

	/**
	* database version
	*
	* @var string
	*/
	private $dbversion = '6.27.0';

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

		if (function_exists('register_uninstall_hook')) {
			register_uninstall_hook(__FILE__, array('RacketManagerLoader', 'uninstall'));
		}

		add_action( 'widgets_init', array(&$this, 'registerWidget') );
		add_action( 'init', array(&$this, 'racketmanagerRewrites') );
		add_action('wp_enqueue_scripts', array(&$this, 'loadStyles'), 5 );
		add_action('wp_enqueue_scripts', array(&$this, 'loadScripts') );

		add_action( 'wp_loaded', array(&$this, 'addRacketmanagerTemplates') );

		add_filter( 'wp_privacy_personal_data_exporters', array(&$this, 'racketmanagerRegisterExporter') );
		add_filter( 'wp_mail', array(&$this, 'racketmanagerMail') );
		add_filter( 'email_change_email', array(&$this, 'racketmanagerChangeEmailAddress'), 10, 3 );

	}

	public function addRacketmanagerTemplates() {
		// Add your templates to this array.
		$this->templates = array(
			'templates/page_template/template_notitle.php' => 'No Title',
			'templates/page_template/template_member_account.php' => 'Member Account'
		);

		// Add a filter to the wp 4.7 version attributes metabox
		add_filter( 'theme_page_templates', array( $this, 'racketmanagerTemplatesAsOption' ) );

		// Add a filter to the save post to inject our template into the page cache
		add_filter( 'wp_insert_post_data', array( $this, 'racketmanagerPostTemplates' ) );

		// Add a filter to the template include to determine if the page has our
		// template assigned and return it's path
		add_filter(	'template_include',	array( $this, 'racketmanagerLoadTemplate') );

		add_filter( 'archive_template', array( $this, 'racketmanagerArchiveTemplate') );

	}

	/**
	* Adds our templates to the page dropdown
	*
	*/
	public function racketmanagerTemplatesAsOption( $postsTemplates ) {
		return array_merge( $postsTemplates, $this->templates );
	}

	/**
	* Adds our templates to the pages cache in order to trick WordPress
	* into thinking the template file exists where it doens't really exist.
	*/
	public function racketmanagerPostTemplates( $atts ) {

		// Create the key used for the themes cache
		$cacheKey = 'page_templates-' . md5( get_theme_root() . '/' . get_stylesheet() );

		// Retrieve the cache list.
		// If it doesn't exist, or it's empty prepare an array
		$pageTemplates = wp_get_theme()->get_page_templates();
		if ( empty( $pageTemplates ) ) {
			$pageTemplates = array();
		}

		// New cache, therefore remove the old one
		wp_cache_delete( $cacheKey , 'themes');

		// Now add our template to the list of templates by merging our templates
		// with the existing templates array from the cache.
		$pageTemplates = array_merge( $pageTemplates, $this->templates );

		// Add the modified cache to allow WordPress to pick it up for listing
		// available templates
		wp_cache_add( $cacheKey, $pageTemplates, 'themes', 1800 );

		return $atts;

	}

	/**
	* Checks if the template is assigned to the page
	*/
	public function racketmanagerLoadTemplate( $template ) {

		// Get global post
		global $post;

		// Return template if post is empty or if we don't have a custom one defined
		if ( ! $post || ! isset( $this->templates[get_post_meta($post->ID, '_wp_page_template', true)] ) ) {
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
	public function racketmanagerArchiveTemplate( $template ) {
		global $post;

		if ( is_category('rules') ) {
			$template = plugin_dir_path( __FILE__ ).'templates/pages/category-rules.php';
		}
		if ( is_category('how-to') ) {
			$template = plugin_dir_path( __FILE__ ).'templates/pages/category-how-to.php';
		}
		return $template;
	}

	public function racketmanagerRegisterExporter( $exportersArray ) {
		$exportersArray['racketmanager_exporter'] = array(
			'exporter_friendly_name' => 'Racketmanager exporter',
		 	'callback' => array(&$this, 'racketmanagerPrivacyExporter')
		);
		return $exportersArray;

	}

	public function racketmanagerPrivacyExporter( $emailAddress, $page = 1 ) {
		$page = (int) $page;

		$dataToExport = array();

		$user = get_user_by( 'email', $emailAddress );
		if ( ! $user ) {
			return array(
				'data' => array(),
				'done' => true,
			);
		}

		$userMeta = get_user_meta( $user->ID );

		$userPropToExport = array(
			'gender'           => __( 'User Gender' ),
			'BTM'              => __( 'User BTM' ),
			'remove_date'      => __( 'User Removed Date' ),
			'contactno'        => __( 'User Contact Number' ),
		);

		$userDataToExport = array();

		foreach ( $userPropToExport as $key => $name ) {

			switch ( $key ) {
				case 'gender':
				case 'BTM':
				case 'remove_date':
				case 'contactno':
				$value = isset($userMeta[ $key ][0]) ? $userMeta[ $key ][0] : '';
				break;
				default:
				$value = '';
			}

			if ( ! empty( $value ) ) {
				$userDataToExport[] = array(
					'name'  => $name,
					'value' => $value,
				);
			}
		}

		$dataToExport[] = array(
			'group_id'    => 'user',
			'group_label' => __( 'User' ),
			'item_id'     => "user-{$user->ID}",
			'data'        => $userDataToExport,
		);

		return array(
			'data' => $dataToExport,
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
		define( 'RACKETMANAGER', 'racketmanager');
		define( 'RACKETMANAGER_VERSION', $this->version );
		define( 'RACKETMANAGER_DBVERSION', $this->dbversion );
		define( 'RACKETMANAGER_URL', esc_url(plugin_dir_url(__FILE__)) );
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
		$files = array_merge($this->readDirectory(RACKETMANAGER_PATH."sports"), $this->readDirectory(get_stylesheet_directory() . "/sports"));

		// load files
		foreach ( $files as $file ) {
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
				$fileInfo = pathinfo($dir.'/'.$file);
				$fileType = (isset($fileInfo['extension'])) ? $fileInfo['extension'] : '';
				if ( $file != "." && $file != ".." && !is_dir($file) && substr($file, 0,1) != "."  && $fileType == 'php' )  {
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
		$this->date_format = get_option('date_format');
		$this->time_format = get_option('time_format');
		$this->site_name = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		$this->admin_email = get_option('admin_email');
		$this->site_url = get_option('siteurl');
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
		wp_register_script( 'racketmanager', RACKETMANAGER_URL.'js/racketmanager.js', array('jquery', 'jquery-ui-core', 'jquery-ui-autocomplete', 'jquery-effects-core', 'jquery-effects-slide', 'sack', 'thickbox'), RACKETMANAGER_VERSION );
		wp_enqueue_script('racketmanager');
		wp_enqueue_script( 'password-strength-meter' );
		wp_enqueue_script( 'password-strength-meter-mediator', RACKETMANAGER_URL . 'js/password-strength-meter-mediator.js', array('password-strength-meter'));
		wp_localize_script( 'password-strength-meter', 'pwsL10n', array(
			'empty' => __( 'Strength indicator' ),
			'short' => __( 'Very weak' ),
			'bad' => __( 'Weak' ),
			'good' => _x( 'Good', 'password strength' ),
			'strong' => __( 'Strong' ),
			'mismatch' => __( 'Mismatch' )
		) );
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
		wp_enqueue_style('racketmanager-print', RACKETMANAGER_URL . "css/print.css", false, RACKETMANAGER_VERSION, 'print');
		wp_enqueue_style('racketmanager-modal', RACKETMANAGER_URL . "css/modal.css", false, RACKETMANAGER_VERSION, 'screen');
		wp_enqueue_style('racketmanager', RACKETMANAGER_URL . "css/style.css", false, RACKETMANAGER_VERSION, 'screen');

		wp_register_style('jquery-ui', RACKETMANAGER_URL . "css/jquery/jquery-ui.min.css", false, '1.11.4', 'all');
		wp_register_style('jquery-ui-structure', RACKETMANAGER_URL . "css/jquery/jquery-ui.structure.min.css", array('jquery-ui'), '1.11.4', 'all');
		wp_register_style('jquery-ui-theme', RACKETMANAGER_URL . "css/jquery/jquery-ui.theme.min.css", array('jquery-ui', 'jquery-ui-structure'), '1.11.4', 'all');
		wp_register_style('jquery-ui-autocomplete', RACKETMANAGER_URL . "css/jquery/jquery-ui.autocomplete.min.css", array('jquery-ui', 'jquery-ui-autocomplete'), '1.11.4', 'all');
		wp_register_style('datatables-style', 'https://cdn.datatables.net/v/ju/dt-1.11.3/fh-3.2.0/datatables.min.css');

		wp_enqueue_style('jquery-ui-structure');
		wp_enqueue_style('jquery-ui-theme');

		ob_start();
		require_once(RACKETMANAGER_PATH.'css/colors.css.php');
		$css = ob_get_contents();
		ob_end_clean();

		wp_add_inline_style( 'racketmanager', $css );
	}

	/*
	* Create formatted url
	*/
	public function racketmanagerRewrites() {
	  // daily matches - date
	  add_rewrite_rule(
	    'leagues/daily-matches/([0-9]{4})-([0-9]{2})-([0-9]{2})/?$',
	    'index.php?pagename=leagues/daily-matches&match_date=$matches[1]-$matches[2]-$matches[3]',
	    'top'
	  );
	  // competition - season
	  add_rewrite_rule(
	    'leagues/(.+?)/(.+?)-competition/([0-9]{4})?$',
	    'index.php?pagename=leagues/$matches[1]/$matches[2]-competition&season=$matches[3]',
	    'top'
	  );
	  // competition
	  add_rewrite_rule(
	    'leagues/(.+?)/(.+?)-competition/?$',
	    'index.php?pagename=leagues/$matches[1]/$matches[2]-competition',
	    'top'
	  );
	  // league - season - matchday - team
	  add_rewrite_rule(
	    'leagues/(.+?)-(.+?)-([0-9]{1})/([0-9]{4})/day([0-9]{1,2})/(.+?)/?$',
	    'index.php?pagename=leagues/$matches[1]/$matches[2]&league_name=$matches[1]-$matches[2]-$matches[3]&season=$matches[4]&match_day=$matches[5]&team=$matches[6]',
	    'top'
	  );
	  // league - season - matchday
	  add_rewrite_rule(
	    'leagues/(.+?)-(.+?)-([0-9]{1})/([0-9]{4})/day([0-9]{1,2})/?$',
	    'index.php?pagename=leagues/$matches[1]/$matches[2]&league_name=$matches[1]-$matches[2]-$matches[3]&season=$matches[4]&match_day=$matches[5]',
	    'top'
	  );
	  // league - season - team
	  add_rewrite_rule(
	    'leagues/(.+?)-(.+?)-([0-9]{1})/([0-9]{4})/(.+?)/?$',
	    'index.php?pagename=leagues%2F$matches[1]%2F$matches[2]&league_name=$matches[1]-$matches[2]-$matches[3]&season=$matches[4]&team=$matches[5]',
	    'top'
	  );
	  // league
	  add_rewrite_rule(
	    'leagues/(.+?)-(.+?)-([0-9]{1})/?$',
	    'index.php?pagename=leagues%2F$matches[1]%2F$matches[2]&league_name=$matches[1]%20$matches[2]%20$matches[3]',
	    'top'
	  );
	  // league - season
	  add_rewrite_rule(
	    'leagues\/([a-z]+?)-([a-z]+?)-([0-9]{1})\/([0-9]{4})\/?$',
	    'index.php?pagename=leagues/$matches[1]/$matches[2]&league_name=$matches[1]-$matches[2]-$matches[3]&season=$matches[4]',
	    'top'
	  );
		// league entry form - type - season - club
	  add_rewrite_rule(
	    'leagues/(.+?)-entry/([0-9]{4})/(.+?)/?$',
	    'index.php?pagename=leagues%2Fentry-form&club_name=$matches[3]&season=$matches[2]&type=$matches[1]',
	    'top'
	  );
		// league - season - matchday - match
	  add_rewrite_rule(
	    'match/(.+?)-(.+?)-([0-9]{1})/([0-9]{4})/day([0-9]{1,2})/(.+?)-vs-(.+?)/?$',
	    'index.php?pagename=match%2F&league_name=$matches[1]-$matches[2]-$matches[3]&season=$matches[4]&match_day=$matches[5]&teamHome=$matches[6]&teamAway=$matches[7]',
	    'top'
	  );
	  // league - season - matchday - match
	  add_rewrite_rule(
	    'match/(.+?)-(.+?)-(.+?)/([0-9]{4})/(.+?)/(.+?)-vs-(.+?)/?$',
	    'index.php?pagename=match%2F&league_name=$matches[1]-$matches[2]-$matches[3]&season=$matches[4]&round=$matches[5]&teamHome=$matches[6]&teamAway=$matches[7]',
	    'top'
	  );
		// tournament entry form - type - season - club
	  add_rewrite_rule(
	    'tournaments/(.+?)-entry/([0-9]{4})/(.+?)/?$',
	    'index.php?pagename=tournaments%2F$matches[1]%2Fentry-form',
	    'top'
	  );
	  // tournament winners - type - season - tournament
	  add_rewrite_rule(
	    'tournaments/(.+?)/winners/(.+?)/?$',
	    'index.php?pagename=tournaments%2F$matches[1]%2Fwinners&tournament=$matches[2]&type=$matches[1]',
	    'top'
	  );
	  // tournament winners - type - season
	  add_rewrite_rule(
	    'tournaments/(.+?)/winners/?$',
	    'index.php?pagename=tournaments%2F$matches[1]%2Fwinners&type=$matches[1]',
	    'top'
	  );
	  // tournament order of play - type - season - tournament
	  add_rewrite_rule(
	    'tournaments/(.+?)/order-of-play/(.+?)/?$',
	    'index.php?pagename=tournaments%2F$matches[1]%2Forder-of-play&tournament=$matches[2]&type=$matches[1]',
	    'top'
	  );
	  // tournament order of play - type - season
	  add_rewrite_rule(
	    'tournaments/(.+?)/order-of-play/?$',
	    'index.php?pagename=tournaments%2F$matches[1]%2Forder-of-play&type=$matches[1]',
	    'top'
	  );
	  // tournament - season
	  add_rewrite_rule(
	    'tournaments/(.+?)/(.+?)-(.+?)-(.+?)/([0-9]{4})?$',
	    'index.php?pagename=tournaments%2F$matches[1]%2F$matches[2]-$matches[3]-$matches[4]&season=$matches[5]',
	    'top'
	  );
	  // cup - season (winners)
	  add_rewrite_rule(
	    'cups/(.+?)/winners/([0-9]{4})?$',
	    'index.php?pagename=cups%2F$matches[1]%2Fwinners&season=$matches[2]',
	    'top'
	  );
	  // cup - season
	  add_rewrite_rule(
	    'cups/(.+?)/(.+?)-(.+?)-(.+?)/([0-9]{4})?$',
	    'index.php?pagename=cups%2F$matches[1]%2F$matches[2]-$matches[3]-$matches[4]&season=$matches[5]',
	    'top'
	  );
	  // cup entry form - type - season - club
	  add_rewrite_rule(
	    'cups/(.+?)-entry/([0-9]{4})/(.+?)/?$',
	    'index.php?pagename=cups%2Fentry-form&club_name=$matches[3]&season=$matches[2]&type=$matches[1]',
	    'top'
	  );
		// club
	  add_rewrite_rule(
	    'clubs\/(.+?)\/?$','index.php?pagename=club&club_name=$matches[1]','top'
	  );
	}

	/**
	* add html content type to mail header
	*
	* @param array $args
	* @return args
	*/
	public function racketmanagerMail($args) {
		$headers = $args['headers'];
		if ( !$headers ) {
			$headers = array();
		}
		$headers[] = 'Content-Type: text/html; charset=UTF-8';
		$args['headers'] = $headers;
		return $args;
	}

	public function racketmanagerChangeEmailAddress($emailChange, $user, $userData) {
		global $racketmanager_shortcodes, $racketmanager;

    $vars['site_name'] = $racketmanager->site_name;
    $vars['site_url'] = $racketmanager->site_url;
    $vars['user_login'] = $userData['user_login'];
    $vars['display_name'] = $userData['display_name'];
    $vars['email_link'] = $racketmanager->admin_email;
    $emailChange['message'] = $racketmanager_shortcodes->loadTemplate( 'email-email-change', $vars, 'email' );
		return $emailChange;

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
	* Uninstall Plugin
	*/
	public static function uninstall() {
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
			if ( $this->error ) {
				echo "<div class='error'><p>".$this->message."</p></div>";
			} else {
				echo "<div id='message' class='updated fade show'><p><strong>".$this->message."</strong></p></div>";
			}
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

		$orderByString = "`name` ".$order;
		$orderBy = $orderByString;
		$seasons = $wpdb->get_results("SELECT `name`, `id` FROM {$wpdb->racketmanager_seasons} ORDER BY $orderBy" );
		$i = 0;
		foreach ( $seasons as $season ) {
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

		$searchTerms = array();
		if ( $id ) {
			$searchTerms[] = $wpdb->prepare("`id` = '%d'", intval($id));
		}
		if ( $name ) {
			$searchTerms[] = $wpdb->prepare("`name` = '%s'", $name);
		}
		$search = "";

		if ( !empty($searchTerms) ) {
			$search = " WHERE ";
			$search .= implode(" AND ", $searchTerms);
		}

		$sql = "SELECT `id`, `name` FROM {$wpdb->racketmanager_seasons} $search ORDER BY `name`";

		$season = wp_cache_get( md5($sql), 'seasons' );
		if ( !$season ) {
			$season = $wpdb->get_results( $sql );
			wp_cache_set( md5($sql), $season, 'seasons' );
		}

		if (!isset($season[0])) {
			return false;
		}

		return $season[0];

	}

	/**
	* get tournaments from database
	*
	* @param none
	* @param string $search
	* @return array
	*/
	public function getTournaments( $args = array() ) {
		global $wpdb;
		$defaults = array( 'offset' => 0, 'limit' => 99999999, 'type' => false, 'name' => false, 'entryopen' => false, 'open' => false, 'orderby' => array("name" => "DESC") );
		$args = array_merge($defaults, $args);
		extract($args, EXTR_SKIP);

		$searchTerms = array();

		if ( $type ) {
			$searchTerms[] = $wpdb->prepare("`type` = '%s'", $type);
		}

		if ( $entryopen ) {
			$searchTerms[] = "`closingdate` >= CURDATE()";
		}

		if ( $open ) {
			$searchTerms[] = "(`date` >= CURDATE() OR `date` = '0000-00-00')";
		}

		$search = "";
		if (!empty($searchTerms)) {
			$search = " WHERE ";
			$search .= implode(" AND ", $searchTerms);
		}

		$orderbyString = ""; $i = 0;
		foreach ($orderby as $order => $direction) {
			if (!in_array($direction, array("DESC", "ASC", "desc", "asc"))) {
				$direction = "ASC";
			}
			$orderbyString .= "`".$order."` ".$direction;
			if ($i < (count($orderby)-1)) {
				$orderbyString .= ",";
			}
			$i++;
		}
		$orderby = $orderbyString;

		$sql = $wpdb->prepare( "SELECT `id`, `name`, `type`, `season`, `venue`, DATE_FORMAT(`date`, '%%Y-%%m-%%d') AS date, DATE_FORMAT(`closingdate`, '%%Y-%%m-%%d') AS closingdate, `tournamentsecretary`, `numcourts`, `starttime`, `timeincrement`, `orderofplay` FROM {$wpdb->racketmanager_tournaments} $search ORDER BY $orderby LIMIT %d, %d", intval($offset), intval($limit) );

		$tournaments = wp_cache_get( md5($sql), 'tournaments' );
		if ( !$tournaments ) {
			$tournaments = $wpdb->get_results( $sql );
			wp_cache_set( md5($sql), $tournaments, 'tournaments' );
		}

		$i = 0;
		foreach ( $tournaments as $i => $tournament ) {

			$tournament = $this->formatTournament($tournament);

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
	public function getTournament( $args = array() ) {
		global $wpdb;

		$defaults = array( 'id' => false, 'name' => false );
		$args = array_merge($defaults, $args);
		extract($args, EXTR_SKIP);

		$searchString = '';

		if ( $id ) {
			$searchString = $wpdb->prepare(" WHERE `id` = '%s'", $id);
		}
		if ( $name ) {
			$searchString = $wpdb->prepare("WHERE `name` = '%s'", $name);
		}
		$sql = $wpdb->prepare( "SELECT `id`, `name`, `type`, `season`, `venue`, DATE_FORMAT(`date`, '%%Y-%%m-%%d') AS date, DATE_FORMAT(`closingdate`, '%%Y-%%m-%%d') AS closingdate, `tournamentsecretary`, `numcourts`, `starttime`, `timeincrement`, `orderofplay` FROM {$wpdb->racketmanager_tournaments} $searchString" );

		$tournament = wp_cache_get( md5($sql), 'tournament' );
		if ( !$tournament ) {
			$tournament = $wpdb->get_row( $sql );
			wp_cache_set( md5($sql), $tournament, 'tournament' );
		}

		return $this->formatTournament($tournament);
	}

	public function formatTournament($tournament) {

		$tournament->dateDisplay = ( substr($tournament->date, 0, 10) == '0000-00-00' ) ? 'TBC' : mysql2date($this->date_format, $tournament->date);
		$tournament->closingDateDisplay = ( substr($tournament->closingdate, 0, 10) == '0000-00-00' ) ? 'N/A' : mysql2date($this->date_format, $tournament->closingdate);

		if ( $tournament->venue == 0 ) {
			$tournament->venue = '';
			$tournament->venueName = 'TBC';
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

		if ( isset($tournament->closingdate) && $tournament->closingdate >= date("Y-m-d") ) {
			$tournament->open = true;
		} else {
			$tournament->open = false;
		}
		if ( isset($tournament->date) && $tournament->date >= date("Y-m-d") ) {
			$tournament->active = true;
		} else {
			$tournament->active = false;
		}
		$tournament->orderofplay = (array)maybe_unserialize($tournament->orderofplay);
		return $tournament;

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
		foreach ( $clubs as $i => $club ) {
			$club = get_club($club);

			$clubs[$i] = $club;
		}

		return $clubs;
	}

	/**
	* get competitions from database
	*
	* @param int $competitionId (default: false)
	* @param string $search
	* @return array
	*/
	public function getCompetitions( $args = array() ) {
		global $wpdb;

		$defaults = array( 'offset' => 0, 'limit' => 99999999, 'type' => false, 'name' => false, 'season' => false, 'orderby' => array("name" => "ASC") );
		$args = array_merge($defaults, $args);
		extract($args, EXTR_SKIP);

		$searchTerms = array();
		if ( $name ) {
			$name = $wpdb->esc_like(stripslashes($name)).'%';
			$searchTerms[] = $wpdb->prepare("`name` like '%s'", $name);
		}

		if ( $type ) {
			$searchTerms[] = $wpdb->prepare("`competitiontype` = '%s'", $type);
		}

		$search = "";
		if (!empty($searchTerms)) {
			$search = " WHERE ";
			$search .= implode(" AND ", $searchTerms);
		}

		$orderbyString = ""; $i = 0;
		foreach ($orderby as $order => $direction) {
			if (!in_array($direction, array("DESC", "ASC", "desc", "asc"))) {
				$direction = "ASC";
			}
			$orderbyString .= "`".$order."` ".$direction;
			if ($i < (count($orderby)-1)) {
				$orderbyString .= ",";
			}
			$i++;
		}
		$orderby = $orderbyString;

		$competitions = $wpdb->get_results($wpdb->prepare( "SELECT `name`, `id`, `num_sets`, `num_rubbers`, `type`, `settings`, `seasons`, `competitiontype` FROM {$wpdb->racketmanager_competitions} $search ORDER BY $orderby LIMIT %d, %d", intval($offset), intval($limit) ));
		$i = 0;
		foreach ( $competitions as $i => $competition ) {
			$competition->name = stripslashes($competition->name);
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
	* update competition
	*
	* @param int $competition Competition Id
	* @param string $title
	* @param array $settings
	* @return null
	*/
	public function editCompetition($competition, $title, $settings) {
		global $wpdb;

		// Set textdomain
		$options = $this->options;
		$options['textdomain'] = (string)$settings['sport'];
		update_option('leaguemanager', $options);

		if ( $settings['point_rule'] == 'user' && isset($_POST['forwin']) && is_numeric($_POST['forwin']) ) {
			$settings['point_rule'] = array( 'forwin' => intval($_POST['forwin']), 'fordraw' => intval($_POST['fordraw']), 'forloss' => intval($_POST['forloss']), 'forwin_overtime' => intval($_POST['forwin_overtime']), 'forloss_overtime' => intval($_POST['forloss_overtime']) );
		}

		foreach ( $this->getStandingsDisplayOptions() as $key => $label ) {
				$settings['standings'][$key] = isset($settings['standings'][$key]) ? 1 : 0;
		}

		$numRubbers = $settings['num_rubbers'];
		$numSets = $settings['num_sets'];
		$type = $settings['competition_type'];

		$wpdb->query( $wpdb->prepare ( "UPDATE {$wpdb->racketmanager_competitions} SET `name` = '%s', `settings` = '%s', `num_rubbers` = '%d', `num_sets` = '%d', `type` = '%s' WHERE `id` = '%d'", $title, maybe_serialize($settings), $numRubbers, $numSets, $type, $competition ) );
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
		if (!isset($team[0])) {
			return 0;
		} else {
			return $team[0]->id;
		}
	}

	/**
	* add Team to Table
	*
	* @param string $title
	* @return int
	*/
	public function addTeamtoTable( $leagueId, $teamId, $season , $custom = array(), $message = true, $rank = false, $status = false, $profile = false ) {
		global $wpdb, $racketmanager;

		$tableId = $this->checkTableEntry( $leagueId, $teamId, $season );
		if ( $tableId ) {
			$messageText = 'Team already in table';
		} else {
			if ( !$rank ) {
				$sql = "INSERT INTO {$wpdb->racketmanager_table} (`team_id`, `season`, `custom`, `league_id`) VALUES ('%d', '%s', '%s', '%d')";
				$wpdb->query( $wpdb->prepare ( $sql, $teamId, $season, maybe_serialize($custom), $leagueId) );
			} else {
				$sql = "INSERT INTO {$wpdb->racketmanager_table} (`team_id`, `season`, `custom`, `league_id`, `rank`, `status`, `profile`) VALUES ('%d', '%s', '%s', '%d', '%d', '%s', '%d')";
				$wpdb->query( $wpdb->prepare ( $sql, $teamId, $season, maybe_serialize($custom), $leagueId, $rank, $status, $profile ) );
			}
			$tableId = $wpdb->insert_id;
			$messageText = 'Table entry added';
		}
		if ( $message ) {
			$this->setMessage( __($messageText,'racketmanager') );
		}

		return $tableId;
	}

	/**
	* check for table entry
	*
	* @param int $leagueId
	* @param string $teamId
	* @param string $season
	* @return $num_teams
	*/
	public function checkTableEntry( $leagueId, $teamId, $season ) {
		global $wpdb;

		$query = $wpdb->prepare ( "SELECT `id` FROM {$wpdb->racketmanager_table} WHERE `team_id` = '%d' AND `season` = '%s' AND `league_id` = '%d'", $teamId, $season, $leagueId);
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
	* @param int $leagueId
	* @return $teamId
	*/
	public function addPlayerTeam( $player1, $player1Id, $player2, $player2Id, $contactno, $contactemail, $affiliatedclub, $leagueId ) {
		global $wpdb, $racketmanager;

		$league = get_league($leagueId);
		$type = $league->type;
		if ( $type == 'LD' ) { $type = 'XD'; }
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
		$teamId = $wpdb->insert_id;
		$captain = $racketmanager->getRosterEntry($player1Id)->player_id;
		$league = get_league($leagueId);
		$racketmanager->addTeamCompetition( $teamId, $league->competition_id, $captain, $contactno, $contactemail );

		return $teamId;
	}

	/**
	* edit player team
	*
	* @param int $teamId
	* @param int $player1Id
	* @param string $player1
	* @param int $player2Id
	* @param string $player2
	* @param string $contactno
	* @param string $contactemail
	* @param string $affiliatedclub
	* @param int $leagueId
	* @return null
	*/
	public function editPlayerTeam( $teamId, $player1, $player1Id, $player2, $player2Id, $contactno, $contactemail, $affiliatedclub, $leagueId ) {
		global $wpdb, $racketmanager;

		$league = get_league($leagueId);

		if ( $player2Id == 0 ) {
			$title = $player1;
			$roster = array($player1Id);
		} else {
			$title = $player1.' / '.$player2;
			$roster = array($player1Id, $player2Id);
		}

		$wpdb->query( $wpdb->prepare ( "UPDATE {$wpdb->racketmanager_teams} SET `title` = '%s', `affiliatedclub` = '%d', `roster` = '%s' WHERE `id` = %d", $title, $affiliatedclub, maybe_serialize($roster), $teamId ) );

		$teamCompetition = $wpdb->get_results( $wpdb->prepare("SELECT `id` FROM {$wpdb->racketmanager_team_competition} WHERE `team_id` = '%d' AND `competition_id` = '%d'", $teamId, $league->competition_id) );
		$captain = $racketmanager->getRosterEntry($player1Id)->player_id;
		if (!isset($teamCompetition[0])) {
			$racketmanager->addTeamCompetition( $teamId, $league->competition_id, $captain, $contactno, $contactemail );
		} else {
			$wpdb->query( $wpdb->prepare ( "UPDATE {$wpdb->racketmanager_team_competition} SET `captain` = '%s' WHERE `team_id` = %d AND `competition_id` = %d", $captain, $teamId, $league->competition_id ) );
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
					$userId = wp_update_user( $userdata );
					if ( is_wp_error($userId) ) {
						error_log('Unable to update user email '.$captain.' - '.$contactemail);
					}
				}
			}
		}
	}

	/**
	* add team to competition
	*
	* @param int $leagueId
	* @param int $competitionId
	* @param string $title
	* @param string $captain
	* @param string $contactno
	* @param string $contactemail
	* @param int $matchday
	* @param int $matchtime
	* @return $teamCompetitionId
	*/
	public function addTeamCompetition( $teamId, $competitionId, $captain = null, $contactno = null, $contactemail = null, $matchday = '', $matchtime = null ) {
		global $wpdb;

		$sql = "INSERT INTO {$wpdb->racketmanager_team_competition} (`team_id`, `competition_id`, `captain`, `match_day`, `match_time`) VALUES ('%d', '%d', '%d', '%s', '%s')";
		$wpdb->query( $wpdb->prepare ( $sql, $teamId, $competitionId, $captain, $matchday, $matchtime ) );
		$teamCompetitionId = $wpdb->insert_id;
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
				$userId = wp_update_user( $userdata );
				if ( is_wp_error($userId) ) {
					error_log('Unable to update user email '.$captain.' - '.$contactemail);
				}
			}
		}

		return $teamCompetitionId;
	}

	/**
	* gets roster from database
	*
	* @param array $query_args
	* @return array
	*/
	public function getRoster( $args ) {
		global $wpdb;

		$defaults = array( 'count' => false, 'team' => false, 'club' => false, 'player' => false, 'gender' => false, 'inactive' => false, 'cache' => true, 'type' => false, 'orderby' => array("display_name" => "ASC" ));
		$args = array_merge($defaults, (array)$args);
		extract($args, EXTR_SKIP);

		$searchTerms = array();
		if ($team) {
			$searchTerms[] = $wpdb->prepare("`affiliatedclub` in (select `affiliatedclub` from {$wpdb->racketmanager_teams} where `id` = '%d')", intval($team));
		}

		if ($club) {
			$searchTerms[] = $wpdb->prepare("`affiliatedclub` = '%d'", intval($club));
		}

		if ($player) {
			$searchTerms[] = $wpdb->prepare("`player_id` = '%d'", intval($player));
		}

		if ($type) {
			$searchTerms[] = "`system_record` IS NULL";
		}

		if ($inactive) {
			$searchTerms[] = "`removed_date` IS NULL";
		}

		$search = "";
		if (!empty($searchTerms)) {
			$search = implode(" AND ", $searchTerms);
		}

		$orderbyString = "";
		$i = 0;
		foreach ($orderby as $order => $direction) {
			if (!in_array($direction, array("DESC", "ASC", "desc", "asc"))) {
				$direction = "ASC";
			}
			$orderbyString .= "`".$order."` ".$direction;
			if ($i < (count($orderby)-1)) {
				$orderbyString .= ",";
			}
			$i++;
		}
		$order = $orderbyString;

		if ( $count ) {
			$sql = "SELECT COUNT(ID) FROM {$wpdb->racketmanager_roster}";
			if ( $search != "") {
				$sql .= " WHERE $search";
			}
			$cachekey = md5($sql);
			if ( isset($this->num_players[$cachekey]) && $cache && $count ) {
				return intval($this->num_players[$cachekey]);
			}

			$this->num_players[$cachekey] = $wpdb->get_var($sql);
			return $this->num_players[$cachekey];
		}

		$sql = "SELECT A.`id` as `roster_id`, B.`ID` as `player_id`, `display_name` as fullname, `affiliatedclub`, A.`removed_date`, A.`removed_user`, A.`created_date`, A.`created_user` FROM {$wpdb->racketmanager_roster} A INNER JOIN {$wpdb->users} B ON A.`player_id` = B.`ID`" ;
		if ( $search != "") {
			$sql .= " WHERE $search";
		}
		if ( $order != "") {
			$sql .= " ORDER BY $order";
		}

		$rosters = wp_cache_get( md5($sql), 'rosters' );
		if ( !$rosters ) {
			$rosters = $wpdb->get_results( $sql );
			wp_cache_set( md5($sql), $rosters, 'rosters' );
		}

		$i = 0;
		$class = '';
		foreach ( $rosters as $roster ) {
			$class = ( 'alternate' == $class ) ? '' : 'alternate';
			$rosters[$i]->class = $class;

			$rosters[$i] = (object)(array)$roster;

			$rosters[$i]->affiliatedclub = $roster->affiliatedclub;
			$rosters[$i]->roster_id = $roster->roster_id;
			$rosters[$i]->player_id = $roster->player_id;
			$rosters[$i]->fullname = $roster->fullname;
			$rosters[$i]->gender = get_user_meta($roster->player_id, 'gender', true );
			$rosters[$i]->type = get_user_meta($roster->player_id, 'racketmanager_type', true );
			$rosters[$i]->locked = get_user_meta($roster->player_id, 'locked', true );
			$rosters[$i]->locked_date = get_user_meta($roster->player_id, 'locked_date', true );
			$rosters[$i]->locked_user = get_user_meta($roster->player_id, 'locked_user', true );
			if ( $rosters[$i]->locked_user ) {
				$rosters[$i]->lockedUserName = get_userdata($rosters[$i]->locked_user)->display_name;
			} else {
				$rosters[$i]->lockedUserName = '';
			}
			$rosters[$i]->removed_date = $roster->removed_date;
			$rosters[$i]->removed_user = $roster->removed_user;
			if ( $roster->removed_user ) {
				$rosters[$i]->removedUserName = get_userdata($roster->removed_user)->display_name;
			} else {
				$rosters[$i]->removedUserName = '';
			}
			$rosters[$i]->btm = get_user_meta($roster->player_id, 'btm', true );
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
	public function getRosterEntry( $rosterId, $cache = true ) {
		global $wpdb;

		$sql = "SELECT A.`player_id` as `player_id`, A.`system_record`, `affiliatedclub`, A.`removed_date`, A.`removed_user`, A.`created_date`, A.`created_user` FROM {$wpdb->racketmanager_roster} A WHERE A.`id`= '".intval($rosterId)."'";

		$roster = wp_cache_get( md5($sql), 'rosterentry' );
		if ( !$roster || !$cache ) {
			$roster = $wpdb->get_row( $sql );
			wp_cache_set( md5($sql), $roster, 'rosterentry' );
		}
		$roster->id = $rosterId;
		$player = get_userdata($roster->player_id);
		$roster->fullname = $player->display_name;
		$roster->email = $player->user_email;
		$player = get_user_meta($roster->player_id);
		$roster->firstname = $player['first_name'][0];
		$roster->surname = $player['last_name'][0];
		$roster->gender = isset($player['gender']) ? $player['gender'][0] : '';
		$roster->btm = isset($player['btm']) ? $player['btm'][0] : '';
		$roster->locked = isset($player['locked']) ? $player['locked'][0] : '';
		$roster->locked_date = isset($player['locked_date']) ? $player['locked_date'][0] : '';
		$roster->locked_user = isset($player['locked_user']) ? $player['locked_user'][0] : '';

		return $roster;
	}

	/**
	* delete Roster
	*
	* @param int $teamId
	* @return boolean
	*/
	public function delRoster( $rosterId ) {
		global $wpdb;

		$userid = get_current_user_id();
		$wpdb->query( $wpdb->prepare("UPDATE {$wpdb->racketmanager_roster} SET `removed_date` = NOW(), `removed_user` = %d WHERE `id` = '%d'", $userid, $rosterId) );
		$this->setMessage( __('Player removed from club', 'racketmanager') );

		return true;
	}

	/**
	* get list of players
	*
	* @param array $query_args
	* @return array
	*/
	public function getPlayers( $args ) {
		$defaults = array( 'name' => false, 'cache' => true );
		$args = array_merge($defaults, (array)$args);
		extract($args, EXTR_SKIP);
		$cachekey = md5(implode(array_map(function($entry) { if(is_array($entry)) { return implode($entry); } else { return $entry; } }, $args)) );
		$search = '';
		if ($name) {
			$search = '*'.$name.'*';
			$searchTerms = 'display_name';
		}

		$orderbyString = 'display_name';
		$order = 'ASC';

		// use cached object
		if ( isset($this->players[$cachekey]) && $cache ) {
			return $this->players[$cachekey];
		}

		$searchArgs = array();
		$searchArgs['orderby'] = $orderbyString;
		$searchArgs['order'] = $order;
		if ( $search ) {
			$searchArgs['search'] = $search;
			$searchArgs['search_columns'] = array($searchTerms);
		}
		$players = get_users( $searchArgs);
		if ( !$players ) {
			return false;
		}

		$i = 0;
		foreach ( $players as $player ) {

			$players[$i] = (object)(array)$player;
			$players[$i]->id = $player->ID;
			$players[$i]->fullname = $player->display_name;
			$players[$i]->firstname = get_user_meta($player->ID, 'first_name', true );
			$players[$i]->lastname = get_user_meta($player->ID, 'last_name', true );
			$players[$i]->gender = get_user_meta($player->ID, 'gender', true );
			$players[$i]->removed_date = get_user_meta($player->ID, 'remove_date', true );
			$players[$i]->btm = get_user_meta($player->ID, 'btm', true );
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

		if ($player_id) {
			$player = get_user_by( 'id', $player_id );
		} elseif ($fullname) {
			$player = get_user_by( 'slug', sanitize_title($fullname) );
		}

		if ( !$player ) {
			return false;
		}

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
		if ( !$player ) {
			return false;
		}

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
	* @param string $email (optional)
	* @return int | false
	*/
	public function addPlayer( $firstname, $surname, $gender, $btm, $email = false, $message = true ) {

		$userdata = array();
		$userdata['first_name'] = $firstname;
		$userdata['last_name'] = $surname;
		$userdata['display_name'] = $firstname.' '.$surname;
		$userdata['user_login'] = $firstname.'.'.$surname;
		$userdata['user_pass'] = $userdata['user_login'].'1';
		if ( $email ) {
			$userdata['user_email'] = $email;
		}
		$userId = wp_insert_user( $userdata );
		if ( ! is_wp_error( $userId ) ) {
			update_user_meta($userId, 'show_admin_bar_front', false );
			update_user_meta($userId, 'gender', $gender);
			if ( isset($btm) ) {
				update_user_meta($userId, 'btm', $btm);
			}
		}

		if ( $message ) {
			$this->setMessage( __('Player added', 'racketmanager') );
		}

		return $userId;
	}

	/**
	* get matches without using league object
	*
	* @param array $matchArgs
	* @return array $matches
	*/
	public function getMatches( $matchArgs ) {
		global $wpdb;

		$defaults = array( 'leagueId' => false, 'season' => false, 'final' => false, 'competitiontype' => false, 'competitionseason' => false, 'orderby' => array('league_id' => 'ASC', 'id' => 'ASC'), 'competitionId' => false, 'confirmed' => false, 'match_date' => false, 'competition_type' => false, 'time' => false, 'history' => false, 'affiliatedClub' => false, 'league_name' => false, 'homeTeam' => false, 'awayTeam' => false, 'matchDay' => false, 'competition_name' => false, 'homeAffiliatedClub' => false, 'count' => false );
		$matchArgs = array_merge($defaults, (array)$matchArgs);
		extract($matchArgs, EXTR_SKIP);

		if ( $count ) {
			$sql = "SELECT COUNT(*) FROM {$wpdb->racketmanager_matches} WHERE 1 = 1";
		} else {
			$sql = "SELECT `final` AS final_round, `group`, `home_team`, `away_team`, DATE_FORMAT(`date`, '%Y-%m-%d %H:%i') AS date, DATE_FORMAT(`date`, '%e') AS day, DATE_FORMAT(`date`, '%c') AS month, DATE_FORMAT(`date`, '%Y') AS year, DATE_FORMAT(`date`, '%H') AS `hour`, DATE_FORMAT(`date`, '%i') AS `minutes`, `match_day`, `location`, `league_id`, `home_points`, `away_points`, `winner_id`, `loser_id`, `post_id`, `season`, `id`, `custom`, `confirmed`, `home_captain`, `away_captain`, `comments` FROM {$wpdb->racketmanager_matches} WHERE 1 = 1";
		}

		if ( $match_date ) {
			$sql .= " AND DATEDIFF('". htmlspecialchars(strip_tags($match_date))."', `date`) = 0";
		}
		if ( $competition_type ) {
			$sql .= " AND `league_id` in (select `id` from {$wpdb->racketmanager} WHERE `competition_id` in (SELECT `id` FROM {$wpdb->racketmanager_competitions} WHERE `competitiontype` = '".$competition_type."'))";
		}
		if ( $competition_name ) {
			$sql .= " AND `league_id` in (select `id` from {$wpdb->racketmanager} WHERE `competition_id` in (SELECT `id` FROM {$wpdb->racketmanager_competitions} WHERE `name` = '".$competition_name."'))";
		}

		if ( $competitionId ) {
			$sql .= " AND `league_id` in (select `id` from {$wpdb->racketmanager} WHERE `competition_id` = '".$competitionId."')";
		}

		if ( $leagueId ) {
			$sql .= " AND `league_id`  = '".$leagueId."'";
		}
		if ( $league_name ) {
			$sql .= " AND `league_id` in (select `id` from {$wpdb->racketmanager} WHERE `title` = '".$league_name."')";
		}
		if ( $season ) {
			$sql .= " AND `season`  = '".$season."'";
		}
		if ( $final ) {
			$sql .= " AND `final`  = '".$final."'";
		}
		if ( $competitiontype ) {
			$sql .= " AND `league_id` in (select l.`id` from {$wpdb->racketmanager} l, {$wpdb->racketmanager_competitions} c WHERE l.`competition_id` = c.`id` AND c.`competitiontype` = '".$competitiontype."'";
			if ( $competitionseason ) {
				$sql .= " AND c.`name` LIKE '".$competitionseason."%'";
			}
			$sql .= ")";
		}

		if ( $confirmed ) {
			$sql .= " AND `confirmed` in ('P','A','C')";
		}

		// get only finished matches with score for time 'latest'
		if ( $time == 'latest' ) {
			$sql .= " AND (`home_points` != '' OR `away_points` != '')";
		}
		if ( $time == 'outstanding' ) {
			$sql .= " AND `date` <= NOW() AND `winner_id` = 0 AND `confirmed` IS NULL";
		}

		// get only updated matches in specified period for history
		if ( $history ) {
			$sql .= " AND `updated` >= NOW() - INTERVAL ".$history." DAY";
		}

		if ( $affiliatedClub ) {
			$sql .= " AND (`home_team` IN (SELECT `id` FROM {$wpdb->racketmanager_teams} WHERE `affiliatedclub` = ".$affiliatedClub.") OR `away_team` IN (SELECT `id` FROM {$wpdb->racketmanager_teams} WHERE `affiliatedclub` = ".$affiliatedClub."))";
		}
		if ( $homeAffiliatedClub ) {
			$sql .= " AND `home_team` IN (SELECT `id` FROM {$wpdb->racketmanager_teams} WHERE `affiliatedclub` = ".$homeAffiliatedClub.")";
		}
		if (!empty($homeTeam)) {
			$sql .= " AND `home_team` = ".$homeTeam." ";
		}
		if (!empty($awayTeam)) {
			$sql .= " AND `away_team` = ".$awayTeam." ";
		}
		if ( $matchDay && intval($matchDay) > 0 ) {
			$sql .= " AND `match_day` = ".$matchDay." ";
		}

		if ( $count ) {
			$matches = intval($wpdb->get_var($sql));
		} else {
			$orderbyString = "";
			$i = 0;
			foreach ($orderby as $order => $direction) {
				$orderbyString .= "`".$order."` ".$direction;
				if ($i < (count($orderby)-1)) {
					$orderbyString .= ",";
				}
				$i++;
			}
			$sql .= " ORDER BY ".$orderbyString;

			// get matches
			$matches = $wpdb->get_results($sql);
			$class = '';

			foreach ( $matches as $i => $match ) {

				$class = ( 'alternate' == $class ) ? '' : 'alternate';
				$match = get_match($match);
				if ( $match->final_round == 'final' ) {
					if ( !is_numeric($match->home_team) ) {
						$match->prevHomeMatch = $this->getPrevRoundMatches($match->home_team, $match->season, $match->league);
					}
					if ( !is_numeric($match->away_team) ) {
						$match->prevAwayMatch = $this->getPrevRoundMatches($match->away_team, $match->season, $match->league);
					}
				}
				$match->class = $class;
				$matches[$i] = $match;
			}
		}

		return $matches;
	}

	/**
	* get details of previous round match
	*
	* @param string $teamRef
	* @param string $season
	* @param string $leagueId
	* @return array $prevMatch
	*/
	public function getPrevRoundMatches($teamRef, $season, $leagueId) {
		global $racketmanager;
		$team = explode("_", $teamRef);
		$league = get_league($leagueId);
		$prevMatches = $league->getMatches( array('final' => $team[1], 'season' => $season, "orderby" => array("id" => "ASC") ));
		if ( $prevMatches ) {
			$matchRef = $team[2] - 1;
			return $prevMatches[$matchRef];
		} else {
			return false;
		}
	}

	/**
	* show winners
	*
	* @param string $season
	* @param string $seasonType
	* @return void
	*/
	public function getWinners( $season, $seasonType, $competitionType = 'tournament' ) {
		global $racketmanager, $wpdb;

		$seasonType = $wpdb->esc_like(stripslashes($seasonType)).'%';

		$sql = "SELECT l.`title` ,wt.`title` AS `winner` ,lt.`title` AS `loser`, m.`id`, m.`home_team`, m.`away_team`, m.`winner_id` AS `winnerId`, m.`loser_id` AS `loserId` FROM {$wpdb->racketmanager_matches} m, {$wpdb->racketmanager} l, {$wpdb->racketmanager_competitions} c, {$wpdb->racketmanager_teams} wt, {$wpdb->racketmanager_teams} lt WHERE `league_id` = l.`id` AND l.`competition_id` = c.`id` AND c.`competitiontype` = '%s' AND c.`name` like '%s' AND m.`final` = 'FINAL' AND m.`season` = '%d' AND m.`winner_id` = wt.`id` AND m.`loser_id` = lt.`id` order by 1";

		$sql = $wpdb->prepare($sql, $competitionType, $seasonType, $season);
		$winners = $wpdb->get_results($sql);

		if ( !$winners ) {
			return false;
		}

		$i = 0;
		foreach ( $winners as $winner ) {

			$match = get_match($winner->id);
			$winners[$i] = (object)(array)$winner;
			$winners[$i]->league = $winner->title;
			$winners[$i]->winner = $winner->winner;
			if ( $winner->winnerId == $winner->home_team ) {
				$winners[$i]->winnerClub = $match->teams['home']->affiliatedclubname;
			} else {
				$winners[$i]->winnerClub = $match->teams['away']->affiliatedclubname;
			}
			$winners[$i]->loser = $winner->loser;
			if ( $winner->loserId == $winner->home_team ) {
				$winners[$i]->loserClub = $match->teams['home']->affiliatedclubname;
			} else {
				$winners[$i]->loserClub = $match->teams['away']->affiliatedclubname;
			}

			$i++;
		}

		return $winners;

	}

	/**
	* get confirmation email
	*
	* @param boolean $championship
	* @param string $type
	* @return string $email
	*/
	public function getConfirmationEmail($type) {
		global $racketmanager;
		$rmOptions = $racketmanager->getOptions();
		return isset($rmOptions[$type]['resultConfirmationEmail']) ? $rmOptions[$type]['resultConfirmationEmail'] : '';
	}

	/**
	* get available league standing status
	*
	* @return array
	*/
	public function getStandingStatus() {
		return array(
			'C' => __( 'Champions', 'racketmanager' )
			,'P1' => __( 'Promoted in first place', 'racketmanager')
			,'P2' => __( 'Promoted in second place', 'racketmanager')
			,'P3' => __( 'Promoted in third place', 'racketmanager')
			,'W1' => __( 'League winners but league locked', 'racketmanager')
			,'W2' => __( 'Second place but league locked', 'racketmanager')
			,'RB' => __( 'Relegated in bottom place', 'racketmanager')
			,'RT' => __( 'Relegated as team in division above', 'racketmanager')
			,'BT' => __( 'Finished bottom but not relegated', 'racketmanager')
			,'NT' => __( 'New team', 'racketmanager')
			,'W' => __( 'Withdrawn', 'racketmanager')
		);
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

		if ($table == "teams") { $table = $wpdb->racketmanager_teams; }
		elseif ($table == "table") { $table = $wpdb->racketmanager_table; }
		elseif ($table == "matches") { $table = $wpdb->racketmanager_matches; }
		elseif ($table == "roster") { $table = $wpdb->racketmanager_roster; }
		elseif ($table == "leagues") { $table = $wpdb->racketmanager; }
		elseif ($table == "seasons") { $table = $wpdb->racketmanager_seasons; }
		elseif ($table == "competititons") { $table = $wpdb->racketmanager_competititons; }
		else { return false; }

		$sql = $wpdb->prepare("SHOW COLUMNS FROM {$table} LIKE %s", $column);

		$res = wp_cache_get( md5($sql), 'racketmanager' );

		if ( !$res ) {
			$res = $wpdb->query( $sql );
			wp_cache_set( md5($sql), $res, 'racketmanager' );
		}
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
				$errorMsg = $userId->get_error_message();
				error_log('Unable to update user email '.$player.' - '.$contactEmail.' - '.$errorMsg);
				return false;
			}
		}
		return true;
	}

	/**
	* update club
	*
	* @param object $club
	* @return boolean
	*/
	public function updateClub( $club ) {
		global $wpdb;

		$wpdb->query( $wpdb->prepare ( "UPDATE {$wpdb->racketmanager_clubs} SET `name` = '%s', `type` = '%s', `shortcode` = '%s',`matchsecretary` = '%d', `contactno` = '%s', `website` = '%s', `founded`= '%s', `facilities` = '%s', `address` = '%s', `latitude` = '%s', `longitude` = '%s' WHERE `id` = %d", $club->name, $club->type, $club->shortcode, $club->matchsecretary, $club->contactno, $club->website, $club->founded, $club->facilities, $club->address, $club->latitude, $club->longitude, $club->id ) );

		if ( $club->matchsecretary != '') {
			$currentContactNo = get_user_meta( $club->matchsecretary, 'contactno', true);
			$currentContactEmail = get_userdata($club->matchsecretary)->user_email;
			if ($currentContactNo != $club->matchSecretaryContactNo ) {
				update_user_meta( $club->matchsecretary, 'contactno', $club->matchSecretaryContactNo );
			}
			if ($currentContactEmail != $club->matchSecretaryEmail ) {
				$userdata = array();
				$userdata['ID'] = $club->matchsecretary;
				$userdata['user_email'] = $club->matchSecretaryEmail;
				$userId = wp_update_user( $userdata );
				if ( is_wp_error($userId) ) {
					$errorMsg = $userId->get_error_message();
					error_log('Unable to update user email '.$club->matchsecretary.' - '.$club->matchSecretaryEmail.' - '.$errorMsg);
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
	* @param string $competitionType
	* @param string $matchStatus
	* @return boolean
	*/
	public function getMatchUpdateAllowed($homeTeam, $awayTeam, $competitionType, $matchStatus) {
		$userCanUpdate = false;
		$return = array();
		$userType = '';
		$userTeam = '';
		$message = '';
		if ( is_user_logged_in() ) {
			$options = $this->getOptions();
			$userid = get_current_user_id();
			$matchCapability = $options[$competitionType]['matchCapability'];
			$resultEntry = $options[$competitionType]['resultEntry'];

			if ( isset($homeTeam) && isset($awayTeam) && isset($homeTeam->affiliatedclub) && isset($awayTeam->affiliatedclub) ) {
				if ( $userid ) {
					if ( !current_user_can( 'manage_racketmanager' ) ) {
						if ( $matchCapability == 'roster' ) {
							$club = get_club($homeTeam->affiliatedclub);
							$homeRoster = $club->getRoster( array( 'count' => true, 'player' => $userid, 'inactive' => true ) );
							if ( $homeRoster != 0 ) {
								if ( $club->matchsecretary == $userid ) {
									$userType = 'matchsecretary';
								} else {
									$userType = 'player';
								}
								$userTeam = 'home';
								$userCanUpdate = true;
							} elseif ( $resultEntry == 'either' ) {
								$club = get_club($awayTeam->affiliatedclub);
								$awayRoster = $club->getRoster( array( 'count' => true, 'player' => $userid, 'inactive' => true ) );
								if ( $awayRoster != 0 ) {
									if ( $club->matchsecretary == $userid ) {
										$userType = 'matchsecretary';
									} else {
										$userType = 'player';
									}
									$userTeam = 'away';
									$userCanUpdate = true;
								}
							} else {
								$message = 'notTeamPlayer';
							}
						} elseif ( $matchCapability == 'captain' ) {
							if ( isset($homeTeam->captainId) && $userid == $homeTeam->captainId ) {
								$userType = 'captain';
								$userTeam = 'home';
								$userCanUpdate = true;
							} elseif ( $resultEntry == 'home' && ( isset($awayTeam->captainId) && $userid == $awayTeam->captainId ) ) {
								if ( $matchStatus == 'P') {
									$userType = 'captain';
									$userTeam = 'away';
									$userCanUpdate = true;
								}
							} elseif ( $resultEntry == 'either' && ( isset($awayTeam->captainId) && $userid == $awayTeam->captainId ) ) {
								$userType = 'captain';
								$userTeam = 'away';
								$userCanUpdate = true;
							} else {
								$message = 'notCaptain';
							}
						}
					} else {
						$userType = 'admin';
						$userTeam = '';
						$userCanUpdate = true;
					}
				} else {
					$message = 'notLoggedIn';
				}
			} else {
				$message = 'notTeamSet';
			}
		} else {
			$message = 'notLoggedIn';
		}
		array_push($return,$userCanUpdate,$userType,$userTeam,$message);
		return $return;
	}

	/**
  * notify teams for next round
  *
  * @param object $match next match
  * @return void
  */
  public function notifyNextMatchTeams($match) {
    global $racketmanager;

	if ( !(isset($match->teams['home']->contactemail) && $match->teams['home']->contactemail > '') && !(isset($match->teams['away']->contactemail) && $match->teams['away']->contactemail > '' ) ) {
      return false;
    }
    if ( ( $match->teams['home']->id == -1 || $match->teams['away']->id == -1 ) || ( !isset($match->custom['host']) ) ) {
      return false ;
    }
    $to = array();
    if ( isset($match->teams['home']->contactemail) && $match->teams['home']->contactemail > '' ) { array_push($to, $match->teams['home']->contactemail); }
    if ( isset($match->teams['away']->contactemail) && $match->teams['away']->contactemail > '' ) { array_push($to, $match->teams['away']->contactemail); }
    $emailFrom = $racketmanager->getConfirmationEmail($match->league->competitionType);
    $organisationName = $racketmanager->site_name;
    $roundName = $match->league->championship->finals[$match->final_round]['name'];
    $messageArgs = array();
    $messageArgs['round'] = $roundName;
    $messageArgs['competitiontype'] = $match->league->competitionType;
    if ( $match->league->competitionType == 'tournament' ) {
      $leagueTitle = explode(" ", $match->league->title);
      $tournamentType = $leagueTitle[0];
      $tournaments = $racketmanager->getTournaments( array( 'type' => $tournamentType, 'open' => true ) );
      $tournament = $tournaments[0];
      if ( $emailFrom == '' ) { $emailFrom = $tournament->tournamentSecretaryEmail; }
      $messageArgs['tournament'] = $tournament->id;
    } elseif ( $match->league->competitionType == 'cup' ) {
      $messageArgs['competition'] = $match->league->competitionName;
    }
	$messageArgs['emailfrom'] = $emailFrom;
    $emailMessage = racketmanager_match_notification($match->id, $messageArgs );
    $headers = array();
	$headers[] = 'From: '.ucfirst($match->league->competitionType).' Secretary <'.$emailFrom.'>';
	$headers[] = 'cc: '.ucfirst($match->league->competitionType).' Secretary <'.$emailFrom.'>';
    $subject = $organisationName." - ".$match->league->title." - ".$roundName." - Match Details";
    wp_mail($to, $subject, $emailMessage, $headers);
	return true;
  }

	/**
  * get from line for email
  *
  * @return string from line
  */
	public function getFromUserEmail() {
		return 'From: '.wp_get_current_user()->display_name.' <'.$this->admin_email.'>';
	}

	/**
  * notify clubs entries open
  *
  * @return string notifivation status
  */
	public function notifyEntryOpen($competitionType, $season, $competitionSeason) {
		global $racketmanager_shortcodes;

		$return = array();
		if ( !$competitionSeason ) {
			$return['error'] = true;
			$return['msg'] = __('Type not selected','racketmanager');
		} else {
			$clubs = $this->getClubs();

			$headers = array();
			$fromEmail = $this->getConfirmationEmail($competitionType);
			if ( $fromEmail ) {
				$headers[] = 'From: '.ucfirst($competitionType).'Secretary <'.$fromEmail.'>';
				$headers[] = 'cc: '.ucfirst($competitionType).'Secretary <'.$fromEmail.'>';
				$organisationName = $this->site_name;

				foreach ($clubs as $club) {
					$emailSubject = $this->site_name." - ".ucfirst($competitionSeason)." ".$season." ".ucfirst($competitionType)." Entry Open - ".$club->name;
					$emailTo = $club->matchSecretaryName.' <'.$club->matchSecretaryEmail.'>';
					$actionURL = $this->site_url.'/'.$competitionType.'s/'.$competitionSeason.'-entry/'.$season.'/'.seoUrl($club->shortcode);
					$emailMessage = $racketmanager_shortcodes->loadTemplate( 'competition-entry-open', array( 'emailSubject' => $emailSubject, 'fromEmail' => $fromEmail, 'actionURL' => $actionURL, 'organisationName' => $organisationName, 'season' => $season, 'competitionSeason' => $competitionSeason, 'competitionType' => $competitionType, 'club' => $club ), 'email' );
					wp_mail($emailTo, $emailSubject, $emailMessage, $headers);
					$messageSent = true;
				}
				if ( $messageSent ) {
					$return['msg'] = __('Match secretaries notified','racketmanager');
				} else {
					$return['error'] = true;
					$return['msg'] = __('No notification','racketmanager');
				}
			} else {
				$return['error'] = true;
				$return['msg'] = __('No secretary email','racketmanager');
			}
		}
		return $return;
	}

	/**
  * user favourite
  *
  * @return boolean true/false
  */
	public function userFavourite($type, $id) {

		if ( !is_user_logged_in() ) {
			return false;
		}
		$userId = get_current_user_id();
		$metaKey = 'favourite-'.$type;
		$favourites = get_user_meta($userId, $metaKey);
		$favouriteFound = (array_search($id, $favourites,true));
		if ( is_numeric($favouriteFound) ) {
			return true;
		}
		return false;
	}

	/**
  * notify favourites
  *
	* @param object $users
	* @param object $matches
  * @return null
  */
	public function notifyFavourites($matches, $league) {
		$users = $this->getUsersForFavourite('league', $league->id);
		if ( $users ) {
			$favourite = $league->title;
			$this->notifyFavouritesEmail($favourite, $league, $users, $matches);
		}

		$clubs = array();
		foreach ($matches as $i => $match) {
			if ( isset($match->teams['home']->affiliatedclub) ) {
				$clubs[$i]['id'] = $match->teams['home']->affiliatedclub;
				$clubs[$i]['name'] = $match->teams['home']->affiliatedclubname;
				$clubs[$i]['matches'] = array();
				$clubs[$i]['matches'][] = $match;
			}
			if ( isset($match->teams['away']->affiliatedclub) && isset($match->teams['home']->affiliatedclub) && $match->teams['home']->affiliatedclub != $match->teams['away']->affiliatedclub ) {
				$clubs[$i]['id'] = $match->teams['away']->affiliatedclub;
				$clubs[$i]['name'] = $match->teams['away']->affiliatedclubname;
				$clubs[$i]['matches'] = array();
				$clubs[$i]['matches'][] = $match;
			}
		}
		$clubs = array_unique($clubs, SORT_REGULAR);
		foreach ($clubs as $club) {
			$users = $this->getUsersForFavourite('club', $club['id']);
			if ( $users ) {
				$favourite = $club['name'];
				$this->notifyFavouritesEmail($favourite, $league, $users, $club['matches']);
			}
		}
	}

	/**
  * get users for favourite
  *
	* @param string $type
	* @param string $key
  * @return array list of users
  */
	public function getUsersForFavourite($type, $key) {
		return get_users(array(
			'meta_key' => 'favourite-'.$type,
			'meta_value' => $key,
			'fields' => 'ids'
		));
	}

	/**
  * send emails to users for favourite updates
  *
	* @param string $favourite
	* @param object $league
	* @param array 	$users
	* @param array 	$matches
  * @return null
  */
	public function notifyFavouritesEmail($favourite, $league, $users, $matches) {
		global $racketmanager_shortcodes;

		$headers = array();
		$fromEmail = $this->getConfirmationEmail($league->competitionType);
		$headers[] = 'From: '.ucfirst($league->competitionType).' Secretary <'.$fromEmail.'>';
		$organisationName = $this->site_name;
		$emailSubject = $this->site_name." - ".$league->title." Result Notification";
		$favouriteURL = $this->site_url.'/member-account/favourites';
		$matchURL = $this->site_url.'/'.$league->competitionType.'s/'.seoUrl($league->title).'/'.$league->current_season['name'].'/';

		foreach ( $users as $user ) {
			$userDtls = get_userdata($user);
			$emailTo = $userDtls->display_name.' <'.$userDtls->user_email.'>';
			$emailMessage = $racketmanager_shortcodes->loadTemplate( 'favourite-notification', array( 'emailSubject' => $emailSubject, 'fromEmail' => $fromEmail, 'matchURL' => $matchURL, 'favouriteURL' => $favouriteURL, 'favouriteTitle' => $favourite, 'organisationName' => $organisationName, 'user' => $userDtls, 'matches' => $matches ), 'email' );
			wp_mail($emailTo, $emailSubject, $emailMessage, $headers);
		}
	}

	public function showMatchScreen($match) {
		global $racketmanager, $championship;

		$userCanUpdateArray = $racketmanager->getMatchUpdateAllowed($match->teams['home'], $match->teams['away'], $match->league->competitionType, $match->confirmed);
		$userCanUpdate = $userCanUpdateArray[0];
		$userMessage = $userCanUpdateArray[3];
		if ( $match->final_round == '' ) {
			$match->round = '';
			$match->type = 'league';
		} else {
			$match->round = $match->final_round;
			$match->type = 'tournament';
		}
		$league = get_league($match->league_id);
		$numSets = $league->num_sets;
		$tabbase = 0;
		?>
		<div id="matchrubbers">
			<div id="matchheader">
				<div class="row justify-content-between" id="match-header-1">
					<div class="col-auto leaguetitle"><?php echo $league->title ?></div>
					<div class="col-auto matchday">
						<?php if ( $league->mode == 'championship' ) {
							echo $league->championship->getFinalName($match->final_round);
						} else {
							echo 'Week'.$match->match_day;
						} ?>
					</div>
					<div class="col-auto matchdate"><?php echo substr($match->date,0,10) ?></div>
				</div>
				<div class="row justify-content-center" id="match-header-2">
					<?php if ( $league->mode != 'championship' ) { ?>
						<div class="col-auto matchtitle"><?php echo $match->match_title ?></div>
					<?php } ?>
				</div>
			</div>
			<form id="match-view" action="#" method="post" onsubmit="return checkSelect(this)">
				<?php wp_nonce_field( 'scores-match' ) ?>

				<input type="hidden" name="current_league_id" id="current_league_id" value="<?php echo $match->league_id ?>" />
				<input type="hidden" name="current_match_id" id="current_match_id" value="<?php echo $match->id ?>" />
				<input type="hidden" name="current_season" id="current_season" value="<?php echo $match->season ?>" />
				<input type="hidden" name="home_team" value="<?php echo $match->home_team ?>" />
				<input type="hidden" name="away_team" value="<?php echo $match->away_team ?>" />
				<input type="hidden" name="match_type" value="<?php echo $match->type ?>" />
				<input type="hidden" name="match_round" value="<?php echo $match->round ?>" />

				<div class="row mb-3">
					<div class="col-4 text-center"><strong><?php _e( 'Team', 'racketmanager' ) ?></strong></div>
					<div class="col-4 text-center"><strong><?php _e('Sets', 'racketmanager' ) ?></strong></div>
					<div class="col-4 text-center"><strong><?php _e( 'Team', 'racketmanager' ) ?></strong></div>
				</div>
				<div class="row align-items-center mb-3">
					<div class="col-4 text-center">
						<?php echo $match->teams['home']->title ?>
					</div>
					<div class="col-4 align-self-center">
						<div class="row text-center mb-1">
							<?php for ( $i = 1; $i <= $numSets; $i++ ) {
								if (!isset($match->sets[$i])) {
									$match->sets[$i] = array('player1' => '', 'player2' => '');
								}
								$colspan = 12 / $numSets;
								$tabindex = $tabbase + 10 + $i; ?>
								<div class="col-<?php echo $colspan ?> col-sm-12 col-lg-<?php echo $colspan ?>">
									<input tabindex="<?php echo $tabindex ?>" class="points" type="text" size="2" id="set_<?php echo $i ?>_player1" name="custom[sets][<?php echo $i ?>][player1]" value="<?php echo $match->sets[$i]['player1'] ?>" />
									-
									<?php $tabindex = $tabbase + 11 + $i; ?>
									<input tabindex="<?php echo $tabindex ?>" class="points" type="text" size="2" id="set_<?php echo $i ?>_player2" name="custom[sets][<?php echo $i ?>][player2]" value="<?php echo $match->sets[$i]['player2'] ?>" />
								</div>
							<?php } ?>
						</div>
					</div>
					<div class="col-4 text-center">
						<?php echo $match->teams['away']->title ?>
					</div>
				</div>
				<div class="row text-center mb-3">
					<div class="col-12">
						<input class="points" type="text" size="2" readonly id="home_points" name="home_points" value="<?php echo isset($match->home_points) ? $match->home_points : '' ?>" />
						<input class="points" type="text" size="2" readonly id="away_points" name="away_points[" value="<?php echo isset($match->away_points) ? $match->away_points : '' ?>" />
					</div>
				</div>
				<div class="form-floating">
					<textarea class="form-control result-comments" tabindex="490" placeholder="Leave a comment here" name="resultConfirmComments" id="resultConfirmComments"><?php echo $match->comments ?></textarea>
					<label for="resultConfirmComments"><?php _e( 'Comments', 'racketmanager' ) ?></label>
				</div>
				<div class="mb-3">
					<?php if ( isset($match->updated_user) ) { ?>
						<div class="row">
							<div class="col-auto">
								Updated By:
							</div>
							<div class="col-auto">
								<?php echo $racketmanager->getPlayerName($match->updated_user); ?>
							</div>
						</div>
						<?php if ( isset($match->updated) ) { ?>
							<div class="row">
								<div class="col-auto">
									On:
								</div>
								<div class="col-auto">
									<?php echo $match->updated; ?>
								</div>
							</div>
						<?php } ?>
					<?php } ?>
				</div>
				<?php if ( $userCanUpdate ) {
					if (current_user_can( 'update_results' ) || $match->confirmed == 'P' || $match->confirmed == null) { ?>
						<div class="row mb-3">
							<div class="col-12">
								<input type="hidden" name="updateMatch" id="updateMatch" value="results" />
								<button tabindex="500" class="button button-primary" type="button" id="updateMatchResults" onclick="Racketmanager.updateMatchResults(this)">Update Result</button>
							</div>
						</div>
						<div class="row mb-3">
							<div id="updateResponse" class="updateResponse"></div>
						</div>
					<?php } else { ?>
						<div class="row mb-3">
							<div class="col-12 updateResponse message-error">
								<?php _e('Updates not allowed', 'racketmanager') ?>
							</div>
						</div>
					<?php } ?>
				<?php } else { ?>
					<div class="row mb-3 justify-content-center">
						<div class="col-auto">
							<?php if ( $userMessage == 'notLoggedIn' ) { ?>
								You need to <a href="<?php echo wp_login_url( $_SERVER['REQUEST_URI'] ); ?>">login</a> to update the result.
							<?php } else {
								_e('User not allowed to update result', 'racketmanager');
							} ?>
						</div>
					</div>
				<?php } ?>
			</form>
		</div>
	<?php	}

	public function showRubbersScreen($match) {
		global $racketmanager, $league, $match;
		if ( $match->final_round == '' ) {
			$match->round = '';
			$match->type = 'league';
		} else {
			$match->round = $match->final_round;
			$match->type = 'tournament';
		}
		$match->num_sets = $match->league->num_sets;
		$match->num_rubbers = $match->league->num_rubbers;
		$matchType = $match->league->type;
		switch ($matchType) {
			case 'MD':
			$homeRoster['m'] = $racketmanager->getRoster(array('team' => $match->home_team, 'gender' => 'M'));
			$awayRoster['m'] = $racketmanager->getRoster(array('team' => $match->away_team, 'gender' => 'M'));
			break;
			case 'WD':
			$homeRoster['f'] = $racketmanager->getRoster(array('team' => $match->home_team, 'gender' => 'F'));
			$awayRoster['f'] = $racketmanager->getRoster(array('team' => $match->away_team, 'gender' => 'F'));
			break;
			case 'XD':
			case 'LD':
			$homeRoster['m'] = $racketmanager->getRoster(array('team' => $match->home_team, 'gender' => 'M'));
			$homeRoster['f'] = $racketmanager->getRoster(array('team' => $match->home_team, 'gender' => 'F'));
			$awayRoster['m'] = $racketmanager->getRoster(array('team' => $match->away_team, 'gender' => 'M'));
			$awayRoster['f'] = $racketmanager->getRoster(array('team' => $match->away_team, 'gender' => 'F'));
			break;
			default:
			$homeRoster['m'] = array();
			$homeRoster['f'] = array();
			$awayRoster['m'] = array();
			$awayRoster['f'] = array();
		}
		$this->buildRubbersScreen($match, $homeRoster, $awayRoster);
	}
	/**
	* build screen to allow input of match rubber scores
	*
	*/
	public function buildRubbersScreen($match, $homeRoster, $awayRoster) {
		global $racketmanager, $league, $match;
		$userCanUpdateArray = $racketmanager->getMatchUpdateAllowed($match->teams['home'], $match->teams['away'], $match->league->competitionType, $match->confirmed);
		$userCanUpdate = $userCanUpdateArray[0];
		$userType = $userCanUpdateArray[1];
		$userTeam = $userCanUpdateArray[2];
		$userMessage = $userCanUpdateArray[3];
		$updatesAllowed = true;
		if ( $match->confirmed == 'P' && $userType != 'admin' ) {
			$updatesAllowed = false;
		}
		?>
		<div id="matchrubbers" class="rubber-block">
			<div id="matchheader">
				<div class="row justify-content-between" id="match-header-1">
					<div class="col-auto leaguetitle"><?php echo $match->league->title ?></div>
					<?php if ( isset($match->match_day) && $match->match_day > 0 ) { ?>
						<div class="col-auto matchday">Week <?php echo $match->match_day ?></div>
					<?php } ?>
					<div class="col-auto matchdate"><?php echo substr($match->date,0,10) ?></div>
				</div>
				<div class="row justify-content-center" id="match-header-2">
					<div class="col-auto matchtitle"><?php echo $match->match_title ?></div>
				</div>
			</div>
			<form id="match-rubbers" action="#" method="post" onsubmit="return checkSelect(this)">
				<?php wp_nonce_field( 'rubbers-match' ) ?>

				<input type="hidden" name="current_league_id" id="current_league_id" value="<?php echo $match->league_id ?>" />
				<input type="hidden" name="current_match_id" id="current_match_id" value="<?php echo $match->id ?>" />
				<input type="hidden" name="current_season" id="current_season" value="<?php echo $match->season ?>" />
				<input type="hidden" name="num_rubbers" value="<?php echo $match->num_rubbers ?>" />
				<input type="hidden" name="home_team" value="<?php echo $match->home_team ?>" />
				<input type="hidden" name="away_team" value="<?php echo $match->away_team ?>" />
				<input type="hidden" name="match_type" value="<?php echo $match->type ?>" />
				<input type="hidden" name="match_round" value="<?php echo $match->round ?>" />

				<div class="row">
					<div class="col-1 text-center"><strong><?php _e( 'Pair', 'racketmanager' ) ?></strong></div>
					<div class="col-3 text-center"><strong><?php _e( 'Home Team', 'racketmanager' ) ?></strong></div>
					<div class="col-5 text-center"><strong><?php _e('Sets', 'racketmanager' ) ?></strong></div>
					<div class="col-3 text-center"><strong><?php _e( 'Away Team', 'racketmanager' ) ?></strong></div>
				</div>

				<?php
				$rubbers = $match->getRubbers();
				$r = $tabbase = 0 ;
				$numPlayers = 2;

				foreach ($rubbers as $rubber) {
					$r = $rubber->rubber_number;
					if ( $match->league->type == 'MD' ) {
						$homeRoster[$r][1]['players'] = $homeRoster[$r][2]['players'] = $homeRoster['m'];
						$homeRoster[$r][1]['gender'] = $awayRoster[$r][1]['gender'] = $homeRoster[$r][2]['gender'] = $awayRoster[$r][2]['gender'] = 'm';
						$awayRoster[$r][1]['players'] = $awayRoster[$r][2]['players'] = $awayRoster['m'];
					} elseif ( $match->league->type == 'WD' ) {
						$homeRoster[$r][1]['players'] = $homeRoster[$r][2]['players'] = $homeRoster['f'];
						$homeRoster[$r][1]['gender'] = $awayRoster[$r][1]['gender'] = $homeRoster[$r][2]['gender'] = $awayRoster[$r][2]['gender'] = 'f';
						$awayRoster[$r][1]['players'] = $awayRoster[$r][2]['players'] = $awayRoster['f'];
					} elseif ( $match->league->type == 'XD' ) {
						$homeRoster[$r][1]['players'] = $homeRoster['m'];
						$awayRoster[$r][1]['players'] = $awayRoster['m'];
						$homeRoster[$r][1]['gender'] = $awayRoster[$r][1]['gender'] = 'm';
						$homeRoster[$r][2]['players'] = $homeRoster['f'];
						$awayRoster[$r][2]['players'] = $awayRoster['f'];
						$homeRoster[$r][2]['gender'] = $awayRoster[$r][2]['gender'] = 'f';
					} elseif ( $match->league->type == 'LD' ) {
						if ( $rubber->rubber_number == 1 ) {
							$homeRoster[$r][1]['players'] = $homeRoster['f'];
							$awayRoster[$r][1]['players'] = $awayRoster['f'];
							$homeRoster[$r][1]['gender'] = $awayRoster[$r][1]['gender'] = $homeRoster[$r][2]['gender'] = $awayRoster[$r][2]['gender'] = 'f';
							$homeRoster[$r][2]['players'] = $homeRoster['f'];
							$awayRoster[$r][2]['players'] = $awayRoster['f'];
						} elseif ( $rubber->rubber_number == 2 ) {
							$homeRoster[$r][1]['players'] = $homeRoster['m'];
							$awayRoster[$r][1]['players'] = $awayRoster['m'];
							$homeRoster[$r][1]['gender'] = $awayRoster[$r][1]['gender'] = $homeRoster[$r][2]['gender'] = $awayRoster[$r][2]['gender'] = 'm';
							$homeRoster[$r][2]['players'] = $homeRoster['m'];
							$awayRoster[$r][2]['players'] = $awayRoster['m'];
						} elseif ( $rubber->rubber_number == 3 ) {
							$homeRoster[$r][1]['players'] = $homeRoster['m'];
							$awayRoster[$r][1]['players'] = $awayRoster['m'];
							$homeRoster[$r][1]['gender'] = $awayRoster[$r][1]['gender'] = 'm';
							$homeRoster[$r][2]['players'] = $homeRoster['f'];
							$awayRoster[$r][2]['players'] = $awayRoster['f'];
							$homeRoster[$r][2]['gender'] = $awayRoster[$r][2]['gender'] = 'f';
						}
					}
					?>
					<div class="row mb-3">
						<input type="hidden" name="id[<?php echo $r ?>]" value="<?php echo $rubber->id ?>" </>
						<div class="col-1 text-center align-self-center"><?php echo isset($rubber->rubber_number) ? $rubber->rubber_number : '' ?></div>
						<div class="col-11">
							<div class="row mb-1">
								<div class="col-6 col-sm-4">
									<div class="row">
										<?php for ($p=1; $p <= $numPlayers ; $p++) { ?>
											<div class="col-12">
												<div class="form-group mb-2">
													<?php $tabindex = $tabbase + 1; ?>
													<select class="form-select" tabindex="<?php echo $tabindex ?>" required size="1" name="homeplayer<?php echo $p ?>[<?php echo $r ?>]" id="homeplayer<?php echo $p ?>_<?php echo $r ?>" <?php if ( !$updatesAllowed ) { echo 'disabled';} ?>>
														<?php if ($homeRoster[$r][$p]['gender'] == 'm') { $select = 'Select male player'; } else { $select = 'Select female player'; } ?>
														<option value="0"><?php _e( $select, 'racketmanager' ) ?></option>
														<?php foreach ( $homeRoster[$r][$p]['players'] as $roster ) {
															if ( isset($roster->removed_date) && $roster->removed_date != '' ) { $disabled = 'disabled'; } else { $disabled = ''; } ?>
															<option value="<?php echo $roster->roster_id ?>"<?php $player = 'home_player_'.$p; if (isset($rubber->$player)) { selected($roster->roster_id, $rubber->$player ); echo $disabled; } ?>>
																<?php echo $roster->fullname ?>
															</option>
														<?php } ?>
													</select>
												</div>
											</div>
										<?php } ?>
									</div>
								</div>
								<div class="col-12 col-sm-4 align-self-center order-3 order-sm-2">
									<div class="row text-center">
										<?php for ( $i = 1; $i <= $match->num_sets; $i++ ) {
											if (!isset($rubber->sets[$i])) {
												$rubber->sets[$i] = array('player1' => '', 'player2' => '');
											}
											$colspan = ceil(12 / $match->num_sets);
											$tabindex = $tabbase + 10 + $i; ?>
											<div class="col-<?php echo $colspan ?> col-sm-12 col-lg-<?php echo $colspan ?>">
												<input tabindex="<?php echo $tabindex ?>" class="points" type="text" <?php if ( !$updatesAllowed ) { echo 'readonly';} ?> size="2" id="set_<?php echo $r ?>_<?php echo $i ?>_player1" name="custom[<?php echo $r ?>][sets][<?php echo $i ?>][player1]" value="<?php echo $rubber->sets[$i]['player1'] ?>" />
												-
												<?php $tabindex = $tabbase + 11 + $i; ?>
												<input tabindex="<?php echo $tabindex ?>" class="points" type="text" <?php if ( !$updatesAllowed ) { echo 'readonly';} ?> size="2" id="set_<?php echo $r ?>_<?php echo $i ?>_player2" name="custom[<?php echo $r ?>][sets][<?php echo $i ?>][player2]" value="<?php echo $rubber->sets[$i]['player2'] ?>" />
											</div>
										<?php } ?>
									</div>
								</div>
								<div class="col-6 col-sm-4 order-2 order-sm-3">
									<div class="row">
										<?php for ($p=1; $p <= $numPlayers ; $p++) { ?>
											<div class="col-12">
												<div class="form-group mb-2">
													<?php $tabindex = $tabbase + 3; ?>
													<select class="form-select" tabindex="<?php echo $tabindex ?>" required size="1" name="awayplayer<?php echo $p ?>[<?php echo $r ?>]" id="awayplayer<?php echo $p ?>_<?php echo $r ?>" <?php if ( !$updatesAllowed ) { echo 'disabled';} ?>>
														<?php if ($awayRoster[$r][$p]['gender'] == 'm') { $select = 'Select male player'; } else { $select = 'Select female player'; } ?>
														<option value="0"><?php _e( $select, 'racketmanager' ) ?></option>
														<?php foreach ( $awayRoster[$r][$p]['players'] as $roster ) {
															if ( isset($roster->removed_date) && $roster->removed_date != '' ) { $disabled = 'disabled'; } else { $disabled = ''; } ?>
															<option value="<?php echo $roster->roster_id ?>"<?php $player = 'away_player_'.$p; if (isset($rubber->$player)) { selected($roster->roster_id, $rubber->$player ); echo $disabled; } ?>>
																<?php echo $roster->fullname ?>
															</option>
														<?php } ?>
													</select>
												</div>
											</div>
										<?php } ?>
									</div>
								</div>
							</div>
							<div class="row text-center">
								<div class="col-12">
									<input class="points" type="text" size="2" readonly id="home_points[<?php echo $r ?>]" name="home_points[<?php echo $r ?>]" value="<?php echo isset($rubber->home_points) ? $rubber->home_points : '' ?>" />
									<input class="points" type="text" size="2" readonly id="away_points[<?php echo $r ?>]" name="away_points[<?php echo $r ?>]" value="<?php echo isset($rubber->away_points) ? $rubber->away_points : '' ?>" />
								</div>
							</div>
						</div>
					</div>
					<?php
					$tabbase +=100;
					$r ++;
				}	?>
				<div id="captains" class="row mb-3">
					<div class="col-1 text-center align-self-center"></div>
					<div class="col-11">
						<div class="row">
							<div class="col-4 mb-3">
								<div class="col-12 captain"><?php _e( 'Captain', 'racketmanager' ) ?></div>
								<div class="col-12">
									<?php echo $match->teams['home']->captain; ?>
								</div>
							</div>
							<div class="col-4 mb-3">
							</div>
							<div class="col-4 mb-3">
								<div class="col-12 captain"><?php _e( 'Captain', 'racketmanager' ) ?></div>
								<div class="col-12">
									<?php echo $match->teams['away']->captain; ?>
								</div>
							</div>
						</div>
					</div>
				</div>
				<?php if ( isset($match->home_captain) || isset($match->away_captain) ) { ?>
					<div id="approvals" class="row mb-3">
						<div class="col-1 text-center align-self-center"></div>
						<div class="col-11">
							<div class="row">
								<div class="col-4 mb-3">
									<div class="col-12 captain"><?php _e( 'Approval', 'racketmanager' ) ?></div>
									<div class="col-12">
										<?php echo $racketmanager->getPlayerName($match->teams['home']->captain); ?>
									</div>
									<div class="col-12">
										<?php if ( isset($match->home_captain) ) {
											echo $racketmanager->getPlayerName($match->home_captain);
										} else { ?>
											<?php if ( !current_user_can( 'manage_racketmanager' ) && $match->confirmed == 'P' ) { ?>
												<?php if ( $userType != 'admin' && $userTeam == 'home' ) { ?>
													<div class="form-check">
														<input class="form-check-input" type="radio" name="resultConfirm" value="confirm" required />
														<label class="form-check-label">Confirm</label>
													</div>
													<div class="form-check">
														<input class="form-check-input" type="radio" name="resultConfirm" value="challenge" required />
														<label class="form-check-label">Challenge</label>
													</div>
													<div class="form-floating">
														<textarea class="form-control result-comments" placeholder="Leave a comment here" name="resultConfirmCommentsHome" id="resultConfirmCommentsHome"></textarea>
														<label for="resultConfirmCommentsHome"><?php _e( 'Comments', 'racketmanager' ) ?></label>
													</div>
												<?php } ?>
											<?php } ?>
										<?php } ?>
									</div>
								</div>
								<div class="col-4 mb-3"></div>
								<div class="col-4 mb-3">
									<div class="col-12 captain"><?php _e( 'Approval', 'racketmanager' ) ?></div>
									<div class="col-12">
										<?php if ( isset($match->away_captain) ) {
											echo $racketmanager->getPlayerName($match->away_captain);
										} else { ?>
											<?php if ( !current_user_can( 'manage_racketmanager' ) && $match->confirmed == 'P' ) { ?>
												<?php if ( $userType != 'admin' && $userTeam == 'away' ) { ?>
													<div class="form-check">
														<input class="form-check-input" type="radio" name="resultConfirm" value="confirm" required />
														<label class="form-check-label"><?php _e( 'Confirm', 'racketmanager' ) ?></label>
													</div>
													<div class="form-check">
														<input class="form-check-input" type="radio" name="resultConfirm" value="challenge" required />
														<label class="form-check-label"><?php _e( 'Challenge', 'racketmanager' ) ?></label>
													</div>
													<div class="form-floating">
														<textarea class="form-control result-comments" placeholder="Leave a comment here" name="resultConfirmCommentsAway" id="resultConfirmCommentsAway"></textarea>
														<label for="resultConfirmCommentsAway"><?php _e( 'Comments', 'racketmanager' ) ?></label>
													</div>
												<?php } ?>
											<?php } ?>
										<?php } ?>
									</div>
								</div>
							</div>
						</div>
					</div>
				<?php } ?>
				<div class="row mt-3 mb-3">
					<div>
						<div class="form-floating">
							<textarea class="form-control result-comments" tabindex="490" placeholder="Leave a comment here" name="resultConfirmComments" id="resultConfirmComments"><?php echo $match->comments ?></textarea>
							<label for="resultConfirmComments"><?php _e( 'Comments', 'racketmanager' ) ?></label>
						</div>
					</div>
				</div>
				<div class="mb-3">
					<?php if ( isset($match->updated_user) ) { ?>
						<div class="row">
							<div class="col-auto">Updated By:</div>
							<div class="col-auto">
								<?php echo $racketmanager->getPlayerName($match->updated_user); ?>
							</div>
						</div>
						<?php if ( isset($match->updated) ) { ?>
							<div class="row">
								<div class="col-auto">On:</div>
								<div class="col-auto"><?php echo $match->updated; ?></div>
							</div>
						<?php } ?>
					<?php } ?>
				</div>
				<?php if ( $userCanUpdate ) {
					if (current_user_can( 'update_results' ) || $match->confirmed == 'P' || $match->confirmed == null) { ?>
						<div class="row mb-3">
							<div class="col-12">
								<input type="hidden" name="updateRubber" id="updateRubber" value="<?php if ( !$updatesAllowed ) { echo 'confirm';} else { echo 'results';} ?>" />
								<button tabindex="500" class="button button-primary" type="button" id="updateRubberResults" onclick="Racketmanager.updateResults(this)">Update Results</button>
							</div>
						</div>
						<div class="row mb-3">
							<div id="updateResponse" class="updateResponse"></div>
						</div>
					<?php } else { ?>
						<div class="row mb-3">
							<div class="col-12 updateResponse message-error">
								<?php _e('Updates not allowed', 'racketmanager') ?>
							</div>
						</div>
					<?php } ?>
				<?php } else { ?>
					<div class="row mb-3 justify-content-center">
						<div class="col-auto">
							<?php if ( $userMessage == 'notLoggedIn' ) { ?>
								You need to <a href="<?php echo wp_login_url( $_SERVER['REQUEST_URI'] ); ?>">login</a> to update the result.
							<?php } else {
								_e('User not allowed to update result', 'racketmanager');
							} ?>
						</div>
					</div>
				<?php } ?>
			</form>
		</div>
	<?php
	}

}

global $racketmanager;
if ( is_admin() ) {
	require_once (dirname (__FILE__) . '/admin/admin.php');
	$racketmanager = new RacketManagerAdmin();
} else {
	$racketmanager = new RacketManager();
}
	define( 'RACKETMANAGER_PATH', plugin_dir_path(__FILE__) );
	define( 'RACKETMANAGER_PLUGIN_FILE',  __FILE__ );

// suppress output
if ( isset($_POST['racketmanager_export']) ) {
	ob_start();
}
?>
