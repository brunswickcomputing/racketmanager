<?php
declare( strict_types=1 );
/**
 * League Schedule Admin Controller
 *
 * Handles view=plan on league screens.
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\Presenters\Admin_League_Schedule_Presenter;
use Racketmanager\Admin\View_Models\Admin_League_Schedule_Page_View_Model;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Admin\Presenters\Admin_Message_Mapper;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Services\Admin\League\League_Schedule_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition_Service;

final readonly class League_Schedule_Admin_Controller {

    public function __construct(
        private Competition_Service $competition_service,
        private Event_Repository_Interface $event_repository,
        private Admin_League_Schedule_Presenter $admin_league_schedule_presenter,
        private League_Schedule_Action_Dispatcher $league_schedule_dispatcher,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * @param array<string, mixed> $query      Typically $_GET
     * @param array<string, mixed> $post       Typically $_POST
     * @param array<string, mixed> $flash_data Flash data already popped by the bridge (schedule report).
     *
     * @return array{view_model?:Admin_League_Schedule_Page_View_Model, redirect?:string, message?:string, message_type?:bool|string, flash_data?:array<string, mixed>}
     *
     * @throws Invalid_Status_Exception
     * @throws Competition_Not_Found_Exception
     */
    public function schedule_page( array $query, array $post, array $flash_data = array() ): array {
        $this->action_guard->assert_capability( 'edit_leagues' );

        $request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
        $is_post        = 'POST' === strtoupper( $request_method );
        $competition_id = isset( $query['competition_id'] ) ? intval( $query['competition_id'] ) : intval( $post['competition_id'] ?? 0 );

        if ( ! $competition_id ) {
            throw new Competition_Not_Found_Exception( __( 'Competition not found', 'racketmanager' ) );
        }

        $competition = $this->competition_service->get_competition( $competition_id );

        if ( $is_post && isset( $post['scheduleAction'] ) ) {
            $result = $this->league_schedule_dispatcher->handle( $query, $post );

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

        $season = isset( $query['season'] ) ? sanitize_text_field( wp_unslash( strval( $query['season'] ) ) ) : '';
        if ( '' === $season ) {
            $season = (string) ( $competition->current_season['name'] ?? '' );
        }

        $schedule_report = $flash_data;

        $raw_events  = $this->competition_service->get_events_for_competition( $competition_id );
        $events_data = array();
        foreach ( ( is_array( $raw_events ) ? $raw_events : array() ) as $event_item ) {
            $event = is_object( $event_item )
                ? $event_item
                : $this->event_repository->find_by_id( (int) $event_item );
            if ( ! $event ) {
                continue;
            }

            $event_id   = (int) ( method_exists( $event, 'get_id' ) ? $event->get_id() : ( $event->id ?? 0 ) );
            $event_name = (string) ( method_exists( $event, 'get_name' ) ? $event->get_name() : ( $event->name ?? '' ) );

            $event_season = (string) $season;
            if ( '' === $event_season ) {
                $event_season = (string) ( method_exists( $event, 'get_season' ) ? $event->get_season() : ( $event->season ?? '' ) );
            }

            $match_count            = $this->event_repository->count_by_event_season( $event_id, $event_season, false );
            $match_completion_count = $this->event_repository->count_by_event_season( $event_id, $event_season, true );

            $events_data[] = array(
                'id'                     => $event_id,
                'name'                   => $event_name,
                'season'                 => $event_season,
                'match_count'            => $match_count,
                'match_completion_count' => $match_completion_count,
            );
        }

        $vm = $this->admin_league_schedule_presenter->present(
            competition: $competition,
            season: (string) $season,
            events: $events_data,
            schedule_report: $schedule_report,
        );

        return array(
            'view_model' => $vm,
        );
    }
}
