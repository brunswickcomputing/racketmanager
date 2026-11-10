<?php
/**
 * RacketManager-Admin API: RacketManager-admin class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManager-Admin
 */

declare( strict_types=1 );

namespace Racketmanager;

use LogicException;
use Racketmanager\Admin\Admin_Display;
use Racketmanager\Admin\Admin_Match_Report;
use Racketmanager\Infrastructure\Wordpress\Ajax\Admin_Ajax_Controller;
use Racketmanager\Infrastructure\Wordpress\Assets\Asset_Manager;
use Racketmanager\Infrastructure\Wordpress\Bootstrap\Plugin_Bootstrap;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Player_Service;
use WP_Error;

/**
 * RacketManager administration functions
 * Class to implement RacketManager Administration panel
 *
 * @author Kolja Schleich
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManagerAdmin
 */
class Admin {

    /**
     * Instance of this class.
     *
     * @var Admin|null
     */
    protected static ?Admin $instance = null;

    /**
     * Error messages.
     *
     * @var array|null $error_messages
     */
    public ?array $error_messages = null;

    /**
     * Ajax controller instance.
     *
     * @var Admin_Ajax_Controller|null
     */
    public ?Admin_Ajax_Controller $ajax_admin = null;

    /**
     * Admin display coordinator.
     *
     * @var Admin_Display|null
     */
    public ?Admin_Display $admin_display = null;

    /**
     * Service container instance.
     *
     * @var Simple_Container|null
     */
    public ?Simple_Container $container = null;

    /**
     * Main RacketManager plugin instance.
     *
     * @var RacketManager|null
     */
    public ?RacketManager $racketmanager = null;

    /**
     * Asset manager instance.
     *
     * @var Asset_Manager|null
     */
    public ?Asset_Manager $asset_manager = null;

    /**
     * League repository instance.
     *
     * @var League_Repository_Interface|null
     */
    private ?League_Repository_Interface $league_repository;

    /**
     * Fixture repository instance.
     *
     * @var Fixture_Repository_Interface|null
     */
    private ?Fixture_Repository_Interface $fixture_repository;

    /**
     * Admin match report handler.
     *
     * @var Admin_Match_Report
     */
    private Admin_Match_Report $match_report;

    /**
     * Whether admin hooks have already been registered.
     *
     * @var bool
     */
    private bool $hooks_registered = false;

    /**
     * Constructor
     *
     * @param Simple_Container|null             $container          Service container instance.
     * @param RacketManager|null                $racketmanager      Plugin runtime instance.
     * @param Admin_Display|null                $admin_display      Admin display coordinator.
     * @param Asset_Manager|null                $asset_manager      Asset manager instance.
     * @param Admin_Ajax_Controller|null        $ajax_admin         Admin ajax controller instance.
     * @param League_Repository_Interface|null  $league_repository  League repository instance.
     * @param Fixture_Repository_Interface|null $fixture_repository Fixture repository instance.
     */
    public function __construct(
        ?Simple_Container $container = null,
        ?RacketManager $racketmanager = null,
        ?Admin_Display $admin_display = null,
        ?Asset_Manager $asset_manager = null,
        ?Admin_Ajax_Controller $ajax_admin = null,
        ?League_Repository_Interface $league_repository = null,
        ?Fixture_Repository_Interface $fixture_repository = null,
    ) {
        self::$instance = $this;

        $this->container          = $container;
        $this->racketmanager      = $racketmanager;
        $this->asset_manager      = $asset_manager;
        $this->admin_display      = $admin_display;
        $this->ajax_admin         = $ajax_admin;
        $this->league_repository  = $this->resolve_league_repository( $league_repository );
        $this->fixture_repository = $fixture_repository ?? ( $this->container?->has( Fixture_Repository_Interface::class ) ? $this->container->get( Fixture_Repository_Interface::class ) : null );
        $this->match_report       = new Admin_Match_Report( $this->league_repository, $this->fixture_repository );

        if ( $this->ajax_admin && $this->container?->has( 'ajax_registry' ) ) {
            $this->ajax_admin->register_hooks( $this->container->get( 'ajax_registry' ) );
        }

        // Load WordPress admin meta box/template helpers (add_meta_box, do_meta_boxes, submit_meta_box(), etc.)
        // These functions live in wp-admin/includes/template.php and are needed by Admin::metaboxes() and related UI.
        if ( defined( 'ABSPATH' ) && file_exists( ABSPATH . 'wp-admin/includes/template.php' ) ) {
            /** @noinspection PhpIncludeInspection */
            require_once ABSPATH . 'wp-admin/includes/template.php';
        }

        $this->register_admin_hooks();
    }

