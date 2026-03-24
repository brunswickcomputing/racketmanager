<?php

namespace Racketmanager\Presenters;

use Racketmanager\Domain\Enums\Fixture_Reset_Status;
use Racketmanager\Domain\DTO\Fixture\Fixture_Update_Response;
use Racketmanager\Domain\Enums\Fixture\Fixture_Update_Status;

class Fixture_Presenter {

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
