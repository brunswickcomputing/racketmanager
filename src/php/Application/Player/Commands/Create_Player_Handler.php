<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Player\Commands;

use Exception;
use Racketmanager\Application\Player\Ports\Player_Service_Interface;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Exceptions\Duplicate_BTM_Exception;
use Racketmanager\Exceptions\Duplicate_Email_Exception;
use Racketmanager\Exceptions\Player_Exists_Exception;
use WP_Error;

/**
 * Handler for creating a new player.
 */
readonly class Create_Player_Handler {
    /**
     * Initialises the service with the player service dependency.
     *
     * @param Player_Service_Interface $player_service Player service instance.
     *
     * @return void
     */
    public function __construct(
        private Player_Service_Interface $player_service
    ) {
    }

    /**
     * Handle creating a new player.
     *
     * @param Create_Player_Command $command Command containing the player creation data.
     *
     * @return Player|WP_Error Created player instance on success, or error on failure.
     * @throws Player_Exists_Exception Player already exists exception.
     * @throws Duplicate_Email_Exception Duplicate email exception.
     * @throws Duplicate_BTM_Exception Duplicate BTM exception.
     * @throws Exception Exception.
     */
    public function handle( Create_Player_Command $command ): Player|WP_Error {
        return $this->player_service->add_new_player(
            $command->to_array()
        );
    }
}
