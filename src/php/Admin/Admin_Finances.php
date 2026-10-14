<?php
/**
 * RacketManager-Admin API: RacketManager-admin-finances class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManager-Admin-Finances
 */

declare( strict_types=1 );

namespace Racketmanager\Admin;

use Racketmanager\Admin\View_Models\Error_Bag;
use Racketmanager\Admin\View_Models\Finances_Charge_Page_View_Model;
use Racketmanager\Admin\View_Models\Finances_Charges_Page_View_Model;
use Racketmanager\Admin\View_Models\Finances_Invoice_Page_View_Model;
use Racketmanager\Admin\View_Models\Finances_Invoices_Page_View_Model;
use Racketmanager\Exceptions\Charge_Not_Found_Exception;
use Racketmanager\Exceptions\Invoice_Not_Found_Exception;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Action_Adapter;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Screen_Presenter;
use Racketmanager\Services\Admin\Finance\Finance_Charge_Action_Dispatcher;
use Racketmanager\Services\Admin\Finance\Finance_Invoice_Action_Dispatcher;
use Racketmanager\Services\Validator\Validator_Finance;
use Racketmanager\Util\Util_Messages;
use stdClass;
use function Racketmanager\show_invoice;

/**
 * RacketManager finances administration functions
 * Class to implement RacketManager Administration Finances panel
 */
final class Admin_Finances extends Admin_Display {

    private Admin_Action_Adapter $actions;
    private Admin_Screen_Presenter $screens;
    private Finance_Charge_Action_Dispatcher $finance_charge_dispatcher;
    private Finance_Invoice_Action_Dispatcher $finance_invoice_dispatcher;

    public function __construct(
        $plugin_instance,
        ?Admin_Action_Adapter $actions = null,
        ?Admin_Screen_Presenter $screens = null,
        ?Finance_Charge_Action_Dispatcher $finance_charge_dispatcher = null,
        ?Finance_Invoice_Action_Dispatcher $finance_invoice_dispatcher = null,
    ) {
        parent::__construct( $plugin_instance );
        $c                                 = $this->racketmanager->container;
        $this->actions                     = $actions ?? $c->get( 'admin_action_adapter' );
        $this->screens                     = $screens ?? $c->get( 'admin_screen_presenter' );
        $this->finance_charge_dispatcher  = $finance_charge_dispatcher ?? $c->get( 'finance_charge_action_dispatcher' );
        $this->finance_invoice_dispatcher = $finance_invoice_dispatcher ?? $c->get( 'finance_invoice_action_dispatcher' );
        $this->admin_action_adapter        = $this->actions;
    }

    /**
     * Function to handle administration finances displays
     *
     * @param string|null $view
     *
     * @return void
     */
    public function handle_display( ?string $view ): void {
        $view_map = array(
            'charges'         => array( $this, 'display_charges_page' ),
            'club-invoices'   => array( $this, 'display_club_invoices_page' ),
            'player-invoices' => array( $this, 'display_player_invoices_page' ),
            'invoice'         => array( $this, 'display_invoice_page' ),
            'charge'          => array( $this, 'display_charge_page' ),
        );

        $callback = $view_map[ $view ] ?? array( $this, 'display_finances_page' );
        call_user_func( $callback );
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
        $this->render_invoices_page( 'club-invoices' );
    }

    /**
     * Display player invoices page
     */
    public function display_player_invoices_page(): void {
        $this->render_invoices_page( 'player-invoices' );
    }

    /**
     * Shared render logic for club and player invoices pages
     *
     * @param string $tab 'club-invoices'|'player-invoices'
     */
    private function render_invoices_page( string $tab ): void {
        $this->screens->apply_flash();

        if ( isset( $_POST['doActionInvoices'] ) && isset( $_POST['action'] ) ) {
            $result = $this->finance_invoice_dispatcher->handle( $_GET, $_POST );
            if ( $this->deliver_finances_action_result( $result, $_GET ) ) {
                return;
            }
        }

        $validator = new Validator_Finance();
        $validator = $validator->capability( 'edit_leagues' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, 'error' );
            $this->show_message();
            return;
        }

        $club_id   = isset( $_GET['club'] ) ? intval( $_GET['club'] ) : null;
        $charge_id = isset( $_GET['charge'] ) ? intval( $_GET['charge'] ) : null;
        $status    = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : 'open';

