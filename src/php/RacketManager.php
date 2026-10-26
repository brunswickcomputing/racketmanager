<?php
declare( strict_types=1 );
/**
 * RacketManager API: RacketManager class
 *
 * @author Paul Moffat
 * @package RacketManager
 */

namespace Racketmanager;

use NumberFormatter;
use Racketmanager\Infrastructure\Wordpress\Assets\Asset_Manager;
use Racketmanager\Infrastructure\Wordpress\Bootstrap\Plugin_Bootstrap;
use Racketmanager\Infrastructure\Wordpress\Options\Option_Service;
use Racketmanager\Infrastructure\Wordpress\Templates\Template_Loader;
use Racketmanager\Services\Container\Container_Bootstrap;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Player_Service;

/**
 * Main class to implement RacketManager
 */
class RacketManager {
    protected static ?RacketManager $instance     = null;
    /**
     * The array of templates that this plugin tracks.
     *
     * @var array $template
     */
    protected array $templates = [];
    /**
     * Site name.
     *
     * @var string $site_name
     */
    public string $site_name = '';
    /**
     * Message.
     *
     * @var string $message
     */
    public string $message = '';
    /**
     * Error.
     *
     * @var string|bool $error
     */
    public bool|string $error = false;
    /**
     * Options.
     *
     * @var array $options
     */
    public array $options = [];
    /**
     * Options service.
     */
    public ?Option_Service $option_service = null;
    /**
     * Asset manager.
     */
    public ?Asset_Manager $asset_manager = null;
    /**
     * Template loader.
     */
    public ?Template_Loader $template_loader = null;
    /**
     * Plugin lifecycle bootstrap (hook registration and library loading).
     */
    public ?Plugin_Bootstrap $plugin_bootstrap = null;
    /**
     * Date format.
     *
     * @var string $date_format
     */
    public string $date_format = 'Y-m-d';
    /**
     * Time format.
     *
     * @var string $time_format
     */
    public string $time_format = 'H:i';
    /**
     * Admin email.
     *
     * @var string $admin_email
     */
    public string $admin_email = '';
    /**
     * Site url.
     *
     * @var string $site_url
     */
    public string $site_url = '';
    /**
     * Seasons.
     *
     * @var array $seasons
     */
    public array $seasons = [];
    /**
     * Currency code
     *
     * @var string
     */
    public string $currency_code = 'GBP';
    /**
     * Currency format
     *
     * @var NumberFormatter|null
     */
    public ?NumberFormatter $currency_fmt = null;
    public object $ajax_account;
    public object $ajax_club;
    public object $ajax_finance;
    public object $ajax_frontend;
    public object $ajax_fixture;
    public object $ajax_tournament ;
    public object $login;
    public object $shortcodes;
    public object $shortcodes_general;
    public object $shortcodes_club;
    public object $shortcodes_competition;
    public object $shortcodes_email;
    public object $shortcodes_event;
    public object $shortcodes_league;
    public object $shortcodes_login;
    public object $shortcodes_match;
    public object $shortcodes_message;
    public object $shortcodes_tournament;
    public object $rewrites;
    /**
     * Simple dependency injection container.
     */
    public Simple_Container $container;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct() {
        if ( empty( $this->options ) ) {
            // Boot the dependency injection container and register services.
            $this->container        = Container_Bootstrap::boot( $this );
            $this->option_service   = $this->container->get( 'option_service' );
            $this->load_options();
            $this->asset_manager    = $this->container->get( 'asset_manager' );
            $this->template_loader  = $this->container->get( 'template_loader' );
            $this->plugin_bootstrap = $this->container->get( 'plugin_bootstrap' );

            // Load procedural libraries and register WordPress lifecycle / scheduled task hooks.
            $this->plugin_bootstrap->boot( $this );
        }
        self::$instance = $this;
    }

