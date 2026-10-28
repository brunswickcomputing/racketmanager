<?php
declare( strict_types=1 );
/**
 * Competition Seasons Admin Controller
 *
 * Handles the competition seasons screen (view=seasons).
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\Presenters\Admin_Message_Mapper;
use Racketmanager\Admin\View_Models\Competition_Seasons_Page_View_Model;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\League\Season_Delete_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition_Service;

final readonly class Competition_Seasons_Admin_Controller {

    public function __construct(
        private Competition_Service $competition_service,
        private Season_Delete_Action_Dispatcher $dispatcher,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * @param array<string, mixed> $query Typically $_GET
     * @param array<string, mixed> $post  Typically $_POST
     *
     * @return array{view_model?:Competition_Seasons_Page_View_Model, redirect?:string, message?:string, message_type?:bool|string}
     *
     * @throws Invalid_Status_Exception
     */
    public function seasons_page( array $query, array $post ): array {
        $this->action_guard->assert_capability( 'edit_leagues' );

        $competition_id = isset( $query['competition_id'] ) ? intval( $query['competition_id'] ) : ( isset( $post['competition_id'] ) ? intval( $post['competition_id'] ) : 0 );

        if ( ! $competition_id ) {
            throw new Invalid_Status_Exception( __( 'Competition not found', 'racketmanager' ) );
        }

        try {
            $competition = $this->competition_service->get_by_id( $competition_id );
        } catch ( Competition_Not_Found_Exception ) {
            throw new Invalid_Status_Exception( __( 'Competition not found', 'racketmanager' ) );
        }

        if ( ! $competition instanceof Competition ) {
            throw new Invalid_Status_Exception( __( 'Competition not found', 'racketmanager' ) );
        }

        $request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
        $is_post        = 'POST' === strtoupper( $request_method );

        if ( $is_post && isset( $post['doActionSeason'] ) ) {
            $action_result = $this->dispatcher->handle( $query, $post );

            $redirect_url = Admin_Redirect_Url_Builder::competition_screen(
                $query,
                array(
                    'view'           => 'seasons',
                    'competition_id' => $competition_id,
                )
            );

            return array(
                'redirect'     => $redirect_url,
                'message'      => $action_result->message,
                'message_type' => null !== $action_result->message_type ? Admin_Message_Mapper::to_legacy( $action_result->message_type ) : false,
            );
        }

        return array(
            'view_model' => new Competition_Seasons_Page_View_Model(
                competition: $competition,
                competition_id: $competition_id,
            ),
        );
    }
}
