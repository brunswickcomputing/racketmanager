<?php
/**
 * Config Validation API: Config validator class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Validate
 */

namespace Racketmanager;

/**
 * Class to implement the Config Validator object
 */
final class Validator_Config extends Validator {
    /**
     * Validate name
     *
     * @param string|null $name name.
     *
     * @return object $validation updated validation object.
     */
    public function name( ?string $name ): object {
        if ( ! $name ) {
            $this->error      = true;
            $this->err_flds[] = 'name';
            $this->err_msgs[] = __( 'Name must be specified', 'racketmanager' );
        }

        return $this;
    }
    /**
     * Validate type
     *
     * @param string|null $type type.
     *
     * @return object $validation updated validation object.
     */
    public function type( ?string $type ): object {
        if ( ! $type ) {
            $this->error      = true;
            $this->err_flds[] = 'type';
            $this->err_msgs[] = __( 'Type must be specified', 'racketmanager' );
        }
        return $this;
    }
    /**
     * Validate sport
     *
     * @param string|null $sport sport.
     *
     * @return object $validation updated validation object.
     */
    public function sport( ?string $sport ): object {
        if ( ! $sport ) {
            $this->error      = true;
            $this->err_flds[] = 'sport';
            $this->err_msgs[] = __( 'Sport must be specified', 'racketmanager' );
        }

        return $this;
    }

    /**
     * Validate entry type
     *
     * @param string|null $entry_type entry type.
     *
     * @return object $validation updated validation object.
     */
    public function entry_type( ?string $entry_type ): object {
        if ( ! $entry_type ) {
            $this->error      = true;
            $this->err_flds[] = 'entry_type';
            $this->err_msgs[] = __( 'Entry type must be specified', 'racketmanager' );
        }

        return $this;
    }

    /**
     * Validate grade
     *
     * @param string|null $grade grade.
     *
     * @return object $validation updated validation object.
     */
    public function grade( ?string $grade ): object {
        if ( ! $grade ) {
            $this->error      = true;
            $this->err_flds[] = 'grade';
            $this->err_msgs[] = __( 'Grade must be specified', 'racketmanager' );
        }

        return $this;
    }

    /**
     * Validate maximum number of teams in a league
     *
     * @param int|null $max_teams maximum teams.
     *
     * @return object $validation updated validation object.
     */
    public function max_teams( ?int $max_teams ): object {
        if ( ! $max_teams ) {
            $this->error      = true;
            $this->err_flds[] = 'max_teams';
            $this->err_msgs[] = __( 'Maximum teams must be specified', 'racketmanager' );
        }

        return $this;
    }

    /**
     * Validate maximum number of teams per club
     *
     * @param int|null $teams_per_club maximum teams per club in a league.
     *
     * @return object $validation updated validation object.
     */
    public function teams_per_club( ?int $teams_per_club ): object {
        if ( ! $teams_per_club ) {
            $this->error      = true;
            $this->err_flds[] = 'teams_per_club';
            $this->err_msgs[] = __( 'Number of teams per club must be set', 'racketmanager' );
        }

        return $this;
    }

    /**
     * Validate number of teams promoted and relegated
     *
     * @param int|null $teams_prom_relg number of teams promoted and relegated.
     * @param int|null $teams_per_club maximum teams per club in a league.
     *
     * @return object $validation updated validation object.
     */
    public function teams_prom_relg( ?int $teams_prom_relg, ?int $teams_per_club ): object {
        if ( ! $teams_prom_relg ) {
            $this->error      = true;
            $this->err_flds[] = 'teams_prom_relg';
            $this->err_msgs[] = __( 'Number of promoted/relegated teams must be set', 'racketmanager' );
        } elseif ( ! empty( $teams_per_club ) && $teams_prom_relg > $teams_per_club ) {
            $this->error      = true;
            $this->err_flds[] = 'teams_prom_relg';
            $this->err_msgs[] = __( 'Number of promoted/relegated teams must be at most number of teams per club', 'racketmanager' );
        }

        return $this;
    }

    /**
     * Validate lowest promotion position
     *
     * @param int|null $lowest_promotion lowest promotion position.
     *
     * @return object $validation updated validation object.
     */
    public function lowest_promotion( ?int $lowest_promotion ): object {
        if ( ! $lowest_promotion ) {
            $this->error      = true;
            $this->err_flds[] = 'lowest_promotion';
            $this->err_msgs[] = __( 'Lowest promotion position must be set', 'racketmanager' );
        }

        return $this;
    }

