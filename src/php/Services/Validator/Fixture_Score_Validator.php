<?php

namespace Racketmanager\Services\Validator;

use Racketmanager\Util\Util;

/**
 * Extracted validation logic for match scores, previously in Validator_Fixture.
 */
class Fixture_Score_Validator
{
    private bool $error = false;
    private array $err_flds = [];
    private array $err_msgs = [];
    private array $sets = [];
    private array $stats = [];
    private array $points = [];
    private float $home_points = 0;
    private float $away_points = 0;
    private bool $completed_set = false;

    /**
     * Validate match score
     *
     * @param object $match match object (needs league property).
     * @param array|null $sets sets.
     * @param string|null $match_status match status.
     * @param string $set_prefix_start
     * @param int|null $rubber_number
     *
     * @return self
     */
    public function validate(object $match, ?array $sets, ?string $match_status, string $set_prefix_start, ?int $rubber_number = null): self
    {
        $this->error = false;
        $this->err_flds = [];
        $this->err_msgs = [];

        $num_sets_to_win = intval($match->league->num_sets_to_win);
        $num_games_to_win = 1;
        $point_rule = $match->league->get_point_rule();
        $points_format = null;
        if (1 === $num_sets_to_win && !empty($point_rule['match_result']) && 'games' === $point_rule['match_result']) {
            $points_format = 'games';
        }
        $home_score = 0;
        $away_score = 0;
        $scoring = $match->league->scoring ?? 'TB';
        $sets_updated = array();
        $s = 1;
        $stats = array();
        $stats['sets']['home'] = 0;
        $stats['sets']['away'] = 0;
        $stats['games']['home'] = 0;
        $stats['games']['away'] = 0;

        $points['home']['sets'] = 0;
        $points['away']['sets'] = 0;
        $points['shared']['sets'] = 0;
        $points['split']['sets'] = 0;
        if (!empty($sets)) {
            $num_sets = count($sets);
            $set_retired = null;
            if ('retired_player1' === $match_status || 'retired_player2' === $match_status || 'abandoned' === $match_status) {
                for ($s1 = $num_sets; $s1 >= 1; $s1--) {
                    if ((isset($sets[$s1]['player1']) && '' !== $sets[$s1]['player1']) || (isset($sets[$s1]['player2']) && '' !== $sets[$s1]['player2'])) {
                        $set_retired = $s1;
                        break;
                    }
                }
            }
            foreach ($sets as $set) {
                $set_prefix = $set_prefix_start . $s . '_';
                $set_type = Util::get_set_type($scoring, $match->final_round, $match->league->num_sets, $s, $rubber_number, $match->num_rubbers, $match->leg);
                $set_info = Util::get_set_info($set_type);
                if (1 === $s) {
                    $num_games_to_win = $set_info->min_win;
                }
                if (($s > $num_sets_to_win) && ($home_score === $num_sets_to_win || $away_score === $num_sets_to_win)) {
                    $set_info->set_type = 'null';
                }
                $set_status = null;
                switch ($match_status) {
                    case 'retired_player1':
                    case 'retired_player2':
                    case 'abandoned':
                        if ($set_retired === $s) {
                            $set_status = $match_status;
                        } elseif ($s > $set_retired) {
                            $set_info->set_type = 'null';
                        }
                        break;
                    case 'cancelled':
                    default:
                        $set_status = $match_status;
                        break;
                }
                $set = $this->validate_set($set, $set_prefix, $set_info, $set_status);
                $set_player_1 = is_null($set['player1']) ? null : strtoupper($set['player1']);
                $set_player_2 = is_null($set['player2']) ? null : strtoupper($set['player2']);
                $set_completed = $set['completed'];
                if (null !== $set_player_1 && null !== $set_player_2) {
                    if (($set_player_1 > $set_player_2 && (empty($set_status) || ('abandoned' === $set_status && $set_completed))) || ('retired_player2') === $set_status || ('invalid_player2') === $set_status || ('invalid_players') === $set_status) {
                        if (empty($points_format)) {
                            ++$points['home']['sets'];
                            ++$stats['sets']['home'];
                            ++$home_score;
                            if ('MTB' === $set['settype']) {
                                ++$stats['games']['home'];
                            }
                        } else {
                            $home_score = $set_player_1;
                            $away_score = $set_player_2;
                        }
                    } elseif (($set_player_1 < $set_player_2 && (empty($set_status) || ('abandoned' === $set_status && $set_completed))) || ('retired_player1') === $set_status || ('invalid_player1') === $set_status) {
                        if (empty($points_format)) {
                            ++$points['away']['sets'];
                            ++$stats['sets']['away'];
                            ++$away_score;
                            if ('MTB' === $set['settype']) {
                                ++$stats['games']['away'];
                            }
                        } else {
                            $home_score = $set_player_1;
                            $away_score = $set_player_2;
                        }
                    } elseif ('S' === $set_player_1) {
                        ++$points['shared']['sets'];
                        $stats['sets']['home'] += 0.5;
                        $stats['sets']['away'] += 0.5;
                        $home_score += 0.5;
                        $away_score += 0.5;
                    }
                }
                if (is_numeric($set_player_1) && 'MTB' !== $set['settype']) {
                    $stats['games']['home'] += $set_player_1;
                }
                if (is_numeric($set_player_2) && 'MTB' !== $set['settype']) {
                    $stats['games']['away'] += $set_player_2;
                }
                $sets_updated[$s] = $set;
                ++$s;
            }
            if (!empty($home_score) && !empty($away_score)) {
                ++$points['split']['sets'];
            }
        }
        if ('league' === $match->league->event->competition->type) {
            $point_rule = $match->league->get_point_rule();
            $walkover_rubber_penalty = empty($point_rule['forwalkover_rubber']) ? 0 : $point_rule['forwalkover_rubber'];
        } else {
            $walkover_rubber_penalty = 0;
        }
        if ('walkover_player1' === $match_status) {
            $stats['sets']['home'] += $num_sets_to_win;
            $points['home']['sets'] += $num_sets_to_win;
            $points['away']['walkover'] = true;
            $home_score += $num_sets_to_win;
            $away_score -= $walkover_rubber_penalty;
            $stats['games']['home'] += $num_games_to_win * $num_sets_to_win;
        } elseif ('walkover_player2' === $match_status) {
            $stats['sets']['away'] += $num_sets_to_win;
            $points['away']['sets'] += $num_sets_to_win;
            $points['home']['walkover'] = true;
            $away_score += $num_sets_to_win;
            $home_score -= $walkover_rubber_penalty;
            $stats['games']['away'] += $num_games_to_win * $num_sets_to_win;
        } elseif ('retired_player1' === $match_status) {
            $points['home']['retired'] = true;
            $points['away']['sets'] = $num_sets_to_win;
            $stats['sets']['away'] = $num_sets_to_win;
            $away_score = $num_sets_to_win;
        } elseif ('retired_player2' === $match_status) {
            $points['away']['retired'] = true;
            $points['home']['sets'] = $num_sets_to_win;
            $stats['sets']['home'] = $num_sets_to_win;
            $home_score = $num_sets_to_win;
        } elseif ('invalid_player2' === $match_status) {
            $stats['sets']['home'] = $num_sets_to_win;
            $points['home']['sets'] = $num_sets_to_win;
            $points['away']['invalid'] = true;
            $home_score = $num_sets_to_win;
            $away_score -= $walkover_rubber_penalty;
            $stats['games']['home'] = $num_games_to_win * $num_sets_to_win;
            $stats['games']['away'] = 0;
        } elseif ('invalid_player1' === $match_status) {
            $stats['sets']['away'] = $num_sets_to_win;
            $points['away']['sets'] = $num_sets_to_win;
            $points['home']['invalid'] = true;
            $away_score = $num_sets_to_win;
            $home_score -= $walkover_rubber_penalty;
            $stats['games']['away'] = $num_games_to_win * $num_sets_to_win;
            $stats['games']['home'] = 0;
        } elseif ('invalid_players' === $match_status) {
            $stats['sets']['home'] = 0;
            $points['home']['sets'] = 0;
            $stats['sets']['away'] = 0;
            $points['away']['sets'] = 0;
            $points['both']['invalid'] = true;
            $away_score = $walkover_rubber_penalty;
            $home_score = $walkover_rubber_penalty;
            $stats['games']['away'] = 0;
            $stats['games']['home'] = 0;
        } elseif ('share' === $match_status) {
            $shared_sets = $match->league->num_sets / 2;
            $points['shared']['sets'] = $match->league->num_sets;
            $home_score += $shared_sets;
            $away_score += $shared_sets;
        } elseif ('withdrawn' === $match_status) {
            $points['withdrawn'] = 1;
        } elseif ('cancelled' === $match_status) {
            $points['cancelled'] = 1;
        } elseif ('abandoned' === $match_status) {
            if ($home_score !== $num_sets_to_win && $away_score !== $num_sets_to_win) {
                $shared_sets = $match->league->num_sets - $home_score - $away_score;
                $points['shared']['sets'] = $shared_sets;
                $home_score += $shared_sets;
                $away_score += $shared_sets;
            }
        }
        $this->home_points = $home_score;
        $this->away_points = $away_score;
        $this->sets = $sets_updated;
        $this->stats = $stats;
        $this->points = $points;
        return $this;
    }

