<?php

namespace Racketmanager\Domain\DTO\Account;

/**
 * Command for user login via AJAX.
 */
class Login_Command {
    public function __construct(
        public readonly ?string $user_login,
        public readonly ?string $user_password,
        public readonly ?string $redirect_to = null
    ) {
    }

    public static function from_post( array $post ): self {
        $user_login    = isset( $post['log'] ) ? sanitize_text_field( wp_unslash( $post['log'] ) ) : null;
        $user_password = isset( $post['pwd'] ) ? sanitize_text_field( wp_unslash( $post['pwd'] ) ) : null;
        $redirect_to   = isset( $post['redirect_to'] ) ? sanitize_url( wp_unslash( $post['redirect_to'] ) ) : null;

        return new self(
            user_login: $user_login,
            user_password: $user_password,
            redirect_to: $redirect_to
        );
    }
}
