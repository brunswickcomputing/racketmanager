<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Competition\Commands;

use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Services\Competition_Entry_Service;
use WP_Error;

/**
 * Handler for executing cup competition entries.
 */
class Submit_Cup_Entry_Handler {
    public function __construct(
        private readonly Competition_Entry_Service $competition_entry_service
    ) {
    }

    /**
     * Handle the cup entry submission.
     *
     * @param Submit_Cup_Entry_Command $command
     * @return bool|WP_Error
     * @throws Competition_Not_Found_Exception
     */
    public function handle( Submit_Cup_Entry_Command $command ): mixed {
        return $this->competition_entry_service->request_cup_entry( $command->to_request_dto() );
    }
}
