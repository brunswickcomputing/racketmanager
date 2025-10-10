<?php
/**
 * Club Validation API: Club validator class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Validate
 */

namespace Racketmanager\validator;

use function Racketmanager\get_club_role;
use function Racketmanager\get_player;
use function Racketmanager\get_user;

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
    /**
     * Validate club role
     *
     * @param int|null $club_role_id.
     *
     * @return object $validation updated validation object.
     */
    public function club_role( ?int $club_role_id ): object {
        if ( ! $club_role_id ) {
            $this->error      = true;
            $this->err_flds[] = 'userName';
            $this->err_msgs[] = __( 'Club role not found', 'racketmanager' );
        } else {
            $club_role = get_club_role( $club_role_id );
            if ( ! $club_role ) {
                $this->error      = true;
                $this->err_flds[] = 'userName';
                $this->err_msgs[] = __( 'Club role not found', 'racketmanager' );
            }
        }
        return $this;
    }
    /**
     * Validate user
     *
     * @param int|null $user_id userid.
     * @param string   $error_field field.
     *
     * @return object $validation updated validation object.
     */
    public function user( ?int $user_id, string $error_field = 'userName' ): object {
        if ( ! $user_id ) {
            $this->error      = true;
            $this->err_flds[] = $error_field;
            $this->err_msgs[] = __( 'User must be specified', 'racketmanager' );
        } else {
            $user = get_user( $user_id );
            if ( ! $user ) {
                $this->error      = true;
                $this->err_flds[] = $error_field;
                $this->err_msgs[] = __( 'User not found', 'racketmanager' );
            }
        }
        return $this;
    }
}
