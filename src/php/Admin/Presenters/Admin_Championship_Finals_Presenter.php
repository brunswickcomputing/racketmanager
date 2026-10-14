<?php
/**
 * Admin championship finals presenter
 *
 * @package RacketManager
 * @subpackage Admin/Presenters
 */

declare( strict_types=1 );

namespace Racketmanager\Admin\Presenters;

use Racketmanager\Admin\View_Models\Admin_Championship_Final_Fixture_View_Model;

/**
 * Maps championship finals to preformatted fixture view models.
 */
final class Admin_Championship_Finals_Presenter {

    public function __construct(
        private Admin_Date_Presenter $date_presenter,
    ) {
    }

    /**
     * @param array        $finals
     * @param object|null  $tournament
     * @param object       $league
     * @param string       $season
     *
     * @return array
     */
    public function present_finals( array $finals, ?object $tournament, object $league, string $season ): array {
        $presented = array();
        foreach ( $finals as $final_key => $final ) {
            $final_obj = is_object( $final ) ? clone $final : (object) $final;
            $fixtures  = $final_obj->fixtures ?? array();
            $presented_fixtures = array();

            foreach ( $fixtures as $fixture_details ) {
                if ( ! is_object( $fixture_details ) ) {
                    continue;
                }
                $presented_fixtures[] = $this->present_fixture_details( $fixture_details, $tournament, $league, $season );
            }

            $final_obj->fixtures     = $presented_fixtures;
            $presented[ $final_key ] = $final_obj;
        }

        return $presented;
    }

    public function present_fixture_details( object $fixture_details, ?object $tournament, object $league, string $season ): Admin_Championship_Final_Fixture_View_Model {
        $match       = $fixture_details->fixture ?? (object) array();
        $comp        = $fixture_details->competition ?? ( $league->event->competition ?? (object) array() );
        $evt         = $fixture_details->event ?? ( $league->event ?? (object) array() );
        $lg          = $fixture_details->league ?? $league;
        $home_team   = $fixture_details->home_team->team ?? ( $fixture_details->home_team ?? null );
        $away_team   = $fixture_details->away_team->team ?? ( $fixture_details->away_team ?? null );

        $formatted_date = ( isset( $match->date ) && '' !== $match->date && '0000-00-00' !== $match->date && '0000-00-00 00:00:00' !== $match->date )
            ? $this->date_presenter->format_date( (string) $match->date )
            : 'N/A';

        $hour_min       = ( $match->hour ?? '' ) . ':' . ( $match->minutes ?? '' );
        $formatted_time = ( '00:00' === $hour_min || ! isset( $match->hour ) || '' === $match->hour || empty( $match->date ) )
            ? 'N/A'
            : $this->date_presenter->format_time( (string) $match->date );

        $formatted_location = ( isset( $match->location ) && '' !== $match->location )
            ? (string) $match->location
            : 'N/A';

        $home_name = is_object( $home_team ) && method_exists( $home_team, 'get_name' )
            ? $home_team->get_name()
            : (string) ( $home_team->name ?? $home_team->title ?? '' );

        $away_name = is_object( $away_team ) && method_exists( $away_team, 'get_name' )
            ? $away_team->get_name()
            : (string) ( $away_team->name ?? $away_team->title ?? '' );

        $teams_label = trim( $home_name . ' - ' . $away_name );

        $home_class = '';
        $away_class = '';
        $home_tip   = '';
        $away_tip   = '';

        $winner_id = ! empty( $match->winner_id ) ? (int) $match->winner_id : null;
        $home_id   = isset( $match->home_team ) && is_numeric( $match->home_team ) ? (int) $match->home_team : null;
        $away_id   = isset( $match->away_team ) && is_numeric( $match->away_team ) ? (int) $match->away_team : null;

        if ( null !== $winner_id && null !== $home_id && $winner_id === $home_id ) {
            $home_class = 'winner';
            $home_tip   = 'Match winner';
        } elseif ( null !== $winner_id && null !== $away_id && $winner_id === $away_id ) {
            $away_class = 'winner';
            $away_tip   = 'Match winner';
        } elseif ( isset( $match->host ) ) {
            if ( 'home' === $match->host ) {
                $home_class = 'host';
                $home_tip   = 'Home team';
            } elseif ( 'away' === $match->host ) {
                $away_class = 'host';
                $away_tip   = 'Home team';
            }
        }

        $formatted_score = ( isset( $match->home_points ) && null !== $match->home_points && isset( $match->away_points ) && null !== $match->away_points )
            ? sprintf( '%d-%d', (int) $match->home_points, (int) $match->away_points )
            : '-';

        $is_tournament = ! empty( $comp->is_tournament );
        $is_cup        = ! empty( $comp->is_cup );

        $match_id      = (string) ( $match->id ?? '' );
        $match_season  = (string) ( $match->season ?? $season );
        $match_final   = (string) ( $match->final ?? '' );
        $league_id     = (string) ( $lg->id ?? '' );

        if ( $is_tournament ) {
            $tournament_id = (string) ( $tournament->id ?? '' );
            $match_link    = 'admin.php?page=racketmanager-tournaments&view=fixture&tournament=' . $tournament_id . '&league=' . $league_id . '&edit=' . $match_id . '&season=' . $match_season . '&final=' . $match_final;
        } elseif ( $is_cup ) {
            $competition_id = (string) ( $comp->id ?? '' );
            $match_link     = 'admin.php?page=racketmanager-cups&view=match&competition_id=' . $competition_id . '&league=' . $league_id . '&edit=' . $match_id . '&season=' . $match_season . '&final=' . $match_final;
        } else {
            $match_link     = 'admin.php?page=racketmanager&subpage=match&league=' . $league_id . '&edit=' . $match_id . '&season=' . $match_season;
        }

        return new Admin_Championship_Final_Fixture_View_Model(
            fixture_details: $fixture_details,
            fixture: $match,
            league: $lg,
            event: $evt,
            competition: $comp,
            home_team: $home_team,
            away_team: $away_team,
            formatted_date: $formatted_date,
            formatted_time: $formatted_time,
            formatted_location: $formatted_location,
            match_link: $match_link,
            teams_label: $teams_label,
            home_name: $home_name,
            away_name: $away_name,
            home_class: $home_class,
            away_class: $away_class,
            home_tip: $home_tip,
            away_tip: $away_tip,
            formatted_score: $formatted_score,
        );
    }
}
