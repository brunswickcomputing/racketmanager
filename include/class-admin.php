<?php
/**
* RacketManager administration functions
*
*/

/**
* Class to implement RacketManager Administration panel
*
* @author Kolja Schleich
* @author Paul Moffat
* @package RacketManager
* @subpackage RacketManagerAdmin
*/
final class RacketManagerAdmin extends RacketManager
{
	private $league_id;
	/**
	* Constructor
	*/
	public function __construct() {
		parent::__construct();

		require_once  ABSPATH . 'wp-admin/includes/template.php';

		add_action( 'admin_enqueue_scripts', array(&$this, 'loadScripts') );
		add_action( 'admin_enqueue_scripts', array(&$this, 'loadStyles') );

		add_action( 'admin_menu', array(&$this, 'menu') );
		add_action( 'admin_footer', array(&$this, 'scroll_top') );

		add_action( 'show_user_profile', array(&$this, 'custom_user_profile_fields') );
		add_action( 'edit_user_profile', array(&$this, 'custom_user_profile_fields') );
		add_action( 'personal_options_update', array(&$this, 'update_extra_profile_fields') );
		add_action( 'edit_user_profile_update', array(&$this, 'update_extra_profile_fields') );

		// Add meta box to post screen

		add_action( 'publish_post', array(&$this, 'editMatchReport') );
		add_action( 'edit_post', array(&$this, 'editMatchReport') );
		add_action( 'add_meta_boxes', array(&$this, 'metaboxes') );
		add_action( 'wp_ajax_racketmanager_get_league_dropdown', array(&$this, 'getLeagueDropdown') );
	}

	/**
	* adds menu to the admin interface
	*/
	public function menu() {
		$plugin = 'racketmanager/racketmanager.php';

		// keep capabilities here for next update
		$page = add_menu_page(
			__('RacketManager','racketmanager')
			, __('RacketManager','racketmanager')
			, 'racket_manager'
			, 'racketmanager'
			, array(&$this, 'display')
			, 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iaXNvLTg4NTktMSI/Pg0KPCEtLSBHZW5lcmF0b3I6IEFkb2JlIElsbHVzdHJhdG9yIDE3LjEuMCwgU1ZHIEV4cG9ydCBQbHVnLUluIC4gU1ZHIFZlcnNpb246IDYuMDAgQnVpbGQgMCkgIC0tPg0KPCFET0NUWVBFIHN2ZyBQVUJMSUMgIi0vL1czQy8vRFREIFNWRyAxLjEvL0VOIiAiaHR0cDovL3d3dy53My5vcmcvR3JhcGhpY3MvU1ZHLzEuMS9EVEQvc3ZnMTEuZHRkIj4NCjxzdmcgdmVyc2lvbj0iMS4xIiBpZD0iQ2FwYV8xIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiB4PSIwcHgiIHk9IjBweCINCgkgdmlld0JveD0iMCAwIDQxMi40MjUgNDEyLjQyNSIgc3R5bGU9ImVuYWJsZS1iYWNrZ3JvdW5kOm5ldyAwIDAgNDEyLjQyNSA0MTIuNDI1OyIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSI+DQo8cGF0aCBkPSJNNDEyLjQyNSwxMDguOTMzYzAtMzAuNTI5LTEwLjk0MS01OC4xOC0zMC44MDgtNzcuODZDMzYxLjc3NiwxMS40MTgsMzMzLjkxLDAuNTkzLDMwMy4xNTMsMC41OTMNCgljLTQxLjMsMC04My45MTMsMTguNzQ5LTExNi45MTMsNTEuNDM4Yy0zMC4zMTksMzAuMDM0LTQ4Ljc1NCw2OC4xMTUtNTEuNTczLDEwNS44NThjLTAuODQ1LDUuMzk4LTEuNjM0LDExLjEzLTIuNDYyLDE3LjE4OA0KCWMtNC43NDQsMzQuNjg2LTEwLjYwMyw3Ny40MTUtMzQuMDQ5LDEwNC41MDNjLTIuMDYsMC4zMzMtMy45ODEsMS4yOTUtNS40NzYsMi43ODlMNy42MDMsMzY3LjQ0Nw0KCWMtMTAuMTM3LDEwLjEzOC0xMC4xMzcsMjYuNjMyLDAsMzYuNzdjNC45MTEsNC45MTEsMTEuNDQsNy42MTUsMTguMzg1LDcuNjE1czEzLjQ3NC0yLjcwNSwxOC4zODYtNy42MTdsODUuMDYtODUuMDk1DQoJYzEuNTM1LTEuNTM2LDIuNDU3LTMuNDQ4LDIuNzg0LTUuNDM4YzI3LjA4Ny0yMy40NjEsNjkuODI5LTI5LjMyMiwxMDQuNTI0LTM0LjA2OGM2LjU0OS0wLjg5NiwxMi43MzQtMS43NDEsMTguNTA4LTIuNjY2DQoJYzEuNDM0LTAuMjMsMi43NDMtMC43NiwzLjg4NS0xLjUwN2MzNi4yNTMtNC4wNDcsNzIuNDY0LTIxLjk3MiwxMDEuMzI1LTUwLjU2MkMzOTMuNDg1LDE5Mi4xNjYsNDEyLjQyNSwxNDkuOTA1LDQxMi40MjUsMTA4LjkzM3oNCgkgTTE0NS40NzYsMjE4LjM0OWM0Ljk4NCwxMC4yNDQsMTEuNTY0LDE5LjUyMSwxOS42MDgsMjcuNDljOC41MTQsOC40MzQsMTguNTEsMTUuMjM3LDI5LjU3NiwyMC4yNjINCgljLTI1Ljg0Niw1LjIzOC01Mi43NjksMTMuODIzLTczLjQxNSwzMC42OTJsLTYuMjE2LTYuMjE2QzEzMS42MzksMjcwLjI0NiwxNDAuMjE3LDI0My44MzEsMTQ1LjQ3NiwyMTguMzQ5eiBNMzAuMjMsMzkwLjA3NQ0KCWMtMS4xMzMsMS4xMzMtMi42NCwxLjc1Ny00LjI0MiwxLjc1N2MtMS42MDMsMC0zLjEwOS0wLjYyNC00LjI0My0xLjc1N2MtMi4zMzktMi4zMzktMi4zMzktNi4xNDYsMC04LjQ4NWw3OC4wMDYtNzguMDA3DQoJbDguNDY5LDguNDY5TDMwLjIzLDM5MC4wNzV6IE0yNDMuNTU5LDI1Ni4zMThjLTAuMDAyLDAtMC4wMDgsMC0wLjAxMSwwYy0yNS44MjItMC4wMDMtNDguMDg3LTguNTQtNjQuMzg5LTI0LjY4OA0KCWMtMTYuMjc5LTE2LjEyNi0yNC44ODMtMzguMTM2LTI0Ljg4My02My42NTJjMC0yLjU5NiwwLjEtNS4yMDEsMC4yNzYtNy44MDhjMC4wMjMtMC4xNDMsMC4wNDUtMC4yOTUsMC4wNjgtMC40MzgNCgljMC4xMS0wLjY4NSwwLjE0Ny0xLjM2NCwwLjExNy0yLjAzMWMyLjg3LTMyLjQyMiwxOS4xMjEtNjUuMjUzLDQ1LjU3OS05MS40NjFjMjkuMjg0LTI5LjAwOSw2Ni43NjctNDUuNjQ2LDEwMi44MzctNDUuNjQ2DQoJYzI1LjgxOSwwLDQ4LjA4NSw4LjUzNyw2NC4zODksMjQuNjg5YzE2LjI3OSwxNi4xMjYsMjQuODgzLDM4LjEzNiwyNC44ODMsNjMuNjUxYy0wLjAwMSwzNS42NzItMTYuNzgxLDcyLjc1NS00Ni4wNCwxMDEuNzM5DQoJQzMxNy4xLDIzOS42ODIsMjc5LjYyNCwyNTYuMzE5LDI0My41NTksMjU2LjMxOHoiLz4NCjxnPg0KPC9nPg0KPGc+DQo8L2c+DQo8Zz4NCjwvZz4NCjxnPg0KPC9nPg0KPGc+DQo8L2c+DQo8Zz4NCjwvZz4NCjxnPg0KPC9nPg0KPGc+DQo8L2c+DQo8Zz4NCjwvZz4NCjxnPg0KPC9nPg0KPGc+DQo8L2c+DQo8Zz4NCjwvZz4NCjxnPg0KPC9nPg0KPGc+DQo8L2c+DQo8Zz4NCjwvZz4NCjwvc3ZnPg0K'
			, 2
		);
		add_action("admin_print_scripts-$page", array(&$this, 'loadScripts') );
		add_action("admin_print_scripts-$page", array(&$this, 'loadStyles') );

		$page = add_submenu_page(
			'racketmanager' //parent page
			, __('RacketManager', 'racketmanager') //page title
			, __('Competitions','racketmanager') //menu title
			,'racket_manager' //capability
			, 'racketmanager' //menu slug
			, array(&$this, 'display')
		);
		add_action("admin_print_scripts-$page", array(&$this, 'loadScripts') );
		add_action("admin_print_scripts-$page", array(&$this, 'loadStyles') );

		$page = add_submenu_page(
			'racketmanager'
			, __('Leagues', 'racketmanager')
			, __('Leagues','racketmanager')
			,'racket_manager'
			, 'racketmanager-leagues'
			, array(&$this, 'display')
		);
		add_action("admin_print_scripts-$page", array(&$this, 'loadScripts') );
		add_action("admin_print_scripts-$page", array(&$this, 'loadStyles') );

		$page = add_submenu_page(
			'racketmanager'
			, __('Cups', 'racketmanager')
			, __('Cups','racketmanager')
			,'racket_manager'
			, 'racketmanager-cups'
			, array(&$this, 'display')
		);
		add_action("admin_print_scripts-$page", array(&$this, 'loadScripts') );
		add_action("admin_print_scripts-$page", array(&$this, 'loadStyles') );

		$page = add_submenu_page(
			'racketmanager'
			, __('Tournaments', 'racketmanager')
			, __('Tournaments','racketmanager')
			,'racket_manager'
			, 'racketmanager-tournaments'
			, array(&$this, 'display')
		);
		add_action("admin_print_scripts-$page", array(&$this, 'loadScripts') );
		add_action("admin_print_scripts-$page", array(&$this, 'loadStyles') );

		$page = add_submenu_page(
			'racketmanager'
			, __('Clubs', 'racketmanager')
			, __('Clubs','racketmanager')
			,'racket_manager'
			, 'racketmanager-clubs'
			, array(&$this, 'display')
		);
		add_action("admin_print_scripts-$page", array(&$this, 'loadScripts') );
		add_action("admin_print_scripts-$page", array(&$this, 'loadStyles') );

		$page = add_submenu_page(
			'racketmanager'
			, __('Results', 'racketmanager')
			, __('Results','racketmanager')
			,'racket_manager'
			, 'racketmanager-results'
			, array(&$this, 'display')
		);
		add_action("admin_print_scripts-$page", array(&$this, 'loadScripts') );
		add_action("admin_print_scripts-$page", array(&$this, 'loadStyles') );

		$page = add_submenu_page(
			'racketmanager'
			, __('Schedule', 'racketmanager')
			, __('Schedule','racketmanager')
			,'racket_manager'
			, 'racketmanager-schedule'
			, array(&$this, 'display')
		);
		add_action("admin_print_scripts-$page", array(&$this, 'loadScripts') );
		add_action("admin_print_scripts-$page", array(&$this, 'loadStyles') );

		$page = add_submenu_page(
			'racketmanager'
			, __('Players', 'racketmanager')
			, __('Players','racketmanager')
			,'racket_manager'
			, 'racketmanager-players'
			, array( $this, 'display' )
		);
		add_action("admin_print_scripts-$page", array(&$this, 'loadScripts') );
		add_action("admin_print_scripts-$page", array(&$this, 'loadStyles') );

		$page = add_submenu_page(
			'racketmanager'
			, __('Administration', 'racketmanager')
			, __('Administration','racketmanager')
			,'racketmanager_settings'
			, 'racketmanager-admin'
			, array( $this, 'display' )
		);
		add_action("admin_print_scripts-$page", array(&$this, 'loadScripts') );
		add_action("admin_print_scripts-$page", array(&$this, 'loadStyles') );

		$page = add_submenu_page(
			'racketmanager'
			, __('Finances', 'racketmanager')
			, __('Finances','racketmanager')
			,'racket_manager'
			, 'racketmanager-finances'
			, array(&$this, 'display')
		);
		add_action("admin_print_scripts-$page", array(&$this, 'loadScripts') );
		add_action("admin_print_scripts-$page", array(&$this, 'loadStyles') );

		$page = add_submenu_page(
			'racketmanager'
			, __('Settings', 'racketmanager')
			, __('Settings','racketmanager')
			,'racketmanager_settings'
			, 'racketmanager-settings'
			, array( $this, 'display' )
		);
		add_action("admin_print_scripts-$page", array(&$this, 'loadScripts') );
		add_action("admin_print_scripts-$page", array(&$this, 'loadStyles') );

		$page = add_submenu_page(
			'racketmanager'
			, __('Import')
			, __('Import')
			,'import_leagues'
			, 'racketmanager-import'
			, array( $this, 'display' )
		);
		add_action("admin_print_scripts-$page", array(&$this, 'loadScripts') );
		add_action("admin_print_scripts-$page", array(&$this, 'loadStyles') );

		$page = add_submenu_page(
			'racketmanager'
			, __('Documentation', 'racketmanager')
			, __('Documentation','racketmanager')
			, 'view_leagues'
			, 'racketmanager-doc'
			, array( $this, 'display' )
		);
		add_action("admin_print_scripts-$page", array(&$this, 'loadScripts') );
		add_action("admin_print_scripts-$page", array(&$this, 'loadStyles') );

		add_filter( 'plugin_action_links_'.RACKETMANAGER_PLUGIN_BASENAME, array( &$this, 'pluginActions' ) );
	}

	/**
	* adds scroll to top icon to the admin interface
	*/
	public function scroll_top() {
		?>
		<a class="go-top dashicons dashicons-arrow-up-alt2"></a>
		<?php
	}

	/** Display in the wp backend
	* http://codex.wordpress.org/Plugin_API/Action_Reference/show_user_profile
	*
	* Show custom user profile fields
	* @param  obj $user The WP user object.
	* @return void
	*/

	public function custom_user_profile_fields( $user ) {
		?>
		<table class="form-table" aria-label="<?php _e('racketmanager_fields','racketmanager') ?>">
			<tr>
				<th>
					<label for="gender"><?php _e( 'Gender','racketmanager' ); ?></label>
				</th>
				<td>
					<input type="radio" required name="gender" value="M" <?php echo ( get_the_author_meta( 'gender', $user->ID )  == 'M') ? 'checked' : '' ?>> <?php _e('Male', 'racketmanager') ?><br />
					<input type="radio" name="gender" value="F" <?php echo ( get_the_author_meta( 'gender', $user->ID )  == 'F') ? 'checked' : '' ?>> <?php _e('Female', 'racketmanager') ?>
				</td>
			</tr>
			<tr>
				<th>
					<label for="contactno"><?php _e( 'Contact Number','racketmanager' ); ?></label>
				</th>
				<td>
					<input type="tel" name="contactno" value="<?php echo esc_attr( get_the_author_meta( 'contactno', $user->ID ) ); ?>">
				</td>
			</tr>
			<tr>
				<th>
					<label for="btm"><?php _e( 'LTA Tennis Number','racketmanager' ); ?></label>
				</th>
				<td>
					<input type="number" name="btm" value="<?php echo esc_attr( get_the_author_meta( 'btm', $user->ID ) ); ?>">
				</td>
			</tr>
			<tr>
				<th>
					<label for="remove_date"><?php _e( 'Date Removed','racketmanager' ); ?></label>
				</th>
				<td>
					<input type="date" name="remove_date" value="<?php echo esc_attr( get_the_author_meta( 'remove_date', $user->ID ) ); ?>">
				</td>
			</tr>
			<tr>
				<th>
					<label for="locked_date"><?php _e( 'Date Locked','racketmanager' ); ?></label>
				</th>
				<td>
					<input type="date" name="locked_date" value="<?php echo esc_attr( get_the_author_meta( 'locked_date', $user->ID ) ); ?>">
				</td>
			</tr>
		</table>
		<?php
	}

	/** Update the custom meta
	* https://codex.wordpress.org/Plugin_API/Action_Reference/personal_options_update
	* https://codex.wordpress.org/Plugin_API/Action_Reference/edit_user_profile_update
	*
	* Show custom user profile fields
	* @param  int user_id.
	*/
	public function update_extra_profile_fields( $user_id ) {

		if ( current_user_can( 'edit_user', $user_id ) ) {
			if ( isset($_POST['gender']) ) {
				update_user_meta( $user_id, 'gender', $_POST['gender'] );
			}
			if ( isset($_POST['contactno']) ) {
				update_user_meta( $user_id, 'contactno', $_POST['contactno'] );
			}
			if ( isset($_POST['btm']) ) {
				update_user_meta( $user_id, 'btm', $_POST['btm'] );
			}
			if ( isset($_POST['remove_date']) ) {
				update_user_meta( $user_id, 'remove_date', $_POST['remove_date'] );
			}
		}
	}

	/**
	* adds the required Metaboxes
	*/
	public function metaboxes() {
		add_meta_box( 'racketmanager', __('Match-Report','racketmanager'), array(&$this, 'addMetaBox'), 'post' );
	}

	/**
	* build league menu
	*
	* @return array
	*/
	private function getMenu() {
		$league = get_league();
		$league_id = (isset($_GET['league_id']) ? intval($_GET['league_id']) : $league->id);
		$season = (isset($_GET['season']) ? htmlspecialchars($_GET['season']) : $league->current_season);
		$sport = (isset($league->sport) ? ($league->sport) : '' );
		$league_mode = (isset($league->mode) ? ($league->mode) : '' );

		$menu = array();
		$menu['teams'] = array( 'title' => __('Add Teams', 'racketmanager'), 'callback' => array(&$this, 'displayTeamsList'), 'cap' => 'edit_teams', 'show' => true );
		$menu['team'] = array( 'title' => __('Add Team', 'racketmanager'), 'callback' => array(&$this, 'displayTeamPage'), 'cap' => 'edit_teams', 'show' => false );
		$menu['match'] = array( 'title' => __('Add Matches', 'racketmanager'), 'callback' => array(&$this, 'displayMatchPage'), 'cap' => 'edit_matches' );
		if ( $league->is_championship ) {
			$menu['match']['show'] = false;
			if ( $league->entryType == 'player' ) {
				$menu['team']['show'] = true;
			}
		} else {
			$menu['match']['show'] = true;
		}
		$menu['contact'] = array( 'title' => __('Contact', 'racketmanager'), 'callback' => array(&$this, 'displayContactPage'), 'cap' => 'edit_teams', 'show' => true );
		$menu = apply_filters('league_menu_'.$sport, $menu, $league->id, $season);
		$menu = apply_filters('league_menu_'.$league->mode, $menu, $league->id, $season);

		return $menu;
	}

