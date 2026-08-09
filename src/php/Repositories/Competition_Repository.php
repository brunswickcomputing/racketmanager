<?php
/**
 * Competition_Repository class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories;

use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\DTO\Competition\Competition_Overview_DTO;
use Racketmanager\Repositories\Interfaces\Competition_Repository_Interface;
use wpdb;

/**
 * Class to implement the Competition repository
 */
class Competition_Repository implements Competition_Repository_Interface {
    private wpdb $wpdb;
    private string $table_name;
    private string $events_table;
    private string $leagues_table;
    private string $league_teams_table;
    private string $teams_table;
    private string $rubber_players_table;
    private string $rubbers_table;
    private string $fixtures_table;

    /**
     * Create a new Competition_Repository instance
     *
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb                 = $wpdb;
        $this->table_name           = $this->wpdb->prefix . 'racketmanager_competitions';
        $this->events_table         = $this->wpdb->prefix . 'racketmanager_events';
        $this->fixtures_table       = $this->wpdb->prefix . 'racketmanager_matches';
        $this->leagues_table        = $this->wpdb->prefix . 'racketmanager_leagues';
        $this->league_teams_table   = $this->wpdb->prefix . 'racketmanager_league_teams';
        $this->rubber_players_table = $this->wpdb->prefix . 'racketmanager_rubber_players';
        $this->rubbers_table        = $this->wpdb->prefix . 'racketmanager_rubbers';
        $this->teams_table          = $this->wpdb->prefix . 'racketmanager_teams';
    }

    /**
     * Save a competition.
     *
     * @param object $entity
     *
     * @return int|bool
     */
    public function save( object $entity ): bool|int {
        /** @var Competition $entity */
        $data = array(
            'name'      => $entity->get_name(),
            'settings'  => json_encode( $entity->get_settings() ), // Store settings as JSON
            'seasons'   => json_encode( $entity->get_seasons() ), // Store seasons as JSON in DB
            'type'      => $entity->get_type(),
            'age_group' => $entity->get_age_group(),
        );
        $data_format = array(
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
        );
        if ( empty( $entity->get_id() ) ) {
            $inserted = $this->wpdb->insert(
                $this->table_name,
                $data,
                $data_format,
            );
            if ( $inserted ) {
                $entity->set_id( $this->wpdb->insert_id );
                wp_cache_set( $entity->get_id(), $entity, 'competitions' );
                return $this->wpdb->insert_id;
            }
            return false;
        } else {
            wp_cache_set( $entity->get_id(), $entity, 'competitions' );
            return $this->wpdb->update(
                $this->table_name,
                $data, // Data to update
                array(
                    'id' => $entity->get_id()
                ), // Where clause
                $data_format,
                array(
                    '%d'
                ) // Where format
            ) !== false;
        }
    }

    /**
     * Find a competition by its ID.
     *
     * @param int|string|null $id
     *
     * @return Competition|null
     */
    public function find_by_id( int|string|null $id ): ?Competition {
        if ( empty( $id ) ) {
            return null;
        }
        if ( is_numeric( $id ) ) {
            $id     = (int) $id;
            $search = '`id` = %d';
        } else {
            $search = '`name` = %s';
        }
        $competition = wp_cache_get( $id, 'competitions' );

        if ( ! $competition ) {
            $row = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT * FROM $this->table_name WHERE $search LIMIT 1",
                    $id
                )
            );

