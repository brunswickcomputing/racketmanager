<?php
/**
 * Tournament Overview Admin Controller
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\Presenters\Admin_Message_Mapper;
use Racketmanager\Admin\View_Models\Tournament_Overview_Page_View_Model;
use Racketmanager\Domain\DTO\Admin\Overview\Tournament_Overview_Action_Request_DTO;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Admin\Overview\Tournament_Overview_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Tournament_Service;

readonly final class Tournament_Overview_Admin_Controller {

    public function __construct(
        private Tournament_Service $tournament_service,
        private Tournament_Overview_Action_Dispatcher $dispatcher,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * Controller for admin.php?page=racketmanager-tournaments&view=tournament
     *
     * @param array $query Typically $_GET
     * @param array $post Typically $_POST
     *
     * @return array{view_model:Tournament_Overview_Page_View_Model, message?:string, message_type?:bool|string}
     *
     * @throws Invalid_Status_Exception
     * @throws Tournament_Not_Found_Exception
     */
    public function overview_page( array $query, array $post ): array {
        $tournament_id = isset( $query['tournament'] ) ? intval( $query['tournament'] ) : null;

        try {
            $tournament = $this->tournament_service->get_tournament( $tournament_id );
            $overview   = $this->tournament_service->get_tournament_overview( $tournament_id );
        } catch ( Tournament_Not_Found_Exception $e ) {
            throw new Tournament_Not_Found_Exception( $e->getMessage() );
        }

        $dto            = new Tournament_Overview_Action_Request_DTO( $tournament_id, $post );
        $action_result  = $this->dispatcher->handle( $dto );
        $message        = $action_result->message;
        $message_type   = Admin_Message_Mapper::to_legacy( $action_result->message_type );

        $events = $this->tournament_service->get_leagues_by_event_for_tournament( $tournament_id );
        $tab    = 'overview';

        $categorized_entries = $this->tournament_service->get_categorized_players_for_tournament( $tournament_id );

        $vm = new Tournament_Overview_Page_View_Model(
            tournament: $tournament,
            overview: $overview,
            events: $events,
            tab: $tab,
            confirmed_entries: $categorized_entries['confirmed'],
            unpaid_entries: $categorized_entries['unpaid'],
            pending_entries: $categorized_entries['pending'],
            withdrawn_entries: $categorized_entries['withdrawn'],
        );

        $result = array(
            'view_model' => $vm,
        );

        if ( null !== $message ) {
            $result['message']      = $message;
            $result['message_type'] = $message_type;
        }

        return $result;
    }
}
