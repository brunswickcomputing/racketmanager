<?php
/**
 * RacketManager-Admin API: RacketManager-admin class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManager-Admin
 */

namespace Racketmanager;

use stdClass;

/**
 * RacketManager administration functions
 * Class to implement RacketManager Administration panel
 *
 * @author Kolja Schleich
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManagerAdmin
 */
class RacketManager_Admin extends RacketManager {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      RacketManager|null
	 */
	protected static ?RacketManager $instance = null;
	/**
	 * Error messages.
	 *
	 * @var array|null $error_messages
	 */
    public ?array $error_messages;
	/**
	 * Error fields.
	 *
	 * @var array|null $error_messages
	 */
    public ?array $error_fields;
	/**
	 * Constructor
	 */
	public function __construct() {
		self::$instance = $this;

		parent::__construct();

		require_once ABSPATH . 'wp-admin/includes/template.php';

		require_once RACKETMANAGER_PATH . 'include/class-racketmanager-ajax-admin.php';
		new Racketmanager_Ajax_Admin();
		require_once RACKETMANAGER_PATH . 'include/class-racketmanager-admin-finances.php';
		require_once RACKETMANAGER_PATH . 'include/class-racketmanager-admin-competition.php';
		require_once RACKETMANAGER_PATH . 'include/class-racketmanager-admin-event.php';
		require_once RACKETMANAGER_PATH . 'include/class-racketmanager-admin-tournament.php';
		require_once RACKETMANAGER_PATH . 'include/class-racketmanager-admin-cup.php';
		require_once RACKETMANAGER_PATH . 'include/class-racketmanager-admin-league.php';
		require_once RACKETMANAGER_PATH . 'include/class-racketmanager-admin-players.php';

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
	}

