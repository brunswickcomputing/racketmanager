<?php

namespace Racketmanager\Services\Validator;

/**
 * Handles individual set score validation logic.
 */
class Set_Score_Validator {
    private bool $error = false;
    private array $err_flds = [];
    private array $err_msgs = [];
    private bool $completed_set = false;

    /**
     * Validate set and return updated set data.
     */
    public function validate_set( array $set, string $set_prefix, object $set_info, ?string $match_status ): array {
        $this->reset_state();
        $set = $this->adjust_scores_by_status( $set, $set_info->set_type, $match_status );

        $completed_set = false;
        if ( ! is_null( $set['player1'] ) || ! is_null( $set['player2'] ) ) {
            if ( 'null' === $set_info->set_type ) {
                $this->validate_null_set_type( $set, $set_prefix );
            } elseif ( 'share' === $match_status || 'withdrawn' === $match_status ) {
                $set['player1']  = '';
                $set['player2']  = '';
                $set['tiebreak'] = '';
            } elseif ( 'S' === $set['player1'] || 'S' === $set['player2'] ) {
                $this->validate_shared_scores( $set, $set_prefix );
            } elseif ( empty( $set['player1'] ) && empty( $set['player2'] ) ) {
                $this->validate_empty_scores( $set_prefix, $match_status );
            } elseif ( $set['player1'] === $set['player2'] ) {
                $this->validate_identical_scores( $set_prefix, $match_status );
            } elseif ( $set['player1'] > $set['player2'] ) {
                $this->validate_set_score( $set, $set_prefix, (object) [ 'team_1' => 'player1', 'team_2' => 'player2' ], $set_info, $match_status );
                $completed_set = $this->completed_set;
            } elseif ( $set['player1'] < $set['player2'] ) {
                $this->validate_set_score( $set, $set_prefix, (object) [ 'team_1' => 'player2', 'team_2' => 'player1' ], $set_info, $match_status );
                $completed_set = $this->completed_set;
            } elseif ( '' === $set['player1'] || '' === $set['player2'] ) {
                $this->validate_partially_empty_scores( $set, $set_prefix, $match_status );
            }
        }

        $set['completed'] = $completed_set;
        $set['settype']   = $set_info->set_type;

        return $set;
    }

    private function reset_state(): void {
        $this->error         = false;
        $this->err_flds      = [];
        $this->err_msgs      = [];
        $this->completed_set = false;
    }

    /**
     * Adjust scores based on match status
     */
    private function adjust_scores_by_status( array $set, string $set_type, ?string $match_status ): array {
        $retired   = ! empty( $match_status ) && str_starts_with( $match_status, 'retired' );
        $walkover  = ! empty( $match_status ) && str_starts_with( $match_status, 'walkover' );
        $cancelled = ! empty( $match_status ) && str_starts_with( $match_status, 'cancelled' );
        $abandoned = ! empty( $match_status ) && str_starts_with( $match_status, 'abandoned' );

        if ( $walkover ) {
            if ( 'null' === $set_type ) {
                $set['player1'] = '';
                $set['player2'] = '';
            } else {
                $set['player1'] = null;
                $set['player2'] = null;
            }
            $set['tiebreak'] = '';
        } elseif ( $retired || $abandoned ) {
            if ( 'null' === $set_type ) {
                $set['player1']  = '';
                $set['player2']  = '';
                $set['tiebreak'] = '';
            }
        } elseif ( $cancelled ) {
            $set['player1'] = null;
            $set['player2'] = null;
        }

        return $set;
    }

    /**
     * Validate scores for 'null' set type
     */
    private function validate_null_set_type( array $set, string $set_prefix ): void {
        if ( '' !== $set['player1'] ) {
            $this->error      = true;
            $this->err_flds[] = $set_prefix . 'player1';
            $this->err_msgs[] = __( 'Set score should be empty', 'racketmanager' );
        }
        if ( '' !== $set['player2'] ) {
            $this->error      = true;
            $this->err_flds[] = $set_prefix . 'player2';
            $this->err_msgs[] = __( 'Set score should be empty', 'racketmanager' );
        }
        if ( '' !== $set['tiebreak'] ) {
            $this->error      = true;
            $this->err_flds[] = $set_prefix . 'tiebreak';
            $this->err_msgs[] = __( 'Tie break should be empty', 'racketmanager' );
        }
    }