	/**
	* showMenu() - show admin menu
	*
	* @param none
	*/
	public function display() {
		global $league, $racketmanager, $championship;

		$options = $this->options;

		// Update Plugin Version
		if ( $options['version'] != RACKETMANAGER_VERSION ) {
			$options['version'] = RACKETMANAGER_VERSION;
			update_option('leaguemanager', $options);
		}

		// Update database
		if( $options['dbversion'] != RACKETMANAGER_DBVERSION ) {
			include_once RACKETMANAGER_PATH . '/admin/upgrade.php';
			racketmanager_upgrade_page();
			return;
		}

		switch ($_GET['page']) {
			case 'racketmanager-doc':
			include_once RACKETMANAGER_PATH . '/admin/documentation.php';
			break;
			case 'racketmanager-leagues':
			$this->displayLeaguesPage();
			break;
			case 'racketmanager-cups':
			$this->displayCupsPage();
			break;
			case 'racketmanager-tournaments':
			$this->displayTournamentsPage();
			break;
			case 'racketmanager-clubs':
			$view = isset($_GET['view']) ? $_GET['view'] : '';
			if ( $view == 'teams' ) {
				$this->displayTeamsPage();
			} elseif ( $view == 'players' ) {
				$this->displayClubPlayersPage();
			} elseif ( $view == 'player' ) {
				$this->displayPlayerPage();
			} else {
				$this->displayClubsPage();
			}
			break;
			case 'racketmanager-results':
			$view = isset($_GET['subpage']) ? $_GET['subpage'] : '';
			if ( $view == 'match' ) {
				$this->displayMatchResultsPage();
			} else {
				$this->displayResultsPage();
			}
			break;
			case 'racketmanager-admin':
			$view = isset($_GET['subpage']) ? $_GET['subpage'] : '';
			if ( $view == 'competitions' ) {
				$this->displayCompetitionsList();
			} elseif ( $view == 'player' ) {
				$this->displayPlayerPage();
			} else {
				$this->displayAdminPage();
			}
			break;
			case 'racketmanager-players':
				$view = isset($_GET['view']) ? $_GET['view'] : '';
				if ( $view == 'player' ) {
					$this->displayPlayerPage();
				} else {
					$this->displayPlayersPage();
				}
			break;
			case 'racketmanager-finances':
			$view = isset($_GET['subpage']) ? $_GET['subpage'] : '';
			if ( $view == 'charges' ) {
				$this->displayChargesPage();
			} elseif ( $view == 'invoice' ) {
				$this->displayInvoicePage();
			} else {
				$this->displayFinancesPage();
			}
			break;
			case 'racketmanager-settings':
			$this->displayOptionsPage();
			break;
			case 'racketmanager-import':
			$this->displayImportPage();
			break;
			case 'racketmanager-documentation':
			include_once RACKETMANAGER_PATH . '/admin/documentation.php';
			break;
			case 'racketmanager-schedule':
			$this->displaySchedulePage();
			break;
			case 'racketmanager':
			default:
			if ( isset($_GET['subpage']) ) {
				switch ($_GET['subpage']) {
					case 'show-competitions':
					$this->displayTournamentCompetitionsPage();
					break;
					case 'show-competition':
					$this->displayCompetitionPage();
					break;
					case 'club':
					$this->displayClubPage();
					break;
					case 'team':
					$this->displayTeamPage();
					break;
					case 'show-season':
					$this->displaySeasonPage();
					break;
					case 'tournament':
					$this->displayTournamentPage();
					break;
					case 'tournament-plan':
					$this->displayTournamentPlanPage();
					break;
					case 'contact':
					$this->displayContactPage();
					break;
					default:
					$this->league_id = intval($_GET['league_id']);
					$league = get_league($this->league_id);
					$menu = $this->getMenu();
					$page = htmlspecialchars($_GET['subpage']);
					if ( array_key_exists( $page, $menu ) ) {
						if ( isset($menu[$page]['callback']) && is_callable($menu[$page]['callback']) ) {
							call_user_func($menu[$page]['callback']);
						} else {
							include_once $menu[$page]['file'];
						}
					} else {
						$this->displayLeaguePage();
					}
				}
			} else {
				$this->displayIndexPage();
			}
		}
	}

	/**
	* show RacketManager index page
	*
	*/
	private function displayIndexPage() {
		global $racketmanager, $competition, $club;

		if ( !current_user_can( 'view_leagues' ) ) {
			echo '<div class="error"><p style="text-align: center;">'.__("You do not have sufficient permissions to access this page.").'</p></div>';
		} else {
			$tab = 'competitionsleague';
			$club_id = isset($_GET['club_id']) ? $_GET['club_id'] : 0;
			if ( $club_id ) {
				$club = get_club($club_id);
			}
			if ( isset($_POST['addCompetition']) ) {
				if ( current_user_can('edit_leagues') ) {
					check_admin_referer('racketmanager_add-competition');
					$competition = new stdClass();
					$competition->name = htmlspecialchars(strip_tags($_POST['competition_name']));
					$competition->num_rubbers = $_POST['num_rubbers'];
					$competition->num_sets = $_POST['num_sets'];
					$competition->type = $_POST['competition_type'];
					$competition->competitionType = $_POST['competitiontype'];
					$competition = new Competition($competition);
					$this->createCompetitionPages($competition->id, $competition->name);
					$this->setMessage( __('Competition added', 'racketmanager') );
				} else {
					$this->setMessage(__("You don't have permission to perform this task", 'racketmanager'), true);
				}
				$this->printMessage();
			} elseif ( isset($_POST['docompdel']) && $_POST['action'] == 'delete' ) {
				if ( current_user_can('del_leagues') ) {
					check_admin_referer('competitions-bulk');
					$messages = array();
					$messageError= false;
					foreach ( $_POST['competition'] as $competition_id ) {
						$competition = get_competition($competition_id);
						$competition->delete();
						$this->deleteCompetitionPages($competition->name);
						$messages[] = $competition->name.' '.__('deleted', 'racketmanager');
					}
					$message = implode('<br>', $messages);
					$this->setMessage( $message, $messageError );
				} else {
					$this->setMessage(__("You don't have permission to perform this task", 'racketmanager'), true);
				}
				$this->printMessage();
			}
			include_once RACKETMANAGER_PATH . '/admin/index.php';
		}
	}

	/**
	* show RacketManager results page
	*
	*/
	private function displayResultsPage() {
		global $racketmanager, $league, $championship, $competition ;

		if ( !current_user_can( 'view_leagues' ) ) {
			echo '<div class="error"><p style="text-align: center;">'.__("You do not have sufficient permissions to access this page.").'</p></div>';
		} else {
			$seasonSelect = isset($_GET['season']) ? $_GET['season'] : '';
			$competitionSelect = isset($_GET['competition']) ? $_GET['competition'] : '';
			$resultsCheckFilter = isset($_GET['filterResultsChecker'])  ? $_GET['filterResultsChecker'] : 'outstanding';
			$tab = isset($_GET['tab']) ? $_GET['tab'] : "resultschecker";
			if ( isset($_POST['doResultsChecker']) ) {
				if ( current_user_can('update_results') ) {
					check_admin_referer('results-checker-bulk');
					foreach ( $_POST['resultsChecker'] as $i => $resultsChecker_id ) {
						if ( $_POST['action'] == 'approve' ) {
							$this->approveResultsChecker( intval($resultsChecker_id) );
						} elseif ( $_POST['action'] == 'handle' ) {
							$this->handleResultsChecker( intval($resultsChecker_id) );
						} elseif ( $_POST['action'] == 'delete' ) {
							$this->deleteResultsChecker( intval($resultsChecker_id) );
						}
					}
				} else {
					$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
				}
				$this->printMessage();
				$tab = "resultschecker";
			}
			$resultsCheckers = $this->getResultsChecker(array('season' => $seasonSelect, 'competition' => $competitionSelect, 'status' => $resultsCheckFilter));
			include_once RACKETMANAGER_PATH . '/admin/show-results.php';
		}
	}

	/**
	* show RacketManager match results page
	*
	*/
	private function displayMatchResultsPage() {
		global $match ;

		if ( !current_user_can( 'update_results' ) ) {
			echo '<div class="error"><p style="text-align: center;">'.__("You do not have sufficient permissions to access this page.").'</p></div>';
		} else {
			$match = get_match($_GET['match_id']);
			$referrer = isset($_GET['referrer']) ? $_GET['referrer'] : '';
			include_once RACKETMANAGER_PATH . '/admin/show-match.php';
		}
	}

	/**
	* display competitions page
	*
	*/
	private function displayTournamentCompetitionsPage() {
		global $racketmanager, $competition;

		if ( !current_user_can( 'edit_leagues' ) ) {
			echo '<div class="error"><p style="text-align: center;">'.__("You do not have sufficient permissions to access this page.").'</p></div>';
		} else {
			if ( isset($_POST['doaddCompetitionsToSeason']) && $_POST['action'] == 'addCompetitionsToSeason' ) {
				check_admin_referer('racketmanager_add-seasons-competitions-bulk');
				if ( isset($_POST['competition'])) {
					foreach ( $_POST['competition'] as $competition_id ) {
						$this->addSeasonToCompetition( htmlspecialchars($_POST['season']), intval($_POST['num_match_days']), $competition_id );
					}
				}
			}
			$tournament = get_tournament( $_GET['tournament'] );
			$competitionType = 'tournament';
			$season = $tournament->season;
			$type = $tournament->type;
			$standalone = true;
			$competitionQuery = array( 'type' => $competitionType, 'name' => $type, 'season' => $season );
			$pageTitle = $tournament->name.' '.__( 'Tournament Competitions', 'racketmanager' );
			include_once RACKETMANAGER_PATH . '/admin/show-competitions.php';
		}
	}

	/**
	* display competition page
	*
	*/
	private function displayCompetitionPage() {
		global $racketmanager, $competition;

		if ( !current_user_can( 'edit_leagues' ) ) {
			echo '<div class="error"><p style="text-align: center;">'.__("You do not have sufficient permissions to access this page.").'</p></div>';
		} else {
			$tab = 'leagues';
			$competition_id = $_GET['competition_id'];
			$competition = get_competition($_GET['competition_id']);
			$league_id = false;
			$league_title = "";
			$season_id = false;
			$season_data = array('name' => '', 'num_match_days' => '', 'homeAndAway' => '');
			$club_id = 0;

			if ( isset($_POST['addLeague']) ) {
				if ( !current_user_can('edit_leagues') ) {
					$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
				} else {
					check_admin_referer('racketmanager_add-league');
					if ( empty($_POST['league_id'] ) ){
						$league = new stdClass();
						$league->title = htmlspecialchars($_POST['league_title']);
						$league->competition_id = intval($_POST['competition_id']);
						$league = new League($league);
						$this->setMessage( __('League added', 'racketmanager') );
					} else {
						$league = get_league(intval($_POST['league_id']));
						if ( $league->title == htmlspecialchars($_POST['league_title']) ) {
							$this->setMessage( __('No updates', 'racketmanager'), true );
						} else {
							$league->update(htmlspecialchars($_POST['league_title']));
							$this->setMessage( __('League Updated', 'racketmanager') );
						}
					}
				}
				$this->printMessage();
			} elseif ( isset($_GET['editleague']) ) {
				$league_id = htmlspecialchars($_GET['editleague']);
				$league = get_league($league_id);
				$league_title = $league->title;
			} elseif ( isset($_POST['saveSeason']) || isset($_GET['editseason'])) {
				$tab = 'seasons';
				if ( !empty($_POST['season']) ) {
					if ( empty($_POST['season_id']) ) {
						$this->addSeasonToCompetition( htmlspecialchars($_POST['season']), intval($_POST['num_match_days']), intval($_POST['competition_id']) );
					} else {
						$this->editSeason( intval($_POST['season_id']), intval($_POST['num_match_days']), intval($_POST['competition_id']) );
					}
				} else {
					if ( isset($_GET['editseason']) ) {
						$season_id = htmlspecialchars($_GET['editseason']);
						$season_data = $competition->seasons[$season_id];
					}
				}
				$this->printMessage();
			} elseif ( isset($_POST['doactionseason']) ) {
				$tab = 'seasons';
				check_admin_referer('seasons-bulk');
				if ( 'delete' == $_POST['action'] ) {
					$this->delCompetitionSeason( $_POST['del_season'], $competition->id );
				}
				$this->printMessage();
			} elseif ( isset($_POST['doactionleague']) && $_POST['action'] == 'delete' ) {
				if ( !current_user_can('del_leagues') ) {
					$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
				} else {
					check_admin_referer('leagues-bulk');
					$messages = array();
					$messageError= false;
					foreach ( $_POST['league'] as $league_id ) {
						$league = get_league($league_id);
						$league->delete();
						$messages[] = $league->title.' '.__('deleted', 'racketmanager');
					}
					$message = implode('<br>', $messages);
					$this->setMessage( $message, $messageError );
				}
				$this->printMessage();
			} elseif ( isset($_POST['doactionconstitution']) && $_POST['action'] == 'delete' ) {
				$tab = 'constitution';
				if ( current_user_can('del_leagues') ) {
					check_admin_referer('constitution-bulk');
					foreach ( $_POST['table'] as $tableId ) {
						$teams = isset($_POST['teamId']) ? $_POST['teamId'] : array();
						$leagues = isset($_POST['leagueId']) ? $_POST['leagueId'] : array();
						$team = isset($teams[$tableId]) ? $teams[$tableId] : 0;
						$league = isset($leagues[$tableId]) ? $leagues[$tableId] : 0;
						if ( isset($team) && isset($league) ) {
							$league = get_league($league);
							$league->deleteTeam($team, $_POST['latestSeason']);
						}
					}
				} else {
					$this->setMessage(__("You don't have permission to perform this task", 'racketmanager'), true);
				}
			} elseif ( isset($_POST['saveconstitution']) ) {
				$tab = 'constitution';
				check_admin_referer('constitution-bulk');
				$js = ( $_POST['js-active'] == 1 ) ? true : false;
				$rank = 0;
				foreach ( $_POST['tableId'] as $tableId ) {
					$team = $_POST['teamId'][$tableId];
					$leagueId = $_POST['leagueId'][$tableId];
					if ( $js ) {
						$rank ++;
					} else {
						$rank = isset($_POST['rank'][$tableId]) ? $_POST['rank'][$tableId] : '';
					}
					$status = $_POST['status'][$tableId];
					$profile = $_POST['profile'][$tableId];
					if ( $_POST['constitutionAction'] == 'insert' ) {
						$league = get_league($leagueId);
						$profile = '0';
						$league->addTeam($team, $_POST['latestSeason'], $rank, $status, $profile);
					} elseif ( $_POST['constitutionAction'] == 'update' ) {
						$this->updateTable( $tableId, $leagueId, $rank, $status, $profile );
					}
				}
			} elseif ( isset($_POST['action']) && $_POST['action'] == 'addTeamsToLeague' ) {
				$tab = 'constitution';
				foreach ( $_POST['team'] as $team_id ) {
					$rank = '99';
					$status = 'NT';
					$profile = '1';
					$league = get_league($_POST['league_id']);
					$league->addTeam($team_id, htmlspecialchars($_POST['season']), $rank, $status, $profile);
					$team = get_team($team_id);
					$team->setCompetition($_POST['competition_id']);
				}
			} elseif ( isset($_POST['updateSettings']) ) {
				check_admin_referer('racketmanager_manage-competition-options');
				if ( !current_user_can('edit_league_settings') ) {
					$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
				} else {
					$settings = $_POST['settings'];
					if ( $competition->name != $_POST['competition_title'] ) {
						$competition->setName($_POST['competition_title']);
					}
					$competition->setSettings($settings);
					$competition->reloadSettings();
					$competition = get_competition($competition);
					$this->setMessage( __('Settings saved', 'racketmanager') );
				}
				$this->printMessage();
				// Set active tab
				$tab = 'settings';
			} elseif ( isset($_GET['statsseason']) && $_GET['statsseason'] == 'Show' ) {
				if ( isset($_GET['club_id']) ) {
					$club_id = intval($_GET['club_id']);
				}
				$tab = 'playerstats';
			} elseif ( isset($_GET['view']) && $_GET['view'] == 'matches' ) {
				$tab = 'matches';
			} elseif ( isset($_POST['dosetseason']) ) {
				$season = $_POST['season'];
				$competition->setSeason($season, true);
			}
			if ( !isset($season) ) {
				$season = (isset($_GET['season']) ? htmlspecialchars($_GET['season']) : $competition->current_season['name']);
			}
			include_once RACKETMANAGER_PATH . '/admin/show-competition.php';
		}
	}