        $args = array(
            'type' => ( 'club-invoices' === $tab ) ? 'club' : 'player',
        );
        if ( $club_id ) {
            $args['billable'] = $club_id;
        }
        if ( $status ) {
            $args['status'] = $status;
        }
        if ( $charge_id ) {
            $args['charge'] = $charge_id;
        }

        $finance_invoices = $this->finance_service->get_invoices_by_criteria( $args );
        $clubs            = ( 'club-invoices' === $tab ) ? (array) $this->club_service->get_clubs() : array();
        $charges          = $this->get_finance_charges_for_invoices( 'club-invoices' === $tab ? 'team' : 'player' );

        $vm = new Finances_Invoices_Page_View_Model(
            invoices: $finance_invoices,
            charges: $charges,
            clubs: $clubs,
            racketmanager_tab: $tab,
            club_id: $club_id,
            charge_id: $charge_id,
            status: $status,
        );

        $this->screens->present(
            array( 'view_model' => $vm ),
            Finances_Invoices_Page_View_Model::class,
            'admin/finances/show-invoices'
        );
    }

    /**
     * Get charges for invoices
     *
     * @param string $type
     *
     * @return array
     */
    private function get_finance_charges_for_invoices( string $type ): array {
        $args = array(
            'entry'   => $type,
            'orderby' => array(
                'season'         => 'DESC',
                'competition_id' => 'ASC',
            ),
        );
        return $this->finance_service->get_charges_by_criteria( $args );
    }

    /**
     * Display charges page
     */
    public function display_charges_page(): void {
        $this->screens->apply_flash();

        if ( isset( $_POST['doChargesDel'] ) && isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) {
            $result = $this->finance_charge_dispatcher->handle( $_GET, $_POST );
            if ( $this->deliver_finances_action_result( $result, $_GET ) ) {
                return;
            }
        }

        $validator = new Validator_Finance();
        $validator = $validator->capability( 'edit_leagues' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, 'error' );
            $this->show_message();
            return;
        }

        $competition_id = isset( $_GET['competition'] ) ? intval( $_GET['competition'] ) : null;
        $season         = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;

        $args = array();
        if ( $competition_id ) {
            $args['competition'] = $competition_id;
        }
        if ( $season ) {
            $args['season'] = $season;
        }

        $finance_charges = $this->finance_service->get_charges_by_criteria( $args );
        $competitions    = $this->competition_service->get_all();
        $seasons         = $this->season_service->get_all_seasons();

        $vm = new Finances_Charges_Page_View_Model(
            charges: $finance_charges,
            competitions: $competitions,
            seasons: $seasons,
            competition_id: $competition_id,
            season: $season ? (string) $season : null,
            racketmanager_tab: 'charges',
        );

        $this->screens->present(
            array( 'view_model' => $vm ),
            Finances_Charges_Page_View_Model::class,
            'admin/finances/show-charges'
        );
    }

    /**
     * Display charge edit/add page
     */
    public function display_charge_page(): void {
        $flash      = $this->screens->apply_flash();
        $flash_data = $flash['flash_data'] ?? array();
        $errors     = is_array( $flash_data['errors'] ?? null ) ? new Error_Bag( $flash_data['errors'] ) : new Error_Bag();

        if ( isset( $_POST['generateInvoices'] ) || isset( $_POST['saveCharge'] ) ) {
            $result = $this->finance_charge_dispatcher->handle( $_GET, $_POST );
            if ( $this->deliver_finances_action_result( $result, $_GET ) ) {
                return;
            }
        }

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
                $charge = $this->finance_service->get_charge_with_details( $charge_id );
                $edit   = true;
            } catch ( Charge_Not_Found_Exception $e ) {
                $this->set_message( $e->getMessage(), 'error' );
                $this->show_message();
                return;
            }
        }

        $form_data = $flash_data['form_data'] ?? array();
        if ( ! empty( $form_data ) ) {
            $charge_obj = new stdClass();
            if ( is_object( $charge ) ) {
                foreach ( get_object_vars( $charge ) as $key => $val ) {
                    $charge_obj->$key = $val;
                }
            }
            if ( isset( $form_data['charges_id'] ) ) {
                $charge_obj->id = intval( $form_data['charges_id'] );
            }
            if ( isset( $form_data['competition_id'] ) ) {
                $charge_obj->competition_id = intval( $form_data['competition_id'] );
            }
            if ( isset( $form_data['season'] ) ) {
                $charge_obj->season = sanitize_text_field( strval( $form_data['season'] ) );
            }
            if ( isset( $form_data['status'] ) ) {
                $charge_obj->status = sanitize_text_field( strval( $form_data['status'] ) );
            }
            if ( isset( $form_data['date'] ) ) {
                $charge_obj->date = sanitize_text_field( strval( $form_data['date'] ) );
            }
            if ( isset( $form_data['feeClub'] ) ) {
                $charge_obj->fee_competition = is_numeric( $form_data['feeClub'] ) ? floatval( $form_data['feeClub'] ) : sanitize_text_field( strval( $form_data['feeClub'] ) );
            }
            if ( isset( $form_data['feeTeam'] ) ) {
                $charge_obj->fee_event = is_numeric( $form_data['feeTeam'] ) ? floatval( $form_data['feeTeam'] ) : sanitize_text_field( strval( $form_data['feeTeam'] ) );
            }
            $charge = $charge_obj;
        }

        if ( $edit ) {
            $form_title   = __( 'Edit Charge', 'racketmanager' );
            $form_action  = __( 'Update', 'racketmanager' );
            $club_charges = ( ! empty( $charge->competition_is_team_entry ) && $charge_id )
                ? $this->finance_service->get_charges_for_clubs( $charge_id )
                : array();
        } else {
            $form_title   = __( 'Add Charge', 'racketmanager' );
            $form_action  = __( 'Add', 'racketmanager' );
            $club_charges = array();
        }

        $competitions          = $this->competition_service->get_all();
        $racketmanager_seasons = $this->season_service->get_all_seasons();

        $vm = new Finances_Charge_Page_View_Model(
            charge: $charge,
            competitions: $competitions,
            seasons: $racketmanager_seasons,
            club_charges: $club_charges,
            edit: $edit,
            form_title: $form_title,
            form_action: $form_action,
            errors: $errors,
        );

        $this->screens->present(
            array( 'view_model' => $vm ),
            Finances_Charge_Page_View_Model::class,
            'admin/finances/charge'
        );
    }

    /**
     * Display invoice page
     */
    public function display_invoice_page(): void {
        $flash      = $this->screens->apply_flash();
        $flash_data = $flash['flash_data'] ?? array();
        $errors     = is_array( $flash_data['errors'] ?? null ) ? new Error_Bag( $flash_data['errors'] ) : new Error_Bag();

        if ( isset( $_POST['saveInvoice'] ) ) {
            $result = $this->finance_invoice_dispatcher->handle( $_GET, $_POST );
            if ( $this->deliver_finances_action_result( $result, $_GET ) ) {
                return;
            }
        }

        $validator = new Validator_Finance();
        $validator = $validator->capability( 'edit_leagues' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, 'error' );
            $this->show_message();
            return;
        }

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

            $form_data = $flash_data['form_data'] ?? array();
            if ( ! empty( $form_data ) ) {
                if ( isset( $form_data['purchaseOrder'] ) && isset( $invoice->invoice ) && method_exists( $invoice->invoice, 'set_purchase_order' ) ) {
                    $invoice->invoice->set_purchase_order( sanitize_text_field( strval( $form_data['purchaseOrder'] ) ) );
                }
                if ( isset( $form_data['status'] ) && isset( $invoice->invoice ) && method_exists( $invoice->invoice, 'set_status' ) ) {
                    $invoice->invoice->set_status( sanitize_text_field( strval( $form_data['status'] ) ) );
                }
            }

            $invoice_view = show_invoice( $invoice->invoice->get_id() );

            $vm = new Finances_Invoice_Page_View_Model(
                invoice: $invoice,
                invoice_view: $invoice_view,
                errors: $errors,
            );

            $this->screens->present(
                array( 'view_model' => $vm ),
                Finances_Invoice_Page_View_Model::class,
                'admin/finances/invoice'
            );
        } catch ( Invoice_Not_Found_Exception $e ) {
            $this->set_message( $e->getMessage(), true );
            $this->show_message();
        }
    }
}