    /**
     * Validate set
     */
    public function validate_set(array $set, string $set_prefix, object $set_info, ?string $match_status): array
    {
        $set = $this->adjust_scores_by_status($set, $set_info->set_type, $match_status);

        $completed_set = false;
        if (!is_null($set['player1']) || !is_null($set['player2'])) {
            if ('null' === $set_info->set_type) {
                $this->validate_null_set_type($set, $set_prefix);
            } elseif ('share' === $match_status || 'withdrawn' === $match_status) {
                $set['player1'] = '';
                $set['player2'] = '';
                $set['tiebreak'] = '';
            } elseif ('S' === $set['player1'] || 'S' === $set['player2']) {
                $this->validate_shared_scores($set, $set_prefix);
            } elseif (empty($set['player1']) && empty($set['player2'])) {
                $this->validate_empty_scores($set_prefix, $match_status);
            } elseif ($set['player1'] === $set['player2']) {
                $this->validate_identical_scores($set_prefix, $match_status);
            } elseif ($set['player1'] > $set['player2']) {
                $this->validate_set_score($set, $set_prefix, 'player1', 'player2', $set_info, $match_status);
                $completed_set = $this->completed_set;
            } elseif ($set['player1'] < $set['player2']) {
                $this->validate_set_score($set, $set_prefix, 'player2', 'player1', $set_info, $match_status);
                $completed_set = $this->completed_set;
            } elseif ('' === $set['player1'] || '' === $set['player2']) {
                $this->validate_partially_empty_scores($set, $set_prefix, $match_status);
            }
        }

        $set['completed'] = $completed_set;
        $set['settype'] = $set_info->set_type;
        return $set;
    }

