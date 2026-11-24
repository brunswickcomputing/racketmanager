<?php
declare( strict_types=1 );
/**
 * Tournament team admin controller
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Exception;
use Racketmanager\Admin\View_Models\Tournament_Team_Page_View_Model;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Application\Admin\Ports\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Util\Util_Lookup;

/**
 * Coordinates the tournament team administration workflow.
 *
 * Validates access, resolves the requested team in an optional league and tournament context,
 * prepares supporting data for the edit screen, and returns either a page view model or an
 * error response suitable for display in the admin interface.
 */
final readonly class Tournament_Team_Admin_Controller {

    /**
     * Initialises the instance with the services required to manage tournaments, leagues, teams, clubs, action authorisation, and league queries.
     *
     * @param Tournament_Service     $tournament_service Service for tournament-related operations.
     * @param League_Service         $league_service Service for league-related operations.
     * @param Team_Service           $team_service Service for team-related operations.
     * @param Club_Service           $club_service Service for club-related operations.
     * @param Action_Guard_Interface $action_guard Guard used to validate whether actions are allowed.
     * @param League_Query_Service   $league_query_service Service used to query league data.
     *
     * @return void
     */
    public function __construct(
        private Tournament_Service $tournament_service,
        private League_Service $league_service,
        private Team_Service $team_service,
        private Club_Service $club_service,
        private Action_Guard_Interface $action_guard,
        private League_Query_Service $league_query_service,
    ) {
    }

    /**
     * Handles the team edit request by validating permissions, resolving the requested team, loading related league or tournament context, and preparing the page view model.
     *
     * @param array $query Request query data containing the team identifier and optional league, season, or tournament identifiers.
     *
     * @return array Response data containing either an error message with message type or the view model required to render the team edit page.
     */
    public function handle( array $query ): array {
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
            $team = $this->league_query_service->get_team_dtls( $league, $team_id );
        } else {
            $team = $this->team_service->get_team_by_id( $team_id );
        }

        if ( ! $team ) {
            return array(
                'message'      => __( 'Team not found', 'racketmanager' ),
                'message_type' => true,
            );
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
