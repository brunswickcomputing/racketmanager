<?php
declare( strict_types=1 );
/**
 * Club_Repository class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories;

use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\Club\Club_Factory;
use Racketmanager\Domain\DTO\Club\Club_Competition_DTO;
use Racketmanager\Domain\Team\Team_Factory;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Util\Util;
use wpdb;

/**
 * Class to implement the Club repository
 */
class Club_Repository implements Club_Repository_Interface {
    private wpdb $wpdb;
    private string $table_name;

    /**
     * Initialises the clubs repository with the global WordPress database instance.
     *
     * @return void
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb       = $wpdb;
        $this->table_name = $this->wpdb->prefix . 'racketmanager_clubs';
    }

    /**
     * Inserts a new club into the database.
     * The save action is explicit, not in the Club constructor.
     *
     * @param object $entity The club object to save.
     *
     * @return bool|int
     */
    public function save( object $entity ): bool|int {
        /** @var Club $entity */
        if ( $entity->get_id() === null ) {
            $inserted = $this->wpdb->insert(
                $this->table_name,
                array(
                    'name'       => $entity->get_name(),
                    'website'    => $entity->get_website(),
                    'type'       => $entity->get_type(),
                    'address'    => $entity->get_address(),
                    'contactno'  => $entity->get_contact_no(),
                    'founded'    => $entity->get_founded(),
                    'facilities' => $entity->get_facilities(),
                    'shortcode'  => $entity->get_shortcode(),
                ),
                array(
                    '%s', // Format for name (string).
                    '%s', // Format for website (string).
                    '%s', // Format for type (string).
                    '%s', // Format for address (string).
                    '%s', // Format for contactno (string).
                    '%s', // Format for founded (string).
                    '%s', // Format for facilities (string).
                    '%s', // Format for shortcode (string).
                )
            );
            if ( $inserted ) {
                $entity->set_id( $this->wpdb->insert_id );
                wp_cache_flush_group( 'clubs' );
                return $this->wpdb->insert_id;
            }
            return false;
        } else {
            // UPDATE: Use wpdb->update with the prepare logic built-in.
            $updated = $this->wpdb->update(
                $this->table_name,
                array(
                    'name'       => $entity->get_name(),
                    'website'    => $entity->get_website(),
                    'type'       => $entity->get_type(),
                    'address'    => $entity->get_address(),
                    'contactno'  => $entity->get_contact_no(),
                    'founded'    => $entity->get_founded(),
                    'facilities' => $entity->get_facilities(),
                    'shortcode'  => $entity->get_shortcode(),
                ),                                             // Data to update.
                array( 'id' => $entity->get_id() ),            // Where clause.
                array(
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                ),                                            // Data format.
                array( '%d' )                                 // Where format.
            );
            wp_cache_flush_group( 'clubs' );
            return false !== $updated;
        }
    }

    /**
     * Finds a club by the specified identifier or search field.
     *
     * @param null|int|string $id Club identifier, name, or shortcode to search for.
     * @param string          $search_term Field to search by. Supported values are 'id', 'name', and 'shortcode'.
     *
     * @return Club|null Club instance when found, or null when no matching club exists.
     */
    public function find_by_id( null|int|string $id, string $search_term = 'id' ): ?Club {
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

            // Prepare the query safely using prepare() to prevent SQL injection.
            $query = "SELECT `id`, `name`, `website`, `type`, `address`, `contactno`, `founded`, `facilities`, `shortcode` FROM $this->table_name WHERE " . $search . ' LIMIT 1';

            $club_data = $this->wpdb->get_row( $query ); // Get a single row as an object.
            if ( $club_data ) {
                wp_cache_set( $id, $club_data, 'clubs' );
            }
        }

        if ( $club_data ) {
            // Instantiate and return a new Club object with the fetched data.
            return Club_Factory::from_object( $club_data );
        }

        return null; // Club not found.
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
        $defaults    = array(
            'club'    => false,
            'count'   => false,
            'players' => false,
            'type'    => false,
        );
        $args        = array_merge( $defaults, $args );
        $club        = $args['club'];
        $count       = $args['count'];
        $players     = $args['players'];
        $type        = $args['type'];

