<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Event\Queries;

use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Presentation\Presenters\Tournament_Presenter;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Presentation\View_Models\Team_Matches_Group_View_Model;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Player_Stats_Service;
use function is_array;
use function is_numeric;
use function method_exists;
use function __;

class Get_Event_Clubs_Handler {
    public function __construct(
        private readonly Competition_Service $competition_service,
        private readonly Club_Service $club_service,
        private readonly Player_Stats_Service $player_stats_service,
        private readonly Tournament_Presenter $tournament_presenter
    ) {}

    public function handle( Get_Event_Clubs_Query $query ): Shortcode_View_Model {
        $identifier = $query->event_id ?? $query->event_name;
        if ( null === $identifier || '' === $identifier ) {
            return Shortcode_View_Model::for_error( __( 'Event not found', 'racketmanager' ) );
        }

        try {
            $event = $this->competition_service->get_event_by_id( $identifier );
        } catch ( Competition_Not_Found_Exception ) {
            return Shortcode_View_Model::for_error( __( 'Event not found', 'racketmanager' ) );
        }

        $event_club = null;
        $event->set_season( $query->season );

        if ( ! Event_Allowlist_Guard::is_event_allowed( $event, $event->current_season['name'] ?? $query->season, $this->competition_service ) ) {
            return Shortcode_View_Model::for_error( __( 'Event not found for competition', 'racketmanager' ) );
        }

        if ( ! empty( $query->club_id ) ) {
            try {
                if ( is_numeric( $query->club_id ) ) {
                    $club = $this->club_service->get_club( (int) $query->club_id );
                } else {
                    $club = $this->club_service->get_club_by_shortcode( (string) $query->club_id );
                }
                $event_club = $event->get_club( $club );
                $event_club->players = $this->player_stats_service->populate_players_stats(
                    $event_club->players,
                    $event,
                    $event->current_season['name'] ?? null,
                    'event'
                );

                $event_club_id = method_exists( $club, 'get_id' ) ? $club->get_id() : ( $club->id ?? null );

                if ( ! empty( $event_club->matches ) && is_array( $event_club->matches ) ) {
                    foreach ( $event_club->matches as $key => $group_matches ) {
                        $event_club->matches[ $key ] = $this->tournament_presenter->present_match_group(
                            $key,
                            $group_matches,
                            $this->tournament_presenter->is_tournament_competition( $event->competition ?? null ),
                            [
                                'current_club' => $event_club_id,
                                'show_header'  => true,
                                'by_date'      => true,
                            ]
                        );
                    }
                }
                if ( ! empty( $event_club->results ) && is_array( $event_club->results ) ) {
                    foreach ( $event_club->results as $key => $group_matches ) {
                        $event_club->results[ $key ] = $this->tournament_presenter->present_match_group(
                            $key,
                            $group_matches,
                            $this->tournament_presenter->is_tournament_competition( $event->competition ?? null ),
                            [
                                'current_club' => $event_club_id,
                                'show_header'  => true,
                                'by_date'      => true,
                            ]
                        );
                    }
                }
            } catch ( Club_Not_Found_Exception ) {
                return Shortcode_View_Model::for_error( __( 'Club not found', 'racketmanager' ) );
            }
        }

        $event->clubs = $event->get_clubs( [ 'status' => 1 ] );
        $filename     = ! empty( $query->template ) ? 'clubs-' . $query->template : 'clubs';

        return Shortcode_View_Model::for_template(
            $filename,
            [
                'event'      => $event,
                'event_club' => $event_club,
            ],
            'event'
        );
    }
}
