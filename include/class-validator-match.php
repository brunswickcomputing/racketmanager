<?php
/**
 * Match Validation API: Match validator class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Validate
 */

namespace Racketmanager;

/**
 * Class to implement the Match Validator object
 */
final class Validator_Match extends Validator {
    public bool $completed_set;
    /**
     * @var float|int|mixed|string
     */
    public mixed $home_points;
    /**
     * @var float|int|mixed|string
     */
    public mixed $away_points;
    public array $sets;
    public array $stats;
    public array $points;
    public array $rubbers = array();
    private array $players_involved = array();

    /**
     * Validate modal
     *
     * @param ?string $modal modal name.
     * @param string $error_field error field.
     * @return object $validation updated validation object.
     */
    public function modal( ?string $modal, string $error_field = 'match' ): object {
        if ( empty( $modal ) ) {
            $this->error      = true;
            $this->err_flds[] = $error_field;
            $this->err_msgs[] = __( 'Modal name not supplied', 'racketmanager' );
        }
        return $this;
    }
    /**
     * Validate match
     *
     * @param ?int $match_id match id.
     * @param string $error_field error field.
     * @return object $validation updated validation object.
     */
    public function match( ?int $match_id, string $error_field = 'match' ): object {
        if ( empty( $match_id ) ) {
            $this->error      = true;
            $this->err_flds[] = $error_field;
            $this->err_msgs[] = __( 'Match id not supplied', 'racketmanager' );
        } else {
            $match = get_match( $match_id );
            if ( ! $match ) {
                $this->error      = true;
                $this->err_flds[] = $error_field;
                $this->err_msgs[] = __( 'Match not found', 'racketmanager' );
            }
        }
        return $this;
    }
    /**
     * Validate new match date
     *
     * @param ?string $schedule_date new match_date.
     * @param string $match_date current match date.
     * @return object $validation updated validation object.
     */
    public function scheduled_date( ?string $schedule_date, string $match_date ): object {
        if ( empty( $schedule_date ) ) {
            $this->error      = true;
            $this->err_flds[] = 'schedule-date';
            $this->err_msgs[] = __( 'New date not set', 'racketmanager' );
        } else {
            if ( strlen( $schedule_date ) === 10 ) {
                $schedule_date = substr( $schedule_date, 0, 10 );
                $match_date    = substr( $match_date, 0, 10 );
            } else {
                $schedule_date = substr( $schedule_date, 0, 10 ) . ' ' . substr( $schedule_date, 11, 5 );
            }
            if ( $schedule_date === $match_date ) {
                $this->error      = true;
                $this->err_flds[] = 'schedule-date';
                $this->err_msgs[] = __( 'Date not changed', 'racketmanager' );
            }
        }
        return $this;
    }
    /**
     * Validate match status
     *
     * @param ?string $match_status match status.
     * @param string $error_field error field.
     * @param bool   $required is null value invalid.
     * @return object $validation updated validation object.
     */
    public function match_status( ?string $match_status, string $error_field = 'match', bool $required = false ): object {
        if ( empty( $match_status ) ) {
            if ( $required ) {
                $this->error      = true;
                $this->err_flds[] = $error_field;
                $this->err_msgs[] = __( 'No match status selected', 'racketmanager' );
            }
        } else {
            $match_status_values = explode( '_', $match_status );
            $status_value        = $match_status_values[0];
            $player_ref          = $match_status_values[1] ?? null;
            switch ( $status_value ) {
                case 'walkover':
                case 'retired':
                    if ( 'player1' !== $player_ref && 'player2' !== $player_ref ) {
                        $this->error      = true;
                        $this->err_flds[] = $error_field;
                        $this->err_msgs[] = __( 'Score status team selection not valid', 'racketmanager' );
                    }
                    break;
                case 'none':
                case 'abandoned':
                case 'cancelled':
                case 'share':
                    break;
                default:
                    $this->error      = true;
                    $this->err_flds[] = $error_field;
                    $this->err_msgs[] = __( 'Match status not valid', 'racketmanager' );
                    break;
            }
        }
        return $this;
    }
    /**
     * Validate rubber
     *
     * @param ?int $rubber_id rubber id.
     * @param string $error_field error field.
     * @return object $validation updated validation object.
     */
    public function rubber( ?int $rubber_id, string $error_field = 'match' ): object {
        if ( empty( $rubber_id ) ) {
            $this->error      = true;
            $this->err_flds[] = $error_field;
            $this->err_msgs[] = __( 'Rubber id not supplied', 'racketmanager' );
        } else {
            $rubber = get_rubber( $rubber_id );
            if ( ! $rubber ) {
                $this->error      = true;
                $this->err_flds[] = $error_field;
                $this->err_msgs[] = __( 'Rubber not found', 'racketmanager' );
            }
        }
        return $this;
    }
    /**
     * Validate rubber number
     *
     * @param ?int $rubber_number rubber number.
     * @param string $error_field error field.
     * @return object $validation updated validation object.
     */
    public function rubber_number( ?int $rubber_number, string $error_field = 'match' ): object {
        if ( empty( $rubber_number ) ) {
            $this->error      = true;
            $this->err_flds[] = $error_field;
            $this->err_msgs[] = __( 'Rubber number not supplied', 'racketmanager' );
        }
        return $this;
    }
    /**
     * Validate score status
     *
     * @param ?string $score_status score status.
     * @param string  $error_field error field.
     * @param bool    $required is value required.
     * @return object $validation updated validation object.
     */
    public function score_status( ?string $score_status, string $error_field = 'match', bool $required = false ): object {
        if ( empty( $score_status ) ) {
            if ( $required ) {
                $this->error      = true;
                $this->err_flds[] = $error_field;
                $this->err_msgs[] = __( 'No match status selected', 'racketmanager' );
            }
        } else {
            $score_status_values = explode( '_', $score_status );
            $status_value        = $score_status_values[0];
            $player_ref = $score_status_values[1] ?? null;
            switch ( $status_value ) {
                case 'walkover':
                case 'retired':
                    if ( 'player1' !== $player_ref && 'player2' !== $player_ref ) {
                        $this->error      = true;
                        $this->err_flds[] = 'score_status';
                        $this->err_msgs[] = __( 'Score status team selection not valid', 'racketmanager' );
                    }
                    break;
                case 'share':
                case 'none':
                case 'invalid':
                case 'abandoned':
                    break;
                default:
                    $this->error      = true;
                    $this->err_flds[] = 'score_status';
                    $this->err_msgs[] = __( 'Score status not valid', 'racketmanager' );
                    break;
            }
        }
        return $this;
    }

