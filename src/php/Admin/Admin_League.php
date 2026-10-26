<?php
declare( strict_types=1 );
/**
 * RacketManager-Admin API: RacketManager-admin-league class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManager-Admin-League
 */

namespace Racketmanager\Admin;

use Racketmanager\Admin\Presenters\Admin_Date_Presenter;
use Racketmanager\Admin\Presenters\Admin_Entry_Link_Presenter;
use Racketmanager\Admin\Presenters\Admin_League_Matches_Presenter;
use Racketmanager\Admin\Presenters\Admin_League_Schedule_Presenter;
use Racketmanager\Admin\View_Models\Admin_League_Schedule_Event_View_Model;
use Racketmanager\Admin\View_Models\Admin_League_Schedule_Page_View_Model;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\League_Team;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\League_Not_Found_Exception;
use Racketmanager\Exceptions\Season_Not_Found_Exception;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Standings\Standings_Service;
use Racketmanager\Services\Validator\Validator;
use Racketmanager\Util\Util;
use stdClass;

/**
 * RacketManager administration functions
 * Class to implement RacketManager Administration League panel
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManagerAdmin
 */
final class Admin_League extends Admin_Display {

    private Admin_Entry_Link_Presenter $admin_entry_link_presenter;

    public function __construct(
        $plugin_instance,
        ?Admin_Entry_Link_Presenter $admin_entry_link_presenter = null,
        private ?Standings_Service $standings_service = null,
        ?League_Query_Service $league_query_service = null,
        ?Event_Repository_Interface $event_repository = null,
    ) {
        parent::__construct( $plugin_instance );
        $this->admin_entry_link_presenter = $admin_entry_link_presenter ?? $this->racketmanager->container->get( 'admin_entry_link_presenter' );
        if ( null !== $league_query_service ) {
            $this->league_query_service = $league_query_service;
        }
        if ( null !== $event_repository ) {
            $this->event_repository = $event_repository;
        }
    }

    /**
     * Function to handle administration finances displays
     *
     * @param string|null $view
     *
     * @return void
     */
    public function handle_display( ?string $view ): void {
        switch ( $view ) {
            case 'seasons':
                $this->display_seasons_page();
                break;
            case 'overview':
                $this->display_overview_page();
                break;
            case 'setup':
                $this->display_setup_page();
                break;
            case 'setup-event':
                $this->display_setup_event_page();
                break;
            case 'modify':
                $this->admin_competition = new Admin_Competition( $this->racketmanager );
                $this->admin_competition->display_season_modify_page();
                break;
            case 'config':
                $this->admin_competition = new Admin_Competition( $this->racketmanager );
                $this->admin_competition->display_config_page();
                break;
            case 'event-config':
                $this->admin_event = new Admin_Event( $this->racketmanager );
                $this->admin_event->display_config_page();
                break;
            case 'event':
                $this->display_event_page();
                break;
            case 'constitution':
                $this->display_constitution_page();
                break;
            case 'league':
                $this->display_league_page();
                break;
            case 'matches':
                $this->display_matches_page();
                break;
            case 'match':
                $this->display_match_page();
                break;
            case 'plan':
                $this->display_schedule_page();
                break;
            case 'teams':
                $this->display_teams_list();
                break;
            case 'team':
                $this->admin_club = new Admin_Club( $this->racketmanager );
                $this->admin_club->display_team_page();
                break;
            case 'contact':
                $this->display_contact_page();
                break;
            default:
                $this->display_leagues_page();
                break;
        }
    }
    /**
     * Display leagues page
     */
    public function display_leagues_page(): void {
        $validator = new Validator();
        $validator = $validator->capability( 'edit_leagues' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            $this->show_message();
            return;
        }
        $competition_type  = 'league';
        $type              = '';
        $season            = '';
        $standalone        = true;
        $competition_query = array( 'type' => $competition_type );
        $page_title        = ucfirst( $competition_type ) . ' ' . __( 'Competitions', 'racketmanager' );
        require_once RACKETMANAGER_PATH . 'templates/admin/show-competitions.php';
    }

