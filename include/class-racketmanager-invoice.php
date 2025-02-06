<?php
/**
 * Racketmanager_Invoice API: invoice class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Invoice
 */

namespace Racketmanager;

/**
 * Class to implement the invoice object
 */
final class Racketmanager_Invoice {
	/**
	 * Id
	 *
	 * @var int
	 */
	public $id;
	/**
	 * Invoice number
	 *
	 * @var int
	 */
	public $invoice_number;
	/**
	 * Club
	 *
	 * @var object
	 */
	public $club;
	/**
	 * Club id
	 *
	 * @var int
	 */
	public $club_id;
	/**
	 * Player
	 *
	 * @var object
	 */
	public $player;
	/**
	 * Player id
	 *
	 * @var int
	 */
	public $player_id;
	/**
	 * Charge
	 *
	 * @var object
	 */
	public $charge;
	/**
	 * Charge id
	 *
	 * @var int
	 */
	public $charge_id;
	/**
	 * Status
	 *
	 * @var string
	 */
	public $status;
	/**
	 * Invoice date
	 *
	 * @var string
	 */
	public $date;
	/**
	 * Date due
	 *
	 * @var string
	 */
	public $date_due;
	/**
	 * Amount
	 *
	 * @var string
	 */
	public $amount;
	/**
	 * Payment ref
	 *
	 * @var string
	 */
	public $payment_reference;
	/**
	 * Get class instance
	 *
	 * @param int $invoice_id id.
	 */
	public static function get_instance( $invoice_id ) {
		global $wpdb;
		if ( ! $invoice_id ) {
			return false;
		}
		$invoice = wp_cache_get( $invoice_id, 'invoice' );

		if ( ! $invoice ) {
			$invoice = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT `id`, `charge_id`, `club_id`, `player_id`, `status`, `invoiceNumber` as `invoice_number`, `date`, `date_due`, `amount`, `payment_reference` FROM {$wpdb->racketmanager_invoices} WHERE `id` = %d LIMIT 1",
					$invoice_id
				)
			);  // db call ok.

			if ( ! $invoice ) {
				return false;
			}

			$invoice = new Racketmanager_Invoice( $invoice );