    /**
     * Register admin action and filter hooks.
     */
    public function register_admin_hooks(): void {
        if ( $this->hooks_registered ) {
            return;
        }
        $this->hooks_registered = true;

        add_action( 'admin_enqueue_scripts', [ $this, 'load_scripts' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'load_styles' ] );

        add_action( 'admin_menu', [ $this, 'menu' ] );
        add_action( 'admin_footer', [ $this, 'scroll_top' ] );

        add_action( 'show_user_profile', [ $this, 'custom_user_profile_fields' ] );
        add_action( 'edit_user_profile', [ $this, 'custom_user_profile_fields' ] );
        add_action( 'personal_options_update', [ $this, 'update_extra_profile_fields' ] );
        add_action( 'edit_user_profile_update', [ $this, 'update_extra_profile_fields' ] );

        // Add a meta-box to the post-screen.
        add_action( 'publish_post', [ $this, 'edit_match_report' ] );
        add_action( 'edit_post', [ $this, 'edit_match_report' ] );
        add_action( 'add_meta_boxes', [ $this, 'metaboxes' ] );
    }

    /**
     * Resolve the league repository dependency using parameter precedence and container fallbacks.
     *
     * @param League_Repository_Interface|null $league_repository Explicit league repository instance, if provided.
     *
     * @return League_Repository_Interface|null
     */
    private function resolve_league_repository( ?League_Repository_Interface $league_repository ): ?League_Repository_Interface {
        if ( null !== $league_repository ) {
            return $league_repository;
        }

        $repository = null;
        if ( $this->container?->has( League_Repository_Interface::class ) ) {
            $repository = $this->container->get( League_Repository_Interface::class );
        } elseif ( $this->container?->has( 'league_repository' ) ) {
            $repository = $this->container->get( 'league_repository' );
        }

        return $repository;
    }

    /**
     * Return an instance of this class.
     *
     * @param Simple_Container|null $container     Service container instance.
     * @param RacketManager|null    $racketmanager Main RacketManager plugin instance.
     *
     * @return Admin
     * @throws LogicException When the Admin instance has not been initialized.
     * @since     1.0.0
     */
    public static function get_instance( ?Simple_Container $container = null, ?RacketManager $racketmanager = null ): self {
        if ( null !== self::$instance ) {
            return self::$instance;
        }

        $container ??= Plugin_Bootstrap::container();
        if ( null !== $container && $container->has( 'admin' ) ) {
            $instance = $container->get( 'admin' );
            if ( $instance instanceof self ) {
                self::$instance = $instance;
                return $instance;
            }
        }

        $rm = $racketmanager;
        if ( null !== $rm && isset( $rm->container ) && $rm->container->has( 'admin' ) ) {
            $instance = $rm->container->get( 'admin' );
            if ( $instance instanceof self ) {
                self::$instance = $instance;
                return $instance;
            }
        }

        throw new LogicException( 'Admin instance has not been initialized. Ensure Container_Bootstrap has booted the application container.' );
    }

    /**
     * Return the currently loaded instance, if any.
     */
    public static function get_current_instance(): ?self {
        return self::$instance;
    }

    /**
     * Reset singleton instance (primarily for testing).
     */
    public static function reset_instance(): void {
        self::$instance = null;
    }

    /**
     * Legacy options getter helper.
     *
     * @param false|string $index Option key name, or false for all options.
     *
     * @return mixed Array of options or option value.
     */
    public function get_options( false|string $index = false ): mixed {
        return $this->racketmanager?->get_options( $index ) ?? [];
    }

