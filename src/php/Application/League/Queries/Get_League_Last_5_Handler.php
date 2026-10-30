<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\Queries;

use Racketmanager\Application\League\DTOs\League_Last_5_Result;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\League_Service;
use function __;
use function is_array;
use function is_object;

/**
 * Query handler for [last-5] shortcode.
 */
class Get_League_Last_5_Handler {
    /**
     * Cache of team ID -> title maps keyed by league_id and season.
     *
     * @var array<string, array<string, string>>
     */
    private array $team_titles_cache = [];

    public function __construct(
        private readonly League_Service $league_service,
        private readonly League_Query_Service $league_query_service
    ) {}

    public function handle( Get_League_Last_5_Query $query ): League_Last_5_Result {
        $league = null;
        if ( ! empty( $query->league_id ) ) {
            $league = $this->league_service->get_league( $query->league_id );
        }
        if ( ! $league ) {
            return League_Last_5_Result::error( __( 'League not found', 'racketmanager' ) );
        }

        $this->league_query_service->set_season( $league );

        if ( ! $query->team_id ) {
            return League_Last_5_Result::error( __( 'No team ID found', 'racketmanager' ) );
        }

        $matches = $this->league_query_service->get_matches( $league, [
            'time'             => 'prev',
            'team_id'          => $query->team_id,
            'match_day'        => -1,
            'limit'            => 5,
            'reset_query_args' => true,
        ] );

        $matches_list = is_array( $matches ) ? $matches : [];
        $team_titles  = [];
        if ( $matches_list ) {
            $league_id = (string) ( $league->id ?? $query->league_id ?? '' );
            $season    = (string) ( $league->season ?? ( $league->current_season['name'] ?? '' ) );
            $cache_key = $league_id . ':' . $season;

            if ( ! isset( $this->team_titles_cache[ $cache_key ] ) ) {
                $teams = $this->league_query_service->get_league_teams( $league, [ 'reset_query_args' => true ] );
                $titles = [];
                foreach ( is_array( $teams ) ? $teams : [] as $team ) {
                    $id    = is_object( $team ) ? ( $team->id ?? null ) : ( $team['id'] ?? null );
                    $title = is_object( $team ) ? ( $team->title ?? null ) : ( $team['title'] ?? null );
                    if ( null !== $id && null !== $title ) {
                        $titles[ (string) $id ] = (string) $title;
                    }
                }
                $this->team_titles_cache[ $cache_key ] = $titles;
            }

            $team_titles = $this->team_titles_cache[ $cache_key ];
        }

        return new League_Last_5_Result(
            league: $league,
            matches: $matches_list,
            team_id: $query->team_id,
            team_titles: $team_titles,
            template: $query->template
        );
    }
}
