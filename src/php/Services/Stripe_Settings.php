<?php
declare( strict_types=1 );
/**
 * Stripe_Settings API: stripe class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Stripe_Settings
 */

namespace Racketmanager\Services;

use Racketmanager\Application\Finance\DTOs\Stripe_Settings as Stripe_Settings_DTO;

/**
 * Class to implement the Stripe_Settings object
 */
class Stripe_Settings extends Stripe_Settings_DTO {
    /**
     * Update payment status
     *
     * @param Finance_Service $finance_service Finance service instance.
     * @param string          $payment_ref     PaymentIntent id.
     * @param string          $status          Payment status defaults to paid.
     * @return void
     */
    public function update_payment( Finance_Service $finance_service, string $payment_ref, string $status = 'paid' ): void {
        $finance_service->update_payment( $payment_ref, $status );
    }
}
