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
            $this->err_msgs[] = __( 'Modal id not supplied', 'racketmanager' );
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
}
