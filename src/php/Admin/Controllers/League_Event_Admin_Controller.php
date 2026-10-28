<?php
declare( strict_types=1 );
/**
 * League Event Admin Controller
 *
 * Handles display and actions for league event screen.
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\Presenters\Admin_Message_Mapper;
use Racketmanager\Admin\View_Models\League_Event_Page_View_Model;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Exceptions\Event_Not_Found_Exception;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Services\Admin\League\League_Event_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition\Event_Query_Service;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\League_Service;

final readonly class League_Event_Admin_Controller {

    public function __construct(
        private Event_Repository_Interface $event_repository,
        private League_Service $league_service,
        private Event_Query_Service $event_query_service,
        private League_Query_Service $league_query_service,
        private Action_Guard_Interface $action_guard,
        private League_Event_Action_Dispatcher $league_event_dispatcher,
    ) {
    }

    /**
     * @param array<string, mixed> $query Typically $_GET
     * @param array<string, mixed> $post  Typically $_POST
     *
     * @return array{view_model?: League_Event_Page_View_Model, redirect?: string, message?: string, message_type?: bool|string, flash_data?: array<string, mixed>}
     *
     * @throws Event_Not_Found_Exception
     */
    public function event_page( array $query, array $post ): array {
        $this->action_guard->assert_capability( 'edit_leagues' );

        if ( ( isset( $post['addLeague'] ) || ( isset( $post['doActionLeague'] ) && isset( $post['action'] ) && 'delete' === $post['action'] ) ) ) {
            $result = $this->league_event_dispatcher->handle( $query, $post );

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

        $event_id = isset( $query['event_id'] ) ? intval( $query['event_id'] ) : null;
        if ( ! $event_id ) {
            throw new Event_Not_Found_Exception( __( 'Event not found', 'racketmanager' ) );
        }

        $event = $this->event_repository->find_by_id( $event_id );
        if ( ! $event ) {
            throw new Event_Not_Found_Exception( __( 'Event not found', 'racketmanager' ) );
        }

        $league_id    = null;
        $league_edit  = null;
        $league_title = '';
        if ( isset( $query['edit_league'] ) ) {
            $league_id   = intval( $query['edit_league'] );
            $league_edit = $this->league_service->get_league( $league_id );
            if ( $league_edit ) {
                $league_title = (string) $league_edit->title;
            }
        }

        $event_season = $event->current_season['name'] ?? '';
        $season       = isset( $query['season'] ) ? sanitize_text_field( wp_unslash( strval( $query['season'] ) ) ) : (string) $event_season;

        $leagues = $event instanceof Event
            ? $this->event_query_service->get_leagues( $event )
            : array();

        $league_matches_map = array();
        foreach ( $leagues as $league_item ) {
            if ( ! ( $league_item->is_championship ?? false ) ) {
                if ( $league_item instanceof League ) {
                    $this->league_query_service->set_num_matches( $league_item, true );
                }
                $league_matches_map[ $league_item->id ] = $league_item->num_matches_total ?? 0;
            }
        }

        $vm = new League_Event_Page_View_Model(
            event: $event,
            season: $season,
            leagues: $leagues,
            league_matches_map: $league_matches_map,
            league_id: $league_id,
            league_title: $league_title,
            league_edit: $league_edit,
            tab: 'leagues',
        );

        return array(
            'view_model' => $vm,
        );
    }
}
