<?php
/**
 * RacketManager-Admin API: RacketManager-admin-finances class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManager-Admin-Finances
 */

namespace Racketmanager;

use stdClass;

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
	}
	/**
	 * Display finances page
	 */
	public function display_finances_page(): void {
		if ( ! current_user_can( 'edit_leagues' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			$this->display_charges_page();
		}
	}
	/**
	 * Display club invoices page
	 */
	public function display_club_invoices_page(): void {
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
			$racketmanager_tab = 'club-invoices';
			$args              = $this->get_invoice_actions( $status, $club_id, $charge_id );
			$args['type']      = 'club';
			$finance_invoices = $this->get_invoices( $args );
			include_once RACKETMANAGER_PATH . '/admin/finances/show-invoices.php';
		}
	}
	/**
	 * Display player invoices page
	 */
	public function display_player_invoices_page(): void {
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
			$racketmanager_tab = 'player-invoices';
			$args              = $this->get_invoice_actions( $status, $club_id, $charge_id );
			$args['type'] = 'player';
			$finance_invoices = $this->get_invoices( $args );
			include_once RACKETMANAGER_PATH . '/admin/finances/show-invoices.php';
		}
	}
	/**
	 * Display charges page
	 */
	public function display_charges_page(): void {
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
			$racketmanager_tab = 'charges';
			if ( isset( $_POST['generateInvoices'] ) ) {
				$racketmanager_tab = 'racketmanager-invoices';
				if ( isset( $_POST['charges_id'] ) ) {
					$charge_id = intval( $_POST['charges_id'] );
					$charge    = get_charge( $charge_id );
					if ( $charge ) {
						$schedule_name   = 'rm_send_invoices';
						$schedule_args[] = $charge_id;
						Racketmanager_Util::clear_scheduled_event( $schedule_name, $schedule_args );
						$charge->send_invoices();
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
			}

			$this->printMessage();
			$args             = array();
			if ( $competition_id ) {
				$args['competition'] = $competition_id;
			}
			if ( $season ) {
				$args['season'] = $season;
			}
			$finance_charges = $this->get_charges( $args );

			include_once RACKETMANAGER_PATH . '/admin/finances/show-charges.php';
		}
	}
	/**
	 * Display charges page
	 */
	public function display_charge_page(): void {
		if ( ! current_user_can( 'edit_teams' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			$charges = null;
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
						$this->set_message( __( 'Charge updated', 'racketmanager' ) );
					} else {
						$this->set_message( __( 'No updates', 'racketmanager' ), 'warning' );
					}
				} else {
					$charge                  = new stdClass();
					$charge->competition_id  = empty( $_POST['competition_id'] ) ? null : intval( $_POST['competition_id'] );
					$charge->season          = isset( $_POST['season'] ) ? sanitize_text_field( wp_unslash( $_POST['season'] ) ) : null;
					$charge->status          = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : null;
					$charge->date            = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : null;
					$charge->fee_competition = isset( $_POST['feeClub'] ) ? floatval( $_POST['feeClub'] ) : null;
					$charge->fee_event       = isset( $_POST['feeTeam'] ) ? floatval( $_POST['feeTeam'] ) : null;
					$valid                   = $this->validate_charge( $charge );
					if ( $valid ) {
						$charge = new Racketmanager_Charges( $charge );
						$this->set_message( __( 'Charges added', 'racketmanager' ) );
					} else {
						$this->set_message( __( 'Error with charge creation', 'racketmanager' ), 'error' );
					}
				}
			}
			$this->printMessage();
			$edit = false;
			if ( isset( $_GET['charges'] ) || ! empty( $charges->id ) ) {
				if ( isset( $_GET['charges'] ) ) {
					$charges_id = intval( $_GET['charges'] );
				} else {
					$charges_id = $charges->id;
				}
				$edit    = true;
				$charges = get_charge( $charges_id );

				$form_title  = __( 'Edit Charge', 'racketmanager' );
				$form_action = __( 'Update', 'racketmanager' );
			} else {
				$charges_id               = '';
				$form_title               = __( 'Add Charge', 'racketmanager' );
				$form_action              = __( 'Add', 'racketmanager' );
			}

			include_once RACKETMANAGER_PATH . '/admin/finances/charge.php';
		}
	}
	/**
	 * Display invoice page
	 */
	public function display_invoice_page(): void {
		global $racketmanager;
		if ( ! current_user_can( 'edit_teams' ) ) {
			$racketmanager->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$racketmanager->printMessage();
		} else {
			if ( isset( $_POST['saveInvoice'] ) ) {
				if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_manage-invoice' ) ) {
					$racketmanager->set_message( __( 'Security token invalid', 'racketmanager' ), true );
					$racketmanager->printMessage();
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
			$racketmanager->printMessage();
			if ( isset( $_GET['charge'] ) && isset( $_GET['club'] ) ) {
				$invoice_id = $this->get_invoice( intval( $_GET['charge'] ), intval( $_GET['club'] ) );
			} elseif ( isset( $_GET['invoice'] ) ) {
				$invoice_id = intval( $_GET['invoice'] );
			}
			$tab          = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'racketmanager-invoices';
			$invoice_view = '';
			$billing      = $racketmanager->get_options( 'billing' );
			if ( isset( $invoice_id ) && $invoice_id ) {
				$invoice = get_invoice( $invoice_id );
			}
			if ( isset( $invoice ) && $invoice ) {
				$invoice_view = $invoice->generate();
				include_once RACKETMANAGER_PATH . '/admin/finances/invoice.php';
			} else {
				$racketmanager->set_message( __( 'Invoice not found', 'racketmanager' ), true );
				$racketmanager->printMessage();
			}
		}
	}
	/**
	 * Get Invoice
	 *
	 * @param int $charge charge used by invoice.
	 * @param int $club club for whom invoice is created.
	 * @return int $invoice_id
	 */
	private function get_invoice( int $charge, int $club ): int {
		global $wpdb;

		return $wpdb->get_var( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT `id` FROM $wpdb->racketmanager_invoices WHERE `charge_id` = %d AND `club_id` = %d LIMIT 1",
				$charge,
				$club
			)
		);
	}

	/**
	 * Get invoice actions from screen
	 *
	 * @param string $status
	 * @param int|null $club_id
	 * @param int|null $charge_id
	 *
	 * @return array
	 */
	public function get_invoice_actions( string $status, ?int $club_id, ?int $charge_id ): array {
		if ( isset( $_POST['doActionInvoices'] ) && isset( $_POST['action'] ) && - 1 !== $_POST['action'] ) {
			$racketmanager_tab = 'racketmanager-invoices';
			check_admin_referer( 'invoices-bulk' );
			if ( ! current_user_can( 'del_teams' ) ) {
				$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
			} else {
				$messages = array();
				if ( isset( $_POST['invoice'] ) ) {
					foreach ( $_POST['invoice'] as $invoice_id ) { //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
						$invoice = get_invoice( $invoice_id );
						if ( $invoice->status !== $_POST['action'] ) {
							$new_status = sanitize_text_field( wp_unslash( $_POST['action'] ) );
							if ( $new_status ) {
								$invoice->set_status( $new_status );
								$messages[] = __( 'Invoice', 'racketmanager' ) . ' ' . $invoice->invoice_number . ' ' . __( 'updated', 'racketmanager' );
							}
						}
					}
					$message = implode( '<br>', $messages );
					$this->set_message( $message );
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

		return $args;
	}

	/**
	 * Validate charge
	 *
	 * @param object $charge charge object
	 *
	 * @return bool
	 *
	 */
	private function validate_charge( object $charge ): bool {
		global $racketmanager;
		if ( empty ( $charge->competition_id ) ) {
			$racketmanager->error_messages[] = __( 'Competition must be set', 'racketmanager' );
			$racketmanager->error_fields[]   = 'competition_id';
		}
		if ( empty( $charge->season ) ) {
			$racketmanager->error_messages[] = __( 'Season must be set', 'racketmanager' );
			$racketmanager->error_fields[]   = 'season';
		}
		if ( empty( $charge->status ) ) {
			$racketmanager->error_messages[] = __( 'Status must be set', 'racketmanager' );
			$racketmanager->error_fields[]   = 'status';
		}
		if ( empty( $charge->date ) ) {
			$racketmanager->error_messages[] = __( 'Date must be set', 'racketmanager' );
			$racketmanager->error_fields[]   = 'date';
		}
		if ( empty( $racketmanager->error_fields ) ) {
			return true;
		} else {
			return false;
		}
	}
}