    /**
     * Legacy options section setter helper.
     *
     * @param string $type    Options section type identifier.
     * @param array  $options Array of section options.
     *
     * @return void
     */
    public function set_options( string $type, array $options ): void {
        $this->racketmanager?->set_options( $type, $options );
    }

    /**
     * Legacy asset url helper.
     *
     * @param string $path Relative asset path.
     *
     * @return string Full URL to the asset.
     */
    public function get_asset_url( string $path ): string {
        return $this->asset_manager?->get_asset_url( $path ) ?? '';
    }

    /**
     * Dynamic property getter for backward compatibility.
     *
     * @param string $name Property name to retrieve.
     *
     * @return array|null Options array if requested, null otherwise.
     */
    public function __get( string $name ): ?array {
        if ( 'options' === $name ) {
            return $this->racketmanager?->options ?? [];
        }
        return null;
    }

    /**
     * Dynamic property check for backward compatibility.
     *
     * @param string $name Property name to check.
     *
     * @return bool True if property is set, false otherwise.
     */
    public function __isset( string $name ): bool {
        if ( 'options' === $name ) {
            return isset( $this->racketmanager?->options );
        }
        return false;
    }
    /**
     * Adds the menu to the admin interface
     */
    public function menu(): void {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" viewBox="0 0 412.425 412.425" style="fill:white" xml:space="preserve"><path d="M412.425,108.933c0-30.529-10.941-58.18-30.808-77.86C361.776,11.418,333.91,0.593,303.153,0.593 c-41.3,0-83.913,18.749-116.913,51.438c-30.319,30.034-48.754,68.115-51.573,105.858c-0.845,5.398-1.634,11.13-2.462,17.188    c-4.744,34.686-10.603,77.415-34.049,104.503c-2.06,0.333-3.981,1.295-5.476,2.789L7.603,367.447 c-10.137,10.138-10.137,26.632,0,36.77c4.911,4.911,11.44,7.615,18.385,7.615s13.474-2.705,18.386-7.617l85.06-85.095 c1.535-1.536,2.457-3.448,2.784-5.438c27.087-23.461,69.829-29.322,104.524-34.068c6.549-0.896,12.734-1.741,18.508-2.666 c1.434-0.23,2.743-0.76,3.885-1.507c36.253-4.047,72.464-21.972,101.325-50.562C393.485,192.166,412.425,149.905,412.425,108.933z M145.476,218.349c4.984,10.244,11.564,19.521,19.608,27.49c8.514,8.434,18.51,15.237,29.576,20.262 c-25.846,5.238-52.769,13.823-73.415,30.692l-6.216-6.216C131.639,270.246,140.217,243.831,145.476,218.349z M30.23,390.075    c-1.133,1.133-2.64,1.757-4.242,1.757c-1.603,0-3.109-0.624-4.243-1.757c-2.339-2.339-2.339-6.146,0-8.485l78.006-78.007 l8.469,8.469L30.23,390.075z M243.559,256.318c-0.002,0-0.008,0-0.011,0c-25.822-0.003-48.087-8.54-64.389-24.688 c-16.279-16.126-24.883-38.136-24.883-63.652c0-2.596,0.1-5.201,0.276-7.808c0.023-0.143,0.045-0.295,0.068-0.438 c0.11-0.685,0.147-1.364,0.117-2.031c2.87-32.422,19.121-65.253,45.579-91.461c29.284-29.009,66.767-45.646,102.837-45.646 c25.819,0,48.085,8.537,64.389,24.689c16.279,16.126,24.883,38.136,24.883,63.651c-0.001,35.672-16.781,72.755-46.04,101.739 C317.1,239.682,279.624,256.319,243.559,256.318z"/></svg>';
        // keep capabilities here for the next update.
        $page = add_menu_page(
            __( 'RacketManager', 'racketmanager' ),
            __( 'RacketManager', 'racketmanager' ),
            'racket_manager',
            'racketmanager',
            [ $this->admin_display, 'display' ],
            'data:image/svg+xml;base64,' . base64_encode( $svg ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Used for admin menu SVG data URI.
            2
        );
        add_action( "admin_print_scripts-$page", [ $this, 'load_scripts' ] );
        add_action( "admin_print_scripts-$page", [ $this, 'load_styles' ] );

        $submenus = [
            [
                'page_title' => __( 'RacketManager', 'racketmanager' ),
                'menu_title' => __( 'Competitions', 'racketmanager' ),
                'capability' => 'racket_manager',
                'menu_slug'  => 'racketmanager',
                'callback'   => [ $this->admin_display, 'display' ],
            ],
            [
                'page_title' => __( 'Leagues', 'racketmanager' ),
                'menu_title' => __( 'Leagues', 'racketmanager' ),
                'capability' => 'racket_manager',
                'menu_slug'  => 'racketmanager-leagues',
                'callback'   => [ $this->admin_display, 'display' ],
            ],
            [
                'page_title' => __( 'Cups', 'racketmanager' ),
                'menu_title' => __( 'Cups', 'racketmanager' ),
                'capability' => 'racket_manager',
                'menu_slug'  => 'racketmanager-cups',
                'callback'   => [ $this->admin_display, 'display' ],
            ],
            [
                'page_title' => __( 'Tournaments', 'racketmanager' ),
                'menu_title' => __( 'Tournaments', 'racketmanager' ),
                'capability' => 'racket_manager',
                'menu_slug'  => 'racketmanager-tournaments',
                'callback'   => [ $this->admin_display, 'display' ],
            ],
            [
                'page_title' => __( 'Clubs', 'racketmanager' ),
                'menu_title' => __( 'Clubs', 'racketmanager' ),
                'capability' => 'racket_manager',
                'menu_slug'  => 'racketmanager-clubs',
                'callback'   => [ $this->admin_display, 'display' ],
            ],
            [
                'page_title' => __( 'Results', 'racketmanager' ),
                'menu_title' => __( 'Results', 'racketmanager' ),
                'capability' => 'racket_manager',
                'menu_slug'  => 'racketmanager-results',
                'callback'   => [ $this->admin_display, 'display' ],
            ],
            [
                'page_title' => __( 'Players', 'racketmanager' ),
                'menu_title' => __( 'Players', 'racketmanager' ),
                'capability' => 'racket_manager',
                'menu_slug'  => 'racketmanager-players',
                'callback'   => [ $this->admin_display, 'display' ],
            ],
            [
                'page_title' => __( 'Seasons', 'racketmanager' ),
                'menu_title' => __( 'Seasons', 'racketmanager' ),
                'capability' => 'racketmanager_settings',
                'menu_slug'  => 'racketmanager-seasons',
                'callback'   => [ $this->admin_display, 'display' ],
            ],
            [
                'page_title' => __( 'Finances', 'racketmanager' ),
                'menu_title' => __( 'Finances', 'racketmanager' ),
                'capability' => 'racket_manager',
                'menu_slug'  => 'racketmanager-finances',
                'callback'   => [ $this->admin_display, 'display' ],
            ],
            [
                'page_title' => __( 'Settings', 'racketmanager' ),
                'menu_title' => __( 'Settings', 'racketmanager' ),
                'capability' => 'racketmanager_settings',
                'menu_slug'  => 'racketmanager-settings',
                'callback'   => [ $this->admin_display, 'display' ],
            ],
            [
                'page_title' => __( 'Import', 'racketmanager' ),
                'menu_title' => __( 'Import', 'racketmanager' ),
                'capability' => 'import_leagues',
                'menu_slug'  => 'racketmanager-import',
                'callback'   => [ $this->admin_display, 'display' ],
            ],
            [
                'page_title' => __( 'Documentation', 'racketmanager' ),
                'menu_title' => __( 'Documentation', 'racketmanager' ),
                'capability' => 'view_leagues',
                'menu_slug'  => 'racketmanager-doc',
                'callback'   => [ $this->admin_display, 'display' ],
            ],
        ];

        foreach ( $submenus as $submenu ) {
            $this->register_submenu_page(
                'racketmanager',
                $submenu['page_title'],
                $submenu['menu_title'],
                $submenu['capability'],
                $submenu['menu_slug'],
                $submenu['callback']
            );
        }

        add_filter( 'plugin_action_links_' . RACKETMANAGER_PLUGIN_BASENAME, [ $this, 'plugin_actions' ] );
    }

    /**
     * Registers a submenu page and hooks script and style assets
     *
     * @param string         $parent_slug The slug name for the parent menu.
     * @param string         $page_title  The text to be displayed in the title tags of the page.
     * @param string         $menu_title  The text to be used for the menu.
     * @param string         $capability  The capability required for this menu to be displayed.
     * @param string         $menu_slug   The slug name to refer to this menu by.
     * @param array|callable $callback    The function to be called to output the content.
     *
     * @return void
     * @noinspection PhpSameParameterValueInspection
     */
    private function register_submenu_page(
        string $parent_slug,
        string $page_title,
        string $menu_title,
        string $capability,
        string $menu_slug,
        array|callable $callback
    ): void {
        $page = add_submenu_page(
            $parent_slug,
            $page_title,
            $menu_title,
            $capability,
            $menu_slug,
            $callback
        );
        add_action( "admin_print_scripts-$page", [ $this, 'load_scripts' ] );
        add_action( "admin_print_scripts-$page", [ $this, 'load_styles' ] );
    }

    /**
     * Adds scroll to the top icon to the admin interface
     */
    public function scroll_top(): void {
        ?>
        <a class="go-top dashicons dashicons-arrow-up-alt2"><?php esc_html_e( 'Top', 'racketmanager' ); ?></a>
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
        wp_nonce_field( 'racketmanager_update_extra_profile_fields_' . $user->ID, 'racketmanager_profile_nonce', false );
        ?>
        <div class="racketmanager-fields mt-3">
            <h2><?php esc_html_e( 'Racketmanager Details', 'racketmanager' ); ?></h2>
            <table class="form-table" aria-label="<?php esc_attr_e( 'racketmanager_fields', 'racketmanager' ); ?>">
                <tr>
                    <th>
                        <div><?php esc_html_e( 'Gender', 'racketmanager' ); ?></div>
                    </th>
                    <td>
                        <input type="radio" required name="gender" id="genderM" value="M" <?php checked( get_the_author_meta( 'gender', $user->ID ), 'M' ); ?>><label for="genderM"><?php esc_html_e( 'Male', 'racketmanager' ); ?></label>
                        <br>
                        <input type="radio" name="gender" id="genderF" value="F" <?php checked( get_the_author_meta( 'gender', $user->ID ), 'F' ); ?>><label for="genderF"><?php esc_html_e( 'Female', 'racketmanager' ); ?></label>
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
     * Save custom user profile fields after validating the nonce and capability.
     *
     * @param int $user_id user_id.
     */
    public function update_extra_profile_fields( int $user_id ): void {
        if ( $user_id <= 0 || ! current_user_can( 'edit_user', $user_id ) ) {
            return;
        }

        if ( ! $this->verify_profile_update_nonce( $user_id ) ) {
            return;
        }

        $this->save_user_profile_meta( $user_id );
    }

    /**
     * Verify the profile update nonce for plugin-specific or WordPress core requests.
     *
     * @param int $user_id User ID being updated.
     *
     * @return bool
     */
    private function verify_profile_update_nonce( int $user_id ): bool {
        $plugin_nonce = isset( $_POST['racketmanager_profile_nonce'] ) && is_string( $_POST['racketmanager_profile_nonce'] )
            ? sanitize_text_field( wp_unslash( $_POST['racketmanager_profile_nonce'] ) )
            : '';
        if ( '' !== $plugin_nonce && wp_verify_nonce( $plugin_nonce, 'racketmanager_update_extra_profile_fields_' . $user_id ) ) {
            return true;
        }

        $core_nonce = isset( $_POST['_wpnonce'] ) && is_string( $_POST['_wpnonce'] )
            ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) )
            : '';

        return '' !== $core_nonce && false !== wp_verify_nonce( $core_nonce, 'update-user_' . $user_id );
    }

