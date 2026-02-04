<?php
/**
 * Event_Repository class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories;

use Racketmanager\Domain\DTO\Event_Details_DTO;
use Racketmanager\Domain\Event;
use wpdb;

/**
 * Class to implement the Event repository
 */
class Event_Repository {
    private wpdb $wpdb;
    private string $table_name;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $this->wpdb->prefix . 'racketmanager_events';
    }

    public function save( Event $event ): void {
        if ( empty( $event->get_id() ) ) {
            $this->wpdb->insert(
                $this->table_name,
                array(
                    'name'           => $event->get_name(),
                    'settings'       => maybe_serialize( $event->get_settings() ),
                    // Persist seasons as JSON
                    'seasons'        => $event->get_seasons_json(),
                    'type'           => $event->get_type(),
                    'num_sets'       => $event->get_num_sets(),
                    'num_rubbers'    => $event->get_num_rubbers(),
                    'competition_id' => $event->get_competition_id(),
                ),
                array(
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%d',
                    '%d',
                    '%d',
                )
            );
            $event->set_id( $this->wpdb->insert_id );
        } else {
            $this->wpdb->update(
                $this->table_name,
                array(
                    'name'           => $event->get_name(),
                    'settings'       => maybe_serialize( $event->get_settings() ),
                    // Persist seasons as JSON
                    'seasons'        => $event->get_seasons_json(),
                    'type'           => $event->get_type(),
                    'num_sets'       => $event->get_num_sets(),
                    'num_rubbers'    => $event->get_num_rubbers(),
                    'competition_id' => $event->get_competition_id(),
                ), // Data to update
                array(
                    'id' => $event->get_id()
                ), // Where clause
                array(
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%d',
                    '%d',
                    '%d',
                ),
                array(
                    '%d'
                ) // Where format
            );
        }
    }

    public function find_by_id( $event_id ): ?Event {
        if ( empty( $event_id ) ) {
            return null;
        }
        $event = wp_cache_get( $event_id, 'events' );

        if ( ! $event ) {
            $event = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT * FROM $this->table_name WHERE `id` = %d LIMIT 1",
                    $event_id
                )
            );

            if ( ! $event ) {
                return null;
            }
            $event = new Event( $event );

            wp_cache_set( $event->get_id(), $event, 'events' );
        }

        return $event;
    }

    public function find_by_competition_id( int $competition_id ): array {
        $events = wp_cache_get( md5( $competition_id ), 'events' );
        if ( ! $events ) {
            $results = $this->wpdb->get_results(
                $this->wpdb->prepare(
                    "SELECT * FROM $this->table_name WHERE `competition_id` = %d ORDER BY `name`",
                    $competition_id
                )
            );
            $events = array_map(
                function( $row ) {
                    return new Event( $row );
                    },
                $results
            );
            wp_cache_set( md5( $competition_id ), $events, 'events' );
        }
        return $events;
    }

    public function delete( int $event_id ): void {
        $this->wpdb->delete( $this->table_name, array( 'id' => $event_id ), array( '%d' ) );
    }

    /**
     * Retrieves events for a competition with counts of associated leagues, teams, and clubs.
     *
     * @param int $competition_id
     * @param int $season
     * @param int|null $min_fixtures
     *
     * @return Event_Details_DTO[]
     */
    public function find_events_by_competition_with_counts( int $competition_id, int $season, ?int $min_fixtures = 1): array {
        $events_table = $this->table_name;
        $leagues_table = $this->wpdb->prefix . 'racketmanager_leagues';
        $league_teams_table = $this->wpdb->prefix . 'racketmanager_league_teams';
        $teams_table = $this->wpdb->prefix . 'racketmanager_teams';
        $rubber_players_table = $this->wpdb->prefix . 'racketmanager_rubber_players';
        $rubbers_table = $this->wpdb->prefix . 'racketmanager_rubbers';
        $matches_table = $this->wpdb->prefix . 'racketmanager_matches';

        $player_activity_subquery = $this->wpdb->prepare(
            "SELECT l.event_id, rp.player_id FROM $rubber_players_table rp INNER JOIN $rubbers_table r ON rp.rubber_id = r.id INNER JOIN $matches_table f ON r.match_id = f.id AND f.season = %d INNER JOIN $leagues_table l ON f.league_id = l.id GROUP BY l.event_id, rp.player_id HAVING COUNT(rp.id) >= %d",
            $season,
            $min_fixtures
        );

        $query = $this->wpdb->prepare(
            "SELECT e.id as event_id, e.name as event_name, e.type as format, e.settings as settings, COUNT(DISTINCT l.id) as num_leagues, COUNT(DISTINCT lte.team_id) as num_teams, COUNT(DISTINCT t.club_id) as num_clubs, COUNT(DISTINCT active_players.player_id) as num_players FROM `$events_table` e LEFT JOIN `$leagues_table` l ON e.id = l.event_id LEFT JOIN `$league_teams_table` lte ON l.id = lte.league_id AND lte.season = %d LEFT JOIN `$teams_table` t ON lte.team_id = t.id LEFT JOIN ($player_activity_subquery) AS active_players ON e.id = active_players.event_id WHERE e.competition_id = %d GROUP BY e.id, e.name, e.type ORDER BY e.name",
            $season,
            $competition_id
        );

        $results = $this->wpdb->get_results($query);

        return array_map(function($row) {
            return new Event_Details_DTO( $row );
        }, $results);
    }
}
