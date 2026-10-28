<?php
declare( strict_types=1 );
/**
 * Club Team Admin Controller
 *
 * Handles display and actions for editing a team within club context.
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Exception;
use Racketmanager\Admin\View_Models\Tournament_Team_Page_View_Model;
use Racketmanager\Exceptions\Team_Not_Found_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Util\Util_Lookup;

final readonly class Club_Team_Admin_Controller {

    public function __construct(
        private Tournament_Service $tournament_service,
        private League_Service $league_service,
        private Team_Service $team_service,
        private Club_Service $club_service,
        private League_Query_Service $league_query_service,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     *
     * @return array{view_model?: Tournament_Team_Page_View_Model, redirect?: string, message?: string, message_type?: bool|string}
     */
    public function team_page( array $query, array $post ): array {
        unset( $post );

        try {
            $this->action_guard->assert_capability( 'edit_teams' );
        } catch ( Exception $e ) {
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

        $league     = null;
        $file       = 'team.php';
        $season     = '';
        $match_days = array();

        if ( isset( $query['league_id'] ) ) {
            $league_id = intval( $query['league_id'] );
            $league    = $this->league_service->get_league( $league_id );
            if ( ! $league ) {
                return array(
                    'message'      => __( 'League not found', 'racketmanager' ),
                    'message_type' => true,
                );
            }
            $season     = isset( $query['season'] ) ? sanitize_text_field( wp_unslash( strval( $query['season'] ) ) ) : '';
            $match_days = Util_Lookup::get_match_days();
            if ( ! empty( $league->event->competition->is_player_entry ) ) {
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
            $team = $this->league_query_service->get_team_dtls( $league, $team_id, '' !== $season ? $season : null );
        } else {
            try {
                $team = $this->team_service->get_team_by_id( $team_id );
            } catch ( Team_Not_Found_Exception ) {
                $team = null;
            }
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
                $match_days,
                isset( $query['club_id'] ) && ! $league ? intval( $query['club_id'] ) : null
            ),
        );
    }
}
