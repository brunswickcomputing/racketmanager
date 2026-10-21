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

use Racketmanager\Admin\Admin_Display;
use Racketmanager\Infrastructure\Wordpress\Ajax\Admin_Ajax_Controller;
use Racketmanager\Infrastructure\Wordpress\Assets\Asset_Manager;
use Racketmanager\Services\Container\Container_Bootstrap;
use Racketmanager\Services\Container\Simple_Container;
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

    public ?Admin_Ajax_Controller $ajax_admin = null;
    public ?Admin_Display $admin_display = null;
    public ?Simple_Container $container = null;
    public ?RacketManager $racketmanager = null;
    public ?Asset_Manager $asset_manager = null;
    private bool $hooks_registered = false;

    /**
     * Constructor
     *
     * @param Simple_Container|null       $container
     * @param RacketManager|null          $racketmanager
     * @param Admin_Display|null          $admin_display
     * @param Asset_Manager|null          $asset_manager
     * @param Admin_Ajax_Controller|null  $ajax_admin
     */
    public function __construct(
        ?Simple_Container $container = null,
        ?RacketManager $racketmanager = null,
        ?Admin_Display $admin_display = null,
        ?Asset_Manager $asset_manager = null,
        ?Admin_Ajax_Controller $ajax_admin = null,
    ) {
        self::$instance = $this;

        $this->container     = $container;
        $this->racketmanager = $racketmanager;
        $this->asset_manager = $asset_manager;
        $this->admin_display = $admin_display;
        $this->ajax_admin    = $ajax_admin;

        if ( $this->ajax_admin && $this->container?->has( 'ajax_registry' ) ) {
            $this->ajax_admin->register_hooks( $this->container->get( 'ajax_registry' ) );
        }

        // Load WordPress admin meta box/template helpers (add_meta_box, do_meta_boxes, submit_meta_box(), etc.)
        // These functions live in wp-admin/includes/template.php and are needed by Admin::metaboxes() and related UI.
        if ( defined( 'ABSPATH' ) && file_exists( ABSPATH . 'wp-admin/includes/template.php' ) ) {
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

        add_action( 'admin_enqueue_scripts', array( &$this, 'load_scripts' ) );
        add_action( 'admin_enqueue_scripts', array( &$this, 'load_styles' ) );

        add_action( 'admin_menu', array( &$this, 'menu' ) );
        add_action( 'admin_footer', array( &$this, 'scroll_top' ) );

        add_action( 'show_user_profile', array( &$this, 'custom_user_profile_fields' ) );
        add_action( 'edit_user_profile', array( &$this, 'custom_user_profile_fields' ) );
        add_action( 'personal_options_update', array( &$this, 'update_extra_profile_fields' ) );
        add_action( 'edit_user_profile_update', array( &$this, 'update_extra_profile_fields' ) );

        // Add a meta-box to the post-screen.
        add_action( 'publish_post', array( &$this, 'edit_match_report' ) );
        add_action( 'edit_post', array( &$this, 'edit_match_report' ) );
        add_action( 'add_meta_boxes', array( &$this, 'metaboxes' ) );
    }

    /**
     * Return an instance of this class.
     *
     * @param Simple_Container|null $container
     * @param RacketManager|null    $racketmanager
     * @return Admin
     * @since     1.0.0
     */
    public static function get_instance( ?Simple_Container $container = null, ?RacketManager $racketmanager = null ): self {
        if ( null !== self::$instance ) {
            return self::$instance;
        }

        if ( null !== $container && $container->has( 'admin' ) ) {
            $instance = $container->get( 'admin' );
            if ( $instance instanceof self ) {
                self::$instance = $instance;
                return $instance;
            }
        }

        $rm = $racketmanager ?? (
            isset( $GLOBALS['racketmanager'] ) && $GLOBALS['racketmanager'] instanceof RacketManager
                ? $GLOBALS['racketmanager']
                : null
        );

        if ( null !== $rm && isset( $rm->container ) && $rm->container->has( 'admin' ) ) {
            $instance = $rm->container->get( 'admin' );
            if ( $instance instanceof self ) {
                self::$instance = $instance;
                return $instance;
            }
        }

        throw new \LogicException( 'Admin instance has not been initialized. Ensure Container_Bootstrap has booted the application container.' );
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
     * @param false|string $index
     * @return mixed
     */
    public function get_options( false|string $index = false ): mixed {
        return $this->racketmanager?->get_options( $index ) ?? [];
    }

    /**
     * Legacy options update helper.
     *
     * @param array $options
     * @return void
     */
    public function update_plugin_options( array $options ): void {
        $this->racketmanager?->update_plugin_options( $options );
    }

    /**
     * Legacy options section setter helper.
     *
     * @param string $type
     * @param array  $options
     */
    public function set_options( string $type, array $options ): void {
        $this->racketmanager?->set_options( $type, $options );
    }

    /**
     * Legacy asset url helper.
     *
     * @param string $path
     * @return string
     */
    public function get_asset_url( string $path ): string {
        return $this->asset_manager?->get_asset_url( $path ) ?? '';
    }

    /**
     * Dynamic property getter for backward compatibility.
     *
     * @param string $name
     * @return mixed
     */
    public function __get( string $name ): mixed {
        if ( 'options' === $name ) {
            return $this->racketmanager?->options ?? [];
        }
        return null;
    }

    /**
     * Dynamic property check for backward compatibility.
     *
     * @param string $name
     * @return bool
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
            array( $this->admin_display, 'display' ),
            'data:image/svg+xml;base64,' . base64_encode( $svg ),
            2
        );
        add_action( "admin_print_scripts-$page", array( &$this, 'load_scripts' ) );
        add_action( "admin_print_scripts-$page", array( &$this, 'load_styles' ) );

        $page = add_submenu_page(
            'racketmanager', // parent page.
            __( 'RacketManager', 'racketmanager' ), // page title.
            __( 'Competitions', 'racketmanager' ), // menu title.
            'racket_manager', // capability.
            'racketmanager', // menu slug.
            array( $this->admin_display, 'display' )
        );
        add_action( "admin_print_scripts-$page", array( &$this, 'load_scripts' ) );
        add_action( "admin_print_scripts-$page", array( &$this, 'load_styles' ) );

        $page = add_submenu_page(
            'racketmanager',
            __( 'Leagues', 'racketmanager' ),
            __( 'Leagues', 'racketmanager' ),
            'racket_manager',
            'racketmanager-leagues',
            array( $this->admin_display, 'display' )
        );
        add_action( "admin_print_scripts-$page", array( &$this, 'load_scripts' ) );
        add_action( "admin_print_scripts-$page", array( &$this, 'load_styles' ) );

        $page = add_submenu_page(
            'racketmanager',
            __( 'Cups', 'racketmanager' ),
            __( 'Cups', 'racketmanager' ),
            'racket_manager',
            'racketmanager-cups',
            array( $this->admin_display, 'display' )
        );
        add_action( "admin_print_scripts-$page", array( &$this, 'load_scripts' ) );
        add_action( "admin_print_scripts-$page", array( &$this, 'load_styles' ) );

        $page = add_submenu_page(
            'racketmanager',
            __( 'Tournaments', 'racketmanager' ),
            __( 'Tournaments', 'racketmanager' ),
            'racket_manager',
            'racketmanager-tournaments',
            array( $this->admin_display, 'display' )
        );
        add_action( "admin_print_scripts-$page", array( &$this, 'load_scripts' ) );
        add_action( "admin_print_scripts-$page", array( &$this, 'load_styles' ) );

        $page = add_submenu_page(
            'racketmanager',
            __( 'Clubs', 'racketmanager' ),
            __( 'Clubs', 'racketmanager' ),
            'racket_manager',
            'racketmanager-clubs',
            array( $this->admin_display, 'display' )
        );
        add_action( "admin_print_scripts-$page", array( &$this, 'load_scripts' ) );
        add_action( "admin_print_scripts-$page", array( &$this, 'load_styles' ) );

        $page = add_submenu_page(
            'racketmanager',
            __( 'Results', 'racketmanager' ),
            __( 'Results', 'racketmanager' ),
            'racket_manager',
            'racketmanager-results',
            array( $this->admin_display, 'display' )
        );
        add_action( "admin_print_scripts-$page", array( &$this, 'load_scripts' ) );
        add_action( "admin_print_scripts-$page", array( &$this, 'load_styles' ) );

        $page = add_submenu_page(
            'racketmanager',
            __( 'Players', 'racketmanager' ),
            __( 'Players', 'racketmanager' ),
            'racket_manager',
            'racketmanager-players',
            array( $this->admin_display, 'display' )
        );
        add_action( "admin_print_scripts-$page", array( &$this, 'load_scripts' ) );
        add_action( "admin_print_scripts-$page", array( &$this, 'load_styles' ) );

        $page = add_submenu_page(
            'racketmanager',
            __( 'Seasons', 'racketmanager' ),
            __( 'Seasons', 'racketmanager' ),
            'racketmanager_settings',
            'racketmanager-seasons',
            array( $this->admin_display, 'display' )
        );
        add_action( "admin_print_scripts-$page", array( &$this, 'load_scripts' ) );
        add_action( "admin_print_scripts-$page", array( &$this, 'load_styles' ) );

        $page = add_submenu_page(
            'racketmanager',
            __( 'Finances', 'racketmanager' ),
            __( 'Finances', 'racketmanager' ),
            'racket_manager',
            'racketmanager-finances',
            array( $this->admin_display, 'display' )
        );
        add_action( "admin_print_scripts-$page", array( &$this, 'load_scripts' ) );
        add_action( "admin_print_scripts-$page", array( &$this, 'load_styles' ) );

        $page = add_submenu_page(
            'racketmanager',
            __( 'Settings', 'racketmanager' ),
            __( 'Settings', 'racketmanager' ),
            'racketmanager_settings',
            'racketmanager-settings',
            array( $this->admin_display, 'display' )
        );
        add_action( "admin_print_scripts-$page", array( &$this, 'load_scripts' ) );
        add_action( "admin_print_scripts-$page", array( &$this, 'load_styles' ) );

        $page = add_submenu_page(
            'racketmanager',
            __( 'Import', 'racketmanager' ),
            __( 'Import', 'racketmanager' ),
            'import_leagues',
            'racketmanager-import',
            array( $this->admin_display, 'display' )
        );
        add_action( "admin_print_scripts-$page", array( &$this, 'load_scripts' ) );
        add_action( "admin_print_scripts-$page", array( &$this, 'load_styles' ) );

        $page = add_submenu_page(
            'racketmanager',
            __( 'Documentation', 'racketmanager' ),
            __( 'Documentation', 'racketmanager' ),
            'view_leagues',
            'racketmanager-doc',
            array( $this->admin_display, 'display' )
        );
        add_action( "admin_print_scripts-$page", array( &$this, 'load_scripts' ) );
        add_action( "admin_print_scripts-$page", array( &$this, 'load_styles' ) );

        add_filter( 'plugin_action_links_' . RACKETMANAGER_PLUGIN_BASENAME, array( &$this, 'plugin_actions' ) );
    }

    /**
     * Adds scroll to the top icon to the admin interface
     */
    public function scroll_top(): void {
        ?>
        <a class="go-top dashicons dashicons-arrow-up-alt2"><?php esc_html__( 'Top', 'racketmanager' ); ?></a>
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
        if ( current_user_can( 'edit_user' ) ) {
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
     * Display a link to the settings page in the plugin table
     *
     * @param array $links array of action links.
     *
     * @return array
     */
    public function plugin_actions( array $links ): array {
        $links['settings']      = '<a href="/wp-admin/admin.php?page=racketmanager-settings">' . __( 'Settings', 'racketmanager' ) . '</a>';
        $links['documentation'] = '<a href="/wp-admin/admin.php?page=racketmanager-doc">' . __( 'Documentation', 'racketmanager' ) . '</a>';
        return $links;
    }

    /**
     * Load Javascript
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

    /************
     *
     *   COMPETITION SECTION
     */



    /**
     * Add meta box to the post screen
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
     * Update post-id for a match report
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

    /**
     * Schedule player ratings calculation
     */
    public function schedule_player_ratings(): bool|WP_Error {
        return $this->container->get( 'player_service' )->schedule_global_player_ratings();
    }
}