    /**
     * Persist sanitised user profile metadata.
     *
     * @param int $user_id User ID being updated.
     *
     * @return void
     */
    private function save_user_profile_meta( int $user_id ): void {
        // phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified in caller update_extra_profile_fields().
        $gender = isset( $_POST['gender'] ) && ! is_array( $_POST['gender'] )
            ? sanitize_text_field( wp_unslash( (string) $_POST['gender'] ) )
            : '';
        if ( in_array( $gender, [ 'M', 'F' ], true ) ) {
            update_user_meta( $user_id, 'gender', $gender );
        }

        $fields = [
            'contactno'     => 'string',
            'remove_date'   => 'string',
            'locked_date'   => 'string',
            'btm'           => 'int',
            'year_of_birth' => 'int',
        ];

        foreach ( $fields as $field => $type ) {
            if ( ! isset( $_POST[ $field ] ) || is_array( $_POST[ $field ] ) ) {
                continue;
            }

            $value = 'int' === $type
                ? absint( wp_unslash( $_POST[ $field ] ) )
                : sanitize_text_field( wp_unslash( (string) $_POST[ $field ] ) );

            update_user_meta( $user_id, $field, $value );
        }
        // phpcs:enable WordPress.Security.NonceVerification.Missing
    }

    /**
     * Adds the required Metaboxes
     */
    public function metaboxes(): void {
        add_meta_box( 'racketmanager', __( 'Match-Report', 'racketmanager' ), [ $this, 'add_meta_box' ], 'post' );
    }

