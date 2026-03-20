<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Fixture;

/**
 * Data Transfer Object for fixture reset requests.
 */
readonly class Fixture_Reset_Request {
    public function __construct(
        public int $fixture_id,
        public ?string $modal = null
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
            fixture_id: isset( $post['match_id'] ) ? (int) $post['match_id'] : 0, // In transition, it's still match_id in POST
            modal: isset( $post['modal'] ) ? sanitize_text_field( wp_unslash( $post['modal'] ) ) : null
        );
    }
}
