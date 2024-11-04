<?php
/**
 * Racketmanager_Competition API: Competition class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Competition
 */

namespace Racketmanager;

/**
 * Class to implement the Competition object
 */
class Racketmanager_Competition {
	/**
	 * Competition ID
	 *
	 * @var int
	 */
	public $id;

	/**
	 * Competition name
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
	 * Team ranking mode
	 *
	 * @var string
	 */
	public $team_ranking = 'auto';

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
	public $standings = array(
		'status'     => 1,
		'pld'        => 1,
		'won'        => 1,
		'tie'        => 1,
		'lost'       => 1,
		'winPercent' => 1,
		'last5'      => 1,
		'sets'       => 1,
		'games'      => 1,
	);

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
	 * Event offsets indexed by ID
	 *
	 * @var array
	 */
	public $event_index = array();

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
	 * Championship flag
	 *
	 * @var boolean
	 */
	public $is_championship = false;

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
	 * Number of events
	 *
	 * @var int
	 */
	public $num_events = '';

	/**
	 * Events
	 *
	 * @var array
	 */
	public $events = '';

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
	 * Competition Teams
	 *
	 * @var array
	 */
	public $competition_teams = '';
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
	 * Cup flag
	 *
	 * @var boolean
	 */
	public $is_cup = false;
	/**
	 * Tournament flag
	 *
	 * @var boolean
	 */
	public $is_tournament = false;
	/**
	 * League flag
	 *
	 * @var boolean
	 */
	public $is_league = false;
	/**
	 * Team entry flag
	 *
	 * @var boolean
	 */
	public $is_team_entry = false;
	/**
	 * Teams
	 *
	 * @var array
	 */
	public $teams = array();
	/**
	 * Current phase string
	 *
	 * @var string
	 */
	public $current_phase = false;
	/**
	 * Player entry flag
	 *
	 * @var boolean
	 */
	public $is_player_entry = false;
	/**
	 * Players array
	 *
	 * @var array
	 */
	public $players = array();
	/**
	 * Clubs array
	 *
	 * @var array
	 */
	public $clubs = array();
	/**
	 * Date Open
	 *
	 * @var string
	 */
	public $date_open;
	/**
	 * Date Start
	 *
	 * @var string
	 */
	public $date_start;
	/**
	 * Date End
	 *
	 * @var string
	 */
	public $date_end;
	/**
	 * Venue
	 *
	 * @var string
	 */
	public $venue;
	/**
	 * Is complete
	 *
	 * @var boolean
	 */
	public $is_complete = false;
	/**
	 * Is started
	 *
	 * @var boolean
	 */
	public $is_started = false;
	/**
	 * Is closed
	 *
	 * @var boolean
	 */
	public $is_closed = false;
	/**
	 * Is open
	 *
	 * @var boolean
	 */
	public $is_open = false;
	/**
	 * Competition code
	 *
	 * @var string
	 */
	public $competition_code;
	/**
	 * Retrieve competition instance
	 *
	 * @param int    $competition_id competition id.
	 * @param string $search_term search.
	 */
	public static function get_instance( $competition_id, $search_term = 'id' ) {
		global $wpdb;
		switch ( $search_term ) {
			case 'name':
				$search = $wpdb->prepare(
					'`name` = %s',
					$competition_id
				);
				break;
			case 'id':
			default:
				$competition_id = (int) $competition_id;
				$search         = $wpdb->prepare(
					'`id` = %d',
					$competition_id
				);
				break;
		}
		if ( ! $competition_id ) {
			return false;
		}
		$competition = wp_cache_get( $competition_id, 'competitions' );
		if ( ! $competition ) {
			$competition = $wpdb->get_row(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				"SELECT `name`, `id`, `type`, `settings`, `seasons` FROM {$wpdb->racketmanager_competitions} WHERE " . $search . ' LIMIT 1'
			);
			if ( ! $competition ) {
				return false;
			}
			$competition->settings         = (array) maybe_unserialize( $competition->settings );
			$competition->settings['type'] = $competition->type;
			$competition                   = (object) ( $competition->settings + (array) $competition );
			// check if specific sports class exists.
			if ( ! isset( $competition->sport ) ) {
				$competition->sport = '';
			}
			$instance = 'Racketmanager\Racketmanager_Competition_' . ucfirst( $competition->sport );
			if ( class_exists( $instance ) ) {
				$competition = new $instance( $competition );
			} else {
				$competition = new Racketmanager_Competition( $competition );
			}

			wp_cache_set( $competition->id, $competition, 'competitions' );
		}

		return $competition;
	}

