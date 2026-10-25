<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\Commands;

use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Services\Admin\Championship\Draw_Action_Handler_Interface;

/**
 * Handler for generating and setting tournament/championship bracket fixtures.
 */
readonly class Generate_Tournament_Bracket_Handler {
    public function __construct(
        private Draw_Action_Handler_Interface $championship_admin_service
    ) {
    }

    /**
     * Handle bracket fixture generation.
     *
     * @param Generate_Tournament_Bracket_Command $command
     * @return Action_Result_DTO
     */
    public function handle( Generate_Tournament_Bracket_Command $command ): Action_Result_DTO {
        return $this->championship_admin_service->set_championship_fixtures( $command->to_draw_action_dto() );
    }
}
