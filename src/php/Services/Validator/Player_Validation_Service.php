<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Validator;

use Racketmanager\Domain\Fixture;
use Racketmanager\Domain\League;
use Racketmanager\Services\Registration_Service;

/**
 * Service for validating player eligibility and handling dummy player assignments.
 */
class Player_Validation_Service {
    private Registration_Service $registration_service;

    public function __construct( Registration_Service $registration_service ) {
        $this->registration_service = $registration_service;
    }

    /**
     * Map dummy players based on match type and status.
     *
     * @param string $match_type e.g., 'MD', 'WD', 'XD'
     * @param string $status e.g., 'share', 'walkover_player1'
     * @param array $players Current player assignments
     * @param array $dummy_players Pre-loaded dummy players by team and gender
     * @return array Updated player assignments
     */
    public function apply_dummy_players( string $match_type, string $status, array $players, array $dummy_players ): array {
        switch ( $status ) {
            case 'share':
                if ( 'MD' === $match_type || 'BD' === $match_type ) {
                    $players['home']['1'] = $dummy_players['home']['share']['male']->roster_id ?? 0;
                    $players['home']['2'] = $players['home']['1'];
                    $players['away']['1'] = $dummy_players['away']['share']['male']->roster_id ?? 0;
                    $players['away']['2'] = $players['away']['1'];
                } elseif ( 'WD' === $match_type || 'GD' === $match_type ) {
                    $players['home']['1'] = $dummy_players['home']['share']['female']->roster_id ?? 0;
                    $players['home']['2'] = $players['home']['1'];
                    $players['away']['1'] = $dummy_players['away']['share']['female']->roster_id ?? 0;
                    $players['away']['2'] = $players['away']['1'];
                } elseif ( 'XD' === $match_type ) {
                    $players['home']['1'] = $dummy_players['home']['share']['male']->roster_id ?? 0;
                    $players['home']['2'] = $dummy_players['home']['share']['female']->roster_id ?? 0;
                    $players['away']['1'] = $dummy_players['away']['share']['male']->roster_id ?? 0;
                    $players['away']['2'] = $dummy_players['away']['share']['female']->roster_id ?? 0;
                }
                break;
            case 'walkover_player1':
                if ( 'MD' === $match_type || 'BD' === $match_type ) {
                    if ( empty( $players['home']['1'] ) ) {
                        $players['home']['1'] = $dummy_players['home']['walkover']['male']->roster_id ?? 0;
                    }
                    if ( empty( $players['home']['2'] ) ) {
                        $players['home']['2'] = $dummy_players['home']['walkover']['male']->roster_id ?? 0;
                    }
                    $players['away']['1'] = $dummy_players['away']['noplayer']['male']->roster_id ?? 0;
                    $players['away']['2'] = $players['away']['1'];
                } elseif ( 'WD' === $match_type || 'GD' === $match_type ) {
                    if ( empty( $players['home']['1'] ) ) {
                        $players['home']['1'] = $dummy_players['home']['walkover']['female']->roster_id ?? 0;
                    }
                    if ( empty( $players['home']['2'] ) ) {
                        $players['home']['2'] = $dummy_players['home']['walkover']['female']->roster_id ?? 0;
                    }
                    $players['away']['1'] = $dummy_players['away']['noplayer']['female']->roster_id ?? 0;
                    $players['away']['2'] = $players['away']['1'];
                } elseif ( 'XD' === $match_type ) {
                    if ( empty( $players['home']['1'] ) ) {
                        $players['home']['1'] = $dummy_players['home']['walkover']['male']->roster_id ?? 0;
                    }
                    if ( empty( $players['home']['2'] ) ) {
                        $players['home']['2'] = $dummy_players['home']['walkover']['female']->roster_id ?? 0;
                    }
                    $players['away']['1'] = $dummy_players['away']['noplayer']['male']->roster_id ?? 0;
                    $players['away']['2'] = $dummy_players['away']['noplayer']['female']->roster_id ?? 0;
                }
                break;
            case 'walkover_player2':
                if ( 'MD' === $match_type || 'BD' === $match_type ) {
                    $players['home']['1'] = $dummy_players['home']['noplayer']['male']->roster_id ?? 0;
                    $players['home']['2'] = $players['home']['1'];
                    if ( empty( $players['away']['1'] ) ) {
                        $players['away']['1'] = $dummy_players['away']['walkover']['male']->roster_id ?? 0;
                    }
                    if ( empty( $players['away']['2'] ) ) {
                        $players['away']['2'] = $dummy_players['away']['walkover']['male']->roster_id ?? 0;
                    }
                } elseif ( 'WD' === $match_type || 'GD' === $match_type ) {
                    $players['home']['1'] = $dummy_players['home']['noplayer']['female']->roster_id ?? 0;
                    $players['home']['2'] = $players['home']['1'];
                    if ( empty( $players['away']['1'] ) ) {
                        $players['away']['1'] = $dummy_players['away']['walkover']['female']->roster_id ?? 0;
                    }
                    if ( empty( $players['away']['2'] ) ) {
                        $players['away']['2'] = $dummy_players['away']['walkover']['female']->roster_id ?? 0;
                    }
                } elseif ( 'XD' === $match_type ) {
                    $players['home']['1'] = $dummy_players['home']['noplayer']['male']->roster_id ?? 0;
                    $players['home']['2'] = $dummy_players['home']['noplayer']['female']->roster_id ?? 0;
                    if ( empty( $players['away']['1'] ) ) {
                        $players['away']['1'] = $dummy_players['away']['walkover']['male']->roster_id ?? 0;
                    }
                    if ( empty( $players['away']['2'] ) ) {
                        $players['away']['2'] = $dummy_players['away']['walkover']['female']->roster_id ?? 0;
                    }
                }
                break;
        }
        return $players;
    }

    /**
     * Validate WTN order of players across rubbers in a team match.
     *
     * @param Fixture $fixture
     * @param League $league
     * @param array $processed_rubbers List of rubbers with player WTNs
     * @return array List of validation errors/warnings
     */
    public function validate_wtn_order( Fixture $fixture, League $league, array $processed_rubbers ): array {
        global $racketmanager;
        $warnings = [];
        $check_options = $racketmanager->get_options( 'checks' );
        
        if ( empty( $league?->event?->competition?->rules['wtn_check'] ) || empty( $check_options['wtn_check'] ) ) {
            return $warnings;
        }

        $prev_wtns = [];
        
        foreach ( $processed_rubbers as $rubber_data ) {
            // $rubber_data is an object containing status, winner_id, home_points, away_points, custom, and stats
            // We need the WTNs from the rubber. Currently handle_rubber_update doesn't return them in processed_rubbers.
            // Let's refine the processed_rubbers to include wtns.
        }
        
        return $warnings;
    }
}
