<?php
/**
 * Racketmanager_Schedule_Round_Robin API: stripe class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Schedule
 */

namespace Racketmanager;

/**
 * Class to implement the Racketmanager_Schedule_Round_Robin object
 */
final class Racketmanager_Schedule_Round_Robin {

	/**
	 * Currency
	 *
	 * @var string
	 */
	public $currency;
	/**
	 * Is live indicator
	 *
	 * @var boolean
	 */
	public $is_live = false;
	/**
	 * Api_publishable_key
	 *
	 * @var string
	 */
	public $api_publishable_key;
	/**
	 * Api_psecret_key
	 *
	 * @var string
	 */
	public $api_secret_key;
	/**
	 * Api endpoint secret
	 *
	 * @var string
	 */
	public $api_endpoint_key;
	/**
	 * Constructor
	 */
	public function __construct() {
	}
	/**
	 * Generate
	 *
	 * @param int     $num_teams number of teams.
	 * @param int     $num_rounds numer of rounds.
	 * @param boolean $home_away home away indicator.
	 * @return array of rounds
	 */
	public function generate( $num_teams, $num_rounds, $home_away ) {
		if ( $num_teams % 2 !== 0 ) {
			++$num_teams;
		}
		$this->num_rounds = $num_rounds;
		$this->home_away  = $home_away;
		$this->num_teams  = $num_teams;
		$this->teams      = range( 1, $num_teams );
		$this->half       = (int)($this->num_teams / 2);
		$indexes          = $this->teams;
		$rounds           = [];
		$round_num        = 1;
		// Generate the 1st Round
		$round = $this->generate_round( $round_num, $indexes );
		$rounds[ $round_num - 1 ] = array( 'fixtures' => $round );
		if ( $this->home_away ) {
			$rounds[ $round_num + $num_rounds - 1 ] = array( 'fixtures' => $this->set_reverse_round( $round ) );
		}
		// Generate the remaining rounds
		for ( $round_num = 2; $round_num < $this->num_teams; $round_num++ ) {
			// Remove and save the constant index
			$constant = array_splice( $indexes, $this->num_teams - 1, 1 )[0];
			// Move the first half of the list to the end of it
			$first_half = array_splice( $indexes, 0, $this->half );
			$indexes    = array_merge( $indexes, $first_half );
			// Add the constant index
			$indexes[] = $constant;
			// Generate the round
			$round = $this->generate_round( $round_num, $indexes );
			$rounds[ $round_num - 1 ] = array( 'fixtures' => $round );
			if ( $this->home_away ) {
				$rounds[ $round_num + $num_rounds - 1 ] = array( 'fixtures' => $this->set_reverse_round( $round ) );
			}
		}
		return $rounds;
	}
	/**
	 * Generate Individual round
	 *
	 * @param int   $round round number.
	 * @param array $indexes index of teams.
	 * @return array of fixtures
	 */
	private function generate_round( $round, $indexes ) {
		$fixtures    = [];
		$start       = 0;
		$fixture_num = 1;
		// In even round the highest index is home
		if ( $round % 2 === 0 ) {
			$pos         = $indexes[ $this->num_teams -1 ] - 1;
			$home        = $this->teams[ $pos ];
			$pos         = $indexes[0] - 1;
			$away        = $this->teams[ $pos ];
			$fixtures[ $fixture_num ]  = array( 'home' => $home, 'away' => $away );
			++$start;
			++$fixture_num;
		}
		for ( $i = $start; $i < $this->half; $i++ ) {
			$pos        = $indexes[ $i ] - 1;
			$home       = $this->teams[ $pos ];
			$pos        = $indexes[ $this->num_teams - 1 - $i ] - 1;
			$away       = $this->teams[ $pos ];
			$fixtures[ $fixture_num ] = array( 'home' => $home, 'away' => $away );
			++$fixture_num;
		}
		return $fixtures;
	}
	/**
	 * set reverse round
	 *
	 * @param array $round round detils.
	 * @return array of fixtures
	 */
	private function set_reverse_round( $round ) {
		$reverse_fixtures = array();
		foreach ( $round as $fixture_num => $fixture ) {
			$home = $fixture['home'];
			$away = $fixture['away'];
			$reverse_fixtures[ $fixture_num ] = array( 'home' => $away, 'away' => $home );
		}
		return $reverse_fixtures;
	}
}
