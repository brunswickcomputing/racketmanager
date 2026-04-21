<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Fixture;

use Racketmanager\Exceptions\Fixture_Validation_Exception;

/**
 * Data Transfer Object for fixture status options requests.
 */
readonly class Fixture_Status_Options_Request {
    public function __construct(
        public int $fixture_id,
        public ?string $modal = null,
        public ?string $match_status = null
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
            fixture_id: isset( $post['match_id'] ) ? (int) $post['match_id'] : 0,
            modal: isset( $post['modal'] ) ? sanitize_text_field( wp_unslash( $post['modal'] ) ) : null,
            match_status: isset( $post['match_status'] ) ? sanitize_text_field( wp_unslash( $post['match_status'] ) ) : null
        );
    }

    /**
     * Validate the request.
     *
     * @throws Fixture_Validation_Exception
     */
    public function validate(): void {
        $error_msgs = [];
        $error_flds = [];

        if ( empty( $this->fixture_id ) ) {
            $error_msgs[] = __( 'Match id not found', 'racketmanager' );
            $error_flds[] = 'match_id';
        }

        if ( empty( $this->modal ) ) {
            $error_msgs[] = __( 'Modal name not supplied', 'racketmanager' );
            $error_flds[] = 'modal';
        }

        if ( ! empty( $error_msgs ) ) {
            throw new Fixture_Validation_Exception( $error_msgs, $error_flds );
        }
    }
}