        $search_terms   = array();
        $sql            = " FROM $teams_table WHERE `club_id` = '%d'";
        $search_terms[] = $club;
        if ( ! $players ) {
            $sql .= " AND (`team_type` is null OR `team_type` != 'P')";
        } else {
            $sql .= " AND `team_type` = 'P'";
        }
        if ( $type ) {
            if ( 'OS' === $type ) {
                $sql           .= " AND `type` like '%%%s%%'";
                $search_terms[] = 'S';
            } else {
                $sql           .= " AND `type` = '%s'";
                $search_terms[] = $type;
            }
        }
        if ( $count ) {
            $sql = 'SELECT COUNT(*) ' . $sql;
            return (int) $this->wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
                $this->wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                    $sql,            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                    $search_terms    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                )
            );
        }
        $sql = 'SELECT * ' . $sql . ' ORDER BY `title`';
        $sql = $this->wpdb->prepare(
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
            function ( $row ) {
                return Team_Factory::from_object( $row );
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
            'offset'      => 0,
            'limit'       => 99999999,
            'type'        => 'affiliated',
            'name'        => false,
            'count'       => false,
            'player_type' => false,
            'player'      => false,
            'club'        => false,
            'orderby'     => 'asc',
        );
        $args     = array_merge( $defaults, $args );

        $search_terms = $this->build_search_terms( $args );
        $search       = Util::search_string( $search_terms, true );

        if ( ! empty( $args['count'] ) ) {
            $sql = "SELECT COUNT(ID) FROM $this->table_name $search";
            return (int) $this->wpdb->get_var( $sql );
        }

        $order = $this->build_order_clause( (string) $args['orderby'] );
        $sql   = $this->wpdb->prepare(
            "SELECT `id` FROM $this->table_name $search $order LIMIT %d, %d",
            (int) $args['offset'],
            (int) $args['limit']
        );

        $clubs = wp_cache_get( md5( $sql ), 'clubs' );
        if ( ! $clubs ) {
            $clubs = $this->wpdb->get_results( $sql );
            wp_cache_set( md5( $sql ), $clubs, 'clubs' );
        }

        return is_array( $clubs ) ? $this->hydrate_clubs( $clubs ) : [];
    }

    /**
     * Builds SQL search conditions from query arguments.
     *
     * @param array $args Query arguments.
     *
     * @return array List of SQL condition strings.
     */
    private function build_search_terms( array $args ): array {
        $search_terms = [];

        $type = $args['type'];
        if ( $type && 'all' !== $type ) {
            $search_terms[] = ( 'current' === $type )
                ? "`type` != 'past'"
                : $this->wpdb->prepare( '`type` = %s', $type );
        }

        if ( ! empty( $args['club'] ) ) {
            $search_terms[] = $this->wpdb->prepare( '`id` = %d', $args['club'] );
        }

        if ( ! empty( $args['player'] ) && ! empty( $args['player_type'] ) ) {
            $player_term = $this->build_player_search_term( (string) $args['player_type'], (int) $args['player'] );
            if ( $player_term ) {
                $search_terms[] = $player_term;
            }
        }

        return $search_terms;
    }

    /**
     * Builds the player-specific SQL condition based on player role.
     *
     * @param string $player_type Type of player role ('secretary', 'captain', 'player').
     * @param int    $player      Player user/player ID.
     *
     * @return string|null SQL condition string or null if unhandled.
     */
    private function build_player_search_term( string $player_type, int $player ): ?string {
        $roles_table        = $this->wpdb->prefix . 'racketmanager_club_roles';
        $teams_events_table = $this->wpdb->prefix . 'racketmanager_teams_events';
        $teams_table        = $this->wpdb->prefix . 'racketmanager_teams';
        $club_players_table = $this->wpdb->prefix . 'racketmanager_club_players';

        return match ( $player_type ) {
            'secretary' => $this->wpdb->prepare(
                "`id` IN (SELECT `club_id` FROM $roles_table WHERE `role_id` = 1 AND `user_id` = %d)",
                $player
            ),
            'captain'   => $this->wpdb->prepare(
                "(`id` IN (SELECT `club_id` FROM $teams_events_table te, $teams_table t WHERE `captain` = %d AND te.`team_id` = t.`id`) OR `id` IN (SELECT `club_id` FROM $roles_table WHERE `role_id` = 1 AND `user_id` = %d))",
                $player,
                $player
            ),
            'player'    => $this->wpdb->prepare(
                "`id` IN (SELECT `club_id` FROM $club_players_table cp WHERE `player_id` = %d AND `removed_date` IS NULL)",
                $player
            ),
            default     => null,
        };
    }

    /**
     * Maps orderby argument to SQL ORDER BY clause.
     *
     * @param string $orderby Sort order keyword.
     *
     * @return string SQL ORDER BY clause or empty string.
     */
    private function build_order_clause( string $orderby ): string {
        $order = match ( $orderby ) {
            'asc'        => '`name` ASC',
            'desc'       => '`name` DESC',
            'rand'       => 'RAND()',
            'menu_order' => '`id` ASC',
            default      => '',
        };

        return $order ? "ORDER BY $order" : '';
    }

    /**
     * Hydrates database club rows into Club domain objects.
     *
     * @param array $rows Array of database row objects.
     *
     * @return array Array of hydrated Club objects.
     */
    private function hydrate_clubs( array $rows ): array {
        $hydrated_clubs = [];
        foreach ( $rows as $row ) {
            $club = $this->find_by_id( (int) $row->id );
            if ( $club ) {
                $hydrated_clubs[] = $club;
            }
        }

        return $hydrated_clubs;
    }

    /**
     * Deletes a club record by its unique identifier and flushes the clubs cache group.
     *
     * @param int $id Unique identifier of the club to delete.
     *
     * @return bool True if the delete query completed without error, false otherwise.
     */
    public function delete( int $id ): bool {
        $deleted = $this->wpdb->query(
            $this->wpdb->prepare(
                "DELETE FROM $this->table_name WHERE `id` = %d",
                $id
            )
        );
        wp_cache_flush_group( 'clubs' );
        return false !== $deleted;
    }

    /**
     * Finds clubs participating in a competition for a given season, including team and player counts.
     *
     * @param int    $competition_id Competition identifier to filter clubs by.
     * @param string $season Season identifier to filter league team entries and fixtures by.
     * @param int    $min_fixtures Minimum number of rubber player records required when counting players.
     *
     * @return Club_Competition_DTO[] List of club competition data transfer objects.
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
     * Finds affiliated clubs that have not entered any team in the specified competition and season.
     *
     * @param int    $competition_id Competition identifier used to check league entries.
     * @param string $season Season identifier used to check league team entries.
     *
     * @return array List of club records ordered by club name.
     */
    public function find_clubs_not_entered( int $competition_id, string $season ): array {
        $clubs_table        = $this->table_name; // wp_custom_clubs.
        $teams_table        = $this->wpdb->prefix . 'racketmanager_teams';
        $league_teams_table = $this->wpdb->prefix . 'racketmanager_league_teams';
        $leagues_table      = $this->wpdb->prefix . 'racketmanager_leagues';
        $events_table       = $this->wpdb->prefix . 'racketmanager_events';

        // Query for clubs that do NOT have any team assigned to a league in this competition/season.
        $query = $this->wpdb->prepare(
            "SELECT * FROM `$clubs_table` c WHERE NOT EXISTS (SELECT 1 FROM `$teams_table` t INNER JOIN `$league_teams_table` lte ON t.id = lte.team_id INNER JOIN `$leagues_table` l ON lte.league_id = l.id INNER JOIN `$events_table` e ON l.event_id = e.id WHERE t.club_id = c.id AND e.competition_id = %d AND lte.season = %s) AND c.type = 'affiliated' ORDER BY c.name",
            $competition_id,
            $season
        );

        return $this->wpdb->get_results( $query );
    }

    /**
     * Determines whether a player is associated with a club, optionally filtered by team, player, record type, and active status.
     *
     * @param array $args Optional arguments used to check club-player membership.
     *
     * @return bool True if a matching club-player record exists, false otherwise.
     */
    public function is_player_in_club( array $args = array() ): bool {
        $defaults = array(
            'team'   => false,
            'player' => false,
            'active' => false,
            'type'   => false,
        );
        $args     = array_merge( $defaults, $args );
        $team     = $args['team'];
        $type     = $args['type'];
        $player   = $args['player'];
        $active   = $args['active'];

        $search_terms = array();
        if ( $team ) {
            $teams_table    = $this->wpdb->prefix . 'racketmanager_teams';
            $search_terms[] = $this->wpdb->prepare( "`club_id` in (select `club_id` from $teams_table where `id` = %d)", (int) $team );
        }

        if ( $player ) {
            $search_terms[] = $this->wpdb->prepare( '`player_id` = %d', (int) $player );
        }

        if ( $type ) {
            $search_terms[] = '`system_record` IS NULL';
        }

        if ( $active ) {
            $search_terms[] = '`removed_date` IS NULL';
        }

        $search             = Util::search_string( $search_terms, true );
        $club_players_table = $this->wpdb->prefix . 'racketmanager_club_players';
        $sql                = "SELECT COUNT(ID) FROM $club_players_table " . $search;
        $count              = $this->wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

        return (int) $count > 0;
    }
}