    /**
     * Validate match score
     *
     * @param object $match match object.
     * @param array|null $sets sets.
     * @param string|null $match_status match status.
     * @param string      $set_prefix_start
     * @param int|null $rubber_number
     *
     * @return object $validation updated validation object.
     */
    public function match_score( object $match, ?array $sets, ?string $match_status, string $set_prefix_start, ?int $rubber_number = null ): object {
        $num_sets_to_win  = intval( $match->league->num_sets_to_win );
        $num_games_to_win = 1;
        $point_rule       = $match->league->get_point_rule();
        $points_format    = null;
        if ( 1 === $num_sets_to_win && ! empty( $point_rule['match_result'] ) && 'games' === $point_rule['match_result'] ) {
            $points_format = 'games';
        }
        $home_score             = 0;
        $away_score             = 0;
        $scoring                = $match->league->scoring ?? 'TB';
        $sets_updated           = array();
        $s                      = 1;
        $stats                  = array();
        $stats['sets']['home']  = 0;
        $stats['sets']['away']  = 0;
        $stats['games']['home'] = 0;
        $stats['games']['away'] = 0;

        $points['home']['sets']   = 0;
        $points['away']['sets']   = 0;
        $points['shared']['sets'] = 0;
        $points['split']['sets']  = 0;
        if ( ! empty( $sets ) ) {
            $num_sets    = count( $sets );
            $set_retired = null;
            if ( 'retired_player1' === $match_status || 'retired_player2' === $match_status || 'abandoned' === $match_status ) {
                for ( $s1 = $num_sets; $s1 >= 1; $s1-- ) {
                    if ( '' !== $sets[ $s1 ]['player1'] || '' !== $sets[ $s1 ]['player2'] ) {
                        $set_retired = $s1;
                        break;
                    }
                }
            }
            foreach ( $sets as $set ) {
                $set_prefix = $set_prefix_start . $s . '_';
                $set_type   = Util::get_set_type( $scoring, $match->final_round, $match->league->num_sets, $s, $rubber_number, $match->num_rubbers, $match->leg );
                $set_info   = Util::get_set_info( $set_type );
                if ( 1 === $s ) {
                    $num_games_to_win = $set_info->min_win;
                }
                if ( ( $s > $num_sets_to_win ) && ( $home_score === $num_sets_to_win || $away_score === $num_sets_to_win ) ) {
                    $set_info->set_type = 'null';
                }
                $set_status = null;
                switch ( $match_status ) {
                    case 'retired_player1':
                    case 'retired_player2':
                    case 'abandoned':
                        if ( $set_retired === $s ) {
                            $set_status = $match_status;
                        } elseif ( $s > $set_retired ) {
                            $set_info->set_type = 'null';
                        }
                        break;
                    case 'cancelled':
                    default:
                        $set_status = $match_status;
                        break;
                }
                $set = $this->validate_set( $set, $set_prefix, $set_info, $set_status );
                $set_player_1  = is_null( $set['player1'] ) ? null : strtoupper( $set['player1'] );
                $set_player_2  = is_null( $set['player2'] ) ? null : strtoupper( $set['player2'] );
                $set_completed = $set['completed'];
                if ( null !== $set_player_1 && null !== $set_player_2 ) {
                    if ( ( $set_player_1 > $set_player_2 && ( empty( $set_status ) || ( 'abandoned' === $set_status && $set_completed ) ) ) || ( 'retired_player2' ) === $set_status || ( 'invalid_player2' ) === $set_status || ( 'invalid_players' ) === $set_status ) {
                        if ( empty( $points_format ) ) {
                            ++$points['home']['sets'];
                            ++$stats['sets']['home'];
                            ++$home_score;
                            if ( 'MTB' === $set['settype'] ) {
                                ++$stats['games']['home'];
                            }
                        } else {
                            $home_score = $set_player_1;
                            $away_score = $set_player_2;
                        }
                    } elseif ( ( $set_player_1 < $set_player_2 && ( empty( $set_status ) || ( 'abandoned' === $set_status && $set_completed ) ) ) || ( 'retired_player1' ) === $set_status || ( 'invalid_player1' ) === $set_status ) {
                        if ( empty( $points_format ) ) {
                            ++$points['away']['sets'];
                            ++$stats['sets']['away'];
                            ++$away_score;
                            if ( 'MTB' === $set['settype'] ) {
                                ++$stats['games']['away'];
                            }
                        } else {
                            $home_score = $set_player_1;
                            $away_score = $set_player_2;
                        }
                    } elseif ( 'S' === $set_player_1 ) {
                        ++$points['shared']['sets'];
                        $stats['sets']['home'] += 0.5;
                        $stats['sets']['away'] += 0.5;
                        $home_score             += 0.5;
                        $away_score             += 0.5;
                    }
                }
                if ( is_numeric( $set_player_1 ) && 'MTB' !== $set['settype'] ) {
                    $stats['games']['home'] += $set_player_1;
                }
                if ( is_numeric( $set_player_2 ) && 'MTB' !== $set['settype'] ) {
                    $stats['games']['away'] += $set_player_2;
                }
                $sets_updated[ $s ] = $set;
                ++$s;
            }
            if ( ! empty( $home_score ) && ! empty( $away_score ) ) {
                ++$points['split']['sets'];
            }
        }
        if ( 'league' === $match->league->event->competition->type ) {
            $point_rule              = $match->league->get_point_rule();
            $walkover_rubber_penalty = empty( $point_rule['forwalkover_rubber'] ) ? 0 : $point_rule['forwalkover_rubber'];
        } else {
            $walkover_rubber_penalty = 0;
        }
        if ( 'walkover_player1' === $match_status ) {
            $stats['sets']['home']     += $num_sets_to_win;
            $points['home']['sets']    += $num_sets_to_win;
            $points['away']['walkover'] = true;
            $home_score                += $num_sets_to_win;
            $away_score                -= $walkover_rubber_penalty;
            $stats['games']['home']    += $num_games_to_win * $num_sets_to_win;
        } elseif ( 'walkover_player2' === $match_status ) {
            $stats['sets']['away']     += $num_sets_to_win;
            $points['away']['sets']    += $num_sets_to_win;
            $points['home']['walkover'] = true;
            $away_score                += $num_sets_to_win;
            $home_score                -= $walkover_rubber_penalty;
            $stats['games']['away']    += $num_games_to_win * $num_sets_to_win;
        } elseif ( 'retired_player1' === $match_status ) {
            $points['home']['retired'] = true;
            $points['away']['sets']    = $num_sets_to_win;
            $stats['sets']['away']     = $num_sets_to_win;
            $away_score                = $num_sets_to_win;
        } elseif ( 'retired_player2' === $match_status ) {
            $points['away']['retired'] = true;
            $points['home']['sets']    = $num_sets_to_win;
            $stats['sets']['home']     = $num_sets_to_win;
            $home_score                = $num_sets_to_win;
        } elseif ( 'invalid_player2' === $match_status ) {
            $stats['sets']['home']     = $num_sets_to_win;
            $points['home']['sets']    = $num_sets_to_win;
            $points['away']['invalid'] = true;
            $home_score                 = $num_sets_to_win;
            $away_score                -= $walkover_rubber_penalty;
            $stats['games']['home']    = $num_games_to_win * $num_sets_to_win;
            $stats['games']['away']    = 0;
        } elseif ( 'invalid_player1' === $match_status ) {
            $stats['sets']['away']     = $num_sets_to_win;
            $points['away']['sets']    = $num_sets_to_win;
            $points['home']['invalid'] = true;
            $away_score                = $num_sets_to_win;
            $home_score               -= $walkover_rubber_penalty;
            $stats['games']['away']    = $num_games_to_win * $num_sets_to_win;
            $stats['games']['home']    = 0;
        } elseif ( 'invalid_players' === $match_status ) {
            $stats['sets']['home']     = 0;
            $points['home']['sets']    = 0;
            $stats['sets']['away']     = 0;
            $points['away']['sets']    = 0;
            $points['both']['invalid'] = true;
            $away_score                = $walkover_rubber_penalty;
            $home_score                = $walkover_rubber_penalty;
            $stats['games']['away']    = 0;
            $stats['games']['home']    = 0;
        } elseif ( 'share' === $match_status ) {
            $shared_sets              = $match->league->num_sets / 2;
            $points['shared']['sets'] = $match->league->num_sets;
            $home_score              += $shared_sets;
            $away_score              += $shared_sets;
        } elseif ( 'withdrawn' === $match_status ) {
            $points['withdrawn'] = 1;
        } elseif ( 'cancelled' === $match_status ) {
            $points['cancelled'] = 1;
        } elseif ( 'abandoned' === $match_status ) {
            if ( $home_score !== $num_sets_to_win && $away_score !== $num_sets_to_win ) {
                $shared_sets              = $match->league->num_sets - $home_score - $away_score;
                $points['shared']['sets'] = $shared_sets;
                $home_score              += $shared_sets;
                $away_score              += $shared_sets;
            }
        }
        $this->home_points = $home_score;
        $this->away_points = $away_score;
        $this->sets        = $sets_updated;
        $this->stats       = $stats;
        $this->points      = $points;
        return $this;
    }
    /**
     * Validate set
     *
     * @param array $set set information.
     * @param string $set_prefix set prefix.
     * @param object $set_info type of set.
     * @param string|null $match_status match_status setting.
     */
    public function validate_set( array $set, string $set_prefix, object $set_info, ?string $match_status ): array {
        $completed_set  = false;
        $set_type       = $set_info->set_type;
        if ( 'walkover_player1' === $match_status || 'walkover_player2' === $match_status ) {
            if ( 'null' === $set_type ) {
                $set['player1'] = '';
                $set['player2'] = '';
            } else {
                $set['player1'] = null;
                $set['player2'] = null;
            }
            $set['tiebreak'] = '';
        } elseif ( 'retired_player1' === $match_status || 'retired_player2' === $match_status || 'abandoned' === $match_status ) {
            if ( 'null' === $set_type ) {
                $set['player1']  = '';
                $set['player2']  = '';
                $set['tiebreak'] = '';
            }
        }
        if ( ! is_null( $set['player1'] ) || ! is_null( $set['player2'] ) ) {
            if ( 'null' === $set_type ) {
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
            } elseif ( 'share' === $match_status || 'withdrawn' === $match_status ) {
                $set['player1']  = '';
                $set['player2']  = '';
                $set['tiebreak'] = '';
            } elseif ( 'S' === $set['player1'] || 'S' === $set['player2'] ) {
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
            } elseif ( empty( $set['player1'] ) && empty( $set['player2'] ) ) {
                if ( 'retired_player1' !== $match_status && 'retired_player2' !== $match_status && 'abandoned' !== $match_status ) {
                    $this->error      = true;
                    $this->err_flds[] = $set_prefix . 'player1';
                    $this->err_flds[] = $set_prefix . 'player2';
                    $this->err_msgs[] = __( 'Set scores must be entered', 'racketmanager' );
                }
            } elseif ( $set['player1'] === $set['player2'] ) {
                if ( 'retired_player1' !== $match_status && 'retired_player2' !== $match_status && 'abandoned' !== $match_status ) {
                    $this->error      = true;
                    $this->err_flds[] = $set_prefix . 'player1';
                    $this->err_flds[] = $set_prefix . 'player2';
                    $this->err_msgs[] = __( 'Set scores must be different', 'racketmanager' );
                }
            } elseif ( $set['player1'] > $set['player2'] ) {
                $this->validate_set_score( $set, $set_prefix, 'player1', 'player2', $set_info, $match_status );
                $completed_set   = $this->completed_set;
            } elseif ( $set['player1'] < $set['player2'] ) {
                $this->validate_set_score( $set, $set_prefix, 'player2', 'player1', $set_info, $match_status );
                $completed_set   = $this->completed_set;
            } elseif ( '' === $set['player1'] || '' === $set['player2'] ) {
                if ( 'retired_player1' !== $match_status && 'retired_player2' !== $match_status ) {
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
        }
        $set['completed'] = $completed_set;
        $set['settype']   = $set_type;
        return $set;
    }

    /**
     * Validate set score function
     *
     * @param array $set set details.
     * @param string $set_prefix ste prefix.
     * @param string $team_1 team 1.
     * @param string $team_2 team 2.
     * @param object $set_info set info.
     * @param string|null $match_status match status.
     *
     * @return void
     */
    private function validate_set_score( array $set, string $set_prefix, string $team_1, string $team_2, object $set_info, string $match_status = null ): void {
        $game_difference_incorrect = __( 'Games difference incorrect', 'racketmanager' );
        $tie_break_score_required  = __( 'Tie break score required', 'racketmanager' );
        $tiebreak_allowed  = $set_info->tiebreak_allowed;
        $tiebreak_required = $set_info->tiebreak_required;
        $max_win           = $set_info->max_win;
        $min_win           = $set_info->min_win;
        $max_loss          = $set_info->max_loss;
        $min_loss          = $set_info->min_loss;
        $retired_player    = 'retired_' . $team_2;
        $completed_set     = true;
        if ( $set[ $team_1 ] < $min_win && $match_status !== $retired_player ) {
            if ( 'abandoned' === $match_status ) {
                $completed_set = false;
            } else {
                $this->error      = true;
                $this->err_msgs[] = __( 'Winning set score too low', 'racketmanager' );
                $this->err_flds[] = $set_prefix . $team_1;
            }
        } elseif ( $set[ $team_1 ] > $max_win ) {
            $this->error      = true;
            $this->err_msgs[] = __( 'Winning set score too high', 'racketmanager' );
            $this->err_flds[] = $set_prefix . $team_1;
        } elseif ( intval( $set[ $team_1 ] ) === intval( $min_win ) && $max_win !== $min_win && $set[ $team_2 ] > $min_loss && $match_status !== $retired_player ) {
            $this->error      = true;
            $this->err_msgs[] = __( 'Games difference must be at least 2', 'racketmanager' );
            $this->err_flds[] = $set_prefix . $team_1;
            $this->err_flds[] = $set_prefix . $team_2;
        } elseif ( intval( $set[ $team_1 ] ) === $max_win ) {
            if ( $set[ $team_2 ] < $max_loss && $max_win !== $min_win ) {
                $this->error      = true;
                $this->err_msgs[] = $game_difference_incorrect;
                $this->err_flds[] = $set_prefix . $team_1;
                $this->err_flds[] = $set_prefix . $team_2;
            } elseif ( $tiebreak_allowed && $set[ $team_2 ] > $max_loss ) {
                if ( ! strlen( $set['tiebreak'] ) > 0 ) {
                    $this->error      = true;
                    $this->err_msgs[] = $tie_break_score_required;
                    $this->err_flds[] = $set_prefix . 'tiebreak';
                } elseif ( ! is_numeric( $set['tiebreak'] ) || strval( round( $set['tiebreak'] ) ) !== $set['tiebreak'] ) {
                    $this->error      = true;
                    $this->err_msgs[] = __( 'Tie break score must be whole number', 'racketmanager' );
                    $this->err_flds[] = $set_prefix . 'tiebreak';
                }
            } elseif ( $tiebreak_required && '' === $set['tiebreak'] ) {
                $this->error      = true;
                $this->err_msgs[] = $tie_break_score_required;
                $this->err_flds[] = $set_prefix . 'tiebreak';
            }
        } elseif ( $set[ $team_1 ] > $min_win && $set[ $team_2 ] < $min_loss ) {
            $this->error      = true;
            $this->err_msgs[] = $game_difference_incorrect;
            $this->err_flds[] = $set_prefix . $team_1;
            $this->err_flds[] = $set_prefix . $team_2;
        } elseif ( $set[ $team_1 ] > $min_win && $set[ $team_2 ] > $min_loss && ( $set[ $team_1 ] - 2 ) !== intval( $set[ $team_2 ] ) ) {
            if ( ! str_starts_with( $match_status, 'retired_player' ) ) {
                $this->error      = true;
                $this->err_msgs[] = $game_difference_incorrect;
                $this->err_flds[] = $set_prefix . $team_2;
            }
        } elseif ( $set['tiebreak'] > '' ) {
            if ( ! $tiebreak_required ) {
                $this->error      = true;
                $this->err_msgs[] = __( 'Tie break score should be empty', 'racketmanager' );
                $this->err_flds[] = $set_prefix . 'tiebreak';
            }
        } elseif ( $tiebreak_required ) {
            if ( '' === $set['tiebreak'] ) {
                $this->error      = true;
                $this->err_msgs[] = $tie_break_score_required;
                $this->err_flds[] = $set_prefix . 'tiebreak';
            } elseif ( ! is_numeric( $set['tiebreak'] ) || strval( round( $set['tiebreak'] ) ) !== $set['tiebreak'] ) {
                $this->error      = true;
                $this->err_msgs[] = __( 'Tie break score must be whole number', 'racketmanager' );
                $this->err_flds[] = $set_prefix . 'tiebreak';
            }
        }
        $this->completed_set = $completed_set;
    }

    /**
     * Validate team match action
     *
     * @param string|null $action
     *
     * @return object
     */
    public function result_action( ?string $action ): object {
        if ( empty( $action) ) {
            $this->error      = true;
            $this->err_msgs[] = __( 'Action is not set', 'racketmanager' );
            $this->err_flds[] = 'action';
        } elseif ( 'results' !== $action && 'confirm' !== $action ) {
            $this->error      = true;
            $this->err_msgs[] = __( 'Invalid action', 'racketmanager' );
            $this->err_flds[] = 'action';
        }
        return $this;
    }

    /**
     * Function to validate result confirmation action.
     *
     * @param string|null $result_confirm
     * @param string|null $comments
     *
     * @return object
     */
    public function result_confirm( ?string $result_confirm, ?string $comments ): object {
        if ( empty( $result_confirm ) ) {
            $this->error      = true;
            $this->err_msgs[] = __( 'Either confirm or challenge result', 'racketmanager' );
            $this->err_flds[] = 'resultConfirm';
            $this->err_flds[] = 'resultChallenge';
            $this->status     = 400;
        } elseif ( 'C' === $result_confirm ) {
            if ( empty( $comments ) ) {
                $this->error      = true;
                $this->err_msgs[] = __( 'You must enter a reason for challenging the result', 'racketmanager' );
                $this->err_flds[] = 'resultConfirmComments';
                $this->status     = 400;
            }
        } elseif ( 'A' !== $result_confirm ) {
            $this->error      = true;
            $this->err_msgs[] = __( 'Invalid option selected', 'racketmanager' );
            $this->err_flds[] = 'resultConfirm';
            $this->err_flds[] = 'resultChallenge';
            $this->status     = 400;
        }
        return $this;
    }

    /**
     * Function to check if user is in team
     *
     * @param object $is_update_allowed
     * @param array $match_players
     *
     * @return object
     */
    public function can_player_enter_result( object $is_update_allowed, array $match_players ): object {
        $update_allowed = $is_update_allowed->user_can_update;
        $user_type      = $is_update_allowed->user_type;
        if ( $update_allowed ) {
            if ( 'player' === $user_type ) {
                $player_found = false;
                $userid = wp_get_current_user()->ID;
                if ( $userid ) {
                    $player = get_player( $userid );
                    if ( $player ) {
                        $player_clubs = $player->get_clubs();
                        if ( $player_clubs ) {
                            foreach ( $player_clubs as $player_club ) {
                                $club_player_id = $player_club->club_player_id;
                                foreach ( $match_players as $teams ) {
                                    foreach ( $teams as $players ) {
                                        foreach ( $players as $player ) {
                                            if ( $player == $club_player_id ) {
                                                $player_found = true;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                if ( ! $player_found ) {
                    $this->error      = true;
                    $this->err_msgs[] = __( 'Player cannot submit results', 'racketmanager' );
                }
            }
        } else {
            $this->error      = true;
            $this->err_msgs[] = __( 'Result entry not permitted', 'racketmanager' );
        }
        return $this;
    }

    /**
     * Function to check players involved in match
     *
     * @param array $players
     * @param array $player_numbers
     * @param int $rubber
     * @param bool $playoff
     * @param bool $reverse_rubber
     *
     * @return object
     */
    public function players_involved( array $players, array $player_numbers, int $rubber, bool $playoff, bool $reverse_rubber ): object {
        $opponents        = array( 'home', 'away' );
        foreach ( $opponents as $opponent ) {
            $team_players = $players[ $opponent ] ?? array();
            foreach ( $player_numbers as $player_number ) {
                if ( empty( $team_players[ $player_number ] ) ) {
                    $this->error      = true;
                    $this->err_flds[] = 'players_' . $rubber . '_' . $opponent . '_' . $player_number;
                    $this->err_msgs[] = __( 'Player not selected', 'racketmanager' );
                } else {
                    $player_ref  = $team_players[ $player_number ];
                    $club_player = get_club_player( $player_ref );
                    if ( ! $club_player->system_record ) {
                        $player_found = in_array( $player_ref, $this->players_involved, true );
                        if ( ! $player_found ) {
                            if ( $playoff ) {
                                $this->error      = true;
                                $this->err_flds[] = 'players_' . $rubber . '_' . $opponent . '_' . $player_number;
                                $this->err_msgs[] = __( 'Player for playoff must have played', 'racketmanager' );
                            } elseif ( $reverse_rubber ) {
                                $this->error      = true;
                                $this->err_flds[] = 'players_' . $rubber . '_' . $opponent . '_' . $player_number;
                                $this->err_msgs[] = __( 'Player for reverse rubber must have played', 'racketmanager' );
                            } else {
                                $this->players_involved[] = $player_ref;
                            }
                        } elseif ( ! $playoff && ! $reverse_rubber ) {
                            $this->error      = true;
                            $this->err_flds[] = 'players_' . $rubber . '_' . $opponent . '_' . $player_number;
                            $this->err_msgs[] = __( 'Player already selected', 'racketmanager' );
                        }
                    }
                }
            }
        }
        return $this;
    }
}
