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
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition\Event_Query_Service;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\Competition_Service;

final readonly class Cup_Overview_Admin_Controller {

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
     * @param array<string, mixed> $query Typically $_GET
     * @param array<string, mixed> $post  Typically $_POST
     *
     * @return array{view_model?: Cup_Overview_Page_View_Model, redirect?: string, message?: string, message_type?: bool|string}
     *
     * @throws Competition_Not_Found_Exception
     * @throws Season_Not_Found_Exception
     */
    public function overview_page( array $query, array $post ): array {
        unset( $post );

        $this->action_guard->assert_capability( 'edit_leagues' );

        $competition_id = isset( $query['competition_id'] ) ? intval( $query['competition_id'] ) : null;
        if ( ! $competition_id ) {
            throw new Competition_Not_Found_Exception( __( 'Competition not found', 'racketmanager' ) );
        }

        $season = isset( $query['season'] ) ? intval( $query['season'] ) : null;
        if ( ! $season ) {
            throw new Season_Not_Found_Exception( __( 'Season is required', 'racketmanager' ) );
        }

        $competition = $this->competition_service->get_competition_by_season( $competition_id, $season );

        $competition_events = $this->competition_service->get_events_for_competition( $competition_id, $season );
        $draw_sizes         = array();
        $i                  = 0;
        foreach ( $competition_events as $event ) {
            $leagues = $event instanceof Event
                ? $this->event_query_service->get_leagues( $event )
                : ( method_exists( $event, 'get_leagues' ) ? $event->get_leagues() : ( $event->leagues ?? array() ) );
            if ( $leagues ) {
                foreach ( $leagues as $league ) {
                    $league_id = is_object( $league ) ? ( $league->id ?? null ) : ( $league['id'] ?? null );
                    if ( null !== $league_id ) {
                        if ( ! empty( $league->is_championship ) && isset( $league->championship->num_teams_first_round ) ) {
                            $draw_sizes[ $league_id ] = (string) $league->championship->num_teams_first_round;
                        } else {
                            if ( is_object( $league ) ) {
                                $this->league_query_service->set_num_matches( $league, true );
                            }
                            $draw_sizes[ $league_id ] = (string) ( $league->num_matches_total ?? '' );
                        }
                    }
                }
                $competition_events[ $i ]->leagues = $leagues;
            }
            ++$i;
        }

        $tab             = 'overview';
        $cup_season_data = $competition->get_season_by_name( (string) $season );
        $cup_season      = is_object( $cup_season_data ) ? $cup_season_data : ( (object) ( $cup_season_data ?? array() ) );
        if ( isset( $cup_season->date_closing ) && $cup_season->date_closing <= gmdate( 'Y-m-d' ) ) {
            $cup_season->is_active = true;
        } else {
            $cup_season->is_active = false;
        }
        $cup_season->is_open    = false;
        $cup_season->venue_name = null;
        if ( isset( $cup_season->venue ) ) {
            try {
                $venue_club             = $this->club_service->get_club( $cup_season->venue );
                $cup_season->venue_name = $venue_club->shortcode;
            } catch ( Club_Not_Found_Exception ) {
                $cup_season->venue_name = null;
            }
        }
        $cup_season->entries  = $this->competition_service->get_clubs_for_competition( $competition_id, $season );
        $competition_overview = $this->competition_service->get_competition_overview( $competition_id, $season );
        $entry_urls           = $this->entry_link_presenter->club_entry_urls( $competition, (string) $season, (array) ( $cup_season->entries ?? array() ) );

        $vm = new Cup_Overview_Page_View_Model(
            competition: $competition,
            season: (string) $season,
            cup_season: $cup_season,
            competition_overview: $competition_overview,
            competition_events: $competition_events,
            draw_sizes: $draw_sizes,
            entry_urls: $entry_urls,
            tab: $tab,
        );

        return array(
            'view_model' => $vm,
        );
    }
}
