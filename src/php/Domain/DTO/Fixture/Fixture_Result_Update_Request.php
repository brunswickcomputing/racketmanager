<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Fixture;

/**
 * Data Transfer Object for single fixture result update requests.
 */
readonly class Fixture_Result_Update_Request {
	public function __construct(
		public int $fixture_id,
		public ?array $sets = null,
		public ?string $match_status = null,
		public ?string $confirmed = null
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
			fixture_id: isset( $post['current_match_id'] ) ? (int) $post['current_match_id'] : 0,
			sets: $post['sets'] ?? null,
			match_status: isset( $post['match_status'] ) ? sanitize_text_field( wp_unslash( $post['match_status'] ) ) : null,
			confirmed: isset( $post['confirmed'] ) ? sanitize_text_field( wp_unslash( $post['confirmed'] ) ) : null
		);
	}
}
