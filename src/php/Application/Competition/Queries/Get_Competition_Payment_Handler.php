<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Competition\Queries;

use Racketmanager\Application\Competition\DTOs\Competition_Payment_Result;
use Racketmanager\Exceptions\Charge_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Competition_Entry_Service;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Stripe_Settings;
use Racketmanager\Services\Tournament_Service;
use function __;

class Get_Competition_Payment_Handler {
    public function __construct(
        private readonly Tournament_Service $tournament_service,
        private readonly Player_Service $player_service,
        private readonly Finance_Service $finance_service,
        private readonly Competition_Entry_Service $entry_service
    ) {}

    public function handle( Get_Competition_Payment_Query $query ): Competition_Payment_Result {
        if ( 'tournament' !== $query->competition_type || '' === $query->tournament_name ) {
            return Competition_Payment_Result::error( __( 'Tournament not found', 'racketmanager' ) );
        }

        try {
            $tournament = $this->tournament_service->get_tournament_by_name( $query->tournament_name );
            $player = $this->player_service->get_player( $query->player_id );
            $entry = $this->entry_service->get_tournament_entry_by_tournament_and_player( (int) $tournament->id, (int) $player->id );

            if ( $query->complete ) {
                return new Competition_Payment_Result(
                    tournament: $tournament,
                    player: $player,
                    tournament_entry: $entry,
                    total_due: null,
                    invoice_id: null,
                    stripe: new Stripe_Settings( $query->billing_options ),
                    complete: true,
                    template: 'tournament-payment-complete'
                );
            }

            $charge = $this->finance_service->get_charge( $tournament->competition_id . '_' . $tournament->season );
            $invoices = $this->finance_service->get_invoices_by_criteria( [
                'charge' => $charge->id,
                'player' => $query->player_id,
                'status' => 'open',
            ] );
        } catch ( Tournament_Not_Found_Exception|Player_Not_Found_Exception|Charge_Not_Found_Exception $e ) {
            return Competition_Payment_Result::error( $e->getMessage() );
        }

        $total_due  = 0.0;
        $invoice_id = null;
        foreach ( $invoices as $invoice ) {
            $total_due += (float) $invoice->amount;
            $invoice_id = (int) $invoice->id;
        }

        $template = '' !== $query->template ? 'tournament-payment-' . $query->template : 'tournament-payment';

        return new Competition_Payment_Result(
            tournament: $tournament,
            player: $player,
            tournament_entry: $entry,
            total_due: $total_due,
            invoice_id: $invoice_id,
            stripe: new Stripe_Settings( $query->billing_options ),
            complete: false,
            template: $template
        );
    }
}