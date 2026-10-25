<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\Commands;

use Racketmanager\Services\Competition_Entry_Service;
use WP_Error;

/**
 * Handler for validating/submitting tournament partner requests.
 */
class Submit_Tournament_Partner_Handler {
    public function __construct(
        private readonly Competition_Entry_Service $competition_entry_service
    ) {
    }

    /**
     * Handle tournament partner validation/submission.
     *
     * @param Submit_Tournament_Partner_Command $command
     * @return bool|WP_Error
     */
    public function handle( Submit_Tournament_Partner_Command $command ): mixed {
        return $this->competition_entry_service->validate_tournament_partner( $command->to_request_dto() );
    }
}
