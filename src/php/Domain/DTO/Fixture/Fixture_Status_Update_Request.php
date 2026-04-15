<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Fixture;

use Racketmanager\Exceptions\Fixture_Validation_Exception;

/**
 * Data Transfer Object for fixture status update requests.
 */
readonly class Fixture_Status_Update_Request {
    public function __construct(
        public int $fixture_id,
        public ?string $match_status = null,
        public ?string $modal = null,
        public ?int $rubber_number = null
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
            match_status: isset( $post['score_status'] ) ? sanitize_text_field( wp_unslash( $post['score_status'] ) ) : null,
            modal: isset( $post['modal'] ) ? sanitize_text_field( wp_unslash( $_POST['modal'] ) ) : null,
            rubber_number: isset( $post['rubber_number'] ) ? (int) $post['rubber_number'] : null
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
        $error_field_suffix = $this->rubber_number ? '-' . $this->rubber_number : '';
        $score_status_field = 'score_status' . $error_field_suffix;

        if ( empty( $this->fixture_id ) ) {
            $error_msgs[] = __( 'Match id not supplied', 'racketmanager' );
            $error_flds[] = 'match_id' . $error_field_suffix;
        }

        if ( ! $this->rubber_number && empty( $this->modal ) ) {
            $error_msgs[] = __( 'Modal name not supplied', 'racketmanager' );
            $error_flds[] = 'modal';
        }

        if ( empty( $this->match_status ) ) {
            $error_msgs[] = __( 'No match status selected', 'racketmanager' );
            $error_flds[] = $score_status_field;
        } else {
            $match_status_values = explode( '_', $this->match_status );
            $status_value        = $match_status_values[0];
            $player_ref          = $match_status_values[1] ?? null;
            switch ( $status_value ) {
                case 'walkover':
                case 'retired':
                    if ( 'player1' !== $player_ref && 'player2' !== $player_ref ) {
                        $error_msgs[] = __( 'Score status team selection not valid', 'racketmanager' );
                        $error_flds[] = $score_status_field;
                    }
                    break;
                case 'none':
                case 'abandoned':
                case 'cancelled':
                case 'share':
                    break;
                case 'invalid':
                    if ( ! $this->rubber_number ) {
                        $error_msgs[] = __( 'Match status not valid', 'racketmanager' );
                        $error_flds[] = $score_status_field;
                    }
                    break;
                default:
                    $error_msgs[] = __( 'Match status not valid', 'racketmanager' );
                    $error_flds[] = $score_status_field;
                    break;
            }
        }

        if ( ! empty( $error_msgs ) ) {
            throw new Fixture_Validation_Exception( $error_msgs, $error_flds );
        }
    }
}
