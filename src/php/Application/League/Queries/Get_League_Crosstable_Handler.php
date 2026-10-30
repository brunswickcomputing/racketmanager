<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\Queries;

use Racketmanager\Application\League\DTOs\League_Crosstable_Result;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\League_Service;
use function __;
use function array_slice;
use function array_values;
use function count;
use function is_array;

/**
 * Query handler for [crosstable] shortcode.
 */
class Get_League_Crosstable_Handler {
    public function __construct(
        private readonly League_Service $league_service,
        private readonly League_Query_Service $league_query_service
    ) {}

    public function handle( Get_League_Crosstable_Query $query ): League_Crosstable_Result {
        $league = null;
        if ( ! empty( $query->league_id ) ) {
            $league = $this->league_service->get_league( $query->league_id );
        }
        if ( ! $league ) {
            return League_Crosstable_Result::error( __( 'League not found', 'racketmanager' ) );
        }

        $league->set_template( 'crosstable', $query->template );
        $this->league_query_service->set_season( $league, $query->season ?? false );
        $league->set_group( $query->group ?? '' );

        $teams = $this->league_query_service->get_league_teams( $league, [ 'orderby' => [ 'rank' => 'ASC' ] ] );
        $teams = is_array( $teams ) ? $teams : [];

        $columns = $this->crosstable_columns( $league, $teams );
        $cells   = $this->build_crosstable_cells( $league, $teams, $columns );

        return new League_Crosstable_Result(
            league: $league,
            teams: $teams,
            columns: $columns,
            cells: $cells,
            mode: $query->mode,
            season: $query->season,
            template: $query->template
        );
    }

    /**
     * Column teams of the crosstable, in the league's team order.
     *
     * The template renders one column per ranked row (`count( $teams )`), reading the
     * column team from `$league->teams` (populated by get_league_teams()), so the list is
     * capped to that length; a missing column renders as an empty cell.
     *
     * @return object[] Column teams indexed 0..n-1.
     */
    private function crosstable_columns( League $league, array $teams ): array {
        $columns = is_array( $league->teams ) ? array_values( $league->teams ) : [];
        return array_slice( $columns, 0, count( $teams ) );
    }

    /**
     * Pre-compute the crosstable score matrix so the template only echoes values.
     *
     * Rows come from the ranked `$teams`; columns are exactly the `$columns` list the
     * template iterates, so no cell is computed that is not rendered.
     *
     * @param object[] $teams   Row teams (ranked).
     * @param object[] $columns Column teams (see crosstable_columns()).
     *
     * @return array<int, array<int, string>> Matrix keyed by [ row team id ][ column team id ].
     */
    private function build_crosstable_cells( League $league, array $teams, array $columns ): array {
        $cells = [];
        foreach ( $teams as $row_team ) {
            if ( ! isset( $row_team->id ) ) {
                continue;
            }
            foreach ( $columns as $column_team ) {
                if ( ! isset( $column_team->id ) ) {
                    continue;
                }
                $cells[ (int) $row_team->id ][ (int) $column_team->id ] = $this->league_query_service->get_crosstable_field( $league, (int) $row_team->id, (int) $column_team->id );
            }
        }
        return $cells;
    }
}
