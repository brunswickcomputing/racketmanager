<?php
/**
 * Tournament Setup Admin Controller
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

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
     * Controller for admin.php?page=racketmanager-tournaments&view=setup
     *
     * @param array $query Typically $_GET
     * @param array $post  Typically $_POST
     * @return array{view_model:Tournament_Setup_Page_View_Model, message?:string, message_type?:bool|string}
     *
     * @throws Invalid_Status_Exception
     * @throws Tournament_Not_Found_Exception
     */
    public function setup_page( array $query, array $post ): array {
        $this->action_guard->assert_capability( 'edit_matches' );

        $validator = new Validator_Tournament(); // Kept for field-level validation + error mapping.
        $result = array();

        $message_info = $this->handle_post_actions( $post, $validator );

        $tournament_id = isset( $query['tournament'] ) ? intval( $query['tournament'] ) : null;

        $errors = Admin_Error_Bag_Mapper::from_validator( $validator );

        $vm            = $this->build_view_model( $tournament_id, $validator );

        // Prefer Error_Bag for templates; keep validator for BC.
        $result['view_model'] = new Tournament_Setup_Page_View_Model(
            tournament: $vm->tournament,
            season: $vm->season,
            match_dates: $vm->match_dates,
            match_count: $vm->match_count,
            league: $vm->league,
            errors: $errors,
            validator: $validator
        );

        if ( null !== $message_info['message'] ) {
            $result['message']      = $message_info['message'];
            $result['message_type'] = $message_info['message_type'];
        }

        return $result;
    }

    /**
     * Handle POST actions for the setup page.
     *
     * @param array $post
     * @param Validator_Tournament $validator
     * @return array{message:string|null, message_type:bool|string}
     *
     * @throws Invalid_Status_Exception
     * @throws Tournament_Not_Found_Exception
     */
    private function handle_post_actions( array $post, Validator_Tournament $validator ): array {
        $message      = null;

        if ( isset( $post['action'] ) ) {
            return $this->handle_round_dates_post( $post, $validator );
        }

        if ( isset( $post['rank'] ) ) {
            return $this->handle_generate_ratings_post( $post, $validator );
        }

        return array(
            'message'      => $message,
            'message_type' => false,
        );
    }

    /**
     * POST: set round dates / add/replace matches.
     *
     * @param array $post
     * @param Validator_Tournament $validator
     * @return array{message:string|null, message_type:bool|string}
     *
     * @throws Invalid_Status_Exception
     * @throws Tournament_Not_Found_Exception
     */
    private function handle_round_dates_post( array $post, Validator_Tournament $validator ): array {
        $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_add_championship-matches', 'edit_matches' );

        $tournament_id = isset( $post['tournament_id'] ) ? intval( $post['tournament_id'] ) : null;
        $request       = new Championship_Rounds_Request_DTO( $post );

        try {
            $response = $this->tournament_service->set_round_dates_for_tournament( $tournament_id, $request );
            if ( is_wp_error( $response ) ) {
                $validator->error    = true;
                $validator->err_flds = $response->get_error_codes();
                $validator->err_msgs = $response->get_error_messages();

                return array(
                    'message'      => __( 'Error setting tournament round dates', 'racketmanager' ),
                    'message_type' => true,
                );
            }

            return array(
                'message'      => __( 'Tournament round dates updated', 'racketmanager' ),
                'message_type' => false,
            );
        } catch ( Tournament_Not_Found_Exception|Competition_Not_Found_Exception|Season_Not_Found_Exception $e ) {
            throw new Tournament_Not_Found_Exception( $e->getMessage() );
        }
    }

    /**
     * POST: generate ratings.
     *
     * @param array $post
     * @param Validator_Tournament $validator
     * @return array{message:string|null, message_type:bool|string}
     *
     * @throws Invalid_Status_Exception
     * @throws Tournament_Not_Found_Exception
     */
    private function handle_generate_ratings_post( array $post, Validator_Tournament $validator ): array {
        $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_calculate_ratings', 'edit_matches' );

        $tournament_id = isset( $post['tournament_id'] ) ? intval( $post['tournament_id'] ) : null;

        try {
            $updates = $this->tournament_service->calculate_player_team_rating_for_tournament( $tournament_id );
            if ( $updates ) {
                return array(
                    'message'      => __( 'Tournament ratings set', 'racketmanager' ),
                    'message_type' => false,
                );
            }

            return array(
                'message'      => __( 'No ratings to set', 'racketmanager' ),
                'message_type' => 'warning',
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
            $match_dates  = array();
            $match_date   = null;
            $round_length = $competition->settings['round_length'] ?? 7;
            $i            = 0;

            foreach ( $tournament->finals as $final ) {
                $r = $final['round'] - 1;
                if ( 0 === $i ) {
                    $match_date = $tournament->date_end;
                } elseif ( 1 === $i ) {
                    $match_date = Util::amend_date( $tournament->date_end, 7, '-' );
                } else {
                    $match_date = Util::amend_date( $match_date, $round_length, '-' );
                }
                $match_dates[ $r ] = $match_date;
                ++$i;
            }
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
