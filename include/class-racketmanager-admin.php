<?php
/**
 * RacketManager-Admin API: RacketManager-admin class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManager-Admin
 */

namespace Racketmanager;

/**
 * RacketManager administration functions
 * Class to implement RacketManager Administration panel
 *
 * @author Kolja Schleich
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManagerAdmin
 */
final class RacketManager_Admin extends RacketManager {

	/**
	 * League_id the id of the current league.
	 *
	 * @var $league_id
	 */
	private $league_id;
	/**
	 * Constructor
	 */
	public function __construct() {
		global $racketmanager_ajax_admin;
		parent::__construct();

		require_once ABSPATH . 'wp-admin/includes/template.php';

		require_once RACKETMANAGER_PATH . 'include/class-racketmanager-ajax-admin.php';
		$racketmanager_ajax_admin = new Racketmanager_Ajax_Admin();

		add_action( 'admin_enqueue_scripts', array( &$this, 'loadScripts' ) );
		add_action( 'admin_enqueue_scripts', array( &$this, 'loadStyles' ) );

		add_action( 'admin_menu', array( &$this, 'menu' ) );
		add_action( 'admin_footer', array( &$this, 'scroll_top' ) );

		add_action( 'show_user_profile', array( &$this, 'custom_user_profile_fields' ) );
		add_action( 'edit_user_profile', array( &$this, 'custom_user_profile_fields' ) );
		add_action( 'personal_options_update', array( &$this, 'update_extra_profile_fields' ) );
		add_action( 'edit_user_profile_update', array( &$this, 'update_extra_profile_fields' ) );

		// Add meta box to post screen.
		add_action( 'publish_post', array( &$this, 'edit_match_report' ) );
		add_action( 'edit_post', array( &$this, 'edit_match_report' ) );
		add_action( 'add_meta_boxes', array( &$this, 'metaboxes' ) );
		add_action( 'wp_ajax_racketmanager_get_event_dropdown', array( &$this, 'get_event_dropdown' ) );
		add_action( 'wp_ajax_racketmanager_get_league_dropdown', array( &$this, 'get_league_dropdown' ) );
	}

	/**
	 * Adds menu to the admin interface
	 */
	public function menu() {
		// keep capabilities here for next update.
		$page = add_menu_page(
			__( 'RacketManager', 'racketmanager' ),
			__( 'RacketManager', 'racketmanager' ),
			'racket_manager',
			'racketmanager',
			array( &$this, 'display' ),
			'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iaXNvLTg4NTktMSI/Pg0KPCEtLSBHZW5lcmF0b3I6IEFkb2JlIElsbHVzdHJhdG9yIDE3LjEuMCwgU1ZHIEV4cG9ydCBQbHVnLUluIC4gU1ZHIFZlcnNpb246IDYuMDAgQnVpbGQgMCkgIC0tPg0KPCFET0NUWVBFIHN2ZyBQVUJMSUMgIi0vL1czQy8vRFREIFNWRyAxLjEvL0VOIiAiaHR0cDovL3d3dy53My5vcmcvR3JhcGhpY3MvU1ZHLzEuMS9EVEQvc3ZnMTEuZHRkIj4NCjxzdmcgdmVyc2lvbj0iMS4xIiBpZD0iQ2FwYV8xIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiB4PSIwcHgiIHk9IjBweCINCgkgdmlld0JveD0iMCAwIDQxMi40MjUgNDEyLjQyNSIgc3R5bGU9ImVuYWJsZS1iYWNrZ3JvdW5kOm5ldyAwIDAgNDEyLjQyNSA0MTIuNDI1OyIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSI+DQo8cGF0aCBkPSJNNDEyLjQyNSwxMDguOTMzYzAtMzAuNTI5LTEwLjk0MS01OC4xOC0zMC44MDgtNzcuODZDMzYxLjc3NiwxMS40MTgsMzMzLjkxLDAuNTkzLDMwMy4xNTMsMC41OTMNCgljLTQxLjMsMC04My45MTMsMTguNzQ5LTExNi45MTMsNTEuNDM4Yy0zMC4zMTksMzAuMDM0LTQ4Ljc1NCw2OC4xMTUtNTEuNTczLDEwNS44NThjLTAuODQ1LDUuMzk4LTEuNjM0LDExLjEzLTIuNDYyLDE3LjE4OA0KCWMtNC43NDQsMzQuNjg2LTEwLjYwMyw3Ny40MTUtMzQuMDQ5LDEwNC41MDNjLTIuMDYsMC4zMzMtMy45ODEsMS4yOTUtNS40NzYsMi43ODlMNy42MDMsMzY3LjQ0Nw0KCWMtMTAuMTM3LDEwLjEzOC0xMC4xMzcsMjYuNjMyLDAsMzYuNzdjNC45MTEsNC45MTEsMTEuNDQsNy42MTUsMTguMzg1LDcuNjE1czEzLjQ3NC0yLjcwNSwxOC4zODYtNy42MTdsODUuMDYtODUuMDk1DQoJYzEuNTM1LTEuNTM2LDIuNDU3LTMuNDQ4LDIuNzg0LTUuNDM4YzI3LjA4Ny0yMy40NjEsNjkuODI5LTI5LjMyMiwxMDQuNTI0LTM0LjA2OGM2LjU0OS0wLjg5NiwxMi43MzQtMS43NDEsMTguNTA4LTIuNjY2DQoJYzEuNDM0LTAuMjMsMi43NDMtMC43NiwzLjg4NS0xLjUwN2MzNi4yNTMtNC4wNDcsNzIuNDY0LTIxLjk3MiwxMDEuMzI1LTUwLjU2MkMzOTMuNDg1LDE5Mi4xNjYsNDEyLjQyNSwxNDkuOTA1LDQxMi40MjUsMTA4LjkzM3oNCgkgTTE0NS40NzYsMjE4LjM0OWM0Ljk4NCwxMC4yNDQsMTEuNTY0LDE5LjUyMSwxOS42MDgsMjcuNDljOC41MTQsOC40MzQsMTguNTEsMTUuMjM3LDI5LjU3NiwyMC4yNjINCgljLTI1Ljg0Niw1LjIzOC01Mi43NjksMTMuODIzLTczLjQxNSwzMC42OTJsLTYuMjE2LTYuMjE2QzEzMS42MzksMjcwLjI0NiwxNDAuMjE3LDI0My44MzEsMTQ1LjQ3NiwyMTguMzQ5eiBNMzAuMjMsMzkwLjA3NQ0KCWMtMS4xMzMsMS4xMzMtMi42NCwxLjc1Ny00LjI0MiwxLjc1N2MtMS42MDMsMC0zLjEwOS0wLjYyNC00LjI0My0xLjc1N2MtMi4zMzktMi4zMzktMi4zMzktNi4xNDYsMC04LjQ4NWw3OC4wMDYtNzguMDA3DQoJbDguNDY5LDguNDY5TDMwLjIzLDM5MC4wNzV6IE0yNDMuNTU5LDI1Ni4zMThjLTAuMDAyLDAtMC4wMDgsMC0wLjAxMSwwYy0yNS44MjItMC4wMDMtNDguMDg3LTguNTQtNjQuMzg5LTI0LjY4OA0KCWMtMTYuMjc5LTE2LjEyNi0yNC44ODMtMzguMTM2LTI0Ljg4My02My42NTJjMC0yLjU5NiwwLjEtNS4yMDEsMC4yNzYtNy44MDhjMC4wMjMtMC4xNDMsMC4wNDUtMC4yOTUsMC4wNjgtMC40MzgNCgljMC4xMS0wLjY4NSwwLjE0Ny0xLjM2NCwwLjExNy0yLjAzMWMyLjg3LTMyLjQyMiwxOS4xMjEtNjUuMjUzLDQ1LjU3OS05MS40NjFjMjkuMjg0LTI5LjAwOSw2Ni43NjctNDUuNjQ2LDEwMi44MzctNDUuNjQ2DQoJYzI1LjgxOSwwLDQ4LjA4NSw4LjUzNyw2NC4zODksMjQuNjg5YzE2LjI3OSwxNi4xMjYsMjQuODgzLDM4LjEzNiwyNC44ODMsNjMuNjUxYy0wLjAwMSwzNS42NzItMTYuNzgxLDcyLjc1NS00Ni4wNCwxMDEuNzM5DQoJQzMxNy4xLDIzOS42ODIsMjc5LjYyNCwyNTYuMzE5LDI0My41NTksMjU2LjMxOHoiLz4NCjxnPg0KPC9nPg0KPGc+DQo8L2c+DQo8Zz4NCjwvZz4NCjxnPg0KPC9nPg0KPGc+DQo8L2c+DQo8Zz4NCjwvZz4NCjxnPg0KPC9nPg0KPGc+DQo8L2c+DQo8Zz4NCjwvZz4NCjxnPg0KPC9nPg0KPGc+DQo8L2c+DQo8Zz4NCjwvZz4NCjxnPg0KPC9nPg0KPGc+DQo8L2c+DQo8Zz4NCjwvZz4NCjwvc3ZnPg0K',
			2
		);
		add_action( "admin_print_scripts-$page", array( &$this, 'loadScripts' ) );
		add_action( "admin_print_scripts-$page", array( &$this, 'loadStyles' ) );

		$page = add_submenu_page(
			'racketmanager', // parent page.
			__( 'RacketManager', 'racketmanager' ), // page title.
			__( 'Competitions', 'racketmanager' ), // menu title.
			'racket_manager', // capability.
			'racketmanager', // menu slug.
			array( &$this, 'display' )
		);
		add_action( "admin_print_scripts-$page", array( &$this, 'loadScripts' ) );
		add_action( "admin_print_scripts-$page", array( &$this, 'loadStyles' ) );

		$page = add_submenu_page(
			'racketmanager',
			__( 'Leagues', 'racketmanager' ),
			__( 'Leagues', 'racketmanager' ),
			'racket_manager',
			'racketmanager-leagues',
			array( &$this, 'display' )
		);
		add_action( "admin_print_scripts-$page", array( &$this, 'loadScripts' ) );
		add_action( "admin_print_scripts-$page", array( &$this, 'loadStyles' ) );

		$page = add_submenu_page(
			'racketmanager',
			__( 'Cups', 'racketmanager' ),
			__( 'Cups', 'racketmanager' ),
			'racket_manager',
			'racketmanager-cups',
			array( &$this, 'display' )
		);
		add_action( "admin_print_scripts-$page", array( &$this, 'loadScripts' ) );
		add_action( "admin_print_scripts-$page", array( &$this, 'loadStyles' ) );

		$page = add_submenu_page(
			'racketmanager',
			__( 'Tournaments', 'racketmanager' ),
			__( 'Tournaments', 'racketmanager' ),
			'racket_manager',
			'racketmanager-tournaments',
			array( &$this, 'display' )
		);
		add_action( "admin_print_scripts-$page", array( &$this, 'loadScripts' ) );
		add_action( "admin_print_scripts-$page", array( &$this, 'loadStyles' ) );

		$page = add_submenu_page(
			'racketmanager',
			__( 'Clubs', 'racketmanager' ),
			__( 'Clubs', 'racketmanager' ),
			'racket_manager',
			'racketmanager-clubs',
			array( &$this, 'display' )
		);
		add_action( "admin_print_scripts-$page", array( &$this, 'loadScripts' ) );
		add_action( "admin_print_scripts-$page", array( &$this, 'loadStyles' ) );

		$page = add_submenu_page(
			'racketmanager',
			__( 'Results', 'racketmanager' ),
			__( 'Results', 'racketmanager' ),
			'racket_manager',
			'racketmanager-results',
			array( &$this, 'display' )
		);
		add_action( "admin_print_scripts-$page", array( &$this, 'loadScripts' ) );
		add_action( "admin_print_scripts-$page", array( &$this, 'loadStyles' ) );

		$page = add_submenu_page(
			'racketmanager',
			__( 'Players', 'racketmanager' ),
			__( 'Players', 'racketmanager' ),
			'racket_manager',
			'racketmanager-players',
			array( $this, 'display' )
		);
		add_action( "admin_print_scripts-$page", array( &$this, 'loadScripts' ) );
		add_action( "admin_print_scripts-$page", array( &$this, 'loadStyles' ) );

		$page = add_submenu_page(
			'racketmanager',
			__( 'Administration', 'racketmanager' ),
			__( 'Administration', 'racketmanager' ),
			'racketmanager_settings',
			'racketmanager-admin',
			array( $this, 'display' )
		);
		add_action( "admin_print_scripts-$page", array( &$this, 'loadScripts' ) );
		add_action( "admin_print_scripts-$page", array( &$this, 'loadStyles' ) );

		$page = add_submenu_page(
			'racketmanager',
			__( 'Finances', 'racketmanager' ),
			__( 'Finances', 'racketmanager' ),
			'racket_manager',
			'racketmanager-finances',
			array( &$this, 'display' )
		);
		add_action( "admin_print_scripts-$page", array( &$this, 'loadScripts' ) );
		add_action( "admin_print_scripts-$page", array( &$this, 'loadStyles' ) );

		$page = add_submenu_page(
			'racketmanager',
			__( 'Settings', 'racketmanager' ),
			__( 'Settings', 'racketmanager' ),
			'racketmanager_settings',
			'racketmanager-settings',
			array( $this, 'display' )
		);
		add_action( "admin_print_scripts-$page", array( &$this, 'loadScripts' ) );
		add_action( "admin_print_scripts-$page", array( &$this, 'loadStyles' ) );

		$page = add_submenu_page(
			'racketmanager',
			__( 'Import', 'racketmanager' ),
			__( 'Import', 'racketmanager' ),
			'import_leagues',
			'racketmanager-import',
			array( $this, 'display' )
		);
		add_action( "admin_print_scripts-$page", array( &$this, 'loadScripts' ) );
		add_action( "admin_print_scripts-$page", array( &$this, 'loadStyles' ) );

		$page = add_submenu_page(
			'racketmanager',
			__( 'Documentation', 'racketmanager' ),
			__( 'Documentation', 'racketmanager' ),
			'view_leagues',
			'racketmanager-doc',
			array( $this, 'display' )
		);
		add_action( "admin_print_scripts-$page", array( &$this, 'loadScripts' ) );
		add_action( "admin_print_scripts-$page", array( &$this, 'loadStyles' ) );

		add_filter( 'plugin_action_links_' . RACKETMANAGER_PLUGIN_BASENAME, array( &$this, 'pluginActions' ) );
	}

