<?php
/**
 * Tournament Setup Admin Controller
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Domain\Competition;
use Racketmanager\Domain\Tournament;
use Racketmanager\Admin\Presenters\Admin_Error_Bag_Mapper;
use Racketmanager\Admin\View_Models\Tournament_Setup_Page_View_Model;
use Racketmanager\Domain\DTO\Tournament\Championship_Rounds_Request_DTO;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Season_Not_Found_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Services\Validator\Validator_Tournament;
use Racketmanager\Util\Util;

readonly final class Tournament_Setup_Admin_Controller {

    public function __construct(
        private Tournament_Service $tournament_service,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * @return bool True when the current request is a POST.
     */
    private function is_post_request(): bool {
        return 'POST' === strtoupper( strval( $_SERVER['REQUEST_METHOD'] ?? '' ) );
    }

    /**
     * @param array $query
     * @param array $post
     * @return int|null
     */
    private function resolve_tournament_id( array $query, array $post ): ?int {
        if ( isset( $query['tournament'] ) ) {
            return intval( $query['tournament'] );
        }

        if ( isset( $post['tournament_id'] ) ) {
            return intval( $post['tournament_id'] );
        }

        return null;
    }

    /**
     * Controller for admin.php?page=racketmanager-tournaments&view=setup
     *
     * @param array<string, mixed> $query Typically $_GET
     * @param array<string, mixed> $post  Typically $_POST
     *
     * @return array{view_model?:Tournament_Setup_Page_View_Model, message?:string, message_type?:bool|string, redirect?:string}
     *
     * @throws Invalid_Status_Exception
     * @throws Tournament_Not_Found_Exception
     */
    public function setup_page( array $query, array $post ): array {
        $this->action_guard->assert_capability( 'edit_matches' );

        $validator     = new Validator_Tournament();
        $tournament_id = $this->resolve_tournament_id( $query, $post );

        if ( $this->is_post_request() ) {
            $result = $this->handle_post_actions( $query, $post, $validator, $tournament_id );
            if ( isset( $result['redirect'] ) || isset( $result['view_model'] ) ) {
                return $result;
            }
        }

        return $this->handle_get( $query, $validator, $tournament_id );
    }

    /**
     * Handle GET rendering.
     *
     * @param array<string, mixed> $query
     * @param Validator_Tournament $validator
     * @param int|null             $tournament_id
     *
     * @return array{view_model:Tournament_Setup_Page_View_Model, message?:string, message_type?:bool|string}
     *
     * @throws Tournament_Not_Found_Exception
     */
    private function handle_get( array $query, Validator_Tournament $validator, ?int $tournament_id ): array {
        $message      = null;
        $message_type = false;

        if ( isset( $query['updated'] ) ) {
            $message = __( 'Tournament round dates updated', 'racketmanager' );
        } elseif ( isset( $query['ratings_set'] ) ) {
            $did_set      = ( '1' === strval( $query['ratings_set'] ) );
            $message      = $did_set ? __( 'Tournament ratings set', 'racketmanager' ) : __( 'No ratings to set', 'racketmanager' );
            $message_type = $did_set ? false : 'warning';
        }

        $vm = $this->build_view_model( $tournament_id, $validator );

        $result = array(
            'view_model' => $vm,
        );

        if ( null !== $message ) {
            $result['message']      = $message;
            $result['message_type'] = $message_type;
        }

        return $result;
    }

    /**
     * Handle POST actions for the setup page.
     *
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     * @param Validator_Tournament $validator
     * @param int|null             $tournament_id
     *
     * @return array{message?:string, message_type?:bool|string, redirect?:string, view_model?:Tournament_Setup_Page_View_Model}
     *
     * @throws Invalid_Status_Exception
     * @throws Tournament_Not_Found_Exception
     */
    private function handle_post_actions( array $query, array $post, Validator_Tournament $validator, ?int $tournament_id ): array {
        if ( isset( $post['action'] ) ) {
            return $this->handle_round_dates_post( $query, $post, $validator, $tournament_id );
        }

        if ( isset( $post['rank'] ) ) {
            return $this->handle_generate_ratings_post( $query, $post, $validator, $tournament_id );
        }

        return array();
    }

    /**
     * POST: set round dates / add/replace matches.
     *
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     * @param Validator_Tournament $validator
     * @param int|null             $tournament_id
     *
     * @return array{message?:string, message_type?:bool|string, redirect?:string, view_model?:Tournament_Setup_Page_View_Model}
     *
     * @throws Invalid_Status_Exception
     * @throws Tournament_Not_Found_Exception
     */
    private function handle_round_dates_post( array $query, array $post, Validator_Tournament $validator, ?int $tournament_id ): array {
        $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_add_championship-matches', 'edit_matches' );

        $request = new Championship_Rounds_Request_DTO( $post );

        try {
            $response = $this->tournament_service->set_round_dates_for_tournament( $tournament_id, $request );
            if ( is_wp_error( $response ) ) {
                $validator->error    = true;
                $validator->err_flds = $response->get_error_codes();
                $validator->err_msgs = $response->get_error_messages();

                $vm = $this->build_view_model( $tournament_id, $validator );

                return array(
                    'view_model'   => $vm,
                    'message'      => __( 'Error setting tournament round dates', 'racketmanager' ),
                    'message_type' => true,
                );
            }

            $redirect_url = Admin_Redirect_Url_Builder::tournament_setup_view(
                $query,
                $post,
                $tournament_id
            );
            $redirect_url = add_query_arg( 'updated', 1, $redirect_url );

            return array( 'redirect' => $redirect_url );
        } catch ( Tournament_Not_Found_Exception|Competition_Not_Found_Exception|Season_Not_Found_Exception $e ) {
            throw new Tournament_Not_Found_Exception( $e->getMessage() );
        }
    }

    /**
     * POST: generate ratings.
     *
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     * @param Validator_Tournament $validator
     * @param int|null             $tournament_id
     *
     * @return array{redirect:string}
     *
     * @throws Invalid_Status_Exception
     * @throws Tournament_Not_Found_Exception
     */
    private function handle_generate_ratings_post( array $query, array $post, Validator_Tournament $validator, ?int $tournament_id ): array {
        $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_calculate_ratings', 'edit_matches' );

        try {
            $updates = $this->tournament_service->calculate_player_team_rating_for_tournament( $tournament_id );

            $redirect_url = Admin_Redirect_Url_Builder::tournament_setup_view(
                $query,
                $post,
                $tournament_id
            );
            $redirect_url = add_query_arg( 'ratings_set', $updates ? 1 : 0, $redirect_url );

            return array(
                'redirect'     => $redirect_url,
                'message'      => $updates ? __( 'Tournament ratings set', 'racketmanager' ) : __( 'No ratings to set', 'racketmanager' ),
                'message_type' => $updates ? false : 'warning',
            );
        } catch ( Tournament_Not_Found_Exception $e ) {
            throw new Tournament_Not_Found_Exception( $e->getMessage() );
        }
    }

    /**
     * Build view model for the setup page.
     *
     * @param int|null $tournament_id
     * @param Validator_Tournament $validator
     * @return Tournament_Setup_Page_View_Model
     *
     * @throws Tournament_Not_Found_Exception
     */
    private function build_view_model( ?int $tournament_id, Validator_Tournament $validator ): Tournament_Setup_Page_View_Model {
        try {
            $tournament_details = $this->tournament_service->get_tournament_with_details( $tournament_id );
        } catch ( Tournament_Not_Found_Exception|Competition_Not_Found_Exception $e ) {
            throw new Tournament_Not_Found_Exception( $e->getMessage() );
        }

        $tournament  = $tournament_details->tournament;
        $competition = $tournament_details->competition;

        $season           = $tournament->get_season();
        $tournament_season = $competition->get_season_by_name( $season );
        $match_dates       = $tournament_season['match_dates'] ?? array();

        if ( empty( $match_dates ) ) {
            $match_dates = $this->tournament_service->calculate_default_match_dates( $tournament, $competition );
        }

        $errors = Admin_Error_Bag_Mapper::from_validator( $validator );

        return new Tournament_Setup_Page_View_Model(
            tournament: $tournament,
            season: $season,
            match_dates: is_array( $match_dates ) ? $match_dates : array(),
            match_count: null,
            league: null,
            errors: $errors,
            validator: $validator
        );
    }
}