    /**
     * Validate shared scores ('S')
     */
    private function validate_shared_scores( array $set, string $set_prefix ): void {
        if ( 'S' !== $set['player1'] ) {
            $this->error      = true;
            $this->err_flds[] = $set_prefix . 'player1';
            $this->err_msgs[] = __( 'Both scores must be shared', 'racketmanager' );
        }
        if ( 'S' !== $set['player2'] ) {
            $this->error      = true;
            $this->err_flds[] = $set_prefix . 'player2';
            $this->err_msgs[] = __( 'Both scores must be shared', 'racketmanager' );
        }
    }

    /**
     * Validate when both scores are empty
     */
    private function validate_empty_scores( string $set_prefix, ?string $match_status ): void {
        $retired  = ! empty( $match_status ) && str_starts_with( $match_status, 'retired' );
        $walkover = ! empty( $match_status ) && str_starts_with( $match_status, 'walkover' );

        if ( ! $retired && ! $walkover && 'abandoned' !== $match_status ) {
            $this->error      = true;
            $this->err_flds[] = $set_prefix . 'player1';
            $this->err_flds[] = $set_prefix . 'player2';
            $this->err_msgs[] = __( 'Set scores must be entered', 'racketmanager' );
        }
    }

    /**
     * Validate when scores are identical
     */
    private function validate_identical_scores( string $set_prefix, ?string $match_status ): void {
        $retired   = ! empty( $match_status ) && str_starts_with( $match_status, 'retired' );
        $walkover  = ! empty( $match_status ) && str_starts_with( $match_status, 'walkover' );
        $abandoned = ! empty( $match_status ) && str_starts_with( $match_status, 'abandoned' );

        if ( ! $retired && ! $walkover && ! $abandoned ) {
            $this->error      = true;
            $this->err_flds[] = $set_prefix . 'player1';
            $this->err_flds[] = $set_prefix . 'player2';
            $this->err_msgs[] = __( 'Set scores must be different', 'racketmanager' );
        }
    }

    private function validate_set_score( array $set, string $set_prefix, object $teams, object $set_info, ?string $match_status = null ): void {
        $team_1            = $teams->team_1;
        $team_2            = $teams->team_2;
        $tiebreak_allowed  = $set_info->tiebreak_allowed;
        $tiebreak_required = $set_info->tiebreak_required;
        $tiebreak_set      = $set_info->tiebreak_set;
        $max_win           = $set_info->max_win;
        $min_win           = $set_info->min_win;
        $max_loss          = $set_info->max_loss;
        $min_loss          = $set_info->min_loss;
        $retired           = ! empty( $match_status ) && substr( $match_status, 0, 8 ) === 'retired';

        $this->completed_set = true;

        if ( $set[ $team_1 ] < $min_win && $retired ) {
            if ( 'abandoned' === $match_status ) {
                $this->completed_set = false;
            } else {
                $this->error      = true;
                $this->err_msgs[] = __( 'Winning set score too low', 'racketmanager' );
                $this->err_flds[] = $set_prefix . $team_1;
            }
        } elseif ( ! $this->check_score_too_high( $set, $set_prefix, $team_1, $max_win ) ) {
            if ( $retired && intval( $set[ $team_1 ] ) === intval( $min_win ) && $max_win !== $min_win && $set[ $team_2 ] > $min_loss ) {
                $this->error      = true;
                $this->err_msgs[] = __( 'Games difference must be at least 2', 'racketmanager' );
                $this->err_flds[] = $set_prefix . $team_1;
                $this->err_flds[] = $set_prefix . $team_2;
            } else {
                $rules = (object) [
                    'min_win'      => $min_win,
                    'min_loss'     => $min_loss,
                    'max_win'      => $max_win,
                    'max_loss'     => $max_loss,
                    'match_status' => $match_status,
                ];
                $this->check_game_difference( $set, $set_prefix, $teams, $rules );
                $this->validate_tiebreak_score( $set, $set_prefix, $tiebreak_allowed, $tiebreak_required, $tiebreak_set );
            }
        }
    }

    private function check_score_too_high( array $set, string $set_prefix, string $team_1, int $max_win ): bool {
        if ( $set[ $team_1 ] > $max_win ) {
            $this->error      = true;
            $this->err_msgs[] = __( 'Winning set score too high', 'racketmanager' );
            $this->err_flds[] = $set_prefix . $team_1;

            return true;
        }

        return false;
    }

