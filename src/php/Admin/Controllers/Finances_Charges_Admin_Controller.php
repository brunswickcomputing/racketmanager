<?php
declare( strict_types=1 );
/**
 * Finances Charges Admin Controller
 *
 * Handles display and bulk deletion of charges.
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\Presenters\Admin_Message_Mapper;
use Racketmanager\Admin\View_Models\Finances_Charges_Page_View_Model;
use Racketmanager\Presentation\Presenters\Invoice_Presenter;
use Racketmanager\Services\Admin\Finance\Finance_Charge_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Season_Service;

final readonly class Finances_Charges_Admin_Controller {

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
     *
     * @return array{view_model?: Finances_Charges_Page_View_Model, redirect?: string, message?: string, message_type?: bool|string, flash_data?: array<string, mixed>}
     */
    public function charges_page( array $query, array $post ): array {
        if ( isset( $post['doChargesDel'] ) && isset( $post['action'] ) && 'delete' === $post['action'] ) {
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

        $this->action_guard->assert_capability( 'edit_leagues' );

        $competition_id = isset( $query['competition'] ) ? intval( $query['competition'] ) : null;
        $season         = isset( $query['season'] ) ? intval( $query['season'] ) : null;

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

        $currency_code = $this->finance_service->get_currency()->value;
        $vm            = $this->invoice_presenter->present_admin_charges_list(
            charges: $finance_charges,
            competitions: $competitions,
            seasons: $seasons,
            competition_id: $competition_id,
            season: $season ? (string) $season : null,
            tab: 'charges',
            currency_code: $currency_code,
        );

        return array(
            'view_model' => $vm,
        );
    }
}
