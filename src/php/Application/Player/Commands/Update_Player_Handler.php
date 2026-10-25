<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Player\Commands;

use Exception;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Exceptions\Duplicate_BTM_Exception;
use Racketmanager\Exceptions\Duplicate_Email_Exception;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Not_Updated_Exception;
use Racketmanager\Services\Player_Service;
use WP_Error;

/**
 * Handler for executing player profile updates.
 */
class Update_Player_Handler {
    public function __construct(
        private readonly Player_Service $player_service
    ) {
    }

    /**
     * Handle updating player details.
     *
     * @param Update_Player_Command $command
     * @return Player|WP_Error
     * @throws Player_Not_Found_Exception
     * @throws Player_Not_Updated_Exception
     * @throws Duplicate_Email_Exception
     * @throws Duplicate_BTM_Exception
     * @throws Exception
     */
    public function handle( Update_Player_Command $command ): Player|WP_Error {
        return $this->player_service->amend_player_details(
            $command->player_id,
            $command->to_array()
        );
    }
}
