<?php
declare( strict_types=1 );
/**
 * League Matches Admin Controller
 *
 * Handles view=matches on league screens.
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\View_Models\League_Matches_Page_View_Model;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Exceptions\Event_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Season_Not_Found_Exception;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition\Event_Query_Service;

final readonly class League_Matches_Admin_Controller {

    public function __construct(
        private Event_Repository_Interface $event_repository,
        private Event_Query_Service $event_query_service,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * @param array<string, mixed> $query Typically $_GET
     * @param array<string, mixed> $post  Typically $_POST
     *
     * @return array{view_model:League_Matches_Page_View_Model}
     *
     * @throws Invalid_Status_Exception
     * @throws Event_Not_Found_Exception
     * @throws Season_Not_Found_Exception
     */
    public function matches_page( array $query, array $post ): array {
        unset( $post );

        $this->action_guard->assert_capability( 'edit_leagues' );

        $event_id = isset( $query['event_id'] ) ? intval( $query['event_id'] ) : 0;
        if ( ! $event_id ) {
            throw new Event_Not_Found_Exception( __( 'Event not found', 'racketmanager' ) );
        }

        $event = $this->event_repository->find_by_id( $event_id );
        if ( ! $event instanceof Event ) {
            throw new Event_Not_Found_Exception( __( 'Event not found', 'racketmanager' ) );
        }

        $seasons = $event->get_seasons();
        if ( empty( $seasons ) ) {
            throw new Season_Not_Found_Exception( __( 'You need to add at least one season for the competition', 'racketmanager' ) );
        }

        $season = isset( $query['season'] ) && '' !== $query['season'] ? intval( $query['season'] ) : null;
        if ( null === $season ) {
            $current_season = $event->current_season['name'] ?? null;
            $season         = null !== $current_season ? intval( $current_season ) : intval( array_key_first( $seasons ) );
        }

        $season_names = array_map(
            static fn( $key, $s ): string => (string) ( is_array( $s ) ? ( $s['name'] ?? $key ) : $key ),
            array_keys( $seasons ),
            $seasons
        );
        if ( ! in_array( (string) $season, $season_names, true ) ) {
            throw new Season_Not_Found_Exception( __( 'Season not found', 'racketmanager' ) );
        }

        $matches = $this->event_query_service->get_matches(
            $event,
            array(
                'event_id' => $event->id,
                'season'   => $season,
                'orderby'  => array(
                    'match_day' => 'ASC',
                    'date'      => 'ASC',
                    'home_team' => 'ASC',
                    'league_id' => 'ASC',
                ),
            )
        );

        $vm = new League_Matches_Page_View_Model(
            event: $event,
            season: (string) $season,
            matches: is_array( $matches ) ? $matches : array(),
            tab: 'matches',
        );

        return array(
            'view_model' => $vm,
        );
    }
}