	/**
	 * Constructor
	 *
	 * @param object $competition Competition object.
	 */
	public function __construct( $competition ) {
		if ( ! isset( $competition->id ) ) {
			$this->add( $competition );
		}
		if ( isset( $competition->settings ) ) {
			$competition->settings      = (array) maybe_unserialize( $competition->settings );
			$competition->settings_keys = array_keys( (array) maybe_unserialize( $competition->settings ) );
			$competition                = (object) array_merge( (array) $competition, $competition->settings );
		}

		foreach ( get_object_vars( $competition ) as $key => $value ) {
			if ( 'standings' === $key ) {
				$this->$key = array_merge( $this->$key, $value );
			} else {
				$this->$key = $value;
			}
		}

		$this->name = stripslashes( $this->name );
		$this->type = stripslashes( $this->type );

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
		$this->set_num_events( true );
		// set season to latest.
		if ( $this->num_seasons > 0 ) {
			$this->set_season();
			if ( ! empty( $this->current_season['dateOpen'] ) ) {
				$this->date_open = $this->current_season['dateOpen'];
			}
			if ( ! empty( $this->current_season['dateStart'] ) ) {
				$this->date_start = $this->current_season['dateStart'];
			}
			if ( ! empty( $this->current_season['dateEnd'] ) ) {
				$this->date_end = $this->current_season['dateEnd'];
			}
			if ( ! empty( $this->current_season['venue_name'] ) ) {
				$this->venue = $this->current_season['venue_name'];
			}
		}

		// Championship.
		if ( 'championship' === $this->mode ) {
			$this->is_championship = true;
		} else {
			$this->is_championship = false;
		}
		$this->is_league       = false;
		$this->is_cup          = false;
		$this->is_tournament   = false;
		$this->is_team_entry   = false;
		$this->is_player_entry = false;
		switch ( $this->type ) {
			case 'league':
				$this->is_league     = true;
				$this->is_team_entry = true;
				break;
			case 'cup':
				$this->is_cup        = true;
				$this->is_team_entry = true;
				break;
			case 'tournament':
				$this->is_tournament   = true;
				$this->is_player_entry = true;
				break;
			default:
				break;
		}
		if ( empty( $this->competition_code ) ) {
			$this->competition_code = null;
		}
	}

