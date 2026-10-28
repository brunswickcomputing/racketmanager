<?php
declare(strict_types=1);

namespace Racketmanager\Application\Mail;

/**
 * Mailer port for application notification and dispatch.
 */
interface Mailer_Interface {
    public function send(
        string|array|null $to,
        ?string $subject,
        ?string $message,
        string|array|null $headers = '',
        ?array $attachments = []
    ): bool;
}
