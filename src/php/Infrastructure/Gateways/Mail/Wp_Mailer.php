<?php
declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Gateways\Mail;

use Racketmanager\Application\Mail\Mailer_Interface;

class Wp_Mailer implements Mailer_Interface {
    public function send(
        string|array|null $to,
        ?string $subject,
        ?string $message,
        string|array|null $headers = '',
        ?array $attachments = []
    ): bool {
        if ( empty( $to ) || null === $subject || null === $message ) {
            return false;
        }

        return (bool) wp_mail( $to, $subject, $message, $headers ?? '', $attachments ?? [] );
    }
}