	/**
	* display league overview page
	*
	*/
	private function displayLeaguePage() {
		global $league, $championship, $competition, $racketmanager ;

		if ( !current_user_can( 'view_leagues' ) ) {
			echo '<div class="error"><p style="text-align: center;">'.__("You do not have sufficient permissions to access this page.").'</p></div>';
		} else {
			$league = get_league();
			$league->setSeason();
			$season = $league->getSeason();
			$league_mode = (isset($league->mode) ? ($league->mode) : '' );
			$tab = 'standings';
			$matchDay = false;
			if ( isset($_POST['doaction']) ) {
				if ( $_POST['action'] == "delete" ) {
					if ( current_user_can('del_teams') ) {
						check_admin_referer('teams-bulk');
						$messages = array();
						$messageError= false;
						foreach ( $_POST['team'] as $team_id ) {
							$league->deleteTeam(intval($team_id), $season);
							$messages[] = $team_id.' '.__('deleted', 'racketmanager');
						}
						$message = implode('<br>', $messages);
						$this->setMessage( $message, $messageError );
					} else {
						$this->setMessage(__("You don't have permission to perform this task", 'racketmanager'), true);
					}
				}
				if ( $league->is_championship ) {
					$tab = 'preliminary';
				}
			}  elseif ( isset($_POST['delmatches']) ) {
				if ( $_POST['delMatchOption'] == "delete" ) {
					if ( current_user_can('del_matches') ) {
						check_admin_referer('matches-bulk');
						$messages = array();
						foreach ( $_POST['match'] as $match_id ) {
							$match = get_match($match_id);
							$match->delete();
							$messages[] = (sprintf(__( 'Match id %d deleted', 'racketmanager' ), $match_id));
							$message = implode('<br>', $messages);
							$this->setMessage($message);
						}
						$tab = 'matches';
					} else {
						$this->setMessage(__("You don't have permission to perform this task", 'racketmanager'), true);
					}
				}
			} elseif ( isset($_POST['updateLeague']) && 'team' == $_POST['updateLeague'] ) {
				if ( current_user_can('edit_teams') ) {
					check_admin_referer('racketmanager_manage-teams');
					if ( 'Add' == $_POST['action'] ) {
						$this->setMessage(__('New team cannot be added to a league', 'racketmanager'), true);
					} else {
						$team = get_team(intval($_POST['team_id']));
						if ( $_POST['league_id'] && $_POST['editTeam'] ) {
							$team->setCompetition($league->competition_id, htmlspecialchars($_POST['captainId']), htmlspecialchars($_POST['contactno']), htmlspecialchars($_POST['contactemail']),  htmlspecialchars($_POST['matchday']), htmlspecialchars($_POST['matchtime']));
						} else {
							$team->update(htmlspecialchars(strip_tags($_POST['team'])), $_POST['affiliatedclub'], $_POST['team_type']);
						}
					}
				} else {
					$this->setMessage(__("You don't have permission to perform this task", 'racketmanager'), true);
				}
				if ( $league->is_championship ) {
					$tab = 'preliminary';
				}
			} elseif ( isset($_POST['updateLeague']) && 'teamPlayer' == $_POST['updateLeague'] ) {
				if ( current_user_can('edit_teams') ) {
					check_admin_referer('racketmanager_manage-teams');
					$teamPlayer2 = isset($_POST['teamPlayer2']) ? htmlspecialchars(strip_tags($_POST['teamPlayer2'])) : '';
					$teamPlayer2Id = isset($_POST['teamPlayerId2']) ? $_POST['teamPlayerId2'] : 0;
					if ( 'Add' == $_POST['action'] ) {
						$team = new stdClass();
						$team->player1 = htmlspecialchars(strip_tags($_POST['teamPlayer1']));
						$team->player1Id = $_POST['teamPlayerId1'];
						$team->player2 = $teamPlayer2;
						$team->player2Id = $teamPlayer2Id;
						$team->type = $league->type;
						$team->status = 'P';
						$team->affiliatedclub = intval($_POST['affiliatedclub']);
						$team = new Team($team);
						$team->setCompetition($league->competition_id, htmlspecialchars($_POST['captainId']), htmlspecialchars($_POST['contactno']), htmlspecialchars($_POST['contactemail']));
						$league->addTeam($team->id, htmlspecialchars($_POST['season']));
					} else {
						$team = get_team(intval($_POST['team_id']));
						if ( $team->status == 'P' ) {
							$team->updatePlayer(htmlspecialchars(strip_tags($_POST['teamPlayer1'])), $_POST['teamPlayerId1'], $teamPlayer2, $teamPlayer2Id, htmlspecialchars($_POST['affiliatedclub']));
							$team->setCompetition($league->competition_id, htmlspecialchars($_POST['captainId']), htmlspecialchars($_POST['contactno']), htmlspecialchars($_POST['contactemail']));
						} else {
							$this->setMessage(__('Team is not a player team', 'racketmanager'), true);
						}
					}
				} else {
					$this->setMessage(__("You don't have permission to perform this task", 'racketmanager'), true);
				}
				if ( $league->is_championship ) {
					$tab = 'preliminary';
				}
			} elseif ( isset($_POST['updateLeague']) && 'match' == $_POST['updateLeague'] ) {
				if ( current_user_can('edit_matches') ) {
					check_admin_referer('racketmanager_manage-matches');

					$group = isset($_POST['group']) ? htmlspecialchars(strip_tags($_POST['group'])) : '';

					if ( 'add' == $_POST['mode'] ) {
						$num_matches = count($_POST['match']);
						foreach ( $_POST['match'] as $i => $match_id ) {
							$match = new stdClass();
							if ( isset($_POST['add_match'][$i]) || $_POST['away_team'][$i] != $_POST['home_team'][$i]  ) {
								$index = ( isset($_POST['mydatepicker'][$i]) ) ? $i : 0;
								if (!isset($_POST['begin_hour'][$i])) { $_POST['begin_hour'][$i] = 0; }
								if (!isset($_POST['begin_minutes'][$i])) { $_POST['begin_minutes'][$i] = 0; }
								$match->date = $_POST['mydatepicker'][$index].' '.intval($_POST['begin_hour'][$i]).':'.intval($_POST['begin_minutes'][$i]).':00';
								$match->match_day = ( isset($_POST['match_day'][$i]) ? $_POST['match_day'][$i] : (!empty($_POST['match_day']) ? intval($_POST['match_day']) : '' )) ;
								$match->custom = isset($_POST['custom']) ? $_POST['custom'][$i] : array();
								$match->home_team = $_POST['home_team'][$i];
								$match->away_team = $_POST['away_team'][$i];
								$match->location = htmlspecialchars(strip_tags($_POST['location'][$i]));
								$match->league_id = intval($_POST['league_id']);
								$match->season = htmlspecialchars(strip_tags($_POST['season']));
								$match->group = $group;
								$match->final = htmlspecialchars(strip_tags($_POST['final']));
								$match->num_rubbers = intval($_POST['num_rubbers']);
								$match = new RM_Match($match);
							} else {
								$num_matches -= 1;
							}
						}
						$this->setMessage(sprintf(_n('%d Match added', '%d Matches added', $num_matches, 'racketmanager'), $num_matches));
					} else {
						if ( current_user_can('edit_matches') ) {
							$num_matches = count($_POST['match']);
							$post_match = $this->htmlspecialchars_array($_POST['match']);
							foreach ( $post_match as $i => $match_id ) {
								$match = get_match($match_id);
								$begin_hour = isset($_POST['begin_hour'][$i]) ? intval($_POST['begin_hour'][$i]) : "00";
								$begin_minutes = isset($_POST['begin_minutes'][$i]) ? intval($_POST['begin_minutes'][$i]) : "00";
								if( isset($_POST['mydatepicker'][$i]) ) {
									$index = ( isset($_POST['mydatepicker'][$i]) ) ? $i : 0;
									$date = htmlspecialchars(strip_tags($_POST['mydatepicker'][$index])).' '.$begin_hour.':'.$begin_minutes.':00';
								} else {
									$index = ( isset($_POST['year'][$i]) && isset($_POST['month'][$i]) && isset($_POST['day'][$i]) ) ? $i : 0;
									$date = intval($_POST['year'][$index]).'-'.intval($_POST['month'][$index]).'-'.intval($_POST['day'][$index]).' '.$begin_hour.':'.$begin_minutes.':00';
								}
								$match->date = $date;
								$match->league_id = $league->id;
								$match->match_day = (isset($_POST['match_day']) && is_array($_POST['match_day'])) ? intval($_POST['match_day'][$i]) : (isset($_POST['match_day']) && !empty($_POST['match_day']) ? intval($_POST['match_day']) : '' ) ;
								$match->custom = isset($_POST['custom']) ? $_POST['custom'][$i] : array();
								$match->home_team = isset($_POST['home_team'][$i]) ? htmlspecialchars(strip_tags($_POST['home_team'][$i])) : '';
								$match->away_team = isset($_POST['away_team'][$i]) ? htmlspecialchars(strip_tags($_POST['away_team'][$i])) : '';
								$match->location = htmlspecialchars($_POST['location'][$i]);
								$match->final = htmlspecialchars(strip_tags($_POST['final']));
								$match->update();
							}
							$this->setMessage(sprintf(_n('%d Match updated', '%d Matches updated', $num_matches, 'racketmanager'), $num_matches));
						} else {
							$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
						}
					}
				} else {
					$this->setMessage(__("You don't have permission to perform this task", 'racketmanager'), true);
				}
			} elseif ( isset($_POST['updateLeague']) && 'results' == $_POST['updateLeague'] ) {
				if ( current_user_can('update_results') ) {
					check_admin_referer('matches-bulk');
					$custom = isset($_POST['custom']) ? $_POST['custom'] : array();
					$this->updateResults( $_POST['matches'], $_POST['home_points'], $_POST['away_points'], $custom, $_POST['season'] );
					$tab = 'matches';
					$matchDay = intval($_POST['current_match_day']);
				} else {
					$this->setMessage(__("You don't have permission to perform this task", 'racketmanager'), true);
				}
			} elseif ( isset($_POST['updateLeague']) && 'teams_manual' == $_POST['updateLeague'] ) {
				if ( current_user_can('update_results') ) {
					check_admin_referer('teams-bulk');
					$points = array();
					$points['points_plus'] = $_POST['points_plus'];
					$points['points_minus'] = $_POST['points_minus'];
					$points['add_points'] = $_POST['add_points'];
					$matches = array();
					$matches['num_done_matches'] = $_POST['num_done_matches'];
					$matches['num_won_matches'] = $_POST['num_won_matches'];
					$matches['num_draw_matches'] = $_POST['num_draw_matches'];
					$matches['num_lost_matches'] = $_POST['num_lost_matches'];
					$league->saveStandingsManually( $_POST['team_id'], $points, $matches, $_POST['custom'] );

					$this->setMessage(__('Standings Table updated','racketmanager'));
				} else {
					$this->setMessage(__("You don't have permission to perform this task", 'racketmanager'), true);
				}
			} elseif ( isset($_POST['action']) && $_POST['action'] == 'addTeamsToLeague' ) {
				foreach ( $_POST['team'] as $i => $team_id ) {
					$league->addTeam($team_id, htmlspecialchars($_POST['season']));
					$team = get_team($team_id);
					$team->setCompetition($_POST['competition_id']);
					if ( $league->is_championship ) {
						$tab = 'preliminary';
					}
				}
			} elseif ( isset($_POST['contactTeam']) ) {
				if ( current_user_can('edit_teams') ) {
					check_admin_referer('racketmanager_contact-teams-preview');
					$this->contactLeagueTeams( intval($_POST['league_id']), $_POST['season'], htmlspecialchars_decode($_POST['emailMessage']) );
				} else {
					$this->setMessage(__("You don't have permission to perform this task", 'racketmanager'), true);
				}
			} elseif (isset($_POST['saveRanking'])) { // rank teams manually
				if ( current_user_can('update_results') ) {
					$js = ( $_POST['js-active'] == 1 ) ? true : false;

					$team_ranks = array();
					$team_ids = array_values($_POST['table_id']);
					foreach ($team_ids as $key => $team_id) {
						if ( $js ) {
							$rank = $key + 1;
						} else {
							$rank = intval($_POST['rank'][$team_id]);
						}
						$team = get_leagueteam($team_id);
						$team_ranks[$rank-1] = $team;
					}
					ksort($team_ranks);
					$team_ranks = $league->getRanking($team_ranks);
					$league->updateRanking($team_ranks);
					$this->setMessage(__('Team ranking saved','racketmanager'));
				} else {
					$this->setMessage(__("You don't have permission to perform this task", 'racketmanager'), true);
				}
				$tab = 'standings';
			} elseif (isset($_POST['randomRanking'])) { // rank teams randomly
				if ( current_user_can('update_results') ) {
					$js = ( $_POST['js-active'] == 1 ) ? true : false;
					$team_ranks = array();
					$team_ids = array_values($_POST['table_id']);
					shuffle($team_ids);
					foreach ($team_ids as $key => $team_id) {
						if ( $js ) {
							$rank = $key + 1;
						} else {
							$rank = intval($_POST['rank'][$team_id]);
						}
						$team = get_leagueteam($team_id);
						$team_ranks[$rank-1] = $team;
					}
					ksort($team_ranks);
					$team_ranks = $league->getRanking($team_ranks);
					$league->updateRanking($team_ranks);
					$this->setMessage(__('Team ranking saved','racketmanager'));
				} else {
					$this->setMessage(__("You don't have permission to perform this task", 'racketmanager'), true);
				}
				$tab = 'standings';
			} elseif (isset($_POST['updateRanking'])) {
				if ( current_user_can('update_results') ) {
					$league->_rankTeams($league->id);
					$this->setMessage(__('Team ranking updated','racketmanager'));
				} else {
					$this->setMessage(__("You don't have permission to perform this task", 'racketmanager'), true);
				}
				$tab = 'standings';
			}
			$this->printMessage();

			// check if league is a cup championship
			$cup = ( $league_mode == 'championship' ) ? true : false;

			$group = isset($_GET['group']) ? htmlspecialchars(strip_tags($_GET['group'])) : '';
			$team_id = isset($_GET['team_id']) ? intval($_GET['team_id']) : false;
			$match_day = isset($_GET['match_day']) ? intval($_GET['match_day']) : false;
			$options = $this->options;

			$match_args = array("final" => "", "cache" => false);
			if ( $season ) {
				$match_args["season"] = $season;
			}
			if ( $group ) {
				$match_args["group"] = $group;
			}
			if ( $team_id ) {
				$match_args['team_id'] = $team_id;
			}

			if (intval($league->num_matches_per_page) > 0) {
				$match_args['limit'] = intval($league->num_matches_per_page);
			}
			if ( isset($_GET['match_day'])) {
				if ($_GET['match_day'] != -1) {
					$matchDay = intval($_GET['match_day']);
					$league->setMatchDay($matchDay);
				}
				$tab = 'matches';
			} else {
				if ( $league->match_display == 'current_match_day' ) {
					$league->setMatchDay('current');
				} elseif ( $league->match_display == 'all' ) {
					$league->setMatchDay(-1);
				}
			}

			if ( empty($league->competition->seasons)  ) {
				$this->setMessage( __( 'You need to add at least one season for the competition', 'racketmanager' ), true );
				$this->printMessage();
			}

			$teams = $league->getLeagueTeams( array( "season" => $season, "cache" => false) );
			if ( $league_mode != 'championship' ) {
				$matches = $league->getMatches( $match_args );
				$league->setNumMatches();
			}

			if ( isset($_GET['match_paged']) ) {
				$tab = 'matches';
			}

			if ( isset($_GET['standingstable']) ) {
				$get = $_GET['standingstable'];
				$match_day = false;
				$mode = 'all';
				if ( preg_match('/match_day-\d/', $get, $hits) ) {
					$res = explode("-", $hits[0]);
					$match_day = $res[1];
				} elseif ( in_array($get, array('home', 'away')) ) {
					$mode = htmlspecialchars($get);
				}
				$teams = $league->getStandings( $teams, $match_day, $mode );
			}

			if (isset($_GET['match_day']) ) {
				$tab = 'matches';
			}

			include_once RACKETMANAGER_PATH . '/admin/show-league.php';
		}
	}

	/**
	* display teams list page
	*
	*/
	private function displayTeamsList() {
		global $racketmanager;

		if ( !current_user_can( 'edit_teams' ) ) {
			echo '<div class="error"><p style="text-align: center;">'.__("You do not have sufficient permissions to access this page.").'</p></div>';
		} else {
			$league_id = intval($_GET['league_id']);
			$league = get_league( $league_id );
			$leagueType = $league->type;
			if ( $leagueType == 'LD' ) {
				$leagueType = 'XD';
			}
			if ( $league->entryType == 'player' ) {
				$entryType = 'player';
			} else {
				$entryType = '';
			}
			$season = isset($_GET['season']) ? htmlspecialchars(strip_tags($_GET['season'])) : '';
			$view = isset($_GET['view']) ? htmlspecialchars(strip_tags($_GET['view'])) : '';
			include_once RACKETMANAGER_PATH . '/admin/includes/teamslist.php';
		}
	}

	/**
	* display leagues page
	*
	*/
	private function displayLeaguesPage() {
		global $racketmanager;

		if ( !current_user_can( 'edit_leagues' ) ) {
			echo '<div class="error"><p style="text-align: center;">'.__("You do not have sufficient permissions to access this page.").'</p></div>';
		} else {
			if ( isset($_POST['notifyLeagueOpen']) ) {
				check_admin_referer('racketmanager_notify-league-open');
				if ( isset($_POST['type']) ) {
					$notification = $this->notifyEntryOpen('league', htmlspecialchars($_POST['season']), htmlspecialchars($_POST['type']) );
					$this->setMessage($notification['msg'], isset($notification['error']) ? $notification['error'] : false );
				} else {
					$this->setMessage(__('Type not selected','racketmanager'), true );
				}
				$this->printMessage();
			}
			$competitionType = 'league';
			$type = '';
			$season = '';
			$standalone = true;
			$competitionQuery = array( 'type' => $competitionType );
			$pageTitle = __( ucfirst($competitionType), 'racketmanager').' '.__( 'Competitions', 'racketmanager' );
			include_once RACKETMANAGER_PATH . '/admin/show-competitions.php';
			include_once RACKETMANAGER_PATH . '/admin/show-league-entry.php';
		}
	}

	/**
	* display cups page
	*
	*/
	private function displayCupsPage() {
		global $racketmanager;

		if ( !current_user_can( 'edit_leagues' ) ) {
			echo '<div class="error"><p style="text-align: center;">'.__("You do not have sufficient permissions to access this page.").'</p></div>';
		} else {
			if ( isset($_POST['notifyCupOpen']) ) {
				check_admin_referer('racketmanager_notify-cup-open');
				if ( isset($_POST['type']) ) {
					$notification = $this->notifyEntryOpen('cup', htmlspecialchars($_POST['season']), htmlspecialchars($_POST['type']) );
					$this->setMessage($notification['msg'], isset($notification['error']) ? $notification['error'] : false );
				} else {
					$this->setMessage(__('Type not selected','racketmanager'), true );
				}
				$this->printMessage();
			}
			$competitionType = 'cup';
			$type = '';
			$season = '';
			$standalone = true;
			$competitionQuery = array( 'type' => $competitionType );
			$pageTitle = __( ucfirst($competitionType), 'racketmanager').' '.__( 'Competitions', 'racketmanager' );
			include_once RACKETMANAGER_PATH . '/admin/show-competitions.php';
			include_once RACKETMANAGER_PATH . '/admin/show-cup-entry.php';
		}
	}

	/**
	* display tournaments page
	*
	*/
	private function displayTournamentsPage() {
		global $racketmanager;

		if ( !current_user_can( 'edit_leagues' ) ) {
			echo '<div class="error"><p style="text-align: center;">'.__("You do not have sufficient permissions to access this page.").'</p></div>';
		} else {
			if ( isset($_POST['addTournament']) ) {
				if ( !current_user_can('edit_teams') ) {
					$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
				} else {
					check_admin_referer('racketmanager_add-tournament');
					$tournament = new stdClass();
					$tournament->name = htmlspecialchars($_POST['tournament']);
					$tournament->type = htmlspecialchars($_POST['type']);
					$tournament->season = htmlspecialchars($_POST['season']);
					$tournament->venue = intval($_POST['venue']);
					$tournament->date = htmlspecialchars($_POST['date']);
					$tournament->closingdate = htmlspecialchars($_POST['closingdate']);
					$tournament->numcourts = intval($_POST['numcourts']);
					$tournament->starttime = htmlspecialchars($_POST['starttime']);
					$tournament = new Tournament($tournament);
					$this->printMessage();
				}
			} elseif ( isset($_POST['editTournament']) ) {
				if ( !current_user_can('edit_teams') ) {
					$racketmanager->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
				} else {
					check_admin_referer('racketmanager_manage-tournament');
					$tournament = get_tournament(intval($_POST['tournament_id']));
					$tournament->update( htmlspecialchars($_POST['tournament']), htmlspecialchars($_POST['type']), htmlspecialchars($_POST['season']), htmlspecialchars($_POST['venue']),  htmlspecialchars($_POST['date']), htmlspecialchars($_POST['closingdate']), intval($_POST['numcourts']), htmlspecialchars($_POST['starttime']) );
					}
				$this->printMessage();
			} elseif ( isset($_POST['doTournamentDel']) && $_POST['action'] == 'delete' ) {
				if ( !current_user_can('del_teams') ) {
					$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
				} else {
					check_admin_referer('tournaments-bulk');
					foreach ( $_POST['tournament'] as $tournament_id ) {
						$tournament = get_tournament($tournament_id);
						$tournament->delete();
					}
				}
				$this->printMessage();
			}
			$club_id = 0;
			$this->printMessage();
			$clubs = $racketmanager->getClubs( );
			include_once RACKETMANAGER_PATH . '/admin/show-tournaments.php';
		}
	}

	/**
	* display tournament page
	*
	*/
	private function displayTournamentPage() {
		global $racketmanager;

		if ( !current_user_can( 'edit_teams' ) ) {
			echo '<div class="error"><p style="text-align: center;">'.__("You do not have sufficient permissions to access this page.").'</p></div>';
		} else {
			$edit = false;

			if ( isset( $_GET['tournament'] ) ) {
				$tournamentId = $_GET['tournament'];
				$edit = true;
				$tournament = get_tournament( $tournamentId );

				$form_title = __( 'Edit Tournament', 'racketmanager' );
				$form_action = __( 'Update', 'racketmanager' );
			} else {
				$tournamentId = '';
				$form_title = __( 'Add Tournament', 'racketmanager' );
				$form_action = __( 'Add', 'racketmanager' );
				$tournament = (object)array( 'name' => '', 'type' => '', 'id' => '', 'venue' => '', 'date' => '', 'closingdate' => '', 'numcourts' => '', 'starttime' => '');
			}

			$clubs = $racketmanager->getClubs( );
			include_once RACKETMANAGER_PATH . '/admin/includes/tournament.php';
		}
	}

