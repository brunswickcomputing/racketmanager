<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Competition\Queries;

use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Season_Not_Found_Exception;
use Racketmanager\Presentation\Presenters\Competition_Shortcode_Presenter;
use Racketmanager\Presentation\Presenters\Tournament_Presenter;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Presentation\View_Models\Team_Matches_Group_View_Model;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition\Competition_Season_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Player_Stats_Service;
use function is_numeric;
use function method_exists;
use function Racketmanager\un_seo_url;
use function substr;
use function __;

/**
 * Handler for resolving [competition-clubs] shortcode data into a view model.
 */
class Get_Competition_Clubs_Handler {
    public function __construct(
        private readonly Competition_Service $competition_service,
        private readonly Club_Service $club_service,
        private readonly Player_Stats_Service $player_stats_service,
        private readonly Tournament_Presenter $tournament_presenter,
        private readonly Competition_Shortcode_Presenter $competition_presenter,
        private readonly Competition_Season_Service $competition_season_service
    ) {}

    public function handle( Get_Competition_Clubs_Query $query ): Shortcode_View_Model {
        $comp_id = $query->competition_id ?? $query->competition_name;
        if ( null === $comp_id || '' === $comp_id ) {
            return Shortcode_View_Model::error( __( 'Competition not found', 'racketmanager' ) );
        }

        if ( is_string( $comp_id ) ) {
            $comp_id = un_seo_url( $comp_id );
        }

        try {
            $competition = $this->competition_service->get_by_id( $comp_id );
        } catch ( Competition_Not_Found_Exception $e ) {
            return Shortcode_View_Model::error( $e->getMessage() );
        }

        if ( ! $competition ) {
            return Shortcode_View_Model::error( __( 'Competition not found', 'racketmanager' ) );
        }

        $this->competition_season_service->resolve_current_season( $competition, (string) ( $query->season ?? '' ) );

        $season_name = isset( $competition->current_season['name'] ) ? (string) $competition->current_season['name'] : null;
        $season_int  = $query->season ?? ( ( null !== $season_name && is_numeric( $season_name ) ) ? (int) $season_name : null );

        $clubs            = null;
        $competition_club = null;

        if ( ! empty( $query->club_identifier ) ) {
            try {
                if ( is_numeric( $query->club_identifier ) ) {
                    $competition_club = $this->club_service->get_club( (int) $query->club_identifier );
                } else {
                    $competition_club = $this->club_service->get_club_by_shortcode( $query->club_identifier );
                }
            } catch ( Club_Not_Found_Exception ) {
                $competition_club = null;
            }

            if ( $competition_club ) {
                $club_id = method_exists( $competition_club, 'get_id' ) ? $competition_club->get_id() : ( $competition_club->id ?? null );
                $competition_club->teams = $this->competition_service->get_teams_by_query(
                    $competition,
                    [
                        'club'   => $club_id,
                        'season' => $season_name,
                        'status' => 1,
                    ]
                );

                $competition_club->matches = [];
                $matches = $this->competition_service->get_matches_for_competition(
                    $competition,
                    [
                        'season'  => $season_name,
                        'club'    => $club_id,
                        'time'    => 'next',
                        'orderby' => [
                            'date'      => 'ASC',
                            'league_id' => 'DESC',
                        ],
                    ]
                );
                foreach ( $matches as $match ) {
                    $key = substr( (string) $match->date, 0, 10 );
                    if ( ! isset( $competition_club->matches[ $key ] ) ) {
                        $competition_club->matches[ $key ] = [];
                    }
                    $competition_club->matches[ $key ][] = $match;
                }

                $competition_club->results = [];
                $results_matches = $this->competition_service->get_matches_for_competition(
                    $competition,
                    [
                        'season'  => $season_name,
                        'club'    => $club_id,
                        'time'    => 'latest',
                        'orderby' => [
                            'date'      => 'ASC',
                            'league_id' => 'DESC',
                        ],
                    ]
                );
                foreach ( $results_matches as $match ) {
                    $key = substr( (string) $match->date, 0, 10 );
                    if ( ! isset( $competition_club->results[ $key ] ) ) {
                        $competition_club->results[ $key ] = [];
                    }
                    $competition_club->results[ $key ][] = $match;
                }

                foreach ( $competition_club->matches as $key => $group_matches ) {
                    $competition_club->matches[ $key ] = $this->tournament_presenter->present_match_group(
                        $key,
                        $group_matches,
                        $this->tournament_presenter->is_tournament_competition( $competition ),
                        [
                            'current_club' => $club_id,
                            'show_header'  => true,
                            'by_date'      => true,
                        ]
                    );
                }
                foreach ( $competition_club->results as $key => $group_matches ) {
                    $competition_club->results[ $key ] = $this->tournament_presenter->present_match_group(
                        $key,
                        $group_matches,
                        $this->tournament_presenter->is_tournament_competition( $competition ),
                        [
                            'current_club' => $club_id,
                            'show_header'  => true,
                            'by_date'      => true,
                        ]
                    );
                }

                $competition_club->players = $this->competition_service->get_players_for_competition_query(
                    $competition,
                    [
                        'club'   => $club_id,
                        'season' => $season_name,
                    ]
                );
                $competition_club->players = $this->player_stats_service->populate_players_stats(
                    $competition_club->players,
                    $competition,
                    $season_name,
                    'competition'
                );
            } else {
                return Shortcode_View_Model::error( __( 'Club not found', 'racketmanager' ) );
            }
        } else {
            try {
                $clubs = $this->competition_service->get_club_details_for_competition( (int) $competition->get_id(), $season_int );
            } catch ( Season_Not_Found_Exception $e ) {
                return Shortcode_View_Model::error( $e->getMessage() );
            }
        }

        $vars = [
            'competition'      => $competition,
            'competition_club' => $competition_club,
            'clubs'            => $clubs,
        ];

        if ( $competition_club ) {
            $club_details         = $this->competition_presenter->present_competition_club_detail_links( $competition, $competition_club );
            $vars['club_url']     = $club_details['club_url'];
            $vars['team_links']   = $club_details['team_links'];
            $vars['player_links'] = $club_details['player_links'];
        } else {
            $vars['club_links']   = $this->competition_presenter->present_competition_club_links( $competition, $clubs ?? [] );
        }

        $template = '' !== $query->template ? 'clubs-' . $query->template : 'clubs';

        return new Shortcode_View_Model(
            template: $template,
            vars: $vars,
            template_type: 'competition'
        );
    }
}
