<?php

namespace Racketmanager\Presenters;

use Racketmanager\Domain\Enums\Fixture_Reset_Status;
use Racketmanager\Domain\DTO\Fixture\Fixture_Update_Response;
use Racketmanager\Domain\Enums\Fixture\Fixture_Update_Status;

use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\DTO\Fixture\Fixture_Status_Update_Request;
use Racketmanager\Application\Fixture\DTOs\Fixture_Status_Read_Model;
use Racketmanager\Domain\DTO\Fixture\Fixture_Result_Update_Request;
use Racketmanager\Application\Fixture\DTOs\Fixture_Result_Read_Model;
use stdClass;

class Fixture_Presenter {

    public function map_to_status_read_model(
        Fixture $fixture,
        Fixture_Status_Update_Request $request,
        int $num_rubbers = 0
    ): Fixture_Status_Read_Model {
        $status_dtls = $this->get_status_details(
            (string) $request->match_status,
            (int) $fixture->get_home_team(),
            (int) $fixture->get_away_team()
        );

        return new Fixture_Status_Read_Model(
            (int) $fixture->get_id(),
            $status_dtls->status,
            $status_dtls->message,
            $status_dtls->class,
            $request->modal,
            $num_rubbers,
            $request->rubber_number
        );
    }

    /**
     * Function to set match or rubber status details
     *
     * @param string $status status value.
     * @param int $home_team home team id.
     * @param int $away_team away_team id.
     */
    public function get_status_details( string $status, int $home_team, int $away_team ): object {
        $status_message = array();
        $status_class   = array();
        $status_values  = explode( '_', $status );
        $status_value   = $status_values[0];
        $player_ref     = $status_values[1] ?? null;
        $winner         = null;
        $loser          = null;
        $score_message  = null;
        switch ( $status_value ) {
            case 'walkover':
                $score_message = __( 'Walkover', 'racketmanager' );
                if ( 'player2' === $player_ref ) {
                    $winner = $away_team;
                    $loser  = $home_team;
                } elseif ( 'player1' === $player_ref ) {
                    $winner = $home_team;
                    $loser  = $away_team;
                }
                break;
            case 'retired':
                $score_message = __( 'Retired', 'racketmanager' );
                if ( 'player1' === $player_ref ) {
                    $winner = $away_team;
                    $loser  = $home_team;
                } elseif ( 'player2' === $player_ref ) {
                    $winner = $home_team;
                    $loser  = $away_team;
                }
                break;
            case 'invalid':
                $score_message = __( 'Invalid player', 'racketmanager' );
                if ( 'player1' === $player_ref ) {
                    $winner = $away_team;
                    $loser  = $home_team;
                } elseif ( 'player2' === $player_ref ) {
                    $winner = $home_team;
                    $loser  = $away_team;
                }
                break;
            case 'share':
                $score_message = __( 'Not played', 'racketmanager' );
                break;
            case 'abandoned':
                $score_message = __( 'Abandoned', 'racketmanager' );
                break;
            case 'cancelled':
                $score_message = __( 'Cancelled', 'racketmanager' );
                break;
            case 'none':
                $status = '';
                break;
            default:
                break;
        }
        if ( $winner ) {
            $status_message[ $winner ] = '';
            $status_message[ $loser ]  = $score_message;
            $status_class[ $winner ]   = 'winner';
            $status_class[ $loser ]    = 'loser';
        } elseif ( 'share' === $status_value || 'cancelled' === $status_value || 'invalid' === $status_value ) {
            $status_message[ $home_team ] = $score_message;
            $status_message[ $away_team ] = $score_message;
            $status_class[ $home_team ]   = 'tie';
            $status_class[ $away_team ]   = 'tie';
        } elseif ( 'abandoned' === $status_value ) {
            $status_message[ $home_team ] = $score_message;
            $status_message[ $away_team ] = $score_message;
            $status_class[ $home_team ]   = '';
            $status_class[ $away_team ]   = '';
        } else {
            $status_message[ $home_team ] = '';
            $status_message[ $away_team ] = '';
            $status_class[ $home_team ]   = '';
            $status_class[ $away_team ]   = '';
        }
        $status_dtls          = new stdClass();
        $status_dtls->message = $status_message;
        $status_dtls->class   = $status_class;
        $status_dtls->status  = $status;

        return $status_dtls;
    }

    public function map_to_result_read_model(
        Fixture $fixture,
        Fixture_Update_Response $response,
        Fixture_Result_Update_Request $request
    ): Fixture_Result_Read_Model {
        return new Fixture_Result_Read_Model(
            $this->get_update_message( $response ),
            $fixture->get_home_points(),
            $fixture->get_away_points(),
            $fixture->get_winner_id(),
            $request->sets
        );
    }

    public function get_reset_message( Fixture_Reset_Status $status ): string {
        return match ( $status ) {
            Fixture_Reset_Status::SUCCESS_DIVISION_RESET => __( 'Division match result reset', 'racketmanager' ),
            Fixture_Reset_Status::SUCCESS_KNOCKOUT_RESET => __( 'Knockout result and progression reset', 'racketmanager' ),
            Fixture_Reset_Status::ERROR_NOT_FOUND        => __( 'Fixture not found', 'racketmanager' ),
            default => __( 'Fixture reset', 'racketmanager' ),
        };
    }

    public function get_update_message( Fixture_Update_Response $response ): string {
        if ( $response->has_outcome( Fixture_Update_Status::PROGRESSED ) ) {
            return __( 'Result saved and draw updated', 'racketmanager' );
        }

        if ( $response->has_outcome( Fixture_Update_Status::TABLE_UPDATED ) ) {
            return __( 'Result saved and league table updated', 'racketmanager' );
        }

        return __( 'Result saved', 'racketmanager' );
    }

}