	/**
	* display tournament plan page
	*
	*/
	private function displayTournamentPlanPage() {
		global $racketmanager;

		if ( !current_user_can( 'edit_teams' ) ) {
			echo '<div class="error"><p style="text-align: center;">'.__("You do not have sufficient permissions to access this page.").'</p></div>';
		} else {
			if ( isset($_POST['saveTournamentPlan']) ) {
				check_admin_referer('racketmanager_tournament-planner');
				$tournament = get_tournament($_POST['tournamentId']);
				$tournament->savePlan($_POST['court'], $_POST['starttime'], $_POST['match'], $_POST['matchtime']);
				$this->printMessage();
			} elseif ( isset($_POST['resetTournamentPlan']) ) {
				check_admin_referer('racketmanager_tournament-planner');
				$tournament = get_tournament($_POST['tournamentId']);
				$tournament->resetPlan();
				$this->printMessage();
			} elseif ( isset($_POST['saveTournament']) ) {
				check_admin_referer('racketmanager_tournament');
				$tournament = get_tournament($_POST['tournamentId']);
				$tournament->updatePlan($_POST['starttime'], $_POST['numcourts'], $_POST['timeincrement']);
				$this->printMessage();
			}

			if ( isset( $_GET['tournament'] ) ) {
				$tournamentId = $_GET['tournament'];
				$tournament = get_tournament( $tournamentId );
				$finalMatches = $racketmanager->getMatches( array('season' => $tournament->season, 'final' => 'final', 'competitiontype' => 'tournament', 'competitionseason' => $tournament->type));
			}
			include_once RACKETMANAGER_PATH . '/admin/includes/tournament-plan.php';
		}
	}

	/**
	* display clubs page
	*
	*/
	private function displayClubsPage() {
		global $racketmanager, $club;

		if ( !current_user_can( 'edit_leagues' ) ) {
			echo '<div class="error"><p style="text-align: center;">'.__("You do not have sufficient permissions to access this page.").'</p></div>';
		} else {
			if ( isset($_POST['addClub']) ) {
				check_admin_referer('racketmanager_add-club');
				if ( !current_user_can('edit_teams') ) {
					$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
				} else {
					$club = new stdClass();
					$club->name = htmlspecialchars(strip_tags($_POST['club']));
					$club->type = htmlspecialchars($_POST['type']);
					$club->shortcode = htmlspecialchars($_POST['shortcode']);
					$club->contactno = htmlspecialchars($_POST['contactno']);
					$club->website = htmlspecialchars($_POST['website']);
					$club->founded = htmlspecialchars($_POST['founded']);
					$club->facilities = htmlspecialchars($_POST['facilities']);
					$club->address = htmlspecialchars($_POST['address']);
					$club->latitude = htmlspecialchars($_POST['latitude']);
					$club->longitude = htmlspecialchars($_POST['longitude']);
					$club = new Club($club);
					$this->setMessage( __('Club added','racketmanager') );
				}
				$this->printMessage();
			} elseif ( isset($_POST['editClub']) ) {
				check_admin_referer('racketmanager_manage-club');
				if ( !current_user_can('edit_teams') ) {
					$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
				} else {
					$club = get_club(intval($_POST['club_id']));
					$club->name = htmlspecialchars(strip_tags($_POST['club']));
					$club->type = htmlspecialchars($_POST['type']);
					$oldShortcode = $club->shortcode;
					if ( $club->shortcode != htmlspecialchars($_POST['shortcode']) ) {
						$club->shortcode = htmlspecialchars($_POST['shortcode']);
					}
					$club->matchsecretary = intval($_POST['matchsecretary']);
					$club->matchSecretaryContactNo = htmlspecialchars($_POST['matchSecretaryContactNo']);
					$club->matchSecretaryEmail = htmlspecialchars($_POST['matchSecretaryEmail']);
					$club->contactno = htmlspecialchars($_POST['contactno']);
					$club->website = htmlspecialchars($_POST['website']);
					$club->founded = htmlspecialchars($_POST['founded']);
					$club->facilities = htmlspecialchars($_POST['facilities']);
					$club->address = htmlspecialchars($_POST['address']);
					$club->latitude = htmlspecialchars($_POST['latitude']);
					$club->longitude = htmlspecialchars($_POST['longitude']);
					$club->update( $club, $oldShortcode );
					$this->setMessage( __('Club updated','racketmanager') );
				}
				$this->printMessage();
			} elseif ( isset($_POST['doClubDel']) && $_POST['action'] == 'delete' ) {
				check_admin_referer('clubs-bulk');
				if ( !current_user_can('del_teams') ) {
					$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
				} else {
					$messages = array();
					$messageError= false;
					foreach ( $_POST['club'] as $club_id ) {
						$club = get_club($club_id);
						if ( $club->hasTeams() ) {
							$messages[] = $club->name.' '.__('not deleted - still has teams attached','racketmanager');
							$messageError = true;
						} else {
							$club->delete();
							$messages[] = $club->name.' '.__('deleted', 'racketmanager');
						}
					}
					$message = implode('<br>', $messages);
					$this->setMessage( $message, $messageError );
					$club_id = 0;
				}

				$this->printMessage();
			}
			include_once RACKETMANAGER_PATH . '/admin/show-clubs.php';
		}
	}

	/**
	* display club page
	*
	*/
	private function displayClubPage() {
		global $racketmanager;

		if ( !current_user_can( 'edit_teams' ) ) {
			echo '<div class="error"><p style="text-align: center;">'.__("You do not have sufficient permissions to access this page.").'</p></div>';
		} else {
			$edit = false;
			$league_id = '';
			$season = '';
			if ( isset( $_GET['club_id'] ) ) {
				$clubId = $_GET['club_id'];
				$edit = true;
				$club = get_club( $clubId );
				$form_title = __( 'Edit Club', 'racketmanager' );
				$form_action = __( 'Update', 'racketmanager' );
			} else {
				$clubId = '';
				$form_title = __( 'Add Club', 'racketmanager' );
				$form_action = __( 'Add', 'racketmanager' );
				$club = (object)array( 'name' => '', 'type' => '', 'id' => '', 'website' => '', 'matchsecretary' => '', 'matchSecretaryName' => '', 'contactno' => '', 'matchSecretaryContactNo' => '', 'matchSecretaryEmail' => '', 'shortcode' => '', 'founded' => '', 'facilities' => '', 'address' => '', 'latitude' => '', 'longitude' => '' );
			}
			include_once RACKETMANAGER_PATH . '/admin/includes/club.php';
		}
	}

	/**
	* display club players page
	*
	*/
	private function displayClubPlayersPage() {
		global $racketmanager;

		if ( !current_user_can( 'edit_teams' ) ) {
			echo '<div class="error"><p style="text-align: center;">'.__("You do not have sufficient permissions to access this page.").'</p></div>';
		} else {
			if ( isset($_POST['addPlayer']) ) {
				check_admin_referer('racketmanager_manage-player');
				$playerValid = $this->validatePlayer();
				if ( $playerValid[0] ) {
					$newPlayer = $playerValid[1];
					$club = get_club($_POST['club_Id']);
					$club->registerPlayer($newPlayer);
				} else {
					$formValid = false;
					$errorFields = $playerValid[1];
					$errorMsgs = $playerValid[2];
					$message = __('Error with player details', 'racketmanager');
					foreach( $errorMsgs as $errorMsg ) {
						$message .= '<br>'.$errorMsg;
						$this->setMessage( $message, true);
					}
				}
			} elseif ( isset($_POST['doClubPlayerdel']) && $_POST['action'] == 'delete' ) {
				check_admin_referer('club-players-bulk');
				foreach ( $_POST['clubPlayer'] as $roster_id ) {
					$this->delClubPlayer( intval($roster_id) );
				}
			}
			$this->printMessage();
			if (isset($_GET['club_id'])) {
				$club_id = $_GET['club_id'];
			}
			$club = get_club($club_id);
			$active = isset($_GET['active']) ? $_GET['active'] : false;
			$gender = isset($_GET['gender']) ? $_GET['gender'] : false;
			$players = $club->getPlayers(array('active' => $active, 'gender' => $gender));
			include_once RACKETMANAGER_PATH . '/admin/club/show-club-players.php';
		}
	}

	/**
	* display player page
	*
	*/
	private function displayPlayerPage() {
		global $racketmanager;

		if ( !current_user_can( 'edit_teams' ) ) {
			echo '<div class="error"><p style="text-align: center;">'.__("You do not have sufficient permissions to access this page.").'</p></div>';
		} else {
			$formValid = true;
			if ( isset($_POST['updatePlayer']) ) {
				check_admin_referer('racketmanager_manage-player');
				$playerValid = $this->validatePlayer();
				if ( $playerValid[0] ) {
					$player = get_player($_POST['player_id']);
					$newPlayer = $playerValid[1];
					$player->update($newPlayer->firstname, $newPlayer->surname, $newPlayer->gender, $newPlayer->btm, $newPlayer->email, $newPlayer->locked);
				} else {
					$formValid = false;
					$errorFields = $playerValid[1];
					$errorMsgs = $playerValid[2];
					$message = __('Error with player details', 'racketmanager');
					foreach( $errorMsgs as $errorMsg ) {
						$message .= '<br>'.$errorMsg;
						$this->setMessage( $message, true);
					}
				}
			}
			$this->printMessage();
			if (isset($_GET['club_id'])) { $club_id = $_GET['club_id']; }
			if (isset($_GET['player_id'])) { $player_id = $_GET['player_id']; }
			$player = get_player($player_id);
			include_once RACKETMANAGER_PATH . '/admin/players/show-player.php';
		}
	}

	public function validatePlayer() {
		global $racketmanager;
		$options = $racketmanager->getOptions('rosters');
		if ( isset($options['btm']) && $options['btm'] == 1 ) {
			$btmRequired = true;
		} else {
			$btmRequired = false;
		}

		$return = array();
		$valid = true;
		$errorField = array();
		$errorMsg = array();
		$errorId = 0;
		if ( isset($_POST['firstname']) && $_POST['firstname'] == '' ) {
			$valid = false;
			$errorField[$errorId] = "firstname";
			$errorMsg[$errorId] = "First name required";
			$errorId ++;
		} else {
			$firstname = trim($_POST['firstname']);
		}
		if ( isset($_POST['surname']) && $_POST['surname'] == '' ) {
			$valid = false;
			$errorField[$errorId] = "surname";
			$errorMsg[$errorId] = "Surname required";
			$errorId ++;
		} else {
			$surname = trim($_POST['surname']);
		}
		if ( !isset($_POST['gender']) || $_POST['gender'] == '' ) {
			$valid = false;
			$errorField[$errorId] = "gender";
			$errorMsg[$errorId] = "Gender required";
			$errorId ++;
		} else {
			$gender = $_POST['gender'];
		}
		if ( !isset($_POST['btm']) || $_POST['btm'] == '' ) {
			if ( $btmRequired ) {
				$valid = false;
				$errorField[$errorId] = "btm";
				$errorMsg[$errorId] = "LTA Tennis Number required";
				$errorId ++;
			} else {
				$btm = '';
			}
		} else {
			$btm = $_POST['btm'];
		}
		if ( !isset($_POST['email']) || $_POST['email'] == '' ) {
			$email = '';
		} else {
			$email = $_POST['email'];
		}
		if ( !isset($_POST['locked']) || $_POST['locked'] == '' ) {
			$locked = '';
		} else {
			$locked = $_POST['locked'];
		}

		if ( $valid ) {
			$player = new stdClass();
			$player->data = array();
			$player->firstname = $firstname;
			$player->surname = $surname;
			$player->fullname = $firstname.' '.$surname;
			$player->user_login = strtolower($firstname).'.'.strtolower($surname);
			$player->email = $email;
			$player->btm = $btm;
			$player->gender = $gender;
			$player->locked = $locked;
			array_push($return, $valid, $player);
		} else {
			array_push($return, $valid, $errorField, $errorMsg);
		}
		return $return;
	}

	/**
	* display competitions list page
	*
	*/
	private function displayCompetitionsList() {
		global $racketmanager;

		if ( !current_user_can( 'edit_teams' ) ) {
			echo '<div class="error"><p style="text-align: center;">'.__("You do not have sufficient permissions to access this page.").'</p></div>';
		} else {
			if ( isset( $_GET['season'] ) ) {
				$season = $_GET['season'];
				$season = $racketmanager->getSeasonDB( array( 'name' => $season) );
			}
			if ( isset( $_GET['tournament'] ) ) {
				$tournament = get_tournament($_GET['tournament']);
			}
			include_once RACKETMANAGER_PATH . '/admin/includes/competitions-list.php';
		}
	}

	/**
	* display teams page
	*
	*/
	private function displayTeamsPage() {
		global $racketmanager;

		if ( !current_user_can( 'edit_teams' ) ) {
			echo '<div class="error"><p style="text-align: center;">'.__("You do not have sufficient permissions to access this page.").'</p></div>';
		} else {
			if ( isset($_POST['addTeam']) ) {
				check_admin_referer('racketmanager_add-team');
				$club = get_club($_POST['affiliatedClub']);
				$club->addTeam($_POST['team_type']);
			} elseif ( isset($_POST['editTeam']) ) {
				check_admin_referer('racketmanager_manage-teams');
				if ( !current_user_can('edit_teams') ) {
					$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
				} else {
					$team = get_team(intval($_POST['team_id']));
					$team->update(htmlspecialchars(strip_tags($_POST['team'])), $_POST['affiliatedclub'], $_POST['team_type']);
				}
			} elseif ( isset($_POST['doteamdel']) && $_POST['action'] == 'delete' ) {
				if ( !current_user_can('del_teams') ) {
					$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
				} else {
					check_admin_referer('teams-bulk');
					$messages = array();
					$messageError= false;
					foreach ( $_POST['team'] as $team_id ) {
						$team = get_team($team_id);
						$team->delete();
						$messages[] = $team->title.' '.__('deleted', 'racketmanager');
					}
					$message = implode('<br>', $messages);
					$this->setMessage( $message, $messageError );
				}
			}
			$this->printMessage();
			if (isset($_GET['club_id'])) {
				$club_id = $_GET['club_id'];
			}
			$club = get_club($club_id);
			include_once  RACKETMANAGER_PATH . '/admin/club/show-teams.php';
		}
	}

	/**
	* display team page
	*
	*/
	private function displayTeamPage() {
		global $racketmanager;

		if ( !current_user_can( 'edit_teams' ) ) {
			echo '<div class="error"><p style="text-align: center;">'.__("You do not have sufficient permissions to access this page.").'</p></div>';
		} else {
			$file = "team.php";
			$edit = false;
			$league = false;
			if ( isset( $_GET['league_id'] ) ) {
				$league_id = intval($_GET['league_id']);
				$league = get_league( $league_id );
				$season = isset($_GET['season']) ? htmlspecialchars(strip_tags($_GET['season'])) : '';
				$matchdays = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
				if ( isset($league->entryType) && $league->entryType == 'player' ) {
					$file = "playerteam.php";
				}
			} else {
				$league_id = '';
				$season = '';
				if ( isset( $_GET['club_id'] ) ) {
					$clubId = $_GET['club_id'];
				} else {
					$clubId = '';
				}
			}

			if ( isset( $_GET['edit'] ) ) {
				$edit = true;
				if ( $league ) {
					$team = $league->getTeamDtls(intval($_GET['edit']));
				} else {
					$team = get_team(intval($_GET['edit']));
				}
				if ( !isset($team->roster) ) {
					$team->roster = array();
				}
				$form_title = __( 'Edit Team', 'racketmanager' );
				$form_action = __( 'Update', 'racketmanager' );
			} else {
				$form_title = __( 'Add Team', 'racketmanager' );
				$form_action = __( 'Add', 'racketmanager' );
				$team = new stdClass();
				$team->id = '';
				$team->title = '';
				$team->captain = '';
				$team->captainId = '';
				$team->contactno = '';
				$team->contactemail = '';
				$team->match_day = '';
				$team->match_time = '';
			}
			$clubs = $racketmanager->getClubs( );

			require_once RACKETMANAGER_PATH . '/admin/includes/teams/'. $file;
		}
	}

	/**
	* display match editing page
	*
	*/
	private function displayMatchPage() {
		global $wpdb, $competition;

		if ( !current_user_can( 'edit_matches' ) ) {
			echo '<div class="error"><p style="text-align: center;">'.__("You do not have sufficient permissions to access this page.").'</p></div>';
		} else {
			$error = $is_finals = $finalkey = $cup = $singleCupGame = false;
			$group = ( isset($_GET['group']) ? htmlspecialchars($_GET['group']) : '');
			$class = 'alternate';
			$bulk = false;
			if ( isset($_GET['league_id']) ) {
				$league_id = intval($_GET['league_id']);
				$league = get_league( $league_id );
				$non_group = (isset($league->non_group) ? $league->non_group : 0);

				// check if league is a cup championship
				$cup = ( $league->mode == 'championship' ) ? true : false;
			}
			$season = $league->current_season['name'];

			// select first group if none is selected and league is cup championship
			if ( $cup && empty($group) && !$is_finals ) {
				$groups = ( isset($league->groups) ? $league->groups : '');
				if ( !is_array($groups) ) {
					$groups = explode(";", $groups);
				}
				if (isset($groups[0])) {
					$group = $groups[0];
				} else {
					$group = '';
				}
			}

			$matches = array();
			if ( isset( $_GET['edit'] ) ) {

				$mode = 'edit';
				$edit = true;
				$bulk = false;
				$form_title  = $submit_title = __( 'Edit Match', 'racketmanager' );

				$id = intval($_GET['edit']);
				$match = get_match($id);
				if ( isset($match->final_round) && $match->final_round != '' ) {
					$cup = true;
					$singleCupGame = true;
				}
				$league_id = $match->league_id;
				$matches[0] = $match;
				$match_day = $match->match_day;
				$finalkey = isset($match->final_round) ? $match->final_round : '';

				$max_matches = 1;

			} elseif ( isset($_GET['match_day']) ) {
				$mode = 'edit';
				$edit = true; $bulk = true;
				$order = false;

				$match_day = intval($_GET['match_day']);
				$season = htmlspecialchars($_GET['season']);

				$match_args = array("match_day" => $match_day, "season" => $season);
				if ( $cup ) {
					$match_args["group"] = $group;
				}

				$form_title = sprintf(__( 'Edit Matches - %d. Match Day', 'racketmanager' ), $match_day);
				$submit_title = __('Edit Matches', 'racketmanager');

				$matches = $league->getMatches( $match_args );
				$max_matches = count($matches);
			} elseif ( isset($_GET['final']) ) {
				$is_finals = true;
				$bulk = false;
				$order = false;
				$finalkey = $league->championship->getCurrentFinalKey();
				$mode = htmlspecialchars($_GET['mode']);
				$edit = ( $mode == 'edit' ) ? true : false;

				$final = $league->championship->getFinals($finalkey);
				$num_first_round = $league->championship->num_teams_first_round;

				$max_matches = $final['num_matches'];

				if ( 'add' == $mode ) {
					$form_title = $submit_title = sprintf(__( 'Add Matches - %s', 'racketmanager' ), $league->championship->getFinalname($finalkey));
					for ( $h = 0; $h < $max_matches; $h++ ) {
						$matches[$h] = new RM_Match();
						$matches[$h]->hour = $league->default_match_start_time['hour'];
						$matches[$h]->minutes = $league->default_match_start_time['minutes'];
					}
				} else {
					$form_title = $submit_title = sprintf(__( 'Edit Matches - %s', 'racketmanager' ), $league->championship->getFinalname($finalkey));
					$match_args = array("final" => $finalkey);
					$matches = $league->getMatches( $match_args );
				}
			} else {
				$mode = 'add';
				$edit = false;
				$bulk = $cup ? true : false;
				global $wpdb;

				// Get max match day
				$search = $wpdb->prepare("`league_id` = '%d' AND `season`  = '%s'", $league->id, $season);
				if ( $cup ) {
					$search .= $wpdb->prepare(" AND `group` = '%s'", $group);
				}

				$maxMatchDay = $wpdb->get_var( "SELECT MAX(match_day) FROM {$wpdb->racketmanager_matches} WHERE  ".$search."" );

				if ( !isset($_GET['final']) ) {
					if ( $cup ) {
						$form_title = sprintf(__( 'Add Matches - Group %s', 'racketmanager' ), $group);
						$submit_title = __( 'Add Matches', 'racketmanager' );
						$max_matches = ceil(($league->num_teams/2) * $season['num_match_days']); // set number of matches to add to half the number of teams per match day
					} else {
						$form_title = $submit_title = __( 'Add Matches', 'racketmanager' );
						$max_matches = ceil($league->num_teams_total); // set number of matches to add to half the number of teams per match day
					}
					$match_day = 1;
					$matches[] = new stdClass();
					$matches[0]->year = ( isset($_GET['season']) && is_numeric($_GET['season']) ) ? intval($_GET['season']) : date("Y");
				}

				// Simply limit the number of matches to add to 50
				if ($max_matches > 50) {
					$max_matches = 50;
				}
				for ( $i = 0; $i < $max_matches; $i++ ) {
					$matches[] = new RM_Match();
					$matches[$i]->hour = $league->default_match_start_time['hour'];
					$matches[$i]->minutes = $league->default_match_start_time['minutes'];
				}
			}

			if ( $singleCupGame ) {
				$final = $league->championship->getFinals($finalkey);
				$finalTeams = $league->championship->getFinalTeams($final['key'], 'ARRAY');
				if ( is_numeric($match->home_team) ) {
					$home_title = get_team($match->home_team)->title;
				} else {
					$home_title = $finalTeams[$match->home_team]->title;
				}
				if ( is_numeric($match->away_team) ) {
					$away_title = get_team($match->away_team)->title;
				} else {
					$away_title = $finalTeams[$match->away_team]->title;
				}
			} elseif ( $is_finals ) {
				$teams = $league->championship->getFinalTeams( $finalkey );
				$teamsHome = $teams;
			} else {
				$teams = $league->getLeagueTeams( array("season" => $season, "orderby" => array("title" => "ASC")) );
			}
			include_once RACKETMANAGER_PATH . '/admin/includes/match.php';
		}
	}

