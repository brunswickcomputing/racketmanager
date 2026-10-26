<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Account\Commands;

/**
 * Command for requesting a password reset via AJAX.
 */
class Reset_Password_Command {
    public function __construct(
        public readonly ?string $user_login
    ) {
    }

    public static function from_post( array $post ): self {
        $user_login = isset( $post['user_login'] ) ? sanitize_text_field( wp_unslash( $post['user_login'] ) ) : null;

        return new self(
            user_login: $user_login
        );
    }
}
