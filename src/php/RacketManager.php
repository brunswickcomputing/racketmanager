<?php
/**
 * RacketManager API: RacketManager class
 *
 * @author Paul Moffat
 * @package RacketManager
 */

namespace Racketmanager;

use NumberFormatter;
use Racketmanager\Ajax\Ajax_Account;
use Racketmanager\Ajax\Ajax_Club;
use Racketmanager\Ajax\Ajax_Finance;
use Racketmanager\Ajax\Ajax_Frontend;
use Racketmanager\Ajax\Ajax_Match;
use Racketmanager\Ajax\Ajax_Tournament;
use Racketmanager\Domain\Message;
use Racketmanager\Rest\Rest_Routes;
use Racketmanager\Services\Competition_Entry_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Login;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Rewrites;
use Racketmanager\Services\Validator\Validator;
use Racketmanager\Services\Container\Container_Bootstrap;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Public\Shortcodes;
use Racketmanager\Public\Shortcodes_Club;
use Racketmanager\Public\Shortcodes_Competition;
use Racketmanager\Public\Shortcodes_Email;
use Racketmanager\Public\Shortcodes_Event;
use Racketmanager\Public\Shortcodes_League;
use Racketmanager\Public\Shortcodes_Login;
use Racketmanager\Public\Shortcodes_Match;
use Racketmanager\Public\Shortcodes_Message;
use Racketmanager\Public\Shortcodes_Tournament;
use Racketmanager\Util\Util;
use stdClass;

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
    protected array $templates;
    /**
     * Site name.
     *
     * @var string $site_name
     */
    public string $site_name;
    /**
     * Message.
     *
     * @var string $message
     */
    public string $message;
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
    public array $options;
    /**
     * Date format.
     *
     * @var string $date_format
     */
    public string $date_format;
    /**
     * Time format.
     *
     * @var string $time_format
     */
    public string $time_format;
    /**
     * Admin email.
     *
     * @var string $admin_email
     */
    public string $admin_email;
    /**
     * Site url.
     *
     * @var string $site_url
     */
    public string $site_url;
    /**
     * Seasons.
     *
     * @var array $seasons
     */
    public array $seasons;
    /**
     * Currency code
     *
     * @var string
     */
    public string $currency_code;
    /**
     * Currency format
     *
     * @var NumberFormatter|null
     */
    public ?NumberFormatter $currency_fmt;
    public object $ajax;
    public object $ajax_account;
    public object $ajax_club;
    public object $ajax_finance;
    public object $ajax_frontend;
    public object $ajax_match;
    public object $ajax_tournament ;
    public object $login;
    public object $shortcodes;
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
    private Competition_Service $competition_service;
    private Competition_Entry_Service $competition_entry_service;
    private Finance_Service $finance_service;
    private Player_Service $player_service;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct() {
        global $wpdb;
        if ( empty( $this->options ) ) {
            $wpdb->show_errors();
            $this->load_options();
            $this->load_libraries();

            // Boot the dependency injection container and register services.
            $this->container = Container_Bootstrap::boot( $this );
            // Resolve commonly used services from the container.
            $this->competition_entry_service = $this->container->get( 'competition_entry_service' );
            $this->competition_service       = $this->container->get( 'competition_service' );
            $this->finance_service           = $this->container->get( 'finance_service' );
            $this->player_service            = $this->container->get( 'player_service' );

            add_action( 'widgets_init', array( &$this, 'register_widget' ) );
            add_action( 'init', array( &$this, 'racketmanager_locale' ) );
            add_action( 'init', array( &$this, 'init_components' ) );
            add_action( 'init', array( &$this, 'load_shortcodes' ) );
            add_action( 'wp_enqueue_scripts', array( &$this, 'load_styles' ), 5 );
            add_action( 'wp_enqueue_scripts', array( &$this, 'load_scripts' ) );
            add_action( 'rm_resultPending', array( &$this, 'chase_pending_results' ), 1 );
            add_action( 'rm_confirmationPending', array( &$this, 'chase_pending_approvals' ), 1 );
            add_action( 'wp_loaded', array( &$this, 'add_racketmanager_templates' ) );
            add_action( 'template_redirect', array( &$this, 'redirect_to_login' ) );
            add_filter( 'wp_privacy_personal_data_exporters', array( &$this, 'racketmanager_register_exporter' ) );
            add_filter( 'wp_mail', array( &$this, 'racketmanager_mail' ) );
            add_filter( 'email_change_email', array( &$this, 'racketmanager_change_email_address' ), 10, 3 );
            add_filter( 'pre_get_document_title', array( &$this, 'set_page_title' ), 999 );
            add_action( 'rm_calculate_player_ratings', array( &$this, 'calculate_player_ratings' ), 1 );
            add_action( 'rm_calculate_tournament_ratings', array( &$this, 'calculate_tournament_ratings' ), 1 );
            add_action( 'rm_calculate_team_ratings', array( $this->competition_service, 'calculate_team_ratings' ), 10, 3 );
            add_action( 'rm_notify_team_entry_open', array( $this->competition_entry_service, 'notify_team_entry_open' ), 10, 2 );
            add_action( 'rm_notify_team_entry_reminder', array( $this->competition_entry_service, 'notify_team_entry_reminder' ), 10, 2 );
            add_action( 'rm_notify_tournament_entry_open', array( $this->competition_entry_service, 'notify_tournament_entry_open' ) );
            add_action( 'rm_notify_tournament_entry_reminder', array( $this->competition_entry_service, 'notify_tournament_entry_open_reminder' ) );
            add_action( 'rm_notify_tournament_finalists', array( &$this, 'notify_tournament_finalists' ) );
            add_action( 'rm_send_invoices', array( $this->finance_service, 'send_invoices' ) );
        }
        self::$instance = $this;
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
     * Set page title function
     *
     * @param string $title title.
     *
     * @return string new title
     */
    public function set_page_title( string $title ): string {
        global $wp;
        $slug        = get_post_field( 'post_name' );
        $site_name   = $this->site_name;
        $event       = isset( $wp->query_vars['event'] ) && is_string( $wp->query_vars['event'] ) ? ucwords( un_seo_url( $wp->query_vars['event'] ) ) : '';
        $season      = isset( $wp->query_vars['season'] ) ? ucwords( un_seo_url( $wp->query_vars['season'] ) ) : '';
        $club        = isset( $wp->query_vars['club_name'] ) ? ucwords( un_seo_url( $wp->query_vars['club_name'] ) ) : '';
        $player      = isset( $wp->query_vars['player_id'] ) ? ucwords( un_seo_url( $wp->query_vars['player_id'] ) ) : '';
        $competition = isset( $wp->query_vars['competition_name'] ) ? ucwords( un_seo_url( $wp->query_vars['competition_name'] ) ) : '';
        $tournament  = isset( $wp->query_vars['tournament'] ) ? ucwords( un_seo_url( $wp->query_vars['tournament'] ) ) : '';
        $type        = isset( $wp->query_vars['competition_type'] ) ? ucwords( un_seo_url( $wp->query_vars['competition_type'] ) ) : '';
        if ( 'player' === $slug && $player ) {
            $title = $player . ' - ' . $site_name;
        }
        if ( 'event' === $slug ) {
            if ( $season ) {
                $event .= ' ' . $season;
            }
            if ( $player ) {
                $title = $player . ' - ' . $event . ' - ' . $site_name;
            } elseif ( $club ) {
                $title = $club . ' - ' . $event . ' - ' . $site_name;
            } else {
                $title = $event . ' - ' . $site_name;
            }
        }
        if ( 'competitions' === $slug ) {
            $type = isset( $wp->query_vars['type'] ) ? ucwords( un_seo_url( $wp->query_vars['type'] ) ) : '';
            if ( $type ) {
                $title = $type . ' ' . __( 'List', 'racketmanager' );
            } else {
                $title = __( 'Competitions', 'racketmanager' );
            }
            if ( $club ) {
                $title .= ' - ' . $club;
            }
            $title .= ' - ' . $site_name;
        }
        if ( 'competition' === $slug ) {
            $competition = isset( $wp->query_vars['competition'] ) ? ucwords( un_seo_url( $wp->query_vars['competition'] ) ) : '';
            if ( $competition ) {
                $title = $competition . ' ';
            } else {
                $title = __( 'Competition', 'racketmanager' );
            }
            if ( $season ) {
                $title .= ' ' . $season;
            }
            if ( $club ) {
                $title .= ' - ' . $club;
            }
            $title .= ' - ' . $site_name;
        }
        if ( 'latest-results' === $slug ) {
            if ( $type ) {
                /* translators: %s: competition type */
                $title = sprintf( __( 'Latest %s Results', 'racketmanager' ), $type );
            } else {
                $title = __( 'Latest results', 'racketmanager' );
            }
            if ( $competition ) {
                $title .= ' - ' . $competition;
            }
            $title .= ' - ' . $site_name;
        }
        if ( 'cup' === $slug ) {
            $title = $event;
            if ( $season ) {
                $title .= ' - ' . $season;
            }
            $title .= ' - ' . $site_name;
        }
        if ( 'league' === $slug ) {
            $league = isset( $wp->query_vars['league_name'] ) ? ucwords( un_seo_url( $wp->query_vars['league_name'] ) ) : '';
            $team   = isset( $wp->query_vars['team'] ) ? ucwords( un_seo_url( $wp->query_vars['team'] ) ) : '';
            if ( $season ) {
                $league .= ' - ' . $season;
            }
            if ( $team ) {
                $title = $team . ' - ' . $league . ' - ' . $site_name;
            } else {
                $title = $league . ' - ' . $site_name;
            }
        }
        if ( 'team' === $slug ) {
            $team = isset( $wp->query_vars['team'] ) ? ucwords( un_seo_url( $wp->query_vars['team'] ) ) : '';
            if ( $team ) {
                $title = $team;
            }
            if ( $competition ) {
                $title .= ' - ' . $competition;
            }
            if ( $event ) {
                $title .= ' - ' . $event;
            }
            $title .= ' - ' . $site_name;
        }
        if ( 'match' === $slug ) {
            $league    = isset( $wp->query_vars['league_name'] ) ? ucwords( un_seo_url( $wp->query_vars['league_name'] ) ) : '';
            $team_home = isset( $wp->query_vars['teamHome'] ) ? ucwords( un_seo_url( $wp->query_vars['teamHome'] ) ) : '';
            $team_away = isset( $wp->query_vars['teamAway'] ) ? ucwords( un_seo_url( $wp->query_vars['teamAway'] ) ) : '';
            if ( $season ) {
                $league .= ' - ' . $season;
            }
            if ( $team_home && $team_away ) {
                $title = $team_home . ' ' . __( 'vs', 'racketmanager' ) . ' ' . $team_away . ' - ';
            } else {
                $title .= __( 'Match', 'racketmanager' ) . ' - ';
            }
            if ( $league ) {
                $title .= $league . ' - ';
            }
            if ( $tournament ) {
                $title .= $tournament . ' - ';
            }
            $title .= $site_name;
        }
        if ( 'entry' === $slug ) {
            $title = __( 'Entry Form', 'racketmanager' );
            if ( $competition ) {
                $title .= ' - ' . $competition;
            } elseif ( $tournament ) {
                $title .= ' - ' . $tournament . ' - ' . __( 'Tournament', 'racketmanager' );
                if ( $player ) {
                    $title .= ' - ' . $player;
                }
            }
            if ( $season ) {
                $title .= ' - ' . $season;
            }
            if ( $club ) {
                $title .= ' - ' . $club;
            }
            $title .= ' - ' . $site_name;
        }
        if ( 'tournament' === $slug ) {
            $tournament = isset( $wp->query_vars['tournament'] ) ? ucwords( un_seo_url( $wp->query_vars['tournament'] ) ) : __( 'Latest', 'racketmanager' );
            $draw       = isset( $wp->query_vars['draw'] ) ? ucwords( un_seo_url( $wp->query_vars['draw'] ) ) : '';
            $player     = isset( $wp->query_vars['player'] ) ? ucwords( un_seo_url( $wp->query_vars['player'] ) ) : '';
            $tab        = isset( $wp->query_vars['tab'] ) ? ucwords( un_seo_url( $wp->query_vars['tab'] ) ) : '';
            $title      = '';
            if ( $player ) {
                $title .= $player . ' - ' . __( 'Player', 'racketmanager' ) . ' - ';
            }
            if ( $draw ) {
                $title .= $draw . ' ' . __( 'Draw', 'racketmanager' ) . ' - ';
            }
            if ( $event ) {
                $title .= $event . ' ' . __( 'Event', 'racketmanager' ) . ' - ';
            }
            if ( 'matches' === $tab ) {
                $title .= __( 'Matches', 'racketmanager' ) . ' - ';
            }
            $title .= $tournament . ' - ' . __( 'Tournament', 'racketmanager' );
            $title .= ' - ' . $site_name;
        }
        if ( 'club' === $slug ) {
            if ( $club ) {
                $title = $club;
            } else {
                $title = __( 'Clubs', 'racketmanager' );
            }
            $title .= ' - ' . $site_name;
        }
        if ( 'invoices' === $slug ) {
            $invoice = isset( $wp->query_vars['invoice'] ) ? ucwords( un_seo_url( $wp->query_vars['invoice'] ) ) : null;
            if ( $invoice ) {
                $title = __( 'Invoice', 'racketmanager' ) . ' - ' . $invoice;
            } else {
                $title = __( 'Invoices', 'racketmanager' );
            }
            if ( $club ) {
                $title .= ' - ' . $club;
            }
            $title .= ' - ' . $site_name;
        }
        if ( 'roles' === $slug ) {
            $title = __( 'Roles', 'racketmanager' );
            if ( $club ) {
                $title .= ' - ' . $club;
            }
            $title .= ' - ' . $site_name;
        }
        if ( 'players' === $slug ) {
            if ( $player ) {
                $title = $player;
            } else {
                $title = __( 'Players', 'racketmanager' );
            }
            if ( $club ) {
                $title .= ' - ' . $club;
            }
            $title .= ' - ' . $site_name;
        }
        return $title;
    }
    /**
     * Chase pending results
     *
     * @param string $competition Competition type.
     *
     * @return void
     */
    public function chase_pending_results( string $competition = 'league' ): void {
        $result_pending                 = $this->get_options( $competition )['resultPending'];
        $result_penalty                 = $this->get_options( $competition )['resultPenalty'];
        $result_timeout                 = $this->get_options( $competition )['resultTimeout'];
        $match_args                     = array();
        $match_args['time']             = 'outstanding';
        $match_args['competition_type'] = $competition;
        $match_args['orderby']          = array(
            'date' => 'ASC',
            'id'   => 'ASC',
        );
        $match_args['timeOffset']       = $result_pending;
        $matches                        = $this->get_matches( $match_args );
        foreach ( $matches as $match ) {
            $match->chase_match_result( $result_pending, $result_timeout, $result_penalty );
        }
    }


    /**
     * Chase pending approvals
     *
     * @param string $competition Competition type.
     *
     * @return void
     */
    public function chase_pending_approvals( string $competition = 'league' ): void {
        $confirmation_timeout           = $this->get_options( $competition )['confirmationTimeout'];
        $match_args                     = array();
        $match_args['confirmed']        = 'true';
        $match_args['competition_type'] = $competition;
        $match_args['orderby']          = array(
            'date' => 'ASC',
            'id'   => 'ASC',
        );
        $match_args['timeOffset']       = $confirmation_timeout;
        $matches                        = $this->get_matches( $match_args );
        foreach ( $matches as $match ) {
            $match->complete_result( $confirmation_timeout );
        }
        $confirmation_required  = $this->get_options( $competition )['confirmationRequired'];
        if ( $confirmation_required ) {
            $confirmation_pending           = $this->get_options( $competition )['confirmationPending'];
            $confirmation_penalty           = $this->get_options( $competition )['confirmationPenalty'];
            $confirmation_timeout           = $this->get_options( $competition )['confirmationTimeout'];
            $match_args                     = array();
            $match_args['confirmed']        = 'true';
            $match_args['competition_type'] = $competition;
            $match_args['orderby']          = array(
                    'updated' => 'ASC',
                    'id'      => 'ASC',
            );
            $match_args['timeOffset']       = $confirmation_pending;
            $matches                        = $this->get_matches( $match_args );
            foreach ( $matches as $match ) {
                $match->chase_match_approval( $confirmation_pending, false, $confirmation_timeout, $confirmation_penalty );
            }
        }
    }
    /**
     * Calculate player ratings
     *
     * @param int|null $club_id club id.
     *
     * @return void
     */
    public function calculate_player_ratings( int $club_id = null ): void {
        // Delegate to the Player__Service implementation.
        $this->player_service->calculate_player_ratings( $club_id );
    }

    /**
     * Calculate tournament ratings
     *
     * @param int $tournament_id tournament id.
     *
     * @return void
     */
    public function calculate_tournament_ratings( int $tournament_id ): void {
        if ( $tournament_id ) {
            $tournament = get_tournament( $tournament_id );
            $tournament?->calculate_player_team_ratings();
        }
    }

    /**
     * Notify tournament finalists
     *
     * @param int $tournament_id tournament id.
     *
     * @return void
     */
    public function notify_tournament_finalists( int $tournament_id ): void {
        if ( $tournament_id ) {
            $tournament = get_tournament( $tournament_id );
            $tournament?->notify_finalists();
        }
    }

    /**
     * Get League standings function
     *
     * @param array $args array of query arguments.
     *
     * @return array
     */
    public function get_league_standings( array $args = array() ): array {
        global $wpdb;
        $defaults = array(
            'season'     => false,
            'team'       => false,
            'age_group'  => false,
        );
        $args      = array_merge( $defaults, $args );
        $season    = $args['season'];
        $team_id   = $args['team'];
        $age_group = $args['age_group'];
        $sql       = "SELECT l.id, t.`won_matches`,t.`rank` FROM $wpdb->racketmanager l, $wpdb->racketmanager_league_teams t WHERE l.`id` = t.`league_id` AND l.`id` IN (SELECT `id` FROM $wpdb->racketmanager WHERE `event_id` IN (SELECT e.`id` FROM $wpdb->racketmanager_events e, $wpdb->racketmanager_competitions c WHERE e.`competition_id` = c.`id` AND c.`type` = 'league'))";
        if ( $season ) {
            $sql .= $wpdb->prepare(
                ' AND t.`season` = %d',
                $season
            );
        }
        if ( $team_id ) {
            $sql .= $wpdb->prepare(
                ' AND t.`team_id` = %d',
                $team_id
            );
        }
        if ( $age_group ) {
            $sql .= $wpdb->prepare(
                    " AND l.`id` IN (SELECT `id` FROM $wpdb->racketmanager WHERE `event_id` IN (SELECT e.`id` FROM $wpdb->racketmanager_events e, $wpdb->racketmanager_competitions c WHERE e.`competition_id` = c.`id` AND `age_group` = %s))",
                    $age_group
            );
        }
        $sql             .= ' ORDER BY l.`id` ASC';
        $league_standings = wp_cache_get( md5( $sql ), 'league_standings' );
        if ( ! $league_standings ) {
            $league_standings = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            wp_cache_set( md5( $sql ), $league_standings, 'league_standings' );
        }
        return $league_standings;
    }
    /**
     * Adds our templates
     */
    public function add_racketmanager_templates(): void {
        // Add your templates to this array.
        $this->templates = array(
            'templates/page_template/template_no_title.php' => 'No Title',
            'templates/page_template/template_member_account.php' => 'Member Account',
        );

        // Add a filter to the wp 4.7 version attributes meta-box.
        add_filter( 'theme_page_templates', array( $this, 'racketmanager_templates_as_option' ) );

        // Add a filter to the save post to inject our template into the page cache.
        add_filter( 'wp_insert_post_data', array( $this, 'register_racketmanager_templates' ) );

        // Add a filter to the template include to determine if the page has our.
        // template assigned and return its path.
        add_filter( 'template_include', array( $this, 'racketmanager_load_template' ) );

        add_filter( 'archive_template', array( $this, 'racketmanager_archive_template' ) );
    }

    /**
     * Adds our templates to the page dropdown
     *
     * @param array $posts_templates array of post templates.
     */
    public function racketmanager_templates_as_option( array $posts_templates ): array {
        return array_merge( $posts_templates, $this->templates );
    }

    /**
     * Adds our templates to the pages cache in order to trick WordPress
     * into thinking the template file exists where it doesn't really exist.
     *
     * @param array $atts array of attributes.
     */
    public function register_racketmanager_templates( array $atts ): array {

        // Create the key used for the themes cache.
        $cache_key = 'page_templates-' . md5( get_theme_root() . '/' . get_stylesheet() );

        // Retrieve the cache list.
        // If it doesn't exist, or it's empty prepare an array.
        $page_templates = wp_get_theme()->get_page_templates();
        if ( empty( $page_templates ) ) {
            $page_templates = array();
        }

        // New cache, therefore remove the old one.
        wp_cache_delete( $cache_key, 'themes' );

        // Now add our template to the list of templates by merging our templates.
        // with the existing templates array from the cache.
        $page_templates = array_merge( $page_templates, $this->templates );

        // Add the modified cache to allow WordPress to pick it up for listing available templates.
        wp_cache_add( $cache_key, $page_templates, 'themes', 1800 );

        return $atts;
    }

    /**
     * Checks if the template is assigned to the page
     *
     * @param string $template template.
     */
    public function racketmanager_load_template( string $template ): string {

        // Get global post.
        global $post;

        // Return template if post is empty or if we don't have a custom one defined.
        if ( ! $post || ! isset( $this->templates[ get_post_meta( $post->ID, '_wp_page_template', true ) ] ) ) {
            return $template;
        }

        $file = RACKETMANAGER_PATH . get_post_meta( $post->ID, '_wp_page_template', true );

        // Just to be safe, we check if the file exist first.
        if ( file_exists( $file ) ) {
            return $file;
        } else {
            echo esc_html( $file );
        }

        // Return template.
        return $template;
    }

    /**
     * Load specific archive templates
     *
     * @param string $template template.
     */
    public function racketmanager_archive_template( string $template ): string {
        if ( is_category( 'rules' ) ) {
            $template = RACKETMANAGER_PATH . 'templates/pages/category-rules.php';
        }
        if ( is_category( 'how-to' ) ) {
            $template = RACKETMANAGER_PATH . 'templates/pages/category-how-to.php';
        }
        return $template;
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
     * Load libraries
     */
    private function load_libraries(): void {
        // PSR-4 autoloading is required from the main plugin bootstrap.
        // Load only sports registrar scripts (non-class files) so filters (e.g., racketmanager_sports) are registered.
        $plugin_sports = array_filter(
            $this->read_directory( RACKETMANAGER_PATH . 'src/php/sports' ),
            static function ( $file ): bool {
                $base = basename( $file );
                // Registrar scripts are lowercase like tennis.php; exclude class files like Competition_Tennis.php, League_Tennis.php
                return (bool) preg_match( '/^[a-z0-9\-]+\.php$/', $base );
            }
        );
        // Allow theme overrides/augmentations to continue working.
        $theme_sports = array_filter(
            $this->read_directory( get_stylesheet_directory() . '/Sports' ),
            static function ( $file ): bool {
                $base = basename( $file );
                return (bool) preg_match( '/^[a-z0-9\-]+\.php$/', $base );
            }
        );
        $registrars = array_values( array_unique( array_merge( $plugin_sports, $theme_sports ) ) );
        foreach ( $registrars as $file ) {
            require_once $file;
        }

        // template tags & functions.
        require_once RACKETMANAGER_PATH . '/template-tags.php';
        require_once RACKETMANAGER_PATH . '/functions.php';
        include_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    /**
     * Initialise components
     */
    public function init_components(): void {
        $this->ajax_account             = new Ajax_Account( $this );
        $this->ajax_club                = new Ajax_Club( $this );
        $this->ajax_finance             = new Ajax_Finance( $this );
        $this->ajax_frontend            = new Ajax_Frontend( $this );
        $this->ajax_match               = new Ajax_Match( $this );
        $this->ajax_tournament          = new Ajax_Tournament( $this );
        $this->shortcodes               = new Shortcodes( $this );
        $this->shortcodes_club          = new Shortcodes_Club( $this );
        $this->shortcodes_competition   = new Shortcodes_Competition( $this );
        $this->shortcodes_email         = new Shortcodes_Email( $this );
        $this->shortcodes_event         = new Shortcodes_Event( $this );
        $this->shortcodes_league        = new Shortcodes_League( $this );
        $this->shortcodes_login         = new Shortcodes_Login( $this );
        $this->shortcodes_match         = new Shortcodes_Match( $this );
        $this->shortcodes_message       = new Shortcodes_Message( $this );
        $this->shortcodes_tournament    = new Shortcodes_Tournament( $this );
        $this->rewrites                 = new Rewrites();
        $this->login                    = new Login();
        Rest_Routes::single( $this );
    }
    /**
     * Load shortcodes
     */
    public function load_shortcodes(): void {
        add_shortcode( 'dailymatches', array( $this->shortcodes, 'show_daily_matches' ) );
        add_shortcode( 'latest_results', array( $this->shortcodes, 'show_latest_results' ) );
        add_shortcode( 'players', array( $this->shortcodes, 'show_players' ) );
        add_shortcode( 'player', array( $this->shortcodes, 'show_player' ) );
        add_shortcode( 'favourites', array( $this->shortcodes, 'show_favourites' ) );
        add_shortcode( 'invoice', array( $this->shortcodes, 'show_invoice' ) );
        add_shortcode( 'purchase-order', array( $this->shortcodes, 'show_purchase_order' ) );
        add_shortcode( 'memberships', array( $this->shortcodes, 'show_memberships' ) );
        add_shortcode( 'search-players', array( $this->shortcodes, 'show_player_search' ) );
        add_shortcode( 'team-order', array( $this->shortcodes, 'show_team_order' ) );
        add_shortcode( 'show-alert', array( $this->shortcodes, 'show_alert' ) );

        add_shortcode( 'clubs', array( $this->shortcodes_club, 'show_clubs' ) );
        add_shortcode( 'club', array( $this->shortcodes_club, 'show_club' ) );
        add_shortcode( 'club-players', array( $this->shortcodes_club, 'show_club_players' ) );
        add_shortcode( 'club-roles', array( $this->shortcodes_club, 'show_club_roles' ) );
        add_shortcode( 'club-role-modal', array( $this->shortcodes_club, 'show_club_role_modal' ) );
        add_shortcode( 'club-competitions', array( $this->shortcodes_club, 'show_club_competitions' ) );
        add_shortcode( 'club-event', array( $this->shortcodes_club, 'show_club_event' ) );
        add_shortcode( 'club-team', array( $this->shortcodes_club, 'show_club_team' ) );
        add_shortcode( 'club-invoices', array( $this->shortcodes_club, 'show_club_invoices' ) );
        add_shortcode( 'team-edit', array( $this->shortcodes_club, 'show_team_edit_modal' ) );

        add_shortcode( 'competitions', array( $this->shortcodes_competition, 'show_competitions' ) );
        add_shortcode( 'competition', array( $this->shortcodes_competition, 'show_competition' ) );
        add_shortcode( 'competition-overview', array( $this->shortcodes_competition, 'show_competition_overview' ) );
        add_shortcode( 'competition-events', array( $this->shortcodes_competition, 'show_competition_events' ) );
        add_shortcode( 'competition-teams', array( $this->shortcodes_competition, 'show_competition_teams' ) );
        add_shortcode( 'competition-clubs', array( $this->shortcodes_competition, 'show_competition_clubs' ) );
        add_shortcode( 'competition-players', array( $this->shortcodes_competition, 'show_competition_players' ) );
        add_shortcode( 'competition-winners', array( $this->shortcodes_competition, 'show_competition_winners' ) );
        add_shortcode( 'competition-entry', array( $this->shortcodes_competition, 'show_competition_entry' ) );
        add_shortcode( 'competition-entry-payment', array( $this->shortcodes_competition, 'show_competition_entry_payment' ) );
        add_shortcode( 'competition-entry-payment-complete', array( $this->shortcodes_competition, 'show_competition_entry_payment_complete' ) );
        add_shortcode( 'event-dropdown', array( $this->shortcodes_competition, 'show_dropdown' ) );

        add_shortcode( 'match-notification', array( $this->shortcodes_email, 'show_match_notification' ) );
        add_shortcode( 'result-notification', array( $this->shortcodes_email, 'show_result_notification' ) );
        add_shortcode( 'result-notification-captain', array( $this->shortcodes_email, 'show_captain_result_notification' ) );
        add_shortcode( 'result-outstanding-notification', array( $this->shortcodes_email, 'show_result_outstanding_notification' ) );
        add_shortcode( 'club-player-notification', array( $this->shortcodes_email, 'show_club_player_notification' ) );
        add_shortcode( 'match_date_change_notification', array( $this->shortcodes_email, 'show_match_date_change_notification' ) );
        add_shortcode( 'withdrawn-team', array( $this->shortcodes_email, 'show_team_withdrawn' ) );
        add_shortcode( 'withdrawn-team-match', array( $this->shortcodes_email, 'show_withdrawn_team_match' ) );
        add_shortcode( 'event-constitution', array( $this->shortcodes_email, 'show_event_constitution' ) );

        add_shortcode( 'event', array( $this->shortcodes_event, 'show_event' ) );
        add_shortcode( 'event-standings', array( $this->shortcodes_event, 'show_event_standings' ) );
        add_shortcode( 'event-draw', array( $this->shortcodes_event, 'show_event_draw' ) );
        add_shortcode( 'event-matches', array( $this->shortcodes_event, 'show_event_matches' ) );
        add_shortcode( 'event-clubs', array( $this->shortcodes_event, 'show_event_clubs' ) );
        add_shortcode( 'event-teams', array( $this->shortcodes_event, 'show_event_teams' ) );
        add_shortcode( 'event-players', array( $this->shortcodes_event, 'show_event_players' ) );
        add_shortcode( 'event-partner', array( $this->shortcodes_event, 'show_event_partner' ) );
        add_shortcode( 'event-team-matches', array( $this->shortcodes_event, 'show_event_team_matches' ) );
        add_shortcode( 'team-order-players', array( $this->shortcodes_event, 'show_team_order_players' ) );
        add_shortcode( 'league-dropdown', array( $this->shortcodes_event, 'show_dropdown' ) );

        add_shortcode( 'championship', array( $this->shortcodes_league, 'show_championship' ) );
        add_shortcode( 'leaguearchive', array( $this->shortcodes_league, 'show_archive' ) );
        add_shortcode( 'standings', array( $this->shortcodes_league, 'show_standings' ) );
        add_shortcode( 'crosstable', array( $this->shortcodes_league, 'show_crosstable' ) );
        add_shortcode( 'matches', array( $this->shortcodes_league, 'show_matches' ) );
        add_shortcode( 'match', array( $this->shortcodes_league, 'show_match' ) );
        add_shortcode( 'teams', array( $this->shortcodes_league, 'show_teams' ) );
        add_shortcode( 'league-players', array( $this->shortcodes_league, 'show_league_players' ) );
        add_shortcode( 'season-dropdown', array( $this->shortcodes_league, 'show_season_dropdown' ) );
        add_shortcode( 'match-dropdown', array( $this->shortcodes_league, 'show_match_dropdown' ) );
        add_shortcode( 'last-5', array( $this->shortcodes_league, 'show_last_5' ) );

        add_shortcode( 'custom-login-form', array( $this->shortcodes_login, 'render_login_form' ) );
        add_shortcode( 'login-form', array( $this->shortcodes_login, 'login_form' ) );
        add_shortcode( 'custom-password-lost-form', array( $this->shortcodes_login, 'render_password_lost_form' ) );
        add_shortcode( 'custom-password-reset-form', array( $this->shortcodes_login, 'render_password_reset_form' ) );
        add_shortcode( 'account-info', array( $this->shortcodes_login, 'generate_member_account_form' ) );
        add_action( 'init', array( $this->shortcodes_login, 'load_translations' ) );

        add_shortcode( 'match-option', array( $this->shortcodes_match, 'show_match_option_modal' ) );
        add_shortcode( 'match-status', array( $this->shortcodes_match, 'show_match_status_modal' ) );
        add_shortcode( 'rubber-status', array( $this->shortcodes_match, 'show_rubber_status_modal' ) );
        add_shortcode( 'match-card', array( $this->shortcodes_match, 'show_match_card' ) );
        add_shortcode( 'score', array( $this->shortcodes_match, 'show_score' ) );
        add_shortcode( 'match-header', array( $this->shortcodes_match, 'show_match_header' ) );
        add_shortcode( 'match-detail', array( $this->shortcodes_match, 'show_match_detail' ) );

        add_shortcode( 'messages', array( $this->shortcodes_message, 'show_messages' ) );
        add_shortcode( 'show-message', array( $this->shortcodes_message, 'show_message' ) );

        add_shortcode( 'tournament', array( $this->shortcodes_tournament, 'show_tournament' ) );
        add_shortcode( 'tournament-overview', array( $this->shortcodes_tournament, 'show_tournament_overview' ) );
        add_shortcode( 'tournament-events', array( $this->shortcodes_tournament, 'show_events' ) );
        add_shortcode( 'tournament-draws', array( $this->shortcodes_tournament, 'show_draws' ) );
        add_shortcode( 'tournament-players', array( $this->shortcodes_tournament, 'show_tournament_players' ) );
        add_shortcode( 'tournament-winners', array( $this->shortcodes_tournament, 'show_tournament_winners' ) );
        add_shortcode( 'tournament-matches', array( $this->shortcodes_tournament, 'show_tournament_matches' ) );
        add_shortcode( 'tournament-match', array( $this->shortcodes_tournament, 'show_tournament_match' ) );
        add_shortcode( 'orderofplay', array( $this->shortcodes_tournament, 'show_order_of_play' ) );
        add_shortcode( 'latest-tournament', array( $this->shortcodes_tournament, 'show_latest_tournament' ) );
        add_shortcode( 'tournament-withdrawal', array( $this->shortcodes_tournament, 'show_tournament_withdrawal_modal' ) );
    }
    /**
     * Read files in directory
     *
     * @param string $dir directory name.
     *
     * @return array
     */
    public function read_directory( string $dir ): array {
        $files = array();

        if ( file_exists( $dir ) ) {
            $handle = opendir( $dir );
            do {
                $file      = readdir( $handle );
                $file_info = pathinfo( $dir . '/' . $file );
                $file_type = ( isset( $file_info['extension'] ) ) ? $file_info['extension'] : '';
                if ( '.' !== $file && '..' !== $file && ! is_dir( $file ) && !str_starts_with($file, '.') && 'php' === $file_type ) {
                    $files[ $file ] = $dir . '/' . $file;
                }
            } while ( false !== $file );
        }

        return $files;
    }

    /**
     * Load options
     */
    private function load_options(): void {
        $this->options     = get_option( 'racketmanager' );
        $this->date_format = get_option( 'date_format' );
        $this->time_format = get_option( 'time_format' );
        $this->site_name   = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
        $this->admin_email = get_option( 'admin_email' );
        $this->site_url    = get_option( 'siteurl' );
    }

    /**
     * Get options
     *
     * @param false|string $index index lookup (optional).
     */
    public function get_options( false|string $index = false ) {
        if ( $index ) {
            return $this->options[ $index ];
        } else {
            return $this->options;
        }
    }
    /**
     * Set options
     *
     * @param array $options.
     */
    public function set_options( $type, array $options ): void {
        $this->options[ $type ] = $options;
        $this->update_plugin_options($this->options);
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
        $this->options = $options;
        update_option( 'racketmanager', $this->options );
    }

    /**
     * Resolve an asset URL, preferring a `.min` variant when not in debug and when it exists.
     * Provide a plugin-relative path like `dist/js/racketmanager.js`.
     */
    protected function get_asset_url(string $relative_path, bool $prefer_min = true): string {
        $is_debug = ( defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ) || ( defined('WP_DEBUG') && WP_DEBUG );
        $base_url = RACKETMANAGER_URL . ltrim($relative_path, '/');

        if ( $prefer_min && ! $is_debug ) {
            // Insert .min before the extension
            $dot_pos = strrpos($relative_path, '.');
            if ( $dot_pos !== false ) {
                $min_relative = substr($relative_path, 0, $dot_pos) . '.min' . substr($relative_path, $dot_pos);
                $min_path = RACKETMANAGER_PATH . ltrim($min_relative, '/');
                if ( file_exists( $min_path ) ) {
                    return RACKETMANAGER_URL . ltrim($min_relative, '/');
                }
            }
        }
        return $base_url;
    }
    /**
     * Load Javascript
     */
    public function load_scripts(): void {
        $javascript_locale = str_replace( '_', '-', get_locale() );
        $module_handle     = 'racketmanager-module';
        // Use built bundle output by the JS build step (see package.json -> build)
        $module_src        = $this->get_asset_url('dist/js/racketmanager.js');

        // Enqueue jQuery UI dependencies FIRST
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-autocomplete');
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('jquery-ui-tooltip');
        wp_enqueue_script('jquery-effects-core');
        wp_enqueue_script('jquery-effects-slide');

        // Register a regular script JUST for the inline config
        wp_register_script(
                'racketmanager-config',
                false, // No source file
                [],
                RACKETMANAGER_VERSION,
                false // Load in header, BEFORE module
        );
        wp_enqueue_script('racketmanager-config');

        // Add ajax_var to the config script
        wp_add_inline_script(
                'racketmanager-config',
                'window.ajax_var = ' . wp_json_encode( array(
                        'url'        => admin_url( 'admin-ajax.php' ),
                        'ajax_nonce' => wp_create_nonce( 'ajax-nonce' ),
                ) ) . ';'
        );

        // Now register and enqueue your module AFTER the config
        wp_register_script_module( $module_handle, $module_src, ['jquery-ui-autocomplete'], RACKETMANAGER_VERSION );
        wp_enqueue_script_module( $module_handle );

        // Add locale config
        $config = [
                'currency' => $this->currency_code,
                'locale'   => $javascript_locale,
        ];
        wp_add_inline_script( 'racketmanager-config', 'window.locale_var = ' . wp_json_encode($config) . ';' );


        wp_enqueue_script( 'password-strength-meter' );        wp_localize_script( 'password-strength-meter', 'pwsL10n', array(
            'empty'    => __( 'But... it\'s empty!', 'theme-domain' ),
            'short'    => __( 'Too short!', 'theme-domain' ),
            'bad'      => __( 'Not even close!', 'theme-domain' ),
            'good'     => __( 'You are getting closer...', 'theme-domain' ),
            'strong'   => __( 'Now, that\'s a password!', 'theme-domain' ),
            'mismatch' => __( 'They are completely different, come on!', 'theme-domain' )
        ) );
        ?>
    <script type="text/javascript">
    //<![CDATA[
    RacketManagerAjaxL10n = {
        blogUrl: "<?php bloginfo( 'wpurl' ); ?>",
        pluginUrl: "<?php echo esc_url( RACKETMANAGER_URL ); ?>",
        requestUrl: "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>",
        Edit: "<?php esc_html_e( 'Edit', 'racketmanager' ); ?>",
        Post: "<?php esc_html_e( 'Post', 'racketmanager' ); ?>",
        Save: "<?php esc_html_e( 'Save', 'racketmanager' ); ?>",
        Cancel: "<?php esc_html_e( 'Cancel', 'racketmanager' ); ?>",
        pleaseWait: "<?php esc_html_e( 'Please wait...', 'racketmanager' ); ?>",
        Revisions: "<?php esc_html_e( 'Page Revisions', 'racketmanager' ); ?>",
        Time: "<?php esc_html_e( 'Insert time', 'racketmanager' ); ?>",
        Options: "<?php esc_html_e( 'Options', 'racketmanager' ); ?>",
        Delete: "<?php esc_html_e( 'Delete', 'racketmanager' ); ?>"
    }
    //]]>
    </script>
        <?php
    }

    /**
     * Load CSS styles
     */
    public function load_styles(): void {
        wp_enqueue_style( 'racketmanager-print', $this->get_asset_url('dist/css/print.css'), false, RACKETMANAGER_VERSION, 'print' );
        wp_enqueue_style( 'racketmanager-modal', $this->get_asset_url('dist/css/modal.css'), false, RACKETMANAGER_VERSION, 'screen' );
        wp_enqueue_style( 'racketmanager', $this->get_asset_url('dist/css/style.css'), false, RACKETMANAGER_VERSION, 'screen' );

        $jquery_ui_version = '1.13.2';
        wp_register_style( 'jquery-ui', RACKETMANAGER_URL . 'css/jquery/jquery-ui.min.css', false, $jquery_ui_version );
        wp_register_style( 'jquery-ui-structure', RACKETMANAGER_URL . 'css/jquery/jquery-ui.structure.min.css', array( 'jquery-ui' ), $jquery_ui_version );
        wp_register_style( 'jquery-ui-theme', RACKETMANAGER_URL . 'css/jquery/jquery-ui.theme.min.css', array( 'jquery-ui', 'jquery-ui-structure' ), $jquery_ui_version );
        wp_register_style( 'jquery-ui-autocomplete', RACKETMANAGER_URL . 'css/jquery/jquery-ui.autocomplete.min.css', array( 'jquery-ui', 'jquery-ui-autocomplete' ), $jquery_ui_version );

        wp_enqueue_style( 'jquery-ui-structure' );
        wp_enqueue_style( 'jquery-ui-theme' );
    }
    /**
     * Set locale info function
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
     * Add html content type to mail header
     *
     * @param array $args arguments for mail message.
     *
     * @return array args
     */
    public function racketmanager_mail( array $args ): array {
        $headers = $args['headers'];
        if ( ! $headers ) {
            $headers = array();
        } elseif ( ! is_array( $headers ) ) {
            $temp_headers = explode( "\n", str_replace( "\r\n", "\n", $headers ) );
            $headers      = $temp_headers;
        }
        $headers[]       = 'Content-Type: text/html; charset=UTF-8';
        $args['headers'] = $headers;
        $subject         = $args['subject'];
        $message         = $args['message'];
        $headers         = $args['headers'];
        if ( is_array( $args['to'] ) ) {
            $to = $args['to'];
        } else {
            $to = explode( ',', $args['to'] );
        }
        $cc       = array();
        $bcc      = array();
        $reply_to = array();
        foreach ( $headers as $header ) {
            if ( ! str_contains( $header, ':' ) ) {
                continue;
            }
            // Explode them out.
            list( $name, $content ) = explode( ':', trim( $header ), 2 );

            // Cleanup crew.
            $name    = trim( $name );
            $content = trim( $content );

            switch ( strtolower( $name ) ) {
                // Mainly for legacy -- process a "From:" header if it's there.
                case 'from':
                    $from        = $content;
                    break;
                case 'content-type':
                    break;
                case 'cc':
                    $cc = array_merge( (array) $cc, explode( ',', $content ) );
                    break;
                case 'bcc':
                    $bcc = array_merge( (array) $bcc, explode( ',', $content ) );
                    break;
                case 'reply-to':
                    $reply_to = array_merge( (array) $reply_to, explode( ',', $content ) );
                    break;
                default:
                    // Add it to our grand headers array.
                    $headers[ trim( $name ) ] = trim( $content );
                    break;
            }
        }
        if ( ! empty( $from ) ) {
            $address_headers = compact( 'to', 'cc', 'bcc' );
            foreach ( $address_headers as $addresses ) {
                if ( empty( $addresses ) ) {
                    continue;
                }
                foreach ( $addresses as $address ) {
                    if ( preg_match( '/(.*)<(.+)>/', $address, $matches ) && 3 === count( $matches ) ) {
                        $address = $matches[2];
                    }
                    $user = get_user_by( 'email', $address );
                    if ( $user ) {
                        $message_object                 = new stdClass();
                        $message_object->subject        = $subject;
                        $message_object->userid         = $user->ID;
                        $message_object->date           = current_time( 'mysql', false );
                        $message_object->message_object = $message;
                        $message_object->sender         = $from;
                        $message_object->status         = 1;
                        new Message( $message_object );
                    }
                }
            }
        }
        return $args;
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
     * Redirect users on certain pages to login function
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
     * Get seasons
     *
     * @param string $order sort order.
     *
     * @return array
     */
    public function get_seasons( string $order = 'ASC' ): array {
        global $wpdb;

        $order_by_string = '`name` ' . $order;
        $order_by        = $order_by_string;
        $seasons         = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            "SELECT `name`, `id` FROM $wpdb->racketmanager_seasons ORDER BY $order_by"
        );
        $i = 0;
        foreach ( $seasons as $season ) {
            $seasons[ $i ]->id   = $season->id;
            $seasons[ $i ]->name = stripslashes( $season->name );

            $this->seasons[ $season->id ] = $seasons[ $i ];
            ++$i;
        }
        return $seasons;
    }

    /**
     * Get season
     *
     * @param array $args query arguments.
     *
     * @return object|false
     */
    public function get_season( array $args = array() ): false|object {
        global $wpdb;

        $defaults = array(
            'id'   => false,
            'name' => false,
        );
        $args     = array_merge( $defaults, $args );
        $id       = $args['id'];
        $name     = $args['name'];

        $search_terms = array();
        if ( $id ) {
            $search_terms[] = $wpdb->prepare( '`id` = %d', intval( $id ) );
        }
        if ( $name ) {
            $search_terms[] = $wpdb->prepare( '`name` = %s', $name );
        }
        $search = Util::search_string( $search_terms, true );
        $sql    = "SELECT `id`, `name` FROM $wpdb->racketmanager_seasons $search ORDER BY `name`";

        $season = wp_cache_get( md5( $sql ), 'seasons' );
        if ( ! $season ) {
            $season = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql
            );
            wp_cache_set( md5( $sql ), $season, 'seasons' );
        }

        if ( ! isset( $season[0] ) ) {
            return false;
        }

        return $season[0];
    }

    /**
     * Get tournaments from database
     *
     * @param array $args query arguments.
     *
     * @return array
     */
    public function get_tournaments( array $args = array() ): array {
        global $wpdb;
        $defaults       = array(
            'offset'         => 0,
            'limit'          => 99999999,
            'competition_id' => false,
            'season'         => false,
            'name'           => false,
            'entry_open'     => false,
            'open'           => false,
            'active'         => false,
            'age_group'      => false,
            'orderby'        => array( 'name' => 'DESC' ),
        );
        $args           = array_merge( $defaults, $args );
        $offset         = $args['offset'];
        $limit          = $args['limit'];
        $competition_id = $args['competition_id'];
        $season         = $args['season'];
        $entry_open     = $args['entry_open'];
        $open           = $args['open'];
        $active         = $args['active'];
        $age_group      = $args['age_group'];
        $orderby        = $args['orderby'];

        $search_terms = array();

        if ( $competition_id ) {
            $search_terms[] = $wpdb->prepare( '`competition_id` = %s', $competition_id );
        }
        if ( $season ) {
            $search_terms[] = $wpdb->prepare( '`season` = %s', $season );
        }
        if ( $entry_open ) {
            $search_terms[] = '`date_closing` >= CURDATE()';
        }
        if ( $open ) {
            $search_terms[] = "(`date` >= CURDATE() OR `date` = '0000-00-00')";
        }
        if ( $active ) {
            $search_terms[] = '`date` >= CURDATE() AND `date_start` <= CURDATE()';
        }
        if ( $age_group ) {
            $search_terms[] = $wpdb->prepare(" `competition_id` in (select `id` from $wpdb->racketmanager_competitions WHERE `age_group` = %s)", $age_group );
        }
        $search = Util::search_string( $search_terms, true );
        $order  = Util::order_by_string( $orderby );
        $sql    = $wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            "SELECT `id` FROM $wpdb->racketmanager_tournaments $search $order LIMIT %d, %d",
            intval( $offset ),
            intval( $limit )
        );
        $tournaments = wp_cache_get( md5( $sql ), 'tournaments' );
        if ( ! $tournaments ) {
            $tournaments = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql
            );
            wp_cache_set( md5( $sql ), $tournaments, 'tournaments' );
        }
        foreach ( $tournaments as $i => $tournament ) {
            $tournament = get_tournament( $tournament->id );

            $tournaments[ $i ] = $tournament;
        }
        return $tournaments;
    }
    /**
     * Get clubs from database
     *
     * @param array $args query arguments.
     *
     * @return array|int
     */
    public function get_clubs( array $args = array() ): array|int {
        global $wpdb;
        $defaults     = array(
            'offset'      => 0,
            'limit'       => 99999999,
            'type'        => false,
            'name'        => false,
            'count'       => false,
            'player_type' => false,
            'player'      => false,
            'club'        => false,
            'orderby' => 'asc',
        );
        $args         = array_merge( $defaults, $args );
        $offset       = $args['offset'];
        $limit        = $args['limit'];
        $type         = $args['type'];
        $count        = $args['count'];
        $orderby      = $args['orderby'];
        $player_type  = $args['player_type'];
        $player       = $args['player'];
        $club         = $args['club'];
        $search_terms = array();
        if ( $type && 'all' !== $type ) {
            if ( 'current' === $type ) {
                $search_terms[] = "`type` != 'past'";
            } else {
                $search_terms[] = $wpdb->prepare( '`type` = %s', $type );
            }
        }
        $search_terms[] = empty( $club ) ? '1 = 1' : $wpdb->prepare('`id` = %d', $club );
        if ( $player ) {
            switch ( $player_type ) {
                case 'secretary':
                    $search_terms[] = $wpdb->prepare( "`id` IN (SELECT `club_id` FROM $wpdb->racketmanager_club_roles WHERE `role_id` = 1 AND `user_id` = %d)", $player );
                    break;
                case 'captain':
                    $search_terms[] = $wpdb->prepare("(`id` IN (SELECT `club_id` FROM $wpdb->racketmanager_teams_events te, $wpdb->racketmanager_teams t WHERE `captain` = %d AND te.`team_id` = t.`id`) OR `id` IN (SELECT `club_id` FROM $wpdb->racketmanager_club_roles WHERE `role_id` = 1 AND `user_id` = %d))", $player, $player );
                    break;
                case 'player':
                    $search_terms[] = $wpdb->prepare("`id` IN (SELECT `club_id` FROM $wpdb->racketmanager_club_players cp WHERE `player_id` = %d AND `removed_date` IS NULL)", $player );
                    break;
                default:
                    break;
            }
        }
        $search = Util::search_string( $search_terms, true );
        switch ( $orderby ) {
            case 'asc':
                $order = '`name` ASC';
                break;
            case 'desc':
                $order = '`name` DESC';
                break;
            case 'rand':
                $order = 'RAND()';
                break;
            case 'menu_order':
                $order = '`id` ASC';
                break;
            default:
                break;
        }
        $order = empty( $order ) ? null : 'ORDER BY ' . $order;
        if ( $count ) {
            $sql = "SELECT COUNT(ID) FROM $wpdb->racketmanager_clubs $search";
            return $wpdb->get_var( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                    $sql
            );
        }

        $sql = $wpdb->prepare(
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                "SELECT `id` FROM $wpdb->racketmanager_clubs $search $order LIMIT %d, %d",
                intval( $offset ),
                intval( $limit )
        );

        $clubs = wp_cache_get( md5( $sql ), 'clubs' );
        if ( ! $clubs ) {
            $clubs = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                    $sql
            );
            wp_cache_set( md5( $sql ), $clubs, 'clubs' );
        }
        foreach ( $clubs as $i => $club ) {
            $club = get_club( $club->id );

            $clubs[ $i ] = $club;
        }
        return $clubs;
    }

    /**
     * Get events from database
     *
     * @param array $args query arguments.
     *
     * @return array
     */
    public function get_events( array $args = array() ): array {
        global $wpdb;

        $defaults         = array(
                'offset'           => 0,
                'limit'            => 99999999,
                'competition_type' => false,
                'entry_type'       => false,
                'name'             => false,
                'season'           => false,
                'reverse_rubbers'  => false,
                'orderby'          => array( 'name' => 'ASC' ),
        );
        $args             = array_merge( $defaults, $args );
        $offset           = $args['offset'];
        $limit            = $args['limit'];
        $competition_type = $args['competition_type'];
        $entry_type       = $args['entry_type'];
        $name             = $args['name'];
        $season           = $args['season'];
        $reverse_rubbers  = $args['reverse_rubbers'];
        $orderby          = $args['orderby'];
        $search_terms     = array();
        if ( $name ) {
            $name           = $wpdb->esc_like( stripslashes( $name ) ) . '%';
            $search_terms[] = $wpdb->prepare( '`name` like %s', $name );
        }
        if ( $competition_type ) {
            $search_terms[] = $wpdb->prepare( "`competition_id` in (select `id` from $wpdb->racketmanager_competitions WHERE `type` = %s)", $competition_type );
        }
        switch ( $entry_type ) {
            case 'team':
                $search_terms[] = "`competition_id` in (select `id` from $wpdb->racketmanager_competitions WHERE `type` in ( 'league', 'cup' ) )";
                break;
            case 'player':
                $search_terms[] = "`competition_id` in (select `id` from $wpdb->racketmanager_competitions WHERE `type` = 'tournament' )";
                break;
            default:
                break;
        }
        $search = Util::search_string( $search_terms, true );
        $order  = Util::order_by_string( $orderby );
        $sql    = $wpdb->prepare(
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                "SELECT `name`, `id`, `type`, `settings`, `seasons` FROM $wpdb->racketmanager_events $search $order LIMIT %d, %d",
                intval( $offset ),
                intval( $limit )
        );
        $events = wp_cache_get( md5( $sql ), 'events' );
        if ( ! $events ) {
            $events = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                    $sql
            );
            wp_cache_set( md5( $sql ), $events, 'events' );
        }
        foreach ( $events as $i => $event ) {
            $event = get_event( $event->id );
            if ( $event ) {
                if ( $season ) {
                    if ( ! empty( $event->get_season_by_name( $season ) ) ) {
                        $show_event = true;
                    } else {
                        $show_event = false;
                    }
                } else {
                    $show_event = true;
                }
                if ( $reverse_rubbers ) {
                    if ( $event->reverse_rubbers ) {
                        $show_event = false;
                    } else {
                        $show_event = true;
                    }
                }
            } else {
                $show_event = false;
            }
            if ( $show_event ) {
                $events[ $i ] = $event;
            } else {
                unset( $events[ $i ] );
            }
        }
        return $events;
    }
    /**
     * Get leagues from database
     *
     * @param array $args query arguments.
     *
     * @return array
     */
    public function get_leagues( array $args = array() ): array {
        global $wpdb;

        $defaults         = array(
                'offset'           => 0,
                'limit'            => 99999999,
                'competition_type' => false,
                'name'             => false,
                'season'           => false,
                'orderby'          => array( 'title' => 'ASC' ),
        );
        $args             = array_merge( $defaults, $args );
        $offset           = $args['offset'];
        $limit            = $args['limit'];
        $competition_type = $args['competition_type'];
        $name             = $args['name'];
        $season           = $args['season'];
        $orderby          = $args['orderby'];
        $search_terms     = array();
        if ( $name ) {
            $name           = $wpdb->esc_like( stripslashes( $name ) ) . '%';
            $search_terms[] = $wpdb->prepare( '`title` like %s', $name );
        }
        if ( $competition_type ) {
            $search_terms[] = $wpdb->prepare( "`event_id` in (select e.`id` from $wpdb->racketmanager_events e, $wpdb->racketmanager_competitions c WHERE e.`competition_id` = c.`id` AND c.`type` = %s)", $competition_type );
        }
        $search = Util::search_string( $search_terms, true );
        $order  = Util::order_by_string( $orderby );
        $sql    = $wpdb->prepare(
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                "SELECT `id` FROM $wpdb->racketmanager $search $order LIMIT %d, %d",
                intval( $offset ),
                intval( $limit )
        );
        $leagues = wp_cache_get( md5( $sql ), 'leagues' );
        if ( ! $leagues ) {
            $leagues = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                    $sql
            );
            wp_cache_set( md5( $sql ), $leagues, 'leagues' );
        }
        foreach ( $leagues as $i => $league ) {
            $league = get_league( $league->id );
            if ( $season ) {
                if ( $league->event->get_season_by_name( $season ) ) {
                    $leagues[ $i ] = $league;
                } else {
                    unset( $leagues[ $i ] );
                }
            } else {
                $leagues[ $i ] = $league;
            }
        }
        return $leagues;
    }
    /**
     * Get Team ID for given string
     *
     * @param string $title title.
     *
     * @return int
     */
    public function get_team_id( string $title ): int {
        global $wpdb;

        $team = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->prepare(
                        "SELECT `id` FROM $wpdb->racketmanager_teams WHERE `title` = %s",
                        $title
                )
        );
        if ( ! isset( $team[0] ) ) {
            return 0;
        } else {
            return $team[0]->id;
        }
    }

    /**
     * Check to see if a player is in a club (based on team)
     *
     * @param array $args query arguments.
     *
     * @return array|int
     */
    public function is_player_in_club( array $args ): bool {
        global $wpdb;

        $defaults = array(
                'team'    => false,
                'player'  => false,
                'active'  => false,
                'type'    => false,
        );
        $args     = array_merge( $defaults, $args );
        $team     = $args['team'];
        $type     = $args['type'];
        $player   = $args['player'];
        $active   = $args['active'];

        $search_terms = array();
        if ( $team ) {
            $search_terms[] = $wpdb->prepare( "`club_id` in (select `club_id` from $wpdb->racketmanager_teams where `id` = %d)", intval( $team ) );
        }

        if ( $player ) {
            $search_terms[] = $wpdb->prepare( '`player_id` = %d', intval( $player ) );
        }

        if ( $type ) {
            $search_terms[] = '`system_record` IS NULL';
        }

        if ( $active ) {
            $search_terms[] = '`removed_date` IS NULL';
        }
        $search = Util::search_string( $search_terms, true );
        $sql    = "SELECT COUNT(ID) FROM $wpdb->racketmanager_club_players " . $search;
        $count = $wpdb->get_var( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql
        );
        return $count > 0;
    }
    /**
     * Get list of players
     *
     * @param array $args query arguments.
     *
     * @return array
     */
    public function get_all_players( array $args = array() ): array {
        global $wpdb;
        $defaults       = array(
                'active' => false,
                'name'   => false,
        );
        $args           = array_merge( $defaults, $args );
        $active         = $args['active'];
        $name           = $args['name'];
        $orderby_string = 'display_name';
        $order          = 'ASC';
        if ( $active ) {
            $sql     = "SELECT DISTINCT `player_id` FROM $wpdb->racketmanager_rubber_players ORDER BY `player_id`";
            $players = wp_cache_get( md5( $sql ), 'players' );
            if ( ! $players ) {
                $players = $wpdb->get_results(
                        $sql // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                );
                if ( $players ) {
                    $i = 0;
                    foreach ( $players as $player ) {
                        $player        = get_player( $player->player_id );
                        $players[ $i ] = $player;
                        ++$i;
                    }
                }
                wp_cache_set( md5( $sql ), $players, 'players' );
            }
        } else {
            $user_fields               = array( 'ID', 'display_name' );
            $user_args                 = array();
            $user_args['meta_key']     = 'gender'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
            $user_args['meta_value']   = 'M,F'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
            $user_args['meta_compare'] = 'IN';
            $user_args['orderby']      = $orderby_string;
            $user_args['order']        = $order;
            if ( $name ) {
                if ( is_numeric( $name ) ) {
                    $user_args['meta_key']   = 'btm'; //phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
                    $user_args['meta_value'] = $name; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
                } else {
                    $user_args['search']         = '*' . $name . '*';
                    $user_args['search_columns'] = array( 'display_name' );
                }
            }
            $user_search = wp_json_encode( $user_args );
            $players     = wp_cache_get( md5( $user_search ), 'players' );
            if ( ! $players ) {
                $user_args['fields'] = $user_fields;
                $players             = get_users( $user_args );
                if ( $players ) {
                    $i = 0;
                    foreach ( $players as $player ) {
                        $player        = get_player( $player->ID );
                        $players[ $i ] = $player;
                        ++$i;
                    }
                }
                wp_cache_set( md5( $user_search ), $players, 'players' );
            }
        }
        return $players;
    }

    /**
     * Get player name
     *
     * @param int $player_id player id.
     *
     * @return string | false
     */
    public function get_player_name( int $player_id ): false|string {
        $player = get_player( $player_id );
        if ( ! $player ) {
            return false;
        }

        return $player->display_name;
    }

    /**
     * Match query arguments
     *
     * @var array
     */
    private array $match_query_args = array(
            'leagueId'            => false,
            'season'              => false,
            'final'               => false,
            'competition_type'    => false,
            'orderby'             => array(
                    'league_id' => 'ASC',
                    'id'        => 'ASC',
            ),
            'competition_id'      => false,
            'event_id'            => false,
            'confirmed'           => false,
            'match_date'          => false,
            'time'                => false,
            'timeOffset'          => false,
            'history'             => false,
            'club'                => false,
            'league_name'         => false,
            'team_name'           => false,
            'home_team'           => false,
            'away_team'           => false,
            'match_day'           => false,
            'competition_name'    => false,
            'home_club'           => false,
            'count'               => false,
            'confirmationPending' => false,
            'resultPending'       => false,
            'status'              => false,
            'team'                => false,
            'tournament_id'       => false,
            'player'              => false,
            'type'                => false,
            'complete'            => false,
            'age_group'           => false,
    );

    /**
     * Get matches without using league object
     *
     * @param array $match_args query arguments.
     *
     * @return array|int $matches
     */
    public function get_matches( array $match_args ): array|int {
        global $wpdb;

        $match_args           = array_merge( $this->match_query_args, $match_args );
        $league_id            = $match_args['leagueId'];
        $season               = $match_args['season'];
        $final                = $match_args['final'];
        $competition_type     = $match_args['competition_type'];
        $orderby              = $match_args['orderby'];
        $competition_id       = $match_args['competition_id'];
        $event_id             = $match_args['event_id'];
        $confirmed            = $match_args['confirmed'];
        $match_date           = $match_args['match_date'];
        $time                 = $match_args['time'];
        $time_offset          = $match_args['timeOffset'];
        $history              = $match_args['history'];
        $club                 = $match_args['club'];
        $league_name          = $match_args['league_name'];
        $team                 = $match_args['team'];
        $team_name            = $match_args['team_name'];
        $home_team            = $match_args['home_team'];
        $home_club            = $match_args['home_club'];
        $away_team            = $match_args['away_team'];
        $match_day            = $match_args['match_day'];
        $competition_name     = $match_args['competition_name'];
        $count                = $match_args['count'];
        $confirmation_pending = $match_args['confirmationPending'];
        $result_pending       = $match_args['resultPending'];
        $status               = $match_args['status'];
        $tournament_id        = $match_args['tournament_id'];
        $player               = $match_args['player'];
        $type                 = $match_args['type'];
        $complete             = $match_args['complete'];
        $age_group            = $match_args['age_group'];
        $sql_from             = " FROM $wpdb->racketmanager_matches AS m, $wpdb->racketmanager AS l";
        if ( $count ) {
            $sql        = "SELECT COUNT(*) FROM $wpdb->racketmanager_matches WHERE 1 = 1";
            $sql_fields = '';
        } else {
            $sql_fields = "SELECT m.`final` AS final_round, m.`group`, `home_team`, `away_team`, DATE_FORMAT(m.`date`, '%Y-%m-%d %H:%i') AS date, DATE_FORMAT(m.`date`, '%e') AS day, DATE_FORMAT(m.`date`, '%c') AS month, DATE_FORMAT(m.`date`, '%Y') AS year, DATE_FORMAT(m.`date`, '%H') AS `hour`, DATE_FORMAT(m.`date`, '%i') AS `minutes`, `match_day`, `location`, l.`id` AS `league_id`, m.`home_points`, m.`away_points`, m.`winner_id`, m.`loser_id`, m.`post_id`, `season`, m.`id` AS `id`, m.`custom`, m.`confirmed`, m.`home_captain`, m.`away_captain`, m.`comments`, m.`updated`, `event_id`, m.`status`, `leg`, `winner_id_tie`, `loser_id_tie`";
            $sql        = ' WHERE m.`league_id` = l.`id`';
        }

        if ( $match_date ) {
            $sql .= " AND DATEDIFF('" . htmlspecialchars( wp_strip_all_tags( $match_date ) ) . "', `date`) = 0";
        }
        if ( $competition_name ) {
            $sql .= " AND `league_id` in (select `id` from $wpdb->racketmanager WHERE `event_id` in (SELECT e.`id` FROM $wpdb->racketmanager_events e, $wpdb->racketmanager_competitions c WHERE c.`name` = '" . $competition_name . "' AND e.`competition_id` = c.`id`))";
        }
        if ( $competition_id ) {
            $sql .= " AND `league_id` in (select `id` from $wpdb->racketmanager WHERE `event_id` IN (select `id` from $wpdb->racketmanager_events WHERE `competition_id` = '" . $competition_id . "') )";
        }
        if ( $event_id ) {
            $sql .= " AND `league_id` in (select `id` from $wpdb->racketmanager WHERE `event_id` = '" . $event_id . "')";
        }
        if ( $league_id ) {
            $sql .= " AND `league_id`  = '" . $league_id . "'";
        }
        if ( $league_name ) {
            $sql .= " AND `league_id` in (select `id` from $wpdb->racketmanager WHERE `title` = '" . $league_name . "')";
        }
        if ( $season ) {
            $sql .= " AND `season`  = '" . $season . "'";
        }
        if ( $final ) {
            if ( 'all' === $final ) {
                $sql .= " AND `final` != ''";
            } else {
                $sql .= " AND `final`  = '" . $final . "'";
            }
        }
        if ( $competition_type ) {
            $sql .= " AND `league_id` in (select `id` from $wpdb->racketmanager WHERE `event_id` in (select e.`id` from $wpdb->racketmanager_events e, $wpdb->racketmanager_competitions c WHERE e.`competition_id` = c.`id` AND c.`type` = '" . $competition_type . "'))";
        }
        if ( $tournament_id ) {
            $sql .= " AND `league_id` in (select `id` from $wpdb->racketmanager WHERE `event_id` in (SELECT e.`id` FROM $wpdb->racketmanager_events e, $wpdb->racketmanager_competitions c, $wpdb->racketmanager_tournaments t WHERE e.`competition_id` = c.`id` AND c.`id` = t.`competition_id` AND t.`id` = " . $tournament_id . '))';
        }
        if ( $time_offset ) {
            $time_offset = intval( $time_offset ) . ':00:00';
        } else {
            $time_offset = '00:00:00';
        }
        if ( $status ) {
            $sql .= " AND `confirmed` = '" . $status . "'";
        }
        if ( $confirmed ) {
            $sql .= " AND `confirmed` in ('P','A','C')";
            if ( $time_offset ) {
                $sql .= " AND ADDTIME(`updated`,'" . $time_offset . "') <= NOW()";
            }
        }
        if ( $confirmation_pending ) {
            $confirmation_pending = intval( $confirmation_pending ) . ':00:00';
            $sql_fields          .= ",ADDTIME(`updated`,'" . $confirmation_pending . "') as confirmation_overdue_date, TIME_FORMAT(TIMEDIFF(now(),ADDTIME(`updated`,'" . $confirmation_pending . "')), '%H')/24 as overdue_time";
        }
        if ( $result_pending ) {
            $result_pending = intval( $result_pending ) . ':00:00';
            $sql_fields    .= ",ADDTIME(`date`,'" . $result_pending . "') as result_overdue_date, TIME_FORMAT(TIMEDIFF(now(),ADDTIME(`date`,'" . $result_pending . "')), '%H')/24 as overdue_time";
        }
        if ( $time ) {
            if ( 'latest' === $time ) { // get only finished matches with score for time 'latest'.
                $sql .= " AND (`home_points` != '' OR `away_points` != '')";
            } elseif ( 'outstanding' === $time ) {
                $sql .= " AND ADDTIME(`date`,'" . $time_offset . "') <= NOW() AND `winner_id` = 0 AND `confirmed` IS NULL";
            } elseif ( is_numeric( $time ) ) {
                $sql .= ' AND m.`date` > now() - INTERVAL ' . $time . ' DAY';
            }
        }
        if ( $history ) { // get only updated matches in specified period for history.
            $sql .= ' AND `updated` >= NOW() - INTERVAL ' . $history . ' DAY';
        }

        if ( $club ) {
            $sql .= " AND (`home_team` IN (SELECT `id` FROM $wpdb->racketmanager_teams WHERE `club_id` = " . $club . ") OR `away_team` IN (SELECT `id` FROM $wpdb->racketmanager_teams WHERE `club_id` = " . $club . '))';
        }
        if ( $team ) {
            $sql .= ' AND (`home_team` = ' . $team . ' OR `away_team` = ' . $team . ')';
        }
        if ( $home_club ) {
            $sql .= " AND `home_team` IN (SELECT `id` FROM $wpdb->racketmanager_teams WHERE `club_id` = " . $home_club . ')';
        }
        if ( ! empty( $home_team ) ) {
            $sql .= ' AND `home_team` = ' . $home_team . ' ';
        }
        if ( ! empty( $away_team ) ) {
            $sql .= ' AND `away_team` = ' . $away_team . ' ';
        }
        if ( ! empty( $team_name ) ) {
            $sql .= " AND (`home_team` IN (SELECT `id` FROM $wpdb->racketmanager_teams WHERE `title` LIKE '%" . $team_name . "%') OR `away_team` IN (SELECT `id` FROM $wpdb->racketmanager_teams WHERE `title` LIKE '%" . $team_name . "%'))";
        }
        if ( $match_day && intval( $match_day ) > 0 ) {
            $sql .= ' AND `match_day` = ' . $match_day . ' ';
        }
        if ( $player ) {
            if ( $tournament_id ) {
                $sql_from .= " ,$wpdb->racketmanager_team_players tp";
                $sql      .= " AND ((m.`home_team` = tp.`team_id` AND tp.`player_id` = '$player') OR (m.`away_team` = tp.`team_id` AND tp.`player_id` = '$player'))";
            } else {
                $sql_from .= " ,$wpdb->racketmanager_rubbers r, $wpdb->racketmanager_rubber_players rp";
                $sql      .= " AND m.`id` = r.`match_id` AND r.`id` = rp.`rubber_id` AND `player_id` = '$player'";
            }
        }
        if ( $type ) {
            $sql .= " AND `league_id` in (select `id` from $wpdb->racketmanager WHERE `event_id` in (select e.`id` from $wpdb->racketmanager_events e WHERE e.`type` like '%%" . $type . "%%'))";
        }
        if ( $complete ) {
            $sql .= ' AND m.`winner_id` != 0';
        }
        if ( $age_group ) {
            $sql .= " AND `league_id` in (select `id` from $wpdb->racketmanager WHERE `event_id` in (SELECT e.`id` FROM $wpdb->racketmanager_events e, $wpdb->racketmanager_competitions c WHERE e.`competition_id` = c.`id` and `age_group` = '" . $age_group . "'))";
        }
        if ( $count ) {
            return intval(
                    $wpdb->get_var( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
                    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                            $sql
                    )
            );
        }
        $order   = Util::order_by_string( $orderby );
        $sql     = $sql_fields . $sql_from . $sql . $order;
        $matches = wp_cache_get( md5( $sql ), 'matches' );
        if ( ! $matches ) {
            $matches = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                    $sql
            );
            wp_cache_set( md5( $sql ), $matches, 'matches' );
        }
        foreach ( $matches as $i => $match ) {
            $match = get_match( $match );
            if ( $player ) {
                $match->rubbers = $match->get_rubbers( $player );
            }
            $matches[ $i ] = $match;
        }
        return $matches;
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
        global $wpdb;

        $winners = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->prepare(
                        "SELECT l.`title` ,wt.`title` AS `winner` ,lt.`title` AS `loser`, m.`id`, m.`home_team`, m.`away_team`, m.`winner_id` AS `winner_id`, m.`loser_id` AS `loser_id`, e.`type`, e.`name` AS `event_name`, e.`id` AS `event_id`, c.`name` AS `competition_name`, c.`id` AS `competition_id`, wt.`status` AS `team_type`  FROM $wpdb->racketmanager_matches m, $wpdb->racketmanager l, $wpdb->racketmanager_competitions c, $wpdb->racketmanager_teams wt, $wpdb->racketmanager_teams lt, $wpdb->racketmanager_events e WHERE `league_id` = l.`id` AND l.`event_id` = e.`id` AND e.`competition_id` = c.`id` AND c.`type` = %s AND c.`id` = %d AND m.`final` = 'FINAL' AND m.`season` = %d AND m.`winner_id` = wt.`id` AND m.`loser_id` = lt.`id` order by c.`type`, l.`title`",
                        $competition_type,
                        $competition_id,
                        $season
                )
        );

        if ( ! $winners ) {
            return false;
        }

        $return = array();
        foreach ( $winners as $winner ) {
            $match = get_match( $winner->id );
            if ( $winner->winner_id === $winner->home_team ) {
                $winner_club = isset( $match->teams['home']->club ) ? $match->teams['home']->club->shortcode : null;
            } else {
                $winner_club = isset( $match->teams['away']->club ) ? $match->teams['away']->club->shortcode : null;
            }
            if ( $winner->loser_id === $winner->home_team ) {
                $loser_club = isset( $match->teams['home']->club ) ? $match->teams['home']->club->shortcode : null;
            } else {
                $loser_club = isset( $match->teams['away']->club ) ? $match->teams['away']->club->shortcode : null;
            }
            $winner->league      = $winner->title;
            $winner->winner_club = $winner_club;
            $winner->loser_club  = $loser_club;
            if ( $group_by ) {
                $key = strtoupper( $winner->type );
                if ( false === array_key_exists( $key, $return ) ) {
                    $return[ $key ] = array();
                }
                // now just add the row data.
                $return[ $key ][] = $winner;
            } else {
                $return[] = $winner;
            }
        }

        return $return;
    }

    /**
     * Get confirmation email
     *
     * @param string $type type of confirmation email.
     *
     * @return string $email
     */
    public function get_confirmation_email( string $type ): string {
        global $racketmanager;
        $options = $racketmanager->get_options();
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
     * Email entry form
     *
     * @param string $template email template to use.
     * @param array $template_args template arguments.
     * @param string $email_to email address to send.
     * @param string $email_subject email subject.
     * @param array $headers email headers.
     */
    public function email_entry_form( string $template, array $template_args, string $email_to, string $email_subject, array $headers ): void {
        $email_message = $this->shortcodes->load_template(
                $template,
                $template_args,
                'email'
        );
        wp_mail( $email_to, $email_subject, $email_message, $headers );
    }
    /**
     * Gets results checker from database
     *
     * @param array $args query arguments.
     *
     * @return array|int
     */
    public function get_result_warnings( array $args = array() ): array|int {
        global $wpdb;
        $defaults    = array(
                'season'      => false,
                'status'      => false,
                'competition' => false,
                'event'       => false,
                'count'       => false,
                'player'      => false,
                'type'        => false,
                'confirmed'   => false,
                'match'       => false,
        );
        $args        = array_merge( $defaults, $args );
        $season      = $args['season'];
        $status      = $args['status'];
        $competition = $args['competition'];
        $event       = $args['event'];
        $count       = $args['count'];
        $player_id   = $args['player'];
        $type        = $args['type'];
        $confirmed   = $args['confirmed'];
        $match_id    = $args['match'];
        $sql         = " FROM $wpdb->racketmanager_results_checker rc WHERE 1";

        if ( $status && 'all' !== $status ) {
            if ( 'outstanding' === $status ) {
                $sql .= ' AND `status` IS NULL';
            } else {
                $sql .= $wpdb->prepare( ' AND `status` = %d', $status );
            }
        }
        if ( $season && 'all' !== $season ) {
            $sql .= $wpdb->prepare( " AND `match_id` IN (SELECT `id` FROM $wpdb->racketmanager_matches WHERE `season` = %s)", $season );
        }
        if ( $competition && 'all' !== $competition ) {
            $sql .= $wpdb->prepare( " AND `match_id` IN (SELECT m.`id` FROM $wpdb->racketmanager_matches m, $wpdb->racketmanager l WHERE m.`league_id` = l.`id` AND l.`event_id` IN (SELECT `id` FROM $wpdb->racketmanager_events WHERE `competition_id` = %d))", $competition );
        } elseif ( $event && 'all' !== $event ) {
            $sql .= $wpdb->prepare( " AND `match_id` IN (SELECT m.`id` FROM $wpdb->racketmanager_matches m, $wpdb->racketmanager l WHERE m.`league_id` = l.`id` AND l.`event_id` = %d)", $event );
        }
        if ( $player_id ) {
            $sql .= $wpdb->prepare( ' AND `player_id` = %d', $player_id );
        }
        if ( $type ) {
            $sql .= $wpdb->prepare( ' AND `description` = %s', $type );
        }
        if ( $confirmed ) {
            $sql .= $wpdb->prepare( " AND `match_id` IN (SELECT `id` FROM $wpdb->racketmanager_matches WHERE `id` = rc.`match_id` AND `confirmed` != %s)", $confirmed );
        }
        if ( $match_id ) {
            $sql .= $wpdb->prepare( ' AND `match_id` = %d', $match_id );
        }
        if ( $count ) {
            $sql = 'SELECT COUNT(*)' . $sql;
            return $wpdb->get_var( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                    $sql
            );
        }
        $sql  = 'SELECT `id`, `league_id`, `match_id`, `team_id`, `player_id`, `updated_date`, `updated_user`, `description`, `status`' . $sql;
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
        foreach ( $results_checkers as $i => $results_checker ) {
            $result_check = get_result_check( $results_checker->id );
            if ( $result_check ) {
                $results_checkers[ $i ] = $result_check;
            }
        }
        return $results_checkers;
    }
    /**
     * Get Charges
     *
     * @param array $args query arguments.
     *
     * @return array $charges
     */
    public function get_charges( array $args = array() ): array {
        global $wpdb;
        $defaults     = array(
                'competition' => false,
                'season'      => false,
                'status'      => false,
                'entry'       => false,
                'orderby'     => array(
                        'season'         => 'ASC',
                        'competition_id' => 'ASC',
                ),
        );
        $args         = array_merge( $defaults, $args );
        $competition  = $args['competition'];
        $season       = $args['season'];
        $status       = $args['status'];
        $entry        = $args['entry'];
        $orderby      = $args['orderby'];
        $search_terms = array();
        if ( $competition ) {
            $search_terms[] = $wpdb->prepare( '`competition_id` = %d', $competition );
        }
        if ( $season ) {
            $search_terms[] = $wpdb->prepare( '`season` = %d', $season );
        }
        if ( $status ) {
            $search_terms[] = $wpdb->prepare( '`status` = %s', $status );
        }
        switch ( $entry ) {
            case 'team':
                $search_terms[] = "`competition_id` IN (SELECT `id` FROM $wpdb->racketmanager_competitions WHERE type IN ('league','cup'))";
                break;
            case 'player':
                $search_terms[] = "`competition_id` IN (SELECT `id` FROM $wpdb->racketmanager_competitions WHERE type IN ('tournament'))";
                break;
            default:
                break;
        }
        $search  = Util::search_string( $search_terms, true );
        $order   = Util::order_by_string( $orderby );
        $charges = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
                "SELECT `id` FROM $wpdb->racketmanager_charges $search $order" //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        );
        $i       = 0;
        foreach ( $charges as $charge ) {
            $charge        = get_charge( $charge->id );
            $charges[ $i ] = $charge;
            ++$i;
        }
        return $charges;
    }

    /**
     * Get teams from database
     *
     * @param array $args search arguments.
     * @return array|int
     */
    public function get_teams( array $args = array() ): array|int {
        global $wpdb;

        $defaults = array(
                'offset'  => 0,
                'limit'   => 99999999,
                'orderby' => array(
                        'id' => 'ASC',
                ),
                'count'   => false,
                'player'  => false,
                'partner' => false,
        );
        $args     = array_merge( $defaults, $args );
        $offset   = $args['offset'];
        $limit    = $args['limit'];
        $orderby  = $args['orderby'];
        $count    = $args['count'];
        $player   = $args['player'];
        $partner  = $args['partner'];

        $search_terms   = array();
        if ( $player ) {
            $search_terms[] = $wpdb->prepare( "`id` IN (SELECT `team_id` FROM $wpdb->racketmanager_team_players WHERE `player_id` = %d )", $player );
        }
        if ( $partner ) {
            $search_terms[] = $wpdb->prepare( "`id` IN (SELECT `team_id` FROM $wpdb->racketmanager_team_players WHERE `player_id` = %d )", $partner );
        }
        $search = Util::search_string( $search_terms, true );
        if ( $count ) {
            $sql = 'SELECT COUNT(distinct(`id`))';
        } else {
            $sql = 'SELECT `id`';
        }
        $sql .= " FROM $wpdb->racketmanager_teams " . $search;

        if ( $count ) {
            $teams = wp_cache_get( md5( $sql ), 'teams' );
            if ( ! $teams ) {
                $teams = $wpdb->get_var(
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                        $sql
                ); // db call ok.
                wp_cache_set( md5( $sql ), $teams, 'teams' );

            }
            return $teams;
        }
        $sql .= Util::order_by_string( $orderby );
        $sql  = $wpdb->prepare(
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql . ' LIMIT %d, %d',
                intval( $offset ),
                intval( $limit )
        );
        $teams = wp_cache_get( md5( $sql ), 'teams' );
        if ( ! $teams ) {
            $teams = $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                    $sql
            ); // db call ok.
            wp_cache_set( md5( $sql ), $teams, 'teams' );
        }
        foreach ( $teams as $i => $team ) {
            $team = get_team( $team->id );
            if ( $team ) {
                $teams[ $i ] = $team;
            }
        }
        return $teams;
    }
    /**
     * Validate player
     *
     * @return array
     */
    public function validate_player(): array {
        $return        = array();
        // phpcs:disable WordPress.Security.NonceVerification.Missing
        $player_id     = isset( $_POST['playerId'] ) ? intval( $_POST['playerId'] ) : null;
        $firstname     = isset( $_POST['firstname'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['firstname'] ) ) ) : null;
        $surname       = isset( $_POST['surname'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['surname'] ) ) ) : null;
        $gender        = isset( $_POST['gender'] ) ? sanitize_text_field( wp_unslash( $_POST['gender'] ) ) : null;
        $btm           = isset( $_POST['btm'] ) ? sanitize_text_field( wp_unslash( $_POST['btm'] ) ) : null;
        $contactno     = empty( $_POST['contactno'] ) ? '' : sanitize_text_field( wp_unslash( $_POST['contactno'] ) );
        $email         = isset( $_POST['email'] ) ? sanitize_text_field( wp_unslash( $_POST['email'] ) ) : null;
        $locked        = isset( $_POST['locked'] );
        $year_of_birth = empty( $_POST['year_of_birth'] ) ? null : intval( $_POST['year_of_birth'] );
        $validator     = new Validator();
        $validator     = $validator->first_name( $firstname );
        $validator     = $validator->surname( $surname );
        $validator     = $validator->gender( $gender );
        if ( empty( $validator->error ) && empty( $player_id ) ) {
            $name = $firstname . ' ' . $surname;
            $existing_player = get_player( $name, 'name' );
            if ( $existing_player ) {
                $player_id = $existing_player->ID;
            }
        }
        $validator     = $validator->btm( intval( $btm ), $player_id );
        $validator     = $validator->email( $email, $player_id, false );
        // phpcs:enable WordPress.Security.NonceVerification.Missing
        $player                = new stdClass();
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
        if ( empty( $validator->error ) ) {
            array_push( $return, true, $player );
        } else {
            array_push( $return, false, $validator->err_flds, $validator->err_msgs, $player );
        }
        return $return;
    }
}