	/**
	* display admin page
	*
	*/
	private function displayAdminPage() {
		global $racketmanager;

		$players = '';

		if ( !current_user_can( 'edit_leagues' ) ) {
			echo '<div class="error"><p style="text-align: center;">'.__("You do not have sufficient permissions to access this page.").'</p></div>';
		} else {
			$tab = "seasons";
			if ( isset($_POST['addSeason']) ) {
				check_admin_referer('racketmanager_add-season');
				$this->addSeason( htmlspecialchars(strip_tags($_POST['seasonName'])) );
				$tab = "seasons";
			} elseif ( isset($_POST['doSeasonDel']) && $_POST['action'] == 'delete' ) {
				check_admin_referer('seasons-bulk');
				foreach ( $_POST['season'] as $season_id ) {
					$this->delSeason( intval($season_id) );
				}
				$tab = "seasons";
			} elseif ( isset($_POST['doaddCompetitionsToSeason']) && $_POST['action'] == 'addCompetitionsToSeason' ) {
				check_admin_referer('racketmanager_add-seasons-competitions-bulk');
				foreach ( $_POST['competition'] as $competition_id ) {
					$this->addSeasonToCompetition( htmlspecialchars($_POST['season']), intval($_POST['num_match_days']), $competition_id );
				}
				$tab = "seasons";
			}
			$this->printMessage();

			include_once RACKETMANAGER_PATH . '/admin/show-admin.php';
		}
	}

	/**
	* display players page
	*
	*/
	private function displayPlayersPage() {
		global $racketmanager;

		$players = '';

		if ( !current_user_can( 'edit_leagues' ) ) {
			echo '<div class="error"><p style="text-align: center;">'.__("You do not have sufficient permissions to access this page.").'</p></div>';
		} else {
			$clubId = isset($_GET['club']) ? $_GET['club'] : '';
			$status = isset($_GET['status']) ? $_GET['status'] : '';
			$tab = isset($_GET['tab']) ? $_GET['tab'] : "playerrequest";
			if ( isset($_POST['addPlayer']) ) {
				check_admin_referer('racketmanager_add-player');
				$playerValid = $this->validatePlayer();
				if ( $playerValid[0] ) {
					$newPlayer = $playerValid[1];
					$player = get_player($newPlayer->user_login, 'name');
					if ( !$player ) {
						$player = new Player($newPlayer);
						$this->setMessage(__('Player added', 'racketmanager'));
					} else {
						$this->setMessage( __('Player already exists', 'racketmanager'), true );
					}
				}
				$tab = "players";
			} elseif ( isset($_POST['doPlayerDel']) ) {
				if ( $_POST['action'] == 'delete' ) {
					if ( current_user_can('edit_teams') ) {
						check_admin_referer('player-bulk');
						$messages = array();
						$messageError= false;
						foreach ( $_POST['player'] as $player_id ) {
							$player = get_player($player_id);
							$player->delete();
							$messages[] = $player->fullname.' '.__('deleted', 'racketmanager');
						}
						$message = implode('<br>', $messages);
						$this->setMessage( $message, $messageError );
					} else {
						$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
					}
				}
				$tab = "players";
			} elseif ( isset($_GET['doPlayerSearch']) ) {
				if ( $_GET['name'] ) {
					$players = $racketmanager->getPlayers( array('name' => $_GET['name']) );
				} else {
					$this->setMessage( __("No search term specified", 'racketmanager'), true );
				}
				$tab = "players";
			} elseif ( isset($_POST['doplayerrequest']) ) {
				if ( current_user_can('edit_teams') ) {
					check_admin_referer('club-player-request-bulk');
					foreach ( $_POST['playerRequest'] as $i => $playerRequest_id ) {
						if ( $_POST['action'] == 'approve' ) {
							$this->_approvePlayerRequest( intval($_POST['club_id'][$i]), intval($playerRequest_id) );
						} elseif ( $_POST['action'] == 'delete' ) {
							$this->deletePlayerRequest( intval($playerRequest_id) );
						}
					}
				} else {
					$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
				}
				$tab = "playerrequest";
			} elseif ( isset($_GET['view']) && $_GET['view'] == 'playerRequest' ) {
				$tab = "playerrequest";
			} elseif ( isset($_GET['tab']) && $_GET['tab'] == 'players' ) {
				$tab = "players";
			}
			$this->printMessage();
			if ( !$players ) {
				$players = $racketmanager->getPlayers( array() );
			}
			$playerRequests = Racketmanager_Util::getPlayerRequests(array('club' => $clubId, 'status' => $status));

			include_once RACKETMANAGER_PATH . 'admin/show-players.php';
		}
	}

	/**
	* display import Page
	*
	*/
	private function displayImportPage() {
		global $competition;
		if ( !current_user_can( 'import_leagues' ) ) {
			echo '<div class="error"><p style="text-align: center;">'.__("You do not have sufficient permissions to access this page.").'</p></div>';
		} else {
			if ( isset($_POST['import']) ) {
				check_admin_referer('racketmanager_import-datasets');
				$leagueId = isset($_POST['league_id']) ? $_POST['league_id'] : 0;
				$affiliatedClub = isset($_POST['affiliatedClub']) ? $_POST['affiliatedClub'] : 0;
				$this->import( $leagueId, $_FILES['racketmanager_import'], htmlspecialchars($_POST['delimiter']), htmlspecialchars($_POST['mode']), $affiliatedClub );
				$this->printMessage();
			}
			global $racketmanager;
			include_once RACKETMANAGER_PATH . '/admin/tools/import.php';
		}
	}

	/**
	* display contact page
	*
	*/
	private function displayContactPage() {
		global $racketmanager, $racketmanager_shortcodes;

		if ( !current_user_can( 'edit_teams' ) ) {
			echo '<div class="error"><p style="text-align: center;">'.__("You do not have sufficient permissions to access this page.", 'racketmanager').'</p></div>';
		} else {
			if ( isset($_POST['contactTeamPreview']) ) {
				if ( isset($_POST['league_id']) ) {
					$league = get_league($_POST['league_id']);
				}
				if ( isset($_POST['season']) ) {
					$season = $_POST['season'];
				}
				$emailTitle = $_POST['contactTitle'];
				$emailIntro = $_POST['contactIntro'];
				$emailBody = $_POST['contactBody'];
				$emailClose = $_POST['contactClose'];
				$organisationName = $racketmanager->site_name;
				$emailMessage = $racketmanager_shortcodes->loadTemplate( 'contact-teams', array( 'league' => $league, 'organisationName' => $organisationName, 'season' => $season, 'title' => $emailTitle, 'intro' => $emailIntro, 'body' => $emailBody, 'closing' => $emailClose ), 'email' );
				$tab = 'preview';
			} else {
				if ( isset($_GET['league_id']) ) {
					$league = get_league($_GET['league_id']);
				}
				if ( isset($_GET['season']) ) {
					$season = $_GET['season'];
				}
				$emailTitle = '';
				$emailIntro = '';
				$emailClose = '';
				$emailBody = array();
				$emailMessage = '';
				$tab = 'compose';
			}

			include_once RACKETMANAGER_PATH . '/admin/includes/contact.php';
		}
	}

	/**
	* display finances page
	*
	*/
	private function displayFinancesPage() {
		global $racketmanager;

		$players = '';

		if ( !current_user_can( 'edit_leagues' ) ) {
			echo '<div class="error"><p style="text-align: center;">'.__("You do not have sufficient permissions to access this page.").'</p></div>';
		} else {
			$clubId = isset($_GET['club']) ? $_GET['club'] : '';
			$status = isset($_GET['status'])  ? $_GET['status'] : '';
			$tab = isset($_GET['tab']) ? $_GET['tab'] : "charges";
			if ( isset($_POST['generateInvoices']) ) {
				$tab = 'invoices';
				$chargesId = $_POST['charges_id'];
				$charges = get_Charges( $chargesId);
				$chargesEntries = $charges->getClubEntries();
				$billing = $this->getOptions('billing');
				$dateDue = new DateTime($charges->date);
				if ( isset($billing['paymentTerms']) && intval($billing['paymentTerms']) != 0 ) {
					$dateInterval = intval($billing['paymentTerms']);
					$dateInterval = "P".$dateInterval."D";
					$dateDue->add(new DateInterval($dateInterval));
				}
				$invoiceNumber = $billing['invoiceNumber'];
				foreach ($chargesEntries as $entry) {
					$invoice = new stdClass();
					$invoice->charge_id = $charges->id;
					$invoice->club_id = $entry->id;
					$invoice->invoiceNumber = $billing['invoiceNumber'];
					$invoice->status = 'new';
					$invoice->date = $charges->date;
					$invoice->date_due = $dateDue->format('Y-m-d');
					$invoice = new Invoice($invoice);
					$sent = false;
					$sent = $invoice->send();
					if ( $sent ) {
						$invoice->setStatus('sent');
					}
					$billing['invoiceNumber'] += 1;
				}
				if ( $sent ) {
					$options = $this->getOptions();
					$options['billing']['invoiceNumber'] = $billing['invoiceNumber'];
					update_option( 'leaguemanager', $options );
					$this->setMessage( __('Invoices sent', 'racketmanager') );
					$charges->setStatus('final');
				} else {
					$this->setMessage( __('No invoices sent', 'racketmanager'), true );
				}
			} elseif ( isset($_POST['doChargesDel']) && $_POST['action'] == 'delete' ) {
				$tab = 'charges';
				check_admin_referer('charges-bulk');
				if ( !current_user_can('del_teams') ) {
					$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
				} else {
					$messages = array();
					$messageError= false;
					foreach ( $_POST['charge'] as $charges_id ) {
						$charge = get_charges($charges_id);
						$chargeRef = ucfirst($charge->type).' '.$charge->season;
						if ( $charge->hasInvoices() ) {
							$messages[] = $chargeRef.' '.__('not deleted - still has invoices attached','racketmanager');
							$messageError = true;
						} else {
							$charge->delete();
							$messages[] = $chargeRef.' '.__('deleted', 'racketmanager');
						}
					}
					$message = implode('<br>', $messages);
					$this->setMessage( $message, $messageError );
				}
			} elseif ( isset($_POST['doActionInvoices']) && $_POST['action'] != -1 ) {
				$tab = 'invoices';
				check_admin_referer('invoices-bulk');
				if ( !current_user_can('del_teams') ) {
					$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
				} else {
					$messages = array();
					$messageError= false;
					foreach ( $_POST['invoice'] as $invoice_id ) {
						$invoice = get_invoice($invoice_id);
						if ( $invoice->status != $_POST['action'] ) {
							$invoice->setStatus($_POST['action']);
							$messages[] = __('Invoice', 'racketmanager').' '.$invoice->invoiceNumber.' '.__('updated', 'racketmanager');
						}
					}
					$message = implode('<br>', $messages);
					$this->setMessage( $message, $messageError );
				}
			}

			$this->printMessage();

			$invoices = $racketmanager->getInvoices(array('club' => $clubId, 'status' => $status));
			include_once RACKETMANAGER_PATH . '/admin/show-finances.php';
		}
	}

	/**
	* display charges page
	*
	*/
	private function displayChargesPage() {
		global $racketmanager, $racketmanager_shortcodes;

		if ( !current_user_can( 'edit_teams' ) ) {
			echo '<div class="error"><p style="text-align: center;">'.__("You do not have sufficient permissions to access this page.").'</p></div>';
		} else {
			if ( isset($_POST['saveCharges']) ) {
				if( isset($_POST['charges_id']) && $_POST['charges_id'] != '' ) {
					$charges = get_charges($_POST['charges_id']);
					$updates = false;
					if ( $charges->feeClub != $_POST['feeClub'] ) {
						$charges->setFeeClub($_POST['feeClub']);
						$updates = true;
					}
					if ( $charges->feeTeam != $_POST['feeTeam'] ) {
						$charges->setFeeTeam($_POST['feeTeam']);
						$updates = true;
					}
					if ( $charges->status != $_POST['status'] ) {
						$charges->setStatus($_POST['status']);
						$updates = true;
					}
					if ( $charges->competitionType != $_POST['competitionType'] ) {
						$charges->setCompetitionType($_POST['competitionType']);
						$updates = true;
					}
					if ( $charges->type != $_POST['type'] ) {
						$charges->setType($_POST['type']);
						$updates = true;
					}
					if ( $charges->date != $_POST['date'] ) {
						$charges->setDate($_POST['date']);
						$updates = true;
					}
					if ( $charges->season != $_POST['season'] ) {
						$charges->setSeason($_POST['season']);
						$updates = true;
					}
					if ( $updates ) {
						$this->setMessage( __('Charges updated', 'racketmanager') );
					} else {
						$this->setMessage( __('No updates', 'racketmanager'), true );
					}
				} else {
					$charges = new stdClass();
					$charges->competitionType = $_POST['competitionType'];
					$charges->type = $_POST['type'];
					$charges->season = $_POST['season'];
					$charges->status = $_POST['status'];
					$charges->date = $_POST['date'];
					$charges->feeClub = $_POST['feeClub'];
					$charges->feeTeam = $_POST['feeTeam'];
					$charges = new Charges($charges);
					$this->setMessage( __('Charges added', 'racketmanager') );
				}
			}
			$this->printMessage();
			$edit = false;
			if ( isset($_GET['charges']) || (isset($charges->id) && $charges->id != '') ) {
				if ( isset($_GET['charges']) ) {
					$chargesId = $_GET['charges'];
				} else {
					$chargesId = $charges->id;
				}
				$edit = true;
				$charges = get_Charges( $chargesId );

				$form_title = __( 'Edit Charges', 'racketmanager' );
				$form_action = __( 'Update', 'racketmanager' );
			} else {
				$chargesId = '';
				$form_title = __( 'Add Charges', 'racketmanager' );
				$form_action = __( 'Add', 'racketmanager' );
				$charges = (object)array( 'competitionType' => '', 'type' => '', 'id' => '', 'season' => '', 'date' => '', 'status' => '', 'feeClub' => '', 'feeTeam' => '');
			}

			include_once RACKETMANAGER_PATH . '/admin/finances/charge.php';
		}
	}

	/**
	* display invoice page
	*
	*/
	private function displayInvoicePage() {
		global $racketmanager, $racketmanager_shortcodes;

		if ( !current_user_can( 'edit_teams' ) ) {
			echo '<div class="error"><p style="text-align: center;">'.__("You do not have sufficient permissions to access this page.").'</p></div>';
		} else {
			if ( isset($_POST['saveInvoice']) ) {
				$invoice = get_invoice($_POST['invoice_id']);
				$updates = false;
				if ( $invoice->status != $_POST['status'] ) {
					$updates = $invoice->setStatus($_POST['status']);
				}
				if ( $updates ) {
					$this->setMessage( __('Invoice updated', 'racketmanager') );
				} else {
					$this->setMessage( __('No updates', 'racketmanager'), true );
				}
			}
			$this->printMessage();
			if ( isset($_GET['charge']) && isset($_GET['club']) ) {
				$invoiceId = $this->getInvoice($_GET['charge'], $_GET['club']);
			} elseif ( isset($_GET['invoice']) ) {
				$invoiceId = $_GET['invoice'];
			}
			$tab = isset($_GET['tab']) ? $_GET['tab'] : "invoices";

			$invoiceView = '';
			if ( isset($invoiceId) && $invoiceId ) {
				$billing = $racketmanager->getOptions('billing');
				$invoice = get_invoice($invoiceId);
				if ( $invoice ) {
					$invoiceView = $invoice->generate();
					include_once RACKETMANAGER_PATH . '/admin/finances/invoice.php';
					return;
				}
			}
			echo '<div class="error">'.__("Invoice not found", "racketmanager").'</p></div>';

		}
	}

	/**
	* display season page
	*
	*/
	private function displaySeasonPage() {
		global $racketmanager, $racketmanager_shortcodes;

		if ( !current_user_can( 'edit_leagues' ) ) {
			echo '<div class="error"><p style="text-align: center;">'.__("You do not have sufficient permissions to access this page.", 'racketmanager').'</p></div>';
		} else {
			if ( isset($_POST['saveSeason'])) {
				if ( isset($_POST['seasonId']) ) {
					$seasonId = $_POST['seasonId'];
				}
				if ( isset($_POST['matchDate'])) {
					$matchDate = $_POST['matchDate'];
				} else {
					$matchDate = array();
				}
				if ( isset($_POST['homeAway'])) {
					$homeAway = $_POST['homeAway'];
				} else {
					$homeAway = true;
				}
				if ( isset($_POST['status'])) {
					$status = $_POST['status'];
				} else {
					$status = "draft";
				}
				$this->editSeason( intval($_POST['seasonId']), intval($_POST['num_match_days']), intval($_POST['competitionId']), $matchDate, $homeAway, $status );
				$this->printMessage();
			} else {
				$seasonId = htmlspecialchars($_GET['season']);
			}
			$competition = get_competition($_GET['competition_id']);
			$season_data = $competition->seasons[$seasonId];

			include_once RACKETMANAGER_PATH . '/admin/includes/season.php';
		}
	}

	/**
	* display schedule page
	*
	*/
	private function displaySchedulePage() {
		global $racketmanager, $racketmanager_shortcodes;

		if ( !current_user_can( 'edit_leagues' ) ) {
			echo '<div class="error"><p style="text-align: center;">'.__("You do not have sufficient permissions to access this page.", 'racketmanager').'</p></div>';
		} else {
			if ( isset($_POST['doScheduleCompetitions']) ) {
				$schedule = $this->scheduleLeagueMatches( $_POST['competition'] );
				$this->printMessage();
			} elseif ( isset($_POST['doDeleteCompetitionMatches']) ) {
				foreach ($_POST['competition'] as $competitionId) {
					$this->deleteCompetitionMatches($competitionId);
				}
				$this->printMessage();
			}
			$competitions = $racketmanager->getCompetitions( array('type' => 'league') );
			include_once RACKETMANAGER_PATH . '/admin/show-schedule.php';
		}
	}

