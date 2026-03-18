<?php
/**
 * Result Factory: Result_Factory class
 *
 * @package RacketManager
 * @subpackage Services
 */

namespace Racketmanager\Services;

use Racketmanager\Domain\Result;
use Racketmanager\Domain\Scoring\Set_Score;

class Result_Factory {
    /**
     * Create a Result domain object from a POST request or similar array.
     *
     * @param array $data The raw input data.
     * @param int|string|null $home_team_id
     * @param int|string|null $away_team_id
     *
     * @return Result
     */
    public static function from_array( array $data, int|string|null $home_team_id = null, int|string|null $away_team_id = null ): Result {
        $home_points = (float) ( $data['home_points'] ?? 0 );
        $away_points = (float) ( $data['away_points'] ?? 0 );
        $raw_sets    = $data['sets'] ?? [];
        $status      = isset( $data['status'] ) ? (int) $data['status'] : null;
        $custom      = $data['custom'] ?? [];

        $sets = [];
        $i    = 1;
        foreach ( $raw_sets as $set_data ) {
            $home_games    = isset( $set_data['home'] ) ? (int) $set_data['home'] : ( isset( $set_data['player1'] ) && '' !== $set_data['player1'] ? (int) $set_data['player1'] : null );
            $away_games    = isset( $set_data['away'] ) ? (int) $set_data['away'] : ( isset( $set_data['player2'] ) && '' !== $set_data['player2'] ? (int) $set_data['player2'] : null );
            $home_tiebreak = isset( $set_data['home_tb'] ) ? (int) $set_data['home_tb'] : ( isset( $set_data['tiebreak'] ) && '' !== $set_data['tiebreak'] && $home_games > $away_games ? (int) $set_data['tiebreak'] : null );
            $away_tiebreak = isset( $set_data['away_tb'] ) ? (int) $set_data['away_tb'] : ( isset( $set_data['tiebreak'] ) && '' !== $set_data['tiebreak'] && $away_games > $home_games ? (int) $set_data['tiebreak'] : null );

            $sets[ $i ] = new Set_Score(
                home_games: $home_games,
                away_games: $away_games,
                home_tiebreak: $home_tiebreak,
                away_tiebreak: $away_tiebreak
            );
            $i++;
        }

        // Handle byes
        if ( empty( $home_points ) && '-1' === (string) $home_team_id ) {
            $home_points = (float) ( $data['winning_points'] ?? 0 );
        }
        if ( empty( $away_points ) && '-1' === (string) $away_team_id ) {
            $away_points = (float) ( $data['winning_points'] ?? 0 );
        }

        // If points are zero and sets are provided, calculate points from sets
        if ( 0.0 === $home_points && 0.0 === $away_points && ! empty( $sets ) ) {
            $calculated  = Result_Calculator::calculate_points_from_sets( $sets );
            $home_points = $calculated['home_points'];
            $away_points = $calculated['away_points'];
        }

        $winner_id = isset( $data['winner_id'] ) ? (int) $data['winner_id'] : null;
        $loser_id  = isset( $data['loser_id'] ) ? (int) $data['loser_id'] : null;

        if ( ( null === $winner_id || null === $loser_id ) && null !== $home_team_id && null !== $away_team_id ) {
            $determined = Result_Calculator::determine_winner_and_loser(
                $home_points,
                $away_points,
                $home_team_id,
                $away_team_id,
                $status,
                $custom
            );
            $winner_id = $determined['winner_id'];
            $loser_id  = $determined['loser_id'];
        }

        return new Result(
            home_points: $home_points,
            away_points: $away_points,
            winner_id: $winner_id,
            loser_id: $loser_id,
            status: $status,
            is_walkover: (bool) ( $data['is_walkover'] ?? ( 1 === $status ) ),
            sets: $sets,
            custom: $custom
        );
    }
}
