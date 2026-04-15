<?php

namespace Racketmanager\Presenters;

use Racketmanager\Domain\Enums\Fixture_Reset_Status;
use Racketmanager\Domain\DTO\Fixture\Fixture_Update_Response;
use Racketmanager\Domain\Enums\Fixture\Fixture_Update_Status;

use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\DTO\Fixture\Fixture_Result_Update_Request;
use Racketmanager\Application\Fixture\DTOs\Fixture_Result_Read_Model;

class Fixture_Presenter {

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
