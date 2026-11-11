<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\Commands;

use Racketmanager\Application\Ports\Finance_Service_Interface;
use Racketmanager\Exceptions\Invoice_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Exceptions\Stripe_API_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;

/**
 * Handler for creating tournament payment requests.
 */
readonly class Create_Tournament_Payment_Handler {
    /**
     * Constructor
     *
     * @param Finance_Service_Interface $finance_service finance service.
     */
    public function __construct(
        private Finance_Service_Interface $finance_service
    ) {
    }

    /**
     * Handle the tournament payment intent creation.
     *
     * @param Create_Tournament_Payment_Command $command command.
     * @return string Client secret
     * @throws Tournament_Not_Found_Exception Tournament not found.
     * @throws Player_Not_Found_Exception Player not found.
     * @throws Invoice_Not_Found_Exception Invoice not found.
     * @throws Stripe_API_Exception Stripe API error.
     */
    public function handle( Create_Tournament_Payment_Command $command ): string {
        return $this->finance_service->create_tournament_payment_request(
            $command->tournament_id,
            $command->player_id,
            $command->invoice_id
        );
    }
}
