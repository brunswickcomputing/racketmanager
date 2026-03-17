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
        return new Result(
            home_points: (float) ( $data['home_points'] ?? 0 ),
            away_points: (float) ( $data['away_points'] ?? 0 ),
            winner_id: isset( $data['winner_id'] ) ? (int) $data['winner_id'] : null,
            loser_id: isset( $data['loser_id'] ) ? (int) $data['loser_id'] : null,
            status: isset( $data['status'] ) ? (int) $data['status'] : null,
            is_walkover: (bool) ( $data['is_walkover'] ?? false ),
            sets: $data['sets'] ?? [],
            custom: $data['custom'] ?? []
        );
    }
}
