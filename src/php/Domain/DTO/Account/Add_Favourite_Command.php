<?php

namespace Racketmanager\Domain\DTO\Account;

/**
 * Command for adding or removing a favourite item via AJAX.
 */
class Add_Favourite_Command {
    public function __construct(
        public readonly string $type,
        public readonly int $id,
        public readonly int $user_id
    ) {
    }

    public static function from_post( array $post, int $user_id ): self {
        $type = isset( $post['type'] ) ? sanitize_text_field( wp_unslash( $post['type'] ) ) : '';
        $id   = isset( $post['id'] ) ? (int) $post['id'] : 0;

        return new self(
            type: $type,
            id: $id,
            user_id: $user_id
        );
    }
}
