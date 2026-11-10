<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\Queries;

use Racketmanager\Application\League\DTOs\League_Archive_Result;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Services\Competition\Event_Query_Service;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\League_Service;
use function __;
use function is_numeric;
use function is_object;
use function method_exists;

/**
 * Query handler for [leaguearchive] shortcode.
 */
readonly class Get_League_Archive_Handler {
    /**
     * Constructor.
     *
     * @param League_Service       $league_service league service.
     * @param Competition_Service  $competition_service competition service.
     * @param Event_Query_Service  $event_query_service event query service.
     * @param League_Query_Service $league_query_service league query service.
     */
    public function __construct(
        private League_Service $league_service,
        private Competition_Service $competition_service,
        private Event_Query_Service $event_query_service,
        private League_Query_Service $league_query_service,
    ) {}

    /**
     * Handle a Get_League_Archive_Query.
     *
     * @param Get_League_Archive_Query $query query.
     *
     * @return League_Archive_Result
     */
    public function handle( Get_League_Archive_Query $query ): League_Archive_Result {
        $league = null;
        if ( $query->league_name ) {
            $league = $this->league_service->get_league( $query->league_name );
        }
        if ( ! $league ) {
            return League_Archive_Result::error( __( 'League not found', 'racketmanager' ) );
        }

        $event = isset( $league->event_id ) ? $this->competition_service->get_event_by_id( $league->event_id ) : null;
        if ( ! $event ) {
            return League_Archive_Result::error( __( 'Event not found', 'racketmanager' ) );
        }

        $league_query_service = $this->league_query_service;
        if ( false !== $league_query_service->resolve_requested_season( $league ) ) {
            $league_query_service->set_season( $league );
        }
        $seasons = is_object( $event ) && method_exists( $event, 'get_seasons' ) ? $event->get_seasons() : [];
        $leagues = $event instanceof Event ? $this->event_query_service->get_leagues( $event ) : [];
        $tab     = $query->tab ?? 'standings';

        $tab_state = $league_query_service->set_tab( $league, true );
        $templates = [
            'standings'  => $query->standings,
            'crosstable' => $query->crosstable,
            'matches'    => $query->matches,
            'teams'      => $query->teams,
            'archive'    => $query->template,
        ];

        $view_state = $tab_state->with(
            [
                'tab'                   => $tab,
                'templates'             => $templates,
                'matches_template_type' => $query->matches_template_type,
                'current_match_day'     => is_numeric( $league->match_day ?? null ) ? $league->match_day : 1,
            ]
        );

        return new League_Archive_Result(
            league: $league,
            event: $event,
            seasons: $seasons,
            leagues: $leagues,
            tab: $tab,
            template: $query->template,
            standings: $query->standings,
            crosstable: $query->crosstable,
            matches: $query->matches,
            teams: $query->teams,
            matches_template_type: $query->matches_template_type,
            post_id: $query->post_id,
            view_state: $view_state
        );
    }
}
