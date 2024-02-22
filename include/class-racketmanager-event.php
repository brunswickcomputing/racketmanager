<?php
/**
 * Racketmanager_Event API: Event class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Event
 */

namespace Racketmanager;

/**
 * Class to implement the Event object
 */
class Racketmanager_Event {
	/**
	 * Event ID
	 *
	 * @var int
	 */
	public $id;

	/**
	 * Event name
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Seasons data
	 *
	 * @var array
	 */
	public $seasons = array();

	/**
	 * Number of seasons
	 *
	 * @var int
	 */
	public $num_seasons = 0;

	/**
	 * Sport type
	 *
	 * @var string
	 */
	public $sport = 'tennis';

	/**
	 * Point rule
	 *
	 * @var string
	 */
	public $point_rule = 'tennis';

	/**
	 * Primary points format
	 *
	 * @var string
	 */
	public $point_format = '%d-%d';

	/**
	 * Secondary points format
	 *
	 * @var string
	 */
	public $point_format2 = '%d-%d';

	/**
	 * League mode
	 *
	 * @var string
	 */
	public $mode = 'default';

	/**
	 * Default match starting time
	 *
	 * @var array
	 */
	public $default_match_start_time = array(
		'hour'    => 19,
		'minutes' => 30,
	);

	/**
	 * Standings table layout settings
	 *
	 * @var array
	 */
	public $standings = array();

	/**
	 * Number of teams ascending
	 *
	 * @var int
	 */
	public $num_ascend = 0;

	/**
	 * Number of teams descending
	 *
	 * @var int
	 */
	public $num_descend = 0;

	/**
	 * Number of teams for relegation
	 *
	 * @var int
	 */
	public $num_relegation = 0;

	/**
	 * Number of teams per page in list
	 *
	 * @var int
	 */
	public $num_matches_per_page = 10;

	/**
	 * League offsets indexed by ID
	 *
	 * @var array
	 */
	public $league_index = array();

	/**
	 * League loop
	 *
	 * @var boolean
	 */
	public $in_the_league_loop = false;

	/**
	 * Current league
	 *
	 * @var int
	 */
	public $league_team = -1;

	/**
	 * Custom team input field keys and translated labels
	 *
	 * @var array
	 */
	public $fields_team = array();

	/**
	 * Championship flag
	 *
	 * @var boolean
	 */
	public $is_championship = false;

	/**
	 * Num_rubbers
	 *
	 * @var int
	 */
	public $num_rubbers = '';

	/**
	 * Num_sets
	 *
	 * @var int
	 */
	public $num_sets = '';

	/**
	 * Type
	 *
	 * @var string
	 */
	public $type = '';

	/**
	 * Current season
	 *
	 * @var array
	 */
	public $current_season = '';

	/**
	 * Number of match days
	 *
	 * @var int
	 */
	public $num_match_days = '';

	/**
	 * Number of leagues
	 *
	 * @var int
	 */
	public $num_leagues = '';

	/**
	 * Leagues
	 *
	 * @var array
	 */
	public $leagues = '';

	/**
	 * Settings keys
	 *
	 * @var array
	 */
	public $settings_keys = '';

	/**
	 * Constitutions
	 *
	 * @var array
	 */
	public $constitutions = '';

	/**
	 * Event Teams
	 *
	 * @var array
	 */
	public $event_teams = '';
	/**
	 * Settings
	 *
	 * @var array
	 */
	public $settings;
	/**
	 * Groups
	 *
	 * @var string
	 */
	public $groups;
	/**
	 * Teams per group
	 *
	 * @var int
	 */
	public $teams_per_group;
	/**
	 * Number to advance
	 *
	 * @var int
	 */
	public $num_advance;
	/**
	 * Match place 3
	 *
	 * @var boolean
	 */
	public $match_place3;
	/**
	 * Non group
	 *
	 * @var boolean
	 */
	public $non_group;
	/**
	 * Entry open
	 *
	 * @var boolean
	 */
	public $entry_open;
	/**
	 * Entry type
	 *
	 * @var string
	 */
	public $entry_type;
	/**
	 * Clubs
	 *
	 * @var array
	 */
	public $clubs;
	/**
	 * Players
	 *
	 * @var array
	 */
	public $players;
	/**
	 * Competition
	 *
	 * @var object
	 */
	public $competition;
	/**
	 * Competition id
	 *
	 * @var int
	 */
	public $competition_id;
	/**
	 * Retrieve event instance
	 *
	 * @param int    $event_id event id.
	 * @param string $search_term search.
	 */
	public static function get_instance( $event_id, $search_term = 'id' ) {
		global $wpdb;
		switch ( $search_term ) {
			case 'name':
				$search = $wpdb->prepare(
					'`name` = %s',
					$event_id
				);
				break;
			case 'id':
			default:
				$event_id = (int) $event_id;
				$search   = $wpdb->prepare(
					'`id` = %d',
					$event_id
				);
				break;
		}
		if ( ! $event_id ) {
			return false;
		}

		$event = wp_cache_get( $event_id, 'events' );
		if ( ! $event ) {
			$event = $wpdb->get_row(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				"SELECT `name`, `id`, `num_sets`, `num_rubbers`, `type`, `settings`, `seasons`, `competition_id` FROM {$wpdb->racketmanager_events} WHERE " . $search . ' LIMIT 1'
			); // db call ok.
			if ( ! $event ) {
				return false;
			}
			$event->settings = (array) maybe_unserialize( $event->settings );
			$event           = (object) ( $event->settings + (array) $event );
			// check if specific sports class exists.
			if ( ! isset( $event->sport ) ) {
				$event->sport = '';
			}
			$instance = 'Racketmanager\Racketmanager_Event_' . ucfirst( $event->sport );
			if ( class_exists( $instance ) ) {
				$event = new $instance( $event );
			} else {
				$event = new Racketmanager_Event( $event );
			}

			wp_cache_set( $event->id, $event, 'events' );
		}

		return $event;
	}