	/**
	* display link to settings page in plugin table
	*
	* @param array $links array of action links
	* @return array
	*/
	public function pluginActions( $links ) {
		if (is_array($links)) {
			$links['settings'] = '<a href="admin.php?page=racketmanager-settings">' . __('Settings', 'racketmanager') . '</a>';
			$links['documentation'] = '<a href="admin.php?page=racketmanager-doc">' . __('Documentation', 'racketmanager') . '</a>';
		}
		return $links;
	}

	/**
	* load Javascript
	*
	*/
	public function loadScripts() {
		wp_register_script( 'racketmanager-bootstrap', plugins_url('/admin/js/bootstrap/bootstrap.js', dirname(__FILE__)), array(), RACKETMANAGER_VERSION );
		wp_enqueue_script('racketmanager-bootstrap');
		wp_register_script( 'racketmanager-functions', plugins_url('/admin/js/functions.js', dirname(__FILE__)), array( 'thickbox', 'jquery', 'jquery-ui-core', 'jquery-ui-datepicker', 'jquery-ui-sortable', 'jquery-ui-tooltip', 'jquery-effects-core', 'jquery-effects-slide', 'jquery-effects-explode', 'jquery-ui-autocomplete', 'iris' ), RACKETMANAGER_VERSION );
		wp_enqueue_script('racketmanager-functions');

		wp_register_script( 'racketmanager-ajax', plugins_url('/admin/js/ajax.js', dirname(__FILE__)), array('sack'), RACKETMANAGER_VERSION );
		wp_enqueue_script('racketmanager-ajax');

		?>
		<script type='text/javascript'>
		//<!--<![CDATA[-->
		RacketManagerAjaxL10n = {
			requestUrl: "<?php bloginfo( 'wpurl' ); ?>/wp-admin/admin-ajax.php",
			manualPointRuleDescription: "<?php _e( 'Order: win, win overtime, tie, loss, loss overtime', 'racketmanager' ) ?>",
			pluginUrl: "<?php plugins_url('', dirname(__FILE__)); ?>/wp-content/plugins/leaguemanager", Edit: "<?php _e("Edit"); ?>",
			Post: "<?php _e("Post"); ?>", Save: "<?php _e("Save"); ?>",
			Cancel: "<?php _e("Cancel"); ?>",
			pleaseWait: "<?php _e("Please wait..."); ?>",
			Delete: "<?php _e('Delete', 'racketmanager') ?>",
			Yellow: "<?php _e( 'Yellow', 'racketmanager') ?>",
			Red: "<?php _e( 'Red', 'racketmanager') ?>",
			Yellow_Red: "<?php _e('Yellow/Red', 'racketmanager') ?>",
			Insert: "<?php _e( 'Insert', 'racketmanager' ) ?>",
			InsertPlayer: "<?php _e( 'Insert Player', 'racketmanager' ) ?>"
		}
		//<!--]]>-->
		</script>
		<?php
	}

	/**
	* load CSS styles
	*
	*/
	public function loadStyles() {
		wp_enqueue_style('racketmanager-bootstrap', plugins_url("/css/bootstrap.min.css", dirname(__FILE__)), false, RACKETMANAGER_VERSION, 'screen');
		wp_enqueue_style('racketmanager', plugins_url("/css/admin.css", dirname(__FILE__)), false, RACKETMANAGER_VERSION, 'screen');
		wp_enqueue_style('racketmanager-modal', plugins_url("/css/modal.css", dirname(__FILE__)), false, RACKETMANAGER_VERSION, 'screen');

		wp_register_style('jquery-ui', plugins_url("/css/jquery/jquery-ui.min.css", dirname(__FILE__)), false, '1.11.4', 'all');
		wp_register_style('jquery-ui-structure', plugins_url("/css/jquery/jquery-ui.structure.min.css", dirname(__FILE__)), array('jquery-ui'), '1.11.4', 'all');
		wp_register_style('jquery-ui-theme', plugins_url("/css/jquery/jquery-ui.theme.min.css", dirname(__FILE__)), array('jquery-ui', 'jquery-ui-structure'), '1.11.4', 'all');

		wp_enqueue_style('jquery-ui-structure');
		wp_enqueue_style('jquery-ui-theme');

		wp_enqueue_style('thickbox');
	}

	/**
	* update match results
	*
	* @param array $matches
	* @param array $home_points
	* @param array $away_points
	* @param array $custom
	* @param string $season
	* @param boolean $final
	* @param boolean $message
	* @return int $num_matches
	*/
	private function updateResults( $matches, $home_points, $away_points, $custom, $season, $final = false, $message = true ) {
		if ( !current_user_can('update_results') ) {
			$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
			return false;
		}

		$league = get_league();
		$league->setFinals($final);
		$num_matches = $league->_updateResults( $matches, $home_points, $away_points, $custom, $season, $final );

		if ( $message ) {
			$this->setMessage( sprintf(__('Updated Results of %d matches','racketmanager'), $num_matches) );
		}
		return true;
	}

	/************
	*
	*   COMPETITION SECTION
	*
	*
	*/

	private function createCompetitionPages( $competitionId, $competitionName ) {

		$pageContent = "[competition id=".$competitionId."]";
		$title = $competitionName.' '.__('Tables', 'racketmanager');

		$this->createCompetitionPage($pageContent, $title);

		$pageContent = "[leaguearchive competition id=".$competitionId."]";
		$title = $competitionName;

		$this->createCompetitionPage($pageContent, $title);

	}

	private function createCompetitionPage( $content, $title ) {

		$page_definition = array(
			$title => array(
				'title' => $title,
				'page_template' => 'No Title',
				'content' => $content
			)
		);

		Racketmanager_Util::addRacketManagerPage($page_definition);

	}

	/**
	* delete all Competition Pages
	*
	* @pageName string $competitionName
	* @return none
	*/
	private function deleteCompetitionPages( $competitionName ) {

		$title = $competitionName.' '.__('Tables', 'racketmanager');
		$pageName = sanitize_title_with_dashes($title);
		$this->deleteRacketmanagerPage($pageName);

		$title = $competitionName;
		$pageName = sanitize_title_with_dashes($title);
		$this->deleteRacketmanagerPage($pageName);

	}

