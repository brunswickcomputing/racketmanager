<?php
/**
 * Tournament Matches Admin Controller
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\Presenters\Admin_Message_Mapper;
use Racketmanager\Admin\View_Models\Tournament_Matches_Page_View_Model;
use Racketmanager\Domain\DTO\Admin\Championship\Draw_Action_Request_DTO;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Admin\Tournament\Matches_Action_Dispatcher;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Services\Validator\Validator_Tournament;

use function Racketmanager\get_league;

readonly final class Tournament_Matches_Admin_Controller {

    public function __construct(
        private Tournament_Service $tournament_service,
        private Matches_Action_Dispatcher $matches_action_dispatcher,
    ) {
    }

    /**
     * Controller for admin.php?page=racketmanager-tournaments&view=matches
     *
     * @param array $query Typically $_GET
     * @param array $post  Typically $_POST
     * @return array{view_model?:Tournament_Matches_Page_View_Model, redirect?:string, message?:string, message_type?:bool|string}
     *
     * @throws Invalid_Status_Exception
     * @throws Tournament_Not_Found_Exception
     */
    public function matches_page( array $query, array $post ): array {
        $v = new Validator_Tournament();
        $v = $v->capability( 'edit_matches' );
        if ( ! empty( $v->error ) ) {
            throw new Invalid_Status_Exception( $v->msg );
        }

        $is_post = ( 'POST' === strtoupper( strval( $_SERVER['REQUEST_METHOD'] ?? '' ) ) );

        //phpcs:disable WordPress.Security.NonceVerification.Recommended
        $tournament_id = isset( $query['tournament'] ) ? intval( $query['tournament'] ) : null;
        $league_id     = isset( $query['league_id'] ) ? intval( $query['league_id'] ) : ( isset( $post['league_id'] ) ? intval( $post['league_id'] ) : null );
        $final_key     = isset( $query['final'] ) ? sanitize_text_field( wp_unslash( $query['final'] ) ) : ( isset( $post['final'] ) ? sanitize_text_field( wp_unslash( strval( $post['final'] ) ) ) : null );
        //phpcs:enable WordPress.Security.NonceVerification.Recommended

        // POST: manage matches (updateLeague=match) -> PRG redirect back to GET.
        if ( $is_post ) {
            $dto = new Draw_Action_Request_DTO(
                tournament_id: intval( $tournament_id ?? 0 ),
                league_id: intval( $league_id ?? 0 ),
                season: isset( $post['season'] ) ? sanitize_text_field( wp_unslash( strval( $post['season'] ) ) ) : null,
                post: $post
            );

            $response = $this->matches_action_dispatcher->handle( $dto );

            $redirect_url = add_query_arg(
                array(
                    'page'       => isset( $query['page'] ) ? sanitize_text_field( wp_unslash( strval( $query['page'] ) ) ) : 'racketmanager-tournaments',
                    'view'       => 'matches',
                    'tournament' => $tournament_id,
                    'league_id'  => $league_id,
                    'final'      => $final_key,
                ),
                admin_url( 'admin.php' )
            );

            $result = array(
                'redirect' => $redirect_url,
            );

            if ( null !== $response->message ) {
                $result['message'] = $response->message;
                $result['message_type'] = Admin_Message_Mapper::to_legacy( $response->message_type );
            }

            return $result;
        }

        // GET: render page (same variables expected by templates/admin/includes/match.php).
        $tournament = $this->tournament_service->get_tournament( $tournament_id );
        $season     = $tournament->get_season();

        $league = get_league( $league_id );
        if ( ! $league ) {
            throw new Invalid_Status_Exception( __( 'League not found', 'racketmanager' ) );
        }

        $competition = $league->event->competition;

        $