	/**
	 * Constructor
	 *
	 * @param object $event Event object.
	 */
	public function __construct( $event ) {
		if ( ! isset( $event->id ) ) {
			$this->add( $event );
		}
		if ( isset( $event->settings ) ) {
			$event->settings      = (array) maybe_unserialize( $event->settings );
			$event->settings_keys = array_keys( (array) maybe_unserialize( $event->settings ) );
			$event                = (object) array_merge( (array) $event, $event->settings );
		}

		foreach ( get_object_vars( $event ) as $key => $value ) {
			if ( 'standings' === $key ) {
				$this->$key = array_merge( $this->$key, $value );
			} else {
				$this->$key = $value;
			}
		}

		$this->name        = stripslashes( $this->name );
		$this->num_rubbers = stripslashes( $this->num_rubbers );
		$this->num_sets    = stripslashes( $this->num_sets );
		$this->type        = stripslashes( $this->type );
		$this->competition = get_competition( $this->competition_id );

		// set seasons.
		if ( '' === $this->seasons ) {
			$this->seasons = array();
		} else {
			$this->seasons = (array) maybe_unserialize( $this->seasons );
		}
		if ( ! is_admin() ) {
			$i       = 0;
			$seasons = array();
			foreach ( $this->seasons as $season ) {
				$seasons[ $season['name'] ] = $season;
				if ( isset( $season['status'] ) && 'draft' === $season['status'] ) {
					unset( $seasons[ $season['name'] ] );
				}
				++$i;
			}
			$this->seasons = $seasons;
		}
		$this->num_seasons = count( $this->seasons );
		$this->set_num_leagues( true );
		$this->standings = $this->competition->standings;

		// set default standings display options for additional team fields.
		if ( count( $this->fields_team ) > 0 ) {
			foreach ( $this->fields_team as $key => $data ) {
				if ( ! isset( $this->standings[ $key ] ) ) {
					$this->standings[ $key ] = 1;
				}
			}
		}

		// set season to latest.
		if ( $this->num_seasons > 0 ) {
			$this->set_season();
		}

		// Championship.
		if ( 'championship' === $this->competition->mode ) {
			$this->is_championship = true;
		}

		// add actions & filter.
		add_filter( 'racketmanager_event_standings_options', array( &$this, 'standings_table_display_options' ) );
	}