    /**
     * Adjust scores based on match status
     */
    private function adjust_scores_by_status(array $set, string $set_type, ?string $match_status): array
    {
        $retired = !empty($match_status) && str_starts_with($match_status, 'retired');
        $walkover = !empty($match_status) && str_starts_with($match_status, 'walkover');
        $cancelled = !empty($match_status) && str_starts_with($match_status, 'cancelled');
        $abandoned = !empty($match_status) && str_starts_with($match_status, 'abandoned');

        if ($walkover) {
            if ('null' === $set_type) {
                $set['player1'] = '';
                $set['player2'] = '';
            } else {
                $set['player1'] = null;
                $set['player2'] = null;
            }
            $set['tiebreak'] = '';
        } elseif ($retired || $abandoned) {
            if ('null' === $set_type) {
                $set['player1'] = '';
                $set['player2'] = '';
                $set['tiebreak'] = '';
            }
        } elseif ($cancelled) {
            $set['player1'] = null;
            $set['player2'] = null;
        }

        return $set;
    }

    /**
     * Validate scores for 'null' set type
     */
    private function validate_null_set_type(array $set, string $set_prefix): void
    {
        if ('' !== $set['player1']) {
            $this->error = true;
            $this->err_flds[] = $set_prefix . 'player1';
            $this->err_msgs[] = __('Set score should be empty', 'racketmanager');
        }
        if ('' !== $set['player2']) {
            $this->error = true;
            $this->err_flds[] = $set_prefix . 'player2';
            $this->err_msgs[] = __('Set score should be empty', 'racketmanager');
        }
        if ('' !== $set['tiebreak']) {
            $this->error = true;
            $this->err_flds[] = $set_prefix . 'tiebreak';
            $this->err_msgs[] = __('Tie break should be empty', 'racketmanager');
        }
    }