    /**
     * Display season overview
     */
    public function display_overview_page(): void {
        $this->apply_flash_message();
        $competition_id = null;
        $competition    = null;
        $validator      = new Validator();
        $validator      = $validator->capability( 'edit_leagues' );
        if  ( empty( $validator->error ) ) {
            $competition_id = isset( $_GET['competition_id'] ) ? intval( $_GET['competition_id'] ) : null;
            $validator      = $validator->competition( $competition_id );
            if ( empty( $validator->error ) ) {
                $competition = $this->competition_service->get_competition( $competition_id );
                $season      = isset( $_GET['season'] ) ? sanitize_text_field( wp_unslash( $_GET['season'] ) ) : null;
                $seasons     = $competition->get_seasons();
                $validator   = $validator->season_set( $season, $seasons );
            }
        }
        if ( ! empty( $validator->error ) ) {
            if ( empty( $validator->msg ) ) {
                $this->set_message( $validator->err_msgs[0] ?? '', true );
            } else {
                $this->set_message( $validator->msg, true );
            }
            $this->show_message();
            return;
        }
        if ( isset( $_POST['contactTeam'] ) && $this->contact_teams() ) {
            return;
        }
        $this->show_message();
        $competition_overview = $this->competition_service->get_competition_overview( $competition_id, null !== $season ? (int) $season : null );
        $competition_events   = $this->competition_service->get_events_with_details_for_competition( $competition_id, (string) $season );
        $tab                  = 'overview';
        $current_season       = (object) $competition->get_season_by_name( (string) $season ) ?? array();
        if ( isset( $current_season->date_closing ) && $current_season->date_closing <= gmdate( 'Y-m-d' ) ) {
            $current_season->is_active = true;
        } else {
            $current_season->is_active = false;
        }
        $current_season->is_open = false;
        $current_season->entries = $this->competition_service->get_clubs_for_competition( $competition_id, null !== $season ? (int) $season : null );
        $entry_urls              = $this->admin_entry_link_presenter->club_entry_urls( $competition, (string) $season, (array) ( $current_season->entries ?? array() ) );
        require_once RACKETMANAGER_PATH . 'templates/admin/league/show-season.php';
    }
    /**
     * Display setup
     */
    public function display_setup_page(): void {
        $this->apply_flash_message();
        $validator = new Validator();
        $validator = $validator->capability( 'edit_matches' );
        if (  ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            $this->show_message();
            return;
        }
        if ( isset( $_POST['action'] ) || isset( $_POST['rank'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $result = $this->league_setup_dispatcher->handle( $_GET, $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified by the action guard.
            if ( $this->deliver_league_action_result( $result, $_GET ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                return;
            }
        }
        $this->show_message();
        $season         = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
        $competition_id = isset( $_GET['competition_id'] ) ? intval( $_GET['competition_id'] ) : null; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $validator      = $validator->competition( $competition_id );
        if ( empty( $validator->error ) ) {
            $competition = $this->competition_service->get_competition( $competition_id );
            $current_season = $competition->get_season_by_name( (string) $season );
            require_once RACKETMANAGER_PATH . 'templates/admin/includes/setup.php';
        } else {
            $this->set_message( $validator->err_msgs[0] ?? $validator->msg, true );
            $this->show_message();
        }
    }
    /**
     * Display event setup
     */
    public function display_setup_event_page(): void {
        $this->apply_flash_message();
        $validator = new Validator();
        $validator = $validator->capability( 'edit_matches' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            $this->show_message();
            return;
        }
        if ( isset( $_POST['action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $result = $this->event_setup_dispatcher->handle( $_GET, $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified by the action guard.
            if ( $this->deliver_league_action_result( $result, $_GET ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                return;
            }
        }
        $this->show_message();
        $season   = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
        $event_id = isset( $_GET['event_id'] ) ? intval( $_GET['event_id'] ) : null; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( $event_id ) {
            $event = $this->get_event_repository()->find_by_id( $event_id );
            if ( $event ) {
                $current_season = $event->get_season_by_name( (string) $season );
                require_once RACKETMANAGER_PATH . 'templates/admin/includes/setup.php';
            }
        }
    }
    /**
     * Hydrate the league table rows into League_Team objects for the crosstable view.
     *
     * Non-objects and rows the repository cannot hydrate are dropped and the result is
     * re-indexed 0..n-1: the template derives the displayed rank from that position, which
     * matches the sequential lists returned by get_league_teams()/get_standings().
     *
     * @param array $teams Team rows (League_Team objects or raw table rows).
     *
     * @return League_Team[] Hydrated teams in their original order.
     */
    private function hydrate_crosstable_teams( array $teams ): array {
        $hydrated   = array();
        $repository = null;
        foreach ( $teams as $team ) {
            if ( ! $team instanceof League_Team && is_object( $team ) ) {
                $repository ??= $this->racketmanager->container->get( 'league_team_repository' );
                $team         = $repository->hydrate( $team );
            }
            if ( $team instanceof League_Team ) {
                $hydrated[] = $team;
            }
        }
        return $hydrated;
    }

    /**
     * Pre-compute the crosstable score matrix so the template only echoes values.
     *
     * @param League        $league League.
     * @param League_Team[] $teams  Teams in display order (rows and columns).
     *
     * @return array<int, array<int, string>> Matrix keyed by [ row team id ][ column team id ].
     */
    private function build_crosstable_cells( League $league, array $teams ): array {
        $cells = array();
        foreach ( $teams as $row_team ) {
            foreach ( $teams as $column_team ) {
                $cells[ (int) $row_team->id ][ (int) $column_team->id ] = $this->get_league_query_service()->get_crosstable_field( $league, (int) $row_team->id, (int) $column_team->id );
            }
        }
        return $cells;
    }

    /**
     * Display league page
     */
    public function display_league_page(): void {
        global $league;

        $this->apply_flash_message();
        if ( ! current_user_can( 'view_leagues' ) ) {
            $this->set_message( $this->invalid_permissions, true );
            $this->show_message();
        } else {
            $league_id = isset( $_GET['league_id'] ) ? intval( $_GET['league_id'] ) : null;
            if ( $league_id ) {
                $league = $this->league_service->get_league( $league_id );
                if ( $league ) {
                    $seasons   = $league->event->get_seasons();
                    $league_id = $league->id;
                    $this->get_league_query_service()->set_season( $league );
                    $season      = $league->get_season();
                    $league_mode = ( isset( $league->event->competition->settings['mode'] ) ? ( $league->event->competition->settings['mode'] ) : '' );
                    $requested_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( strval( $_GET['tab'] ) ) ) : '';
                    $allowed_tabs  = array( 'standings', 'crosstable', 'matches', 'preliminary' );
                    $tab           = in_array( $requested_tab, $allowed_tabs, true ) ? $requested_tab : 'standings';
                    $match_day   = false;
                    // phpcs:disable WordPress.Security.NonceVerification.Missing
                    if ( isset( $_POST['contactTeam'] ) ) {
                        if ( $this->contact_teams() ) {
                            return;
                        }
                    } elseif ( isset( $_POST['doAction'] ) || ( isset( $_POST['action'] ) && 'addTeamsToLeague' === $_POST['action'] ) || isset( $_POST['saveRanking'] ) || isset( $_POST['randomRanking'] ) || isset( $_POST['ratingPointsRanking'] ) || ( isset( $_POST['updateLeague'] ) && 'teams_manual' === $_POST['updateLeague'] ) ) {
                        $result = $this->league_team_dispatcher->handle( $_GET, $_POST, $league );
                        if ( $this->deliver_league_action_result( $result, $_GET ) ) {
                            return;
                        }
                    } elseif ( isset( $_POST['delMatches'] ) || ( isset( $_POST['updateLeague'] ) && in_array( $_POST['updateLeague'], array( 'match', 'results' ), true ) ) ) {
                        $result = $this->league_match_dispatcher->handle( $_GET, $_POST, $league );
                        if ( $this->deliver_league_action_result( $result, $_GET ) ) {
                            return;
                        }
                    } elseif ( isset( $_POST['updateLeague'] ) && 'team' === $_POST['updateLeague'] ) {
                        // TODO: handle update of team captain/contact/match day/match time
                        if ( $league->is_championship ) {
                            $tab = 'preliminary';
                        }
                    }
                    $this->show_message();
                    // phpcs:enable WordPress.Security.NonceVerification.Missing

                    // check if league is a cup championship.
                    $cup = 'championship' === $league_mode;
                    // phpcs:disable WordPress.Security.NonceVerification.Recommended
                    $group     = isset( $_GET['group'] ) ? sanitize_text_field( wp_unslash( $_GET['group'] ) ) : '';
                    $team_id   = isset( $_GET['team_id'] ) ? intval( $_GET['team_id'] ) : false;
                    if ( isset( $_GET['match_day'] ) ) {
                        if ( -1 !== $_GET['match_day'] ) {
                            $match_day = intval( $_GET['match_day'] );
                            $this->get_league_query_service()->set_match_day( $league, $match_day );
                        }
                        $tab = 'matches';
                    } elseif ( 'current_match_day' === $league->match_display ) {
                        $this->get_league_query_service()->set_match_day( $league, 'current' );
                    } elseif ( 'all' === $league->match_display ) {
                        $this->get_league_query_service()->set_match_day( $league, -1 );
                    }
                    // phpcs:enable WordPress.Security.NonceVerification.Recommended
                    $options    = $this->racketmanager->options;
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
                    if ( $league->num_matches_per_page > 0 ) {
                        $match_args['limit'] = $league->num_matches_per_page;
                    }
                    if ( empty( $league->event->get_seasons() ) ) {
                        $this->set_message( __( 'You need to add at least one season for the competition', 'racketmanager' ), true );
                        $this->show_message();
                    }
                    $teams = $this->get_league_query_service()->get_league_teams( $league,
                        array(
                            'season' => $season,
                            'cache'  => false,
                        )
                    );
                    $finals = array();
                    if ( 'championship' === $league_mode ) {
                        $finals = $this->present_league_finals( $league, (string) $season );
                    } else {
                        $matches = $this->prepare_league_matches( $league, $match_args, (string) $season, $group );
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
                        $this->standings_service ??= $this->racketmanager->container->get( 'standings_service' );
                        $teams = $this->standings_service->get_standings( $league, $teams, (int) $match_day, $mode );
                    }
                    if ( isset( $_GET['match_day'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                        $tab = 'matches';
                    }
                    $crosstable_teams = array();
                    $crosstable_cells = array();
                    if ( ! $cup ) {
                        $crosstable_teams = $this->hydrate_crosstable_teams( is_array( $teams ) ? $teams : array() );
                        $crosstable_cells = $this->build_crosstable_cells( $league, $crosstable_teams );
                    }
                    require_once RACKETMANAGER_PATH . 'templates/admin/show-league.php';
                } else {
                    $this->set_message( __( 'League not found', 'racketmanager' ), true );
                    $this->show_message();
                }
            } else {
                $this->set_message( __( 'League id not found', 'racketmanager' ), true );
                $this->show_message();
            }
        }
    }
    /**
     * Display event page
     */
    public function display_event_page(): void {
        $validator = new Validator();
        $validator = $validator->capability( 'edit_leagues' );
        if ( empty( $validator->error ) ) {
            $event_id = isset( $_GET['event_id'] ) ? intval( $_GET['event_id'] ) : null;
            $validator = $validator->event( $event_id );
        }
        if ( ! empty( $validator->error ) ) {
            if ( empty( $validator->msg ) ) {
                $this->set_message( $validator->err_msgs[0] ?? '', true );
            } else {
                $this->set_message( $validator->msg, true );
            }
            $this->show_message();
            return;
        }
        if ( ! current_user_can( 'edit_leagues' ) ) {
            $this->set_message( $this->invalid_permissions, true );
            $this->show_message();
            return;
        }
        $tab       = 'leagues';
        $event     = $this->get_event_repository()->find_by_id( $event_id );
        $league_id = false;
        if ( isset( $_POST['addLeague'] ) || ( isset( $_POST['doActionLeague'] ) && isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) ) {
            $result = $this->league_event_dispatcher->handle( $_GET, $_POST );
            if ( $this->deliver_league_action_result( $result, $_GET ) ) {
                return;
            }
        } elseif ( isset( $_GET['edit_league'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $league_id    = intval( $_GET['edit_league'] );  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $league_edit  = $this->league_service->get_league( $league_id );
            $league_title = $league_edit->title;
        }
        $this->apply_flash_message();
        $this->show_message();
        if ( ! isset( $season ) ) {
            $event_season = $event->current_season['name'] ?? '';
            $season       = ( isset( $_GET['season'] ) ? sanitize_text_field( wp_unslash( $_GET['season'] ) ) : $event_season );  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        }
        $leagues            = ( isset( $event ) && $event instanceof \Racketmanager\Domain\Competition\Event ) ? $this->get_event_query_service()->get_leagues( $event ) : array();
        $league_matches_map = array();
        foreach ( $leagues as $league_item ) {
            if ( ! ( $league_item->is_championship ?? false ) ) {
                if ( $league_item instanceof \Racketmanager\Domain\Competition\League ) {
                    $this->get_league_query_service()->set_num_matches( $league_item, true );
                }
                $league_matches_map[ $league_item->id ] = $league_item->num_matches_total ?? 0;
            }
        }
        require RACKETMANAGER_PATH . 'templates/admin/event/show-leagues.php';
    }
    /**
     * Display constitution page
     */
    public function display_constitution_page(): void {
        if ( ! current_user_can( 'edit_leagues' ) ) {
            $this->set_message( $this->invalid_permissions, true );
            $this->show_message();
        } elseif ( isset( $_GET['event_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $event_id     = intval( $_GET['event_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $event        = $this->get_event_repository()->find_by_id( $event_id );
            $league_id    = false;
            $league_title = '';
            $season_id    = false;
            $season_data  = array(
                'name'           => '',
                'num_match_days' => '',
                'homeAndAway'    => '',
            );
            $club_id      = 0;
            if ( ( isset( $_POST['doActionConstitution'] ) && isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) || isset( $_POST['saveConstitution'] ) || isset( $_POST['promoteRelegate'] ) || ( isset( $_POST['action'] ) && 'addTeamsToLeague' === $_POST['action'] ) || isset( $_POST['generate_matches'] ) ) {
                $result = $this->league_constitution_dispatcher->handle( $_GET, $_POST, $event );
                if ( $this->deliver_league_action_result( $result, $_GET ) ) {
                    return;
                }
            }
            $this->apply_flash_message();
            $this->show_message();
            if ( ! isset( $season ) ) {
                $event_season = $event->current_season['name'] ?? '';
                $season       = ( isset( $_GET['season'] ) ? sanitize_text_field( wp_unslash( $_GET['season'] ) ) : $event_season );  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            }
            $seasons             = $this->season_service->get_all_seasons();
            $leagues             = ( isset( $event ) && $event instanceof \Racketmanager\Domain\Competition\Event ) ? $this->get_event_query_service()->get_leagues( $event ) : array();
            $event_seasons       = ( isset( $event ) && method_exists( $event, 'get_seasons' ) ) ? $event->get_seasons() : array();

            $num_teams           = 0;
            $league_active_teams = array();
            $teams               = array();
            $constitution_action = 'update';
            $constitution_exists = false;
            $updates_allowed     = false;
            $latest_season       = '';
            $latest_event_season = '';
            $standing_status     = \Racketmanager\Util\Util_Lookup::get_standing_statuses();

            $has_content = ! ( ( empty( $event->is_box ) && empty( $seasons ) ) || ( empty( $event->leagues ) && empty( $leagues ) ) || empty( $event_seasons ) );

            if ( $has_content ) {
                $num_teams = ( isset( $event ) && $event instanceof \Racketmanager\Domain\Competition\Event ) ? $this->get_event_query_service()->get_teams( $event, array( 'count' => true, 'season' => $season ) ) : 0;
                foreach ( $leagues as $l ) {
                    if ( method_exists( $l, 'get_num_teams' ) ) {
                        $league_active_teams[ $l->id ] = $this->get_league_query_service()->get_num_teams( $l, 'active', true );
                    }
                }

                $today               = gmdate( 'Y-m-d' );
                $latest_season_dtls  = $event->current_season ?? array();
                $latest_season       = $latest_season_dtls['name'] ?? '';
                $latest_competition  = ( isset( $event->competition ) && method_exists( $event->competition, 'get_season_by_name' ) ) ? $event->competition->get_season_by_name( $latest_season ) : null;
                $competition_seasons = ( isset( $event->competition ) && method_exists( $event->competition, 'get_seasons' ) ) ? $event->competition->get_seasons() : array();
                $updates_allowed     = ! empty( $latest_competition['date_start'] ) && $latest_competition['date_start'] > $today;

                $latest_event_season = $event->current_season['name'] ?? '';
                $stop_next           = false;
                foreach ( array_reverse( $competition_seasons ) as $comp_season ) {
                    $comp_season_name = is_array( $comp_season ) ? ( $comp_season['name'] ?? '' ) : ( (string) $comp_season );
                    if ( $stop_next ) {
                        $latest_event_season = $comp_season_name;
                        break;
                    }
                    if ( $latest_season === $comp_season_name ) {
                        $stop_next = true;
                    }
                }

                $teams = ( isset( $event ) && $event instanceof \Racketmanager\Domain\Competition\Event ) ? $this->get_event_query_service()->get_constitution(
                    $event,
                    array(
                        'season'    => $latest_season,
                        'oldseason' => $latest_event_season,
                    )
                ) : null;
                $constitution_action = 'update';
                $constitution_exists = true;
                if ( ! $teams ) {
                    $teams               = ( isset( $event ) && $event instanceof \Racketmanager\Domain\Competition\Event ) ? $this->get_event_query_service()->build_constitution( $event, array( 'season' => $latest_event_season ) ) : array();
                    $constitution_action = 'insert';
                    $constitution_exists = false;
                }
            }

            require RACKETMANAGER_PATH . 'templates/admin/league/show-constitution.php';
        }
    }
    /**
     * Display schedule page
     */
    public function display_schedule_page(): void {
        $validator = new Validator();
        $validator = $validator->capability( 'edit_leagues' );
        if ( empty( $validator->error ) ) {
            $competition_id = isset( $_GET['competition_id'] ) ? intval( $_GET['competition_id'] ) : null;
            $validator      = $validator->competition( $competition_id );
            if ( ! empty( $validator->error ) ) {
                $this->set_message( $validator->err_msgs[0], true );
            }
        } else {
            $this->set_message( $validator->msg, true );
        }
        if ( ! empty( $validator->error ) ) {
            $this->show_message();
            return;
        }
        if ( isset( $_POST['scheduleAction'] ) ) {
            $result = $this->league_schedule_dispatcher->handle( $_GET, $_POST );
            if ( $this->deliver_league_action_result( $result, $_GET ) ) {
                return;
            }
        }
        $flash           = $this->apply_flash_message();
        $schedule_report = is_array( $flash['flash_data'] ?? null ) ? $flash['flash_data'] : array();
        $this->show_message();
        $competition_id = intval( $_GET['competition_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $competition    = $this->competition_service->get_competition( $competition_id );
        $league_id      = false;
        $league_title   = '';
        $season_id      = false;
        $season_data    = array(
            'name'           => '',
            'num_match_days' => '',
            'homeAndAway'    => '',
        );
        $club_id        = 0;
        if ( ! isset( $season ) ) {
            $event_season = $competition->current_season['name'] ?? '';
            $season       = ( isset( $_GET['season'] ) ? sanitize_text_field( wp_unslash( $_GET['season'] ) ) : (string) $event_season );  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        }

        $raw_events  = $this->competition_service->get_events_for_competition( $competition_id );
        $events_data = array();
        foreach ( ( is_array( $raw_events ) ? $raw_events : array() ) as $event_item ) {
            $event = is_object( $event_item )
                ? $event_item
                : $this->get_event_repository()->find_by_id( (int) $event_item );
            if ( ! $event ) {
                continue;
            }

            $event_id   = (int) ( method_exists( $event, 'get_id' ) ? $event->get_id() : ( $event->id ?? 0 ) );
            $event_name = (string) ( method_exists( $event, 'get_name' ) ? $event->get_name() : ( $event->name ?? '' ) );
            // Counts (and the "Send fixtures" affordance) follow the season being viewed, which is also
            // the season the schedule/delete actions act on; fall back to the event's own season only
            // when no season could be resolved for the page.
            $event_season = (string) $season;
            if ( '' === $event_season ) {
                $event_season = (string) ( method_exists( $event, 'get_season' ) ? $event->get_season() : ( $event->season ?? '' ) );
            }

            $match_count            = $this->get_event_repository()->count_by_event_season( $event_id, $event_season, false );
            $match_completion_count = $this->get_event_repository()->count_by_event_season( $event_id, $event_season, true );

            $events_data[] = array(
                'id'                     => $event_id,
                'name'                   => $event_name,
                'season'                 => $event_season,
                'match_count'            => $match_count,
                'match_completion_count' => $match_completion_count,
            );
        }

        $vm = $this->resolve_league_schedule_presenter()->present(
            competition: $competition,
            season: (string) $season,
            events: $events_data,
            schedule_report: $schedule_report,
        );

        $events = $vm->events;

        require RACKETMANAGER_PATH . 'templates/admin/league/show-schedule.php';
    }

    public function display_matches_page(): void {
        $event_id  = null;
        $event     = null;
        $season    = null;
        $validator = new Validator();
        $validator = $validator->capability( 'edit_leagues' );
        if ( empty( $validator->error ) ) {
            $event_id = isset( $_GET['event_id'] ) ? intval( $_GET['event_id'] ) : null;
            $validator = $validator->event( $event_id );
            if ( empty( $validator->error ) ) {
                $event     = $this->get_event_repository()->find_by_id( $event_id );
                $season    = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
                $validator = $validator->season_set( null === $season ? null : (string) $season, $event->get_seasons() );
            }
        }
        if ( ! empty( $validator->error ) ) {
            if ( empty( $validator->msg ) ) {
                $this->set_message( $validator->err_msgs[0], true );
            } else {
                $this->set_message( $validator->msg, true );
            }
            $this->show_message();
            return;
        }
        $matches = ( isset( $event ) && $event instanceof \Racketmanager\Domain\Competition\Event ) ? $this->get_event_query_service()->get_matches(
            $event,
            array(
                'event_id' => $event->id,
                'season'   => $season,
                'orderby'  => array(
                    'match_day' => 'ASC',
                    'date'      => 'ASC',
                    'home_team' => 'ASC',
                    'league_id' => 'ASC',
                ),
            )
        ) : array();
        require_once RACKETMANAGER_PATH . 'templates/admin/event/show-matches.php';
    }
    /**
     * Display match editing page
     */
    public function display_match_page(): void {
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
                $league    = $this->league_service->get_league( $league_id );
                if ( ! $league ) {
                    $this->set_message( __( 'League not found', 'racketmanager' ), true );
                    $this->show_message();
                    return;
                }
                $this->get_league_query_service()->set_season( $league );
                // check if league is a cup championship.
                $cup = $league->event->competition->is_championship ?? false;
            }

            $matches = array();
            if ( isset( $_GET['edit'] ) ) {
                $match_id     = intval( $_GET['edit'] );
                $match        = $this->fixture_repository->find_by_id( $match_id );
                if ( ! $match ) {
                    $this->set_message( __( 'Match not found', 'racketmanager' ), true );
                    $this->show_message();
                    return;
                }
                $mode         = 'edit';
                $edit         = true;
                $form_title   = __( 'Edit Match', 'racketmanager' );
                $submit_title = $form_title;
                if ( isset( $match->final_round ) && '' !== $match->final_round ) {
                    $cup             = true;
                    $single_cup_game = true;
                }
                $league_id  = $match->league_id;
                if ( ! $league && $league_id ) {
                    $league = $this->league_service->get_league( $league_id );
                    if ( $league ) {
                        $this->get_league_query_service()->set_season( $league );
                    }
                }
                $matches[0] = $match;
                $match_day  = $match->match_day;
                $final_key   = $match->final_round ?? '';

                $max_matches = 1;
            }

            if ( ! $league ) {
                $this->set_message( __( 'League not found', 'racketmanager' ), true );
                $this->show_message();
                return;
            }

            $season = $league->current_season['name'] ?? ( method_exists( $league, 'get_season' ) ? (string) $league->get_season() : '' );

            // select first group if none is selected and league is cup championship.
            if ( $cup && empty( $group ) ) {
                $groups = ($league->groups ?? '');
                if ( ! is_array( $groups ) ) {
                    $groups = explode( ';', $groups );
                }
                $group = $groups[0] ?? '';
            }

            if ( isset( $_GET['edit'] ) ) {
                // Already populated above.
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

                $matches     = $this->get_league_query_service()->get_matches( $league, $match_args );
                $max_matches = count( $matches );
            } elseif ( isset( $_GET['final'] ) ) {
                $is_finals = true;
                $final_key = isset( $_GET['final'] ) && '' !== $_GET['final'] ? sanitize_text_field( wp_unslash( $_GET['final'] ) ) : $league->championship->get_current_final_key();
                $mode      = isset( $_GET['mode'] ) ? sanitize_text_field( wp_unslash( $_GET['mode'] ) ) : null;
                $edit      = 'edit' === $mode;

                $final           = $league->championship->get_finals( $final_key );
                $num_first_round = $league->championship->num_teams_first_round;

                $max_matches = $final['num_matches'];

                if ( 'add' === $mode ) {
                    /* translators: %s: round name */
                    $form_title = sprintf( __( 'Add Matches - %s', 'racketmanager' ), Util::get_final_name( $final_key ) );
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
                        $matches[ $h ]->hour    = $league->event->competition->settings['default_match_start_time']['hour'];
                        $matches[ $h ]->minutes = $league->event->competition->settings['default_match_start_time']['minutes'];
                    }
                } else {
                    /* translators: %s: round name */
                    $form_title = sprintf( __( 'Edit Matches - %s', 'racketmanager' ), Util::get_final_name( $final_key ) );
                    $match_args = array(
                        'final'   => $final_key,
                        'orderby' => array(
                            'id' => 'ASC',
                        ),
                    );
                    if ( 'final' !== $final_key && ! empty( $league->current_season['home_away'] ) && 'true' === $league->current_season['home_away'] ) {
                        $match_args['leg'] = 1;
                    }
                    $matches = $this->get_league_query_service()->get_matches( $league, $match_args );
                }
                $submit_title = $form_title;
            } else {
                $mode = 'add';
                $edit = false;
                $bulk = $cup;

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
                    $matches[ $i ]->hour    = $league->event->competition->settings['default_match_start_time']['hour'];
                    $matches[ $i ]->minutes = $league->event->competition->settings['default_match_start_time']['minutes'];
                }
            }

            if ( $single_cup_game ) {
                $final       = $league->championship->get_finals( $final_key );
                $final_teams = $league->championship->get_final_teams( $final['key'] );
                if ( is_numeric( $match->home_team ) ) {
                    try {
                        $home_team = $this->team_service->get_team_by_id( (int) $match->home_team );
                    } catch ( \Exception ) {
                        $home_team = null;
                    }
                    $home_title = $home_team?->title;
                } else {
                    $home_team = $final_teams[ $match->home_team ] ?? null;
                    if ( $home_team ) {
                        $home_title = $home_team->title;
                    } else {
                        $home_title = null;
                    }
                }
                if ( is_numeric( $match->away_team ) ) {
                    try {
                        $away_team = $this->team_service->get_team_by_id( (int) $match->away_team );
                    } catch ( \Exception ) {
                        $away_team = null;
                    }
                    $away_title = $away_team?->title;
                } else {
                    $away_team = $final_teams[ $match->away_team ] ?? null;
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
                $teams = $this->get_league_query_service()->get_league_teams( $league,
                    array(
                        'season'  => $season,
                        'orderby' => array( 'title' => 'ASC' ),
                    )
                );
            }
            //phpcs:enable WordPress.Security.NonceVerification.Recommended
            include_once RACKETMANAGER_PATH . 'templates/admin/includes/match.php';
        }
    }
    /**
     * Build league menu
     *
     * @param object|null $league Optional league entity.
     * @return array
     */
    private function get_menu( ?object $league = null ): array {
        if ( null === $league ) {
            $league_id = isset( $_GET['league_id'] ) ? intval( $_GET['league_id'] ) : null;
            $league    = $league_id ? $this->league_service->get_league( $league_id ) : null;
        }
        if ( ! $league ) {
            return array();
        }
        $season = ( isset( $_GET['season'] ) ? sanitize_text_field( wp_unslash( $_GET['season'] ) ) : ( $league->current_season ?? '' ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
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
            'callback' => array( $this, 'display_contact_page' ),
            'cap'      => 'edit_teams',
            'show'     => true,
        );
        $menu            = apply_filters( 'racketmanager_league_menu_' . $sport, $menu, $league->id, $season );
        return apply_filters( 'racketmanager_league_menu_' . $league->mode, $menu, $league->id, $season );
    }

    /**
     * Fetch league matches and present them as preformatted view models for the admin league template.
     *
     * @param object $league     League entity.
     * @param array  $match_args Match query arguments.
     * @param string $season     Season name.
     * @param string $group      Group name.
     *
     * @return array
     */
    public function prepare_league_matches( object $league, array $match_args, string $season, string $group ): array {
        $match_args['reset_query_args'] = true;
        $matches                        = $this->get_league_query_service()->get_matches( $league, $match_args );
        $this->get_league_query_service()->set_num_matches( $league );

        return $this->resolve_league_matches_presenter()->present_collection( $matches, $league, $season, $group );
    }

    private function resolve_league_matches_presenter(): Admin_League_Matches_Presenter {
        $container = $this->racketmanager->container ?? null;
        if ( $container instanceof Simple_Container && $container->has( 'admin_league_matches_presenter' ) ) {
            return $container->get( 'admin_league_matches_presenter' );
        }

        return new Admin_League_Matches_Presenter( new Admin_Date_Presenter() );
    }

    private function resolve_league_schedule_presenter(): Admin_League_Schedule_Presenter {
        if ( $this->admin_league_schedule_presenter instanceof Admin_League_Schedule_Presenter ) {
            return $this->admin_league_schedule_presenter;
        }

        $container = $this->racketmanager->container ?? null;
        if ( $container instanceof Simple_Container && $container->has( 'admin_league_schedule_presenter' ) ) {
            return $container->get( 'admin_league_schedule_presenter' );
        }

        throw new \RuntimeException( 'Admin_League_Schedule_Presenter not found in container.' );
    }
}
