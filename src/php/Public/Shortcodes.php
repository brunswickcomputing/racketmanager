<?php
/**
 * Shortcodes API: RacketManagerShortcodes class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManagerShortcodes
 */

namespace Racketmanager\Public;

use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Exceptions\Invoice_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\General_Shortcode_Adapter;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Tournament_Shortcode_Adapter;
use Racketmanager\Presentation\Presenters\Club_Presenter;
use Racketmanager\Presentation\Presenters\Fixture_Presentation_Adapter;
use Racketmanager\Presentation\Presenters\Player_Presenter;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Competition_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Entry_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Fixture\Fixture_Detail_Service;
use Racketmanager\Services\Fixture\Fixture_Link_Service;
use Racketmanager\Services\Fixture\Fixture_Permission_Service;
use Racketmanager\Services\Fixture_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Util\Util_Lookup;
use function Racketmanager\get_club;
use function Racketmanager\get_league;
use function Racketmanager\get_player;
use function Racketmanager\get_user;
use function Racketmanager\player_search;
use function Racketmanager\show_alert;
use function Racketmanager\un_seo_url;

/**
 * Class to implement shortcode functions
 */
class Shortcodes {
    public string $event_not_found;
    public string $no_event_id;
    public string $competition_not_found;
    public string $season_not_found;
    public string $club_not_found;
    public string $player_not_found;
    public string $tournament_not_found;
    public string $no_competition_id;
    public string $league_not_found;
    public string $not_played;
    public string $retired_player;
    public string $not_played_no_opponent;
    public string $match_not_found;
    public string $no_match_id;
    public string $team_not_found;
    public string $no_team_id;
    public string $club_player_not_found;
    public string $season_not_found_for_competition;

    protected ?Competition_Service $competition_service = null;
    protected ?Club_Service $club_service = null;
    protected ?Finance_Service $finance_service = null;
    protected ?Player_Service $player_service = null;
    protected ?Registration_Service $registration_service = null;
    protected ?Team_Service $team_service = null;
    protected RacketManager $racketmanager;
    protected ?Tournament_Service $tournament_service = null;
    protected ?Competition_Entry_Service $competition_entry_service = null;
    protected ?Fixture_Service $fixture_service = null;
    protected ?Fixture_Detail_Service $fixture_detail_service = null;
    protected ?Competition_Repository_Interface $competition_repository = null;
    protected ?Fixture_Repository_Interface $fixture_repository = null;
    protected ?Rubber_Repository_Interface $rubber_repository = null;
    protected ?Fixture_Link_Service $fixture_link_service = null;
    protected ?Fixture_Permission_Service $fixture_permission_service = null;
    protected ?Club_Presenter $club_presenter = null;
    protected ?Player_Presenter $player_presenter = null;

