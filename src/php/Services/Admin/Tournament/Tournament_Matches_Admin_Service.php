<?php
/**
 * Tournament_Matches_Admin_Service class
 *
 * @package RacketManager
 * @subpackage Services/Admin/Tournament
 */

namespace Racketmanager\Services\Admin\Tournament;

use Racketmanager\Admin\View_Models\Tournament_Matches_Page_View_Model;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Util\Util;
use function Racketmanager\get_league;
use function Racketmanager\get_match;
use function Racketmanager\get_team;

/**
 * Service to prepare data for the Tournament Matches admin page.
 */
class Tournament_Matches_Admin_Service {

    /**
     * @param Tournament_Service $tournament_service
     */
    public function __construct(
        private readonly Tournament_Service $tournament_service
    ) {
    }

    /**
     * Prepares the view model for the tournament matches page.
     *
     * @param int|null             $tournament_id
     * @param int|null             $league_id
     * @param string|null          $final_key
     * @param int|null             $match_id
     * @param string               $view
     *
     * @return Tournament_Matches_Page_View_Model
     * @throws Tournament_Not_Found_Exception
     * @throws Invalid_Status_Exception
     */
    public function prepare_matches_view_model( ?int $tournament_id, ?int $league_id, ?string $final_key, ?int $match_id, string $view ): Tournament_Matches_Page_View_Model {
        $tournament = $this->tournament_service->get_tournament( $tournament_id );
        $season     = $tournament->get_season();

        $league = get_league( $league_id );
        if ( ! $league ) {
            throw new Invalid_Status_Exception( __( 'League not found', 'racketmanager' ) );
        }

        $competition = $league->event->competition;

        $mode      = 'edit';
        $is_finals = ! empty( $final_key );

        $matches      = array();
        $teams        = array();
        $max_matches  = 0;
        $form_title   = __( 'Matches', 'racketmanager' );
        $submit_title = $form_title;
        $home_title   = '';
        $away_title   = '';
        $match_day    = null;

        if ( 'match' === $view ) {
            if ( ! $match_id ) {
                throw new Invalid_Status_Exception( __( 'Match not found', 'racketmanager' ) );
            }

            $match = get_match( $match_id );
            if ( ! $match ) {
                throw new Invalid_Status_Exception( __( 'Match not found', 'racketmanager' ) );
            }

            $form_title   = __( 'Edit Match', 'racketmanager' );
            $submit_title = $form_title;
            $matches      = array( $match );
            $match_day    = $match->match_day;
            $max_matches  = 1;

            $final       = $league->championship->get_finals( $final_key );
            $final_teams = $league->championship->get_final_teams( $final['key'] ?? '' );

            if ( is_numeric( $match->home_team ) ) {
                $home_team  = get_team( intval( $match->home_team ) );
                $home_title = strval( $home_team?->title ?? '' );
            } else {
                $home_team  = $final_teams[ $match->home_team ] ?? null;
                $home_title = strval( $home_team ? $home_team->title : '' );
            }

            if ( is_numeric( $match->away_team ) ) {
                $away_team  = get_team( intval( $match->away_team ) );
                $away_title = strval( $away_team?->title ?? '' );
            } else {
                $away_team  = $final_teams[ $match->away_team ] ?? null;
                $away_title = strval( $away_team ? $away_team->title : '' );
            }

            $teams = $final_teams ?? array();
        } elseif ( $is_finals ) {
            $final       = $league->championship->get_finals( $final_key );
            $max_matches = intval( $final['num_matches'] ?? 0 );

            /* translators: %s: round name */
            $form_title   = sprintf( __( 'Edit Matches - %s', 'racketmanager' ), Util::get_final_name( $final_key ) );
            $submit_title = $form_title;

            $match_args = array(
                'final'   => $final_key,
                'orderby' => array( 'id' => 'ASC' ),
            );
            if ( 'final' !== $final_key && ! empty( $league->current_season['home_away'] ) && 'true' === $league->current_season['home_away'] ) {
                $match_args['leg'] = 1;
            }

            $matches = $league->get_matches( $match_args );
            $teams   = $league->championship->get_final_teams( $final_key ) ?? array();
        }

        return new Tournament_Matches_Page_View_Model(
            league: $league,
            tournament: $tournament,
            competition: $competition,
            season: strval( $season ),
            form_title: $form_title,
            submit_title: $submit_title,
            matches: $matches,
            edit: true,
            bulk: false,
            is_finals: $is_finals,
            mode: $mode,
            teams: $teams,
            single_cup_game: ( 'match' === $view ),
            max_matches: $max_matches,
            final_key: strval( $final_key ?? '' ),
            home_title: $home_title,
            away_title: $away_title,
            match_day: $match_day,
        );
    }
}
