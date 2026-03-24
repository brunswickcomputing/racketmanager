<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Fixture;

/**
 * Data Transfer Object for team fixture result update requests.
 */
readonly class Team_Result_Update_Request {
    public function __construct(
        public int $match_id,
        public ?string $match_status = null,
        public ?array $rubber_statuses = null,
        public ?array $match_comments = null,
        public ?array $rubber_ids = null,
        public ?array $rubber_types = null,
        public ?array $players = null,
        public ?array $sets = null
    ) {
    }

    /**
     * Create from global $_POST.
     *
     * @param array $post
     *
     * @return self
     */
    public static function from_post( array $post ): self {
        return new self(
            match_id: isset( $post['current_match_id'] ) ? (int) $post['current_match_id'] : 0,
            match_status: isset( $post['new_match_status'] ) ? sanitize_text_field( wp_unslash( $post['new_match_status'] ) ) : null,
            rubber_statuses: $post['match_status'] ?? null,
            match_comments: isset( $post['matchComments'] ) ? [$post['matchComments']] : null,
            rubber_ids: $post['id'] ?? null,
            rubber_types: $post['type'] ?? null,
            players: $post['players'] ?? array(),
            sets: $post['sets'] ?? array()
        );
    }
}
