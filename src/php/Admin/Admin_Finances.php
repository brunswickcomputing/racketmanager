<?php
/**
 * RacketManager-Admin API: RacketManager-admin-finances class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManager-Admin-Finances
 */

namespace Racketmanager\Admin;

use Racketmanager\Exceptions\Charge_Not_Deleted_Exception;
use Racketmanager\Exceptions\Charge_Not_Found_Exception;
use Racketmanager\Exceptions\Charge_Not_Updated_Exception;
use Racketmanager\Exceptions\Invoice_Not_Created_Exception;
use Racketmanager\Exceptions\Invoice_Not_Found_Exception;
use Racketmanager\Exceptions\Invoice_Not_Updated_Exception;
use Racketmanager\Services\Validator\Validator_Finance;
use Racketmanager\Util\Util_Messages;
use stdClass;
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
        $finance_invoices  = $this->finance_service->get_invoices_by_criteria( $args );
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
        $finance_invoices  = $this->finance_service->get_invoices_by_criteria( $args );
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
        return $this->finance_service->get_charges_by_criteria( $args );

    }
    /**
     * Display charges page
     */
    public
    function display_charges_page(): void {
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
        if ( isset( $_POST['doChargesDel'] ) && isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) {
            $racketmanager_tab = 'racketmanager-charges';
            $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_charges-bulk' );
            if ( empty( $validator->error ) ) {
                $validator = $validator->capability( 'del_teams' );
            }
            if ( ! empty( $validator->error ) ) {
                $this->set_message( $validator->msg, 'error' );
                $this->show_message();
                return;
            }
            $messages      = array();
            $message_error = false;
            $charges = isset( $_POST['charge'] ) ? array_map( 'intval', $_POST['charge'] ) : array();
            if ( ! empty( $charges ) ) {
                foreach ( $charges as $charge_id ) {
                    try {
                        $deleted = $this->finance_service->remove_charge( $charge_id );
                        if ( $deleted ) {
                            $messages[] = Util_Messages::charge_deleted( $charge_id );
                        } else {
                            $messages[] = Util_Messages::charge_not_deleted( $charge_id );
                        }

                    } catch ( Charge_Not_Found_Exception|Charge_Not_Deleted_Exception $e ) {
                        $messages[] = $e->getMessage();
                        $message_error = true;
                    }
                }
                $message = implode( '<br>', $messages );
                $this->set_message( $message, $message_error );
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
        $finance_charges = $this->finance_service->get_charges_by_criteria( $args );
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
        $edit      = false;
        $charge    = null;
        $charge_id = isset( $_GET['charges'] ) ? intval( $_GET['charges'] ) : null;
        if ( $charge_id ) {
            try {
                $charge = $this->finance_service->get_charge( $charge_id, true );
                $edit   = true;
            } catch( Charge_Not_Found_Exception $e ) {
                $this->set_message( $e->getMessage(), 'error' );
                $this->show_message();
                return;
            }
        }
        if ( isset( $_POST['generateInvoices'] ) ) {
            $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_charges-bulk' );
            if ( ! empty( $validator->error ) ) {
                $this->set_message( $validator->msg, 'error' );
            } else {
                $charge_id = isset( $_POST['charges_id'] ) ? intval( $_POST['charges_id'] ) : null;
                try {
                    $result = $this->finance_service->send_invoices_for_charge( $charge_id, true );
                    if ( $result ) {
                        $this->set_message( __( 'Invoices generated and sent', 'racketmanager' ) );
                    } else {
                        $this->set_message( __( 'Error on invoice sending', 'racketmanager' ), 'error' );
                    }
                } catch ( Charge_Not_Found_Exception|Invoice_Not_Created_Exception $e ) {
                    $this->set_message( $e->getMessage(), 'error' );
                }
                $racketmanager_tab = 'racketmanager-invoices';
            }
        } elseif ( isset( $_POST['saveCharge'] ) ) {
            $update    = null;
            $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_manage-charges' );
            if ( empty( $validator->error ) ) {
                $charge_input = $this->get_charge_input();
                if ( isset( $_POST['editCharge'] ) ) {
                    try {
                        $edit   = true;
                        $result = $this->finance_service->amend_charge( $charge_input, $charge_id );
                        if ( is_wp_error( $result ) ) {
                            $validator->error    = true;
                            $validator->err_flds = $result->get_error_codes();
                            $validator->err_msgs = $result->get_error_messages();
                            $validator->msg = __( 'Error updating charge', 'racketmanager' );
                        } else {
                            $this->set_message( __( 'Charge updated', 'racketmanager' ) );
                            $charge = $result;
                        }
                    } catch ( Charge_Not_Updated_Exception $e ) {
                        $this->set_message( $e->getMessage(), 'warning' );
                    } catch ( Charge_Not_Found_Exception $e ) {
                        $this->set_message( $e->getMessage(), 'error' );
                    }
                } elseif ( isset( $_POST['addCharge'] ) ) {
                    $charge = $charge_input;
                    $result = $this->finance_service->add_charge( $charge_input );
                    if ( is_wp_error( $result ) ) {
                        $validator->error    = true;
                        $validator->err_flds = $result->get_error_codes();
                        $validator->err_msgs = $result->get_error_messages();
                        $validator->msg = __( 'Error adding charge', 'racketmanager' );
                    } else {
                        $this->set_message( __( 'Charge added', 'racketmanager' ) );
                        $edit = true;
                        $charge = $result;
                        $charge_id = $charge->get_id();
                        ?>
                        <script>
                            let url = new URL(window.location.href);
                            url.searchParams.append('charges', <?php echo esc_attr( $charge_id ); ?>);
                            history.pushState('', '', url.toString());
                        </script>
                        <?php
                    }
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
            }
        }
        $this->show_message();
        if ( $edit ) {
            $form_title   = __( 'Edit Charge', 'racketmanager' );
            $form_action  = __( 'Update', 'racketmanager' );
            $club_charges = $this->finance_service->get_charges_for_clubs( $charge_id );
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
     * @return object
     */
    private function get_charge_input(): object {
        $charge = new stdClass();

        $charge->id              = isset( $_POST['charges_id'] ) ? intval( $_POST['charges_id'] ) : null;
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
            } else {
                $invoice_id = isset( $_POST['invoice_id'] ) ? intval( $_POST['invoice_id'] ) : null;
                $status  = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : null;
                $purchase_order = isset( $_POST['purchaseOrder'] ) ? sanitize_text_field( wp_unslash( $_POST['purchaseOrder'] ) ) : null;
                try {
                    $result = $this->finance_service->amend_invoice( $invoice_id, $status, $purchase_order );
                    if ( is_wp_error( $result ) ) {
                        $validator->error = true;
                        $validator->err_flds = $result->get_error_codes();
                        $validator->err_msgs = $result->get_error_messages();
                        $validator->msg = __( 'Error updating invoice', 'racketmanager' );
                    } else {
                        $this->set_message( __( 'Invoice updated', 'racketmanager' ) );
                    }
                } catch ( Invoice_Not_Found_Exception $e ) {
                    $this->set_message( $e->getMessage(), true );
                } catch ( Invoice_Not_Updated_Exception $e ) {
                    $this->set_message( $e->getMessage(), 'warning' );
                }
            }
        }
        $this->show_message();
        $tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'racketmanager-invoices';

        $charge_id  = isset( $_GET['charge'] ) ? intval( $_GET['charge'] ) : null;
        $club_id    = isset( $_GET['club'] ) ? intval( $_GET['club'] ) : null;
        $invoice_id = isset( $_GET['invoice'] ) ? intval( $_GET['invoice'] ) : null;
        try {
            if ( $charge_id && $club_id ) {
                $invoice = $this->finance_service->get_invoice_by_charge_and_club( $charge_id, $club_id );
            } elseif ( $invoice_id ) {
                $invoice = $this->finance_service->get_full_invoice_details( $invoice_id );
            } else {
                throw new Invoice_Not_Found_Exception( Util_Messages::invoice_not_found() );
            }
            $invoice_view = show_invoice( $invoice->invoice->get_id() );
            require_once RACKETMANAGER_PATH . 'templates/admin/finances/invoice.php';
        } catch( Invoice_Not_Found_Exception $e ) {
            $this->set_message( $e->getMessage(), true );
            $this->show_message();
        }
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
    public function get_invoice_actions( string $status, ?int $club_id, ?int $charge_id ): array|bool {
        $validator = new Validator_Finance();
        if ( isset( $_POST['doActionInvoices'] ) && isset( $_POST['action'] ) ) {
            $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_invoices-bulk' );
            if ( ! empty( $validator->error ) ) {
                $this->set_message( $validator->msg, true );
                $this->show_message();
                return false;
            }
        }
        $messages = array();
        $invoices = isset( $_POST['invoice'] ) ? array_map( 'intval', $_POST['invoice'] ) : array();
        $action   = isset( $_POST['action'] ) ? sanitize_text_field( wp_unslash( $_POST['action'] ) ) : null;
        foreach ( $invoices as $invoice_id ) {
            try {
                $messages[] = $this->finance_service->action_invoice( $invoice_id, $action );
            } catch ( Invoice_Not_Found_Exception $e ) {
                $messages[] = $e->getMessage();
            }
        }
        $message = implode( '<br>', $messages );
        $this->set_message( $message );
        $this->show_message();
        $args = array();
        if ( $club_id ) {
            $args['billable'] = $club_id;
        }
        if ( $status ) {
            $args['status'] = $status;
        }
        if ( $charge_id ) {
            $args['charge'] = $charge_id;
        }

        return $args;
    }

}