    /**
     * Initialise shortcodes
     */
    public function __construct( $plugin_instance ) {
        $this->racketmanager = $plugin_instance;

        $this->competition_not_found            = __( 'Competition not found', 'racketmanager' );
        $this->club_not_found                   = __( 'Club not found', 'racketmanager' );
        $this->club_player_not_found            = __( 'Player not found for club', 'racketmanager' );
        $this->event_not_found                  = __( 'Event not found', 'racketmanager' );
        $this->league_not_found                 = __( 'League not found', 'racketmanager' );
        $this->match_not_found                  = __( 'Match not found', 'racketmanager' );
        $this->player_not_found                 = __( 'Player not found', 'racketmanager' );
        $this->season_not_found                 = __( 'Season not found', 'racketmanager' );
        $this->season_not_found_for_competition = __( 'Season not found for competition', 'racketmanager' );
        $this->team_not_found                   = __( 'Team not found', 'racketmanager' );
        $this->tournament_not_found             = __( 'Tournament not found', 'racketmanager' );
        $this->no_competition_id                = __( 'Competition id not supplied', 'racketmanager' );
        $this->no_event_id                      = __( 'Event id not supplied', 'racketmanager' );
        $this->no_match_id                      = __( 'Match id not found', 'racketmanager' );
        $this->no_team_id                       = __( 'Team id not supplied', 'racketmanager' );
        $this->not_played                       = __( 'Not played', 'racketmanager' );
        $this->retired_player                   = __( 'Retired - %s', 'racketmanager' );
        $this->not_played_no_opponent           = __( 'Match not played - %s did not show', 'racketmanager' );

        $c                               = $this->racketmanager->container ?? null;
        if ( $c ) {
            $this->competition_service       = $c->has( 'competition_service' ) ? $c->get( 'competition_service' ) : null;
            $this->club_service              = $c->has( 'club_service' ) ? $c->get( 'club_service' ) : null;
            $this->finance_service           = $c->has( 'finance_service' ) ? $c->get( 'finance_service' ) : null;
            $this->player_service            = $c->has( 'player_service' ) ? $c->get( 'player_service' ) : null;
            $this->registration_service      = $c->has( 'registration_service' ) ? $c->get( 'registration_service' ) : null;
            $this->team_service              = $c->has( 'team_service' ) ? $c->get( 'team_service' ) : null;
            $this->tournament_service        = $c->has( 'tournament_service' ) ? $c->get( 'tournament_service' ) : null;
            $this->competition_entry_service = $c->has( 'competition_entry_service' ) ? $c->get( 'competition_entry_service' ) : null;
            $this->fixture_service           = $c->has( 'fixture_service' ) ? $c->get( 'fixture_service' ) : null;
            $this->fixture_detail_service    = $c->has( 'fixture_detail_service' ) ? $c->get( 'fixture_detail_service' ) : null;
            $this->competition_repository    = $c->has( 'competition_repository' ) ? $c->get( 'competition_repository' ) : null;
            $this->fixture_repository        = $c->has( 'fixture_repository' ) ? $c->get( 'fixture_repository' ) : null;
            $this->rubber_repository         = $c->has( 'rubber_repository' ) ? $c->get( 'rubber_repository' ) : null;
            $this->fixture_link_service       = $c->has( 'fixture_link_service' ) ? $c->get( 'fixture_link_service' ) : null;
            $this->fixture_permission_service = $c->has( 'fixture_permission_service' ) ? $c->get( 'fixture_permission_service' ) : null;
            $this->club_presenter             = $c->has( 'club_presenter' ) ? $c->get( 'club_presenter' ) : new Club_Presenter();
            $this->player_presenter           = $c->has( 'player_presenter' ) ? $c->get( 'player_presenter' ) : new Player_Presenter();
        }
    }