    /**
     * Display a link to the settings page in the plugin table
     *
     * @param array $links array of action links.
     *
     * @return array
     */
    public function plugin_actions( array $links ): array {
        $links['settings']      = '<a href="' . esc_url( admin_url( 'admin.php?page=racketmanager-settings' ) ) . '">' . __( 'Settings', 'racketmanager' ) . '</a>';
        $links['documentation'] = '<a href="' . esc_url( admin_url( 'admin.php?page=racketmanager-doc' ) ) . '">' . __( 'Documentation', 'racketmanager' ) . '</a>';
        return $links;
    }

    /**
     * Load JavaScript
     *
     * @param string|null $hook Current admin page hook.
     *
     * @return void
     */
    public function load_scripts( ?string $hook = null ): void {
        if ( null !== $this->asset_manager ) {
            $this->asset_manager->load_admin_scripts( $hook );
            return;
        }

        if ( isset( $this->container ) && $this->container->has( 'asset_manager' ) ) {
            $this->container->get( 'asset_manager' )->load_admin_scripts( $hook );
        }
    }

    /**
     * Load CSS styles
     */
    public function load_styles(): void {
        if ( null !== $this->asset_manager ) {
            $this->asset_manager->load_admin_styles();
            return;
        }

        if ( isset( $this->container ) && $this->container->has( 'asset_manager' ) ) {
            $this->container->get( 'asset_manager' )->load_admin_styles();
        }
    }

    /**
     * Add meta box to the post screen
     *
     * @param object $post Post details.
     */
    public function add_meta_box( object $post ): void {
        $this->match_report->add_meta_box( $post );
    }

    /**
     * Update post-id for a match report
     *
     * @param int $post_id Post ID being edited or published.
     */
    public function edit_match_report( int $post_id = 0 ): void {
        $this->match_report->edit_match_report( $post_id );
    }

    /**
     * Schedule player ratings calculation
     *
     * @return bool|WP_Error True if scheduled successfully, WP_Error if player service is unavailable.
     */
    public function schedule_player_ratings(): bool|WP_Error {
        if ( ! isset( $this->container ) || ! $this->container->has_definition( Player_Service::class ) ) {
            return new WP_Error( 'service_unavailable', __( 'Player service is unavailable.', 'racketmanager' ) );
        }

        $player_service = $this->container->get( Player_Service::class );
        if ( ! is_object( $player_service ) || ! method_exists( $player_service, 'schedule_global_player_ratings' ) ) {
            return new WP_Error( 'service_unavailable', __( 'Player service is unavailable.', 'racketmanager' ) );
        }

        return $player_service->schedule_global_player_ratings();
    }
}
