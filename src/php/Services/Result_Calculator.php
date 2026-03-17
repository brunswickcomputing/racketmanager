<?php

namespace Racketmanager\Services;

/**
 * Result Calculator: Handles logic for calculating scores and stats from raw match data.
 */
class Result_Calculator {
    /**
     * Calculate home and away points from a list of set scores.
     *
     * @param array $sets Array of sets, each containing 'player1' and 'player2' scores.
     * @return array{home_points: float, away_points: float}
     */
    public static function calculate_points_from_sets( array $sets ): array {
        $home_points = 0.0;
        $away_points = 0.0;

        foreach ( $sets as $set ) {
            $p1 = $set['player1'] ?? null;
            $p2 = $set['player2'] ?? null;

            if ( $p1 === null || $p2 === null ) {
                continue;
            }

            if ( $p1 > $p2 ) {
                $home_points += 1.0;
            } elseif ( $p1 < $p2 ) {
                $away_points += 1.0;
            }
        }

        return [
            'home_points' => $home_points,
            'away_points' => $away_points,
        ];
    }
}
