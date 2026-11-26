<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\Queries;

use Racketmanager\Application\League\DTOs\League_Championship_Result;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Application\League\Ports\League_Query_Service_Interface;
use Racketmanager\Application\League\Ports\League_Service_Interface;
use stdClass;
use function __;
use function array_reverse;
use function ceil;
use function is_array;
use function is_iterable;
use function is_object;
use function max;
use function method_exists;

/**
 * Handles league championship requests by loading the target league, applying the required season, and assembling finals data.
 *
 * Coordinates league retrieval, season resolution, championship round discovery, fixture lookup, and rubber loading for championship matches. Returns a structured championship result, including league configuration and template information, or an error result when the requested league cannot be resolved.
 */
readonly class Get_League_Championship_Handler {
    /**
     * Initialises the service with league, fixture, rubber, and league query dependencies.
     *
     * @param League_Service_Interface       $league_service League service dependency.
     * @param Fixture_Repository_Interface   $fixture_repository Fixture repository dependency.
     * @param Rubber_Repository_Interface    $rubber_repository Rubber repository dependency.
     * @param League_Query_Service_Interface $league_query_service League query service dependency.
     *
     * @return void
     */
    public function __construct(
        private League_Service_Interface $league_service,
        private Fixture_Repository_Interface $fixture_repository,
        private Rubber_Repository_Interface $rubber_repository,
        private League_Query_Service_Interface $league_query_service
    ) {}

    /**
     * Handles a league championship query and returns the resolved championship data.
     *
     * Retrieves the requested league, applies the requested or current season, loads championship finals,
     * attaches fixture and rubber data where available, and returns an error result when the league cannot be found.
     *
     * @param Get_League_Championship_Query $query Query containing the league identifier, optional season, and template.
     *
     * @return League_Championship_Result Championship result containing league, finals, match configuration, and template data, or an error result if the league is not found.
     */
    public function handle( Get_League_Championship_Query $query ): League_Championship_Result {
        $league = null;
        if ( ! empty( $query->league_id ) ) {
            $league = $this->league_service->get_league( $query->league_id );
        }
        if ( ! $league ) {
            return League_Championship_Result::error( __( 'League not found', 'racketmanager' ) );
        }

        $this->league_query_service->set_season( $league, $query->season ?? $league->get_season() );
        $season = $league->get_season();
        $league->set_season( $season );

        $finals = [];
        if ( isset( $league->championship ) && is_object( $league->championship ) && method_exists( $league->championship, 'get_finals' ) ) {
            $num_first_round = (int) ( $league->championship->num_teams_first_round ?? 0 );
            $raw_finals      = $league->championship->get_finals();
            if ( is_array( $raw_finals ) ) {
                foreach ( array_reverse( $raw_finals ) as $f => $final ) {
                    $data              = new stdClass();
                    $data->key         = $final['key'] ?? '';
                    $data->name        = $final['name'] ?? '';
                    $data->num_matches = (int) ( $final['num_matches'] ?? 0 );
                    $data->round       = $final['round'] ?? '';
                    $num_matches       = max( 1, $data->num_matches );
                    $data->rowspan     = ( $num_first_round / 2 >= 4 ) ? (int) ceil( 4 / $num_matches ) : (int) ceil( ( $num_first_round / 2 ) / $num_matches );

                    $matches_raw = $this->league_query_service->get_matches(
                        $league,
                        [
                            'final'   => $final['key'] ?? '',
                            'orderby' => [ 'id' => 'ASC' ],
                        ]
                    );

                    $matches = [];
                    $i       = 1;
                    if ( is_iterable( $matches_raw ) ) {
                        foreach ( $matches_raw as $match_item ) {
                            $match_id = is_object( $match_item ) ? (int) ( $match_item->id ?? 0 ) : (int) $match_item;
                            $fixture  = $this->fixture_repository->find_by_id( $match_id );
                            if ( ! $fixture ) {
                                continue;
                            }

                            if ( null === $fixture->rubbers && ( $league->num_rubbers ?? 0 ) > 0 ) {
                                $fixture->set_rubbers( $this->rubber_repository->find_by_fixture_id( $match_id ) );
                            }

                            $matches[ $i ] = $fixture;
                            ++$i;
                        }
                    }

                    if ( ! empty( $matches ) ) {
                        $data->matches = $matches;
                        $finals[ $f ]  = $data;
                    }
                }
            }
        }

        return new League_Championship_Result(
            league: $league,
            finals: $finals,
            num_rubbers: isset( $league->num_rubbers ) ? (int) $league->num_rubbers : null,
            num_sets: isset( $league->num_sets ) ? (int) $league->num_sets : null,
            template: $query->template
        );
    }
}
