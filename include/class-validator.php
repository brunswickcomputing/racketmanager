<?php
/**
 * Validator API: player class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Validator
 */

namespace Racketmanager;

use stdClass;

/**
 * Class to implement the Validator object
 */
class Validator {
    /**
     * Error indicator
     *
     * @var boolean
     */
    public bool $error;
    /**
     * Error field
     *
     * @var array
     */
    public array $err_flds;
    /**
     * Error message
     *
     * @var array
     */
    public array $err_msgs;
    /**
     * Error id
     *
     * @var int
     */
    public int $error_id;
    /**
     * Constructor
     */
    public function __construct() {
        $this->error    = false;
        $this->err_flds = array();
        $this->err_msgs = array();
        $this->error_id = 0;
    }
    /**
     * Validate player
     *
     * @param int $player_id player id.
     *
     * @return object $validation updated validation object.
     */
    public function player( int $player_id ): object {
        if ( empty( $player_id ) ) {
            $this->error      = true;
            $this->err_flds[] = 'contactno';
            $this->err_msgs[] = __( 'Player id required', 'racketmanager' );
        } else {
            $player = get_player( $player_id );
            if ( ! $player ) {
                $this->error      = true;
                $this->err_flds[] = 'contactno';
                $this->err_msgs[] = __( 'Player not found', 'racketmanager' );
            }
        }
        return $this;
    }
    /**
     * Validate first name
     *
     * @param string|null $first_name first name.
     *
     * @return object $validation updated validation object.
     */
    public function first_name( ?string $first_name ): object {
        if ( empty( $first_name ) ) {
            $this->error      = true;
            $this->err_flds[] = 'firstname';
            $this->err_msgs[] = __( 'First name is required', 'racketmanager' );
        }
        return $this;
    }
    /**
     * Validate surname
     *
     * @param string|null $surname surname.
     *
     * @return object $validation updated validation object.
     */
    public function surname( ?string $surname ): object {
        if ( empty( $surname ) ) {
            $this->error      = true;
            $this->err_flds[] = 'surname';
            $this->err_msgs[] = __( 'Surname is required', 'racketmanager' );
        }
        return $this;
    }
    /**
     * Validate gender
     *
     * @param string|null $gender gender.
     *
     * @return object $validation updated validation object.
     */
    public function gender( ?string $gender ): object {
        if ( empty( $gender ) ) {
            $this->error      = true;
            $this->err_flds[] = 'gender';
            $this->err_msgs[] = __( 'Gender is required', 'racketmanager' );
        }
        return $this;
    }
    /**
     * Validate telephone
     *
     * @param string|null $telephone telephone number.
     *
     * @return object $validation updated validation object.
     */
    public function telephone( ?string $telephone ): object {
        if ( empty( $telephone ) ) {
            $this->error      = true;
            $this->err_flds[] = $err_field;
            $this->err_msgs[] = __( 'Telephone number required', 'racketmanager' );
        }
        return $this;
    }
    /**
     * Validate email
     *
     * @param string|null $email email address.
     * @param int|null    $player_id player id.
     * @param bool   $email_required is email address required.
     *
     * @return object $validation updated validation object.
     */
    public function email( ?string $email, ?int $player_id, bool $email_required = true, ?string $field_ref = null ): object {
        $err_field = 'contactemail';
        if ( $field_ref ) {
            $err_field .= '-' . $field_ref;
        }
        if ( empty( $email ) ) {
            if ( $email_required ) {
                $this->error      = true;
                $this->err_flds[] = $err_field;
                $this->err_msgs[] = __( 'Email address is required', 'racketmanager' );
            }
        } else {
            $player = get_player( $email, 'email' );
            if ( $player && $player_id !== $player->ID ) {
                $this->error      = true;
                $this->err_flds[] = $field_ref;
                $this->err_msgs[] = __( 'Email address already used', 'racketmanager' );
            }
        }
        return $this;
    }
    /**
     * Validate btm
     *
     * @param int|null $btm lta tennis number.
     * @param int|null $player_id player id.
     *
     * @return object $validation updated validation object.
     */
    public function btm( ?int $btm, ?int $player_id ): object {
        $btm_required = is_lta_number_required();
        if ( empty( $btm ) ) {
            if ( $btm_required ) {
                $this->error      = true;
                $this->err_flds[] = 'btm';
                $this->err_msgs[] = __( 'LTA Tennis Number is required', 'racketmanager' );
            }
        } else {
            $player = get_player( $btm, 'btm' );
            if ( $player && $player_id !== $player->ID ) {
                $this->error      = true;
                $this->err_flds[] = 'btm';
                $this->err_msgs[] = __( 'LTA Tennis Number already used', 'racketmanager' );
            }
        }
        return $this;
    }
    /**
     * Validate season
     *
     * @param string|null $season season.
     *
     * @return object $validation updated validation object.
     */
    public function season( ?string $season ): object {
        if ( empty( $season ) ) {
            $this->error      = true;
            $this->err_flds[] = 'season';
            $this->err_msgs[] = __( 'Season is required', 'racketmanager' );
        }
        return $this;
    }
    /**
     * Validate club
     *
     * @param string|null $club club.
     *
     * @return object $validation updated validation object.
     */
    public function club( ?string $club ): object {
        if ( empty( $club ) ) {
            $this->error      = true;
            $this->err_flds[] = 'club';
            $this->err_msgs[] = __( 'Club not found', 'racketmanager' );
        }
        return $this;
    }
    /**
     * Validate competition
     *
     * @param string|null $competition competition.
     *
     * @return object $validation updated validation object.
     */
    public function competition( ?string $competition ): object {
        if ( empty( $competition ) ) {
            $this->error      = true;
            $this->err_flds[] = 'competition';
            $this->err_msgs[] = __( 'Competition not found', 'racketmanager' );
        }
        return $this;
    }
    /**
     * Validate event
     *
     * @param object|int|null $event event.
     *
     * @return object $validation updated validation object.
     */
    public function event( object|int|null $event ): object {
        if ( empty( $event ) ) {
            $this->error      = true;
            $this->err_flds[] = 'event';
            $this->err_msgs[] = __( 'Event id not found', 'racketmanager' );
            $this->status     = 404;
        } else {
            if ( is_int( $event ) ) {
                $event = get_event( $event );
                if ( ! $event ) {
                    $this->error      = true;
                    $this->err_flds[] = 'event';
                    $this->err_msgs[] = __( 'Event not found', 'racketmanager' );
                    $this->status     = 404;
                }
            }
        }
        return $this;
    }
    /**
     * Validate tournament
     *
     * @param string|null $tournament tournament.
     *
     * @return object $validation updated validation object.
     */
    public function tournament( ?string $tournament ): object {
        if ( empty( $tournament ) ) {
            $this->error      = true;
            $this->err_flds[] = 'tournament';
            $this->err_msgs[] = __( 'Tournament not found', 'racketmanager' );
        }
        return $this;
    }
    public function get_details(): object {
        $return           = new stdClass();
        $return->error    = $this->error;
        $return->err_flds = $this->err_flds;
        $return->err_msgs = $this->err_msgs;
        return $return;
    }
}
