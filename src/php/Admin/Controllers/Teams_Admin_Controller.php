<?php
declare( strict_types=1 );
/**
 * Teams Admin Controller
 *
 * Handles the "add teams" screen (view=teams) for tournaments, leagues/constitutions and cups.
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\View_Models\Teams_List_Page_View_Model;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\League_Team_Admin_Service;
use Racketmanager\Application\Admin\Ports\Action_Guard_Interface;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Tournament_Service;

/**
 * Controller for the admin teams management screen.
 *
 * Coordinates access control, request handling, league and tournament lookup,
 * team addition, redirect generation, and view model creation for screens that
 * allow administrators to add teams to a league-based competition context.
 *
 * Supports GET requests by returning a teams list view model containing the
 * league, season, tournament context, and teams that can be added. Supports
 * POST requests by validating the action, adding the selected teams through the
 * league team administration service, and returning a post-redirect-get response
 * payload with a status message.
 *
 * @package RacketManager
 * @subpackage Admin\Controllers
 */
final readonly class Teams_Admin_Controller {

    /**
     * Initialises the service with required tournament, league, team administration, and action guard dependencies.
     *
     * @param Tournament_Service        $tournament_service Tournament service dependency.
     * @param League_Service            $league_service League service dependency.
     * @param League_Team_Admin_Service $league_team_admin_service League team administration service dependency.
     * @param Action_Guard_Interface    $action_guard Action guard dependency.
     *
     * @return void
     */
    public function __construct(
        private Tournament_Service $tournament_service,
        private League_Service $league_service,
        private League_Team_Admin_Service $league_team_admin_service,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * Handles the teams administration page by validating access, processing bulk team additions, or preparing the teams list view model.
     *
     * @param array              $query Request query parameters used to resolve league, season, tournament, event, and type context.
     * @param array              $post Posted form data used when adding selected teams to the league.
     * @param Admin_Page_Context $context Administrative page context used when building redirect URLs.
     *
     * @return array Response data containing either redirect and status message details after a POST request, or a view model for rendering the page.
     * @throws Invalid_Status_Exception If the league is not found.
     */
    public function teams_page( array $query, array $post, Admin_Page_Context $context ): array {
        $this->action_guard->assert_capability( 'edit_teams' );

        $request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
        $is_post        = 'POST' === strtoupper( $request_method );

        $league_id     = intval( $query['league_id'] ?? $query['league'] ?? $post['league_id'] ?? 0 );
        $season        = sanitize_text_field( wp_unslash( strval( $query['season'] ?? $post['season'] ?? '' ) ) );
        $tournament_id = intval( $query['tournament'] ?? $post['tournament_id'] ?? 0 );
        $event_id      = intval( $query['event_id'] ?? $post['event_id'] ?? 0 );
        $type          = isset( $query['type'] ) ? sanitize_text_field( wp_unslash( strval( $query['type'] ) ) ) : null;

        if ( ! $league_id ) {
            throw new Invalid_Status_Exception( esc_html__( 'League not found', 'racketmanager' ) );
        }

        $league = $this->league_service->get_league( $league_id );
        if ( ! $league instanceof League ) {
            throw new Invalid_Status_Exception( esc_html__( 'League not found', 'racketmanager' ) );
        }

        $tournament = null;
        if ( $tournament_id ) {
            $tournament = $this->tournament_service->get_tournament( $tournament_id );
        }

        // POST: add selected teams, then PRG redirect back to the originating screen.
        if ( $is_post ) {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_add-teams-bulk', 'edit_teams' );

            $team_ids = array_map( 'strval', (array) ( $post['team'] ?? array() ) );
            $outcome  = $this->league_team_admin_service->add_teams( $league, $team_ids, (int) $season );

            $competition_id = isset( $league->event->competition->id ) ? (int) $league->event->competition->id : null;
            $redirect       = Admin_Redirect_Url_Builder::teams_view(
                $query,
                $post,
                $context,
                $league_id,
                $tournament_id ? $tournament_id : null,
                $event_id ? $event_id : null,
                '' !== $season ? $season : null,
                $type,
                $competition_id
            );

            if ( $outcome['errors'] ) {
                return array(
                    'redirect'     => $redirect,
                    'message'      => implode( '<br>', $outcome['errors'] ),
                    'message_type' => true,
                );
            }

            return array(
                'redirect'     => $redirect,
                /* translators: %d: number of teams */
                'message'      => sprintf( _n( '%d team added', '%d teams added', $outcome['count'], 'racketmanager' ), $outcome['count'] ),
                'message_type' => false,
            );
        }

        // GET: list addable teams.
        $vm = new Teams_List_Page_View_Model(
            league: $league,
            league_id: $league_id,
            season: $season,
            tournament_id: $tournament_id,
            tournament: $tournament,
            teams: $this->league_team_admin_service->get_addable_teams( $league, $season ),
            type: $type,
        );

        return array(
            'view_model' => $vm,
        );
    }
}
