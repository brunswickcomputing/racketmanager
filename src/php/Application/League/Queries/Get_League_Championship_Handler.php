<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\Queries;

use Racketmanager\Application\League\DTOs\League_Championship_Result;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\League_Service;
use stdClass;
use function __;
use function array_reverse;
use function ceil;
use function count;
use function is_array;
use function is_iterable;
use function is_object;
use function max;
use function method_exists;

/**
 * Query handler for [championship] shortcode.
 */
class Get_League_Championship_Handler {
    public function __construct(
        private readonly League_Service $league_service,
        private readonly Fixture_Repository_Interface $fixture_repository,
        private readonly Rubber_Repository_Interface $rubber_repository,
        private readonly League_Query_Service $league_query_service
    ) {}

    public function handle( Get_League_Championship_Query $query ): League_Championship_Result {
        $league = null;
        if ( ! empty( $query->league_id ) ) {
            $league = $this->league_service->get_league( $query->league_id );
        }
        if ( ! $league ) {
            return League_Championship_Result::error( __( 'League not found', 'racketmanager' ) );
        }

        $league->set_template( 'championship', $query->template );
        $this->league_query_service->set_season( $league, $query->season ?? $league->get_season() );
        $season = $league->get_season();
        $league->season = $season;

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

                    $matches_raw = $this->league_query_service->get_matches( $league, [
                        'final'   => $final['key'] ?? '',
                        'orderby' => [ 'id' => 'ASC' ],
                    ] );

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

                    if ( count( $matches ) > 0 ) {
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
