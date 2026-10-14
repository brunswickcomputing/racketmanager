<?php
declare(strict_types=1);

namespace Racketmanager\Gateways\Payment;

interface Payment_Gateway_Interface {
    public function create_payment_intent( array $args ): Payment_Intent_Result;
}