    private function check_game_difference( array $set, string $set_prefix, object $teams, object $rules ): void {
        $team_1       = $teams->team_1;
        $team_2       = $teams->team_2;
        $min_win      = $rules->min_win;
        $min_loss     = $rules->min_loss;
        $max_win      = $rules->max_win;
        $max_loss     = $rules->max_loss;
        $match_status = $rules->match_status;

        $game_diff_msg = __( 'Games difference incorrect', 'racketmanager' );

        if ( intval( $set[ $team_1 ] ) === $max_win ) {
            if ( $set[ $team_2 ] < $max_loss && $max_win !== $min_win ) {
                $this->error      = true;
                $this->err_msgs[] = $game_diff_msg;
                $this->err_flds[] = $set_prefix . $team_1;
                $this->err_flds[] = $set_prefix . $team_2;
            }
        } elseif ( intval( $set[ $team_1 ] ) === $min_win && intval( $set[ $team_2 ] ) > $max_loss ) {
            $this->error      = true;
            $this->err_msgs[] = $game_diff_msg;
            $this->err_flds[] = $set_prefix . $team_1;
            $this->err_flds[] = $set_prefix . $team_2;
        } elseif ( intval( $set[ $team_1 ] ) > $min_win ) {
            if ( intval( $set[ $team_2 ] ) < $min_loss ) {
                $this->error      = true;
                $this->err_msgs[] = $game_diff_msg;
                $this->err_flds[] = $set_prefix . $team_1;
                $this->err_flds[] = $set_prefix . $team_2;
            } elseif ( ( intval( $set[ $team_1 ] ) - 2 ) !== intval( $set[ $team_2 ] ) && ! str_starts_with( $match_status ?? '', 'retired_player' ) ) {
                $this->error      = true;
                $this->err_msgs[] = $game_diff_msg;
                $this->err_flds[] = $set_prefix . $team_2;
            }
        }
    }

    private function validate_tiebreak_score( array $set, string $set_prefix, bool $tiebreak_allowed, bool $tiebreak_required, ?int $tiebreak_set ): void {
        if ( null === $tiebreak_set ) {
            return;
        }
        $tie_break_score_required = __( 'Tie break score required', 'racketmanager' );
        $tie_break_whole_number   = __( 'Tie break score must be whole number', 'racketmanager' );

        $is_tiebreak_score_needed = ( intval( $set['player1'] ) > $tiebreak_set && intval( $set['player2'] ) === $tiebreak_set ) || ( intval( $set['player1'] ) === $tiebreak_set && intval( $set['player2'] ) > $tiebreak_set );

        if ( $set['tiebreak'] > '' ) {
            if ( ! $tiebreak_allowed && ! $tiebreak_required ) {
                $this->error      = true;
                $this->err_msgs[] = __( 'Tie break score should be empty', 'racketmanager' );
                $this->err_flds[] = $set_prefix . 'tiebreak';
            } elseif ( ! is_numeric( $set['tiebreak'] ) || strval( round( $set['tiebreak'] ) ) !== $set['tiebreak'] ) {
                $this->error      = true;
                $this->err_msgs[] = $tie_break_whole_number;
                $this->err_flds[] = $set_prefix . 'tiebreak';
            }
        } elseif ( $tiebreak_required || ( $is_tiebreak_score_needed && $tiebreak_allowed ) ) {
            $this->error      = true;
            $this->err_msgs[] = $tie_break_score_required;
            $this->err_flds[] = $set_prefix . 'tiebreak';
        }
    }

    /**
     * Validate when one of the scores is empty
     */
    private function validate_partially_empty_scores( array $set, string $set_prefix, ?string $match_status ): void {
        $retired  = ! empty( $match_status ) && str_starts_with( $match_status, 'retired' );
        $walkover = ! empty( $match_status ) && str_starts_with( $match_status, 'walkover' );

        if ( ! $retired && ! $walkover ) {
            $this->error = true;
            if ( '' === $set['player1'] ) {
                $this->err_flds[] = $set_prefix . 'player1';
            }
            if ( '' === $set['player2'] ) {
                $this->err_flds[] = $set_prefix . 'player2';
            }
            $this->err_msgs[] = __( 'Set score not entered', 'racketmanager' );
        }
    }

    public function get_error(): bool {
        return $this->error;
    }

    public function get_err_flds(): array {
        return $this->err_flds;
    }

    public function get_err_msgs(): array {
        return $this->err_msgs;
    }
}
