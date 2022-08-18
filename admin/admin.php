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
	/**
	* Constructor
	*/
	public function __construct() {
		parent::__construct();

		require_once( ABSPATH . 'wp-admin/includes/template.php' );

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
		add_action('add_meta_boxes', array(&$this, 'metaboxes'));
		add_action( 'wp_ajax_racketmanager_get_league_dropdown', array(&$this, 'getLeagueDropdown'));
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
			, RACKETMANAGER_URL.'/admin/icons/cup_sw.png', 2
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

		add_filter( 'plugin_action_links_' . $plugin, array( &$this, 'pluginActions' ) );
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
		<table class="form-table">
			<tr>
				<th>
					<label for="gender"><?php _e( 'Gender','racketmanager' ); ?></label>
				</th>
				<td>
					<input type="radio" required="required" name="gender" value="M" <?php echo ( get_the_author_meta( 'gender', $user->ID )  == 'M') ? 'checked' : '' ?>> <?php _e('Male', 'racketmanager') ?><br />
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
					<label for="btm"><?php _e( 'BTM Number','racketmanager' ); ?></label>
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
		$menu['team'] = array( 'title' => __('Add Team', 'racketmanager'), 'callback' => array(&$this, 'displayTeamPage'), 'cap' => 'edit_teams', 'show' => true );
		$menu['match'] = array( 'title' => __('Add Matches', 'racketmanager'), 'callback' => array(&$this, 'displayMatchPage'), 'cap' => 'edit_matches' );
		if ( !$league->is_championship ) {
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
			include_once ( dirname (__FILE__) . '/upgrade.php' );
			racketmanager_upgrade_page();
			return;
		}

		switch ($_GET['page']) {
			case 'racketmanager-doc':
			include_once( dirname(__FILE__) . '/documentation.php' );
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
			} elseif ( $view == 'roster' ) {
				$this->displayRosterPage();
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
			} else {
				$this->displayAdminPage();
			}
			break;
			case 'racketmanager-settings':
			$this->displayOptionsPage();
			break;
			case 'racketmanager-import':
			$this->displayImportPage();
			break;
			case 'racketmanager-documentation':
			include_once( dirname(__FILE__) . '/documentation.php' );
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
							include_once( $menu[$page]['file'] );
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
			if ( $club_id ) $club = get_club($club_id);
			if ( isset($_POST['addCompetition']) ) {
				if ( current_user_can('edit_leagues') ) {
					check_admin_referer('racketmanager_add-competition');
					$this->addCompetition( htmlspecialchars(strip_tags($_POST['competition_name'])), $_POST['num_rubbers'], $_POST['num_sets'], $_POST['competition_type'], $_POST['competitiontype'] );
					$this->printMessage();
				} else {
					$this->setMessage(__("You don't have permission to perform this task", 'racketmanager'), true);
				}
			} elseif ( isset($_POST['docompdel']) && $_POST['action'] == 'delete' ) {
				if ( current_user_can('del_leagues') ) {
					check_admin_referer('competitions-bulk');
					foreach ( $_POST['competition'] AS $competition_id ) {
						$this->delCompetition( intval($competition_id) );
					}
				} else {
					$this->setMessage(__("You don't have permission to perform this task", 'racketmanager'), true);
				}
			}
			include_once( dirname(__FILE__) . '/index.php' );
		}
	}

	/**
	* show RacketManager results page
	*
	*/
	private function displayResultsPage() {
		global $league, $championship, $competition ;

		if ( !current_user_can( 'view_leagues' ) ) {
			echo '<div class="error"><p style="text-align: center;">'.__("You do not have sufficient permissions to access this page.").'</p></div>';
		} else {
			$tab = isset($_GET['tab']) ? $_GET['tab'] : "resultschecker";
			$resultsCheckFilter = 'outstanding';
			if ( isset($_POST['doResultsChecker']) ) {
				if ( current_user_can('update_results') ) {
					check_admin_referer('results-checker-bulk');
					foreach ( $_POST['resultsChecker'] AS $i => $resultsChecker_id ) {
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
			} elseif ( isset($_POST['doFilterResultsChecker']) ) {
				if ( $_POST['filterResultsChecker'] == 'outstanding' ) {
					$resultsCheckFilter = 'outstanding';
				} elseif ( $_POST['filterResultsChecker'] == 'all' ) {
					$resultsCheckFilter = '';
				}
				$tab = "resultschecker";
			}
			$resultsCheckers = $this->getResultsChecker($resultsCheckFilter);
			include_once( dirname(__FILE__) . '/show-results.php' );
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
			include_once( dirname(__FILE__) . '/show-match.php' );
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
					foreach ( $_POST['competition'] AS $competition_id ) {
						$this->addSeasonToCompetition( htmlspecialchars($_POST['season']), intval($_POST['num_match_days']), $competition_id );
					}
				}
			}
			$tournament = $racketmanager->getTournament( array('id' => $_GET['tournament']) );
			$competitionType = 'tournament';
			$season = $tournament->season;
			$type = $tournament->type;
			$standalone = true;
			$competitionQuery = array( 'type' => $competitionType, 'name' => $type, 'season' => $season );
			$pageTitle = $tournament->name.' '.__( 'Tournament Competitions', 'racketmanager' );
			include_once( dirname(__FILE__) . '/show-competitions.php' );
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

			if ( isset($_POST['addLeague']) && !isset($_POST['deleteit']) ) {
				check_admin_referer('racketmanager_add-league');
				if ( empty($_POST['league_id'] ) ){
					$this->addLeague( htmlspecialchars($_POST['league_title']), intval($_POST['competition_id']) );
				} else {
					$this->editLeague( intval($_POST['league_id']), htmlspecialchars($_POST['league_title']), intval($_POST['competition_id']) );
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
				check_admin_referer('leagues-bulk');
				foreach ( $_POST['league'] AS $league_id )
				$this->delLeague( intval($league_id) );
			} elseif ( isset($_POST['doactionconstitution']) && $_POST['action'] == 'delete' ) {
				$tab = 'constitution';
				if ( current_user_can('del_leagues') ) {
					check_admin_referer('constitution-bulk');
					foreach ( $_POST['table'] AS $tableId ) {
						$teams = isset($_POST['teamId']) ? $_POST['teamId'] : array();
						$leagues = isset($_POST['leagueId']) ? $_POST['leagueId'] : array();
						$team = isset($teams[$tableId]) ? $teams[$tableId] : 0;
						$league = isset($leagues[$tableId]) ? $leagues[$tableId] : 0;
						if ( isset($team) && isset($league) ) {
							$this->delTeamFromLeague( $team, $league, $_POST['latestSeason']);
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
				foreach ( $_POST['tableId'] AS $tableId ) {
					$team = $_POST['teamId'][$tableId];
					$league = $_POST['leagueId'][$tableId];
					if ( $js ) {
						$rank ++;
					} else {
						$rank = isset($_POST['rank'][$tableId]) ? $_POST['rank'][$tableId] : '';
					}
					$status = $_POST['status'][$tableId];
					$profile = $_POST['profile'][$tableId];
					if ( $_POST['constitutionAction'] == 'insert' ) {
						$racketmanager->addTeamtoTable( $league, $team, $_POST['latestSeason'], array(), true, $rank, $status, $profile );
					} elseif ( $_POST['constitutionAction'] == 'update' ) {
						$this->updateTable( $tableId, $league, $team, $_POST['latestSeason'], $rank, $status, $profile );
					}
				}
			} elseif ( isset($_POST['action']) && $_POST['action'] == 'addTeamsToLeague' ) {
				$tab = 'constitution';
				foreach ( $_POST['team'] AS $i => $team_id ) {
					$racketmanager->addTeamtoTable( $_POST['league_id'], $team_id, htmlspecialchars($_POST['season']), array(), false, '99', 'NT', '1' );
					$this->setTeamCompetition( $team_id, $_POST['competition_id'] );
				}
			} elseif ( isset($_POST['updateSettings']) ) {
				check_admin_referer('racketmanager_manage-competition-options');

				$settings = (array)$_POST['settings'];

				$this->_editCompetition( intval($_POST['competition_id']), $_POST['competition_title'], $settings );
				$this->printMessage();

				$options = $racketmanager->options;
				$competition->reloadSettings();

				// Set active tab
				$tab = 'settings';
			} elseif ( isset($_GET['statsseason']) && $_GET['statsseason'] == 'Show' ) {
				if ( isset($_GET['club_id']) ) {
					$club_id = intval($_GET['club_id']);
				}
				$tab = 'playerstats';
			} elseif ( isset($_GET['view']) && $_GET['view'] == 'matches' ) {
			}
			include_once( dirname(__FILE__) . '/show-competition.php' );
		}
	}

	/**
	* display league overview page
	*
	*/
	private function displayLeaguePage() {
		global $league, $championship, $competition ;

		if ( !current_user_can( 'view_leagues' ) ) {
			echo '<div class="error"><p style="text-align: center;">'.__("You do not have sufficient permissions to access this page.").'</p></div>';
		} else {
			$league = get_league();
			$league->setSeason();
			$season = $league->getSeason();
			$league_mode = (isset($league->mode) ? ($league->mode) : '' );

			$tab = 'standings';
			$matchDay = false;
			if ( isset($_POST['updateLeague']) && !isset($_POST['doaction']) && !isset($_POST['delmatches']) && !isset($_POST['doaction-match_day']) )  {
				if ( 'team' == $_POST['updateLeague'] ) {
					if ( current_user_can('edit_teams') ) {
						check_admin_referer('racketmanager_manage-teams');

						$home = isset( $_POST['home'] ) ? 1 : 0;
						$custom = !isset($_POST['custom']) ? array() : $_POST['custom'];
						$roster = isset($_POST['roster']) ? intval($_POST['roster']) : 0;
						$profile = isset($_POST['profile']) ? intval($_POST['profile']) : 0;
						$group = isset($_POST['group']) ? htmlspecialchars(strip_tags($_POST['group'])) : '';

						if ( 'Add' == $_POST['action'] ) {
							if ( '' == $_POST['team_id'] ) {
								$team_id = $this->addNewTeamToLeague( htmlspecialchars($_POST['league_id']), $_POST['affiliatedclub'], ($_POST['team_type']), htmlspecialchars($_POST['captainId']), htmlspecialchars($_POST['contactno']), htmlspecialchars($_POST['contactemail']), htmlspecialchars($_POST['matchday']), htmlspecialchars($_POST['matchtime']), $home, $roster, $profile, $custom );
								$this->addTableEntry( htmlspecialchars($_POST['league_id']), $team_id, htmlspecialchars($_POST['season']) );
							} else {
								$this->editTeam( intval($_POST['team_id']), htmlspecialchars(strip_tags($_POST['team'])), $_POST['affiliatedclub'], $_POST['team_type'],htmlspecialchars($_POST['captainId']), htmlspecialchars($_POST['contactno']), htmlspecialchars($_POST['contactemail']),  $_POST['matchday'], $_POST['matchtime'], $home, $group, $roster, $profile, $custom, intval($_POST['league_id']) );
								$this->addTableEntry( htmlspecialchars($_POST['league_id']), intval($_POST['team_id']), htmlspecialchars($_POST['season']) );
							}
						} else {
							$this->editTeam( intval($_POST['team_id']), htmlspecialchars(strip_tags($_POST['team'])), $_POST['affiliatedclub'], $_POST['team_type'], htmlspecialchars($_POST['captainId']), htmlspecialchars($_POST['contactno']), htmlspecialchars($_POST['contactemail']),  htmlspecialchars($_POST['matchday']), htmlspecialchars($_POST['matchtime']), $home, $group, $roster, $profile, $custom, intval($_POST['league_id']), );
						}
					} else {
						$this->setMessage(__("You don't have permission to perform this task", 'racketmanager'), true);
					}
				} elseif ( 'teamPlayer' == $_POST['updateLeague'] ) {
					if ( current_user_can('edit_teams') ) {
						check_admin_referer('racketmanager_manage-teams');

						$teamPlayer2 = isset($_POST['teamPlayer2']) ? htmlspecialchars(strip_tags($_POST['teamPlayer2'])) : '';
						$teamPlayer2Id = isset($_POST['teamPlayerId2']) ? $_POST['teamPlayerId2'] : 0;

						if ( 'Add' == $_POST['action'] ) {
							if ( '' == $_POST['team_id'] ) {
								$team_id = $this->addTeamPlayer( htmlspecialchars(strip_tags($_POST['teamPlayer1'])), $_POST['teamPlayerId1'], $teamPlayer2, $teamPlayer2Id, htmlspecialchars($_POST['contactno']), htmlspecialchars($_POST['contactemail']), htmlspecialchars($_POST['affiliatedclub']), htmlspecialchars($_POST['league_id']) );
								$this->addTableEntry( htmlspecialchars($_POST['league_id']), $team_id, htmlspecialchars($_POST['season']) );
							} else {
								$this->editTeamPlayer( intval($_POST['team_id']), htmlspecialchars(strip_tags($_POST['teamPlayer1'])), $_POST['teamPlayerId1'], $teamPlayer2, $teamPlayer2Id, htmlspecialchars($_POST['contactno']), htmlspecialchars($_POST['contactemail']), htmlspecialchars($_POST['affiliatedclub']),  intval($_POST['league_id']) );
								$this->addTableEntry( htmlspecialchars($_POST['league_id']), intval($_POST['team_id']), htmlspecialchars($_POST['season']) );
							}
						} else {
							$this->editTeamPlayer( intval($_POST['team_id']), htmlspecialchars(strip_tags($_POST['teamPlayer1'])), $_POST['teamPlayerId1'], $teamPlayer2, $teamPlayer2Id, htmlspecialchars($_POST['contactno']), htmlspecialchars($_POST['contactemail']), htmlspecialchars($_POST['affiliatedclub']), intval($_POST['league_id']) );
						}
					} else {
						$this->setMessage(__("You don't have permission to perform this task", 'racketmanager'), true);
					}
				} elseif ( 'match' == $_POST['updateLeague'] ) {
					if ( current_user_can('edit_matches') ) {
						check_admin_referer('racketmanager_manage-matches');

						$group = isset($_POST['group']) ? htmlspecialchars(strip_tags($_POST['group'])) : '';

						if ( 'add' == $_POST['mode'] ) {
							$num_matches = count($_POST['match']);
							foreach ( $_POST['match'] AS $i => $match_id ) {
								if ( isset($_POST['add_match'][$i]) || $_POST['away_team'][$i] != $_POST['home_team'][$i]  ) {
									$index = ( isset($_POST['mydatepicker'][$i]) ) ? $i : 0;
									if (!isset($_POST['begin_hour'][$i])) $_POST['begin_hour'][$i] = 0;
									if (!isset($_POST['begin_minutes'][$i])) $_POST['begin_minutes'][$i] = 0;
									$date = $_POST['mydatepicker'][$index].' '.intval($_POST['begin_hour'][$i]).':'.intval($_POST['begin_minutes'][$i]).':00';
									$match_day = ( isset($_POST['match_day'][$i]) ? $_POST['match_day'][$i] : (!empty($_POST['match_day']) ? intval($_POST['match_day']) : '' )) ;
									$custom = isset($_POST['custom']) ? $_POST['custom'][$i] : array();

									$this->addMatch( $date, $_POST['home_team'][$i], $_POST['away_team'][$i], $match_day, htmlspecialchars(strip_tags($_POST['location'][$i])), intval($_POST['league_id']), htmlspecialchars(strip_tags($_POST['season'])), $group, htmlspecialchars(strip_tags($_POST['final'])), $custom, intval($_POST['num_rubbers']) );
								} else {
									$num_matches -= 1;
								}
							}
							$this->setMessage(sprintf(_n('%d Match added', '%d Matches added', $num_matches, 'racketmanager'), $num_matches));
						} else {
							$num_matches = count($_POST['match']);
							$post_match = $this->htmlspecialchars_array($_POST['match']);
							foreach ( $post_match AS $i => $match_id ) {
								$begin_hour = isset($_POST['begin_hour'][$i]) ? intval($_POST['begin_hour'][$i]) : "00";
								$begin_minutes = isset($_POST['begin_minutes'][$i]) ? intval($_POST['begin_minutes'][$i]) : "00";
								if( isset($_POST['mydatepicker'][$i]) ) {
									$index = ( isset($_POST['mydatepicker'][$i]) ) ? $i : 0;
									$date = htmlspecialchars(strip_tags($_POST['mydatepicker'][$index])).' '.$begin_hour.':'.$begin_minutes.':00';
								} else {
									$index = ( isset($_POST['year'][$i]) && isset($_POST['month'][$i]) && isset($_POST['day'][$i]) ) ? $i : 0;
									$date = intval($_POST['year'][$index]).'-'.intval($_POST['month'][$index]).'-'.intval($_POST['day'][$index]).' '.$begin_hour.':'.$begin_minutes.':00';
								}
								$match_day = (isset($_POST['match_day']) && is_array($_POST['match_day'])) ? intval($_POST['match_day'][$i]) : (isset($_POST['match_day']) && !empty($_POST['match_day']) ? intval($_POST['match_day']) : '' ) ;
								$custom = isset($_POST['custom']) ? $_POST['custom'][$i] : array();
								$home_team = isset($_POST['home_team'][$i]) ? htmlspecialchars(strip_tags($_POST['home_team'][$i])) : '';
								$away_team = isset($_POST['away_team'][$i]) ? htmlspecialchars(strip_tags($_POST['away_team'][$i])) : '';
								$this->editMatch( $date, $home_team, $away_team, $match_day, htmlspecialchars($_POST['location'][$i]), intval($_POST['league_id']), $match_id, $group, htmlspecialchars(strip_tags($_POST['final'])), $custom );
							}
							$this->setMessage(sprintf(_n('%d Match updated', '%d Matches updated', $num_matches, 'racketmanager'), $num_matches));
						}
					} else {
						$this->setMessage(__("You don't have permission to perform this task", 'racketmanager'), true);
					}
				} elseif ( 'results' == $_POST['updateLeague'] ) {
					if ( current_user_can('update_results') ) {
						check_admin_referer('matches-bulk');
						$custom = isset($_POST['custom']) ? $_POST['custom'] : array();
						$this->updateResults( $_POST['matches'], $_POST['home_points'], $_POST['away_points'], $_POST['home_team'], $_POST['away_team'], $custom, $_POST['season'] );
						$tab = 'matches';
						$matchDay = intval($_POST['current_match_day']);
					} else {
						$this->setMessage(__("You don't have permission to perform this task", 'racketmanager'), true);
					}
				} elseif ( 'teams_manual' == $_POST['updateLeague'] ) {
					if ( current_user_can('update_results') ) {
						check_admin_referer('teams-bulk');
						$league->saveStandingsManually( $_POST['team_id'], $_POST['points_plus'], $_POST['points_minus'], $_POST['num_done_matches'], $_POST['num_won_matches'], $_POST['num_draw_matches'], $_POST['num_lost_matches'], $_POST['add_points'], $_POST['custom'] );

						$this->setMessage(__('Standings Table updated','racketmanager'));
					} else {
						$this->setMessage(__("You don't have permission to perform this task", 'racketmanager'), true);
					}
				}

				$this->printMessage();
			}  elseif ( isset($_POST['doaction']) ) {
				if ( $_POST['action'] == "delete" ) {
					if ( current_user_can('del_teams') ) {
						check_admin_referer('teams-bulk');
						foreach ( $_POST['team'] AS $team_id )
						$this->delTeamFromLeague( intval($team_id), intval($_GET['league_id']), $season );
					} else {
						$this->setMessage(__("You don't have permission to perform this task", 'racketmanager'), true);
					}
				}
			}  elseif ( isset($_POST['delmatches']) ) {
				if ( $_POST['delMatchOption'] == "delete" ) {
					if ( current_user_can('del_matches') ) {
						check_admin_referer('matches-bulk');
						foreach ( $_POST['match'] AS $match_id )
						$this->delMatch( intval($match_id) );

						$tab = 'matches';
					} else {
						$this->setMessage(__("You don't have permission to perform this task", 'racketmanager'), true);
					}
				}
			} elseif ( isset($_POST['action']) && $_POST['action'] == 'addTeamsToLeague' ) {
				foreach ( $_POST['team'] AS $i => $team_id ) {
					$this->addTableEntry( htmlspecialchars($_POST['league_id']), $team_id, htmlspecialchars($_POST['season']) );
					$this->setTeamCompetition( $team_id, $_POST['competition_id'] );
				}
			} elseif ( isset($_POST['contactTeam']) ) {
				if ( current_user_can('edit_teams') ) {
					check_admin_referer('racketmanager_contact-teams-preview');
					$this->contactLeagueTeams( intval($_POST['league_id']), $_POST['season'], htmlspecialchars_decode($_POST['emailMessage']) );
				} else {
					$this->setMessage(__("You don't have permission to perform this task", 'racketmanager'), true);
				}
				$this->printMessage();
			}

			// rank teams manually
			if (isset($_POST['saveRanking'])) {
				if ( current_user_can('update_results') ) {
					$js = ( $_POST['js-active'] == 1 ) ? true : false;

					$team_ranks = array();
					$team_ids = array_values($_POST['table_id']);
					foreach ($team_ids AS $key => $team_id) {
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
				$this->printMessage();

				$tab = 'standings';
			}

			// rank teams randomly
			if (isset($_POST['randomRanking'])) {
				if ( current_user_can('update_results') ) {
					$js = ( $_POST['js-active'] == 1 ) ? true : false;
					$team_ranks = array();
					$team_ids = array_values($_POST['table_id']);
					shuffle($team_ids);
					foreach ($team_ids AS $key => $team_id) {
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
				$this->printMessage();

				$tab = 'standings';
			}
			if (isset($_POST['updateRanking'])) {
				if ( current_user_can('update_results') ) {
					$league->_rankTeams($league->id);
					$this->setMessage(__('Team ranking updated','racketmanager'));
					$this->printMessage();
				} else {
					$this->setMessage(__("You don't have permission to perform this task", 'racketmanager'), true);
				}

				$tab = 'standings';
			}

			// check if league is a cup championship
			$cup = ( $league_mode == 'championship' ) ? true : false;

			$group = isset($_GET['group']) ? htmlspecialchars(strip_tags($_GET['group'])) : '';
			if ( empty($group) && isset($_POST['group']) ) $group = htmlspecialchars(strip_tags($_POST['group']));

			$team_id = isset($_POST['team_id']) ? intval($_POST['team_id']) : false;

			$options = $this->options;

			$match_args = array("final" => "", "cache" => false);
			if ( $season )
			$match_args["season"] = $season;
			if ( $group )
			$match_args["group"] = $group;
			if ( $team_id )
			$match_args['team_id'] = $team_id;

			if (intval($league->num_matches_per_page) > 0)
			$match_args['limit'] = intval($league->num_matches_per_page);

			if ( isset($_POST['doaction-match_day'])) {
				if ($_POST['match_day'] != -1) {
					$matchDay = intval($_POST['match_day']);
					$league->setMatchDay($matchDay);
				}
				$tab = 'matches';
			} else {
				if ( $league->match_display == 'current_match_day' )
				$league->setMatchDay('current');
				elseif ( $league->match_display == 'all' )
				$league->setMatchDay(-1);
			}

			if ( empty($competition->seasons)  ) {
				$this->setMessage( __( 'You need to add at least one season for the competition', 'racketmanager' ), true );
				$this->printMessage();
			}

			$teams = $league->getLeagueTeams( array( "season" => $season, "cache" => false) );
			if ( $league_mode != 'championship' ) {
				$matches = $league->getMatches( $match_args );
				$league->setNumMatches();
			}

			if ( isset($_GET['match_paged']) )
			$tab = 'matches';

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

			include_once( dirname(__FILE__) . '/show-league.php' );
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
			if ( $leagueType == 'LD' ) $leagueType = 'XD';

			if ( $league->entryType == 'player' ) {
				$entryType = 'player';
			} else {
				$entryType = '';
			}
			$season = isset($_GET['season']) ? htmlspecialchars(strip_tags($_GET['season'])) : '';
			$view = isset($_GET['view']) ? htmlspecialchars(strip_tags($_GET['view'])) : '';
			include_once( dirname(__FILE__) . '/includes/teamslist.php' );
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
			$competitionType = 'league';
			$type = '';
			$season = '';
			$standalone = true;
			$competitionQuery = array( 'type' => $competitionType );
			$pageTitle = __( ucfirst($competitionType), 'racketmanager').' '.__( 'Competitions', 'racketmanager' );
			include_once( dirname(__FILE__) . '/show-competitions.php' );
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
			include_once( dirname(__FILE__) . '/show-competitions.php' );
			include_once( dirname(__FILE__) . '/show-cup-entry.php' );
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
				check_admin_referer('racketmanager_add-tournament');
				$this->addTournament( htmlspecialchars($_POST['tournament']), htmlspecialchars($_POST['type']), htmlspecialchars($_POST['season']), htmlspecialchars($_POST['venue']),  htmlspecialchars($_POST['date']), htmlspecialchars($_POST['closingdate']), htmlspecialchars($_POST['tournamentSecretaryName']), htmlspecialchars($_POST['tournamentSecretary']), htmlspecialchars($_POST['tournamentSecretaryContactNo']), htmlspecialchars($_POST['tournamentSecretaryEmail']), intval($_POST['numcourts']), htmlspecialchars($_POST['starttime']) );
				$this->printMessage();
			} elseif ( isset($_POST['editTournament']) ) {
				check_admin_referer('racketmanager_manage-tournament');
				$this->editTournament( intval($_POST['tournament_id']), htmlspecialchars($_POST['tournament']), htmlspecialchars($_POST['type']), htmlspecialchars($_POST['season']), htmlspecialchars($_POST['venue']),  htmlspecialchars($_POST['date']), htmlspecialchars($_POST['closingdate']), htmlspecialchars($_POST['tournamentSecretaryName']), htmlspecialchars($_POST['tournamentSecretary']), htmlspecialchars($_POST['tournamentSecretaryContactNo']), htmlspecialchars($_POST['tournamentSecretaryEmail']), intval($_POST['numcourts']), htmlspecialchars($_POST['starttime']) );
				$this->printMessage();
			} elseif ( isset($_POST['doTournamentDel']) && $_POST['action'] == 'delete' ) {
				check_admin_referer('tournaments-bulk');
				foreach ( $_POST['tournament'] AS $tournament_id ) {
					$this->delTournament( intval($tournament_id) );
				}
			}
			$club_id = 0;
			$this->printMessage();
			$clubs = $racketmanager->getClubs( );
			include_once( dirname(__FILE__) . '/show-tournaments.php' );
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
				$tournament = $racketmanager->getTournament( array('id' => $tournamentId) );

				$form_title = __( 'Edit Tournament', 'racketmanager' );
				$form_action = __( 'Update', 'racketmanager' );
			} else {
				$tournamentId = '';
				$form_title = __( 'Add Tournament', 'racketmanager' );
				$form_action = __( 'Add', 'racketmanager' );
				$tournament = (object)array( 'name' => '', 'type' => '', 'id' => '', 'tournamentSecretary' => '', 'tournamentSecretaryName' => '', 'venue' => '', 'tournamentSecretaryContactNo' => '', 'tournamentSecretaryEmail' => '', 'date' => '', 'closingdate' => '', 'numcourts' => '', 'starttime' => '');
			}

			$clubs = $racketmanager->getClubs( );
			include_once( dirname(__FILE__) . '/includes/tournament.php' );
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
			if ( isset($_POST['saveTournamentPlanner']) ) {
				check_admin_referer('racketmanager_tournament-planner');
				$this->saveTournamentPlan($_POST['tournamentId'], $_POST['numFinals'], $_POST['court'], $_POST['starttime'], $_POST['match'], $_POST['matchtime'] );
				$this->printMessage();
			} elseif ( isset($_POST['resetTournamentPlanner']) ) {
				check_admin_referer('racketmanager_tournament-planner');
				$this->resetTournamentPlan($_POST['tournamentId']);
				$this->printMessage();
			} elseif ( isset($_POST['saveTournament']) ) {
				check_admin_referer('racketmanager_tournament');
				$this->updateTournament($_POST['tournamentId'], $_POST['starttime'], $_POST['numcourts'], $_POST['timeincrement'] );
				$this->printMessage();
			}

			if ( isset( $_GET['tournament'] ) ) {
				$tournamentId = $_GET['tournament'];
				$tournament = $racketmanager->getTournament( array('id' => $tournamentId) );
				$finalMatches = $racketmanager->getMatches( array('season' => $tournament->season, 'final' => 'final', 'competitiontype' => 'tournament', 'competitionseason' => $tournament->type));
			}
			include_once( dirname(__FILE__) . '/includes/tournament-plan.php' );
		}
	}

	/**
	* display clubs page
	*
	*/
	private function displayClubsPage() {
		global $racketmanager;

		if ( !current_user_can( 'edit_leagues' ) ) {
			echo '<div class="error"><p style="text-align: center;">'.__("You do not have sufficient permissions to access this page.").'</p></div>';
		} else {
			if ( isset($_POST['addClub']) ) {
				check_admin_referer('racketmanager_add-club');
				$this->addClub( htmlspecialchars($_POST['club']), htmlspecialchars($_POST['type']), htmlspecialchars($_POST['shortcode']),  htmlspecialchars($_POST['contactno']), htmlspecialchars($_POST['website']), htmlspecialchars($_POST['founded']), htmlspecialchars($_POST['facilities']), htmlspecialchars($_POST['address']), htmlspecialchars($_POST['latitude']), htmlspecialchars($_POST['longitude']) );
				$this->printMessage();
			} elseif ( isset($_POST['editClub']) ) {
				check_admin_referer('racketmanager_manage-club');
				$this->editClub( intval($_POST['club_id']), htmlspecialchars(strip_tags($_POST['club'])), htmlspecialchars($_POST['type']), htmlspecialchars($_POST['shortcode']), intval($_POST['matchsecretary']), htmlspecialchars($_POST['matchSecretaryContactNo']), htmlspecialchars($_POST['matchSecretaryEmail']), htmlspecialchars($_POST['contactno']), htmlspecialchars($_POST['website']), htmlspecialchars($_POST['founded']), htmlspecialchars($_POST['facilities']), htmlspecialchars($_POST['address']), htmlspecialchars($_POST['latitude']), htmlspecialchars($_POST['longitude']) );
				$this->printMessage();
			} elseif ( isset($_POST['doClubDel']) && $_POST['action'] == 'delete' ) {
				check_admin_referer('clubs-bulk');
				foreach ( $_POST['club'] AS $club_id ) {
					$this->delClub( intval($club_id) );
				}
				$club_id = 0;
				$this->printMessage();
			}
			include_once( dirname(__FILE__) . '/show-clubs.php' );
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
			$noleague = true;
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
			include_once( dirname(__FILE__) . '/includes/club.php' );
		}
	}

	/**
	* display roster page
	*
	*/
	private function displayRosterPage() {
		global $racketmanager;

		if ( !current_user_can( 'edit_teams' ) ) {
			echo '<div class="error"><p style="text-align: center;">'.__("You do not have sufficient permissions to access this page.").'</p></div>';
		} else {
			if ( isset($_POST['addRosterPlayer']) ) {
				check_admin_referer('racketmanager_add-roster');
				if ( !isset($_POST['firstname']) ) {
					$this->setMessage( __("First name required",'racketmanager'), true );
				} elseif ( !isset($_POST['surname']) ) {
					$this->setMessage( __("Surname required",'racketmanager'), true );
				} elseif ( !isset($_POST['gender']) ) {
					$this->setMessage( __("Gender required",'racketmanager'), true );
				} else {
					$btm = isset($_POST['btm']) ? $_POST['btm'] : false;
					$email = isset($_POST['email']) ? $_POST['email'] : false;
 					$this->addPlayerToRoster( $_POST['club_Id'], $_POST['firstname'], $_POST['surname'], $_POST['gender'], $btm, $email );
				}
			} elseif ( isset($_POST['dorosterdel']) && $_POST['action'] == 'delete' ) {
				check_admin_referer('roster-bulk');
				foreach ( $_POST['roster'] AS $roster_id ) {
					$this->delRoster( intval($roster_id) );
				}
			}
			$this->printMessage();
			if (isset($_GET['club_id'])) $club_id = $_GET['club_id'];
			$club = get_club($club_id);
			include_once( dirname(__FILE__) . '/club/show-roster.php' );
		}
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
				$tournament = $racketmanager->getTournament(array('id' => $_GET['tournament']));
			}
			include_once( dirname(__FILE__) . '/includes/competitions-list.php' );
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
				$this->addTeam( $_POST['affiliatedClub'], $_POST['team_type'] );
			} elseif ( isset($_POST['editTeam']) ) {
				check_admin_referer('racketmanager_manage-teams');
				$this->editTeam( intval($_POST['team_id']), htmlspecialchars(strip_tags($_POST['team'])), $_POST['affiliatedclub'], $_POST['team_type']);
			} elseif ( isset($_POST['doteamdel']) && $_POST['action'] == 'delete' ) {
				check_admin_referer('teams-bulk');
				foreach ( $_POST['team'] AS $team_id ) {
					$this->delTeam( intval($team_id) );
				}
			}
			$this->printMessage();
			if (isset($_GET['club_id'])) $club_id = $_GET['club_id'];
			$club = get_club($club_id);
			include_once( dirname(__FILE__) . '/club/show-teams.php' );
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
			if ( isset( $_GET['league_id'] ) ) {
				$noleague = false ;
				$league_id = intval($_GET['league_id']);
				$league = get_league( $league_id );
				$season = isset($_GET['season']) ? htmlspecialchars(strip_tags($_GET['season'])) : '';
				$matchdays = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
				if ( isset($league->entryType) && $league->entryType == 'player' ) {
					$file = "playerteam.php";
				}
			} else {
				$noleague = true;
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
				if ( $noleague ) {
					$club = get_club($clubId);
					$team = $club->getTeam(intval($_GET['edit']));
				} else {
					$team = $league->getTeamDtls(intval($_GET['edit']));
				}

				if ( !isset($team->roster) ) $team->roster = array();

				$form_title = __( 'Edit Team', 'racketmanager' );
				$form_action = __( 'Update', 'racketmanager' );
			} else {
				$form_title = __( 'Add Team', 'racketmanager' );
				$form_action = __( 'Add', 'racketmanager' );
				$team = (object)array( 'title' => '', 'home' => 0, 'id' => '', 'website' => '', 'captain' => '', 'captainId' => '', 'contactno' => '', 'contactemail' => '', 'stadium' => '', 'match_day' => '', 'match_time' => '', 'roster' => array('id' => '', 'cat_id' => '' ) );
			}
			$clubs = $racketmanager->getClubs( );

			require_once( dirname(__FILE__) . '/includes/teams/'. $file );
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
				if ( is_array($groups) ) {
				} else {
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
						$matches[$h] = new Match();
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

				if ( isset($_GET['final']) ) {
				} else {
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
				if ($max_matches > 50) $max_matches = 50;

				for ( $i = 0; $i < $max_matches; $i++ ) {
					$matches[] = new Match();
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
			include_once( dirname(__FILE__) . '/includes/match.php' );
		}
	}

	/**
	* display admin page
	*
	*/
	private function displayAdminPage() {
		global $racketmanager;

		if ( !current_user_can( 'edit_leagues' ) ) {
			echo '<div class="error"><p style="text-align: center;">'.__("You do not have sufficient permissions to access this page.").'</p></div>';
		} else {
			$tab = "rosterrequest";
			if ( isset($_POST['addSeason']) ) {
				check_admin_referer('racketmanager_add-season');
				$this->addSeason( htmlspecialchars(strip_tags($_POST['seasonName'])) );
				$tab = "seasons";
			} elseif ( isset($_POST['doSeasonDel']) && $_POST['action'] == 'delete' ) {
				check_admin_referer('seasons-bulk');
				foreach ( $_POST['season'] AS $season_id ) {
					$this->delSeason( intval($season_id) );
				}
				$tab = "seasons";
			} elseif ( isset($_POST['doaddCompetitionsToSeason']) && $_POST['action'] == 'addCompetitionsToSeason' ) {
				check_admin_referer('racketmanager_add-seasons-competitions-bulk');
				foreach ( $_POST['competition'] AS $competition_id ) {
					$this->addSeasonToCompetition( htmlspecialchars($_POST['season']), intval($_POST['num_match_days']), $competition_id );
				}
				$tab = "seasons";
			} elseif ( isset($_POST['addPlayer']) ) {
				check_admin_referer('racketmanager_add-player');
				$this->addPlayer( htmlspecialchars(strip_tags($_POST['firstname'])), htmlspecialchars(strip_tags($_POST['surname'])), $_POST['gender'], htmlspecialchars(strip_tags($_POST['btm'])), $_POST['email'], 'true');
				$tab = "players";
			} elseif ( isset($_POST['doPlayerDel']) && $_POST['action'] == 'delete' ) {
				if ( current_user_can('edit_teams') ) {
					check_admin_referer('player-bulk');
					foreach ( $_POST['player'] AS $player_id ) {
						$this->delPlayer( intval($player_id) );
					}
				} else {
					$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
				}
				$tab = "players";
			} elseif ( isset($_POST['dorosterrequest']) ) {
				if ( current_user_can('edit_teams') ) {
					check_admin_referer('roster-request-bulk');
					foreach ( $_POST['rosterRequest'] AS $i => $rosterRequest_id ) {
						if ( $_POST['action'] == 'approve' ) {
							$this->_approveRosterRequest( intval($_POST['club_id'][$i]), intval($rosterRequest_id) );
						} elseif ( $_POST['action'] == 'delete' ) {
							$this->deleteRosterRequest( intval($rosterRequest_id) );
						}
					}
				} else {
					$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
				}
				$tab = "rosterrequest";
			} elseif ( isset($_GET['view']) && $_GET['view'] == 'rosterRequest' ) {
				$tab = "rosterrequest";
			}
			$this->printMessage();

			include_once( dirname(__FILE__) . '/show-admin.php' );
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
			include_once( RACKETMANAGER_PATH . '/admin/tools/import.php' );
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

			include_once( dirname(__FILE__) . '/includes/contact.php' );
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
				$this->editSeason( intval($_POST['seasonId']), intval($_POST['num_match_days']), intval($_POST['competitionId']), $matchDate, $homeAway );
				$this->printMessage();
			} else {
				$seasonId = htmlspecialchars($_GET['season']);
			}
			$competition = get_competition($_GET['competition_id']);
			$season_data = $competition->seasons[$seasonId];

			include_once( dirname(__FILE__) . '/includes/season.php' );
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
			include_once( dirname(__FILE__) . '/show-schedule.php' );
		}
	}

	/**
	* display link to settings page in plugin table
	*
	* @param array $links array of action links
	* @return array
	*/
	public function pluginActions( $links ) {
		$settings_link = '<a href="admin.php?page=racketmanager-settings">' . __('Settings') . '</a>';
		array_unshift( $links, $settings_link );

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
		<!--<![CDATA[-->
		RacketManagerAjaxL10n = {
			requestUrl: "<?php bloginfo( 'wpurl' ); ?>/wp-admin/admin-ajax.php", manualPointRuleDescription: "<?php _e( 'Order: win, win overtime, tie, loss, loss overtime', 'racketmanager' ) ?>", pluginUrl: "<?php plugins_url('', dirname(__FILE__)); ?>/wp-content/plugins/leaguemanager", Edit: "<?php _e("Edit"); ?>", Post: "<?php _e("Post"); ?>", Save: "<?php _e("Save"); ?>", Cancel: "<?php _e("Cancel"); ?>", pleaseWait: "<?php _e("Please wait..."); ?>", Delete: "<?php _e('Delete', 'racketmanager') ?>", Yellow: "<?php _e( 'Yellow', 'racketmanager') ?>", Red: "<?php _e( 'Red', 'racketmanager') ?>", Yellow_Red: "<?php _e('Yellow/Red', 'racketmanager') ?>", Insert: "<?php _e( 'Insert', 'racketmanager' ) ?>", InsertPlayer: "<?php _e( 'Insert Player', 'racketmanager' ) ?>", AddPlayerFromRoster: "<?php _e( 'Add Player from Team Roster', 'racketmanager' ) ?>"
		}
		<!--]]>-->
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
	* get available competition types
	*
	* @return array
	*/
	public function getCompetitionTypes() {
		$competitionTypes = array( 'cup' => __('cup', 'racketmanager'), 'league' => __('league', 'racketmanager'), 'tournament' => __('tournament', 'racketmanager') );
		return $competitionTypes;
	}

	/**
	* get available league modes
	*
	* @return array
	*/
	public function getModes() {
		$modes = array( 'default' => __('Default', 'racketmanager') );
		/**
		* Fired when league modes are built
		*
		* @param array $modes
		* @return array
		* @category wp-filter
		*/
		$modes = apply_filters( 'racketmanager_modes', $modes);
		return $modes;
	}

	/**
	* get available entry types
	*
	* @return array
	*/
	public function getentryTypes() {
		$entryTypes = array( 'team' => __('Team', 'racketmanager'), 'player' => __('Player', 'racketmanager') );
		return $entryTypes;
	}

	/**
	* get array of supported point rules
	*
	* @return array
	*/
	public function getPointRules() {
		$rules = array( 'manual' => __( 'Update Standings Manually', 'racketmanager' ), 'one' => __( 'One-Point-Rule', 'racketmanager' ), 'two' => __('Two-Point-Rule','racketmanager'), 'three' => __('Three-Point-Rule', 'racketmanager'), 'score' => __( 'Score', 'racketmanager'), 'user' => __('User defined', 'racketmanager') );

		/**
		* Fired when league point rules are built
		*
		* @param array $rules
		* @return array
		* @category wp-filter
		*/
		$rules = apply_filters( 'racketmanager_point_rules_list', $rules );
		asort($rules);

		return $rules;
	}

	/**
	* get available point formats
	*
	* @return array
	*/
	public function getPointFormats() {
		$point_formats = array( '%s:%s' => '%s:%s', '%s' => '%s', '%d:%d' => '%d:%d', '%d - %d' => '%d - %d', '%d' => '%d', '%.1f:%.1f' => '%f:%f', '%.1f - %.1f' => '%f - %f', '%.1f' => '%f' );
		/**
		* Fired when league point formats are built
		*
		* @param array $point_formats
		* @return array
		* @category wp-filter
		*/
		$point_formats = apply_filters( 'racketmanager_point_formats', $point_formats );
		return $point_formats;
	}

	/**
	* update match results
	*
	* @param array $matches
	* @param array $home_points
	* @param array $away_points
	* @param array $home_team
	* @param array $away_team
	* @param array $custom
	* @param string $season
	* @param boolean $final
	* @param boolean $message
	* @return int $num_matches
	*/
	private function updateResults( $matches, $home_points, $away_points, $home_team, $away_team, $custom, $season, $final = false, $message = true ) {
		if ( !current_user_can('update_results') ) {
			$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
			return false;
		}

		$league = get_league();
		$league->setFinals($final);
		$num_matches = $league->_updateResults( $matches, $home_points, $away_points, $home_team, $away_team, $custom, $season, $final );

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

	/**
	* add new competition
	*
	* @param string $name
	* @param int $num_rubbers
	* @param int $num_sets
	* @param string $type
	* @param string $mode
	* @param string $entryType
	* @return boolean
	*/
	private function addCompetition( $name, $num_rubbers, $num_sets, $type, $competitionType ) {
		global $wpdb, $racketmanager;

		if ( !current_user_can('edit_leagues') ) {
			$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
			return false;
		}

		if ( $competitionType == 'league' ) {
			$mode = 'default';
			$entryType = 'team';
		} elseif ( $competitionType == 'cup' ) {
			$mode = 'championship';
			$entryType = 'team';
		} elseif ( $competitionType == 'tournament' ) {
			$mode = 'championship';
			$entryType = 'player';
		}
		if ( $mode == 'championship' ) {
			$ranking = "manual";
			$standings = array( 'pld' => 1, 'won' => 1, 'tie' => 1, 'lost' => 1 );
		} else {
			$ranking = "auto";
			$standings = array( 'pld' => 0, 'won' => 0, 'tie' => 0, 'lost' => 0 );
		}
		$settings = array(
			"sport" => "tennis",
			"point_rule" => "tennis",
			"point_format" => "%s",
			"point_format2" => "%s",
			"team_ranking" => $ranking,
			"mode" => $mode,
			"entryType" => $entryType,
			"default_match_start_time" => array("hour" => 19, "minutes" => 30),
			"standings" => $standings,
			"num_ascend" => "",
			"num_descend" => "",
			"num_relegation" => "",
			"num_matches_per_page" => 10,
		);

		$wpdb->query( $wpdb->prepare ( "INSERT INTO {$wpdb->racketmanager_competitions} (`name`, `num_rubbers`, `num_sets`, `type`, `settings`, `competitiontype`) VALUES ('%s', '%d', '%d', '%s', '%s', '%s')", $name, $num_rubbers, $num_sets, $type, maybe_serialize($settings), $competitionType ) );
		$competition_id = $wpdb->insert_id;
		$competition = get_competition( $competition_id );

		$this->createCompetitionPages($competition_id, $name);

		$this->setMessage( __('Competition added', 'racketmanager') );

		return true;
	}

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

		$this->addRacketManagerPage($page_definition);

	}

	/**
	* edit Competition
	*
	* @param int $competition_id
	* @param string $title
	* @param array $settings
	* @return boolean
	*/
	public function _editCompetition( $competition_id, $title, $settings ) {
		global $racketmanager;

		if ( !current_user_can('edit_league_settings') ) {
			$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
			return false;
		}

		if ( $settings['mode'] == 'championship' ) {
			if ( !$settings['primary_league'] ) {
				$error = true;
				$this->setMessage( __('Primary league not set', 'racketmanager'), true );
				return false;
			}
		}
		$racketmanager->editCompetition($competition_id, $title, $settings);
		$this->setMessage( __('Settings saved', 'racketmanager') );
		return true;
	}

	/**
	* delete Competition
	*
	* @param int $competition_id
	* @return boolean
	*/
	private function delCompetition( $competition_id ) {
		global $wpdb;

		if ( !current_user_can('del_leagues') ) {
			$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
			return false;
		}

		$competition = get_competition($competition_id);
		foreach ( $competition->getLeagues() AS $league ) {

			$league_id = $league->id;

			// remove tables
			$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->racketmanager_table} WHERE `league_id` = '%d'", $league_id) );
			// remove matches and rubbers
			$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->racketmanager_rubbers} WHERE `match_id` IN ( SELECT `id` from {$wpdb->racketmanager_matches} WHERE `league_id` = '%d')", $league_id) );
			$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->racketmanager_matches} WHERE `league_id` = '%d'", $league_id) );

			$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->racketmanager} WHERE `id` = '%d'", $league_id) );

		}

		$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->racketmanager_team_competition} WHERE `competition_id` = '%d'", $competition_id) );
		$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->racketmanager_competitions_seasons} WHERE `competition_id` = '%d'", $competition_id) );
		$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->racketmanager_competitions} WHERE `id` = '%d'", $competition_id) );

		$this->deleteCompetitionPages($competition->name);

		$this->setMessage( __('Competition deleted', 'racketmanager') );
		return true;
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
		$matchCount = $racketmanager->getMatches(array('count' => true, 'competition_id' => $competition->id, 'season' => $season, 'time' => 'latest'));

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
	* @param string $title
	* @return int
	*/
	private function updateTable( $tableId, $leagueId, $teamId, $season , $rank, $status, $profile ) {
		global $wpdb, $racketmanager;

		$sql = "UPDATE {$wpdb->racketmanager_table} SET `league_id` = '%d', `rank` = '%d', `status` = '%s', `profile` = '%d' WHERE `id` = '%d'";
		$wpdb->query( $wpdb->prepare ( $sql, $leagueId, $rank, $status, $profile, $tableId ) );
		$this->setMessage( __('Updated', 'racketmanager') );
		return;
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

		$settings = array();
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
		if ( !$num_match_days ) {
			if ( $competition->competitiontype == 'cup' || $competition->competitiontype == 'tournament' ) {
				$options = $racketmanager->getOptions();
				$rm_options = $options['championship'];
				$num_match_days = isset($rm_options['numRounds']) ? $rm_options['numRounds'] : 0;
			}
		}

		if ( !$num_match_days ) {
			$this->setMessage( 'Number of match days not specified','racketmanager', 'error' );
			return false;
		}

		if ( $competition->seasons == '' ) {
			$competition->seasons = array();
		}
		$competition->seasons[$season] = array( 'name' => $season, 'num_match_days' => $num_match_days );
		ksort($competition->seasons);
		$this->saveCompetitionSeasons($competition->seasons, $competition->id);

		if ( $competition->competitiontype == 'league' ) {
			$emailAddr = $racketmanager->getConfirmationEmail($competition->competitiontype);
			$organisationName = $racketmanager->site_name;
			$messageArgs = array();
			$messageArgs['competition'] = $competition->name;
			$messageArgs['emailfrom'] = $emailAddr;
			$emailMessage = racketmanager_constitution_notification($competition->id, $messageArgs );
			$headers = array();
			$headers[] = 'From: '.ucfirst($competition->competitiontype).' Secretary <'.$emailAddr.'>';
			$headers[] = 'cc: '.ucfirst($competition->competitiontype).' Secretary <'.$emailAddr.'>';
			$subject = $organisationName." - ".$competition->name." ".$season." - Constitution";
			$racketmanager->lm_mail($emailAddr, $subject, $emailMessage, $headers);
			$teams = $competition->getTeams( array('status' => 3) );
			foreach ($teams as $team) {
				$this->delTeamFromLeague($team->teamId, $team->leagueId, $season);
			}
		}

		$this->setMessage( sprintf(__('Season <strong>%s</strong> added','racketmanager'), $season ) );

		return true;
	}

	/**
	* edit season in competition
	*
	* @param int $season_id
	* @param string $season
	* @param int $competition_id
	* @param array $matchDate
	* @param boolean $homeAway
	* @return boolean
	*/
	private function editSeason( $season, $num_match_days, $competition_id, $matchDates=false, $homeAway=true ) {
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

			$competition->seasons[$season] = array( 'name' => $season, 'num_match_days' => $num_match_days, 'matchDates' => $matchDates, 'homeAway' => $homeAway );
			ksort($competition->seasons);
			$this->saveCompetitionSeasons($competition->seasons, $competition->id);

			$this->setMessage( sprintf(__('Season <strong>%s</strong> saved','racketmanager'), $season ) );
		}
		return;
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

		foreach ( $seasons AS $season ) {

			foreach ( $competition->getLeagues() AS $league ) {

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

	/************
	*
	*   LEAGUE SECTION
	*
	*
	*/

	/**
	* add new League
	*
	* @param string $title
	* @param int $competition_id
	* @return boolean
	*/
	private function addLeague( $title, $competition_id = false ) {
		global $wpdb;

		if ( !current_user_can('edit_leagues') ) {
			$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
			return false;
		}

		$settings = array();
		$wpdb->query( $wpdb->prepare ( "INSERT INTO {$wpdb->racketmanager} (title, competition_id, settings, seasons) VALUES ('%s', '%d', '%s', '%s')", $title, $competition_id, maybe_serialize($settings), '') );
		$this->setMessage( __('League added', 'racketmanager') );

		return true;
	}

	/**
	* edit League
	*
	* @param int $league_id
	* @param string $title
	* @param array $competition_id
	* @return boolean
	*/
	private function editLeague( $league_id, $title, $competition_id )
	{
		global $wpdb;

		if ( !current_user_can('edit_leagues') ) {
			$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
			return false;
		}

		$wpdb->query( $wpdb->prepare ( "UPDATE {$wpdb->racketmanager} SET `title` = '%s', `competition_id` = '%d' WHERE `id` = '%d'", $title, intval($competition_id), $league_id ) );
		$this->setMessage( __('League Updated', 'racketmanager') );

		return true;
	}

	/**
	* delete League
	*
	* @param int $league_id
	* @return boolean
	*/
	private function delLeague( $league_id ) {
		global $wpdb, $racketmanager;

		if ( !current_user_can('del_leagues') ) {
			$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
			return false;
		}

		// remove tables
		$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->racketmanager_table} WHERE `league_id` = '%d'", $league_id) );
		// remove matches and rubbers
		$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->racketmanager_rubbers} WHERE `match_id` IN ( SELECT `id` from {$wpdb->racketmanager_matches} WHERE `league_id` = '%d')", $league_id) );
		$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->racketmanager_matches} WHERE `league_id` = '%d'", $league_id) );

		$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->racketmanager} WHERE `id` = '%d'", $league_id) );

		return true;
	}

	/************
	*
	*   CLUB SECTION
	*
	*
	*/
	/**
	* add club
	*
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
	private function addClub( $name, $type, $shortcode, $contactno, $website, $founded, $facilities, $address, $latitude, $longitude ) {
		global $wpdb, $racketmanager;

		if ( !current_user_can('edit_teams') ) {
			$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
			return false;
		}

		$wpdb->query( $wpdb->prepare ( "INSERT INTO {$wpdb->racketmanager_clubs} (`name`, `type`, `shortcode`, `contactno`, `website`, `founded`, `facilities`, `address`, `latitude`, `longitude`) VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s','%s' )", $name, $type, $shortcode, $contactno, $website, $founded, $facilities, $address, $latitude, $longitude ) );

		$this->setMessage( __('Club added','racketmanager') );

		return true;
	}

	/**
	* edit club
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
	private function editClub( $club_id, $name, $type, $shortcode, $matchsecretary, $matchSecretaryContactNo, $matchSecretaryEmail, $contactno, $website, $founded, $facilities, $address, $latitude, $longitude ) {

		if ( !current_user_can('edit_teams') ) {
			$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
			return false;
		}

		$this->updateClub( $club_id, $name, $type, $shortcode, $matchsecretary, $matchSecretaryContactNo, $matchSecretaryEmail, $contactno, $website, $founded, $facilities, $address, $latitude, $longitude );

		$this->setMessage( __('Club updated','racketmanager') );

		return true;
	}

	/**
	* delete Club
	*
	* @param int $club_id
	* @return boolean
	*/
	private function delClub( $club_id ) {
		global $wpdb, $club;

		if ( !current_user_can('del_teams') ) {
			$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
			return false;
		}
		$teams = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->racketmanager_teams} WHERE `affiliatedclub` = '%d'", $club_id) );
		if ( !empty($teams) ) {
			$this->setMessage( __('Unable to delete club - still has teams attached','racketmanager') );
		} else {
			$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->racketmanager_roster_requests} WHERE `affiliatedclub` = '%d'", $club_id) );
			$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->racketmanager_roster} WHERE `affiliatedclub` = '%d'", $club_id) );
			$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->racketmanager_clubs} WHERE `id` = '%d'", $club_id) );
			$this->setMessage( __('Club Deleted','racketmanager') );

			return true;
		}
	}

	/************
	*
	*   TEAM SECTION
	*
	*
	*/

	/**
	* add new table entry
	*
	* @param int $league_id
	* @param string $team_id
	* @param string $season
	* @param array $custom
	* @param boolean $message (optional)
	* @return int | false
	*/
	private function addTableEntry( $leagueId, $teamId, $season , $custom = array(), $message = true ) {
		global $wpdb, $racketmanager;

		if ( !current_user_can('edit_teams') ) {
			$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
			return false;
		}

		$tableId = $racketmanager->addTeamtoTable( $leagueId, $teamId, $season , $custom = array(), $message = true );
		return $tableId;
	}

	/**
	* add new team
	*
	* @param string $title
	* @param int $affiliatedclub
	* @param string $stadium
	* @return int | false
	*/
	private function addTeam( $affiliatedclub, $team_type ) {
		global $wpdb;

		if ( !current_user_can('edit_teams') ) {
			$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
			return false;
		}

		$sql = $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->racketmanager_teams} WHERE `affiliatedclub` = %d AND `type` = '%s' AND `status` != 'P'",$affiliatedclub, $team_type);
		$count = $wpdb->get_var($sql);
		$count ++;

		switch (substr($team_type,0,1)) {
			case 'W': $type = 'Ladies'; break;
			case 'M': $type = 'Mens';break;
			case 'X': $type = 'Mixed';break;
			default: $type= 'error';break;
		}

		if ( $type == 'error' ) {
			$this->setMessage( __('Type not selected','racketmanager'), 'error' );
			return false;
		}
		$club = get_club($affiliatedclub);
		$title = $club->shortcode.' '.$type.' '.$count;
		$stadium = $club->name;

		$sql = "INSERT INTO {$wpdb->racketmanager_teams} (`title`, `stadium`, `affiliatedclub`, `type`) VALUES ('%s', '%s', '%d', '%s')";
		$wpdb->query( $wpdb->prepare ( $sql, $title, $stadium, $affiliatedclub, $team_type) );
		$team_id = $wpdb->insert_id;

		$this->setMessage( __('Team added','racketmanager') );

		return $team_id;
	}

	/**
	* add new team to League
	*
	* @param string $title
	* @param int $affiliatedclub
	* @param string $stadium
	* @param string $captain
	* @param string $contactno
	* @param string $contactemail
	* @param int $matchday
	* @param int $matchtime
	* @param int $home 1 | 0
	* @param int|array $roster
	* @param int $profile
	* @param array $custom
	* @param int $league_id
	* @param boolean $message (optional)
	* @return int | false
	*/
	private function addNewTeamToLeague( $league_id, $affiliatedclub, $team_type, $captain = false, $contactno = false, $contactemail = false, $matchday = false, $matchtime = false, $home = '', $roster = '', $profile = '', $custom = '', $message = true ) {
		global $wpdb, $racketmanager;

		if ( !current_user_can('edit_teams') ) {
			$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
			return false;
		}

		$team_id = $this->addTeam( $affiliatedclub, $team_type );

		$league = get_league($league_id);
		$team_competition_id = $racketmanager->addTeamCompetition( $team_id, $league->competition_id, $captain, $contactno, $contactemail, $matchday, $matchtime );

		if ( $message ) {
			$this->setMessage( __('Team added','racketmanager') );
		}

		return $team_id;
	}

	/**
	* edit team
	*
	* @param int $team_id
	* @param string $title
	* @param string $captain
	* @param string $contactno
	* @param string $contactemail
	* @param int $affiliatedclub
	* @param int $home 1 | 0
	* @param mixed $group
	* @param int|array $roster
	* @param int $profile
	* @param array $custom
	* @param int $league_id
	* @return boolean
	*/
	private function editTeam( $team_id, $title, $affiliatedclub, $team_type, $captain = false, $contactno = false, $contactemail = false, $matchday = false, $matchtime = false, $home = false, $group = false, $roster = false, $profile = false, $custom = false, $league_id = false ) {
		global $wpdb;

		if ( !current_user_can('edit_teams') ) {
			$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
			return false;
		}

		if ( !$league_id ) {
		} else {
			$league = get_league($league_id);
			if ( $team_type != $league->type ) {
				if ( $team_type == 'XD' && $league->type == 'LD' ) {
				} else {
					$this->setMessage( __('Team type does not match league type', 'racketmanager'), true );
					return false;
				}
			}
			$this->setTeamCompetition($team_id, $league->competition_id, $captain, $contactno, $contactemail, $matchday, $matchtime);
		}

		$club = get_club($affiliatedclub);
		$stadium = $club->name;
		$wpdb->query( $wpdb->prepare ( "UPDATE {$wpdb->racketmanager_teams} SET `title` = '%s', `affiliatedclub` = '%d', `stadium` = '%s', `home` = '%d', `roster`= '%s', `profile` = '%d', `custom` = '%s', `type` = '%s' WHERE `id` = %d", $title, $affiliatedclub, $stadium, $home, maybe_serialize($roster), $profile, maybe_serialize($custom), $team_type, $team_id ) );

		$this->setMessage( __('Team updated', 'racketmanager') );

		return true;
	}

	/**
	* delete Team
	*
	* @param int $team_id
	* @return boolean
	*/
	private function delTeam( $team_id ) {
		global $wpdb;

		if ( !current_user_can('del_teams') ) {
			$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
			return false;
		}

		$team = get_team( $team_id );

		$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->racketmanager_rubbers} WHERE `match_id` in (select `id` from {$wpdb->racketmanager_matches} WHERE `home_team` = '%d' OR `away_team` = '%d')", $team_id, $team_id) );
		$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->racketmanager_matches} WHERE `home_team` = '%d' OR `away_team` = '%d'", $team_id, $team_id) );
		$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->racketmanager_team_competition} WHERE `team_id` = '%d'", $team_id) );
		$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->racketmanager_teams} WHERE `id` = '%d'", $team_id) );

		return true;
	}

	/**
	* delete Team from League
	*
	* @param int $team_id
	* @return boolean
	*/
	private function delTeamFromLeague( $team_id, $league_id, $season ) {
		global $wpdb;

		if ( !current_user_can('del_teams') ) {
			$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
			return false;
		}

		$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->racketmanager_rubbers} WHERE `match_id` in (select `id` from {$wpdb->racketmanager_matches} WHERE `season` = '%d' AND `league_id` = '%d' AND (`home_team` = '%d' OR `away_team` = '%d'))", $season, $league_id, $team_id, $team_id) );
		$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->racketmanager_matches} WHERE `season` = '%d' AND `league_id` = '%d' AND (`home_team` = '%d' OR `away_team` = '%d')", $season, $league_id, $team_id, $team_id) );
		$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->racketmanager_table} WHERE `team_id` = '%d' AND `league_id` = '%d' and `season` = '%s'", $team_id, $league_id, $season) );

		$this->setMessage( __('Team Deleted','racketmanager') );

		return true;
	}

	/**
	* set Team Competition
	*
	* @param int $teamId
	* @param int $competitionId
	* @param string $captain
	* @param string $contactno
	* @param string $contactemail
	* @param int $matchday
	* @param int $matchtime
	* @return boolean
	*/
	private function setTeamCompetition( $teamId, $competitionId, $captain = NULL, $contactNo = NULL, $contactEmail = NULL , $matchDay = NULL, $matchTime = NULL) {
		global $wpdb, $racketmanager;

		if ( !current_user_can('edit_teams') ) {
			$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
			return false;
		}

		$team_competition = $wpdb->get_results( $wpdb->prepare("SELECT `id` FROM {$wpdb->racketmanager_team_competition} WHERE `team_id` = '%d' AND `competition_id` = '%d'", $teamId, $competitionId) );
		if (!isset($team_competition[0])) {
			$racketmanager->addTeamCompetition( $teamId, $competitionId, $captain, $contactNo, $contactEmail, $matchDay, $matchTime );
		} else {
			if ( isset($captain) ) {
				$wpdb->query( $wpdb->prepare ( "UPDATE {$wpdb->racketmanager_team_competition} SET `captain` = '%s', `match_day` = '%s', `match_time` = '%s' WHERE `team_id` = %d AND `competition_id` = %d", $captain, $matchDay, $matchTime, $teamId, $competitionId ) );
				$racketmanager->updatePlayerDetails($captain,$contactNo,$contactEmail);
			}
		}

		return true;
	}

	/**
	* add new team of players
	*
	* @param string $player1
	* @param string $player1Id
	* @param string $player2
	* @param string $player2Id
	* @param string $contactno
	* @param string $contactemail
	* @param int $affiliatedclub
	* @param boolean $message (optional)
	* @return int | false
	*/
	private function addTeamPlayer( $player1, $player1Id, $player2, $player2Id, $contactno, $contactemail, $affiliatedclub, $league_id, $message = true ) {
		global $wpdb, $racketmanager;

		if ( !current_user_can('edit_teams') ) {
			$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
			return false;
		}

		$team_id = $racketmanager->addPlayerTeam( $player1, $player1Id, $player2, $player2Id, $contactno, $contactemail, $affiliatedclub, $league_id );
		if ( $message )
		$this->setMessage( __('Player Team added','racketmanager') );

		return $team_id;
	}

	/**
	* edit team of players
	*
	* @param int $team_id
	* @param string $player1
	* @param int $player1Id
	* @param string $player2
	* @param int $player2Id
	* @param string $contactno
	* @param string $contactemail
	* @param int $affiliatedclub
	* @param int $league_id
	* @return boolean
	*/
	private function editTeamPlayer( $team_id, $player1, $player1Id, $player2, $player2Id, $contactno, $contactemail, $affiliatedclub, $league_id ) {
		global $wpdb, $racketmanager;

		if ( !current_user_can('edit_teams') ) {
			$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
			return false;
		}

		$league = get_league($league_id);

		if ( $player2Id == 0 ) {
			$title = $player1;
			$roster = array($player1Id);
		} else {
			$title = $player1.' / '.$player2;
			$roster = array($player1Id, $player2Id);
		}

		$wpdb->query( $wpdb->prepare ( "UPDATE {$wpdb->racketmanager_teams} SET `title` = '%s', `affiliatedclub` = '%d', `roster` = '%s' WHERE `id` = %d", $title, $affiliatedclub, maybe_serialize($roster), $team_id ) );

		$team_competition = $wpdb->get_results( $wpdb->prepare("SELECT `id` FROM {$wpdb->racketmanager_team_competition} WHERE `team_id` = '%d' AND `competition_id` = '%d'", $team_id, $league->competition_id) );
		$captain = $racketmanager->getRosterEntry($player1Id)->player_id;
		if (!isset($team_competition[0])) {
			$racketmanager->addTeamCompetition( $team_id, $league->competition_id, $captain, $contactno, $contactemail );
		} else {
			$wpdb->query( $wpdb->prepare ( "UPDATE {$wpdb->racketmanager_team_competition} SET `captain` = '%s' WHERE `team_id` = %d AND `competition_id` = %d", $captain, $team_id, $league->competition_id ) );
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

		}

		$this->setMessage( __('Team updated','racketmanager') );

		return true;
	}

	/************
	*
	*   TOURNAMENT SECTION
	*
	*
	*/
	/**
	* add tournament
	*
	* @param string $name
	* @param string $type
	* @param string $season
	* @param int $venue
	* @param int $tournamentSecretary
	* @param string $tournamentSecretaryContactNo
	* @param string $tournamentSecretaryEmail
	* @param string $date
	* @param string $closingdate
	* @return boolean
	*/
	private function addTournament( $name, $type, $season, $venue, $date, $closingdate, $tournamentSecretaryContactName, $tournamentSecretary, $tournamentSecretaryContactNo, $tournamentSecretaryEmail, $numcourts, $starttime ) {
		global $wpdb, $racketmanager;

		if ( !current_user_can('edit_teams') ) {
			$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
			return false;
		}

		$wpdb->query( $wpdb->prepare ( "INSERT INTO {$wpdb->racketmanager_tournaments} (`name`, `type`, `season`, `venue`, `tournamentsecretary`, `date`, `closingdate`, `numcourts`, `starttime` ) VALUES ('%s', '%s', '%d', '%d', '%s', '%s', '%s', %d, '%s' )", $name, $type, $season, $venue, $tournamentSecretary, $date, $closingdate, $numcourts, $starttime ) );

		$this->setMessage( __('Tournament added','racketmanager') );

		return true;
	}

	/**
	* edit tournament
	*
	* @param int $club_id
	* @param string $name
	* @param string $type
	* @param string $season
	* @param int $venue
	* @param int $tournamentSecretary
	* @param string $tournamentSecretaryContactNo
	* @param string $tournamentSecretaryEmail
	* @param string $date
	* @param string $closingdate
	* @param int $numcourts
	* @param string s$tarttime
	* @return boolean
	*/
	private function editTournament( $tournament_id, $name, $type, $season, $venue, $date, $closingdate, $tournamentSecretaryContactName, $tournamentSecretary, $tournamentSecretaryContactNo, $tournamentSecretaryEmail, $numcourts, $starttime ) {
		global $wpdb;

		if ( !current_user_can('edit_teams') ) {
			$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
			return false;
		}

		$wpdb->query( $wpdb->prepare ( "UPDATE {$wpdb->racketmanager_tournaments} SET `name` = '%s', `type` = '%s', `season` = '%s', `venue` = '%d',`tournamentsecretary` = '%d', `date` = '%s', `closingdate` = '%s', `numcourts` = %d, `starttime` = '%s' WHERE `id` = %d", $name, $type, $season, $venue, $tournamentSecretary, $date, $closingdate, $numcourts, $starttime, $tournament_id ) );

		if ( $tournamentSecretary != '') {
			$currentContactNo = get_user_meta( $tournamentSecretary, 'contactno', true);
			$currentContactEmail = get_userdata($tournamentSecretary)->user_email;
			if ($currentContactNo != $tournamentSecretaryContactNo ) {
				update_user_meta( $tournamentSecretary, 'contactno', $tournamentSecretaryContactNo );
			}
			if ($currentContactEmail != $tournamentSecretaryEmail ) {
				$userdata = array();
				$userdata['ID'] = $tournamentSecretary;
				$userdata['user_email'] = $tournamentSecretaryEmail;
				$userId = wp_update_user( $userdata );
				if ( is_wp_error($userId) ) {
					$error_msg = $userId->get_error_message();
					error_log('Unable to update user email '.$tournamentSecretary.' - '.$tournamentSecretaryEmail.' - '.$error_msg);
				}
			}
		}

		$this->setMessage( __('Tournament updated','racketmanager') );

		return true;
	}

	/**
	* delete Tournament
	*
	* @param int $club_id
	* @return boolean
	*/
	private function delTournament( $tournament_id ) {
		global $wpdb;

		if ( !current_user_can('del_teams') ) {
			$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
			return false;
		}

		$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->racketmanager_tournaments} WHERE `id` = '%d'", $tournament_id) );
		$this->setMessage( __('Tournament Deleted','racketmanager') );

		return true;
	}


	/**
	* display dropdon menu of teams (cleaned from double entries)
	*
	*/
	private function teamsDropdownCleaned() {
		global $wpdb;
		$all_teams = $wpdb->get_results( "SELECT `title`, `id` FROM {$wpdb->racketmanager_teams} WHERE `status` != 'P' ORDER BY `title` ASC" );
		$teams = array();
		foreach ( $all_teams AS $team ) {
			if ( !in_array($team->title, $teams) )
			$teams[$team->id] = htmlspecialchars(stripslashes($team->title), ENT_QUOTES);
		}
		foreach ( $teams AS $team_id => $name )
		echo "<option value='".$team_id."'>".$name."</option>";
	}

	/**
	* display dropdon menu of team players (cleaned from double entries)
	*
	*/
	function teamPlayersDropdownCleaned() {
		global $wpdb;
		$all_teams = $wpdb->get_results( "SELECT `title`, `id` FROM {$wpdb->racketmanager_teams} WHERE `status` = 'P' ORDER BY `title` ASC" );
		$teams = array();
		foreach ( $all_teams AS $team ) {
			if ( !in_array($team->title, $teams) )
			$teams[$team->id] = htmlspecialchars(stripslashes($team->title), ENT_QUOTES);
		}
		foreach ( $teams AS $team_id => $name )
		echo "<option value='".$team_id."'>".$name."</option>";
	}

	/************
	*
	*   MATCH SECTION
	*
	*
	*/

	/**
	* add Match
	*
	* @param string $date
	* @param int $home_team
	* @param int $away_team
	* @param int $match_day
	* @param string $location
	* @param int $league_id
	* @param mixed $season
	* @param mixed $group
	* @param string $final
	* @param array $custom
	* @param array $num_rubbers
	* @return int | false
	*/
	public function addMatch( $date, $home_team, $away_team, $match_day, $location, $league_id, $season, $group = '', $final = '', $custom = array(), $num_rubbers = 0  ) {
		global $wpdb;

		if ( !current_user_can('edit_matches') ) {
			$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
			return false;
		}

		$sql = "INSERT INTO {$wpdb->racketmanager_matches} (date, home_team, away_team, match_day, location, league_id, season, final, custom, `group`) VALUES ('%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s')";
		$wpdb->query ( $wpdb->prepare ( $sql, $date, $home_team, $away_team, $match_day, $location, $league_id, $season, $final, maybe_serialize($custom), $group ) );
		$match_id = $wpdb->insert_id;
		$league = get_league($league_id);
		if ($league->num_rubbers > 1) {
			for ($ix = 1; $ix <= $league->num_rubbers; $ix++) {
				$type = $league->competition_type;
				if ( $league->competition_type == 'MD' ) {
					$type = 'MD';
				} elseif ( $league->competition_type == 'WD' ) {
					$type = 'WD';
				} elseif ( $league->competition_type == 'XD' ) {
					$type = 'XD';
				} elseif ( $league->competition_type == 'LD' ) {
					if ( $ix == 1 ) {
						$type = 'WD';
					} elseif ( $ix == 2 ) {
						$type = 'MD';
					} elseif ( $ix == 3 ) {
						$type = 'XD';
					}
				}
				$rubber_id = $this->addRubber($date, $match_id, $ix, $type);
			}
		}

		return $match_id;
	}

	/**
	* edit Match
	*
	* @param string $date
	* @param int $home_team
	* @param int $away_team
	* @param int $match_day
	* @param string $location
	* @param int $league_id
	* @param int $match_id
	* @param mixed $group
	* @param string $final
	* @param array $custom
	* @return boolean
	*/
	private function editMatch( $date, $home_team, $away_team, $match_day, $location, $league_id, $match_id, $group, $final, $custom ) {
		global $wpdb;

		if ( !current_user_can('edit_matches') ) {
			$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
			return false;
		}

		$this->league_id = $league_id;
		$home_points = (!isset($home_points)) ? 'NULL' : $home_points;
		$away_points = (!isset($away_points)) ? 'NULL' : $away_points;

		$match = $wpdb->get_results( $wpdb->prepare("SELECT `custom` FROM {$wpdb->racketmanager_matches} WHERE `id` = '%d'", $match_id) );
		$custom = (!empty($match) ? array_merge( (array)maybe_unserialize($match[0]->custom), $custom ) : '' );
		$wpdb->query( $wpdb->prepare ( "UPDATE {$wpdb->racketmanager_matches} SET `date` = '%s', `home_team` = '%s', `away_team` = '%s', `match_day` = '%d', `location` = '%s', `league_id` = '%d', `group` = '%s', `final` = '%s', `custom` = '%s' WHERE `id` = %d", $date, $home_team, $away_team, $match_day, $location, $league_id, $group, $final, maybe_serialize($custom), $match_id ) );

		return true;
	}

	/**
	* delete Match
	*
	* @param int $match_id
	* @return boolean
	*/
	private function delMatch( $match_id ) {
		global $wpdb;

		if ( !current_user_can('del_matches') ) {
			$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
			return false;
		}

		$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->racketmanager_rubbers} WHERE `match_id` = '%d'", $match_id) );
		$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->racketmanager_matches} WHERE `id` = '%d'", $match_id) );

		return true;
	}

	/**
	* get date selection.
	*
	* @param int $day
	* @param int $month
	* @param int $year
	* @param int $index default 0
	* @return string
	*/
	public function getDateSelection( $day, $month, $year, $index = 0 ) {
		$out = '<select size="1" name="day['.$index.']" class="date">';
		$out .= "<option value='00'>".__('Day','racketmanager')."</option>";
		for ( $d = 1; $d <= 31; $d++ ) {
			$selected = ( $d == $day ) ? ' selected="selected"' : '';
			$out .= '<option value="'.str_pad($d, 2, 0, STR_PAD_LEFT).'"'.$selected.'>'.$d.'</option>';
		}
		$out .= '</select>';
		$out .= '<select size="1" name="month['.$index.']" class="date">';
		$out .= "<option value='00'>".__('Month','racketmanager')."</option>";
		foreach ( $this->getMonths() AS $key => $m ) {
			$selected = ( $key == $month ) ? ' selected="selected"' : '';
			$out .= '<option value="'.str_pad($key, 2, 0, STR_PAD_LEFT).'"'.$selected.'>'.$m.'</option>';
		}
		$out .= '</select>';
		$out .= '<select size="1" name="year['.$index.']" class="date">';
		$out .= "<option value='0000'>".__('Year','racketmanager')."</option>";
		for ( $y = date("Y")-20; $y <= date("Y")+10; $y++ ) {
			$selected =  ( $y == $year ) ? ' selected="selected"' : '';
			$out .= '<option value="'.$y.'"'.$selected.'>'.$y.'</option>';
		}
		$out .= '</select>';
		return $out;
	}

	/**
	* get months
	*
	* @param none
	* @return void
	*/
	public function getMonths() {
		$locale = get_locale();
		setlocale(LC_ALL, $locale);
		for ( $month = 1; $month <= 12; $month++ )
		$months[$month] = htmlentities( strftime( "%B", mktime( 0,0,0, $month, date("m"), date("Y") ) ) );

		return $months;
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
				$options['rosters']['rosterConfirmation'] = htmlspecialchars($_POST['rosterConfirmation']);
				$options['rosters']['rosterConfirmationEmail'] = htmlspecialchars($_POST['rosterConfirmationEmail']);
				$options['checks']['rosterLeadTime'] = htmlspecialchars($_POST['rosterLeadTime']);
				$options['checks']['playedRounds'] = htmlspecialchars($_POST['playedRounds']);
				$options['checks']['playerLocked'] = htmlspecialchars($_POST['playerLocked']);
				$competitionTypes = $this->getCompetitionTypes();
				foreach ( $competitionTypes AS $competitionType ) {
					$options[$competitionType]['matchCapability'] = htmlspecialchars($_POST[$competitionType]['matchCapability']);
					$options[$competitionType]['resultConfirmation'] = htmlspecialchars($_POST[$competitionType]['resultConfirmation']);
					$options[$competitionType]['resultEntry'] = htmlspecialchars($_POST[$competitionType]['resultEntry']);
					$options[$competitionType]['resultConfirmationEmail'] = htmlspecialchars($_POST[$competitionType]['resultConfirmationEmail']);
					$options[$competitionType]['resultNotification'] = htmlspecialchars($_POST[$competitionType]['resultNotification']);
				}
				$options['colors']['headers'] = htmlspecialchars($_POST['color_headers']);
				$options['colors']['rows'] = array( 'alternate' => htmlspecialchars($_POST['color_rows_alt']), 'main' => htmlspecialchars($_POST['color_rows']), 'ascend' => htmlspecialchars($_POST['color_rows_ascend']), 'descend' => htmlspecialchars($_POST['color_rows_descend']), 'relegation' => htmlspecialchars($_POST['color_rows_relegation']) );
				$options['colors']['boxheader'] = array(htmlspecialchars($_POST['color_boxheader1']), htmlspecialchars($_POST['color_boxheader2']));
				$options['championship']['numRounds'] = htmlspecialchars($_POST['numRounds']);

				update_option( 'leaguemanager', $options );
				$this->setMessage(__( 'Settings saved', 'racketmanager' ));
				$this->printMessage();

				// Set active tab
				$tab = intval($_POST['active-tab']);
			}

			require_once (dirname (__FILE__) . '/settings-global.php');
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
			foreach ( $leagues AS $league ) {
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
	*   PLAYERS SECTION
	*
	*
	*/

	/**
	* delete Player
	*
	* @param int $player_id
	* @return boolean
	*/
	private function delPlayer( $player_id ) {
		global $wpdb;

		if ( !current_user_can('del_teams') ) {
			$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
			return false;
		}
		$rosterCount = $wpdb->get_var("SELECT count(*) FROM {$wpdb->racketmanager_roster} WHERE `player_id` = ".$player_id);
		if ( $rosterCount == 0 ) {
			wp_delete_user( $player_id) ;
		} else {
			update_user_meta( $player_id, 'remove_date', date('Y-m-d') );
		}

		return true;
	}

	/************
	*
	*   ROSTER SECTION
	*
	*
	*/

	/**
	* delete Roster Request
	*
	* @param int $rosterRequst_id
	* @return void
	*/
	public function _approveRosterRequest( $club_id, $rosterRequestId ) {

		if ( !current_user_can('edit_teams') ) {
			$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
			return false;
		}

		$club = get_club($club_id);
		$club->approveRosterRequest( $rosterRequestId );
	}

	/**
	* delete Roster Request
	*
	* @param int $rosterRequst_id
	* @return void
	*/
	private function deleteRosterRequest( $rosterRequestId ) {
		global $wpdb;

		if ( !current_user_can('edit_teams') ) {
			$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
			return false;
		}

		$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->racketmanager_roster_requests} WHERE `id` = %d", $rosterRequestId) );
		$this->setMessage( __('Roster request deleted', 'racketmanager') );

		return true;
	}

	/**
	* import data from CSV file
	*
	* @param int $league_id
	* @param array $file CSV file
	* @param string $delimiter
	* @param array $mode 'teams' | 'matches' | 'fixtures' | 'players' | 'roster'
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
			$new_file = $this->getFilePath($file['name']);
			if ( move_uploaded_file($file['tmp_name'], $new_file) ) {
				if ( 'table' == $mode ) {
					$this->importTable($new_file, $delimiter, $league_id);
				} elseif ( 'fixtures' == $mode ) {
					$this->importFixtures($new_file, $delimiter, $league_id);
				} elseif ( 'roster' == $mode ) {
					$this->importRoster($new_file, $delimiter, $affiliatedClub);
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
			if ( "TAB" == $delimiter ) $delimiter = "\t"; // correct tabular delimiter

			$teams = $points_plus = $points_minus = $points2_plus = $points2_minus = $pld = $won = $draw = $lost = $custom = $add_points = array();

			$i = $x = 0;
			while (!feof($handle)) {
				$buffer = fgets($handle, 4096);
				$line = explode($delimiter, $buffer);

				// ignore header and empty lines
				if ( $i > 0 && count($line) > 1 ) {
					$season = $line[0];
					$team	= utf8_encode($line[1]);
					$team_id = $this->getTeamID($team);
					if ( $team_id != 0 ) {

						$tabledtls = $this->checkTableEntry( $this->league_id, $team_id, $season );
						if ( $tabledtls == 0 ) {

							$table_id = $this->addTableEntry( $this->league_id, $team_id, $season, $custom, false );

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

			$i = $x = $r = 0;
			while (!feof($handle)) {
				$buffer = fgets($handle, 4096);
				$line = explode($delimiter, $buffer);
				// ignore header and empty lines
				if ( $i > 0 && count($line) > 1 ) {
					$date = ( !empty($line[6]) ) ? $line[0]." ".$line[6] : $line[0]. " 00:00";
					$season = $this->season = isset($line[1]) ? $line[1] : '';
					$match_day = isset($line[2]) ? $line[2] : '';
					$date = trim($date);
					$home_team = $this->getTeamID(utf8_encode($line[3]));
					$away_team = $this->getTeamID(utf8_encode($line[4]));
					if ( $home_team != 0 && $away_team != 0 ) {

						$location = isset($line[5]) ? utf8_encode($line[5]) : '';
						$group = isset($line[7]) ? $line[7] : '';

						$match_id = $this->addMatch($date, $home_team, $away_team, $match_day, $location, $this->league_id, $season, $group,'', array(), $rubbers);

						$matches[$match_id] = $match_id;
						$home_teams[$match_id] = $home_team;
						$away_teams[$match_id] = $away_team;
						$home_points[$match_id] = $away_points[$match_id] = '';

						$custom = apply_filters( 'racketmanager_import_fixtures_'.$league->sport, $custom, $match_id );

					}
					$x++;
				}

				$i++;
			}

			fclose($handle);

			parent::setMessage(sprintf(__( '%d Fixtures imported', 'racketmanager' ), $x));
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

			$players = array();

			$i = $x = 0;
			while (!feof($handle)) {
				$buffer = fgets($handle, 4096);
				$line = explode($delimiter, $buffer);

				// ignore header and empty lines
				if ( $i > 0 && count($line) > 1 ) {
					$firstname	= isset($line[0]) ? utf8_encode($line[0]) : '';
					$surname	= isset($line[1]) ? utf8_encode($line[1]) : '';
					$gender		= isset($line[2]) ? utf8_encode($line[2]) : '';
					$btm		= isset($line[3]) ? utf8_encode($line[3]) : '';
					$email		= isset($line[4]) ? utf8_encode($line[4]) : '';
					if ( !username_exists($firstname.'.'.$surname) ) {
						$player_id	= $this->addPlayer( $firstname, $surname, $gender, $btm, $email, false );
						$players[$player_id] = $player_id;
						$x++;
					}
				}

				$i++;
			}

			fclose($handle);

			parent::setMessage(sprintf(__( '%d Players imported', 'racketmanager' ), $x));
		}
	}

	/**
	* import roster from file
	*
	* @param string $file
	* @param string $delimiter
	*/
	private function importRoster( $file, $delimiter, $affiliatedClub ) {
		global $racketmanager;

		if ( !current_user_can('import_leagues') ) {
			$this->setMessage( __("You don't have permission to perform this task", 'racketmanager'), true );
			return false;
		}

		$handle = @fopen($file, "r");
		if ($handle) {
			if ( "TAB" == $delimiter ) $delimiter = "\t"; // correct tabular delimiter

			$club = get_club( $affiliatedClub );
			$players = array();

			$i = $x = 0;
			while (!feof($handle)) {
				$buffer = fgets($handle, 4096);
				$line = explode($delimiter, $buffer);

				// ignore header and empty lines
				if ( $i > 0 && count($line) > 1 ) {
					$firstname	= isset($line[0]) ? utf8_encode($line[0]) : '';
					$surname	= isset($line[1]) ? utf8_encode($line[1]) : '';
					$gender		= isset($line[2]) ? utf8_encode($line[2]) : '';
					$btm		= isset($line[3]) ? utf8_encode($line[3]) : '';
					$email		= isset($line[4]) ? utf8_encode($line[4]) : '';
					if ( !username_exists($firstname.'.'.$surname) ) {
						$player_id	= $this->addPlayer( $firstname, $surname, $gender, $btm, $email, false );
						$players[$player_id] = $player_id;
					} else {
						$player_id = get_user_by('login', $firstname.'.'.$surname )->ID;
					}
					$this->addPlayerIdToRoster($affiliatedClub, $player_id);

					$x++;
				}

				$i++;
			}

			fclose($handle);

			parent::setMessage(sprintf(__( '%d Rosters imported', 'racketmanager' ), $x));
		}
	}

	private function addPlayerIdToRoster($club_id, $player_id ) {
		global $wpdb, $racketmanager;

		if (!$player_id == 0 ) {
			$rosterCount = $wpdb->get_var("SELECT count(*) FROM {$wpdb->racketmanager_roster} WHERE `player_id` = ".$player_id." AND `affiliatedclub` = ".$club_id." AND `removed_date` IS NULL");
			if ($rosterCount == 0) {
				$club = get_club($club_id);
				$roster_id	= $club->addRoster( $player_id, false );
				$roster[$roster_id] = $roster_id;
			}
		}
		$racketmanager->setMessage( __('Player added to Roster','racketmanager') );
		return;
	}

	private function addPlayerToRoster($affiliatedClub, $firstName, $surname, $gender, $btm, $email ) {
		global $wpdb, $racketmanager;

		$fullName = $firstName . ' ' . $surname;
		$player = $racketmanager->getPlayer(array('fullname' => $fullName));
		if ( !$player ) {
			$playerId = $racketmanager->addPlayer( $firstName, $surname, $gender, $btm, $email);
			$rosterFound = false;
		} else {
			$playerId = $player->ID;
			$rosterCount = $racketmanager->getRoster(array('club' => $affiliatedClub, 'player' => $playerId, 'inactive' => true, 'count' => true));
			if ( $rosterCount == 0 ) {
				$rosterFound = false;
			} else {
				$rosterFound = true;
			}
		}
		if ( $rosterFound == false ) {
			$club = get_club($affiliatedClub);
			$roster_id	= $club->addRoster( $playerId, false );
			$roster[$roster_id] = $roster_id;
			$racketmanager->setMessage( __('Player added to club','racketmanager') );
		} else {
			$racketmanager->setMessage( __('Player already registered','racketmanager'), true	 );
		}
		return;
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

		$tables = array($wpdb->racketmanager, $wpdb->racketmanager_teams, $wpdb->racketmanager_matches, $wpdb->racketmanager_rosters, $wpdb->racketmanager_rubbers);

		foreach( $tables AS $table ) {
			$results = $wpdb->get_results("SHOW COLUMNS FROM {$table}");
			$columns = array();
			foreach ( $results AS $result ) {
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
	function getLeagueDropdown( $competition_id = false ) {
		global $racketmanager;

		$competition_id = (int)$_POST['competition_id'];
		$competition = get_competition($competition_id);
		$leagues = $competition->getLeagues(); ?>

		<select size='1' name='league_id' id='league_id' class="form-select" >
			<option value='0'><?php _e('Choose league', 'racketmanager') ?></option>
			<?php foreach ( $leagues AS $league ) { ?>
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
	public function getResultsChecker( $outstanding = false ) {
		global $wpdb, $racketmanager;

		$sql = "SELECT `id`, `league_id`, `match_id`, `team_id`, `player_id`, `updated_date`, `updated_user`, `description`, `status` FROM {$wpdb->racketmanager_results_checker} WHERE 1 = 1"  ;

		if ( $outstanding ) {
			$sql .= " AND `status` IS NULL";
		}

		$sql .= " ORDER BY `match_id` DESC, `league_id` ASC, `team_id` ASC, `player_id` ASC";

		$resultsCheckers = wp_cache_get( md5($sql), 'resultsCheckers' );
		if ( !$resultsCheckers ) {
			$resultsCheckers = $wpdb->get_results( $sql );
			wp_cache_set( md5($sql), $resultsCheckers, 'resultsCheckers' );
		}

		$class = '';
		foreach ( $resultsCheckers AS $i => $resultsChecker ) {
			$class = ( 'alternate' == $class ) ? '' : 'alternate';
			$resultsChecker->class = $class;

			$resultsChecker->league = get_league($resultsChecker->league_id);
			$resultsChecker->date = get_match($resultsChecker->match_id)->date;
			$resultsChecker->match = get_match($resultsChecker->match_id);
			if ( $resultsChecker->team_id > 0 ) {
				$resultsChecker->team = get_team($resultsChecker->team_id)->title;
			} else {
				$resultsChecker->team = '';
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
	* @return array
	*/
	private function getResultsCheckerEntry( $resultsCheckerId ) {
		global $wpdb;

		$resultsChecker = $wpdb->get_row("SELECT `league_id`, `match_id`, `team_id`, `player_id`, `updated_date`, `updated_user`, `description`, `status` FROM {$wpdb->racketmanager_results_checker} WHERE `id` = '".intval($resultsCheckerId)."'");

		if ( !$resultsChecker ) return false;

		$this->resultsChecker[$resultsCheckerId] = $resultsChecker;
		return $this->resultsChecker[$resultsCheckerId];
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
	* save Tournament Plan
	*
	* @param int $tournament
	* @param int $numMatches
	* @param array $court
	* @param array $matches
	* @return void
	*/
	private function saveTournamentPlan($tournament, $numMatches, $courts, $starttimes, $matches, $matchtimes) {
		global $wpdb, $racketmanager;

		$tournament = $racketmanager->getTournament(array('id' => $tournament));
		$orderofplay = array();
		for ($i=0; $i < count($courts); $i++) {
			$orderofplay[$i]['court'] = $courts[$i];
			$orderofplay[$i]['starttime'] = $starttimes[$i];
			$orderofplay[$i]['matches'] = $matches[$i];
			for ($m=0; $m < count($matches[$i]); $m++) {
				$matchId = $matches[$i][$m];
				if ( $matchId != '' ) {
					$time = strtotime($starttimes[$i]) + $matchtimes[$i][$m];
					$match = get_match($matchId);
					$month = str_pad($match->month,2, '0', STR_PAD_LEFT);
					$day = str_pad($match->day,2, '0', STR_PAD_LEFT);
					$date = $match->year.'-'.$month.'-'.$day.' '.date('H:i', $time);
					$location = $courts[$i];
					if ( $date != $match->date || $location != $match->location ) {
						$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->racketmanager_matches} SET `date` = '%s', `location` = '%s' WHERE `id` = %d", $date, $location, $matchId) );
					}
				}
			}
		}
		if ( $orderofplay != $tournament->orderofplay ) {
			$orderofplay = maybe_serialize($orderofplay);
			wp_cache_flush();
			$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->racketmanager_tournaments} SET `orderofplay` = '%s' WHERE `id` = %d", $orderofplay, $tournament->id ) );
			$this->setMessage( __('Tournament plan updated', 'racketmanager') );
		} else {
			$this->setMessage( __('No updates', 'racketmanager') );
		}
		return true;
	}

	/**
	* update Tournament
	*
	* @param int $tournament
	* @param text $starttime
	* @param int $numcourts
	* @param text $timeincrement
	* @return void
	*/
	private function updateTournament($tournament, $starttime, $numcourts, $timeincrement) {
		global $wpdb, $racketmanager;

		$tournament = $racketmanager->getTournament(array('id' => $tournament));
		if ( $starttime != $tournament->starttime || $numcourts != $tournament->numcourts || $timeincrement != $tournament->timeincrement ) {
			wp_cache_flush();
			$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->racketmanager_tournaments} SET `starttime` = '%s', `numcourts` = %d, `timeincrement` = '%s' WHERE `id` = %d", $starttime, $numcourts, $timeincrement, $tournament->id ) );
			$this->setMessage( __('Tournament updated', 'racketmanager') );
		} else {
			$this->setMessage( __('No updates', 'racketmanager') );
		}
		return true;
	}
	/**
	* reset Tournament Plan
	*
	* @param int $tournament
	* @return void
	*/
	private function resetTournamentPlan($tournament) {
		global $wpdb, $racketmanager;

		$tournament = $racketmanager->getTournament(array('id' => $tournament));
		$orderofplay = array();
		$finalMatches = $racketmanager->getMatches( array('season' => $tournament->season, 'final' => 'final', 'competitiontype' => 'tournament', 'competitionseason' => $tournament->type));

		foreach ($finalMatches as $match) {
			$month = str_pad($match->month,2, '0', STR_PAD_LEFT);
			$day = str_pad($match->day,2, '0', STR_PAD_LEFT);
			$date = $match->year.'-'.$month.'-'.$day.' 00:00';
			$location = '';
			if ( $date != $match->date || $location != $match->location ) {
				$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->racketmanager_matches} SET `date` = '%s', `location` = '%s' WHERE `id` = %d", $date, $location, $match->id) );
			}
		}
		if ( $orderofplay != $tournament->orderofplay ) {
			$orderofplay = maybe_serialize($orderofplay);
			wp_cache_flush();
			$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->racketmanager_tournaments} SET `orderofplay` = '%s' WHERE `id` = %d", $orderofplay, $tournament->id ) );
			$this->setMessage( __('Tournament plan reset', 'racketmanager') );
		} else {
			$this->setMessage( __('No updates', 'racketmanager') );
		}
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
		$fromEmail = $this->getConfirmationEmail($league->competitionType);
		$headers[] = 'From: '.ucfirst($league->competitionType).' Secretary <'.$emailFrom.'>';
		$headers[] = 'cc: '.ucfirst($league->competitionType).' Secretary <'.$emailFrom.'>';
		$organisationName = $this->site_name;

		foreach ($teams as $team) {
			$emailSubject = $this->site_name." - ".$league->title." ".$season." - Important Message";
			$teamDtls = $league->getTeamDtls($team->id);
			$emailTo = $teamDtls->contactemail;
			if ( $emailTo ) {
				$this->lm_mail($emailTo, $emailSubject, $emailMessage, $headers);
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

		$season = $wpdb->get_var( "SELECT MAX(name) FROM {$wpdb->racketmanager_seasons}" );
		return $season;
	}

	/**
	* schedule league matches
	*
	* @param array $competitions
	* @return void
	*/
	private function scheduleLeagueMatches($competitions) {
		global $wpdb, $racketmanager;

		$error = false;

		$result = $this->validateSchedule($competitions);

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
		return;
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
		$c = 0;
		foreach ($competitions as $competitionId) {
			$competition = get_competition($competitionId);
			$season = $competition->getSeason();
			$matchCount = $racketmanager->getMatches(array('count' => true, 'competition_id' => $competition->id, 'season' => $season));
			if ( $matchCount != 0 ) {
				$success = false;
				$this->setMessage( sprintf(__('%s already has matches scheduled for %d','racketmanager'), $competition->name, $season), true );
				break;
			} else {
				if ( $c == 0 ) {
					$numMatchDays = $competition->current_season['num_match_days'];
					if ( !isset($competition->current_season['matchDates']) ) {
						$success = false;
						$this->setMessage( __('Competitions match dates not set','racketmanager'), true );
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
						$this->setMessage( __('Competitions have different number of match days','racketmanager'), true );
					}
					$homeAwayNew = isset($competition->current_season['homeAway']) ? $competition->current_season['homeAway'] : 'true' ;
					if ( $homeAwayNew != $homeAway ) {
						$success = false;
						$this->setMessage( __('Competitions have different home / away setting','racketmanager'), true );
					}
				}
			}
			$c ++;
		}

		if ( $success ) {
			$competitionIds = implode(',',$competitions);
			$season = $this->getLatestSeason();

			/* clear out schedule keys for this run */
			$wpdb->query( "UPDATE {$wpdb->racketmanager_table} SET `group` = '' WHERE `season` = $season AND `league_id` IN (SELECT `id` FROM {$wpdb->racketmanager} WHERE `competition_id` IN ($competitionIds))" );

			/* set refs for those teams in the same division so they play first */
			$sql = "SELECT `t`.`affiliatedclub`, tbl.`league_id` FROM {$wpdb->racketmanager_team_competition} tc, {$wpdb->racketmanager_teams} t, {$wpdb->racketmanager} l, {$wpdb->racketmanager_table} tbl WHERE tc.`team_id` = t.`id` AND tc.`competition_id` = l.`competition_id` AND l.`id` = tbl.`league_id` AND tbl.`team_id` = t.`id` AND tc.`competition_id` in (".$competitionIds.") AND tbl.`season` = $season GROUP BY t.`affiliatedclub`, tbl.`league_id` HAVING COUNT(*) > 1";
			$clubLeagues = $wpdb->get_results( $sql );
			foreach ($clubLeagues as $clubLeague) {
				$sql = "SELECT tbl.`id`, tbl.`team_id`, tbl.`league_id` FROM {$wpdb->racketmanager_team_competition} tc, {$wpdb->racketmanager_teams} t, {$wpdb->racketmanager} l, {$wpdb->racketmanager_table} tbl WHERE tc.`team_id` = t.`id` AND tc.`competition_id` = l.`competition_id` AND l.`id` = tbl.`league_id` AND tbl.`team_id` = t.`id` AND tc.`competition_id` in (".$competitionIds.") AND tbl.`season` = ".$season." AND t.`affiliatedclub` = ".$clubLeague->affiliatedclub." AND tbl.`league_id` = '".$clubLeague->league_id."'  ORDER BY tbl.`team_id`";
				$teams = $wpdb->get_results( $sql );
				$counter = 1;
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
								$this->setMessage( sprintf(__('Unable to schedule first round for league %d for team %d and team %d','racketmanager'), $league1, $team1, $team2), true );
							}
						} else {
							$success = false;
							$this->setMessage( sprintf(__('Error in scheduling first round for league %d for team %d and team %d','racketmanager'), $league1, $team1, $team2), true );
						}
					}
					$counter ++;
				}
			}

			/* find all clubs with multiple matches at the same time */
			$sql = "SELECT `t`.`affiliatedclub`, `tc`.`match_day`, `tc`.`match_time` FROM {$wpdb->racketmanager_team_competition} tc, {$wpdb->racketmanager_teams} t, {$wpdb->racketmanager} l, {$wpdb->racketmanager_table} tbl WHERE tc.`team_id` = t.`id` AND tc.`competition_id` = l.`competition_id` AND l.`id` = tbl.`league_id` AND tbl.`team_id` = t.`id` AND tc.`competition_id` in (".$competitionIds.") AND tbl.`season` = $season GROUP BY t.`affiliatedclub`, tc.`match_day`, tc.`match_time` HAVING COUNT(*) > 1";
			$competitionTeams = $wpdb->get_results( $sql );
			/* for each club / match time combination balance schedule so one team is home while the other is away */
			foreach ($competitionTeams as $competitionTeam) {
				$sql = "SELECT tbl.`id`, tbl.`team_id`, tbl.`league_id`, tbl.`group` FROM {$wpdb->racketmanager_team_competition} tc, {$wpdb->racketmanager_teams} t, {$wpdb->racketmanager} l, {$wpdb->racketmanager_table} tbl WHERE tc.`team_id` = t.`id` AND tc.`competition_id` = l.`competition_id` AND l.`id` = tbl.`league_id` AND tbl.`team_id` = t.`id` AND tc.`competition_id` in (".$competitionIds.") AND tbl.`season` = ".$season." AND t.`affiliatedclub` = ".$competitionTeam->affiliatedclub." AND tc.`match_day` = '".$competitionTeam->match_day."' AND tc.`match_time` = '".$competitionTeam->match_time."' ORDER BY tbl.`group`, tbl.`team_id`";
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
						$team2 = $team->team_id;
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
									$this->setMessage( sprintf(__('1 - Error in scheduling league %d for team %d','racketmanager'), $league1, $team1), true );
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
										$this->setMessage( sprintf(__('4 - Error in scheduling league %d for team %d','racketmanager'), $league1, $team1), true );
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
										$this->setMessage( sprintf(__('2 - Error in scheduling league %d for team %d','racketmanager'), $league1, $team1), true );
										debug_to_console($refs);
										debug_to_console($altRefs);
									}
								}
							}
						} else {
							$success = false;
							$this->setMessage( sprintf(__('3 - Error in scheduling league %d for team %d','racketmanager'), $league1, $team1), true );
						}
					}
					$counter ++;
				}
			}
		}
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

		$groups = $wpdb->get_results( "SELECT `group` as `value` FROM {$wpdb->racketmanager_table} WHERE `league_id` = $league AND `season` = $season AND `group` != ''");
		return $groups;
	}

}
?>