	/**
	 * Adds scroll to top icon to the admin interface
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
	 *
	 * @param  obj $user The WP user object.
	 * @return void
	 */
	public function custom_user_profile_fields( $user ) {
		?>
		<table class="form-table" aria-label="<?php esc_html_e( 'racketmanager_fields', 'racketmanager' ); ?>">
			<tr>
				<th>
					<label for="gender"><?php esc_html_e( 'Gender', 'racketmanager' ); ?></label>
				</th>
				<td>
					<input type="radio" required name="gender" value="M" <?php echo ( get_the_author_meta( 'gender', $user->ID ) === 'M' ) ? 'checked' : ''; ?>> <?php esc_html_e( 'Male', 'racketmanager' ); ?><br />
					<input type="radio" name="gender" value="F" <?php echo ( get_the_author_meta( 'gender', $user->ID ) === 'F' ) ? 'checked' : ''; ?>> <?php esc_html_e( 'Female', 'racketmanager' ); ?>
				</td>
			</tr>
			<tr>
				<th>
					<label for="contactno"><?php esc_html_e( 'Contact Number', 'racketmanager' ); ?></label>
				</th>
				<td>
					<input type="tel" name="contactno" value="<?php echo esc_attr( get_the_author_meta( 'contactno', $user->ID ) ); ?>">
				</td>
			</tr>
			<tr>
				<th>
					<label for="btm"><?php esc_html_e( 'LTA Tennis Number', 'racketmanager' ); ?></label>
				</th>
				<td>
					<input type="number" name="btm" value="<?php echo esc_attr( get_the_author_meta( 'btm', $user->ID ) ); ?>">
				</td>
			</tr>
			<tr>
				<th>
					<label for="year_of_birth"><?php esc_html_e( 'Year of birth', 'racketmanager' ); ?></label>
				</th>
				<td>
					<input type="number" name="year_of_birth" value="<?php echo esc_attr( get_the_author_meta( 'year_of_birth', $user->ID ) ); ?>">
				</td>
			</tr>
			<tr>
				<th>
					<label for="remove_date"><?php esc_html_e( 'Date Removed', 'racketmanager' ); ?></label>
				</th>
				<td>
					<input type="date" name="remove_date" value="<?php echo esc_attr( get_the_author_meta( 'remove_date', $user->ID ) ); ?>">
				</td>
			</tr>
			<tr>
				<th>
					<label for="locked_date"><?php esc_html_e( 'Date Locked', 'racketmanager' ); ?></label>
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
	 *
	 * @param int $user_id user_id.
	 */
	public function update_extra_profile_fields( $user_id ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( current_user_can( 'edit_user', $user_id ) ) {
			if ( isset( $_POST['gender'] ) ) {
				update_user_meta( $user_id, 'gender', sanitize_text_field( wp_unslash( $_POST['gender'] ) ) );
			}
			if ( isset( $_POST['contactno'] ) ) {
				update_user_meta( $user_id, 'contactno', sanitize_text_field( wp_unslash( $_POST['contactno'] ) ) );
			}
			if ( isset( $_POST['btm'] ) ) {
				update_user_meta( $user_id, 'btm', intval( $_POST['btm'] ) );
			}
			if ( isset( $_POST['year_of_birth'] ) ) {
				update_user_meta( $user_id, 'year_of_birth', intval( $_POST['year_of_birth'] ) );
			}
			if ( isset( $_POST['remove_date'] ) ) {
				update_user_meta( $user_id, 'remove_date', sanitize_text_field( wp_unslash( $_POST['remove_date'] ) ) );
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Adds the required Metaboxes
	 */
	public function metaboxes() {
		add_meta_box( 'racketmanager', __( 'Match-Report', 'racketmanager' ), array( &$this, 'add_meta_box' ), 'post' );
	}

	/**
	 * Build league menu
	 *
	 * @return array
	 */
	private function get_menu() {
		$league = get_league();
		$season = ( isset( $_GET['season'] ) ? sanitize_text_field( wp_unslash( $_GET['season'] ) ) : $league->current_season ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$sport  = ( isset( $league->sport ) ? ( $league->sport ) : '' );

		$menu          = array();
		$menu['teams'] = array(
			'title'    => __( 'Add Teams', 'racketmanager' ),
			'callback' => array( &$this, 'display_teams_list' ),
			'cap'      => 'edit_teams',
			'show'     => true,
		);
		$menu['team']  = array(
			'title'    => __( 'Add Team', 'racketmanager' ),
			'callback' => array( &$this, 'display_team_page' ),
			'cap'      => 'edit_teams',
			'show'     => false,
		);
		$menu['match'] = array(
			'title'    => __( 'Add Matches', 'racketmanager' ),
			'callback' => array( &$this, 'display_match_page' ),
			'cap'      => 'edit_matches',
		);
		if ( $league->is_championship ) {
			$menu['match']['show'] = false;
			if ( $league->event->competition->is_player_entry && empty( $league->championship->is_consolation ) ) {
				$menu['team']['show'] = true;
			}
		} else {
			$menu['match']['show'] = true;
		}
		$menu['contact'] = array(
			'title'    => __( 'Contact', 'racketmanager' ),
			'callback' => array( &$this, 'displayContactPage' ),
			'cap'      => 'edit_teams',
			'show'     => true,
		);
		$menu            = apply_filters( 'racketmanager_league_menu_' . $sport, $menu, $league->id, $season );
		$menu            = apply_filters( 'racketmanager_league_menu_' . $league->mode, $menu, $league->id, $season );

		return $menu;
	}

	/**
	 * ShowMenu() - show admin menu
	 */
	public function display() {
		global $league;

		$options = $this->options;

		// Update Plugin Version.
		if ( RACKETMANAGER_VERSION !== $options['version'] ) {
			$options['version'] = RACKETMANAGER_VERSION;
			update_option( 'racketmanager', $options );
		}

		// Update database.
		if ( ! isset( $options['dbversion'] ) || RACKETMANAGER_DBVERSION !== $options['dbversion'] ) {
			include_once RACKETMANAGER_PATH . '/admin/upgrade.php';
			racketmanager_upgrade_page();
			return;
		}

		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$view = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : ''; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		switch ( $page ) {
			case 'racketmanager-doc':
				include_once RACKETMANAGER_PATH . '/admin/documentation.php';
				break;
			case 'racketmanager-leagues':
				$this->display_leagues_page();
				break;
			case 'racketmanager-cups':
				$this->displayCupsPage();
				break;
			case 'racketmanager-tournaments':
				if ( 'tournament' === $view ) {
					$this->displayTournamentPage();
				} elseif ( 'tournament-plan' === $view ) {
					$this->displayTournamentPlanPage();
				} else {
					$this->displayTournamentsPage();
				}
				break;
			case 'racketmanager-clubs':
				if ( 'teams' === $view ) {
					$this->display_teams_page();
				} elseif ( 'players' === $view ) {
					$this->display_club_players_page();
				} elseif ( 'player' === $view ) {
					$this->display_player_page();
				} else {
					$this->display_clubs_page();
				}
				break;
			case 'racketmanager-results':
				$view = isset( $_GET['subpage'] ) ? sanitize_text_field( wp_unslash( $_GET['subpage'] ) ) : '';  //phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( 'match' === $view ) {
					$this->display_match_results_page();
				} else {
					$this->display_results_page();
				}
				break;
			case 'racketmanager-admin':
				if ( 'competitions' === $view ) {
					$this->displayCompetitionsList();
				} elseif ( 'player' === $view ) {
					$this->display_player_page();
				} else {
					$this->display_admin_page();
				}
				break;
			case 'racketmanager-players':
				if ( 'player' === $view ) {
					$this->display_player_page();
				} else {
					$this->displayPlayersPage();
				}
				break;
			case 'racketmanager-finances':
				$view = isset( $_GET['subpage'] ) ? sanitize_text_field( wp_unslash( $_GET['subpage'] ) ) : '';  //phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( 'charges' === $view ) {
					$this->displayChargesPage();
				} elseif ( 'invoice' === $view ) {
					$this->displayInvoicePage();
				} else {
					$this->displayFinancesPage();
				}
				break;
			case 'racketmanager-settings':
				$this->display_options_page();
				break;
			case 'racketmanager-import':
				$this->displayImportPage();
				break;
			case 'racketmanager-documentation':
				include_once RACKETMANAGER_PATH . '/admin/documentation.php';
				break;
			case 'racketmanager':
			default:
				if ( isset( $_GET['subpage'] ) ) {  //phpcs:ignore WordPress.Security.NonceVerification.Recommended
					switch ( sanitize_text_field( wp_unslash( $_GET['subpage'] ) ) ) {  //phpcs:ignore WordPress.Security.NonceVerification.Recommended
						case 'show-competition':
							$this->display_competition_page();
							break;
						case 'show-event':
							$this->display_event_page();
							break;
						case 'club':
							$this->display_club_page();
							break;
						case 'team':
							$this->display_team_page();
							break;
						case 'show-season':
							$this->displaySeasonPage();
							break;
						case 'contact':
							$this->displayContactPage();
							break;
						default:
							$this->league_id = isset( $_GET['league_id'] ) ? intval( $_GET['league_id'] ) : 0;  //phpcs:ignore WordPress.Security.NonceVerification.Recommended
							$league          = get_league( $this->league_id );
							$menu            = $this->get_menu();
							$page            = sanitize_text_field( wp_unslash( $_GET['subpage'] ) );  //phpcs:ignore WordPress.Security.NonceVerification.Recommended
							if ( array_key_exists( $page, $menu ) ) {
								if ( isset( $menu[ $page ]['callback'] ) && is_callable( $menu[ $page ]['callback'] ) ) {
									call_user_func( $menu[ $page ]['callback'] );
								} else {
									include_once $menu[ $page ]['file'];
								}
							} else {
								$this->displayLeaguePage();
							}
					}
				} else {
					$this->display_index_page();
				}
		}
	}

	/**
	 * Show RacketManager index page
	 */
	private function display_index_page() {
		global $competition, $club;

		if ( ! current_user_can( 'view_leagues' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			$tab     = 'competitionsleague';
			$club_id = isset( $_GET['club_id'] ) ? sanitize_text_field( wp_unslash( $_GET['club_id'] ) ) : 0;
			if ( $club_id ) {
				$club = get_club( $club_id );
			}
			if ( isset( $_POST['addCompetition'] ) ) {
				if ( current_user_can( 'edit_leagues' ) ) {
					if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_add-competition' ) ) {
						$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
						$this->printMessage();
						return;
					}
					$competition         = new \stdClass();
					$validation['error'] = false;
					if ( isset( $_POST['competition_name'] ) ) {
						$competition->name = sanitize_text_field( wp_unslash( $_POST['competition_name'] ) );
					} else {
						$validation['error'] = true;
					}
					if ( isset( $_POST['type'] ) ) {
						$competition->type = sanitize_text_field( wp_unslash( $_POST['type'] ) );
					} else {
						$validation['error'] = true;
					}
					if ( $validation['error'] ) {
						$this->set_message( __( 'Error in competition creation', 'racketmanager' ), true );
					} else {
						$competition = new Racketmanager_Competition( $competition );
					}
				} else {
					$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
				}
				$this->printMessage();
			} elseif ( isset( $_POST['docompdel'] ) && isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) {
				if ( current_user_can( 'del_leagues' ) ) {
					check_admin_referer( 'competitions-bulk' );
					$messages      = array();
					$message_error = false;
					if ( isset( $_POST['competition'] ) ) {
						// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
						foreach ( $_POST['competition'] as $competition_id ) {
							$competition = get_competition( $competition_id );
							$competition->delete();
							$this->delete_competition_pages( $competition->name );
							$messages[] = $competition->name . ' ' . __( 'deleted', 'racketmanager' );
						}
						$message = implode( '<br>', $messages );
						$this->set_message( $message, $message_error );
					} else {
						$this->set_message( __( 'No deletions flagged', 'racketmanager' ), true );
					}
				} else {
					$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
				}
				$this->printMessage();
			}
			include_once RACKETMANAGER_PATH . '/admin/index.php';
		}
	}

	/**
	 * Show RacketManager results page
	 */
	private function display_results_page() {
		if ( ! current_user_can( 'view_leagues' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			$season_select        = isset( $_GET['season'] ) ? sanitize_text_field( wp_unslash( $_GET['season'] ) ) : '';
			$competition_select   = isset( $_GET['competition'] ) ? intval( $_GET['competition'] ) : '';
			$event_select         = isset( $_GET['event'] ) ? intval( $_GET['event'] ) : '';
			$results_check_filter = isset( $_GET['filterResultsChecker'] ) ? sanitize_text_field( wp_unslash( $_GET['filterResultsChecker'] ) ) : 'outstanding';
			$tab                  = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'resultschecker';
			if ( isset( $_POST['doResultsChecker'] ) ) {
				if ( current_user_can( 'update_results' ) ) {
					check_admin_referer( 'results-checker-bulk' );
					if ( isset( $_POST['resultsChecker'] ) && isset( $_POST['action'] ) ) {
						// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
						foreach ( $_POST['resultsChecker'] as $i => $results_checker_id ) {
							if ( 'approve' === $_POST['action'] ) {
								$this->approveResultsChecker( intval( $results_checker_id ) );
							} elseif ( 'handle' === $_POST['action'] ) {
								$this->handleResultsChecker( intval( $results_checker_id ) );
							} elseif ( 'delete' === $_POST['action'] ) {
								$this->deleteResultsChecker( intval( $results_checker_id ) );
							}
						}
					} else {
						$this->set_message( __( 'No actions flagged', 'racketmanager' ), true );
					}
				} else {
					$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
				}
				$this->printMessage();
				$tab = 'resultschecker';
			}
			$results_checkers = $this->getResultsChecker(
				array(
					'season'      => $season_select,
					'competition' => $competition_select,
					'event'       => $event_select,
					'status'      => $results_check_filter,
				)
			);
			include_once RACKETMANAGER_PATH . '/admin/show-results.php';
		}
	}

	/**
	 * Show RacketManager match results page
	 */
	private function display_match_results_page() {
		global $match;

		if ( ! current_user_can( 'update_results' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			$match = isset( $_GET['match_id'] ) ? get_match( intval( $_GET['match_id'] ) ) : null;  //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( $match ) {
				$referrer = empty( $_GET['referrer'] ) ? null : sanitize_text_field( wp_unslash( $_GET['referrer'] ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
				include_once RACKETMANAGER_PATH . '/admin/show-match.php';
			} else {
				$this->set_message( __( 'Match not found', 'racketmanager' ), true );
				$this->printMessage();
			}
		}
	}

	/**
	 * Display competition page
	 */
	private function display_competition_page() {
		global $competition;
		$this->set_message( '' );
		if ( ! current_user_can( 'edit_leagues' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			$tab = 'events';
			if ( isset( $_GET['competition_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$competition_id = intval( $_GET['competition_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$competition    = get_competition( $competition_id );
				$event_id       = false;
				$event_title    = '';
				$season_id      = false;
				$season_data    = array(
					'name'           => '',
					'num_match_days' => '',
					'homeAndAway'    => '',
				);
				$club_id        = 0;
				if ( isset( $_POST['saveSeason'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$tab = 'seasons';
					if ( ! current_user_can( 'edit_leagues' ) ) {
						$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
					} elseif ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_add-season' ) ) {
						$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
					} elseif ( ! empty( $_POST['season'] ) && empty( $_POST['season_id'] ) && isset( $_POST['num_match_days'] ) && isset( $_POST['competition_id'] ) ) {
						$this->add_season_to_competition( sanitize_text_field( wp_unslash( $_POST['season'] ) ), intval( $_POST['competition_id'] ), intval( $_POST['num_match_days'] ) );
					}
					$this->printMessage();
				} elseif ( isset( $_POST['doactionseason'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$tab = 'seasons';
					$this->delete_seasons_from_competition();
					$this->printMessage();
				} elseif ( isset( $_POST['updateSettings'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$tab = 'settings';
					$this->update_competition_settings( $competition );
					$this->printMessage();
				} elseif ( isset( $_POST['scheduleAction'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$tab = 'schedule';
					if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_schedule-matches' ) ) {
						$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
						$this->printMessage();
						return;
					}
					if ( isset( $_POST['actionSchedule'] ) ) {
						if ( 'schedule' === $_POST['actionSchedule'] ) {
							if ( isset( $_POST['event'] ) ) {
								$this->scheduleLeagueMatches( $_POST['event'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
								$this->printMessage();
							}
						} elseif ( 'delete' === $_POST['actionSchedule'] ) {
							if ( isset( $_POST['event'] ) ) {
								foreach ( $_POST['event'] as $event_id ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
									$this->delete_event_matches( $event_id );
								}
							}
						}
						$this->printMessage();
					}
				} elseif ( isset( $_POST['addEvent'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					if ( isset( $_POST['event_title'] ) ) {  // phpcs:ignore WordPress.Security.NonceVerification.Missing
						$event       = new \stdClass();
						$event->name = sanitize_text_field( wp_unslash( $_POST['event_title'] ) );  // phpcs:ignore WordPress.Security.NonceVerification.Missing
						if ( isset( $_POST['num_rubbers'] ) ) {
							$event->num_rubbers = intval( $_POST['num_rubbers'] );
						} else {
							$this->set_message( __( 'Missing number of rubbers', 'racketmanager' ), true );
						}
						if ( ! empty( $_POST['num_sets'] ) ) {
							$event->num_sets = intval( $_POST['num_sets'] );
						} else {
							$this->set_message( __( 'Missing numer of sets', 'racketmanager' ), true );
						}
						if ( ! empty( $_POST['type'] ) ) {
							$event->type = sanitize_text_field( wp_unslash( $_POST['type'] ) );
						} else {
							$this->set_message( __( 'No event type', 'racketmanager' ), true );
						}
						$event->competition_id = $competition->id;
						$event                 = new Racketmanager_Event( $event );
						$this->set_message( __( 'Event created', 'racketmanager' ) );
					} else {
						$this->set_message( __( 'No event title', 'racketmanager' ), true );
					}
					$this->printMessage();
				} elseif ( isset( $_POST['doactionevent'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager__events-bulk' ) ) {
						$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
						$this->printMessage();
						return;
					}
					if ( isset( $_POST['action'] ) && 'delete' === $_POST['action'] && isset( $_POST['event'] ) ) {
						foreach ( $_POST['event'] as $event_id ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
							$event = get_event( $event_id );
							$event->delete();
							$message = $event->name . ' deleted';
						}
						$this->printMessage();
					}
				} elseif ( isset( $_POST['contactTeam'] ) ) {
					if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_contact-teams-preview' ) ) {
						$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
					} elseif ( current_user_can( 'edit_teams' ) ) {
						if ( isset( $_POST['competition_id'] ) && isset( $_POST['season'] ) && isset( $_POST['emailMessage'] ) ) {
							$this->contact_competition_teams( intval( $_POST['competition_id'] ), sanitize_text_field( wp_unslash( $_POST['season'] ) ), htmlspecialchars_decode( $_POST['emailMessage'] ) ); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
						}
					} else {
						$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
					}
				} elseif ( isset( $_GET['editleague'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$league_id    = intval( $_GET['editleague'] );  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$league       = get_league( $league_id );
					$league_title = $league->title;
				} elseif ( isset( $_GET['view'] ) && 'matches' === $_GET['view'] ) {  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$tab = 'matches';
				}
				if ( ! isset( $season ) ) {
					$competition_season = isset( $competition->current_season['name'] ) ? $competition->current_season['name'] : null;
					$season             = isset( $_GET['season'] ) ? sanitize_text_field( wp_unslash( $_GET['season'] ) ) : $competition_season;  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				}
				if ( isset( $_GET['tournament'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$tournament = get_tournament( intval( $_GET['tournament'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$page_title = $tournament->name . ' ' . __( 'Tournament Events', 'racketmanager' );
				}
				include_once RACKETMANAGER_PATH . '/admin/show-competition.php';

			}
		}
	}
	/**
	 * Display event page
	 */
	private function display_event_page() {
		if ( ! current_user_can( 'edit_leagues' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			$tab = 'leagues';
			if ( isset( $_GET['event_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$event_id     = intval( $_GET['event_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$event        = get_event( $event_id );
				$league_id    = false;
				$league_title = '';
				$season_id    = false;
				$season_data  = array(
					'name'           => '',
					'num_match_days' => '',
					'homeAndAway'    => '',
				);
				$club_id      = 0;
				if ( isset( $_POST['addLeague'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$this->add_league_to_event();
					$this->printMessage();
				} elseif ( isset( $_POST['doactionleague'] ) && isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$this->delete_leagues_from_event();
					$this->printMessage();
				} elseif ( isset( $_POST['saveSeason'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$tab = 'seasons';
					if ( ! current_user_can( 'edit_leagues' ) ) {
						$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
					} elseif ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_add-season' ) ) {
						$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
					} elseif ( ! empty( $_POST['season'] ) && empty( $_POST['season_id'] && isset( $_POST['event_id'] ) ) ) {
						if ( $event->is_box ) {
							$home_away    = isset( $_POST['homeAway'] ) ? sanitize_text_field( wp_unslash( $_POST['homeAway'] ) ) : null;
							$match_dates  = isset( $_POST['matchDate'] ) ? $_POST['matchDate'] : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
							$closing_date = isset( $_POST['date_closing'] ) ? sanitize_text_field( wp_unslash( $_POST['date_closing'] ) ) : null;
							$this->add_season_to_event( sanitize_text_field( wp_unslash( $_POST['season'] ) ), intval( $_POST['event_id'] ), null, $closing_date, $home_away, $match_dates );
						} elseif ( isset( $_POST['num_match_days'] ) ) {
							$this->add_season_to_event( sanitize_text_field( wp_unslash( $_POST['season'] ) ), intval( $_POST['event_id'] ), intval( $_POST['num_match_days'] ) );
						}
						$event = get_event( $event_id );
					}
					$this->printMessage();
				} elseif ( isset( $_POST['doactionseason'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$tab = 'seasons';
					$this->delete_seasons_from_event();
					$this->printMessage();
				} elseif ( isset( $_POST['doactionconstitution'] ) && isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$tab = 'constitution';
					$this->delete_constitution_teams();
					$this->printMessage();
				} elseif ( isset( $_POST['saveconstitution'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$tab = 'constitution';
					$this->save_constitution();
					$this->printMessage();
				} elseif ( isset( $_POST['action'] ) && 'addTeamsToLeague' === $_POST['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$tab = 'constitution';
					$this->add_teams_to_constitution();
					$this->printMessage();
				} elseif ( isset( $_POST['generate_matches'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$tab = 'constitution';
					$this->generate_box_league_matches();
					$this->printMessage();
				} elseif ( isset( $_POST['updateSettings'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$tab = 'settings';
					$this->update_event_settings( $event );
					$this->printMessage();
				} elseif ( isset( $_GET['editleague'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$league_id    = intval( $_GET['editleague'] );  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$league       = get_league( $league_id );
					$league_title = $league->title;
				} elseif ( isset( $_GET['statsseason'] ) && 'Show' === $_GET['statsseason'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					if ( isset( $_GET['club_id'] ) ) {  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
						$club_id = intval( $_GET['club_id'] );  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					}
					$tab = 'playerstats';
				} elseif ( isset( $_GET['view'] ) && 'matches' === $_GET['view'] ) {  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$tab = 'matches';
				}
				if ( ! isset( $season ) ) {
					$event_season = isset( $event->current_season['name'] ) ? $event->current_season['name'] : '';
					$season       = ( isset( $_GET['season'] ) ? sanitize_text_field( wp_unslash( $_GET['season'] ) ) : $event_season );  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				}
				if ( isset( $_GET['tournament'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$tournament = get_tournament( intval( $_GET['tournament'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$page_title = $tournament->name . ' ' . __( 'Tournament Events', 'racketmanager' );
				}
				include_once RACKETMANAGER_PATH . '/admin/show-event.php';

			}
		}
	}

	/**
	 * Add league to event via admin
	 */
	private function add_league_to_event() {
		if ( ! current_user_can( 'edit_leagues' ) ) {
			$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
		} elseif ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_add-league' ) ) {
				$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
		} elseif ( empty( $_POST['league_id'] ) ) {
			if ( isset( $_POST['league_title'] ) && isset( $_POST['event_id'] ) ) {
				$league           = new \stdClass();
				$league->title    = sanitize_text_field( wp_unslash( $_POST['league_title'] ) );
				$league->event_id = intval( $_POST['event_id'] );
				$league           = new Racketmanager_League( $league );
				$this->set_message( __( 'League added', 'racketmanager' ) );
			}
		} else {
			$league = get_league( intval( $_POST['league_id'] ) );
			if ( sanitize_text_field( wp_unslash( $_POST['league_title'] ) ) === $league->title ) {
				$this->set_message( __( 'No updates', 'racketmanager' ), true );
			} else {
				$league->update( sanitize_text_field( wp_unslash( $_POST['league_title'] ) ) );
				$this->set_message( __( 'League Updated', 'racketmanager' ) );
			}
		}
	}
	/**
	 * Delete season(s) from competition via admin
	 */
	private function delete_seasons_from_competition() {
		if ( ! current_user_can( 'del_seasons' ) ) {
			$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
		} elseif ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'seasons-bulk' ) ) {
				$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
		} elseif ( isset( $_POST['action'] ) && 'delete' === $_POST['action'] && isset( $_POST['del_season'] ) && isset( $_POST['competition_id'] ) ) {
				$this->delete_competition_season( $_POST['del_season'], intval( $_POST['competition_id'] ) ); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash

		}
	}
	/**
	 * Delete season(s) from event via admin
	 */
	private function delete_seasons_from_event() {
		if ( ! current_user_can( 'del_seasons' ) ) {
			$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
		} elseif ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'seasons-bulk' ) ) {
				$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
		} elseif ( isset( $_POST['action'] ) && 'delete' === $_POST['action'] && isset( $_POST['del_season'] ) && isset( $_POST['event_id'] ) ) {
				$this->delete_event_season( $_POST['del_season'], intval( $_POST['event_id'] ) ); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash

		}
	}

	/**
	 * Delete league(s) from event via admin
	 */
	private function delete_leagues_from_event() {
		if ( ! current_user_can( 'del_leagues' ) ) {
			$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
		} elseif ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'leagues-bulk' ) ) {
				$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
		} else {
			$messages      = array();
			$message_error = false;
			if ( isset( $_POST['league'] ) ) {
				foreach ( $_POST['league'] as $league_id ) { //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
					$league = get_league( $league_id );
					$league->delete();
					$messages[] = $league->title . ' ' . __( 'deleted', 'racketmanager' );
				}
				$message = implode( '<br>', $messages );
				$this->set_message( $message, $message_error );
			}
		}
	}

	/**
	 * Save constitution for event via admin
	 */
	private function save_constitution() {
		if ( ! current_user_can( 'edit_leagues' ) ) {
			$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
		} elseif ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'constitution-bulk' ) ) {
				$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
		} else {
			$js = false;
			if ( isset( $_POST['js-active'] ) ) {
				$js = ( 1 === intval( $_POST['js-active'] ) ) ? true : false;
			}
			$rank = 0;
			if ( isset( $_POST['table_id'] ) ) {
				$latest_season = isset( $_POST['latest_season'] ) ? sanitize_text_field( wp_unslash( $_POST['latest_season'] ) ) : null;
				// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
				foreach ( $_POST['table_id'] as $table_id ) {
					$team      = isset( $_POST['team_id'][ $table_id ] ) ? $_POST['team_id'][ $table_id ] : null;
					$league_id = isset( $_POST['league_id'][ $table_id ] ) ? $_POST['league_id'][ $table_id ] : null;
					if ( $js ) {
						++$rank;
					} else {
						$rank = isset( $_POST['rank'][ $table_id ] ) ? $_POST['rank'][ $table_id ] : '';
					}
					$status  = isset( $_POST['status'][ $table_id ] ) ? $_POST['status'][ $table_id ] : null;
					$profile = isset( $_POST['profile'][ $table_id ] ) ? $_POST['profile'][ $table_id ] : null;
					if ( isset( $_POST['constitutionAction'] ) && 'insert' === $_POST['constitutionAction'] ) {
						$league = get_league( $league_id );
						if ( $league ) {
							$profile = '0';
							$league->add_team( $team, $latest_season, $rank, $status, $profile );
						}
					} elseif ( isset( $_POST['constitutionAction'] ) && 'update' === $_POST['constitutionAction'] ) {
						$this->updateTable( $table_id, $league_id, $rank, $status, $profile );
					}
				}
				// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			}
		}
	}

	/**
	 * Add teams(s) to constitution via admin
	 */
	private function add_teams_to_constitution() {
		if ( ! current_user_can( 'edit_leagues' ) ) {
			$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
		} elseif ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_add-teams-bulk' ) ) {
				$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
		} elseif ( isset( $_POST['team'] ) && isset( $_POST['league_id'] ) && isset( $_POST['season'] ) && isset( $_POST['event_id'] ) ) {
			foreach ( $_POST['team'] as $team_id ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
				$rank    = '99';
				$status  = 'NT';
				$profile = '1';
				$league  = get_league( intval( $_POST['league_id'] ) );
				$league->add_team( $team_id, sanitize_text_field( wp_unslash( $_POST['season'] ) ), $rank, $status, $profile );
				$team = get_team( $team_id );
				$team->set_event( intval( $_POST['event_id'] ) );
			}
		}
	}

	/**
	 * Delete teams(s) from constitution via admin
	 */
	private function delete_constitution_teams() {
		if ( current_user_can( 'del_leagues' ) ) {
			if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'constitution-bulk' ) ) {
				$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
			} elseif ( isset( $_POST['table'] ) && isset( $_POST['latest_season'] ) ) {
					// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
				foreach ( $_POST['table'] as $table_id ) {
					$teams   = isset( $_POST['team_id'] ) ? $_POST['team_id'] : array();
					$leagues = isset( $_POST['league_id'] ) ? $_POST['league_id'] : array();
					$team    = isset( $teams[ $table_id ] ) ? $teams[ $table_id ] : 0;
					$league  = isset( $leagues[ $table_id ] ) ? $leagues[ $table_id ] : 0;
					if ( isset( $team ) && isset( $league ) ) {
						$league = get_league( $league );
						$league->delete_team( $team, sanitize_text_field( wp_unslash( $_POST['latest_season'] ) ) );
					}
				}
					// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash

			}
		} else {
			$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
		}
	}
	/**
	 * Generate matches
	 */
	private function generate_box_league_matches() {
		if ( ! current_user_can( 'edit_leagues' ) ) {
			$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
		} elseif ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'constitution-bulk' ) ) {
			$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
		} else {
			$event_id = isset( $_POST['event_id'] ) ? intval( $_POST['event_id'] ) : null;
			$season   = isset( $_POST['latest_season'] ) ? intval( $_POST['latest_season'] ) : null;
			if ( $event_id ) {
				if ( $season ) {
					$event = get_event( $event_id );
					$event->generate_box_league_matches( $season );
					$this->set_message( __( 'Matches generated', 'racketmanager' ) );
				} else {
					$this->set_message( __( 'No season set', 'racketmanager' ), true );
				}
			} else {
				$this->set_message( __( 'No event set', 'racketmanager' ), true );
			}
		}
	}
	/**
	 * Update competition settings via admin
	 *
	 * @param object $competition competition object.
	 */
	private function update_competition_settings( $competition ) {
		$competition = get_competition( $competition );
		if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_manage-competition-options' ) ) {
			$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
		} elseif ( ! current_user_can( 'edit_league_settings' ) ) {
				$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
		} elseif ( isset( $_POST['settings'] ) && isset( $_POST['competition_title'] ) ) {
				$settings = $_POST['settings']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			if ( sanitize_text_field( wp_unslash( $_POST['competition_title'] ) ) !== $competition->name ) {
				$competition->set_name( sanitize_text_field( wp_unslash( $_POST['competition_title'] ) ) );
			}
			$competition->set_settings( $settings );
			$competition->reload_settings();
			$competition = get_competition( $competition );
			$this->set_message( __( 'Settings saved', 'racketmanager' ) );
		}
	}
	/**
	 * Update event settings via admin
	 *
	 * @param object $event event object.
	 */
	private function update_event_settings( $event ) {
		$event = get_event( $event );
		if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_manage-event-options' ) ) {
			$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
		} elseif ( ! current_user_can( 'edit_league_settings' ) ) {
				$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
		} elseif ( isset( $_POST['settings'] ) && isset( $_POST['event_title'] ) ) {
				$settings = $_POST['settings']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			if ( sanitize_text_field( wp_unslash( $_POST['event_title'] ) ) !== $event->name ) {
				$event->set_name( sanitize_text_field( wp_unslash( $_POST['event_title'] ) ) );
			}
				$event->set_settings( $settings );
				$event->reload_settings();
				$event = get_event( $event );
				$this->set_message( __( 'Settings saved', 'racketmanager' ) );
		}
	}

	/**
	 * Display league overview page
	 */
	private function displayLeaguePage() {
		global $league, $championship, $competition;

		if ( ! current_user_can( 'view_leagues' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			$league    = get_league();
			$league_id = $league->id;
			$league->set_season();
			$season      = $league->get_season();
			$league_mode = ( isset( $league->event->competition->mode ) ? ( $league->event->competition->mode ) : '' );
			$tab         = 'standings';
			$match_day   = false;
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			if ( isset( $_POST['doaction'] ) ) {
				if ( isset( $_POST['action'] ) ) {
					if ( 'delete' === $_POST['action'] ) {
						$this->delete_teams_from_league( $league );
					} elseif ( 'withdraw' === $_POST['action'] ) {
						$this->withdraw_teams_from_league( $league );
					} else {
						$this->set_message( __( 'No action selected', 'racketmanager' ), true );
					}
				}
				if ( $league->is_championship ) {
					$tab = 'preliminary';
				}
			} elseif ( isset( $_POST['delmatches'] ) ) {
				$this->delete_matches_from_league();
				$tab = 'matches';
			} elseif ( isset( $_POST['updateLeague'] ) && 'team' === $_POST['updateLeague'] ) {
				$this->league_manage_team( $league );
				if ( $league->is_championship ) {
					$tab = 'preliminary';
				}
			} elseif ( isset( $_POST['updateLeague'] ) && 'teamPlayer' === $_POST['updateLeague'] ) {
				$this->add_player_team_to_league( $league );
				if ( $league->is_championship ) {
					$tab = 'preliminary';
				}
			} elseif ( isset( $_POST['updateLeague'] ) && 'match' === $_POST['updateLeague'] ) {
				$this->manage_matches_in_league( $league );
			} elseif ( isset( $_POST['updateLeague'] ) && 'results' === $_POST['updateLeague'] ) {
				$this->update_results_in_league();
				$tab = 'matches';
			} elseif ( isset( $_POST['updateLeague'] ) && 'teams_manual' === $_POST['updateLeague'] ) {
				$this->league_manual_rank( $league );
			} elseif ( isset( $_POST['action'] ) && 'addTeamsToLeague' === $_POST['action'] ) {
				$this->league_add_teams( $league );
				if ( $league->is_championship ) {
					$tab = 'preliminary';
				}
			} elseif ( isset( $_POST['contactTeam'] ) ) {
				$this->league_contact_teams();
				$tab = 'standings';
			} elseif ( isset( $_POST['saveRanking'] ) ) {
				$this->league_manual_rank_teams( $league );
				$tab = 'standings';
			} elseif ( isset( $_POST['randomRanking'] ) ) {
				$this->league_random_rank_teams( $league );
				$tab = 'standings';
			}
			$this->printMessage();
			// phpcs:enable WordPress.Security.NonceVerification.Missing

			// check if league is a cup championship.
			$cup = ( 'championship' === $league_mode ) ? true : false;
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			$group     = isset( $_GET['group'] ) ? sanitize_text_field( wp_unslash( $_GET['group'] ) ) : '';
			$team_id   = isset( $_GET['team_id'] ) ? intval( $_GET['team_id'] ) : false;
			$match_day = false;
			if ( isset( $_GET['match_day'] ) ) {
				if ( -1 !== $_GET['match_day'] ) {
					$match_day = intval( $_GET['match_day'] );
					$league->set_match_day( $match_day );
				}
				$tab = 'matches';
			} elseif ( 'current_match_day' === $league->match_display ) {
					$league->set_match_day( 'current' );
			} elseif ( 'all' === $league->match_display ) {
				$league->set_match_day( -1 );
			}
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
			$options    = $this->options;
			$match_args = array(
				'final' => '',
				'cache' => false,
			);
			if ( $season ) {
				$match_args['season'] = $season;
			}
			if ( $group ) {
				$match_args['group'] = $group;
			}
			if ( $team_id ) {
				$match_args['team_id'] = $team_id;
			}
			if ( intval( $league->num_matches_per_page ) > 0 ) {
				$match_args['limit'] = intval( $league->num_matches_per_page );
			}
			if ( empty( $league->event->seasons ) ) {
				$this->set_message( __( 'You need to add at least one season for the competition', 'racketmanager' ), true );
				$this->printMessage();
			}
			$teams = $league->get_league_teams(
				array(
					'season' => $season,
					'cache'  => false,
				)
			);
			if ( 'championship' !== $league_mode ) {
				$match_args['reset_query_args'] = true;
				$matches                        = $league->get_matches( $match_args );
				$league->set_num_matches();
			}
			if ( isset( $_GET['match_paged'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$tab = 'matches';
			}
			if ( isset( $_GET['standingstable'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$get       = sanitize_text_field( wp_unslash( $_GET['standingstable'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$match_day = false;
				$mode      = 'all';
				if ( preg_match( '/match_day-\d/', $get, $hits ) ) {
					$res       = explode( '-', $hits[0] );
					$match_day = $res[1];
				} elseif ( in_array( $get, array( 'home', 'away' ), true ) ) {
					$mode = htmlspecialchars( $get );
				}
				$teams = $league->get_standings( $teams, $match_day, $mode );
			}
			if ( isset( $_GET['match_day'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$tab = 'matches';
			}
			include_once RACKETMANAGER_PATH . '/admin/show-league.php';
		}
	}

	/**
	 * Add teams to league in admin screen
	 *
	 * @param object $league league object.
	 */
	private function league_add_teams( $league ) {
		if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_add-teams-bulk' ) ) {
			$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
		} elseif ( current_user_can( 'edit_teams' ) ) {
			if ( isset( $_POST['team'] ) && isset( $_POST['event_id'] ) && isset( $_POST['season'] ) ) {
				$league = get_league( $league );
				foreach ( $_POST['team'] as $i => $team_id ) { //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					$league->add_team( $team_id, sanitize_text_field( wp_unslash( $_POST['season'] ) ) );
					if ( is_numeric( $team_id ) ) {
						$team = get_team( $team_id );
						$team->set_event( intval( $_POST['event_id'] ) );
					}
				}
			}
		} else {
			$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
		}
	}

	/**
	 * Add team to league in admin screen
	 *
	 * @param object $league league object.
	 */
	private function league_manage_team( $league ) {
		if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_manage-teams' ) ) {
			$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
		} elseif ( current_user_can( 'edit_teams' ) ) {
			if ( isset( $_POST['action'] ) && 'Add' === $_POST['action'] ) {
				$this->set_message( __( 'New team cannot be added to a league', 'racketmanager' ), true );
			} elseif ( isset( $_POST['team_id'] ) ) {
				$team = get_team( intval( $_POST['team_id'] ) );
				if ( ! empty( $_POST['league_id'] ) && ! empty( $_POST['editTeam'] ) ) {
					$league       = get_league( $league );
					$captain      = isset( $_POST['captainId'] ) ? intval( $_POST['captainId'] ) : null;
					$contactno    = isset( $_POST['contactno'] ) ? sanitize_text_field( wp_unslash( $_POST['contactno'] ) ) : null;
					$contactemail = isset( $_POST['contactemail'] ) ? sanitize_text_field( wp_unslash( $_POST['contactemail'] ) ) : null;
					$matchday     = isset( $_POST['matchday'] ) ? sanitize_text_field( wp_unslash( $_POST['matchday'] ) ) : null;
					$matchtime    = isset( $_POST['matchtime'] ) ? sanitize_text_field( wp_unslash( $_POST['matchtime'] ) ) : null;
					$team->set_event( $league->event->id, $captain, $contactno, $contactemail, $matchday, $matchtime );
				} elseif ( isset( $_POST['team'] ) && isset( $_POST['affiliatedclub'] ) && isset( $_POST['team_type'] ) ) {
						$team->update( sanitize_text_field( wp_unslash( $_POST['team'] ) ), intval( $_POST['affiliatedclub'] ), sanitize_text_field( wp_unslash( $_POST['team_type'] ) ) );
				}
			}
		} else {
			$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
		}
	}

	/**
	 * Add player team to league in admin screen
	 *
	 * @param object $league league object.
	 */
	private function add_player_team_to_league( $league ) {
		if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_manage-teams' ) ) {
			$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
		} elseif ( current_user_can( 'edit_teams' ) ) {
			$team_player_1    = isset( $_POST['teamPlayer1'] ) ? sanitize_text_field( wp_unslash( $_POST['teamPlayer1'] ) ) : '';
			$team_player_1_id = isset( $_POST['teamPlayerId1'] ) ? intval( $_POST['teamPlayerId1'] ) : 0;
			$team_player_2    = isset( $_POST['teamPlayer2'] ) ? sanitize_text_field( wp_unslash( $_POST['teamPlayer2'] ) ) : '';
			$team_player_2_id = isset( $_POST['teamPlayerId2'] ) ? intval( $_POST['teamPlayerId2'] ) : 0;
			$club             = isset( $_POST['affiliatedclub'] ) ? intval( $_POST['affiliatedclub'] ) : '';
			$captain          = isset( $_POST['captainId'] ) ? intval( $_POST['captainId'] ) : null;
			$contactno        = isset( $_POST['contactno'] ) ? sanitize_text_field( wp_unslash( $_POST['contactno'] ) ) : null;
			$contactemail     = isset( $_POST['contactemail'] ) ? sanitize_text_field( wp_unslash( $_POST['contactemail'] ) ) : null;
			if ( isset( $_POST['action'] ) ) {
				$league               = get_league( $league );
				$team                 = new \stdClass();
				$team->player1        = $team_player_1;
				$team->player1_id     = $team_player_1_id;
				$team->player2        = $team_player_2;
				$team->player2_id     = $team_player_2_id;
				$team->type           = $league->type;
				$team->team_type      = 'P';
				$team->affiliatedclub = $club;
				$team                 = new Racketmanager_Team( $team );
				$season               = isset( $_POST['season'] ) ? sanitize_text_field( wp_unslash( $_POST['season'] ) ) : null;
				$team->set_event( $league->event->id, $captain, $contactno, $contactemail );
				$league->add_team( $team->id, $season );
			} elseif ( isset( $_POST['team_id'] ) ) {
				$team = get_team( intval( $_POST['team_id'] ) );
				if ( 'P' === $team->team_type ) {
					$team->update_player( $team_player_1, $team_player_1_id, $team_player_2, $team_player_2_id, $club );
					$team->set_event( $league->event->id, $captain, $contactno, $contactemail );
				} else {
					$this->set_message( __( 'Team is not a player team', 'racketmanager' ), true );
				}
			}
		} else {
			$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
		}
	}

	/**
	 * Delete teams from league in admin screen
	 *
	 * @param object $league league object.
	 */
	private function delete_teams_from_league( $league ) {
		if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_teams-bulk' ) ) {
			$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
		} elseif ( isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) {
			if ( current_user_can( 'del_teams' ) ) {
				$league        = get_league( $league );
				$season        = isset( $_POST['season'] ) ? sanitize_text_field( wp_unslash( $_POST['season'] ) ) : null;
				$messages      = array();
				$message_error = false;
				if ( isset( $_POST['team'] ) ) {
					foreach ( $_POST['team'] as $team_id ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
						$league->delete_team( intval( $team_id ), $season );
						$messages[] = $team_id . ' ' . __( 'deleted', 'racketmanager' );
					}
					$message = implode( '<br>', $messages );
					$this->set_message( $message, $message_error );
				}
			} else {
				$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
			}
		}
	}
	/**
	 * Withdraw teams from league in admin screen
	 *
	 * @param object $league league object.
	 */
	private function withdraw_teams_from_league( $league ) {
		if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_teams-bulk' ) ) {
			$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
		} elseif ( isset( $_POST['action'] ) && 'withdraw' === $_POST['action'] ) {
			if ( current_user_can( 'del_teams' ) ) {
				$league        = get_league( $league );
				$season        = isset( $_POST['season'] ) ? sanitize_text_field( wp_unslash( $_POST['season'] ) ) : null;
				$messages      = array();
				$message_error = false;
				if ( isset( $_POST['team'] ) ) {
					foreach ( $_POST['team'] as $team_id ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
						$team = get_team( $team_id );
						$league->withdraw_team( intval( $team_id ), $season );
						$messages[] = $team->title . ' ' . __( 'withdrawn', 'racketmanager' );
					}
					$message = implode( '<br>', $messages );
					$this->set_message( $message, $message_error );
				}
			} else {
				$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
			}
		}
	}
	/**
	 * Manage matches in league in admin screen
	 *
	 * @param object $league league object.
	 */
	private function manage_matches_in_league( $league ) {
		if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_manage-matches' ) ) {
			$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
		} elseif ( current_user_can( 'edit_matches' ) ) {
				$group = isset( $_POST['group'] ) ? sanitize_text_field( wp_unslash( $_POST['group'] ) ) : '';
			if ( ! empty( $_POST['mode'] ) && 'add' === sanitize_text_field( wp_unslash( $_POST['mode'] ) ) ) {
				$this->add_matches_to_league( $league, $group );
			} else {
				$this->edit_matches_in_league( $league );
			}
		} else {
			$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
		}
	}

	/**
	 * Add matches to league in admin screen
	 *
	 * @param object $league league object.
	 * @param string $group group details.
	 */
	private function add_matches_to_league( $league, $group ) {
		if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_manage-matches' ) ) {
			$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
		} elseif ( isset( $_POST['match'] ) ) {
				$league = get_league( $league );
				$season = isset( $_POST['season'] ) ? sanitize_text_field( wp_unslash( $_POST['season'] ) ) : null;
				$final  = isset( $_POST['final'] ) ? sanitize_text_field( wp_unslash( $_POST['final'] ) ) : null;
			if ( $final ) {
				$final_exists = $league->get_matches(
					array(
						'final'  => $final,
						'season' => $season,
					)
				);
				if ( $final_exists ) {
					/* translators: %d: number of matches */
					$this->set_message( sprintf( __( 'Matches already exist for %s', 'racketmanager' ), $final ), true );
					return;
				}
			}
			$num_matches = count( $_POST['match'] );
			foreach ( $_POST['match'] as $i => $match_id ) { //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
				$match = new \stdClass();
				if ( isset( $_POST['away_team'][ $i ] ) && isset( $_POST['home_team'][ $i ] ) && $_POST['away_team'][ $i ] !== $_POST['home_team'][ $i ] ) {
					$index = ( isset( $_POST['mydatepicker'][ $i ] ) ) ? $i : 0;
					if ( ! isset( $_POST['begin_hour'][ $i ] ) ) {
						$_POST['begin_hour'][ $i ] = 0;
					}
					if ( ! isset( $_POST['begin_minutes'][ $i ] ) ) {
						$_POST['begin_minutes'][ $i ] = 0;
					}
					if ( isset( $_POST['mydatepicker'][ $index ] ) && isset( $_POST['begin_hour'][ $i ] ) ) {
						$match->date      = sanitize_text_field( wp_unslash( $_POST['mydatepicker'][ $index ] ) ) . ' ' . intval( $_POST['begin_hour'][ $i ] ) . ':' . intval( $_POST['begin_minutes'][ $i ] ) . ':00';
						$match->match_day = '';
						if ( isset( $_POST['match_day'][ $i ] ) ) {
							$match->match_day = sanitize_text_field( wp_unslash( $_POST['match_day'][ $i ] ) );
						} elseif ( ! empty( $_POST['match_day'] ) ) {
							$match->match_day = intval( $_POST['match_day'] );
						}
						$match->host        = isset( $_POST['host'][ $i ] ) ? sanitize_text_field( wp_unslash( $_POST['host'][ $i ] ) ) : null;
						$match->home_team   = sanitize_text_field( wp_unslash( $_POST['home_team'][ $i ] ) );
						$match->away_team   = sanitize_text_field( wp_unslash( $_POST['away_team'][ $i ] ) );
						$match->location    = isset( $_POST['location'][ $i ] ) ? sanitize_text_field( wp_unslash( $_POST['location'][ $i ] ) ) : null;
						$match->league_id   = isset( $_POST['league_id'] ) ? sanitize_text_field( wp_unslash( $_POST['league_id'] ) ) : null;
						$match->season      = isset( $_POST['season'] ) ? sanitize_text_field( wp_unslash( $_POST['season'] ) ) : null;
						$match->group       = $group;
						$match->final_round = isset( $_POST['final'] ) ? sanitize_text_field( wp_unslash( $_POST['final'] ) ) : null;
						$match->num_rubbers = isset( $_POST['num_rubbers'] ) ? intval( $_POST['num_rubbers'] ) : null;
						$league->add_match( $match );
					}
				} else {
					--$num_matches;
				}
			}
				/* translators: %d: number of matches */
				$this->set_message( sprintf( _n( '%d Match added', '%d Matches added', $num_matches, 'racketmanager' ), $num_matches ) );
		}
	}

	/**
	 * Edit matches in league in admin screen
	 *
	 * @param object $league league object.
	 */
	private function edit_matches_in_league( $league ) {
		if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_manage-matches' ) ) {
			$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
		} elseif ( isset( $_POST['match'] ) ) {
				$num_matches = count( $_POST['match'] );
				$post_match  = $this->htmlspecialchars_array( $_POST['match'] ); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			foreach ( $post_match as $i => $match_id ) {
				$match         = get_match( $match_id );
				$begin_hour    = isset( $_POST['begin_hour'][ $i ] ) ? intval( $_POST['begin_hour'][ $i ] ) : '00';
				$begin_minutes = isset( $_POST['begin_minutes'][ $i ] ) ? intval( $_POST['begin_minutes'][ $i ] ) : '00';
				if ( isset( $_POST['mydatepicker'][ $i ] ) ) {
					$index = ( isset( $_POST['mydatepicker'][ $i ] ) ) ? $i : 0;
					$date  = isset( $_POST['mydatepicker'][ $index ] ) ? sanitize_text_field( wp_unslash( $_POST['mydatepicker'][ $index ] ) ) : null;
					$date  = $date . ' ' . $begin_hour . ':' . $begin_minutes . ':00';
				} else {
					$index = ( isset( $_POST['year'][ $i ] ) && isset( $_POST['month'][ $i ] ) && isset( $_POST['day'][ $i ] ) ) ? $i : 0;
					$year  = isset( $_POST['year'][ $index ] ) ? intval( $_POST['year'][ $index ] ) : 0;
					$month = isset( $_POST['month'][ $index ] ) ? intval( $_POST['month'][ $index ] ) : 0;
					$day   = isset( $_POST['day'][ $index ] ) ? intval( $_POST['day'][ $index ] ) : 0;
					$date  = $year . '-' . $month . '-' . $day . ' ' . $begin_hour . ':' . $begin_minutes . ':00';
				}
				$match->date      = $date;
				$match->league_id = $league->id;
				$match->match_day = '';
				if ( isset( $_POST['match_day'] ) ) {
					if ( is_array( $_POST['match_day'] ) ) {
						$match->match_day = isset( $_POST['match_day'][ $i ] ) ? intval( $_POST['match_day'][ $i ] ) : null;
					} elseif ( ! empty( $_POST['match_day'] ) ) {
						$match->match_day = isset( $_POST['match_day'] ) ? intval( $_POST['match_day'] ) : null;
					}
				}
				$match->host        = isset( $_POST['host'][ $i ] ) ? sanitize_text_field( wp_unslash( $_POST['host'][ $i ] ) ) : null;
				$match->home_team   = isset( $_POST['home_team'][ $i ] ) ? sanitize_text_field( wp_unslash( $_POST['home_team'][ $i ] ) ) : '';
				$match->away_team   = isset( $_POST['away_team'][ $i ] ) ? sanitize_text_field( wp_unslash( $_POST['away_team'][ $i ] ) ) : '';
				$match->location    = isset( $_POST['location'][ $i ] ) ? sanitize_text_field( wp_unslash( $_POST['location'][ $i ] ) ) : null;
				$match->final_round = isset( $_POST['final'] ) ? sanitize_text_field( wp_unslash( $_POST['final'] ) ) : null;
				$league->update_match( $match );
			}
				/* translators: %d: number of matches updated */
				$this->set_message( sprintf( _n( '%d Match updated', '%d Matches updated', $num_matches, 'racketmanager' ), $num_matches ) );
		}
	}

	/**
	 * Delete matches from league in admin screen
	 */
	private function delete_matches_from_league() {
		if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_matches-bulk' ) ) {
			$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
		} elseif ( isset( $_POST['delMatchOption'] ) && 'delete' === $_POST['delMatchOption'] ) {
			if ( current_user_can( 'del_matches' ) ) {
				$messages = array();
				if ( isset( $_POST['match'] ) ) {
					foreach ( $_POST['match'] as $match_id ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
						$match = get_match( $match_id );
						$match->delete();
						/* translators: %d: Match id */
						$messages[] = ( sprintf( __( 'Match id %d deleted', 'racketmanager' ), $match_id ) );
						$message    = implode( '<br>', $messages );
						$this->set_message( $message );
					}
				}
			} else {
				$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
			}
		}
	}

	/**
	 * Update results in league in admin screen
	 */
	private function update_results_in_league() {
		global $league;
		if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_matches-bulk' ) ) {
			$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
		} elseif ( current_user_can( 'update_results' ) ) {
				//phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$custom      = isset( $_POST['custom'] ) ? $_POST['custom'] : array();
				$matches     = isset( $_POST['matches'] ) ? $_POST['matches'] : array();
				$home_points = isset( $_POST['home_points'] ) ? $_POST['home_points'] : array();
				$away_points = isset( $_POST['away_points'] ) ? $_POST['away_points'] : array();
				//phpcs:enable WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$season = isset( $_POST['season'] ) ? sanitize_text_field( wp_unslash( $_POST['season'] ) ) : null;
			if ( ! empty( $matches ) && ! empty( $home_points ) && ! empty( $away_points ) && ! empty( $season ) ) {
				if ( ! current_user_can( 'update_results' ) ) {
					$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
				} else {
					$league->set_finals( false );
					$num_matches = $league->update_match_results( $matches, $home_points, $away_points, $custom, $season, false );
					/* translators: %d: number of matches updated */
					$this->set_message( sprintf( __( 'Updated Results of %d matches', 'racketmanager' ), $num_matches ) );
				}
			}
				$match_day = isset( $_POST['current_match_day'] ) ? intval( $_POST['current_match_day'] ) : null;
		} else {
			$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
		}
	}

	/**
	 * Rank teams in league after manually adjusting points in admin screen
	 *
	 * @param object $league league object.
	 */
	private function league_manual_rank( $league ) {
		if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_teams-bulk' ) ) {
			$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
		} elseif ( current_user_can( 'update_results' ) ) {
				$points = array();
			if ( isset( $_POST['points_plus'] ) && isset( $_POST['points_minus'] ) && isset( $_POST['add_points'] ) && isset( $_POST['num_done_matches'] ) && isset( $_POST['num_won_matches'] ) && isset( $_POST['num_draw_matches'] ) && isset( $_POST['num_lost_matches'] ) ) {
				$league = get_league( $league );
				//phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$points['points_plus']       = $_POST['points_plus'];
				$points['points_minus']      = $_POST['points_minus'];
				$points['add_points']        = $_POST['add_points'];
				$matches                     = array();
				$matches['num_done_matches'] = $_POST['num_done_matches'];
				$matches['num_won_matches']  = $_POST['num_won_matches'];
				$matches['num_draw_matches'] = $_POST['num_draw_matches'];
				$matches['num_lost_matches'] = $_POST['num_lost_matches'];
				if ( isset( $_POST['team_id'] ) && isset( $_POST['custom'] ) ) {
					$league->save_standings_manually( $_POST['team_id'], $points, $matches, $_POST['custom'] );
					$this->set_message( __( 'Standings Table updated', 'racketmanager' ) );
				}
				//phpcs:enable WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			}
		} else {
			$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
		}
	}

	/**
	 * Manually rank teams league in admin screen
	 *
	 * @param object $league league object.
	 */
	private function league_manual_rank_teams( $league ) {
		if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_teams-bulk' ) ) {
			$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
		} elseif ( current_user_can( 'update_results' ) ) {
			if ( isset( $_POST['js-active'] ) && '1' === $_POST['js-active'] ) {
				$js = true;
			} else {
				$js = false;
			}
				$team_ranks = array();
				$league     = get_league( $league );
			if ( isset( $_POST['table_id'] ) ) {
				$team_ids = array_values( $_POST['table_id'] ); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				foreach ( $team_ids as $key => $team_id ) {
					if ( $js ) {
						$rank = $key + 1;
					} else {
						$rank = isset( $_POST['rank'][ $team_id ] ) ? intval( $_POST['rank'][ $team_id ] ) : 0;
					}
					$team                    = get_league_team( $team_id );
					$team_ranks[ $rank - 1 ] = $team;
				}
				ksort( $team_ranks );
				$team_ranks = $league->get_ranking( $team_ranks );
				$league->update_ranking( $team_ranks );
				$this->set_message( __( 'Team ranking saved', 'racketmanager' ) );
			}
		} else {
			$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
		}
	}

	/**
	 * Randomly rank teams league in admin screen
	 *
	 * @param object $league league object.
	 */
	private function league_random_rank_teams( $league ) {
		if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_teams-bulk' ) ) {
			$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
		} elseif ( current_user_can( 'update_results' ) ) {
				$league = get_league( $league );
			$team_ranks = array();
			if ( isset( $_POST['table_id'] ) ) {
				$team_ids = array_values( $_POST['table_id'] ); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				shuffle( $team_ids );
				foreach ( $team_ids as $key => $team_id ) {
					$rank                    = $key + 1;
					$team                    = get_league_team( $team_id );
					$team_ranks[ $rank - 1 ] = $team;
				}
				$team_ranks = $league->get_ranking( $team_ranks );
				$league->update_ranking( $team_ranks );
				$this->set_message( __( 'Team ranking saved', 'racketmanager' ) );
			}
		} else {
			$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
		}
	}

	/**
	 * Contact teams in league in admin screen
	 */
	private function league_contact_teams() {
		if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_contact-teams-preview' ) ) {
			$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
		} elseif ( current_user_can( 'edit_teams' ) ) {
			if ( isset( $_POST['league_id'] ) && isset( $_POST['season'] ) && isset( $_POST['emailMessage'] ) ) {
				$this->contactLeagueTeams( intval( $_POST['league_id'] ), sanitize_text_field( wp_unslash( $_POST['season'] ) ), htmlspecialchars_decode( $_POST['emailMessage'] ) ); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			}
		} else {
			$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
		}
	}

	/**
	 * Display teams list page
	 */
	private function display_teams_list() {
		global $racketmanager;
		//phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! current_user_can( 'edit_teams' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} elseif ( isset( $_GET['league_id'] ) ) {
			$league_id   = intval( $_GET['league_id'] );
			$league      = get_league( $league_id );
			$league_type = $league->type;
			if ( 'LD' === $league_type ) {
				$league_type = 'XD';
			}
			if ( $league->event->competition->is_player_entry ) {
				$entry_type = 'player';
			} else {
				$entry_type = '';
			}
			$teams = array();
			if ( empty( $league->championship->is_consolation ) ) {
				$clubs = $racketmanager->get_clubs();
				if ( $clubs ) {
					foreach ( $clubs as $club ) {
						$club       = get_club( $club );
						$club_teams = $club->get_teams( $entry_type, $league_type );
						if ( $club_teams ) {
							foreach ( $club_teams as $team ) {
								$teams[] = $team;
							}
						}
					}
				}
			} else {
				$primary_league = get_league( $league->event->primary_league );
				$teams          = $primary_league->get_league_teams( array() );
				$t              = 0;
				foreach ( $teams as $team ) {
					$match_array                     = array();
					$match_array['loser_id']         = $team->id;
					$match_array['count']            = true;
					$match_array['final']            = 'all';
					$match_array['reset_query_args'] = true;
					$matches                         = $primary_league->get_matches( $match_array );
					if ( ! $matches ) { // team did not lose a match.
						unset( $teams[ $t ] );
					} else {
						$match_array['loser_id'] = null;
						$match_array['team_id']  = $team->id;
						$matches                 = $primary_league->get_matches( $match_array );
						$last_match              = null;
						if ( $matches > 2 ) { // team played more than 2 matches.
							unset( $teams[ $t ] );
						} elseif ( 2 === $matches ) { // team played 2 matches in main league.
							$match_array['count'] = false;
							$matches              = $primary_league->get_matches( $match_array );
							if ( $matches ) {
								$first_match = $matches[0];
								$last_match  = null;
								if ( '-1' !== $first_match->home_team && '-1' !== $first_match->away_team ) { // first match not a bye.
									unset( $teams[ $t ] );
								} else {
									$last_match = $matches[1];
								}
							}
						} elseif ( 1 === $matches ) {
							$match_array['count'] = false;
							$matches              = $primary_league->get_matches( $match_array );
							$last_match           = $matches[0];
						}
						if ( $last_match && $last_match->is_walkover ) {
							unset( $teams[ $t ] );
						}
					}
					++$t;
				}
				$pending_teams                   = array();
				$match_array                     = array();
				$match_array['reset_query_args'] = true;
				$final_name                      = $primary_league->championship->get_final_keys( 1 );
				$match_array['final']            = $final_name;
				$match_array['pending']          = true;
				$matches                         = $primary_league->get_matches( $match_array );
				if ( $matches ) {
					foreach ( $matches as $match ) {
						$team          = new \stdClass();
						$team->id      = '2_' . $final_name . '_' . $match->id;
						$team->title   = __( 'Loser of ', 'racketmanager' ) . $match->teams['home']->title . ' ' . __( 'vs', 'racketmanager' ) . ' ' . $match->teams['away']->title;
						$team->stadium = '';
						$teams[]       = $team;
					}
				} else {
					$final_name           = $primary_league->championship->get_final_keys( 2 );
					$match_array['final'] = $final_name;
					$matches              = $primary_league->get_matches( $match_array );
					if ( $matches ) {
						foreach ( $matches as $match ) {
							$team          = new \stdClass();
							$team->id      = '2_' . $final_name . '_' . $match->id;
							$team->title   = __( 'Loser of ', 'racketmanager' ) . $match->teams['home']->title . ' ' . __( 'vs', 'racketmanager' ) . ' ' . $match->teams['away']->title;
							$team->stadium = '';
							$teams[]       = $team;
						}
					}
				}
			}
			$season = isset( $_GET['season'] ) ? sanitize_text_field( wp_unslash( $_GET['season'] ) ) : '';
			$view   = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : '';
			//phpcs:enable WordPress.Security.NonceVerification.Recommended
			include_once RACKETMANAGER_PATH . '/admin/includes/teamslist.php';
		}
	}

	/**
	 * Display leagues page
	 */
	private function display_leagues_page() {
		if ( ! current_user_can( 'edit_leagues' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			$competition_type  = 'league';
			$type              = '';
			$season            = '';
			$standalone        = true;
			$competition_query = array( 'type' => $competition_type );
			$page_title        = ucfirst( $competition_type ) . ' ' . __( 'Competitions', 'racketmanager' );
			include_once RACKETMANAGER_PATH . '/admin/show-competitions.php';
		}
	}

	/**
	 * Display cups page
	 */
	private function displayCupsPage() {
		if ( ! current_user_can( 'edit_leagues' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			$competition_type  = 'cup';
			$type              = '';
			$season            = '';
			$standalone        = true;
			$competition_query = array( 'type' => $competition_type );
			$page_title        = ucfirst( $competition_type ) . ' ' . __( 'Competitions', 'racketmanager' );
			include_once RACKETMANAGER_PATH . '/admin/show-competitions.php';
		}
	}

	/**
	 * Display tournaments page
	 */
	private function displayTournamentsPage() {
		if ( ! current_user_can( 'edit_leagues' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			if ( isset( $_POST['addTournament'] ) ) {
				if ( ! current_user_can( 'edit_teams' ) ) {
					$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
				} else {
					check_admin_referer( 'racketmanager_add-tournament' );
					$tournament                 = new \stdClass();
					$tournament->name           = isset( $_POST['tournament'] ) ? sanitize_text_field( wp_unslash( $_POST['tournament'] ) ) : null;
					$tournament->competition_id = isset( $_POST['competition_id'] ) ? intval( $_POST['competition_id'] ) : null;
					$tournament->season         = isset( $_POST['season'] ) ? sanitize_text_field( wp_unslash( $_POST['season'] ) ) : null;
					$tournament->venue          = isset( $_POST['venue'] ) ? intval( $_POST['venue'] ) : null;
					$tournament->date_open      = isset( $_POST['date_open'] ) ? sanitize_text_field( wp_unslash( $_POST['date_open'] ) ) : null;
					$tournament->closing_date   = isset( $_POST['closingdate'] ) ? sanitize_text_field( wp_unslash( $_POST['closingdate'] ) ) : null;
					$tournament->date_start     = isset( $_POST['date_start'] ) ? sanitize_text_field( wp_unslash( $_POST['date_start'] ) ) : null;
					$tournament->date           = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : null;
					$tournament->starttime      = isset( $_POST['starttime'] ) ? sanitize_text_field( wp_unslash( $_POST['starttime'] ) ) : null;
					$success                    = new Racketmanager_Tournament( $tournament );
					if ( $success ) {
						$this->set_competition_dates( $tournament );
					}
					$this->printMessage();
				}
			} elseif ( isset( $_POST['doTournamentDel'] ) && isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) {
				if ( ! current_user_can( 'del_teams' ) ) {
					$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
				} else {
					check_admin_referer( 'tournaments-bulk' );
					foreach ( $_POST['tournament'] as $tournament_id ) { //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
						$tournament = get_tournament( $tournament_id );
						$tournament->delete();
					}
				}
				$this->printMessage();
			}
			$club_id = 0;
			$this->printMessage();
			$clubs = $this->get_clubs();
			include_once RACKETMANAGER_PATH . '/admin/show-tournaments.php';
		}
	}
	/**
	 * Set competition dates for tournament function
	 *
	 * @param object $tournament tournament.
	 * @return void
	 */
	private function set_competition_dates( $tournament ) {
		$competition = get_competition( $tournament->competition_id );
		if ( $competition ) {
			$season = isset( $competition->seasons[ $tournament->season ] ) ? $competition->seasons[ $tournament->season ] : null;
			if ( $season ) {
				$updates = false;
				if ( empty( $season['dateEnd'] ) || $season['dateEnd'] !== $tournament->date ) {
					$updates           = true;
					$season['dateEnd'] = $tournament->date;
				}
				if ( empty( $season['dateStart'] ) || $season['dateStart'] !== $tournament->date_start ) {
					$updates             = true;
					$season['dateStart'] = $tournament->date_start;
				}
				if ( empty( $season['closing_date'] ) || $season['closing_date'] !== $tournament->closing_date ) {
					$updates                = true;
					$season['closing_date'] = $tournament->closing_date;
				}
				if ( $updates ) {
					$season_data                 = new \stdclass();
					$season_data->season         = $season['name'];
					$season_data->num_match_days = $season['num_match_days'];
					$season_data->object_id      = $competition->id;
					$season_data->match_dates    = isset( $season['matchDates'] ) ? $season['matchDates'] : false;
					$season_data->fixed_dates    = isset( $season['fixedMatchDates'] ) ? $season['fixedMatchDates'] : false;
					$season_data->home_away      = isset( $season['homeAway'] ) ? $season['homeAway'] : false;
					$season_data->status         = $season['status'];
					$season_data->closing_date   = $season['closing_date'];
					$season_data->date_start     = $season['dateStart'];
					$season_data->date_end       = $season['dateEnd'];
					$season_data->type           = 'competition';
					$season_data->is_box         = false;
					$this->edit_season( $season_data );
				}
			} else {
				$competition_season = $this->add_season_to_competition( $tournament->season, $tournament->competition_id );
				if ( $competition_season ) {
					$season_data                 = new \stdclass();
					$season_data->season         = $competition_season['name'];
					$season_data->num_match_days = $competition_season['num_match_days'];
					$season_data->object_id      = $competition->id;
					$season_data->match_dates    = false;
					$season_data->fixed_dates    = false;
					$season_data->home_away      = false;
					$season_data->status         = $competition_season['status'];
					$season_data->closing_date   = $tournament->closing_date;
					$season_data->date_start     = $tournament->date_start;
					$season_data->date_end       = $tournament->date;
					$season_data->type           = 'competition';
					$season_data->is_box         = false;
					$this->edit_season( $season_data );
				}
			}
		}
	}
	/**
	 * Display tournament page
	 */
	private function displayTournamentPage() {
		if ( ! current_user_can( 'edit_teams' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} elseif ( isset( $_POST['editTournament'] ) ) {
			if ( ! current_user_can( 'edit_teams' ) ) {
				$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
			} else {
				check_admin_referer( 'racketmanager_manage-tournament' );
				if ( isset( $_POST['tournament_id'] ) ) {
					$tournament_id = intval( $_POST['tournament_id'] );
					$tournament    = get_tournament( $tournament_id );
					if ( $tournament ) {
						$tournament_updates               = clone $tournament;
						$tournament_updates->name         = isset( $_POST['tournament'] ) ? sanitize_text_field( wp_unslash( $_POST['tournament'] ) ) : null;
						$tournament_updates->season       = isset( $_POST['season'] ) ? sanitize_text_field( wp_unslash( $_POST['season'] ) ) : null;
						$tournament_updates->venue        = isset( $_POST['venue'] ) ? intval( $_POST['venue'] ) : null;
						$tournament_updates->date         = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : null;
						$tournament_updates->date_open    = isset( $_POST['date_open'] ) ? sanitize_text_field( wp_unslash( $_POST['date_open'] ) ) : null;
						$tournament_updates->closing_date = isset( $_POST['closingdate'] ) ? sanitize_text_field( wp_unslash( $_POST['closingdate'] ) ) : null;
						$tournament_updates->date_start   = isset( $_POST['date_start'] ) ? sanitize_text_field( wp_unslash( $_POST['date_start'] ) ) : null;
						$tournament_updates->starttime    = isset( $_POST['starttime'] ) ? sanitize_text_field( wp_unslash( $_POST['starttime'] ) ) : null;
						$success                          = $tournament->update( $tournament_updates );
						if ( $success ) {
							$this->set_competition_dates( $tournament_updates );
						}
					} else {
						$this->set_message( __( 'Tournament not found', 'racketmanager' ), true );
					}
				}
			}
			$this->printMessage();
		} elseif ( isset( $_GET['tournament_id'] ) ) {
			$tournament_id = intval( $_GET['tournament_id'] ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$tournament    = get_tournament( $tournament_id );
		} else {
			$tournament_id = null;
		}
		$edit = false;
		if ( $tournament_id ) {
			$edit        = true;
			$form_title  = __( 'Edit Tournament', 'racketmanager' );
			$form_action = __( 'Update', 'racketmanager' );
		} else {
			$form_title  = __( 'Add Tournament', 'racketmanager' );
			$form_action = __( 'Add', 'racketmanager' );
			$tournament  = (object) array(
				'name'           => '',
				'competition_id' => '',
				'id'             => '',
				'venue'          => '',
				'date'           => '',
				'closingdate'    => '',
				'numcourts'      => '',
				'starttime'      => '',
				'date_open'      => '',
				'closing_date'   => '',
				'date_start'     => '',
			);
		}
		$clubs             = $this->get_clubs(
			array(
				'type' => 'affiliated',
			)
		);
		$competition_query = array( 'type' => 'tournament' );
		$competitions      = $this->get_competitions( $competition_query );
		include_once RACKETMANAGER_PATH . '/admin/show-tournament.php';
	}

	/**
	 * Display tournament plan page
	 */
	private function displayTournamentPlanPage() {
		if ( ! current_user_can( 'edit_teams' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			if ( isset( $_POST['saveTournamentPlan'] ) ) {
				check_admin_referer( 'racketmanager_tournament-planner' );
				if ( isset( $_POST['tournamentId'] ) ) {
					// phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					$tournament = get_tournament( intval( $_POST['tournamentId'] ) );
					$courts     = isset( $_POST['court'] ) ? $_POST['court'] : null;
					$start_time = isset( $_POST['starttime'] ) ? $_POST['starttime'] : null;
					$matches    = isset( $_POST['match'] ) ? $_POST['match'] : null;
					$match_time = isset( $_POST['matchtime'] ) ? $_POST['matchtime'] : null;
					// phpcs:enable WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					$tournament->save_plan( $courts, $start_time, $matches, $match_time );
					$this->printMessage();
				}
			} elseif ( isset( $_POST['resetTournamentPlan'] ) ) {
				check_admin_referer( 'racketmanager_tournament-planner' );
				if ( isset( $_POST['tournamentId'] ) ) {
					$tournament = get_tournament( intval( $_POST['tournamentId'] ) );
					$tournament->reset_plan();
					$this->printMessage();
				}
			} elseif ( isset( $_POST['saveTournament'] ) ) {
				check_admin_referer( 'racketmanager_tournament' );
				if ( isset( $_POST['tournamentId'] ) ) {
					$tournament     = get_tournament( intval( $_POST['tournamentId'] ) );
					$start_time     = isset( $_POST['starttime'] ) ? sanitize_text_field( wp_unslash( $_POST['starttime'] ) ) : null;
					$num_courts     = isset( $_POST['numcourts'] ) ? intval( $_POST['numcourts'] ) : null;
					$time_increment = isset( $_POST['timeincrement'] ) ? sanitize_text_field( wp_unslash( $_POST['timeincrement'] ) ) : null;
					$tournament->update_plan( $start_time, $num_courts, $time_increment );
					$this->printMessage();
				}
			}

			if ( isset( $_GET['tournament'] ) ) {
				$tournament_id = intval( $_GET['tournament'] );
				$tournament    = get_tournament( $tournament_id );
				$final_matches = $this->get_matches(
					array(
						'season'         => $tournament->season,
						'final'          => 'final',
						'competition_id' => $tournament->competition_id,
					)
				);
			}
			include_once RACKETMANAGER_PATH . '/admin/includes/tournament-plan.php';
		}
	}

	/**
	 * Display clubs page
	 */
	private function display_clubs_page() {
		global $club;

		if ( ! current_user_can( 'edit_leagues' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			if ( isset( $_POST['addClub'] ) ) {
				check_admin_referer( 'racketmanager_add-club' );
				if ( ! current_user_can( 'edit_teams' ) ) {
					$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
				} else {
					$club             = new \stdClass();
					$club->name       = isset( $_POST['club'] ) ? sanitize_text_field( wp_unslash( $_POST['club'] ) ) : null;
					$club->type       = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : null;
					$club->shortcode  = isset( $_POST['shortcode'] ) ? sanitize_text_field( wp_unslash( $_POST['shortcode'] ) ) : null;
					$club->contactno  = isset( $_POST['contactno'] ) ? sanitize_text_field( wp_unslash( $_POST['contactno'] ) ) : null;
					$club->website    = isset( $_POST['website'] ) ? sanitize_text_field( wp_unslash( $_POST['website'] ) ) : null;
					$club->founded    = isset( $_POST['founded'] ) ? intval( $_POST['founded'] ) : null;
					$club->facilities = isset( $_POST['facilities'] ) ? sanitize_text_field( wp_unslash( $_POST['facilities'] ) ) : null;
					$club->address    = isset( $_POST['address'] ) ? sanitize_text_field( wp_unslash( $_POST['address'] ) ) : null;
					$club->latitude   = isset( $_POST['latitude'] ) ? sanitize_text_field( wp_unslash( $_POST['latitude'] ) ) : null;
					$club->longitude  = isset( $_POST['longitude'] ) ? sanitize_text_field( wp_unslash( $_POST['longitude'] ) ) : null;
					$club             = new Racketmanager_Club( $club );
					$this->set_message( __( 'Club added', 'racketmanager' ) );
				}
				$this->printMessage();
			} elseif ( isset( $_POST['editClub'] ) ) {
				check_admin_referer( 'racketmanager_manage-club' );
				if ( ! current_user_can( 'edit_teams' ) ) {
					$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
				} elseif ( isset( $_POST['club_id'] ) ) {
						$club          = get_club( intval( $_POST['club_id'] ) );
						$club->name    = isset( $_POST['club'] ) ? sanitize_text_field( wp_unslash( $_POST['club'] ) ) : null;
						$club->type    = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : null;
						$old_shortcode = $club->shortcode;
					if ( $club->shortcode !== $_POST['shortcode'] ) {
						$club->shortcode = isset( $_POST['shortcode'] ) ? sanitize_text_field( wp_unslash( $_POST['shortcode'] ) ) : null;
					}
						$club->matchsecretary             = isset( $_POST['match_secretary'] ) ? intval( $_POST['match_secretary'] ) : null;
						$club->match_secretary_contact_no = isset( $_POST['match_secretary_contact_no'] ) ? sanitize_text_field( wp_unslash( $_POST['match_secretary_contact_no'] ) ) : null;
						$club->match_secretary_email      = isset( $_POST['match_secretary_email'] ) ? sanitize_text_field( wp_unslash( $_POST['match_secretary_email'] ) ) : null;
						$club->contactno                  = isset( $_POST['contactno'] ) ? sanitize_text_field( wp_unslash( $_POST['contactno'] ) ) : null;
						$club->website                    = isset( $_POST['website'] ) ? sanitize_text_field( wp_unslash( $_POST['website'] ) ) : null;
						$club->founded                    = isset( $_POST['founded'] ) ? intval( $_POST['founded'] ) : null;
						$club->facilities                 = isset( $_POST['facilities'] ) ? sanitize_text_field( wp_unslash( $_POST['facilities'] ) ) : null;
						$club->address                    = isset( $_POST['address'] ) ? sanitize_text_field( wp_unslash( $_POST['address'] ) ) : null;
						$club->latitude                   = isset( $_POST['latitude'] ) ? sanitize_text_field( wp_unslash( $_POST['latitude'] ) ) : null;
						$club->longitude                  = isset( $_POST['longitude'] ) ? sanitize_text_field( wp_unslash( $_POST['longitude'] ) ) : null;
						$club->update( $club, $old_shortcode );
						$this->set_message( __( 'Club updated', 'racketmanager' ) );
				}
				$this->printMessage();
			} elseif ( isset( $_POST['doClubDel'] ) && isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) {
				check_admin_referer( 'clubs-bulk' );
				if ( ! current_user_can( 'del_teams' ) ) {
					$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
				} else {
					$messages      = array();
					$message_error = false;
					foreach ( $_POST['club'] as $club_id ) { //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
						$club = get_club( $club_id );
						if ( $club->has_teams() ) {
							$messages[]    = $club->name . ' ' . __( 'not deleted - still has teams attached', 'racketmanager' );
							$message_error = true;
						} else {
							$club->delete();
							$messages[] = $club->name . ' ' . __( 'deleted', 'racketmanager' );
						}
					}
					$message = implode( '<br>', $messages );
					$this->set_message( $message, $message_error );
					$club_id = 0;
				}

				$this->printMessage();
			}
			include_once RACKETMANAGER_PATH . '/admin/show-clubs.php';
		}
	}

	/**
	 * Display club page
	 */
	private function display_club_page() {
		if ( ! current_user_can( 'edit_teams' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			$edit      = false;
			$league_id = '';
			$season    = '';
			if ( isset( $_GET['club_id'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$club_id     = intval( $_GET['club_id'] ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$edit        = true;
				$club        = get_club( $club_id );
				$form_title  = __( 'Edit Club', 'racketmanager' );
				$form_action = __( 'Update', 'racketmanager' );
			} else {
				$club_id     = '';
				$form_title  = __( 'Add Club', 'racketmanager' );
				$form_action = __( 'Add', 'racketmanager' );
				$club        = (object) array(
					'name'                       => '',
					'type'                       => '',
					'id'                         => '',
					'website'                    => '',
					'matchsecretary'             => '',
					'match_secretary_name'       => '',
					'contactno'                  => '',
					'match_secretary_contact_no' => '',
					'match_secretary_email'      => '',
					'shortcode'                  => '',
					'founded'                    => '',
					'facilities'                 => '',
					'address'                    => '',
					'latitude'                   => '',
					'longitude'                  => '',
				);
			}
			include_once RACKETMANAGER_PATH . '/admin/includes/club.php';
		}
	}

	/**
	 * Display club players page
	 */
	private function display_club_players_page() {
		global $racketmanager;

		if ( ! current_user_can( 'edit_teams' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			if ( isset( $_POST['addPlayer'] ) ) {
				check_admin_referer( 'racketmanager_manage-player' );
				$player_valid = $this->validatePlayer();
				if ( $player_valid[0] ) {
					$new_player = $player_valid[1];
					if ( isset( $_POST['club_Id'] ) ) {
						$club = get_club( intval( $_POST['club_Id'] ) );
						$club->register_player( $new_player );
					}
				} else {
					$form_valid     = false;
					$error_fields   = $player_valid[1];
					$error_messages = $player_valid[2];
					$message        = __( 'Error with player details', 'racketmanager' );
					foreach ( $error_messages as $error_message ) {
						$message .= '<br>' . $error_message;
						$this->set_message( $message, true );
					}
				}
			} elseif ( isset( $_POST['doClubPlayerdel'] ) && isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) {
				check_admin_referer( 'club-players-bulk' );
				if ( isset( $_POST['clubPlayer'] ) ) {
					foreach ( $_POST['clubPlayer'] as $roster_id ) { //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
						$racketmanager->delete_club_player( intval( $roster_id ) );
					}
				}
			}
			$this->printMessage();
			if ( isset( $_GET['club_id'] ) ) {
				$club_id = intval( $_GET['club_id'] );
			}
			$club    = get_club( $club_id );
			$active  = isset( $_GET['active'] ) ? sanitize_text_field( wp_unslash( $_GET['active'] ) ) : false;
			$gender  = isset( $_GET['gender'] ) ? sanitize_text_field( wp_unslash( $_GET['gender'] ) ) : false;
			$players = $club->get_players(
				array(
					'active' => $active,
					'gender' => $gender,
				)
			);
			include_once RACKETMANAGER_PATH . '/admin/club/show-club-players.php';
		}
	}

	/**
	 * Display player page
	 */
	private function display_player_page() {
		if ( ! current_user_can( 'edit_teams' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			$form_valid = true;
			if ( isset( $_POST['updatePlayer'] ) ) {
				check_admin_referer( 'racketmanager_manage-player' );
				$player_valid = $this->validatePlayer();
				if ( $player_valid[0] ) {
					if ( isset( $_POST['player_id'] ) ) {
						$player     = get_player( intval( $_POST['player_id'] ) );
						$new_player = $player_valid[1];
						$player->update( $new_player );
					}
				} else {
					$form_valid     = false;
					$error_fields   = $player_valid[1];
					$error_messages = $player_valid[2];
					$message        = __( 'Error with player details', 'racketmanager' );
					foreach ( $error_messages as $error_message ) {
						$message .= '<br>' . $error_message;
						$this->set_message( $message, true );
					}
				}
			}
			$this->printMessage();
			if ( isset( $_GET['club_id'] ) ) {
				$club_id = intval( $_GET['club_id'] );
			}
			if ( isset( $_GET['player_id'] ) ) {
				$player_id = intval( $_GET['player_id'] );
			}
			$player = get_player( $player_id );
			include_once RACKETMANAGER_PATH . '/admin/players/show-player.php';
		}
	}

	/**
	 * Validate player
	 *
	 * @return array
	 */
	public function validatePlayer() {
		$options = $this->get_options( 'rosters' );
		if ( isset( $options['btm'] ) && '1' === $options['btm'] ) {
			$btm_required = true;
		} else {
			$btm_required = false;
		}

		$return        = array();
		$valid         = true;
		$error_field   = array();
		$error_message = array();
		$error_id      = 0;
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['firstname'] ) && '' === sanitize_text_field( wp_unslash( $_POST['firstname'] ) ) ) {
			$valid                      = false;
			$error_field[ $error_id ]   = 'firstname';
			$error_message[ $error_id ] = 'First name required';
			++$error_id;
		} else {
			$firstname = trim( sanitize_text_field( wp_unslash( $_POST['firstname'] ) ) );
		}
		if ( isset( $_POST['surname'] ) && '' === sanitize_text_field( wp_unslash( $_POST['surname'] ) ) ) {
			$valid                      = false;
			$error_field[ $error_id ]   = 'surname';
			$error_message[ $error_id ] = 'Surname required';
			++$error_id;
		} else {
			$surname = trim( sanitize_text_field( wp_unslash( $_POST['surname'] ) ) );
		}
		if ( ! isset( $_POST['gender'] ) || '' === sanitize_text_field( wp_unslash( $_POST['gender'] ) ) ) {
			$valid                      = false;
			$error_field[ $error_id ]   = 'gender';
			$error_message[ $error_id ] = 'Gender required';
			++$error_id;
		} else {
			$gender = sanitize_text_field( wp_unslash( $_POST['gender'] ) );
		}
		if ( ! isset( $_POST['btm'] ) || 0 === intval( $_POST['btm'] ) ) {
			if ( $btm_required ) {
				$valid                      = false;
				$error_field[ $error_id ]   = 'btm';
				$error_message[ $error_id ] = 'LTA Tennis Number required';
				++$error_id;
			} else {
				$btm = '';
			}
		} else {
			$btm = intval( $_POST['btm'] );
		}
		if ( ! isset( $_POST['contactno'] ) || '' === intval( $_POST['contactno'] ) ) {
			$contactno = '';
		} else {
			$contactno = sanitize_text_field( wp_unslash( $_POST['contactno'] ) );
		}
		if ( ! isset( $_POST['email'] ) || '' === sanitize_text_field( wp_unslash( $_POST['email'] ) ) ) {
			$email = '';
		} else {
			$email = sanitize_text_field( wp_unslash( $_POST['email'] ) );
		}
		if ( ! isset( $_POST['locked'] ) || '' === sanitize_text_field( wp_unslash( $_POST['locked'] ) ) ) {
			$locked = '';
		} else {
			$locked = sanitize_text_field( wp_unslash( $_POST['locked'] ) );
		}
		if ( ! isset( $_POST['year_of_birth'] ) || 0 === intval( $_POST['year_of_birth'] ) ) {
			$year_of_birth = '';
		} else {
			$year_of_birth = intval( $_POST['year_of_birth'] );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		if ( $valid ) {
			$player                = new \stdClass();
			$player->data          = array();
			$player->firstname     = $firstname;
			$player->surname       = $surname;
			$player->fullname      = $firstname . ' ' . $surname;
			$player->user_login    = strtolower( $firstname ) . '.' . strtolower( $surname );
			$player->email         = $email;
			$player->btm           = $btm;
			$player->contactno     = $contactno;
			$player->gender        = $gender;
			$player->locked        = $locked;
			$player->year_of_birth = $year_of_birth;
			array_push( $return, $valid, $player );
		} else {
			array_push( $return, $valid, $error_field, $error_message );
		}
		return $return;
	}

	/**
	 * Display competitions list page
	 */
	private function displayCompetitionsList() {
		global $racketmanager;

		if ( ! current_user_can( 'edit_teams' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			if ( isset( $_GET['season'] ) ) {
				$season = sanitize_text_field( wp_unslash( $_GET['season'] ) );
				$season = $racketmanager->get_season( array( 'name' => $season ) );
			}
			if ( isset( $_GET['tournament'] ) ) {
				$tournament = get_tournament( intval( $_GET['tournament'] ) );
			}
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
			include_once RACKETMANAGER_PATH . '/admin/includes/competitions-list.php';
		}
	}

	/**
	 * Display teams page
	 */
	private function display_teams_page() {
		if ( ! current_user_can( 'edit_teams' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			if ( isset( $_POST['addTeam'] ) ) {
				check_admin_referer( 'racketmanager_add-team' );
				if ( isset( $_POST['affiliatedClub'] ) && isset( $_POST['team_type'] ) ) {
					$club = get_club( intval( $_POST['affiliatedClub'] ) );
					$club->add_team( sanitize_text_field( wp_unslash( $_POST['team_type'] ) ) );
				}
			} elseif ( isset( $_POST['editTeam'] ) ) {
				check_admin_referer( 'racketmanager_manage-teams' );
				if ( ! current_user_can( 'edit_teams' ) ) {
					$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
				} elseif ( isset( $_POST['team_id'] ) ) {
						$team = get_team( intval( $_POST['team_id'] ) );
					if ( isset( $_POST['team'] ) && isset( $_POST['affiliatedclub'] ) && isset( $_POST['team_type'] ) ) {
						$team->update( sanitize_text_field( wp_unslash( $_POST['team'] ) ), intval( $_POST['affiliatedclub'] ), sanitize_text_field( wp_unslash( $_POST['team_type'] ) ) );
					}
				}
			} elseif ( isset( $_POST['doteamdel'] ) && isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) {
				if ( ! current_user_can( 'del_teams' ) ) {
					$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
				} else {
					check_admin_referer( 'teams-bulk' );
					$messages      = array();
					$message_error = false;
					if ( isset( $_POST['team'] ) ) {
						foreach ( $_POST['team'] as $team_id ) { //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
							$team = get_team( $team_id );
							$team->delete();
							$messages[] = $team->title . ' ' . __( 'deleted', 'racketmanager' );
						}
						$message = implode( '<br>', $messages );
						$this->set_message( $message, $message_error );
					}
				}
			}
			$this->printMessage();
			if ( isset( $_GET['club_id'] ) ) {
				$club_id = intval( $_GET['club_id'] );
			}
			$club = get_club( $club_id );
			include_once RACKETMANAGER_PATH . '/admin/club/show-teams.php';
		}
	}

	/**
	 * Display team page
	 */
	private function display_team_page() {
		global $racketmanager;
		if ( ! current_user_can( 'edit_teams' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			$file   = 'team.php';
			$edit   = false;
			$league = false;
			//phpcs:disable WordPress.Security.NonceVerification.Recommended
			if ( isset( $_GET['league_id'] ) ) {
				$league_id = intval( $_GET['league_id'] );
				$league    = get_league( $league_id );
				$season    = isset( $_GET['season'] ) ? sanitize_text_field( wp_unslash( $_GET['season'] ) ) : '';
				$matchdays = array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday' );
				if ( $league->event->competition->is_player_entry ) {
					$file = 'playerteam.php';
				}
			} else {
				$league_id = '';
				$season    = '';
				if ( isset( $_GET['club_id'] ) ) {
					$club_id = intval( $_GET['club_id'] );
				} else {
					$club_id = '';
				}
			}

			if ( isset( $_GET['edit'] ) ) {
				$edit = true;
				if ( $league ) {
					$team = $league->get_team_dtls( intval( $_GET['edit'] ) );
				} else {
					$team = get_team( intval( $_GET['edit'] ) );
				}
				if ( ! isset( $team->roster ) ) {
					$team->roster = array();
				}
				$form_title  = __( 'Edit Team', 'racketmanager' );
				$form_action = __( 'Update', 'racketmanager' );
			} else {
				$form_title         = __( 'Add Team', 'racketmanager' );
				$form_action        = __( 'Add', 'racketmanager' );
				$team               = new \stdClass();
				$team->id           = '';
				$team->title        = '';
				$team->captain      = '';
				$team->captain_id   = '';
				$team->contactno    = '';
				$team->contactemail = '';
				$team->match_day    = '';
				$team->match_time   = '';
			}
			$clubs = $racketmanager->get_clubs();
			//phpcs:enable WordPress.Security.NonceVerification.Recommended
			require_once RACKETMANAGER_PATH . '/admin/includes/teams/' . $file;
		}
	}

	/**
	 * Display match editing page
	 */
	private function display_match_page() {
		global $wpdb, $competition;

		if ( ! current_user_can( 'edit_matches' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			//phpcs:disable WordPress.Security.NonceVerification.Recommended
			$is_finals       = false;
			$finalkey        = false;
			$cup             = false;
			$single_cup_game = false;
			$group           = isset( $_GET['group'] ) ? sanitize_text_field( wp_unslash( $_GET['group'] ) ) : null;
			$class           = 'alternate';
			$bulk            = false;
			if ( isset( $_GET['league_id'] ) ) {
				$league_id = intval( $_GET['league_id'] );
				$league    = get_league( $league_id );
				$non_group = ( isset( $league->non_group ) ? $league->non_group : 0 );

				// check if league is a cup championship.
				$cup = ( $league->event->competition->is_championship ) ? true : false;
			}
			$season = $league->current_season['name'];

			// select first group if none is selected and league is cup championship.
			if ( $cup && empty( $group ) && ! $is_finals ) {
				$groups = ( isset( $league->groups ) ? $league->groups : '' );
				if ( ! is_array( $groups ) ) {
					$groups = explode( ';', $groups );
				}
				if ( isset( $groups[0] ) ) {
					$group = $groups[0];
				} else {
					$group = '';
				}
			}

			$matches = array();
			if ( isset( $_GET['edit'] ) ) {
				$match_id     = intval( $_GET['edit'] );
				$match        = get_match( $match_id );
				$mode         = 'edit';
				$edit         = true;
				$form_title   = __( 'Edit Match', 'racketmanager' );
				$submit_title = $form_title;
				if ( isset( $match->final_round ) && '' !== $match->final_round ) {
					$cup             = true;
					$single_cup_game = true;
				}
				$league_id  = $match->league_id;
				$matches[0] = $match;
				$match_day  = $match->match_day;
				$finalkey   = isset( $match->final_round ) ? $match->final_round : '';

				$max_matches = 1;
			} elseif ( isset( $_GET['match_day'] ) ) {
				$mode  = 'edit';
				$edit  = true;
				$bulk  = true;
				$order = false;

				$match_day = intval( $_GET['match_day'] );
				$season    = isset( $_GET['season'] ) ? sanitize_text_field( wp_unslash( $_GET['season'] ) ) : null;

				$match_args = array(
					'match_day' => $match_day,
					'season'    => $season,
				);
				if ( $cup ) {
					$match_args['group'] = $group;
				}
				/* translators: $d: Match day */
				$form_title   = sprintf( __( 'Edit Matches - Match Day %d', 'racketmanager' ), $match_day );
				$submit_title = __( 'Edit Matches', 'racketmanager' );

				$matches     = $league->get_matches( $match_args );
				$max_matches = count( $matches );
			} elseif ( isset( $_GET['final'] ) ) {
				$is_finals = true;
				$bulk      = false;
				$order     = false;
				$finalkey  = $league->championship->get_current_final_key();
				$mode      = isset( $_GET['mode'] ) ? sanitize_text_field( wp_unslash( $_GET['mode'] ) ) : null;
				$edit      = ( 'edit' === $mode ) ? true : false;

				$final           = $league->championship->get_finals( $finalkey );
				$num_first_round = $league->championship->num_teams_first_round;

				$max_matches = $final['num_matches'];

				if ( 'add' === $mode ) {
					/* translators: %s: round name */
					$form_title = sprintf( __( 'Add Matches - %s', 'racketmanager' ), $league->championship->get_final_name( $finalkey ) );
					for ( $h = 0; $h < $max_matches; $h++ ) {
						$matches[ $h ] = new \stdClass();
						if ( 'final' !== $finalkey ) {
							$matches[ $h ]->host = 'home';
						}
						$matches[ $h ]->hour    = $league->event->competition->default_match_start_time['hour'];
						$matches[ $h ]->minutes = $league->event->competition->default_match_start_time['minutes'];
					}
				} else {
					/* translators: %s: round name */
					$form_title = sprintf( __( 'Edit Matches - %s', 'racketmanager' ), $league->championship->get_final_name( $finalkey ) );
					$match_args = array(
						'final'   => $finalkey,
						'orderby' => array(
							'id' => 'ASC',
						),
					);
					if ( 'final' !== $finalkey && ! empty( $league->current_season['homeAway'] ) && 'true' === $league->current_season['homeAway'] ) {
						$match_args['leg'] = 1;
					}
					$matches = $league->get_matches( $match_args );
				}
				$submit_title = $form_title;
			} else {
				$mode = 'add';
				$edit = false;
				$bulk = $cup ? true : false;
				global $wpdb;

				// Get max match day.
				$search = $wpdb->prepare(
					'`league_id` = %d AND `season`  = %s',
					$league->id,
					$season
				);
				if ( $cup ) {
					$search .= $wpdb->prepare(
						' AND `group` = %s',
						$group
					);
				}
				if ( ! isset( $_GET['final'] ) ) {
					$submit_title = __( 'Add Matches', 'racketmanager' );
					if ( $cup ) {
						/* translators: %s: group name */
						$form_title  = sprintf( __( 'Add Matches - Group %s', 'racketmanager' ), $group );
						$max_matches = ceil( ( $league->num_teams / 2 ) * $season['num_match_days'] ); // set number of matches to add to half the number of teams per match day.
					} else {
						$form_title  = $submit_title;
						$max_matches = ceil( $league->num_teams_total ); // set number of matches to add to half the number of teams per match day.
					}
					$match_day        = 1;
					$matches[]        = new \stdClass();
					$matches[0]->year = ( isset( $_GET['season'] ) && is_numeric( $_GET['season'] ) ) ? intval( $_GET['season'] ) : gmdate( 'Y' );
				}

				for ( $i = 0; $i < $max_matches; $i++ ) {
					$matches[]              = new \stdClass();
					$matches[ $i ]->hour    = $league->event->competition->default_match_start_time['hour'];
					$matches[ $i ]->minutes = $league->event->competition->default_match_start_time['minutes'];
				}
			}

			if ( $single_cup_game ) {
				$final       = $league->championship->get_finals( $finalkey );
				$final_teams = $league->championship->get_final_teams( $final['key'], 'ARRAY' );
				if ( is_numeric( $match->home_team ) ) {
					$home_team = get_team( $match->home_team );
					if ( $home_team ) {
						$home_title = $home_team->title;
					} else {
						$home_title = null;
					}
				} else {
					$home_team = $final_teams[ $match->home_team ];
					if ( $home_team ) {
						$home_title = $home_team->title;
					} else {
						$home_title = null;
					}
				}
				if ( is_numeric( $match->away_team ) ) {
					$away_team = get_team( $match->away_team );
					if ( $away_team ) {
						$away_title = $away_team->title;
					} else {
						$away_title = null;
					}
				} else {
					$away_team = $final_teams[ $match->away_team ];
					if ( $away_team ) {
						$away_title = $away_team->title;
					} else {
						$away_title = null;
					}
				}
			} elseif ( $is_finals ) {
				$teams = $league->championship->get_final_teams( $finalkey );
				if ( 'add' === $mode ) {
					$round = $final['round'];
					if ( 1 !== intval( $round ) ) {
						$prev_round      = $final['round'] - 1;
						$prev_round_name = $league->championship->get_final_keys( $prev_round );
						$first_round     = false;
						$home_team       = 1;
						$away_team       = 2;
					} else {
						$first_round = true;
						switch ( $max_matches ) {
							case 1:
								$team_array = array( 1 );
								break;
							case 2:
								$team_array = array( 1, 3 );
								break;
							case 4:
								$team_array = array( 1, 5, 3, 7 );
								break;
							case 8:
								$team_array = array( 1, 9, 4, 12, 11, 14, 7, 15 );
								break;
							case 16:
								$team_array = array( 1, 17, 9, 25, 4, 21, 13, 28, 6, 22, 14, 30, 7, 23, 15, 31 );
								break;
							case 32:
								$team_array = array( 1, 33, 17, 49, 9, 41, 25, 57, 4, 36, 20, 52, 12, 44, 28, 60, 6, 38, 22, 54, 14, 46, 30, 62, 7, 39, 23, 55, 15, 47, 31, 63 );
								break;
							default:
								$team_array = array();
								break;
						}
					}
					for ( $i = 0; $i < $max_matches; $i++ ) {
						if ( $first_round ) {
							$home_team      = $team_array[ $i ];
							$home_team_name = $home_team . '_';
							$away_team      = $num_first_round + 1 - $home_team;
							$away_team_name = $away_team . '_';
						} else {
							$home_team_name = '1_' . $prev_round_name . '_' . $home_team;
							$away_team_name = '1_' . $prev_round_name . '_' . $away_team;
						}
						$matches[ $i ]->home_team = $teams[ $home_team_name ]->id;
						$matches[ $i ]->away_team = $teams[ $away_team_name ]->id;
						if ( $first_round ) {
							++$home_team;
							$away_team = $num_first_round + 1 - $home_team;
						} else {
							$home_team += 2;
							$away_team += 2;
						}
					}
				}
			} else {
				$teams = $league->get_league_teams(
					array(
						'season'  => $season,
						'orderby' => array( 'title' => 'ASC' ),
					)
				);
			}
			//phpcs:enable WordPress.Security.NonceVerification.Recommended
			include_once RACKETMANAGER_PATH . '/admin/includes/match.php';
		}
	}

	/**
	 * Display admin page
	 */
	private function display_admin_page() {
		$players = '';

		if ( ! current_user_can( 'edit_leagues' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			$tab = 'seasons';
			if ( isset( $_POST['addSeason'] ) ) {
				check_admin_referer( 'racketmanager_add-season' );
				if ( isset( $_POST['seasonName'] ) ) {
					$this->add_season( sanitize_text_field( wp_unslash( $_POST['seasonName'] ) ) );
				}
				$tab = 'seasons';
			} elseif ( isset( $_POST['doSeasonDel'] ) && isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) {
				if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'seasons-bulk' ) ) {
					$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
					$this->printMessage();
					return;
				}
				if ( isset( $_POST['season'] ) ) {
					foreach ( $_POST['season'] as $season_id ) { //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
						$this->delete_season( intval( $season_id ) );
					}
				}
				$tab = 'seasons';
			} elseif ( isset( $_POST['doaddCompetitionsToSeason'] ) && isset( $_POST['action'] ) && 'addCompetitionsToSeason' === $_POST['action'] ) {
				if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_add-seasons-competitions-bulk' ) ) {
					$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
				} elseif ( isset( $_POST['competition'] ) ) {
					foreach ( $_POST['competition'] as $competition_id ) { //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
						if ( isset( $_POST['num_match_days'] ) ) {
							$this->add_season_to_competition( sanitize_text_field( wp_unslash( $_POST['season'] ) ), $competition_id, intval( $_POST['num_match_days'] ) );
						}
					}
				}
				$tab = 'seasons';
			}
			$this->printMessage();

			include_once RACKETMANAGER_PATH . '/admin/show-admin.php';
		}
	}

	/**
	 * Display players page
	 */
	private function displayPlayersPage() {
		global $racketmanager;

		$players = '';

		if ( ! current_user_can( 'edit_leagues' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			$club_id = isset( $_GET['club'] ) ? intval( $_GET['club'] ) : null;
			$status  = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : null;
			$tab     = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'playerrequest';
			if ( isset( $_POST['addPlayer'] ) ) {
				check_admin_referer( 'racketmanager_add-player' );
				$player_valid = $this->validatePlayer();
				if ( $player_valid[0] ) {
					$new_player = $player_valid[1];
					$player     = get_player( $new_player->user_login, 'login' );  // get player by login.
					if ( ! $player ) {
						$player = new Racketmanager_Player( $new_player );
						$this->set_message( __( 'Player added', 'racketmanager' ) );
					} else {
						$this->set_message( __( 'Player already exists', 'racketmanager' ), true );
					}
				}
				$tab = 'players';
			} elseif ( isset( $_POST['doPlayerDel'] ) ) {
				if ( isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) {
					if ( current_user_can( 'edit_teams' ) ) {
						check_admin_referer( 'player-bulk' );
						$messages      = array();
						$message_error = false;
						if ( isset( $_POST['player'] ) ) {
							foreach ( $_POST['player'] as $player_id ) { //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
								$player = get_player( $player_id );
								$player->delete();
								$messages[] = $player->fullname . ' ' . __( 'deleted', 'racketmanager' );
							}
							$message = implode( '<br>', $messages );
							$this->set_message( $message, $message_error );
						}
					} else {
						$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
					}
				}
				$tab = 'players';
			} elseif ( isset( $_GET['doPlayerSearch'] ) ) {
				if ( ! empty( $_GET['name'] ) ) {
					$players = $racketmanager->get_all_players( array( 'name' => sanitize_text_field( wp_unslash( $_GET['name'] ) ) ) );
				} else {
					$this->set_message( __( 'No search term specified', 'racketmanager' ), true );
				}
				$tab = 'players';
			} elseif ( isset( $_POST['doplayerrequest'] ) ) {
				if ( current_user_can( 'edit_teams' ) ) {
					check_admin_referer( 'club-player-request-bulk' );
					if ( isset( $_POST['playerRequest'] ) ) {
						foreach ( $_POST['playerRequest'] as $i => $player_request_id ) { //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
							if ( 'approve' === $_POST['action'] ) {
								if ( ! current_user_can( 'edit_teams' ) ) {
									$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
								} elseif ( isset( $_POST['club_id'][ $i ] ) ) {
										$club = get_club( intval( $_POST['club_id'][ $i ] ) );
										$club->approve_player_request( intval( $player_request_id ) );
								}
							} elseif ( 'delete' === $_POST['action'] ) {
								if ( ! current_user_can( 'edit_teams' ) ) {
									$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
								} else {
									$this->delete_player_request( intval( $player_request_id ) );
								}
							}
						}
					}
				} else {
					$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
				}
				$tab = 'playerrequest';
			} elseif ( isset( $_GET['view'] ) && 'playerRequest' === $_GET['view'] ) {
				$tab = 'playerrequest';
			} elseif ( isset( $_GET['tab'] ) && 'players' === $_GET['tab'] ) {
				$tab = 'players';
			}
			$this->printMessage();
			if ( ! $players ) {
				$players = $racketmanager->get_all_players( array() );
			}
			$player_requests = Racketmanager_Util::get_player_requests(
				array(
					'club'   => $club_id,
					'status' => $status,
				)
			);

			include_once RACKETMANAGER_PATH . 'admin/show-players.php';
		}
	}

	/**
	 * Display import Page
	 */
	private function displayImportPage() {
		if ( ! current_user_can( 'import_leagues' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			if ( isset( $_POST['import'] ) ) {
				if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_import-datasets' ) ) {
					$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
					$this->printMessage();
				} else {
					$league_id = isset( $_POST['league_id'] ) ? intval( $_POST['league_id'] ) : null;
					$season    = isset( $_POST['season'] ) ? sanitize_text_field( wp_unslash( $_POST['season'] ) ) : null;
					$club      = isset( $_POST['affiliatedClub'] ) ? intval( $_POST['affiliatedClub'] ) : null;
					$files     = isset( $_FILES['racketmanager_import'] ) ? $_FILES['racketmanager_import'] : null; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					$delimiter = isset( $_POST['delimiter'] ) ? $_POST['delimiter'] : null; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					$mode      = isset( $_POST['mode'] ) ? sanitize_text_field( wp_unslash( $_POST['mode'] ) ) : null;
					$this->import( $league_id, $season, $files, $delimiter, $mode, $club );
					$this->printMessage();
				}
			}
			include_once RACKETMANAGER_PATH . '/admin/tools/import.php';
		}
	}

	/**
	 * Display contact page
	 */
	private function displayContactPage() {
		global $racketmanager, $racketmanager_shortcodes;

		if ( ! current_user_can( 'edit_teams' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			if ( isset( $_POST['contactTeamPreview'] ) ) {
				if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_contact-teams' ) ) {
					$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
					$this->printMessage();
					return;
				}
				if ( isset( $_POST['league_id'] ) ) {
					$league      = get_league( intval( $_POST['league_id'] ) );
					$title       = $league->title;
					$object_type = 'league';
					$object      = $league;
					$object_name = 'league_id';
					$object_id   = $league->id;
				} elseif ( isset( $_POST['competition_id'] ) ) {
					$competition = get_competition( intval( $_POST['competition_id'] ) );
					$title       = $competition->name;
					$object_type = 'competition';
					$object      = $competition;
					$object_name = 'competition_id';
					$object_id   = $competition->id;
				}
				if ( isset( $_POST['season'] ) ) {
					$season = sanitize_text_field( wp_unslash( $_POST['season'] ) );
				}
				$email_title   = isset( $_POST['contactTitle'] ) ? sanitize_text_field( wp_unslash( $_POST['contactTitle'] ) ) : null;
				$email_intro   = isset( $_POST['contactIntro'] ) ? sanitize_textarea_field( wp_unslash( $_POST['contactIntro'] ) ) : null;
				$email_body    = isset( $_POST['contactBody'] ) ? $_POST['contactBody'] : null; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$email_close   = isset( $_POST['contactClose'] ) ? sanitize_textarea_field( wp_unslash( $_POST['contactClose'] ) ) : null;
				$email_subject = $this->site_name . ' - ' . $title . ' ' . $season . ' - Important Message';

				$email_message = $racketmanager_shortcodes->load_template(
					'contact-teams',
					array(
						$object_type    => $object,
						'organisation'  => $racketmanager->site_name,
						'season'        => $season,
						'title_text'    => $email_title,
						'intro'         => $email_intro,
						'body'          => $email_body,
						'closing'       => $email_close,
						'email_subject' => $email_subject,
					),
					'email'
				);
				$tab           = 'preview';
			} else {
				if ( isset( $_GET['league_id'] ) ) {
					$league      = get_league( intval( $_GET['league_id'] ) );
					$object_type = 'league';
					$object_name = 'league_id';
					$object_id   = $league->id;
				} elseif ( isset( $_GET['competition_id'] ) ) {
					$competition = get_competition( intval( $_GET['competition_id'] ) );
					$object_type = 'competition';
					$object_name = 'competition_id';
					$object_id   = $competition->id;
				}
				if ( isset( $_GET['season'] ) ) {
					$season = sanitize_text_field( wp_unslash( $_GET['season'] ) );
				}
				$email_title   = '';
				$email_intro   = '';
				$email_close   = '';
				$email_body    = array();
				$email_message = '';
				$tab           = 'compose';
			}

			include_once RACKETMANAGER_PATH . '/admin/includes/contact.php';
		}
	}

	/**
	 * Display finances page
	 */
	private function displayFinancesPage() {
		global $racketmanager;

		$players = '';

		if ( ! current_user_can( 'edit_leagues' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			$club_id           = isset( $_GET['club'] ) ? intval( $_GET['club'] ) : '';
			$status            = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
			$racketmanager_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'racketmanager-charges';
			if ( isset( $_POST['generateInvoices'] ) ) {
				$racketmanager_tab = 'racketmanager-invoices';
				if ( isset( $_POST['charges_id'] ) ) {
					$charges_id      = intval( $_POST['charges_id'] );
					$charges         = get_Charges( $charges_id );
					$charges_entries = $charges->get_club_entries();
					$billing         = $this->get_options( 'billing' );
					$date_due        = new \DateTime( $charges->date );
					if ( isset( $billing['paymentTerms'] ) && intval( $billing['paymentTerms'] ) !== 0 ) {
						$date_interval = intval( $billing['paymentTerms'] );
						$date_interval = 'P' . $date_interval . 'D';
						$date_due->add( new \DateInterval( $date_interval ) );
					}
					$invoice_number = $billing['invoiceNumber'];
					foreach ( $charges_entries as $entry ) {
						$invoice                 = new \stdClass();
						$invoice->charge_id      = $charges->id;
						$invoice->club_id        = $entry->id;
						$invoice->invoice_number = $billing['invoiceNumber'];
						$invoice->status         = 'new';
						$invoice->date           = $charges->date;
						$invoice->date_due       = $date_due->format( 'Y-m-d' );
						$invoice                 = new Racketmanager_Invoice( $invoice );
						$sent                    = false;
						$sent                    = $invoice->send();
						if ( $sent ) {
							$invoice->set_status( 'sent' );
						}
						$billing['invoiceNumber'] += 1;
					}
					if ( $sent ) {
						$options                             = $this->get_options();
						$options['billing']['invoiceNumber'] = $billing['invoiceNumber'];
						update_option( 'racketmanager', $options );
						$this->set_message( __( 'Invoices sent', 'racketmanager' ) );
						$charges->set_status( 'final' );
					} else {
						$this->set_message( __( 'No invoices sent', 'racketmanager' ), true );
					}
				}
			} elseif ( isset( $_POST['doChargesDel'] ) && isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) {
				$racketmanager_tab = 'racketmanager-charges';
				check_admin_referer( 'charges-bulk' );
				if ( ! current_user_can( 'del_teams' ) ) {
					$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
				} else {
					$messages      = array();
					$message_error = false;
					if ( isset( $_POST['charge'] ) ) {
						foreach ( $_POST['charge'] as $charges_id ) { //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
							$charge     = get_charges( $charges_id );
							$charge_ref = ucfirst( $charge->type ) . ' ' . $charge->season;
							if ( $charge->has_invoices() ) {
								$messages[]    = $charge_ref . ' ' . __( 'not deleted - still has invoices attached', 'racketmanager' );
								$message_error = true;
							} else {
								$charge->delete();
								$messages[] = $charge_ref . ' ' . __( 'deleted', 'racketmanager' );
							}
						}
						$message = implode( '<br>', $messages );
						$this->set_message( $message, $message_error );
					}
				}
			} elseif ( isset( $_POST['doActionInvoices'] ) && isset( $_POST['action'] ) && -1 !== $_POST['action'] ) {
				$racketmanager_tab = 'racketmanager-invoices';
				check_admin_referer( 'invoices-bulk' );
				if ( ! current_user_can( 'del_teams' ) ) {
					$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
				} else {
					$messages      = array();
					$message_error = false;
					if ( isset( $_POST['invoice'] ) ) {
						foreach ( $_POST['invoice'] as $invoice_id ) { //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
							$invoice = get_invoice( $invoice_id );
							if ( $invoice->status !== $_POST['action'] ) {
								$invoice->set_status( intval( $_POST['action'] ) );
								$messages[] = __( 'Invoice', 'racketmanager' ) . ' ' . $invoice->invoice_number . ' ' . __( 'updated', 'racketmanager' );
							}
						}
						$message = implode( '<br>', $messages );
						$this->set_message( $message, $message_error );
					}
				}
			}

			$this->printMessage();

			$invoices = $racketmanager->getInvoices(
				array(
					'club'   => $club_id,
					'status' => $status,
				)
			);
			include_once RACKETMANAGER_PATH . '/admin/show-finances.php';
		}
	}

	/**
	 * Display charges page
	 */
	private function displayChargesPage() {
		global $racketmanager, $racketmanager_shortcodes;

		if ( ! current_user_can( 'edit_teams' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			if ( isset( $_POST['saveCharges'] ) ) {
				if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_manage-charges' ) ) {
					$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
					$this->printMessage();
					return;
				}
				if ( isset( $_POST['charges_id'] ) && '' !== $_POST['charges_id'] ) {
					$charges = get_charges( intval( $_POST['charges_id'] ) );
					$updates = false;
					if ( isset( $_POST['feeClub'] ) && $charges->fee_club !== $_POST['feeClub'] ) {
						$charges->set_club_fee( floatval( $_POST['feeClub'] ) );
						$updates = true;
					}
					if ( isset( $_POST['feeTeam'] ) && $charges->fee_team !== $_POST['feeTeam'] ) {
						$charges->set_team_fee( floatval( $_POST['feeTeam'] ) );
						$updates = true;
					}
					if ( isset( $_POST['status'] ) && $charges->status !== $_POST['status'] ) {
						$charges->set_status( sanitize_text_field( wp_unslash( $_POST['status'] ) ) );
						$updates = true;
					}
					if ( isset( $_POST['competitionType'] ) && $charges->competition_type !== $_POST['competitionType'] ) {
						$charges->set_competition_type( sanitize_text_field( wp_unslash( $_POST['competitionType'] ) ) );
						$updates = true;
					}
					if ( isset( $_POST['type'] ) && $charges->type !== $_POST['type'] ) {
						$charges->set_type( sanitize_text_field( wp_unslash( $_POST['type'] ) ) );
						$updates = true;
					}
					if ( isset( $_POST['date'] ) && $charges->date !== $_POST['date'] ) {
						$charges->set_date( sanitize_text_field( wp_unslash( $_POST['date'] ) ) );
						$updates = true;
					}
					if ( isset( $_POST['season'] ) && $charges->season !== $_POST['season'] ) {
						$charges->set_season( sanitize_text_field( wp_unslash( $_POST['season'] ) ) );
						$updates = true;
					}
					if ( $updates ) {
						$this->set_message( __( 'Charges updated', 'racketmanager' ) );
					} else {
						$this->set_message( __( 'No updates', 'racketmanager' ), true );
					}
				} else {
					$charges                 = new \stdClass();
					$charges->competition_id = isset( $_POST['competition_id'] ) ? sanitize_text_field( wp_unslash( $_POST['competition_id'] ) ) : null;
					$charges->season         = isset( $_POST['season'] ) ? sanitize_text_field( wp_unslash( $_POST['season'] ) ) : null;
					$charges->status         = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : null;
					$charges->date           = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : null;
					$charges->fee_club       = isset( $_POST['feeClub'] ) ? floatval( $_POST['feeClub'] ) : null;
					$charges->fee_team       = isset( $_POST['feeTeam'] ) ? floatval( $_POST['feeTeam'] ) : null;
					$charges                 = new Racketmanager_Charges( $charges );
					$this->set_message( __( 'Charges added', 'racketmanager' ) );
				}
			}
			$this->printMessage();
			$edit = false;
			if ( isset( $_GET['charges'] ) || ( isset( $charges->id ) && '' !== $charges->id ) ) {
				if ( isset( $_GET['charges'] ) ) {
					$charges_id = intval( $_GET['charges'] );
				} else {
					$charges_id = $charges->id;
				}
				$edit    = true;
				$charges = get_Charges( $charges_id );

				$form_title  = __( 'Edit Charges', 'racketmanager' );
				$form_action = __( 'Update', 'racketmanager' );
			} else {
				$charges_id              = '';
				$form_title              = __( 'Add Charges', 'racketmanager' );
				$form_action             = __( 'Add', 'racketmanager' );
				$charges                 = new \stdclass();
				$charges->competition_id = '';
				$charges->id             = '';
				$charges->season         = '';
				$charges->date           = '';
				$charges->status         = '';
				$charges->fee_club       = '';
				$charges->fee_team       = '';
			}

			include_once RACKETMANAGER_PATH . '/admin/finances/charge.php';
		}
	}

	/**
	 * Display invoice page
	 */
	private function displayInvoicePage() {
		global $racketmanager;

		if ( ! current_user_can( 'edit_teams' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			if ( isset( $_POST['saveInvoice'] ) ) {
				if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_manage-invoice' ) ) {
					$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
					$this->printMessage();
					return;
				}
				if ( isset( $_POST['invoice_id'] ) ) {
					$invoice = get_invoice( intval( $_POST['invoice_id'] ) );
					$updates = false;
					if ( isset( $_POST['status'] ) && $invoice->status !== $_POST['status'] ) {
						$updates = $invoice->set_status( sanitize_text_field( wp_unslash( $_POST['status'] ) ) );
					}
					if ( $updates ) {
						$this->set_message( __( 'Invoice updated', 'racketmanager' ) );
					} else {
						$this->set_message( __( 'No updates', 'racketmanager' ), true );
					}
				}
			}
			$this->printMessage();
			if ( isset( $_GET['charge'] ) && isset( $_GET['club'] ) ) {
				$invoice_id = $this->getInvoice( intval( $_GET['charge'] ), intval( $_GET['club'] ) );
			} elseif ( isset( $_GET['invoice'] ) ) {
				$invoice_id = intval( $_GET['invoice'] );
			}
			$tab          = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'racketmanager-invoices';
			$invoice_view = '';
			$billing      = $racketmanager->get_options( 'billing' );
			if ( isset( $invoice_id ) && $invoice_id ) {
				$invoice = get_invoice( $invoice_id );
			}
			if ( isset( $invoice ) && $invoice ) {
				$invoice_view = $invoice->generate();
				include_once RACKETMANAGER_PATH . '/admin/finances/invoice.php';
			} else {
				$this->set_message( __( 'Invoice not found', 'racketmanager' ), true );
				$this->printMessage();
			}
		}
	}

	/**
	 * Display season page
	 */
	private function displaySeasonPage() {
		if ( ! current_user_can( 'edit_leagues' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			if ( isset( $_POST['saveSeason'] ) ) {
				if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_update-season' ) ) {
					$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
					$this->printMessage();
					return;
				}
				if ( ! current_user_can( 'edit_seasons' ) ) {
					$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
				} else {
					if ( isset( $_POST['seasonId'] ) ) {
						$season_id = intval( $_POST['seasonId'] );
					}
					if ( isset( $_POST['competitionId'] ) ) {
						$item_id = intval( $_POST['competitionId'] );
						$item    = 'competition';
					} elseif ( isset( $_POST['eventId'] ) ) {
						$item_id = intval( $_POST['eventId'] );
						$item    = 'event';
					}
					$num_match_days = isset( $_POST['num_match_days'] ) ? intval( $_POST['num_match_days'] ) : null;
					$closing_date   = isset( $_POST['date_closing'] ) ? sanitize_text_field( wp_unslash( $_POST['date_closing'] ) ) : null;
					$date_start     = isset( $_POST['date_start'] ) ? sanitize_text_field( wp_unslash( $_POST['date_start'] ) ) : null;
					$date_end       = isset( $_POST['date_end'] ) ? sanitize_text_field( wp_unslash( $_POST['date_end'] ) ) : null;
					$is_box         = isset( $_POST['is_box'] ) ? sanitize_text_field( wp_unslash( $_POST['is_box'] ) ) : false;
					if ( isset( $_POST['matchDate'] ) ) {
						$match_date = $_POST['matchDate']; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					} else {
						$match_date = array();
					}
					if ( isset( $_POST['fixedMatchDates'] ) ) {
						$fixed_dates = 'true' === $_POST['fixedMatchDates'] ? true : false;
					} else {
						$fixed_dates = true;
					}
					if ( isset( $_POST['homeAway'] ) ) {
						$home_away = 'true' === $_POST['homeAway'] ? true : false;
					} else {
						$home_away = true;
					}
					if ( isset( $_POST['status'] ) ) {
						$status = sanitize_text_field( wp_unslash( $_POST['status'] ) );
					} else {
						$status = 'draft';
					}
					$season_data                 = new \stdclass();
					$season_data->season         = $season_id;
					$season_data->num_match_days = $num_match_days;
					$season_data->object_id      = $item_id;
					$season_data->match_dates    = $match_date;
					$season_data->fixed_dates    = $fixed_dates;
					$season_data->home_away      = $home_away;
					$season_data->status         = $status;
					$season_data->closing_date   = $closing_date;
					$season_data->date_start     = $date_start;
					$season_data->date_end       = $date_end;
					$season_data->type           = $item;
					$season_data->is_box         = $is_box;
					$this->edit_season( $season_data );
				}
				$this->printMessage();
			}
			$season_id = isset( $_GET['season'] ) ? sanitize_text_field( wp_unslash( $_GET['season'] ) ) : null;
			if ( isset( $_GET['competition_id'] ) ) {
				$object      = get_competition( intval( $_GET['competition_id'] ) );
				$competition = $object;
				$season_data = $object->seasons[ $season_id ];
				include_once RACKETMANAGER_PATH . '/admin/includes/season.php';
			} elseif ( isset( $_GET['event_id'] ) ) {
				$object      = get_event( intval( $_GET['event_id'] ) );
				$event       = $object;
				$season_data = $object->seasons[ $season_id ];
				include_once RACKETMANAGER_PATH . '/admin/includes/season.php';
			}
		}
	}
	/**
	 * Display link to settings page in plugin table
	 *
	 * @param array $links array of action links.
	 * @return array
	 */
	public function pluginActions( $links ) {
		if ( is_array( $links ) ) {
			$links['settings']      = '<a href="admin.php?page=racketmanager-settings">' . __( 'Settings', 'racketmanager' ) . '</a>';
			$links['documentation'] = '<a href="admin.php?page=racketmanager-doc">' . __( 'Documentation', 'racketmanager' ) . '</a>';
		}
		return $links;
	}

	/**
	 * Load Javascript
	 */
	public function loadScripts() {
		wp_register_script( 'racketmanager-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js', array(), RACKETMANAGER_VERSION, false );
		wp_enqueue_script( 'racketmanager-bootstrap' );
		wp_register_script( 'racketmanager-functions', plugins_url( '/admin/js/functions.js', __DIR__ ), array( 'thickbox', 'jquery', 'jquery-ui-core', 'jquery-ui-datepicker', 'jquery-ui-sortable', 'jquery-ui-tooltip', 'jquery-effects-core', 'jquery-effects-slide', 'jquery-effects-explode', 'jquery-ui-autocomplete', 'iris' ), RACKETMANAGER_VERSION, false );
		wp_enqueue_script( 'racketmanager-functions' );
		wp_localize_script(
			'racketmanager-functions',
			'ajax_var',
			array(
				'url'        => admin_url( 'admin-ajax.php' ),
				'ajax_nonce' => wp_create_nonce( 'ajax-nonce' ),
			)
		);

		wp_register_script( 'racketmanager-ajax', plugins_url( '/admin/js/ajax.js', __DIR__ ), array(), RACKETMANAGER_VERSION, false );
		wp_enqueue_script( 'racketmanager-ajax' );
		wp_localize_script(
			'racketmanager-ajax',
			'ajax_var',
			array(
				'url'        => admin_url( 'admin-ajax.php' ),
				'ajax_nonce' => wp_create_nonce( 'ajax-nonce' ),
			)
		);
		?>
		<script type='text/javascript'>
		//<!--<![CDATA[-->
		RacketManagerAjaxL10n = {
			requestUrl: "<?php bloginfo( 'wpurl' ); ?>/wp-admin/admin-ajax.php",
			manualPointRuleDescription: "<?php esc_html_e( 'Order: win, win overtime, tie, loss, loss overtime', 'racketmanager' ); ?>",
			pluginUrl: "<?php plugins_url( '', __DIR__ ); ?>/wp-content/plugins/leaguemanager",
			Edit: "<?php esc_html_e( 'Edit', 'racketmanager' ); ?>",
			Post: "<?php esc_html_e( 'Post', 'racketmanager' ); ?>",
			Save: "<?php esc_html_e( 'Save', 'racketmanager' ); ?>",
			Cancel: "<?php esc_html_e( 'Cancel', 'racketmanager' ); ?>",
			pleaseWait: "<?php esc_html_e( 'Please wait...', 'racketmanager' ); ?>",
			Delete: "<?php esc_html_e( 'Delete', 'racketmanager' ); ?>",
			Yellow: "<?php esc_html_e( 'Yellow', 'racketmanager' ); ?>",
			Red: "<?php esc_html_e( 'Red', 'racketmanager' ); ?>",
			Yellow_Red: "<?php esc_html_e( 'Yellow/Red', 'racketmanager' ); ?>",
			Insert: "<?php esc_html_e( 'Insert', 'racketmanager' ); ?>",
			InsertPlayer: "<?php esc_html_e( 'Insert Player', 'racketmanager' ); ?>"
		}
		//<!--]]>-->
		</script>
		<?php
	}

	/**
	 * Load CSS styles
	 */
	public function loadStyles() {
		wp_enqueue_style( 'racketmanager-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css', false, RACKETMANAGER_VERSION, 'screen' );
		wp_enqueue_style( 'racketmanager', plugins_url( '/css/admin.css', __DIR__ ), false, RACKETMANAGER_VERSION, 'screen' );
		wp_enqueue_style( 'racketmanager-modal', plugins_url( '/css/modal.css', __DIR__ ), false, RACKETMANAGER_VERSION, 'screen' );

		$jquery_ui_version = '1.13.2';
		wp_register_style( 'jquery-ui', plugins_url( '/css/jquery/jquery-ui.min.css', __DIR__ ), false, $jquery_ui_version, 'all' );
		wp_register_style( 'jquery-ui-structure', plugins_url( '/css/jquery/jquery-ui.structure.min.css', __DIR__ ), array( 'jquery-ui' ), $jquery_ui_version, 'all' );
		wp_register_style( 'jquery-ui-theme', plugins_url( '/css/jquery/jquery-ui.theme.min.css', __DIR__ ), array( 'jquery-ui', 'jquery-ui-structure' ), $jquery_ui_version, 'all' );

		wp_enqueue_style( 'jquery-ui-structure' );
		wp_enqueue_style( 'jquery-ui-theme' );

		wp_enqueue_style( 'thickbox' );
	}

	/************
	 *
	 *   COMPETITION SECTION
	 */

	/**
	 * Delete all Competition Pages
	 *
	 * @param string $competition_name competition name.
	 */
	private function delete_competition_pages( $competition_name ) {
		$title     = $competition_name . ' ' . __( 'Tables', 'racketmanager' );
		$page_name = sanitize_title_with_dashes( $title );
		$this->delete_racketmanager_page( $page_name );

		$title     = $competition_name;
		$page_name = sanitize_title_with_dashes( $title );
		$this->delete_racketmanager_page( $page_name );
	}

	/**
	 * Delete matches for event
	 *
	 * @param int $event event to be deleted.
	 * @return boolean $success
	 */
	private function delete_event_matches( $event ) {
		global $wpdb, $racketmanager;

		$success     = true;
		$event       = get_event( $event );
		$season      = $event->get_season();
		$match_count = $racketmanager->get_matches(
			array(
				'count'    => true,
				'event_id' => $event->id,
				'season'   => $season,
				'time'     => 'latest',
			)
		);

		if ( 0 !== $match_count ) {
			$this->set_message( __( 'Event has completed matches', 'racketmanager' ), true );
			$success = false;
		} else {
			$leagues = $event->get_leagues();
			foreach ( $leagues as $league ) {
				$matches = $league->get_matches( array( 'season' => $season ) );
				foreach ( $matches as $match ) {
					$match = get_match( $match->id );
					$match->delete();
				}
			}
			$this->set_message( __( 'Matches deleted', 'racketmanager' ) );
		}
		return $success;
	}

	/**
	 * Update Table
	 *
	 * @param int    $table_id table id.
	 * @param int    $league_id league id.
	 * @param int    $rank rank.
	 * @param string $status status.
	 * @param string $profile profile.
	 */
	private function updateTable( $table_id, $league_id, $rank, $status, $profile ) {
		global $wpdb;

		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"UPDATE {$wpdb->racketmanager_table} SET `league_id` = %d, `rank` = %d, `status` = %s, `profile` = %d WHERE `id` = %d",
				$league_id,
				$rank,
				$status,
				$profile,
				$table_id
			)
		);
		$this->set_message( __( 'Updated', 'racketmanager' ) );
	}

	/**
	 * Add new Season
	 *
	 * @param string $name name of season.
	 * @return boolean
	 */
	private function add_season( $name ) {
		global $wpdb;

		if ( ! current_user_can( 'edit_seasons' ) ) {
			$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
			return false;
		}
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"INSERT INTO {$wpdb->racketmanager_seasons} (name) VALUES (%s)",
				$name
			)
		);
		$this->set_message( __( 'Season added', 'racketmanager' ) );
		return true;
	}

	/**
	 * Delete season
	 *
	 * @param int $season_id season id to be deleted.
	 * @return boolean
	 */
	private function delete_season( $season_id ) {
		global $wpdb;

		if ( ! current_user_can( 'del_seasons' ) ) {
			$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
			return false;
		}

		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE FROM {$wpdb->racketmanager_seasons} WHERE `id` = %d",
				$season_id
			)
		);
		$this->set_message( __( 'Season deleted', 'racketmanager' ) );

		return true;
	}
	/**
	 * Add new season to competition
	 *
	 * @param string $season season.
	 * @param int    $competition_id competition id.
	 * @param int    $num_match_days number of match days.
	 * @return boolean
	 */
	private function add_season_to_competition( $season, $competition_id, $num_match_days = null ) {
		global $racketmanager, $competition;

		$competition = get_competition( $competition_id );
		if ( ! $num_match_days && ( 'cup' === $competition->type || 'tournament' === $competition->type ) ) {
			$options        = $racketmanager->get_options();
			$rm_options     = $options['championship'];
			$num_match_days = isset( $rm_options['numRounds'] ) ? $rm_options['numRounds'] : 0;
		}

		if ( ! $num_match_days ) {
			$this->set_message( 'Number of match days not specified', 'racketmanager', 'error' );
			return false;
		}

		if ( '' === $competition->seasons ) {
			$competition->seasons = array();
		}
		$competition->seasons[ $season ] = array(
			'name'           => $season,
			'num_match_days' => $num_match_days,
			'status'         => 'draft',
		);
		ksort( $competition->seasons );
		$this->save_competition_seasons( $competition->seasons, $competition->id );
		$events = $competition->get_events();
		foreach ( $events as $event ) {
			$event = get_event( $event );
			if ( ! isset( $event->seasons[ $season ] ) ) {
				$event_season = $this->add_season_to_event( $season, $event->id, $num_match_days );
			}
		}
		/* translators: %s: season name */
		$this->set_message( sprintf( __( 'Season %s added', 'racketmanager' ), $season ) );

		return $competition->seasons[ $season ];
	}
	/**
	 * Add new season to event
	 *
	 * @param string $season season.
	 * @param int    $event_id event_id.
	 * @param int    $num_match_days number of match days.
	 * @param string $closing_date (optional) closing date.
	 * @param string $home_away (optional) match format.
	 * @param array  $match_dates (optional) match dates.
	 * @return boolean
	 */
	private function add_season_to_event( $season, $event_id, $num_match_days, $closing_date = null, $home_away = null, $match_dates = null ) {
		global $racketmanager, $event;

		$event = get_event( $event_id );
		if ( '' === $event->seasons ) {
			$event->seasons = array();
		}
		if ( $event->is_box ) {
			$event->seasons[ $season ] = array(
				'name'           => $season,
				'closing_date'   => $closing_date,
				'matchDates'     => $match_dates,
				'homeAway'       => $home_away,
				'num_match_days' => 0,
				'status'         => 'draft',
			);
		} else {
			if ( ! $num_match_days && ( 'cup' === $event->competition->type || 'tournament' === $event->competition->type ) ) {
				$options        = $racketmanager->get_options();
				$rm_options     = $options['championship'];
				$num_match_days = isset( $rm_options['numRounds'] ) ? $rm_options['numRounds'] : 0;
			}
			if ( ! $num_match_days ) {
				$this->set_message( 'Number of match days not specified', 'racketmanager', 'error' );
				return false;
			}
			$event->seasons[ $season ] = array(
				'name'           => $season,
				'num_match_days' => $num_match_days,
				'status'         => 'draft',
			);
		}
		ksort( $event->seasons );
		$this->save_event_seasons( $event->seasons, $event->id );
		/* translators: %s: season name */
		$this->set_message( sprintf( __( 'Season %s added', 'racketmanager' ), $season ) );

		return true;
	}
	/**
	 * Edit season in object - competition or event
	 *
	 * @param object $season_data season data.
	 */
	private function edit_season( $season_data ) {
		global $racketmanager, $competition;
		$error = false;
		if ( false !== $season_data->match_dates ) {
			if ( empty( $season_data->match_dates ) ) {
				$this->set_message( __( 'Match dates not set', 'racketmanager' ), true );
				$error = true;
			} else {
				$match_date_values = array();
				$prev_match_date   = '';
				$match_date_empty  = 0;
				foreach ( $season_data->match_dates as $match_date ) {
					if ( empty( $match_date ) ) {
						++$match_date_empty;
						$this->set_message( __( 'Match date not set', 'racketmanager' ), true );
						$error = true;
					} elseif ( 'true' === $season_data->fixed_dates ) {
						$valid_match_date = array_search( $match_date, $match_date_values, true );
						if ( false !== $valid_match_date ) {
							$this->set_message( __( 'Match dates must be unique', 'racketmanager' ), true );
							$error = true;
						} elseif ( $match_date <= $prev_match_date ) {
								$this->set_message( __( 'Match date must be later than previous date', 'racketmanager' ), true );
								$error = true;
						} else {
							$match_date_values[] = $match_date;
							$prev_match_date     = $match_date;
						}
					}
				}
				if ( $error && count( $season_data->match_dates ) === $match_date_empty ) {
					$error = false;
					$this->set_message( null );
				}
			}
		}
		if ( ! $season_data->num_match_days && ! $season_data->is_box ) {
			$this->set_message( __( 'Number of match days must be set', 'racketmanager' ), true );
			$error = true;
		}
		if ( ! $season_data->status ) {
			$this->set_message( __( 'Status must be set', 'racketmanager' ), true );
			$error = true;
		}
		if ( true !== $season_data->home_away && false !== $season_data->home_away ) {
			$this->set_message( __( 'Fixture type must be set', 'racketmanager' ), true );
			$error = true;
		}
		if ( 'competition' === $season_data->type ) {
			if ( ! $season_data->closing_date ) {
				$this->set_message( __( 'Closing date must be set', 'racketmanager' ), true );
				$error = true;
			}
			if ( ! $season_data->date_start ) {
				$this->set_message( __( 'Start date must be set', 'racketmanager' ), true );
				$error = true;
			}
			if ( ! $season_data->date_end ) {
				$this->set_message( __( 'End date must be set', 'racketmanager' ), true );
				$error = true;
			}
		}
		if ( ! $season_data->type ) {
			$this->set_message( __( 'Type must be set', 'racketmanager' ), true );
			$error = true;
		}
		if ( ! $error ) {
			if ( 'competition' === $season_data->type ) {
				$competition = get_competition( $season_data->object_id );
				$object      = $competition;
			} elseif ( 'event' === $season_data->type ) {
				$event  = get_event( $season_data->object_id );
				$object = $event;
			}
			$object->seasons[ $season_data->season ] = array(
				'name'            => $season_data->season,
				'num_match_days'  => $season_data->num_match_days,
				'matchDates'      => $season_data->match_dates,
				'homeAway'        => $season_data->home_away,
				'fixedMatchDates' => $season_data->fixed_dates,
				'status'          => $season_data->status,
				'closing_date'    => $season_data->closing_date,
			);
			if ( 'competition' === $season_data->type ) {
				$object->seasons[ $season_data->season ]['dateStart'] = $season_data->date_start;
				$object->seasons[ $season_data->season ]['dateEnd']   = $season_data->date_end;
			}
			ksort( $object->seasons );
			if ( 'competition' === $season_data->type ) {
				$this->save_competition_seasons( $object->seasons, $season_data->object_id );
			} elseif ( 'event' === $season_data->type ) {
				$this->save_event_seasons( $object->seasons, $season_data->object_id );
			}
			/* translators: %s: season */
			$this->set_message( sprintf( __( 'Season %s saved', 'racketmanager' ), $season_data->season ) );
			if ( 'competition' === $season_data->type ) {
				$events = $competition->get_events();
				foreach ( $events as $event ) {
					$event_season                 = new \stdClass();
					$event_season->object_id      = $event->id;
					$event_season->type           = 'event';
					$event_season->season         = $season_data->season;
					$event_season->num_match_days = $season_data->num_match_days;
					$event_season->match_dates    = $season_data->match_dates;
					$event_season->home_away      = $season_data->home_away;
					$event_season->fixed_dates    = $season_data->fixed_dates;
					$event_season->status         = $season_data->status;
					$event_season->closing_date   = $season_data->closing_date;
					$this->edit_season( $event_season );
				}
			}
			if ( 'live' === $season_data->status && 'event' === $season_data->type && 'league' === $object->competition->type ) {
				$object->send_constitution( $object->seasons[ $season_data->season ] );
				$teams = $object->get_teams( array( 'status' => 3 ) );
				foreach ( $teams as $team ) {
					$league = get_league( $team->league_id );
					$league->delete_team( $team->team_id, $season_data->season );
				}
				/* translators: %s: season */
				$this->set_message( sprintf( __( 'Season %s saved and constitution emailed', 'racketmanager' ), $season_data->season ) );
			}
		}
	}

	/**
	 * Delete season of competition
	 *
	 * @param array $seasons seasons.
	 * @param int   $competition_id competition id.
	 * @return boolean
	 */
	private function delete_competition_season( $seasons, $competition_id ) {
		global $wpdb, $competition;

		$competition = get_competition( $competition_id );

		foreach ( $seasons as $season ) {
			foreach ( $competition->get_events() as $event ) {
				foreach ( $event->get_leagues() as $league ) {
					$league_id = $league->id;
					$league    = get_league( $league->id );
					// remove tables.
					$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->prepare(
							"DELETE FROM {$wpdb->racketmanager_table} WHERE `league_id` = %d AND `season` = %s",
							$league_id,
							$season
						)
					);
					// remove matches and rubbers.
					$matches = $league->get_matches( array( 'season' => $season ) );
					foreach ( $matches as $match ) {
						$match = get_match( $match->id );
						$match->delete();
					}
				}
			}
			unset( $competition->seasons[ $season ] );
		}
		$this->save_competition_seasons( $competition->seasons, $competition->id );

		return true;
	}

	/**
	 * Save seasons array to database
	 *
	 * @param array $seasons seasons.
	 * @param int   $competition_id competition id.
	 * @return boolean
	 */
	private function save_competition_seasons( $seasons, $competition_id ) {
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->racketmanager_competitions} SET `seasons` = %s WHERE `id` = %d",
				maybe_serialize( $seasons ),
				$competition_id
			)
		); // db call ok, no cache ok.
		wp_cache_delete( $competition_id, 'competitions' );
		return true;
	}
	/**
	 * Delete season of event
	 *
	 * @param array $seasons seasons.
	 * @param int   $event_id event id.
	 * @return boolean
	 */
	private function delete_event_season( $seasons, $event_id ) {
		global $wpdb, $event;

		$event = get_event( $event_id );

		foreach ( $seasons as $season ) {
			foreach ( $event->get_leagues() as $league ) {
				$league_id = $league->id;
				$league    = get_league( $league->id );
				// remove tables.
				$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						"DELETE FROM {$wpdb->racketmanager_table} WHERE `league_id` = %d AND `season` = %s",
						$league_id,
						$season
					)
				);
				// remove matches and rubbers.
				$matches = $league->get_matches( array( 'season' => $season ) );
				foreach ( $matches as $match ) {
					$match = get_match( $match->id );
					$match->delete();
				}
			}
			unset( $event->seasons[ $season ] );
		}
		$this->save_event_seasons( $event->seasons, $event->id );

		return true;
	}
	/**
	 * Save seasons array to database
	 *
	 * @param array $seasons seasons.
	 * @param int   $event_id event id.
	 * @return boolean
	 */
	private function save_event_seasons( $seasons, $event_id ) {
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->racketmanager_events} SET `seasons` = %s WHERE `id` = %d",
				maybe_serialize( $seasons ),
				$event_id
			)
		); // db call ok, no cache ok.
		wp_cache_delete( $event_id, 'events' );
		return true;
	}

	/**
	 * Display global settings page (e.g. color scheme options)
	 */
	public function display_options_page() {
		if ( ! current_user_can( 'manage_racketmanager' ) ) {
			$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			$options = $this->options;
			$comptab = 1;

			$tab = 0;
			if ( isset( $_POST['updateRacketManager'] ) ) {
				if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_manage-global-league-options' ) ) {
					$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
					$this->printMessage();
					return;
				}
				$options['rosters']['btm']                     = isset( $_POST['btmRequired'] ) ? sanitize_text_field( wp_unslash( $_POST['btmRequired'] ) ) : null;
				$options['rosters']['rosterEntry']             = isset( $_POST['clubPlayerEntry'] ) ? sanitize_text_field( wp_unslash( $_POST['clubPlayerEntry'] ) ) : null;
				$options['rosters']['rosterConfirmation']      = isset( $_POST['confirmation'] ) ? sanitize_text_field( wp_unslash( $_POST['confirmation'] ) ) : null;
				$options['rosters']['rosterConfirmationEmail'] = isset( $_POST['confirmationEmail'] ) ? sanitize_text_field( wp_unslash( $_POST['confirmationEmail'] ) ) : null;
				$options['rosters']['ageLimitCheck']           = isset( $_POST['clubPlayerAgeLimitCheck'] ) ? sanitize_text_field( wp_unslash( $_POST['clubPlayerAgeLimitCheck'] ) ) : null;
				$options['checks']['ageLimitCheck']            = isset( $_POST['ageLimitCheck'] ) ? sanitize_text_field( wp_unslash( $_POST['ageLimitCheck'] ) ) : null;
				$options['checks']['leadTimeCheck']            = isset( $_POST['leadTimeCheck'] ) ? sanitize_text_field( wp_unslash( $_POST['leadTimeCheck'] ) ) : null;
				$options['checks']['rosterLeadTime']           = isset( $_POST['playerLeadTime'] ) ? intval( $_POST['playerLeadTime'] ) : null;
				$options['checks']['playedRounds']             = isset( $_POST['playedRounds'] ) ? intval( $_POST['playedRounds'] ) : null;
				$options['checks']['playerLocked']             = isset( $_POST['playerLocked'] ) ? sanitize_text_field( wp_unslash( $_POST['playerLocked'] ) ) : null;
				$competition_types                             = Racketmanager_Util::get_competition_types();
				foreach ( $competition_types as $competition_type ) {
					$options[ $competition_type ]['matchCapability']         = isset( $_POST[ $competition_type ]['matchCapability'] ) ? sanitize_text_field( wp_unslash( $_POST[ $competition_type ]['matchCapability'] ) ) : null;
					$options[ $competition_type ]['resultConfirmation']      = isset( $_POST[ $competition_type ]['resultConfirmation'] ) ? sanitize_text_field( wp_unslash( $_POST[ $competition_type ]['resultConfirmation'] ) ) : null;
					$options[ $competition_type ]['resultEntry']             = isset( $_POST[ $competition_type ]['resultEntry'] ) ? sanitize_text_field( wp_unslash( $_POST[ $competition_type ]['resultEntry'] ) ) : null;
					$options[ $competition_type ]['resultConfirmationEmail'] = isset( $_POST[ $competition_type ]['resultConfirmationEmail'] ) ? sanitize_text_field( wp_unslash( $_POST[ $competition_type ]['resultConfirmationEmail'] ) ) : null;
					$options[ $competition_type ]['resultNotification']      = isset( $_POST[ $competition_type ]['resultNotification'] ) ? sanitize_text_field( wp_unslash( $_POST[ $competition_type ]['resultNotification'] ) ) : null;
					$options[ $competition_type ]['resultPending']           = isset( $_POST[ $competition_type ]['resultPending'] ) ? sanitize_text_field( wp_unslash( $_POST[ $competition_type ]['resultPending'] ) ) : null;
					$options[ $competition_type ]['confirmationPending']     = isset( $_POST[ $competition_type ]['confirmationPending'] ) ? sanitize_text_field( wp_unslash( $_POST[ $competition_type ]['confirmationPending'] ) ) : null;
					$options[ $competition_type ]['confirmationTimeout']     = isset( $_POST[ $competition_type ]['confirmationTimeout'] ) ? sanitize_text_field( wp_unslash( $_POST[ $competition_type ]['confirmationTimeout'] ) ) : null;
					$this->scheduleResultChase( $competition_type, $options[ $competition_type ] );
				}
				$options['colors']['headers']                = isset( $_POST['color_headers'] ) ? sanitize_text_field( wp_unslash( $_POST['color_headers'] ) ) : null;
				$options['colors']['rows']                   = array(
					'alternate'  => isset( $_POST['color_rows_alt'] ) ? sanitize_text_field( wp_unslash( $_POST['color_rows_alt'] ) ) : null,
					'main'       => isset( $_POST['color_rows'] ) ? sanitize_text_field( wp_unslash( $_POST['color_rows'] ) ) : null,
					'ascend'     => isset( $_POST['color_rows_ascend'] ) ? sanitize_text_field( wp_unslash( $_POST['color_rows_ascend'] ) ) : null,
					'descend'    => isset( $_POST['color_rows_descend'] ) ? sanitize_text_field( wp_unslash( $_POST['color_rows_descend'] ) ) : null,
					'relegation' => isset( $_POST['color_rows_relegation'] ) ? sanitize_text_field( wp_unslash( $_POST['color_rows_relegation'] ) ) : null,
				);
				$options['colors']['boxheader']              = array( isset( $_POST['color_boxheader1'] ) ? sanitize_text_field( wp_unslash( $_POST['color_boxheader1'] ) ) : null, isset( $_POST['color_boxheader2'] ) ? sanitize_text_field( wp_unslash( $_POST['color_boxheader2'] ) ) : null );
				$options['championship']['numRounds']        = isset( $_POST['numRounds'] ) ? sanitize_text_field( wp_unslash( $_POST['numRounds'] ) ) : null;
				$options['billing']['billingEmail']          = isset( $_POST['billingEmail'] ) ? sanitize_text_field( wp_unslash( $_POST['billingEmail'] ) ) : null;
				$options['billing']['billingAddress']        = isset( $_POST['billingAddress'] ) ? sanitize_text_field( wp_unslash( $_POST['billingAddress'] ) ) : null;
				$options['billing']['billingTelephone']      = isset( $_POST['billingTelephone'] ) ? sanitize_text_field( wp_unslash( $_POST['billingTelephone'] ) ) : null;
				$options['billing']['billingCurrency']       = isset( $_POST['billingCurrency'] ) ? sanitize_text_field( wp_unslash( $_POST['billingCurrency'] ) ) : null;
				$options['billing']['bankName']              = isset( $_POST['bankName'] ) ? sanitize_text_field( wp_unslash( $_POST['bankName'] ) ) : null;
				$options['billing']['sortCode']              = isset( $_POST['sortCode'] ) ? sanitize_text_field( wp_unslash( $_POST['sortCode'] ) ) : null;
				$options['billing']['accountNumber']         = isset( $_POST['accountNumber'] ) ? intval( $_POST['accountNumber'] ) : null;
				$options['billing']['invoiceNumber']         = isset( $_POST['invoiceNumber'] ) ? intval( $_POST['invoiceNumber'] ) : null;
				$options['billing']['paymentTerms']          = isset( $_POST['paymentTerms'] ) ? intval( $_POST['paymentTerms'] ) : null;
				$options['keys']['googleMapsKey']            = isset( $_POST['googleMapsKey'] ) ? sanitize_text_field( wp_unslash( $_POST['googleMapsKey'] ) ) : null;
				$options['keys']['recaptchaSiteKey']         = isset( $_POST['recaptchaSiteKey'] ) ? sanitize_text_field( wp_unslash( $_POST['recaptchaSiteKey'] ) ) : null;
				$options['keys']['recaptchaSecretKey']       = isset( $_POST['recaptchaSecretKey'] ) ? sanitize_text_field( wp_unslash( $_POST['recaptchaSecretKey'] ) ) : null;
				$options['player']['walkover']['female']     = isset( $_POST['walkoverFemale'] ) ? intval( $_POST['walkoverFemale'] ) : null;
				$options['player']['noplayer']['female']     = isset( $_POST['noplayerFemale'] ) ? intval( $_POST['noplayerFemale'] ) : null;
				$options['player']['share']['female']        = isset( $_POST['shareFemale'] ) ? intval( $_POST['shareFemale'] ) : null;
				$options['player']['unregistered']['female'] = isset( $_POST['unregisteredFemale'] ) ? intval( $_POST['unregisteredFemale'] ) : null;
				$options['player']['walkover']['male']       = isset( $_POST['walkoverMale'] ) ? intval( $_POST['walkoverMale'] ) : null;
				$options['player']['noplayer']['male']       = isset( $_POST['noplayerMale'] ) ? intval( $_POST['noplayerMale'] ) : null;
				$options['player']['share']['male']          = isset( $_POST['shareMale'] ) ? intval( $_POST['shareMale'] ) : null;
				$options['player']['unregistered']['male']   = isset( $_POST['unregisteredMale'] ) ? intval( $_POST['unregisteredMale'] ) : null;
				$options['player']['walkover']['rubber']     = isset( $_POST['walkoverPointsRubber'] ) ? intval( $_POST['walkoverPointsRubber'] ) : null;
				$options['player']['walkover']['match']      = isset( $_POST['walkoverPointsMatch'] ) ? intval( $_POST['walkoverPointsMatch'] ) : null;
				$options['player']['share']['rubber']        = isset( $_POST['sharePoints'] ) ? intval( $_POST['sharePoints'] ) : null;

				update_option( 'racketmanager', $options );
				$this->set_message( __( 'Settings saved', 'racketmanager' ) );
				$this->printMessage();

				// Set active tab.
				$tab = isset( $_POST['active-tab'] ) ? sanitize_text_field( wp_unslash( $_POST['active-tab'] ) ) : null;
			}

			require_once RACKETMANAGER_PATH . '/admin/settings-global.php';
		}
	}

	/**
	 * Add meta box to post screen
	 *
	 * @param object $post post details.
	 */
	public function add_meta_box( $post ) {
		global $wpdb, $post;
		$leagues = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT `title`, `id` FROM {$wpdb->racketmanager} ORDER BY id ASC"
		);
		if ( $leagues ) {
			$league_id   = 0;
			$match_id    = 0;
			$season      = 0;
			$curr_league = false;
			$match       = false;
			if ( 0 !== $post->ID ) {
				$match = $wpdb->get_row( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						"SELECT `id`, `league_id`, `season` FROM {$wpdb->racketmanager_matches} WHERE `post_id` = %d",
						$post->ID
					)
				);

				if ( $match ) {
					$match_id    = $match->id;
					$league_id   = $match->league_id;
					$season      = $match->season;
					$curr_league = get_league( $league_id );
				}
			}

			echo "<input type='hidden' name='curr_match_id' value='" . esc_html( $match_id ) . "' />";
			echo '<div class="container">';
			echo '<div class="row">';
			echo '<div class="col-auto">';
			echo '<div class="form-floating mb-3">';
			echo "<select name='league_id' class='alignleft form-select' id='league_id' onChange='Racketmanager.getSeasonDropdown(this.value, " . esc_html( $season ) . ")'>";
			echo '<option value="0">' . esc_html__( 'Choose League', 'racketmanager' ) . '</option>';
			foreach ( $leagues as $league ) {
				echo "<option value='" . esc_html( $league->id ) . "'" . selected( $league_id, $league->id, false ) . '>' . esc_html( $league->title ) . '</option>';
			}
			echo '</select>';
			echo '<label for="league_id">' . esc_html__( 'League', 'racketmanager' );
			echo '</div>';
			echo '</div>';
			echo '</div>';
			echo '<div class="row">';
			echo '<div class="col-auto">';
			echo '<div id="seasons" class="form-floating">';
			if ( $match ) {
				echo $curr_league->get_season_dropdown( $curr_league->get_season() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			echo '</div>';
			echo '</div>';
			echo '</div>';
			echo '<div class="row">';
			echo '<div class="col-auto">';
			echo '<div id="matches" class="form-floating">';
			if ( $match ) {
				echo $curr_league->get_match_dropdown( $match->id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			echo '</div>';
			echo '</div>';
			echo '</div>';
			echo '</div>';

			echo '<br style="clear: both;" />';
		}
	}

	/**
	 * Update post id for match report
	 */
	public function edit_match_report() {
		global $wpdb;
		//phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['post_ID'] ) ) {
			$post_id       = intval( $_POST['post_ID'] );
			$match_id      = isset( $_POST['match_id'] ) ? intval( $_POST['match_id'] ) : false;
			$curr_match_id = isset( $_POST['curr_match_id'] ) ? intval( $_POST['curr_match_id'] ) : false;

			if ( $match_id && $curr_match_id !== $match_id ) {
				$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						"UPDATE {$wpdb->racketmanager_matches} SET `post_id` = %d WHERE `id` = %d",
						$post_id,
						$match_id
					)
				);
				if ( 0 !== $curr_match_id ) {
					$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->prepare(
							"UPDATE {$wpdb->racketmanager_matches} SET `post_id` = 0 WHERE `id` = %d",
							$curr_match_id
						)
					);
				}
			}
		}
		//phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/************
	 *
	 *   CLUB PLAYERS SECTION
	 */

	/**
	 * Delete Club Player Request
	 *
	 * @param int $player_request_id player request id.
	 * @return boolean
	 */
	private function delete_player_request( $player_request_id ) {
		global $wpdb;

		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE FROM {$wpdb->racketmanager_club_player_requests} WHERE `id` = %d",
				$player_request_id
			)
		);
		$this->set_message( __( 'Club Player request deleted', 'racketmanager' ) );

		return true;
	}

	/**
	 * Import data from CSV file
	 *
	 * @param int    $league_id league.
	 * @param string $season season.
	 * @param array  $file CSV file.
	 * @param string $delimiter delimiter.
	 * @param array  $mode 'teams' | 'matches' | 'fixtures' | 'players' | 'clubplayers'.
	 * @param int    $club - optional.
	 */
	private function import( $league_id, $season, $file, $delimiter, $mode, $club = false ) {
		if ( empty( $file['name'] ) ) {
			$this->set_message( __( 'No file specified for upload', 'racketmanager' ), true );
		} elseif ( 0 === $file['size'] ) {
			$this->set_message( __( 'Upload file is empty', 'racketmanager' ), true );
		} else {
			$access_type = get_filesystem_method();
			if ( 'direct' === $access_type ) {
				/* you can safely run request_filesystem_credentials() without any issues and don't need to worry about passing in a URL */
				$creds = request_filesystem_credentials( site_url() . '/wp-admin/', '', false, false, array() );
				/* initialize the API */
				if ( ! WP_Filesystem( $creds ) ) {
					/* any problems and we exit */
					$this->set_message( __( 'Unable to access file system', 'racketmanager' ), true );
				} else {
					global $wp_filesystem;
					$new_file = Racketmanager_Util::get_file_path( $file['name'] );
					if ( $wp_filesystem->copy( $file['tmp_name'], $new_file ) ) {
						$contents = $wp_filesystem->get_contents_array( $new_file );
						if ( $contents ) {
							$league_id = intval( $league_id );
							$club      = isset( $club ) ? intval( $club ) : 0;
							if ( 'TAB' === $delimiter ) {
								$delimiter = "\t"; // correct tabular delimiter.
							}
							if ( 'table' === $mode ) {
								$this->importTable( $contents, $delimiter, $league_id, $season );
							} elseif ( 'fixtures' === $mode ) {
								$this->importFixtures( $contents, $delimiter, $league_id, $season );
							} elseif ( 'clubplayers' === $mode ) {
								$this->importClubPlayers( $contents, $delimiter, $club );
							} elseif ( 'players' === $mode ) {
								$this->importPlayers( $contents, $delimiter );
							} else {
								$this->set_message( __( 'Type of data to upload not selected', 'racketmanager' ), true );
							}
						} else {
							$this->set_message( __( 'Unable to read file contents', 'racketmanager' ), true );
						}
						if ( ! $wp_filesystem->delete( $new_file ) ) {
							$this->set_message( __( 'Unable to delete file', 'racketmanager' ), true );
						}
					} else {
						/* translators: %s: location of file */
						$this->set_message( sprintf( __( 'The uploaded file could not be moved to %s.', 'racketmanager' ), ABSPATH . 'wp-content/uploads' ) );
					}
				}
			} else {
				$this->set_message( __( 'Unable to access file', 'racketmanager' ), true );
			}
		}
	}

	/**
	 * Import table from CSV file
	 *
	 * @param array  $contents array of file contents.
	 * @param string $delimiter delimiter.
	 * @param int    $league_id league.
	 * @param string $season season.
	 */
	private function importTable( $contents, $delimiter, $league_id, $season ) {
		$league       = get_league( $league_id );
		$teams        = array();
		$points_plus  = array();
		$points_minus = array();
		$pld          = array();
		$won          = array();
		$draw         = array();
		$lost         = array();
		$custom       = array();
		$add_points   = array();
		$i            = 0;
		$x            = 0;
		foreach ( $contents as $record ) {
			$line = explode( $delimiter, $record );
			// ignore header and empty lines.
			if ( $i > 0 && count( $line ) > 1 ) {
				$team    = $line[0];
				$team_id = $this->getteam_id( $team );
				if ( 0 !== $team_id ) {
					$table_id = $league->add_team( $team_id, $season );
					if ( $table_id ) {
						$teams[ $team_id ] = $team_id;
						$pld[ $team_id ]   = isset( $line[1] ) ? $line[1] : 0;
						$won[ $team_id ]   = isset( $line[2] ) ? $line[2] : 0;
						$draw[ $team_id ]  = isset( $line[3] ) ? $line[3] : 0;
						$lost[ $team_id ]  = isset( $line[4] ) ? $line[4] : 0;
						if ( isset( $line[5] ) ) {
							if ( strpos( $line[5], ':' ) !== false ) {
								$points2 = explode( ':', $line[5] );
							} else {
								$points2 = array( $line[5], 0 );
							}
						} else {
							$points2 = array( 0, 0 );
						}
						if ( isset( $line[6] ) ) {
							if ( strpos( $line[6], ':' ) !== false ) {
								$points = explode( ':', $line[6] );
							} else {
								$points = array( $line[6], 0 );
							}
						} else {
							$points = array( 0, 0 );
						}
						$points_plus[ $team_id ]       = $points[0];
						$points_minus[ $team_id ]      = $points[1];
						$custom[ $team_id ]['points2'] = array(
							'plus'  => $points2[0],
							'minus' => $points2[1],
						);
						$add_points[ $team_id ]        = 0;
						++$x;
					}
				}
			}
			++$i;
		}
		/* translators: %d: number of table entries imported */
		$this->set_message( sprintf( __( '%d Table Entries imported', 'racketmanager' ), $x ) );
	}

	/**
	 * Import fixtures from file
	 *
	 * @param array  $contents array of file contents.
	 * @param string $delimiter delimiter.
	 * @param int    $league_id league.
	 * @param string $season season.
	 */
	private function importFixtures( $contents, $delimiter, $league_id, $season ) {
		$league  = get_league( $league_id );
		$rubbers = $league->num_rubbers;
		if ( is_null( $rubbers ) ) {
			$rubbers = 1;
		}
		$matches     = array();
		$home_points = array();
		$away_points = array();
		$home_teams  = array();
		$away_teams  = array();
		$custom      = array();
		$i           = 0;
		$x           = 0;
		foreach ( $contents as $record ) {
			$line = explode( $delimiter, $record );
			// ignore header and empty lines.
			if ( $i > 0 && count( $line ) > 1 ) {
				$match            = new \stdClass();
				$date             = ( ! empty( $line[6] ) ) ? $line[0] . ' ' . $line[6] : $line[0] . ' 00:00';
				$match->match_day = isset( $line[1] ) ? $line[1] : '';
				$match->date      = trim( $date );
				$match->season    = $season;
				$match->home_team = $this->getteam_id( $line[2] );
				$match->away_team = $this->getteam_id( $line[3] );
				if ( 0 !== $match->home_team && 0 !== $match->away_team ) {
					$match->location          = isset( $line[4] ) ? $line[4] : '';
					$match->group             = isset( $line[5] ) ? $line[5] : '';
					$match                    = new Racketmanager_Match( $match );
					$match_id                 = $match->id;
					$matches[ $match_id ]     = $match_id;
					$home_teams[ $match_id ]  = $match->home_team;
					$away_teams[ $match_id ]  = $match->away_team;
					$home_points[ $match_id ] = '';
					$away_points[ $match_id ] = '';

					$custom = apply_filters( 'racketmanager_import_fixtures_' . $league->sport, $custom, $match_id );
				}
				++$x;
			}
			++$i;
		}
		/* translators: %d: number of fixtures imported */
		$this->set_message( sprintf( __( '%d Fixtures imported', 'racketmanager' ), $x ) );
	}

	/**
	 * Import players from file
	 *
	 * @param array  $contents array of file contents.
	 * @param string $delimiter delimiter.
	 */
	private function importPlayers( $contents, $delimiter ) {
		$error_messages = array();
		$i              = 0;
		$x              = 0;
		foreach ( $contents as $record ) {
			$line = explode( $delimiter, $record );
			// ignore header and empty lines.
			if ( $i > 0 && count( $line ) > 1 ) {
				$_POST['firstname']     = isset( $line[0] ) ? $line[0] : '';
				$_POST['surname']       = isset( $line[1] ) ? $line[1] : '';
				$_POST['gender']        = isset( $line[2] ) ? $line[2] : '';
				$_POST['btm']           = isset( $line[3] ) ? $line[3] : '';
				$_POST['email']         = isset( $line[4] ) ? $line[4] : '';
				$_POST['contactno']     = isset( $line[5] ) ? $line[5] : '';
				$_POST['year_of_birth'] = isset( $line[6] ) ? $line[6] : '';
				$player_valid           = $this->validatePlayer();
				if ( $player_valid[0] ) {
					$new_player = $player_valid[1];
					$player     = get_player( $new_player->user_login, 'login' );  // get player by login.
					if ( ! $player ) {
						$player = new Racketmanager_Player( $new_player );
						if ( $player ) {
							++$x;
						}
					}
				} else {
					$error_messages = $player_valid[2];
					/* translators: %d: player line with error */
					$message = sprintf( __( 'Error with player %d details', 'racketmanager' ), $i );
					foreach ( $error_messages as $error_message ) {
						$message .= '<br>' . $error_message;
					}
					$error_messages[] = $message;
				}
			}
			++$i;
		}
		/* translators: %d: number of players imported */
		$message = sprintf( __( '%d Players imported', 'racketmanager' ), $x );
		foreach ( $error_messages as $error_message ) {
			$message .= '<br>' . $error_message;
		}
		$this->set_message( $message );
	}

	/**
	 * Import club players from file
	 *
	 * @param array  $contents array of file contents.
	 * @param string $delimiter delimiter.
	 * @param int    $club club.
	 */
	private function importClubPlayers( $contents, $delimiter, $club ) {
		$club           = get_club( $club );
		$i              = 0;
		$x              = 0;
		$error_messages = array();
		foreach ( $contents as $record ) {
			$line = explode( $delimiter, $record );
			// ignore header and empty lines.
			if ( $i > 0 && count( $line ) > 1 ) {
				$_POST['firstname']     = isset( $line[0] ) ? $line[0] : '';
				$_POST['surname']       = isset( $line[1] ) ? $line[1] : '';
				$_POST['gender']        = isset( $line[2] ) ? $line[2] : '';
				$_POST['btm']           = isset( $line[3] ) ? $line[3] : '';
				$_POST['email']         = isset( $line[4] ) ? $line[4] : '';
				$_POST['contactno']     = isset( $line[5] ) ? $line[5] : '';
				$_POST['year_of_birth'] = isset( $line[6] ) ? $line[6] : '';
				$player_valid           = $this->validatePlayer();
				if ( $player_valid[0] ) {
					$new_player = $player_valid[1];
					$club->register_player( $new_player );
					++$x;
				} else {
					$error_messages = $player_valid[2];
					/* translators: %d: player id */
					$message = sprintf( __( 'Error with player %d details', 'racketmanager' ), $i );
					foreach ( $error_messages as $error_message ) {
						$message .= '<br>' . $error_message;
					}
					$error_messages[] = $message;
				}
			}
			++$i;
		}
		/* translators: %d: number of players imported */
		$message = sprintf( __( '%d Club Players imported', 'racketmanager' ), $x );
		foreach ( $error_messages as $error_message ) {
			$message .= '<br>' . $error_message;
		}
		$this->set_message( $message );
	}

	/**
	 * Recursively apply htmlspecialchars to an array
	 *
	 * @param array $arr array.
	 */
	public function htmlspecialchars_array( $arr = array() ) {
		$rs = array();
		foreach ( $arr as $key => $val ) {
			if ( is_array( $val ) ) {
				$rs[ $key ] = $this->htmlspecialchars_array( $val );
			} else {
				$rs[ $key ] = htmlspecialchars( $val, ENT_QUOTES );
			}
		}
		return $rs;
	}

	/**
	 * Display event dropdown
	 *
	 * @param int $competition_id competition details.
	 */
	public function get_event_dropdown( $competition_id = false ) {
		$return = new \stdClass();
		if ( isset( $_POST['security'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), 'ajax-nonce' ) ) {
				$return->error = true;
				$return->msg   = __( 'Security token invalid', 'racketmanager' );
			}
		} else {
			$return->error = true;
			$return->msg   = __( 'No security token found in request', 'racketmanager' );
		}
		if ( ! isset( $return->error ) ) {
			if ( ! $competition_id && isset( $_POST['competition_id'] ) ) {
				$competition_id = intval( $_POST['competition_id'] );
			}
			if ( $competition_id ) {
				$competition = get_competition( $competition_id );
				$events      = $competition->get_events();
				ob_start();
				?>
				<select size='1' name='event_id' id='event_id' class="form-select" onChange='Racketmanager.getLeagueDropdown(this.value)'>
					<option value='0'><?php esc_html_e( 'Choose event', 'racketmanager' ); ?></option>
					<?php foreach ( $events as $event ) { ?>
						<option value=<?php echo esc_html( $event->id ); ?>><?php echo esc_html( $event->name ); ?></option>
					<?php } ?>
				</select>
				<label for="event_id"><?php esc_html_e( 'Event', 'racketmanager' ); ?></label>
				<?php
				$output = ob_get_contents();
				ob_end_clean();
			} else {
				$return->error = true;
				$return->msg   = __( 'Competition not selected', 'racketmanager' );
			}
		}
		if ( isset( $return->error ) ) {
			wp_send_json_error( $return->msg, 500 );
		} else {
			wp_send_json_success( $output );
		}
	}

	/**
	 * Display league dropdown
	 *
	 * @param int $event_id event details.
	 */
	public function get_league_dropdown( $event_id = false ) {
		$return = new \stdClass();
		if ( isset( $_POST['security'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), 'ajax-nonce' ) ) {
				$return->error = true;
				$return->msg   = __( 'Security token invalid', 'racketmanager' );
			}
		} else {
			$return->error = true;
			$return->msg   = __( 'No security token found in request', 'racketmanager' );
		}
		if ( ! isset( $return->error ) ) {
			if ( ! $event_id && isset( $_POST['event_id'] ) ) {
				$event_id = intval( $_POST['event_id'] );
			}
			if ( $event_id ) {
				$event   = get_event( $event_id );
				$leagues = $event->get_leagues();
				ob_start();
				?>
				<select size='1' name='league_id' id='league_id' class="form-select" onChange='Racketmanager.getSeasonDropdown(this.value)'>
					<option value='0'><?php esc_html_e( 'Choose league', 'racketmanager' ); ?></option>
					<?php foreach ( $leagues as $league ) { ?>
						<option value=<?php echo esc_html( $league->id ); ?>><?php echo esc_html( $league->title ); ?></option>
					<?php } ?>
				</select>
				<label for="league_id"><?php esc_html_e( 'League', 'racketmanager' ); ?></label>
				<?php
				$output = ob_get_contents();
				ob_end_clean();
			} else {
				$return->error = true;
				$return->msg   = __( 'Event not selected', 'racketmanager' );
			}
		}
		if ( isset( $return->error ) ) {
			wp_send_json_error( $return->msg, 500 );
		} else {
			wp_send_json_success( $output );
		}
	}

	/**
	 * Gets results checker from database
	 *
	 * @param array $args query arguments.
	 * @return array
	 */
	public function getResultsChecker( $args = array() ) {
		global $wpdb;

		$defaults    = array(
			'season'      => false,
			'status'      => false,
			'competition' => false,
			'event'       => false,
		);
		$args        = array_merge( $defaults, $args );
		$season      = $args['season'];
		$status      = $args['status'];
		$competition = $args['competition'];
		$event       = $args['event'];
		$sql         = "SELECT `id`, `league_id`, `match_id`, `team_id`, `player_id`, `updated_date`, `updated_user`, `description`, `status` FROM {$wpdb->racketmanager_results_checker} WHERE 1 = 1";

		if ( $status && 'all' !== $status ) {
			if ( 'outstanding' === $status ) {
				$sql .= ' AND `status` IS NULL';
			} else {
				$sql .= $wpdb->prepare( ' AND `status` = %d', $status );
			}
		}
		if ( $season && 'all' !== $season ) {
			$sql .= $wpdb->prepare( " AND `match_id` IN (SELECT `id` FROM {$wpdb->racketmanager_matches} WHERE `season` = %s)", $season );
		}
		if ( $competition && 'all' !== $competition ) {
			$sql .= $wpdb->prepare( " AND `match_id` IN (SELECT m.`id` FROM {$wpdb->racketmanager_matches} m, {$wpdb->racketmanager} l WHERE m.`league_id` = l.`id` AND l.`event_id` IN (SELECT `id` FROM {$wpdb->racketmanager_events} WHERE `competition_id` = %d))", $competition );
		} elseif ( $event && 'all' !== $event ) {
			$sql .= $wpdb->prepare( " AND `match_id` IN (SELECT m.`id` FROM {$wpdb->racketmanager_matches} m, {$wpdb->racketmanager} l WHERE m.`league_id` = l.`id` AND l.`event_id` = %d)", $event );
		}

		$sql .= ' ORDER BY `match_id` DESC, `league_id` ASC, `team_id` ASC, `player_id` ASC';

		$results_checkers = wp_cache_get(
			md5( $sql ),
			'results_checkers'
		);
		if ( ! $results_checkers ) {
			$results_checkers = $wpdb->get_results(
				//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql
			); // db call ok.
			wp_cache_set(
				md5( $sql ),
				$results_checkers,
				'results_checkers'
			);
		}

		$class = '';
		foreach ( $results_checkers as $i => $results_checker ) {
			$class                  = ( 'alternate' === $class ) ? '' : 'alternate';
			$results_checker->class = $class;

			$results_checker->match = get_match( $results_checker->match_id );
			$results_checker->team  = '';
			if ( $results_checker->team_id > 0 ) {
				if ( $results_checker->team_id === $results_checker->match->home_team ) {
					$results_checker->team = $results_checker->match->teams['home']->title;
				} elseif ( $results_checker->team_id === $results_checker->match->away_team ) {
					$results_checker->team = $results_checker->match->teams['away']->title;
				}
			}
			$player = get_userdata( $results_checker->player_id );
			if ( $player ) {
				$results_checker->player = $player->display_name;
			} else {
				$results_checker->player = '';
			}
			$results_checker->updated_user_name = '';
			if ( '' !== $results_checker->updated_user ) {
				$user = get_userdata( $results_checker->updated_user );
				if ( $user ) {
					$results_checker->updated_user_name = $user->fullname;
				}
			}
			if ( 1 === $results_checker->status ) {
				$results_checker->status = 'Approved';
			} elseif ( 2 === $results_checker->status ) {
				$results_checker->status = 'Handled';
			} else {
				$results_checker->status = '';
			}

			$results_checkers[ $i ] = $results_checker;
		}

		return $results_checkers;
	}

	/**
	 * Get single results checker
	 *
	 * @param int $results_checker_id id of entry to return.
	 * @return object
	 */
	private function getResultsCheckerEntry( $results_checker_id ) {
		global $wpdb;
		return $wpdb->get_row( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT `league_id`, `match_id`, `team_id`, `player_id`, `rubber_id`, `updated_date`, `updated_user`, `description`, `status` FROM {$wpdb->racketmanager_results_checker} WHERE `id` = '" . intval( $results_checker_id ) . "'"
		);
	}

	/**
	 * Approve Results Checker entry
	 *
	 * @param int $results_checker_id id of entry to approve.
	 * @return boolean
	 */
	private function approveResultsChecker( $results_checker_id ) {
		global $wpdb, $racketmanager;

		$results_checker = $this->getResultsCheckerEntry( $results_checker_id );
		if ( empty( $results_checker->updated_date ) ) {
			$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"UPDATE {$wpdb->racketmanager_results_checker} SET `updated_date` = now(), `updated_user` = %d, `status` = 1 WHERE `id` = %d ",
					get_current_user_id(),
					$results_checker_id
				)
			);
			$racketmanager->set_message( __( 'Results checker approved', 'racketmanager' ) );
		}

		return true;
	}

	/**
	 * Handle Results Checker entry
	 *
	 * @param int $results_checker_id id of entry to handle.
	 * @return boolean
	 */
	private function handleResultsChecker( $results_checker_id ) {
		global $wpdb, $racketmanager_shortcodes;

		$results_checker = $this->getResultsCheckerEntry( $results_checker_id );
		if ( empty( $results_checker->updated_date ) ) {
			$player = get_player( $results_checker->player_id );
			$match  = get_match( $results_checker->match_id );
			if ( $match ) {
				$rubber = get_rubber( $results_checker->rubber_id );
				if ( $rubber ) {
					$num_sets_to_win  = $match->league->num_sets_to_win;
					$num_games_to_win = 1;
					$set_type         = isset( $rubber->sets[1]['settype'] ) ? $rubber->sets[1]['settype'] : null;
					if ( $set_type ) {
						$set_info = Racketmanager_Util::get_set_info( $set_type );
						if ( $set_info ) {
							$num_games_to_win = $set_info->min_win;
						}
					}
					$points = array();
					if ( $results_checker->team_id === $match->home_team ) {
						$points['home']['walkover'] = true;
						if ( 'home' === $rubber->custom['walkover'] ) {
							$points['away']['walkover'] = true;
							$rubber->custom['walkover'] = 'both';
							$points['away']['sets']     = 0;
							$stats['sets']['away']      = 0;
							$stats['games']['away']     = 0;
						} else {
							$rubber->custom['walkover'] = 'away';
							$points['away']['sets']     = $num_sets_to_win;
							$stats['sets']['away']      = $num_sets_to_win;
							$stats['games']['away']     = $num_games_to_win * $num_sets_to_win;
						}
						$points['home']['sets'] = 0;
						$stats['sets']['home']  = 0;
						$stats['games']['home'] = 0;
					} else {
						$points['away']['walkover'] = true;
						if ( 'away' === $rubber->custom['walkover'] ) {
							$points['home']['walkover'] = true;
							$rubber->custom['walkover'] = 'both';
							$points['home']['sets']     = 0;
							$stats['sets']['home']      = 0;
							$stats['games']['home']     = 0;
						} else {
							$rubber->custom['walkover'] = 'home';
							$points['home']['sets']     = $num_sets_to_win;
							$stats['sets']['home']      = $num_sets_to_win;
							$stats['games']['home']     = $num_games_to_win * $num_sets_to_win;
						}
						$points['away']['sets'] = 0;
						$stats['sets']['away']  = 0;
						$stats['games']['away'] = 0;
					}
					$points['home']['team']  = $match->home_team;
					$points['away']['team']  = $match->away_team;
					$result                  = $rubber->calculate_result( $points );
					$rubber->home_points     = $result->home;
					$rubber->away_points     = $result->away;
					$rubber->winner_id       = $result->winner;
					$rubber->loser_id        = $result->loser;
					$rubber->custom['stats'] = $stats;
					$rubber->status          = '1';
					$rubber->update_result();
				}
				$comments = $match->comments;
				$comment  = $rubber->title . ': ' . __( 'ineligible player', 'racketmanager' ) . ' ' . $player->display_name . ' - ' . $results_checker->description;
				if ( empty( $comments['result'] ) ) {
					$comments['result'] = $comment;
				} else {
					$comments['result'] .= "\n" . $comment;
				}
				$match->set_comments( $comments );
				$match->update_result( $match->home_points, $match->away_points, $match->custom, $match->confirmed );
				$organisation_name = $this->site_name;
				$headers           = array();
				$email_from        = $this->get_confirmation_email( $match->league->event->competition->type );
				$headers[]         = 'From: ' . ucfirst( $match->league->event->competition->type ) . ' Secretary <' . $email_from . '>';
				$headers[]         = 'cc: ' . ucfirst( $match->league->event->competition->type ) . ' Secretary <' . $email_from . '>';
				$email_subject     = $this->site_name . ' - ' . $match->teams['home']->title . ' - ' . $match->teams['away']->title . ' - ' . __( 'ineligible player', 'racketmanager' );
				if ( $results_checker->team_id === $match->home_team ) {
					$captain   = $match->teams['home']->captain;
					$opponent  = $match->teams['away']->title;
					$email_to  = $match->teams['home']->captain . ' <' . $match->teams['home']->contactemail . '>';
					$headers[] = 'cc: ' . $match->teams['away']->captain . ' <' . $match->teams['away']->contactemail . '>';
				} elseif ( $results_checker->team_id === $match->away_team ) {
					$captain   = $match->teams['away']->captain;
					$opponent  = $match->teams['home']->title;
					$email_to  = $match->teams['away']->captain . ' <' . $match->teams['away']->contactemail . '>';
					$headers[] = 'cc: ' . $match->teams['home']->captain . ' <' . $match->teams['home']->contactemail . '>';
				}
				$headers[]     = 'cc: ' . $match->teams['home']->club->match_secretary_name . ' <' . $match->teams['home']->club->match_secretary_email . '>';
				$headers[]     = 'cc: ' . $match->teams['away']->club->match_secretary_name . ' <' . $match->teams['away']->club->match_secretary_email . '>';
				$email_message = $racketmanager_shortcodes->load_template(
					'result-check',
					array(
						'email_subject' => $email_subject,
						'organisation'  => $organisation_name,
						'captain'       => $captain,
						'opponent'      => $opponent,
						'player'        => $player->display_name,
						'reason'        => $results_checker->description,
						'contact_email' => $email_from,
					),
					'email'
				);
				wp_mail( $email_to, $email_subject, $email_message, $headers );
			}
			$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"UPDATE {$wpdb->racketmanager_results_checker} SET `updated_date` = now(), `updated_user` = %d, `status` = 2 WHERE `id` = %d ",
					get_current_user_id(),
					$results_checker_id
				)
			);
			$this->set_message( __( 'Results checker updated', 'racketmanager' ) );
		}

		return true;
	}

	/**
	 * Delete Results Checker entry
	 *
	 * @param int $results_checker_id id of entry to delete.
	 * @return boolean
	 */
	private function deleteResultsChecker( $results_checker_id ) {
		global $wpdb;

		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE FROM {$wpdb->racketmanager_results_checker} WHERE `id` = %d",
				$results_checker_id
			)
		);
		$this->set_message( __( 'Results checker deleted', 'racketmanager' ) );

		return true;
	}

	/**
	 * Contact League Teams
	 *
	 * @param int    $league league.
	 * @param string $season season.
	 * @param string $email_message message.
	 * @return boolean
	 */
	private function contactLeagueTeams( $league, $season, $email_message ) {
		global $wpdb, $racketmanager;

		$league        = get_league( $league );
		$teams         = $league->get_league_teams( array( 'season' => $season ) );
		$email_message = str_replace( '\"', '"', $email_message );
		$headers       = array();
		$email_from    = $this->get_confirmation_email( $league->event->competition->type );
		$headers[]     = 'From: ' . ucfirst( $league->event->competition->type ) . ' Secretary <' . $email_from . '>';
		$headers[]     = 'cc: ' . ucfirst( $league->event->competition->type ) . ' Secretary <' . $email_from . '>';
		$email_subject = $this->site_name . ' - ' . $league->title . ' ' . $season . ' - Important Message';

		foreach ( $teams as $team ) {
			$team_dtls = $league->get_team_dtls( $team->id );
			$email_to  = $team_dtls->contactemail;
			if ( $email_to ) {
				wp_mail( $email_to, $email_subject, $email_message, $headers );
				$message_sent = true;
			}
		}

		if ( $message_sent ) {
			$this->set_message( __( 'Email sent to captains', 'racketmanager' ) );
		}
		return true;
	}
	/**
	 * Contact Competition Teams
	 *
	 * @param int    $competition competition.
	 * @param string $season season.
	 * @param string $email_message message.
	 * @return boolean
	 */
	private function contact_competition_teams( $competition, $season, $email_message ) {
		$competition = get_competition( $competition );
		if ( $competition ) {
			$email_message = str_replace( '\"', '"', $email_message );
			$headers       = array();
			$email_from    = $this->get_confirmation_email( $competition->type );
			$headers[]     = 'From: ' . ucfirst( $competition->type ) . ' Secretary <' . $email_from . '>';
			$headers[]     = 'cc: ' . ucfirst( $competition->type ) . ' Secretary <' . $email_from . '>';
			$email_subject = $this->site_name . ' - ' . $competition->name . ' ' . $season . ' - Important Message';
			$email_to      = array();
			if ( $competition->is_player_entry ) {
				if ( $competition->is_tournament ) {
					$tournament_key = $competition->id . ',' . $competition->current_season['name'];
					$tournament     = get_tournament( $tournament_key, 'shortcode' );
					if ( $tournament ) {
						$players = $tournament->get_players();
						foreach ( $players as $player_name ) {
							$player = get_player( $player_name, 'name' );
							if ( $player && ! empty( $player->email ) ) {
								$headers[] = 'bcc: ' . $player->display_name . ' <' . $player->email . '>';
							}
						}
					}
				}
			} else {
				$teams  = array();
				$events = $competition->get_events();
				foreach ( $events as $event ) {
					$event = get_event( $event );
					if ( $event ) {
						$event_teams = $event->get_teams( array( 'season' => $event->current_season['name'] ) );
						if ( $event_teams ) {
							$teams = array_merge( $teams, $event_teams );
						}
					}
				}
				foreach ( $teams as $team ) {
					$league = get_league( $team->league_id );
					if ( $league ) {
						$team_dtls = $league->get_team_dtls( $team->team_id );
						if ( $team_dtls ) {
							$headers[] = 'bcc: ' . $team_dtls->captain . ' <' . $team_dtls->contactemail . '>';
						}
					}
				}
			}
			wp_mail( $email_to, $email_subject, $email_message, $headers );
			$this->set_message( __( 'Message sent', 'racketmanager' ) );
		}
		return true;
	}
	/**
	 * Get latest season
	 *
	 * @return int
	 */
	public function getLatestSeason() {
		global $wpdb;

		return $wpdb->get_var( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT MAX(name) FROM {$wpdb->racketmanager_seasons}"
		);
	}

	/**
	 * Schedule league matches
	 *
	 * @param array $events array of events to schedule matches for.
	 * @return void
	 */
	private function scheduleLeagueMatches( $events ) {
		$validation = $this->validate_schedule( $events );
		if ( $validation->success ) {
			$max_teams    = $validation->num_rounds + 1;
			$default_refs = array();
			for ( $i = 1; $i <= $max_teams; $i++ ) {
				$default_refs[] = $i;
			}

			$i = 1;
			do {
				$result = $this->setup_teams_in_schedule( $events, $max_teams, $default_refs );
				++$i;
			} while ( ! $result && $i < 20 );

			if ( $result ) {
				foreach ( $events as $event_id ) {
					$event = get_event( $event_id );
					foreach ( $event->get_leagues() as $league ) {
						$league = get_league( $league );
						$league->schedule_matches();
					}
				}
				$this->set_message( __( 'Matches scheduled', 'racketmanager' ) );
			}
		}
	}

	/**
	 * Validate schedule by team
	 *
	 * @param array $events array of events to validate schedule.
	 * @return object $validation
	 */
	private function validate_schedule( $events ) {
		global $wpdb;

		$validation          = new \stdClass();
		$validation->success = true;
		$messages            = array();
		$c                   = 0;
		$num_match_days      = 0;
		$home_away           = '';
		foreach ( $events as $event_id ) {
			$event       = get_event( $event_id );
			$season      = $event->get_season();
			$match_count = $this->get_matches(
				array(
					'count'    => true,
					'event_id' => $event->id,
					'season'   => $season,
				)
			);
			if ( 0 !== $match_count ) {
				$validation->success = false;
				/* translators: %1$s: event name %2$d season */
				$messages[] = sprintf( __( '%1$s already has matches scheduled for %2$d', 'racketmanager' ), $event->name, $season );
				break;
			} elseif ( 0 === $c ) {
				$num_match_days = $event->current_season['num_match_days'];
				if ( ! isset( $event->current_season['matchDates'] ) ) {
					$validation->success = false;
					/* translators: %s: event name */
					$messages[] = sprintf( __( 'Events match dates not set for %s', 'racketmanager' ), $event->name );
				}
				$home_away = isset( $event->current_season['homeAway'] ) ? $event->current_season['homeAway'] : 'true';
				if ( $home_away ) {
					$validation->num_rounds = $num_match_days / 2;
				} else {
					$validation->num_rounds = $num_match_days;
				}
			} else {
				if ( $event->current_season['num_match_days'] !== $num_match_days ) {
					$validation->success = false;
					$messages[]          = __( 'Events have different number of match days', 'racketmanager' );
				}
				if ( ! isset( $event->current_season['matchDates'] ) ) {
					$validation->success = false;
					/* translators: %s: event name */
					$messages[] = sprintf( __( 'Events match dates not set for %s', 'racketmanager' ), $event->name );
				}
				$home_away_new = isset( $event->current_season['homeAway'] ) ? $event->current_season['homeAway'] : 'true';
				if ( $home_away_new !== $home_away ) {
					$validation->success = false;
					$messages[]          = __( 'Events have different home / away setting', 'racketmanager' );
				}
			}
			++$c;
		}

		if ( $validation->success ) {
			$season                = $this->getLatestSeason();
			$event_ids             = implode( ',', $events );
			$teams_missing_details = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					"SELECT `t`.`title`FROM {$wpdb->racketmanager_teams} t , {$wpdb->racketmanager_team_events} tc , {$wpdb->racketmanager_table} t1 , {$wpdb->racketmanager} l WHERE t.`id` = `tc`.`team_id` AND `tc`.`match_day` = '' AND `tc`.`event_id` in (" . $event_ids . ') AND l.`id` = `t1`.`league_id` AND `l`.`event_id` = tc.`event_id` AND `t1`.`season` = %s AND `t1`.`team_id` = `tc`.`team_id`',
					$season
				)
			);
			if ( $teams_missing_details ) {
				$missing_teams = array();
				foreach ( $teams_missing_details as $team ) {
					$missing_teams[] = $team->title;
				}
				$teams               = implode( ' and ', $missing_teams );
				$validation->success = false;
				/* translators: %s: teams with missing match days */
				$messages[] = sprintf( __( 'Missing match days for %s', 'racketmanager' ), $teams );
			}
		}
		$message = implode( '<br>', $messages );
		$this->set_message( $message, true );
		return $validation;
	}

	/**
	 * Setup teams in schedule where necessary
	 *
	 * @param array $events array of events to schedule.
	 * @param int   $max_teams maximum numbers of teams in division.
	 * @param array $default_refs default keys to use for scheduling.
	 * @return boolean $validation->success
	 */
	private function setup_teams_in_schedule( $events, $max_teams, $default_refs ) {
		global $wpdb;
		$validation           = new \stdClass();
		$validation->success  = true;
		$validation->messages = array();
		$season               = $this->getLatestSeason();
		$event_ids            = implode( ',', $events );
		/* clear out schedule keys for this run */
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"UPDATE {$wpdb->racketmanager_table} SET `group` = '' WHERE `season` = %s AND `league_id` IN (SELECT `id` FROM {$wpdb->racketmanager} WHERE `event_id` IN ($event_ids))",
				$season
			)
		);
		$validation = $this->handle_teams_in_same_division( $events, $season, $validation, $default_refs );
		if ( $validation->success ) {
			$validation = $this->handle_teams_with_same_match_time( $events, $season, $max_teams, $validation, $default_refs );
		}
		$message = implode( '<br>', $validation->messages );
		$this->set_message( $message, true );
		return $validation->success;
	}

	/**
	 * Setup teams from the same club in a division.
	 * These teams will play each other in the first round
	 * Options are:
	 *  1 - 6
	 *  2 - 5
	 *  3 - 4
	 *
	 * @param array  $events array of events to schedule.
	 * @param string $season season.
	 * @param object $validation details of validation.
	 * @param array  $default_refs default keys to use for scheduling.
	 * @return object $validation
	 */
	private function handle_teams_in_same_division( $events, $season, $validation, $default_refs ) {
		global $wpdb;
		$event_ids = implode( ',', $events );
		/* set refs for those teams in the same division so they play first */
		$club_leagues = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				"SELECT `t`.`affiliatedclub`, tbl.`league_id` FROM {$wpdb->racketmanager_team_events} tc, {$wpdb->racketmanager_teams} t, {$wpdb->racketmanager} l, {$wpdb->racketmanager_table} tbl WHERE tc.`team_id` = t.`id` AND tc.`event_id` = l.`event_id` AND l.`id` = tbl.`league_id` AND tbl.`team_id` = t.`id` AND tc.`event_id` in (" . $event_ids . ') AND tbl.`season` = %s GROUP BY t.`affiliatedclub`, tbl.`league_id` HAVING COUNT(*) > 1',
				$season
			)
		);
		foreach ( $club_leagues as $club_league ) {
			$teams = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					"SELECT tbl.`id`, tbl.`team_id`, tbl.`league_id` FROM {$wpdb->racketmanager_team_events} tc, {$wpdb->racketmanager_teams} t, {$wpdb->racketmanager} l, {$wpdb->racketmanager_table} tbl WHERE tc.`team_id` = t.`id` AND tc.`event_id` = l.`event_id` AND l.`id` = tbl.`league_id` AND tbl.`team_id` = t.`id` AND tc.`event_id` in (" . $event_ids . ') AND tbl.`season` = %s AND t.`affiliatedclub` = %d AND tbl.`league_id` = %d ORDER BY tbl.`team_id`',
					$season,
					$club_league->affiliatedclub,
					$club_league->league_id
				)
			);
			$counter  = 1;
			$alt_refs = array();
			$refs     = array();
			$table1   = '';
			$league1  = '';
			$team1    = '';
			foreach ( $teams as $team ) {
				if ( $counter & 1 ) {
					$team1    = $team->team_id;
					$table1   = $team->id;
					$league1  = $team->league_id;
					$refs     = $default_refs;
					$alt_refs = $refs;
					$groups   = $this->getTableGroups( $league1, $season );
					if ( $groups ) {
						foreach ( $groups as $group ) {
							$ref = array_search( intval( $group->value ), $refs, true );
							array_splice( $refs, $ref, 1 );
						}
					}
				} else {
					$team2   = $team->team_id;
					$table2  = $team->id;
					$league2 = $team->league_id;
					$groups  = $this->getTableGroups( $league2, $season );
					if ( $groups ) {
						foreach ( $groups as $group ) {
							$ref = array_search( intval( $group->value ), $alt_refs, true );
							array_splice( $alt_refs, $ref, 1 );
						}
					}
					if ( $refs ) {
						$i              = 0;
						$ref_free       = false;
						$alt_found      = false;
						$ref_option     = array( 2, 3, 1 );
						$alt_ref_option = array( 5, 4, 6 );
						for ( $i = 0; $i < 3; $i++ ) {
							$ref_free = array_search( intval( $ref_option[ $i ] ), $refs, true );
							$ref      = $ref_option[ $i ];
							if ( $ref_free ) {
								$alt_ref   = $alt_ref_option[ $i ];
								$alt_found = array_search( $alt_ref, $alt_refs, true );
								if ( false !== $alt_found ) {
									break;
								}
							}
						}
						if ( false !== $alt_found ) {
							$this->setTableGroup( $ref, $table1 );
							$this->setTableGroup( $alt_ref, $table2 );
						} else {
							$validation->success = false;
							/* translators: %1$d: league %2$d team 1 %2$d team 2 */
							$validation->messages[] = sprintf( __( 'Unable to schedule first round for league %1$d for team %2$d and team %3$d', 'racketmanager' ), $league1, $team1, $team2 );
						}
					} else {
						$validation->success = false;
						/* translators: %1$d: league %2$d team 1 %2$d team 2 */
						$validation->messages[] = sprintf( __( 'Error in scheduling first round for league %1$d for team %2$d and team %3$d', 'racketmanager' ), $league1, $team1, $team2 );
					}
				}
				++$counter;
			}
		}
		return $validation;
	}

	/**
	 * Setup teams from same club with same match time.
	 * These teams will always play alternate home matches.
	 * Options are:
	 *  1 - 4
	 *  2 - 5
	 *  3 - 6
	 *
	 * @param array  $events array of events to schedule.
	 * @param string $season season.
	 * @param int    $max_teams maximum number of teams in division.
	 * @param object $validation details of validation.
	 * @param array  $default_refs default keys to use for scheduling.
	 * @return object $validation
	 */
	private function handle_teams_with_same_match_time( $events, $season, $max_teams, $validation, $default_refs ) {
		global $wpdb;
		$event_ids = implode( ',', $events );
		/* find all clubs with multiple matches at the same time */
		$event_teams = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				"SELECT `t`.`affiliatedclub`, `tc`.`match_day`, `tc`.`match_time`, count(*) FROM {$wpdb->racketmanager_team_events} tc, {$wpdb->racketmanager_teams} t, {$wpdb->racketmanager} l, {$wpdb->racketmanager_table} tbl WHERE tc.`team_id` = t.`id` AND tc.`event_id` = l.`event_id` AND l.`id` = tbl.`league_id` AND tbl.`team_id` = t.`id` AND tc.`event_id` in (" . $event_ids . ') AND tbl.`season` = %s AND tbl.`profile` != 3 GROUP BY t.`affiliatedclub`, tc.`match_day`, tc.`match_time` HAVING COUNT(*) > 1 ORDER BY count(*) DESC, RAND()',
				$season
			)
		);
		/* for each club / match time combination balance schedule so one team is home while the other is away */
		foreach ( $event_teams as $event_team ) {
			$teams = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					"SELECT tbl.`id`, tbl.`team_id`, tbl.`league_id`, tbl.`group` FROM {$wpdb->racketmanager_team_events} tc, {$wpdb->racketmanager_teams} t, {$wpdb->racketmanager} l, {$wpdb->racketmanager_table} tbl WHERE tc.`team_id` = t.`id` AND tc.`event_id` = l.`event_id` AND l.`id` = tbl.`league_id` AND tbl.`team_id` = t.`id` AND tc.`event_id` in (" . $event_ids . ') AND tbl.`season` = %s AND t.`affiliatedclub` = %d AND tc.`match_day` = %s AND tc.`match_time` = %s AND tbl.`profile` != 3 ORDER BY tbl.`group`, tbl.`team_id`',
					$season,
					$event_team->affiliatedclub,
					$event_team->match_day,
					$event_team->match_time
				)
			);
			$counter  = 1;
			$refs     = array();
			$alt_refs = array();
			$table1   = 0;
			$team1    = 0;
			$league1  = 0;
			foreach ( $teams as $team ) {

				/* for first of pair */
				if ( $counter & 1 ) {
					$team1    = $team->team_id;
					$table1   = $team->id;
					$league1  = $team->league_id;
					$group1   = $team->group;
					$refs     = $default_refs;
					$alt_refs = $refs;
					$groups   = $this->getTableGroups( $league1, $season );
					if ( $groups ) {
						foreach ( $groups as $group ) {
							$ref = array_search( intval( $group->value ), $refs, true );
							array_splice( $refs, $ref, 1 );
						}
					}
				} else {
					/* for second of pair */
					$table2  = $team->id;
					$league2 = $team->league_id;
					$group2  = $team->group;
					$groups  = $this->getTableGroups( $league2, $season );
					if ( $groups ) {
						foreach ( $groups as $group ) {
							$ref = array_search( intval( $group->value ), $alt_refs, true );
							array_splice( $alt_refs, $ref, 1 );
						}
					}
					if ( $refs ) {
						if ( ! empty( $group1 ) ) {
							$ref = $group1;
							if ( ! empty( $group2 ) ) {
								$alt_ref   = $group2;
								$alt_found = true;
							} else {
								$alt_ref = $ref + $max_teams / 2;
								if ( $alt_ref > $max_teams ) {
									$alt_ref = $alt_ref - $max_teams;
								}
								$alt_found = array_search( intval( $ref ), $refs, true );
							}
							if ( false !== $alt_found ) {
								$this->setTableGroup( $ref, $table1 );
								$this->setTableGroup( $alt_ref, $table2 );
							} else {
								$validation->success = false;
								$league              = get_league( $league1 );
								$team                = get_team( $team1 );
								/* translators: %1$s: team name %2$s league name */
								$validation->messages[] = sprintf( __( '1 - Error in scheduling %1$s in %2$s', 'racketmanager' ), $team->title, $league->title );
							}
						} else {
							$ref_set = false;
							if ( ! empty( $group2 ) ) {
								$alt_ref = $group2;
								$ref     = $alt_ref - $max_teams / 2;
								if ( $ref < 1 ) {
									$ref = $ref + $max_teams;
								}
								$alt_found = array_search( intval( $ref ), $refs, true );
								if ( false !== $alt_found ) {
									$ref_set = true;
									$this->setTableGroup( $ref, $table1 );
									$this->setTableGroup( $alt_ref, $table2 );
								} else {
									$validation->success = false;
									$league              = get_league( $league1 );
									$team                = get_team( $team1 );
									/* translators: %1$s: team name %2$s league name */
									$validation->messages[] = sprintf( __( '4 - Error in scheduling %1$s in %2$s', 'racketmanager' ), $team->title, $league->title );
								}
							} else {
								$count_refs = count( $refs );
								for ( $i = 0; $i < $count_refs; $i++ ) {
									$ref     = $refs[ $i ];
									$alt_ref = $ref + $max_teams / 2;
									if ( $alt_ref > $max_teams ) {
										$alt_ref = $alt_ref - $max_teams;
									}
									$alt_found = array_search( intval( $alt_ref ), $alt_refs, true );
									if ( false !== $alt_found ) {
										$ref_set = true;
										$this->setTableGroup( $ref, $table1 );
										$this->setTableGroup( $alt_ref, $table2 );
										break;
									}
								}
								if ( ! $ref_set ) {
									$validation->success = false;
									$league              = get_league( $league1 );
									$team                = get_team( $team1 );
									/* translators: %1$s: team name %2$s league name */
									$validation->messages[] = sprintf( __( '2 - Error in scheduling %1$s in %2$s', 'racketmanager' ), $team->title, $league->title );
								}
							}
						}
					} else {
						$validation->success = false;
						$league              = get_league( $league1 );
						$team                = get_team( $team1 );
						/* translators: %1$s: team name %2$s league name */
						$validation->messages[] = sprintf( __( '3 - Error in scheduling %1$s in %2$s', 'racketmanager' ), $team->title, $league->title );
					}
				}
				++$counter;
			}
		}
		return $validation;
	}

	/**
	 * Set table group
	 *
	 * @param string  $group group.
	 * @param integer $id id.
	 */
	public function setTableGroup( $group, $id ) {
		global $wpdb;

		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"UPDATE {$wpdb->racketmanager_table} SET `group` = %s WHERE `id` = %d",
				$group,
				$id
			)
		);
	}

	/**
	 * Set get table groups
	 *
	 * @param integer $league league.
	 * @param integer $season season.
	 * @return array $groups table groups.
	 */
	private function getTableGroups( $league, $season ) {
		global $wpdb;

		return $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			//phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT `group` as `value` FROM {$wpdb->racketmanager_table} WHERE `league_id` = $league AND `season` = $season AND `group` != ''"
		);
	}

	/**
	 * Get Charges
	 *
	 * @return array $charges
	 */
	public function getCharges() {
		global $wpdb;
		$charges = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT `id` FROM {$wpdb->racketmanager_charges} order by `season`, `competition_id`"
		);
		$i       = 0;
		foreach ( $charges as $charge ) {
			$charge        = get_charges( $charge->id );
			$charges[ $i ] = $charge;
			++$i;
		}
		return $charges;
	}

	/**
	 * Get Invoices
	 *
	 * @param array $args query arguments.
	 */
	private function getInvoices( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'club'   => false,
			'status' => false,
		);
		$args     = array_merge( $defaults, $args );
		$club     = $args['club'];
		$status   = $args['status'];

		$search_terms = array();
		if ( $club && 'all' !== $club ) {
			$search_terms[] = $wpdb->prepare( '`club_id` = %d', $club );
		}
		if ( $status ) {
			if ( 'paid' === $status ) {
				$search_terms[] = $wpdb->prepare( '`status` = %s', $status );
			} elseif ( 'open' === $status ) {
				$search_terms[] = "`status` != ('paid')";
			} elseif ( 'overdue' === $status ) {
				$search_terms[] = "(`status` != ('paid') AND `date_due` < CURDATE())";
			}
		}

		$search = '';
		if ( ! empty( $search_terms ) ) {
			$search  = ' AND ';
			$search .= implode( ' AND ', $search_terms );
		}

		$invoices = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT `id`, `status`, `charge_id`, `club_id`, `invoiceNumber` as `invoice_number`, `date`, `date_due` FROM {$wpdb->racketmanager_invoices} WHERE 1 = 1 $search order by `invoiceNumber`"
		);

		$i = 0;
		foreach ( $invoices as $i => $invoice ) {
			$invoice        = get_invoice( $invoice );
			$invoices[ $i ] = $invoice;
		}
		return $invoices;
	}

	/**
	 * Get Invoice
	 *
	 * @param int $charge charge used by invoice.
	 * @param int $club club for who invocie is created.
	 * @return int $invoice_id
	 */
	private function getInvoice( $charge, $club ) {
		global $wpdb;

		return $wpdb->get_var( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT `id` FROM {$wpdb->racketmanager_invoices} WHERE `charge_id` = %d AND `club_id` = %d LIMIT 1",
				$charge,
				$club
			)
		);
	}

