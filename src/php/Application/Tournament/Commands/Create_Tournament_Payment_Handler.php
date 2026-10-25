<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\Commands;

use Racketmanager\Exceptions\Invoice_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Exceptions\Stripe_API_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Finance_Service;

/**
 * Handler for creating tournament payment requests.
 */
class Create_Tournament_Payment_Handler {
    public function __construct(
        private readonly Finance_Service $finance_service
    ) {
    }

    /**
     * Handle the tournament payment intent creation.
     *
     * @param Create_Tournament_Payment_Command $command
     * @return string Client secret
     * @throws Tournament_Not_Found_Exception
     * @throws Player_Not_Found_Exception
     * @throws Invoice_Not_Found_Exception
     * @throws Stripe_API_Exception
     */
    public function handle( Create_Tournament_Payment_Command $command ): string {
        return (string) $this->finance_service->create_tournament_payment_request(
            $command->tournament_id,
            $command->player_id,
            $command->invoice_id
        );
    }
}
