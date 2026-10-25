<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\Commands;

use Racketmanager\Domain\DTO\Tournament\Tournament_Entry_Response_DTO;
use Racketmanager\Services\Competition_Entry_Service;
use WP_Error;

/**
 * Handler for executing tournament entries.
 */
readonly class Submit_Tournament_Entry_Handler {
    public function __construct(
        private Competition_Entry_Service $competition_entry_service
    ) {
    }

    /**
     * Handle the tournament entry submission.
     *
     * @param Submit_Tournament_Entry_Command $command
     * @return Tournament_Entry_Response_DTO|WP_Error
     */
    public function handle( Submit_Tournament_Entry_Command $command ): mixed {
        return $this->competition_entry_service->request_tournament_entry( $command->to_request_dto() );
    }
}
