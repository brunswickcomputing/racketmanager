<?php

namespace Racketmanager\Services;

use Racketmanager\Domain\Scoring\Set_Score;

/**
 * Result Calculator: Handles logic for calculating scores and stats from raw match data.
 */
class Result_Calculator {
    /**
     * Calculate home and away points from a list of set scores.
     *
     * @param Set_Score[] $sets Array of Set_Score objects.
     * @return array{home_points: float, away_points: float}
     */
    public static function calculate_points_from_sets( array $sets ): array {
        $home_points = 0.0;
        $away_points = 0.0;

        foreach ( $sets as $set ) {
            $winner = $set->winner();
            if ( 'home' === $winner ) {
                $home_points += 1.0;
            } elseif ( 'away' === $winner ) {
                $away_points += 1.0;
            }
        }

        return [
            'home_points' => $home_points,
            'away_points' => $away_points,
        ];
    }

    /**
     * Calculate aggregate statistics (rubbers, sets, games) from a list of rubber objects.
     *
     * @param array  $rubbers   List of rubber objects.
     * @param string $home_id   Home team/player ID.
     * @param string $away_id   Away team/player ID.
     * @return array{
     *     stats: array{
     *         rubbers: array{home: float, away: float},
     *         sets: array{home: int, away: int},
     *         games: array{home: int, away: int}
     *     },
     *     home_win: int,
     *     away_win: int,
     *     draw: int,
     *     shared: int,
     *     home_walkover: int,
     *     away_walkover: int,
     *     home_points: float,
     *     away_points: float
     * }
     */
    public static function calculate_stats_from_rubbers( array $rubbers, string $home_id, string $away_id ): array {
        $stats = [
            'rubbers' => [ 'home' => 0.0, 'away' => 0.0 ],
            'sets'    => [ 'home' => 0, 'away' => 0 ],
            'games'   => [ 'home' => 0, 'away' => 0 ],
        ];

        $home_win      = 0;
        $away_win      = 0;
        $draw          = 0;
        $shared        = 0;
        $home_walkover = 0;
        $away_walkover = 0;
        $home_points   = 0.0;
        $away_points   = 0.0;

        foreach ( $rubbers as $rubber ) {
            $status    = isset( $rubber->status ) ? (int) $rubber->status : 0;
            $winner_id = isset( $rubber->winner_id ) ? (string) $rubber->winner_id : '0';

            switch ( $status ) {
                case 1: // Walkover
                    if ( $home_id === $winner_id ) {
                        ++$away_walkover;
                    } elseif ( $away_id === $winner_id ) {
                        ++$home_walkover;
                    }
                    break;
                case 3: // Shared
                    ++$shared;
                    break;
            }

            if ( $home_id === $winner_id ) {
                ++$home_win;
                $stats['rubbers']['home']++;
            } elseif ( $away_id === $winner_id ) {
                ++$away_win;
                $stats['rubbers']['away']++;
            } elseif ( '-1' === $winner_id ) {
                ++$draw;
                $stats['rubbers']['home'] += 0.5;
                $stats['rubbers']['away'] += 0.5;
            }

            if ( isset( $rubber->home_points ) && is_numeric( $rubber->home_points ) ) {
                $home_points += (float) $rubber->home_points;
            }
            if ( isset( $rubber->away_points ) && is_numeric( $rubber->away_points ) ) {
                $away_points += (float) $rubber->away_points;
            }

            $custom_stats = $rubber->custom['stats'] ?? [];
            $stats['sets']['home']  += (int) ( $custom_stats['sets']['home'] ?? 0 );
            $stats['sets']['away']  += (int) ( $custom_stats['sets']['away'] ?? 0 );
            $stats['games']['home'] += (int) ( $custom_stats['games']['home'] ?? 0 );
            $stats['games']['away'] += (int) ( $custom_stats['games']['away'] ?? 0 );
        }

        return [
            'stats'         => $stats,
            'home_win'      => $home_win,
            'away_win'      => $away_win,
            'draw'          => $draw,
            'shared'        => $shared,
            'home_walkover' => $home_walkover,
            'away_walkover' => $away_walkover,
            'home_points'   => $home_points,
            'away_points'   => $away_points,
        ];
    }