    /**
     * Validate number of entries
     *
     * @param int|null $num_entries maximum number of entries.
     *
     * @return object $validation updated validation object.
     */
    public function num_entries( ?int $num_entries ): object {
        if ( ! $num_entries ) {
            $this->error      = true;
            $this->err_flds[] = 'num_entries';
            $this->err_msgs[] = __( 'Maximum number of entries must be set', 'racketmanager' );
        }

        return $this;
    }

    /**
     * Validate team ranking
     *
     * @param string|null $team_ranking team ranking.
     *
     * @return object $validation updated validation object.
     */
    public function team_ranking( ?string $team_ranking ): object {
        if ( ! $team_ranking ) {
            $this->error      = true;
            $this->err_flds[] = 'team_ranking';
            $this->err_msgs[] = __( 'Ranking type must be set', 'racketmanager' );
        }

        return $this;
    }

    /**
     * Validate point rule
     *
     * @param string|null $point_rule point rule.
     *
     * @return object $validation updated validation object.
     */
    public function point_rule( ?string $point_rule ): object {
        if ( ! $point_rule ) {
            $this->error      = true;
            $this->err_flds[] = 'point_rule';
            $this->err_msgs[] = __( 'Point rule must be set', 'racketmanager' );
        }

        return $this;
    }

    /**
     * Validate scoring method
     *
     * @param string|null $scoring scoring method.
     *
     * @return object $validation updated validation object.
     */
    public function scoring( ?string $scoring ): object {
        if ( ! $scoring ) {
            $this->error      = true;
            $this->err_flds[] = 'scoring';
            $this->err_msgs[] = __( 'Scoring method must be set', 'racketmanager' );
        }

        return $this;
    }

    /**
     * Validate number of sets
     *
     * @param int|null $num_sets number of sets.
     *
     * @return object $validation updated validation object.
     */
    public function num_sets( ?int $num_sets ): object {
        if ( ! $num_sets ) {
            $this->error      = true;
            $this->err_flds[] = 'num_sets';
            $this->err_msgs[] = __( 'Number of sets must be set', 'racketmanager' );
        }

        return $this;
    }

    /**
     * Validate number of rubbers
     *
     * @param int|null $num_rubbers number of rubbers.
     *
     * @return object $validation updated validation object.
     */
    public function num_rubbers( ?int $num_rubbers ): object {
        if ( ! $num_rubbers ) {
            $this->error      = true;
            $this->err_flds[] = 'num_rubbers';
            $this->err_msgs[] = __( 'Number of rubbers must be set', 'racketmanager' );
        }

        return $this;
    }

    /**
     * Validate fixed match dates
     *
     * @param bool|null $match_date_option fixed match dates.
     *
     * @return object $validation updated validation object.
     */
    public function match_date_option( ?bool $match_date_option ): object {
        if ( is_null( $match_date_option ) ) {
            $this->error      = true;
            $this->err_flds[] = 'fixed_match_dates';
            $this->err_msgs[] = __( 'Match date option must be set', 'racketmanager' );
        }

        return $this;
    }

