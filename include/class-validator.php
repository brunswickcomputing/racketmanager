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
			$this->error                          = true;
			$this->err_flds[ $this->error_id ] = 'contactno';
			$this->err_msgs[ $this->error_id ]   = __( 'Player id required', 'racketmanager' );
			++$this->error_id;
		} else {
			$player = get_player( $player_id );
			if ( ! $player ) {
				$this->error                          = true;
				$this->err_flds[ $this->error_id ] = 'contactno';
				$this->err_msgs[ $this->error_id ]   = __( 'Player not found', 'racketmanager' );
				++$this->error_id;
			}
		}
		return $this;
	}
	/**
	 * Validate telephone
	 *
	 * @param string $telephone telephone number.
	 *
	 * @return object $validation updated validation object.
	 */
	public function telephone( string $telephone ): object {
		if ( empty( $telephone ) ) {
			$this->error                          = true;
			$this->err_flds[ $this->error_id ] = 'contactno';
			$this->err_msgs[ $this->error_id ]   = __( 'Telephone number required', 'racketmanager' );
			++$this->error_id;
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
	public function email( ?string $email, ?int $player_id, bool $email_required = true ): object {
		if ( empty( $email ) ) {
            if ( $email_required ) {
                $this->error                       = true;
                $this->err_flds[ $this->error_id ] = 'contactemail';
                $this->err_msgs[ $this->error_id ] = __( 'Email address is required', 'racketmanager' );
                ++$this->error_id;
            }
		} else {
			$player = get_player( $email, 'email' );
			if ( $player && $player_id !== $player->ID ) {
				$this->error                       = true;
				$this->err_flds[ $this->error_id ] = 'contactemail';
				$this->err_msgs[ $this->error_id ] = __( 'Email address already used', 'racketmanager' );
				++$this->error_id;
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
                $this->error                          = true;
                $this->err_flds[ $this->error_id ] = 'btm';
                $this->err_msgs[ $this->error_id ]   = __( 'LTA Tennis Number is required', 'racketmanager' );
                ++$this->error_id;
            }
		} else {
			$player = get_player( $btm, 'btm' );
			if ( $player && $player_id !== $player->ID ) {
				$this->error                          = true;
				$this->err_flds[ $this->error_id ] = 'btm';
				$this->err_msgs[ $this->error_id ]   = __( 'LTA Tennis Number already used', 'racketmanager' );
				++$this->error_id;
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
			$this->error                       = true;
			$this->err_flds[ $this->error_id ] = 'season';
			$this->err_msgs[ $this->error_id ] = __( 'Season is required', 'racketmanager' );
			++$this->error_id;
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
			$this->error                       = true;
			$this->err_flds[ $this->error_id ] = 'club';
			$this->err_msgs[ $this->error_id ] = __( 'Club not found', 'racketmanager' );
			++$this->error_id;
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
			$this->error                       = true;
			$this->err_flds[ $this->error_id ] = 'competition';
			$this->err_msgs[ $this->error_id ] = __( 'Competition not found', 'racketmanager' );
			++$this->error_id;
		}
		return $this;
	}
	/**
	 * Validate event
	 *
	 * @param object|null $event event.
	 *
	 * @return object $validation updated validation object.
	 */
	public function event( ?object $event ): object {
		if ( empty( $event ) ) {
			$this->error                       = true;
			$this->err_flds[ $this->error_id ] = 'event';
			$this->err_msgs[ $this->error_id ] = __( 'Event not found', 'racketmanager' );
			++$this->error_id;
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
			$this->error                       = true;
			$this->err_flds[ $this->error_id ] = 'tournament';
			$this->err_msgs[ $this->error_id ] = __( 'Tournament not found', 'racketmanager' );
			++$this->error_id;
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
