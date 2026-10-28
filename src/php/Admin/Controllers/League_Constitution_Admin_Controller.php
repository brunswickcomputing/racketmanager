<?php
declare( strict_types=1 );
/**
 * League Constitution Admin Controller
 *
 * Handles display and actions for league constitution screen.
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\Presenters\Admin_Message_Mapper;
use Racketmanager\Admin\View_Models\League_Constitution_Page_View_Model;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Exceptions\Event_Not_Found_Exception;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Services\Admin\League\League_Constitution_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition\Event_Query_Service;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\Season_Service;
use Racketmanager\Util\Util_Lookup;

final readonly class League_Constitution_Admin_Controller {

    public function __construct(
        private Event_Repository_Interface $event_repository,
        private Season_Service $season_service,
        private Event_Query_Service $event_query_service,
        private League_Query_Service $league_query_service,
        private Action_Guard_Interface $action_guard,
        private League_Constitution_Action_Dispatcher $league_constitution_dispatcher,
    ) {
    }

    /**
     * @param array<string, mixed> $query Typically $_GET
     * @param array<string, mixed> $post  Typically $_POST
     *
     * @return array{view_model?: League_Constitution_Page_View_Model, redirect?: string, message?: string, message_type?: bool|string, flash_data?: array<string, mixed>}
     *
     * @throws Event_Not_Found_Exception
     */
    public function constitution_page( array $query, array $post ): array {
        $this->action_guard->assert_capability( 'edit_leagues' );

        $event_id = isset( $query['event_id'] ) ? intval( $query['event_id'] ) : ( isset( $post['event_id'] ) ? intval( $post['event_id'] ) : null );
        if ( ! $event_id ) {
            throw new Event_Not_Found_Exception( __( 'Event not found', 'racketmanager' ) );
        }

        $event = $this->event_repository->find_by_id( $event_id );
        if ( ! $event ) {
            throw new Event_Not_Found_Exception( __( 'Event not found', 'racketmanager' ) );
        }

        $is_constitution_mutation = ( isset( $post['doActionConstitution'] ) && isset( $post['action'] ) && 'delete' === $post['action'] )
            || isset( $post['saveConstitution'] )
            || isset( $post['promoteRelegate'] )
            || ( isset( $post['action'] ) && 'addTeamsToLeague' === $post['action'] )
            || isset( $post['generate_matches'] );

        if ( $is_constitution_mutation ) {
            $result = $this->league_constitution_dispatcher->handle( $query, $post, $event );

            $extra_args = array();
            if ( null !== $result->tab_override && '' !== $result->tab_override ) {
                $extra_args['tab'] = $result->tab_override;
            }
            $extra_args = array_merge( $extra_args, $result->query_params );

            $redirect = Admin_Redirect_Url_Builder::league_screen( $query, $extra_args );

            return array(
                'redirect'     => $redirect,
                'message'      => $result->message,
                'message_type' => Admin_Message_Mapper::to_legacy( $result->message_type ),
                'flash_data'   => $result->flash_data,
            );
        }

        $event_season = $event->current_season['name'] ?? '';
        $season       = isset( $query['season'] ) ? sanitize_text_field( wp_unslash( strval( $query['season'] ) ) ) : (string) $event_season;

        $seasons       = $this->season_service->get_all_seasons();
        $leagues       = $event instanceof Event ? $this->event_query_service->get_leagues( $event ) : array();
        $event_seasons = ( $event instanceof Event && method_exists( $event, 'get_seasons' ) ) ? $event->get_seasons() : array();

        $num_teams           = 0;
        $league_active_teams = array();
        $teams               = array();
        $constitution_action = 'update';
        $constitution_exists = false;
        $updates_allowed     = false;
        $latest_season       = '';
        $latest_event_season = '';
        $standing_status     = Util_Lookup::get_standing_statuses();

        $has_content = ! ( ( empty( $event->is_box ) && empty( $seasons ) ) || ( empty( $event->leagues ) && empty( $leagues ) ) || empty( $event_seasons ) );

        if ( $has_content ) {
            $num_teams = $event instanceof Event
                ? $this->event_query_service->get_teams( $event, array( 'count' => true, 'season' => $season ) )
                : 0;

            foreach ( $leagues as $l ) {
                if ( method_exists( $l, 'get_num_teams' ) ) {
                    $league_active_teams[ $l->id ] = $this->league_query_service->get_num_teams( $l, 'active', true );
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

            $teams = $event instanceof Event ? $this->event_query_service->get_constitution(
                $event,
                array(
                    'season'    => $latest_season,
                    'oldseason' => $latest_event_season,
                )
            ) : null;
            $constitution_action = 'update';
            $constitution_exists = true;
            if ( ! $teams ) {
                $teams               = $event instanceof Event ? $this->event_query_service->build_constitution( $event, array( 'season' => $latest_event_season ) ) : array();
                $constitution_action = 'insert';
                $constitution_exists = false;
            }
        }

        $vm = new League_Constitution_Page_View_Model(
            event: $event,
            season: $season,
            seasons: $seasons,
            leagues: $leagues,
            event_seasons: $event_seasons,
            num_teams: (int) $num_teams,
            league_active_teams: $league_active_teams,
            teams: (array) $teams,
            constitution_action: $constitution_action,
            constitution_exists: $constitution_exists,
            updates_allowed: $updates_allowed,
            latest_season: $latest_season,
            latest_event_season: $latest_event_season,
            standing_status: $standing_status,
            has_content: $has_content,
            event_id: $event_id,
        );

        return array(
            'view_model' => $vm,
        );
    }
}
