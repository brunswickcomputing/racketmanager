<?php
/**
 * Racketmanager_League API: League class
 *
 * @author Kolja Schleich
 * @package RacketManager
 * @subpackage League
 */

namespace Racketmanager;

/**
 * Class to implement the League object
 */
class Racketmanager_League {

	/**
	 * League ID
	 *
	 * @var int
	 */
	public $id;

	/**
	 * League title
	 *
	 * @var string
	 */
	public $title;

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
	public $sport = 'default';

	/**
	 * Point rule
	 *
	 * @var string
	 */
	public $point_rule = 'three';

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
	 * Standings table layout settings
	 *
	 * @var array
	 */
	public $standings;

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
	 * Number of teams for relegationnum_relegation
	 *
	 * @var int
	 */
	public $num_relegation = 0;

	/**
	 * Number of teams per page in list
	 *
	 * @var int
	 */
	public $num_teams_per_page = 10;

	/**
	 * Number of pages for teams
	 *
	 * @var int
	 */
	public $num_pages_teams = 0;

	/**
	 * Current page for teams
	 *
	 * @var int
	 */
	public $current_page_teams = 1;

	/**
	 * Teams pagination
	 *
	 * @var string
	 */
	public $pagination_teams = '';

	/**
	 * Number of matches per page
	 *
	 * @var int
	 */
	public $num_matches_per_page = 30;

	/**
	 * Default display filter for matches
	 *
	 * @var string
	 */
	public $match_display = 'current_match_day';

	/**
	 * Number of pages for matches
	 *
	 * @var int
	 */
	public $num_pages_matches = 0;

	/**
	 * Current page for matches
	 *
	 * @var int
	 */
	public $current_page_matches = 1;

	/**
	 * Matches pagination
	 *
	 * @var string
	 */
	public $pagination_matches = '';

	/**
	 * Slideshow options
	 *
	 * @var array
	 */
	public $slideshow = array(
		'season'      => 'latest',
		'num_matches' => 0,
	);

	/**
	 * Team groups
	 *
	 * @var string
	 */
	public $groups;

	/**
	 * Current team group
	 *
	 * @var string
	 */
	public $current_group = '';

	/**
	 * Teams
	 *
	 * @var array
	 */
	public $teams = array();

	/**
	 * Total number of teams
	 *
	 * @var int
	 */
	public $num_teams_total = 0;

	/**
	 * Number of teams for current query
	 *
	 * @var int
	 */
	public $num_teams = 0;

	/**
	 * Number of teams by group
	 *
	 * @var array
	 */
	public $num_teams_by_group = array();

	/**
	 * Matches
	 *
	 * @var array
	 */
	public $matches = array();

	/**
	 * Total number of matches
	 *
	 * @var int
	 */
	public $num_matches_total = 0;

	/**
	 * Number of matches
	 *
	 * @var int
	 */
	public $num_matches = 0;

	/**
	 * Current season
	 *
	 * @var array
	 */
	public $current_season = array();

	/**
	 * Number of match days
	 *
	 * @var int
	 */
	public $num_match_days = 0;

	/**
	 * Current match day
	 *
	 * @var int
	 */
	public $match_day = -1;

	/**
	 * Query arguments
	 *
	 * @var array
	 */
	private $query_args = array();

	/**
	 * Team database query args
	 *
	 * @var array
	 */
	private $team_query_args = array(
		'limit'            => false,
		'group'            => '',
		'season'           => '',
		'rank'             => 0,
		'orderby'          => array( 'rank' => 'ASC' ),
		'home'             => false,
		'ids'              => array(),
		'cache'            => true,
		'reset_query_args' => false,
		'get_details'      => false,
		'status'           => false,
		'club'             => false,
		'team_name'        => '',
		'team_id'          => '',
	);

	/**
	 * Team query argument types
	 *
	 * @var array
	 */
	private $team_query_args_types = array(
		'limit'            => 'numeric',
		'group'            => 'string',
		'season'           => 'string',
		'rank'             => 'numeric',
		'orderby'          => 'array',
		'home'             => 'boolean',
		'ids'              => 'array_numeric',
		'cache'            => 'boolean',
		'reset_query_args' => 'boolean',
		'get_details'      => 'boolean',
		'status'           => 'string',
		'club'             => 'numeric',
		'team_name'        => 'string',
		'team_id'          => 'numeric',
	);

	/**
	 * Match query arguments
	 *
	 * @var array
	 */
	private $match_query_args = array(
		'limit'            => true,
		'group'            => '',
		'season'           => '',
		'final'            => '',
		'match_day'        => -1,
		'match_date'       => false,
		'time'             => '',
		'home_only'        => false,
		'count'            => false,
		'orderby'          => array(
			'date' => 'ASC',
			'id'   => 'ASC',
		),
		'standingstable'   => false,
		'cache'            => true,
		'team_id'          => 0,
		'home_team'        => '',
		'away_team'        => '',
		'team_pair'        => array(),
		'winner_id'        => false,
		'loser_id'         => false,
		'home_points'      => false,
		'away_points'      => false,
		'mode'             => '',
		'reset_limit'      => true,
		'reset_query_args' => false,
		'update_results'   => false,
		'confirmed'        => false,
		'leg'              => false,
		'player'           => false,
		'withdrawn'        => true,
		'affiliatedClub'   => false,
		'pending'          => false,
	);

	/**
	 * Match query argument types
	 *
	 * @var array
	 */
	private $match_query_args_types = array(
		'limit'            => 'numeric',
		'group'            => 'string',
		'season'           => 'string',
		'final'            => 'string',
		'match_day'        => 'numeric',
		'match_date'       => 'string',
		'time'             => 'string',
		'home_only'        => 'boolean',
		'count'            => 'boolean',
		'orderby'          => 'array',
		'standingstable'   => 'boolean',
		'cache'            => 'boolean',
		'team_id'          => 'numeric',
		'home_team'        => 'string',
		'away_team'        => 'string',
		'team_pair'        => 'array',
		'winner_id'        => 'numeric',
		'loser_id'         => 'numeric',
		'home_points'      => 'string',
		'away_points'      => 'string',
		'mode'             => 'string',
		'reset_limit'      => 'boolean',
		'reset_query_args' => 'boolean',
		'update_results'   => 'boolean',
		'confirmed'        => 'boolean',
		'leg'              => 'numeric',
		'player'           => 'numeric',
		'withdrawn'        => 'boolean',
		'affiliatedClub'   => 'numeric',
		'pending'          => 'boolean',
	);

	/**
	 * Settings keys
	 *
	 * @var array
	 */
	private $settings_keys = array();

	/**
	 * Team offsets indexed by ID
	 *
	 * @var array
	 */
	public $team_index = array();

	/**
	 * Team loop
	 *
	 * @var boolean
	 */
	public $in_the_team_loop = false;

	/**
	 * Current team
	 *
	 * @var int
	 */
	public $current_team = -1;

	/**
	 * Team is selected?
	 *
	 * @var boolean
	 */
	public $is_selected_team = false;

	/**
	 * Match loop
	 *
	 * @var boolean
	 */
	public $in_the_match_loop = false;

	/**
	 * Current match
	 *
	 * @var int
	 */
	public $current_match = -1;

	/**
	 * Match is selected
	 *
	 * @var boolean
	 */
	public $is_selected_match = false;

	/**
	 * Toggle match day selection menu display
	 *
	 * @var boolean
	 */
	public $show_match_day_selection = true;

	/**
	 * Toggle team selection menu display
	 *
	 * @var boolean
	 */
	public $show_team_selection = true;

	/**
	 * Toggle matches selection menu display, depends on $show_match_day_selection and $show_team_selection
	 *
	 * @var boolean
	 */
	public $show_matches_selection = true;

	/**
	 * Is this an archive
	 *
	 * @var boolean
	 */
	public $is_archive = false;

	/**
	 * Set archive tab
	 *
	 * @var int
	 */
	public $archive_tab = 0;

	/**
	 * Save templates for whole league or archive display
	 *
	 * @var array
	 */
	public $templates = array();

	/**
	 * Project ID for team profiles
	 *
	 * @var int
	 */
	public $teamprofiles = 0;

	/**
	 * Project ID for team roster
	 *
	 * @var int
	 */
	public $teamroster = 0;

	/**
	 * Project ID for match statistics
	 *
	 * @var int
	 */
	public $matchstats = 0;

	/**
	 *
	 * Championship flag
	 *
	 * @var boolean
	 */
	public $is_championship = false;

	/**
	 * Racketmanager_Championship object
	 *
	 * @var Racketmanager_Championship
	 */
	public $championship = null;

	/**
	 * Event id
	 *
	 * @var int
	 */
	public $event_id = '';

	/**
	 * Number of sets
	 *
	 * @var int
	 */
	public $num_sets = '';

	/**
	 * Number of rubbers
	 *
	 * @var int
	 */
	public $num_rubbers = '';

	/**
	 * Number of sets required to win a match
	 *
	 * @var int
	 */
	public $num_sets_to_win = '';

	/**
	 * Competition type
	 *
	 * @var string
	 */
	public $competition_type = '';

	/**
	 * Type
	 *
	 * @var string
	 */
	public $type = '';

	/**
	 * Event
	 *
	 * @var object
	 */
	public $event = '';

	/**
	 * Scoring
	 *
	 * @var string
	 */
	public $scoring = '';

	/**
	 * Custom team input field keys and translated labels
	 *
	 * @var array
	 */
	public $fields_team = array();

	/**
	 * Custom match input field keys and translated labels
	 *
	 * @var array
	 */
	protected $fields_match = array();
	/**
	 * Is final flag
	 *
	 * @var boolean
	 */
	public $is_final = false;
	/**
	 * Entry type
	 *
	 * @var string
	 */
	public $entry_type;
	/**
	 * Match template type
	 *
	 * @var string
	 */
	public $matches_template_type;
	/**
	 * Season
	 *
	 * @var string
	 */
	public $season;
	/**
	 * Players
	 *
	 * @var array
	 */
	public $players;
	/**
	 * Retrieve league instance
	 *
	 * @param int $league_id league id.
	 */
	public static function get_instance( $league_id ) {
		global $wpdb;

		if ( is_numeric( $league_id ) ) {
			$search = $wpdb->prepare(
				'`id` = %d',
				intval( $league_id )
			);
		} else {
			$search = $wpdb->prepare(
				'`title` = %s',
				$league_id
			);
		}
		if ( ! $league_id ) {
			return false;
		}

		$league = wp_cache_get( $league_id, 'leagues' );

		if ( ! $league ) {
			$league = $wpdb->get_row(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				"SELECT `title`, `id`, `settings`, `event_id` FROM {$wpdb->racketmanager} WHERE " . $search . ' LIMIT 1'
			);  // db call ok.
			if ( $league ) {
				$event = get_event( $league->event_id );
				if ( ! $event ) {
					return false;
				}
			} else {
				return false;
			}
			$league->settings = (array) maybe_unserialize( $league->settings );
			$league           = (object) array_merge( (array) $league, $league->settings );

			if ( ! $league ) {
				return false;
			}

			// check if specific sports class exists.
			if ( ! isset( $event->competition->sport ) ) {
				$league->sport = '';
			}
			$instance = 'Racketmanager\Racketmanager_League_' . ucfirst( $event->competition->sport );
			if ( class_exists( $instance ) ) {
				$league = new $instance( $league );
			} else {
				$league = new Racketmanager_League( $league );
			}

			wp_cache_set( $league->id, $league, 'leagues' );
		}

		return $league;
	}