	/**
	 * Add new event
	 *
	 * @param object $event event object.
	 */
	private function add( $event ) {
		global $wpdb;
		$settings = array();
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"INSERT INTO {$wpdb->racketmanager_events} (`name`, `num_rubbers`, `num_sets`, `type`, `settings`) VALUES (%s, %d, %d, %s, %s)",
				$event->name,
				$event->num_rubbers,
				$event->num_sets,
				$event->type,
				maybe_serialize( $settings ),
			)
		);
		$event->id = $wpdb->insert_id;
	}

	/**
	 * Delete Event
	 */
	public function delete() {
		global $wpdb;

		foreach ( $this->get_leagues() as $league ) {
			$league = get_league( $league->id );
			$league->delete();
		}
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE FROM {$wpdb->racketmanager_team_event} WHERE `event_id` = %d",
				$this->id
			)
		);
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE FROM {$wpdb->racketmanager_events_seasons} WHERE `event_id` = %d",
				$this->id
			)
		);
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE FROM {$wpdb->racketmanager_events} WHERE `id` = %d",
				$this->id
			)
		);
	}

	/**
	 * Set name
	 *
	 * @param string $name event name.
	 */
	public function set_name( $name ) {
		global $wpdb;

		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"UPDATE {$wpdb->racketmanager_events} SET `name` = %s WHERE `id` =%d",
				$name,
				$this->id
			)
		);
		$this->name = $name;
	}

	/**
	 * Update settings
	 *
	 * @param array $settings settings array.
	 */
	public function set_settings( $settings ) {
		global $wpdb;
		$num_rubbers = $settings['num_rubbers'];
		$num_sets    = $settings['num_sets'];
		$type        = $settings['type'];

		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"UPDATE {$wpdb->racketmanager_events} SET `settings` = %s, `num_rubbers` = %d, `num_sets` = %d, `type` = %s WHERE `id` = %d",
				maybe_serialize( $settings ),
				$num_rubbers,
				$num_sets,
				$type,
				$this->id
			)
		);
	}

	/**
	 * Set current season
	 *
	 * @param mixed   $season season.
	 * @param boolean $force_overwrite force overwrite.
	 */
	public function set_season( $season = false, $force_overwrite = false ) {
		global $wp;
		if ( ! empty( $season ) && true === $force_overwrite ) {
			$data = $this->seasons[ $season ];
		} elseif ( isset( $_GET['season'] ) && ! empty( $_GET['season'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$key = htmlspecialchars( wp_strip_all_tags( wp_unslash( $_GET['season'] ) ) );
			if ( ! isset( $this->seasons[ $key ] ) ) {
				$data = false;
			} else {
				$data = $this->seasons[ $key ];
			}
		} elseif ( isset( $_GET[ 'season_' . $this->id ] ) && ! empty( $_GET[ 'season_' . $this->id ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$key = htmlspecialchars( wp_strip_all_tags( wp_unslash( $_GET[ 'season_' . $this->id ] ) ) );
			if ( ! isset( $this->seasons[ $key ] ) ) {
				$data = false;
			} else {
				$data = $this->seasons[ $key ];
			}
		} elseif ( isset( $wp->query_vars['season'] ) ) {
			$key = $wp->query_vars['season'];
			if ( ! isset( $this->seasons[ $key ] ) ) {
				$data = false;
			} else {
				$data = $this->seasons[ $key ];
			}
		} elseif ( ! empty( $season ) ) {
			$data = $this->seasons[ $season ];
		} else {
			$data = end( $this->seasons );
		}

		if ( empty( $data ) ) {
			$data = end( $this->seasons );
		}

		$this->current_season = $data;
		$this->num_match_days = $data['num_match_days'];
	}

	/**
	 * Get current season name
	 *
	 * @return string
	 */
	public function get_season() {
		return stripslashes( $this->current_season['name'] );
	}

	/**
	 * Get current season
	 *
	 * @param mixed   $season season.
	 * @param boolean $index lookup.
	 * @return array
	 */
	public function get_season_event( $season = false, $index = false ) {
		if ( isset( $_GET['season'] ) && ! empty( $_GET['season'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$key = htmlspecialchars( wp_strip_all_tags( wp_unslash( $_GET['season'] ) ) );
			if ( ! isset( $this->seasons[ $key ] ) ) {
				$data = false;
			} else {
				$data = $this->seasons[ $key ];
			}
		} elseif ( isset( $_GET[ 'season_' . $this->id ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$key = htmlspecialchars( wp_strip_all_tags( wp_unslash( $_GET[ 'season_' . $this->id ] ) ) );
			if ( ! isset( $this->seasons[ $key ] ) ) {
				$data = false;
			} else {
				$data = $this->seasons[ $key ];
			}
		} elseif ( $season ) {
			$data = $this->seasons[ $season ];
		} elseif ( ! empty( $this->seasons ) ) {
			$data = end( $this->seasons );
		} else {
			$data = false;
		}
		if ( empty( $data ) ) {
			$data = end( $this->seasons );
		}
		if ( $index ) {
			return $data[ $index ];
		} else {
			return $data;
		}
	}

	/**
	 * Gets number of leagues
	 *
	 * @param boolean $total should total be stored.
	 */
	public function set_num_leagues( $total = false ) {
		global $wpdb;

		if ( true === $total ) {
			$this->num_leagues = $wpdb->get_var( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT COUNT(ID) FROM {$wpdb->racketmanager} WHERE `event_id` = %d",
					$this->id
				)
			);
		}
	}

	/**
	 * Get leagues from database
	 *
	 * @param array $args search arguments.
	 * @return array
	 */
	public function get_leagues( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'offset'  => 0,
			'limit'   => 99999999,
			'orderby' => array( 'title' => 'ASC' ),
		);
		$args     = array_merge( $defaults, $args );
		$offset   = $args['offset'];
		$limit    = $args['limit'];
		$orderby  = $args['orderby'];

		$search_terms   = array();
		$search_terms[] = $wpdb->prepare( '`event_id` = %d', intval( $this->id ) );

		$search = '';
		if ( ! empty( $search_terms ) ) {
			$search  = ' WHERE ';
			$search .= implode( ' AND ', $search_terms );
		}

		$orderby_string = '';
		$i              = 0;
		foreach ( $orderby as $order => $direction ) {
			if ( ! in_array( $direction, array( 'DESC', 'ASC', 'desc', 'asc' ), true ) ) {
				$direction = 'ASC';
			}
			$orderby_string .= '`' . $order . '` ' . $direction;
			if ( $i < ( count( $orderby ) - 1 ) ) {
				$orderby_string .= ',';
			}
			++$i;
		}
		$orderby = $orderby_string;
		$sql     = $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT `title`, `id`, `settings`, `event_id` FROM {$wpdb->racketmanager} $search ORDER BY $orderby LIMIT %d, %d",
			intval( $offset ),
			intval( $limit )
		);
		$leagues = wp_cache_get( md5( $sql ), 'leagues' );
		if ( ! $leagues ) {
			$leagues = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql
			); // db call ok.
			wp_cache_set( md5( $sql ), $leagues, 'leagues' );
		}

		$league_index = array();
		foreach ( $leagues as $i => $league ) {
			$league_index[ $league->id ] = $i;
			$league                      = get_league( $league->id );
			$leagues[ $i ]               = $league;
		}
		$this->leagues      = $leagues;
		$this->league_index = $league_index;

		return $leagues;
	}
	/**
	 * Get player stats
	 *
	 * @param array $args query arguments.
	 * @return array
	 */
	public function get_player_stats( $args ) {
		global $wpdb;

		$defaults  = array(
			'season'    => false,
			'club'      => false,
			'league_id' => false,
			'system'    => false,
			'player'    => false,
		);
		$args      = array_merge( $defaults, (array) $args );
		$season    = $args['season'];
		$club      = $args['club'];
		$league_id = $args['league_id'];
		$system    = $args['system'];
		$player    = $args['player'];

		$sql1 = "SELECT p.ID AS `player_id`, p.`display_name` AS `fullname`, ro.`id` AS `roster_id`,  ro.`affiliatedclub` FROM {$wpdb->racketmanager_club_players} AS ro, {$wpdb->users} AS p WHERE ro.`player_id` = p.`ID`";
		$sql2 = "FROM {$wpdb->racketmanager_teams} AS t, {$wpdb->racketmanager_rubbers} AS r, {$wpdb->racketmanager_matches} AS m, {$wpdb->racketmanager_club_players} AS ro WHERE r.`winner_id` != 0 AND (((r.`home_player_1` = ro.`id` OR r.`home_player_2` = ro.`id`) AND  m.`home_team` = t.`id`) OR ((r.`away_player_1` = ro.`id` OR r.`away_player_2` = ro.`id`) AND m.`away_team` = t.`id`)) AND ro.`affiliatedclub` = t.`affiliatedclub` AND r.`match_id` = m.`id` AND m.`league_id` IN (SELECT `id` FROM {$wpdb->racketmanager} WHERE `event_id` = '%d') ";

		$search_terms2 = array( $this->id );

		if ( $season ) {
			$sql2           .= " AND m.`season` = '%s'";
			$search_terms2[] = htmlspecialchars( wp_strip_all_tags( $season ) );
		}
		if ( $league_id ) {
			$sql2           .= " AND m.`league_id` = '%d'";
			$search_terms2[] = intval( $league_id );
		}
		if ( $club ) {
			$sql2           .= " AND ro.`affiliatedclub` = '%d'";
			$search_terms2[] = intval( $club );
		}
		if ( $player ) {
			$sql2           .= " AND ro.`id` = '%d'";
			$search_terms2[] = intval( $player );
		}
		if ( ! $system ) {
			$sql2 .= ' AND ro.`system_record` IS NULL';
		}

		$order = '`affiliatedclub`, `fullname` ';

		$sql = $sql1 . ' AND ro.`id` in (SELECT ro.id ' . $sql2 . ')';

		if ( '' !== $order ) {
			$sql .= " ORDER BY $order";
		}

		$sql = $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$sql,
			$search_terms2
		);
		$playerstats = wp_cache_get( md5( $sql ), 'playerstats' );
		if ( ! $playerstats ) {
			$playerstats = $wpdb->get_results(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql
			); // db call ok.
			wp_cache_set( md5( $sql ), $playerstats, 'playerstats' );
		}

		foreach ( $playerstats as $i => $playerstat ) {
			$sql3  = 'SELECT t.`id` AS team_id,  t.`title` AS team_title, m.`season`, m.`match_day`, m.`home_team`, m.`away_team`, m.`winner_id` AS match_winner, m.`home_points`, m.`away_points`, m.`loser_id` AS match_loser, r.`rubber_number`, r.`home_player_1`, r.`home_player_2`, r.`away_player_1`, r.`away_player_2`, r.`winner_id` AS rubber_winner, r.`loser_id` AS rubber_loser, r.`custom`, m.`final` as `final_round`';
			$sql3 .= $sql2 . ' AND ro.`ID` = ' . $playerstat->roster_id;
			$sql3 .= ' ORDER BY m.`season`, m.`match_day`';

			$sql = $wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql3,
				$search_terms2
			);
			$stats = wp_cache_get( md5( $sql ), 'playerstats' );
			if ( ! $stats ) {
				$stats = $wpdb->get_results(
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					$sql
				); // db call ok.
				wp_cache_set( md5( $sql ), $stats, 'playerstats' );
			}

			foreach ( $stats as $s => $stat ) {
				$stat->custom = stripslashes_deep( maybe_unserialize( $stat->custom ) );
				$stats[ $s ]  = $stat;
			}

			$playerstat->matchdays = $stats;
			$playerstats[ $i ]     = (object) (array) $playerstat;
		}

		return $playerstats;
	}

	/**
	 * Get teams from database
	 *
	 * @param array $args query arguemnts.
	 * @return array database results
	 */
	public function get_teams_info( $args = array() ) {
		global $wpdb;

		$defaults       = array(
			'league_id'      => false,
			'rank'           => false,
			'orderby'        => array(
				'rank'  => 'ASC',
				'title' => 'ASC',
			),
			'home'           => false,
			'affiliatedclub' => false,
		);
		$args           = array_merge( $defaults, $args );
		$league_id      = $args['league_id'];
		$rank           = $args['rank'];
		$orderby        = $args['orderby'];
		$home           = $args['home'];
		$affiliatedclub = $args['affiliatedclub'];

		$search_terms = array();
		if ( $league_id ) {
			if ( 'any' === $league_id ) {
				$search_terms[] = "A.`league_id` != ''";
			} else {
				$search_terms[] = $wpdb->prepare( 'A.`league_id` = %d', intval( $league_id ) );
			}
		}
		if ( $affiliatedclub ) {
			$search_terms[] = $wpdb->prepare( '`affiliatedclub` = %d', intval( $affiliatedclub ) );
		}
		if ( $rank ) {
			$search_terms[] = $wpdb->prepare( 'A.`rank` = %s', $rank );
		}

		if ( $home ) {
			$search_terms[] = 'B.`home` = 1';
		}

		$search = '';
		if ( ! empty( $search_terms ) ) {
			$search  = ' AND ';
			$search .= implode( ' AND ', $search_terms );
		}

		$orderby_string = '';
		$i              = 0;
		foreach ( $orderby as $order => $direction ) {
			if ( ! in_array( $direction, array( 'DESC', 'ASC', 'desc', 'asc' ), true ) ) {
				$direction = 'ASC';
			}
			$orderby_string .= '`' . $order . '` ' . $direction;
			if ( $i < ( count( $orderby ) - 1 ) ) {
				$orderby_string .= ',';
			}
			++$i;
		}
		$orderby = $orderby_string;

		$sql = "SELECT DISTINCT B.`id`, B.`title`, C.`captain`, B.`affiliatedclub`, B.`stadium`, B.`home`, B.`roster`, B.`profile`, A.`group`, C.`match_day`, C.`match_time` FROM {$wpdb->racketmanager_teams} B, {$wpdb->racketmanager_table} A, {$wpdb->racketmanager_team_events} C WHERE B.id = A.team_id AND A.team_id = C.team_id and C.event_id in (select `event_id` from {$wpdb->racketmanager} WHERE `id` = A.league_id) AND C.`event_id` = " . $this->id . ' AND A.season = ' . $this->get_season() . " $search ORDER BY $orderby";

		$teams = wp_cache_get( md5( $sql ), 'teams' );
		if ( ! $teams ) {
			$teams = $wpdb->get_results(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql
			); // db call ok.
			wp_cache_set( md5( $sql ), $teams, 'teams' );
		}

		$class = '';
		foreach ( $teams as $i => $team ) {
			$class        = ( 'alternate' === $class ) ? '' : 'alternate';
			$captain      = get_userdata( $team->captain );
			$team->roster = maybe_unserialize( $team->roster );
			$team->title  = htmlspecialchars( stripslashes( $team->title ), ENT_QUOTES );
			if ( ! empty( $captain ) ) {
				$team->captain      = $captain->display_name;
				$team->captain_id   = $captain->ID;
				$team->contactno    = get_user_meta( $captain->ID, 'contactno', true );
				$team->contactemail = $captain->user_email;
			} else {
				$team->captain      = 'Unknown';
				$team->captain_id   = '';
				$team->contactno    = '';
				$team->contactemail = '';
			}
			$team->affiliatedclub     = stripslashes( $team->affiliatedclub );
			$team->club               = get_club( $team->affiliatedclub );
			$team->affiliatedclubname = $team->club->name;
			$team->stadium            = stripslashes( $team->stadium );
			$team->class              = $class;
			$teams[ $i ]              = $team;
		}

		return $teams;
	}

	/**
	 * Get specific team details from database
	 *
	 * @param int $team_id team id.
	 * @return array database results
	 */
	public function get_team_info( $team_id ) {
		global $wpdb;

		$sql = "SELECT `captain`, `match_day`, `match_time` FROM {$wpdb->racketmanager_team_events} WHERE `event_id` = " . $this->id . ' AND `team_id` = ' . $team_id;

		$team = wp_cache_get( md5( $sql ), 'team' );
		if ( ! $team ) {
			$team = $wpdb->get_row(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql
			);
			wp_cache_set( md5( $sql ), $team, 'team' );
		}

		if ( $team ) {
			$captain = get_userdata( $team->captain );
			if ( $captain ) {
				$team->captain      = $captain->display_name;
				$team->captain_id   = $captain->ID;
				$team->contactno    = get_user_meta( $captain->ID, 'contactno', true );
				$team->contactemail = $captain->user_email;
			} else {
				$team->captain      = 'Unknown';
				$team->captain_id   = '';
				$team->contactno    = '';
				$team->contactemail = '';
			}
		}

		return $team;
	}

	/**
	 * Get settings
	 *
	 * @param string $key settings key.
	 * @return array
	 */
	public function get_settings( $key = false ) {
		$settings = array();
		foreach ( $this->settings_keys as $k ) {
			$settings[ $k ] = $this->$k;
		}

		if ( $key ) {
			return ( isset( $settings[ $key ] ) ) ? $settings[ $key ] : false;
		}

		return $settings;
	}

	/**
	 * Reload settings from database
	 */
	public function reload_settings() {
		global $wpdb;

		wp_cache_delete( $this->id, 'events' );
		$result = $wpdb->get_row( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT `settings` FROM {$wpdb->racketmanager_events} WHERE `id` = %d",
				intval( $this->id )
			)
		);
		foreach ( maybe_unserialize( $result->settings ) as $key => $value ) {
			$this->$key = $value;
		}
	}

	/**
	 * Add custom standings table display options
	 *
	 * @param array $options options.
	 * @return array
	 */
	public function standings_table_display_options( $options ) {
		if ( count( $this->fields_team ) > 0 ) {
			foreach ( $this->fields_team as $key => $data ) {
				$options[ $key ] = isset( $data['desc'] ) ? $data['desc'] : $data['label'];
			}
		}

		return $options;
	}

	/**
	 * Get constitution from database
	 *
	 * @param array $args search arguments.
	 * @return array
	 */
	public function get_constitution( $args = array() ) {
		global $wpdb;

		$defaults  = array(
			'offset'    => 0,
			'limit'     => 99999999,
			'season'    => false,
			'oldseason' => false,
			'club'      => false,
			'count'     => false,
		);
		$args      = array_merge( $defaults, $args );
		$offset    = $args['offset'];
		$limit     = $args['limit'];
		$season    = $args['season'];
		$oldseason = $args['oldseason'];
		$club      = $args['club'];
		$count     = $args['count'];

		$search_terms   = array();
		$search_terms[] = $wpdb->prepare( '`event_id` = %d', $this->id );

		if ( $season ) {
			$search_terms[] = $wpdb->prepare( 't1.`season` = %s', $season );
		}

		if ( ! $oldseason ) {
			$oldseason = $season;
		}

		if ( $club ) {
			$search_terms[] = $wpdb->prepare( 't2.`affiliatedclub` = %d', intval( $club ) );
		}

		$search = '';
		if ( ! empty( $search_terms ) ) {
			$search  = ' AND ';
			$search .= implode( ' AND ', $search_terms );
		}
		if ( $count ) {
			$sql = 'SELECT COUNT(*)';
		} else {
			$sql = 'SELECT `l`.`title` AS `league_title`, l.`id` AS `league_id`, ot.`league_id` AS old_league_id, t2.`id` AS `team_id`, t1.`id` AS `table_id`, `t2`.`title`,`t1`.`rank`,`ot`.`rank` AS old_rank, l.`id`, ot.`points_plus`, ot.`add_points`, t1.`status`, t1.`profile`';
		}
		$sql .= " FROM {$wpdb->racketmanager} l, {$wpdb->racketmanager_teams} t2, {$wpdb->racketmanager_table} t1 LEFT OUTER JOIN {$wpdb->racketmanager_table} ot ON `ot`.`season` = %s and `ot`.`team_id` = `t1`.`team_id` and ot.league_id in (select id from wp_racketmanager_leagues ol where ol.`event_id` = %d) WHERE t1.`team_id` = t2.`id` AND l.`id` = t1.`league_id` $search ";
		$sql  = $wpdb->prepare(
			$sql, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$oldseason,
			$this->id,
		);

		if ( $count ) {
			return $wpdb->get_var( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$sql //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			);
		}
		$sql = $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$sql . ' ORDER BY l.`title` ASC, t1.`rank` ASC LIMIT %d, %d',
			intval( $offset ),
			intval( $limit )
		);

		$constitutions = wp_cache_get( md5( $sql ), 'constitution' );
		if ( ! $constitutions ) {
			$constitutions = $wpdb->get_results(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql
			);
			wp_cache_set( md5( $sql ), $constitutions, 'constitution' );
		}

		foreach ( $constitutions as $i => $constitution ) {
			if ( isset( $constitution->old_league_id ) ) {
				$constitution->old_league_title = get_league( $constitution->old_league_id )->title;
			} else {
				$constitution->old_league_title = '';
			}
			$constitutions[ $i ] = $constitution;
		}

		$this->constitutions = $constitutions;

		return $constitutions;
	}

	/**
	 * Get constitution from database
	 *
	 * @param string $args search arguments.
	 * @return array
	 */
	public function build_constitution( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'offset' => 0,
			'limit'  => 99999999,
			'season' => false,
			'club'   => false,
		);
		$args     = array_merge( $defaults, $args );
		$offset   = $args['offset'];
		$limit    = $args['limit'];
		$season   = $args['season'];
		$club     = $args['club'];

		$search_terms   = array();
		$search_terms[] = $wpdb->prepare( '`event_id` = %d', intval( $this->id ) );

		if ( $season ) {
			$search_terms[] = $wpdb->prepare( '`season` = %s', $season );
		}

		if ( $club ) {
			$search_terms[] = $wpdb->prepare( 't2.`affiliatedclub` = %d', intval( $club ) );
		}

		$search = '';
		if ( ! empty( $search_terms ) ) {
			$search  = ' AND ';
			$search .= implode( ' AND ', $search_terms );
		}

		$sql = $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT `l`.`title` AS `old_league_title`, l.`id` AS `old_league_id`, t2.`id` AS `team_id`, t1.`id` AS `table_id`, `t2`.`title`,`t1`.`rank` AS old_rank, l.`id`, t1.`points_plus`, t1.`add_points`, t1.`status`, t1.`profile` FROM {$wpdb->racketmanager} l, {$wpdb->racketmanager_table} t1, {$wpdb->racketmanager_teams} t2 WHERE t1.`team_id` = t2.`id` AND l.`id` = t1.`league_id` $search ORDER BY l.`title` ASC, t1.`rank` ASC LIMIT %d, %d",
			intval( $offset ),
			intval( $limit )
		);
		$constitutions = wp_cache_get( md5( $sql ), 'constitution' );
		if ( ! $constitutions ) {
			$constitutions = $wpdb->get_results(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql
			); // db call ok.
			wp_cache_set( md5( $sql ), $constitutions, 'constitution' );
		}

		foreach ( $constitutions as $i => $constitution ) {
			$constitution->rank      = $constitution->old_rank;
			$constitution->status    = '';
			$constitution->profile   = '0';
			$constitution->league_id = $constitution->old_league_id;

			$constitutions[ $i ] = $constitution;
		}

		$this->constitutions = $constitutions;

		return $constitutions;
	}
	/**
	 * Get clubs for event
	 *
	 * @param string $args search arguments.
	 * @return array
	 */
	public function get_clubs( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'offset'  => 0,
			'limit'   => 99999999,
			'season'  => false,
			'orderby' => false,
			'status'  => false,
			'count'   => false,
			'name'    => false,
		);
		$args     = array_merge( $defaults, $args );
		$offset   = $args['offset'];
		$limit    = $args['limit'];
		$season   = $args['season'];
		$orderby  = $args['orderby'];
		$status   = $args['status'];
		$count    = $args['count'];
		$name     = $args['name'];

		$search_terms   = array();
		$search_terms[] = $wpdb->prepare( '`event_id` = %d', $this->id );
		if ( ! $season ) {
			$season = $this->current_season['name'];
		}
		if ( $season ) {
			$search_terms[] = $wpdb->prepare( 't1.`season` = %s', $season );
		}

		if ( $status ) {
			$search_terms[] = $wpdb->prepare( 't1.`profile` = %d', intval( $status ) );
		}
		if ( $name ) {
			$search_terms[] = $wpdb->prepare( 't2.`title` like %s', '%' . $name . '%' );
		}

		$search = '';
		if ( ! empty( $search_terms ) ) {
			$search  = ' AND ';
			$search .= implode( ' AND ', $search_terms );
		}

		if ( $count ) {
			$sql = 'SELECT COUNT(*)';
		} else {
			$sql = 'SELECT t2.`affiliatedclub`, count(t2.`id`) as `team_count`';
		}
		$sql .= " FROM {$wpdb->racketmanager} l, {$wpdb->racketmanager_teams} t2, {$wpdb->racketmanager_table} t1, {$wpdb->racketmanager_clubs} c WHERE t1.`team_id` = t2.`id` AND l.`id` = t1.`league_id` AND t2.`affiliatedclub` = c.`id`" . $search;

		if ( $count ) {
			return $wpdb->get_var(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql
			); // db call ok.
		} else {
			$sql .= ' GROUP BY t2.`affiliatedclub`';
		}
		$sql = $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$sql . ' ORDER BY c.`name` ASC LIMIT %d, %d',
			intval( $offset ),
			intval( $limit )
		);

		$event_clubs = wp_cache_get( md5( $sql ), 'event_clubs' );
		if ( ! $event_clubs ) {
			$event_clubs = $wpdb->get_results(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql
			); // db call ok.
			wp_cache_set( md5( $sql ), $event_clubs, 'event_clubs' );
		}

		foreach ( $event_clubs as $i => $event_club ) {
			$team_count               = $event_club->team_count;
			$event_club               = get_club( $event_club->affiliatedclub );
			$event_club->team_count   = $team_count;
			$event_club->player_count = $this->get_players(
				array(
					'season' => $season,
					'count'  => true,
					'club'   => $event_club->id,
				)
			);
			$event_clubs[ $i ]        = $event_club;
		}

		$this->clubs = $event_clubs;

		return $event_clubs;
	}

	/**
	 * Get teams from database
	 *
	 * @param string $args search arguments.
	 * @return array
	 */
	public function get_teams( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'offset'  => 0,
			'limit'   => 99999999,
			'season'  => false,
			'orderby' => array(
				'league_title' => 'ASC',
				'name'         => 'ASC',
			),
			'club'    => false,
			'status'  => false,
			'count'   => false,
			'name'    => false,
			'players' => false,
		);
		$args     = array_merge( $defaults, $args );
		$offset   = $args['offset'];
		$limit    = $args['limit'];
		$season   = $args['season'];
		$orderby  = $args['orderby'];
		$club     = $args['club'];
		$status   = $args['status'];
		$count    = $args['count'];
		$name     = $args['name'];
		$players  = $args['players'];

		$search_terms   = array();
		$search_terms[] = $wpdb->prepare( '`event_id` = %d', $this->id );

		if ( $season ) {
			$search_terms[] = $wpdb->prepare( 't1.`season` = %s', $season );
		}

		if ( $club ) {
			$search_terms[] = $wpdb->prepare( 't2.`affiliatedclub` = %d', intval( $club ) );
		}

		if ( $status ) {
			$search_terms[] = $wpdb->prepare( 't1.`profile` = %d', intval( $status ) );
		}
		if ( $name ) {
			$search_terms[] = $wpdb->prepare( 't2.`title` like %s', '%' . $name . '%' );
		}

		$search = '';
		if ( ! empty( $search_terms ) ) {
			$search  = ' AND ';
			$search .= implode( ' AND ', $search_terms );
		}

		if ( $count ) {
			$sql = 'SELECT COUNT(*)';
		} else {
			$sql = 'SELECT `l`.`title` AS `league_title`, l.`id` AS `league_id`, t2.`id` AS `team_id`, t1.`id` AS `tableId`, `t2`.`title` as `name`,`t1`.`rank`, l.`id`, t1.`status`, t1.`profile`, t1.`group`, t2.`roster`, t2.`affiliatedclub`, t2.`status` AS `team_type`';
		}
		$sql .= " FROM {$wpdb->racketmanager} l, {$wpdb->racketmanager_teams} t2, {$wpdb->racketmanager_table} t1 WHERE t1.`team_id` = t2.`id` AND l.`id` = t1.`league_id` " . $search;

		if ( $count ) {
			return $wpdb->get_var(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql
			); // db call ok.
		}
		$orderby_string = '';
		$i              = 0;
		foreach ( $orderby as $order => $direction ) {
			if ( ! in_array( $direction, array( 'DESC', 'ASC', 'desc', 'asc' ), true ) ) {
				$direction = 'ASC';
			}
			$orderby_string .= '`' . $order . '` ' . $direction;
			if ( $i < ( count( $orderby ) - 1 ) ) {
				$orderby_string .= ',';
			}
			++$i;
		}
		$orderby = $orderby_string;
		$sql    .= ' ORDER BY ' . $orderby;
		$sql     = $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$sql . ' LIMIT %d, %d',
			intval( $offset ),
			intval( $limit )
		);

		$event_teams = wp_cache_get( md5( $sql ), 'event_teams' );
		if ( ! $event_teams ) {
			$event_teams = $wpdb->get_results(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql
			); // db call ok.
			wp_cache_set( md5( $sql ), $event_teams, 'event_teams' );
		}

		foreach ( $event_teams as $i => $event_team ) {
			$event_team->roster = maybe_unserialize( $event_team->roster );
			$event_team->club   = get_club( $event_team->affiliatedclub );
			$event_team->title  = $event_team->name;
			if ( 'P' === $event_team->team_type && ! empty( $event_team->roster ) ) {
				$p = 1;
				foreach ( $event_team->roster as $player ) {
					$teamplayer                  = get_player( $player );
					$event_team->player[ $p ]    = $teamplayer->fullname;
					$event_team->player_id[ $p ] = $player;
					++$p;
				}
			} else {
				$event_team->player_count = $this->get_players(
					array(
						'season' => $season,
						'club'   => $event_team->club->id,
						'team'   => $event_team->team_id,
						'count'  => true,
					)
				);
			}
			$event_teams[ $i ] = $event_team;
		}

		$this->event_teams = $event_teams;

		return $event_teams;
	}
	/**
	 * Get players for event
	 *
	 * @param string $args search arguments.
	 * @return array
	 */
	public function get_players( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'offset'  => 0,
			'limit'   => 99999999,
			'season'  => false,
			'orderby' => array(),
			'club'    => false,
			'team'    => false,
			'count'   => false,
			'group'   => false,
		);
		$args     = array_merge( $defaults, $args );
		$offset   = $args['offset'];
		$limit    = $args['limit'];
		$season   = $args['season'];
		$orderby  = $args['orderby'];
		$club     = $args['club'];
		$team     = $args['team'];
		$count    = $args['count'];
		$group    = $args['group'];

		$search_terms  = array();
		$search_args   = array();
		$search_args[] = $this->id;
		if ( ! $season ) {
			$season = $this->current_season['name'];
		}
		if ( $season ) {
			$search_terms[] = '`season` = %s';
			$search_args[]  = $season;
		}
		if ( $team ) {
			$search_terms[] .= '(`home_team` = %d OR `away_team` = %d)';
			$search_args[]   = $team;
			$search_args[]   = $team;
		}
		$search = '';
		if ( ! empty( $search_terms ) ) {
			$search  = ' AND ';
			$search .= implode( ' AND ', $search_terms );
		}
		$orderby_string = '';
		$order          = '';
		$i              = 0;
		foreach ( $orderby as $order => $direction ) {
			if ( ! in_array( $direction, array( 'DESC', 'ASC', 'desc', 'asc' ), true ) ) {
				$direction = 'ASC';
			}
			$orderby_string .= '`' . $order . '` ' . $direction;
			if ( $i < ( count( $orderby ) - 1 ) ) {
				$orderby_string .= ',';
			}
			++$i;
		}
		if ( $orderby_string ) {
			$order = ' ORDER BY ' . $orderby_string;
		}
		$sql = "SELECT `home_player_1`, `home_player_2`, `away_player_1`, `away_player_2` FROM {$wpdb->racketmanager_rubbers} r, {$wpdb->racketmanager_matches} m  WHERE r.`match_id` = m.`id` AND m.`league_id` IN (SELECT `id` FROM {$wpdb->racketmanager} WHERE `event_id` = %d)" . $search . $order;
		if ( intval( $limit > 0 ) ) {
			$sql          .= ' LIMIT %d, %d';
			$search_args[] = $offset;
			$search_args[] = $limit;
		}
		$sql = $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$sql,
			$search_args,
		);
		$event_rubber_players = wp_cache_get( md5( $sql ), 'event_rubber_players' );
		if ( ! $event_rubber_players ) {
			$event_rubber_players = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql
			); // db call ok.
			wp_cache_set( md5( $sql ), $event_rubber_players, 'event_rubber_players' );
		}
		$club_players = array();
		foreach ( $event_rubber_players as $rubber_players ) {
			$club_players[] = $rubber_players->home_player_1;
			$club_players[] = $rubber_players->home_player_2;
			$club_players[] = $rubber_players->away_player_1;
			$club_players[] = $rubber_players->away_player_2;
		}
		$club_players = array_unique( $club_players );
		$players      = array();
		$sql          = "SELECT DISTINCT `player_id` FROM {$wpdb->racketmanager_club_players} WHERE `id` in ( 0";
		foreach ( $club_players as $i => $club_player ) {
			if ( is_numeric( $club_player ) ) {
				$sql .= ',' . $club_player;
			}
		}
		$sql .= ')';
		$sql .= ' AND `system_record` IS NULL';
		if ( $club ) {
			$sql .= ' AND `affiliatedclub` = ' . $club;
		}
		$sql    .= ' ORDER BY `player_id`';
		$players = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$sql,
		); // db call ok, no cache ok.
		$event_players = array();
		foreach ( $players as $player ) {
			$player          = get_player( $player->player_id );
			$event_players[] = $player->fullname;
		}
		if ( $count ) {
			return count( $event_players );
		}
		asort( $event_players );
		if ( $group ) {
			$this->players = array();
			foreach ( $event_players as $player ) {
				$key = strtoupper( substr( $player, 0, 1 ) );
				if ( false === array_key_exists( $key, $this->players ) ) {
					$this->players[ $key ] = array();
				}
				// now just add the row data.
				$this->players[ $key ][] = $player;
			}
		} else {
			$this->players = $event_players;
		}

		return $this->players;
	}
	/**
	 * Mark teams withdrawn from event
	 *
	 * @param string $season season.
	 * @param int    $club Club Id.
	 * @param int    $team team id (optional).
	 */
	public function mark_teams_withdrawn( $season, $club, $team = false ) {
		global $wpdb;

		$search_terms = array();
		if ( $team ) {
			$search_terms[] = $wpdb->prepare( '`team_id` = %d', intval( $team ) );
		}
		$search = '';
		if ( ! empty( $search_terms ) ) {
			$search  = ' AND ';
			$search .= implode( ' AND ', $search_terms );
		}

		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"UPDATE {$wpdb->racketmanager_table} SET `profile` = 3, `status` = 'W' WHERE `league_id` IN (select `id` FROM {$wpdb->racketmanager} WHERE `event_id` = %d) AND `season` = %s AND `team_id` IN (SELECT `id` FROM {$wpdb->racketmanager_teams} WHERE `affiliatedclub` = %d) $search ",
				$this->id,
				$season,
				$club
			)
		);
	}

	/**
	 * Mark teams entered into event
	 *
	 * @param int    $team Team Id.
	 * @param string $season season.
	 */
	public function mark_teams_entered( $team, $season ) {
		global $wpdb;
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"UPDATE {$wpdb->racketmanager_table} SET `profile` = 1 WHERE `league_id` IN (select `id` FROM {$wpdb->racketmanager} WHERE `event_id` = %d) AND `season` = %s AND `team_id` = %d",
				$this->id,
				$season,
				$team
			)
		);
	}

	/**
	 * Add team entered into event
	 *
	 * @param int    $team Team Id.
	 * @param string $season season.
	 */
	public function add_team_to_event( $team, $season ) {
		$leagues   = $this->get_leagues( array( 'orderby' => array( 'title' => 'DESC' ) ) );
		$league_id = $leagues[0]->id;
		$rank      = 99;
		$status    = 'NT';
		$profile   = 2;
		$league    = get_league( $league_id );
		$league->add_team( $team, $season, $rank, $status, $profile );
	}
	/**
	 * Get matches for event
	 *
	 * @param array $match_args query arguments.
	 * @return array $matches
	 */
	public function get_matches( $match_args ) {
		global $wpdb;

		$match_args           = array_merge( $this->match_query_args, (array) $match_args );
		$league_id            = $match_args['leagueId'];
		$season               = $match_args['season'];
		$final                = $match_args['final'];
		$orderby              = $match_args['orderby'];
		$confirmed            = $match_args['confirmed'];
		$player               = $match_args['player'];
		$match_date           = $match_args['match_date'];
		$time                 = $match_args['time'];
		$time_offset          = $match_args['timeOffset'];
		$history              = $match_args['history'];
		$club                 = $match_args['club'];
		$league_name          = $match_args['league_name'];
		$team_name            = $match_args['team_name'];
		$home_team            = $match_args['home_team'];
		$home_club            = $match_args['home_club'];
		$away_team            = $match_args['away_team'];
		$match_day            = $match_args['match_day'];
		$count                = $match_args['count'];
		$confirmation_pending = $match_args['confirmationPending'];
		$result_pending       = $match_args['resultPending'];
		$status               = $match_args['status'];
		if ( $count ) {
			$sql = "SELECT COUNT(*) FROM {$wpdb->racketmanager_matches} WHERE 1 = 1 AND l.`event_id` = $this->id";
		} else {
			$sql_fields = "SELECT DISTINCT m.`final` AS final_round, m.`group`, `home_team`, `away_team`, DATE_FORMAT(m.`date`, '%Y-%m-%d %H:%i') AS date, DATE_FORMAT(m.`date`, '%e') AS day, DATE_FORMAT(m.`date`, '%c') AS month, DATE_FORMAT(m.`date`, '%Y') AS year, DATE_FORMAT(m.`date`, '%H') AS `hour`, DATE_FORMAT(m.`date`, '%i') AS `minutes`, `match_day`, `location`, l.`id` AS `league_id`, m.`home_points`, m.`away_points`, m.`winner_id`, .m.`loser_id`, m.`post_id`, `season`, m.`id` AS `id`, m.`custom`, `confirmed`, `home_captain`, `away_captain`, `comments`, `updated`";
			$sql        = " FROM {$wpdb->racketmanager_matches} AS m, {$wpdb->racketmanager} AS l, {$wpdb->racketmanager_rubbers} AS r WHERE m.`league_id` = l.`id` AND m.`id` = r.`match_id` AND l.`event_id` = $this->id";
		}

		if ( $match_date ) {
			$sql .= " AND DATEDIFF('" . htmlspecialchars( wp_strip_all_tags( $match_date ) ) . "', `date`) = 0";
		}
		if ( $league_id ) {
			$sql .= " AND `league_id`  = '" . $league_id . "'";
		}
		if ( $league_name ) {
			$sql .= " AND `league_id` in (select `id` from {$wpdb->racketmanager} WHERE `title` = '" . $league_name . "')";
		}
		if ( $season ) {
			$sql .= " AND `season`  = '" . $season . "'";
		}
		if ( $final ) {
			$sql .= " AND `final`  = '" . $final . "'";
		}
		if ( $time_offset ) {
			$time_offset = intval( $time_offset ) . ':00:00';
		} else {
			$time_offset = '00:00:00';
		}
		if ( $status ) {
			$sql .= " AND `confirmed` = '" . $status . "'";
		}
		if ( $confirmed ) {
			$sql .= " AND `confirmed` in ('P','A','C')";
			if ( $time_offset ) {
				$sql .= " AND ADDTIME(`updated`,'" . $time_offset . "') <= NOW()";
			}
		}
		if ( $player ) {
			$sql .= " AND ( `home_player_1` = '$player' OR `home_player_2` = '$player' OR `away_player_1` = '$player' OR `away_player_2` = '$player')";
		}
		if ( $confirmation_pending ) {
			$confirmation_pending = intval( $confirmation_pending ) . ':00:00';
			$sql_fields          .= ",ADDTIME(`updated`,'" . $confirmation_pending . "') as confirmation_overdue_date, TIME_FORMAT(TIMEDIFF(now(),ADDTIME(`updated`,'" . $confirmation_pending . "')), '%H')/24 as overdue_time";
		}
		if ( $result_pending ) {
			$result_pending = intval( $result_pending ) . ':00:00';
			$sql_fields    .= ",ADDTIME(`date`,'" . $result_pending . "') as result_overdue_date, TIME_FORMAT(TIMEDIFF(now(),ADDTIME(`date`,'" . $result_pending . "')), '%H')/24 as overdue_time";
		}

		// get only finished matches with score for time 'latest'.
		if ( 'latest' === $time ) {
			$sql .= " AND (m.`home_points` != '' OR m.`away_points` != '')";
		} elseif ( 'outstanding' === $time ) {
			$sql .= " AND ADDTIME(m.`date`,'" . $time_offset . "') <= NOW() AND m.`winner_id` = 0 AND `confirmed` IS NULL";
		} elseif ( 'next' === $time ) {
			$sql .= ' AND TIMESTAMPDIFF(MINUTE, NOW(), m.`date`) >= 0';
		}
		// get only updated matches in specified period for history.
		if ( $history ) {
			$sql .= ' AND `updated` >= NOW() - INTERVAL ' . $history . ' DAY';
		}

		if ( $club ) {
			$sql .= " AND (`home_team` IN (SELECT `id` FROM {$wpdb->racketmanager_teams} WHERE `affiliatedclub` = " . $club . ") OR `away_team` IN (SELECT `id` FROM {$wpdb->racketmanager_teams} WHERE `affiliatedclub` = " . $club . '))';
		}
		if ( $home_club ) {
			$sql .= " AND `home_team` IN (SELECT `id` FROM {$wpdb->racketmanager_teams} WHERE `affiliatedclub` = " . $home_club . ')';
		}
		if ( ! empty( $home_team ) ) {
			$sql .= ' AND `home_team` = ' . $home_team . ' ';
		}
		if ( ! empty( $away_team ) ) {
			$sql .= ' AND `away_team` = ' . $away_team . ' ';
		}
		if ( ! empty( $team_name ) ) {
			$sql .= " AND (`home_team` IN (SELECT `id` FROM {$wpdb->racketmanager_teams} WHERE `title` LIKE '%" . $team_name . "%') OR `away_team` IN (SELECT `id` FROM {$wpdb->racketmanager_teams} WHERE `title` LIKE '%" . $team_name . "%'))";
		}
		if ( $match_day && intval( $match_day ) > 0 ) {
			$sql .= ' AND `match_day` = ' . $match_day . ' ';
		}

		if ( $count ) {
			$matches = intval(
				$wpdb->get_var( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					$sql
				)
			);
		} else {
			$orderby_string = '';
			$i              = 0;
			if ( is_array( $orderby ) ) {
				foreach ( $orderby as $order => $direction ) {
					$orderby_string .= '`' . $order . '` ' . $direction;
					if ( $i < ( count( $orderby ) - 1 ) ) {
						$orderby_string .= ',';
					}
					++$i;
				}
			}
			$sql = $sql_fields . $sql . ' ORDER BY ' . $orderby_string;
			// get matches.
			$matches = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql
			);
			$class = '';

			foreach ( $matches as $i => $match ) {
				$class        = ( 'alternate' === $class ) ? '' : 'alternate';
				$match        = get_match( $match );
				$match->class = $class;
				if ( $player ) {
					$match->rubbers = $match->get_rubbers( $player );
				}
				$matches[ $i ] = $match;
			}
		}

		return $matches;
	}
	/**
	 * Match query arguments
	 *
	 * @var array
	 */
	private $match_query_args = array(
		'leagueId'            => false,
		'season'              => false,
		'final'               => false,
		'orderby'             => array(
			'league_id' => 'ASC',
			'id'        => 'ASC',
		),
		'confirmed'           => false,
		'player'              => false,
		'match_date'          => false,
		'time'                => false,
		'timeOffset'          => false,
		'history'             => false,
		'club'                => false,
		'league_name'         => false,
		'team_name'           => false,
		'home_team'           => false,
		'away_team'           => false,
		'match_day'           => false,
		'home_club'           => false,
		'count'               => false,
		'confirmationPending' => false,
		'resultPending'       => false,
		'status'              => false,
	);
}
