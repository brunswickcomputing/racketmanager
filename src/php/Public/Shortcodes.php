<?php
/**
 * Shortcodes API: RacketManagerShortcodes class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManagerShortcodes
 */

namespace Racketmanager\Public;

use Racketmanager\Exceptions\Invoice_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\RacketManager;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Util\Util_Lookup;
use stdClass;
use function Racketmanager\get_club;
use function Racketmanager\get_competition;
use function Racketmanager\get_league;
use function Racketmanager\get_match;
use function Racketmanager\get_player;
use function Racketmanager\get_tournament;
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

    protected Competition_Service $competition_service;
    protected Club_Service $club_service;
    protected Finance_Service $finance_service;
    protected Player_Service $player_service;
    protected Registration_Service $registration_service;
    protected Team_Service $team_service;
    protected RacketManager $racketmanager;

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

        $c                          = $this->racketmanager->container;
        $this->competition_service  = $c->get( 'competition_service' );
        $this->club_service         = $c->get( 'club_service' );
        $this->finance_service      = $c->get( 'finance_service' );
        $this->player_service       = $c->get( 'player_service' );
        $this->registration_service = $c->get( 'registration_service' );
        $this->team_service         = $c->get( 'team_service' );
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
     * @return string
     */
    public function show_daily_matches( array $atts ): string {
        global $racketmanager, $wp;
        wp_verify_nonce( 'matches-daily' );
        $args             = shortcode_atts(
            array(
                'competition_type' => 'league',
                'template'         => 'daily',
                'match_date'       => false,
            ),
            $atts
        );
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
        $matches      = $racketmanager->get_matches(
            array(
                'match_date'       => $match_date,
                'competition_type' => $competition_type,
            )
        );
        $matches_list = array();
        foreach ( $matches as $match ) {
            $key = $match->league->title;
            if ( false === array_key_exists( $key, $matches_list ) ) {
                $matches_list[ $key ] = array();
            }
            $matches_list[ $key ][] = $match;
        }

        $filename = ( ! empty( $template ) ) ? 'matches-' . $template : 'matches-daily';

        return $this->load_template(
            $filename,
            array(
                'matches_list' => $matches_list,
                'match_date'   => $match_date,
            )
        );
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
     * @return string
     */
    public function show_latest_results( array $atts ): string {
        global $racketmanager, $wp;

        $args             = shortcode_atts(
            array(
                'competition_type' => 'league',
                'template'         => 'results',
                'days'             => 7,
                'club'             => '',
                'competition_id'   => '',
                'header_level'     => 1,
                'age_group'        => false,
            ),
            $atts
        );
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
            $competition      = get_competition( $competition_name, 'name' );
            if ( $competition ) {
                $competition_id = $competition->id;
            }
        }
        if ( isset( $wp->query_vars['age_group'] ) ) {
            $age_group = get_query_var( 'age_group' );
        }
        $time         = 'latest';
        $matches      = $racketmanager->get_matches(
            array(
                'days'             => $days,
                'competition_type' => $competition_type,
                'time'             => $time,
                'history'          => $days,
                'club'             => $club_id,
                'competition_id'   => $competition_id,
                'age_group'        => $age_group,
            )
        );
        $matches_list = array();
        foreach ( $matches as $match ) {
            $key = $match->league->title;
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
        return $this->load_template(
            $filename,
            array(
                'matches_list' => $matches_list,
                'header_level' => $header_level,
            )
        );
    }
    /**
     * Function to display Players
     *
     *  [players template=X]
     *
     * @param array $atts shortcode attributes.
     * @return string content
     */
    public function show_players( array $atts ): string {
        $args           = shortcode_atts(
            array(
                'template' => '',
            ),
            $atts
        );
        $template       = $args['template'];
        $search_string  = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : null; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $search_results = null;
        if ( $search_string ) {
            $search_results = player_search( $search_string );
        }
        $favourites = array();
        if ( is_user_logged_in() ) {
            $userid     = get_current_user_id();
            $user       = get_user( $userid );
            $favourites = $user->get_favourites( 'player' );
        }
        $filename = ( ! empty( $template ) ) ? 'players-' . $template : 'players';
        return $this->load_template(
            $filename,
            array(
                'favourites'     => $favourites,
                'search_string'  => $search_string,
                'search_results' => $search_results,
            )
        );
    }
    /**
     * Function to display Player
     *
     *  [player template=X]
     *
     * @param array $atts shortcode attributes.
     * @return string content
     */
    public function show_player( array $atts ): string {
        $args     = shortcode_atts(
            array(
                'template' => '',
            ),
            $atts
        );
        $template = $args['template'];
        // Get Player by Name.
        $player_name = get_query_var( 'player_id' );
        $player_name = un_seo_url( $player_name );
        $btm         = get_query_var( 'btm' );
        if ( $btm ) {
            $player = get_player( $btm, 'btm' );
        } else {
            $player = get_player( $player_name, 'name' ); // get a player by name.
        }
        if ( ! $player ) {
            return $this->player_not_found;
        }
        $player_clubs = $this->registration_service->get_clubs_for_player( $player->get_id() );
        $titles       = $this->player_service->get_titles_for_player( $player->get_id() );

        $player->stats        = $player->get_career_stats();
        $player->competitions = array( 'cup', 'league', 'tournament' );
        foreach ( $player->competitions as $competition_type ) {
            if ( 'tournament' === $competition_type ) {
                $player->$competition_type = $player->get_tournaments( array( 'type' => $competition_type ) );
            } else {
                $player->$competition_type = $player->get_competitions( array( 'type' => $competition_type ) );
            }
        }

        $filename = ( ! empty( $template ) ) ? 'player-' . $template : 'player';
        return $this->load_template(
            $filename,
            array(
                'player' => $player,
                'clubs'  => $player_clubs,
                'titles' => $titles,
            )
        );
    }
    /**
     * Function to show favourites
     *
     *    [favourites template=X]
     *
     * @param array $atts shortcode attributes.
     * @return string content
     */
    public function show_favourites( array $atts ): string {
        $args = shortcode_atts(
            array(
                'template' => '',
            ),
            $atts
        );
        if ( ! is_user_logged_in() ) {
            return $this->return_error( __( 'You must be logged in to view favourites', 'racketmanager' ) );
        }
        $template   = $args['template'];
        $user       = get_user( get_current_user_id() );
        $favourites = $user->get_favourites();
        $filename   = ( ! empty( $template ) ) ? 'form-favourites-' . $template : 'form-favourites';
        return $this->load_template( $filename, array( 'favourite_types' => $favourites ), 'form' );
    }
    /**
     * Function to show invoice
     *
     *    [invoice template=X]
     *
     * @param array $atts shortcode attributes.
     * @return string content
     */
    public function show_invoice( array $atts ): string {
        global $racketmanager;
        $args = shortcode_atts(
            array(
                'id'       => 0,
                'template' => null,
            ),
            $atts
        );
        $id       = $args['id'];
        $template = $args['template'];
        if ( ! $id ) {
            $id = get_query_var( 'id' );
        }
        try {
            $invoice = $this->finance_service->get_full_invoice_details( $id );
        } catch ( Invoice_Not_Found_Exception $e ) {
            return $this->return_error( $e->getMessage() );
        }
        $billing  = $racketmanager->get_options( 'billing' );
        $filename = ( ! empty( $template ) ) ? 'invoice-' . $template : 'invoice';
        return $this->load_template(
            $filename,
            array(
                'organisation_name' => $racketmanager->site_name,
                'invoice'           => $invoice,
                'billing'           => $billing,
                'invoice_number'    => $invoice->invoice->get_invoice_number(),
            )
        );
    }
    /**
     * Function to show purchase order
     *
     *    [purchase-order id=X template=X]
     *
     * @param array $atts shortcode attributes.
     * @return string content
     */
    public function show_purchase_order( array $atts ): string {
        $args = shortcode_atts(
            array(
                'id'       => 0,
                'modal'    => null,
                'template' => null,
            ),
            $atts
        );
        $id       = $args['id'];
        $modal    = $args['modal'];
        $template = $args['template'];
        if ( ! $id ) {
            $id = get_query_var( 'id' );
        }
        try {
            $invoice = $this->finance_service->get_invoice( $id );
        } catch ( Invoice_Not_Found_Exception $e ) {
            return $this->return_error( $e->getMessage(), 'modal' );
        }
        $filename = ( ! empty( $template ) ) ? 'purchase-order-modal-' . $template : 'purchase-order-modal';
        return $this->load_template(
            $filename,
            array(
                'invoice' => $invoice,
                'modal'   => $modal,
            ),
            'club'
        );
    }
    /**
     * Function to show memberships
     *
     *    [memberships template=X]
     *
     * @param array $atts shortcode attributes.
     * @return string content
     */
    public function show_memberships( array $atts ): string {
        $args = shortcode_atts(
            array(
                'template' => '',
            ),
            $atts
        );
        if ( ! is_user_logged_in() ) {
            return $this->return_error( __( 'You must be logged in to view memberships', 'racketmanager' ) );
        }
        $template = $args['template'];
        try {
            $player                         = $this->player_service->get_player( get_current_user_id() );
            $template_args['clubs']         = $this->registration_service->get_clubs_for_player( $player->get_id() );
            $template_args['clubs_archive'] = $this->registration_service->get_clubs_for_player( $player->get_id(), 'removed' );
            $filename                       = ( ! empty( $template ) ) ? 'player-clubs-' . $template : 'player-clubs';
            return $this->load_template( $filename, $template_args, 'account' );
        } catch ( Player_Not_Found_Exception $e) {
            return $this->return_error( $e->getMessage() );
        }
    }
    /**
     * Function to search players
     *
     *    [search-players search=x template=X]
     *
     * @param array $atts shortcode attributes.
     * @return string content
     */
    public function show_player_search( array $atts ): string {
        global $racketmanager;
        $args          = shortcode_atts(
            array(
                'search'   => null,
                'template' => '',
            ),
            $atts
        );
        $template      = $args['template'];
        $search_string = $args['search'];
        $players       = $racketmanager->get_all_players( array( 'name' => $search_string ) );
        $filename      = ( ! empty( $template ) ) ? 'players-list-' . $template : 'players-list';

        return $this->load_template( $filename, array( 'players' => $players ) );
    }
    /**
     * Function to show team order
     *
     *    [team-order]
     *
     * @param array $atts shortcode attributes.
     * @return string content
     */
    public function show_team_order( array $atts ): string {
        global $racketmanager;
        $args     = shortcode_atts(
            array(
                'template' => '',
            ),
            $atts
        );
        $template          = $args['template'];
        $club_args         = array();
        $club_args['type'] = 'affiliated';
        $clubs             = $this->club_service->get_clubs( $club_args );
        if ( ! $clubs ) {
            return $this->return_error( __( 'No clubs found', 'racketmanager' ) );
        }
        $event_args                    = array();
        $event_args['entry_type']      = 'team';
        $event_args['reverse_rubbers'] = true;
        $events                        = $racketmanager->get_events( $event_args );
        if ( ! $events ) {
            return $this->return_error( __( 'No events found', 'racketmanager' ) );
        }
        $event_types = Util_Lookup::get_event_types();
        $age_groups  = Util_Lookup::get_age_groups();
        $filename    = ( ! empty( $template ) ) ? 'team-order-' . $template : 'team-order';

        return $this->load_template(
            $filename,
            array(
                'clubs'       => $clubs,
                'events'      => $events,
                'event_types' => $event_types,
                'age_groups'  => $age_groups,
                )
        );
    }
    /**
     * Load template for user display. First, the current theme directory is checked for a template
     * before defaulting to the plugin
     *
     * @param string $template Name of the template file (without extension).
     * @param array $vars Array of variables name=>value available to display code (optional).
     * @param false|string $template_type Type of content template (email, page).
     * @return string the content
     */
    public function load_template( string $template, array $vars = array(), false|string $template_type = false ): string {
        if ( $template_type ) {
            $template_dir = match ($template_type) {
                'competition' => 'templates/competition',
                'event'       => 'templates/event',
                'email'       => 'templates/email',
                'entry'       => 'templates/entry',
                'form'        => 'templates/forms',
                'includes'    => 'templates/includes',
                'page'        => 'templates/page',
                'tournament'  => 'templates/tournament',
                'account'     => 'templates/account',
                'league'      => 'templates/league',
                'match'       => 'templates/match',
                'club'        => 'templates/club',
                default       => 'templates',
            };
        } else {
            $template_dir = 'templates';
        }
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
            echo show_alert( $msg, 'danger');
        }
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }
    /**
     * Check if template exists
     *
     * @param string $template template name.
     * @param string|null $directory optional directory name.
     * @return boolean
     */
    public function check_template( string $template, ?string $directory = null ): bool {
        $template_dir = 'templates/';
        if ( $directory ) {
            $template_dir .= $directory . '/';
        }
        return file_exists( get_stylesheet_directory() . "/racketmanager/$template.php" ) || file_exists( get_template_directory() . "/racketmanager/$template.php" ) || file_exists( RACKETMANAGER_PATH . $template_dir . $template . '.php' );
    }
    /**
     * Get league
     *
     * @param int $league_id league id.
     * @return object
     */
    public function get_league( int $league_id ): object {
        global $league;

        if ( 0 === $league_id ) {
            $league = get_league();
        } else {
            $league = get_league( $league_id );
        }
        return $league;
    }
    /**
     * Get draws for event function
     *
     * @param object $event event object.
     * @param string $season season.
     * @return array of leagues with draws.
     */
    public function get_draw( object $event, string $season ): array {
        $leagues = $event->get_leagues();
        foreach ( $leagues as $l => $league ) {
            $league = get_league( $league->id );
            $finals = array_reverse( $league->championship->get_finals() );
            foreach ( $finals as $f => $final ) {
                $matches = $league->get_matches(
                    array(
                        'season'  => $season,
                        'final'   => $final['key'],
                        'orderby' => array(
                            'id' => 'ASC',
                        ),
                    )
                );
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
            )
        );
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
        $args     = shortcode_atts(
            array(
                'msg'  => '',
                'type' => '',
                'template' => null,
            ),
            $atts
        );
        $msg      = $args['msg'];
        $type     = $args['type'];
        $template = $args['template'];
        $filename = ! empty( $template ) ? 'alert-' . $template : 'alert';
        return $this->load_template(
                $filename,
                array(
                        'msg'   => $msg,
                        'class' => $type,
                    )
        );
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
        $max_age = null;
        $min_age = null;
        $min_age_female = null;
        $options   = $this->racketmanager->get_options( 'rosters' );
        $age_limit = $event->age_limit ?? null;
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

        switch ( $event->type ) {
            case 'BD':
            case 'MD':
                $club_players['m'] = $this->registration_service->get_registered_players_list( 'active', null, $club->get_id(), 'm', true, $max_age, $min_age );
                break;
            case 'GD':
            case 'WD':
                $club_players['f'] = $this->registration_service->get_registered_players_list( 'active', null, $club->get_id(), 'f', true, $max_age, $min_age );
                break;
            case 'XD':
            case 'LD':
                $club_players['m'] = $this->registration_service->get_registered_players_list( 'active', null, $club->get_id(), 'm', true, $max_age, $min_age );
                $club_players['f'] = $this->registration_service->get_registered_players_list( 'active', null, $club->get_id(), 'f', true, $max_age, $min_age_female );
                break;
            default:
                $club_players['m'] = array();
                $club_players['f'] = array();
        }
        return $club_players;
    }
    /**
     * Function to display the finals' order of play
     *
     *    [orderofplay id=ID template=X]
     *
     * @param array $atts shortcode attributes.
     * @return string the content
     */
    public function show_order_of_play( array $atts ): string {
        wp_verify_nonce( 'order-of-play' );
        $args          = shortcode_atts(
            array(
                'id'             => '',
                'competition_id' => null,
                'season'         => null,
                'template'       => '',
            ),
            $atts
        );
        $tournament_id  = $args['id'];
        $competition_id = $args['competition_id'];
        $season         = $args['season'];
        $template       = $args['template'];
        $msg            = null;
        if ( $tournament_id ) {
            $tournament = get_tournament( $tournament_id );
            if ( $tournament ) {
                $finals_order = $tournament->order_of_play;
            } else {
                $msg = $this->tournament_not_found;
            }
        } elseif ( $competition_id ) {
            $competition = get_competition( $competition_id );
            if ( $competition ) {
                if ( $season ) {
                    $competition_season = $competition->get_season_by_name( $season );
                    if ( $competition_season ) {
                        $finals_order = $competition_season['orderofplay'] ?? array();
                    } else{
                        $msg = $this->season_not_found_for_competition;
                    }
                } else {
                    $msg = $this->season_not_found;
                }
            } else {
                $msg = $this->competition_not_found;
            }
        } else {
            $msg = $this->competition_not_found;
        }
        if ( $msg ) {
            return $this->return_error( $msg );
        }
        $order_of_play = array();
        $times  = array();
        $courts = array();
        foreach ( $finals_order as $final_courts ) {
            $court = $final_courts['court'];
            $courts[ $court ] = array();
            foreach ( $final_courts['matches'] as $match_id ) {
                if ( $match_id ) {
                    $match = get_match( $match_id );
                    if ( $match ) {
                        $final_match           = new stdClass();
                        $final_match->id       = $match_id;
                        $final_match->time     = $match->hour . ':' . $match->minutes;
                        $final_match->league   = $match->league->title;
                        $final_match->location = $match->location;
                        $final_match->winner   = $match->winner_id;
                        $time                  = $final_match->time;
                        if ( ! in_array( $time, $times, true ) ) {
                            $times[] = $time;
                        }
                        $order_of_play['match_time'][ $time ][ $court ] = $final_match;
                        // now just add the row data.
                        $courts[ $court ][ $time ][] = $final_match;
                    }
                }
            }
        }
        sort( $times );
        foreach ( $times as $time ) {
            foreach ( $courts as $court => $court_matches ) {
                if ( ! isset( $court_matches[ $time ] ) ) {
                    $courts[ $court ][ $time ] = array();
                }
                ksort( $courts[ $court ] );
            }
        }
        $order_of_play['courts'] = $courts;
        $order_of_play['times']  = $times;
        $filename = ( ! empty( $template ) ) ? 'orderofplay-' . $template : 'orderofplay';

        return $this->load_template(
            $filename,
            array(
                'order_of_play' => $order_of_play,
            )
        );
    }
}
