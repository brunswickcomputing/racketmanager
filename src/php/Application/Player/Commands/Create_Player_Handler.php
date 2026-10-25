<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Player\Commands;

use Exception;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Exceptions\Duplicate_BTM_Exception;
use Racketmanager\Exceptions\Duplicate_Email_Exception;
use Racketmanager\Exceptions\Player_Exists_Exception;
use Racketmanager\Services\Player_Service;
use WP_Error;

/**
 * Handler for creating a new player.
 */
class Create_Player_Handler {
    public function __construct(
        private readonly Player_Service $player_service
    ) {
    }

    /**
     * Handle creating a new player.
     *
     * @param Create_Player_Command $command
     * @return Player|WP_Error
     * @throws Player_Exists_Exception
     * @throws Duplicate_Email_Exception
     * @throws Duplicate_BTM_Exception
     * @throws Exception
     */
    public function handle( Create_Player_Command $command ): Player|WP_Error {
        return $this->player_service->add_new_player(
            $command->to_array()
        );
    }
}