    /**
     * Validate shared scores ('S')
     */
    private function validate_shared_scores(array $set, string $set_prefix): void
    {
        if ('S' !== $set['player1']) {
            $this->error = true;
            $this->err_flds[] = $set_prefix . 'player1';
            $this->err_msgs[] = __('Both scores must be shared', 'racketmanager');
        }
        if ('S' !== $set['player2']) {
            $this->error = true;
            $this->err_flds[] = $set_prefix . 'player2';
            $this->err_msgs[] = __('Both scores must be shared', 'racketmanager');
        }
    }

    /**
     * Validate when both scores are empty
     */
    private function validate_empty_scores(string $set_prefix, ?string $match_status): void
    {
        $retired = !empty($match_status) && str_starts_with($match_status, 'retired');
        $walkover = !empty($match_status) && str_starts_with($match_status, 'walkover');

        if (!$retired && !$walkover && 'abandoned' !== $match_status) {
            $this->error = true;
            $this->err_flds[] = $set_prefix . 'player1';
            $this->err_flds[] = $set_prefix . 'player2';
            $this->err_msgs[] = __('Set scores must be entered', 'racketmanager');
        }
    }

    /**
     * Validate when scores are identical
     */
    private function validate_identical_scores(string $set_prefix, ?string $match_status): void
    {
        $retired = !empty($match_status) && str_starts_with($match_status, 'retired');
        $walkover = !empty($match_status) && str_starts_with($match_status, 'walkover');
        $abandoned = !empty($match_status) && str_starts_with($match_status, 'abandoned');

        if (!$retired && !$walkover && !$abandoned) {
            $this->error = true;
            $this->err_flds[] = $set_prefix . 'player1';
            $this->err_flds[] = $set_prefix . 'player2';
            $this->err_msgs[] = __('Set scores must be different', 'racketmanager');
        }
    }

    /**
     * Validate when one of the scores is empty
     */
    private function validate_partially_empty_scores(array $set, string $set_prefix, ?string $match_status): void
    {
        $retired = !empty($match_status) && str_starts_with($match_status, 'retired');
        $walkover = !empty($match_status) && str_starts_with($match_status, 'walkover');

        if (!$retired && !$walkover) {
            $this->error = true;
            if ('' === $set['player1']) {
                $this->err_flds[] = $set_prefix . 'player1';
            }
            if ('' === $set['player2']) {
                $this->err_flds[] = $set_prefix . 'player2';
            }
            $this->err_msgs[] = __('Set score not entered', 'racketmanager');
        }
    }

    private function validate_set_score(array $set, string $set_prefix, string $team_1, string $team_2, object $set_info, ?string $match_status = null): void
    {
        $tiebreak_allowed = $set_info->tiebreak_allowed;
        $tiebreak_required = $set_info->tiebreak_required;
        $tiebreak_set = $set_info->tiebreak_set;
        $max_win = $set_info->max_win;
        $min_win = $set_info->min_win;
        $max_loss = $set_info->max_loss;
        $min_loss = $set_info->min_loss;
        $retired = !empty($match_status) && substr($match_status, 0, 8) === 'retired';

        $this->completed_set = true;

        if ($set[$team_1] < $min_win && $retired) {
            if ('abandoned' === $match_status) {
                $this->completed_set = false;
            } else {
                $this->error = true;
                $this->err_msgs[] = __('Winning set score too low', 'racketmanager');
                $this->err_flds[] = $set_prefix . $team_1;
            }
        } elseif (!$this->check_score_too_high($set, $set_prefix, $team_1, $max_win)) {
            if ($retired && intval($set[$team_1]) === intval($min_win) && $max_win !== $min_win && $set[$team_2] > $min_loss) {
                $this->error = true;
                $this->err_msgs[] = __('Games difference must be at least 2', 'racketmanager');
                $this->err_flds[] = $set_prefix . $team_1;
                $this->err_flds[] = $set_prefix . $team_2;
            } else {
                $this->check_game_difference($set, $set_prefix, $team_1, $team_2, $min_win, $min_loss, $max_win, $max_loss, $match_status);
                $this->validate_tiebreak_score($set, $set_prefix, $tiebreak_allowed, $tiebreak_required, $tiebreak_set);
            }
        }
    }

