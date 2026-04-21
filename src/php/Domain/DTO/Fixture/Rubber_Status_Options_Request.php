<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Fixture;

use Racketmanager\Exceptions\Fixture_Validation_Exception;

/**
 * Data Transfer Object for rubber status options requests.
 */
readonly class Rubber_Status_Options_Request {
    public function __construct(
        public int $rubber_id,
        public ?string $modal = null,
        public ?string $score_status = null
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
            rubber_id: isset( $post['rubber_id'] ) ? (int) $post['rubber_id'] : 0,
            modal: isset( $post['modal'] ) ? sanitize_text_field( wp_unslash( $post['modal'] ) ) : null,
            score_status: isset( $post['score_status'] ) ? sanitize_text_field( wp_unslash( $post['score_status'] ) ) : null
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

        if ( empty( $this->rubber_id ) ) {
            $error_msgs[] = __( 'Rubber id not found', 'racketmanager' );
            $error_flds[] = 'rubber_id';
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
