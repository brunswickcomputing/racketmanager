<?php
declare( strict_types=1 );
/**
 * Competition_Repository class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories;

use Closure;
use LogicException;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Factory;
use Racketmanager\Domain\DTO\Competition\Competition_Overview_DTO;
use Racketmanager\Domain\DTO\Competition\Competition_Summary_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Competition_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Services\Fixture\Fixture_Relation_Loader;
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
    private ?Closure $fixture_repository_resolver;
    private ?Closure $fixture_relation_loader_resolver;
    private ?Fixture_Repository_Interface $fixture_repository = null;
    private ?Fixture_Relation_Loader $fixture_relation_loader = null;
    private ?Closure $club_repository_resolver;
    private ?Closure $player_repository_resolver;
    private ?Closure $event_repository_resolver;
    private ?Club_Repository_Interface $club_repository = null;
    private ?Player_Repository_Interface $player_repository = null;
    private ?Event_Repository_Interface $event_repository = null;

    /**
     * Create a new Competition_Repository instance
     *
     * @param Closure|null $fixture_repository_resolver Lazily resolves the fixture repository (avoids a construction cycle).
     * @param Closure|null $fixture_relation_loader_resolver Lazily resolves the fixture relation loader.
     * @param Closure|null $club_repository_resolver Lazily resolves the club repository used to hydrate team rows.
     * @param Closure|null $player_repository_resolver Lazily resolves the player repository used to hydrate player rows.
     * @param Closure|null $event_repository_resolver Lazily resolves the event repository used to hydrate event rows (avoids a construction cycle).
     */
    public function __construct(
        ?Closure $fixture_repository_resolver = null,
        ?Closure $fixture_relation_loader_resolver = null,
        ?Closure $club_repository_resolver = null,
        ?Closure $player_repository_resolver = null,
        ?Closure $event_repository_resolver = null
    ) {
        global $wpdb;
        $this->fixture_repository_resolver      = $fixture_repository_resolver;
        $this->fixture_relation_loader_resolver = $fixture_relation_loader_resolver;
        $this->club_repository_resolver         = $club_repository_resolver;
        $this->player_repository_resolver       = $player_repository_resolver;
        $this->event_repository_resolver        = $event_repository_resolver;
        $this->wpdb                             = $wpdb;
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
     * Hydrate competition relations.
     *
     * Competitions are returned (and cached) request-neutral: the current season is the domain default
     * (active or latest season) and no venue lookup happens here. Request-selected seasons and the venue
     * name are applied by Competition_Season_Service::resolve_current_season() at the application boundary.
     *
     * @param Competition $competition
     * @return Competition
     */
    private function hydrate_competition( Competition $competition ): Competition {
        return $competition;
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
            $competition = Competition_Factory::from_object( $row );
            $competition = $this->hydrate_competition( $competition );
            wp_cache_set( $competition->get_id(), $competition, 'competitions' );
        } else {
            $competition = $this->hydrate_competition( $competition );
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
            $rows         = $this->wpdb->get_results( "SELECT * FROM $this->table_name ORDER BY `name`" );
            $competitions = array_map(
                function( $row ) {
                    $comp = Competition_Factory::from_object( $row );
                    return $this->hydrate_competition( $comp );
                },
                $rows
            );
            wp_cache_set( 'competitions', $competitions, 'competitions' );
        } else {
            foreach ( $competitions as $competition ) {
                if ( $competition instanceof Competition ) {
                    $this->hydrate_competition( $competition );
                }
            }
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
        return array_map(
            function( $row ) {
                $comp = Competition_Factory::from_object( $row );
                return $this->hydrate_competition( $comp );
            },
            $rows
        );
    }

    /**
     * Find competitions with summary data.
     *
     * @param string|null $age_group
     * @param string|null $type
     *
     * @return Competition_Summary_DTO[]
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
        $results = $this->wpdb->get_results( $query );

        if ( empty( $results ) || ! is_array( $results ) ) {
            return [];
        }

        return array_map(
            static fn( object|array $row ): Competition_Summary_DTO => new Competition_Summary_DTO( $row ),
            $results
        );
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

		$competition = Competition_Factory::from_object( $row );
		return $this->hydrate_competition( $competition );
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
		$sql .= " FROM {$this->events_table} e, {$this->leagues_table} l, {$this->teams_table} t2, {$this->league_teams_table} t1 WHERE e.`id` = l.`event_id` AND t1.`team_id` = t2.`id` AND l.`id` = t1.`league_id` " . $search;

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
			$team->club         = empty( $team->club_id ) ? null : $this->get_club_repository()->find_by_id( $team->club_id );
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

		$sql .= " FROM {$this->events_table} e
                  INNER JOIN {$this->leagues_table} l ON e.id = l.event_id
                  INNER JOIN {$this->league_teams_table} lt ON l.id = lt.league_id
                  INNER JOIN {$this->teams_table} t ON lt.team_id = t.id
                  INNER JOIN {$this->fixtures_table} m ON l.id = m.league_id AND m.season = lt.season
                  INNER JOIN {$this->rubbers_table} r ON m.id = r.match_id
                  INNER JOIN {$this->rubber_players_table} rp ON r.id = rp.rubber_id
                  WHERE ( (rp.player_team = 'home' AND m.home_team = t.id) OR (rp.player_team = 'away' AND m.away_team = t.id) ) " . str_replace( 'WHERE', 'AND', $search );

		if ( $count ) {
			$result = $this->wpdb->get_var( $sql );
			return null !== $result ? (int) $result : 0;
		}

		$sql .= " ORDER BY $orderby $order LIMIT $offset, $limit";
		$rows = $this->wpdb->get_results( $sql );

		return array_map(
			function ( $row ) {
				$id = (int) $row->id;
				return $id > 0 ? $this->get_player_repository()->find( $id ) : null;
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
			$query .= " AND `id` IN (SELECT `event_id` FROM {$this->leagues_table} WHERE `id` IN (SELECT `league_id` FROM {$this->league_teams_table} WHERE `season` = %s))";
			$params[] = $season;
		}

		$query .= ' ORDER BY `name`';
		$rows = $this->wpdb->get_results( $this->wpdb->prepare( $query, ...$params ) );

		return array_map(
			function ( $row ) {
				$id = (int) $row->id;
				return $id > 0 ? $this->get_event_repository()->find_by_id( $id ) : null;
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

		$sql .= " FROM {$this->fixtures_table} m
                  INNER JOIN {$this->leagues_table} l ON m.league_id = l.id
                  INNER JOIN {$this->events_table} e ON l.event_id = e.id " . $search;

		if ( $count ) {
			$result = $this->wpdb->get_var( $sql );
			return null !== $result ? (int) $result : 0;
		}

		$sql .= \Racketmanager\Util\Util::order_by_string( $orderby );
		$sql .= $this->wpdb->prepare( ' LIMIT %d, %d', $offset, $limit );

		$rows = $this->wpdb->get_results( $sql );

		$fixture_repo    = $this->get_fixture_repository() ?? new Fixture_Repository();
		$relation_loader = $this->get_fixture_relation_loader();
		if ( ! $relation_loader ) {
			throw new LogicException( 'Competition match queries require a fixture relation loader.' );
		}

		$fixtures = array_map(
			function ( $row ) use ( $fixture_repo, $relation_loader ): ?Fixture {
				$fixture = $fixture_repo->find_by_id( (int) $row->id );

				return $fixture ? $relation_loader->load_league( $fixture ) : null;
			},
			$rows
		);

		return array_values( array_filter( $fixtures, static fn( ?Fixture $fixture ): bool => null !== $fixture ) );
	}

	/**
	 * Show winners
	 *
	 * @param string $season season.
	 * @param int $competition_id competition id.
	 * @param string $competition_type competition type.
	 * @param boolean $group_by group by type.
	 *
	 * @return array|false of winners|false.
	 */
	public function get_winners( string $season, int $competition_id, string $competition_type = 'tournament', bool $group_by = false ): false|array {
		$winners = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT l.`title` ,wt.`title` AS `winner` ,lt.`title` AS `loser`, m.`id`, m.`home_team`, m.`away_team`, m.`winner_id` AS `winner_id`, m.`loser_id` AS `loser_id`, e.`type`, e.`name` AS `event_name`, e.`id` AS `event_id`, c.`name` AS `competition_name`, c.`id` AS `competition_id`, wt.`status` AS `team_type` FROM {$this->fixtures_table} m, {$this->leagues_table} l, {$this->table_name} c, {$this->teams_table} wt, {$this->teams_table} lt, {$this->events_table} e WHERE `league_id` = l.`id` AND l.`event_id` = e.`id` AND e.`competition_id` = c.`id` AND c.`type` = %s AND c.`id` = %d AND m.`final` = 'FINAL' AND m.`season` = %d AND m.`winner_id` = wt.`id` AND m.`loser_id` = lt.`id` order by c.`type`, l.`title`",
				$competition_type,
				$competition_id,
				$season
			)
		);

		if ( ! $winners ) {
			return false;
		}

		$return = array();
		foreach ( $winners as $winner ) {
			$fixture_repo = $this->get_fixture_repository();
			$match        = $fixture_repo ? $fixture_repo->find_by_id( (int) $winner->id ) : null;
			if ( $winner->winner_id === $winner->home_team ) {
				$winner_club = isset( $match->teams['home']->club ) ? $match->teams['home']->club->shortcode : null;
			} else {
				$winner_club = isset( $match->teams['away']->club ) ? $match->teams['away']->club->shortcode : null;
			}
			if ( $winner->loser_id === $winner->home_team ) {
				$loser_club = isset( $match->teams['home']->club ) ? $match->teams['home']->club->shortcode : null;
			} else {
				$loser_club = isset( $match->teams['away']->club ) ? $match->teams['away']->club->shortcode : null;
			}
			$winner->league      = $winner->title;
			$winner->winner_club = $winner_club;
			$winner->loser_club  = $loser_club;
			if ( $group_by ) {
				$key = strtoupper( $winner->type );
				if ( false === array_key_exists( $key, $return ) ) {
					$return[ $key ] = array();
				}
				$return[ $key ][] = $winner;
			} else {
				$return[] = $winner;
			}
		}

		return $return;
	}

	/**
	 * Resolve the fixture repository lazily.
	 *
	 * @return Fixture_Repository_Interface|null
	 */
	private function get_fixture_repository(): ?Fixture_Repository_Interface {
		if ( null === $this->fixture_repository && null !== $this->fixture_repository_resolver ) {
			$resolved = ( $this->fixture_repository_resolver )();
			if ( $resolved instanceof Fixture_Repository_Interface ) {
				$this->fixture_repository = $resolved;
			}
		}
		return $this->fixture_repository;
	}

	/**
	 * Resolve the club repository lazily (defaults to a fresh Club_Repository, as the legacy helper did).
	 */
	private function get_club_repository(): Club_Repository_Interface {
		if ( null === $this->club_repository ) {
			$resolved              = $this->club_repository_resolver ? ( $this->club_repository_resolver )() : null;
			$this->club_repository = $resolved instanceof Club_Repository_Interface ? $resolved : new Club_Repository();
		}
		return $this->club_repository;
	}

	/**
	 * Resolve the player repository lazily (defaults to a fresh Player_Repository, as the legacy helper did).
	 */
	private function get_player_repository(): Player_Repository_Interface {
		if ( null === $this->player_repository ) {
			$resolved                = $this->player_repository_resolver ? ( $this->player_repository_resolver )() : null;
			$this->player_repository = $resolved instanceof Player_Repository_Interface ? $resolved : new Player_Repository();
		}
		return $this->player_repository;
	}

	/**
	 * Resolve the event repository lazily (defaults to an Event_Repository bound to this competition repository).
	 */
	private function get_event_repository(): Event_Repository_Interface {
		if ( null === $this->event_repository ) {
			$resolved               = $this->event_repository_resolver ? ( $this->event_repository_resolver )() : null;
			$this->event_repository = $resolved instanceof Event_Repository_Interface ? $resolved : new Event_Repository( null, $this );
		}
		return $this->event_repository;
	}

	/**
	 * Resolve the fixture relation loader lazily.
	 *
	 * @return Fixture_Relation_Loader|null
	 */
	private function get_fixture_relation_loader(): ?Fixture_Relation_Loader {
		if ( null === $this->fixture_relation_loader && null !== $this->fixture_relation_loader_resolver ) {
			$resolved = ( $this->fixture_relation_loader_resolver )();
			if ( $resolved instanceof Fixture_Relation_Loader ) {
				$this->fixture_relation_loader = $resolved;
			}
		}
		return $this->fixture_relation_loader;
	}
}
