<?php

namespace Racketmanager\Services\Validator;

use Racketmanager\Domain\Scoring\Scoring_Context;

/**
 * Handles match-level score awards and statistics updates.
 */
class Fixture_Result_Validator {
    /**
     * Handle match status awards (walkovers, retirements, etc.)
     */
    public function handle_match_status_awards( Scoring_Context $context, ?string $match_status, object $match_info, array &$stats, array &$points, &$home_score, &$away_score ): void {
        $walkover_rubber_penalty = $this->calculate_walkover_penalty( $context );

        switch ( $match_status ) {
            case 'walkover_player1':
            case 'walkover_player2':
                $this->handle_walkover_award( $match_status, $match_info, $walkover_rubber_penalty, $stats, $points, $home_score, $away_score );
                break;
            case 'retired_player1':
            case 'retired_player2':
                $this->handle_retirement_award( $match_status, $match_info->num_sets_to_win, $stats, $points, $home_score, $away_score );
                break;
            case 'invalid_player1':
            case 'invalid_player2':
            case 'invalid_players':
                $this->handle_invalid_status_award( $match_status, $match_info, $walkover_rubber_penalty, $stats, $points, $home_score, $away_score );
                break;
            case 'share':
            case 'withdrawn':
            case 'cancelled':
            case 'abandoned':
                $this->handle_miscellaneous_status_awards( $context, $match_status, $match_info->num_sets_to_win, $points, $home_score, $away_score );
                break;
            default:
                break;
        }
    }

    private function calculate_walkover_penalty( Scoring_Context $context ): int {
        if ( ! empty( $context->point_rule['forwalkover_rubber'] ) ) {
            return (int) $context->point_rule['forwalkover_rubber'];
        }

        return 0;
    }

    private function handle_walkover_award( string $match_status, object $match_info, int $walkover_rubber_penalty, array &$stats, array &$points, &$home_score, &$away_score ): void {
        $num_sets_to_win  = $match_info->num_sets_to_win;
        $num_games_to_win = $match_info->num_games_to_win;

        if ( 'walkover_player1' === $match_status ) {
            $stats['sets']['home']      += $num_sets_to_win;
            $points['home']['sets']     += $num_sets_to_win;
            $points['away']['walkover'] = true;
            $home_score                 += $num_sets_to_win;
            $away_score                 -= $walkover_rubber_penalty;
            $stats['games']['home']     += $num_games_to_win * $num_sets_to_win;
        } else {
            $stats['sets']['away']      += $num_sets_to_win;
            $points['away']['sets']     += $num_sets_to_win;
            $points['home']['walkover'] = true;
            $away_score                 += $num_sets_to_win;
            $home_score                 -= $walkover_rubber_penalty;
            $stats['games']['away']     += $num_games_to_win * $num_sets_to_win;
        }
    }

    private function handle_retirement_award( string $match_status, int $num_sets_to_win, array &$stats, array &$points, &$home_score, &$away_score ): void {
        if ( 'retired_player1' === $match_status ) {
            $points['home']['retired'] = true;
            $points['away']['sets']    = $num_sets_to_win;
            $stats['sets']['away']     = $num_sets_to_win;
            $away_score                = $num_sets_to_win;
        } else {
            $points['away']['retired'] = true;
            $points['home']['sets']    = $num_sets_to_win;
            $stats['sets']['home']     = $num_sets_to_win;
            $home_score                = $num_sets_to_win;
        }
    }

    private function handle_invalid_status_award( string $match_status, object $match_info, int $walkover_rubber_penalty, array &$stats, array &$points, &$home_score, &$away_score ): void {
        $num_sets_to_win  = $match_info->num_sets_to_win;
        $num_games_to_win = $match_info->num_games_to_win;

        if ( 'invalid_player2' === $match_status ) {
            $stats['sets']['home']     = $num_sets_to_win;
            $points['home']['sets']    = $num_sets_to_win;
            $points['away']['invalid'] = true;
            $home_score                = $num_sets_to_win;
            $away_score                -= $walkover_rubber_penalty;
            $stats['games']['home']    = $num_games_to_win * $num_sets_to_win;
            $stats['games']['away']    = 0;
        } elseif ( 'invalid_player1' === $match_status ) {
            $stats['sets']['away']     = $num_sets_to_win;
            $points['away']['sets']    = $num_sets_to_win;
            $points['home']['invalid'] = true;
            $away_score                = $num_sets_to_win;
            $home_score                -= $walkover_rubber_penalty;
            $stats['games']['away']    = $num_games_to_win * $num_sets_to_win;
            $stats['games']['home']    = 0;
        } else {
            $stats['sets']['home']     = 0;
            $points['home']['sets']    = 0;
            $stats['sets']['away']     = 0;
            $points['away']['sets']    = 0;
            $points['both']['invalid'] = true;
            $away_score                = $walkover_rubber_penalty;
            $home_score                = $walkover_rubber_penalty;
            $stats['games']['away']    = 0;
            $stats['games']['home']    = 0;
        }
    }

