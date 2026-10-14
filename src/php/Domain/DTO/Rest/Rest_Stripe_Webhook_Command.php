<?php
/**
 * REST Stripe webhook command DTO
 *
 * @package RacketManager
 * @subpackage Domain/DTO/Rest
 */

declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Rest;

/**
 * Typed command DTO for REST Stripe webhook endpoint.
 */
final readonly class Rest_Stripe_Webhook_Command {

    public function __construct(
        public ?string $payload = null,
        public ?string $signature_header = null,
    ) {
    }
}