	/**
	 * Add new competition
	 *
	 * @param object $competition competition object.
	 */
	private function add( $competition ) {
		global $wpdb;

		if ( 'league' === $competition->type ) {
			$mode       = 'default';
			$entry_type = 'team';
		} elseif ( 'cup' === $competition->type ) {
			$mode       = 'championship';
			$entry_type = 'team';
		} elseif ( 'tournament' === $competition->type ) {
			$mode       = 'championship';
			$entry_type = 'player';
		}
		if ( 'championship' === $mode ) {
			$ranking   = 'manual';
			$standings = array(
				'pld'  => 1,
				'won'  => 1,
				'tie'  => 1,
				'lost' => 1,
			);
		} else {
			$ranking   = 'auto';
			$standings = array(
				'pld'  => 0,
				'won'  => 0,
				'tie'  => 0,
				'lost' => 0,
			);
		}
		$settings = array(
			'sport'                    => 'tennis',
			'point_rule'               => 'tennis',
			'point_format'             => '%s',
			'point_format2'            => '%s',
			'team_ranking'             => $ranking,
			'mode'                     => $mode,
			'entry_type'               => $entry_type,
			'default_match_start_time' => array(
				'hour'    => 19,
				'minutes' => 30,
			),
			'standings'                => $standings,
			'num_ascend'               => '',
			'num_descend'              => '',
			'num_relegation'           => '',
			'num_matches_per_page'     => 10,
		);

		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"INSERT INTO {$wpdb->racketmanager_competitions} (`name`, `type`, `settings` ) VALUES (%s, %s, %s)",
				$competition->name,
				$competition->type,
				maybe_serialize( $settings ),
			)
		);
		$competition->id = $wpdb->insert_id;
	}

	/**
	 * Delete Competition
	 */
	public function delete() {
		global $wpdb;

		foreach ( $this->get_events() as $event ) {
			$event = get_event( $event->id );
			$event->delete();
		}
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE FROM {$wpdb->racketmanager_competitions_seasons} WHERE `competition_id` = %d",
				$this->id
			)
		);
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE FROM {$wpdb->racketmanager_competitions} WHERE `id` = %d",
				$this->id
			)
		);
	}

	/**
	 * Set name
	 *
	 * @param string $name competition name.
	 */
	public function set_name( $name ) {
		global $wpdb;

		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"UPDATE {$wpdb->racketmanager_competitions} SET `name` = %s WHERE `id` =%d",
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
		global $wpdb, $racketmanager;
		foreach ( $racketmanager->get_standings_display_options() as $key => $value ) {
			$settings['standings'][ $key ] = isset( $settings['standings'][ $key ] ) ? 1 : 0;
		}
		$type = $settings['type'];

		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"UPDATE {$wpdb->racketmanager_competitions} SET `settings` = %s, `type` = %s WHERE `id` = %d",
				maybe_serialize( $settings ),
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
			$data = false;
		}
		$today = gmdate( 'Y-m-d' );
		if ( empty( $data ) ) {
			foreach ( array_reverse( $this->seasons ) as $season ) {
				if ( empty( $season['dateStart'] ) || $season['dateStart'] <= $today ) {
					$data = $season;
					break;
				}
			}
		}
		if ( empty( $data ) ) {
			$data = end( $this->seasons );
		}
		$count_matchdates = isset( $data['matchDates'] ) && is_array( $data['matchDates'] ) ? count( $data['matchDates'] ) : 0;
		if ( empty( $data['dateEnd'] ) && $count_matchdates >= 2 ) {
			$data['dateEnd']                = end( $data['matchDates'] );
			$this->seasons[ $data['name'] ] = $data;
		}
		if ( empty( $data['dateStart'] ) && $count_matchdates >= 2 ) {
			$data['dateStart']              = $data['matchDates'][0];
			$this->seasons[ $data['name'] ] = $data;
		}
		$this->current_phase = 'complete';
		if ( ! empty( $data['dateEnd'] ) && $today > $data['dateEnd'] ) {
			$this->current_phase = 'end';
			$this->is_complete   = true;
		} elseif ( ! empty( $data['dateStart'] ) && $today >= $data['dateStart'] ) {
			$this->current_phase = 'start';
			$this->is_started    = true;
		} elseif ( ! empty( $data['closing_date'] ) && $today > $data['closing_date'] ) {
			$this->current_phase = 'close';
			$this->is_closed     = true;
		} elseif ( ! empty( $data['dateOpen'] ) && $today >= $data['dateOpen'] ) {
			$this->current_phase = 'open';
			$this->is_open       = true;
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
	 * Gets number of events
	 *
	 * @param boolean $total should total be stored.
	 */
	public function set_num_events( $total = false ) {
		global $wpdb;

		if ( true === $total ) {
			$this->num_events = $wpdb->get_var( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT COUNT(ID) FROM {$wpdb->racketmanager_events} WHERE `competition_id` = %d",
					$this->id
				)
			);
		}
	}

	/**
	 * Get events from database
	 *
	 * @param array $args search arguments.
	 * @return array
	 */
	public function get_events( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'offset'  => 0,
			'limit'   => 99999999,
			'orderby' => array( 'name' => 'ASC' ),
		);
		$args     = array_merge( $defaults, $args );
		$offset   = $args['offset'];
		$limit    = $args['limit'];
		$orderby  = $args['orderby'];

		$search_terms   = array();
		$search_terms[] = $wpdb->prepare( '`competition_id` = %d', intval( $this->id ) );

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
			"SELECT `name`, `id`, `settings`, `competition_id` FROM {$wpdb->racketmanager_events} $search ORDER BY $orderby LIMIT %d, %d",
			intval( $offset ),
			intval( $limit )
		);
		$events = wp_cache_get( md5( $sql ), 'events' );
		if ( ! $events ) {
			$events = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql
			);
			wp_cache_set( md5( $sql ), $events, 'events' );
		}

		$event_index = array();
		foreach ( $events as $i => $event ) {
			$event_index[ $event->id ] = $i;
			$event                     = get_event( $event->id );
			$events[ $i ]              = $event;
		}

		$this->events      = $events;
		$this->event_index = $event_index;

		return $events;
	}

	/**
	 * Get league from database
	 *
	 * @param string $title league title.
	 * @return int   $league_id league id.
	 */
	public function get_league_id( $title ) {
		global $wpdb;

		$sql    = $wpdb->prepare( "SELECT `id` FROM {$wpdb->racketmanager} WHERE `title` = %s", $title );
		$league = wp_cache_get( md5( $sql ), 'league' );
		if ( ! $league ) {
			$league = $wpdb->get_row( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql
			);
			wp_cache_set( md5( $sql ), $league, 'league' );
		}

		if ( ! $league ) {
			return 0;
		}

		return $league->id;
	}

	/**
	 * Get specific team details from database
	 *
	 * @param int $team_id team id.
	 * @return array database results
	 */
	public function get_team_info( $team_id ) {
		global $wpdb;

		$sql = "SELECT `captain`, `match_day`, `match_time` FROM {$wpdb->racketmanager_team_competition} WHERE `competition_id` = " . $this->id . ' AND `team_id` = ' . $team_id;

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

		wp_cache_delete( $this->id, 'competitions' );
		$result = $wpdb->get_row( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT `settings` FROM {$wpdb->racketmanager_competitions} WHERE `id` = %d",
				intval( $this->id )
			)
		);
		foreach ( maybe_unserialize( $result->settings ) as $key => $value ) {
			$this->$key = $value;
		}
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

		$search_terms   = array();
		$search_terms[] = $wpdb->prepare( 'e.`competition_id` = %d', $this->id );

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
			$sql = 'SELECT `l`.`title` AS `league_title`, l.`id` AS `league_id`, t2.`id` AS `team_id`, t1.`id` AS `tableId`, `t2`.`title` as `name`,`t1`.`rank`, l.`id`, t1.`status`, t1.`profile`, t1.`group`, t2.`roster`, t2.`affiliatedclub`, t2.`status` AS `team_type`, e.`name` AS `event_name`';
		}
		$sql .= " FROM {$wpdb->racketmanager_events} e, {$wpdb->racketmanager} l, {$wpdb->racketmanager_teams} t2, {$wpdb->racketmanager_table} t1 WHERE e.`id` = l.`event_id` AND t1.`team_id` = t2.`id` AND l.`id` = t1.`league_id` " . $search;

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
		$teams = wp_cache_get( md5( $sql ), 'teams' );
		if ( ! $teams ) {
			$teams = $wpdb->get_results(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql
			); // db call ok.
			wp_cache_set( md5( $sql ), $teams, 'teams' );
		}
		foreach ( $teams as $i => $team ) {
			$team->roster = maybe_unserialize( $team->roster );
			$team->club   = get_club( $team->affiliatedclub );
			if ( strpos( $team->name, '_' ) !== false ) {
				$team_name  = null;
				$name_array = explode( '_', $team->name );
				if ( '1' === $name_array[0] ) {
					$team_name = __( 'Winner of', 'racketmanager' );
				} elseif ( '2' === $name_array[0] ) {
					$team_name = __( 'Loser of', 'racketmanager' );
				}
				if ( ! empty( $team_name ) && is_numeric( $name_array[2] ) ) {
					$match = get_match( $name_array[2] );
					if ( $match ) {
						$team_name .= ' ' . $match->teams['home']->title . ' ' . __( 'vs', 'racketmanager' ) . ' ' . $match->teams['away']->title;
					}
				}
				if ( ! empty( $team_name ) ) {
					$team->title = $team_name;
				}
			}
			$team->title        = $team->name;
			$team->player_count = $this->get_players(
				array(
					'season' => $season,
					'count'  => true,
					'team'   => $team->team_id,
				)
			);
			$teams[ $i ]        = $team;
		}

		$this->teams = $teams;

		return $teams;
	}
	/**
	 * Get players for competition
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
			'stats'   => false,
		);
		$args     = array_merge( $defaults, $args );
		$offset   = $args['offset'];
		$limit    = $args['limit'];
		$season   = $args['season'];
		$orderby  = $args['orderby'];
		$club     = $args['club'];
		$team     = $args['team'];
		$count    = $args['count'];
		$stats    = $args['stats'];

		if ( $this->is_player_entry ) {
			$teams = $this->get_teams(
				array(
					'season' => $season,
				)
			);
			foreach ( $teams as $team ) {
				foreach ( $team->player as $player ) {
					$players[] = $player;
				}
			}
			$event_players = array_unique( $players );
		} else {
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
				$search_terms[] .= '(( `home_team` = %d AND `player_team` = %s) OR (`away_team` = %d AND `player_team` = %s))';
				$search_args[]   = $team;
				$search_args[]   = 'home';
				$search_args[]   = $team;
				$search_args[]   = 'away';
			}
			if ( $club ) {
				$search_terms[] .= "(( `home_team` in (SELECT `id` FROM {$wpdb->racketmanager_teams} WHERE `affiliatedclub` = %d) AND `player_team` = %s) OR (`away_team` in (SELECT `id` FROM {$wpdb->racketmanager_teams} WHERE `affiliatedclub` = %d) AND `player_team` = %s))";
				$search_args[]   = $club;
				$search_args[]   = 'home';
				$search_args[]   = $club;
				$search_args[]   = 'away';
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
			if ( $count ) {
				$sql = 'SELECT COUNT(distinct(`player_id`))';
			} else {
				$sql = 'SELECT DISTINCT `player_id`, `club_player_id`';
			}
			$sql .= " FROM {$wpdb->racketmanager_rubber_players} rp, {$wpdb->racketmanager_rubbers} r, {$wpdb->racketmanager_matches} m  WHERE rp.`rubber_id` = r.`id` AND r.`match_id` = m.`id` AND m.`league_id` IN (SELECT l.`id` FROM {$wpdb->racketmanager} l, {$wpdb->racketmanager_events} e WHERE l.`event_id` = e.`id` AND e.`competition_id` = %d)" . $search;
			if ( $count ) {
				$sql = $wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					$sql,
					$search_args,
				);
				$num_players = wp_cache_get( md5( $sql ), 'competition_rubber_players' );
				if ( ! $num_players ) {
					$num_players = $wpdb->get_var(
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
						$sql
					); // db call ok.
					wp_cache_set( md5( $sql ), $num_players, 'competition_rubber_players' );
				}
				return $num_players;
			}
			$sql .= $order;
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
			$players = wp_cache_get( md5( $sql ), 'competition_rubber_players' );
			if ( ! $players ) {
				$players = $wpdb->get_results(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					$sql
				); // db call ok.
				wp_cache_set( md5( $sql ), $players, 'competition_rubber_players' );
			}
			$competition_players = array();
			foreach ( $players as $player ) {
				$player = get_player( $player->player_id );
				if ( $player && ! $player->system_record ) {
					if ( $stats ) {
						$player->matches      = $player->get_matches( $this, $this->current_season['name'], 'competition' );
						$player->stats        = $player->get_stats();
						$player->win_pct      = $player->stats['total']->win_pct;
						$player->matches_won  = $player->stats['total']->matches_won;
						$player->matches_lost = $player->stats['total']->matches_lost;
						$player->played       = $player->stats['total']->played;
					}
					$competition_players[] = $player;
				}
			}
		}
		if ( $stats ) {
			$won    = array_column( $competition_players, 'matches_won' );
			$played = array_column( $competition_players, 'played' );
			array_multisort( $won, SORT_DESC, $played, SORT_ASC, $competition_players );
		} else {
			asort( $competition_players );
		}
		$this->players = $competition_players;
		return $this->players;
	}
	/**
	 * Get clubs for competition
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
		$search_terms[] = $wpdb->prepare( '`competition_id` = %d', $this->id );
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
		$sql .= " FROM {$wpdb->racketmanager_events} e,{$wpdb->racketmanager} l, {$wpdb->racketmanager_teams} t2, {$wpdb->racketmanager_table} t1, {$wpdb->racketmanager_clubs} c WHERE e.`id` = l.`event_id` AND t1.`team_id` = t2.`id` AND l.`id` = t1.`league_id` AND t2.`affiliatedclub` = c.`id`" . $search;

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

		$competition_clubs = wp_cache_get( md5( $sql ), 'competition_clubs' );
		if ( ! $competition_clubs ) {
			$competition_clubs = $wpdb->get_results(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql
			); // db call ok.
			wp_cache_set( md5( $sql ), $competition_clubs, 'competition_clubs' );
		}

		foreach ( $competition_clubs as $i => $competition_club ) {
			$team_count                     = $competition_club->team_count;
			$competition_club               = get_club( $competition_club->affiliatedclub );
			$competition_club->team_count   = $team_count;
			$competition_club->player_count = $this->get_players(
				array(
					'season' => $season,
					'count'  => true,
					'club'   => $competition_club->id,
				)
			);
			$competition_clubs[ $i ]        = $competition_club;
		}

		$this->clubs = $competition_clubs;

		return $competition_clubs;
	}
	/**
	 * Get matches for competition
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
		$sql_from             = " FROM {$wpdb->racketmanager_matches} AS m, {$wpdb->racketmanager} AS l, {$wpdb->racketmanager_events} AS e, {$wpdb->racketmanager_rubbers} AS r";
		if ( $count ) {
			$sql_fields = 'SELECT COUNT(*)';
			$sql        = " WHERE 1 = 1 AND l.`event_id` = e.`id` AND e.`competition_id` = $this->id";
		} else {
			$sql_fields = "SELECT DISTINCT m.`final` AS final_round, m.`group`, `home_team`, `away_team`, DATE_FORMAT(m.`date`, '%Y-%m-%d %H:%i') AS date, DATE_FORMAT(m.`date`, '%e') AS day, DATE_FORMAT(m.`date`, '%c') AS month, DATE_FORMAT(m.`date`, '%Y') AS year, DATE_FORMAT(m.`date`, '%H') AS `hour`, DATE_FORMAT(m.`date`, '%i') AS `minutes`, `match_day`, `location`, l.`id` AS `league_id`, m.`home_points`, m.`away_points`, m.`winner_id`, .m.`loser_id`, m.`post_id`, `season`, m.`id` AS `id`, m.`custom`, `confirmed`, `home_captain`, `away_captain`, `comments`, `updated`, m.`leg`";
			$sql        = " WHERE m.`league_id` = l.`id` AND m.`id` = r.`match_id` AND l.`event_id` = e.`id` AND e.`competition_id` = $this->id";
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
			$sql_from .= ", {$wpdb->racketmanager_rubber_players} AS rp";
			$sql      .= ' AND r.`id` = rp.`rubber_id`';
			$sql      .= " AND rp.`player_id` = '$player'";
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
		$sql = $sql_fields . $sql_from . $sql;
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
			$sql = $sql . ' ORDER BY ' . $orderby_string;
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
	/**
	 * Get winners function
	 *
	 * @param boolean $group_by group by flag.
	 * @return array
	 */
	public function get_winners( $group_by = false ) {
		global $wpdb;

		if ( $this->is_league ) {
			$winners = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT l.`title` ,wt.`title` AS `winner` ,e.`type`, e.`name` AS `event_name`, e.`id` AS `event_id` FROM {$wpdb->racketmanager_table} t, {$wpdb->racketmanager} l, {$wpdb->racketmanager_teams} wt, {$wpdb->racketmanager_events} e WHERE t.`league_id` = l.`id` AND l.`event_id` = e.`id` AND e.`competition_id` = %d AND t.`season` = %d AND t.rank = 1 AND t.team_id = wt.id order by e.`name`, l.`title`",
					$this->id,
					$this->current_season['name']
				)
			);
		} else {
			$winners = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT l.`title` ,wt.`title` AS `winner` ,lt.`title` AS `loser`, m.`id`, m.`home_team`, m.`away_team`, m.`winner_id` AS `winner_id`, m.`loser_id` AS `loser_id`, e.`type`, e.`name` AS `event_name`, e.`id` AS `event_id`, wt.`status` AS `team_type` FROM {$wpdb->racketmanager_matches} m, {$wpdb->racketmanager} l, {$wpdb->racketmanager_teams} wt, {$wpdb->racketmanager_teams} lt, {$wpdb->racketmanager_events} e WHERE `league_id` = l.`id` AND l.`event_id` = e.`id` AND e.`competition_id` = %d AND m.`final` = 'FINAL' AND m.`season` = %d AND m.`winner_id` = wt.`id` AND m.`loser_id` = lt.`id` order by e.`name`, l.`title`",
					$this->id,
					$this->current_season['name']
				)
			);
		}

		if ( ! $winners ) {
			return false;
		}

		$return = array();
		foreach ( $winners as $winner ) {
			if ( ! $this->is_league ) {
				$match = get_match( $winner->id );
			}
			if ( $this->is_player_entry ) {
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
				$winner->winner_club = $winner_club;
				$winner->loser_club  = $loser_club;
			}
			$winner->league           = $winner->title;
			$winner->competition_name = $this->name;
			$winner->competition_type = $this->type;
			$winner->season           = $this->current_season['name'];
			$winner->is_team_entry    = $this->is_team_entry;
			if ( $group_by ) {
				$key = strtoupper( $winner->type );
				if ( false === array_key_exists( $key, $return ) ) {
					$return[ $key ] = array();
				}
				// now just add the row data.
				$return[ $key ][] = $winner;
			} else {
				$return[] = $winner;
			}
		}
		return $return;
	}
	/**
	 * Update seasons
	 *
	 * @param array $seasons season data.
	 */
	public function update_seasons( $seasons ) {
		global $wpdb;
		if ( $this->seasons !== $seasons ) {
			$this->seasons = $seasons;
			$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"UPDATE {$wpdb->racketmanager_competitions} SET `seasons` = %s WHERE `id` = %d",
					maybe_serialize( $seasons ),
					$this->id
				)
			);
			wp_cache_delete( $this->id, 'competitions' );
		}
	}
}
