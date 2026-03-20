<?php

namespace Racketmanager\Presenters;

use Racketmanager\Domain\Enums\Fixture_Reset_Status;

class Fixture_Presenter {

    public function get_reset_message( Fixture_Reset_Status $status ): string {
        return match ( $status ) {
            Fixture_Reset_Status::SUCCESS_DIVISION_RESET => __( 'Division match result reset', 'racketmanager' ),
            Fixture_Reset_Status::SUCCESS_KNOCKOUT_RESET => __( 'Knockout result and progression reset', 'racketmanager' ),
            Fixture_Reset_Status::ERROR_NOT_FOUND        => __( 'Fixture not found', 'racketmanager' ),
            default => __( 'Fixture reset', 'racketmanager' ),
        };
    }

}