    /**
     * Calculate match points from statistics and scoring rules.
     *
     * @param array $stats_result Result from calculate_stats_from_rubbers.
     * @param array $point_rule   Scoring rules from the league.
     * @param int   $status       Match status.
     * @param int   $num_rubbers  Total number of rubbers in the match.
     * @return array{home_points: float, away_points: float}
     */
    public static function calculate_points_from_stats( array $stats_result, array $point_rule, int $status, int $num_rubbers ): array {
        $home_points = $stats_result['home_points'];
        $away_points = $stats_result['away_points'];

        $home_win           = $stats_result['home_win'];
        $away_win           = $stats_result['away_win'];
        $draw               = $stats_result['draw'];
        $home_walkover      = $stats_result['home_walkover'];
        $away_walkover      = $stats_result['away_walkover'];

        $rubber_win         = ! empty( $point_rule['rubber_win'] ) ? (float) $point_rule['rubber_win'] : 0.0;
        $rubber_draw        = ! empty( $point_rule['rubber_draw'] ) ? (float) $point_rule['rubber_draw'] : 0.0;
        $matches_win        = ! empty( $point_rule['matches_win'] ) ? (float) $point_rule['matches_win'] : 0.0;
        $matches_draw       = ! empty( $point_rule['matches_draw'] ) ? (float) $point_rule['matches_draw'] : 0.0;
        $shared_match_rule  = ! empty( $point_rule['shared_match'] ) ? (float) $point_rule['shared_match'] : 0.0;
        $forwalkover_rubber = empty( $point_rule['forwalkover_rubber'] ) ? 0.0 : (float) $point_rule['forwalkover_rubber'];
        $walkover_penalty   = empty( $point_rule['forwalkover_match'] ) ? 0.0 : (float) $point_rule['forwalkover_match'];

        if ( ! empty( $point_rule['match_result'] ) && 'rubber_count' === $point_rule['match_result'] ) {
            if ( 1 === $status ) { // Walkover
                $home_points = $home_win * $rubber_win - $forwalkover_rubber * $home_walkover - $walkover_penalty * $home_walkover;
                $away_points = $away_win * $rubber_win - $forwalkover_rubber * $away_walkover - $walkover_penalty * $away_walkover;
            } elseif ( 3 === $status ) { // Shared
                $home_points = $shared_match_rule * $num_rubbers;
                $away_points = $shared_match_rule * $num_rubbers;
            } else {
                $home_points = $home_win * $rubber_win + $draw * $rubber_draw - $forwalkover_rubber * $home_walkover;
                $away_points = $away_win * $rubber_win + $draw * $rubber_draw - $forwalkover_rubber * $away_walkover;
            }
        } else {
            if ( $home_win > $away_win ) {
                $home_points += $matches_win;
            } elseif ( $home_win < $away_win ) {
                $away_points += $matches_win;
            } else {
                $home_points += $matches_draw;
                $away_points += $matches_draw;
            }

            if ( 1 === $status ) { // Walkover
                $home_points -= $walkover_penalty * $home_walkover;
                $away_points -= $walkover_penalty * $away_walkover;
            }
        }

        return [
            'home_points' => (float) $home_points,
            'away_points' => (float) $away_points,
        ];
    }

    /**
     * Determine winner and loser IDs from points and status.
     *
     * @param float $home_points
     * @param float $away_points
     * @param int|string $home_team_id
     * @param int|string $away_team_id
     * @param int|null $status
     * @param array $custom
     * @return array{winner_id: int|string, loser_id: int|string}
     */
    public static function determine_winner_and_loser(
        float $home_points,
        float $away_points,
        int|string $home_team_id,
        int|string $away_team_id,
        ?int $status = null,
        array $custom = []
    ): array {
        $winner_id = 0;
        $loser_id  = 0;

        if ( 7 === $status || ( ! empty( $custom['withdrawn'] ) ) ) {
            $winner_id = -1;
            $loser_id  = -1;
        } elseif ( 1 === $status || ( ! empty( $custom['walkover'] ) ) ) {
            if ( ( $custom['walkover'] ?? '' ) === 'home' ) {
                $winner_id = $home_team_id;
                $loser_id  = $away_team_id;
            } elseif ( ( $custom['walkover'] ?? '' ) === 'away' ) {
                $winner_id = $away_team_id;
                $loser_id  = $home_team_id;
            } else {
                // Fallback to points if walkover direction is not specified in custom
                if ( $home_points > $away_points ) {
                    $winner_id = $home_team_id;
                    $loser_id  = $away_team_id;
                } elseif ( $home_points < $away_points ) {
                    $winner_id = $away_team_id;
                    $loser_id  = $home_team_id;
                } else {
                    $winner_id = -1;
                    $loser_id  = -1;
                }
            }
        } elseif ( 2 === $status || ( ! empty( $custom['retired'] ) ) ) {
            if ( ( $custom['retired'] ?? '' ) === 'away' ) {
                $winner_id = $home_team_id;
                $loser_id  = $away_team_id;
            } elseif ( ( $custom['retired'] ?? '' ) === 'home' ) {
                $winner_id = $away_team_id;
                $loser_id  = $home_team_id;
            }
        } elseif ( 3 === $status || ( ! empty( $custom['share'] ) ) ) {
            $winner_id = -1;
            $loser_id  = -1;
        } elseif ( '-1' === (string) $home_team_id ) {
            $winner_id = $away_team_id;
            $loser_id  = 0;
        } elseif ( '-1' === (string) $away_team_id ) {
            $winner_id = $home_team_id;
            $loser_id  = 0;
        } elseif ( $home_points > $away_points ) {
            $winner_id = $home_team_id;
            $loser_id  = $away_team_id;
        } elseif ( $home_points < $away_points ) {
            $winner_id = $away_team_id;
            $loser_id  = $home_team_id;
        } else {
            $winner_id = -1;
            $loser_id  = -1;
        }

        return [
            'winner_id' => $winner_id,
            'loser_id'  => $loser_id,
        ];
    }
}
