<?php
/**
 * Result Factory: Result_Factory class
 *
 * @package RacketManager
 * @subpackage Services
 */

namespace Racketmanager\Services;

use Racketmanager\Domain\Result;

class Result_Factory {
    /**
     * Create a Result domain object from a POST request or similar array.
     *
     * @param array $data The raw input data.
     *
     * @return Result
     */
    public static function from_array( array $data ): Result {
        $home_points = (float) ( $data['home_points'] ?? 0 );
        $away_points = (float) ( $data['away_points'] ?? 0 );
        $sets        = $data['sets'] ?? [];

        // If points are zero and sets are provided, calculate points from sets
        if ( 0.0 === $home_points && 0.0 === $away_points && ! empty( $sets ) ) {
            $calculated  = Result_Calculator::calculate_points_from_sets( $sets );
            $home_points = $calculated['home_points'];
            $away_points = $calculated['away_points'];
        }

        return new Result(
            home_points: $home_points,
            away_points: $away_points,
            winner_id: isset( $data['winner_id'] ) ? (int) $data['winner_id'] : null,
            loser_id: isset( $data['loser_id'] ) ? (int) $data['loser_id'] : null,
            status: isset( $data['status'] ) ? (int) $data['status'] : null,
            is_walkover: (bool) ( $data['is_walkover'] ?? false ),
            sets: $sets,
            custom: $data['custom'] ?? []
        );
    }
}
