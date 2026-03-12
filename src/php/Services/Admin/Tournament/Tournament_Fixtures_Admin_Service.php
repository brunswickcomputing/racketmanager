<?php
/**
 * Tournament_Fixtures_Admin_Service class
 *
 * @package RacketManager
 * @subpackage Services/Admin/Tournament
 */

namespace Racketmanager\Services\Admin\Tournament;

use Racketmanager\Admin\View_Models\Tournament_Fixtures_Page_View_Model;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Fixture_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Util\Util;

/**
 * Service to prepare data for the Tournament Fixtures admin page.
 */
class Tournament_Fixtures_Admin_Service {

    /**
     * @param Tournament_Service $tournament_service
     * @param Fixture_Service    $fixture_service
     * @param League_Service     $league_service
     * @param Team_Service       $team_service
     */
    public function __construct(
        private readonly Tournament_Service $tournament_service,
        private readonly Fixture_Service $fixture_service,
        private readonly League_Service $league_service,
        private readonly Team_Service $team_service,
    ) {
    }

    /**
     * Prepares the view model for the tournament fixtures page.
     *
     * @param int|null             $tournament_id
     * @param int|null             $league_id
     * @param string|null          $final_key
     * @param int|null             $fixture_id
     * @param string               $view
     *
     * @return Tournament_Fixtures_Page_View_Model
     * @throws Tournament_Not_Found_Exception
     * @throws Invalid_Status_Exception
     */
    public function prepare_fixtures_view_model( ?int $tournament_id, ?int $league_id, ?string $final_key, ?int $fixture_id, string $view ): Tournament_Fixtures_Page_View_Model {
        $tournament = $this->tournament_service->get_tournament( $tournament_id );
        $season     = $tournament->get_season();

        $league = $this->league_service->get_league( $league_id );
        if ( ! $league ) {
            throw new Invalid_Status_Exception( __( 'League not found', 'racketmanager' ) );
        }

        $is_finals = ! empty( $final_key );
        $data      = array(
            'form_title'   => __( 'Fixtures', 'racketmanager' ),
            'submit_title' => __( 'Fixtures', 'racketmanager' ),
            'fixtures'      => array(),
            'teams'        => array(),
            'max_fixtures'  => 0,
            'home_title'   => '',
            'away_title'   => '',
            'match_day'    => null,
        );

        if ( 'fixture' === $view ) {
            $data = $this->prepare_fixture_view_data( $league, $final_key, $fixture_id );
        } elseif ( $is_finals ) {
            $data = $this->prepare_finals_view_data( $league, $final_key );
        }

        return new Tournament_Fixtures_Page_View_Model(
            league: $league,
            tournament: $tournament,
            competition: $league->event->competition,
            season: strval( $season ),
            form_title: $data['form_title'],
            submit_title: $data['submit_title'],
            fixtures: $data['fixtures'],
            edit: true,
            bulk: false,
            is_finals: $is_finals,
            mode: 'edit',
            teams: $data['teams'],
            single_cup_game: ( 'fixture' === $view ),
            max_fixtures: $data['max_fixtures'],
            final_key: $final_key ?? '',
            home_title: $data['home_title'],
            away_title: $data['away_title'],
            match_day: $data['match_day'],
        );
    }

    /**
     * Prepares data for a single fixture view.
     *
     * @param object      $league
     * @param string|null $final_key
     * @param int|null    $fixture_id
     *
     * @return array<string, mixed>
     * @throws Invalid_Status_Exception
     */
    private function prepare_fixture_view_data( object $league, ?string $final_key, ?int $fixture_id ): array {
        if ( ! $fixture_id ) {
            throw new Invalid_Status_Exception( __( 'Fixture not found', 'racketmanager' ) );
        }

        $fixture = $this->fixture_service->get_fixture( $fixture_id );
        if ( ! $fixture ) {
            throw new Invalid_Status_Exception( __( 'Fixture not found', 'racketmanager' ) );
        }

        $final       = $league->championship->get_finals( $final_key );
        $final_teams = $league->championship->get_final_teams( $final['key'] ?? '' );

        $title = __( 'Edit Fixture', 'racketmanager' );

        return array(
            'form_title'   => $title,
            'submit_title' => $title,
            'fixtures'      => array( $fixture ),
            'match_day'    => $fixture->match_day,
            'max_fixtures'  => 1,
            'home_title'   => $this->resolve_team_title( $fixture->home_team, $final_teams ),
            'away_title'   => $this->resolve_team_title( $fixture->away_team, $final_teams ),
            'teams'        => $final_teams ?? array(),
        );
    }

    /**
     * Prepares data for the finals fixtures view.
     *
     * @param object      $league
     * @param string|null $final_key
     *
     * @return array<string, mixed>
     */
    private function prepare_finals_view_data( object $league, ?string $final_key ): array {
        $final        = $league->championship->get_finals( $final_key );
        $max_fixtures = intval( $final['num_matches'] ?? 0 );

        /* translators: %s: round name */
        $title = sprintf( __( 'Edit Fixtures - %s', 'racketmanager' ), Util::get_final_name( $final_key ) );

        $fixture_args = array(
            'final'   => $final_key,
            'orderby' => array( 'id' => 'ASC' ),
        );

        $has_home_away = ! empty( $league->current_season['home_away'] ) && 'true' === $league->current_season['home_away'];
        if ( 'final' !== $final_key && $has_home_away ) {
            $fixture_args['leg'] = 1;
        }

        return array(
            'form_title'   => $title,
            'submit_title' => $title,
            'fixtures'     => $league->get_matches( $fixture_args ),
            'teams'        => $league->championship->get_final_teams( $final_key ) ?? array(),
            'max_fixtures' => $max_fixtures,
            'home_title'   => '',
            'away_title'   => '',
            'match_day'    => null,
        );
    }

    /**
     * Resolves team title.
     *
     * @param mixed $team_id
     * @param array $final_teams
     *
     * @return string
     */
    private function resolve_team_title( mixed $team_id, array $final_teams ): string {
        if ( is_numeric( $team_id ) ) {
            $team = $this->team_service->get_team_by_id( intval( $team_id ) );
            return $team->title ?? '';
        }

        $team = $final_teams[ $team_id ] ?? null;
        return $team ? $team->title : '';
    }
}
