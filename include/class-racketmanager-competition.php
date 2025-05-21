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
	public int $id;

	/**
	 * Competition name
	 *
	 * @var string
	 */
	public string $name;

	/**
	 * Seasons data
	 *
	 * @var array|string
	 */
	public array|string $seasons = array();

	/**
	 * Number of seasons
	 *
	 * @var int
	 */
	public int $num_seasons = 0;

	/**
	 * Sport type
	 *
	 * @var string
	 */
	public string $sport = 'tennis';

	/**
	 * Point rule
	 *
	 * @var string
	 */
	public string $point_rule = 'tennis';

	/**
	 * Primary points format
	 *
	 * @var string
	 */
	public string $point_format = '%d-%d';

	/**
	 * Secondary points format
	 *
	 * @var string
	 */
	public string $point_format2 = '%d-%d';

	/**
	 * Team ranking mode
	 *
	 * @var string
	 */
	public string $team_ranking = 'auto';

	/**
	 * League mode
	 *
	 * @var string
	 */
	public string $mode = 'default';

	/**
	 * Default match starting time
	 *
	 * @var array
	 */
	public array $default_match_start_time = array(
		'hour'    => 19,
		'minutes' => 30,
	);
	/**
	 * Finals
	 *
	 * @var array
	 */
	public array $finals = array();

	/**
	 * Standings table layout settings
	 *
	 * @var array
	 */
	public array $standings = array(
		'status'     => 1,
		'pld'        => 1,
		'won'        => 1,
		'tie'        => 1,
		'lost'       => 1,
		'winPercent' => 0,
		'last5'      => 1,
		'sets'       => 1,
		'games'      => 1,
	);
	/**
	 * Number of teams per page in list
	 *
	 * @var int
	 */
	public int $num_matches_per_page = 10;

	/**
	 * Event offsets indexed by ID
	 *
	 * @var array
	 */
	public array $event_index = array();
	/**
	 * Championship flag
	 *
	 * @var boolean
	 */
	public bool $is_championship = false;

	/**
	 * Type
	 *
	 * @var string
	 */
	public string $type = '';

	/**
	 * Current season
	 *
	 * @var array
	 */
	public array $current_season = array();

	/**
	 * Number of match days
	 *
	 * @var int
	 */
	public int $num_match_days = 0;

	/**
	 * Number of events
	 *
	 * @var int
	 */
	public int $num_events = 0;

	/**
	 * Events
	 *
	 * @var array
	 */
	public array $events = array();
	/**
	 * Settings
	 *
	 * @var array
	 */
	public array $settings;
	/**
	 * Entry type
	 *
	 * @var string
	 */
	public string $entry_type;
	/**
	 * Cup flag
	 *
	 * @var boolean
	 */
	public bool $is_cup = false;
	/**
	 * Tournament flag
	 *
	 * @var boolean
	 */
	public bool $is_tournament = false;
	/**
	 * League flag
	 *
	 * @var boolean
	 */
	public bool $is_league = false;
	/**
	 * Team entry flag
	 *
	 * @var boolean
	 */
	public bool $is_team_entry = false;
	/**
	 * Teams
	 *
	 * @var array
	 */
	public array $teams = array();
	/**
	 * Current phase string
	 *
	 * @var string|null
	 */
	public ?string $current_phase = null;
	/**
	 * Player entry flag
	 *
	 * @var boolean
	 */
	public bool $is_player_entry = false;
	/**
	 * Players array
	 *
	 * @var array
	 */
	public array $players = array();
	/**
	 * Clubs array
	 *
	 * @var array
	 */
	public array $clubs = array();
	/**
	 * Date Open
	 *
	 * @var string|null
	 */
	public mixed $date_open;
	/**
	 * Date Start
	 *
	 * @var string|null
	 */
	public mixed $date_start;
	/**
	 * Date End
	 *
	 * @var string|null
	 */
	public mixed $date_end;
	/**
	 * Venue
	 *
	 * @var string|null
	 */
	public mixed $venue;
	/**
	 * Is complete
	 *
	 * @var boolean
	 */
	public bool $is_complete = false;
	/**
	 * Is started
	 *
	 * @var boolean
	 */
	public bool $is_started = false;
	/**
	 * Is closed
	 *
	 * @var boolean
	 */
	public bool $is_closed = false;
	/**
	 * Is pending
	 *
	 * @var boolean
	 */
	public bool $is_pending = false;
	/**
	 * Is open
	 *
	 * @var boolean
	 */
	public bool $is_open = false;
	/**
	 * Competition code
	 *
	 * @var string|null
	 */
	public ?string $competition_code;
	/**
	 * Is competition active
	 *
	 * @var boolean
	 */
	public bool $is_active = false;
	/**
	 * Grade
	 *
	 * @var string|null
	 */
	public ?string $grade;
	/**
	 * Max teams per league
	 *
	 * @var int|null
	 */
	public ?int $max_teams;
	/**
	 * Max teams per club in a league
	 *
	 * @var int|null
	 */
	public ?int $teams_per_club;
	/**
	 * Number of teams promoted and relegated
	 *
	 * @var int|null
	 */
	public ?int $teams_prom_relg;
	/**
	 * Lowest team to be promoted
	 *
	 * @var int|null
	 */
	public ?int $lowest_promotion;
	/**
	 * Default round length
	 *
	 * @var int|null
	 */
	public ?int $round_length;
	/**
	 * Are there match day restrictions
	 *
	 * @var boolean
	 */
	public bool $match_day_restriction;
	/**
	 * Are weekend matches allowed
	 *
	 * @var boolean
	 */
	public bool $match_day_weekends;
	/**
	 * Are match dates fixed
	 *
	 * @var boolean
	 */
	public bool $fixed_match_dates;
	/**
	 * Are fixtures home and away
	 *
	 * @var boolean
	 */
	public bool $home_away;
	/**
	 * Number of courts available by club
	 *
	 * @var array
	 */
	public array $num_courts_available;
	/**
	 * Scoring default format
	 *
	 * @var string|null
	 */
	public ?string $scoring;
	/**
	 * Number of sets default
	 *
	 * @var int
	 */
	public int $num_sets;
	/**
	 * Number of rubbers default
	 *
	 * @var int|null
	 */
	public ?int $num_rubbers;
	/**
	 * Age group
	 *
	 * @var string|null
	 */
	public ?string $age_group;
	/**
	 * Reverse rubbers
	 *
	 * @var boolean|null
	 */
	public ?bool $reverse_rubbers;
	/**
	 * Home away difference
	 *
	 * @var int|null
	 */
	public ?int $home_away_diff;
	/**
	 * Filler weeks
	 *
	 * @var int|null
	 */
	public ?int $filler_weeks;
	/**
	 * Match days allowed array
	 *
	 * @var array
	 */
	public array $match_days_allowed;
	/**
	 * Earliest weekday start time
	 *
	 * @var string|null
	 */
	public string|null $min_start_time_weekday;
	/**
	 * Latest weekday start time
	 *
	 * @var string|null
	 */
	public ?string $max_start_time_weekday;
	/**
	 * Earliest weekend start time
	 *
	 * @var string|null
	 */
	public ?string $min_start_time_weekend;
	/**
	 * Latest weekend start time
	 *
	 * @var string|null
	 */
	public ?string $max_start_time_weekend;
	/**
	 * Rules
	 *
	 * @var array
	 */
	public array $rules;
	/**
	 * Entries
	 *
	 * @var int
	 */
	public int $entries;
	/**
	 * Number of players
	 *
	 * @var int
	 */
	public int $num_players;
	/**
	 * Winners
	 *
	 * @var array
	 */
	public array $winners;
	/**
	 * Season
	 *
	 * @var int
	 */
	public int $season;
	/**
	 * Number of entries
	 *
	 * @var int
	 */
	public int $num_entries;
	/**
	 * Primary league
	 *
	 * @var int|null
	 */
	public ?int $primary_league;
	/**
	 * Offset
	 *
	 * @var int
	 */
	public int $offset;
	/**
	 * Competition type
	 *
	 * @var string
	 */
	public string $competition_type;
	/**
	 * Player
	 *
	 * @var object
	 */
	public object $player;
	/**
	 * Entry link
	 *
	 * @var string
	 */
	public string $entry_link;
	/**
	 * Config
	 *
	 * @var array
	 */
	public array $config;
	/**
	 * Match query arguments
	 *
	 * @var array
	 */
	private array $match_query_args = array(
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
	 * Retrieve competition instance
	 *
	 * @param int|string $competition_id competition id.
	 * @param string|null $search_term search.
	 */
	public static function get_instance( int|string $competition_id, ?string $search_term = 'id' ) {
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
				"SELECT `name`, `id`, `type`, `settings`, `seasons`, `age_group` FROM $wpdb->racketmanager_competitions WHERE " . $search . ' LIMIT 1'
			);
			if ( ! $competition ) {
				return false;
			}
			$competition->settings              = (array) maybe_unserialize( $competition->settings );
			$competition->settings['type']      = $competition->type;
			$competition->settings['age_group'] = $competition->age_group;
			$competition                        = (object) ( $competition->settings + (array) $competition );
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
	public function __construct( object $competition ) {
		if ( ! isset( $competition->id ) ) {
			$this->add( $competition );
		}
		if ( isset( $competition->settings ) ) {
			$competition->settings      = (array) maybe_unserialize( $competition->settings );
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
		if ( empty( $this->seasons ) ) {
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
			if ( ! empty( $this->current_season['date_open'] ) ) {
				$this->date_open = $this->current_season['date_open'];
			}
			if ( ! empty( $this->current_season['date_start'] ) ) {
				$this->date_start = $this->current_season['date_start'];
			} else {
				$this->date_start = $this->current_season['match_dates'][0] ?? null;
			}
			if ( ! empty( $this->current_season['date_end'] ) ) {
				$this->date_end = $this->current_season['date_end'];
			} else {
				$last_round = isset( $this->current_season['match_dates'] ) ? end( $this->current_season['match_dates'] ) : null;
				if ( $last_round ) {
					$this->date_end = Racketmanager_Util::amend_date( $last_round, 14 );
				}
			}
			if ( ! empty( $this->current_season['venue_name'] ) ) {
				$this->venue = $this->current_season['venue_name'];
			}
			if ( isset( $this->current_season['date_closing'] ) && $this->current_season['date_closing'] < gmdate( 'Y-m-d' ) ) {
				$this->is_active = true;
			} else {
				$this->is_active = false;
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
				$finals              = array();
				$max_rounds          = 4;
				$r                   = $max_rounds;
				for ( $round = 1; $round <= $max_rounds; ++$round ) {
					$num_teams      = pow( 2, $round );
					$num_matches    = $num_teams / 2;
					$key            = Racketmanager_Util::get_final_key( $num_teams );
					$name           = Racketmanager_Util::get_final_name( $key );
					$finals[ $key ] = array(
						'key'         => $key,
						'name'        => $name,
						'num_matches' => $num_matches,
						'num_teams'   => $num_teams,
						'round'       => $r,
					);
					--$r;
				}
				$this->finals = $finals;
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
	private function add( object $competition ): void {
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
		if ( ! empty( $mode ) && ! empty( $entry_type ) ) {
			$settings = array(
				'mode'       => $mode,
				'entry_type' => $entry_type,
			);

			$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"INSERT INTO $wpdb->racketmanager_competitions (`name`, `type`, `settings`, `age_group` ) VALUES (%s, %s, %s, %s)",
					$competition->name,
					$competition->type,
					maybe_serialize( $settings ),
					$competition->age_group,
				)
			);
			$competition->id = $wpdb->insert_id;
		}
	}

	/**
	 * Delete Competition
	 */
	public function delete(): void {
		global $wpdb;

		foreach ( $this->get_events() as $event ) {
			$event = get_event( $event->id );
			$event->delete();
		}
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE FROM $wpdb->racketmanager_competitions_seasons WHERE `competition_id` = %d",
				$this->id
			)
		);
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE FROM $wpdb->racketmanager_competitions WHERE `id` = %d",
				$this->id
			)
		);
	}

	/**
	 * Set name
	 *
	 * @param string $name competition name.
	 */
	public function set_name( string $name ): void {
		global $wpdb;

		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"UPDATE $wpdb->racketmanager_competitions SET `name` = %s WHERE `id` =%d",
				$name,
				$this->id
			)
		);
		$this->name = $name;
		wp_cache_set( $this->id, $this, 'competitions' );
	}

	/**
	 * Update settings
	 *
	 * @param array $settings settings array.
	 */
	public function set_settings( array $settings ): void {
		global $wpdb, $racketmanager;
		foreach ( Racketmanager_Util::get_standings_display_options() as $key => $value ) {
			$settings['standings'][ $key ] = isset( $settings['standings'][ $key ] ) ? 1 : 0;
		}
		$type = $settings['type'];

		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"UPDATE $wpdb->racketmanager_competitions SET `settings` = %s, `type` = %s WHERE `id` = %d",
				maybe_serialize( $settings ),
				$type,
				$this->id
			)
		);
		$this->settings = $settings;
		wp_cache_set( $this->id, $this, 'competitions' );
	}

	/**
	 * Set current season
	 *
	 * @param string $season season.
	 * @param boolean $force_overwrite force overwrite.
	 */
	public function set_season( string $season = '', bool $force_overwrite = false ): void {
		global $wp;
		if ( ! empty( $season ) && true === $force_overwrite ) {
			$data = $this->seasons[ $season ];
		} elseif ( ! empty( $_GET['season'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
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
				$date_active = empty( $season['date_closing'] ) ? null : Racketmanager_Util::amend_date( $season['date_closing'], 7 );
				if ( ! empty( $date_active ) && $date_active <= $today ) {
					$data = $season;
					break;
				}
			}
		}
		if ( empty( $data ) ) {
			$data = end( $this->seasons );
		}
		$count_match_dates = isset( $data['match_dates'] ) && is_array( $data['match_dates'] ) ? count( $data['match_dates'] ) : 0;
		$this->is_complete = false;
		if ( empty( $data['date_end'] ) && $count_match_dates >= 2 ) {
			$data['date_end']               = end( $data['match_dates'] );
			$this->seasons[ $data['name'] ] = $data;
		}
		if ( empty( $data['date_start'] ) && $count_match_dates >= 2 ) {
			$data['date_start']             = $data['match_dates'][0];
			$this->seasons[ $data['name'] ] = $data;
		}
		if ( ! empty( $data['date_end'] ) && $today > $data['date_end'] ) {
			$this->current_phase = 'end';
			$this->is_complete   = true;
		} elseif ( ! empty( $data['date_start'] ) && $today >= $data['date_start'] ) {
			$this->current_phase = 'start';
			$this->is_started    = true;
		} elseif ( ! empty( $data['date_closing'] ) && $today > $data['date_closing'] ) {
			$this->current_phase = 'close';
			$this->is_closed     = true;
		} elseif ( ! empty( $data['date_open'] ) ) {
			if ( $today >= $data['date_open'] ) {
				$this->current_phase = 'open';
				$this->is_open       = true;
			} else {
				$this->current_phase = 'pending';
				$this->is_pending    = true;
			}
		} else {
			$this->current_phase = 'complete';
			$this->is_complete   = true;
		}
		$data['venue_name'] = null;
		if ( ! empty( $data['venue'] ) ) {
			$venue_club = get_club( $data['venue'] );
			if ( $venue_club ) {
				$data['venue_name'] = $venue_club->shortcode;
			}
		}
		$this->current_season = $data;
		$this->num_match_days = $data['num_match_days'];
	}

	/**
	 * Get current season name
	 *
	 * @return string
	 */
	public function get_season(): string {
		return stripslashes( $this->current_season['name'] );
	}
	/**
	 * Gets number of events
	 *
	 * @param boolean $total should total be stored.
	 */
	public function set_num_events( bool $total = false ): void {
		global $wpdb;

		if ( true === $total ) {
			$this->num_events = $wpdb->get_var( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT COUNT(ID) FROM $wpdb->racketmanager_events WHERE `competition_id` = %d",
					$this->id
				)
			);
		}
	}

	/**
	 * Get events from database
	 *
	 * @param array $args search arguments.
	 *
	 * @return array
	 */
	public function get_events( array $args = array() ): array {
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
		$search_terms[] = $wpdb->prepare( '`competition_id` = %d', $this->id );

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
			"SELECT `name`, `id`, `settings`, `competition_id` FROM $wpdb->racketmanager_events $search ORDER BY $orderby LIMIT %d, %d",
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
	 * Reload settings from database
	 */
	public function reload_settings(): void {
		global $wpdb;

		wp_cache_delete( $this->id, 'competitions' );
		$result = $wpdb->get_row( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT `settings` FROM $wpdb->racketmanager_competitions WHERE `id` = %d",
				$this->id
			)
		);
		foreach ( maybe_unserialize( $result->settings ) as $key => $value ) {
			$this->$key = $value;
		}
	}
	/**
	 * Get teams from database
	 *
	 * @param array $args search arguments.
	 * @return array|int
	 */
	public function get_teams( array $args = array() ): array|int {
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
			$search_terms[] = $wpdb->prepare( 't2.`club_id` = %d', intval( $club ) );
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
			$sql = 'SELECT `l`.`title` AS `league_title`, l.`id` AS `league_id`, t2.`id` AS `team_id`, t1.`id` AS `table_id`, `t2`.`title` as `name`,`t1`.`rank`, l.`id`, t1.`status`, t1.`profile`, t1.`group`, t2.`roster`, t2.`club_id`, t2.`status` AS `team_type`, e.`name` AS `event_name`';
		}
		$sql .= " FROM $wpdb->racketmanager_events e, $wpdb->racketmanager l, $wpdb->racketmanager_teams t2, $wpdb->racketmanager_table t1 WHERE e.`id` = l.`event_id` AND t1.`team_id` = t2.`id` AND l.`id` = t1.`league_id` " . $search;

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
			$team->roster       = maybe_unserialize( $team->roster );
			$team->club         = get_club( $team->club_id );
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
	 * @param array $args search arguments.
	 *
	 * @return array|int
	 */
	public function get_players( array $args = array() ): array|int {
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

		$competition_players = array();
		$players             = array();
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
			$competition_players = array_unique( $players );
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
				$search_terms[] .= "(( `home_team` in (SELECT `id` FROM $wpdb->racketmanager_teams WHERE `club_id` = %d) AND `player_team` = %s) OR (`away_team` in (SELECT `id` FROM $wpdb->racketmanager_teams WHERE `club_id` = %d) AND `player_team` = %s))";
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
			$sql .= " FROM $wpdb->racketmanager_rubber_players rp, $wpdb->racketmanager_rubbers r, $wpdb->racketmanager_matches m  WHERE rp.`rubber_id` = r.`id` AND r.`match_id` = m.`id` AND m.`league_id` IN (SELECT l.`id` FROM $wpdb->racketmanager l, $wpdb->racketmanager_events e WHERE l.`event_id` = e.`id` AND e.`competition_id` = %d)" . $search;
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
	 * @param array $args search arguments.
	 *
	 * @return array|int
	 */
	public function get_clubs( array $args = array() ): array|int {
		global $wpdb;

		$defaults = array(
			'offset'  => 0,
			'limit'   => 99999999,
			'season'  => false,
			'orderby' => false,
			'status'  => false,
			'count'   => false,
			'name'    => false,
			'club_id' => false,
		);
		$args     = array_merge( $defaults, $args );
		$offset   = $args['offset'];
		$limit    = $args['limit'];
		$season   = $args['season'];
		$status   = $args['status'];
		$count    = $args['count'];
		$name     = $args['name'];
		$club_id  = $args['club_id'];

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
		if ( $club_id ) {
			$search_terms[] = $wpdb->prepare( 'c.`id` = %d', intval( $club_id ) );
		}

		$search = '';
		if ( ! empty( $search_terms ) ) {
			$search  = ' AND ';
			$search .= implode( ' AND ', $search_terms );
		}

		if ( $count ) {
			$sql = 'SELECT COUNT(*)';
		} else {
			$sql = 'SELECT t2.`club_id`, count(t2.`id`) as `team_count`';
		}
		$sql .= " FROM $wpdb->racketmanager_events e,$wpdb->racketmanager l, $wpdb->racketmanager_teams t2, $wpdb->racketmanager_table t1, $wpdb->racketmanager_clubs c WHERE e.`id` = l.`event_id` AND t1.`team_id` = t2.`id` AND l.`id` = t1.`league_id` AND t2.`club_id` = c.`id`" . $search;

		if ( $count ) {
			return $wpdb->get_var(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql
			); // db call ok.
		} else {
			$sql .= ' GROUP BY t2.`club_id`';
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
			$competition_club               = get_club( $competition_club->club_id );
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
	 *
	 * @return array $matches
	 */
	public function get_matches( array $match_args ): array {
		global $wpdb;

		$match_args           = array_merge( $this->match_query_args, $match_args );
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
		$sql_from             = " FROM $wpdb->racketmanager_matches AS m, $wpdb->racketmanager AS l, $wpdb->racketmanager_events AS e, $wpdb->racketmanager_rubbers AS r";
		if ( $count ) {
			$sql_fields = 'SELECT COUNT(*)';
			$sql        = " WHERE 1 = 1 AND l.`event_id` = e.`id` AND e.`competition_id` = $this->id";
		} else {
			$sql_fields = "SELECT DISTINCT m.`final` AS final_round, m.`group`, `home_team`, `away_team`, DATE_FORMAT(m.`date`, '%Y-%m-%d %H:%i') AS date, DATE_FORMAT(m.`date`, '%e') AS day, DATE_FORMAT(m.`date`, '%c') AS month, DATE_FORMAT(m.`date`, '%Y') AS year, DATE_FORMAT(m.`date`, '%H') AS `hour`, DATE_FORMAT(m.`date`, '%i') AS `minutes`, `match_day`, `location`, l.`id` AS `league_id`, m.`home_points`, m.`away_points`, m.`winner_id`, m.`loser_id`, m.`post_id`, `season`, m.`id` AS `id`, m.`custom`, `confirmed`, `home_captain`, `away_captain`, `comments`, `updated`, m.`leg`";
			$sql        = " WHERE m.`league_id` = l.`id` AND m.`id` = r.`match_id` AND l.`event_id` = e.`id` AND e.`competition_id` = $this->id";
		}

		if ( $match_date ) {
			$sql .= " AND DATEDIFF('" . htmlspecialchars( wp_strip_all_tags( $match_date ) ) . "', `date`) = 0";
		}
		if ( $league_id ) {
			$sql .= " AND `league_id`  = '" . $league_id . "'";
		}
		if ( $league_name ) {
			$sql .= " AND `league_id` in (select `id` from $wpdb->racketmanager WHERE `title` = '" . $league_name . "')";
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
			$sql_from .= ", $wpdb->racketmanager_rubber_players AS rp";
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
			$sql .= " AND (`home_team` IN (SELECT `id` FROM $wpdb->racketmanager_teams WHERE `club_id` = " . $club . ") OR `away_team` IN (SELECT `id` FROM $wpdb->racketmanager_teams WHERE `club_id` = " . $club . '))';
		}
		if ( $home_club ) {
			$sql .= " AND `home_team` IN (SELECT `id` FROM $wpdb->racketmanager_teams WHERE `club_id` = " . $home_club . ')';
		}
		if ( ! empty( $home_team ) ) {
			$sql .= ' AND `home_team` = ' . $home_team . ' ';
		}
		if ( ! empty( $away_team ) ) {
			$sql .= ' AND `away_team` = ' . $away_team . ' ';
		}
		if ( ! empty( $team_name ) ) {
			$sql .= " AND (`home_team` IN (SELECT `id` FROM $wpdb->racketmanager_teams WHERE `title` LIKE '%" . $team_name . "%') OR `away_team` IN (SELECT `id` FROM $wpdb->racketmanager_teams WHERE `title` LIKE '%" . $team_name . "%'))";
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
	 * Get winners function
	 *
	 * @param boolean $group_by group by flag.
	 *
	 * @return false|array
	 */
	public function get_winners( bool $group_by = false ): false|array {
		global $wpdb;

		if ( $this->is_league ) {
			$winners = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT l.`title` ,wt.`title` AS `winner` ,e.`type`, e.`name` AS `event_name`, e.`id` AS `event_id` FROM $wpdb->racketmanager_table t, $wpdb->racketmanager l, $wpdb->racketmanager_teams wt, $wpdb->racketmanager_events e WHERE t.`league_id` = l.`id` AND l.`event_id` = e.`id` AND e.`competition_id` = %d AND t.`season` = %d AND t.rank = 1 AND t.team_id = wt.id order by e.`name`, l.`title`",
					$this->id,
					$this->current_season['name']
				)
			);
		} else {
			$winners = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT l.`title` ,wt.`title` AS `winner` ,lt.`title` AS `loser`, m.`id`, m.`home_team`, m.`away_team`, m.`winner_id` AS `winner_id`, m.`loser_id` AS `loser_id`, e.`type`, e.`name` AS `event_name`, e.`id` AS `event_id`, wt.`status` AS `team_type` FROM $wpdb->racketmanager_matches m, $wpdb->racketmanager l, $wpdb->racketmanager_teams wt, $wpdb->racketmanager_teams lt, $wpdb->racketmanager_events e WHERE `league_id` = l.`id` AND l.`event_id` = e.`id` AND e.`competition_id` = %d AND m.`final` = 'FINAL' AND m.`season` = %d AND m.`winner_id` = wt.`id` AND m.`loser_id` = lt.`id` order by e.`name`, l.`title`",
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
	public function update_seasons( array $seasons ): bool {
		global $wpdb;
		if ( $this->seasons !== $seasons ) {
			$this->seasons = $seasons;
			$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"UPDATE $wpdb->racketmanager_competitions SET `seasons` = %s WHERE `id` = %d",
					maybe_serialize( $seasons ),
					$this->id
				)
			);
			wp_cache_set( $this->id, $this, 'competitions' );
			return true;
		} else {
			return false;
		}
	}
	/**
	 * Save plan
	 *
	 * @param int $season season.
	 * @param array $courts number of courts available.
	 * @param array $start_times start times of matches.
	 * @param array $matches matches.
	 * @param array $match_times match times.
	 *
	 * @return boolean updates performed
	 */
	public function save_plan( int $season, array $courts, array $start_times, array $matches, array $match_times ): bool {
		global $racketmanager;
		$seasons     = $this->seasons;
		$season_dtls = $this->seasons[$season] ?? null;
		if ( $season_dtls ) {
			$order_of_play = array();
			$num_courts    = count( $courts );
			for ( $i = 0; $i < $num_courts; $i++ ) {
				$order_of_play[ $i ]['court']      = $courts[ $i ];
				$order_of_play[ $i ]['start_time'] = $start_times[ $i ];
				$order_of_play[ $i ]['matches']    = $matches[ $i ];
				$num_matches                    = count( $matches[ $i ] );
				for ( $m = 0; $m < $num_matches; $m++ ) {
					$match_id = trim( $matches[ $i ][ $m ] );
					if ( ! empty( $match_id ) ) {
						$time  = strtotime( $start_times[ $i ] ) + $match_times[ $i ][ $m ];
						$match = get_match( $match_id );
						if ( $match ) {
							$month    = str_pad( $match->month, 2, '0', STR_PAD_LEFT );
							$day      = str_pad( $match->day, 2, '0', STR_PAD_LEFT );
							$date     = $match->year . '-' . $month . '-' . $day . ' ' . gmdate( 'H:i', $time );
							$location = $courts[ $i ];
							if ( $date !== $match->date || $location !== $match->location ) {
								$match->set_match_date_in_db( $date );
								$match->set_location( $location );
							}
						}
					}
				}
			}
			$curr_order_of_play = $season_dtls['orderofplay'] ?? null;
			if ( $order_of_play !== $curr_order_of_play ) {
				$season_dtls['orderofplay'] = $order_of_play;
				$seasons[ $season ]         = $season_dtls;
				$this->update_seasons( $seasons );
				$racketmanager->set_message( __( 'Cup plan updated', 'racketmanager' ) );
			} else {
				$racketmanager->set_message( __( 'No updates', 'racketmanager' ) );
			}
		}
		return true;
	}
	/**
	 * Update plan config
	 *
	 * @param int $season season.
	 * @param string|null $start_time start time.
	 * @param int|null $num_courts number of courts.
	 * @param string|null $time_increment time increment for matches.
	 *
	 * @return boolean updates performed
	 */
	public function update_plan( int $season, ?string $start_time, ?int $num_courts, ?string $time_increment ): bool {
		global $racketmanager;
		$update      = false;
		$valid       = true;
		$err_msg     = array();
		$seasons     = $this->seasons;
		$season_dtls = $this->seasons[$season] ?? null;
		if ( $season_dtls ) {
			$curr_start_time     = $season_dtls['starttime'] ?? null;
			$curr_num_courts     = $season_dtls['num_courts'] ?? null;
			$curr_time_increment = $season_dtls['time_increment'] ?? null;
			if ( empty( $start_time ) ) {
				$valid     = false;
				$err_msg[] = __( 'Start time not set', 'racketmanager' );
			}
			if ( empty( $num_courts ) ) {
				$valid     = false;
				$err_msg[] = __( 'Number of courts not set', 'racketmanager' );
			}
			if ( empty( $time_increment ) ) {
				$valid     = false;
				$err_msg[] = __( 'Time increment not set', 'racketmanager' );
			}
			if ( $valid ) {
				if ( $start_time !== $curr_start_time || $num_courts !== $curr_num_courts || $time_increment !== $curr_time_increment ) {
					$season_dtls['starttime']      = $start_time;
					$season_dtls['num_courts']     = $num_courts;
					$season_dtls['time_increment'] = $time_increment;
					$seasons[ $season ]            = $season_dtls;
					$this->update_seasons( $seasons );
					$racketmanager->set_message( __( 'Cup plan updated', 'racketmanager' ) );
					$update = true;
				} else {
					$racketmanager->set_message( __( 'No updates', 'racketmanager' ), 'warning' );
				}
			} else {
				$racketmanager->set_message( implode( '<br>', $err_msg ), true );
			}
		} else {
			$racketmanager->set_message( __( 'Season not found', 'racketmanager' ), true );
		}
		return $update;
	}
	/**
	 * Reset plan config
	 *
	 * @param int $season season.
	 * @param array $matches matches.
	 *
	 * @return boolean updates performed
	 */
	public function reset_plan( int $season, array $matches ): bool {
		global $racketmanager;
		$seasons     = $this->seasons;
		$season_dtls = $this->seasons[$season] ?? null;
		$updates     = false;
		if ( $season_dtls ) {
			if ( $matches ) {
				foreach ( $matches as $match_id ) {
					$match = get_match( $match_id );
					if ( $match ) {
						$month    = str_pad( $match->month, 2, '0', STR_PAD_LEFT );
						$day      = str_pad( $match->day, 2, '0', STR_PAD_LEFT );
						$date     = $match->year . '-' . $month . '-' . $day . ' 00:00';
						$location = '';
						if ( $date !== $match->date || $location !== $match->location ) {
							$match->set_match_date_in_db( $date );
							$match->set_location( $location );
							$updates = true;
						}
					}
				}
			}
			if ( $updates ) {
				$season_dtls['orderofplay'] = array();
				$seasons[ $season ]         = $season_dtls;
				$this->update_seasons( $seasons );
				$racketmanager->set_message( __( 'Plan reset', 'racketmanager' ) );
			} else {
				$racketmanager->set_message( __( 'No updates', 'racketmanager' ), 'warning' );
			}
		}
		return $updates;
	}
	/**
	 * Delete season function
	 *
	 * @param int $season season name.
	 *
	 * @return boolean
	 */
	public function delete_season( int $season ): bool {
		if ( isset( $this->seasons[ $season ] ) ) {
			$seasons = $this->seasons;
			foreach ( $this->get_events() as $event ) {
				$event->delete_season( $season );
			}
			unset( $seasons[ $season ] );
			$this->update_seasons( $seasons );
			return true;
		} else {
			return false;
		}
	}
	/**
	 * Add season
	 *
	 * @param array $season season data.
	 */
	public function add_season( array $season ): void {
		global $racketmanager;
		$seasons                 = $this->seasons;
		$season_name             = $season['name'];
		$seasons[ $season_name ] = $season;
		$this->update_seasons( $seasons );
		$racketmanager->set_message( __( 'Season added', 'racketmanager' ) );
	}
	/**
	 * Update season
	 *
	 * @param array $season season data.
	 */
	public function update_season( array $season ): void {
		global $racketmanager;
		$seasons                 = $this->seasons;
		$season_name             = $season['name'];
		$seasons[ $season_name ] = $season;
		ksort( $seasons );
		$this->update_seasons( $seasons );
		$racketmanager->set_message( __( 'Season updated', 'racketmanager' ) );
	}
	/**
	 * Set configuration function
	 *
	 * @param object $config config object.
	 *
	 * @return boolean update indicator.
	 */
	public function set_config( object $config ): bool {
		global $racketmanager;
		$updates = false;
		if ( empty( $config->name ) ) {
			$racketmanager->error_messages[] = __( 'Name must be set', 'racketmanager' );
			$racketmanager->error_fields[]   = 'name';
		}
		if ( empty( $config->sport ) ) {
			$racketmanager->error_messages[] = __( 'Sport must be set', 'racketmanager' );
			$racketmanager->error_fields[]   = 'sport';
		}
		if ( empty( $config->type ) ) {
			$racketmanager->error_messages[] = __( 'Type must be set', 'racketmanager' );
			$racketmanager->error_fields[]   = 'type';
		}
		if ( empty( $config->entry_type ) ) {
			$racketmanager->error_messages[] = __( 'Entry type must be set', 'racketmanager' );
			$racketmanager->error_fields[]   = 'entry_type';
		}
		if ( empty( $config->age_group ) ) {
			$racketmanager->error_messages[] = __( 'Age group must be set', 'racketmanager' );
			$racketmanager->error_fields[]   = 'age_group';
		}
		if ( empty( $config->grade ) ) {
			$racketmanager->error_messages[] = __( 'Grade must be set', 'racketmanager' );
			$racketmanager->error_fields[]   = 'grade';
		}
		if ( 'league' === $config->type ) {
			if ( empty( $config->max_teams ) ) {
				$racketmanager->error_messages[] = __( 'Maximum number of teams must be set', 'racketmanager' );
				$racketmanager->error_fields[]   = 'max_teams';
			}
			if ( empty( $config->teams_per_club ) ) {
				$racketmanager->error_messages[] = __( 'Number of teams per club must be set', 'racketmanager' );
				$racketmanager->error_fields[]   = 'teams_per_club';
			}
			if ( empty( $config->teams_prom_relg ) ) {
				$racketmanager->error_messages[] = __( 'Number of promoted/relegated teams must be set', 'racketmanager' );
				$racketmanager->error_fields[]   = 'teams_prom_relg';
			}
			if ( $config->teams_prom_relg > $config->teams_per_club ) {
				$racketmanager->error_messages[] = __( 'Number of promoted/relegated teams must be at most number of teams per club', 'racketmanager' );
				$racketmanager->error_fields[]   = 'teams_prom_relg';
			}
			if ( empty( $config->lowest_promotion ) ) {
				$racketmanager->error_messages[] = __( 'Lowest promotion position must be set', 'racketmanager' );
				$racketmanager->error_fields[]   = 'lowest_promotion';
			}
		} elseif ( 'tournament' === $config->type ) {
			if ( empty( $config->num_entries ) ) {
				$racketmanager->error_messages[] = __( 'Maximum number of entries must be set', 'racketmanager' );
				$racketmanager->error_fields[]   = 'num_entries';
			}
		}
		if ( empty( $config->team_ranking ) ) {
			$racketmanager->error_messages[] = __( 'Ranking type must be set', 'racketmanager' );
			$racketmanager->error_fields[]   = 'team_ranking';
		}
		if ( empty( $config->point_rule ) ) {
			$racketmanager->error_messages[] = __( 'Point rule must be set', 'racketmanager' );
			$racketmanager->error_fields[]   = 'point_rule';
		}
		if ( empty( $config->scoring ) ) {
			$racketmanager->error_messages[] = __( 'Scoring method must be set', 'racketmanager' );
			$racketmanager->error_fields[]   = 'scoring';
		}
		if ( empty( $config->num_sets ) ) {
			$racketmanager->error_messages[] = __( 'Number of sets must be set', 'racketmanager' );
			$racketmanager->error_fields[]   = 'num_sets';
		}
		if ( $this->is_team_entry ) {
			if ( empty( $config->num_rubbers ) ) {
				$racketmanager->error_messages[] = __( 'Number of rubbers must be set', 'racketmanager' );
				$racketmanager->error_fields[]   = 'num_rubbers';
			}
		}
		if ( is_null( $config->fixed_match_dates ) ) {
			$racketmanager->error_messages[] = __( 'Match date option must be set', 'racketmanager' );
			$racketmanager->error_fields[]   = 'fixed_match_dates';
		}
		if ( is_null( $config->home_away ) ) {
			$racketmanager->error_messages[] = __( 'Fixture types must be set', 'racketmanager' );
			$racketmanager->error_fields[]   = 'home_away';
		}
		if ( empty( $config->round_length ) ) {
			$racketmanager->error_messages[] = __( 'Round length must be set', 'racketmanager' );
			$racketmanager->error_fields[]   = 'round_length';
		}
		if ( 'tournament' !== $config->type ) {
			if ( ! empty( $config->match_day_restriction ) && empty( $config->match_days_allowed ) ) {
				$racketmanager->error_messages[] = __( 'Match days allowed must be set', 'racketmanager' );
				$racketmanager->error_fields[]   = 'match_day_restriction';
			}
			if ( empty( $config->default_match_start_time ) ) {
				$racketmanager->error_messages[] = __( 'Default match start time must be set', 'racketmanager' );
				$racketmanager->error_fields[]   = 'default_match_start_time';
			}
			$validate_weekday_times = false;
			$validate_weekend_times = false;
			if ( empty( $config->match_day_restriction ) ) {
				$validate_weekday_times = true;
				$validate_weekend_times = true;
			} elseif ( ! empty( $config->match_days_allowed ) ) {
				foreach ( $config->match_days_allowed as $day_allowed => $value ) {
					if ( $day_allowed <= 5 ) {
						$validate_weekday_times = true;
					} else {
						$validate_weekend_times = true;
					}
				}
			}
			if ( $validate_weekday_times ) {
				if ( empty( $config->min_start_time_weekday ) ) {
					$racketmanager->error_messages[] = __( 'Minimum weekday start time must be set', 'racketmanager' );
					$racketmanager->error_fields[]   = 'min_start_time_weekday';
				}
				if ( empty( $config->max_start_time_weekday ) ) {
					$racketmanager->error_messages[] = __( 'Maximum weekday start time must be set', 'racketmanager' );
					$racketmanager->error_fields[]   = 'max_start_time_weekday';
				} elseif ( ! empty( $config->min_start_time_weekday ) ) {
					if ( $config->max_start_time_weekday < $config->min_start_time_weekday ) {
						$racketmanager->error_messages[] = __( 'Maximum weekday start time must be greater than minimum', 'racketmanager' );
						$racketmanager->error_fields[]   = 'max_start_time_weekday';
					}
				}
			}
			if ( $validate_weekend_times ) {
				if ( empty( $config->min_start_time_weekend ) ) {
					$racketmanager->error_messages[] = __( 'Minimum weekend start time must be set', 'racketmanager' );
					$racketmanager->error_fields[]   = 'min_start_time_weekend';
				}
				if ( empty( $config->max_start_time_weekend ) ) {
					$racketmanager->error_messages[] = __( 'Maximum weekend start time must be set', 'racketmanager' );
					$racketmanager->error_fields[]   = 'max_start_time_weekend';
				} elseif ( ! empty( $config->min_start_time_weekend ) ) {
					if ( $config->max_start_time_weekend < $config->min_start_time_weekend ) {
						$racketmanager->error_messages[] = __( 'Maximum weekend start time must be greater than minimum', 'racketmanager' );
						$racketmanager->error_fields[]   = 'max_start_time_weekend';
					}
				}
			}
		}
		if ( empty( $config->point_format ) ) {
			$racketmanager->error_messages[] = __( 'Point format must be set', 'racketmanager' );
			$racketmanager->error_fields[]   = 'point_format';
		}
		if ( empty( $config->point_format2 ) ) {
			$racketmanager->error_messages[] = __( 'Secondary point format must be set', 'racketmanager' );
			$racketmanager->error_fields[]   = 'point_format2';
		}
		if ( empty( $config->num_matches_per_page ) ) {
			$racketmanager->error_messages[] = __( 'Number of matches per page must be set', 'racketmanager' );
			$racketmanager->error_fields[]   = 'num_matches_per_page';
		}
		if ( empty( $racketmanager->error_fields ) ) {
			$settings = new \stdClass();
			if ( empty( $this->sport ) || $this->sport !== $config->sport ) {
				$updates = true;
			}
			$settings->sport = $config->sport;
			if ( $this->type !== $config->type ) {
				$settings->type = $config->type;
				switch ( $config->type ) {
					case 'league':
						$config->mode = 'default';
						$updates      = true;
						break;
					case 'cup':
						$config->mode       = 'championship';
						$config->entry_type = 'team';
						$updates            = true;
						break;
					case 'tournament':
						$config->mode       = 'championship';
						$config->entry_type = 'player';
						$updates            = true;
						break;
					default:
						break;
				}
			}
			if ( empty( $this->entry_type ) || $this->entry_type !== $config->entry_type ) {
				$updates = true;
			}
			$settings->entry_type = $config->entry_type;
			if ( empty( $this->mode ) || $this->mode !== $config->mode ) {
				$updates = true;
			}
			$settings->mode = $config->mode;
			if ( empty( $this->competition_code ) || $this->competition_code !== $config->competition_code ) {
				$updates = true;
			}
			$settings->competition_code = $config->competition_code;
			if ( empty( $this->grade ) || $this->grade !== $config->grade ) {
				$updates = true;
			}
			$settings->grade = $config->grade;
			if ( empty( $this->age_group ) || $this->age_group !== $config->age_group ) {
				$updates = true;
			}
			$this->age_group = $config->age_group;
			if ( 'league' === $config->type ) {
				if ( empty( $this->max_teams ) || $this->max_teams !== $config->max_teams ) {
					$updates = true;
				}
				$settings->max_teams = $config->max_teams;
				if ( empty( $this->teams_per_club ) || $this->teams_per_club !== $config->teams_per_club ) {
					$updates = true;
				}
				$settings->teams_per_club = $config->teams_per_club;
				if ( empty( $this->teams_prom_relg ) || $this->teams_prom_relg !== $config->teams_prom_relg ) {
					$updates = true;
				}
				$settings->teams_prom_relg = $config->teams_prom_relg;
				if ( empty( $this->lowest_promotion ) || $this->lowest_promotion !== $config->lowest_promotion ) {
					$updates = true;
				}
				$settings->lowest_promotion = $config->lowest_promotion;
			} elseif ( 'tournament' === $config->type ) {
				if ( empty( $this->num_entries ) || $this->num_entries !== $config->num_entries ) {
					$updates = true;
				}
				$settings->num_entries = $config->num_entries;
			}
			if ( empty( $this->team_ranking ) || $this->team_ranking !== $config->team_ranking ) {
				$updates = true;
			}
			$settings->team_ranking = $config->team_ranking;
			if ( empty( $this->point_rule ) || $this->point_rule !== $config->point_rule ) {
				$updates = true;
			}
			$settings->point_rule = $config->point_rule;
			if ( empty( $this->scoring ) || $this->scoring !== $config->scoring ) {
				$updates = true;
			}
			$settings->scoring = $config->scoring;
			if ( empty( $this->num_sets ) || $this->num_sets !== $config->num_sets ) {
				$updates = true;
			}
			$settings->num_sets = $config->num_sets;
			if ( $this->is_team_entry ) {
				if ( empty( $this->num_rubbers ) || $this->num_rubbers !== $config->num_rubbers ) {
					$updates = true;
				}
				$settings->num_rubbers = $config->num_rubbers;
				if ( empty( $this->reverse_rubbers ) || $this->reverse_rubbers !== $config->reverse_rubbers ) {
					$updates = true;
				}
				$settings->reverse_rubbers = $config->reverse_rubbers;
			}
			if ( ! isset( $this->fixed_match_dates ) || $this->fixed_match_dates !== $config->fixed_match_dates ) {
				$updates = true;
			}
			$settings->fixed_match_dates = $config->fixed_match_dates;
			if ( ! isset( $this->home_away ) || $this->home_away !== $config->home_away ) {
				$updates = true;
			}
			$settings->home_away = $config->home_away;
			if ( empty( $this->round_length ) || $this->round_length !== $config->round_length ) {
				$updates = true;
			}
			$settings->round_length = $config->round_length;
			if ( 'league' === $config->type || 'cup' === $config->type ) {
				if ( empty( $this->home_away_diff ) || $this->home_away_diff !== $config->home_away_diff ) {
					$updates = true;
				}
				$settings->home_away_diff = $config->home_away_diff;
			}
			if ( 'league' === $config->type ) {
				if ( empty( $this->filler_weeks ) || $this->filler_weeks !== $config->filler_weeks ) {
					$updates = true;
				}
				$settings->filler_weeks = $config->filler_weeks;
			}
			if ( 'tournament' !== $config->type ) {
				$match_days = Racketmanager_Util::get_match_days();
				foreach ( $match_days as $match_day => $value ) {
					$config->match_days_allowed[ $match_day ] = isset( $config->match_days_allowed[ $match_day ] ) ? 1 : 0;
					if ( ! isset( $this->match_days_allowed[ $match_day ] ) || $this->match_days_allowed[ $match_day ] !== $config->match_days_allowed[ $match_day ] ) {
						$updates = true;
					}
				}
				$settings->match_days_allowed = $config->match_days_allowed;
				if ( ! isset( $this->match_day_restriction ) || $this->match_day_restriction !== $config->match_day_restriction ) {
					$updates = true;
				}
				$settings->match_day_restriction = $config->match_day_restriction;
				if ( ! isset( $this->match_day_weekends ) || $this->match_day_weekends !== $config->match_day_weekends ) {
					$updates = true;
				}
				$settings->match_day_weekends     = $config->match_day_weekends;
				$default_match_start_time         = explode( ':', $config->default_match_start_time );
				$default_match_start_time_hour    = $default_match_start_time[0];
				$default_match_start_time_minutes = $default_match_start_time[1];
				if ( empty( $this->default_match_start_time['hour'] ) || $this->default_match_start_time['hour'] !== $default_match_start_time_hour ) {
					$updates = true;
				}
				$settings->default_match_start_time['hour'] = $default_match_start_time_hour;
				if ( empty( $this->default_match_start_time['minutes'] ) || $this->default_match_start_time['minutes'] !== $default_match_start_time_minutes ) {
					$updates = true;
				}
				$settings->default_match_start_time['minutes'] = $default_match_start_time_minutes;
				if ( empty( $this->min_start_time_weekday ) || $this->min_start_time_weekday !== $config->min_start_time_weekday ) {
					$updates = true;
				}
				$settings->min_start_time_weekday = $config->min_start_time_weekday;
				if ( empty( $this->max_start_time_weekday ) || $this->max_start_time_weekday !== $config->max_start_time_weekday ) {
					$updates = true;
				}
				$settings->max_start_time_weekday = $config->max_start_time_weekday;
				if ( empty( $this->min_start_time_weekend ) || $this->min_start_time_weekend !== $config->min_start_time_weekend ) {
					$updates = true;
				}
				$settings->min_start_time_weekend = $config->min_start_time_weekend;
				if ( empty( $this->max_start_time_weekend ) || $this->max_start_time_weekend !== $config->max_start_time_weekend ) {
					$updates = true;
				}
				$settings->max_start_time_weekend = $config->max_start_time_weekend;
			}
			if ( empty( $this->point_format ) || $this->point_format !== $config->point_format ) {
				$updates = true;
			}
			$settings->point_format = $config->point_format;
			if ( empty( $this->point_format2 ) || $this->point_format2 !== $config->point_format2 ) {
				$updates = true;
			}
			$settings->point_format2 = $config->point_format2;
			if ( empty( $this->num_matches_per_page ) || $this->num_matches_per_page !== $config->num_matches_per_page ) {
				$updates = true;
			}
			$settings->num_matches_per_page = $config->num_matches_per_page;
			$standing_display_options       = Racketmanager_Util::get_standings_display_options();
			foreach ( $standing_display_options as $display_option => $value ) {
				$config->standings[ $display_option ] = isset( $config->standings[ $display_option ] ) ? 1 : 0;
				if ( $this->standings[ $display_option ] !== $config->standings[ $display_option ] ) {
					$updates = true;
				}
			}
			$settings->standings = $config->standings;
			$rules_options       = $racketmanager->get_options( 'checks' );
			foreach ( $rules_options as $rules_option => $value ) {
				$config->rules[ $rules_option ] = isset( $config->rules[ $rules_option ] ) ? 1 : 0;
				if ( ! isset( $this->rules[ $rules_option ] ) || $this->rules[ $rules_option ] !== $config->rules[ $rules_option ] ) {
					$updates = true;
				}
			}
			$settings->rules = $config->rules;
			if ( 'league' === $config->type ) {
				if ( empty( $this->num_courts_available ) || $this->num_courts_available !== $config->num_courts_available ) {
					$updates = true;
				}
				$settings->num_courts_available = $config->num_courts_available;
			}
			if ( $this->name !== $config->name || $updates ) {
				$this->name     = $config->name;
				$this->settings = (array) $settings;
				$updates        = true;
				$this->update_settings();
			}
		}
		return $updates;
	}
	/**
	 * Update settings function
	 */
	private function update_settings(): void {
		global $wpdb;
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"UPDATE $wpdb->racketmanager_competitions SET `name` = %s, `type` = %s, `settings` = %s, `age_group` = %s WHERE `id` = %d",
				$this->name,
				$this->type,
				maybe_serialize( $this->settings ),
				$this->age_group,
				$this->id
			)
		);
		wp_cache_set( $this->id, $this, 'competitions' );
	}
	/**
	 * Notify team entry open
	 *
	 * @param int $season season name.
	 *
	 * @return object
	 */
	public function notify_team_entry_open( int $season ): object {
		global $racketmanager, $racketmanager_shortcodes;
		$msg             = null;
		$return          = new \stdClass();
		$is_championship = null;
		if ( isset( $this->seasons[ $season ] ) ) {
			$season_dtls = (object) $this->seasons[ $season ];
			if ( $this->is_league ) {
				$events = $this->get_events();
				foreach ( $events as $event ) {
					if ( empty( $event->get_leagues() ) ) {
						$return->error = true;
						$msg[]         = __( 'No leagues found for event', 'racketmanager' ) . ' ' . $event->name;
					} elseif ( count( $event->seasons ) > 1 ) {
						$constitution = $event->get_constitution(
							array(
								'season' => $season,
								'count'  => true,
							)
						);
						if ( ! $constitution ) {
							$return->error = true;
							$msg[]         = __( 'Constitution not set', 'racketmanager' ) . ' ' . $event->name;
						}
					}
				}
				$is_championship         = false;
				$season_dtls->venue_name = null;
			} elseif ( $this->is_cup ) {
				$is_championship         = true;
				$season_dtls->venue_name = null;
				if ( ! empty( $season_dtls->venue ) ) {
					$venue_club = get_club( $season_dtls->venue );
					if ( $venue_club ) {
						$season_dtls->venue_name = $venue_club->shortcode;
					}
				}
			} else {
				$return->error = true;
				$return->msg   = __( 'Competition type not valid', 'racketmanager' );
			}
			if ( empty( $return->error ) ) {
				$url              = $racketmanager->site_url . '/entry-form/' . seo_url( $this->name ) . '/' . $season . '/';
				$competition_name = $this->name . ' ' . $season;
				$clubs            = $racketmanager->get_clubs(
					array(
						'type' => 'affiliated',
					)
				);
				$headers          = array();
				$from_email       = $racketmanager->get_confirmation_email( $this->type );
				if ( $from_email ) {
					$headers[]         = 'From: ' . ucfirst( $this->type ) . 'Secretary <' . $from_email . '>';
					$headers[]         = 'cc: ' . ucfirst( $this->type ) . 'Secretary <' . $from_email . '>';
					$organisation_name = $racketmanager->site_name;
					$messages_sent     = 0;
					foreach ( $clubs as $club ) {
						$email_subject = $racketmanager->site_name . ' - ' . ucwords( $competition_name ) . ' ' . __( 'Entry Open', 'racketmanager' ) . ' - ' . $club->name;
						$email_to      = $club->match_secretary_name . ' <' . $club->match_secretary_email . '>';
						$action_url    = $url . seo_url( $club->shortcode ) . '/';
						$email_message = $racketmanager_shortcodes->load_template(
							'competition-entry-open',
							array(
								'email_subject'   => $email_subject,
								'from_email'      => $from_email,
								'action_url'      => $action_url,
								'organisation'    => $organisation_name,
								'is_championship' => $is_championship,
								'competition'     => $competition_name,
								'addressee'       => $club->match_secretary_name,
								'season_dtls'     => $season_dtls,
							),
							'email'
						);
						wp_mail( $email_to, $email_subject, $email_message, $headers );
						++$messages_sent;
					}
					if ( $messages_sent ) {
						/* translation: %d number of messages sent */
						$return->msg = sprintf( __( '%d match secretaries notified', 'racketmanager' ), $messages_sent );
					} else {
						$return->error = true;
						$msg[]         = __( 'No notification', 'racketmanager' );
					}
				} else {
					$return->error = true;
					$msg[]         = __( 'No secretary email', 'racketmanager' );
				}
			}
		} else {
			$return->error = true;
			$msg[]         = __( 'Competition season not found', 'racketmanager' );
		}
		if ( ! empty( $return->error ) ) {
			$return->msg = __( 'Notification error', 'racketmanager' );
			foreach ( $msg as $error ) {
				$return->msg .= '<br>' . $error;
			}
		}
		return $return;
	}
	/**
	 * Notify team entry reminder
	 *
	 * @param int $season season name.
	 *
	 * @return object
	 */
	public function notify_team_entry_reminder( int $season ): object {
		global $racketmanager, $racketmanager_shortcodes;
		$msg           = null;
		$return        = new \stdClass();
		$messages_sent = 0;
		if ( isset( $this->seasons[ $season ] ) ) {
			$clubs = $racketmanager->get_clubs(
				array(
					'type' => 'affiliated',
				)
			);
			foreach ( $clubs as $c => $club ) {
				$entry_found = $this->get_clubs(
					array(
						'club_id' => $club->id,
						'count'   => true,
						'season'  => $season,
						'status'  => 1,
					)
				);
				if ( $entry_found ) {
					unset( $clubs[ $c ] );
				}
			}
			if ( $clubs ) {
				$season_dtls             = (object) $this->seasons[ $season ];
				$season_dtls->venue_name = null;
				if ( $this->is_league ) {
					$is_championship = false;
				} else {
					if ( ! empty( $season_dtls->venue ) ) {
						$venue_club = get_club( $season_dtls->venue );
						if ( $venue_club ) {
							$season_dtls->venue_name = $venue_club->shortcode;
						}
					}
					$is_championship = true;
				}
				$date_closing     = date_create( $season_dtls->date_closing );
				$now              = date_create();
				$remaining_time   = date_diff( $date_closing, $now, true );
				$days_remaining   = $remaining_time->days;
				$url              = $racketmanager->site_url . '/entry-form/' . seo_url( $this->name ) . '/' . $season . '/';
				$competition_name = $this->name . ' ' . $season;
				$headers          = array();
				$from_email       = $racketmanager->get_confirmation_email( $this->type );
				if ( $from_email ) {
					$headers[]         = 'From: ' . ucfirst( $this->type ) . 'Secretary <' . $from_email . '>';
					$headers[]         = 'cc: ' . ucfirst( $this->type ) . 'Secretary <' . $from_email . '>';
					$organisation_name = $racketmanager->site_name;
					foreach ( $clubs as $club ) {
						$email_subject = $racketmanager->site_name . ' - ' . ucwords( $competition_name ) . ' ' . __( 'Entries Closing Soon', 'racketmanager' ) . ' - ' . $club->name;
						$email_to      = $club->match_secretary_name . ' <' . $club->match_secretary_email . '>';
						$action_url    = $url . seo_url( $club->shortcode ) . '/';
						$email_message = $racketmanager_shortcodes->load_template(
							'competition-entry-open',
							array(
								'email_subject'   => $email_subject,
								'from_email'      => $from_email,
								'action_url'      => $action_url,
								'organisation'    => $organisation_name,
								'is_championship' => $is_championship,
								'competition'     => $competition_name,
								'addressee'       => $club->match_secretary_name,
								'season_dtls'     => $season_dtls,
								'days_remaining'  => $days_remaining,
							),
							'email'
						);
						wp_mail( $email_to, $email_subject, $email_message, $headers );
						++$messages_sent;
					}
					if ( $messages_sent ) {
						/* translation: %d number of messages sent */
						$return->msg = sprintf( __( '%d match secretaries notified', 'racketmanager' ), $messages_sent );
					} else {
						$return->error = true;
						$msg[]         = __( 'No notification', 'racketmanager' );
					}
				} else {
					$return->error = true;
					$msg[]         = __( 'No secretary email', 'racketmanager' );
				}
			} else {
				$return->error = true;
				$msg[]         = __( 'No clubs with outstanding entries', 'racketmanager' );
			}
		} else {
			$return->error = true;
			$msg[]         = __( 'Competition season not found', 'racketmanager' );
		}
		if ( ! empty( $return->error ) ) {
			$return->msg = __( 'Notification error', 'racketmanager' );
			foreach ( $msg as $error ) {
				$return->msg .= '<br>' . $error;
			}
		}
		return $return;
	}
}
