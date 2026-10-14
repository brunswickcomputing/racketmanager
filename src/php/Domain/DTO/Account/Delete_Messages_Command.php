<?php

namespace Racketmanager\Domain\DTO\Account;

/**
 * Command for deleting messages by type via AJAX.
 */
class Delete_Messages_Command {
    public function __construct(
        public readonly ?string $message_type,
        public readonly int $user_id
    ) {
    }

    public static function from_post( array $post, int $user_id ): self {
        $message_type = isset( $post['message_type'] ) ? sanitize_text_field( wp_unslash( $post['message_type'] ) ) : null;

        return new self(
            message_type: $message_type,
            user_id: $user_id
        );
    }
}
