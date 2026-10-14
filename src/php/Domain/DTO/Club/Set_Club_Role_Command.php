<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Club;

/**
 * Command DTO for assigning a club role and contact details.
 */
readonly class Set_Club_Role_Command {
    public function __construct(
        public ?int $club_id,
        public ?int $user_id,
        public ?int $club_role_id,
        public ?string $contact_no,
        public ?string $email
    ) {
    }

    /**
     * @param array<string, mixed> $post
     */
    public static function from_post( array $post ): self {
        return new self(
            club_id: isset( $post['clubId'] ) ? intval( $post['clubId'] ) : null,
            user_id: isset( $post['userId'] ) ? intval( $post['userId'] ) : null,
            club_role_id: isset( $post['clubRoleId'] ) ? intval( $post['clubRoleId'] ) : null,
            contact_no: isset( $post['contactno'] ) ? sanitize_text_field( wp_unslash( $post['contactno'] ) ) : null,
            email: isset( $post['contactemail'] ) ? sanitize_text_field( wp_unslash( $post['contactemail'] ) ) : null
        );
    }
}
