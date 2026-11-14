<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\Commands;

use Racketmanager\Application\Competition\Ports\Competition_Entry_Service_Interface;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;

/**
 * Handler for confirming tournament withdrawals.
 */
class Withdraw_Tournament_Entry_Handler {
    public function __construct(
        private readonly Competition_Entry_Service_Interface $competition_entry_service
    ) {
    }

    /**
     * Handle the tournament withdrawal.
     *
     * @param Withdraw_Tournament_Entry_Command $command
     * @return int
     * @throws Tournament_Not_Found_Exception
     * @throws Player_Not_Found_Exception
     */
    public function handle( Withdraw_Tournament_Entry_Command $command ): int {
        return $this->competition_entry_service->confirm_tournament_withdrawal(
            $command->tournament_id,
            $command->player_id
        );
    }
}