	/**
	 * Return an instance of this class.
	 *
	 * @return object|null A single instance of this class.
	 * @since     1.0.0
	 *
	 */
	public static function get_instance(): object|null {
		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
	/**
	 * Adds menu to the admin interface
	 */
	public function menu(): void {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" viewBox="0 0 412.425 412.425" style="fill:white" xml:space="preserve"><path d="M412.425,108.933c0-30.529-10.941-58.18-30.808-77.86C361.776,11.418,333.91,0.593,303.153,0.593 c-41.3,0-83.913,18.749-116.913,51.438c-30.319,30.034-48.754,68.115-51.573,105.858c-0.845,5.398-1.634,11.13-2.462,17.188	c-4.744,34.686-10.603,77.415-34.049,104.503c-2.06,0.333-3.981,1.295-5.476,2.789L7.603,367.447 c-10.137,10.138-10.137,26.632,0,36.77c4.911,4.911,11.44,7.615,18.385,7.615s13.474-2.705,18.386-7.617l85.06-85.095 c1.535-1.536,2.457-3.448,2.784-5.438c27.087-23.461,69.829-29.322,104.524-34.068c6.549-0.896,12.734-1.741,18.508-2.666 c1.434-0.23,2.743-0.76,3.885-1.507c36.253-4.047,72.464-21.972,101.325-50.562C393.485,192.166,412.425,149.905,412.425,108.933z M145.476,218.349c4.984,10.244,11.564,19.521,19.608,27.49c8.514,8.434,18.51,15.237,29.576,20.262 c-25.846,5.238-52.769,13.823-73.415,30.692l-6.216-6.216C131.639,270.246,140.217,243.831,145.476,218.349z M30.23,390.075	c-1.133,1.133-2.64,1.757-4.242,1.757c-1.603,0-3.109-0.624-4.243-1.757c-2.339-2.339-2.339-6.146,0-8.485l78.006-78.007 l8.469,8.469L30.23,390.075z M243.559,256.318c-0.002,0-0.008,0-0.011,0c-25.822-0.003-48.087-8.54-64.389-24.688 c-16.279-16.126-24.883-38.136-24.883-63.652c0-2.596,0.1-5.201,0.276-7.808c0.023-0.143,0.045-0.295,0.068-0.438 c0.11-0.685,0.147-1.364,0.117-2.031c2.87-32.422,19.121-65.253,45.579-91.461c29.284-29.009,66.767-45.646,102.837-45.646 c25.819,0,48.085,8.537,64.389,24.689c16.279,16.126,24.883,38.136,24.883,63.651c-0.001,35.672-16.781,72.755-46.04,101.739 C317.1,239.682,279.624,256.319,243.559,256.318z"/></svg>';
		// keep capabilities here for next update.
		$page = add_menu_page(
			__( 'RacketManager', 'racketmanager' ),
			__( 'RacketManager', 'racketmanager' ),
			'racket_manager',
			'racketmanager',
			array( &$this, 'display' ),
			'data:image/svg+xml;base64,' . base64_encode( $svg ),
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
	 * @param object $user The WP user object.
	 *
	 * @return void
	 */
	public function custom_user_profile_fields( object $user ): void {
		?>
        <div class="racketmanager-fields mt-3">
            <h2><?php esc_html_e( 'Racketmanager Details', 'racketmanager' ); ?></h2>
            <table class="form-table" aria-label="<?php esc_html_e( 'racketmanager_fields', 'racketmanager' ); ?>">
                <tr>
                    <th>
                        <div><?php esc_html_e( 'Gender', 'racketmanager' ); ?></div>
                    </th>
                    <td>
                        <input type="radio" required name="gender" id="genderM" value="M" <?php echo ( get_the_author_meta( 'gender', $user->ID ) === 'M' ) ? 'checked' : ''; ?>><label for="genderM"><?php esc_html_e( 'Male', 'racketmanager' ); ?></label>
                        <br>
                        <input type="radio" name="gender" id="genderF" value="F" <?php echo ( get_the_author_meta( 'gender', $user->ID ) === 'F' ) ? 'checked' : ''; ?>><label for="genderF"><?php esc_html_e( 'Female', 'racketmanager' ); ?></label>
                    </td>
                </tr>
                <tr>
                    <th>
                        <label for="contactno"><?php esc_html_e( 'Contact Number', 'racketmanager' ); ?></label>
                    </th>
                    <td>
                        <input type="tel" name="contactno" id="contactno" value="<?php echo esc_attr( get_the_author_meta( 'contactno', $user->ID ) ); ?>">
                    </td>
                </tr>
                <tr>
                    <th>
                        <label for="btm"><?php esc_html_e( 'LTA Tennis Number', 'racketmanager' ); ?></label>
                    </th>
                    <td>
                        <input type="number" id="btm" name="btm" value="<?php echo esc_attr( get_the_author_meta( 'btm', $user->ID ) ); ?>">
                    </td>
                </tr>
                <tr>
                    <th>
                        <label for="year_of_birth"><?php esc_html_e( 'Year of birth', 'racketmanager' ); ?></label>
                    </th>
                    <td>
                        <input type="number" name="year_of_birth" id="year_of_birth" value="<?php echo esc_attr( get_the_author_meta( 'year_of_birth', $user->ID ) ); ?>">
                    </td>
                </tr>
                <tr>
                    <th>
                        <label for="remove_date"><?php esc_html_e( 'Date Removed', 'racketmanager' ); ?></label>
                    </th>
                    <td>
                        <input type="date" name="remove_date" id="remove_date" value="<?php echo esc_attr( get_the_author_meta( 'remove_date', $user->ID ) ); ?>">
                    </td>
                </tr>
                <tr>
                    <th>
                        <label for="locked_date"><?php esc_html_e( 'Date Locked', 'racketmanager' ); ?></label>
                    </th>
                    <td>
                        <input type="date" name="locked_date" id="locked_date" value="<?php echo esc_attr( get_the_author_meta( 'locked_date', $user->ID ) ); ?>">
                    </td>
                </tr>
            </table>
        </div>
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
	public function update_extra_profile_fields( int $user_id ): void {
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
	public function metaboxes(): void {
		add_meta_box( 'racketmanager', __( 'Match-Report', 'racketmanager' ), array( &$this, 'add_meta_box' ), 'post' );
	}

	/**
	 * Build league menu
	 *
	 * @return array
	 */
	protected function get_menu(): array {
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
			'callback' => array( &$this, 'display_contact_page' ),
			'cap'      => 'edit_teams',
			'show'     => true,
		);
		$menu            = apply_filters( 'racketmanager_league_menu_' . $sport, $menu, $league->id, $season );
		return apply_filters( 'racketmanager_league_menu_' . $league->mode, $menu, $league->id, $season );
	}

	/**
	 * ShowMenu() - show admin menu
	 */
	public function display(): void {
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
			case 'racketmanager-documentation':
			case 'racketmanager-doc':
				include_once RACKETMANAGER_PATH . '/admin/documentation.php';
				break;
			case 'racketmanager-leagues':
				$racketmanager_admin_league = new RacketManager_Admin_League();
				if ( 'seasons' === $view ) {
					$racketmanager_admin_league->display_seasons_page();
				} elseif ( 'overview' === $view ) {
					$racketmanager_admin_league->display_overview_page();
				} elseif ( 'setup' === $view ) {
					$racketmanager_admin_league->display_setup_page();
				} elseif ( 'setup-event' === $view ) {
					$racketmanager_admin_league->display_setup_event_page();
				} elseif ( 'modify' === $view ) {
					$racketmanager_admin_competition = new RacketManager_Admin_Competition();
					$racketmanager_admin_competition->display_season_modify_page();
				} elseif ( 'event' === $view ) {
					$racketmanager_admin_league->display_event_page();
				} elseif ( 'constitution' === $view ) {
					$racketmanager_admin_league->display_constitution_page();
				} elseif ( 'league' === $view ) {
					$racketmanager_admin_league->display_league_page();
				} elseif ( 'match' === $view ) {
					$racketmanager_admin_league->display_match_page();
				} elseif ( 'plan' === $view ) {
					$racketmanager_admin_league->display_schedule_page();
				} elseif ( 'teams' === $view ) {
					$racketmanager_admin_league->display_teams_list();
				} elseif ( 'team' === $view ) {
					$racketmanager_admin_league->display_team_page();
				} elseif ( 'contact' === $view ) {
					$racketmanager_admin_league->display_contact_page();
				} elseif ( 'event-config' === $view ) {
					$racketmanager_admin_event = new RacketManager_Admin_Event();
					$racketmanager_admin_event->display_config_page();
				} elseif ( 'config' === $view ) {
					$racketmanager_admin_competition = new RacketManager_Admin_Competition();
					$racketmanager_admin_competition->display_config_page();
				} else {
					$racketmanager_admin_league->display_leagues_page();
				}
				break;
			case 'racketmanager-cups':
				$racketmanager_admin_cup = new RacketManager_Admin_Cup();
				if ( 'seasons' === $view ) {
					$racketmanager_admin_cup->display_cup_seasons_page();
				} elseif ( 'modify' === $view ) {
					$racketmanager_admin_competition = new RacketManager_Admin_Competition();
					$racketmanager_admin_competition->display_season_modify_page();
				} elseif ( 'overview' === $view ) {
					$racketmanager_admin_cup->display_cup_overview_page();
				} elseif ( 'setup' === $view ) {
					$racketmanager_admin_cup->display_cup_setup_page();
				} elseif ( 'setup-event' === $view ) {
					$racketmanager_admin_cup->display_setup_event_page();
				} elseif ( 'draw' === $view ) {
					$racketmanager_admin_cup->display_cup_draw_page();
				} elseif ( 'matches' === $view ) {
					$racketmanager_admin_cup->display_cup_matches_page();
				} elseif ( 'match' === $view ) {
					$racketmanager_admin_cup->display_cup_match_page();
				} elseif ( 'plan' === $view ) {
					$racketmanager_admin_cup->display_cup_plan_page();
				} elseif ( 'teams' === $view ) {
					$racketmanager_admin_cup->display_teams_list();
				} elseif ( 'team' === $view ) {
					$racketmanager_admin_cup->display_team_page();
				} elseif ( 'config' === $view ) {
					$racketmanager_admin_competition = new RacketManager_Admin_Competition();
					$racketmanager_admin_competition->display_config_page();
				} elseif ( 'event' === $view || 'event-config' === $view ) {
					$racketmanager_admin_event = new RacketManager_Admin_Event();
					$racketmanager_admin_event->display_config_page();
				} else {
					$racketmanager_admin_cup->display_cups_page();
				}
				break;
			case 'racketmanager-tournaments':
				$racketmanager_admin_tournament = new RacketManager_Admin_Tournament();
				if ( 'modify' === $view ) {
					$racketmanager_admin_tournament->displayTournamentPage();
				} elseif ( 'plan' === $view ) {
					$racketmanager_admin_tournament->displayTournamentPlanPage();
				} elseif ( 'tournament' === $view ) {
					$racketmanager_admin_tournament->display_tournament_overview_page();
				} elseif ( 'draw' === $view ) {
					$racketmanager_admin_tournament->display_tournament_draw_page();
				} elseif ( 'setup' === $view ) {
					$racketmanager_admin_tournament->display_tournament_setup_page();
				} elseif ( 'setup-event' === $view ) {
					$racketmanager_admin_tournament->display_setup_event_page();
				} elseif ( 'matches' === $view ) {
					$racketmanager_admin_tournament->display_tournament_matches_page();
				} elseif ( 'match' === $view ) {
					$racketmanager_admin_tournament->display_tournament_match_page();
				} elseif ( 'teams' === $view ) {
					$racketmanager_admin_tournament->display_tournament_teams_page();
				} elseif ( 'config' === $view ) {
					$racketmanager_admin_competition = new RacketManager_Admin_Competition();
					$racketmanager_admin_competition->display_config_page();
				} elseif ( 'event' === $view ) {
					$racketmanager_admin_event = new RacketManager_Admin_Event();
					$racketmanager_admin_event->display_config_page();
				} elseif ( 'team' === $view ) {
					$racketmanager_admin_tournament->display_team_page();
				} elseif ( 'contact' === $view ) {
					$racketmanager_admin_tournament->display_contact_page();
				} else {
					$racketmanager_admin_tournament->display_tournaments_page();
				}
				break;
			case 'racketmanager-clubs':
				if ( 'teams' === $view ) {
					$this->display_teams_page();
				} elseif ( 'players' === $view ) {
					$this->display_club_players_page();
				} elseif ( 'player' === $view ) {
					$racketmanager_admin_players = new RacketManager_Admin_Players();
					$racketmanager_admin_players->display_player_page();
				} else {
					$this->display_clubs_page();
				}
				break;
			case 'racketmanager-results':
				$this->display_results_page();
				break;
			case 'racketmanager-admin':
				$this->display_admin_page();
				break;
			case 'racketmanager-players':
				$racketmanager_admin_players = new RacketManager_Admin_Players();
				switch ( $view ) {
					case 'player':
						$racketmanager_admin_players->display_player_page();
						break;
					case 'errors':
						$racketmanager_admin_players->display_errors_page();
						break;
					case 'requests':
						$racketmanager_admin_players->display_requests_page();
						break;
					case 'players':
						$racketmanager_admin_players->display_players_page();
						break;
					default:
						$racketmanager_admin_players->display_players_section();
						break;
				}
				break;
			case 'racketmanager-finances':
				$racketmanager_admin_finances = new RacketManager_Admin_Finances();
				if ( 'charges' === $view ) {
					$racketmanager_admin_finances->display_charges_page();
				} elseif ( 'club-invoices' === $view ) {
					$racketmanager_admin_finances->display_club_invoices_page();
				} elseif ( 'player-invoices' === $view ) {
					$racketmanager_admin_finances->display_player_invoices_page();
				} elseif ( 'invoice' === $view ) {
					$racketmanager_admin_finances->display_invoice_page();
				} elseif ( 'charge' === $view ) {
					$racketmanager_admin_finances->display_charge_page();
				} else {
					$racketmanager_admin_finances->display_finances_page();
				}
				break;
			case 'racketmanager-settings':
				$this->display_options_page();
				break;
			case 'racketmanager-import':
				$this->displayImportPage();
				break;
			case 'racketmanager':
			default:
				if ( isset( $_GET['subpage'] ) ) {  //phpcs:ignore WordPress.Security.NonceVerification.Recommended
					switch ( sanitize_text_field( wp_unslash( $_GET['subpage'] ) ) ) {  //phpcs:ignore WordPress.Security.NonceVerification.Recommended
						case 'club':
							$this->display_club_page();
							break;
						case 'team':
							$this->display_team_page();
							break;
						case 'contact':
							$this->display_contact_page();
							break;
						default:
							$league_id = isset( $_GET['league_id'] ) ? intval( $_GET['league_id'] ) : 0;  //phpcs:ignore WordPress.Security.NonceVerification.Recommended
							$league    = get_league( $league_id );
							$menu      = $this->get_menu();
							$page      = sanitize_text_field( wp_unslash( $_GET['subpage'] ) );  //phpcs:ignore WordPress.Security.NonceVerification.Recommended
							if ( array_key_exists( $page, $menu ) ) {
								if ( isset( $menu[ $page ]['callback'] ) && is_callable( $menu[ $page ]['callback'] ) ) {
									call_user_func( $menu[ $page ]['callback'] );
								} else {
									include_once $menu[ $page ]['file'];
								}
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
	private function display_index_page(): void {
		global $racketmanager, $competition, $club;

		if ( ! current_user_can( 'view_leagues' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			$club_id = isset( $_GET['club_id'] ) ? sanitize_text_field( wp_unslash( $_GET['club_id'] ) ) : 0;
			if ( $club_id ) {
				$club = get_club( $club_id );
			}
			$is_invalid = false;
			if ( isset( $_POST['addCompetition'] ) ) {
				if ( current_user_can( 'edit_leagues' ) ) {
					if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_add-competition' ) ) {
						$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
						$this->printMessage();
						return;
					}
					$name      = isset( $_POST['competition_name'] ) ? sanitize_text_field( wp_unslash( $_POST['competition_name'] ) ) : null;
					$type      = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : null;
					$age_group = isset( $_POST['age_group'] ) ? sanitize_text_field( wp_unslash( $_POST['age_group'] ) ) : null;
					if ( empty( $name ) ) {
						$racketmanager->error_fields[]   = 'competition_name';
						$racketmanager->error_messages[] = __( 'Competition name must be set', 'racketmanager' );
					}
					if ( empty( $type ) ) {
						$racketmanager->error_fields[]   = 'type';
						$racketmanager->error_messages[] = __( 'Type must be set', 'racketmanager' );
					}
					if ( empty( $age_group ) ) {
						$racketmanager->error_fields[]   = 'age_group';
						$racketmanager->error_messages[] = __( 'Age group must be set', 'racketmanager' );
					}
					if ( empty( $racketmanager->error_fields ) ) {
						$competition            = new stdClass();
						$competition->name      = $name;
						$competition->type      = $type;
						$competition->age_group = $age_group;
						$competition            = new Racketmanager_Competition( $competition );
					} else {
						$this->set_message( __( 'Error in competition creation', 'racketmanager' ), true );
					}
				} else {
					$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
				}
				$this->printMessage();
			} elseif ( isset( $_POST['doCompDel'] ) && isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) {
				if ( current_user_can( 'del_leagues' ) ) {
					check_admin_referer( 'competitions-bulk' );
					$messages = array();
					if ( isset( $_POST['competition'] ) ) {
						// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
						foreach ( $_POST['competition'] as $competition_id ) {
							$competition = get_competition( $competition_id );
							$competition->delete();
							$this->delete_competition_pages( $competition->name );
							$messages[] = $competition->name . ' ' . __( 'deleted', 'racketmanager' );
						}
						$message = implode( '<br>', $messages );
						$this->set_message( $message );
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
	private function display_results_page(): void {
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
							$result_check = get_result_check( $results_checker_id );
							if ( $result_check ) {
								if ( 'approve' === $_POST['action'] ) {
									$result_check->approve();
								} elseif ( 'handle' === $_POST['action'] ) {
									$result_check->handle();
								} elseif ( 'delete' === $_POST['action'] ) {
									$result_check->delete();
								}
							} else {
								$this->set_message( __( 'Result check not found', 'racketmanager' ), true );
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
			$results_checkers = $this->get_result_warnings(
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
	 * Add league to event via admin
	 */
	protected function add_league_to_event(): void {
		if ( ! current_user_can( 'edit_leagues' ) ) {
			$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
		} elseif ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_add-league' ) ) {
            $this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
		} elseif ( empty( $_POST['league_id'] ) ) {
			if ( isset( $_POST['event_id'] ) ) {
				$event_id = intval( $_POST['event_id'] );
				$event    = get_event( $event_id );
				if ( $event ) {
					$league_title = isset( $_POST['league_title'] ) ? sanitize_text_field( wp_unslash( $_POST['league_title'] ) ) : null;
					$event->add_league( $league_title );
				}
				$this->set_message( __( 'League added', 'racketmanager' ) );
			}
		} else {
			$league = get_league( intval( $_POST['league_id'] ) );
			if ( sanitize_text_field( wp_unslash( $_POST['league_title'] ) ) === $league->title ) {
				$this->set_message( __( 'No updates', 'racketmanager' ), 'warning' );
			} else {
				$league_title = isset( $_POST['league_title'] ) ? sanitize_text_field( wp_unslash( $_POST['league_title'] ) ) : null;
				$sequence     = isset( $_POST['sequence'] ) ? sanitize_text_field( wp_unslash( $_POST['sequence'] ) ) : null;
				$league->update( $league_title, $sequence );
				$this->set_message( __( 'League Updated', 'racketmanager' ) );
			}
		}
	}
	/**
	 * Delete season(s) from competition via admin
	 *
	 * @param object $competition competition object.
	 */
	protected function delete_seasons_from_competition( object $competition ): void {
		global $racketmanager;
		if ( ! current_user_can( 'del_seasons' ) ) {
			$racketmanager->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
		} elseif ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'seasons-bulk' ) ) {
			$racketmanager->set_message( __( 'Security token invalid', 'racketmanager' ), true );
		} elseif ( isset( $_POST['action'] ) && 'delete' === $_POST['action'] && isset( $_POST['del_season'] ) ) {
			$msg = array();
			foreach ( $_POST['del_season'] as $season ) {  //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
				$update          = $competition->delete_season( $season );
				$schedule_args[] = intval( $competition->id );
				$schedule_args[] = intval( $season );
				$schedule_name   = 'rm_notify_team_entry_open';
				Racketmanager_Util::clear_scheduled_event( $schedule_name, $schedule_args );
				$schedule_name = 'rm_notify_team_entry_reminder';
				Racketmanager_Util::clear_scheduled_event( $schedule_name, $schedule_args );
				$schedule_name = 'rm_calculate_team_ratings';
				Racketmanager_Util::clear_scheduled_event( $schedule_name, $schedule_args );
				if ( $update ) {
					/* translators: %s: season name */
					$msg[] = sprintf( __( 'Season %s deleted', 'racketmanager' ), $season );
				} else {
					/* translators: %s: season name */
					$msg[] = sprintf( __( 'Season %s not deleted', 'racketmanager' ), $season );
				}
			}
			$racketmanager->set_message( implode( '<br>', $msg ) );
		}
	}
	/**
	 * Delete league(s) from event via admin
	 */
	protected function delete_leagues_from_event(): void {
		if ( ! current_user_can( 'del_leagues' ) ) {
			$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
		} elseif ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'leagues-bulk' ) ) {
				$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
		} else {
			$messages = array();
			if ( isset( $_POST['league'] ) ) {
				foreach ( $_POST['league'] as $league_id ) { //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
					$league = get_league( $league_id );
					$league->delete();
					$messages[] = $league->title . ' ' . __( 'deleted', 'racketmanager' );
				}
				$message = implode( '<br>', $messages );
				$this->set_message( $message );
			}
		}
	}

	/**
	 * Save constitution for event via admin
	 */
	protected function save_constitution(): void {
		if ( ! current_user_can( 'edit_leagues' ) ) {
			$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
		} elseif ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'constitution-bulk' ) ) {
				$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
		} else {
			$js = false;
			if ( isset( $_POST['js-active'] ) ) {
				$js = 1 === intval( $_POST['js-active'] );
			}
			$rank = 0;
			if ( isset( $_POST['table_id'] ) ) {
				$latest_season = isset( $_POST['latest_season'] ) ? sanitize_text_field( wp_unslash( $_POST['latest_season'] ) ) : null;
				// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
				foreach ( $_POST['table_id'] as $table_id ) {
					$team      = $_POST['team_id'][$table_id] ?? null;
					$league_id = $_POST['league_id'][$table_id] ?? null;
					if ( $js ) {
						++$rank;
					} else {
						$rank = $_POST['rank'][$table_id] ?? '';
					}
					$status  = $_POST['status'][$table_id] ?? null;
					$profile = $_POST['profile'][$table_id] ?? null;
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
	protected function add_teams_to_constitution(): void {
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
	protected function delete_constitution_teams(): void {
		if ( current_user_can( 'del_leagues' ) ) {
			if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'constitution-bulk' ) ) {
				$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
			} elseif ( isset( $_POST['table'] ) && isset( $_POST['latest_season'] ) ) {
					// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
				foreach ( $_POST['table'] as $table_id ) {
					$teams   = $_POST['team_id'] ?? array();
					$leagues = $_POST['league_id'] ?? array();
					$team    = $teams[$table_id] ?? 0;
					$league  = $leagues[$table_id] ?? 0;
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
	protected function generate_box_league_matches(): void {
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
					$event->generate_box_league_matches();
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
	 * Update event settings via admin
	 *
	 * @param object $event event object.
	 */
	protected function update_event_settings( object $event ): void {
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
            $this->set_message( __( 'Settings saved', 'racketmanager' ) );
		}
	}
	/**
	 * Handle league teams action function
	 *
	 * @param object $league league object.
	 *
	 * @return void
	 */
	protected function handle_league_teams_action( object $league ): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['action'] ) ) {
			if ( 'delete' === $_POST['action'] ) {
				$this->delete_teams_from_league( $league );
			} elseif ( 'withdraw' === $_POST['action'] ) {
				$this->withdraw_teams_from_league( $league );
			} else {
				$this->set_message( __( 'No action selected', 'racketmanager' ), true );
			}
		}
	}
	/**
	 * Add teams to league in admin screen
	 *
	 * @param object $league league object.
	 */
	protected function league_add_teams( object $league ): void {
		if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_add-teams-bulk' ) ) {
			$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
		} elseif ( current_user_can( 'edit_teams' ) ) {
			if ( isset( $_POST['team'] ) && isset( $_POST['event_id'] ) && isset( $_POST['season'] ) ) {
				$league = get_league( $league );
				foreach ( $_POST['team'] as $team_id ) { //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
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
	protected function league_manage_team( object $league ): void {
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
					$match_day    = isset( $_POST['matchday'] ) ? intval( $_POST['matchday'] ) : null;
					$matchtime    = isset( $_POST['matchtime'] ) ? sanitize_text_field( wp_unslash( $_POST['matchtime'] ) ) : null;
					$team->set_event( $league->event->id, $captain, $contactno, $contactemail, $match_day, $matchtime );
				} elseif ( isset( $_POST['team'] ) && isset( $_POST['clubId'] ) && isset( $_POST['team_type'] ) ) {
                    $team->update( sanitize_text_field( wp_unslash( $_POST['team'] ) ), intval( $_POST['clubId'] ), sanitize_text_field( wp_unslash( $_POST['team_type'] ) ) );
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
	private function delete_teams_from_league( object $league ): void {
		if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_teams-bulk' ) ) {
			$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
		} elseif ( isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) {
			if ( current_user_can( 'del_teams' ) ) {
				$league        = get_league( $league );
				$season        = isset( $_POST['season'] ) ? sanitize_text_field( wp_unslash( $_POST['season'] ) ) : null;
				$messages      = array();
				if ( isset( $_POST['team'] ) ) {
					foreach ( $_POST['team'] as $team_id ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
						$league->delete_team( intval( $team_id ), $season );
						$messages[] = $team_id . ' ' . __( 'deleted', 'racketmanager' );
					}
					$message = implode( '<br>', $messages );
					$this->set_message( $message );
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
	private function withdraw_teams_from_league( object $league ): void {
		if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_teams-bulk' ) ) {
			$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
		} elseif ( isset( $_POST['action'] ) && 'withdraw' === $_POST['action'] ) {
			if ( current_user_can( 'del_teams' ) ) {
				$league        = get_league( $league );
				$season        = isset( $_POST['season'] ) ? sanitize_text_field( wp_unslash( $_POST['season'] ) ) : null;
				$messages      = array();
				if ( isset( $_POST['team'] ) ) {
					foreach ( $_POST['team'] as $team_id ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
						$team = get_team( $team_id );
						$league->withdraw_team( intval( $team_id ), $season );
						$messages[] = $team->title . ' ' . __( 'withdrawn', 'racketmanager' );
					}
					$message = implode( '<br>', $messages );
					$this->set_message( $message );
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
	protected function manage_matches_in_league( object $league ): void {
		if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_manage-matches' ) ) {
			$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
		} elseif ( current_user_can( 'edit_matches' ) ) {
			if ( ! empty( $_POST['mode'] ) && 'add' === sanitize_text_field( wp_unslash( $_POST['mode'] ) ) ) {
				$this->add_matches_to_league( $league );
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
	 * @param string|null $group group details.
	 */
	protected function add_matches_to_league( object $league, string $group = null ): void {
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
				$match = new stdClass();
				if ( isset( $_POST['away_team'][ $i ] ) && isset( $_POST['home_team'][ $i ] ) && $_POST['away_team'][ $i ] !== $_POST['home_team'][ $i ] ) {
					$index = ( isset( $_POST['myDatePicker'][ $i ] ) ) ? $i : 0;
					if ( ! isset( $_POST['begin_hour'][ $i ] ) ) {
						$_POST['begin_hour'][ $i ] = 0;
					}
					if ( ! isset( $_POST['begin_minutes'][ $i ] ) ) {
						$_POST['begin_minutes'][ $i ] = 0;
					}
					if ( isset( $_POST['myDatePicker'][ $index ] ) && isset( $_POST['begin_hour'][ $i ] ) ) {
						$match->date      = sanitize_text_field( wp_unslash( $_POST['myDatePicker'][ $index ] ) ) . ' ' . intval( $_POST['begin_hour'][ $i ] ) . ':' . intval( $_POST['begin_minutes'][ $i ] ) . ':00';
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
	protected function edit_matches_in_league( object $league ): void {
		if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_manage-matches' ) ) {
			$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
		} elseif ( isset( $_POST['match'] ) ) {
				$num_matches = count( $_POST['match'] );
				$post_match  = $this->htmlspecialchars_array( $_POST['match'] ); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			foreach ( $post_match as $i => $match_id ) {
				$match         = get_match( $match_id );
				$begin_hour    = isset( $_POST['begin_hour'][ $i ] ) ? intval( $_POST['begin_hour'][ $i ] ) : '00';
				$begin_minutes = isset( $_POST['begin_minutes'][ $i ] ) ? intval( $_POST['begin_minutes'][ $i ] ) : '00';
				if ( isset( $_POST['myDatePicker'][ $i ] ) ) {
					$date  = sanitize_text_field( wp_unslash( $_POST['myDatePicker'][ $i ] ) );
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
						$match->match_day = intval( $_POST['match_day'] );
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
	protected function delete_matches_from_league(): void {
        global $racketmanager;
		if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_matches-bulk' ) ) {
			$racketmanager->set_message( __( 'Security token invalid', 'racketmanager' ), true );
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
						$racketmanager->set_message( $message );
					}
				}
			} else {
				$racketmanager->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
			}
		}
	}

	/**
	 * Update results in league in admin screen
	 */
	protected function update_results_in_league(): void {
		global $league;
		if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_matches-bulk' ) ) {
			$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
		} elseif ( current_user_can( 'update_results' ) ) {
				//phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$custom      = $_POST['custom'] ?? array();
				$matches     = $_POST['matches'] ?? array();
				$home_points = $_POST['home_points'] ?? array();
				$away_points = $_POST['away_points'] ?? array();
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
		} else {
			$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
		}
	}

	/**
	 * Rank teams in league after manually adjusting points in admin screen
	 *
	 * @param object $league league object.
	 */
	protected function league_manual_rank( object $league ): void {
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
	protected function league_manual_rank_teams( object $league ): void {
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
	protected function league_random_rank_teams( object $league ): void {
		if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_teams-bulk' ) ) {
			$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
		} elseif ( current_user_can( 'update_results' ) ) {
			$league     = get_league( $league );
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
	 * Rating points rank teams league in admin screen
	 *
	 * @param object $league league object.
	 */
	protected function league_rating_points_rank_teams( object $league ): void {
		global $racketmanager;
		if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_teams-bulk' ) ) {
			$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
		} elseif ( current_user_can( 'update_results' ) ) {
			$league     = get_league( $league );
			$team_ranks = array();
			if ( isset( $_POST['table_id'] ) ) {
				$display_opt = $racketmanager->get_options( 'display' );
				$team_ids    = array_values( $_POST['table_id'] ); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				if ( isset( $_POST['rating_points'] ) ) {
					$rating_points = array_values( $_POST['rating_points'] ); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					if ( empty( $display_opt['wtn'] ) || $league->event->competition->is_team_entry ) {
						array_multisort( $rating_points, SORT_DESC, $team_ids, SORT_ASC );
					} else {
						array_multisort( $rating_points, SORT_ASC, $team_ids, SORT_ASC );
					}
				}
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
	protected function league_contact_teams(): void {
        global $racketmanager;
		if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_contact-teams-preview' ) ) {
			$racketmanager->set_message( __( 'Security token invalid', 'racketmanager' ), true );
		} elseif ( current_user_can( 'edit_teams' ) ) {
			if ( isset( $_POST['league_id'] ) && isset( $_POST['season'] ) && isset( $_POST['emailMessage'] ) ) {
                $league = get_league( $_POST['league_id'] );
				$sent = $league->contact_teams( sanitize_text_field( wp_unslash( $_POST['season'] ) ), htmlspecialchars_decode( $_POST['emailMessage'] ) ); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				if ( $sent ) {
					$racketmanager->set_message( __( 'Email sent to captains', 'racketmanager' ) );
				}
			}
		} else {
			$racketmanager->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
		}
	}

	/**
	 * Display teams list page
	 */
	protected function display_teams_list(): void {
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
				$teams          = $primary_league->get_league_teams();
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
				$match_array                     = array();
				$match_array['reset_query_args'] = true;
				$final_name                      = $primary_league->championship->get_final_keys( 1 );
				$match_array['final']            = $final_name;
				$match_array['pending']          = true;
				$matches                         = $primary_league->get_matches( $match_array );
				if ( $matches ) {
					foreach ( $matches as $match ) {
						$team          = new stdClass();
						$team->id      = '2_' . $final_name . '_' . $match->id;
						$team->title   = __( 'Loser of ', 'racketmanager' ) . $match->teams['home']->title . ' ' . __( 'vs', 'racketmanager' ) . ' ' . $match->teams['away']->title;
						$team->stadium = '';
						$teams[]       = $team;
					}
				}
				$final_name           = $primary_league->championship->get_final_keys( 2 );
				$match_array['final'] = $final_name;
				$matches              = $primary_league->get_matches( $match_array );
				if ( $matches ) {
					foreach ( $matches as $match ) {
						$possible   = 0;
						$team_types = array( 'home', 'away' );
						foreach ( $team_types as $team_type ) {
							$team_ref = $team_type . '_team';
							if ( is_numeric( $match->$team_ref ) ) {
								$match_array['pending']   = false;
								$match_array['final']     = 'all';
								$match_array['winner_id'] = $match->$team_ref;
								$team_matches             = $primary_league->get_matches( $match_array );
								foreach ( $team_matches as $team_match ) {
									if ( '-1' === $team_match->home_team || '-1' === $team_match->away_team ) {
										++$possible;
									}
								}
							}
						}
						if ( $possible ) {
							$team          = new stdClass();
							$team->id      = '2_' . $final_name . '_' . $match->id;
							$team->title   = __( 'Loser of ', 'racketmanager' ) . $match->teams['home']->title . ' ' . __( 'vs', 'racketmanager' ) . ' ' . $match->teams['away']->title;
							$team->stadium = '';
							$teams[]       = $team;
						}
					}
				}
			}
			$season        = isset( $_GET['season'] ) ? sanitize_text_field( wp_unslash( $_GET['season'] ) ) : '';
			$view          = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : '';
			$tournament_id = isset( $_GET['tournament'] ) ? intval( $_GET['tournament'] ) : null;
			if ( $tournament_id ) {
				$tournament = get_tournament( $tournament_id );
			}
			//phpcs:enable WordPress.Security.NonceVerification.Recommended
			require_once RACKETMANAGER_PATH . '/admin/includes/teams-list.php';
		}
	}
	/**
	 * Display clubs page
	 */
	private function display_clubs_page(): void {
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
					$club             = new stdClass();
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
                    if ( empty( $club->name ) ) {
						$this->set_message( __( 'Name required', 'racketmanager' ), true );
					} elseif ( empty( $club->shortcode ) ) {
						$this->set_message( __( 'Shortcode required', 'racketmanager' ), 'error' );
					} elseif ( empty( $club->address ) ) {
						$this->set_message( __( 'Address required', 'racketmanager' ), 'error' );
					} else {
						$club             = new Racketmanager_Club( $club );
						$this->set_message( __( 'Club added', 'racketmanager' ) );
					}
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
                    if ( empty( $_POST['club'] ) ) {
						$this->set_message( __( 'No clubs selected', 'racketmanager' ), true );
					} else {
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
				}

				$this->printMessage();
			} elseif ( isset( $_POST['doSchedulePlayerRatings'] ) ) {
				check_admin_referer( 'clubs-bulk' );
				if ( ! current_user_can( 'del_teams' ) ) {
					$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
				} else {
					$this->schedule_player_ratings();
				}
				$this->printMessage();
			}
			include_once RACKETMANAGER_PATH . '/admin/show-clubs.php';
		}
	}

	/**
	 * Display club page
	 */
	private function display_club_page(): void {
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
	private function display_club_players_page(): void {
        $club_id = null;
		if ( ! current_user_can( 'edit_teams' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			if ( isset( $_POST['addPlayer'] ) ) {
				if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_manage-player' ) ) {
					$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
					$this->printMessage();
				} else {
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
				}
			} elseif ( isset( $_POST['doClubPlayerDel'] ) && isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) {
				check_admin_referer( 'club-players-bulk' );
				if ( isset( $_POST['clubPlayer'] ) ) {
					foreach ( $_POST['clubPlayer'] as $club_player_id ) { //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
						$club_player = get_club_player( $club_player_id );
						$club_player?->remove();
					}
				}
			} elseif ( isset( $_POST['doPlayerRatings'] ) ) {
				check_admin_referer( 'club-players-bulk' );
				if ( isset( $_POST['club_id'] ) ) {
					$club_id = intval( $_POST['club_id'] );
					$club    = get_club( $club_id );
					if ( $club ) {
						$schedule_name  = 'rm_calculate_player_ratings';
						$schedule_args[]  = $club->id;
						wp_schedule_single_event( time(), $schedule_name, $schedule_args );
						/*
						$racketmanager->calculate_player_ratings( $club->id );
						$players = $club->get_players(
							array(
								'active' => true,
								'type'   => 'player',
							)
						);
						foreach ( $players as $club_player ) {
							$player = get_player( $club_player->player_id );
							$player->set_team_rating();
						}
						$player = null;
						 */
						$this->set_message( __( 'Player ratings set', 'racketmanager' ) );
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
	 * Display teams page
	 */
	private function display_teams_page(): void {
        $club_id = null;
		if ( ! current_user_can( 'edit_teams' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			if ( isset( $_POST['addTeam'] ) ) {
				check_admin_referer( 'racketmanager_add-team' );
				if ( isset( $_POST['club'] ) && isset( $_POST['team_type'] ) ) {
					$club = get_club( intval( $_POST['club'] ) );
					$club->add_team( sanitize_text_field( wp_unslash( $_POST['team_type'] ) ) );
				}
			} elseif ( isset( $_POST['editTeam'] ) ) {
				check_admin_referer( 'racketmanager_manage-teams' );
				if ( ! current_user_can( 'edit_teams' ) ) {
					$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
				} elseif ( isset( $_POST['team_id'] ) ) {
						$team = get_team( intval( $_POST['team_id'] ) );
					if ( isset( $_POST['team'] ) && isset( $_POST['clubId'] ) && isset( $_POST['team_type'] ) ) {
						$team->update( sanitize_text_field( wp_unslash( $_POST['team'] ) ), intval( $_POST['clubId'] ), sanitize_text_field( wp_unslash( $_POST['team_type'] ) ) );
					}
				}
			} elseif ( isset( $_POST['doTeamDel'] ) && isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) {
				if ( ! current_user_can( 'del_teams' ) ) {
					$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
				} else {
					check_admin_referer( 'teams-bulk' );
					$messages      = array();
					if ( isset( $_POST['team'] ) ) {
						foreach ( $_POST['team'] as $team_id ) { //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
							$team = get_team( $team_id );
							$team->delete();
							$messages[] = $team->title . ' ' . __( 'deleted', 'racketmanager' );
						}
						$message = implode( '<br>', $messages );
						$this->set_message( $message );
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
	protected function display_team_page(): void {
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
				$league_id  = intval( $_GET['league_id'] );
				$league     = get_league( $league_id );
				$season     = isset( $_GET['season'] ) ? sanitize_text_field( wp_unslash( $_GET['season'] ) ) : '';
				$match_days = Racketmanager_Util::get_match_days();
				if ( $league->event->competition->is_player_entry ) {
					$file = 'player-team.php';
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
            $team_id       = isset( $_GET['edit'] ) ? intval( $_GET['edit'] ) : null;
            $tournament_id = isset( $_GET['tournament'] ) ? intval( $_GET['tournament'] ) : null;
            if ( $team_id ) {
                if ( $tournament_id ) {
                    $tournament = get_tournament( $tournament_id );
                    if ( ! $tournament ) {
	                    $this->set_message( __( 'Tournament not found', 'racketmanager' ), true );
	                    $this->printMessage();
                    }
                }
				$edit = true;
				if ( $league ) {
					$team = $league->get_team_dtls( $team_id );
				} else {
					$team = get_team( $team_id );
				}
				if ( ! isset( $team->roster ) ) {
					$team->roster = array();
				}
				$form_title  = __( 'Edit Team', 'racketmanager' );
				$form_action = __( 'Update', 'racketmanager' );
	            $clubs = $racketmanager->get_clubs();
	            //phpcs:enable WordPress.Security.NonceVerification.Recommended
	            require_once RACKETMANAGER_PATH . '/admin/includes/teams/' . $file;
			} else {
	            $this->set_message( __( 'Team not specified', 'racketmanager' ), true );
	            $this->printMessage();
			}
		}
	}
	/**
	 * Display match editing page
	 */
	private function display_match_page(): void {
        $league      = null;
        $max_matches = null;
        $match       = null;
        $final       = null;
        $team_array  = array();
        $num_first_round = null;
        $prev_round_name = null;
        $home_team       = null;
        $away_team       = null;
		if ( ! current_user_can( 'edit_matches' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			//phpcs:disable WordPress.Security.NonceVerification.Recommended
			$is_finals       = false;
			$final_key        = false;
			$cup             = false;
			$single_cup_game = false;
			$group           = isset( $_GET['group'] ) ? sanitize_text_field( wp_unslash( $_GET['group'] ) ) : null;
			$class           = 'alternate';
			$bulk            = false;
			if ( isset( $_GET['league_id'] ) ) {
				$league_id = intval( $_GET['league_id'] );
				$league    = get_league( $league_id );
				// check if league is a cup championship.
				$cup = $league->event->competition->is_championship;
			}
			$season = $league->current_season['name'];

			// select first group if none is selected and league is cup championship.
			if ( $cup && empty( $group ) ) {
				$groups = ($league->groups ?? '');
				if ( ! is_array( $groups ) ) {
					$groups = explode( ';', $groups );
				}
				$group = $groups[0] ?? '';
			}

			$matches = array();
			if ( isset( $_GET['edit'] ) ) {
				$reset        = isset( $_GET['reset'] );
				$match_id     = intval( $_GET['edit'] );
				$match        = get_match( $match_id );
				$mode         = 'edit';
				$edit         = true;
				$form_title   = __( 'Edit Match', 'racketmanager' );
				$submit_title = $form_title;
				if ( $reset ) {
					$match->reset_result();
				}
				if ( isset( $match->final_round ) && '' !== $match->final_round ) {
					$cup             = true;
					$single_cup_game = true;
				}
				$league_id  = $match->league_id;
				$matches[0] = $match;
				$match_day  = $match->match_day;
				$final_key   = $match->final_round ?? '';

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
				$final_key  = $league->championship->get_current_final_key();
				$mode      = isset( $_GET['mode'] ) ? sanitize_text_field( wp_unslash( $_GET['mode'] ) ) : null;
				$edit      = 'edit' === $mode;

				$final           = $league->championship->get_finals( $final_key );
				$num_first_round = $league->championship->num_teams_first_round;

				$max_matches = $final['num_matches'];

				if ( 'add' === $mode ) {
					/* translators: %s: round name */
					$form_title = sprintf( __( 'Add Matches - %s', 'racketmanager' ), Racketmanager_Util::get_final_name( $final_key ) );
					for ( $h = 0; $h < $max_matches; $h++ ) {
						$matches[ $h ] = new stdClass();
						if ( 'final' !== $final_key ) {
							$round = $final['round'];
							if ( $round & 1 ) {
								$matches[ $h ]->host = 'home';
							} else {
								$matches[ $h ]->host = 'away';
							}
						}
						$matches[ $h ]->hour    = $league->event->competition->default_match_start_time['hour'];
						$matches[ $h ]->minutes = $league->event->competition->default_match_start_time['minutes'];
					}
				} else {
					/* translators: %s: round name */
					$form_title = sprintf( __( 'Edit Matches - %s', 'racketmanager' ), Racketmanager_Util::get_final_name( $final_key ) );
					$match_args = array(
						'final'   => $final_key,
						'orderby' => array(
							'id' => 'ASC',
						),
					);
					if ( 'final' !== $final_key && ! empty( $league->current_season['home_away'] ) && 'true' === $league->current_season['home_away'] ) {
						$match_args['leg'] = 1;
					}
					$matches = $league->get_matches( $match_args );
				}
				$submit_title = $form_title;
			} else {
				$mode = 'add';
				$edit = false;
				$bulk = $cup;
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
                $matches[]        = new stdClass();
                $matches[0]->year = ( isset( $_GET['season'] ) && is_numeric( $_GET['season'] ) ) ? intval( $_GET['season'] ) : gmdate( 'Y' );
				for ( $i = 0; $i < $max_matches; $i++ ) {
					$matches[]              = new stdClass();
					$matches[ $i ]->hour    = $league->event->competition->default_match_start_time['hour'];
					$matches[ $i ]->minutes = $league->event->competition->default_match_start_time['minutes'];
				}
			}

			if ( $single_cup_game ) {
				$final       = $league->championship->get_finals( $final_key );
				$final_teams = $league->championship->get_final_teams( $final['key'] );
				if ( is_numeric( $match->home_team ) ) {
					$home_team = get_team( $match->home_team );
					$home_title = $home_team?->title;
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
					$away_title = $away_team?->title;
				} else {
					$away_team = $final_teams[ $match->away_team ];
					if ( $away_team ) {
						$away_title = $away_team->title;
					} else {
						$away_title = null;
					}
				}
			} elseif ( $is_finals ) {
				$teams = $league->championship->get_final_teams( $final_key );
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
						$team_array = match ($max_matches) {
							1 => array(1),
							2 => array(1, 3),
							4 => array(1, 5, 3, 7),
							8 => array(1, 9, 4, 12, 11, 14, 7, 15),
							16 => array(1, 17, 9, 25, 4, 21, 13, 28, 6, 22, 14, 30, 7, 23, 15, 31),
							32 => array(1, 33, 17, 49, 9, 41, 25, 57, 4, 36, 20, 52, 12, 44, 28, 60, 6, 38, 22, 54, 14, 46, 30, 62, 7, 39, 23, 55, 15, 47, 31, 63),
							default => array(),
						};
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
	private function display_admin_page(): void {
		$players = '';

		if ( ! current_user_can( 'edit_leagues' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			$tab = 'seasons';
			if ( isset( $_POST['addSeason'] ) ) {
				check_admin_referer( 'racketmanager_add-season' );
				if ( isset( $_POST['seasonName'] ) ) {
					$added = $this->add_season( sanitize_text_field( wp_unslash( $_POST['seasonName'] ) ) );
                    if ( $added ) {
						$this->set_message( __( 'Season added', 'racketmanager' ) );
					} else {
						$this->set_message( __( 'Season not added', 'racketmanager' ), true );
					}
				}
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
			} elseif ( isset( $_POST['doAddCompetitionsToSeason'] ) && isset( $_POST['action'] ) && 'addCompetitionsToSeason' === $_POST['action'] ) {
				if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_add-seasons-competitions-bulk' ) ) {
					$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
				} elseif ( isset( $_POST['competition'] ) ) {
					foreach ( $_POST['competition'] as $competition_id ) { //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
						if ( isset( $_POST['num_match_days'] ) ) {
							$this->add_season_to_competition( sanitize_text_field( wp_unslash( $_POST['season'] ) ), $competition_id, intval( $_POST['num_match_days'] ) );
						}
					}
				}
			}
			$this->printMessage();

			include_once RACKETMANAGER_PATH . '/admin/show-admin.php';
		}
	}
	/**
	 * Display import Page
	 */
	private function displayImportPage(): void {
		if ( ! current_user_can( 'import_leagues' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			if ( isset( $_POST['import'] ) ) {
				if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_import-datasets' ) ) {
					$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
				} else {
					$league_id = isset( $_POST['league_id'] ) ? intval( $_POST['league_id'] ) : null;
					$season    = isset( $_POST['season'] ) ? sanitize_text_field( wp_unslash( $_POST['season'] ) ) : null;
					$club      = isset( $_POST['club'] ) ? intval( $_POST['club'] ) : null;
					$files     = $_FILES['racketmanager_import'] ?? null; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					$delimiter = $_POST['delimiter'] ?? null; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					$mode      = isset( $_POST['mode'] ) ? sanitize_text_field( wp_unslash( $_POST['mode'] ) ) : null;
					$this->import( $league_id, $season, $files, $delimiter, $mode, $club );
				}
				$this->printMessage();
			}
			include_once RACKETMANAGER_PATH . '/admin/tools/import.php';
		}
	}

	/**
	 * Display contact page
	 */
	private function display_contact_page(): void {
		global $racketmanager;
        $title = null;
        $season = null;
        $object_type = null;
        $object = null;
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
				} elseif ( isset( $_POST['tournament_id'] ) ) {
					$tournament = get_tournament( intval( $_POST['tournament_id'] ) );
					$title       = $tournament->name;
					$object_type = 'tournament';
					$object      = $tournament;
					$object_name = 'tournament_id';
					$object_id   = $tournament->id;
				}
				if ( isset( $_POST['season'] ) ) {
					$season = sanitize_text_field( wp_unslash( $_POST['season'] ) );
				}
				$email_title   = isset( $_POST['contactTitle'] ) ? sanitize_text_field( wp_unslash( $_POST['contactTitle'] ) ) : null;
				$email_intro   = isset( $_POST['contactIntro'] ) ? sanitize_textarea_field( wp_unslash( $_POST['contactIntro'] ) ) : null;
				$email_body    = $_POST['contactBody'] ?? null; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$email_close   = isset( $_POST['contactClose'] ) ? sanitize_textarea_field( wp_unslash( $_POST['contactClose'] ) ) : null;
				$email_subject = $racketmanager->site_name . ' - ' . $title . ' ' . $season . ' - Important Message';

				$email_message = $racketmanager->shortcodes->load_template(
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
				} elseif ( isset( $_GET['tournament_id'] ) ) {
					$tournament = get_tournament( intval( $_GET['tournament_id'] ) );
					$object_type = 'tournament';
					$object_name = 'tournament_id';
					$object_id   = $tournament->id;
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
	 * Display link to settings page in plugin table
	 *
	 * @param array $links array of action links.
	 *
	 * @return array
	 */
	public function pluginActions( array $links ): array {
        $links['settings']      = '<a href="/wp-admin/admin.php?page=racketmanager-settings">' . __( 'Settings', 'racketmanager' ) . '</a>';
        $links['documentation'] = '<a href="/wp-admin/admin.php?page=racketmanager-doc">' . __( 'Documentation', 'racketmanager' ) . '</a>';
		return $links;
	}

	/**
	 * Load Javascript
	 */
	public function loadScripts(): void {
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
	public function loadStyles(): void {
		wp_enqueue_style( 'racketmanager-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css', false, RACKETMANAGER_VERSION, 'screen' );
		wp_enqueue_style( 'racketmanager', plugins_url( '/css/admin.css', __DIR__ ), false, RACKETMANAGER_VERSION, 'screen' );
		wp_enqueue_style( 'racketmanager-modal', plugins_url( '/css/modal.css', __DIR__ ), false, RACKETMANAGER_VERSION, 'screen' );

		$jquery_ui_version = '1.13.2';
		wp_register_style( 'jquery-ui', plugins_url( '/css/jquery/jquery-ui.min.css', __DIR__ ), false, $jquery_ui_version );
		wp_register_style( 'jquery-ui-structure', plugins_url( '/css/jquery/jquery-ui.structure.min.css', __DIR__ ), array( 'jquery-ui' ), $jquery_ui_version );
		wp_register_style( 'jquery-ui-theme', plugins_url( '/css/jquery/jquery-ui.theme.min.css', __DIR__ ), array( 'jquery-ui', 'jquery-ui-structure' ), $jquery_ui_version );

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
	private function delete_competition_pages( string $competition_name ): void {
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
	 *
	 * @return boolean $success
	 */
	protected function delete_event_matches( int $event ): bool {
		global $racketmanager;
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

		if ( $match_count ) {
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
	 * @param int $table_id table id.
	 * @param int $league_id league id.
	 * @param int $rank rank.
	 * @param string $status status.
	 * @param string $profile profile.
	 */
	private function updateTable( int $table_id, int $league_id, int $rank, string $status, string $profile ): void {
		global $wpdb;

		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"UPDATE $wpdb->racketmanager_table SET `league_id` = %d, `rank` = %d, `status` = %s, `profile` = %d WHERE `id` = %d",
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
	 *
	 * @return boolean
	 */
	private function add_season( string $name ): bool {
		global $wpdb;

		if ( ! current_user_can( 'edit_seasons' ) ) {
			$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
			return false;
		}
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"INSERT INTO $wpdb->racketmanager_seasons (name) VALUES (%s)",
				$name
			)
		);
		return true;
	}

	/**
	 * Delete season
	 *
	 * @param int $season_id season id to be deleted.
	 *
	 * @return void
	 */
	private function delete_season( int $season_id ): void {
		global $wpdb;

		if ( ! current_user_can( 'del_seasons' ) ) {
			$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );

			return;
		}

		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE FROM $wpdb->racketmanager_seasons WHERE `id` = %d",
				$season_id
			)
		);
		$this->set_message( __( 'Season deleted', 'racketmanager' ) );

	}
	/**
	 * Add new season to competition
	 *
	 * @param string $season season.
	 * @param int $competition_id competition id.
	 * @param int|null $num_match_days number of match days.
	 *
	 * @return array|boolean
	 */
	protected function add_season_to_competition( string $season, int $competition_id, int $num_match_days = null ): bool|array {
		global $competition;

		$competition = get_competition( $competition_id );
		if ( ! $num_match_days ) {
			$num_match_days = $this->get_default_match_days( $competition->type );
		}
		if ( ! $num_match_days ) {
			$this->set_message( __( 'Number of match days not specified', 'racketmanager' ), 'error' );
			return false;
		}
		$seasons            = empty( $competition->seasons ) ? array() : $competition->seasons;
		$seasons[ $season ] = array(
			'name'           => $season,
			'num_match_days' => $num_match_days,
			'status'         => 'draft',
		);
		ksort( $seasons );
		$competition->update_seasons( $seasons );
		$events = $competition->get_events();
		foreach ( $events as $event ) {
			$event = get_event( $event );
			if ( ! isset( $event->seasons[ $season ] ) ) {
				$this->add_season_to_event( $season, $event->id, $num_match_days );
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
	 * @param int $event_id event_id.
	 * @param int|null $num_match_days number of match days.
	 *
	 * @return void
	 */
	private function add_season_to_event( string $season, int $event_id, ?int $num_match_days ): void {
		global $event;

		$event = get_event( $event_id );
		if ( '' === $event->seasons ) {
			$event->seasons = array();
		}
		if ( $event->is_box ) {
			$event->seasons[ $season ] = array(
				'name'           => $season,
				'num_match_days' => 0,
				'status'         => 'draft',
			);
		} else {
			if ( ! $num_match_days ) {
				$num_match_days = $this->get_default_match_days( $event->competition->type );
			}
			if ( ! $num_match_days ) {
				$this->set_message( __( 'Number of match days not specified', 'racketmanager' ), 'error' );

				return;
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

	}
	/**
	 * Get default number of match days
	 *
	 * @param string $type competition type.
	 *
	 * @return int default number of match days.
	 */
	private function get_default_match_days( string $type ): int {
		global $racketmanager;
		$options                = $racketmanager->get_options();
		$rm_options             = $options['championship'];
		$default_num_match_days = $rm_options['numRounds'] ?? 1;
		switch ( $type ) {
			case 'cup':
				$args['count'] = true;
				$args['type']  = 'affiliated';
				$num_clubs     = $racketmanager->get_clubs( $args );
				if ( $num_clubs ) {
					$num_match_days = ceil( log( $num_clubs, 2 ) );
				} else {
					$num_match_days = $default_num_match_days;
				}
				break;
			case 'tournament':
				$num_match_days = $default_num_match_days;
				break;
			default:
				$num_match_days = 0;
				break;
		}
		return $num_match_days;
	}
	/**
	 * Edit season in object - competition or event
	 *
	 * @param object $season_data season data.
	 */
	protected function edit_season( object $season_data ): void {
		global $racketmanager, $competition;
		$error = false;
		if ( false !== $season_data->match_dates ) {
			if ( empty( $season_data->match_dates ) ) {
				$msg                             = __( 'Match dates not set', 'racketmanager' );
				$racketmanager->error_fields[]   = 'match_dates';
				$racketmanager->error_messages[] = $msg;
				$racketmanager->set_message( $msg, true );
				$error = true;
			} else {
				$match_date_values = array();
				$prev_match_date   = '';
				$match_date_empty  = 0;
				foreach ( $season_data->match_dates as $match_date ) {
					if ( empty( $match_date ) ) {
						++$match_date_empty;
						$racketmanager->set_message( __( 'Match date not set', 'racketmanager' ), true );
						$error = true;
					} elseif ( 'true' === $season_data->fixed_dates ) {
						$valid_match_date = in_array($match_date, $match_date_values, true);
						if ( false !== $valid_match_date ) {
							$racketmanager->set_message( __( 'Match dates must be unique', 'racketmanager' ), true );
							$error = true;
						} elseif ( $match_date <= $prev_match_date ) {
							$racketmanager->set_message( __( 'Match date must be later than previous date', 'racketmanager' ), true );
							$error = true;
						} else {
							$match_date_values[] = $match_date;
							$prev_match_date     = $match_date;
						}
					}
				}
				if ( $error && count( $season_data->match_dates ) === $match_date_empty ) {
					$error = false;
					$racketmanager->set_message( null );
				}
			}
		}
		if ( ! $season_data->num_match_days && ! $season_data->is_box ) {
			$racketmanager->set_message( __( 'Number of match days must be set', 'racketmanager' ), true );
			$error = true;
		}
		if ( ! $season_data->status ) {
			$racketmanager->set_message( __( 'Status must be set', 'racketmanager' ), true );
			$error = true;
		}
		if ( true !== $season_data->home_away && false !== $season_data->home_away ) {
			$racketmanager->set_message( __( 'Fixture type must be set', 'racketmanager' ), true );
			$error = true;
		}
		if ( 'competition' === $season_data->type ) {
			if ( empty( $season_data->date_open ) ) {
				$msg                             = __( 'Open date must be set', 'racketmanager' );
				$racketmanager->error_fields[]   = 'date_open';
				$racketmanager->error_messages[] = $msg;
				$racketmanager->set_message( $msg, true );
				$error = true;
			}
			if ( empty( $season_data->date_closing ) ) {
				$msg                             = __( 'Closing date must be set', 'racketmanager' );
				$racketmanager->error_fields[]   = 'date_open';
				$racketmanager->error_messages[] = $msg;
				$racketmanager->set_message( $msg, true );
				$error = true;
			}
			if ( empty( $season_data->date_start ) ) {
				$racketmanager->set_message( __( 'Start date must be set', 'racketmanager' ), true );
				$error = true;
			}
			if ( empty( $season_data->date_end ) ) {
				$racketmanager->set_message( __( 'End date must be set', 'racketmanager' ), true );
				$error = true;
			}
		}
		if ( empty( $season_data->type ) ) {
			$racketmanager->set_message( __( 'Type must be set', 'racketmanager' ), true );
			$error = true;
		}
		if ( ! $error ) {
            $object = null;
			if ( 'competition' === $season_data->type ) {
				$competition = get_competition( $season_data->object_id );
				$object      = $competition;
			} elseif ( 'event' === $season_data->type ) {
				$event  = get_event( $season_data->object_id );
				$object = $event;
			}
			$object->seasons[ $season_data->season ] = array(
				'name'              => $season_data->season,
				'num_match_days'    => $season_data->num_match_days,
				'match_dates'       => $season_data->match_dates,
				'home_away'         => $season_data->home_away,
				'fixed_match_dates' => $season_data->fixed_dates,
				'status'            => $season_data->status,
				'date_closing'      => $season_data->date_closing,
			);
			if ( 'competition' === $season_data->type ) {
				$object->seasons[ $season_data->season ]['date_open']        = $season_data->date_open;
				$object->seasons[ $season_data->season ]['date_start']       = $season_data->date_start;
				$object->seasons[ $season_data->season ]['date_end']         = $season_data->date_end;
				$object->seasons[ $season_data->season ]['competition_code'] = $season_data->competition_code;
				$object->seasons[ $season_data->season ]['venue']            = $season_data->venue ?? null;
				$object->seasons[ $season_data->season ]['grade']            = $season_data->grade ?? null;
			}
			ksort( $object->seasons );
			if ( 'competition' === $season_data->type ) {
				$this->save_competition_seasons( $object->seasons, $season_data->object_id );
			} elseif ( 'event' === $season_data->type ) {
				$this->save_event_seasons( $object->seasons, $season_data->object_id );
			}
			/* translators: %s: season */
			$racketmanager->set_message( sprintf( __( 'Season %s saved', 'racketmanager' ), $season_data->season ) );
			if ( 'competition' === $season_data->type ) {
				$events = $competition->get_events();
				foreach ( $events as $event ) {
					$event_season                 = new stdClass();
					$event_season->object_id      = $event->id;
					$event_season->type           = 'event';
					$event_season->season         = $season_data->season;
					$event_season->num_match_days = $season_data->num_match_days;
					$event_season->match_dates    = $season_data->match_dates;
					$event_season->home_away      = $season_data->home_away;
					$event_season->fixed_dates    = $season_data->fixed_dates;
					$event_season->status         = $season_data->status;
					$event_season->date_closing   = $season_data->date_closing;
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
				$racketmanager->set_message( sprintf( __( 'Season %s saved and constitution emailed', 'racketmanager' ), $season_data->season ) );
			}
		}
	}
	/**
	 * Save seasons array to database
	 *
	 * @param array $seasons seasons.
	 * @param int $competition_id competition id.
	 *
	 * @return void
	 */
	private function save_competition_seasons( array $seasons, int $competition_id ): void {
		global $wpdb, $racketmanager;
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE $wpdb->racketmanager_competitions SET `seasons` = %s WHERE `id` = %d",
				maybe_serialize( $seasons ),
				$competition_id
			)
		); // db call ok, no cache ok.
		wp_cache_delete( $competition_id, 'competitions' );
		$racketmanager->set_message( 'Season deleted', 'racketmanager' );
	}
	/**
	 * Save seasons array to database
	 *
	 * @param array $seasons seasons.
	 * @param int $event_id event id.
	 *
	 * @return void
	 */
	private function save_event_seasons( array $seasons, int $event_id ): void {
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE $wpdb->racketmanager_events SET `seasons` = %s WHERE `id` = %d",
				maybe_serialize( $seasons ),
				$event_id
			)
		); // db call ok, no cache ok.
		wp_cache_delete( $event_id, 'events' );
	}

	/**
	 * Display global settings page (e.g. color scheme options)
	 */
	public function display_options_page(): void {
		if ( ! current_user_can( 'manage_racketmanager' ) ) {
			$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			$options = $this->options;
			$tab = 0;
			if ( isset( $_POST['updateRacketManager'] ) ) {
				if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_manage-global-league-options' ) ) {
					$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
					$this->printMessage();
					return;
				}
				// Set active tab.
				$tab                                           = isset( $_POST['active-tab'] ) ? sanitize_text_field( wp_unslash( $_POST['active-tab'] ) ) : null;
				$valid                                         = true;
				$options['rosters']['btm']                     = isset( $_POST['btmRequired'] ) ? sanitize_text_field( wp_unslash( $_POST['btmRequired'] ) ) : null;
				$options['rosters']['rosterEntry']             = isset( $_POST['clubPlayerEntry'] ) ? sanitize_text_field( wp_unslash( $_POST['clubPlayerEntry'] ) ) : null;
				$options['rosters']['rosterConfirmation']      = isset( $_POST['confirmation'] ) ? sanitize_text_field( wp_unslash( $_POST['confirmation'] ) ) : null;
				$options['rosters']['rosterConfirmationEmail'] = isset( $_POST['confirmationEmail'] ) ? sanitize_text_field( wp_unslash( $_POST['confirmationEmail'] ) ) : null;
				$options['rosters']['ageLimitCheck']           = isset( $_POST['clubPlayerAgeLimitCheck'] ) ? sanitize_text_field( wp_unslash( $_POST['clubPlayerAgeLimitCheck'] ) ) : null;
				$options['display']['wtn']                     = isset( $_POST['wtnDisplay'] );
				$options['checks']['ageLimitCheck']            = isset( $_POST['ageLimitCheck'] );
				$options['checks']['leadTimeCheck']            = isset( $_POST['leadTimeCheck'] );
				$options['checks']['ratingCheck']              = isset( $_POST['ratingCheck'] );
				$options['checks']['wtn_check']                = isset( $_POST['wtnCheck'] );
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
					$options[ $competition_type ]['resultTimeout']           = isset( $_POST[ $competition_type ]['resultTimeout'] ) ? sanitize_text_field( wp_unslash( $_POST[ $competition_type ]['resultTimeout'] ) ) : null;
					$options[ $competition_type ]['resultPenalty']           = isset( $_POST[ $competition_type ]['resultPenalty'] ) ? sanitize_text_field( wp_unslash( $_POST[ $competition_type ]['resultPenalty'] ) ) : null;
					$options[ $competition_type ]['confirmationPending']     = isset( $_POST[ $competition_type ]['confirmationPending'] ) ? sanitize_text_field( wp_unslash( $_POST[ $competition_type ]['confirmationPending'] ) ) : null;
					$options[ $competition_type ]['confirmationTimeout']     = isset( $_POST[ $competition_type ]['confirmationTimeout'] ) ? sanitize_text_field( wp_unslash( $_POST[ $competition_type ]['confirmationTimeout'] ) ) : null;
					$options[ $competition_type ]['confirmationPenalty']     = isset( $_POST[ $competition_type ]['confirmationPenalty'] ) ? sanitize_text_field( wp_unslash( $_POST[ $competition_type ]['confirmationPenalty'] ) ) : null;
					$options[ $competition_type ]['confirmationRequired']    = isset( $_POST[ $competition_type ]['confirmationRequired'] );
					$options[ $competition_type ]['entry_level']             = isset( $_POST[ $competition_type ]['entryLevel'] ) ? sanitize_text_field( wp_unslash( $_POST[ $competition_type ]['entryLevel'] ) ) : null;
					$this->scheduleResultChase( $competition_type, $options[ $competition_type ] );
				}
				$options['championship']['numRounds']           = isset( $_POST['numRounds'] ) ? intval( $_POST['numRounds'] ) : null;
				$options['championship']['open_lead_time']      = isset( $_POST['openLeadtime'] ) ? intval( $_POST['openLeadtime'] ) : null;
				$grades = Racketmanager_Util::get_event_grades();
				foreach ( $grades as $grade => $grade_desc ) {
					$options['championship']['date_closing'][ $grade ]    = isset( $_POST[ $grade ]['dateClose'] ) ? intval( $_POST[ $grade ]['dateClose'] ) : null;
					$options['championship']['date_withdrawal'][ $grade ] = isset( $_POST[ $grade ]['dateWithdraw'] ) ? intval( $_POST[ $grade ]['dateWithdraw'] ) : null;
				}
				$options['billing']['billingEmail']             = isset( $_POST['billingEmail'] ) ? sanitize_text_field( wp_unslash( $_POST['billingEmail'] ) ) : null;
				$options['billing']['billingAddress']           = isset( $_POST['billingAddress'] ) ? sanitize_text_field( wp_unslash( $_POST['billingAddress'] ) ) : null;
				$options['billing']['billingTelephone']         = isset( $_POST['billingTelephone'] ) ? sanitize_text_field( wp_unslash( $_POST['billingTelephone'] ) ) : null;
				$options['billing']['billingCurrency']          = isset( $_POST['billingCurrency'] ) ? sanitize_text_field( wp_unslash( $_POST['billingCurrency'] ) ) : null;
				$options['billing']['bankName']                 = isset( $_POST['bankName'] ) ? sanitize_text_field( wp_unslash( $_POST['bankName'] ) ) : null;
				$options['billing']['sortCode']                 = isset( $_POST['sortCode'] ) ? sanitize_text_field( wp_unslash( $_POST['sortCode'] ) ) : null;
				$options['billing']['accountNumber']            = isset( $_POST['accountNumber'] ) ? intval( $_POST['accountNumber'] ) : null;
				$options['billing']['invoiceNumber']            = isset( $_POST['invoiceNumber'] ) ? intval( $_POST['invoiceNumber'] ) : null;
				$options['billing']['paymentTerms']             = isset( $_POST['paymentTerms'] ) ? intval( $_POST['paymentTerms'] ) : null;
				$options['billing']['stripe_is_live']           = isset( $_POST['billingIsLive'] );
				$options['billing']['api_publishable_key_test'] = isset( $_POST['api_publishable_key_test'] ) ? sanitize_text_field( wp_unslash( $_POST['api_publishable_key_test'] ) ) : null;
				$options['billing']['api_publishable_key_live'] = isset( $_POST['api_publishable_key_live'] ) ? sanitize_text_field( wp_unslash( $_POST['api_publishable_key_live'] ) ) : null;
				$options['billing']['api_secret_key_test']      = isset( $_POST['api_secret_key_test'] ) ? sanitize_text_field( wp_unslash( $_POST['api_secret_key_test'] ) ) : null;
				$options['billing']['api_secret_key_live']      = isset( $_POST['api_secret_key_live'] ) ? sanitize_text_field( wp_unslash( $_POST['api_secret_key_live'] ) ) : null;
				$options['billing']['api_endpoint_key_test']    = isset( $_POST['api_endpoint_key_test'] ) ? sanitize_text_field( wp_unslash( $_POST['api_endpoint_key_test'] ) ) : null;
				$options['billing']['api_endpoint_key_live']    = isset( $_POST['api_endpoint_key_live'] ) ? sanitize_text_field( wp_unslash( $_POST['api_endpoint_key_live'] ) ) : null;
				$options['keys']['googleMapsKey']               = isset( $_POST['googleMapsKey'] ) ? sanitize_text_field( wp_unslash( $_POST['googleMapsKey'] ) ) : null;
				$options['keys']['recaptchaSiteKey']            = isset( $_POST['recaptchaSiteKey'] ) ? sanitize_text_field( wp_unslash( $_POST['recaptchaSiteKey'] ) ) : null;
				$options['keys']['recaptchaSecretKey']          = isset( $_POST['recaptchaSecretKey'] ) ? sanitize_text_field( wp_unslash( $_POST['recaptchaSecretKey'] ) ) : null;
				$options['player']['walkover']['female']        = isset( $_POST['walkoverFemale'] ) ? intval( $_POST['walkoverFemale'] ) : null;
				$options['player']['noplayer']['female']        = isset( $_POST['noPlayerFemale'] ) ? intval( $_POST['noPlayerFemale'] ) : null;
				$options['player']['share']['female']           = isset( $_POST['shareFemale'] ) ? intval( $_POST['shareFemale'] ) : null;
				$options['player']['unregistered']['female']    = isset( $_POST['unregisteredFemale'] ) ? intval( $_POST['unregisteredFemale'] ) : null;
				$options['player']['walkover']['male']          = isset( $_POST['walkoverMale'] ) ? intval( $_POST['walkoverMale'] ) : null;
				$options['player']['noplayer']['male']          = isset( $_POST['noPlayerMale'] ) ? intval( $_POST['noPlayerMale'] ) : null;
				$options['player']['share']['male']             = isset( $_POST['shareMale'] ) ? intval( $_POST['shareMale'] ) : null;
				$options['player']['unregistered']['male']      = isset( $_POST['unregisteredMale'] ) ? intval( $_POST['unregisteredMale'] ) : null;
				$options['player']['walkover']['rubber']        = isset( $_POST['walkoverPointsRubber'] ) ? intval( $_POST['walkoverPointsRubber'] ) : null;
				$options['player']['walkover']['match']         = isset( $_POST['walkoverPointsMatch'] ) ? intval( $_POST['walkoverPointsMatch'] ) : null;
				$options['player']['share']['rubber']           = isset( $_POST['sharePoints'] ) ? intval( $_POST['sharePoints'] ) : null;
				if ( $options['checks']['ratingCheck'] && $options['checks']['wtn_check'] ) {
					$this->set_message( __( 'Only one check can be set for ratings and wtn', 'racketmanager' ), true );
					$valid = false;
					$tab   = 'players';
				}
				if ( $options['billing']['stripe_is_live'] ) {
					if ( empty( $options['billing']['api_publishable_key_live'] ) || empty( $options['billing']['api_secret_key_live'] ) ) {
						$this->set_message( __( 'Live mode requires live keys to be set', 'racketmanager' ), true );
						$valid = false;
						$tab   = 'billing';
					}
				}
				if ( $valid ) {
					update_option( 'racketmanager', $options );
					$this->set_message( __( 'Settings saved', 'racketmanager' ) );
				}
				$this->printMessage();
			}

			require_once RACKETMANAGER_PATH . '/admin/show-settings.php';
		}
	}

	/**
	 * Add meta box to post screen
	 *
	 * @param object $post post details.
	 */
	public function add_meta_box( object $post ): void {
		global $wpdb, $post;
		$leagues = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT `title`, `id` FROM $wpdb->racketmanager ORDER BY id "
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
						"SELECT `id`, `league_id`, `season` FROM $wpdb->racketmanager_matches WHERE `post_id` = %d",
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
            ?>
			<input type='hidden' name='curr_match_id' value="<?php echo esc_html( $match_id ); ?>" />
            <div class="container">
                <div class="row mb-3">
                    <div class="col-auto">
                        <div class="form-floating">
                            <select name='league_id' class='form-select' id='league_id' onChange="Racketmanager.getSeasonDropdown(this.value, <?php echo esc_html( $season ); ?>)">
                                <option value="0"><?php esc_html_e( 'Choose League', 'racketmanager' ); ?></option>
                                <?php
                                foreach ( $leagues as $league ) {
                                    ?>
                                    <option value="<?php echo esc_html( $league->id ); ?>" <?php selected( $league_id, $league->id, false ); ?>><?php echo esc_html( $league->title ); ?></option>
                                    <?php
                                }
                                ?>
                            </select>
                            <label for="league_id"><?php esc_html_e( 'League', 'racketmanager' ); ?></label>
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-auto">
                        <div class="form-floating" id="seasons">
                            <?php
                            if ( $match ) {
                                echo season_dropdown( $curr_league->id, array( 'season' => $curr_league->get_season() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            }
                            ?>
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-auto">
                        <div class="form-floating" id="matches">
                            <?php
                            if ( $match ) {
                                echo match_dropdown( $curr_league->id, array( 'season' => $curr_league->get_season(), 'match_id' => $match->id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
			<br style="clear: both;" />
			<?php
		}
	}

	/**
	 * Update post id for match report
	 */
	public function edit_match_report(): void {
		global $wpdb;
		//phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['post_ID'] ) ) {
			$post_id       = intval( $_POST['post_ID'] );
			$match_id      = isset( $_POST['match_id'] ) ? intval( $_POST['match_id'] ) : false;
			$curr_match_id = isset( $_POST['curr_match_id'] ) ? intval( $_POST['curr_match_id'] ) : false;

			if ( $match_id && $curr_match_id !== $match_id ) {
				$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						"UPDATE $wpdb->racketmanager_matches SET `post_id` = %d WHERE `id` = %d",
						$post_id,
						$match_id
					)
				);
				if ( 0 !== $curr_match_id ) {
					$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->prepare(
							"UPDATE $wpdb->racketmanager_matches SET `post_id` = 0 WHERE `id` = %d",
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
	 * Import data from CSV file
	 *
	 * @param int $league_id league.
	 * @param string $season season.
	 * @param array $file CSV file.
	 * @param string $delimiter delimiter.
	 * @param string $mode 'teams' | 'matches' | 'fixtures' | 'players' | 'clubplayers'.
	 * @param false|int $club - optional.
	 */
	private function import( int $league_id, string $season, array $file, string $delimiter, string $mode, false|int $club = false ): void {
		if ( empty( $file['name'] ) ) {
			$this->set_message( __( 'No file specified for upload', 'racketmanager' ), true );
		} elseif ( 0 === $file['size'] ) {
			$this->set_message( __( 'Upload file is empty', 'racketmanager' ), true );
		} else {
			$access_type = get_filesystem_method();
			if ( 'direct' === $access_type ) {
				/* you can safely run request_filesystem_credentials() without any issues and don't need to worry about passing in a URL */
				$credentials = request_filesystem_credentials( site_url() . '/wp-admin/', '', false, false, array() );
				/* initialize the API */
				if ( ! WP_Filesystem( $credentials ) ) {
					/* any problems and we exit */
					$this->set_message( __( 'Unable to access file system', 'racketmanager' ), true );
				} else {
					global $wp_filesystem;
					$new_file = Racketmanager_Util::get_file_path( $file['name'] );
					if ( $wp_filesystem->copy( $file['tmp_name'], $new_file, true ) ) {
						$contents = $wp_filesystem->get_contents_array( $new_file );
						if ( $contents ) {
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
						$this->set_message( sprintf( __( 'The uploaded file could not be moved to %s.', 'racketmanager' ), ABSPATH . 'wp-content/uploads' ), true );
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
	 * @param array $contents array of file contents.
	 * @param string $delimiter delimiter.
	 * @param int $league_id league.
	 * @param string $season season.
	 */
	private function importTable( array $contents, string $delimiter, int $league_id, string $season ): void {
		$league       = get_league( $league_id );
		$i            = 0;
		$x            = 0;
		foreach ( $contents as $record ) {
			$line = explode( $delimiter, $record );
			// ignore header and empty lines.
			if ( $i > 0 && count( $line ) > 1 ) {
				$team    = $line[0];
				$team_id = $this->get_team_id( $team );
				if ( ! empty( $team_id ) ) {
					$table_id = $league->add_team( $team_id, $season );
					if ( $table_id ) {
                        $league_team = get_league_team( $table_id );
                        if ( $league_team ) {
	                        $league_team->done_matches = $line[1] ?? 0;
	                        $league_team->won_matches  = $line[2] ?? 0;
	                        $league_team->draw_matches = $line[3] ?? 0;
	                        $league_team->lost_matches = $line[4] ?? 0;
	                        if ( isset( $line[5] ) ) {
		                        if (str_contains($line[5], ':')) {
			                        $points2 = explode( ':', $line[5] );
		                        } else {
			                        $points2 = array( $line[5], 0 );
		                        }
	                        } else {
		                        $points2 = array( 0, 0 );
	                        }
	                        $league_team->points2_plus  = intval( $points2[0] );
	                        $league_team->points2_minus = intval( $points2[1] );
	                        if ( isset( $line[6] ) ) {
		                        if (str_contains($line[6], ':')) {
			                        $points = explode( ':', $line[6] );
		                        } else {
			                        $points = array( $line[6], 0 );
		                        }
	                        } else {
		                        $points = array( 0, 0 );
	                        }
	                        $league_team->points_plus  = floatval( $points[0] );
	                        $league_team->points_minus = floatval( $points[1] );
	                        $league_team->add_points   = intval( $line[7] ?? 0 );
	                        $custom['sets_won']        = intval( $line[8] ?? 0 );
	                        $custom['sets_allowed']    = intval( $line[9] ?? 0 );
	                        $custom['games_won']       = intval( $line[10] ?? 0 );
	                        $custom['games_allowed']   = intval( $line[11] ?? 0 );
                            $league_team->custom       = $custom;
                            $league_team->update();
	                        ++$x;
                        }
					}
				}
			}
			++$i;
		}
        if ( ! empty( $i ) ) {
            $league->set_teams_rank( $season );
        }
		/* translators: %d: number of table entries imported */
		$this->set_message( sprintf( __( '%d Table Entries imported', 'racketmanager' ), $x ) );
	}

	/**
	 * Import fixtures from file
	 *
	 * @param array $contents array of file contents.
	 * @param string $delimiter delimiter.
	 * @param int $league_id league.
	 * @param string $season season.
	 */
	private function importFixtures( array $contents, string $delimiter, int $league_id, string $season ): void {
		$league = get_league( $league_id );
		$i      = 0;
		$x      = 0;
		foreach ( $contents as $record ) {
			$line = explode( $delimiter, $record );
			// ignore header and empty lines.
			if ( $i > 0 && count( $line ) > 1 ) {
				$match            = new stdClass();
                $match->league_id = $league->id;
				$date             = ( ! empty( $line[6] ) ) ? $line[0] . ' ' . $line[6] : $line[0] . ' 00:00';
				$match->match_day = $line[1] ?? '';
				$match->date      = trim( $date );
				$match->season    = $season;
				$match->home_team = $this->get_team_id( $line[2] );
				$match->away_team = $this->get_team_id( $line[3] );
				if ( ! empty( $match->home_team )  && ! empty( $match->away_team ) ) {
					$match->location = $line[4] ?? '';
					$match->group    = $line[5] ?? '';
					new Racketmanager_Match( $match );
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
	 * @param array $contents array of file contents.
	 * @param string $delimiter delimiter.
	 */
	private function importPlayers( array $contents, string $delimiter ): void {
		$error_messages = array();
		$i              = 0;
		$x              = 0;
		foreach ( $contents as $record ) {
			$line = explode( $delimiter, $record );
			// ignore header and empty lines.
			if ( $i > 0 && count( $line ) > 1 ) {
				$_POST['firstname']     = $line[0] ?? '';
				$_POST['surname']       = $line[1] ?? '';
				$_POST['gender']        = $line[2] ?? '';
				$_POST['btm']           = $line[3] ?? '';
				$_POST['email']         = $line[4] ?? '';
				$_POST['contactno']     = $line[5] ?? '';
				$_POST['year_of_birth'] = $line[6] ?? '';
				$player_valid           = $this->validatePlayer();
				if ( $player_valid[0] ) {
					$new_player = $player_valid[1];
					$player     = get_player( $new_player->user_login, 'login' );  // get player by login.
					if ( ! $player ) {
						new Racketmanager_Player( $new_player );
						++$x;
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
	 * @param array $contents array of file contents.
	 * @param string $delimiter delimiter.
	 * @param int $club club.
	 */
	private function importClubPlayers( array $contents, string $delimiter, int $club ): void {
		$club           = get_club( $club );
		$i              = 0;
		$x              = 0;
		$error_messages = array();
		foreach ( $contents as $record ) {
			$line = explode( $delimiter, $record );
			// ignore header and empty lines.
			if ( $i > 0 && count( $line ) > 1 ) {
				$_POST['firstname']     = $line[0] ?? '';
				$_POST['surname']       = $line[1] ?? '';
				$_POST['gender']        = $line[2] ?? '';
				$_POST['btm']           = $line[3] ?? '';
				$_POST['email']         = $line[4] ?? '';
				$_POST['contactno']     = $line[5] ?? '';
				$_POST['year_of_birth'] = $line[6] ?? '';
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
	public function htmlspecialchars_array( array $arr = array() ): array {
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
	 * Get latest season
	 *
	 * @return int
	 */
	public function getLatestSeason(): int {
		global $wpdb;

		return $wpdb->get_var( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT MAX(name) FROM $wpdb->racketmanager_seasons"
		);
	}

	/**
	 * Print formatted message
	 */
	public function printMessage(): void {
		if ( ! empty( $this->message ) ) {
			$alert_class = match ( $this->error ) {
				'error'   => 'danger',
				'warning' => 'warning',
				'info'    => 'info',
				default   => 'success',
			};
            echo show_alert( $this->message, $alert_class ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		$this->message = '';
	}

	/**
	 * Schedule result chase
	 *
	 * @param string $competition_type type of competition.
	 * @param array $options array of options to use for chasing result.
	 */
	private function scheduleResultChase( string $competition_type, array $options ): void {
		$day            = intval( gmdate( 'd' ) );
		$month          = intval( gmdate( 'm' ) );
		$year           = intval( gmdate( 'Y' ) );
		$schedule_start = mktime( 19, 0, 0, $month, $day, $year );
		$interval       = 'daily';
		$schedule_args  = array( $competition_type );
		if ( '' !== $options['resultPending'] ) {
			$schedule_name = 'rm_resultPending';
			Racketmanager_Util::clear_scheduled_event( $schedule_name, $schedule_args );
			if ( ! wp_next_scheduled( $schedule_name, $schedule_args ) && ! wp_schedule_event( $schedule_start, $interval, $schedule_name, $schedule_args ) ) {
				error_log( __( 'Error scheduling pending results', 'racketmanager' ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
		}
		if ( '' !== $options['confirmationPending'] ) {
			$schedule_name = 'rm_confirmationPending';
			Racketmanager_Util::clear_scheduled_event( $schedule_name, $schedule_args );
			if ( ! wp_next_scheduled( $schedule_name, $schedule_args ) && ! wp_schedule_event( $schedule_start, $interval, $schedule_name, $schedule_args ) ) {
				error_log( __( 'Error scheduling result confirmations', 'racketmanager' ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
		}
	}
	/**
	 * Schedule player ratings calculation
	 */
	public function schedule_player_ratings(): void {
		$day            = intval( gmdate( 'd' ) );
		$month          = intval( gmdate( 'm' ) );
		$year           = intval( gmdate( 'Y' ) );
		$schedule_start = mktime( 12, 0, 0, $month, $day, $year );
		$interval       = 'weekly';
		$schedule_name  = 'rm_calculate_player_ratings';
		$schedule_args  = array();
		if ( ! wp_next_scheduled( $schedule_name, $schedule_args ) ) {
			$success = wp_schedule_event( $schedule_start, $interval, $schedule_name, $schedule_args );
			if ( $success ) {
				$this->set_message( __( 'Player ratings calculation scheduled', 'racketmanager' ) );
			} else {
				$this->set_message( __( 'Error scheduling player ratings calculation', 'racketmanager' ), true );
			}
		}
	}
	/**
	 * Set championship matches function
	 *
	 * @param object $league league object.
	 * @param int $season season name.
	 * @param array $input_rounds round details.
	 * @param string $action action on matches.
	 */
	protected function set_championship_matches( object $league, int $season, array $input_rounds, string $action ): void {
		global $racketmanager;
		$team_array      = array();
		$prev_round_name = null;
		$home_team       = null;
		$away_team       = null;
        $matches         = array();
        $valid           = true;
		$event_season    = $league->event->seasons[ $season ];
		$num_first_round = $league->championship->num_teams_first_round;
		$rounds          = array();
        $msg             = null;
		foreach ( $input_rounds as $round ) {
			if ( empty( $round['match_date'] ) ) {
				/* translators: $s: $round number */
				$msg[] = sprintf( __( 'Match date missing for round %s', 'racketmanager' ), $round['round'] );
				$valid = false;
			} elseif ( ! empty( $next_round_date ) && $round['match_date'] >= $next_round_date ) {
				/* translators: $s: $round number */
				$msg[] = sprintf( __( 'Match date for round %s after next round date', 'racketmanager' ), $round['round'] );
				$valid = false;
			} else {
				$round_date = $round['match_date'];
				$teams      = $league->championship->get_final_teams( $round['key'] );
				if ( 1 !== intval( $round['round'] ) ) {
					$prev_round      = $round['round'] - 1;
					$prev_round_name = $league->championship->get_final_keys( $prev_round );
					$first_round     = false;
					$home_team       = 1;
					$away_team       = 2;
				} else {
					$first_round = true;
					$team_array = match ( $round['num_matches'] ) {
						'1' => array(1),
						'2' => array(1, 3),
						'4' => array(1, 5, 3, 7),
						'8' => array(1, 9, 4, 12, 11, 14, 7, 15),
						'16' => array(1, 17, 9, 25, 4, 21, 13, 28, 6, 22, 14, 30, 7, 23, 15, 31),
						'32' => array(1, 33, 17, 49, 9, 41, 25, 57, 4, 36, 20, 52, 12, 44, 28, 60, 6, 38, 22, 54, 14, 46, 30, 62, 7, 39, 23, 55, 15, 47, 31, 63),
						default => array(),
					};
				}
				$matches[ $round_date ] = array();
				for ( $i = 0; $i < $round['num_matches']; ++$i ) {
					$match            = new stdClass();
					$match->date      = $round_date . ' 00:00:00';
					$match->match_day = null;
					if ( 'final' !== $round['key'] ) {
						if ( $round['round'] & 1 ) {
							$match->host = 'home';
						} else {
							$match->host = 'away';
						}
					}
					if ( $first_round ) {
						$home_team      = $team_array[ $i ];
						$home_team_name = $home_team . '_';
						$away_team      = $num_first_round + 1 - $home_team;
						$away_team_name = $away_team . '_';
					} else {
						$home_team_name = '1_' . $prev_round_name . '_' . $home_team;
						$away_team_name = '1_' . $prev_round_name . '_' . $away_team;
					}
					$match->home_team = $teams[ $home_team_name ]->id;
					$match->away_team = $teams[ $away_team_name ]->id;
					if ( $first_round ) {
						++$home_team;
						$away_team = $num_first_round + 1 - $home_team;
					} else {
						$home_team += 2;
						$away_team += 2;
					}
					$match->location          = null;
					$match->league_id         = $league->id;
					$match->season            = $season;
					$match->final_round       = $round['key'];
					$match->num_rubbers       = $league->num_rubbers;
					$matches[ $round_date ][] = $match;
				}
				$next_round_date               = $round['match_date'];
				$rounds[ $round['key'] ]       = new stdClass();
				$rounds[ $round['key'] ]->name = $round['key'];
				$rounds[ $round['key'] ]->num  = $round['round'];
				$rounds[ $round['key'] ]->date = $round['match_date'];
			}
		}
		if ( $valid ) {
			$league->set_rounds( $season, $rounds );
			if ( 'replace' === $action ) {
				$league->delete_season_matches( $season );
				$message = __( 'Matches replaced', 'racketmanager' );
			} else {
				$message = __( 'Matches added', 'racketmanager' );
			}
			$event_season['match_dates'] = array();
			foreach ( array_reverse( $matches ) as $match_date => $round_matches ) {
				$event_season['match_dates'][] = $match_date;
				foreach ( $round_matches as $match ) {
					$league->add_match( $match );
				}
			}
			if ( ! $league->championship->is_consolation ) {
				$event_season['num_match_days'] = count( $event_season['match_dates'] );
				$event                          = get_event( $league->event_id );
				if ( $event ) {
					$event_seasons            = $event->seasons;
					$event_seasons[ $season ] = $event_season;
					$event->update_seasons( $event_seasons );
				}
			}
			$racketmanager->set_message( $message );
		} else {
			$message = implode( '<br>', $msg );
			$racketmanager->set_message( $message, true );
		}
		$racketmanager->printMessage();
	}
}
?>
