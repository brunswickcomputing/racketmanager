<?php
declare( strict_types=1 );
/**
 * Finances Charge Admin Controller
 *
 * Handles editing and adding finance charges.
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\Presenters\Admin_Message_Mapper;
use Racketmanager\Admin\View_Models\Error_Bag;
use Racketmanager\Admin\View_Models\Finances_Charge_Page_View_Model;
use Racketmanager\Exceptions\Charge_Not_Found_Exception;
use Racketmanager\Presentation\Presenters\Invoice_Presenter;
use Racketmanager\Services\Admin\Finance\Finance_Charge_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Season_Service;
use stdClass;

final readonly class Finances_Charge_Admin_Controller {

    public function __construct(
        private Finance_Service $finance_service,
        private Competition_Service $competition_service,
        private Season_Service $season_service,
        private Finance_Charge_Action_Dispatcher $finance_charge_dispatcher,
        private Action_Guard_Interface $action_guard,
        private Invoice_Presenter $invoice_presenter,
    ) {
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     * @param array<string, mixed> $flash_data
     *
     * @return array{view_model?: Finances_Charge_Page_View_Model, redirect?: string, message?: string, message_type?: bool|string, flash_data?: array<string, mixed>}
     * @throws Charge_Not_Found_Exception
     */
    public function charge_page( array $query, array $post, array $flash_data = array() ): array {
        if ( isset( $post['generateInvoices'] ) || isset( $post['saveCharge'] ) ) {
            $result = $this->finance_charge_dispatcher->handle( $query, $post );

            $extra_args = array();
            if ( null !== $result->tab_override && '' !== $result->tab_override ) {
                $extra_args['tab'] = $result->tab_override;
            }
            $extra_args = array_merge( $extra_args, $result->query_params );

            $redirect = Admin_Redirect_Url_Builder::finances_screen( $query, $extra_args );

            return array(
                'redirect'     => $redirect,
                'message'      => $result->message,
                'message_type' => Admin_Message_Mapper::to_legacy( $result->message_type ),
                'flash_data'   => $result->flash_data,
            );
        }

        $this->action_guard->assert_capability( 'edit_teams' );

        $errors = is_array( $flash_data['errors'] ?? null ) ? new Error_Bag( $flash_data['errors'] ) : new Error_Bag();

        $edit      = false;
        $charge    = null;
        $charge_id = isset( $query['charges'] ) ? intval( $query['charges'] ) : null;

        if ( $charge_id ) {
            $charge = $this->finance_service->get_charge_with_details( $charge_id );
            $edit   = true;
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

        $currency_code = $this->finance_service->get_currency()->value;
        $vm            = $this->invoice_presenter->present_admin_charge_detail(
            charge: $charge,
            competitions: $competitions,
            seasons: $racketmanager_seasons,
            club_charges: $club_charges,
            edit: $edit,
            form_title: $form_title,
            form_action: $form_action,
            errors: $errors,
            currency_code: $currency_code,
        );

        return array(
            'view_model' => $vm,
        );
    }
}
