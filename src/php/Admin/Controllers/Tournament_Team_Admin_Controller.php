<?php
/**
 * Tournament team admin controller
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\View_Models\Tournament_Team_Page_View_Model;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Util\Util_Lookup;

final class Tournament_Team_Admin_Controller {

    /**
     * @param Tournament_Service $tournament_service
     * @param League_Service $league_service
     * @param Team_Service $team_service
     * @param Club_Service $club_service
     * @param Action_Guard_Interface $action_guard
     */
    public function __construct(
        private readonly Tournament_Service $tournament_service,
        private readonly League_Service $league_service,
        private readonly Team_Service $team_service,
        private readonly Club_Service $club_service,
        private readonly Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * Handle the display of the tournament team page.
     *
     * @param array $query
     *
     * @return array{view_model?:Tournament_Team_Page_View_Model, message?:string, message_type?:bool|string}
     */
    public function handle( array $query ): array {
        try {
            $this->action_guard->assert_capability( 'edit_teams' );
        } catch ( \Exception $e ) {
            return array(
                'message'      => $e->getMessage(),
                'message_type' => true,
            );
        }

        $team_id = isset( $query['edit'] ) ? intval( $query['edit'] ) : null;
        if ( ! $team_id ) {
            return array(
                'message'      => __( 'Team not specified', 'racketmanager' ),
                'message_type' => true,
            );
        }

        $league = null;
        $file   = 'team.php';
        $season = '';
        $match_days = array();

        if ( isset( $query['league_id'] ) ) {
            $league_id  = intval( $query['league_id'] );
            $league     = $this->league_service->get_league( $league_id );
            $season     = isset( $query['season'] ) ? sanitize_text_field( wp_unslash( $query['season'] ) ) : '';
            $match_days = Util_Lookup::get_match_days();
            if ( $league && $league->event->competition->is_player_entry ) {
                $file = 'player-team.php';
            }
        }

        $tournament_id = isset( $query['tournament'] ) ? intval( $query['tournament'] ) : null;
        $tournament    = null;
        if ( $tournament_id ) {
            try {
                $tournament = $this->tournament_service->get_tournament( $tournament_id );
            } catch ( Tournament_Not_Found_Exception $e ) {
                return array(
                    'message'      => $e->getMessage(),
                    'message_type' => true,
                );
            }
        }

        if ( $league ) {
            $team = $league->get_team_dtls( $team_id );
        } else {
            $team = $this->team_service->get_team_by_id( $team_id );
        }

        if ( ! $team ) {
            return array(
                'message'      => __( 'Team not found', 'racketmanager' ),
                'message_type' => true,
            );
        }

        if ( ! isset( $team->roster ) ) {
            $team->roster = array();
        }

        $clubs = $this->club_service->get_clubs();

        return array(
            'view_model' => new Tournament_Team_Page_View_Model(
                $team,
                $league,
                $tournament,
                $clubs,
                __( 'Edit Team', 'racketmanager' ),
                __( 'Update', 'racketmanager' ),
                $file,
                $season,
                $match_days
            ),
        );
    }
}
