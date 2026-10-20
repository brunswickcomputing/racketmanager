<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Competition\Queries;

use Racketmanager\Exceptions\Charge_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Presentation\Presenters\Tournament_Presenter;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\Competition_Entry_Service;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Stripe_Settings;
use Racketmanager\Services\Tournament_Service;
use function __;
use function defined;

class Get_Competition_Payment_Handler {
    public function __construct(
        private readonly Tournament_Service $tournament_service,
        private readonly Player_Service $player_service,
        private readonly Finance_Service $finance_service,
        private readonly Competition_Entry_Service $entry_service,
        private readonly Tournament_Presenter $tournament_presenter
    ) {}

    public function handle( Get_Competition_Payment_Query $query ): Shortcode_View_Model {
        if ( 'tournament' !== $query->competition_type || '' === $query->tournament_name ) {
            return Shortcode_View_Model::error( __( 'Tournament not found', 'racketmanager' ) );
        }

        try {
            $tournament = $this->tournament_service->get_tournament_by_name( $query->tournament_name );
            $player = $this->player_service->get_player( $query->player_id );
            $entry = $this->entry_service->get_tournament_entry_by_tournament_and_player( (int) $tournament->id, (int) $player->id );
            $site_url = defined( 'RACKETMANAGER_SITE' ) ? RACKETMANAGER_SITE : '/';
            $vars = [
                'tournament'           => $tournament,
                'player'               => $player,
                'tournament_entry'     => $entry,
                'payment_complete_url' => $this->tournament_presenter->get_payment_complete_url( $tournament, $site_url ),
                'tournament_header'    => $this->tournament_presenter->present_header( $tournament, false ),
            ];

            if ( $query->complete ) {
                $vars['total_due'] = null;
                $vars['stripe']    = new Stripe_Settings( $query->billing_options );
                return new Shortcode_View_Model( 'tournament-payment-complete', $vars, 'entry' );
            }

            $charge = $this->finance_service->get_charge( $tournament->competition_id . '_' . $tournament->season );
            $invoices = $this->finance_service->get_invoices_by_criteria( [
                'charge' => $charge->id,
                'player' => $query->player_id,
                'status' => 'open',
            ] );
        } catch ( Tournament_Not_Found_Exception|Player_Not_Found_Exception|Charge_Not_Found_Exception $e ) {
            return Shortcode_View_Model::error( $e->getMessage() );
        }

        $vars['total_due']  = 0;
        $vars['invoice_id'] = null;
        foreach ( $invoices as $invoice ) {
            $vars['total_due'] += $invoice->amount;
            $vars['invoice_id'] = $invoice->id;
        }
        $vars['stripe'] = new Stripe_Settings( $query->billing_options );

        return new Shortcode_View_Model(
            template: '' !== $query->template ? 'tournament-payment-' . $query->template : 'tournament-payment',
            vars: $vars,
            template_type: 'entry'
        );
    }
}