    private function check_score_too_high(array $set, string $set_prefix, string $team_1, int $max_win): bool
    {
        if ($set[$team_1] > $max_win) {
            $this->error = true;
            $this->err_msgs[] = __('Winning set score too high', 'racketmanager');
            $this->err_flds[] = $set_prefix . $team_1;
            return true;
        }
        return false;
    }

    private function check_game_difference(array $set, string $set_prefix, string $team_1, string $team_2, int $min_win, int $min_loss, int $max_win, int $max_loss, ?string $match_status): bool
    {
        $has_error = false;
        $game_diff_msg = __('Games difference incorrect', 'racketmanager');

        if (intval($set[$team_1]) === $max_win) {
            if ($set[$team_2] < $max_loss && $max_win !== $min_win) {
                $this->error = true;
                $this->err_msgs[] = $game_diff_msg;
                $this->err_flds[] = $set_prefix . $team_1;
                $this->err_flds[] = $set_prefix . $team_2;
                $has_error = true;
            }
        } elseif (intval($set[$team_1]) === $min_win && intval($set[$team_2]) > $max_loss) {
            $this->error = true;
            $this->err_msgs[] = $game_diff_msg;
            $this->err_flds[] = $set_prefix . $team_1;
            $this->err_flds[] = $set_prefix . $team_2;
            $has_error = true;
        } elseif (intval($set[$team_1]) > $min_win) {
            if (intval($set[$team_2]) < $min_loss) {
                $this->error = true;
                $this->err_msgs[] = $game_diff_msg;
                $this->err_flds[] = $set_prefix . $team_1;
                $this->err_flds[] = $set_prefix . $team_2;
                $has_error = true;
            } elseif ((intval($set[$team_1]) - 2) !== intval($set[$team_2]) && !str_starts_with($match_status ?? '', 'retired_player')) {
                $this->error = true;
                $this->err_msgs[] = $game_diff_msg;
                $this->err_flds[] = $set_prefix . $team_2;
                $has_error = true;
            }
        }

        return $has_error;
    }

    private function validate_tiebreak_score(array $set, string $set_prefix, bool $tiebreak_allowed, bool $tiebreak_required, ?int $tiebreak_set): void
    {
        if (null === $tiebreak_set) {
            return;
        }
        $tie_break_score_required = __('Tie break score required', 'racketmanager');
        $tie_break_whole_number = __('Tie break score must be whole number', 'racketmanager');

        $is_tiebreak_score_needed = (intval($set['player1']) > $tiebreak_set && intval($set['player2']) === $tiebreak_set) ||
                                   (intval($set['player1']) === $tiebreak_set && intval($set['player2']) > $tiebreak_set);

        if ($set['tiebreak'] > '') {
            if (!$tiebreak_allowed && !$tiebreak_required) {
                $this->error = true;
                $this->err_msgs[] = __('Tie break score should be empty', 'racketmanager');
                $this->err_flds[] = $set_prefix . 'tiebreak';
            } elseif (!is_numeric($set['tiebreak']) || strval(round($set['tiebreak'])) !== $set['tiebreak']) {
                $this->error = true;
                $this->err_msgs[] = $tie_break_whole_number;
                $this->err_flds[] = $set_prefix . 'tiebreak';
            }
        } elseif ($tiebreak_required || ($is_tiebreak_score_needed && $tiebreak_allowed)) {
            $this->error = true;
            $this->err_msgs[] = $tie_break_score_required;
            $this->err_flds[] = $set_prefix . 'tiebreak';
        }
    }

    public function get_error(): bool { return $this->error; }
    public function get_err_flds(): array { return $this->err_flds; }
    public function get_err_msgs(): array { return $this->err_msgs; }
    public function get_sets(): array { return $this->sets; }
    public function get_stats(): array { return $this->stats; }
    public function get_points(): array { return $this->points; }
    public function get_home_points(): float { return $this->home_points; }
    public function get_away_points(): float { return $this->away_points; }
}
