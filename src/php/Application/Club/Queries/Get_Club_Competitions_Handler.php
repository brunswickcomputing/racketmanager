<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Club\Queries;

use Racketmanager\Domain\Competition\Event;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Presentation\Presenters\Club_Presenter;
use Racketmanager\Presentation\Presenters\Team_Details_Presenter;
use Racketmanager\Presentation\View_Models\Club_Competition_View_Model;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Repositories\Interfaces\Competition_Repository_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition\Event_Query_Service;
use Racketmanager\Services\Competition_Service;
use function __;
use function is_array;

/**
 * Handles the [club-competitions] query: either one named competition or every cup/league
 * competition in which the club has teams.
 */
class Get_Club_Competitions_Handler {
    private const COMPETITION_TYPES = [ 'cup', 'league' ];

    public function __construct(
        private readonly Club_Service $club_service,
        private readonly Competition_Service $competition_service,
        private readonly Competition_Repository_Interface $competition_repository,
        private readonly Team_Details_Presenter $team_details_presenter,
        private readonly Club_Presenter $club_presenter,
        private readonly Event_Query_Service $event_query_service
    ) {
    }

    public function handle( Get_Club_Competitions_Query $query ): Shortcode_View_Model {
        try {
            $club = $this->club_service->get_club_by_shortcode( $query->club_name );
        } catch ( Club_Not_Found_Exception $e ) {
            return Shortcode_View_Model::error( $e->getMessage() );
        }

        $competitions = [];

        if ( ! empty( $query->competition_name ) ) {
            $competition = $this->competition_repository->find_by_name( $query->competition_name );
            if ( ! $competition ) {
                return Shortcode_View_Model::error( __( 'Competition not found', 'racketmanager' ) );
            }
            $view_model   = $this->present_competition_for_club( $club, $competition );
            $competitions = $view_model ? [ $view_model ] : [];
        } else {
            foreach ( self::COMPETITION_TYPES as $type ) {
                foreach ( $this->competition_service->get_by_criteria( [ 'type' => $type ] ) as $competition ) {
                    $view_model = $this->present_competition_for_club( $club, $competition );
                    if ( $view_model ) {
                        $competitions[] = $view_model;
                    }
                }
            }
        }

        return new Shortcode_View_Model(
            template: '' !== $query->template ? 'competitions-' . $query->template : 'competitions',
            vars: [
                'club'            => $club,
                'competitions'    => $competitions,
                'user_can_update' => $this->club_service->can_user_update( (int) $club->get_id() ),
            ],
            template_type: 'club'
        );
    }

    /**
     * Present the competition with only those events in which the club has teams; null when there are none.
     */
    private function present_competition_for_club( object $club, object $competition ): ?Club_Competition_View_Model {
        $events       = $this->competition_service->get_events_for_competition( (int) $competition->get_id() );
        $event_models = [];

        foreach ( $events as $event ) {
            $teams = $event instanceof Event
                ? $this->event_query_service->get_teams_info(
                    $event,
                    [
                        'club'    => $club->id,
                        'orderby' => [ 'title' => 'ASC' ],
                    ]
                )
                : ( $event->teams ?? [] );
            if ( ! $teams ) {
                continue;
            }
            $team_rows      = $this->team_details_presenter->present_team_rows( is_array( $teams ) ? $teams : [], $event, null );
            $event_models[] = $this->club_presenter->present_club_event( $event, $club, $team_rows );
        }

        return $event_models ? $this->club_presenter->present_club_competition( $competition, $club, $event_models ) : null;
    }
}
