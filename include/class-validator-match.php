<?php
/**
 * Match Validation API: Match validator class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Validate
 */

namespace Racketmanager;

/**
 * Class to implement the Match Validator object
 */
final class Validator_Match extends Validator {
    /**
     * Validate modal
     *
     * @param ?string $modal modal name.
     * @param string $error_field error field.
     * @return object $validation updated validation object.
     */
    public function modal( ?string $modal, string $error_field = 'match' ): object {
        if ( empty( $modal ) ) {
            $this->error      = true;
            $this->err_flds[] = $error_field;
            $this->err_msgs[] = __( 'Modal name not supplied', 'racketmanager' );
        }
        return $this;
    }
    /**
     * Validate match
     *
     * @param ?int $match_id match id.
     * @param string $error_field error field.
     * @return object $validation updated validation object.
     */
    public function match( ?int $match_id, string $error_field = 'match' ): object {
        if ( empty( $match_id ) ) {
            $this->error      = true;
            $this->err_flds[] = $error_field;
            $this->err_msgs[] = __( 'Match id not supplied', 'racketmanager' );
        } else {
            $match = get_match( $match_id );
            if ( ! $match ) {
                $this->error      = true;
                $this->err_flds[] = $error_field;
                $this->err_msgs[] = __( 'Match not found', 'racketmanager' );
            }
        }
        return $this;
    }
    /**
     * Validate new match date
     *
     * @param ?string $schedule_date new match_date.
     * @param string $match_date current match date.
     * @return object $validation updated validation object.
     */
    public function scheduled_date( ?string $schedule_date, string $match_date ): object {
        if ( empty( $schedule_date ) ) {
            $this->error      = true;
            $this->err_flds[] = 'schedule-date';
            $this->err_msgs[] = __( 'New date not set', 'racketmanager' );
        } elseif ( $schedule_date === $match_date ) {
            $this->error      = true;
            $this->err_flds[] = 'schedule-date';
            $this->err_msgs[] = __( 'Date not changed', 'racketmanager' );
        }
        return $this;
    }
    /**
     * Validate match status
     *
     * @param ?string $match_status match status.
     * @param string $error_field error field.
     * @return object $validation updated validation object.
     */
    public function match_status( ?string $match_status, string $error_field = 'match' ): object {
        if ( empty( $match_status ) ) {
            $this->error      = true;
            $this->err_flds[] = $error_field;
            $this->err_msgs[] = __( 'No match status selected', 'racketmanager' );
        } else {
            $match_status_values = explode( '_', $match_status );
            $status_value        = $match_status_values[0];
            $player_ref          = $match_status_values[1] ?? null;
            switch ( $status_value ) {
                case 'walkover':
                case 'retired':
                    if ( 'player1' !== $player_ref && 'player2' !== $player_ref ) {
                        $this->error      = true;
                        $this->err_flds[] = $error_field;
                        $this->err_msgs[] = __( 'Score status team selection not valid', 'racketmanager' );
                    }
                    break;
                case 'none':
                case 'abandoned':
                case 'cancelled':
                case 'share':
                    break;
                default:
                    $this->error      = true;
                    $this->err_flds[] = $error_field;
                    $this->err_msgs[] = __( 'Match status not valid', 'racketmanager' );
                    break;
            }
        }
        return $this;
    }
    /**
     * Validate rubber
     *
     * @param ?int $rubber_id rubber id.
     * @param string $error_field error field.
     * @return object $validation updated validation object.
     */
    public function rubber( ?int $rubber_id, string $error_field = 'match' ): object {
        if ( empty( $rubber_id ) ) {
            $this->error      = true;
            $this->err_flds[] = $error_field;
            $this->err_msgs[] = __( 'Rubber id not supplied', 'racketmanager' );
        } else {
            $rubber = get_rubber( $rubber_id );
            if ( ! $rubber ) {
                $this->error      = true;
                $this->err_flds[] = $error_field;
                $this->err_msgs[] = __( 'Rubber not found', 'racketmanager' );
            }
        }
        return $this;
    }
    /**
     * Validate rubber number
     *
     * @param ?int $rubber_number rubber number.
     * @param string $error_field error field.
     * @return object $validation updated validation object.
     */
    public function rubber_number( ?int $rubber_number, string $error_field = 'match' ): object {
        if ( empty( $rubber_number ) ) {
            $this->error      = true;
            $this->err_flds[] = $error_field;
            $this->err_msgs[] = __( 'Rubber number not supplied', 'racketmanager' );
        }
        return $this;
    }
    /**
     * Validate score status
     *
     * @param ?string $score_status score status.
     * @param string $error_field error field.
     * @return object $validation updated validation object.
     */
    public function score_status( ?string $score_status, string $error_field = 'match' ): object {
        if ( empty( $score_status ) ) {
            $this->error      = true;
            $this->err_flds[] = $error_field;
            $this->err_msgs[] = __( 'No match status selected', 'racketmanager' );
        } else {
            $score_status_values = explode( '_', $score_status );
            $status_value        = $score_status_values[0];
            $player_ref = $score_status_values[1] ?? null;
            switch ( $status_value ) {
                case 'walkover':
                case 'retired':
                    if ( 'player1' !== $player_ref && 'player2' !== $player_ref ) {
                        $valid       = false;
                        $err_field[] = 'score_status';
                        $err_msg[]   = __( 'Score status team selection not valid', 'racketmanager' );
                    }
                    break;
                case 'share':
                case 'none':
                case 'invalid':
                case 'abandoned':
                    break;
                default:
                    $valid       = false;
                    $err_field[] = 'score_status';
                    $err_msg[]   = __( 'Score status not valid', 'racketmanager' );
                    break;
            }
        }
        return $this;
    }
}
