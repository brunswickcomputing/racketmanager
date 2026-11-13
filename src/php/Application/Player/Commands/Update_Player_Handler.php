<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Player\Commands;

use Racketmanager\Application\Player\Ports\Player_Service_Interface;
use Racketmanager\Domain\Player\Player;
use WP_Error;

/**
 * Handler for executing player profile updates.
 */
readonly class Update_Player_Handler {
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
     * Handles the command to update player details.
     *
     * @param Update_Player_Command $command Command containing the player update data.
     *
     * @return Player|WP_Error Updated player instance on success, or error on failure.
     */
    public function handle( Update_Player_Command $command ): Player|WP_Error {
        return $this->player_service->amend_player_details(
            $command->player_id,
            $command->to_array()
        );
    }
}
