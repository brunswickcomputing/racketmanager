<?php
/**
 * Tournament Draw Admin Controller
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\Admin_Championship;
use Racketmanager\Admin\View_Models\Tournament_Draw_Page_View_Model;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\RacketManager;

use function Racketmanager\get_league;

/**
 * Handles draw-page orchestration + tab selection.
 *
 * This controller intentionally sits in the Admin layer and reuses existing
 * championship admin behaviors via Admin_Championship, so Admin_Tournament can
 * stay a thin bridge.
 */
final class Tournament_Draw_Admin_Controller extends Admin_Championship {

    public function __construct( RacketManager $app ) {
        parent::__construct( $app );
    }

    /**
     * Controller for admin.php?page=racketmanager-tournaments&view=draw
     *
     * @param array $query Typically $_GET
     * @param array $post  Typically $_POST
     * @return array{view_model:Tournament_Draw_Page_View_Model, message?:string, message_type?:bool|string}
     *
     * @throws Invalid_Status_Exception
     * @throws Tournament_Not_Found_Exception
     */
    public function draw_page( array $query, array $post ): array {
        // Context
        $tournament_id = isset( $query['tournament'] ) ? intval( $query['tournament'] ) : null;
        $league_id     = isset( $query['league'] ) ? intval( $query['league'] ) : null;
        $tab           = isset( $query['league-tab'] ) ? sanitize_text_field( wp_unslash( $query['league-tab'] ) ) : null;

        try {
            $tournament = $this->tournament_service->get_tournament( $tournament_id );
        } catch ( Tournament_Not_Found_Exception $e ) {
            throw new Tournament_Not_Found_Exception( $e->getMessage() );
        }

        $league = get_league( $league_id );
        if ( ! $league ) {
            throw new Invalid_Status_Exception( __( 'League not found', 'racketmanager' ) );
        }

        // Execute actions and decide tab
        $updates = $this->handle_league_teams_action( $league );
        if ( $updates ) {
            $tab = 'preliminary';
        }

        if ( isset( $post['updateLeague'] ) && 'match' === strval( $post['updateLeague'] ) ) {
            $this->manage_matches_in_league( $league );
            $tab = 'matches';
        } elseif ( isset( $post['action'] ) && 'addTeamsToLeague' === strval( $post['action'] ) ) {
            $this->league_add_teams( $league );
            $this->set_message( __( 'Teams added', 'racketmanager' ) );
            $tab = 'preliminary';
        } elseif ( isset( $post['updateLeague'] ) && 'teamPlayer' === strval( $post['updateLeague'] ) ) {
            $this->edit_player_team( $league );
            $tab = 'preliminary';
        } elseif ( empty( $tab ) ) {
            $tab = $this->handle_championship_admin_page( $league );

            if ( isset( $post['saveRanking'] ) ) {
                $this->rank_teams( $league, 'manual' );
                $tab = 'preliminary';
            } elseif ( isset( $post['randomRanking'] ) ) {
                $this->rank_teams( $league, 'random' );
                $tab = 'preliminary';
            } elseif ( isset( $post['ratingPointsRanking'] ) ) {
                $this->rank_teams( $league, 'ratings' );
                $tab = 'preliminary';
            } elseif ( empty( $tab ) ) {
                $tab = 'finalResults';
            }
        }

        // Build result
        $vm = new Tournament_Draw_Page_View_Model(
            tournament: $tournament,
            league: $league,
            tab: $tab ?: 'finalResults',
        );

        $result = array(
            'view_model' => $vm,
        );

        // If any of the inherited helpers called set_message(), surface it for the bridge.
        if ( ! empty( $this->message ) ) {
            $result['message'] = $this->message;
            $result['message_type'] = $this->error ?: false;
            $this->message = '';
        }

        return $result;
    }
}
