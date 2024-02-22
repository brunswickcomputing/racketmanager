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
					"SELECT `id`, `charge_id`, `club_id`, `status`, `invoiceNumber` as `invoice_number`, `date`, `date_due` FROM {$wpdb->racketmanager_invoices} WHERE `id` = %d LIMIT 1",
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
		if ( ! is_null( $invoice ) ) {
			foreach ( get_object_vars( $invoice ) as $key => $value ) {
				$this->$key = $value;
			}

			if ( ! isset( $this->id ) && isset( $this->invoice_number ) ) {
				$this->add();
			}

			$this->club   = get_club( $this->club_id );
			$this->charge = get_charges( $this->charge_id );
		}
	}

	/**
	 * Add new invoice
	 */
	private function add() {
		global $wpdb;

		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
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
		$this->id = $wpdb->insert_id;
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
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->racketmanager_invoices} set `status` = %s WHERE `id` = %d",
				$status,
				$this->id
			)
		);  // db call ok.
		wp_cache_delete( $this->id, 'invoice' );
		return true;
	}

	/**
	 * Generate invoice
	 */
	public function generate() {
		global $racketmanager_shortcodes, $racketmanager;
		$charge  = get_charges( $this->charge );
		$club    = get_club( $this->club );
		$entry   = $charge->get_club_entry( $club );
		$billing = $racketmanager->get_options( 'billing' );
		return $racketmanager_shortcodes->load_template(
			'invoice',
			array(
				'organisation_name' => $racketmanager->site_name,
				'invoice'           => $this,
				'entry'             => $entry,
				'club'              => $club,
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
