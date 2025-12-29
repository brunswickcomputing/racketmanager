<?php
/**
 * Config Validation API: Config validator class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Validate
 */

namespace Racketmanager\Services\Validator;

/**
 * Class to implement the Config Validator object
 */
class Validator_Config extends Validator {
    /**
     * Validate name
     *
     * @param string|null $name name.
     *
     * @return object $validation updated validation object.
     */
    public function name( ?string $name ): object {
        if ( ! $name ) {
            $error_field   = 'name';
            $error_message = __( 'Name must be specified', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
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
            $error_field   = 'sport';
            $error_message = __( 'Sport must be specified', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
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
            $error_field   = 'entry_type';
            $error_message = __( 'Entry type must be specified', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
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
            $error_field   = 'grade';
            $error_message = __( 'Grade must be specified', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
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
            $error_field   = 'max_teams';
            $error_message = __( 'Maximum teams must be specified', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
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
            $error_field   = 'teams_per_club';
            $error_message = __( 'Number of teams per club must be set', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
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
            $error_field   = 'teams_prom_relg';
            $error_message = __( 'Number of promoted/relegated teams must be set', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
        } elseif ( ! empty( $teams_per_club ) && $teams_prom_relg > $teams_per_club ) {
            $error_field   = 'teams_prom_relg';
            $error_message = __( 'Number of promoted/relegated teams must be at most number of teams per club', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
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
            $error_field   = 'lowest_promotion';
            $error_message = __( 'Lowest promotion position must be set', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
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
            $error_field   = 'num_entries';
            $error_message = __( 'Maximum number of entries must be set', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
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
            $error_field   = 'team_ranking';
            $error_message = __( 'Ranking type must be set', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
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
            $error_field   = 'point_rule';
            $error_message = __( 'Point rule must be set', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
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
            $error_field   = 'scoring';
            $error_message = __( 'Scoring method must be set', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
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
            $error_field   = 'num_sets';
            $error_message = __( 'Number of sets must be set', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
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
            $error_field   = 'num_rubbers';
            $error_message = __( 'Number of rubbers must be set', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
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
            $error_field   = 'fixed_match_dates';
            $error_message = __( 'Match date option must be set', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
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
            $error_field   = 'home_away';
            $error_message = __( 'Fixture types must be set', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
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
            $error_field   = 'round_length';
            $error_message = __( 'Round length must be set', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
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
            $error_field   = 'home_away_diff';
            $error_message = __( 'Difference between fixtures must be set', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
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
            $error_field   = 'filler_weeks';
            $error_message = __( 'Number of filler weeks must be set', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
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
            $error_field   = 'point_format';
            $error_message = __( 'Point format must be set', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
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
            $error_field   = 'point_2_format';
            $error_message = __( 'Secondary point format must be set', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
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
            $error_field   = 'num_matches_per_page';
            $error_message = __( 'Number of matches per page must be set', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
        }

        return $this;
    }

    /**
     * Validate default match start time
     *
     * @param array|null $default_match_start_time default match start time.
     *
     * @return object $validation updated validation object.
     */
    public function default_match_start_time( ?array $default_match_start_time ): object {
        if ( ! $default_match_start_time ) {
            $error_field   = 'default_match_start_time';
            $error_message = __( 'Default match start time must be set', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
        } elseif ( ! isset( $default_match_start_time['hour'] ) ) {
            $error_field   = 'default_match_start_time';
            $error_message = __( 'Default match start time hour not set', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
        } elseif ( ! isset( $default_match_start_time['minutes'] ) ) {
            $error_field   = 'default_match_start_time';
            $error_message = __( 'Default match start time minutes not set', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
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
            $error_field   = 'match_day_restriction';
            $error_message = __( 'Fixture types must be set', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
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
                $error_field   = 'min_start_time_weekday';
                $error_message = __( 'Minimum weekday start time must be set', 'racketmanager' );
                $this->set_errors( $error_field, $error_message );
            }
            if ( empty( $start_time['weekday']['max'] ) ) {
                $error_field   = 'max_start_time_weekday';
                $error_message = __( 'Maximum weekday start time must be set', 'racketmanager' );
                $this->set_errors( $error_field, $error_message );
            } elseif ( ! empty( $start_time['weekday']['min'] ) ) {
                if ( $start_time['weekday']['max'] < $start_time['weekday']['min'] ) {
                    $error_field   = 'max_start_time_weekday';
                    $error_message = __( 'Maximum weekday start time must be greater than minimum', 'racketmanager' );
                    $this->set_errors( $error_field, $error_message );
                }
            }
        }
        if ( $validate_weekend_times ) {
            if ( empty( $start_time['weekend']['min'] ) ) {
                $error_field   = 'min_start_time_weekend';
                $error_message = __( 'Minimum weekend start time must be set', 'racketmanager' );
                $this->set_errors( $error_field, $error_message );
            }
            if ( empty( $start_time['weekend']['max'] ) ) {
                $error_field   = 'max_start_time_weekend';
                $error_message = __( 'Maximum weekend start time must be set', 'racketmanager' );
                $this->set_errors( $error_field, $error_message );
            } elseif ( ! empty( $start_time['weekend']['min'] ) ) {
                if ( $start_time['weekend']['max'] < $start_time['weekend']['min'] ) {
                    $error_field   = 'max_start_time_weekend';
                    $error_message = __( 'Maximum weekend start time must be greater than minimum', 'racketmanager' );
                    $this->set_errors( $error_field, $error_message );
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
            $error_field   = 'num_match_days';
            $error_message = __( 'Number of match days must be set', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
        }

        return $this;
    }

    /**
     * Validate date
     *
     * @param string|null $date date.
     * @param string|null $type type of date.
     * @param string|null $prev_date prev date.
     * @param string|null $prev_type type of prev date.
     *
     * @return object $validation updated validation object.
     */
    public function date( ?string $date, ?string $type = null, ?string $prev_date = null, ?string $prev_type = null ): object {
        if ( $type ) {
            $field_suffix = '_' . $type;
        } else {
            $field_suffix = null;
        }
        if ( empty( $date ) ) {
            $error_field = 'date' . $field_suffix;
            if ( $type ) {
                $error_message = ucfirst( $type ) . ' ' . __( 'date must be set', 'racketmanager' );
            } else {
                $error_message = __( 'Date must be set', 'racketmanager' );
            }
            $this->set_errors( $error_field, $error_message );
        } elseif ( ! empty( $prev_date ) && $date <= $prev_date ) {
            $error_message = sprintf( __( '%s date must be after %s date', 'racketmanager' ), ucfirst( $type ), $prev_type );
            $error_field   = 'date' . $field_suffix;
            $this->set_errors( $error_field, $error_message );
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
            $error_field   = 'feeLeadTime';
            $error_message = __( 'Fee lead time must be set', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
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
            $error_field   = 'age_limit';
            $error_message = __( 'Age limit must be specified', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
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
            $error_field   = 'age_offset';
            $error_message = __( 'Age offset must be specified', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
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
            $error_field   = 'offset';
            $error_message = __( 'Offset must be specified', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
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
            $error_field   = 'primary_league';
            $error_message = __( 'Primary league must be specified', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
        }

        return $this;
    }
}