    /**
     * Resolve a service or repository from the DI container.
     *
     * @template T of object
     * @param string $service_id Container service ID.
     * @return T
     * @throws \RuntimeException If the container is not initialized or the service is not found.
     */
    public function resolve_service( string $service_id ): object {
        if ( ! isset( $this->container ) || ! $this->container->has( $service_id ) ) {
            throw new \RuntimeException( sprintf( 'Service "%s" is not available in the container.', $service_id ) );
        }

        return $this->container->get( $service_id );
    }

    /**
     * Lazily resolve the player service from the container.
     */
    private function get_player_service(): Player_Service {
        return $this->container->get( 'player_service' );
    }

    public function report_fixture_result( int $fixture_id, string $competition_type ): void {
        $fixture_repository = $this->container->get( 'fixture_repository' );
        $fixture            = $fixture_repository->find_by_id( $fixture_id );
        $reporting_service  = $this->container->get( 'result_reporting_service' );

        if ( $fixture && $reporting_service ) {
            $reporting_service->report_result( $fixture, $competition_type );
        }
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
     * Set the page title function
     *
     * @param string $title title.
     *
     * @return string new title
     */
    public function set_page_title( string $title ): string {
        return $this->resolve_service( 'page_title_service' )->build_title( $title, $this->site_name );
    }
    /**
     * Chase pending results
     *
     * @param string $competition Competition type.
     *
     * @return void
     */
    public function chase_pending_results( string $competition = 'league' ): void {
        $this->resolve_service( 'pending_result_orchestration_service' )->chase_pending_results( $competition );
    }

    /**
     * Chase pending approvals
     *
     * @param string $competition Competition type.
     *
     * @return void
     */
    public function chase_pending_approvals( string $competition = 'league' ): void {
        $this->resolve_service( 'pending_result_orchestration_service' )->chase_pending_approvals( $competition );
    }
    /**
     * Calculate player ratings
     *
     * @param int|null $club_id club id.
     *
     * @return void
     */
    public function calculate_player_ratings( ?int $club_id = null ): void {
        // Delegate to the Player__Service implementation.
        $this->get_player_service()->calculate_player_ratings( $club_id );
    }

    /**
     * Get League standings function
     *
     * @param array $args array of query arguments.
     *
     * @return array
     */
    public function get_league_standings( array $args = array() ): array {
        return $this->resolve_service( 'league_repository' )->get_league_standings( $args );
    }
    /**
     * Adds our templates
     */
    public function add_racketmanager_templates(): void {
        $this->template_loader->register_hooks();
        $this->templates = $this->template_loader->get_templates();
    }

    /**
     * Adds our templates to the page dropdown
     *
     * @param array $posts_templates array of post templates.
     * @param mixed $theme           theme object.
     * @param mixed $post            post object or ID.
     */
    public function racketmanager_templates_as_option( array $posts_templates, mixed $theme = null, mixed $post = null ): array {
        return $this->template_loader->racketmanager_templates_as_option( $posts_templates, $theme, $post );
    }

    /**
     * Adds our templates to the page cache to trick WordPress
     * into thinking the template file exists where it doesn't really exist.
     *
     * @param array $atts array of attributes.
     */
    public function register_racketmanager_templates( array $atts ): array {
        return $this->template_loader->register_racketmanager_templates( $atts );
    }

    /**
     * Checks if the template is assigned to the page
     *
     * @param string $template template.
     */
    public function racketmanager_load_template( string $template ): string {
        return $this->template_loader->racketmanager_load_template( $template );
    }

    /**
     * Load specific archive templates
     *
     * @param string $template template.
     */
    public function racketmanager_archive_template( string $template ): string {
        return $this->template_loader->racketmanager_archive_template( $template );
    }

    /**
     * Register exporter array
     *
     * @param array $exporters_array template.
     */
    public function racketmanager_register_exporter( array $exporters_array ): array {
        $exporters_array['racketmanager_exporter'] = array(
            'exporter_friendly_name' => 'Racketmanager exporter',
            'callback'               => array( 'Racketmanager\Services\Privacy_Exporters', 'user_data_exporter' ),
        );
        return $exporters_array;
    }
    /**
     * Register Widget
     */
    public function register_widget(): void {
        register_widget( 'Racketmanager\Services\Widget' );
    }

    /**
     * Initialise components
     */
    public function init_components(): void {
        $this->resolve_service( 'component_coordinator' )->init_components( $this );
    }

    /**
     * Handle export requests.
     */
    public function handle_exports(): void {
        $this->resolve_service( 'component_coordinator' )->handle_exports( $this );
    }
    /**
     * Load shortcodes
     */
    public function load_shortcodes(): void {
        $this->container->get( 'shortcode_registrar' )->register();
    }
    /**
     * Read files in the directory
     *
     * @param string $dir directory name.
     *
     * @return array
     * @deprecated 10.0.0 Use native filesystem functions or Plugin_Bootstrap.
     */
    public function read_directory( string $dir ): array {
        $files = array();

        if ( file_exists( $dir ) ) {
            $handle = opendir( $dir );
            while ( false !== ( $file = readdir( $handle ) ) ) {
                $file_info = pathinfo( $dir . '/' . $file );
                $file_type = ( isset( $file_info['extension'] ) ) ? $file_info['extension'] : '';
                if ( '.' !== $file && '..' !== $file && ! is_dir( $file ) && ! str_starts_with( $file, '.' ) && 'php' === $file_type ) {
                    $files[ $file ] = $dir . '/' . $file;
                }
            }
        }

        return $files;
    }

    /**
     * Load options from Option_Service and sync public properties.
     */
    private function load_options(): void {
        if ( ! isset( $this->option_service ) ) {
            $this->option_service = $this->resolve_service( 'option_service' );
        }

        $this->options     = $this->option_service->get_options();
        $this->date_format = $this->option_service->get_date_format();
        $this->time_format = $this->option_service->get_time_format();
        $this->site_name   = $this->option_service->get_site_name();
        $this->admin_email = $this->option_service->get_admin_email();
        $this->site_url    = $this->option_service->get_site_url();
    }

    /**
     * Get options
     *
     * @param false|string $index index lookup (optional).
     */
    public function get_options( false|string $index = false ) {
        return $this->option_service->get_options( $index );
    }

    /**
     * Set options
     *
     * @param string $type
     * @param array $options.
     */
    public function set_options( $type, array $options ): void {
        $this->option_service->set_options( $type, $options );
        $this->options = $this->option_service->get_options();
    }

    /**
     * Update and persist the plugin options array in a single, centralized place.
     * Use this instead of calling update_option('racketmanager', ...) directly.
     *
     * Usage:
     *   global $racketmanager;
     *   $options = $racketmanager->get_options();
     *   // mutate $options as needed...
     *   $racketmanager->update_plugin_options($options);
     */
    public function update_plugin_options(array $options): void {
        $this->option_service->update_plugin_options( $options );
        $this->options = $this->option_service->get_options();
    }

    /**
     * Resolve an asset URL, preferring a `.min` variant when not in debug and when it exists.
     * Provide a plugin-relative path like `dist/js/racketmanager.js`.
     */
    protected function get_asset_url(string $relative_path, bool $prefer_min = true): string {
        return $this->asset_manager->get_asset_url( $relative_path, $prefer_min );
    }

    /**
     * Load JavaScript
     */
    public function load_scripts(): void {
        $this->asset_manager->load_frontend_scripts( $this->currency_code );
    }

    /**
     * Load CSS styles
     */
    public function load_styles(): void {
        $this->asset_manager->load_frontend_styles();
    }
    /**
     * Set the locale info function
     *
     * @return void
     */
    public function racketmanager_locale(): void {
        setlocale( LC_ALL, get_locale() );
        $this->currency_fmt  = numfmt_create( get_locale(), NumberFormatter::CURRENCY );
        $locale_info         = localeconv();
        $this->currency_code = isset( $locale_info['int_curr_symbol'] ) ? trim( $locale_info['int_curr_symbol'] ) : 'GBP';
    }
    /**
     * Add html content type to the mail header
     *
     * @param array $args arguments for mail message.
     *
     * @return array args
     */
    public function racketmanager_mail( array $args ): array {
        return $this->resolve_service( 'mail_filter_service' )->filter_mail( $args );
    }

    /**
     * Change email address
     *
     * @param array $email_change email change message.
     * @param array $user original user details (not used).
     * @param array $user_data new user details.
     *
     * @return array
     */
    public function racketmanager_change_email_address( array $email_change, array $user, array $user_data ): array {
        $vars['site_name']       = $this->site_name;
        $vars['site_url']        = $this->site_url;
        $vars['user_login']      = $user_data['user_login'];
        $vars['display_name']    = $user['display_name'];
        $vars['email_link']      = $this->admin_email;
        $email_change['message'] = $this->shortcodes->load_template( 'email-email-change', $vars, 'email' );
        return $email_change;
    }
    /**
     * Redirect users on certain pages to the login function
     */
    public function redirect_to_login(): void {
        if ( ! is_user_logged_in() ) {
            $redirect_page = $_SERVER['REQUEST_URI'] ?? null; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $slug          = get_post_field( 'post_name' );
            switch ( $slug ) {
                case 'member-account':
                case 'tournament-entry':
                case 'league-entry':
                case 'cup-entry':
                case 'entry':
                case 'payment':
                case 'payment-complete':
                    wp_safe_redirect( wp_login_url( $redirect_page ) );
                    exit;
                case 'match':
                    $action = get_query_var( 'action' );
                    if ( 'result' === $action ) {
                        wp_safe_redirect( wp_login_url( $redirect_page ) );
                        exit;
                    }
                    break;
                default:
                    break;
            }
        }
    }

    /**
     * Set message
     *
     * @param string $message message.
     * @param boolean|string $error triggers error message if true.
     */
    public function set_message( string $message, bool|string $error = false ): void {
        if ( true === $error ) {
            $this->error = 'error';
        } elseif ( 'warning' === $error ) {
            $this->error = 'warning';
        } elseif ( 'info' === $error ) {
            $this->error = 'info';
        } elseif ( 'error' === $error ) {
            $this->error = 'error';
        } elseif ( 'danger' === $error ) {
            $this->error = 'danger';
        } else {
            $this->error = false;
        }
        $this->message = $message;
    }

    /**
     * Get season
     *
     * @param array $args query arguments.
     *
     * @return object|false
     */
    public function get_season( array $args = array() ): false|object {
        $id   = $args['id'] ?? false;
        $name = $args['name'] ?? false;
        if ( ! $id && ! $name ) {
            return false;
        }
        $repo   = $this->resolve_service( 'season_repository' );
        $season = $id ? $repo->find_by_id( (int) $id, 'id' ) : $repo->find_by_id( $name, 'name' );
        if ( ! $season ) {
            return false;
        }
        if ( $id && $name && $season->get_name() !== (string) $name ) {
            return false;
        }
        return (object) array(
            'id'   => $season->get_id(),
            'name' => $season->get_name(),
        );
    }

    /**
     * Get clubs from the database
     *
     * @param array $args query arguments.
     *
     * @return array|int
     */
    public function get_clubs( array $args = array() ): array|int {
        $args = array_merge( array( 'type' => false ), $args );
        return $this->resolve_service( 'club_repository' )->find_all( $args );
    }

    /**
     * Get events from the database
     *
     * @param array $args query arguments.
     *
     * @return array
     */
    public function get_events( array $args = array() ): array {
        return $this->resolve_service( 'event_repository' )->find_all( $args );
    }
    /**
     * Get leagues from the database
     *
     * @param array $args query arguments.
     *
     * @return array
     */
    public function get_leagues( array $args = array() ): array {
        return $this->resolve_service( 'league_repository' )->find_all( $args );
    }
    /**
     * Get Team ID for a given string
     *
     * @param string $title title.
     *
     * @return int
     */
    public function get_team_id( string $title ): int {
        return (int) ( $this->resolve_service( 'team_repository' )->find_id_by_title( $title ) ?? 0 );
    }

    /**
     * Check to see if a player is in a club (based on a team)
     *
     * @param array $args query arguments.
     *
     * @return bool
     */
    public function is_player_in_club( array $args ): bool {
        return $this->resolve_service( 'club_repository' )->is_player_in_club( $args );
    }
    /**
     * Get a list of players
     *
     * @param array $args query arguments.
     *
     * @return array
     */
    public function get_all_players( array $args = array() ): array {
        $repo = $this->resolve_service( 'player_repository' );
        if ( ! empty( $args['active'] ) ) {
            return $repo->get_active_rubber_players();
        }
        return $repo->find_all( $args );
    }

    /**
     * Get player name
     *
     * @param int $player_id player id.
     *
     * @return string | false
     */
    public function get_player_name( int $player_id ): false|string {
        $player = $this->resolve_service( 'player_repository' )->find( $player_id );
        if ( ! $player ) {
            return false;
        }

        return $player->display_name;
    }

    /**
     * Get matches without using a league object
     *
     * @param array $match_args query arguments.
     *
     * @return array|int $matches
     */
    public function get_matches( array $match_args ): array|int {
        return $this->resolve_service( 'fixture_repository' )->find_all( $match_args );
    }

    /**
     * Show winners
     *
     * @param string $season season.
     * @param int $competition_id competition id.
     * @param string $competition_type competition type.
     * @param boolean $group_by group by type.
     *
     * @return array|false of winners|false.
     */
    public function get_winners( string $season, int $competition_id, string $competition_type = 'tournament', bool $group_by = false ): false|array {
        return $this->resolve_service( 'competition_repository' )->get_winners( $season, $competition_id, $competition_type, $group_by );
    }

    /**
     * Get confirmation email
     *
     * @param string $type type of confirmation email.
     *
     * @return string $email
     */
    public function get_confirmation_email( string $type ): string {
        $options = $this->get_options();
        return $options[$type]['resultConfirmationEmail'] ?? '';
    }

    /**
     * Get from line for email
     *
     * @return string from line
     */
    public function get_from_user_email(): string {
        return 'From: ' . wp_get_current_user()->display_name . ' <' . $this->admin_email . '>';
    }
    /**
     * User favourite
     *
     * @param string $type type of favourite.
     * @param int $id id of favourite.
     *
     * @return boolean true/false
     */
    public function is_user_favourite( string $type, int $id ): bool {
        if ( ! is_user_logged_in() ) {
            return false;
        }
        $user_id         = get_current_user_id();
        $meta_key        = 'favourite-' . $type;
        $favourites      = get_user_meta( $user_id, $meta_key );
        $favourite_found = array_search( strval( $id ), $favourites, true );
        if ( is_numeric( $favourite_found ) ) {
            return true;
        }
        return false;
    }
    /**
     * Gets a result checker from the database
     *
     * @param array $args query arguments.
     *
     * @return array|int
     */
    public function get_result_warnings( array $args = array() ): array|int {
        return $this->resolve_service( 'results_checker_repository' )->find_all( $args );
    }

    /**
     * Get teams from the database
     *
     * @param array $args search arguments.
     * @return array|int
     */
    public function get_teams( array $args = array() ): array|int {
        return $this->resolve_service( 'team_repository' )->find_all( $args );
    }

    /**
     * Validate player
     *
     * @param array|null $input Input data, defaults to $_POST when null.
     * @return array
     */
    public function validate_player( ?array $input = null ): array {
        $service = $this->resolve_service( 'player_validation_service' );
        return $service->validate_player_details( $input );
    }
}
