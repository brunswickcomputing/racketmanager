<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Event\Queries;

use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Fixture_Not_Found_Exception;
use Racketmanager\Presentation\Presenters\Competition_Shortcode_Presenter;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Fixture\Fixture_Detail_Service;
use Racketmanager\Services\League_Service;
use function __;
use function array_reverse;
use function count;
use function is_object;
use function method_exists;

class Get_Event_Draw_Handler {
    public function __construct(
        private readonly Competition_Service $competition_service,
        private readonly ?League_Service $league_service,
        private readonly Competition_Shortcode_Presenter $competition_shortcode_presenter,
        private readonly Fixture_Detail_Service $fixture_detail_service
    ) {}

    public function handle( Get_Event_Draw_Query $query ): Shortcode_View_Model {
        $identifier = $query->event_id ?? $query->event_name;
        if ( null === $identifier || '' === $identifier ) {
            return Shortcode_View_Model::for_error( __( 'Event not found', 'racketmanager' ) );
        }

        try {
            $event = $this->competition_service->get_event_by_id( $identifier );
        } catch ( Competition_Not_Found_Exception ) {
            return Shortcode_View_Model::for_error( __( 'Event not found', 'racketmanager' ) );
        }

        $event->set_season( $query->season );

        if ( ! Event_Allowlist_Guard::is_event_allowed( $event, $event->current_season['name'] ?? $query->season, $this->competition_service ) ) {
            return Shortcode_View_Model::for_error( __( 'Event not found for competition', 'racketmanager' ) );
        }

        if ( ! empty( $event->competition->is_championship ) ) {
            $event->leagues = $this->get_draw( $event, $query->season ?? ( $event->current_season['name'] ?? null ) );
        } else {
            $event->leagues = $event->get_leagues();
        }

        $event = $this->competition_shortcode_presenter->present_event_draw( $event );

        $filename = ! empty( $query->template ) ? 'draw-' . $query->template : 'draw';

        return Shortcode_View_Model::for_template(
            $filename,
            [
                'event' => $event,
            ],
            'event'
        );
    }

    public function get_draw( object $event, ?string $season ): array {
        $leagues = $event->get_leagues();
        foreach ( $leagues as $l => $league ) {
            $league_id  = is_object( $league ) ? ( $league->id ?? 0 ) : $league;
            $league_obj = $this->league_service ? $this->league_service->get_league( $league_id ) : null;
            if ( ! $league_obj ) {
                $league_obj = $league;
            }
            if ( isset( $league_obj->championship ) && is_object( $league_obj->championship ) && method_exists( $league_obj->championship, 'get_finals' ) ) {
                $finals = array_reverse( $league_obj->championship->get_finals() );
                foreach ( $finals as $f => $final ) {
                    $matches = method_exists( $league_obj, 'get_matches' ) ? $league_obj->get_matches( [
                        'season'  => $season,
                        'final'   => $final['key'] ?? null,
                        'orderby' => [
                            'id' => 'ASC',
                        ],
                    ] ) : [];
                    if ( count( $matches ) ) {
                        $final['matches']  = $matches;
                        $final['fixtures'] = $this->resolve_fixture_details( $matches );
                        $finals[ $f ]      = (object) $final;
                    } else {
                        unset( $finals[ $f ] );
                    }
                }
                $league_obj->finals = $finals;
            }
            $leagues[ $l ] = $league_obj;
        }

        return $leagues;
    }

    /**
     * Resolve raw Fixture entities into Fixture_Details_DTOs (teams, players, withdrawal status, links)
     * so the draw presenter can render opponents. Unresolvable fixtures are kept as raw entities.
     * Fixtures with non-empty leg != 2 (e.g. leg 1 of two-legged ties) are skipped because the draw
     * presenter discards them from rendering.
     *
     * @param array $matches
     * @return array
     */
    private function resolve_fixture_details( array $matches ): array {
        $fixtures = [];
        foreach ( $matches as $match ) {
            $details = null;
            if ( $match instanceof Fixture ) {
                if ( ! empty( $match->leg ) && 2 !== (int) $match->leg ) {
                    $fixtures[] = $match;
                    continue;
                }
                try {
                    $details = $this->fixture_detail_service->get_fixture_with_details( $match );
                } catch ( Fixture_Not_Found_Exception ) {
                    $details = null;
                }
            }
            $fixtures[] = $details ?? $match;
        }

        return $fixtures;
    }
}
