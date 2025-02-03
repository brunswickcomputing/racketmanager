<?php
/**
 * Racketmanager_Stripe API: stripe class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Stripe
 */

namespace Racketmanager;

/**
 * Class to implement the Stripe object
 */
final class Racketmanager_Stripe {

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
	 * Constructor
	 */
	public function __construct() {
		global $racketmanager;
		$billing = $racketmanager->get_options( 'billing' );
		if ( $billing ) {
			$this->currency = isset( $billing['billingCurrency'] ) ? $billing['billingCurrency'] : null;
			$this->is_live  = isset( $billing['is_live'] ) ? $billing['is_live'] : false;
			if ( $this->is_live ) {
				$this->api_publishable_key = isset( $billing['api_publishable_key_live'] ) ? $billing['api_publishable_key_live'] : null;
				$this->api_secret_key      = isset( $billing['api_secret_key_live'] ) ? $billing['api_secret_key_live'] : null;
			} else {
				$this->api_publishable_key = isset( $billing['api_publishable_key_test'] ) ? $billing['api_publishable_key_test'] : null;
				$this->api_secret_key      = isset( $billing['api_secret_key_test'] ) ? $billing['api_secret_key_test'] : null;
			}
		}
	}
}
