<?php
declare( strict_types=1 );
/**
 * League Standings Admin Controller
 *
 * Handles view=league on league screens (standings, crosstable, match plan, championship finals).
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\Presenters\Admin_Championship_Finals_Presenter;
use Racketmanager\Admin\Presenters\Admin_League_Matches_Presenter;
use Racketmanager\Admin\View_Models\League_Standings_Page_View_Model;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\League_Team;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\League_Not_Found_Exception;
use Racketmanager\Admin\Presenters\Admin_Message_Mapper;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Services\Admin\League\League_Contact_Action_Dispatcher;
use Racketmanager\Services\Admin\League\League_Match_Action_Dispatcher;
use Racketmanager\Services\Admin\League\League_Team_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\Fixture\Fixture_Detail_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Standings\Standings_Service;

final readonly class League_Standings_Admin_Controller {

    public function __construct(
        private League_Service $league_service,
        private League_Query_Service $league_query_service,
        private League_Team_Action_Dispatcher $league_team_dispatcher,
        private League_Match_Action_Dispatcher $league_match_dispatcher,
        private League_Contact_Action_Dispatcher $league_contact_dispatcher,
        private Admin_Championship_Finals_Presenter $finals_presenter,
        private Admin_League_Matches_Presenter $matches_presenter,
        private Fixture_Detail_Service $fixture_detail_service,
        private Standings_Service $standings_service,
        private League_Team_Repository_Interface $league_team_repository,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * @param array<string, mixed> $query Typically $_GET
     * @param array<string, mixed> $post  Typically $_POST
     *
     * @return array{view_model?:League_Standings_Page_View_Model, redirect?:string, message?:string, message_type?:bool|string, flash_data?:array<string, mixed>}
     *
     * @throws Invalid_Status_Exception
     * @throws League_Not_Found_Exception
     */
    public function league_page( array $query, array $post ): array {
        $this->action_guard->assert_capability( 'view_leagues' );

        $request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
        $is_post        = 'POST' === strtoupper( $request_method );
        $league_id = isset( $query['league_id'] ) ? intval( $query['league_id'] ) : intval( $post['league_id'] ?? 0 );

        if ( ! $league_id ) {
            throw new League_Not_Found_Exception( __( 'League id not found', 'racketmanager' ) );
        }

        $league = $this->league_service->get_league( $league_id );
        if ( ! $league instanceof League ) {
            throw new League_Not_Found_Exception( __( 'League not found', 'racketmanager' ) );
        }

        $this->league_query_service->set_season( $league );

        // POST handling
        if ( $is_post ) {
            if ( isset( $post['contactTeam'] ) ) {
                $result = $this->league_contact_dispatcher->handle( $post );

                return array(
                    'redirect'     => Admin_Redirect_Url_Builder::league_screen( $query ),
                    'message'      => $result->message,
                    'message_type' => Admin_Message_Mapper::to_legacy( $result->message_type ),
                );
            }

            if (
                isset( $post['doAction'] )
                || ( isset( $post['action'] ) && 'addTeamsToLeague' === $post['action'] )
                || isset( $post['saveRanking'] )
                || isset( $post['randomRanking'] )
                || isset( $post['ratingPointsRanking'] )
                || ( isset( $post['updateLeague'] ) && 'teams_manual' === $post['updateLeague'] )
            ) {
                $result = $this->league_team_dispatcher->handle( $query, $post, $league );

                $extra_args = array();
                if ( null !== $result->tab_override && '' !== $result->tab_override ) {
                    $extra_args['tab'] = $result->tab_override;
                }
                $extra_args = array_merge( $extra_args, $result->query_params );

                return array(
                    'redirect'     => Admin_Redirect_Url_Builder::league_screen( $query, $extra_args ),
                    'message'      => $result->message,
                    'message_type' => Admin_Message_Mapper::to_legacy( $result->message_type ),
                    'flash_data'   => $result->flash_data,
                );
            }

            if (
                isset( $post['delMatches'] )
                || ( isset( $post['updateLeague'] ) && in_array( $post['updateLeague'], array( 'match', 'results' ), true ) )
            ) {
                $result = $this->league_match_dispatcher->handle( $query, $post, $league );

                $extra_args = array();
                if ( null !== $result->tab_override && '' !== $result->tab_override ) {
                    $extra_args['tab'] = $result->tab_override;
                }
                $extra_args = array_merge( $extra_args, $result->query_params );

                return array(
                    'redirect'     => Admin_Redirect_Url_Builder::league_screen( $query, $extra_args ),
                    'message'      => $result->message,
                    'message_type' => Admin_Message_Mapper::to_legacy( $result->message_type ),
                    'flash_data'   => $result->flash_data,
                );
            }
        }

        // GET rendering
        $seasons       = $league->event->get_seasons();
        $messages      = array();
        $season        = (string) $league->get_season();
        $league_mode   = (string) ( $league->event->competition->settings['mode'] ?? '' );
        $requested_tab = isset( $query['tab'] ) ? sanitize_key( wp_unslash( strval( $query['tab'] ) ) ) : '';
        $allowed_tabs  = array( 'standings', 'crosstable', 'matches', 'preliminary' );
        $tab           = in_array( $requested_tab, $allowed_tabs, true ) ? $requested_tab : 'standings';
        $cup           = 'championship' === $league_mode;
        $group         = isset( $query['group'] ) ? sanitize_text_field( wp_unslash( strval( $query['group'] ) ) ) : '';
        $team_id       = isset( $query['team_id'] ) ? intval( $query['team_id'] ) : false;
        $match_day     = false;

        if ( $is_post && isset( $post['updateLeague'] ) && 'team' === $post['updateLeague'] && $league->is_championship ) {
            $tab = 'preliminary';
        }

        if ( isset( $query['match_day'] ) ) {
            $match_day = intval( $query['match_day'] );
            $this->league_query_service->set_match_day( $league, $match_day );
            $tab = 'matches';
        } elseif ( 'current_match_day' === ( $league->match_display ?? '' ) ) {
            $this->league_query_service->set_match_day( $league, 'current' );
        } elseif ( 'all' === ( $league->match_display ?? '' ) ) {
            $this->league_query_service->set_match_day( $league, -1 );
        }

        $match_args = array(
            'final' => '',
            'cache' => false,
        );
        if ( '' !== $season ) {
            $match_args['season'] = $season;
        }
        if ( empty( $seasons ) ) {
            $messages = array(
                'message'      => __( 'You need to add at least one season for the competition', 'racketmanager' ),
                'message_type' => true,
            );
        }
        if ( '' !== $group ) {
            $match_args['group'] = $group;
        }
        if ( $team_id ) {
            $match_args['team_id'] = $team_id;
        }
        if ( ( $league->num_matches_per_page ?? 0 ) > 0 ) {
            $match_args['limit'] = $league->num_matches_per_page;
        }

        $teams = $this->league_query_service->get_league_teams(
            $league,
            array(
                'season' => $season,
                'cache'  => false,
            )
        );

        $finals  = array();
        $matches = array();
        if ( 'championship' === $league_mode ) {
            $finals = $this->finals_presenter->present_league_finals( $league, (string) $season, $this->league_query_service, $this->fixture_detail_service );
        } else {
            $matches = $this->prepare_league_matches( $league, $match_args, (string) $season, $group );
        }

        if ( isset( $query['match_paged'] ) ) {
            $tab = 'matches';
        }

        if ( isset( $query['standingstable'] ) ) {
            $get                 = sanitize_text_field( wp_unslash( strval( $query['standingstable'] ) ) );
            $standings_match_day = false;
            $mode                = 'all';
            if ( preg_match( '/match_day-\d/', $get, $hits ) ) {
                $res                 = explode( '-', $hits[0] );
                $standings_match_day = (int) $res[1];
            } elseif ( in_array( $get, array( 'home', 'away' ), true ) ) {
                $mode = htmlspecialchars( $get );
            }
            $teams = $this->standings_service->get_standings( $league, is_array( $teams ) ? $teams : array(), (int) $standings_match_day, $mode );
        }

        if ( isset( $query['match_day'] ) ) {
            $tab = 'matches';
        }

        $crosstable_teams = array();
        $crosstable_cells = array();
        if ( ! $cup ) {
            $crosstable_teams = $this->hydrate_crosstable_teams( is_array( $teams ) ? $teams : array() );
            $crosstable_cells = $this->build_crosstable_cells( $league, $crosstable_teams );
        }

        $options = get_option( 'racketmanager', array() );
        $menu    = $this->get_menu( $league, $season );

        $vm = new League_Standings_Page_View_Model(
            league: $league,
            season: $season,
            tab: $tab,
            teams: is_array( $teams ) ? $teams : array(),
            cup: $cup,
            finals: $finals,
            matches: $matches,
            crosstable_teams: $crosstable_teams,
            crosstable_cells: $crosstable_cells,
            match_day: $match_day,
            group: $group,
            team_id: $team_id,
            options: is_array( $options ) ? $options : array(),
            seasons: is_array( $seasons ) ? $seasons : array(),
            menu: $menu,
            league_mode: $league_mode,
        );

        return array_merge(
            $messages,
            array(
                'view_model' => $vm,
            )
        );
    }

    /**
     * @param array<int, mixed> $teams
     * @return League_Team[]
     */
    private function hydrate_crosstable_teams( array $teams ): array {
        $hydrated = array();
        foreach ( $teams as $team ) {
            if ( is_array( $team ) || ( is_object( $team ) && ! ( $team instanceof League_Team ) ) ) {
                $team = $this->league_team_repository->hydrate( $team );
            }
            if ( $team instanceof League_Team ) {
                $hydrated[] = $team;
            }
        }
        return $hydrated;
    }

    /**
     * @param League $league
     * @param League_Team[] $teams
     * @return array<int, array<int, string>>
     */
    private function build_crosstable_cells( League $league, array $teams ): array {
        $cells = array();
        foreach ( $teams as $row_team ) {
            foreach ( $teams as $column_team ) {
                $cells[ (int) $row_team->id ][ (int) $column_team->id ] = (string) $this->league_query_service->get_crosstable_field( $league, (int) $row_team->id, (int) $column_team->id );
            }
        }
        return $cells;
    }

    private function prepare_league_matches( League $league, array $match_args, string $season, string $group ): array {
        $match_args['reset_query_args'] = true;
        $matches                        = $this->league_query_service->get_matches( $league, $match_args );
        $this->league_query_service->set_num_matches( $league );

        return $this->matches_presenter->present_collection( is_array( $matches ) ? $matches : array(), $league, $season, $group );
    }

    private function get_menu( League $league, string $season ): array {
        $sport         = (string) ( $league->sport ?? '' );
        $menu          = array();
        $menu['teams'] = array(
            'title' => __( 'Add Teams', 'racketmanager' ),
            'cap'   => 'edit_teams',
            'show'  => true,
        );
        $menu['team']  = array(
            'title' => __( 'Add Team', 'racketmanager' ),
            'cap'   => 'edit_teams',
            'show'  => false,
        );
        $menu['match'] = array(
            'title' => __( 'Add Matches', 'racketmanager' ),
            'cap'   => 'edit_matches',
        );
        if ( $league->is_championship ) {
            $menu['match']['show'] = false;
            if ( ! empty( $league->event->competition->is_player_entry ) && empty( $league->championship->is_consolation ) ) {
                $menu['team']['show'] = true;
            }
        } else {
            $menu['match']['show'] = true;
        }
        $menu['contact'] = array(
            'title' => __( 'Contact', 'racketmanager' ),
            'cap'   => 'edit_teams',
            'show'  => true,
        );
        $menu            = apply_filters( 'racketmanager_league_menu_' . $sport, $menu, $league->id, $season );
        return apply_filters( 'racketmanager_league_menu_' . ( $league->mode ?? '' ), $menu, $league->id, $season );
    }
}