    /**
     * Display Daily Matches
     *
     *    [dailymatches league_id="1" competition_id="1" match_date="dd/mm/yyyy" template="name"]
     *
     * - league_id is the ID of league (optional)
     * - competition_id is the ID of the competition (optional)
     * - season: display specific season (optional)
     * - template is the template used for displaying. Replace the template name appropriately. Templates must be named "matches-template.php" (optional)
     *
     * @param array $atts shortcode attributes.
     *
     * @return string
     * @deprecated [dailymatches] is served by Matches_Shortcode_Adapter (Get_Daily_Matches_Handler + Matches_List_Presenter).
     */
    public function show_daily_matches( array $atts ): string {
        global $racketmanager, $wp;
        wp_verify_nonce( 'matches-daily' );
        $args             = shortcode_atts( array(
                'competition_type' => 'league',
                'template'         => 'daily',
                'match_date'       => false,
            ), $atts );
        $competition_type = $args['competition_type'];
        $template         = $args['template'];
        $match_date       = $args['match_date'];
        if ( ! $match_date ) {
            $match_date = get_query_var( 'match_date' );
            if ( '' === $match_date && isset( $_GET['match_date'] ) ) {
                $match_date = sanitize_text_field( wp_unslash( $_GET['match_date'] ) );
            }
        }
        if ( '' === $match_date ) {
            $match_date = gmdate( 'Y-m-d' );
        }
        if ( isset( $wp->query_vars['competition_type'] ) ) {
            $competition_type = un_seo_url( get_query_var( 'competition_type' ) );
        }
        $matches      = $racketmanager->get_matches( array(
                'match_date'       => $match_date,
                'competition_type' => $competition_type,
            ) );
        $matches_list = array();
        foreach ( $matches as $match ) {
            if ( ! $match || ! is_object( $match ) ) {
                continue;
            }
            if ( $match instanceof Fixture ) {
                $match = Fixture_Presentation_Adapter::from_fixture(
                    $match,
                    $this->competition_service,
                    $this->team_service,
                    $this->fixture_link_service,
                    $this->fixture_permission_service,
                    rubber_repository: $this->rubber_repository
                );
            }
            $match_league = method_exists( $match, 'get_league' ) ? $match->get_league() : ( $match->league ?? null );
            if ( ! $match_league && ! empty( $match->league_id ) ) {
                $match_league = isset( $this->competition_service ) ? $this->competition_service->get_league_repository()->find_by_id( (int) $match->league_id ) : ( function_exists( '\Racketmanager\get_league' ) ? get_league( (int) $match->league_id ) : null );
                if ( $match_league ) {
                    $match->league = $match_league;
                }
            }
            if ( is_object( $match_league ) ) {
                $key = method_exists( $match_league, 'get_name' ) ? $match_league->get_name() : ( $match_league->title ?? $match_league->name ?? '' );
            } elseif ( is_string( $match_league ) ) {
                $key = $match_league;
            } else {
                $key = '';
            }
            if ( false === array_key_exists( $key, $matches_list ) ) {
                $matches_list[ $key ] = array();
            }
            $matches_list[ $key ][] = $match;
        }

        $filename = ( ! empty( $template ) ) ? 'matches-' . $template : 'matches-daily';

        return $this->load_template( $filename, array(
                'matches_list' => $this->present_match_groups( $matches_list ),
                'match_date'   => $match_date,
            ) );
    }

    /**
     * Convert league keyed match lists into the immutable group view models the match list templates require.
     *
     * @param array $matches_list Matches keyed by league name.
     *
     * @return array<string, \Racketmanager\Presentation\View_Models\Team_Matches_Group_View_Model>
     */
    private function present_match_groups( array $matches_list ): array {
        $container = $this->racketmanager->container ?? null;
        if ( ! $container || ! $container->has( 'tournament_presenter' ) ) {
            throw new \LogicException( 'The tournament_presenter service is required to render match lists.' );
        }
        $presenter = $container->get( 'tournament_presenter' );
        $groups    = array();
        foreach ( $matches_list as $key => $matches ) {
            $groups[ $key ] = $presenter->present_match_group(
                $key,
                $matches,
                null,
                array(
                    'show_header' => false,
                    'matches_key' => 'league',
                )
            );
        }

        return $groups;
    }

