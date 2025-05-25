<?php
/**
 * Racketmanager_Validator API: player class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Validator
 */

namespace Racketmanager;

/**
 * Class to implement the Validator object
 */
class Racketmanager_Validator {
	/**
	 * Error indicator
	 *
	 * @var boolean
	 */
	public $error;
	/**
	 * Error field
	 *
	 * @var array
	 */
	public $error_field;
	/**
	 * Error message
	 *
	 * @var array
	 */
	public $error_msg;
	/**
	 * Error id
	 *
	 * @var int
	 */
	public $error_id;
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->error       = false;
		$this->error_field = array();
		$this->error_msg   = array();
		$this->error_id    = 0;
	}
	/**
	 * Validate player
	 *
	 * @param int $player_id player id.
	 * @return object $validation updated validation object.
	 */
	public function player( $player_id ) {
		if ( empty( $player_id ) ) {
			$this->error                          = true;
			$this->error_field[ $this->error_id ] = 'contactno';
			$this->error_msg[ $this->error_id ]   = __( 'Player id required', 'racketmanager' );
			++$this->error_id;
		} else {
			$player = get_player( $player_id );
			if ( ! $player ) {
				$this->error                          = true;
				$this->error_field[ $this->error_id ] = 'contactno';
				$this->error_msg[ $this->error_id ]   = __( 'Player not found', 'racketmanager' );
				++$this->error_id;
			}
		}
		return $this;
	}
	/**
	 * Validate telephone
	 *
	 * @param string $telephone telephone number.
	 * @return object $validation updated validation object.
	 */
	public function telephone( $telephone ) {
		if ( empty( $telephone ) ) {
			$this->error                          = true;
			$this->error_field[ $this->error_id ] = 'contactno';
			$this->error_msg[ $this->error_id ]   = __( 'Telephone number required', 'racketmanager' );
			++$this->error_id;
		}
		return $this;
	}
	/**
	 * Validate email
	 *
	 * @param string $email email address.
	 * @param int    $player_id player id.
	 * @return object $validation updated validation object.
	 */
	public function email( $email, $player_id ) {
		if ( empty( $email ) ) {
			$this->error                          = true;
			$this->error_field[ $this->error_id ] = 'contactemail';
			$this->error_msg[ $this->error_id ]   = __( 'Email address is required', 'racketmanager' );
			++$this->error_id;
		} else {
			$player = get_player( $email, 'email' );
			if ( $player ) {
				if ( intval( $player_id ) !== intval( $player->ID ) ) {
					$this->error                          = true;
					$this->error_field[ $this->error_id ] = 'contactemail';
					$this->error_msg[ $this->error_id ]   = __( 'Email address already used', 'racketmanager' );
					++$this->error_id;
				}
			}
		}
		return $this;
	}
	/**
	 * Validate btm
	 *
	 * @param int $btm lta tennis number.
	 * @param int $player_id player id.
	 * @return object $validation updated validation object.
	 */
	public function btm( $btm, $player_id ) {
		if ( empty( $btm ) ) {
			$this->error                          = true;
			$this->error_field[ $this->error_id ] = 'btm';
			$this->error_msg[ $this->error_id ]   = __( 'LTA Tennis Number is required', 'racketmanager' );
			++$this->error_id;
		} else {
			$player = get_player( $btm, 'btm' );
			if ( $player ) {
				if ( intval( $player_id ) !== intval( $player->ID ) ) {
					$this->error                          = true;
					$this->error_field[ $this->error_id ] = 'btm';
					$this->error_msg[ $this->error_id ]   = __( 'LTA Tennis Number already used', 'racketmanager' );
					++$this->error_id;
				}
			}
		}
		return $this;
	}
	/**
	 * Validate season
	 *
	 * @param string $season season.
	 * @return object $validation updated validation object.
	 */
	public function season( $season ) {
		if ( empty( $season ) ) {
			$this->error                          = true;
			$this->error_field[ $this->error_id ] = 'season';
			$this->error_msg[ $this->error_id ]   = __( 'Season is required', 'racketmanager' );
			++$this->error_id;
		}
		return $this;
	}
	/**
	 * Validate club
	 *
	 * @param string $club club.
	 * @return object $validation updated validation object.
	 */
	public function club( $club ) {
		if ( empty( $club ) ) {
			$this->error                          = true;
			$this->error_field[ $this->error_id ] = 'club';
			$this->error_msg[ $this->error_id ]   = __( 'Club not found', 'racketmanager' );
			++$this->error_id;
		}
		return $this;
	}
	/**
	 * Validate competition
	 *
	 * @param string $competition competition.
	 * @return object $validation updated validation object.
	 */
	public function competition( $competition ) {
		if ( empty( $competition ) ) {
			$this->error                          = true;
			$this->error_field[ $this->error_id ] = 'competition';
			$this->error_msg[ $this->error_id ]   = __( 'Competition not found', 'racketmanager' );
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
			$this->error                          = true;
			$this->error_field[ $this->error_id ] = 'event';
			$this->error_msg[ $this->error_id ]   = __( 'Event not found', 'racketmanager' );
			++$this->error_id;
		}
		return $this;
	}
	/**
	 * Validate tournament
	 *
	 * @param string $tournament tournament.
	 * @return object $validation updated validation object.
	 */
	public function tournament( $tournament ) {
		if ( empty( $tournament ) ) {
			$this->error                          = true;
			$this->error_field[ $this->error_id ] = 'tournament';
			$this->error_msg[ $this->error_id ]   = __( 'Tournament not found', 'racketmanager' );
			++$this->error_id;
		}
		return $this;
	}
}
