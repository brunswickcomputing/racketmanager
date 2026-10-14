<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Club\Queries;

use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Repositories\Interfaces\Competition_Repository_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Service;
use function __;

/**
 * Handles the [club-competitions] query: either one named competition or every cup/league
 * competition in which the club has teams.
 */
class Get_Club_Competitions_Handler {
    private const COMPETITION_TYPES = [ 'cup', 'league' ];

    public function __construct(
        private readonly Club_Service $club_service,
        private readonly Competition_Service $competition_service,
        private readonly Competition_Repository_Interface $competition_repository
    ) {}

    public function handle( Get_Club_Competitions_Query $query ): Shortcode_View_Model {
        try {
            $club = $this->club_service->get_club_by_shortcode( $query->club_name );
        } catch ( Club_Not_Found_Exception $e ) {
            return Shortcode_View_Model::error( $e->getMessage() );
        }

        if ( ! empty( $query->competition_name ) ) {
            $competition = $this->competition_repository->find_by_name( $query->competition_name );
            if ( ! $competition ) {
                return Shortcode_View_Model::error( __( 'Competition not found', 'racketmanager' ) );
            }
            $club->competition = $competition;
        } else {
            $club_competitions = [];
            foreach ( self::COMPETITION_TYPES as $type ) {
                $club_competitions = $this->add_competitions_with_club_teams( $club, $club_competitions, $type );
            }
            $club->competitions = $club_competitions;
        }

        return new Shortcode_View_Model(
            template: '' !== $query->template ? 'competitions-' . $query->template : 'competitions',
            vars: [
                'club'            => $club,
                'user_can_update' => $this->club_service->can_user_update( (int) $club->get_id() ),
            ],
            template_type: 'club'
        );
    }

    private function add_competitions_with_club_teams( object $club, array $club_competitions, string $type ): array {
        $competition_found = false;
        $competitions      = $this->competition_service->get_by_criteria( [ 'type' => $type ] );

        foreach ( $competitions as $c => $competition ) {
            $events      = method_exists( $competition, 'get_events' ) ? $competition->get_events() : [];
            $event_found = false;
            foreach ( $events as $e => $event ) {
                $teams = method_exists( $event, 'get_teams_info' )
                    ? $event->get_teams_info(
                        [
                            'club'    => $club->id,
                            'orderby' => [ 'title' => 'ASC' ],
                        ]
                    )
                    : [];
                if ( $teams ) {
                    $event->teams = $teams;
                    $events[ $e ] = $event;
                    $event_found  = true;
                } else {
                    unset( $events[ $e ] );
                }
            }
            if ( $event_found ) {
                $competition_found   = true;
                $competition->events = $events;
                $competitions[ $c ]  = $competition;
            } else {
                unset( $competitions[ $c ] );
            }
        }

        return $competition_found ? array_merge( $club_competitions, $competitions ) : $club_competitions;
    }
}