	/**
	* delete matches for competition
	*
	* @param int $competition
	* @return boolean $success
	*/
	private function deleteCompetitionMatches($competition) {
		global $wpdb, $racketmanager;

		$success = true;
		$competition = get_competition($competition);
		$season = $competition->getSeason();
		$matchCount = $racketmanager->getMatches(array('count' => true, 'competitionId' => $competition->id, 'season' => $season, 'time' => 'latest'));

		if ( $matchCount != 0 ) {
			$this->setMessage( __('Competition has completed matches', 'racketmanager'), true );
			$success = false;
		} else {
			$leagues = $competition->getLeagues();
			foreach ($leagues as $league) {
				$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->racketmanager_rubbers} WHERE `match_id` IN ( SELECT `id` from {$wpdb->racketmanager_matches} WHERE `league_id` = %d AND `season` = %d)", $league->id, $season) );
				$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->racketmanager_matches} WHERE `league_id` = %d AND `season`= %d", $league->id, $season) );
			}
			$this->setMessage( __('Matches deleted', 'racketmanager') );
		}
		return $success;
	}

	/**
	* update Table
	*
	* @param int $tableId
	* @param int $leagueId
	* @param int $rank
	* @param string $status
	* @param string $profile
	* @return int
	*/
	private function updateTable( $tableId, $leagueId, $rank, $status, $profile ) {
		global $wpdb;

		$sql = "UPDATE {$wpdb->racketmanager_table} SET `league_id` = '%d', `rank` = '%d', `status` = '%s', `profile` = '%d' WHERE `id` = '%d'";
		$wpdb->query( $wpdb->prepare ( $sql, $leagueId, $rank, $status, $profile, $tableId ) );
		$this->setMessage( __('Updated', 'racketmanager') );
	}

	/**
	* add new Season
	*
	* @param string $name
	* @return boolean
	*/
	private function addSeason( $name ) {
		global $wpdb;

		if ( !current_user_can('edit_seasons') ) {
			$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
			return false;
		}
		$wpdb->query( $wpdb->prepare ( "INSERT INTO {$wpdb->racketmanager_seasons} (name) VALUES ('%s')", $name) );
		$this->setMessage( __('Season added', 'racketmanager') );
		return true;
	}

	/**
	* delete season
	*
	* @param int $season_id
	* @return boolean
	*/
	private function delSeason( $season_id ) {
		global $wpdb;

		if ( !current_user_can('del_seasons') ) {
			$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
			return false;
		}

		$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->racketmanager_seasons} WHERE `id` = '%d'", $season_id) );
		$this->setMessage( __('Season deleted','racketmanager') );

		return true;
	}
	/**
	* add new season to competition
	*
	* @param string $season
	* @param int $num_match_days
	* @param int $competition_id
	* @return boolean
	*/
	private function addSeasonToCompetition( $season, $num_match_days, $competition_id ) {
		global $racketmanager, $wpdb, $competition;

		if ( !current_user_can('edit_seasons') ) {
			$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
			return false;
		}

		$competition = get_competition($competition_id);
		if ( !$num_match_days  && ( $competition->competitiontype == 'cup' || $competition->competitiontype == 'tournament' ) ) {
			$options = $racketmanager->getOptions();
			$rm_options = $options['championship'];
			$num_match_days = isset($rm_options['numRounds']) ? $rm_options['numRounds'] : 0;
		}

		if ( !$num_match_days ) {
			$this->setMessage( 'Number of match days not specified','racketmanager', 'error' );
			return false;
		}

		if ( $competition->seasons == '' ) {
			$competition->seasons = array();
		}
		$competition->seasons[$season] = array( 'name' => $season, 'num_match_days' => $num_match_days, 'status' => 'draft' );
		ksort($competition->seasons);
		$this->saveCompetitionSeasons($competition->seasons, $competition->id);

		$this->setMessage( sprintf(__('Season <strong>%s</strong> added','racketmanager'), $season ) );

		return true;
	}

	/**
	* edit season in competition
	*
	* @param int $season_id
	* @param string $season
	* @param int $competition_id
	* @param array $matchDates
	* @param boolean $homeAway
	* @param string $status
	* @return boolean
	*/
	private function editSeason( $season, $num_match_days, $competition_id, $matchDates=false, $homeAway=true, $status="draft" ) {
		global $racketmanager, $wpdb, $competition;

		$error = false;

		if ( !current_user_can('edit_seasons') ) {
			$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
			return false;
		}

		if ( $matchDates ) {
			foreach ($matchDates as $matchDate) {
				if (empty($matchDate)) {
					$this->setMessage( __("Match date not set", 'racketmanager'), true );
					$error = true;
				}
			}
		}

		if ( !$error ) {
			$competition = get_competition($competition_id);
			$competition->seasons[$season] = array( 'name' => $season, 'num_match_days' => $num_match_days, 'matchDates' => $matchDates, 'homeAway' => $homeAway, 'status' => $status );
			ksort($competition->seasons);
			$this->saveCompetitionSeasons($competition->seasons, $competition->id);
			if ( $status == 'live' && $competition->competitiontype == 'league' ) {
				$emailAddr = $racketmanager->getConfirmationEmail($competition->competitiontype);
				$organisationName = $racketmanager->site_name;
				$messageArgs = array();
				$messageArgs['competition'] = $competition->name;
				$messageArgs['emailfrom'] = $emailAddr;
				$emailMessage = racketmanager_constitution_notification($competition->id, $messageArgs );
				$headers = array();
				$headers[] = 'From: '.ucfirst($competition->competitiontype).' Secretary <'.$emailAddr.'>';
				foreach ($racketmanager->getClubs() as $club) {
					if ( !empty($club->matchSecretaryEmail) ) {
						$headers[] = 'bcc: '.$club->matchSecretaryName.' <'.$club->matchSecretaryEmail.'>';
					}
				}
				$subject = $organisationName." - ".$competition->name." ".$season." - Constitution";
				wp_mail($emailAddr, $subject, $emailMessage, $headers);
				$teams = $competition->getTeams( array('status' => 3) );
				foreach ($teams as $team) {
					$league = get_league($team->leagueId);
					$league->deleteTeam($team->teamId, $season);
				}
				$this->setMessage( sprintf(__('Season <strong>%s</strong> saved and constitution emailed','racketmanager'), $season ) );
			} else {
				$this->setMessage( sprintf(__('Season <strong>%s</strong> saved','racketmanager'), $season ) );
			}
		}
	}

	/**
	* delete season of competition
	*
	* @param array $seasons
	* @param int $competition_id
	* @return boolean
	*/
	private function delCompetitionSeason( $seasons, $competition_id ) {
		global $racketmanager, $wpdb, $competition;

		if ( !current_user_can('del_seasons') ) {
			$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
			return false;
		}

		$competition = get_competition($competition_id);

		foreach ( $seasons as $season ) {

			foreach ( $competition->getLeagues() as $league ) {

				$league_id = $league->id;
				// remove tables
				$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->racketmanager_table} WHERE `league_id` = '%d' AND `season` = '%s'", $league_id, $season) );
				// remove matches and rubbers
				$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->racketmanager_rubbers} WHERE `match_id` IN ( SELECT `id` from {$wpdb->racketmanager_matches} WHERE `league_id` = '%d' AND `season` = '%s')", $league_id, $season) );
				$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->racketmanager_matches} WHERE `league_id` = '%d' AND `season` = '%s'", $league_id, $season) );

			}
			unset($competition->seasons[$season]);
		}
		$this->saveCompetitionSeasons($competition->seasons, $competition->id);

		return true;
	}

	/**
	* save seasons array to database
	*
	* @param array $seasons
	* @param int $$competition_id
	* @param boolean
	*/
	private function saveCompetitionSeasons($seasons, $competition_id) {
		global $wpdb;

		if ( !current_user_can('edit_seasons') ) {
			$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
			return false;
		}

		$wpdb->query( $wpdb->prepare("UPDATE {$wpdb->racketmanager_competitions} SET `seasons` = '%s' WHERE `id` = '%d'", maybe_serialize($seasons), $competition_id) );

		wp_cache_delete($competition_id, 'competitions');

		return true;
	}

	/**
	* display global settings page (e.g. color scheme options)
	*
	*/
	public function displayOptionsPage() {
		if ( !current_user_can( 'manage_racketmanager' ) ) {
			echo '<p style="text-align: center;">'.__("You do not have sufficient permissions to access this page.").'</p>';
		} else {
			$options = $this->options;
			$comptab = 1;

			$tab = 0;
			if ( isset($_POST['updateRacketManager']) ) {
				check_admin_referer('racketmanager_manage-global-league-options');
				$options['rosters']['btm'] = htmlspecialchars($_POST['btmRequired']);
				$options['rosters']['rosterEntry'] = htmlspecialchars($_POST['clubPlayerEntry']);
				$options['rosters']['rosterConfirmation'] = htmlspecialchars($_POST['confirmation']);
				$options['rosters']['rosterConfirmationEmail'] = htmlspecialchars($_POST['confirmationEmail']);
				$options['checks']['rosterLeadTime'] = htmlspecialchars($_POST['playerLeadTime']);
				$options['checks']['playedRounds'] = htmlspecialchars($_POST['playedRounds']);
				$options['checks']['playerLocked'] = htmlspecialchars($_POST['playerLocked']);
				$competitionTypes = Racketmanager_Util::getCompetitionTypes();
				foreach ( $competitionTypes as $competitionType ) {
					$options[$competitionType]['matchCapability'] = htmlspecialchars($_POST[$competitionType]['matchCapability']);
					$options[$competitionType]['resultConfirmation'] = htmlspecialchars($_POST[$competitionType]['resultConfirmation']);
					$options[$competitionType]['resultEntry'] = htmlspecialchars($_POST[$competitionType]['resultEntry']);
					$options[$competitionType]['resultConfirmationEmail'] = htmlspecialchars($_POST[$competitionType]['resultConfirmationEmail']);
					$options[$competitionType]['resultNotification'] = htmlspecialchars($_POST[$competitionType]['resultNotification']);
					$options[$competitionType]['resultPending'] = htmlspecialchars($_POST[$competitionType]['resultPending']);
					$options[$competitionType]['confirmationPending'] = htmlspecialchars($_POST[$competitionType]['confirmationPending']);
					$options[$competitionType]['confirmationTimeout'] = htmlspecialchars($_POST[$competitionType]['confirmationTimeout']);
					$this->scheduleResultChase($competitionType, $options[$competitionType]);
				}
				$options['colors']['headers'] = htmlspecialchars($_POST['color_headers']);
				$options['colors']['rows'] = array( 'alternate' => htmlspecialchars($_POST['color_rows_alt']), 'main' => htmlspecialchars($_POST['color_rows']), 'ascend' => htmlspecialchars($_POST['color_rows_ascend']), 'descend' => htmlspecialchars($_POST['color_rows_descend']), 'relegation' => htmlspecialchars($_POST['color_rows_relegation']) );
				$options['colors']['boxheader'] = array(htmlspecialchars($_POST['color_boxheader1']), htmlspecialchars($_POST['color_boxheader2']));
				$options['championship']['numRounds'] = htmlspecialchars($_POST['numRounds']);
				$options['billing']['billingEmail'] = htmlspecialchars($_POST['billingEmail']);
				$options['billing']['billingAddress'] = htmlspecialchars($_POST['billingAddress']);
				$options['billing']['billingTelephone'] = htmlspecialchars($_POST['billingTelephone']);
				$options['billing']['billingCurrency'] = htmlspecialchars($_POST['billingCurrency']);
				$options['billing']['bankName'] = htmlspecialchars($_POST['bankName']);
				$options['billing']['sortCode'] = htmlspecialchars($_POST['sortCode']);
				$options['billing']['accountNumber'] = htmlspecialchars($_POST['accountNumber']);
				$options['billing']['invoiceNumber'] = htmlspecialchars($_POST['invoiceNumber']);
				$options['billing']['paymentTerms'] = htmlspecialchars($_POST['paymentTerms']);
				$options['keys']['googleMapsKey'] = htmlspecialchars($_POST['googleMapsKey']);
				$options['keys']['recaptchaSiteKey'] = htmlspecialchars($_POST['recaptchaSiteKey']);
				$options['keys']['recaptchaSecretKey'] = htmlspecialchars($_POST['recaptchaSecretKey']);
				$options['player']['walkover']['female'] = htmlspecialchars($_POST['walkoverFemale']);
				$options['player']['noplayer']['female'] = htmlspecialchars($_POST['noplayerFemale']);
				$options['player']['share']['female'] = htmlspecialchars($_POST['shareFemale']);
				$options['player']['unregistered']['female'] = htmlspecialchars($_POST['unregisteredFemale']);
				$options['player']['walkover']['male'] = htmlspecialchars($_POST['walkoverMale']);
				$options['player']['noplayer']['male'] = htmlspecialchars($_POST['noplayerMale']);
				$options['player']['share']['male'] = htmlspecialchars($_POST['shareMale']);
				$options['player']['unregistered']['male'] = htmlspecialchars($_POST['unregisteredMale']);
				$options['player']['walkover']['rubber'] = htmlspecialchars($_POST['walkoverPointsRubber']);
				$options['player']['walkover']['match'] = htmlspecialchars($_POST['walkoverPointsMatch']);
				$options['player']['share']['rubber'] = htmlspecialchars($_POST['sharePoints']);

				update_option( 'leaguemanager', $options );
				$this->setMessage(__( 'Settings saved', 'racketmanager' ));
				$this->printMessage();

				// Set active tab
				$tab = $_POST['active-tab'];
			}

			require_once (RACKETMANAGER_PATH . '/admin/settings-global.php');
		}
	}

	/**
	* add meta box to post screen
	*
	* @param object $post
	*/
	public function addMetaBox( $post ) {
		global $wpdb, $post_ID, $racketmanager;

		if ( $leagues = $wpdb->get_results( "SELECT `title`, `id` FROM {$wpdb->racketmanager} ORDER BY id ASC" ) ) {
			$league_id = $match_id = $season = 0;
			$curr_league = $match = false;
			if ( $post->ID != 0 ) {
				$match = $wpdb->get_row( $wpdb->prepare("SELECT `id`, `league_id`, `season` FROM {$wpdb->racketmanager_matches} WHERE `post_id` = '%d'", $post->ID) );

				if ( $match ) {
					$match_id = $match->id;
					$league_id = $match->league_id;
					$season = $match->season;
					$curr_league = get_league($league_id);
				}
			}

			echo "<input type='hidden' name='curr_match_id' value='".$match_id."' />";
			echo "<select name='league_id' class='alignleft' id='league_id' onChange='Racketmanager.getSeasonDropdown(this.value, ".$season.")'>";
			echo "<option value='0'>".__('Choose League','racketmanager')."</option>";
			foreach ( $leagues as $league ) {
				echo "<option value='".$league->id."'".selected($league_id, $league->id, false).">".$league->title."</option>";
			}
			echo "</select>";

			echo "<div id='seasons'>";
			if ( $match )
			echo $curr_league->getSeasonDropdown($curr_league->getSeason());
			echo '</div>';
			echo "<div id='matches'>";
			if ( $match )
			echo $curr_league->getMatchDropdown($match->id);
			echo '</div>';

			echo '<br style="clear: both;" />';
		}
	}

	/**
	* update post id for match report
	*
	*/
	public function editMatchReport() {
		global $wpdb;

		if (isset($_POST['post_ID'])) {
			$post_ID = (int) $_POST['post_ID'];
			$match_ID = isset($_POST['match_id']) ? (int) $_POST['match_id'] : false;
			$curr_match_ID = isset($_POST['curr_match_id']) ? (int) $_POST['curr_match_id'] : false;

			if ( $match_ID && $curr_match_ID != $match_ID ) {
				$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->racketmanager_matches} SET `post_id` = '%d' WHERE `id` = '%d'", $post_ID, $match_ID ) );
				if ( $curr_match_ID != 0 )
				$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->racketmanager_matches} SET `post_id` = 0 WHERE `id` = '%d'", $curr_match_ID ) );
			}
		}
	}

	/************
	*
	*   RUBBER SECTION
	*
	*
	*/

	/**
	* add Rubber
	*
	* @param string $date
	* @param int $match_id
	* @param int $rubber_no
	* @param array $custom
	* @return int | false
	*/
	private function addRubber( $date, $match_id, $rubberno, $type, $custom=array() ) {
		global $wpdb;

		if ( !current_user_can('edit_matches') ) {
			$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
			return false;
		}

		$sql = "INSERT INTO {$wpdb->racketmanager_rubbers} (`date`, `match_id`, `rubber_number`, `type`, `custom`) VALUES ('%s', '%d', '%d', '%s', '%s')";
		$wpdb->query ( $wpdb->prepare ( $sql, $date, $match_id, $rubberno, $type, maybe_serialize($custom) ) );

		return $wpdb->insert_id;
	}

	/************
	*
	*   CLUB PLAYERS SECTION
	*
	*
	*/

	/**
	* approve Club Player Request
	*
	* @param int $playerRequestId
	* @return void
	*/
	public function _approvePlayerRequest( $club_id, $playerRequestId ) {

		if ( !current_user_can('edit_teams') ) {
			$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
			return false;
		}

		$club = get_club($club_id);
		$club->approvePlayerRequest( $playerRequestId );
	}

	/**
	* delete Club Player Request
	*
	* @param int $playerRequestId
	* @return void
	*/
	private function deletePlayerRequest( $playerRequestId ) {
		global $wpdb;

		if ( !current_user_can('edit_teams') ) {
			$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
			return false;
		}

		$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->racketmanager_club_player_requests} WHERE `id` = %d", $playerRequestId) );
		$this->setMessage( __('Club Player request deleted', 'racketmanager') );

		return true;
	}

	/**
	* import data from CSV file
	*
	* @param int $league_id
	* @param array $file CSV file
	* @param string $delimiter
	* @param array $mode 'teams' | 'matches' | 'fixtures' | 'players' | 'clubplayers'
	* @param int $affiliatedClub - optional
	* @return void | false
	*/
	private function import( $league_id, $file, $delimiter, $mode, $affiliatedClub = false ) {
		global $racketmanager;

		if ( !current_user_can('import_leagues') ) {
			$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
			return false;
		}

		$league_id = intval($league_id);
		$affiliatedClub = isset($affiliatedClub) ? intval($affiliatedClub) : 0;
		if ( $file['size'] > 0 ) {
			/*
			* Upload CSV file to image directory, temporarily
			*/
			$new_file = Racketmanager_Util::getFilePath($file['name']);
			if ( move_uploaded_file($file['tmp_name'], $new_file) ) {
				if ( 'table' == $mode ) {
					$this->importTable($new_file, $delimiter, $league_id);
				} elseif ( 'fixtures' == $mode ) {
					$this->importFixtures($new_file, $delimiter, $league_id);
				} elseif ( 'clubplayers' == $mode ) {
					$this->importClubPlayers($new_file, $delimiter, $affiliatedClub);
				} elseif ( 'players' == $mode ) {
					$this->importPlayers($new_file, $delimiter);
				}
			} else {
				$this->setMessage(sprintf( __('The uploaded file could not be moved to %s.' ), ABSPATH.'wp-content/uploads') );
			}
			@unlink($new_file); // remove file from server after import is done
		} else {
			$this->setMessage( __('The uploaded file seems to be empty', 'racketmanager'), true );
		}
	}

	/**
	* import table from CSV file
	*
	* @param string $file
	* @param string $delimiter
	* @return void|false
	*/
	private function importTable( $file, $delimiter, $league_id ) {
		global $racketmanager;
		if ( !current_user_can('import_leagues') ) {
			$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
			return false;
		}
		$handle = @fopen($file, "r");
		if ($handle) {
			$league = get_league( $league_id );
			if ( "TAB" == $delimiter ) {
				$delimiter = "\t"; // correct tabular delimiter
			}
			$teams = $points_plus = $points_minus = $pld = $won = $draw = $lost = $custom = $add_points = array();
			$i = $x = 0;
			while (!feof($handle)) {
				$buffer = fgets($handle, 4096);
				$line = explode($delimiter, $buffer);
				// ignore header and empty lines
				if ( $i > 0 && count($line) > 1 ) {
					$season = $line[0];
					$team	= $line[1];
					$team_id = $this->getTeamID($team);
					if ( $team_id != 0 ) {
						$table_id = $league->addTeam($team_id, $season);
						if ( $table_id ) {
							$teams[$team_id] = $team_id;
							$pld[$team_id] = isset($line[2]) ? $line[2] : 0;
							$won[$team_id] = isset($line[3]) ? $line[3] : 0;
							$draw[$team_id] = isset($line[4]) ? $line[4] : 0;
							$lost[$team_id] = isset($line[5]) ? $line[5] : 0;
							if ( isset($line[6]) ) {
								if (strpos($line[6], ':') !== false) {
									$points2 = explode(":", $line[6]);
								} else {
									$points2 = array($line[6], 0);
								}
							} else {
								$points2 = array(0,0);
							}
							if ( isset($line[7]) ) {
								if (strpos($line[7], ':') !== false) {
									$points = explode(":", $line[7]);
								} else {
									$points = array($line[7], 0);
								}
							} else {
								$points = array(0,0);
							}
							$points_plus[$team_id] = $points[0];
							$points_minus[$team_id] = $points[1];
							$custom[$team_id]['points2'] = array( 'plus' => $points2[0], 'minus' => $points2[1] );
							$add_points[$team_id] = 0;
							$x++;
						}
					}
				}
				$i++;
			}
			fclose($handle);
			$this->setMessage(sprintf(__( '%d Table Entries imported', 'racketmanager' ), $x));
		}
	}

	/**
	* import fixtures from file
	*
	* @param string $file
	* @param string $delimiter
	*/
	private function importFixtures( $file, $delimiter, $league_id ) {
		global $racketmanager;

		if ( !current_user_can('import_leagues') ) {
			$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
			return false;
		}

		$handle = @fopen($file, "r");
		if ($handle) {
			if ( "TAB" == $delimiter ) $delimiter = "\t"; // correct tabular delimiter

			$league = get_league( $league_id );
			$rubbers = $league->num_rubbers;
			if ( is_null($rubbers) ) { $rubbers = 1; }
			$matches = $home_points = $away_points = $home_teams = $away_teams = $custom = array();

			$i = $x = 0;
			while (!feof($handle)) {
				$buffer = fgets($handle, 4096);
				$line = explode($delimiter, $buffer);
				// ignore header and empty lines
				if ( $i > 0 && count($line) > 1 ) {
					$match = new stdClass();
					$date = ( !empty($line[6]) ) ? $line[0]." ".$line[6] : $line[0]. " 00:00";
					$match->season = isset($line[1]) ? $line[1] : '';
					$match->match_day = isset($line[2]) ? $line[2] : '';
					$match->date = trim($date);
					$match->home_team = $this->getTeamID($line[3]);
					$match->away_team = $this->getTeamID($line[4]);
					if ( $match->home_team != 0 && $match->away_team != 0 ) {

						$match->location = isset($line[5]) ? $line[5] : '';
						$match->group = isset($line[7]) ? $line[7] : '';
						$match = new RM_Match($match);
						$match_id = $match->id;
						$matches[$match_id] = $match_id;
						$home_teams[$match_id] = $match->home_team;
						$away_teams[$match_id] = $match->away_team;
						$home_points[$match_id] = $away_points[$match_id] = '';

						$custom = apply_filters( 'racketmanager_import_fixtures_'.$league->sport, $custom, $match_id );

					}
					$x++;
				}

				$i++;
			}

			fclose($handle);

			$racketmanager->setMessage(sprintf(__( '%d Fixtures imported', 'racketmanager' ), $x));
		}
	}

	/**
	* import players from file
	*
	* @param string $file
	* @param string $delimiter
	*/
	private function importPlayers( $file, $delimiter ) {
		global $racketmanager;

		if ( !current_user_can('import_leagues') ) {
			$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
			return false;
		}

		$handle = @fopen($file, "r");
		if ($handle) {
			if ( "TAB" == $delimiter ) $delimiter = "\t"; // correct tabular delimiter

			$errorMessages = array();

			$i = $x = 0;
			while (!feof($handle)) {
				$buffer = fgets($handle, 4096);
				$line = explode($delimiter, $buffer);

				// ignore header and empty lines
				if ( $i > 0 && count($line) > 1 ) {
					$_POST['firstname']	= isset($line[0]) ? $line[0] : '';
					$_POST['surname']	= isset($line[1]) ? $line[1] : '';
					$_POST['gender']	= isset($line[2]) ? $line[2] : '';
					$_POST['btm']		= isset($line[3]) ? $line[3] : '';
					$_POST['email']		= isset($line[4]) ? $line[4] : '';
					$playerValid = $this->validatePlayer();
					if ($playerValid[0]) {
						$newPlayer = $playerValid[1];
						$player = get_player($newPlayer->user_login, 'name');
						if ( !$player ) {
							$player = new Player($newPlayer);
							$x++;
						}
					} else {
						$errorMsgs = $playerValid[2];
						$message = sprintf(__('Error with player %d details', 'racketmanager'), $i);
						foreach( $errorMsgs as $errorMsg ) {
							$message .= '<br>'.$errorMsg;
						}
						$errorMessages[] = $message;
					}
				}

				$i++;
			}

			fclose($handle);
			$message = sprintf(__( '%d Players imported', 'racketmanager' ), $x);
			foreach ($errorMessages as $errorMessage) {
				$message .= '<br>'.$errorMessage;
			}
			$racketmanager->setMessage($message);
		}
	}

	/**
	* import club players from file
	*
	* @param string $file
	* @param string $delimiter
	*/
	private function importClubPlayers( $file, $delimiter, $affiliatedClub ) {
		global $racketmanager;

		if ( !current_user_can('import_leagues') ) {
			$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
			return false;
		}

		$handle = @fopen($file, "r");
		if ($handle) {
			if ( "TAB" == $delimiter ) $delimiter = "\t"; // correct tabular delimiter

			$club = get_club( $affiliatedClub );
			$i = $x = 0;
			while (!feof($handle)) {
				$buffer = fgets($handle, 4096);
				$line = explode($delimiter, $buffer);

				// ignore header and empty lines
				if ( $i > 0 && count($line) > 1 ) {
					$_POST['firstname']	= isset($line[0]) ? $line[0] : '';
					$_POST['surname']	= isset($line[1]) ? $line[1] : '';
					$_POST['gender']	= isset($line[2]) ? $line[2] : '';
					$_POST['btm']		= isset($line[3]) ? $line[3] : '';
					$_POST['email']		= isset($line[4]) ? $line[4] : '';
					$playerValid = $this->validatePlayer();
					if ($playerValid[0]) {
						$newPlayer = $playerValid[1];
						$club->registerPlayer($newPlayer);
						$x++;
					} else {
						$errorMsgs = $playerValid[2];
						$message = sprintf(__('Error with player %d details', 'racketmanager'), $i);
						foreach( $errorMsgs as $errorMsg ) {
							$message .= '<br>'.$errorMsg;
						}
						$errorMessages[] = $message;
					}

				}

				$i++;
			}

			fclose($handle);
			$message = sprintf(__( '%d Club Players imported', 'racketmanager' ), $x);
			foreach ($errorMessages as $errorMessage) {
				$message .= '<br>'.$errorMessage;
			}
			$racketmanager->setMessage($message);
		}
	}

	/**
	* Checks if a particular user has a role.
	* Returns true if a match was found.
	*
	* @param string $role Role name.
	* @param int $user_id (Optional) The ID of a user. Defaults to the current user.
	* @return bool
	*
	* put together by AppThemes (http://docs.appthemes.com/tutorials/wordpress-check-user-role-function/)
	*/
	public function checkUserRole( $role, $user_id = null ) {

		if ( is_numeric( $user_id ) )
		$user = get_userdata( $user_id );
		else
		$user = wp_get_current_user();

		if ( empty( $user ) )
		return false;

		return in_array( $role, (array) $user->roles );
	}

	/**
	* recursively apply htmlspecialchars to an array
	*
	* @param array $arr
	*/
	public function htmlspecialchars_array($arr = array()) {
		$rs =  array();
		foreach($arr as $key => $val) {
			if(is_array($val)) {
				$rs[$key] = $this->htmlspecialchars_array($val);
			} else {
				$rs[$key] = htmlspecialchars($val, ENT_QUOTES);
			}
		}
		return $rs;
	}


	/**
	* show database columns of RacketManager
	*/
	private function showDatabaseColumns() {
		global  $wpdb;

		$tables = array($wpdb->racketmanager, $wpdb->racketmanager_teams, $wpdb->racketmanager_matches, $wpdb->racketmanager_club_players, $wpdb->racketmanager_rubbers);

		foreach( $tables as $table ) {
			$results = $wpdb->get_results("SHOW COLUMNS FROM {$table}");
			$columns = array();
			foreach ( $results as $result ) {
				$columns[] = "<li>".$result->Field." ".$result->Type.", NULL: ".$result->Null.", Default: ".$result->Default.", Extra: ".$result->Extra."</li>";
			}
			echo "<p>Table ".$table."<ul>";
			echo implode("", $columns);
			echo "</ul></p>";
		}
	}

	//  Move to racketmanager.php
	//  Move to league.php
	/**
	* display league dropdown
	*
	* @param mixed $competition
	* @return void|string
	*/
	public function getLeagueDropdown( $competition_id = false ) {
		global $racketmanager;
		if (!$competition_id) {
			$competition_id = intval($_POST['competition_id']);
		}
		$competition = get_competition($competition_id);
		$leagues = $competition->getLeagues(); ?>

		<select size='1' name='league_id' id='league_id' class="form-select" >
			<option value='0'><?php _e('Choose league', 'racketmanager') ?></option>
			<?php foreach ( $leagues as $league ) { ?>
				<option value=<?php echo $league->id ?>><?php echo $league->title ?></option>
			<?php } ?>
		</select>
		<label for="league_id"><?php _e('League','racketmanager') ?></label>

		<?php die();
	}

	/**
	* gets results checker from database
	*
	* @param array $query_args
	* @return array
	*/
	public function getResultsChecker( $args = array() ) {
		global $wpdb, $racketmanager;

		$defaults = array( 'season' => false, 'status' => false, 'competition' => false );
		$args = array_merge($defaults, $args);
		extract($args, EXTR_SKIP);
		$sql = "SELECT `id`, `league_id`, `match_id`, `team_id`, `player_id`, `updated_date`, `updated_user`, `description`, `status` FROM {$wpdb->racketmanager_results_checker} WHERE 1 = 1"  ;

		if ( $status ) {
			if ( $status != 'all' ) {
				if ( $status == 'outstanding' ) {
					$sql .= " AND `status` IS NULL";
				} else {
					$sql .= $wpdb->prepare(" AND `status` = %d", $status);
				}
			}
		}
		if ( $season ) {
			if ( $season != 'all' ) {
				$sql .= $wpdb->prepare(" AND `match_id` IN (SELECT `id` FROM {$wpdb->racketmanager_matches} WHERE `season` = '%s')", $season);
			}
		}
		if ( $competition && $competition != 'all' ) {
			$sql .= $wpdb->prepare(" AND `match_id` IN (SELECT m.`id` FROM {$wpdb->racketmanager_matches} m, {$wpdb->racketmanager} l WHERE m.`league_id` = l.`id` AND l.`competition_id` = %d)", $competition);
		}

		$sql .= " ORDER BY `match_id` DESC, `league_id` ASC, `team_id` ASC, `player_id` ASC";

		$resultsCheckers = wp_cache_get( md5($sql), 'resultsCheckers' );
		if ( !$resultsCheckers ) {
			$resultsCheckers = $wpdb->get_results( $sql );
			wp_cache_set( md5($sql), $resultsCheckers, 'resultsCheckers' );
		}

		$class = '';
		foreach ( $resultsCheckers as $i => $resultsChecker ) {
			$class = ( 'alternate' == $class ) ? '' : 'alternate';
			$resultsChecker->class = $class;

			$resultsChecker->match = get_match($resultsChecker->match_id);
			$resultsChecker->team = '';
			if ( $resultsChecker->team_id > 0 ) {
				if ( $resultsChecker->team_id ==  $resultsChecker->match->home_team ) {
					$resultsChecker->team = $resultsChecker->match->teams['home']->title;
				} elseif ( $resultsChecker->team_id ==  $resultsChecker->match->away_team ) {
					$resultsChecker->team = $resultsChecker->match->teams['away']->title;
				}
			}
			$player = get_userdata($resultsChecker->player_id);
			if ( $player ) {
				$resultsChecker->player = $player->display_name;
			} else {
				$resultsChecker->player = '';
			}
			if ( $resultsChecker->updated_user != '' ) {
				$resultsChecker->updated_user_name = get_userdata($resultsChecker->updated_user)->display_name;
			} else {
				$resultsChecker->updated_user_name = '';
			}
			if  ( $resultsChecker->status == 1 ) {
				$resultsChecker->status = 'Approved';
			} elseif ( $resultsChecker->status == 2) {
				$resultsChecker->status = 'Handled';
			} else {
				$resultsChecker->status = '';
			}

			$resultsCheckers[$i] = $resultsChecker;
		}

		return $resultsCheckers;
	}

	/**
	* get single results checker
	*
	* @param int $resultsCheckerId
	* @return object
	*/
	private function getResultsCheckerEntry( $resultsCheckerId ) {
		global $wpdb;
		return $wpdb->get_row("SELECT `league_id`, `match_id`, `team_id`, `player_id`, `updated_date`, `updated_user`, `description`, `status` FROM {$wpdb->racketmanager_results_checker} WHERE `id` = '".intval($resultsCheckerId)."'");
	}

	/**
	* approve Results Checker entry
	*
	* @param int $resultsCheckerId
	* @return void
	*/
	private function approveResultsChecker( $resultsCheckerId ) {
		global $wpdb, $racketmanager;

		$resultsChecker = $this->getResultsCheckerEntry($resultsCheckerId);
		if ( empty($resultsChecker->updated_date) ) {
			$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->racketmanager_results_checker} SET `updated_date` = now(), `updated_user` = %d, `status` = 1 WHERE `id` = %d ", get_current_user_id(), $resultsCheckerId ) );
			$racketmanager->setMessage( __('Results checker approved', 'racketmanager') );
		}

		return true;
	}

	/**
	* handle Results Checker entry
	*
	* @param int $resultsCheckerId
	* @return void
	*/
	private function handleResultsChecker( $resultsCheckerId ) {
		global $wpdb, $racketmanager;

		$resultsChecker = $this->getResultsCheckerEntry($resultsCheckerId);
		if ( empty($resultsChecker->updated_date) ) {
			$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->racketmanager_results_checker} SET `updated_date` = now(), `updated_user` = %d, `status` = 2 WHERE `id` = %d ", get_current_user_id(), $resultsCheckerId ) );
			$racketmanager->setMessage( __('Results checker updated', 'racketmanager') );
		}

		return true;
	}

	/**
	* delete Results Checker entry
	*
	* @param int $resultsCheckerId
	* @return void
	*/
	private function deleteResultsChecker( $resultsCheckerId ) {
		global $wpdb;

		$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->racketmanager_results_checker} WHERE `id` = %d", $resultsCheckerId) );
		$this->setMessage( __('Results checker deleted', 'racketmanager') );

		return true;
	}

	/**
	* contact League Teams
	*
	* @param int $league
	* @param string $season
	* @param string $emailMessage
	* @return void
	*/
	private function contactLeagueTeams($league, $season, $emailMessage) {
		global $wpdb, $racketmanager;

		$league = get_league($league);
		$teams = $league->getLeagueTeams(array('season' => $season));
		$emailMessage = str_replace('\"','"',$emailMessage);
		$headers = array();
		$emailFrom = $this->getConfirmationEmail($league->competitionType);
		$headers[] = 'From: '.ucfirst($league->competitionType).' Secretary <'.$emailFrom.'>';
		$headers[] = 'cc: '.ucfirst($league->competitionType).' Secretary <'.$emailFrom.'>';

		foreach ($teams as $team) {
			$emailSubject = $this->site_name." - ".$league->title." ".$season." - Important Message";
			$teamDtls = $league->getTeamDtls($team->id);
			$emailTo = $teamDtls->contactemail;
			if ( $emailTo ) {
				wp_mail($emailTo, $emailSubject, $emailMessage, $headers);
				$messageSent = true;
			}
		}

		if ( $messageSent ) {
			$this->setMessage( __('Email sent to captains', 'racketmanager') );
		}
		return true;
	}

	/**
	* get latest season
	*
	* @return int
	*/
	public function getLatestSeason() {
		global $wpdb;

		return $wpdb->get_var( "SELECT MAX(name) FROM {$wpdb->racketmanager_seasons}" );
	}

	/**
	* schedule league matches
	*
	* @param array $competitions
	* @return void
	*/
	private function scheduleLeagueMatches($competitions) {
		global $wpdb, $racketmanager;

		$i = 1;
		do {
			$result = $this->validateSchedule($competitions);
			$i ++;
		} while ( !$result || $i > 20 );

		if ( $result ) {
			foreach ($competitions as $competitionId) {
				$competition = get_competition($competitionId);
				foreach ($competition->getLeagues() as $league) {
					$league = get_league($league);
					$league->scheduleMatches();
				}
			}
			$this->setMessage(__('Matches scheduled', 'racketmanager'));
		}
	}

	/**
	* validate schedule by team
	*
	* @param array $competitions
	* @return boolean $success
	*/
	private function validateSchedule($competitions) {
		global $wpdb, $racketmanager;

		$success = true;
		$messages = array();
		$c = 0;
		$numMatchDays = 0;
		$homeAway = '';
		foreach ($competitions as $competitionId) {
			$competition = get_competition($competitionId);
			$season = $competition->getSeason();
			$matchCount = $racketmanager->getMatches(array('count' => true, 'competitionId' => $competition->id, 'season' => $season));
			if ( $matchCount != 0 ) {
				$success = false;
				$messages[] = sprintf(__('%s already has matches scheduled for %d','racketmanager'), $competition->name, $season);
				break;
			} else {
				if ( $c == 0 ) {
					$numMatchDays = $competition->current_season['num_match_days'];
					if ( !isset($competition->current_season['matchDates']) ) {
						$success = false;
						$messages[] = __('Competitions match dates not set','racketmanager');
					}
					$homeAway = isset($competition->current_season['homeAway']) ? $competition->current_season['homeAway'] : 'true' ;
					if ( $homeAway ) {
						$numRounds = $numMatchDays / 2;
					} else {
						$numRounds = $numMatchDays;
					}
					$numTeamsMax = $numRounds + 1;
					$defaultRefs = array();
					for ($i=1; $i <= $numTeamsMax ; $i++) {
						$defaultRefs[] = $i;
					}
				} else {
					if ( $competition->current_season['num_match_days'] != $numMatchDays ) {
						$success = false;
						$messages[] = __('Competitions have different number of match days','racketmanager');
					}
					$homeAwayNew = isset($competition->current_season['homeAway']) ? $competition->current_season['homeAway'] : 'true' ;
					if ( $homeAwayNew != $homeAway ) {
						$success = false;
						$messages[] = __('Competitions have different home / away setting','racketmanager');
					}
				}
			}
			$c ++;
		}

		if ( $success ) {
			$season = $this->getLatestSeason();
			$competitionIds = implode(',',$competitions);
			$sql = "SELECT `t`.`title`FROM {$wpdb->racketmanager_teams} t , {$wpdb->racketmanager_team_competition} tc , {$wpdb->racketmanager_table} t1 , {$wpdb->racketmanager} l WHERE t.`id` = `tc`.`team_id` AND `tc`.`match_day` = '' AND `tc`.`competition_id` in (".$competitionIds.") AND l.`id` = `t1`.`league_id` AND `l`.`competition_id` = tc.`competition_id` AND `t1`.`season` = ".$season." AND `t1`.`team_id` = `tc`.`team_id`";
			$teamsMissingDetails = $wpdb->get_results( $sql );
			if ( $teamsMissingDetails ) {
				$missingTeams = array();
				foreach ($teamsMissingDetails as $team) {
					$missingTeams[] = $team->title;
				}
				$teams = implode(' and ',$missingTeams);
				$success = false;
				$messages[] = sprintf(__('Missing match days for %s','racketmanager'), $teams);
			}
		}
		if ( $success ) {
			/* clear out schedule keys for this run */
			$wpdb->query( "UPDATE {$wpdb->racketmanager_table} SET `group` = '' WHERE `season` = $season AND `league_id` IN (SELECT `id` FROM {$wpdb->racketmanager} WHERE `competition_id` IN ($competitionIds))" );

			/* set refs for those teams in the same division so they play first */
			$sql = "SELECT `t`.`affiliatedclub`, tbl.`league_id` FROM {$wpdb->racketmanager_team_competition} tc, {$wpdb->racketmanager_teams} t, {$wpdb->racketmanager} l, {$wpdb->racketmanager_table} tbl WHERE tc.`team_id` = t.`id` AND tc.`competition_id` = l.`competition_id` AND l.`id` = tbl.`league_id` AND tbl.`team_id` = t.`id` AND tc.`competition_id` in (".$competitionIds.") AND tbl.`season` = ".$season." GROUP BY t.`affiliatedclub`, tbl.`league_id` HAVING COUNT(*) > 1";
			$clubLeagues = $wpdb->get_results( $sql );
			foreach ($clubLeagues as $clubLeague) {
				$sql = "SELECT tbl.`id`, tbl.`team_id`, tbl.`league_id` FROM {$wpdb->racketmanager_team_competition} tc, {$wpdb->racketmanager_teams} t, {$wpdb->racketmanager} l, {$wpdb->racketmanager_table} tbl WHERE tc.`team_id` = t.`id` AND tc.`competition_id` = l.`competition_id` AND l.`id` = tbl.`league_id` AND tbl.`team_id` = t.`id` AND tc.`competition_id` in (".$competitionIds.") AND tbl.`season` = ".$season." AND t.`affiliatedclub` = ".$clubLeague->affiliatedclub." AND tbl.`league_id` = '".$clubLeague->league_id."'  ORDER BY tbl.`team_id`";
				$teams = $wpdb->get_results( $sql );
				$counter = 1;
				$altRefs = array();
				$refs = array();
				$table1 = '';
				$league1 = '';
				$team1 = '';
				foreach ($teams as $team) {
					if ( $counter & 1 ) {
						$team1 = $team->team_id;
						$table1 = $team->id;
						$league1 = $team->league_id;
						$refs = $defaultRefs;
						$altRefs = $refs;
						$groups = $this->getTableGroups($league1, $season);
						if ( $groups ) {
							foreach ($groups as $group) {
								$ref = array_search($group->value, $refs);
								array_splice($refs, $ref, 1);
							}
						}
					} else {
						$team2 = $team->team_id;
						$table2 = $team->id;
						$league2 = $team->league_id;
						$groups = $this->getTableGroups($league2, $season);
						if ( $groups ) {
							foreach ($groups as $group) {
								$ref = array_search($group->value, $altRefs);
								array_splice($altRefs, $ref, 1);
							}
						}
						if ( $refs ) {
							$refSet = false;
							if ( array_search('2', $refs) !== false ) {
								$ref = 2;
								$altRef = 5;
							} elseif ( array_search('3', $refs) !== false ) {
								$ref = 3;
								$altRef = 4;
							} elseif ( array_search('1', $refs) !== false ) {
								$ref = 1;
								$altRef = 6;
							}
							$altFound = array_search($altRef, $altRefs);
							if ( $altFound !== false ) {
								$refSet = true;
								$this->setTableGroup($ref, $table1);
								$this->setTableGroup($altRef, $table2);
							}
							if ( !$refSet ) {
								$success = false;
								$messages[] = sprintf(__('Unable to schedule first round for league %d for team %d and team %d','racketmanager'), $league1, $team1, $team2);
							}
						} else {
							$success = false;
							$messages[] = sprintf(__('Error in scheduling first round for league %d for team %d and team %d','racketmanager'), $league1, $team1, $team2);
						}
					}
					$counter ++;
				}
			}

			/* find all clubs with multiple matches at the same time */
			$sql = "SELECT `t`.`affiliatedclub`, `tc`.`match_day`, `tc`.`match_time`, count(*) FROM {$wpdb->racketmanager_team_competition} tc, {$wpdb->racketmanager_teams} t, {$wpdb->racketmanager} l, {$wpdb->racketmanager_table} tbl WHERE tc.`team_id` = t.`id` AND tc.`competition_id` = l.`competition_id` AND l.`id` = tbl.`league_id` AND tbl.`team_id` = t.`id` AND tc.`competition_id` in (".$competitionIds.") AND tbl.`season` = $season AND tbl.`profile` != 3 GROUP BY t.`affiliatedclub`, tc.`match_day`, tc.`match_time` HAVING COUNT(*) > 1 ORDER BY count(*) DESC, RAND()";
			$competitionTeams = $wpdb->get_results( $sql );
			/* for each club / match time combination balance schedule so one team is home while the other is away */
			foreach ($competitionTeams as $competitionTeam) {
				$sql = "SELECT tbl.`id`, tbl.`team_id`, tbl.`league_id`, tbl.`group` FROM {$wpdb->racketmanager_team_competition} tc, {$wpdb->racketmanager_teams} t, {$wpdb->racketmanager} l, {$wpdb->racketmanager_table} tbl WHERE tc.`team_id` = t.`id` AND tc.`competition_id` = l.`competition_id` AND l.`id` = tbl.`league_id` AND tbl.`team_id` = t.`id` AND tc.`competition_id` in (".$competitionIds.") AND tbl.`season` = ".$season." AND t.`affiliatedclub` = ".$competitionTeam->affiliatedclub." AND tc.`match_day` = '".$competitionTeam->match_day."' AND tc.`match_time` = '".$competitionTeam->match_time."' AND tbl.`profile` != 3 ORDER BY tbl.`group`, tbl.`team_id`";
				$teams = $wpdb->get_results( $sql );
				$counter = 1;
				foreach ($teams as $team) {
					/* for first of pair */
					if ( $counter & 1 ) {
						$team1 = $team->team_id;
						$table1 = $team->id;
						$league1 = $team->league_id;
						$group1 = $team->group;
						$refs = $defaultRefs;
						$altRefs = $refs;
						$groups = $this->getTableGroups($league1, $season);
						if ( $groups ) {
							foreach ($groups as $group) {
								$ref = array_search($group->value, $refs);
								array_splice($refs, $ref, 1);
							}
						}
					} else {
						/* for second of pair */
						$table2 = $team->id;
						$league2 = $team->league_id;
						$group2 = $team->group;
						$groups = $this->getTableGroups($league2, $season);
						if ( $groups ) {
							foreach ($groups as $group) {
								$ref = array_search($group->value, $altRefs);
								array_splice($altRefs, $ref, 1);
							}
						}
						if ( $refs ) {
							if ( !empty($group1) ) {
								$ref = $group1;
								if ( !empty($group2) ) {
									$altRef = $group2;
									$altFound = true;
								} else {
									$altRef = $ref + $numTeamsMax / 2;
									if ( $altRef > $numTeamsMax ) {
										$altRef = $altRef - $numTeamsMax;
									}
									$altFound = array_search($altRef, $altRefs);
								}
								if ( $altFound !== false ) {
									$this->setTableGroup($ref, $table1);
									$this->setTableGroup($altRef, $table2);
								} else {
									$success = false;
									$league = get_league($league1);
									$team = get_team($team1);
									$messages[] = sprintf(__('1 - Error in scheduling %s in %s','racketmanager'), $team->title, $league->title);
								}
							} else {
								$refSet = false;
								if ( !empty($group2) ) {
									$altRef = $group2;
									$ref = $altRef - $numTeamsMax / 2;
									if ( $ref < 1 ) {
										$ref = $ref + $numTeamsMax;
									}
									$altFound = array_search($ref, $refs);
									if ( $altFound !== false ) {
										$refSet = true;
										$this->setTableGroup($ref, $table1);
										$this->setTableGroup($altRef, $table2);
									} else {
										$success = false;
										$league = get_league($league1);
										$team = get_team($team1);
										$messages[] = sprintf(__('4 - Error in scheduling %s in %s','racketmanager'), $team->title, $league->title);
									}
								} else {
									for ($i=0; $i < count($refs) ; $i++) {
										$ref = $refs[$i];
										$altRef = $ref + $numTeamsMax / 2;
										if ( $altRef > $numTeamsMax ) {
											$altRef = $altRef - $numTeamsMax;
										}
										$altFound = array_search($altRef, $altRefs);
										if ( $altFound !== false ) {
											$refSet = true;
											$this->setTableGroup($ref, $table1);
											$this->setTableGroup($altRef, $table2);
											break;
										}
									}
									if ( !$refSet ) {
										$success = false;
										$league = get_league($league1);
										$team = get_team($team1);
										$messages[] = sprintf(__('2 - Error in scheduling %s in %s','racketmanager'), $team->title, $league->title);
									}
								}
							}
						} else {
							$success = false;
							$league = get_league($league1);
							$team = get_team($team1);
							$messages[] = sprintf(__('3 - Error in scheduling %s in %s','racketmanager'), $team->title, $league->title);
						}
					}
					$counter ++;
				}
			}
		}
		$message = implode('<br>', $messages);
		$this->setMessage( $message, true );
		return $success;
	}

	/**
	* set table group
	*
	* @param string $group
	* @param integer $id
	* @return null
	*/
	public function setTableGroup($group, $id) {
		global $wpdb;

		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->racketmanager_table} SET `group` = '%s' WHERE `id` = %d", $group, $id ) );
	}

	/**
	* set get table groups
	*
	* @param integer $league
	* @param integer $season
	* @return array $groups
	*/
	private function getTableGroups($league, $season) {
		global $wpdb;

		return $wpdb->get_results( "SELECT `group` as `value` FROM {$wpdb->racketmanager_table} WHERE `league_id` = $league AND `season` = $season AND `group` != ''");
	}

	/**
	* get Charges
	*
	* @return array $charges
	*/
	public function getCharges() {
		global $wpdb;

		return $wpdb->get_results( "SELECT `id`, `status`, `competitionType`, `type`, `season`, `date` FROM {$wpdb->racketmanager_charges} order by `season`, `type`");
	}

	/**
	* get Invoices
	*
	* @return array $invoices
	*/
	private function getInvoices( $args = array() ) {
		global $wpdb;

		$defaults = array( 'club' => false, 'status' => false );
		$args = array_merge($defaults, $args);
		extract($args, EXTR_SKIP);

		$searchTerms = array();
		if ( $club && $club != 'all') {
			$searchTerms[] = $wpdb->prepare("`club_id` = %d", $club);
		}
		if ( $status ) {
			if ( $status == 'paid') {
				$searchTerms[] = $wpdb->prepare("`status` = '%s'", $status);
			} elseif ( $status == 'open') {
				$searchTerms[] = "`status` != ('paid')";
			} elseif ( $status == 'overdue') {
				$searchTerms[] = "(`status` != ('paid') AND `date_due` < CURDATE())";
			}
		}

		$search = "";
		if (!empty($searchTerms)) {
			$search = " AND ";
			$search .= implode(" AND ", $searchTerms);
		}

		$invoices = $wpdb->get_results( "SELECT `id`, `status`, `charge_id`, `club_id`, `invoiceNumber`, `date`, `date_due` FROM {$wpdb->racketmanager_invoices} WHERE 1 = 1 $search order by `invoiceNumber`");

		$i = 0;
		foreach ($invoices as $i => $invoice) {
			$invoice = get_invoice($invoice);
			$invoices[$i] = $invoice;
		}
		return $invoices;
	}

	/**
	* get Invoice
	*
	* @return int $invoiceId
	*/
	private function getInvoice( $charge, $club ) {
		global $wpdb;

		return $wpdb->get_var( $wpdb->prepare( "SELECT `id` FROM {$wpdb->racketmanager_invoices} WHERE `charge_id` = %d AND `club_id` = %d LIMIT 1", $charge, $club ) );
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

	private function scheduleResultChase($competitionType, $options) {
		$day = intval(date('d'));
		$month = intval(date('m'));
		$year = intval(date('Y'));
		$scheduleStart = mktime(20,0,0,$month,$day,$year);
		$interval = 'daily';
		$scheduleArgs = array($competitionType);
		if ( $options['resultPending'] != '' ) {
			$scheduleName = 'rm_resultPending';
			if ( wp_next_scheduled($scheduleName, $scheduleArgs) ) {
				wp_clear_scheduled_hook($scheduleName, $scheduleArgs);
			}
			if ( ! wp_next_scheduled($scheduleName, $scheduleArgs) ) {
				if (!wp_schedule_event($scheduleStart, $interval, $scheduleName, $scheduleArgs)) {
					error_log(__('Error scheduling pending results', 'racketmanager'));
				}
			}
		}
		if ( $options['confirmationPending'] != '' ) {
			$scheduleName = 'rm_confirmationPending';
			if ( wp_next_scheduled($scheduleName, $scheduleArgs) ) {
				wp_clear_scheduled_hook($scheduleName, $scheduleArgs);
			}
			if ( ! wp_next_scheduled($scheduleName, $scheduleArgs) ) {
				if (!wp_schedule_event($scheduleStart, $interval, $scheduleName, $scheduleArgs)) {
					error_log(__('Error scheduling result confirmations', 'racketmanager'));
				}
			}
		}
 	}

}
?>