	/**
	 * Print formatted message
	 */
	public function printMessage() {
		if ( ! empty( $this->message ) ) {
			// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
			if ( $this->error ) {
				echo "<div class='error'><p>" . $this->message . '</p></div>';
			} else {
				echo "<div id='message' class='updated fade show'><p><strong>" . $this->message . '</strong></p></div>';
			}
			// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		$this->message = '';
	}

	/**
	 * Schedule result chase
	 *
	 * @param string $competition_type type of competitition.
	 * @param array  $options array of options to use for chasing result.
	 */
	private function scheduleResultChase( $competition_type, $options ) {
		$day            = intval( gmdate( 'd' ) );
		$month          = intval( gmdate( 'm' ) );
		$year           = intval( gmdate( 'Y' ) );
		$schedule_start = mktime( 19, 0, 0, $month, $day, $year );
		$interval       = 'daily';
		$schedule_args  = array( $competition_type );
		if ( '' !== $options['resultPending'] ) {
			$schedule_name = 'rm_resultPending';
			if ( wp_next_scheduled( $schedule_name, $schedule_args ) ) {
				wp_clear_scheduled_hook( $schedule_name, $schedule_args );
			}
			if ( ! wp_next_scheduled( $schedule_name, $schedule_args ) && ! wp_schedule_event( $schedule_start, $interval, $schedule_name, $schedule_args ) ) {
				error_log( __( 'Error scheduling pending results', 'racketmanager' ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
		}
		if ( '' !== $options['confirmationPending'] ) {
			$schedule_name = 'rm_confirmationPending';
			if ( wp_next_scheduled( $schedule_name, $schedule_args ) ) {
				wp_clear_scheduled_hook( $schedule_name, $schedule_args );
			}
			if ( ! wp_next_scheduled( $schedule_name, $schedule_args ) && ! wp_schedule_event( $schedule_start, $interval, $schedule_name, $schedule_args ) ) {
				error_log( __( 'Error scheduling result confirmations', 'racketmanager' ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
		}
	}
}
?>
