<?php
declare( strict_types=1 );
/**
 * Cup Overview Admin Controller
 *
 * Handles display and actions for cup season overview view.
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\Presenters\Admin_Entry_Link_Presenter;
use Racketmanager\Admin\View_Models\Cup_Overview_Page_View_Model;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Season_Not_Found_Exception;
use Racketmanager\Application\Admin\Ports\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition\Event_Query_Service;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\Competition_Service;

/**
 * Handles the administration overview page for a cup competition season.
 *
 * Builds the page view model by validating the requested competition and season,
 * enforcing the required administrative capability, loading competition events,
 * resolving league draw sizes, preparing season metadata, resolving venue details,
 * collecting club entries, and generating entry management links.
 *
 * The controller returns structured response data for the admin layer, including
 * the populated cup overview view model when the request is valid.
 */
final readonly class Cup_Overview_Admin_Controller {

    /**
     * Initialises the service with required competition, club, event, league, presentation, and action guard dependencies.
     *
     * @param Competition_Service        $competition_service Competition service dependency.
     * @param Club_Service               $club_service Club service dependency.
     * @param Event_Query_Service        $event_query_service Event query service dependency.
     * @param League_Query_Service       $league_query_service League query service dependency.
     * @param Admin_Entry_Link_Presenter $entry_link_presenter Admin entry link presenter dependency.
     * @param Action_Guard_Interface     $action_guard Action guard dependency.
     *
     * @return void
     */
    public function __construct(
        private Competition_Service $competition_service,
        private Club_Service $club_service,
        private Event_Query_Service $event_query_service,
        private League_Query_Service $league_query_service,
        private Admin_Entry_Link_Presenter $entry_link_presenter,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * Builds the cup overview page view model for the requested competition and season.
     *
     * Validates the current user's capability to edit leagues, resolves the target competition and season from
     * the query parameters, loads competition events, draw sizes, season metadata, venue details, club entries,
     * overview data, and entry URLs, then returns the assembled page data.
     *
     * @param array $query Request query data containing the competition and season identifiers.
     * @param array $post Request post data, unused by this method.
     *
     * @return array Page data containing the cup overview view model.
     * @throws Competition_Not_Found_Exception If the requested competition is not found.
     * @throws Season_Not_Found_Exception If the requested season is not found.
     */
    public function overview_page( array $query, array $post ): array {
        unset( $post );

        $this->action_guard->assert_capability( 'edit_leagues' );

        $competition_id = $this->extract_competition_id( $query );
        $season         = $this->extract_season( $query );

        $competition        = $this->competition_service->get_competition_by_season( $competition_id, $season );
        $competition_events = $this->competition_service->get_events_for_competition( $competition_id, $season );
        $draw_sizes         = $this->populate_events_and_resolve_draw_sizes( $competition_events );

        $cup_season           = $this->prepare_cup_season( $competition, $competition_id, $season );
        $competition_overview = $this->competition_service->get_competition_overview( $competition_id, $season );
        $entry_urls           = $this->entry_link_presenter->club_entry_urls( $competition, (string) $season, $cup_season->entries );

        $vm = new Cup_Overview_Page_View_Model(
            competition: $competition,
            season: (string) $season,
            cup_season: $cup_season,
            competition_overview: $competition_overview,
            competition_events: $competition_events,
            draw_sizes: $draw_sizes,
            entry_urls: $entry_urls,
            tab: 'overview',
        );

        return array(
            'view_model' => $vm,
        );
    }

    /**
     * Extracts and validates the competition identifier from query parameters.
     *
     * @param array $query Request query parameters.
     * @return int Validated competition ID.
     * @throws Competition_Not_Found_Exception If competition ID is missing or invalid.
     */
    private function extract_competition_id( array $query ): int {
        $competition_id = isset( $query['competition_id'] ) ? intval( $query['competition_id'] ) : null;
        if ( ! $competition_id ) {
            throw new Competition_Not_Found_Exception( esc_html__( 'Competition not found', 'racketmanager' ) );
        }

        return $competition_id;
    }

    /**
     * Extracts and validates the season identifier from query parameters.
     *
     * @param array $query Request query parameters.
     * @return int Validated season identifier.
     * @throws Season_Not_Found_Exception If season is missing or invalid.
     */
    private function extract_season( array $query ): int {
        $season = isset( $query['season'] ) ? intval( $query['season'] ) : null;
        if ( ! $season ) {
            throw new Season_Not_Found_Exception( esc_html__( 'Season is required', 'racketmanager' ) );
        }

        return $season;
    }

    /**
     * Populates leagues on competition events and calculates draw sizes for each league.
     *
     * @param array $competition_events List of competition events.
     * @return array<int|string, string> Map of league IDs to draw sizes.
     */
    private function populate_events_and_resolve_draw_sizes( array $competition_events ): array {
        $draw_sizes = array();

        foreach ( $competition_events as $event ) {
            $leagues = $this->get_event_leagues( $event );
            if ( ! empty( $leagues ) ) {
                foreach ( $leagues as $league ) {
                    $league_id = is_object( $league ) ? ( $league->id ?? null ) : ( $league['id'] ?? null );
                    if ( null !== $league_id ) {
                        $draw_sizes[ $league_id ] = $this->resolve_league_draw_size( $league );
                    }
                }
                $this->assign_event_leagues( $event, $leagues );
            }
        }

        return $draw_sizes;
    }

    /**
     * Retrieves leagues associated with a given event.
     *
     * @param mixed $event Event entity or object.
     * @return array List of leagues.
     */
    private function get_event_leagues( mixed $event ): array {
        if ( $event instanceof Event ) {
            return $this->event_query_service->get_leagues( $event );
        }

        if ( method_exists( $event, 'get_leagues' ) ) {
            return $event->get_leagues();
        }

        return $event->leagues ?? array();
    }

    /**
     * Assigns populated leagues back to an event.
     *
     * @param mixed $event Event entity or object.
     * @param array $leagues List of leagues.
     * @return void
     */
    private function assign_event_leagues( mixed $event, array $leagues ): void {
        if ( $event instanceof Event ) {
            $event->set_leagues( $leagues );
        } else {
            $event->leagues = $leagues;
        }
    }

    /**
     * Resolves the draw size string for a given league.
     *
     * @param object|array $league League object or array data.
     * @return string Draw size representation.
     */
    private function resolve_league_draw_size( object|array $league ): string {
        if ( is_object( $league ) ) {
            if ( ! empty( $league->is_championship ) && isset( $league->championship->num_teams_first_round ) ) {
                return (string) $league->championship->num_teams_first_round;
            }

            $this->league_query_service->set_num_matches( $league, true );
            return (string) ( $league->num_matches_total ?? '' );
        }

        return (string) ( $league['num_matches_total'] ?? '' );
    }

    /**
     * Prepares and populates cup season metadata, status flags, venue, and entries.
     *
     * @param object $competition Competition entity.
     * @param int    $competition_id Competition identifier.
     * @param int    $season Season identifier.
     * @return object Populated cup season object.
     */
    private function prepare_cup_season( object $competition, int $competition_id, int $season ): object {
        $cup_season_data = $competition->get_season_by_name( (string) $season );
        $cup_season      = (object) ( $cup_season_data ?? array() );

        $cup_season->is_active  = isset( $cup_season->date_closing ) && $cup_season->date_closing <= gmdate( 'Y-m-d' );
        $cup_season->is_open    = false;
        $cup_season->venue_name = isset( $cup_season->venue ) ? $this->resolve_venue_name( $cup_season->venue ) : null;
        $cup_season->entries    = $this->competition_service->get_clubs_for_competition( $competition_id, $season );

        return $cup_season;
    }

    /**
     * Resolves club venue shortcode for a given venue identifier.
     *
     * @param string $venue Venue club identifier.
     * @return string|null Club shortcode or null if not found.
     */
    private function resolve_venue_name( string $venue ): ?string {
        try {
            $venue_club = $this->club_service->get_club( $venue );
            return $venue_club->shortcode;
        } catch ( Club_Not_Found_Exception ) {
            return null;
        }
    }
}