    private function handle_miscellaneous_status_awards( Scoring_Context $context, string $match_status, int $num_sets_to_win, array &$points, &$home_score, &$away_score ): void {
        if ( 'share' === $match_status ) {
            $shared_sets              = $context->num_sets / 2;
            $points['shared']['sets'] = $context->num_sets;
            $home_score               += $shared_sets;
            $away_score               += $shared_sets;
        } elseif ( 'withdrawn' === $match_status ) {
            $points['withdrawn'] = 1;
        } elseif ( 'cancelled' === $match_status ) {
            $points['cancelled'] = 1;
        } elseif ( 'abandoned' === $match_status ) {
            if ( $home_score !== (float) $num_sets_to_win && $away_score !== (float) $num_sets_to_win ) {
                $shared_sets              = $context->num_sets - $home_score - $away_score;
                $points['shared']['sets'] = $shared_sets;
                $home_score               += $shared_sets;
                $away_score               += $shared_sets;
            }
        }
    }

    public function update_match_points_and_stats( array $set, ?string $set_status, ?string $points_format, array &$points, array &$stats, &$home_score, &$away_score ): void {
        $p1        = is_null( $set['player1'] ) ? null : strtoupper( (string) $set['player1'] );
        $p2        = is_null( $set['player2'] ) ? null : strtoupper( (string) $set['player2'] );
        $completed = $set['completed'];

        if ( null !== $p1 && null !== $p2 ) {
            $context = [
                'p1'            => $p1,
                'p2'            => $p2,
                'set_type'      => $set['settype'],
                'points_format' => $points_format,
            ];
            if ( $this->should_award_set_to_home( $p1, $p2, $set_status, $completed ) ) {
                $this->award_set_to_home( $context, $points, $stats, $home_score, $away_score );
            } elseif ( $this->should_award_set_to_away( $p1, $p2, $set_status, $completed ) ) {
                $this->award_set_to_away( $context, $points, $stats, $home_score, $away_score );
            } elseif ( 'S' === $p1 ) {
                $this->award_shared_set( $points, $stats, $home_score, $away_score );
            }
        }

        $this->update_game_stats( $p1, $p2, $set['settype'], $stats );
    }

    private function should_award_set_to_home( ?string $p1, ?string $p2, ?string $set_status, bool $completed ): bool {
        return ( $p1 > $p2 && ( empty( $set_status ) || ( 'abandoned' === $set_status && $completed ) ) ) || in_array( $set_status, [ 'retired_player2', 'invalid_player2', 'invalid_players' ] );
    }

    private function award_set_to_home( array $context, array &$points, array &$stats, &$home_score, &$away_score ): void {
        if ( empty( $context['points_format'] ) ) {
            ++ $points['home']['sets'];
            ++ $stats['sets']['home'];
            ++ $home_score;
            if ( 'MTB' === $context['set_type'] ) {
                ++ $stats['games']['home'];
            }
        } else {
            $home_score = $context['p1'];
            $away_score = $context['p2'];
        }
    }

    private function should_award_set_to_away( ?string $p1, ?string $p2, ?string $set_status, bool $completed ): bool {
        return ( $p1 < $p2 && ( empty( $set_status ) || ( 'abandoned' === $set_status && $completed ) ) ) || in_array( $set_status, [ 'retired_player1', 'invalid_player1' ] );
    }

    private function award_set_to_away( array $context, array &$points, array &$stats, &$home_score, &$away_score ): void {
        if ( empty( $context['points_format'] ) ) {
            ++ $points['away']['sets'];
            ++ $stats['sets']['away'];
            ++ $away_score;
            if ( 'MTB' === $context['set_type'] ) {
                ++ $stats['games']['away'];
            }
        } else {
            $home_score = $context['p1'];
            $away_score = $context['p2'];
        }
    }

    private function award_shared_set( array &$points, array &$stats, &$home_score, &$away_score ): void {
        ++ $points['shared']['sets'];
        $stats['sets']['home'] += 0.5;
        $stats['sets']['away'] += 0.5;
        $home_score            += 0.5;
        $away_score            += 0.5;
    }

    private function update_game_stats( ?string $p1, ?string $p2, string $set_type, array &$stats ): void {
        if ( is_numeric( $p1 ) && 'MTB' !== $set_type ) {
            $stats['games']['home'] += $p1;
        }
        if ( is_numeric( $p2 ) && 'MTB' !== $set_type ) {
            $stats['games']['away'] += $p2;
        }
    }
}
