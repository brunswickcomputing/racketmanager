<?php

namespace Racketmanager\Services\Validator;

use Racketmanager\Domain\Scoring\Scoring_Context;
use Racketmanager\Util\Util;

/**
 * Extracted validation logic for match scores, previously in Validator_Fixture.
 */
class Score_Validation_Service {
    private bool $error = false;
    private array $err_flds = [];
    private array $err_msgs = [];
    private array $sets = [];
    private array $stats = [];
    private array $points = [];
    private float $home_points = 0;
    private float $away_points = 0;

    private Set_Score_Validator $set_validator;
    private Fixture_Result_Validator $match_validator;

    public function __construct() {
        $this->set_validator   = new Set_Score_Validator();
        $this->match_validator = new Fixture_Result_Validator();
    }

    /**
     * Validate match score
     *
     * @param Scoring_Context $context Scoring context DTO.
     * @param array|null $sets sets.
     * @param string|null $match_status match status.
     * @param string $set_prefix_start
     * @param int|null $rubber_number
     *
     * @return self
     */
    public function validate( Scoring_Context $context, ?array $sets, ?string $match_status, string $set_prefix_start, ?int $rubber_number = null ): self {
        $this->reset_validation_state();

        $num_sets_to_win  = $context->num_sets_to_win;
        $num_games_to_win = 1;
        $point_rule       = $context->point_rule;
        $points_format    = ( 1 === $num_sets_to_win && ! empty( $point_rule['match_result'] ) && 'games' === $point_rule['match_result'] ) ? 'games' : null;

        $home_score   = 0;
        $away_score   = 0;
        $scoring      = $context->scoring_type;
        $sets_updated = array();
        $s            = 1;
        $stats        = $this->initialize_stats_array();
        $points       = $this->initialize_points_array();

        if ( ! empty( $sets ) ) {
            $set_retired = $this->find_retired_set_index( $sets, $match_status );

            foreach ( $sets as $set ) {
                $set_prefix = $set_prefix_start . $s . '_';
                $set_type   = Util::get_set_type( $scoring, $context->final_round, $context->num_sets, $s, $rubber_number, $context->num_rubbers, $context->leg );
                $set_info   = Util::get_set_info( $set_type );
                if ( 1 === $s ) {
                    $num_games_to_win = $set_info->min_win;
                }

                $set_status = $this->determine_set_status_and_info( $s, $match_status, $set_retired, $set_info, $num_sets_to_win, $home_score, $away_score );

                $set = $this->set_validator->validate_set( $set, $set_prefix, $set_info, $set_status );

                if ( $this->set_validator->get_error() ) {
                    $this->error    = true;
                    $this->err_flds = array_unique( array_merge( $this->err_flds, $this->set_validator->get_err_flds() ) );
                    $this->err_msgs = array_unique( array_merge( $this->err_msgs, $this->set_validator->get_err_msgs() ) );
                }

                $this->match_validator->update_match_points_and_stats( $set, $set_status, $points_format, $points, $stats, $home_score, $away_score );

                $sets_updated[ $s ] = $set;
                ++ $s;
            }
            if ( $home_score > 0 && $away_score > 0 ) {
                ++ $points['split']['sets'];
            }

            $match_info = (object) [
                'num_sets_to_win'  => $num_sets_to_win,
                'num_games_to_win' => $num_games_to_win,
            ];
            $this->match_validator->handle_match_status_awards( $context, $match_status, $match_info, $stats, $points, $home_score, $away_score );
        }

        $this->finalize_validation_results( $home_score, $away_score, $sets_updated, $stats, $points );

        return $this;
    }

    private function reset_validation_state(): void {
        $this->error    = false;
        $this->err_flds = [];
        $this->err_msgs = [];
    }

    private function initialize_stats_array(): array {
        return [
            'sets'  => [ 'home' => 0, 'away' => 0 ],
            'games' => [ 'home' => 0, 'away' => 0 ],
        ];
    }

    private function initialize_points_array(): array {
        return [
            'home'   => [ 'sets' => 0 ],
            'away'   => [ 'sets' => 0 ],
            'shared' => [ 'sets' => 0 ],
            'split'  => [ 'sets' => 0 ],
        ];
    }

    private function find_retired_set_index( array $sets, ?string $match_status ): ?int {
        if ( ! in_array( $match_status, [ 'retired_player1', 'retired_player2', 'abandoned' ] ) ) {
            return null;
        }

        $num_sets = count( $sets );
        for ( $s1 = $num_sets; $s1 >= 1; $s1 -- ) {
            if ( ( isset( $sets[ $s1 ]['player1'] ) && '' !== $sets[ $s1 ]['player1'] ) || ( isset( $sets[ $s1 ]['player2'] ) && '' !== $sets[ $s1 ]['player2'] ) ) {
                return $s1;
            }
        }

        return null;
    }

    private function determine_set_status_and_info( int $s, ?string $match_status, ?int $set_retired, object $set_info, int $num_sets_to_win, float $home_score, float $away_score ): ?string {
        if ( ( $s > $num_sets_to_win ) && ( $home_score === (float) $num_sets_to_win || $away_score === (float) $num_sets_to_win ) ) {
            $set_info->set_type = 'null';
        }

        switch ( $match_status ) {
            case 'retired_player1':
            case 'retired_player2':
            case 'abandoned':
                if ( $set_retired === $s ) {
                    return $match_status;
                } elseif ( $s > $set_retired ) {
                    $set_info->set_type = 'null';
                }

                return null;
            case 'cancelled':
            default:
                return $match_status;
        }
    }

    /**
     * Legacy method for direct set validation.
     */
    public function validate_set( array $set, string $set_prefix, object $set_info, ?string $match_status ): array {
        $result = $this->set_validator->validate_set( $set, $set_prefix, $set_info, $match_status );
        if ( $this->set_validator->get_error() ) {
            $this->error    = true;
            $this->err_flds = array_unique( array_merge( $this->err_flds, $this->set_validator->get_err_flds() ) );
            $this->err_msgs = array_unique( array_merge( $this->err_msgs, $this->set_validator->get_err_msgs() ) );
        }

        return $result;
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

    private function finalize_validation_results( float $home_score, float $away_score, array $sets_updated, array $stats, array $points ): void {
        $this->home_points = $home_score;
        $this->away_points = $away_score;
        $this->sets        = $sets_updated;
        $this->stats       = $stats;
        $this->points      = $points;
    }

    public function get_sets(): array {
        return $this->sets;
    }

    public function get_stats(): array {
        return $this->stats;
    }

    public function get_points(): array {
        return $this->points;
    }

    public function get_home_points(): float {
        return $this->home_points;
    }

    public function get_away_points(): float {
        return $this->away_points;
    }
}