	/**
	 * Constructor
	 *
	 * @param object $league League object.
	 */
	public function __construct( $league ) {
		if ( isset( $league->settings ) ) {
			$league->settings      = (array) maybe_unserialize( $league->settings );
			$league->settings_keys = array_keys( (array) maybe_unserialize( $league->settings ) );
			$league                = (object) array_merge( (array) $league, $league->settings );
			unset( $league->settings );
		}

		foreach ( get_object_vars( $league ) as $key => $value ) {
			if ( 'standings' === $key ) {
				$this->$key = array_merge( $this->$key, $value );
			} else {
				$this->$key = $value;
			}
		}

		if ( ! isset( $this->id ) ) {
			$this->add();
		}
		$this->title = stripslashes( $this->title );
		$event       = get_event( $this->event_id );

		$this->seasons = $event->seasons;
		// set seasons.
		if ( '' === $this->seasons ) {
			$this->seasons = array();
		}
		$this->seasons     = (array) maybe_unserialize( $this->seasons );
		$this->num_seasons = count( $this->seasons );
		// set season to latest.
		$this->set_season();
		$this->groups          = trim( $this->groups );
		$this->mode            = $event->competition->mode;
		$this->num_sets        = $event->num_sets;
		$this->num_sets_to_win = floor( $this->num_sets / 2 ) + 1;
		$this->num_rubbers     = $event->num_rubbers;
		$this->type            = $event->type;
		$this->point_rule      = $event->competition->point_rule;
		$this->sport           = $event->competition->sport;
		$this->event           = $event;
		$this->scoring         = isset( $event->scoring ) ? $event->scoring : null;
		$this->set_match_query_args();
		$this->set_num_matches( true ); // get total number of matches.
		$this->set_num_matches();
		$this->set_num_teams( true ); // get total number of teams.
		$this->standings     = $event->standings;
		$this->point_format  = $event->competition->point_format;
		$this->point_format2 = $event->competition->point_format2;
		// set default standings display options for additional team fields.
		if ( count( $this->fields_team ) > 0 ) {
			foreach ( $this->fields_team as $key => $data ) {
				if ( ! isset( $this->standings[ $key ] ) ) {
					$this->standings[ $key ] = 1;
				}
			}
		}
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		// set selected team marker.
		if ( isset( $_GET[ 'team_' . $this->id ] ) ) {
			$this->current_team     = intval( $_GET[ 'team_' . $this->id ] );
			$this->is_selected_team = true;
		}

		// set selected match marker.
		if ( isset( $_GET[ 'match_' . $this->id ] ) ) {
			$this->current_match     = intval( $_GET[ 'match_' . $this->id ] );
			$this->is_selected_match = true;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		// Championship.
		if ( 'championship' === $this->mode ) {
			$this->is_championship = true;
			$this->championship    = new Racketmanager_Championship( $this, $this->get_settings() );
		}

		// add actions & filter.
		add_filter( 'racketmanager_import_matches_' . $this->sport, array( &$this, 'import_matches' ), 10, 4 );
		add_filter( 'racketmanager_import_teams_' . $this->sport, array( &$this, 'import_teams' ), 10, 3 );
	}

	/**
	 * Add new League
	 */
	private function add() {
		global $wpdb;

		$settings = array();
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"INSERT INTO {$wpdb->racketmanager} (title, event_id, settings) VALUES (%s, %d, %s)",
				$this->title,
				$this->event_id,
				maybe_serialize( $settings )
			)
		);
		$this->id = $wpdb->insert_id;
	}

	/**
	 * Edit League
	 *
	 * @param string $title title.
	 */
	public function update( $title ) {
		global $wpdb;

		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"UPDATE {$wpdb->racketmanager} SET `title` = %s WHERE `id` = %d",
				$title,
				$this->id
			)
		);
	}

	/**
	 * Delete League
	 */
	public function delete() {
		global $wpdb;
		$matches = $this->get_matches( array() );
		// remove matches and rubbers.
		foreach ( $matches as $match ) {
			$match = get_match( $match->id );
			$match->delete();
		}
		// remove tables.
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE FROM {$wpdb->racketmanager_table} WHERE `league_id` = %d",
				$this->id
			)
		);
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE FROM {$wpdb->racketmanager} WHERE `id` = %d",
				$this->id
			)
		);
	}

	/**
	 * Delete team from League
	 *
	 * @param integer $team team id.
	 * @param string  $season season.
	 */
	public function delete_team( $team, $season ) {
		global $wpdb, $racketmanager;

		$matches = $this->get_matches(
			array(
				'team_id' => $team,
				'season'  => $season,
			)
		);
		// remove matches and rubbers.
		foreach ( $matches as $match ) {
			$match = get_match( $match->id );
			$match->delete();
		}
		// remove tables.
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE FROM {$wpdb->racketmanager_table} WHERE `team_id` = %d AND `league_id` = %d and `season` = %s",
				$team,
				$this->id,
				$season
			)
		);

		$racketmanager->set_message( __( 'Team Deleted', 'racketmanager' ) );
	}
	/**
	 * Withdraw team from League
	 *
	 * @param integer $team team id.
	 * @param string  $season season.
	 */
	public function withdraw_team( $team, $season ) {
		global $wpdb, $racketmanager;

		// remove tables.
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"UPDATE {$wpdb->racketmanager_table} SET `status` = 'W' WHERE `team_id` = %d AND `league_id` = %d and `season` = %s",
				$team,
				$this->id,
				$season
			)
		);
		$this->update_standings( $season );
		$racketmanager->set_message( __( 'Team withdrawn', 'racketmanager' ) );
	}
	/**
	 * Set detault dataset query arguments
	 */
	private function set_match_query_args() {
		// set to latest match day by default.
		$this->set_match_query_arg( 'match_day', 'current' );

		// set number of matches per page.
		$this->set_match_query_arg( 'limit', $this->num_matches_per_page );
	}

	/**
	 * Set match query argument
	 *
	 * @param string  $key key.
	 * @param mixed   $value value.
	 * @param boolean $replace - used for arrays to add arguments or replace with values.
	 */
	public function set_match_query_arg( $key, $value, $replace = true ) {
		if ( 'limit' === $key && ( true === $value || 'true' === $value ) ) {
			$value = $this->num_matches_per_page;
		}
		// sanitize query arg types.
		$v = $value;
		if ( 'numeric' === $this->match_query_args_types[ $key ] ) {
			$v = intval( $value );
		}
		if ( 'boolean' === $this->match_query_args_types[ $key ] ) {
			$v = intval( $value ) === 1;
		}
		if ( is_array( $this->match_query_args[ $key ] ) && ! $replace ) {
			if ( ! is_array( $v ) ) {
				$v = array( $v );
			}
			$this->match_query_args[ $key ] = array_merge( $this->match_query_args[ $key ], $v );
		} else {
			$this->match_query_args[ $key ] = $v;
		}
	}


	/**
	 * Set team query argument
	 *
	 * @param string  $key key.
	 * @param mixed   $value value.
	 * @param boolean $replace - used for arrays to add arguments or replace with values.
	 */
	public function set_team_query_arg( $key, $value, $replace = true ) {
		if ( 'limit' === $key && ( true === $value || 'true' === $value ) ) {
			$value = $this->num_teams_per_page;
		}
		// sanitize query arg types.
		if ( 'numeric' === $this->team_query_args_types[ $key ] ) {
			$value = intval( $value );
		}
		if ( 'boolean' === $this->team_query_args_types[ $key ] ) {
			$value = intval( $value ) === 1;
		}

		if ( is_array( $this->team_query_args[ $key ] ) && ! $replace ) {
			if ( ! is_array( $value ) ) {
				$value = array( $value );
			}
			$this->team_query_args[ $key ] = array_merge( $this->team_query_args[ $key ], $value );
		} else {
			$this->team_query_args[ $key ] = $value;
		}
	}

	/**
	 * Set current season
	 *
	 * @param mixed   $season season.
	 * @param boolean $force_overwrite force overwrite.
	 */
	public function set_season( $season = false, $force_overwrite = false ) {
		global $wp;
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $season ) && true === $force_overwrite ) {
			$data = $this->seasons[ $season ];
		} elseif ( isset( $_POST['season'] ) && ! empty( $_POST['season'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$key = htmlspecialchars( wp_strip_all_tags( wp_unslash( $_POST['season'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( ! isset( $this->seasons[ $key ] ) ) {
				$data = false;
			} else {
				$data = $this->seasons[ $key ];
			}
		} elseif ( isset( $_GET['season'] ) && ! empty( $_GET['season'] ) ) {
			$key = htmlspecialchars( wp_strip_all_tags( wp_unslash( $_GET['season'] ) ) );
			if ( ! isset( $this->seasons[ $key ] ) ) {
				$data = false;
			} else {
				$data = $this->seasons[ $key ];
			}
		} elseif ( isset( $_GET[ 'season_' . $this->id ] ) && ! empty( $_GET[ 'season_' . $this->id ] ) ) {
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
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		if ( empty( $data ) ) {
			$data = end( $this->seasons );
		}
		if ( ! $data ) {
			$data['name']           = '';
			$data['num_match_days'] = 0;
		}
		$this->current_season = $data;
		$this->num_match_days = $data['num_match_days'];

		$this->set_team_query_arg( 'season', $this->current_season['name'] );
		$this->set_match_query_arg( 'season', $this->current_season['name'] );
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
	 * Set group
	 *
	 * @param string  $group group.
	 * @param boolean $force_overwrite force overwrite.
	 */
	public function set_group( $group = '', $force_overwrite = false ) {
		if ( '' === $group || true !== $force_overwrite ) {
			if ( isset( $_GET['group'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$group = wp_strip_all_tags( wp_unslash( $_GET['group'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			} elseif ( is_admin() && isset( $_POST['group'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$group = wp_strip_all_tags( wp_unslash( $_POST['group'] ) );  // phpcs:ignore WordPress.Security.NonceVerification.Missing
			} else {
				// set to first group in league by default.
				$groups = $this->get_groups();
				if ( isset( $groups[0] ) ) {
					$group = $groups[0];
				}
			}
		}

		if ( is_array( $group ) ) {
			$group = $group[0];
		}
		$group = htmlspecialchars( wp_strip_all_tags( $group ) );
		if ( $this->group_exists( $group ) ) {
			$this->set_team_query_arg( 'group', $group );
			$this->set_match_query_arg( 'group', $group );
			$this->current_group = $group;
		}
	}

	/**
	 * Get current group
	 */
	public function get_group() {
		return $this->current_group;
	}

	/**
	 * Get groups
	 *
	 * @return array
	 */
	public function get_groups() {
		$this->groups = trim( $this->groups );
		if ( is_string( $this->groups ) ) {
			$groups = explode( ';', $this->groups );
		} else {
			$groups = $this->groups;
		}
		if ( ! is_array( $groups ) ) {
			return false;
		}
		return $groups;
	}

	/**
	 * Retrieve match day
	 *
	 * @param mixed $_match_day match day.
	 */
	public function set_match_day( $_match_day = false ) {
		global $wpdb, $wp;
		if ( isset( $_GET['match_day'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$match_day = intval( $_GET['match_day'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		} elseif ( isset( $_GET[ 'match_day_' . $this->id ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$match_day = intval( $_GET[ 'match_day_' . $this->id ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		} elseif ( isset( $_POST['match_day'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$match_day = intval( $_POST['match_day'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		} elseif ( isset( $wp->query_vars['match_day'] ) ) {
			$match_day = \get_query_var( 'match_day' );
		} elseif ( is_numeric( $_match_day ) && 0 !== $_match_day ) {
			$match_day = intval( $_match_day );
		} elseif ( 'last' === $_match_day ) {
			$match_day = wp_cache_get( 'last_' . $this->id, 'leagues_match_days' );
			if ( ! $match_day ) {
				$match = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT `match_day`, DATEDIFF(NOW(), `date`) AS datediff FROM {$wpdb->racketmanager_matches} WHERE `league_id` = %d AND `season` = %s AND DATEDIFF(NOW(), `date`) > 0 ORDER BY datediff ASC LIMIT 1",
						$this->id,
						$this->current_season['name']
					)
				); // db call ok.
				if ( $match ) {
					$match_day = $match->match_day;
					wp_cache_set( 'last_' . $this->id, $match_day, 'leagues_match_days' );
				} else {
					$match_day = $_match_day;
				}
			}
		} elseif ( 'next' === $_match_day ) {
			$match_day = wp_cache_get( 'next_' . $this->id, 'leagues_match_days' );
			if ( ! $match_day ) {
				$match = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT `match_day`, DATEDIFF(NOW(), `date`) AS datediff FROM {$wpdb->racketmanager_matches} WHERE `league_id` = %d AND `season` = %s AND DATEDIFF(NOW(), `date`) < 0 ORDER BY datediff DESC LIMIT 1",
						$this->id,
						$this->current_season['name']
					)
				); // db call ok.
				if ( $match ) {
					$match_day = $match->match_day;
					wp_cache_set( 'next_' . $this->id, $match_day, 'leagues_match_days' );
				} else {
					$match_day = $_match_day;
				}
			}
		} elseif ( 'current' === $_match_day || 'latest' === $_match_day ) {
			$match_day = wp_cache_get( 'current_' . $this->id, 'leagues_match_days' );
			if ( ! $match_day ) {
				$match = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT `id`, `match_day`, ABS(DATEDIFF(NOW(), `date`)) AS datediff FROM {$wpdb->racketmanager_matches} WHERE `league_id` = %d AND `season` = %s ORDER BY datediff ASC LIMIT 1",
						$this->id,
						$this->current_season['name']
					)
				); // db call ok.
				if ( $match ) {
					$match_day = $match->match_day;
					wp_cache_set( 'current_' . $this->id, $match_day, 'leagues_match_days' );
				} else {
					$match_day = $_match_day;
				}
			}
		} else {
			$match_day = 1;
		}
		if ( empty( $match_day ) || ! is_numeric( $match_day ) ) {
			$match_day = 1;
		}
		$this->match_day                     = intval( $match_day );
		$this->match_query_args['match_day'] = $match_day;
	}

	/**
	 * Get pagination
	 *
	 * @param string $which type of pagination.
	 * @return string
	 */
	public function get_page_links( $which = 'matches' ) {
		$this->get_current_page( $which );

		if ( 'matches' === $which ) {
			$base         = is_admin() ? 'match_paged' : 'match_paged_' . $this->id;
			$num_pages    = $this->num_pages_matches;
			$current_page = $this->current_page_matches;
			$num_items    = $this->num_matches;
		} elseif ( 'teams' === $which ) {
			$base         = is_admin() ? 'team_paged' : 'team_paged_' . $this->id;
			$num_pages    = $this->num_pages_teams;
			$current_page = $this->current_page_teams;
			$num_items    = $this->num_matches;
		} else {
			return '';
		}

		$query_args = $this->query_args;

		if ( 'matches' === $which && isset( $_POST['match_day'] ) && is_string( $_POST['match_day'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$query_args['match_day'] = htmlspecialchars( wp_strip_all_tags( wp_unslash( $_POST['match_day'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}
		$page_links = paginate_links(
			array(
				'base'      => add_query_arg( $base, '%#%' ),
				'format'    => '',
				'prev_text' => '&#9668;',
				'next_text' => '&#9658;',
				'total'     => $num_pages,
				'current'   => $current_page,
				'add_args'  => $query_args,
			)
		);

		if ( $page_links && is_admin() ) {
			/* translators: %s: number of matches  */
			$page_links = sprintf( '<span class="displaying-num">' . __( '%s Matches', 'racketmanager' ) . '</span>%s', number_format_i18n( $num_items ), $page_links );
		}

		return $page_links;
	}

	/**
	 * Set number of pages for matches
	 *
	 * @param string $which type of pagination.
	 */
	public function set_num_pages( $which = 'matches' ) {
		if ( 'matches' === $which ) {
			$this->num_pages_matches = ( 0 === $this->num_matches_per_page ) ? 1 : ceil( $this->num_matches / $this->num_matches_per_page );
			if ( 0 === $this->num_pages_matches ) {
				$this->num_pages_matches = 1;
			}
		}

		if ( 'teams' === $which ) {
			$this->num_pages_teams = ( 0 === $this->num_teams_per_page ) ? 1 : ceil( $this->num_teams / $this->num_teams_per_page );
			if ( 0 === $this->num_pages_teams ) {
				$this->num_pages_teams = 1;
			}
		}
	}

	/**
	 * Retrieve current page
	 *
	 * @param string $which type of pagination.
	 */
	public function get_current_page( $which = 'matches' ) {
		global $wp;

		$this->set_num_pages( $which );

		if ( 'matches' === $which ) {
			$key = 'match_paged';
		} elseif ( 'teams' === $which ) {
			$key = 'team_paged';
		}
		if ( isset( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$current_page = intval( $_GET[ $key ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		} elseif ( isset( $wp->query_vars[ $key ] ) ) {
			$current_page = max( 1, intval( $wp->query_vars[ $key ] ) );
		} elseif ( isset( $_GET[ $key . '_' . $this->id ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$current_page = intval( $_GET[ $key . '_' . $this->id ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		} elseif ( isset( $wp->query_vars[ $key . '_' . $this->id ] ) ) {
			$current_page = max( 1, intval( $wp->query_vars[ $key . '_' . $this->id ] ) );
		} else {
			$current_page = 1;
		}

		if ( 'matches' === $which && $current_page > $this->num_pages_matches ) {
			$current_page = $this->num_pages_matches;
		}
		if ( 'teams' === $which && $current_page > $this->num_pages_teams ) {
			$current_page = $this->num_pages_teams;
		}
		// Prevent negative offsets.
		if ( 0 === intval( $current_page ) ) {
			$current_page = 1;
		}
		if ( 'matches' === $which ) {
			$this->current_page_matches = $current_page;
		}
		if ( 'teams' === $which ) {
			$this->current_page_teams = $current_page;
		}
		return $current_page;
	}

	/**
	 * Get teams from league from database
	 *
	 * @param array $query_args query_arguments.
	 * @return array database results
	 */
	public function get_league_teams( $query_args = array() ) {
		global $wpdb;
		$old_query_args = $this->team_query_args;

		// set query args.
		foreach ( $query_args as $key => $value ) {
			$this->set_team_query_arg( $key, $value );
		}
		$club             = $this->team_query_args['club'];
		$season           = $this->team_query_args['season'];
		$rank             = $this->team_query_args['rank'];
		$orderby          = $this->team_query_args['orderby'];
		$home             = $this->team_query_args['home'];
		$cache            = $this->team_query_args['cache'];
		$reset_query_args = $this->team_query_args['reset_query_args'];
		$get_details      = $this->team_query_args['get_details'];
		$status           = $this->team_query_args['status'];
		$team_name        = $this->team_query_args['team_name'];
		$team_id          = $this->team_query_args['team_id'];

		$args = array( $this->id );
		$sql  = "SELECT B.`id` AS `id`, B.`title`, B.`affiliatedclub`, B.`stadium`, B.`home`, A.`group`, B.`roster`, B.`profile`, A.`group`, A.`points_plus`, A.`points_minus`, A.`points2_plus`, A.`points2_minus`, A.`add_points`, A.`done_matches`, A.`won_matches`, A.`draw_matches`, A.`lost_matches`, A.`diff`, A.`league_id`, A.`id` AS `table_id`, A.`season`, A.`rank`, A.`status`, A.`custom`, B.`team_type` FROM {$wpdb->racketmanager_teams} B INNER JOIN {$wpdb->racketmanager_table} A ON B.id = A.team_id WHERE `league_id` = %d";

		if ( '' === $season ) {
			$sql   .= ' AND A.`season` = %s';
			$args[] = $this->current_season['name'];
		} elseif ( 'any' === $season ) {
			$sql .= " AND A.`season` != ''";
		} elseif ( $this->season_exists( htmlspecialchars( $season ) ) ) {
			$sql   .= ' AND A.`season` = %s';
			$args[] = htmlspecialchars( $season );
		}

		if ( $rank ) {
			$sql   .= ' AND A.`rank` = %s';
			$args[] = $rank;
		}
		if ( $home ) {
			$sql .= ' AND B.`home` = 1';
		}
		if ( 'active' === $status && $status ) {
			$sql .= ' AND A.`profile` != 3';
		}
		if ( $club ) {
			$sql   .= ' AND B.`affiliatedclub` = %d';
			$args[] = $club;
		}
		if ( $team_name ) {
			$sql   .= ' AND B.`title` = %s';
			$args[] = $team_name;
		}
		if ( $team_id ) {
			$sql   .= ' AND B.`id` = %d';
			$args[] = $team_id;
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

		$sql .= ' ORDER BY ' . $orderby;
		$sql  = $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$sql,
			$args
		);
		$teams = wp_cache_get( $sql, 'leaguetable' );
		if ( ! $teams || ! $cache ) {
			$teams = $wpdb->get_results(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql
			); // db call ok.
			wp_cache_set( $sql, $teams, 'leaguetable' );
		}
		$class      = '';
		$team_index = array();
		foreach ( $teams as $i => $team ) {
			$team    = get_league_team( $team );
			$class   = array();
			$class[] = ( 'alternate' === $class ) ? '' : 'alternate';
			// Add class for home team.
			if ( 1 === $team->home ) {
				$class[] = 'homeTeam';
			}
			$team->custom  = stripslashes_deep( maybe_unserialize( $team->custom ) );
			$team->roster  = maybe_unserialize( $team->roster );
			$team->title   = htmlspecialchars( stripslashes( $team->title ), ENT_QUOTES );
			$team->stadium = stripslashes( $team->stadium );
			$team->class   = implode( ' ', $class );
			if ( 1 === $team->home ) {
				$team->title = '<strong>' . $team->title . '</strong>';
			}
			$team->points_formatted = array(
				'primary'   => sprintf( $this->point_format, $team->points_plus, $team->points_minus ),
				'secondary' => sprintf( $this->point_format2, $team->points2_plus, $team->points2_minus ),
			);
			if ( $get_details ) {
				$team_dtls           = $this->get_team_dtls( $team->id );
				$team->match_day     = $team_dtls->match_day;
				$team->match_time    = $team_dtls->match_time;
				$team->captain_id    = $team_dtls->captain_id;
				$team->captain       = $team_dtls->captain;
				$team->contactno     = $team_dtls->contactno;
				$team->contactemail  = $team_dtls->contactemail;
				$team->league_status = $team_dtls->league_status;
			}

			$team_index[ $team->id ] = $i;
			$teams[ $i ]             = $team;
		}

		$this->teams      = $teams;
		$this->team_index = $team_index;

		$this->set_num_teams();

		// reset team query args.
		if ( true === $reset_query_args ) {
			foreach ( $old_query_args as $key => $query_arg ) {
				$this->set_team_query_arg( $key, $query_arg, true );
			}

			$this->set_team_query_arg( 'reset_query_args', false );
		}

		return $teams;
	}

	/**
	 * Add team to league
	 *
	 * @param string|int $team_id team identifier.
	 * @param string     $season season.
	 * @param string     $rank rank.
	 * @param string     $status status.
	 * @param string     $profile profile.
	 * @return int $table_id
	 */
	public function add_team( $team_id, $season, $rank = null, $status = null, $profile = null ) {
		global $wpdb, $racketmanager;
		$error = false;
		if ( ! is_numeric( $team_id ) ) {
			$team = get_team( $team_id );
			if ( $team ) {
				$team_id = $team->id;
			} else {
				$team        = new \stdClass();
				$team->title = $team_id;
				$team->type  = $this->type;
				if ( $this->event->competition->is_tournament ) {
					$team->team_type = 'S';
				}
				$team = new Racketmanager_Team( $team );
				if ( $team ) {
					$team_id = $team->id;
				}
			}
		}
		$table_id = $wpdb->get_var( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				"SELECT `id` FROM {$wpdb->racketmanager_table} WHERE `team_id` = %d AND `season` = %s AND `league_id` = %d",
				$team_id,
				$season,
				$this->id
			)
		);
		if ( $table_id ) {
			$message_text = 'Team already in table';
			$error        = true;
		} else {
			if ( ! $rank ) {
				$result = $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
						"INSERT INTO {$wpdb->racketmanager_table} (`team_id`, `season`, `league_id`) VALUES (%d, %s, %d)",
						$team_id,
						$season,
						$this->id
					)
				);
			} else {
				$result = $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						"INSERT INTO {$wpdb->racketmanager_table} (`team_id`, `season`, `league_id`, `rank`, `status`, `profile`) VALUES (%d, %s, %d, %d, %s, %d)",
						$team_id,
						$season,
						$this->id,
						$rank,
						$status,
						$profile
					)
				);
			}
			if ( $result ) {
				$table_id     = $wpdb->insert_id;
				$message_text = __( 'Table entry added', 'racketmanager' );
			} else {
				$message_text = __( 'Error adding team to table', 'racketmanager' );
				$error        = true;
				error_log( $message_text ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( $wpdb->last_error ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
		}
		$racketmanager->set_message( $message_text, $error );

		if ( $error ) {
			return $error;
		} else {
			return $table_id;
		}
	}

	/**
	 * Get single team from League cache
	 *
	 * @param int $team_id team id.
	 * @return Team object
	 */
	public function get_league_team( $team_id ) {
		if ( isset( $this->team_index[ $team_id ] ) ) {
			return $this->teams[ $this->team_index[ $team_id ] ];
		} else {
			return $this->get_team_dtls( $team_id );
		}
	}

	/**
	 * Get single team
	 *
	 * @param int    $team_id team id.
	 * @param string $season season name (optional).
	 * @return object
	 */
	public function get_team_dtls( $team_id, $season = null ) {
		global $wpdb;
		if ( empty( $season ) ) {
			$season = $this->current_season['name'];
		}
		if ( -1 === $team_id ) {
			$team                 = (object) array(
				'id'     => -1,
				'title'  => 'Bye',
				'player' => array(),
			);
			$team->captain        = '';
			$team->contactno      = '';
			$team->contactemail   = '';
			$team->affiliatedclub = '';
			$team->stadium        = '';
			$team->roster         = '';
			return $team;
		}

		$sql = $wpdb->prepare(
			"SELECT A.`title`, B.`captain`, A.`affiliatedclub`, B.`match_day`, B.`match_time`, A.`stadium`, A.`home`, A.`roster`, A.`profile`, A.`id`, A.`status`, A.`type`, A.`team_type`, C.`status` as `league_status` FROM {$wpdb->racketmanager_table} C INNER JOIN  {$wpdb->racketmanager_teams} A ON A.`id` = C.`team_id` AND C.`league_id` = %d LEFT JOIN {$wpdb->racketmanager_team_events} B ON A.`id` = B.`team_id` and B.`event_id` IN (select `event_id` FROM {$wpdb->racketmanager} WHERE `id` = %d) WHERE A.`id` = %d AND C.`season` = %s",
			intval( $this->id ),
			intval( $this->id ),
			intval( $team_id ),
			$season
		);

		$team = wp_cache_get( md5( $sql ), 'teamdetails' );
		if ( ! $team ) {
			$team = $wpdb->get_row(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql
			); // db call ok.
			wp_cache_set( md5( $sql ), $team, 'teamdetails' );
		}

		if ( ! isset( $team ) ) {
			return false;
		}
		if ( strpos( $team->title, '_' ) !== false ) {
			$team_name  = null;
			$name_array = explode( '_', $team->title );
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
		} else {
			$team->title = htmlspecialchars( stripslashes( $team->title ), ENT_QUOTES );
		}
		$captain = get_userdata( $team->captain );
		if ( $captain ) {
			$team->captain_id   = $team->captain;
			$team->captain      = $captain->display_name;
			$team->contactno    = get_user_meta( $captain->ID, 'contactno', true );
			$team->contactemail = $captain->user_email;
		} else {
			$team->captain_id   = '';
			$team->captain      = '';
			$team->contactno    = '';
			$team->contactemail = '';
		}
		if ( ! empty( $team->affiliatedclub ) ) {
			$team->affiliatedclub = stripslashes( $team->affiliatedclub );
			$team->club           = get_club( $team->affiliatedclub );
			if ( $team->club ) {
				$team->affiliatedclubname = $team->club->name;
			} else {
				$team->affiliatedclubname = null;
			}
		} else {
			$team->affiliatedclub     = null;
			$team->club               = null;
			$team->affiliatedclubname = null;
		}
		$team->stadium = stripslashes( $team->stadium );
		$team->roster  = maybe_unserialize( $team->roster );
		if ( 'P' === $team->team_type && null !== $team->roster ) {
			$team->players = array();
			$i             = 1;
			foreach ( $team->roster as $player ) {
				$teamplayer            = get_player( $player );
				$team->players [ $i ]  = $teamplayer;
				$team->player[ $i ]    = isset( $teamplayer->fullname ) ? $teamplayer->fullname : '';
				$team->player_id[ $i ] = $player;
				++$i;
			}
		}
		$team->is_withdrawn = false;
		if ( 'W' === $team->league_status ) {
			$team->is_withdrawn = true;
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
	 * Gets matches from database
	 *
	 * @param array $query_args query arguments.
	 * @return object
	 */
	public function get_matches( $query_args ) {
		global $wpdb;
		$old_query_args = $this->match_query_args;
		// set query args.
		foreach ( $query_args as $key => $value ) {
			$this->set_match_query_arg( $key, $value, true );
		}
		$limit            = $this->match_query_args['limit'];
		$season           = $this->match_query_args['season'];
		$final            = $this->match_query_args['final'];
		$match_day        = $this->match_query_args['match_day'];
		$time             = $this->match_query_args['time'];
		$match_date       = $this->match_query_args['match_date'];
		$count            = $this->match_query_args['count'];
		$orderby          = $this->match_query_args['orderby'];
		$standingstable   = $this->match_query_args['standingstable'];
		$cache            = $this->match_query_args['cache'];
		$team_id          = $this->match_query_args['team_id'];
		$home_team        = $this->match_query_args['home_team'];
		$away_team        = $this->match_query_args['away_team'];
		$team_pair        = $this->match_query_args['team_pair'];
		$winner_id        = $this->match_query_args['winner_id'];
		$loser_id         = $this->match_query_args['loser_id'];
		$home_points      = $this->match_query_args['home_points'];
		$away_points      = $this->match_query_args['away_points'];
		$reset_limit      = $this->match_query_args['reset_limit'];
		$reset_query_args = $this->match_query_args['reset_query_args'];
		$confirmed        = $this->match_query_args['confirmed'];
		$leg              = $this->match_query_args['leg'];
		$player           = $this->match_query_args['player'];
		$withdrawn        = $this->match_query_args['withdrawn'];
		$club             = $this->match_query_args['affiliatedClub'];
		$pending          = $this->match_query_args['pending'];

		$matches = array();
		$args    = array( intval( $this->id ) );
		if ( $count ) {
			$sql_start = "SELECT COUNT(*) FROM {$wpdb->racketmanager_matches} m";
			$sql       = ' WHERE m.`league_id` = %d';
		} else {
			$sql_start = "SELECT  DISTINCT m.`id` FROM {$wpdb->racketmanager_matches} m";
			$sql       = ' WHERE m.`league_id` = %d';
		}

		// disable limit for championship mode.
		if ( $this->is_championship ) {
			$limit = false;
		}
		if ( '' === $season ) {
			$sql   .= ' AND m.`season` = %s';
			$args[] = $this->current_season['name'];
		} elseif ( 'any' === $season ) {
			$sql .= " AND m.`season` != ''";
		} elseif ( $this->season_exists( htmlspecialchars( $season ) ) ) {
			$sql   .= ' AND m.`season` = %s';
			$args[] = htmlspecialchars( $season );
		} else {
			return $matches;
		}
		if ( $final ) {
			if ( 'all' !== $final ) {
				if ( $this->final_exists( htmlspecialchars( wp_strip_all_tags( $final ) ) ) ) {
					$sql      .= ' AND m.`final` = %s';
					$args[]    = htmlspecialchars( wp_strip_all_tags( $final ) );
					$match_day = -1;
					$limit     = 0;
				} else {
					$sql .= " AND m.`final` != ''";
				}
			}
		} else {
			$sql .= " AND ( m.`final` = '' OR m.`final` IS NULL )";
		}

		if ( $team_id ) {
			$sql   .= ' AND (`home_team` = %d OR `away_team` = %d)';
			$args[] = $team_id;
			$args[] = $team_id;
		} elseif ( count( $team_pair ) === 2 ) {
			$sql   .= " AND ( (`home_team` = %d AND `away_team` = %d' OR (`home_team` = %d AND `away_team` = %d ) )";
			$args[] = intval( $team_pair[0] );
			$args[] = intval( $team_pair[1] );
			$args[] = intval( $team_pair[1] );
			$args[] = intval( $team_pair[0] );
		} else {
			if ( ! empty( $home_team ) ) {
				$sql   .= ' AND `home_team` = %d';
				$args[] = $home_team;
			}
			if ( ! empty( $away_team ) ) {
				$sql   .= ' AND `away_team` = %d';
				$args[] = $away_team;
			}
		}
		if ( $match_day && intval( $match_day ) > 0 ) {
			if ( $standingstable ) {
				$sql   .= ' AND `match_day` <=%d';
				$args[] = $match_day;
			} else {
				$sql   .= ' AND `match_day` = %d';
				$args[] = $match_day;
			}
		}

		// get only finished matches with score for time 'latest'.
		if ( 'latest' === $time ) {
			$home_points = false;
			$away_points = false;
			$sql        .= " AND (m.`home_points` != '' OR m.`away_points` != '')";
		}

		if ( '' !== $home_points ) {
			if ( 'null' === $home_points ) {
				$sql .= ' AND m.`home_points` IS NULL';
			} elseif ( 'not_null' === $home_points ) {
				$sql .= ' AND m.`home_points` IS NOT NULL';
			} elseif ( 'not_empty' === $home_points ) {
				$sql .= " AND m.`home_points` != ''";
			}
		}
		if ( $away_points ) {
			if ( 'null' === $away_points ) {
				$sql .= ' AND m.`away_points` IS NULL';
			} elseif ( 'not_null' === $away_points ) {
				$sql .= ' AND m.`away_points` IS NOT NULL';
			} elseif ( 'not_empty' === $away_points ) {
				$sql .= " AND m.`away_points` != ''";
			}
		}

		if ( $winner_id ) {
			$sql   .= ' AND m.`winner_id` = %d';
			$args[] = $winner_id;
		}
		if ( $loser_id ) {
			$sql   .= ' AND m.`loser_id` = %d';
			$args[] = $loser_id;
		}

		if ( 'next' === $time ) {
			$sql .= ' AND TIMESTAMPDIFF(MINUTE, NOW(), m.`date`) >= 0';
		} elseif ( 'prev' === $time || 'latest' === $time ) {
			$sql .= ' AND TIMESTAMPDIFF(MINUTE, NOW(), m.`date`) < 0';
		} elseif ( 'prev1' === $time ) {
			$sql .= ' AND TIMESTAMPDIFF(MINUTE, NOW(), m.`date`) < 0) AND (m.`winner_id` != 0) ';
		} elseif ( 'today' === $time ) {
			$sql .= ' AND DATEDIFF(NOW(), m.`date`) = 0';
		} elseif ( 'day' === $time ) {
			$sql .= " AND DATEDIFF('" . htmlspecialchars( wp_strip_all_tags( $match_date ) ) . "', m.`date`) = 0";
		}
		if ( $confirmed ) {
			$sql .= " AND m.`confirmed` = 'Y'";
		}
		if ( $leg ) {
			$sql   .= ' AND m.`leg` = %s';
			$args[] = $leg;
		}
		if ( $player ) {
			$sql_start .= " ,{$wpdb->racketmanager_rubbers} r, {$wpdb->racketmanager_rubber_players} rp";
			$sql       .= " AND m.`id` = r.`match_id` AND r.`id` = rp.`rubber_id` AND `player_id` = '$player'";
		}
		// Force ordering by date ascending if next matches are queried.
		if ( 'next' === $time ) {
			$orderby['date'] = 'ASC';
		}
		// Force ordering by date descending if previous/latest matches are queried.
		if ( 'prev' === $time || 'latest' === $time ) {
			$orderby['date'] = 'DESC';
		}
		if ( ! $withdrawn ) {
			$sql_start .= " ,{$wpdb->racketmanager_table} t1, {$wpdb->racketmanager_table} t2";
			$sql       .= " AND `home_team` = t1.`team_id` AND t1.`league_id` = m.`league_id` and t1.`season` = m.`season` AND t1.`status` != 'W'";
			$sql       .= " AND `away_team` = t2.`team_id` AND t2.`league_id` = m.`league_id` and t2.`season` = m.`season` AND t2.`status` != 'W'";
		}
		if ( $club ) {
			$sql .= " AND (`home_team` IN (SELECT `id` FROM {$wpdb->racketmanager_teams} WHERE `affiliatedclub` = " . $club . ") OR `away_team` IN (SELECT `id` FROM {$wpdb->racketmanager_teams} WHERE `affiliatedclub` = " . $club . '))';
		}
		if ( $pending ) {
			$sql .= ' AND m.winner_id = 0';
		}
		// get number of matches.
		if ( $count ) {
			$this->set_match_query_arg( 'count', false );
			$sql = $sql_start . $sql;
			$sql = $wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql,
				$args
			);
			// Use WordPress cache for counting matches.
			$matches = wp_cache_get( md5( $sql ), 'num_matches' );
			if ( ! $matches || false === $cache ) {
				$matches = intval(
					$wpdb->get_var(
						// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
						$sql
					)
				); // db call ok.
				wp_cache_set( md5( $sql ), $matches, 'num_matches' );
			}
		} else {
			$orderby_string = '';
			$i              = 0;
			foreach ( $orderby as $order => $direction ) {
				if ( ! in_array( $direction, array( 'DESC', 'ASC', 'desc', 'asc' ), true ) ) {
					$direction = 'ASC';
				}
				$orderby_string .= 'm.`' . $order . '` ' . $direction;
				if ( $i < ( count( $orderby ) - 1 ) ) {
					$orderby_string .= ',';
				}
				++$i;
			}
			$order  = $orderby_string;
			$offset = intval( $limit > 0 ) ? ( $this->get_current_page() - 1 ) * $limit : 0;
			$sql   .= " ORDER BY $order";
			if ( intval( $limit > 0 ) ) {
				$sql .= ' LIMIT ' . intval( $offset ) . ',' . intval( $limit ) . '';
			}
			$sql = $sql_start . $sql;
			$sql = $wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql,
				$args
			);
			$matches = wp_cache_get( md5( $sql ), 'matches' );
			if ( ! $matches || false === $cache ) {
				$matches = $wpdb->get_results(
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					$sql
				); // db call ok.
				wp_cache_set( md5( $sql ), $matches, 'matches' );
			}

			$class = '';
			foreach ( $matches as $i => $match ) {
				$match        = get_match( $match->id );
				$class        = ( 'alternate' === $class ) ? '' : 'alternate';
				$match->class = $class;
				if ( $player ) {
					$match->rubbers = $match->get_rubbers( $player );
				}

				$matches[ $i ] = $match;
			}
			if ( 1 === $limit && $matches ) {
				$matches = $matches[0];
			}
		}

		// reset match limit.
		if ( true === $reset_limit ) {
			$this->set_match_query_arg( 'limit', $old_query_args['limit'] );
			$this->set_match_query_arg( 'reset_limit', false );
		}

		// reset match query args.
		if ( true === $reset_query_args ) {
			foreach ( $old_query_args as $key => $query_arg ) {
				$this->set_match_query_arg( $key, $query_arg, true );
			}
			$this->set_match_query_arg( 'reset_query_args', false );
		}

		if ( true !== $count ) {
			$this->matches = $matches;
		} elseif ( true === $count ) {
			$this->num_matches = $matches;
		}

		return $matches;
	}

	/**
	 * Get standings table
	 *
	 * @param array  $teams teams.
	 * @param int    $match_day match day.
	 * @param string $mode mode.
	 * @return array the ranked teams
	 */
	public function get_standings( $teams, $match_day, $mode = 'all' ) {
		// hide status as it's meaningless.
		$this->standings['status'] = 0;

		// set basic match query args.
		$this->set_match_query_arg( 'standingstable', true );
		$this->set_match_query_arg( 'match_day', $match_day );
		$this->set_match_query_arg( 'final', '' );
		$this->set_match_query_arg( 'limit', false );

		$this->set_match_query_arg( 'mode', $mode );

		foreach ( $teams as $i => $team ) {
			$team       = get_league_team( $team );
			$match_args = array();

			// get only home matches.
			if ( 'home' === $mode ) {
				$match_args['home_team'] = $team->id;
			}
			// get only away matches.
			if ( 'away' === $mode ) {
				$match_args['away_team'] = $team->id;
			}
			// get all matches for given team.
			if ( 'all' === $mode ) {
				$match_args['team_id'] = $team->id;
			}
			// get matches up to given match day.
			if ( $match_day ) {
				$match_args['match_day'] = $match_day;
			}
			$match_args['confirmed'] = true;

			// initialize team standings data.
			$team->done_matches  = 0;
			$team->won_matches   = 0;
			$team->draw_matches  = 0;
			$team->lost_matches  = 0;
			$team->points_plus   = 0;
			$team->points_minus  = 0;
			$team->points2_plus  = 0;
			$team->points2_minus = 0;

			// get matches.
			$matches = $this->get_matches( $match_args );
			foreach ( $matches as $match ) {
				if ( '' !== $match->home_points && '' !== $match->away_points ) {
					$team->done_matches += 1;
				}
				if ( $match->winner_id === $team->id ) {
					$team->won_matches += 1;
				}
				if ( $match->loser_id === $team->id ) {
					$team->lost_matches += 1;
				}
				if ( -1 === $match->winner_id && -1 === $match->loser_id ) {
					$team->draw_matches += 1;
				}
			}

			$team->points       = $this->calculate_points( $team, $matches );
			$team->points_plus  = $team->points['plus'];
			$team->points_minus = $team->points['minus'];

			$team->points2           = $this->calculate_secondary_points( $team, $matches );
			$team->custom['points2'] = $team->points2;
			$team->points2_plus      = $team->points2['plus'];
			$team->points2_minus     = $team->points2['minus'];

			$team->diff           = $team->points2_plus - $team->points2_minus;
			$team->custom['diff'] = $team->diff;
			$team->win_percent();

			$custom = $this->get_standings_data( $team->id, $team->custom, $matches );
			foreach ( $custom as $key => $value ) {
				$team->{$key} = $value;
			}
			$teams[ $i ] = $team;
		}

		/*
		* rank teams.
		*/
		$teams = $this->rank_teams( $teams );
		$teams = $this->get_ranking( $teams );

		return $teams;
	}

	/**
	 * Get standings selection
	 */
	public function get_standings_selection() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$selected = isset( $_GET['standingstable'] ) ? sanitize_text_field( wp_unslash( $_GET['standingstable'] ) ) : '';

		$options = array(
			'all'  => __( 'Current Table', 'racketmanager' ),
			'home' => __( 'Hometable', 'racketmanager' ),
			'away' => __( 'Awaytable', 'racketmanager' ),
		);
		$out     = "<select size='1' name='standingstable'>";
		foreach ( $options as $value => $label ) {
			$out .= "<option value='" . $value . "'" . selected( $value, $selected, false ) . '>' . $label . '</option>';
		}
		for ( $day = 1; $day <= $this->current_season['num_match_days']; $day++ ) {
			/* translators: %d: match day */
			$out .= "<option value='match_day-" . $day . "'" . selected( 'match_day-' . $day, $selected, false ) . '>' . sprintf( __( '%d. Match Day', 'racketmanager' ), $day ) . '</option>';
		}
		$out .= '</select>';

		return $out;
	}

	/**
	 * Set finals flag for championship mode
	 *
	 * @param boolean $is_final final indicator.
	 */
	public function set_finals( $is_final = true ) {
		$this->is_final = $is_final;
	}

	/**
	 * Get point rule depending on selection.
	 *
	 * @param int $rule rule.
	 * @return array of points
	 */
	public function get_point_rule( $rule = false ) {
		if ( ! $rule ) {
			$rule = $this->point_rule;
		}
		$rule = maybe_unserialize( $rule );

		// Manual point rule.
		if ( is_array( $rule ) ) {
			return $rule;
		} else {
			$point_rules = array();
			// One point rule.
			$point_rules['one'] = array(
				'forwin'  => 1,
				'fordraw' => 0,
				'forloss' => 0,
			);
			// Two point rule.
			$point_rules['two'] = array(
				'forwin'  => 2,
				'fordraw' => 1,
				'forloss' => 0,
			);
			// Three-point rule.
			$point_rules['three'] = array(
				'forwin'  => 3,
				'fordraw' => 1,
				'forloss' => 0,
			);
			// Score. One point for each scored goal.
			$point_rules['score'] = 'score';

			/**
			 * Fired when point rules are retrieved
			 *
			 * @param array $point_rules
			 * @return array
			 * @category wp-filter
			 */
			$point_rules = apply_filters( 'racketmanager_point_rules', $point_rules );

			return $point_rules[ $rule ];
		}
	}

	/**
	 * Get number of teams for specific league
	 *
	 * @param boolean $total total teams or teams per page.
	 */
	public function set_num_teams( $total = false ) {
		global $wpdb;

		if ( true === $total ) {
			// get total number of teams.
			$sql         = $wpdb->prepare(
				"SELECT COUNT(ID) FROM {$wpdb->racketmanager_table} WHERE `league_id` = %d AND `season`= %s",
				$this->id,
				$this->current_season['name']
			);
			$count_teams = wp_cache_get( md5( $sql ), 'teams' );
			if ( ! $count_teams ) {
				$count_teams = $wpdb->get_var(
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					$sql
				); // db call ok.
				wp_cache_set( md5( $sql ), $count_teams, 'teams' );
			}
			$this->num_teams_total = $count_teams;
		} else {
			$this->num_teams        = $this->num_teams_total;
			$this->pagination_teams = $this->get_page_links( 'teams' );
		}
	}

	/**
	 * Gets number of matches
	 *
	 * @param boolean $total total matches or matches per page.
	 */
	public function set_num_matches( $total = false ) {
		if ( true === $total ) {
			$this->num_matches_total = $this->get_matches(
				array(
					'count'            => true,
					'season'           => '',
					'reset_query_args' => true,
				)
			);
		} else {
			$this->get_matches(
				array(
					'limit'            => 0,
					'count'            => true,
					'season'           => '',
					'reset_query_args' => true,
				)
			);
			$this->pagination_matches = $this->get_page_links( 'matches' );
		}
	}

	/**
	 * Get specific field for crosstable
	 *
	 * @param int $team_id team.
	 * @param int $opponent_id opponent.
	 * @return string
	 */
	public function get_crosstable_field( $team_id, $opponent_id ) {
		$score = '&nbsp;';
		if ( $team_id === $opponent_id ) {
			$score = '&nbsp;';
		} else {
			$matches   = $this->get_matches(
				array(
					'home_team'        => $team_id,
					'away_team'        => $opponent_id,
					'match_day'        => -1,
					'limit'            => false,
					'reset_query_args' => true,
				)
			);
			$home_away = isset( $this->current_season['homeAway'] ) ? $this->current_season['homeAway'] : 'true';
			if ( 'true' === $home_away ) {
				if ( $matches ) {
					$score = '';
					foreach ( $matches as $match ) {
						$score .= $this->get_score( $team_id, $opponent_id, $match, $home_away );
					}
				} else {
					$score = '&nbsp;';
				}
			} elseif ( $matches ) {
				$match = $matches[0];
				$score = $this->get_score( $team_id, $opponent_id, $match, $home_away );
			} else {
				$matches = $this->get_matches(
					array(
						'home_team'        => $opponent_id,
						'away_team'        => $team_id,
						'match_day'        => -1,
						'limit'            => false,
						'reset_query_args' => true,
					)
				);
				if ( $matches ) {
					$match = $matches[0];
					$score = $this->get_score( $team_id, $opponent_id, $match, $home_away );
				} else {
					$score = '&nbsp;';
				}
			}
		}

		return $score;
	}

	/**
	 * Get score for specific field of crosstable
	 *
	 * @param int    $team_id team.
	 * @param int    $opponent_id opponent.
	 * @param object $match match.
	 * @param string $home_away home & away matches.
	 * @return string
	 */
	public function get_score( $team_id, $opponent_id, $match, $home_away ) {

		// unplayed match.
		if ( ! $match || ( null === $match->home_points && null === $match->away_points ) ) {
			$date      = ( '0000-00-00' === substr( $match->date, 0, 10 ) ) ? 'N/A' : mysql2date( 'D d/m/Y', $match->date );
			$match_day = isset( $match->match_day ) ? __( 'Match Day', 'racketmanager' ) . ' ' . $match->match_day : '';
			if ( 'true' === $home_away ) {
				$out = "<span class='unplayedMatch'>" . $match_day . '<br/>' . $date . '</span><br/>';
			} else {
				$out = "<span class='unplayedMatch'>&nbsp;</span>";
			}
			// match at home.
		} elseif ( $team_id === $match->home_team ) {
			$score_team_1 = $match->home_points;
			$score_team_2 = $match->away_points;
			$score        = sprintf( '%g - %g', $match->home_points, $match->away_points );
			// match away.
		} elseif ( $opponent_id === $match->home_team ) {
			$score_team_1 = $match->away_points;
			$score_team_2 = $match->home_points;
			$score        = sprintf( '%g - %g', $match->away_points, $match->home_points );
		}
		if ( isset( $score_team_1 ) ) {
			if ( $team_id === $match->winner_id ) {
				$score_class = 'winner';
			} elseif ( $team_id === $match->loser_id ) {
				$score_class = 'loser';
			} elseif ( '-1' === $match->winner_id ) {
				$score_class = 'tie';
			}
			if ( 'true' === $home_away ) {
				$link_title = __( 'Match Day', 'racketmanager' ) . ' ' . $match->match_day;
			} else {
				$link_title = '';
			}
			ob_start();
			?>
			<a href="<?php echo esc_html( $match->link ); ?>"
				<span class="score <?php echo esc_attr( $score_class ); ?>" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo esc_attr( $link_title ); ?>">
					<span class="is-team-1"><?php echo esc_html( sprintf( '%g', $score_team_1 ) ); ?></span>
					<?php
					if ( 'true' === $home_away ) {
						?>
						<span class="score-separator">-</span>
						<span class="is-team-2"><?php echo esc_html( sprintf( '%g', $score_team_2 ) ); ?></span>
						<?php
					}
					?>
				</span>
			</a>
			<?php
			$out = ob_get_contents();
			ob_end_clean();
		}

		return $out;
	}

	/**
	 * Default ranking function. Re-defined in sports-specific class
	 * 1) Primary points DESC
	 * 2) Games Allowed ASC
	 * 3) Done Matches ASC
	 *
	 * @param array $teams team.
	 * @return array
	 */
	protected function rank_teams( $teams ) {
		$points        = array();
		$done          = array();
		$games_allowed = array();
		foreach ( $teams as $key => $row ) {
			$points[ $key ]        = $row->points['plus'];
			$done[ $key ]          = $row->done;
			$games_allowed[ $key ] = $row->games_allowed;
		}

		array_multisort( $points, SORT_DESC, $games_allowed, SORT_ASC, $done, SORT_ASC, $teams );
		return $teams;
	}

	/**
	 * Set match day selection in shortcode
	 *
	 * @param string $match_day_selection match day selection.
	 * @param int    $match_day match day.
	 */
	public function set_match_day_selection( $match_day_selection, $match_day = -1 ) {
		if ( intval( $match_day ) > 0 ) {
			$this->show_match_day_selection = false;
		} elseif ( true === $match_day_selection ) {
			$this->show_match_day_selection = true;
		} elseif ( false === $match_day_selection ) {
			$this->show_match_day_selection = false;
		}
	}

	/**
	 * Set team selection in shortcode
	 *
	 * @param string $team_selection team selection.
	 * @param int    $team_id team.
	 */
	public function set_team_selection( $team_selection, $team_id = 0 ) {
		if ( intval( $team_id ) > 0 ) {
			$this->show_team_selection = false;
		} else {
			if ( true === $team_selection ) {
				$this->show_team_selection = true;
			}
			if ( false === $team_selection ) {
				$this->show_team_selection = false;
			}
		}
	}

	/**
	 * Set matches selection in shortcode.
	 *
	 * @param string $show_match_day_selection show match day selection.
	 * @param int    $match_day match day.
	 * @param string $show_team_selection show team selection.
	 * @param int    $team_id team.
	 */
	public function set_matches_selection( $show_match_day_selection, $match_day, $show_team_selection, $team_id ) {
		$this->set_match_day_selection( $show_match_day_selection, $match_day );
		$this->set_team_selection( $show_team_selection, $team_id );

		if ( ( $this->show_match_day_selection || $this->show_team_selection ) && ! $this->is_championship ) {
			$this->show_matches_selection = true;
		} else {
			$this->show_matches_selection = false;
		}
	}

	/**
	 * Set tab in league/archive shortcodes
	 *
	 * @param boolean $is_archive is this an archive.
	 */
	public function set_tab( $is_archive = false ) {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET[ 'match_day_' . $this->id ] ) || isset( $_GET[ 'team_id_' . $this->id ] ) || isset( $_GET[ 'match_paged_' . $this->id ] ) ) {
			$this->archive_tab = 2;
		}
		if ( isset( $_GET[ 'team_' . $this->id ] ) ) {
			$this->archive_tab = 3;
		}
		if ( isset( $_GET[ 'match_' . $this->id ] ) ) {
			$this->archive_tab = 2;
		}
		if ( isset( $_GET[ 'team_paged_' . $this->id ] ) || isset( $_GET[ 'show_' . $this->teamroster ] ) || isset( $_GET[ 'paged_' . $this->teamroster ] ) ) {
			$this->archive_tab = 3;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$this->is_archive = $is_archive;
	}

	/**
	 * Set template in league/archive shortcodes
	 *
	 * @param string $key key.
	 * @param string $template template.
	 */
	public function set_template( $key, $template ) {
		$this->templates[ $key ] = $template;
	}

	/**
	 * Set all templates in league/archive shortcodes
	 *
	 * @param array $templates An associative array of templatey key => template associations.
	 */
	public function set_templates( $templates ) {
		foreach ( $templates as $key => $template ) {
			$this->set_template( $key, $template );
		}
	}

	/**
	 * Check if season exists
	 *
	 * @param string $season season.
	 * @return boolean
	 */
	private function season_exists( $season ) {
		if ( is_array( $this->seasons ) ) {
			return in_array( intval( $season ), array_keys( $this->seasons ), true );
		} else {
			return false;
		}
	}

	/**
	 * Check if group exists
	 *
	 * @param string $group group.
	 * @return boolean
	 */
	private function group_exists( $group ) {
		if ( isset( $this->groups ) && is_string( $this->groups ) ) {
			$groups = explode( ';', $this->groups );
			if ( in_array( $group, $groups, true ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if final exists
	 *
	 * @param string $final_round final.
	 * @return boolean
	 */
	private function final_exists( $final_round ) {
		if ( ! $this->championship instanceof Racketmanager_Championship ) {
			return false;
		}
		$finals = $this->championship->get_finals();
		return in_array( $final_round, array_keys( $finals ), true );
	}

	/**
	 * Check if database column exists
	 *
	 * @param string $table table name.
	 * @param string $column column name.
	 * @return boolean
	 */
	public function database_column_exists( $table, $column ) {
		global $wpdb;

		if ( 'teams' === $table ) {
			$table = $wpdb->racketmanager_teams;
		} elseif ( 'table' === $table ) {
			$table = $wpdb->racketmanager_table;
		} elseif ( 'matches' === $table ) {
			$table = $wpdb->racketmanager_matches;
		} elseif ( 'rubbers' === $table ) {
			$table = $wpdb->racketmanager_rubbers;
		} elseif ( 'leagues' === $table ) {
			$table = $wpdb->racketmanager;
		} elseif ( 'seasons' === $table ) {
			$table = $wpdb->racketmanager_seasons;
		} elseif ( 'competititons' === $table ) {
			$table = $wpdb->racketmanager_competititons;
		} else {
			return false;
		}
		$sql = $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SHOW COLUMNS FROM {$table} LIKE %s",
			$column
		);

		$res = wp_cache_get( md5( $sql ), 'racketmanager' );

		if ( ! $res ) {
			$res = $wpdb->query(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql
			); // db call ok.
			wp_cache_set( md5( $sql ), $res, 'racketmanager' );
		}
		$res = ( 1 === $res ) ? true : false;
		return $res;
	}

	/**
	 * Default ranking function. Re-defined in sports-specific class
	 * 1) Primary points DESC
	 * 2) Done Matches ASC
	 *
	 * @param string $season season.
	 */
	public function set_teams_rank( $season = false ) {
		if ( ! isset( $season ) ) {
			$season = $this->current_season;
		}
		$season = is_array( $season ) ? $season['name'] : $season;

		// rank Teams in groups.
		$groups = ! empty( $this->groups ) ? explode( ';', $this->groups ) : array( '0' );
		foreach ( $groups as $group ) {
			$team_args = array( 'season' => $season );
			if ( ! empty( $group ) ) {
				$team_args['group'] = $group;
			}
			$teams = $this->get_league_teams( $team_args );

			if ( ! empty( $teams ) && 'auto' === $this->event->competition->team_ranking ) {
				$teams = $this->rank_teams( $teams );
				$teams = $this->get_ranking( $teams );
				$this->update_ranking( $teams );
			}
		}
	}
	/**
	 * Get team rank function
	 *
	 * @param int    $team team id.
	 * @param string $season season.
	 * @return int team ranking.
	 */
	public function get_rank( $team, $season ) {
		global $wpdb;
		$sql       = $wpdb->prepare(
			"SELECT `rank` FROM {$wpdb->racketmanager_table} WHERE `league_id` = %d AND `season` = %s AND `team_id` = %d",
			$this->id,
			$season,
			intval( $team ),
		);
		$team_rank = wp_cache_get( md5( $sql ), 'team_rank' );
		if ( ! $team_rank ) {
			$team_rank = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql,
			);
			wp_cache_set( md5( $sql ), $team_rank, 'team_rank' );
		}
		if ( $team_rank ) {
			return $team_rank->rank;
		} else {
			return null;
		}
	}
	/**
	 * Get team league table function
	 *
	 * @param int    $team team id.
	 * @param string $season season.
	 * @return int team ranking.
	 */
	public function get_status( $team, $season ) {
		global $wpdb;
		$sql         = $wpdb->prepare(
			"SELECT `status` FROM {$wpdb->racketmanager_table} WHERE `league_id` = %d AND `season` = %s AND `team_id` = %d",
			$this->id,
			$season,
			intval( $team ),
		);
		$team_status = wp_cache_get( md5( $sql ), 'team_status' );
		if ( ! $team_status ) {
			$team_status = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql,
			);
			wp_cache_set( md5( $sql ), $team_status, 'team_status' );
		}
		if ( $team_status ) {
			return $team_status->status;
		} else {
			return null;
		}
	}
	/**
	 * Gets ranking of teams
	 *
	 * @param array $teams teams.
	 * @return array of parameters
	 */
	public function get_ranking( $teams ) {
		$rank      = 1;
		$incr      = 1;
		$new_teams = array();
		foreach ( $teams as $key => $team ) {
			$team->old_rank = $team->rank;

			if ( $key > 0 ) {
				if ( ! $this->is_championship && ( isset( $team->points ) && $this->is_tie( $team, $teams[ $key - 1 ] ) ) ) {
					++$incr;
				} else {
					$rank += $incr;
					$incr  = 1;
				}
			}

			$team->rank = $rank;
			if ( 'W' !== $team->status ) {
				$team->status = $this->get_team_status( $team, $rank );
			}

			$new_teams[ $key ] = $team;
		}

		$new_teams = $this->tiebreak( $new_teams );

		return $new_teams;
	}

	/**
	 * Get team status depending on previous rank
	 *
	 * @param League_Team $team team.
	 * @param int         $rank rank.
	 * @return string
	 */
	private function get_team_status( $team, $rank ) {
		if ( 0 !== $team->old_rank && $team->done_matches > 1 ) {
			if ( intval( $team->old_rank ) === $rank ) {
				$status = '=';
			} elseif ( $rank < $team->old_rank ) {
				$status = '+';
			} else {
				$status = '-';
			}
		} else {
			$status = '';
		}
		return $status;
	}

	/**
	 * Update Team Rank and status
	 *
	 * @param object $teams teams to be ranked.
	 */
	public function update_ranking( $teams ) {
		global $wpdb;
		foreach ( $teams as $team ) {
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->racketmanager_table} SET `rank` = %d, `status` = %s WHERE `id` = %d",
					$team->rank,
					$team->status,
					$team->table_id
				)
			); // db call ok.
			wp_cache_delete( $team->table_id, 'leagueteam' );
			wp_cache_delete( $team->league_id, 'leaguetable' );
		}
	}

	/**
	 * Display Season dropdown
	 *
	 * @param string $season selected season.
	 */
	public function get_season_dropdown( $season = '' ) {
		ob_start();
		?>
		<select size='1' name='season' id='season' class="form-select" onChange='Racketmanager.getMatchDropdown(<?php echo esc_html( $this->id ); ?>, this.value)'>
			<option value="0"><?php esc_html_e( 'Choose Season', 'racketmanager' ); ?></option>
			<?php
			foreach ( array_reverse( $this->seasons ) as $season_entry ) {
				?>
				<option value=<?php echo esc_html( $season_entry['name'] ); ?> <?php selected( $season, $season_entry['name'], false ); ?>><?php echo esc_html( $season_entry['name'] ); ?></option>
			<?php } ?>
		</select>
		<label for="season"><?php esc_html_e( 'Season', 'racketmanager' ); ?></label>
		<?php
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}

	/**
	 * Display match dropdown
	 *
	 * @param int $match_id selected match ID.
	 * @return string
	 */
	public function get_match_dropdown( $match_id = 0 ) {
		$matches = $this->get_matches(
			array(
				'limit'            => false,
				'match_day'        => -1,
				'reset_query_args' => true,
			)
		);
		ob_start();
		?>

		<select class="form-select" size="1" name="match_id" id="match_id" class="alignleft">
			<option value="0"><?php esc_html_e( 'Choose Match', 'racketmanager' ); ?></option>
			<?php
			foreach ( $matches as $match ) {
				?>
				<option value="<?php echo esc_html( $match->id ); ?>" <?php echo selected( $match_id, $match->id, false ); ?>><?php echo esc_html( $match->get_title( false ) ); ?></option>
			<?php } ?>
		</select>
		<label for="match_id"><?php esc_html_e( 'Match', 'racketmanager' ); ?></label>
		<?php
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}

	/**
	 * =======================
	 * Administation section
	 * =======================
	 */

	/**
	 * Update standings manually
	 *
	 * @param array $teams teams.
	 * @param array $points points.
	 * @param array $matches matches.
	 * @param array $custom custom.
	 */
	public function save_standings_manually( $teams, $points, $matches, $custom ) {
		global $wpdb;

		$season = $this->current_season['name'];

		foreach ( array_keys( $teams ) as $id ) {
			$points2_plus  = isset( $custom[ $id ]['points2']['plus'] ) ? $custom[ $id ]['points2']['plus'] : 0;
			$points2_minus = isset( $custom[ $id ]['points2']['minus'] ) ? $custom[ $id ]['points2']['minus'] : 0;
			if ( ! is_numeric( $points2_plus ) ) {
				$points2_plus = 0;
			}
			if ( ! is_numeric( $points2_minus ) ) {
				$points2_minus = 0;
			}
			$diff = $points2_plus - $points2_minus;

			$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"UPDATE {$wpdb->racketmanager_table} SET `points_plus` = %d, `points_minus` = %d, `points2_plus` = %d, `points2_minus` = %d, `done_matches` = %d, `won_matches` = %d, `draw_matches` = %d, `lost_matches` = %d, `diff` = %d, `add_points` = %d WHERE `team_id` = %d and `league_id` = %d AND `season` = %s",
					$points['points_plus'][ $id ],
					$points['points_minus'][ $id ],
					$points2_plus,
					$points2_minus,
					$matches['num_done_matches'][ $id ],
					$matches['num_won_matches'][ $id ],
					$matches['num_draw_matches'][ $id ],
					$matches['num_lost_matches'][ $id ],
					$diff,
					$points['add_points'][ $id ],
					$id,
					$this->id,
					$season
				)
			);
			wp_cache_flush();
		}

		// Update Teams Rank and Status if not set to manual ranking.
		if ( 'manual' !== $this->event->competition->team_ranking ) {
			$this->set_teams_rank( $season );
		}
	}

	/**
	 * Update match results
	 *
	 * @param array  $matches matches.
	 * @param array  $home_points home points.
	 * @param array  $away_points away points.
	 * @param array  $custom custom.
	 * @param array  $season season.
	 * @param string $final_round final indicator.
	 * @param string $confirmed confirmed status.
	 * @return int
	 */
	public function update_match_results( $matches, $home_points, $away_points, $custom, $season, $final_round = false, $confirmed = 'Y' ) {
		$num_matches = 0;
		if ( ! empty( $matches ) ) {
			$matches_updated = array();
			foreach ( $matches as $match_id ) {
				$match         = get_match( $match_id );
				$c             = isset( $custom[ $match_id ] ) ? $custom[ $match_id ] : array();
				$match_updated = $match->update_result( $home_points[ $match_id ], $away_points[ $match_id ], $c, $confirmed, $match->status );
				if ( $match_updated ) {
					++$num_matches;
					if ( '-1' !== $match->loser_id ) {
						$matches_updated[] = $match;
					}
				}
			}
		}

		if ( $num_matches > 0 ) {
			if ( ! $final_round ) {
				$this->update_standings( $season );
			}
		}
		return $num_matches;
	}
	/**
	 * Update standings function
	 *
	 * @param string $season season.
	 */
	private function update_standings( $season ) {
		// update Standings for each team.
		$league_teams = $this->get_league_teams(
			array(
				'season' => $season,
				'cache'  => false,
			)
		);
		foreach ( $league_teams as $i => $league_team ) {
			$league_teams[ $i ] = $this->save_standings( $league_team );
		}
		// Update Teams Rank and Status.
		$this->set_teams_rank( $season );
	}
	/**
	 * Update points for given team
	 *
	 * @param int $league_team team.
	 */
	private function save_standings( $league_team ) {
		global $wpdb;

		if ( 'manual' !== $this->point_rule ) {
			$league_team = get_league_team( $league_team );
			$league_team->get_num_done_matches();
			$league_team->get_num_won_matches();
			$league_team->get_num_draw_matches();
			$league_team->get_num_lost_matches();

			$league_team->points            = $this->calculate_points( $league_team );
			$league_team->points2           = $this->calculate_secondary_points( $league_team );
			$league_team->custom['points2'] = $league_team->points2;
			$league_team->diff              = $league_team->points2['plus'] - $league_team->points2['minus'];

			if ( ! isset( $league_team->points2['plus'] ) && ! isset( $league_team->points2['minus'] ) ) {
				$league_team->points2 = array(
					'plus'  => 0,
					'minus' => 0,
				);
			}
			// get custom team standings data.
			$league_team->custom = $this->get_standings_data( $league_team->id, $league_team->custom );

			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->racketmanager_table} SET `points_plus` = %f, `points_minus` = %f, `points2_plus` = %d, `points2_minus` = %d, `done_matches` = %d, `won_matches` = %d, `draw_matches` = %d, `lost_matches` = %d, `diff` = %d, `custom` = %s WHERE `team_id` = %d AND `league_id` = %d AND `season` = %s",
					$league_team->points['plus'],
					$league_team->points['minus'],
					$league_team->points2['plus'],
					$league_team->points2['minus'],
					$league_team->done_matches,
					$league_team->won_matches,
					$league_team->draw_matches,
					$league_team->lost_matches,
					$league_team->diff,
					maybe_serialize( $league_team->custom ),
					$league_team->id,
					$league_team->league_id,
					$league_team->season
				)
			); // db call ok.
			wp_cache_delete( $league_team->id, '$league_team' );
			wp_cache_delete( $league_team->league_id, 'leaguetable' );
		}

		return $league_team;
	}

	/**
	 * Calculate points for given team depending on point rule
	 *
	 * @param object $team team.
	 * @param array  $matches match.
	 * @return int
	 */
	private function calculate_points( $team, $matches = false ) {
		$season      = $team->season;
		$rule        = $this->get_point_rule( $this->point_rule );
		$points      = array(
			'plus'  => 0,
			'minus' => 0,
		);
		$team_points = 0;
		if ( ! $matches ) {
			$matches = $this->get_matches(
				array(
					'team_id'          => $team->id,
					'match_day'        => -1,
					'limit'            => false,
					'season'           => $season,
					'cache'            => false,
					'reset_query_args' => true,
				)
			);
		}
		if ( 'score' === $rule ) {
			foreach ( $matches as $match ) {
				if ( $team->id === $match->home_team ) {
					$points['plus']  += $match->home_points;
					$points['minus'] += $match->away_points;
				} else {
					$points['plus']  += $match->away_points;
					$points['minus'] += $match->home_points;
				}
			}
		} else {
			$forwin     = $rule['forwin'];
			$fordraw    = $rule['fordraw'];
			$forloss    = $rule['forloss'];
			$forscoring = isset( $rule['forscoring'] ) ? $rule['forscoring'] : 0;
			foreach ( $matches as $match ) {
				if ( $team->id === $match->home_team ) {
					$team_points += $match->home_points;
				} else {
					$team_points += $match->away_points;
				}
			}
			$points['plus']  = $team->won_matches * $forwin + $team->draw_matches * $fordraw + $team->lost_matches * $forloss + ( $team_points * ( isset( $forscoring ) ? $forscoring : 0 ) );
			$points['minus'] = $team->lost_matches * $forwin + $team->draw_matches * $fordraw + $team->won_matches * $forloss;
		}

		/**
		 * Fired when primary points are calculated
		 *
		 * @param array $points
		 * @param int $team_id
		 * @param mixed $rule
		 * @param array $matches
		 * @return array
		 * @category wp-filter
		 */
		$points = apply_filters( 'racketmanager_team_points_' . $this->sport, $points, $team->id, $rule, $matches );

		return $points;
	}

	/**
	 * Break ties
	 *
	 * @param array $teams teams.
	 * @return array
	 */
	private function tiebreak( $teams ) {
		// re-order teams by rank.
		foreach ( $teams as $key => $row ) {
			$rank[ $key ] = $row->rank;
		}
		array_multisort( $rank, SORT_ASC, $teams );

		return $teams;
	}

	/**
	 * ========================
	 * Sports customization section. The following functions can be overriden by sports class
	 * ========================
	 */

	/**
	 * Determine if two teams are tied based on
	 *
	 * 1) Primary points
	 * 2) Secondary point difference
	 * 3) Secondary points
	 * 4) Win Percentage
	 *
	 * @param League_Team $team1 team1.
	 * @param League_Team $team2 team2.
	 * @return boolean
	 */
	protected function is_tie( $team1, $team2 ) {
		// initialize results array.
		$res = array(
			'primary'    => false,
			'diff'       => false,
			'secondary'  => false,
			'winpercent' => false,
		);

		if ( $team1->points['plus'] === $team2->points['plus'] ) {
			$res['primary'] = true;
		}
		if ( $team1->diff === $team2->diff ) {
			$res['diff'] = true;
		}
		if ( $team1->points2['plus'] === $team2->points2['plus'] ) {
			$res['secondary'] = true;
		}
		if ( $team1->win_percent === $team2->win_percent ) {
			$res['winpercent'] = true;
		}
		// get unique results.
		$res = array_values( array_unique( $res ) );

		// more than one results, i.e. not tied.
		if ( count( $res ) > 1 ) {
			return false;
		}
		return $res[0];
	}

	/**
	 * Custom update results method
	 *
	 * @param object $match match.
	 * @return Match
	 */
	protected function update_results( $match ) {
		return $match;
	}

	/**
	 * Calculate secondary points
	 *
	 * @param object $team team.
	 * @param array  $matches (optional).
	 */
	protected function calculate_secondary_points( $team, $matches = false ) {
		$points = array(
			'plus'  => 0,
			'minus' => 0,
		);

		// general secondary points calculated from sum of primary points, e.g. soccer, handball, basketball.
		if ( isset( $this->fields_team['points2'] ) ) {
			if ( ! $matches ) {
				$matches = $this->get_matches(
					array(
						'team_id'          => $team->id,
						'match_day'        => -1,
						'limit'            => false,
						'cache'            => false,
						'reset_query_args' => true,
					)
				);
			}
			if ( $matches ) {
				foreach ( $matches as $match ) {
					$home_goals = $match->home_points;
					$away_goals = $match->away_points;

					if ( $match->home_team === $team->id ) {
						$points['plus']  += $home_goals;
						$points['minus'] += $away_goals;
					} else {
						$points['plus']  += $away_goals;
						$points['minus'] += $home_goals;
					}
				}
			}
		}

		return $points;
	}

	/**
	 * Get custom standings data
	 *
	 * @param string $team team reference.
	 * @param array  $data data used to get standings.
	 * @param array  $matches array of matches.
	 * @return array
	 */
	protected function get_standings_data( $team, $data, $matches = array() ) {
		return $data;
	}
	/**
	 * Display custom standings header
	 */
	public function display_standings_header() {
		global $league;
		if ( count( $this->fields_team ) > 0 ) {
			foreach ( $this->fields_team as $key => $data ) {
				if ( show_standings( $key ) || ( is_admin() && 'manual' === get_league_pointrule() ) ) {
					echo '<th class="manage-column column-' . esc_html( $key ) . ' column-num d-none d-md-table-cell">' . esc_html( $data['label'] ) . '</th>';
				}
			}
		}
	}

	/**
	 * Display custom standings columns
	 *
	 * @param object $team team.
	 * @param string $rule rule.
	 */
	public function display_standings_columns( $team, $rule ) {
		if ( count( $this->fields_team ) > 0 ) {
			foreach ( $this->fields_team as $key => $data ) {
				if ( ! isset( $team->{$key} ) ) {
					if ( isset( $data['keys'] ) ) {
						$team->{$key} = array();
						foreach ( $data['keys'] as $k ) {
							$team->{$key}[ $k ] = '';
						}
					} else {
						$team->{$key} = '';
					}
				}

				if ( ( isset( $data['type'] ) && 'input' === $data['type'] ) && is_admin() && 'manual' === $rule ) {
					echo '<td class="column-' . esc_html( $key ) . ' column-num d-none d-md-table-cell" data-colname="' . esc_html( $data['label'] ) . '">';
					if ( is_array( $team->{$key} ) ) {
						foreach ( $team->{$key} as $k => $v ) {
							echo '<input class="points" type="text" size="2" id="home_' . esc_html( $team->id ) . '_' . esc_html( $k ) . '" name="custom[' . esc_html( $team->id ) . '][' . esc_html( $key ) . '][' . esc_html( $k ) . ']" value="' . esc_html( $team->{$key}[ $k ] ) . '" />';
						}
					} else {
						echo '<input class="points" type="text" size="2" id="home_' . esc_html( $team->id ) . '" name="custom[' . esc_html( $team->id ) . '][' . esc_html( $key ) . ']" value="' . esc_html( $team->{$key} ) . '" />';
					}
					echo '</td>';
				} elseif ( show_standings( $key ) ) {
					if ( is_array( $team->{$key} ) ) {
						$team->{$key} = vsprintf( $this->point_format2, $team->{$key} );
					}
					echo '<td class="num column-' . esc_html( $key ) . ' d-none d-md-table-cell d-none d-md-table-cell" data-colname="' . esc_html( $data['label'] ) . '">' . esc_html( $team->{$key} ) . '</td>';
				}
			}
		}
	}

	/**
	 * Display custom standings header
	 */
	public function display_matches_header() {
		if ( count( $this->fields_match ) > 0 ) {
			foreach ( $this->fields_match as $key => $data ) {
				echo '<th class="manage-column column-' . esc_html( $key ) . ' column-num">' . esc_html( $data['label'] ) . '</th>';
			}
		}
	}

	/**
	 * Display custom standings columns
	 *
	 * @param object $match match.
	 */
	public function display_matches_columns( $match ) {
		if ( count( $this->fields_match ) > 0 ) {
			foreach ( $this->fields_match as $key => $data ) {
				if ( ! isset( $match->{$key} ) ) {
					$x       = array();
					$tmp_key = array_keys( $data['keys'] )[0];
					if ( isset( $data['keys'] ) && is_array( $data['keys'][ $tmp_key ] ) ) {
						foreach ( $data['keys'] as $k => $v ) {
							$x[ $k ]          = array();
							$x[ $k ][ $v[0] ] = '';
							$x[ $k ][ $v[1] ] = '';
						}
					} elseif ( isset( $data['keys'] ) ) {
						$x[ $data['keys'][0] ] = '';
						$x[ $data['keys'][1] ] = '';
					} else {
						$x = '';
					}
					$match->{$key} = $x;
				}

				echo '<td class="column-' . esc_html( $key ) . ' column-input column-num" data-colname="' . esc_html( $data['label'] ) . '">';

				if ( isset( $data['url'] ) ) {
					echo '<a href="' . esc_html( $data['url'] ) . '&league_id=' . esc_html( $match->league_id ) . '&season=' . esc_html( $match->season ) . '&match=' . esc_html( $match->id ) . '">' . esc_html( $data['label'] ) . '</a>';
				} elseif ( isset( $data['keys'] ) && is_array( $data['keys'][ array_keys( $data['keys'] )[0] ] ) ) {
					// two-dimensional match keys, e.g. Basketball quarters, volleyball/tennis set_season.
					foreach ( $data['keys'] as $k => $v ) {
						echo '<p>';
						foreach ( $v as $f ) {
							echo '<input class="points" type="text" size="2" id="' . esc_html( $key ) . '_' . esc_html( $k ) . '_' . esc_html( $f ) . '_' . esc_html( $match->id ) . '" name="custom[' . esc_html( $match->id ) . '][' . esc_html( $key ) . '][' . esc_html( $k ) . '][' . esc_html( $f ) . ']" value="' . esc_html( $match->{$key}[ $k ][ $f ] ) . '" />';
						}
						echo '</p>';
					}
				} elseif ( isset( $data['keys'] ) ) {
					foreach ( $data['keys'] as $f ) {
						echo '<input class="points" type="text" size="2" id="' . esc_html( $key ) . '_' . esc_html( $f ) . '_' . esc_html( $match->id ) . '" name="custom[' . esc_html( $match->id ) . '][' . esc_html( $key ) . '][' . esc_html( $f ) . ']" value="' . esc_html( $match->{$key}[ $f ] ) . '" />';
					}
				} else {
					echo '<input class="points" type="text" size="2" id="' . esc_html( $key ) . '_' . esc_html( $match->id ) . '" name="custom[' . esc_html( $match->id ) . '][' . esc_html( $key ) . ']" value="' . esc_html( $match->{$key} ) . '" />';
				}
				echo '</td>';
			}
		}
	}

	/**
	 * Import matches
	 *
	 * @param array $custom custom.
	 * @param array $line line.
	 * @param int   $match_id match id.
	 * @param int   $col the starting column index.
	 * @return array
	 */
	public function import_matches( $custom, $line, $match_id, $col ) {
		if ( count( $this->fields_match ) > 0 ) {
			foreach ( $this->fields_match as $key => $data ) {
				if ( isset( $data['keys'] ) && is_array( $data['keys'][ array_keys( $data['keys'] )[0] ] ) ) {
					foreach ( $data['keys'] as $k => $v ) {
						$p = ( isset( $line[ $col ] ) && ! empty( $line[ $col ] ) ) ? explode( '-', $line[ $col ] ) : array( '', '' );

						$x          = array();
						$x[ $v[0] ] = $p[0];
						$x[ $v[1] ] = $p[1];

						$custom[ $match_id ][ $key ][ $k ] = $x;

						++$col;
					}
				} else {
					if ( isset( $data['keys'] ) ) {
						$p = ( isset( $line[ $col ] ) && ! empty( $line[ $col ] ) ) ? explode( '-', $line[ $col ] ) : array( '', '' );

						$x                     = array();
						$x[ $data['keys'][0] ] = $p[0];
						$x[ $data['keys'][1] ] = $p[1];
					} else {
						$x = ( isset( $line[ $col ] ) && ! empty( $line[ $col ] ) ) ? $line[ $col ] : '';
					}

					$custom[ $match_id ][ $key ] = $x;

					++$col;
				}
			}
		}

		return $custom;
	}

	/**
	 * Import teams
	 *
	 * @param array $custom custom.
	 * @param array $line line.
	 * @param int   $col the starting column index.
	 * @return array
	 */
	public function import_teams( $custom, $line, $col ) {
		if ( count( $this->fields_team ) > 0 ) {
			foreach ( $this->fields_team as $key => $data ) {
				if ( isset( $data['keys'] ) ) {
					$p                     = ( isset( $line[ $col ] ) && ! empty( $line[ $col ] ) ) ? explode( '-', $line[ $col ] ) : array( '', '' );
					$x                     = array();
					$x[ $data['keys'][0] ] = $p[0];
					$x[ $data['keys'][1] ] = $p[1];
					$custom[ $key ]        = $x;
				} else {
					$custom[ $key ] = isset( $line[ $col ] ) ? $line[ $col ] : 0;
				}

				++$col;
			}
		}

		return $custom;
	}

	/**
	 * Schedule matches
	 */
	public function schedule_matches() {
		global $racketmanager;
		$season         = $this->get_season();
		$schedule_teams = $this->get_league_teams(
			array(
				'season'  => $season,
				'status'  => 'active',
				'orderby' => array(
					'group' => 'ASC',
					'title' => 'ASC',
				),
			)
		);
		if ( $this->event->is_box ) {
			$num_teams = $this->num_teams;
			if ( 0 !== $this->num_teams % 2 ) {
				++$num_teams;
			}
			$num_rounds    = $num_teams - 1;
			$num_teams_max = $num_teams;
			$home_away     = false;
		} else {
			$num_rounds = $this->current_season['num_match_days'];
			$home_away  = isset( $this->current_season['homeAway'] ) ? $this->current_season['homeAway'] : 'true';
			if ( $home_away ) {
				$num_rounds = $num_rounds / 2;
			}
			$num_teams_max = $num_rounds + 1;
		}
		$refs = array();
		for ( $i = 1; $i <= $num_teams_max; $i++ ) {
			$refs[] = $i;
		}
		foreach ( $schedule_teams as $team ) {
			if ( $team->group ) {
				$ref = array_search( intval( $team->group ), $refs, true );
				array_splice( $refs, $ref, 1 );
			}
		}
		foreach ( $schedule_teams as $team ) {
			if ( ! $team->group ) {
				$group = $refs[0];
				$racketmanager->setTableGroup( $group, $team->table_id );
				array_splice( $refs, 0, 1 );
			}
		}

		$schedule_teams = $this->get_league_teams(
			array(
				'season'      => $season,
				'status'      => 'active',
				'get_details' => true,
				'cache'       => false,
				'orderby'     => array(
					'group' => 'ASC',
					'title' => 'ASC',
				),
			)
		);
		if ( $schedule_teams ) {
			if ( $refs ) {
				foreach ( $refs as $ref ) {
					$team             = array(
						'id'     => -1,
						'title'  => __( 'Bye', 'racketmanager' ),
						'player' => array(),
						'group'  => $ref,
					);
					$schedule_teams[] = (object) $team;
				}
				usort( $schedule_teams, fn ( $a, $b ) => $a->group <=> $b->group );
			}
			$this->create_schedule( $schedule_teams, $num_rounds, $home_away );
		}
	}

	/**
	 * Schedule matches
	 *
	 * @param object  $teams teams to create schedule for.
	 * @param string  $num_rounds number of rounds.
	 * @param boolean $home_away home and away indicator.
	 */
	public function create_schedule( $teams, $num_rounds, $home_away ) {
		$season      = $this->current_season['name'];
		$match_dates = $this->current_season['matchDates'];
		$num_teams   = count( $teams );
		if ( ! $num_rounds ) {
			$num_rounds = $this->current_season['num_match_days'];
		}
		if ( $num_teams & 1 ) {
			++$num_teams;
		}
		$num_fixtures_per_round = $num_teams / 2;
		$rounds                 = array();
		for ( $i = 0; $i < $num_rounds; $i++ ) {
			$rounds[ $i ] = array( 'fixtures' => array() );
			for ( $x = 0; $x < $num_fixtures_per_round; $x++ ) {
				$rounds[ $i ]['fixtures'][ $x ] = array();
			}
		}
		$rounds = $this->make_first_row( $rounds, $num_teams, $num_rounds, $num_fixtures_per_round, $home_away );
		$rounds = $this->make_other_rows( $rounds, $num_teams, $num_rounds, $num_fixtures_per_round, $home_away );
		$this->create_match_schedule( $rounds, $teams, $match_dates, $season, $this->event->is_box );
	}

	/**
	 * Make the first row of the schedule.
	 *
	 * @param array   $rounds array of rounds.
	 * @param int     $num_teams number of teams.
	 * @param int     $num_rounds number of rounds.
	 * @param int     $num_fixtures_per_round numer of fixtures per round.
	 * @param boolean $home_away home and away indicator.
	 */
	public function make_first_row( $rounds, $num_teams, $num_rounds, $num_fixtures_per_round, $home_away ) {
		$counter_first_half  = 0;
		$counter_second_half = 1;

		for ( $i = 1; $i <= $num_rounds; $i++ ) {
			if ( $i <= $num_fixtures_per_round ) {
				$rounds[ $counter_first_half ]['fixtures'][0]['home'] = $i;
				$rounds[ $counter_first_half ]['fixtures'][0]['away'] = $num_teams;
				if ( $home_away ) {
					$rounds[ $counter_first_half + $num_rounds ]['fixtures'][0]['home'] = $num_teams;
					$rounds[ $counter_first_half + $num_rounds ]['fixtures'][0]['away'] = $i;
				}
				$counter_first_half += 2;
			} elseif ( $i > $num_fixtures_per_round && $i !== $num_teams ) {
				$rounds[ $counter_second_half ]['fixtures'][0]['home'] = $num_teams;
				$rounds[ $counter_second_half ]['fixtures'][0]['away'] = $i;
				if ( $home_away ) {
					$rounds[ $counter_second_half + $num_rounds ]['fixtures'][0]['home'] = $i;
					$rounds[ $counter_second_half + $num_rounds ]['fixtures'][0]['away'] = $num_teams;
				}
				$counter_second_half += 2;
			}
		}
		return $rounds;
	}

	/**
	 * Make other rows of the schedule.
	 *
	 * @param array   $rounds array of rounds.
	 * @param int     $num_teams number of teams.
	 * @param int     $num_rounds number of rounds.
	 * @param int     $num_fixtures_per_round numer of fixtures per round.
	 * @param boolean $home_away home and away indicator.
	 */
	public function make_other_rows( $rounds, $num_teams, $num_rounds, $num_fixtures_per_round, $home_away ) {
		$left  = 2;
		$right = $num_rounds;

		for ( $c = 0; $c < $num_rounds; $c++ ) {
			for ( $r = 1; $r < $num_fixtures_per_round; $r++ ) {
				$rounds[ $c ]['fixtures'][ $r ]['home'] = $left;
				$rounds[ $c ]['fixtures'][ $r ]['away'] = $right;
				if ( $home_away ) {
					$rounds[ $c + $num_rounds ]['fixtures'][ $r ]['home'] = $right;
					$rounds[ $c + $num_rounds ]['fixtures'][ $r ]['away'] = $left;
				}
				$right = $this->right_decrement( $right, $num_rounds );
				if ( $r < $num_fixtures_per_round - 1 ) {
					$left = $this->left_decrement( $left, $num_rounds );
				} elseif ( $r === $num_fixtures_per_round - 1 ) {
					$left = $this->left_decrement_for_last_column( $left, $num_teams );
				}
			}
		}
		return $rounds;
	}

	/**
	 * Decrement the left number
	 *
	 * @param int $col column.
	 * @param int $num_rounds number of rounds.
	 */
	public function left_decrement( $col, $num_rounds ) {
		if ( $col < $num_rounds ) {
			return $col + 1;
		} elseif ( $col === $num_rounds ) {
			return 1;
		}
	}

	/**
	 * Decrement the right number
	 *
	 * @param int $col column.
	 * @param int $num_rounds number of rounds.
	 */
	public function right_decrement( $col, $num_rounds ) {
		if ( $col > 1 ) {
			return $col - 1;
		} elseif ( 1 === $col ) {
			return $num_rounds;
		}
	}

	/**
	 * Decrement the left number for the last column.
	 *
	 * @param int $col column.
	 * @param int $num_teams number of teams.
	 */
	public function left_decrement_for_last_column( $col, $num_teams ) {
		if ( $col <= $num_teams - 3 ) {
			return $col + 2;
		} elseif ( $col === $num_teams - 2 ) {
			return 1;
		} elseif ( $col === $num_teams - 1 ) {
			return 2;
		}
	}

	/**
	 * Create match schedule with teams
	 *
	 * @param array   $rounds rounds.
	 * @param array   $teams array of teams.
	 * @param array   $match_dates array of match dates.
	 * @param string  $season season.
	 * @param boolean $is_box is this a box league.
	 */
	public function create_match_schedule( $rounds, $teams, $match_dates, $season, $is_box ) {
		$num_rounds = count( $rounds );
		for ( $i = 0; $i < $num_rounds; $i++ ) {
			if ( ! $is_box ) {
				$round_number              = $i + 1;
				$start_date                = $match_dates[ $i ];
				$rounds[ $i ]['startDate'] = $start_date;
			}
			$fixtures = $rounds[ $i ]['fixtures'];
			foreach ( $fixtures as $fixture ) {
				$home_team_dtls = $teams[ $fixture['home'] - 1 ];
				$away_team_dtls = $teams[ $fixture['away'] - 1 ];
				if ( -1 !== $home_team_dtls->id && -1 !== $away_team_dtls->id ) {
					$match            = new \stdClass();
					$match->season    = $season;
					$match->league_id = $this->id;
					$match->home_team = $home_team_dtls->id;
					$match->away_team = $away_team_dtls->id;
					if ( $is_box ) {
						$match->date      = null;
						$match->match_day = 1;
						$match->location  = '';
					} else {
						$match_day        = $home_team_dtls->match_day;
						$match_time       = $home_team_dtls->match_time;
						$day              = Racketmanager_Util::get_match_day_number( $match_day );
						$match_date       = gmdate( 'Y-m-d', strtotime( $start_date . " +$day day" ) ) . ' ' . $match_time;
						$match->date      = $match_date;
						$match->match_day = $round_number;
						$match->location  = $home_team_dtls->club->shortcode;
					}
					$match = new Racketmanager_Match( $match );
				}
			}
		}
	}
	/**
	 * Add match to league function
	 *
	 * @param object $match match object.
	 * @return void
	 */
	public function add_match( $match ) {
		$match = new Racketmanager_Match( $match );
		if ( $this->is_championship && ! empty( $this->current_season['homeAway'] ) && 'true' === $this->current_season['homeAway'] && 'final' !== $match->final_round ) {
			$match->leg              = 1;
			$new_match               = clone $match;
			$new_match->date         = gmdate( 'Y-m-d H:i:s', strtotime( $match->date . ' +14 day' ) );
			$new_match->id           = null;
			$new_match->linked_match = $match->id;
			$new_match->leg          = $match->leg + 1;
			if ( ! empty( $match->host ) ) {
				$new_match->host = 'home' === $match->host ? 'away' : 'home';
			}
			$new_match = new Racketmanager_Match( $new_match );
			$new_match->update_legs( $new_match->leg, $match->id );
			$match->update_legs( $match->leg, $new_match->id );
		}
	}
	/**
	 * Update match within league function
	 *
	 * @param object $match match object.
	 */
	public function update_match( $match ) {
		$match->update();
		if ( ! empty( $match->linked_match ) ) {
			$linked_match            = get_match( $match->linked_match );
			$linked_match->home_team = $match->home_team;
			$linked_match->away_team = $match->away_team;
			$linked_match->date      = gmdate( 'Y-m-d H:i:s', strtotime( $match->date . ' +14 day' ) );
			if ( ! empty( $match->host ) ) {
				$linked_match->host = 'home' === $match->host ? 'away' : 'home';
			}
			$linked_match->update();
		}
	}
	/**
	 * Get players for league
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
		$group    = $args['group'];
		$stats    = $args['stats'];

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
			$sql = 'SELECT COUNT(*)';
		} else {
			$sql = 'SELECT DISTINCT `player_id`, `club_player_id`';
		}
		$sql .= " FROM {$wpdb->racketmanager_rubber_players} rp, {$wpdb->racketmanager_rubbers} r, {$wpdb->racketmanager_matches} m  WHERE rp.`rubber_id` = r.`id` AND r.`match_id` = m.`id` AND m.`league_id` = %d" . $search;
		if ( $count ) {
			$sql = $wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql,
				$search_args,
			);
			$num_players = wp_cache_get( md5( $sql ), 'league_rubber_players' );
			if ( ! $num_players ) {
				$num_players = $wpdb->get_var(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					$sql
				); // db call ok.
				wp_cache_set( md5( $sql ), $num_players, 'league_rubber_players' );
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
		$players = wp_cache_get( md5( $sql ), 'league_rubber_players' );
		if ( ! $players ) {
			$players = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql
			); // db call ok.
			wp_cache_set( md5( $sql ), $players, 'league_rubber_players' );
		}
		$league_players = array();
		foreach ( $players as $player ) {
			$player = get_player( $player->player_id );
			if ( $player->system_record ) {
				continue;
			}
			if ( ! $stats ) {
				$league_players[] = $player->fullname;
			} else {
				$player->matches = $player->get_matches( $this, $this->current_season['name'], 'league' );
				$player->stats   = $player->get_stats();
				if ( ! $team ) {
					$player->team = $this->get_player_team( array( 'player' => $player->id ) );
				}
				$player->win_pct      = $player->stats['total']->win_pct;
				$player->matches_won  = $player->stats['total']->matches_won;
				$player->matches_lost = $player->stats['total']->matches_lost;
				$player->played       = $player->stats['total']->played;
				$league_players[]     = $player;
			}
		}
		if ( ! $stats ) {
			asort( $league_players );
		} else {

			$won    = array_column( $league_players, 'matches_won' );
			$played = array_column( $league_players, 'played' );
			array_multisort( $won, SORT_DESC, $played, SORT_ASC, $league_players );
		}
		if ( $group ) {
			$this->players = array();
			foreach ( $league_players as $player ) {
				$key = strtoupper( substr( $player, 0, 1 ) );
				if ( false === array_key_exists( $key, $this->players ) ) {
					$this->players[ $key ] = array();
				}
				// now just add the row data.
				$this->players[ $key ][] = $player;
			}
		} else {
			$this->players = $league_players;
		}

		return $this->players;
	}
	/**
	 * Get Player team function
	 *
	 * @param array $args array of parameters.
	 * @return object team
	 */
	private function get_player_team( $args = array() ) {
		global $wpdb;
		$defaults = array(
			'season' => false,
			'team'   => false,
			'player' => false,
		);
		$args     = array_merge( $defaults, $args );
		$season   = $args['season'];
		$team     = $args['team'];
		$player   = $args['player'];

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
		if ( $player ) {
			$search_terms[] = 'ro.`player_id` = %d';
			$search_args[]  = intval( $player );
		}
		$search = '';
		if ( ! empty( $search_terms ) ) {
			$search  = ' AND ';
			$search .= implode( ' AND ', $search_terms );
		}
		$sql = "SELECT distinct t.`id`, t.`title` FROM {$wpdb->racketmanager_teams} AS t, {$wpdb->racketmanager_rubbers} AS r, {$wpdb->racketmanager_rubber_players} AS rp, {$wpdb->racketmanager_matches} AS m, {$wpdb->racketmanager_club_players} AS ro WHERE r.`winner_id` != 0 AND r.`id` = rp.`rubber_id` AND rp.`club_player_id` = ro.`id` AND ((rp.`player_team` = 'home' AND m.`home_team` = t.`id`) OR (rp.`player_team` = 'away' AND m.`away_team` = t.`id`)) AND ro.`affiliatedclub` = t.`affiliatedclub` AND r.`match_id` = m.`id` AND m.`league_id` = %d " . $search;
		$sql = $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$sql,
			$search_args,
		);
		$teams = wp_cache_get( md5( $sql ), 'player_team' );
		if ( ! $teams ) {
			$teams = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql
			); // db call ok.
			wp_cache_set( md5( $sql ), $teams, 'player_team' );
		}
		if ( $teams ) {
			$team = $teams[0];
		}
		return $team;
	}
}
