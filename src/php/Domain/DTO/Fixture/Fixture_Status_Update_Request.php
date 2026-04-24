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
        $suffix     = $this->rubber_number ? '-' . $this->rubber_number : '';

        $this->validate_fixture_id( $error_msgs, $error_flds, $suffix );
        $this->validate_modal( $error_msgs, $error_flds );
        $this->validate_match_status( $error_msgs, $error_flds, $suffix );

        if ( ! empty( $error_msgs ) ) {
            throw new Fixture_Validation_Exception( $error_msgs, $error_flds );
        }
    }

    /**
     * Validate fixture ID.
     *
     * @param string[] $error_msgs
     * @param string[] $error_flds
     * @param string   $suffix
     */
    private function validate_fixture_id( array &$error_msgs, array &$error_flds, string $suffix ): void {
        if ( empty( $this->fixture_id ) ) {
            $error_msgs[] = __( 'Fixture id not supplied', 'racketmanager' );
            $error_flds[] = 'match_id' . $suffix;
        }
    }

    /**
     * Validate modal.
     *
     * @param string[] $error_msgs
     * @param string[] $error_flds
     */
    private function validate_modal( array &$error_msgs, array &$error_flds ): void {
        if ( ! $this->rubber_number && empty( $this->modal ) ) {
            $error_msgs[] = __( 'Modal name not supplied', 'racketmanager' );
            $error_flds[] = 'modal';
        }
    }

    /**
     * Validate match status.
     *
     * @param string[] $error_msgs
     * @param string[] $error_flds
     * @param string   $suffix
     */
    private function validate_match_status( array &$error_msgs, array &$error_flds, string $suffix ): void {
        $field = 'score_status' . $suffix;

        if ( empty( $this->match_status ) ) {
            $error_msgs[] = __( 'No match status selected', 'racketmanager' );
            $error_flds[] = $field;
            return;
        }

        $parts        = explode( '_', $this->match_status );
        $status       = $parts[0];
        $player_ref   = $parts[1] ?? null;

        $this->check_status_validity( $status, $player_ref, $field, $error_msgs, $error_flds );
    }

    /**
     * Check the validity of a specific status.
     *
     * @param string   $status
     * @param ?string  $player_ref
     * @param string   $field
     * @param string[] $error_msgs
     * @param string[] $error_flds
     */
    private function check_status_validity( string $status, ?string $player_ref, string $field, array &$error_msgs, array &$error_flds ): void {
        switch ( $status ) {
            case 'walkover':
            case 'retired':
                if ( 'player1' !== $player_ref && 'player2' !== $player_ref ) {
                    $error_msgs[] = __( 'Score status team selection not valid', 'racketmanager' );
                    $error_flds[] = $field;
                }
                break;
            case 'none':
            case 'abandoned':
            case 'cancelled':
            case 'share':
                break;
            case 'invalid':
                $this->check_invalid_status( $field, $error_msgs, $error_flds );
                break;
            default:
                $error_msgs[] = __( 'Match status not valid', 'racketmanager' );
                $error_flds[] = $field;
                break;
        }
    }

    /**
     * Check if 'invalid' status is allowed.
     *
     * @param string   $field
     * @param string[] $error_msgs
     * @param string[] $error_flds
     */
    private function check_invalid_status( string $field, array &$error_msgs, array &$error_flds ): void {
        if ( ! $this->rubber_number ) {
            $error_msgs[] = __( 'Match status not valid', 'racketmanager' );
            $error_flds[] = $field;
        }
    }
}
