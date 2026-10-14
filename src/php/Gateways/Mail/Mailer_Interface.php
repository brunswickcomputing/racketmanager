<?php
declare(strict_types=1);

namespace Racketmanager\Gateways\Mail;

interface Mailer_Interface {
    public function send(
        string|array|null $to,
        ?string $subject,
        ?string $message,
        string|array|null $headers = '',
        ?array $attachments = []
    ): bool;
}
