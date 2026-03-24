<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Fixture;

/**
 * Data Transfer Object for team fixture result confirmation requests.
 */
readonly class Team_Result_Confirmation_Request {
    public function __construct(
        public int $match_id,
        public ?string $result_confirm = null,
        public ?string $confirm_comments = null,
        public ?bool $result_home = null,
        public ?bool $result_away = null
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
            result_confirm: isset( $post['resultConfirm'] ) ? sanitize_text_field( wp_unslash( $post['resultConfirm'] ) ) : null,
            confirm_comments: isset( $post['resultConfirmComments'] ) ? sanitize_text_field( wp_unslash( $post['resultConfirmComments'] ) ) : '',
            result_home: isset( $post['result_home'] ) ? true : null,
            result_away: isset( $post['result_away'] ) ? true : null
        );
    }
}
