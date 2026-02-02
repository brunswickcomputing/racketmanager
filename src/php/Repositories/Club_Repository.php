<?php
/**
 * Club_Repository class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories;

use Racketmanager\Domain\Club;
use Racketmanager\Domain\DTO\Club_Competition_DTO;
use Racketmanager\Domain\Team;
use Racketmanager\Util\Util;
use wpdb;

/**
 * Class to implement the Club repository
 */
class Club_Repository {
    private wpdb $wpdb;
    private string $table_name;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $this->wpdb->prefix . 'racketmanager_clubs';
    }

    /**
     * Inserts a new club into the database.
     * The save action is explicit, not in the Club constructor.
     * @param Club $club The club object to save.
     */
    public function save( Club $club ): void {
        //`id`, `name`, `website`, `type`, `address`, `contactno`, `founded`, `facilities`, `shortcode`
        if ( $club->get_id() === null ) {
            $this->wpdb->insert(
                $this->table_name,
                array(
                    'name'       => $club->get_name(),
                    'website'    => $club->get_website(),
                    'type'       => $club->get_type(),
                    'address'    => $club->get_address(),
                    'contactno'  => $club->get_contact_no(),
                    'founded'    => $club->get_founded(),
                    'facilities' => $club->get_facilities(),
                    'shortcode'  => $club->get_shortcode(),
                ),
                array(
                    '%s', // Format for name (string)
                    '%s', // Format for website (string)
                    '%s', // Format for type (string)
                    '%s', // Format for address (string)
                    '%s', // Format for contactno (string)
                    '%s', // Format for founded (string)
                    '%s', // Format for facilities (string)
                    '%s', // Format for shortcode (string)
                )
            );
            $club->set_id( $this->wpdb->insert_id );
        } else {
            // UPDATE: Use wpdb->update with the prepare logic built-in
            $this->wpdb->update(
                $this->table_name,
                array( 'name'       => $club->get_name(),
                       'website'    => $club->get_website(),
                       'type'       => $club->get_type(),
                       'address'    => $club->get_address(),
                       'contactno'  => $club->get_contact_no(),
                       'founded'    => $club->get_founded(),
                       'facilities' => $club->get_facilities(),
                       'shortcode'  => $club->get_shortcode()
                    ), // Data to update
                array('id' => $club->get_id() ),            // Where clause
                array( '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s'
                ),                                          // Data format
                array('%d')                                 // Where format
            );
        }
        wp_cache_flush_group( 'clubs' );
    }

    /**
     * Retrieves an existing club from the database by ID.
     *
     * @param int|string|null $id The user ID.
     * @param string $search_term
     *
     * @return Club|null The user object or null if not found.
     */
    public function find( null|int|string $id, string $search_term = 'id' ): ?Club {
        if ( empty( $id ) ) {
            return null;
        }
        $search = match ( $search_term ) {
            'name'      => $this->wpdb->prepare(
                '`name` = %s',
                $id
            ),
            'shortcode' => $this->wpdb->prepare(
                '`shortcode` = %s',
                $id
            ),
            default     => $this->wpdb->prepare(
                '`id` = %d',
                $id
            ),
        };
        $club_data = wp_cache_get( $id, 'clubs' );
        if ( ! $club_data ) {

            // Prepare the query safely using prepare() to prevent SQL injection
            $query = "SELECT `id`, `name`, `website`, `type`, `address`, `contactno`, `founded`, `facilities`, `shortcode` FROM $this->table_name WHERE " . $search . " LIMIT 1";

            $club_data = $this->wpdb->get_row( $query ); // Get a single row as an object
            if ( $club_data ) {
                wp_cache_set( $id, $club_data, 'clubs' );
            }
        }

        if ( $club_data ) {
            // Instantiate and return a new Club object with the fetched data
            return new Club( $club_data );
        }

        return null; // Club not found
    }

    /**
     * Get teams for a club
     *
     * @param array $args query arguments.
     *
     * @return array|int
     */
    public function get_teams( array $args = array() ): array|int {
        $teams_table = $this->wpdb->prefix . 'racketmanager_teams';
        $defaults = array(
            'club'    => false,
            'count'   => false,
            'players' => false,
            'type'    => false,
        );
        $args     = array_merge( $defaults, $args );
        $club     = $args['club'];
        $count    = $args['count'];
        $players  = $args['players'];
        $type     = $args['type'];

        $search_terms = array();
        $sql    = " FROM $teams_table WHERE `club_id` = '%d'";
        $search_terms[] = $club;
        if ( ! $players ) {
            $sql .= " AND (`team_type` is null OR `team_type` != 'P')";
        } else {
            $sql .= " AND `team_type` = 'P'";
        }
        if ( $type ) {
            if ( 'OS' === $type ) {
                $sql   .= " AND `type` like '%%%s%%'";
                $search_terms[] = 'S';
            } else {
                $sql   .= " AND `type` = '%s'";
                $search_terms[] = $type;
            }
        }
        if ( $count ) {
            $sql = 'SELECT COUNT(*) ' . $sql;
            return $this->wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
                $this->wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                    $sql,
                    $search_terms
                )
            );
        }
        $sql  = 'SELECT * ' . $sql . ' ORDER BY `title`';
        $sql  = $this->wpdb->prepare(
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $sql,
            $search_terms
        );

        $teams = wp_cache_get( md5( $sql ), 'teams' );
        if ( ! $teams ) {
            $teams = $this->wpdb->get_results( $sql );
            wp_cache_set( md5( $sql ), $teams, 'teams' );
        }

        return array_map(
            function( $row ) {
                return new Team( $row );
                },
            $teams
        );
    }

    /**
     * Retrieves existing clubs from the database by parameters
     * replaces the $racketmanager->get_clubs function.
     *
     * @param array $args search arguments.
     *
     * @return array|int array of clubs.
     */
    public function find_all( array $args = array() ): array|int {
        $defaults = array(
            'type'    => 'affiliated',
            'count'   => false,
            'orderby' => 'asc',
        );
        $args     = array_merge( $defaults, $args );
        $type     = $args['type'];
        $count    = $args['count'];
        $orderby  = $args['orderby'];

        $search_terms = array();
        if ( $type && 'all' !== $type ) {
            if ( 'current' === $type ) {
                $search_terms[] = "`type` != 'past'";
            } else {
                $search_terms[] = $this->wpdb->prepare( '`type` = %s', $type );
            }
        }
        $search = Util::search_string( $search_terms, true );
        if ( $count ) {
            $sql    = "SELECT COUNT(*) FROM $this->table_name $search";
            $count  = wp_cache_get( md5( $sql ), 'clubs' );
            if ( ! $count ) {
                $count = $this->wpdb->get_var( $sql );
                wp_cache_set( md5( $sql ), $count, 'clubs' );
            }
            return $count;
        }
        switch ( $orderby ) {
            case 'asc':
                $order = '`name` ASC';
                break;
            case 'desc':
                $order = '`name` DESC';
                break;
            case 'rand':
                $order = 'RAND()';
                break;
            case 'menu_order':
                $order = '`id` ASC';
                break;
            default:
                break;
        }
        $order  = empty( $order ) ? null : 'ORDER BY ' . $order;
        $sql    = "SELECT * FROM $this->table_name $search $order";
        $clubs  = wp_cache_get( md5( $sql ), 'clubs' );
        if ( ! $clubs ) {
            $results = $this->wpdb->get_results( $sql );
            $clubs   = array_map(
                function( $row ) {
                    return new Club( $row );
                    },
                $results
            );
            wp_cache_set( md5( $sql ), $clubs, 'clubs' );
        }
        return $clubs;
    }

    /**
     * Delete club from the database.
     *
     * @param int $club_id
     *
     * @return bool
     */
    public function delete( int $club_id ): bool {
        $result = $this->wpdb->query(
            $this->wpdb->prepare(
                "DELETE FROM $this->table_name WHERE `id` = %d",
                $club_id
            )
        );
        wp_cache_flush_group( 'clubs' );
        return $result !== false;
    }

    /**
     * Gets all clubs for a specific competition and season with aggregated counts.
     *
     * @param int $competition_id
     * @param string $season (e.g., "2025/26")
     * @param int $min_fixtures Minimum rubbers played to be 'active'.
     *
     * @return Club_Competition_DTO[]
     */
    public function find_clubs_by_competition_and_season( int $competition_id, string $season, int $min_fixtures = 1 ): array {
        $clubs_table          = $this->wpdb->prefix . 'racketmanager_clubs';
        $events_table         = $this->wpdb->prefix . 'racketmanager_events';
        $leagues_table        = $this->wpdb->prefix . 'racketmanager_leagues';
        $league_teams_table   = $this->wpdb->prefix . 'racketmanager_league_teams';
        $teams_table          = $this->wpdb->prefix . 'racketmanager_teams';
        $rubber_players_table = $this->wpdb->prefix . 'racketmanager_rubber_players';
        $rubbers_table        = $this->wpdb->prefix . 'racketmanager_rubbers';
        $fixtures_table       = $this->wpdb->prefix . 'racketmanager_matches';

        $query = $this->wpdb->prepare(
            "SELECT
                    cl.id as club_id,
                    cl.name as club_name,
                    cl.shortcode as club_shortcode,
                    COUNT(DISTINCT t.id) as num_teams,
                    (SELECT COUNT(DISTINCT rp.player_id)
                     FROM `$rubber_players_table` rp,
                          `$rubbers_table` r,
                          `$fixtures_table` f,
                          `$leagues_table` l_active,
                          `$events_table` e_active,
                          `$league_teams_table` lte_active,
                          `$teams_table` t_active
                     WHERE e_active.competition_id = %d
                       AND f.season = %s
                       AND rp.rubber_id = r.id
                       AND r.match_id = f.id
                       AND f.league_id = l_active.id
                       AND l_active.event_id = e_active.id
                       AND t_active.id = lte_active.team_id
                       AND lte_active.league_id = l_active.id
                       AND lte_active.season = %s
                       AND (
                           (rp.player_team = 'home' AND f.home_team = t_active.id) OR
                            (rp.player_team = 'away' AND f.away_team = t_active.id)
                           )
                       AND t_active.club_id = cl.id
                     HAVING COUNT(rp.id) >= %d
                    ) as num_players
                FROM
                    `$clubs_table` cl
                INNER JOIN `$teams_table` t ON cl.id = t.club_id
                INNER JOIN `$league_teams_table` lte ON t.id = lte.team_id
                INNER JOIN `$leagues_table` l ON lte.league_id = l.id
                INNER JOIN `$events_table` e ON l.event_id = e.id
                WHERE
                    e.competition_id = %d AND lte.season = %s
                GROUP BY
                    cl.id, cl.name, cl.shortcode
                ORDER BY
                    cl.name",
            $competition_id,
            $season,
            $season,
            $min_fixtures,
            $competition_id,
            $season
        );

        $results = $this->wpdb->get_results( $query );

        return array_map( fn( $row ) => new Club_Competition_DTO( $row ), $results );
    }

    /**
     * Retrieves a list of clubs NOT participating in a specific competition for a given season.
     *
     * @param int $competition_id
     * @param string $season The season string from the league_teams table (e.g. "2025/26").
     *
     * @return Club[] Array of Club domain objects.
     */
    public function find_clubs_not_entered( int $competition_id, string $season ): array {
        $clubs_table        = $this->table_name; // wp_custom_clubs
        $teams_table        = $this->wpdb->prefix . 'racketmanager_teams';
        $league_teams_table = $this->wpdb->prefix . 'racketmanager_league_teams';
        $leagues_table      = $this->wpdb->prefix . 'racketmanager_leagues';
        $events_table       = $this->wpdb->prefix . 'racketmanager_events';

        // Query for clubs that do NOT have any team assigned to a league in this competition/season
        $query = $this->wpdb->prepare(
            "SELECT * FROM `$clubs_table` c WHERE NOT EXISTS (SELECT 1 FROM `$teams_table` t INNER JOIN `$league_teams_table` lte ON t.id = lte.team_id INNER JOIN `$leagues_table` l ON lte.league_id = l.id INNER JOIN `$events_table` e ON l.event_id = e.id WHERE t.club_id = c.id AND e.competition_id = %d AND lte.season = %s) AND c.type = 'affiliated' ORDER BY c.name",
            $competition_id,
            $season
        );

        return $this->wpdb->get_results( $query );
    }

}