			wp_cache_set( $invoice->id, $invoice, 'invoice' );
		}

		return $invoice;
	}

	/**
	 * Construct class instance
	 *
	 * @param object $invoice invoice object.
	 */
	public function __construct( $invoice = null ) {
		$this->racketmanager = Racketmanager::get_instance();
		if ( ! is_null( $invoice ) ) {
			foreach ( get_object_vars( $invoice ) as $key => $value ) {
				$this->$key = $value;
			}

			if ( ! isset( $this->id ) ) {
				$this->add();
			}
			if ( !empty( $this->club_id ) ) {
				$this->club   = get_club( $this->club_id );
			}
			if ( !empty( $this->player_id ) ) {
				$this->player   = get_player( $this->player_id );
			}
			$this->charge = get_charge( $this->charge_id );
		}
	}

	/**
	 * Add new invoice
	 */
	private function add() {
		global $racketmanager, $wpdb;
		$this->status = 'new';
		$billing      = $racketmanager->get_options( 'billing' );
		if ( $billing ) {
			$date_due = new \DateTime( $this->date );
			if ( isset( $billing['paymentTerms'] ) && intval( $billing['paymentTerms'] ) !== 0 ) {
				$date_interval = intval( $billing['paymentTerms'] );
				$date_interval = 'P' . $date_interval . 'D';
				$date_due->add( new \DateInterval( $date_interval ) );
			}
			$this->date_due       = $date_due->format( 'Y-m-d' );
			$this->invoice_number = $billing['invoiceNumber'];
		}
		if ( ! empty( $this->invoice_number ) ) {
			if ( empty( $this->player_id ) ) {
				$result = $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						"INSERT INTO {$wpdb->racketmanager_invoices} (`charge_id`, `club_id`, `status`, `invoiceNumber`, `date`, `date_due`) VALUES (%d, %d, %s, %d, %s, %s)",
						$this->charge_id,
						$this->club_id,
						$this->status,
						$this->invoice_number,
						$this->date,
						$this->date_due
					)
				);
			} else {
				$result = $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						"INSERT INTO {$wpdb->racketmanager_invoices} (`charge_id`, `player_id`, `status`, `invoiceNumber`, `date`, `date_due`) VALUES (%d, %d, %s, %d, %s, %s)",
						$this->charge_id,
						$this->player_id,
						$this->status,
						$this->invoice_number,
						$this->date,
						$this->date_due
					)
				);
			}
			if ( $result ) {
				$this->id                            = $wpdb->insert_id;
				$billing['invoiceNumber']           += 1;
				$racketmanager->set_options( 'billing', $billing );
			}
		}
	}
	/**
	 * Delete invoice
	 */
	public function delete() {
		global $wpdb;
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE FROM {$wpdb->racketmanager_invoices} WHERE `id` = %d",
				$this->id
			)
		);
		wp_cache_delete( $this->id, 'invoice' );
	}
	/**
	 * Set invoice amount
	 *
	 * @param string $amount amount value.
	 */
	public function set_amount( $amount ) {
		global $wpdb;
		$this->amount = $amount;
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"UPDATE {$wpdb->racketmanager_invoices} set `amount` = %d WHERE `id` = %d",
				$this->amount,
				$this->id
			)
		);  // db call ok.
		wp_cache_set( $this->id, $this, 'invoice' );
		return true;
	}
	/**
	 * Set invoice status
	 *
	 * @param string $status status value.
	 */
	public function set_status( $status ) {
		global $wpdb;

		if ( 'resent' === $status ) {
			$email = $this->send( $status );
			if ( ! $email ) {
				return false;
			}
		}
		$this->status = $status;
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"UPDATE {$wpdb->racketmanager_invoices} set `status` = %s WHERE `id` = %d",
				$this->status,
				$this->id
			)
		);  // db call ok.
		wp_cache_set( $this->id, $this, 'invoice' );
		return true;
	}
	/**
	 * Set payment reference status
	 *
	 * @param string $reference reference value.
	 */
	public function set_payment_reference( $reference ) {
		global $wpdb;

		$this->payment_reference = $reference;
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"UPDATE {$wpdb->racketmanager_invoices} set `payment_reference` = %s WHERE `id` = %d",
				$this->payment_reference,
				$this->id
			)
		);  // db call ok.
		wp_cache_set( $this->id, $this, 'invoice' );
		return true;
	}
	/**
	 * Generate invoice
	 */
	public function generate() {
		global $racketmanager_shortcodes, $racketmanager;
		$charge  = get_charge( $this->charge );
		if ( empty( $this->club ) ) {
			$target        = get_player( $this->player );
			$target->name  = $this->player->display_name;
			$entry         = $charge->get_player_entry( $target );
			$args          = array();
			$paid_amount   = 0;
			$args['player'] = $target->id;
			$args['charge'] = $charge->id;
			$args['status'] = 'paid';
			$args['before'] = $this->id;
			$prev_invoices = $racketmanager->get_invoices( $args );
			foreach ( $prev_invoices as $invoice ) {
				$paid_amount += $invoice->amount;
			}
			$entry->paid = $paid_amount;
		} else {
			$target = get_club( $this->club );
			$entry  = $charge->get_club_entry( $target );
		}
		$billing = $this->racketmanager->get_options( 'billing' );
		return $racketmanager_shortcodes->load_template(
			'invoice',
			array(
				'organisation_name' => $this->racketmanager->site_name,
				'invoice'           => $this,
				'entry'             => $entry,
				'target'              => $target,
				'billing'           => $billing,
				'invoice_number'    => $this->invoice_number,
			)
		);
	}

	/**
	 * Send invoice
	 *
	 * @param boolean $resend resend indicator.
	 */
	public function send( $resend = false ) {
		global $racketmanager_shortcodes, $racketmanager;

		$billing    = $racketmanager->get_options( 'billing' );
		$headers    = array();
		$from_email = $racketmanager->get_confirmation_email( $this->charge->competition->type );
		if ( $from_email ) {
			$headers[]         = 'From: ' . ucfirst( $this->charge->competition->type ) . 'Secretary <' . $from_email . '>';
			$headers[]         = 'cc: ' . ucfirst( $this->charge->competition->type ) . 'Secretary <' . $from_email . '>';
			$organisation_name = $racketmanager->site_name;
			$headers[]         = 'cc: Treasurer <' . $billing['billingEmail'] . '>';
			$action_url        = $racketmanager->site_url . '/invoice/' . $this->id . '/';
			$email_to          = $this->club->match_secretary_name . ' <' . $this->club->match_secretary_email . '>';
			$email_subject     = $racketmanager->site_name . ' - ' . ucfirst( $this->charge->competition->name ) . ' ' . $this->charge->season . ' Entry Fees Invoice - ' . $this->club->name;
			$email_message     = $racketmanager_shortcodes->load_template(
				'send-invoice',
				array(
					'email_subject' => $email_subject,
					'action_url'    => $action_url,
					'organisation'  => $organisation_name,
					'invoice'       => $this,
					'invoiceView'   => $this->generate(),
					'resend'        => $resend,
					'from_email'    => $from_email,
				),
				'email'
			);
			wp_mail( $email_to, $email_subject, $email_message, $headers );
			return true;
		} else {
			return false;
		}
	}
}
