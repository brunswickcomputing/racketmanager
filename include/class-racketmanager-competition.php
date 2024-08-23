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
			$data = end( $this->seasons );
		}

		if ( empty( $data ) ) {
			$data = end( $this->seasons );
		}

		$count_matchdates = isset( $data['matchDates'] ) ? count( $data['matchDates'] ) : 0;
		if ( empty( $data['dateEnd'] ) && $count_matchdates >= 2 ) {
			$data['dateEnd'] = end( $data['matchDates'] );
		}
		if ( empty( $data['dateStart'] ) && $count_matchdates >= 2 ) {
			$data['dateStart'] = $data['matchDates'][0];
		}
		$today               = gmdate( 'Y-m-d' );
		$this->current_phase = 'complete';
		if ( ! empty( $data['dateEnd'] ) && $today > $data['dateEnd'] ) {
			$this->current_phase = 'end';
		} elseif ( ! empty( $data['dateStart'] ) && $today >= $data['dateStart'] ) {
			$this->current_phase = 'start';
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
	public function get_season_competition( $season = false, $index = false ) {
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
}