    /**
     * Validate fixture type
     *
     * @param bool|null $fixture_type fixture type.
     *
     * @return object $validation updated validation object.
     */
    public function fixture_type( ?bool $fixture_type ): object {
        if ( is_null( $fixture_type ) ) {
            $this->error      = true;
            $this->err_flds[] = 'home_away';
            $this->err_msgs[] = __( 'Fixture types must be set', 'racketmanager' );
        }

        return $this;
    }
    /**
     * Validate round length
     *
     * @param int|null $round_length round length.
     *
     * @return object $validation updated validation object.
     */
    public function round_length( ?int $round_length ): object {
        if ( ! $round_length ) {
            $this->error      = true;
            $this->err_flds[] = 'round_length';
            $this->err_msgs[] = __( 'Round length must be set', 'racketmanager' );
        }
        return $this;
    }
    /**
     * Validate fixture gap
     *
     * @param int|null $fixture_gap fixture gap.
     *
     * @return object $validation updated validation object.
     */
    public function fixture_gap( ?int $fixture_gap ): object {
        if ( is_null( $fixture_gap ) ) {
            $this->error      = true;
            $this->err_flds[] = 'home_away_diff';
            $this->err_msgs[] = __( 'Difference between fixtures must be set', 'racketmanager' );
        }
        return $this;
    }
    /**
     * Validate filler weeks
     *
     * @param int|null $filler_weeks filler weeks.
     *
     * @return object $validation updated validation object.
     */
    public function filler_weeks( ?int $filler_weeks ): object {
        if ( is_null( $filler_weeks ) ) {
            $this->error      = true;
            $this->err_flds[] = 'filler_weeks';
            $this->err_msgs[] = __( 'Number of filler weeks must be set', 'racketmanager' );
        }
        return $this;
    }
    /**
     * Validate point format
     *
     * @param string|null $point_format point format.
     *
     * @return object $validation updated validation object.
     */
    public function point_format( ?string $point_format ): object {
        if ( ! $point_format ) {
            $this->error      = true;
            $this->err_flds[] = 'point_format';
            $this->err_msgs[] = __( 'Point format must be set', 'racketmanager' );
        }

        return $this;
    }
    /**
     * Validate point 2 format
     *
     * @param string|null $point_format point 2 format.
     *
     * @return object $validation updated validation object.
     */
    public function point_2_format( ?string $point_format ): object {
        if ( ! $point_format ) {
            $this->error      = true;
            $this->err_flds[] = 'point_2_format';
            $this->err_msgs[] = __( 'Secondary point format must be set', 'racketmanager' );
        }

        return $this;
    }
    /**
     * Validate number of matches per page
     *
     * @param int|null $num_matches_per_page number of matches per page.
     *
     * @return object $validation updated validation object.
     */
    public function num_matches_per_page( ?int $num_matches_per_page ): object {
        if ( ! $num_matches_per_page ) {
            $this->error      = true;
            $this->err_flds[] = 'num_matches_per_page';
            $this->err_msgs[] = __( 'Number of matches per page must be set', 'racketmanager' );
        }

        return $this;
    }
    /**
     * Validate default match start time
     *
     * @param string|null $default_match_start_time default match start time.
     *
     * @return object $validation updated validation object.
     */
    public function default_match_start_time( ?string $default_match_start_time ): object {
        if ( ! $default_match_start_time ) {
            $this->error      = true;
            $this->err_flds[] = 'default_match_start_time';
            $this->err_msgs[] = __( 'Default match start time must be set', 'racketmanager' );
        }

        return $this;
    }
    /**
     * Validate match day restriction
     *
     * @param bool|null $match_day_restriction match day restriction.
     * @param array|null $match_days_allowed match day restriction.
     * @param array|null $start_time start times.
     *
     * @return object $validation updated validation object.
     */
    public function match_day_restriction( ?bool $match_day_restriction, ?array $match_days_allowed, ?array $start_time ): object {
        if ( ! empty( $match_day_restriction ) && empty( $match_days_allowed ) ) {
            $this->error      = true;
            $this->err_flds[] = 'match_day_restriction';
            $this->err_msgs[] = __( 'Fixture types must be set', 'racketmanager' );
        }
        $validate_weekday_times = false;
        $validate_weekend_times = false;
        if ( empty( $match_day_restriction ) ) {
            $validate_weekday_times = true;
            $validate_weekend_times = true;
        } elseif ( ! empty( $match_days_allowed ) ) {
            foreach ( $match_days_allowed as $day_allowed => $value ) {
                if ( $day_allowed <= 5 ) {
                    $validate_weekday_times = true;
                } else {
                    $validate_weekend_times = true;
                }
            }
        }
        if ( $validate_weekday_times ) {
            if ( empty( $start_time['weekday']['min'] ) ) {
                $this->error      = true;
                $this->err_flds[] = 'min_start_time_weekday';
                $this->err_msgs[] = __( 'Minimum weekday start time must be set', 'racketmanager' );
            }
            if ( empty( $start_time['weekday']['max'] ) ) {
                $this->error      = true;
                $this->err_flds[] = 'max_start_time_weekday';
                $this->err_msgs[] = __( 'Maximum weekday start time must be set', 'racketmanager' );
            } elseif ( ! empty( $start_time['weekday']['min'] ) ) {
                if ( $start_time['weekday']['max'] < $start_time['weekday']['min'] ) {
                    $this->error      = true;
                    $this->err_flds[] = 'max_start_time_weekday';
                    $this->err_msgs[] = __( 'Maximum weekday start time must be greater than minimum', 'racketmanager' );
                }
            }
        }
        if ( $validate_weekend_times ) {
            if ( empty( $start_time['weekend']['min'] ) ) {
                $this->error      = true;
                $this->err_flds[] = 'min_start_time_weekend';
                $this->err_msgs[] = __( 'Minimum weekend start time must be set', 'racketmanager' );
            }
            if ( empty( $start_time['weekend']['max'] ) ) {
                $this->error      = true;
                $this->err_flds[] = 'max_start_time_weekend';
                $this->err_msgs[] = __( 'Maximum weekend start time must be set', 'racketmanager' );
            } elseif ( ! empty( $start_time['weekend']['min'] ) ) {
                if ( $start_time['weekend']['max'] < $start_time['weekend']['min'] ) {
                    $this->error      = true;
                    $this->err_flds[] = 'max_start_time_weekend';
                    $this->err_msgs[] = __( 'Maximum weekend start time must be greater than minimum', 'racketmanager' );
                }
            }
        }
        return $this;
    }
    /**
     * Validate number of match days
     *
     * @param int|null $num_match_days number of match days.
     *
     * @return object $validation updated validation object.
     */
    public function num_match_days( ?int $num_match_days ): object {
        if ( ! $num_match_days ) {
            $this->error      = true;
            $this->err_flds[] = 'num_match_days';
            $this->err_msgs[] = __( 'Number of match days must be set', 'racketmanager' );
        }

        return $this;
    }
    /**
     * Validate date
     *
     * @param string|null $date date.
     * @param string $type type of date.
     * @param string|null $prev_date prev date.
     * @param string|null $prev_type type of prev date.
     *
     * @return object $validation updated validation object.
     */
    public function date( ?string $date, string $type, ?string $prev_date = null, ?string $prev_type = null ): object {
        if ( empty( $date ) ) {
            $this->error      = true;
            $this->err_flds[] = 'date_' . $type;
            $this->err_msgs[] = ucfirst( $type ) . ' ' . __( 'date must be set', 'racketmanager' );
        } elseif ( ! empty( $prev_date ) && $date <= $prev_date ) {
            $this->error      = false;
            $this->err_msgs[] = sprintf( __( '%s date must be after %s date', 'racketmanager' ), ucfirst( $type ), $prev_type );
            $this->err_flds[] = 'date_' . $type;
        }

        return $this;
    }
    /**
     * Validate fees
     *
     * @param string|null $lead_time lead time.
     * @param string|null $competition competition fee.
     * @param string|null $event event fee.
     *
     * @return object $validation updated validation object.
     */
    public function fees( ?string $lead_time, ?string $competition, ?string $event ): object {
        if ( empty( $lead_time ) && ( ! empty( $competition ) || ! empty( $event ) ) ) {
            $this->error      = true;
            $this->err_flds[] = 'feeLeadTime';
            $this->err_msgs[] = __( 'Fee lead time must be set', 'racketmanager' );
        }
        return $this;
    }
    /**
     * Validate age limit
     *
     * @param string|null $age_limit age limit.
     *
     * @return object $validation updated validation object.
     */
    public function age_limit( ?string $age_limit ): object {
        if ( ! $age_limit ) {
            $this->error      = true;
            $this->err_flds[] = 'age_limit';
            $this->err_msgs[] = __( 'Age limit must be specified', 'racketmanager' );
        }
        return $this;
    }
    /**
     * Validate age limit
     *
     * @param string|null $age_offset age offset.
     *
     * @return object $validation updated validation object.
     */
    public function age_offset( ?string $age_offset ): object {
        if ( is_null( $age_offset ) ) {
            $this->error      = true;
            $this->err_flds[] = 'age_offset';
            $this->err_msgs[] = __( 'Age offset must be specified', 'racketmanager' );
        }
        return $this;
    }
    /**
     * Validate offset
     *
     * @param string|null $offset offset.
     *
     * @return object $validation updated validation object.
     */
    public function offset( ?string $offset ): object {
        if ( is_null( $offset ) ) {
            $this->error      = true;
            $this->err_flds[] = 'offset';
            $this->err_msgs[] = __( 'Offset must be specified', 'racketmanager' );
        }
        return $this;
    }
    /**
     * Validate primary league
     *
     * @param int|null $primary_league primary league.
     *
     * @return object $validation updated validation object.
     */
    public function primary_league( ?int $primary_league ): object {
        if ( empty( $primary_league ) ) {
            $this->error      = true;
            $this->err_flds[] = 'primary_league';
            $this->err_msgs[] = __( 'Primary league must be specified', 'racketmanager' );
        }
        return $this;
    }
}
