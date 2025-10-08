<?php
/**
 * Club Validation API: Club validator class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Validate
 */

namespace Racketmanager\validator;

use function Racketmanager\get_player;

/**
 * Class to implement the Club Validator object
 */
final class Validator_Club extends Validator {
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
            $this->err_flds[] = 'club';
            $this->err_msgs[] = __( 'Name must be specified', 'racketmanager' );
        }
        return $this;
    }
    /**
     * Validate shortcode
     *
     * @param string|null $shortcode shortcode.
     *
     * @return object $validation updated validation object.
     */
    public function short_code( ?string $shortcode ): object {
        if ( ! $shortcode ) {
            $this->error      = true;
            $this->err_flds[] = 'shortcode';
            $this->err_msgs[] = __( 'Shortcode must be specified', 'racketmanager' );
        }
        return $this;
    }
    /**
     * Validate address
     *
     * @param string|null $address address.
     *
     * @return object $validation updated validation object.
     */
    public function address( ?string $address ): object {
        if ( ! $address ) {
            $this->error      = true;
            $this->err_flds[] = 'address';
            $this->err_msgs[] = __( 'Address must be specified', 'racketmanager' );
        }
        return $this;
    }
    /**
     * Validate match secretary
     *
     * @param int|null $match_secretary.
     * @param string   $error_field field.
     *
     * @return object $validation updated validation object.
     */
    public function match_secretary( ?int $match_secretary, string $error_field = 'match_secretary' ): object {
        if ( ! $match_secretary ) {
            $this->error      = true;
            $this->err_flds[] = $error_field;
            $this->err_msgs[] = __( 'Match secretary must be specified', 'racketmanager' );
        } else {
            $player = get_player( $match_secretary );
            if ( ! $player ) {
                $this->error      = true;
                $this->err_flds[] = $error_field;
                $this->err_msgs[] = __( 'Match secretary not found', 'racketmanager' );
            }
        }
        return $this;
    }
}
