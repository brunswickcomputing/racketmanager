<?php
declare( strict_types=1 );

/**
 * Admin League Schedule Presenter
 *
 * @package RacketManager
 * @subpackage Admin/Presenters
 */

namespace Racketmanager\Admin\Presenters;

use Racketmanager\Admin\View_Models\Admin_League_Schedule_Event_View_Model;
use Racketmanager\Admin\View_Models\Admin_League_Schedule_Page_View_Model;
use function is_array;
use function is_object;
use function method_exists;

final readonly class Admin_League_Schedule_Presenter {

    public function __construct() {
    }

    /**
     * Present the league schedule page view model.
     *
     * @param object $competition
     * @param string $season
     * @param array $events List of event entities or structured event data arrays.
     *
     * @return Admin_League_Schedule_Page_View_Model
     */
    public function present(
        object $competition,
        string $season,
        array $events,
    ): Admin_League_Schedule_Page_View_Model {
        $competition_id = (int) ( method_exists( $competition, 'get_id' ) ? $competition->get_id() : ( $competition->id ?? 0 ) );
        $comp_type      = method_exists( $competition, 'get_type' ) ? $competition->get_type() : ( $competition->type ?? 'league' );

        $schedule_events = array();
        foreach ( $events as $event_item ) {
            if ( $event_item instanceof Admin_League_Schedule_Event_View_Model ) {
                $schedule_events[] = $event_item;
                continue;
            }

            if ( is_array( $event_item ) ) {
                $event_id               = (int) ( $event_item['id'] ?? 0 );
                $event_name             = (string) ( $event_item['name'] ?? '' );
                $event_season           = (string) ( $event_item['season'] ?? $season );
                $match_count            = (int) ( $event_item['match_count'] ?? 0 );
                $match_completion_count = (int) ( $event_item['match_completion_count'] ?? 0 );
            } elseif ( is_object( $event_item ) ) {
                $event_id               = (int) ( method_exists( $event_item, 'get_id' ) ? $event_item->get_id() : ( $event_item->id ?? 0 ) );
                $event_name             = (string) ( method_exists( $event_item, 'get_name' ) ? $event_item->get_name() : ( $event_item->name ?? '' ) );
                $event_season           = (string) ( method_exists( $event_item, 'get_season' ) ? $event_item->get_season() : ( $event_item->season ?? $season ) );
                $match_count            = (int) ( $event_item->match_count ?? 0 );
                $match_completion_count = (int) ( $event_item->match_completion_count ?? 0 );
            } else {
                continue;
            }

            if ( '' === $event_season ) {
                $event_season = $season;
            }

            $event_link        = 'admin.php?page=racketmanager-' . $comp_type . 's&amp;view=event&amp;competition_id=' . $competition_id . '&amp;event_id=' . $event_id . '&amp;season=' . $event_season;
            $can_send_fixtures = ( $match_count > 0 ) && ( 0 === $match_completion_count );

            $schedule_events[] = new Admin_League_Schedule_Event_View_Model(
                id: $event_id,
                name: $event_name,
                season: $event_season,
                event_link: $event_link,
                can_send_fixtures: $can_send_fixtures,
            );
        }

        return new Admin_League_Schedule_Page_View_Model(
            competition: $competition,
            season: $season,
            competition_id: $competition_id,
            events: $schedule_events,
        );
    }
}
