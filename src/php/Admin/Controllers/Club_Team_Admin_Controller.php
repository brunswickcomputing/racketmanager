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
use Racketmanager\Application\Admin\Ports\Action_Guard_Interface;
use Racketmanager\Domain\Tournament\Tournament;
use Racketmanager\Exceptions\Invalid_Argument_Exception;
use Racketmanager\Exceptions\League_Not_Found_Exception;
use Racketmanager\Exceptions\Team_Not_Found_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Util\Util_Lookup;

/**
 * Coordinates the WordPress admin team-editing workflow for club, league, and tournament contexts.
 *
 * Validates administrative permissions, resolves request parameters, loads related league or tournament data,
 * retrieves the requested team, prepares supporting lookup data, and returns a response array containing either
 * a view model for rendering or an error message for display.
 */
final readonly class Club_Team_Admin_Controller {

    /**
     * Initialises the service with required tournament, league, team, club, query, and action guard dependencies.
     *
     * @param Tournament_Service     $tournament_service Tournament service dependency.
     * @param League_Service         $league_service League service dependency.
     * @param Team_Service           $team_service Team service dependency.
     * @param Club_Service           $club_service Club service dependency.
     * @param League_Query_Service   $league_query_service League query service dependency.
     * @param Action_Guard_Interface $action_guard Action guard dependency.
     *
     * @return void
     */
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
     * Builds the team edit page response by validating permissions, resolving the requested team context, and preparing the view model.
     *
     * @param array $query Request query data used to identify the team, league, tournament, season, and club context.
     * @param array $post Posted form data, currently unused by this method.
     *
     * @return array Response data containing either an error message and message type, or a team page view model.
     * @throws Invalid_Argument_Exception If team is not specified.
     */
    public function team_page( array $query, array $post ): array {
        unset( $post );

        try {
            $this->action_guard->assert_capability( 'edit_teams' );

            $team_id = isset( $query['edit'] ) ? intval( $query['edit'] ) : null;
            if ( ! $team_id ) {
                throw new Invalid_Argument_Exception( esc_html__( 'Team not specified', 'racketmanager' ) );
            }

            $response = array(
                'view_model' => $this->build_view_model( $query, $team_id ),
            );
        } catch ( Exception $e ) {
            $response = $this->error_response( $e->getMessage() );
        }

        return $response;
    }

    /**
     * Build the view model for the team edit page.
     *
     * @param array $query Request query data.
     * @param int   $team_id Team ID.
     *
     * @return Tournament_Team_Page_View_Model Populated team page view model.
     * @throws League_Not_Found_Exception If league is specified but not found.
     * @throws Tournament_Not_Found_Exception If tournament is specified but not found.
     * @throws Team_Not_Found_Exception If team is not found.
     */
    private function build_view_model( array $query, int $team_id ): Tournament_Team_Page_View_Model {
        $league     = $this->resolve_league( $query );
        $season     = '';
        $match_days = array();
        $file       = 'team.php';

        if ( $league ) {
            $season     = isset( $query['season'] ) ? sanitize_text_field( wp_unslash( strval( $query['season'] ) ) ) : '';
            $match_days = Util_Lookup::get_match_days();
            if ( ! empty( $league->event->competition->is_player_entry ) ) {
                $file = 'player-team.php';
            }
        }

        $tournament = $this->resolve_tournament( $query );
        $team       = $this->resolve_team( $team_id, $league, $season );
        $clubs      = $this->club_service->get_clubs();

        return new Tournament_Team_Page_View_Model(
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
        );
    }

    /**
     * Build standard error response array.
     *
     * @param string $message Error message.
     *
     * @return array{message: string, message_type: true}
     */
    private function error_response( string $message ): array {
        return array(
            'message'      => $message,
            'message_type' => true,
        );
    }

    /**
     * Resolve the league from query parameters if specified.
     *
     * @param array $query Request query data.
     *
     * @return object|null Resolved league object, or null if not specified.
     * @throws League_Not_Found_Exception If league_id is specified but not found.
     */
    private function resolve_league( array $query ): ?object {
        if ( ! isset( $query['league_id'] ) ) {
            return null;
        }

        $league_id = intval( $query['league_id'] );
        $league    = $this->league_service->get_league( $league_id );
        if ( ! $league ) {
            throw new League_Not_Found_Exception( esc_html__( 'League not found', 'racketmanager' ) );
        }

        return $league;
    }

    /**
     * Resolve the tournament from query parameters if specified.
     *
     * @param array $query Request query data.
     *
     * @return Tournament|null Resolved tournament, or null if not specified.
     * @throws Tournament_Not_Found_Exception If tournament_id is specified but not found.
     */
    private function resolve_tournament( array $query ): ?Tournament {
        $tournament_id = isset( $query['tournament'] ) ? intval( $query['tournament'] ) : null;
        if ( ! $tournament_id ) {
            return null;
        }

        return $this->tournament_service->get_tournament( $tournament_id );
    }

    /**
     * Resolve team by ID for either league or standalone context.
     *
     * @param int         $team_id Team ID.
     * @param object|null $league Optional league object.
     * @param string      $season Season identifier.
     *
     * @return object Resolved team object.
     * @throws Team_Not_Found_Exception If team cannot be resolved.
     */
    private function resolve_team( int $team_id, ?object $league, string $season ): object {
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
            throw new Team_Not_Found_Exception( esc_html__( 'Team not found', 'racketmanager' ) );
        }

        return $team;
    }
}
