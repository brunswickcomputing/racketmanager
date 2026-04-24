<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Fixture;

use Racketmanager\Exceptions\Fixture_Validation_Exception;

/**
 * Data Transfer Object for fixture date update requests.
 */
readonly class Fixture_Date_Update_Request {
    public function __construct(
        public int $match_id,
        public ?string $schedule_date = null,
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
            schedule_date: isset( $post['schedule-date'] ) ? sanitize_text_field( wp_unslash( $post['schedule-date'] ) ) : null,
            modal: isset( $post['modal'] ) ? sanitize_text_field( wp_unslash( $post['modal'] ) ) : null
        );
    }

    /**
     * Validate the request.
     *
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

        if ( empty( $this->schedule_date ) ) {
            $error_msgs[] = __( 'Schedule date not supplied', 'racketmanager' );
            $error_flds[] = 'schedule-date';
        }

        // Simplified validation for now, mirroring Validator_Fixture behaviour if needed
        // In a real scenario, we'd add more checks (e.g. date format, range, etc.)

        if ( ! empty( $error_msgs ) ) {
            throw new Fixture_Validation_Exception( $error_msgs, $error_flds );
        }
    }
}