    /**
     * Load template for user display. First, the current theme directory is checked for a template
     * before defaulting to the plugin
     *
     * @param string $template Name of the template file (without extension).
     * @param array $vars Array of variables name=>value available to display code (optional).
     * @param false|string $template_type Type of content template (email, page).
     *
     * @return string the content
     */
    public function load_template( string $template, array $vars = array(), false|string $template_type = false ): string {
        if ( $template_type ) {
            $template_dir = match ( $template_type ) {
                'competition' => 'templates/competition',
                'event' => 'templates/event',
                'email' => 'templates/email',
                'entry' => 'templates/entry',
                'form' => 'templates/forms',
                'includes' => 'templates/includes',
                'page' => 'templates/page',
                'tournament' => 'templates/tournament',
                'account' => 'templates/account',
                'league' => 'templates/league',
                'match' => 'templates/match',
                'club' => 'templates/club',
                default => 'templates',
            };
        } else {
            $template_dir = 'templates';
        }
        $default_vars = array(
            'club_presenter'   => $this->club_presenter,
            'player_presenter' => $this->player_presenter,
        );
        $vars = array_merge( $default_vars, $vars );
        extract( $vars ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
        ob_start();

        if ( file_exists( get_stylesheet_directory() . "/racketmanager/$template.php" ) ) {
            require get_stylesheet_directory() . "/racketmanager/$template.php";
        } elseif ( file_exists( get_template_directory() . "/racketmanager/$template.php" ) ) {
            require get_template_directory() . "/racketmanager/$template.php";
        } elseif ( file_exists( RACKETMANAGER_PATH . $template_dir . '/' . $template . '.php' ) ) {
            require RACKETMANAGER_PATH . $template_dir . '/' . $template . '.php';
        } else {
            /* translators: %1$s: template %2$s: directory */
            $msg = sprintf( __( 'Could not load template %1$s.php from %2$s directory', 'racketmanager' ), $template, $template_dir );
            echo show_alert( $msg, 'danger' );
        }
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }

    /**
     * Display Latest Match results
     *
     *    [latest_results league_id="1" competition_id="1" match_date="dd/mm/yyyy" template="name"]
     *
     * - league_id is the ID of league (optional)
     * - competition_id is the ID of the competition (optional)
     * - season: display specific season (optional)
     * - template is the template used for displaying. Replace the template name appropriately. Templates must be named "matches-template.php" (optional)
     *
     * @param array $atts shortcode attributes.
     *
     * @return string
     * @deprecated [latest_results] is served by Matches_Shortcode_Adapter (Get_Latest_Results_Handler + Matches_List_Presenter).
     */
    public function show_latest_results( array $atts ): string {
        global $racketmanager, $wp;

        $args             = shortcode_atts( array(
                'competition_type' => 'league',
                'template'         => 'results',
                'days'             => 7,
                'club'             => '',
                'competition_id'   => '',
                'header_level'     => 1,
                'age_group'        => false,
            ), $atts );
        $competition_type = $args['competition_type'];
        $template         = $args['template'];
        $days             = $args['days'];
        $club_id          = $args['club'];
        $competition_id   = $args['competition_id'];
        $header_level     = $args['header_level'];
        $age_group        = $args['age_group'];
        if ( isset( $wp->query_vars['club_name'] ) ) {
            $club_name = str_replace( '-', ' ', get_query_var( 'club_name' ) );
            $club      = get_club( $club_name, 'shortcode' );
            $club_id   = $club->id;
        }
        if ( isset( $wp->query_vars['days'] ) ) {
            $days = str_replace( '-', ' ', get_query_var( 'days' ) );
        }
        if ( isset( $wp->query_vars['competition_type'] ) ) {
            $competition_type = un_seo_url( get_query_var( 'competition_type' ) );
        }
        if ( isset( $wp->query_vars['competition_name'] ) ) {
            $competition_name = un_seo_url( get_query_var( 'competition_name' ) );
            $competition      = $this->competition_repository->find_by_name( $competition_name );
            if ( $competition ) {
                $competition_id = $competition->id;
            }
        }
        if ( isset( $wp->query_vars['age_group'] ) ) {
            $age_group = get_query_var( 'age_group' );
        }
        $time         = 'latest';
        $matches      = $racketmanager->get_matches( array(
                'days'             => $days,
                'competition_type' => $competition_type,
                'time'             => $time,
                'history'          => $days,
                'club'             => $club_id,
                'competition_id'   => $competition_id,
                'age_group'        => $age_group,
            ) );
        $matches_list = array();
        foreach ( $matches as $match ) {
            if ( ! $match || ! is_object( $match ) ) {
                continue;
            }
            if ( $match instanceof Fixture ) {
                $match = Fixture_Presentation_Adapter::from_fixture(
                    $match,
                    $this->competition_service,
                    $this->team_service,
                    $this->fixture_link_service,
                    $this->fixture_permission_service,
                    rubber_repository: $this->rubber_repository
                );
            }
            $match_league = method_exists( $match, 'get_league' ) ? $match->get_league() : ( $match->league ?? null );
            if ( ! $match_league && ! empty( $match->league_id ) ) {
                $match_league = isset( $this->competition_service ) ? $this->competition_service->get_league_repository()->find_by_id( (int) $match->league_id ) : ( function_exists( '\Racketmanager\get_league' ) ? get_league( (int) $match->league_id ) : null );
                if ( $match_league ) {
                    $match->league = $match_league;
                }
            }
            if ( is_object( $match_league ) ) {
                $key = method_exists( $match_league, 'get_name' ) ? $match_league->get_name() : ( $match_league->title ?? $match_league->name ?? '' );
            } elseif ( is_string( $match_league ) ) {
                $key = $match_league;
            } else {
                $key = '';
            }
            if ( false === array_key_exists( $key, $matches_list ) ) {
                $matches_list[ $key ] = array();
            }
            $matches_list[ $key ][] = $match;
        }
        if ( empty( $template ) ) {
            $filename = 'matches-results';
        } elseif ( isset( $league ) && $this->check_template( 'matches-results-' . $league->sport ) ) {
            $filename = 'matches-results-' . $league->sport;
        } else {
            $filename = 'matches-' . $template;
        }

        return $this->load_template( $filename, array(
                'matches_list' => $this->present_match_groups( $matches_list ),
                'header_level' => $header_level,
            ) );
    }

    /**
     * Check if template exists
     *
     * @param string $template template name.
     * @param string|null $directory optional directory name.
     *
     * @return boolean
     */
    public function check_template( string $template, ?string $directory = null ): bool {
        $template_dir = 'templates/';
        if ( $directory ) {
            $template_dir .= $directory . '/';
        }

        return file_exists( get_stylesheet_directory() . "/racketmanager/$template.php" ) || file_exists( get_template_directory() . "/racketmanager/$template.php" ) || file_exists( RACKETMANAGER_PATH . $template_dir . $template . '.php' );
    }

    private function general_adapter(): General_Shortcode_Adapter {
        return $this->racketmanager->container->get( 'general_shortcode_adapter' );
    }

    /**
     * Function to display Players
     *
     *  [players template=X]
     *
     * @param array $atts shortcode attributes.
     *
     * @return string content
     */
    public function show_players( array $atts ): string {
        return $this->general_adapter()->show_players( $atts );
    }

    /**
     * Function to display Player
     *
     *  [player template=X]
     *
     * @param array $atts shortcode attributes.
     *
     * @return string content
     */
    public function show_player( array $atts ): string {
        return $this->general_adapter()->show_player( $atts );
    }

    /**
     * Function to show favourites
     *
     *    [favourites template=X]
     *
     * @param array $atts shortcode attributes.
     *
     * @return string content
     */
    public function show_favourites( array $atts ): string {
        return $this->general_adapter()->show_favourites( $atts );
    }

    /**
     * Return error function
     *
     * @param string $msg message to display.
     * @param string|null $template template suffix (modal or null).
     *
     * @return string output HTML
     */
    public function return_error( string $msg, ?string $template = null ): string {
        $filename = ! empty( $template ) ? 'alert-' . $template : 'alert';

        return $this->load_template( $filename, array(
                'msg'   => $msg,
                'class' => 'danger',
            ) );
    }

    /**
     * Function to show invoice
     *
     *    [invoice template=X]
     *
     * @param array $atts shortcode attributes.
     *
     * @return string content
     */
    public function show_invoice( array $atts ): string {
        return $this->general_adapter()->show_invoice( $atts );
    }

    /**
     * Function to show purchase order
     *
     *    [purchase-order id=X template=X]
     *
     * @param array $atts shortcode attributes.
     *
     * @return string content
     */
    public function show_purchase_order( array $atts ): string {
        return $this->general_adapter()->show_purchase_order( $atts );
    }

    /**
     * Function to show memberships
     *
     *    [memberships template=X]
     *
     * @param array $atts shortcode attributes.
     *
     * @return string content
     */
    public function show_memberships( array $atts ): string {
        return $this->general_adapter()->show_memberships( $atts );
    }

    /**
     * Function to search players
     *
     *    [search-players search=x template=X]
     *
     * @param array $atts shortcode attributes.
     *
     * @return string content
     */
    public function show_player_search( array $atts ): string {
        return $this->general_adapter()->show_player_search( $atts );
    }

    /**
     * Function to show team order
     *
     *    [team-order]
     *
     * @param array $atts shortcode attributes.
     *
     * @return string content
     */
    public function show_team_order( array $atts ): string {
        return $this->general_adapter()->show_team_order( $atts );
    }

    /**
     * Get league
     *
     * @param int $league_id league id.
     *
     * @return object
     */
    public function get_league( int $league_id ): ?object {
        global $league;

        if ( 0 === $league_id ) {
            $league = get_league();
        } else {
            $league = get_league( $league_id );
        }

        return $league ?: null;
    }

    /**
     * Get draws for event function
     *
     * @param object $event event object.
     * @param string $season season.
     *
     * @return array of leagues with draws.
     */
    public function get_draw( object $event, string $season ): array {
        $leagues = $event->get_leagues();
        foreach ( $leagues as $l => $league ) {
            $league = get_league( $league->id );
            $finals = array_reverse( $league->championship->get_finals() );
            foreach ( $finals as $f => $final ) {
                $matches = $league->get_matches( array(
                        'season'  => $season,
                        'final'   => $final['key'],
                        'orderby' => array(
                            'id' => 'ASC',
                        ),
                    ) );
                if ( count( $matches ) ) {
                    $final['matches'] = $matches;
                    $finals[ $f ]     = (object) $final;
                } else {
                    unset( $finals[ $f ] );
                }
            }
            $league->finals = $finals;
            $leagues[ $l ]  = $league;
        }

        return $leagues;
    }

    /**
     * Show alert function
     *
     *    [show-alert msg=x type-x]
     *
     * @param array $atts shortcode attributes.
     *
     * @return string output HTML
     */
    public function show_alert( array $atts ): string {
        return $this->general_adapter()->show_alert( $atts );
    }

    /**
     * Function to display the finals' order of play
     *
     *    [orderofplay id=ID template=X]
     *
     * @param array $atts shortcode attributes.
     *
     * @return string the content
     */
    public function show_order_of_play( array $atts ): string {
        return $this->tournament_adapter()->show_order_of_play( $atts );
    }

    private function tournament_adapter(): Tournament_Shortcode_Adapter {
        return $this->racketmanager->container->get( 'tournament_shortcode_adapter' );
    }

    /**
     * Get players for a club by event type and age
     *
     * @param object $event event.
     * @param object $club club.
     *
     * @return array
     */
    protected function get_club_players( object $event, object $club ): array {
        $max_age        = null;
        $min_age        = null;
        $min_age_female = null;
        $options        = $this->racketmanager->get_options( 'rosters' );
        $age_limit      = $event->age_limit ?? null;
        if ( $age_limit ) {
            $age_offset = $event->age_offset ?? 0;
        }
        if ( ! empty( $options['ageLimitCheck'] ) && $age_limit && 'open' !== $age_limit ) {
            if ( $age_limit < 30 ) {
                $max_age = $age_limit;
            } else {
                $min_age        = $age_limit;
                $min_age_female = $min_age - $age_offset;
            }
        }

        switch ( substr( $event->type, 0, 1 ) ) {
            case 'B':
            case 'M':
                $club_players['m'] = $this->registration_service->get_registered_players_list( 'active', null, $club->get_id(), 'm', true, $max_age, $min_age );
                break;
            case 'G':
            case 'W':
                $club_players['f'] = $this->registration_service->get_registered_players_list( 'active', null, $club->get_id(), 'f', true, $max_age, $min_age );
                break;
            case 'X':
            case 'L':
                $club_players['m'] = $this->registration_service->get_registered_players_list( 'active', null, $club->get_id(), 'm', true, $max_age, $min_age );
                $club_players['f'] = $this->registration_service->get_registered_players_list( 'active', null, $club->get_id(), 'f', true, $max_age, $min_age_female );
                break;
            case 'S':
            case 'O':
                $club_players['x'] = $this->registration_service->get_registered_players_list( 'active', null, $club->get_id(), null, true, $max_age, $min_age );
                break;
            default:
                $club_players['m'] = array();
                $club_players['f'] = array();
        }

        return $club_players;
    }
}
