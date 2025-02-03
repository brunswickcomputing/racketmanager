<?php
/**
 * RacketManager-Admin API: RacketManager-admin-finances class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManager-Admin-Finances
 */

namespace Racketmanager;

/**
 * RacketManager finances administration functions
 * Class to implement RacketManager Administration Finances panel
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManagerAdmin
 */
final class RacketManager_Admin_Finances extends RacketManager_Admin {

	/**
	 * Constructor
	 */
	public function __construct() {
		global $racketmanager_ajax_admin;
		parent::__construct();
	}
	/**
	 * Display finances page
	 */
	public function display_finances_page() {
		global $racketmanager;

		$players = '';

		if ( ! current_user_can( 'edit_leagues' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			$competition_id    = isset( $_GET['competition'] ) ? intval( $_GET['competition'] ) : null;
			$season            = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
			$club_id           = isset( $_GET['club'] ) ? intval( $_GET['club'] ) : null;
			$charge_id         = isset( $_GET['charge'] ) ? intval( $_GET['charge'] ) : null;
			$status            = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : 'open';
			$racketmanager_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'racketmanager-charges';
			if ( isset( $_POST['generateInvoices'] ) ) {
				$racketmanager_tab = 'racketmanager-invoices';
				if ( isset( $_POST['charges_id'] ) ) {
					$charge_id = intval( $_POST['charges_id'] );
					$charge    = get_charge( $charge_id );
					if ( $charge ) {
						$schedule_name   = 'rm_send_invoices';
						$schedule_args[] = intval( $charge_id );
						Racketmanager_Util::clear_scheduled_event( $schedule_name, $schedule_args );
						$charge->send_invoices( $charge_id );
					}
				}
			} elseif ( isset( $_POST['doChargesDel'] ) && isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) {
				$racketmanager_tab = 'racketmanager-charges';
				check_admin_referer( 'charges-bulk' );
				if ( ! current_user_can( 'del_teams' ) ) {
					$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
				} else {
					$messages      = array();
					$message_error = false;
					if ( isset( $_POST['charge'] ) ) {
						foreach ( $_POST['charge'] as $charges_id ) { //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
							$charge     = get_charge( $charges_id );
							$charge_ref = ucfirst( $charge->competition->name ) . ' ' . $charge->season;
							if ( $charge->has_invoices() ) {
								$messages[]    = $charge_ref . ' ' . __( 'not deleted - still has invoices attached', 'racketmanager' );
								$message_error = true;
							} else {
								$charge->delete();
								$messages[] = $charge_ref . ' ' . __( 'deleted', 'racketmanager' );
							}
						}
						$message = implode( '<br>', $messages );
						$this->set_message( $message, $message_error );
					}
				}
			} elseif ( isset( $_POST['doActionInvoices'] ) && isset( $_POST['action'] ) && -1 !== $_POST['action'] ) {
				$racketmanager_tab = 'racketmanager-invoices';
				check_admin_referer( 'invoices-bulk' );
				if ( ! current_user_can( 'del_teams' ) ) {
					$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
				} else {
					$messages      = array();
					$message_error = false;
					if ( isset( $_POST['invoice'] ) ) {
						foreach ( $_POST['invoice'] as $invoice_id ) { //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
							$invoice = get_invoice( $invoice_id );
							if ( $invoice->status !== $_POST['action'] ) {
								$status = isset( $_POST['action'] ) ? sanitize_text_field( wp_unslash( $_POST['action'] ) ) : null;
								if ( $status ) {
									$invoice->set_status( $status );
									$messages[] = __( 'Invoice', 'racketmanager' ) . ' ' . $invoice->invoice_number . ' ' . __( 'updated', 'racketmanager' );
								}
							}
						}
						$message = implode( '<br>', $messages );
						$this->set_message( $message, $message_error );
					}
				}
			}

			$this->printMessage();
			$args = array();
			if ( $club_id ) {
				$args['club'] = $club_id;
			}
			if ( $status ) {
				$args['status'] = $status;
			}
			if ( $charge_id ) {
				$args['charge'] = $charge_id;
			}
			$finance_invoices = $this->get_invoices( $args );
			$args             = array();
			if ( $competition_id ) {
				$args['competition'] = $competition_id;
			}
			if ( $season ) {
				$args['season'] = $season;
			}
			$finance_charges = $this->get_charges( $args );

			include_once RACKETMANAGER_PATH . '/admin/show-finances.php';
		}
	}

	/**
	 * Display charges page
	 */
	public function display_charges_page() {
		global $racketmanager, $racketmanager_shortcodes;

		if ( ! current_user_can( 'edit_teams' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			if ( isset( $_POST['saveCharges'] ) ) {
				if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_manage-charges' ) ) {
					$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
					$this->printMessage();
					return;
				}
				if ( isset( $_POST['charges_id'] ) && '' !== $_POST['charges_id'] ) {
					$charges = get_charge( intval( $_POST['charges_id'] ) );
					$updates = false;
					if ( isset( $_POST['feeClub'] ) && $charges->fee_competition !== $_POST['feeClub'] ) {
						$charges->set_club_fee( floatval( $_POST['feeClub'] ) );
						$updates = true;
					}
					if ( isset( $_POST['feeTeam'] ) && $charges->fee_event !== $_POST['feeTeam'] ) {
						$charges->set_team_fee( floatval( $_POST['feeTeam'] ) );
						$updates = true;
					}
					if ( isset( $_POST['status'] ) && $charges->status !== $_POST['status'] ) {
						$charges->set_status( sanitize_text_field( wp_unslash( $_POST['status'] ) ) );
						$updates = true;
					}
					if ( isset( $_POST['competitionType'] ) && $charges->competition_type !== $_POST['competitionType'] ) {
						$charges->set_competition_type( sanitize_text_field( wp_unslash( $_POST['competitionType'] ) ) );
						$updates = true;
					}
					if ( isset( $_POST['type'] ) && $charges->type !== $_POST['type'] ) {
						$charges->set_type( sanitize_text_field( wp_unslash( $_POST['type'] ) ) );
						$updates = true;
					}
					if ( isset( $_POST['date'] ) && $charges->date !== $_POST['date'] ) {
						$charges->set_date( sanitize_text_field( wp_unslash( $_POST['date'] ) ) );
						$updates = true;
					}
					if ( isset( $_POST['season'] ) && $charges->season !== $_POST['season'] ) {
						$charges->set_season( sanitize_text_field( wp_unslash( $_POST['season'] ) ) );
						$updates = true;
					}
					if ( $updates ) {
						$this->set_message( __( 'Charges updated', 'racketmanager' ) );
					} else {
						$this->set_message( __( 'No updates', 'racketmanager' ), 'warning' );
					}
				} else {
					$charges                  = new \stdClass();
					$charges->competition_id  = isset( $_POST['competition_id'] ) ? sanitize_text_field( wp_unslash( $_POST['competition_id'] ) ) : null;
					$charges->season          = isset( $_POST['season'] ) ? sanitize_text_field( wp_unslash( $_POST['season'] ) ) : null;
					$charges->status          = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : null;
					$charges->date            = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : null;
					$charges->fee_competition = isset( $_POST['feeClub'] ) ? floatval( $_POST['feeClub'] ) : null;
					$charges->fee_event       = isset( $_POST['feeTeam'] ) ? floatval( $_POST['feeTeam'] ) : null;
					$charges                  = new Racketmanager_Charges( $charges );
					$this->set_message( __( 'Charges added', 'racketmanager' ) );
				}
			}
			$this->printMessage();
			$edit = false;
			if ( isset( $_GET['charges'] ) || ( isset( $charges->id ) && '' !== $charges->id ) ) {
				if ( isset( $_GET['charges'] ) ) {
					$charges_id = intval( $_GET['charges'] );
				} else {
					$charges_id = $charges->id;
				}
				$edit    = true;
				$charges = get_charge( $charges_id );

				$form_title  = __( 'Edit Charges', 'racketmanager' );
				$form_action = __( 'Update', 'racketmanager' );
			} else {
				$charges_id               = '';
				$form_title               = __( 'Add Charges', 'racketmanager' );
				$form_action              = __( 'Add', 'racketmanager' );
				$charges                  = new \stdclass();
				$charges->competition_id  = '';
				$charges->id              = '';
				$charges->season          = '';
				$charges->date            = '';
				$charges->status          = '';
				$charges->fee_competition = '';
				$charges->fee_event       = '';
			}

			include_once RACKETMANAGER_PATH . '/admin/finances/charge.php';
		}
	}

	/**
	 * Display invoice page
	 */
	public function display_invoice_page() {
		global $racketmanager;

		if ( ! current_user_can( 'edit_teams' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			if ( isset( $_POST['saveInvoice'] ) ) {
				if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_manage-invoice' ) ) {
					$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
					$this->printMessage();
					return;
				}
				if ( isset( $_POST['invoice_id'] ) ) {
					$invoice = get_invoice( intval( $_POST['invoice_id'] ) );
					$updates = false;
					if ( isset( $_POST['status'] ) && $invoice->status !== $_POST['status'] ) {
						$updates = $invoice->set_status( sanitize_text_field( wp_unslash( $_POST['status'] ) ) );
					}
					if ( $updates ) {
						$this->set_message( __( 'Invoice updated', 'racketmanager' ) );
					} else {
						$this->set_message( __( 'No updates', 'racketmanager' ), true );
					}
				}
			}
			$this->printMessage();
			if ( isset( $_GET['charge'] ) && isset( $_GET['club'] ) ) {
				$invoice_id = $this->get_invoice( intval( $_GET['charge'] ), intval( $_GET['club'] ) );
			} elseif ( isset( $_GET['invoice'] ) ) {
				$invoice_id = intval( $_GET['invoice'] );
			}
			$tab          = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'racketmanager-invoices';
			$invoice_view = '';
			$billing      = $this->get_options( 'billing' );
			if ( isset( $invoice_id ) && $invoice_id ) {
				$invoice = get_invoice( $invoice_id );
			}
			if ( isset( $invoice ) && $invoice ) {
				$invoice_view = $invoice->generate();
				include_once RACKETMANAGER_PATH . '/admin/finances/invoice.php';
			} else {
				$this->set_message( __( 'Invoice not found', 'racketmanager' ), true );
				$this->printMessage();
			}
		}
	}
	/**
	 * Get Invoice
	 *
	 * @param int $charge charge used by invoice.
	 * @param int $club club for who invocie is created.
	 * @return int $invoice_id
	 */
	private function get_invoice( $charge, $club ) {
		global $wpdb;

		return $wpdb->get_var( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT `id` FROM {$wpdb->racketmanager_invoices} WHERE `charge_id` = %d AND `club_id` = %d LIMIT 1",
				$charge,
				$club
			)
		);
	}
}
