<?php
/**
 * Tournament Validation API: Tournament validator class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Validate
 */

namespace Racketmanager;

/**
 * Class to implement the Tournament Validator object
 */
final class Validator_Tournament extends Validator_Config {
    /**
     * Validate name
     *
     * @param string|null $name name.
     *
     * @return object $validation updated validation object.
     */
    public function name( ?string $name ): object {
        if ( ! $name ) {
            $this->error      = true;
            $this->err_flds[] = 'tournamentName';
            $this->err_msgs[] = __( 'Name must be specified', 'racketmanager' );
        }

        return $this;
    }
/*
if ( empty( $tournament->name ) ) {
$valid     = false;
$err_msg[] = __( 'Name is required', 'racketmanager' );
$err_fld[] = 'tournamentName';
}
if ( empty( $tournament->competition_id ) ) {
    $valid     = false;
    $err_msg[] = __( 'Competition is required', 'racketmanager' );
    $err_fld[] = 'competition_id';
}
if ( empty( $tournament->season ) ) {
    $valid     = false;
    $err_msg[] = __( 'Season is required', 'racketmanager' );
    $err_fld[] = 'season';
}
if ( empty( $tournament->venue ) ) {
    $valid     = false;
    $err_msg[] = __( 'Venue is required', 'racketmanager' );
    $err_fld[] = 'venue';
}
if ( empty( $tournament->grade ) ) {
    $valid     = false;
    $err_msg[] = __( 'Grade is required', 'racketmanager' );
    $err_fld[] = 'grade';
}
if ( empty( $tournament->num_entries ) ) {
    $valid     = false;
    $err_msg[] = __( 'Number of entries is required', 'racketmanager' );
    $err_fld[] = 'num_entries';
}
if ( empty( $tournament->date_open ) ) {
    $valid     = false;
    $err_msg[] = __( 'Opening date is required', 'racketmanager' );
    $err_fld[] = 'dateOpen';
}
if ( empty( $tournament->date_closing ) ) {
    $valid     = false;
    $err_msg[] = __( 'Closing date is required', 'racketmanager' );
    $err_fld[] = 'dateClose';
} elseif ( ! empty( $tournament->date_open ) && $tournament->date_closing <= $tournament->date_open ) {
    $valid     = false;
    $err_msg[] = __( 'Closing date must be after open date', 'racketmanager' );
    $err_fld[] = 'dateClose';
}
if ( empty( $tournament->date_withdrawal ) ) {
    $valid     = false;
    $err_msg[] = __( 'Withdrawal date is required', 'racketmanager' );
    $err_fld[] = 'dateWithdraw';
} elseif ( ! empty( $tournament->date_closing ) && $tournament->date_withdrawal <= $tournament->date_closing ) {
    $valid     = false;
    $err_msg[] = __( 'Withdrawal date must be after closing date', 'racketmanager' );
    $err_fld[] = 'dateClose';
}
if ( empty( $tournament->date_start ) ) {
    $valid     = false;
    $err_msg[] = __( 'Start date is required', 'racketmanager' );
    $err_fld[] = 'dateStart';
} elseif ( ! empty( $tournament->date_withdrawal ) && $tournament->date_start <= $tournament->date_withdrawal ) {
    $valid     = false;
    $err_msg[] = __( 'Start date must be after withdrawal date', 'racketmanager' );
    $err_fld[] = 'dateStart';
}
if ( empty( $tournament->date ) ) {
    $valid     = false;
    $err_msg[] = __( 'End date is required', 'racketmanager' );
    $err_fld[] = 'dateEnd';
} elseif ( ! empty( $tournament->date_start ) && $tournament->date <= $tournament->date_start ) {
    $valid     = false;
    $err_msg[] = __( 'End date must be after start date', 'racketmanager' );
    $err_fld[] = 'dateEnd';
}
*/
}
