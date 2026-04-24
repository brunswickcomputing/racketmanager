<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Fixture;

use Racketmanager\Exceptions\Fixture_Validation_Exception;

/**
 * Data Transfer Object for switching home and away teams.
 */
readonly class Fixture_Switch_Teams_Request {
    public function __construct(
        public int $match_id,
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
            match_id: isset( $post['match_id'] ) ? (int) $post['match_id'] : 0,
            modal: isset( $post['modal'] ) ? sanitize_text_field( wp_unslash( $post['modal'] ) ) : null
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

        if ( empty( $this->match_id ) ) {
            $error_msgs[] = __( 'Match id not found', 'racketmanager' );
            $error_flds[] = 'match_id';
        }

        if ( ! empty( $error_msgs ) ) {
            throw new Fixture_Validation_Exception( $error_msgs, $error_flds );
        }
    }
}