            if ( ! $row ) {
                return null;
            }
            $competition = Competition::from_database( $row );
            wp_cache_set( $competition->get_id(), $competition, 'competitions' );
        }

        return $competition;
    }

    /**
     * Find all competitions.
     *
     * @return array
     */
    public function find_all(): array {
        $competitions = wp_cache_get( 'competitions', 'competitions' );
        if ( ! $competitions ) {
            $competitions = $this->wpdb->get_results( "SELECT * FROM $this->table_name ORDER BY `name`" );
            $competitions = array_map( [ Competition::class, 'from_database' ], $competitions );
            wp_cache_set( 'competitions', $competitions, 'competitions' );
        }
        return $competitions;
    }

    /**
     * Find competitions by criteria.
     *
     * @param array $criteria
     *
     * @return array
     */
    public function find_by( array $criteria ): array {
        $sql  = "SELECT * FROM $this->table_name";
        if ( ! empty( $criteria ) ) {
            $clauses = array();
            foreach ( $criteria as $key => $value ) {
                // Use prepare statement for values to avoid SQL injection
                $clauses[] = $this->wpdb->prepare( "`$key` = %s", $value );
            }
            $sql .= ' WHERE ' . implode( ' AND ', $clauses );
        }
        $sql .= ' ORDER BY `name`';
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $rows = $this->wpdb->get_results( $sql );
        return array_map( [ Competition::class, 'from_database' ], $rows );
    }

    /**
     * Find competitions with summary data.
     *
     * @param $age_group
     * @param $type
     *
     * @return array
     */
    public function find_competitions_with_summary( $age_group = null, $type = null ): array {
        $params     = [];
        $conditions = []; // Use an array instead of a string

        if ( $age_group ) {
            $conditions[] = 'c.age_group = %s';
            $params[]     = $age_group;
        }

        if ( $type ) {
            $conditions[] = 'c.type = %s';
            $params[]     = $type;
        }

        // Build the WHERE clause only if conditions exist
        $where_clause = ! empty( $conditions ) ? ' WHERE ' . implode( ' AND ', $conditions ) : '';

        $query = "SELECT c.id, c.name, c.age_group, c.type, JSON_LENGTH(c.seasons) as season_count, COUNT(DISTINCT e.id) as event_count FROM $this->table_name c LEFT JOIN $this->events_table e ON c.id = e.competition_id $where_clause GROUP BY c.id ORDER BY c.age_group, c.type, c.name";

        if ( $where_clause ) {
            $query = $this->wpdb->prepare( $query, $params );
        }
        return $this->wpdb->get_results( $query );
    }

    /**
     * Delete a competition from the database.
     *
     * @param int $id
     *
     * @return bool
     */
    public function delete( int $id ): bool {
        return $this->wpdb->delete( $this->table_name, array( 'id' => $id ), array( '%d' ) ) !== false;
    }

    /**
     * Retrieves an overview of all competitions with aggregated counts for events, teams, and active players.
     *
     * @param int $competition_id
     * @param int $season
     * @param int|null $min_fixtures
     *
     * @return Competition_Overview_DTO|null
     */
    public function get_competition_overview( int $competition_id, int $season, ?int $min_fixtures = null ): ?Competition_Overview_DTO {
        $player_activity_subquery = $this->wpdb->prepare(
            "SELECT l.event_id, rp.player_id FROM $this->rubber_players_table rp INNER JOIN $this->rubbers_table r ON rp.rubber_id = r.id INNER JOIN $this->fixtures_table f ON r.match_id = f.id AND f.season = %d INNER JOIN $this->leagues_table l ON f.league_id = l.id GROUP BY l.event_id, rp.player_id HAVING COUNT(rp.id) >= %d",
            $season,
            $min_fixtures
        );

        $query = $this->wpdb->prepare(
            "SELECT c.id as id, c.name as name, c.settings as settings, COUNT(DISTINCT e.id) as num_events, COUNT(DISTINCT lte.team_id) as num_teams, COUNT(DISTINCT t.club_id) as num_clubs, COUNT(DISTINCT active_players.player_id) as num_players FROM `$this->table_name` c LEFT JOIN `$this->events_table` e ON c.`id` = e.`competition_id` LEFT JOIN `$this->leagues_table` l ON e.id = l.event_id LEFT JOIN `$this->league_teams_table` lte ON l.id = lte.league_id AND lte.season = %d LEFT JOIN `$this->teams_table` t ON lte.team_id = t.id LEFT JOIN ($player_activity_subquery) AS active_players ON e.id = active_players.event_id WHERE c.id = %d GROUP BY c.id, c.name, c.settings ORDER BY c.name",
            $season,
            $competition_id
        );

        $row = $this->wpdb->get_row( $query );

        return $row ? new Competition_Overview_DTO( $row ) : null;
    }

    /**
     * Checks if a specific club has any teams participating in a competition for a specific season.
     *
     * @param int $competition_id
     * @param int $club_id
     * @param string $season The season stored in the league_teams table.
     *
     * @return bool
     */
    public function is_club_participating( int $competition_id, int $club_id, string $season): bool {
        $query = $this->wpdb->prepare(
            "SELECT COUNT(*)
            FROM `$this->events_table` e
            INNER JOIN `$this->leagues_table` l ON e.id = l.event_id
            INNER JOIN `$this->league_teams_table` lte ON l.id = lte.league_id
            INNER JOIN `$this->teams_table` t ON lte.team_id = t.id
            WHERE e.competition_id = %d
              AND t.club_id = %d
              AND lte.season = %s",
            $competition_id,
            $club_id,
            $season
        );

        return (bool) $this->wpdb->get_var($query);
    }

    /**
     * Get league winners for a competition.
     *
     * @param int $competition_id
     * @param int|null $season
     *
     * @return array
     */
    public function get_league_winners( int $competition_id, ?int $season = null ): array {
        $query = $this->wpdb->prepare(
            "SELECT l.`title` ,wt.`title` AS `winner` ,e.`type`, e.`name` AS `event_name`, e.`id` AS `event_id` FROM $this->league_teams_table t, $this->leagues_table l, $this->teams_table wt, $this->events_table e WHERE t.`league_id` = l.`id` AND l.`event_id` = e.`id` AND e.`competition_id` = %d AND t.`season` = %d AND t.rank = 1 AND t.team_id = wt.id order by e.`name`, l.`title`",
            $competition_id,
            $season
        );
        return $this->wpdb->get_results( $query );
    }

    /**
     * Get championship winners for a competition.
     *
     * @param int $competition_id
     * @param int|null $season
     *
     * @return array
     */
    public function get_championship_winners( int $competition_id, ?int $season = null ): array {
        $query = $this->wpdb->prepare(
            "SELECT l.`title` ,wt.`title` AS `winner` ,lt.`title` AS `loser`, m.`id`, m.`home_team`, m.`away_team`, m.`winner_id` AS `winner_id`, m.`loser_id` AS `loser_id`, e.`type`, e.`name` AS `event_name`, e.`id` AS `event_id`, wt.`status` AS `team_type` FROM $this->fixtures_table m, $this->leagues_table l, $this->teams_table wt, $this->teams_table lt, $this->events_table e WHERE `league_id` = l.`id` AND l.`event_id` = e.`id` AND e.`competition_id` = %d AND m.`final` = 'FINAL' AND m.`season` = %d AND m.`winner_id` = wt.`id` AND m.`loser_id` = lt.`id` order by e.`name`, l.`title`",
            $competition_id,
            $season
        );
        return $this->wpdb->get_results( $query );
    }

	/**
	 * Find a competition by its name.
	 *
	 * @param string $name
	 * @return Competition|null
	 */
	public function find_by_name( string $name ): ?Competition {
		if ( empty( $name ) ) {
			return null;
		}

		$row = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT `name`, `id`, `type`, `settings`, `seasons`, `age_group` FROM $this->table_name WHERE `name` = %s LIMIT 1",
				$name
			)
		);

		if ( ! $row ) {
			return null;
		}

		return Competition::from_object( $row );
	}

	/**
	 * Get teams from database for a competition.
	 *
	 * @param int $competition_id
	 * @param array $args
	 * @return array|int
	 */
	public function find_teams( int $competition_id, array $args = [] ): array|int {
		$defaults = [
			'offset'  => 0,
			'limit'   => 99999999,
			'season'  => false,
			'orderby' => [
				'league_title' => 'ASC',
				'name'         => 'ASC',
			],
			'club'    => false,
			'status'  => false,
			'count'   => false,
			'name'    => false,
		];
		$args     = array_merge( $defaults, $args );
		$offset   = $args['offset'];
		$limit    = $args['limit'];
		$season   = $args['season'];
		$orderby  = $args['orderby'];
		$club     = $args['club'];
		$status   = $args['status'];
		$count    = $args['count'];
		$name     = $args['name'];

		$search_terms   = [];
		$search_terms[] = $this->wpdb->prepare( 'e.`competition_id` = %d', $competition_id );

		if ( $season ) {
			$search_terms[] = $this->wpdb->prepare( 't1.`season` = %s', $season );
		}

		if ( $club ) {
			$search_terms[] = $this->wpdb->prepare( 't2.`club_id` = %d', intval( $club ) );
		}

		if ( $status ) {
			$search_terms[] = $this->wpdb->prepare( 't1.`profile` = %d', intval( $status ) );
		}
		if ( $name ) {
			$search_terms[] = $this->wpdb->prepare( 't2.`title` like %s', '%' . $name . '%' );
		}

		$search = \Racketmanager\Util\Util::search_string( $search_terms );
		if ( $count ) {
			$sql = 'SELECT COUNT(*)';
		} else {
			$sql = 'SELECT `l`.`title` AS `league_title`, l.`id` AS `league_id`, t2.`id` AS `team_id`, t1.`id` AS `table_id`, `t2`.`title` as `name`,`t1`.`rank`, l.`id`, t1.`status`, t1.`profile`, t1.`group`, t2.`roster`, t2.`club_id`, t2.`status` AS `team_type`, e.`name` AS `event_name`';
		}
		$sql .= " FROM {$this->wpdb->prefix}racketmanager_events e, {$this->wpdb->prefix}racketmanager l, {$this->wpdb->prefix}racketmanager_teams t2, {$this->wpdb->prefix}racketmanager_league_teams t1 WHERE e.`id` = l.`event_id` AND t1.`team_id` = t2.`id` AND l.`id` = t1.`league_id` " . $search;

		if ( $count ) {
			$result = $this->wpdb->get_var( $sql );
			return null !== $result ? (int) $result : 0;
		}
		$sql .= \Racketmanager\Util\Util::order_by_string( $orderby );
		$sql  = $this->wpdb->prepare(
			$sql . ' LIMIT %d, %d',
			intval( $offset ),
			intval( $limit )
		);
		$teams = wp_cache_get( md5( $sql ), 'teams' );
		if ( ! $teams ) {
			$teams = $this->wpdb->get_results( $sql );
			wp_cache_set( md5( $sql ), $teams, 'teams' );
		}
		foreach ( $teams as $i => $team ) {
			$team->roster       = maybe_unserialize( $team->roster );
			$team->club         = \Racketmanager\get_club( $team->club_id );
			$team->title        = $team->name;
			$team->player_count = $this->find_players(
				$competition_id,
				[
					'season' => $season,
					'team'   => $team->team_id,
					'count'  => true,
				]
			);
		}
		return $teams;
	}

	/**
	 * Get players from database for a competition.
	 *
	 * @param int $competition_id
	 * @param array $args
	 * @return array|int
	 */
	public function find_players( int $competition_id, array $args = [] ): array|int {
		$defaults = [
			'offset'  => 0,
			'limit'   => 99999999,
			'season'  => false,
			'orderby' => 'name',
			'order'   => 'ASC',
			'team'    => false,
			'club'    => false,
			'count'   => false,
			'stats'   => false,
		];
		$args     = array_merge( $defaults, $args );
		$offset   = $args['offset'];
		$limit    = $args['limit'];
		$season   = $args['season'];
		$orderby  = $args['orderby'];
		$order    = $args['order'];
		$team     = $args['team'];
		$club     = $args['club'];
		$count    = $args['count'];

		$search_terms   = [];
		$search_terms[] = $this->wpdb->prepare( 'e.`competition_id` = %d', $competition_id );

		if ( $season ) {
			$search_terms[] = $this->wpdb->prepare( 'lt.`season` = %s', $season );
		}

		if ( $team ) {
			$search_terms[] = $this->wpdb->prepare( 't.`id` = %d', intval( $team ) );
		}

		if ( $club ) {
			$search_terms[] = $this->wpdb->prepare( 't.`club_id` = %d', intval( $club ) );
		}

		$search = \Racketmanager\Util\Util::search_string( $search_terms );

		if ( $count ) {
			$sql = 'SELECT COUNT(DISTINCT rp.`player_id`)';
		} else {
			$sql = 'SELECT DISTINCT rp.`player_id` as id';
		}

		$sql .= " FROM {$this->wpdb->prefix}racketmanager_events e
                  INNER JOIN {$this->wpdb->prefix}racketmanager l ON e.id = l.event_id
                  INNER JOIN {$this->wpdb->prefix}racketmanager_league_teams lt ON l.id = lt.league_id
                  INNER JOIN {$this->wpdb->prefix}racketmanager_teams t ON lt.team_id = t.id
                  INNER JOIN {$this->wpdb->prefix}racketmanager_matches m ON l.id = m.league_id AND m.season = lt.season
                  INNER JOIN {$this->wpdb->prefix}racketmanager_rubbers r ON m.id = r.match_id
                  INNER JOIN {$this->wpdb->prefix}racketmanager_rubber_players rp ON r.id = rp.rubber_id
                  WHERE (rp.team_id = t.id OR rp.team_id IS NULL) " . str_replace( 'WHERE', 'AND', $search );

		if ( $count ) {
			$result = $this->wpdb->get_var( $sql );
			return null !== $result ? (int) $result : 0;
		}

		$sql .= " ORDER BY $orderby $order LIMIT $offset, $limit";
		$rows = $this->wpdb->get_results( $sql );

		return array_map(
			function ( $row ) {
				return \Racketmanager\get_player( (int) $row->id );
			},
			$rows
		);
	}

	/**
	 * Get events from database for a competition.
	 *
	 * @param int $competition_id
	 * @param array $args
	 * @return array
	 */
	public function find_events( int $competition_id, array $args = [] ): array {
		$defaults = [
			'season' => false,
		];
		$args     = array_merge( $defaults, $args );
		$season   = $args['season'];

		$query = "SELECT * FROM {$this->wpdb->prefix}racketmanager_events WHERE `competition_id` = %d";
		$params = [ $competition_id ];

		if ( $season ) {
			$query .= ' AND `id` IN (SELECT `event_id` FROM ' . $this->wpdb->prefix . 'racketmanager WHERE `id` IN (SELECT `league_id` FROM ' . $this->wpdb->prefix . 'racketmanager_league_teams WHERE `season` = %s))';
			$params[] = $season;
		}

		$query .= ' ORDER BY `name`';
		$rows = $this->wpdb->get_results( $this->wpdb->prepare( $query, ...$params ) );

		return array_map(
			function ( $row ) {
				return \Racketmanager\get_event( (int) $row->id );
			},
			$rows
		);
	}

	/**
	 * Get matches from database for a competition.
	 *
	 * @param int $competition_id
	 * @param array $args
	 * @return array|int
	 */
	public function find_matches( int $competition_id, array $args = [] ): array|int {
		$defaults = [
			'offset'  => 0,
			'limit'   => 99999999,
			'season'  => false,
			'orderby' => [
				'league_id' => 'ASC',
				'id'        => 'ASC',
			],
			'count'   => false,
		];
		$args     = array_merge( $defaults, $args );
		$offset   = $args['offset'];
		$limit    = $args['limit'];
		$season   = $args['season'];
		$orderby  = $args['orderby'];
		$count    = $args['count'];

		$search_terms   = [];
		$search_terms[] = $this->wpdb->prepare( 'e.`competition_id` = %d', $competition_id );

		if ( $season ) {
			$search_terms[] = $this->wpdb->prepare( 'm.`season` = %s', $season );
		}

		$search = \Racketmanager\Util\Util::search_string( $search_terms );

		if ( $count ) {
			$sql = 'SELECT COUNT(*)';
		} else {
			$sql = 'SELECT m.id';
		}

		$sql .= " FROM {$this->wpdb->prefix}racketmanager_matches m
                  INNER JOIN {$this->wpdb->prefix}racketmanager l ON m.league_id = l.id
                  INNER JOIN {$this->wpdb->prefix}racketmanager_events e ON l.event_id = e.id " . $search;

		if ( $count ) {
			$result = $this->wpdb->get_var( $sql );
			return null !== $result ? (int) $result : 0;
		}

		$sql .= \Racketmanager\Util\Util::order_by_string( $orderby );
		$sql .= $this->wpdb->prepare( ' LIMIT %d, %d', $offset, $limit );

		$rows = $this->wpdb->get_results( $sql );

		return array_map(
			function ( $row ) {
				return \Racketmanager\get_match( (int) $row->id );
			},
			$rows
		);
	}
}
