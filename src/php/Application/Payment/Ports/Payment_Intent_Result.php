<?php
declare(strict_types=1);

namespace Racketmanager\Application\Payment\Ports;

final class Payment_Intent_Result {
    public function __construct(
        public readonly string $id,
        public readonly string $client_secret,
        public readonly ?string $status,
        public readonly ?int $amount,
        public readonly ?string $currency
    ) {}
}
