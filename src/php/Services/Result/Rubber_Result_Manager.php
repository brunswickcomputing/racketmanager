<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Result;

use Racketmanager\Domain\DTO\Rubber\Rubber_Update_Request;
use Racketmanager\Domain\DTO\Rubber\Rubber_Update_Result;
use Racketmanager\Domain\Fixture;
use Racketmanager\Exceptions\Fixture_Validation_Exception;
use Racketmanager\Services\Validator\Score_Validation_Service;
use Racketmanager\Domain\Scoring\Scoring_Context;
use Racketmanager\Services\League_Service;
use Racketmanager\Repositories\Rubber_Repository;
use function Racketmanager\get_rubber;

/**
 * Service for managing individual rubber results and updates.
 */
class Rubber_Result_Manager {
    private Score_Validation_Service $score_validator;
    private League_Service $league_service;
    private Rubber_Repository $rubber_repository;

    public function __construct(
        Score_Validation_Service $score_validator,
        League_Service $league_service,
        ?Rubber_Repository $rubber_repository = null
    ) {
        $this->score_validator   = $score_validator;
        $this->league_service    = $league_service;
        $this->rubber_repository = $rubber_repository ?? new Rubber_Repository();
    }

    /**
     * Handle the update of a single rubber.
     *
     * @param Fixture $fixture
     * @param Rubber_Update_Request $request
     * @param array $dummy_players
     * @return Rubber_Update_Result
     * @throws Fixture_Validation_Exception
     */
    public function handle_rubber_update( Fixture $fixture, Rubber_Update_Request $request, array $dummy_players = [] ): Rubber_Update_Result {
        $rubber = get_rubber( $request->rubber_id );
        if ( ! $rubber ) {
            throw new Fixture_Validation_Exception( [ __( 'Rubber not found', 'racketmanager' ) ] );
        }

        $league = $this->league_service->get_league( $fixture->get_league_id() );
        
        // 1. Handle Dummy Players (This logic might move to a Player_Service later)
        $players = $this->apply_dummy_players( $league->type ?? '', $request->rubber_status, $request->players, $dummy_players );

        $player_numbers = [ 1 ];
        if ( $request->rubber_type && str_contains( $request->rubber_type, 'D' ) ) {
            $player_numbers[] = 2;
        }

        // 2. Validate Players Involved
        // For now, we rely on the legacy validator being passed or available. 
        // But since we want to move away from it, we might need a new way.
        // For this step, we assume the players are already partially validated by the caller or we add minimal checks.

        // 3. Validate Score
        $scoring_context = new Scoring_Context(
            num_sets_to_win: (int) $league->num_sets_to_win,
            scoring_type: $league->scoring ?? 'TB',
            point_rule: $league->get_point_rule(),
            is_championship: (bool) $league->is_championship,
            final_round: $fixture->get_final(),
            num_rubbers: (int) $league->num_rubbers,
            leg: $fixture->get_leg(),
            num_sets: (int) $league->num_sets
        );

        $set_prefix = 'set_' . $request->rubber_number . '_';
        $this->score_validator->validate( $scoring_context, $request->sets, $request->rubber_status, $set_prefix, $request->rubber_number );

        if ( $this->score_validator->get_error() ) {
            throw new Fixture_Validation_Exception(
                $this->score_validator->get_err_msgs(),
                $this->score_validator->get_err_flds()
            );
        }

        $sets   = $this->score_validator->get_sets();
        $stats  = $this->score_validator->get_stats();
        $points = $this->score_validator->get_points();

        // 4. Determine Status and Custom data
        $status = 0;
        $custom = [ 'sets' => $sets, 'stats' => $stats ];

        switch ( $request->rubber_status ) {
            case 'share':
                $status          = 3;
                $custom['share'] = true;
                break;
            case 'walkover_player1':
                $status             = 1;
                $custom['walkover'] = 'home';
                break;
            case 'walkover_player2':
                $status             = 1;
                $custom['walkover'] = 'away';
                break;
            case 'retired_player1':
                $status            = 2;
                $custom['retired'] = 'home';
                break;
            case 'retired_player2':
                $status            = 2;
                $custom['retired'] = 'away';
                break;
            case 'abandoned':
                $status              = 6;
                $custom['abandoned'] = true;
                break;
            case 'cancelled':
                $status              = 8;
                $custom['cancelled'] = true;
                break;
            case 'invalid_player1':
                $status            = 9;
                $custom['invalid'] = 'home';
                break;
            case 'invalid_player2':
                $status            = 9;
                $custom['invalid'] = 'away';
                break;
            case 'invalid_players':
                $status            = 9;
                $custom['invalid'] = 'both';
                break;
        }

        // 5. Calculate Result
        $points['home']['team'] = $fixture->get_home_team();
        $points['away']['team'] = $fixture->get_away_team();
        $calc_result            = $rubber->calculate_result( $points );

        $rubber->set_home_points( (float) $calc_result->home );
        $rubber->set_away_points( (float) $calc_result->away );
        $rubber->set_winner_id( (string) $calc_result->winner );
        $rubber->set_loser_id( (string) $calc_result->loser );
        $rubber->set_custom( $custom );
        $rubber->set_status( $status );

        $this->rubber_repository->save( $rubber );
        $rubber->set_players( $players );

        return new Rubber_Update_Result(
            rubber_id: (int) $request->rubber_id,
            home_points: (float) $calc_result->home,
            away_points: (float) $calc_result->away,
            winner_id: $calc_result->winner ? (int) $calc_result->winner : null,
            players: $players,
            sets: $sets,
            status: $status,
            custom: $custom,
            stats: $stats
        );
    }

    /**
     * Map dummy players based on status.
     */
    private function apply_dummy_players( string $match_type, ?string $status, array $players, array $dummy_players ): array {
        if ( empty( $dummy_players ) ) {
            return $players;
        }

        $opponents = [ 'home', 'away' ];
        foreach ( $opponents as $opponent ) {
            if ( ! isset( $players[ $opponent ] ) ) {
                $players[ $opponent ] = [];
            }
            for ( $i = 1; $i <= 2; $i++ ) {
                if ( ! isset( $players[ $opponent ][ $i ] ) ) {
                    $players[ $opponent ][ $i ] = 0;
                }
                
                // If walkover/retired/invalid, ensure dummy players are used if necessary
                // This logic is a simplified version of Racketmanager_Match::set_dummy_players
                if ( $status && $status !== 'none' && (int)$players[ $opponent ][ $i ] === 0 ) {
                    if ( isset( $dummy_players[ $opponent ] ) && ! empty( $dummy_players[ $opponent ] ) ) {
                        // Just pick the first dummy player for now, similar to legacy
                        $players[ $opponent ][ $i ] = $dummy_players[ $opponent ][0]->id ?? 0;
                    }
                }
            }
        }
        return $players;
    }
}
