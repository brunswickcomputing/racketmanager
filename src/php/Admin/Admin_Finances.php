<?php
/**
 * RacketManager-Admin API: RacketManager-admin-finances class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManager-Admin-Finances
 */

namespace Racketmanager\Admin;

use Racketmanager\Domain\Charge;
use Racketmanager\Services\Validator\Validator_Finance;
use Racketmanager\Util\Util;
use stdClass;
use function Racketmanager\get_charge;
use function Racketmanager\get_invoice;
use function Racketmanager\show_invoice;

/**
 * RacketManager finances administration functions
 * Class to implement RacketManager Administration Finances panel
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManagerAdmin
 */
final class Admin_Finances extends Admin_Display {
    /**
     * Function to handle administration finances displays
     *
     * @param string|null $view
     *
     * @return void
     */
    public function handle_display( ?string $view ): void {
        if ( 'charges' === $view ) {
            $this->display_charges_page();
        } elseif ( 'club-invoices' === $view ) {
            $this->display_club_invoices_page();
        } elseif ( 'player-invoices' === $view ) {
            $this->display_player_invoices_page();
        } elseif ( 'invoice' === $view ) {
            $this->display_invoice_page();
        } elseif ( 'charge' === $view ) {
            $this->display_charge_page();
        } else {
            $this->display_finances_page();
        }
    }
    /**
     * Display finances page
     */
    public function display_finances_page(): void {
        if ( ! current_user_can( 'edit_leagues' ) ) {
            $this->set_message( $this->invalid_permissions, true );
            $this->show_message();
        } else {
            $this->display_charges_page();
        }
    }
    /**
     * Display club invoices page
     */
    public function display_club_invoices_page(): void {
        $validator = new Validator_Finance();
        $validator = $validator->capability( 'edit_leagues' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, 'error' );
            $this->show_message();
            return;
        }
        $players           = '';
        $competition_id    = isset( $_GET['competition'] ) ? intval( $_GET['competition'] ) : null;
        $season            = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
        $club_id           = isset( $_GET['club'] ) ? intval( $_GET['club'] ) : null;
        $charge_id         = isset( $_GET['charge'] ) ? intval( $_GET['charge'] ) : null;
        $status            = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : 'open';
        $racketmanager_tab = 'club-invoices';
        $args              = $this->get_invoice_actions( $status, $club_id, $charge_id );
        $args['type']      = 'club';
        $finance_invoices  = $this->racketmanager->get_invoices( $args );
        $clubs             = $this->club_service->get_clubs();
        $charges           = $this->get_finance_charges_for_invoices( 'team' );
        require_once RACKETMANAGER_PATH . 'templates/admin/finances/show-invoices.php';
    }
    /**
     * Display player invoices page
     */
    public function display_player_invoices_page(): void {
        $validator = new Validator_Finance();
        $validator = $validator->capability( 'edit_leagues' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, 'error' );
            $this->show_message();
            return;
        }
        $competition_id    = isset( $_GET['competition'] ) ? intval( $_GET['competition'] ) : null;
        $season            = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
        $club_id           = isset( $_GET['club'] ) ? intval( $_GET['club'] ) : null;
        $charge_id         = isset( $_GET['charge'] ) ? intval( $_GET['charge'] ) : null;
        $status            = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : 'open';
        $racketmanager_tab = 'player-invoices';
        $args              = $this->get_invoice_actions( $status, $club_id, $charge_id );
        $args['type']      = 'player';
        $finance_invoices  = $this->racketmanager->get_invoices( $args );
        $charges           = $this->get_finance_charges_for_invoices( 'player' );
        require_once RACKETMANAGER_PATH . 'templates/admin/finances/show-invoices.php';
    }

    /**
     * Get charges for invoices
     *
     * @param $type
     *
     * @return array
     */
    private function get_finance_charges_for_invoices( $type ): array {
        $args = array();
        $args['entry'] = $type;
        $args['orderby'] = array(
                'season'         => 'DESC',
                'competition_id' => 'ASC',
        );
        return $this->racketmanager->get_charges( $args );

    }
    /**
     * Display charges page
     */
    public function display_charges_page(): void {
        $validator = new Validator_Finance();
        $validator = $validator->capability( 'edit_leagues' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, 'error' );
            $this->show_message();
            return;
        }
        $players = '';
        $competition_id    = isset( $_GET['competition'] ) ? intval( $_GET['competition'] ) : null;
        $season            = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
        $club_id           = isset( $_GET['club'] ) ? intval( $_GET['club'] ) : null;
        $charge_id         = isset( $_GET['charge'] ) ? intval( $_GET['charge'] ) : null;
        $status            = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : 'open';
        $racketmanager_tab = 'charges';
        if ( isset( $_POST['generateInvoices'] ) ) {
            $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_charges-bulk' );
            if ( ! empty( $validator->error ) ) {
                $this->set_message( $validator->msg, 'error' );
            } else {
                if ( isset( $_POST['competition_id'] ) ) {
                    $competition_id = intval( $_POST['competition_id'] );
                }
                if ( isset( $_POST['season'] ) ) {
                    $season = sanitize_text_field( wp_unslash( $_POST['season'] ) );
                }
                $racketmanager_tab = 'racketmanager-invoices';
                if ( isset( $_POST['charges_id'] ) ) {
                    $charge_id = intval( $_POST['charges_id'] );
                    $charge    = get_charge( $charge_id );
                    if ( $charge ) {
                        $schedule_name   = 'rm_send_invoices';
                        $schedule_args[] = $charge_id;
                        Util::clear_scheduled_event( $schedule_name, $schedule_args );
                        $charge->send_invoices();
                    }
                }
            }
        } elseif ( isset( $_POST['doChargesDel'] ) && isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) {
            $racketmanager_tab = 'racketmanager-charges';
            $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_charges-bulk' );
            if ( empty( $validator->error ) ) {
                $validator = $validator->capability( 'del_teams' );
            }
            if ( ! empty( $validator->error ) ) {
                $this->set_message( $validator->msg, 'error' );
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

        $this->show_message();
        $args             = array();
        if ( $competition_id ) {
            $args['competition'] = $competition_id;
        }
        if ( $season ) {
            $args['season'] = $season;
        }
        $finance_charges = $this->racketmanager->get_charges( $args );
        $competitions    = $this->competition_service->get_all();
        require_once RACKETMANAGER_PATH . 'templates/admin/finances/show-charges.php';
    }
    /**
     * Display charges page
     */
    public function display_charge_page(): void {
        $validator = new Validator_Finance();
        $validator = $validator->capability( 'edit_teams' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, 'error' );
            $this->show_message();
            return;
        }
        $edit       = false;
        $charges    = null;
        $charges_id = isset( $_GET['charges'] ) ?  intval( $_GET['charges'] ) : null;
        if ( $charges_id ) {
            $edit      = true;
            $validator = $validator->charge( $charges_id );
            if ( ! empty( $validator->error ) ) {
                $this->set_message( $validator->err_msgs[0], true );
                $this->show_message();
                return;
            }
            $charges = get_charge( $charges_id );
        }
        if ( isset( $_POST['saveCharge'] ) ) {
            $update    = null;
            $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_manage-charges' );
            if ( empty( $validator->error ) ) {
                if ( isset( $_POST['editCharge'] ) ) {
                    $charges_id = isset( $_POST['charges_id'] ) ? intval( $_POST['charges_id'] ) : null;
                    $validator  = $validator->compare( $charges_id, $charges_id );
                    $update     = true;
                } elseif ( isset( $_POST['addCharge'] ) ) {
                    $update = false;
                } else {
                    $validator->error = true;
                    $validator->msg   = __( 'Invalid action', 'racketmanager' );
                }
            }
            if ( ! empty( $validator->error ) ) {
                if ( empty( $validator->msg ) ) {
                    $msg = $validator->err_msgs[0];
                } else {
                    $msg = $validator->msg;
                }
                $this->set_message( $msg, true );
            } else {
                if ( $update ) {
                    $charge_input = $this->get_charge_input( $charges );
                    $validator    = $this->validate_charge( $charge_input );
                    if ( empty( $validator->error ) ) {
                        $updates = $charges->update( $charge_input );
                        if ( $updates ) {
                            $this->set_message( __( 'Charge updated', 'racketmanager' ) );
                        } else {
                            $this->set_message( $this->no_updates, 'warning' );
                        }
                    } else {
                        $charges = $charge_input;
                        $this->set_message( __( 'Error updating charge', 'racketmanager' ), true );
                    }
                } else {
                    $charges   = $this->get_charge_input();
                    $validator = $this->validate_charge( $charges );
                    if ( empty( $validator->error ) ) {
                        $charges = new Charge( $charges );
                        $edit   = true;
                        ?>
                        <script>
                            let url = new URL(window.location.href);
                            url.searchParams.append('charges', <?php echo esc_attr( $charges->id ); ?>);
                            history.pushState('', '', url.toString());
                        </script>
                        <?php
                        $this->set_message( __( 'Charge added', 'racketmanager' ) );
                    } else {
                        $this->set_message( __( 'Error with charge creation', 'racketmanager' ), 'error' );
                    }
                }
            }
        }
        $this->show_message();
        if ( $edit ) {
            $form_title   = __( 'Edit Charge', 'racketmanager' );
            $form_action  = __( 'Update', 'racketmanager' );
            $club_charges = $charges->get_club_entries();
        } else {
            $form_title  = __( 'Add Charge', 'racketmanager' );
            $form_action = __( 'Add', 'racketmanager' );
        }
        $competitions    = $this->competition_service->get_all();
        require_once RACKETMANAGER_PATH . 'templates/admin/finances/charge.php';
    }

    /**
     * Function to get charge inout data from the screen
     *
     * @param object|null $charge charge object.
     *
     * @return object
     */
    private function get_charge_input( ?object $charge = null ): object {
        if ( empty( $charge ) ) {
            $charge = new stdClass();
        } else {
            $charge = clone $charge;
        }
        $charge->competition_id  = empty( $_POST['competition_id'] ) ? null : intval( $_POST['competition_id'] );
        $charge->season          = isset( $_POST['season'] ) ? sanitize_text_field( wp_unslash( $_POST['season'] ) ) : null;
        $charge->status          = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : null;
        $charge->date            = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : null;
        $charge->fee_competition = isset( $_POST['feeClub'] ) ? floatval( $_POST['feeClub'] ) : null;
        $charge->fee_event       = isset( $_POST['feeTeam'] ) ? floatval( $_POST['feeTeam'] ) : null;
        return $charge;
    }
    /**
     * Display invoice page
     */
    public function display_invoice_page(): void {
        $validator = new Validator_Finance();
        $validator = $validator->capability( 'edit_leagues' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, 'error' );
            $this->show_message();
            return;
        }
        if ( isset( $_POST['saveInvoice'] ) ) {
            $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_manage-invoice' );
            if ( ! empty( $validator->error ) ) {
                $this->set_message( $validator->msg, true );
            } elseif ( isset( $_POST['invoice_id'] ) ) {
                $invoice = get_invoice( intval( $_POST['invoice_id'] ) );
                $updates = false;
                if ( isset( $_POST['status'] ) && $invoice->status !== $_POST['status'] ) {
                    $invoice_update = $invoice->set_status( sanitize_text_field( wp_unslash( $_POST['status'] ) ) );
                    if ( $invoice_update ) {
                        $updates = true;
                    }
                }
                if ( isset( $_POST['purchaseOrder'] ) && $invoice->purchase_order !== $_POST['purchaseOrder'] ) {
                    $invoice_update = $invoice->set_purchase_order( sanitize_text_field( wp_unslash( $_POST['purchaseOrder'] ) ) );
                    if ( $invoice_update ) {
                        $updates = true;
                    }
                }
                if ( $updates ) {
                    $this->set_message( __( 'Invoice updated', 'racketmanager' ) );
                } else {
                    $this->set_message( $this->no_updates, true );
                }
            }
        }
        $this->show_message();
        if ( isset( $_GET['charge'] ) && isset( $_GET['club'] ) ) {
            $invoice_id = $this->get_invoice( intval( $_GET['charge'] ), intval( $_GET['club'] ) );
        } elseif ( isset( $_GET['invoice'] ) ) {
            $invoice_id = intval( $_GET['invoice'] );
        }
        $tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'racketmanager-invoices';
        if ( isset( $invoice_id ) && $invoice_id ) {
            $invoice = get_invoice( $invoice_id );
        }
        if ( isset( $invoice ) && $invoice ) {
            $invoice_view = show_invoice( $invoice->id );
            require_once RACKETMANAGER_PATH . 'templates/admin/finances/invoice.php';
        } else {
            $this->set_message( __( 'Invoice not found', 'racketmanager' ), true );
            $this->show_message();
        }
    }
    /**
     * Get Invoice
     *
     * @param int $charge charge used by invoice.
     * @param int $club club for whom invoice is created.
     * @return null|int $invoice_id
     */
    private function get_invoice( int $charge, int $club ): ?int {
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
     * Get invoice actions from the screen
     *
     * @param string $status
     * @param int|null $club_id
     * @param int|null $charge_id
     *
     * @return array
     */
    public function get_invoice_actions( string $status, ?int $club_id, ?int $charge_id ): array {
        $validator = new Validator_Finance();
        if ( isset( $_POST['doActionInvoices'] ) && isset( $_POST['action'] ) ) {
            $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_invoices-bulk' );
            if ( ! empty( $validator->error ) ) {
                $this->set_message( $validator->msg, true );
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
        $this->show_message();
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
     * @param object $charge charge object.
     *
     * @return object
     *
     */
    private function validate_charge( object $charge ): object {
        $validator = new Validator_Finance();
        $validator = $validator->competition( $charge->competition_id );
        $validator = $validator->season( $charge->season );
        $validator = $validator->status( $charge->status );
        $validator = $validator->date( $charge->date );
        return $validator->get_details();
    }
}
