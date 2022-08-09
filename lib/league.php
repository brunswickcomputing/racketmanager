<?php
/**
* League API: League class
*
* @author Kolja Schleich
* @package RacketManager
* @subpackage League
*/

/**
* Class to implement the League object
*
*/
class League {
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
	* seasons data
	*
	* @var array
	*/
	public $seasons = array();

	/**
	* number of seasons
	*
	* @var int
	*/
	public $num_seasons = 0;

	/**
	* sport type
	*
	* @var string
	*/
	public $sport = "default";

	/**
	* point rule
	*
	* @var string
	*/
	public $point_rule = "three";

	/**
	* primary points format
	*
	* @var string
	*/
	public $point_format = "%d-%d";

	/**
	* secondary points format
	*
	* @var string
	*/
	public $point_format2 = "%d-%d";

	/**
	* team ranking mode
	*
	* @var string
	*/
	public $team_ranking = "auto";

	/**
	* league mode
	*
	* @var string
	*/
	public $mode = "default";

	/**
	* default match starting time
	*
	* @var array
	*/
	public $default_match_start_time = array("hour" => 0, "minutes" => 0);

	/**
	* standings table layout settings
	*
	* @var array
	*/
	public $standings = array( 'status' => 1, 'team_link' => 1, 'pld' => 1, 'won' => 1, 'tie' => 1, 'lost' => 1, 'winPercent' => 1, 'last5' => 1 );

	/**
	* number of teams ascending
	*
	* @var int
	*/
	public $num_ascend = 0;

	/**
	* number of teams descending
	*
	* @var int
	*/
	public $num_descend = 0;

	/**
	* number of teams for relegationnum_relegation
	*
	* @var int
	*/
	public $num_relegation = 0;

	/**
	* number of teams per page in list
	*
	* @var int
	*/
	public $num_teams_per_page = 10;

	/**
	* number of pages for teams
	*
	* @var int
	*/
	public $num_pages_teams = 0;

	/**
	* current page for teams
	*
	* @var int
	*/
	public $current_page_teams = 1;

	/**
	* teams pagination
	*
	* @var string
	*/
	public $pagination_teams = '';

	/**
	* number of matches per page
	*
	* @var int
	*/
	public $num_matches_per_page = 30;

	/**
	* default display filter for matches
	*
	* @var string
	*/
	public $match_display = 'current_match_day';

	/**
	* number of pages for matches
	*
	* @var int
	*/
	public $num_pages_matches = 0;

	/**
	* current page for matches
	*
	* @var int
	*/
	public $current_page_matches = 1;

	/**
	* matches pagination
	*
	* @var string
	*/
	public $pagination_matches = '';

	/**
	* slideshow options
	*
	* @var array
	*/
	public $slideshow = array( 'season' => 'latest', 'num_matches' => 0 );

	/**
	* team groups
	*
	* @var array
	*/
	public $groups = array();

	/**
	* curent team group
	*
	* @var string
	*/
	public $current_group = '';

	/**
	* teams
	*
	* @var array
	*/
	public $teams = array();

	/**
	* total number of teams
	*
	* @var int
	*/
	public $num_teams_total = 0;

	/**
	* number of teams for current query
	*
	* @var int
	*/
	public $num_teams = 0;

	/**
	* number of teams by group
	*
	* @var array
	*/
	public $num_teams_by_group = array();

	/**
	* matches
	*
	* @var array
	*/
	public $matches = array();

	/**
	* total number of matches
	*
	* @var int
	*/
	public $num_matches_total = 0;

	/**
	* number of matches
	*
	* @var int
	*/
	public $num_matches = 0;

	/**
	* current season
	*
	* @var array
	*/
	public $current_season = array();

	/**
	* number of match days
	*
	* @var int
	*/
	public $num_match_days = 0;

	/**
	* current match day
	*
	* @var int
	*/
	public $match_day = -1;

	/**
	* query arguments
	*
	* @var array
	*/
	private $query_args = array();

	/**
	* team database query args
	*
	* @var array
	*/
	private $team_query_args = array( 'limit' => false, 'group' => '', 'season' => '', 'rank' => 0, 'orderby' => array("rank" => "ASC"), "home" => false, 'ids' => array(), 'cache' => true, 'reset_query_args' => false, 'getDetails' => false );

	/**
	* team query argument types
	*
	* @var array
	*/
	private $team_query_args_types = array( 'limit' => 'numeric', 'group' => 'string', 'season' => 'string', 'rank' => 'numeric', 'orderby' => 'array', 'home' => 'boolean', 'ids' => 'array_numeric', 'cache' => 'boolean', 'reset_query_args' => 'boolean', 'getDetails' => 'boolean' );

	/**
	* match query arguments
	*
	* @var array
	*/
	private $match_query_args = array( 'limit' => true, 'group' => '', 'season' => '', 'final' => '', 'match_day' => -1, 'time' => '', 'home_only' => false, 'count' => false, 'orderby' => array("date" => "ASC", "id" => "ASC"), 'standingstable' => false, 'cache' => true, 'team_id' => 0, 'home_team' => '', 'away_team' => '', 'team_pair' => array(), 'winner_id' => false, 'loser_id' => false, 'home_points' => false, 'away_points' => false, 'mode' => '', 'reset_limit' => true, 'reset_query_args' => false, 'update_results' => false, 'confirmed' => false );

	/**
	* match query argument types
	*
	* @var array
	*/
	private $match_query_args_types = array( 'limit' => 'numeric', 'group' => 'string', 'season' => 'string', 'final' => 'string', 'match_day' => 'numeric', 'time' => 'string', 'home_only' => 'boolean', 'count' => 'boolean', 'orderby' => 'array', 'standingstable' => 'boolean', 'cache' => 'boolean', 'team_id' => 'numeric', 'home_team' => 'string', 'away_team' => 'string', 'team_pair' => 'array', 'winner_id' => 'numeric', 'loser_id' => 'numeric', 'home_points' => 'string', 'away_points' => 'string', 'mode' => 'string', 'reset_limit' => 'boolean', 'reset_query_args' => 'boolean', 'update_results' => 'boolean', 'confirmed' => 'boolean' );

	/**
	* settings keys
	*
	* @var array
	*/
	private $settings_keys = array();

	/**
	* team offsets indexed by ID
	*
	* @var array
	*/
	public $team_index = array();

	/**
	* team loop
	*
	* @var boolean
	*/
	public $in_the_team_loop = false;

	/**
	* current team
	*
	* @var int
	*/
	public $current_team = -1;

	/**
	* team is selected?
	*
	* @var boolean
	*/
	public $is_selected_team = false;

	/**
	* match loop
	*
	* @var boolean
	*/
	public $in_the_match_loop = false;

	/**
	* current match
	*
	* @var int
	*/
	public $current_match = -1;

	/**
	* match is selected
	*
	* @var boolean
	*/
	public $is_selected_match = false;

	/**
	* toggle match day selection menu display
	*
	* @var boolean
	*/
	public $show_match_day_selection = true;

	/**
	* toggle team selection menu display
	*
	* @var boolean
	*/
	public $show_team_selection = true;

	/**
	* toggle matches selection menu display, depends on $show_match_day_selection and $show_team_selection
	*
	* @var boolean
	*/
	public $show_matches_selection = true;

	/**
	* is this an archive
	*
	* @var boolean
	*/
	public $is_archive = false;

	/**
	* set archive tab
	*
	* @var int
	*/
	public $archive_tab = 0;

	/**
	* save templates for whole league or archive display
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
	* championship flag
	*
	* @var boolean
	*/
	public $is_championship = false;

	/**
	* League_Championship object
	*
	* @var League_Championship
	*/
	public $championship = null;

	/**
	* define active tiebreak rules
	*
	* @var array
	*/
	public $tiebreak = array( 'headToHeadTwoTeams' => 1 );

	/**
	* is tiebreak flag
	*
	* @var boolean
	*/
	private $is_tiebreak = false;

	/**
	* tied teams flag
	*
	* @var boolean
	*/
	private $is_tie = false;

	/**
	* custom team input field keys and translated labels
	*
	* @var array
	*/
	public $fields_team = array();

	/**
	* custom match input field keys and translated labels
	*
	* @var array
	*/
	protected $fields_match = array();

	/**
	* retrieve league instance
	*
	* @param int $league_id
	*/
	public static function get_instance($league_id) {
		global $wpdb;

		if ( is_numeric($league_id) ) {
			$search = "`id` = '%d'";
		} else {
			$search = "`title` = '%s'";
		}
		if ( ! $league_id )
		return false;

		$league = wp_cache_get( $league_id, 'leagues' );

		if ( ! $league ) {
			$league = $wpdb->get_row( $wpdb->prepare( "SELECT `title`, `id`, `seasons`, `settings`, `competition_id` FROM {$wpdb->racketmanager} WHERE ".$search." LIMIT 1", $league_id ) );
			$competition = get_competition($league->competition_id);
			$league->settings = (array)maybe_unserialize($competition->settings);
			$league = (object)array_merge((array)$league, $league->settings);

			if ( !$league ) return false;

			// check if specific sports class exists
			if ( !isset($competition->sport) ) $league->sport = '';
			$instance = "League_". ucfirst($competition->sport);
			if (class_exists($instance)) {
				$league = new $instance( $league );
			} else {
				$league = new League( $league );
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
		global $competition;

		if (isset($league->settings)) {
			$league->settings = (array)maybe_unserialize($league->settings);
			$league->settings_keys = array_keys((array)maybe_unserialize($league->settings));
			$league = (object)array_merge((array)$league, $league->settings);
			unset($league->settings);
		}

		foreach ( get_object_vars( $league ) as $key => $value ) {
			if ( $key == "standings")
			$this->$key = array_merge($this->$key, $value);
			else
			$this->$key = $value;
		}

		$this->title = stripslashes($this->title);
		$competition = get_competition($this->competition_id);

		$this->seasons = $competition->seasons;
		// set seasons
		if ( $this->seasons == '' ) $this->seasons = array();
		$this->seasons = (array)maybe_unserialize($this->seasons);
		$this->num_seasons = count($this->seasons);

		// set season to latest
		$this->setSeason();

		$this->num_sets = $competition->num_sets;
		$this->numSetstoWin = floor($this->num_sets / 2) + 1;
		$this->num_rubbers = $competition->num_rubbers;
		$this->competitionType = $competition->competitiontype;
		$this->type = $competition->type;
		$this->sport = $competition->sport;
		$this->competition_id = $competition->id;
		$this->competitionName = $competition->name;
		$this->scoring = $competition->scoring;
		$this->setMatchQueryArgs();
		$this->setNumMatches(true); // get total number of matches
		$this->setNumMatches();
		$this->setNumTeams(true); // get total number of teams

		// set default standings display options for additional team fields
		if ( count($this->fields_team) > 0 ) {
			foreach ( $this->fields_team AS $key => $data ) {
				if ( !isset($this->standings[$key]) )
				$this->standings[$key] = 1;
			}
		}

		// set selected team marker
		if (isset($_GET['team_'.$this->id])) {
			$this->current_team = intval($_GET['team_'.$this->id]);
			$this->is_selected_team = true;
		}

		// set selected match marker
		if (isset($_GET['match_'.$this->id])) {
			$this->current_match = intval($_GET['match_'.$this->id]);
			$this->is_selected_match = true;
		}

		// Championship
		if ( $this->mode == "championship" ) {
			$this->is_championship = true;
			$this->championship = new League_Championship( $this, $this->getSettings() );
		}

		// add actions & filter
		add_filter( 'league_standings_options', array(&$this, 'standingsTableDisplayOptions') );

		add_filter( 'racketmanager_import_matches_'.$this->sport, array(&$this, 'importMatches'), 10, 4 );
		add_filter( 'racketmanager_import_teams_'.$this->sport, array(&$this, 'importTeams'), 10, 3 );
	}

	/**
	* set detault dataset query arguments
	*/
	private function setMatchQueryArgs() {
		// set to latest match day by default
		$this->setMatchQueryArg('match_day', 'current');

		// set number of matches per page
		$this->setMatchQueryArg('limit', $this->num_matches_per_page);
	}

	/**
	* set match query argument
	*
	* @param string $key
	* @param mixed $value
	* @param boolean $replace - used for arrays to add arguments or replace with values
	*/
	public function setMatchQueryArg( $key, $value, $replace = true ) {
		if ($key == 'limit' && ($value === true || $value == "true")) $value = $this->num_matches_per_page;
		/*
		* sanitize query arg types
		*/
		$v = $value;
		if ($this->match_query_args_types[$key] == 'numeric')
		$v = intval($value);
		if ($this->match_query_args_types[$key] == 'boolean')
		$v = intval($value) == 1;

		if (is_array($this->match_query_args[$key]) && !$replace) {
			if (!is_array($v)) $v = array($v);
			$this->match_query_args[$key] = array_merge($this->match_query_args[$key], $v);
		} else {
			$this->match_query_args[$key] = $v;
		}

	}


	/**
	* set team query argument
	*
	* @param string $key
	* @param mixed $value
	* @param boolean $replace - used for arrays to add arguments or replace with values
	*/
	public function setTeamQueryArg( $key, $value, $replace = true ) {
		if ($key == 'limit' && ($value === true || $value == "true")) $value = $this->num_teams_per_page;

		/*
		* sanitize query arg types
		*/
		if ($this->team_query_args_types[$key] == 'numeric')
		$value = intval($value);
		if ($this->team_query_args_types[$key] == 'boolean')
		$value = intval($value) == 1;

		if (is_array($this->team_query_args[$key]) && !$replace) {
			if (!is_array($value)) $value = array($value);
			$this->team_query_args[$key] = array_merge($this->team_query_args[$key], $value);
		} else {
			$this->team_query_args[$key] = $value;
		}
	}

	/**
	* set current season
	*
	* @param mixed $season
	* @param boolean $force_overwrite
	*/
	public function setSeason( $season = false, $force_overwrite = false ) {
		global $wp;

		if ( !empty($season) && $force_overwrite === true ) {
			$data = $this->seasons[$season];
		} elseif ( isset($_GET['season']) && !empty($_GET['season']) ) {
			$key = htmlspecialchars(strip_tags($_GET['season']));
			if (!isset($this->seasons[$key]))
			$data = false;
			else
			$data = $this->seasons[$key];
		} elseif ( isset($_GET['season_'.$this->id]) && !empty($_GET['season_'.$this->id]) ) {
			$key = htmlspecialchars(strip_tags($_GET['season_'.$this->id]));
			if (!isset($this->seasons[$key]))
			$data = false;
			else
			$data = $this->seasons[$key];
		} elseif ( isset($wp->query_vars['season']) ) {
			$key = get_query_var('season');
			if (!isset($this->seasons[$key]))
			$data = false;
			else
			$data = $this->seasons[$key];
		} elseif ( !empty($season) ) {
			$data = $this->seasons[$season];
		} else {
			$data = end($this->seasons);
		}

		if (empty($data)) $data = end($this->seasons);

		if ( !$data ) {
			$data['name'] = '';
			$data['num_match_days'] = 0;
		}
		$this->current_season = $data;
		$this->num_match_days = $data['num_match_days'];

		$this->setTeamQueryArg('season', $this->current_season['name']);
		$this->setMatchQueryArg('season', $this->current_season['name']);
	}

	/**
	* get current season name
	*
	* @return string
	*/
	public function getSeason() {
		return stripslashes($this->current_season['name']);
	}

	/**
	* set group
	*
	* @param string $group
	* @param boolean $force_overwrite
	*/
	public function setGroup( $group = '', $force_overwrite = false ) {
		if ( $group != '' && $force_overwrite === true ) {
			$group = $group;
		} elseif ( isset($_GET['group']) ) {
			$group = $_GET['group'];
		} else if ( is_admin() && isset($_POST['group']) ) {
			$group = $_POST['group'];
		} else {
			// set to first group in league by default
			$groups = $this->getGroups();
			if ( isset($groups[0]) ) $group = $groups[0];
		}

		if (is_array($group)) $group = $group[0];

		$group = htmlspecialchars(strip_tags($group));
		if ($this->groupExists($group)) {
			$this->setTeamQueryArg('group', $group);
			$this->setMatchQueryArg('group', $group);

			$this->current_group = $group;
		}
	}

	/**
	* get current group
	*
	*/
	public function getGroup() {
		return $this->current_group;
	}

	/**
	* get groups
	*
	* @return array
	*/
	public function getGroups() {
		if ( is_string($this->groups) )
		$groups = explode(";", $this->groups);
		else
		$groups = $this->groups;

		if ( !is_array($groups) )
		return false;

		return $groups;
	}

	/**
	* retrieve match day
	*
	* @param mixed $_match_day
	*/
	public function setMatchDay( $_match_day = false ) {
		global $wpdb ,$wp;

		if ( isset($_GET['match_day']) ) {
			$match_day = intval($_GET['match_day']);
		} elseif ( isset($_GET['match_day_'.$this->id])) {
			$match_day = intval($_GET['match_day_'.$this->id]);
		} elseif (isset($_POST['match_day'])) {
			$match_day = intval($_POST['match_day']);
		} elseif ( isset($wp->query_vars['match_day']) ) {
			$match_day = get_query_var('match_day');
		} elseif (is_numeric($_match_day) && $_match_day != 0) {
			$match_day = intval($_match_day);
		} elseif ( $_match_day == "last" ) {
			$match_day = wp_cache_get( 'last_'.$this->id, 'leagues_match_days' );
			if (!$match_day) {
				$sql = "SELECT `match_day`, DATEDIFF(NOW(), `date`) AS datediff FROM {$wpdb->racketmanager_matches} WHERE `league_id` = '%d' AND `season` = '%s' AND DATEDIFF(NOW(), `date`) > 0 ORDER BY datediff ASC LIMIT 1";
				$match = $wpdb->get_row( $wpdb->prepare($sql, $this->id, $this->current_season['name']) );
				if ($match)  {
					$match_day = $match->match_day;
					wp_cache_set( 'last_'.$this->id, $match_day, 'leagues_match_days' );
				} else {
					$match_day = $_match_day;
				}
			}
		} elseif ( $_match_day == "next" ) {
			$match_day = wp_cache_get( 'next_'.$this->id, 'leagues_match_days' );
			if (!$match_day) {
				$sql = "SELECT `match_day`, DATEDIFF(NOW(), `date`) AS datediff FROM {$wpdb->racketmanager_matches} WHERE `league_id` = '%d' AND `season` = '%s' AND DATEDIFF(NOW(), `date`) < 0 ORDER BY datediff DESC LIMIT 1";
				$match = $wpdb->get_row( $wpdb->prepare($sql, $this->id, $this->current_season['name']) );
				if ($match)  {
					$match_day = $match->match_day;
					wp_cache_set( 'next_'.$this->id, $match_day, 'leagues_match_days' );
				} else {
					$match_day = $_match_day;
				}
			}
		} elseif ( $_match_day == "current" || $_match_day == "latest") {
			$match_day = wp_cache_get( 'current_'.$this->id, 'leagues_match_days' );
			if (!$match_day) {
				$sql = "SELECT `id`, `match_day`, ABS(DATEDIFF(NOW(), `date`)) AS datediff FROM {$wpdb->racketmanager_matches} WHERE `league_id` = '%d' AND `season` = '%s' ORDER BY datediff ASC LIMIT 1";
				$match = $wpdb->get_row( $wpdb->prepare($sql, $this->id, $this->current_season['name']) );
				if ($match) {
					$match_day = $match->match_day;
					wp_cache_set( 'current_'.$this->id, $match_day, 'leagues_match_days' );
				} else {
					$match_day = $_match_day;
				}
			}
		} else {
			$match_day = 1;
		}

		if (empty($match_day) || !is_numeric($match_day)) $match_day = 1;
		$this->match_day = intval($match_day);
		$this->match_query_args['match_day'] = $match_day;
	}


	/**
	* get maximum match day
	*
	* @return int
	*/
	public function getMaxMatchDay() {
		global $wpdb;

		$args = array( $this->id, $this->getSeason() );
		$sql = "SELECT MAX(match_day) FROM {$wpdb->racketmanager_matches} WHERE  `league_id` = '%d' AND `season` = '%s'";
		if ( $this->getGroup() != "" && $this->groupExists(htmlspecialchars($this->getGroup())) ) {
			$sql .= " AND `group` = '%s'";
			$args[] = htmlspecialchars($this->getGroup());
		}

		$match_day = $wpdb->get_var( $wpdb->prepare($sql, $args) );
		return $match_day;
	}

	/**
	* get pagination
	*
	* @param string $which
	* @return string
	*/
	public function getPageLinks($which = 'matches') {
		$this->getCurrentPage($which);

		if ( $which == 'matches' ) {
			$base = is_admin() ? 'match_paged' : 'match_paged_'.$this->id;
			$num_pages = $this->num_pages_matches;
			$per_page = $this->num_matches_per_page;
			$current_page = $this->current_page_matches;
			$num_items = $this->num_matches;
		} elseif ( $which == 'teams' ) {
			$base = is_admin() ? 'team_paged' : 'team_paged_'.$this->id;
			$num_pages = $this->num_pages_teams;
			$per_page = $this->num_teams_per_page;
			$current_page = $this->current_page_teams;
			$num_items = $this->num_matches;
		} else {
			return '';
		}

		$query_args = is_admin() ? array('league_id' => $this->id) : $this->query_args;

		if ($which == 'matches' && isset($_POST['match_day']) && is_string($_POST['match_day'])) {
			$query_args['match_day'] = htmlspecialchars(strip_tags($_POST['match_day']));
		}
		$page_links = paginate_links( array(
			'base' => add_query_arg( $base, '%#%' ),
			'format' => '',
			'prev_text' => '&#9668;',
			'next_text' => '&#9658;',
			'total' => $num_pages,
			'current' => $current_page,
			'add_args' => $query_args
		));

		if ( $page_links && is_admin() ) {
		$page_links = sprintf( '<span class="displaying-num">' . __( '%s Matches', 'racketmanager' ) . '</span>%s',
		number_format_i18n(  $num_items ),
		$page_links
	);
}

return $page_links;
}

/**
* set number of pages for matches
*
* @param string $which
*/
public function setNumPages($which='matches') {
	if ( $which == 'matches' ) {
		$this->num_pages_matches = ( $this->num_matches_per_page == 0 ) ? 1 : ceil( $this->num_matches/$this->num_matches_per_page );
		if ( $this->num_pages_matches == 0 ) $this->num_pages_matches = 1;
	}

	if ( $which == 'teams' ) {
		$this->num_pages_teams = ( $this->num_teams_per_page == 0 ) ? 1 : ceil( $this->num_teams/$this->num_teams_per_page );
		if ( $this->num_pages_teams == 0 ) $this->num_pages_teams = 1;
	}
}

/**
* retrieve current page
*
* @param string $which
*/
public function getCurrentPage($which='matches') {
	global $wp;

	$this->setNumPages($which);

	if ( $which == 'matches' )
	$key = 'match_paged';
	elseif ( $which == 'teams' )
	$key = 'team_paged';

	if (isset($_GET[$key]))
	$current_page = intval($_GET[$key]);
	elseif (isset($wp->query_vars[$key]))
	$current_page = max(1, intval($wp->query_vars[$key]));
	elseif (isset($_GET[$key."_".$this->id]))
	$current_page = intval($_GET[$key."_".$this->id]);
	elseif (isset($wp->query_vars[$key."_".$this->id]))
	$current_page = max(1, intval($wp->query_vars[$key."_".$this->id]));
	else
	$current_page = 1;

	if ( $which == 'matches' && $current_page > $this->num_pages_matches )
	$current_page = $this->num_pages_matches;
	if ( $which == 'teams' && $current_page > $this->num_pages_teams )
	$current_page = $this->num_pages_teams;

	// Prevent negative offsets
	if ( $current_page == 0 )
	$current_page = 1;

	if ( $which == 'matches' ) {
		$this->current_page_matches = $current_page;
	}
	if ( $which == 'teams' )
	$this->current_page_teams = $current_page;

	return $current_page;
}

/**
* get teams from league from database
*
* @param array $args
* @param string $output OBJECT | ARRAY
* @return array database results
*/
public function getLeagueTeams( $query_args = array() ) {
	global $wpdb;

	$old_query_args = $this->team_query_args;

	// set query args
	foreach ($query_args AS $key => $value)
	$this->setTeamQueryArg($key, $value);

	extract($this->team_query_args, EXTR_SKIP);

	$args = array($this->id);
	$sql = "SELECT B.`id` AS `id`, B.`title`, B.`affiliatedclub`, B.`stadium`, B.`home`, A.`group`, B.`roster`, B.`profile`, A.`group`, A.`points_plus`, A.`points_minus`, A.`points2_plus`, A.`points2_minus`, A.`add_points`, A.`done_matches`, A.`won_matches`, A.`draw_matches`, A.`lost_matches`, A.`diff`, A.`league_id`, A.`id` AS `table_id`, A.`season`, A.`rank`, A.`status`, A.`custom` FROM {$wpdb->racketmanager_teams} B INNER JOIN {$wpdb->racketmanager_table} A ON B.id = A.team_id WHERE `league_id` = '%d'";

	if ( $season == "" ) {
		$sql .= " AND A.`season` = '%s'";
		$args[] = $this->current_season['name'];
	} else {
		if ($season == "any") {
			$sql .= " AND A.`season` != ''";
		} elseif ($this->seasonExists(htmlspecialchars($season))) {
			$sql .= " AND A.`season` = '%s'";
			$args[] = htmlspecialchars($season);
		}
	}

	if ( $rank ) {
		$sql .= " AND A.`rank` = '%s'";
		$args[] = $rank;
	}
	if ( $home ) {
		$sql .= " AND B.`home` = 1";
	}

	$orderby_string = ""; $i = 0;
	foreach ($orderby AS $order => $direction) {
		if (!in_array($direction, array("DESC", "ASC", "desc", "asc"))) $direction = "ASC";
		$orderby_string .= "`".$order."` ".$direction;
		if ($i < (count($orderby)-1)) $orderby_string .= ",";
		$i++;
	}
	$orderby = $orderby_string;

	$sql .= " ORDER BY ".$orderby;
	$sql = $wpdb->prepare($sql, $args);

	$teams = wp_cache_get( $sql, 'leaguetable' );
	if ( !$teams || $cache === false ) {
		$teams = $wpdb->get_results( $sql );
		wp_cache_set( $sql, $teams, 'leaguetable' );
	}

	$class = '';
	$team_index = array();
	foreach ( $teams AS $i => $team ) {
		$team = get_leagueteam($team);

		$class = ( 'alternate' == $class ) ? '' : 'alternate';

		// Add class for home team
		if ( 1 == $team->home ) $team->cssClass[] = 'homeTeam';

		$team->custom = stripslashes_deep(maybe_unserialize($team->custom));
		$team->roster = maybe_unserialize($team->roster);
		$team->title = htmlspecialchars(stripslashes($team->title), ENT_QUOTES);
		$team->affiliatedclub = stripslashes($team->affiliatedclub);
		$team->affiliatedclubname = get_club( $team->affiliatedclub )->name;
		$team->stadium = stripslashes($team->stadium);
		$team->cssClass[] = $class;
		$team->class = implode(' ', $team->cssClass);

		if ( 1 == $team->home ) $team->title = '<strong>'.$team->title.'</strong>';

		$team->pointsFormatted = array( 'primary' => sprintf($this->point_format, $team->points_plus, $team->points_minus), 'secondary' => sprintf($this->point_format2, $team->points2_plus, $team->points2_minus) );
		if ( $getDetails ) {
			$teamDtls = $this->getTeamDtls($team->id);
			$team->match_day = $teamDtls->match_day;
			$team->match_time = $teamDtls->match_time;
		}

		$team_index[$team->id] = $i;
		$teams[$i] = $team;
	}

	$this->teams = $teams;
	$this->team_index = $team_index;

	$this->setNumTeams();

	// reset team query args
	if ($reset_query_args === true) {
		foreach ($old_query_args AS $key => $query_arg)
		$this->setTeamQueryArg($key, $query_arg, true);

		$this->setTeamQueryArg('reset_query_args', false);
	}

	return $teams;
}

/**
* get single team from League cache
*
* @param int $team_id
* @return Team object
*/
public function getLeagueTeam( $team_id ) {
	if (isset($this->team_index[$team_id])) {
		return $this->teams[$this->team_index[$team_id]];
	} else {
		return $this->getTeamDtls($team_id);
	}
}

/**
* get single team
*
* @param int $team_id
* @return object
*/
public function getTeamDtls( $team_id ) {
	global $wpdb, $racketmanager;

	if ( $team_id == -1 ) {
		$team = (object) ['id' => -1, 'title' => 'Bye'];
		$team->captain = $team->contactno = $team->contactemail = $team->affiliatedclub = $team->stadium = $team->roster = '';
		return $team;
	}

	$sql = $wpdb->prepare("SELECT A.`title`, B.`captain`, A.`affiliatedclub`, B.`match_day`, B.`match_time`, A.`stadium`, A.`home`, A.`roster`, A.`profile`, A.`id`, A.`status`, A.`type` FROM {$wpdb->racketmanager_teams} A LEFT JOIN {$wpdb->racketmanager_team_competition} B ON A.`id` = B.`team_id` and B.`competition_id` IN (select `competition_id` FROM {$wpdb->racketmanager} WHERE `id` = '%d') WHERE A.`id` = '%d'", intval($this->id), intval($team_id));

	$team = wp_cache_get( md5($sql), 'teamdetails' );
	if ( !$team ) {
		$team = $wpdb->get_row( $sql );
		wp_cache_set( md5($sql), $team, 'teamdetails' );
	}

	if (!isset($team)) return false;

	$team->title = htmlspecialchars(stripslashes($team->title), ENT_QUOTES);
	$captain = get_userdata($team->captain);
	if ( $captain != '' ) {
		$team->captainId = $team->captain;
		$team->captain = $captain->display_name;
		$team->contactno = get_user_meta($captain->ID, 'contactno', true);
		$team->contactemail = $captain->user_email;
	} else {
		$team->captainId = 0;
		$team->captain = '';
		$team->contactno = '';
		$team->contactemail = '';
	}

	$team->affiliatedclub = stripslashes($team->affiliatedclub);
	$team->affiliatedclubname = get_club( $team->affiliatedclub )->name;
	$team->stadium = stripslashes($team->stadium);
	$team->roster = maybe_unserialize($team->roster);
	if ( $team->status == 'P' && $team->roster != null ) {
		$i = 1;
		foreach ($team->roster AS $player) {
			$teamplayer = $racketmanager->getRosterEntry($player);
			$team->player[$i] =  isset($teamplayer->fullname) ? $teamplayer->fullname : '';
			$team->playerId[$i] = $player;
			$i++;
		};
	}

	return $team;
}

/**
* get settings
*
* @param string $key settings key
* @return array
*/
public function getSettings($key=false) {
	$settings = array();
	foreach ($this->settings_keys AS $k)
	$settings[$k] = $this->$k;

	if ( $key )
	return (isset($settings[$key])) ? $settings[$key] : false;

	return $settings;
}

/**
* gets matches from database
*
* @param array $query_args
* @return array
*/
public function getMatches( $query_args ) {
	global $wpdb;

	$old_query_args = $this->match_query_args;

	// set query args
	foreach ($query_args AS $key => $value)
	$this->setMatchQueryArg($key, $value, true);

	extract($this->match_query_args, EXTR_SKIP);

	$args = array(intval($this->id));
	if ( $count )
	$sql = "SELECT COUNT(ID) FROM {$wpdb->racketmanager_matches} WHERE `league_id` = '%d'";
	else
	$sql = "SELECT `final` AS final_round, `group`, `home_team`, `away_team`, DATE_FORMAT(`date`, '%%Y-%%m-%%d %%H:%%i') AS date, DATE_FORMAT(`date`, '%%e') AS day, DATE_FORMAT(`date`, '%%c') AS month, DATE_FORMAT(`date`, '%%Y') AS year, DATE_FORMAT(`date`, '%%H') AS `hour`, DATE_FORMAT(`date`, '%%i') AS `minutes`, `match_day`, `location`, `league_id`, `home_points`, `away_points`, `winner_id`, `loser_id`, `post_id`, `season`, `id`, `custom`, `confirmed`, `home_captain`, `away_captain`, `comments` FROM {$wpdb->racketmanager_matches} WHERE `league_id` = '%d'";

	// disable limit for championship mode
	if ( $this->mode == "championship" ) $limit = false;

	if ( $season == "" ) {
		$sql .= " AND `season` = '%s'";
		$args[] = $this->current_season['name'];
	} else {
		if ($season == "any") {
			$sql .= " AND `season` != ''";
		} elseif ($this->seasonExists(htmlspecialchars($season))) {
			$sql .= " AND `season` = '%s'";
			$args[] = htmlspecialchars($season);
		}
	}

	if ($final) {
		if ($this->finalExists(htmlspecialchars(strip_tags($final)))) {
			$sql .= " AND `final` = '%s'";
			$args[] = htmlspecialchars(strip_tags($final));
			$match_day = -1;
			$limit = 0;
			$group = '';
		} else {
			$sql .= " AND `final` != ''";
		}
	} else {
		$sql .= " AND `final` = ''";
	}

	if ($team_id) {
		$sql .= " AND (`home_team` = '%d' OR `away_team` = '%d')";
		$args[] = $team_id;
		$args[] = $team_id;
	} elseif (count($team_pair) == 2 ) {
		$sql .= " AND ( (`home_team` = '%d' AND `away_team` = '%d') OR (`home_team` = '%d' AND `away_team` = '%d' ) )";
		$args[] = intval($team_pair[0]);
		$args[] = intval($team_pair[1]);
		$args[] = intval($team_pair[1]);
		$args[] = intval($team_pair[0]);
	} else {
		if (!empty($home_team)) {
			$sql .= " AND `home_team` = '%d'";
			$args[] = $home_team;
		}
		if (!empty($away_team)) {
			$sql .= " AND `away_team` = '%d'";
			$args[] = $away_team;
		}
	}
	if ( $match_day && intval($match_day) > 0 ) {
		if ( $standingstable ) {
			$sql .= " AND `match_day` <='%d'";
			$args[] = $match_day;
		} else {
			$sql .= " AND `match_day` = '%d'";
			$args[] = $match_day;
		}
	}

	// get only finished matches with score for time 'latest'
	if ( $time == 'latest' ) {
		$home_points = $away_points = false;
		$sql .= " AND (`home_points` != '' OR `away_points` != '')";
	}

	if ($home_points != "") {
		if ($home_points == "null")
		$sql .= " AND `home_points` IS NULL";
		elseif ($home_points == "not_null")
		$sql .= " AND `home_points` IS NOT NULL";
		elseif ($home_points == "not_empty")
		$sql .= " AND `home_points` != ''";
	}
	if ($away_points) {
		if ($away_points == "null")
		$sql .= " AND `away_points` IS NULL";
		elseif ($away_points == "not_null")
		$sql .= " AND `away_points` IS NOT NULL";
		elseif ($away_points == "not_empty")
		$sql .= " AND `away_points` != ''";
	}

	if ($winner_id) {
		$sql .= " AND `winner_id` = '%d'";
		$args[] = $winner_id;
	}
	if ($loser_id) {
		$sql .= " AND `loser_id` = '%d'";
		$args[] = $loser_id;
	}

	if ( $time == 'next' )
	$sql .= " AND TIMESTAMPDIFF(MINUTE, NOW(), `date`) >= 0";
	elseif ( $time == 'prev' || $time == 'latest' )
	$sql .= " AND TIMESTAMPDIFF(MINUTE, NOW(), `date`) < 0";
	elseif ( $time == 'prev1' )
	$sql .= " AND TIMESTAMPDIFF(MINUTE, NOW(), `date`) < 0) AND (`winner_id` != 0) ";
	elseif ( $time == 'today' )
	$sql .= " AND DATEDIFF(NOW(), `date`) = 0";
	elseif ( $time == 'day' )
	$sql .= " AND DATEDIFF('". htmlspecialchars(strip_tags($match_date))."', `date`) = 0";

	if ( $confirmed ) {
		$sql .= " AND `confirmed` in ('P','A','C')";
	}

	// Force ordering by date ascending if next matches are queried
	if ( $time == 'next' ) {
		$orderby['date'] = 'ASC';
	}
	// Force ordering by date descending if previous/latest matches are queried
	if ( $time == 'prev' || $time == 'latest' ) {
		$orderby['date'] = 'DESC';
	}

	// get number of matches
	if ( $count ) {
		$this->setMatchQueryArg('count', false);
		$sql = $wpdb->prepare($sql, $args);

		// Use Wordpress cache for counting matches
		$matches = wp_cache_get( md5($sql), 'num_matches' );
		if (!$matches || $cache === false) {
			$matches = intval($wpdb->get_var($sql));
			wp_cache_set( md5($sql), $matches, 'num_matches' );
		}
	} else {
		$orderby_string = ""; $i = 0;
		foreach ($orderby AS $order => $direction) {
			if (!in_array($direction, array("DESC", "ASC", "desc", "asc"))) $direction = "ASC";
			if ($this->databaseColumnExists("matches", $order)) {
				$orderby_string .= "`".$order."` ".$direction;
				if ($i < (count($orderby)-1)) $orderby_string .= ",";
			}
			$i++;
		}
		$order = $orderby_string;
		$offset = intval($limit > 0) ? ( $this->getCurrentPage() - 1 ) * $limit : 0;
		$sql .= " ORDER BY $order";
		if ( intval($limit > 0) ) $sql .= " LIMIT ".intval($offset).",".intval($limit)."";

		$sql = $wpdb->prepare($sql, $args);
		$matches = wp_cache_get( md5($sql), 'matches' );
		if (!$matches || $cache === false) {
			$matches = $wpdb->get_results( $sql );
			wp_cache_set( md5($sql), $matches, 'matches' );
		}

		$class = '';
		foreach ( $matches AS $i => $match ) {
			$match = get_match($match);
			$class = ( 'alternate' == $class ) ? '' : 'alternate';
			$match->class = $class;

			$matches[$i] = $match;
		}
		if ($limit == 1 && $matches) $matches = $matches[0];
	}

	// reset match limit
	if ($reset_limit === true) {
		$this->setMatchQueryArg('limit', $old_query_args['limit']);
		$this->setMatchQueryArg('reset_limit', false);
	}

	// reset match query args
	if ($reset_query_args === true) {
		//$this->match_query_args = $old_query_args;
		foreach ($old_query_args AS $key => $query_arg)
		$this->setMatchQueryArg($key, $query_arg, true);

		$this->setMatchQueryArg('reset_query_args', false);
	}

	if ( $count !== true) {
		$this->matches = $matches;
	} elseif ($count === true) {
		$this->num_matches = $matches;
	}

	return $matches;
}

/**
* get standings table
*
* @param array $teams
* @param int $match_day
* @param string $mode
* @param string $group
* @return array the ranked teams
*/
public function getStandings( $teams = false, $match_day = false, $mode = 'all', $group = false ) {
	if ( !$teams ) $teams = $this->getLeagueTeams( array("season" => $season['name']) );

	$rule = $this->getPointRule( $this->point_rule );
	extract( (array)$rule );

	// hide status as it's meaningless
	$this->standings['status'] = 0;

	// set basic match query args
	$this->setMatchQueryArg('standingstable', true);
	$this->setMatchQueryArg('match_day', $match_day);
	$this->setMatchQueryArg('final', '');
	$this->setMatchQueryArg('limit', false);
	$this->setMatchQueryArg('group', $group);
	$this->setMatchQueryArg('mode', $mode);

	foreach ( $teams AS $i => $team ) {
		$team = get_leagueteam($team);
		$match_args = array();

		// get only home matches
		if ( $mode == "home" )
		$match_args['home_team'] = $team->id;
		// get only away matches
		if ( $mode == "away" )
		$match_args['away_team'] = $team->id;
		// get all matches for given team
		if ( $mode == "all" )
		$match_args['team_id'] = $team->id;

		// get matches up to given match day
		if ( $match_day )
		$match_args['match_day'] = $match_day;

		// initialize team standings data
		$team->done_matches = 0;
		$team->won_matches = 0;
		$team->draw_matches = 0;
		$team->lost_matches = 0;
		$team->points_plus = 0;
		$team->points_minus = 0;
		$team->points2_plus = 0;
		$team->points2_minus = 0;

		$points = array( 'plus' => 0, 'minus' => 0 );
		$points2 = array( 'plus' => 0, 'minus' => 0 );
		$team_points = 0;

		// get matches
		$matches = $this->getMatches( $match_args );
		foreach ( $matches AS $match ) {
			if ( $match->home_points != "" && $match->away_points != "" )
			$team->done_matches += 1;

			if ( $match->winner_id == $team->id )
			$team->won_matches += 1;

			if ( $match->loser_id == $team->id )
			$team->lost_matches += 1;

			if ( $match->winner_id == -1 && $match->loser_id == -1 )
			$team->draw_matches += 1;
		}

		$team->points = $this->calculatePoints( $team, $matches );
		$team->points_plus = $team->points['plus'];
		$team->points_minus = $team->points['minus'];

		$team->points2 = $team->custom['points2'] = $this->calculateSecondaryPoints( $team, $matches );
		$team->points2_plus = $team->points2['plus'];
		$team->points2_minus = $team->points2['minus'];

		$team->diff = $team->custom['diff'] = $team->points2_plus - $team->points2_minus;
		$team->winPercent();

		$custom = $this->getStandingsData( $team, $team->custom, $matches );
		foreach ( $custom AS $key => $value )
		$team->{$key} = $value;

		$teams[$i] = $team;
	}

	/*
	* rank teams
	*/
	$teams = $this->rankTeams($teams);
	$teams = $this->getRanking($teams);

	return $teams;
}

/**
* get standings selection
*
* @param objet $league
*/
public function getStandingsSelection() {
	$selected = isset($_GET['standingstable']) ? htmlspecialchars($_GET['standingstable']) : '';

	$options = array( 'all' => __( 'Current Table', 'racketmanager' ), 'home' => __( 'Hometable', 'racketmanager' ), 'away' => __( 'Awaytable', 'racketmanager' ) );
	$action = is_admin() ? menu_page_url('racketmanager', 0)."&amp;subpage=show-league&amp;league_id=".$this->id : get_permalink();
	$out = "<select size='1' name='standingstable'>";
	foreach ( $options AS $value => $label ) {
		$out .= "<option value='".$value."'".selected($value, $selected, false).">".$label."</option>";
	}
	for ( $day = 1; $day <= $this->current_season['num_match_days']; $day++ ) {
		$out .= "<option value='match_day-".$day."'".selected("match_day-".$day, $selected, false).">".sprintf(__("%d. Match Day", 'racketmanager'), $day)."</option>";
	}
	$out .= "</select>";

	return $out;
}

/**
* set finals flag for championship mode
*
* @param boolean $is_final
*/
public function setFinals($is_final = true) {
	$this->is_final = $is_final;
}

/**
* get point rule depending on selection.
*
* @param int $rule
* @return array of points
*/
public function getPointRule( $rule = false ) {

	if (!$rule) $rule = $this->point_rule;
	$rule = maybe_unserialize($rule);

	// Manual point rule
	if ( is_array($rule) ) {
		return $rule;
	} else {
		$point_rules = array();
		// One point rule
		$point_rules['one'] = array( 'forwin' => 1, 'fordraw' => 0, 'forloss' => 0 );
		// Two point rule
		$point_rules['two'] = array( 'forwin' => 2, 'fordraw' => 1, 'forloss' => 0 );
		// Three-point rule
		$point_rules['three'] = array( 'forwin' => 3, 'fordraw' => 1, 'forloss' => 0 );
		// Score. One point for each scored goal
		$point_rules['score'] = 'score';

		/**
		* Fired when point rules are retrieved
		*
		* @param array $point_rules
		* @return array
		* @category wp-filter
		*/
		$point_rules = apply_filters( 'racketmanager_point_rules', $point_rules );

		return $point_rules[$rule];
	}
}

/**
* get number of teams for specific league
*
* @param boolean $total
*/
public function setNumTeams($total = false) {
	global $wpdb;

	if ($total === true) {
		// get total number of teams
		$this->num_teams_total = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(ID) FROM {$wpdb->racketmanager_table} WHERE `league_id` = '%d' AND `season`= '%s'", $this->id, $this->current_season['name']) );
	} else {
		$this->num_teams = $this->num_teams_total;
		$this->pagination_teams = $this->getPageLinks('teams');
	}
}

/**
* gets number of matches
*
* @param boolean $total
*/
public function setNumMatches($total=false) {

	if ($total === true) {
		$this->num_matches_total = $this->getMatches(array("count" => true, "season" => ''));
	} else {
		$this->getMatches(array("limit" => 0, "count" => true, "season" => ''));
		$this->pagination_matches = $this->getPageLinks('matches');
	}
}

/**
* get specific field for crosstable
*
* @param int $_team_id
* @param int $opponent_id
* @param int $home
* @return string
*/
public function getCrosstableField($team_id, $opponent_id, $home=0 ) {

	$match = $this->getMatches( array("home_team" => $team_id, "away_team" => $opponent_id, "match_day" => -1, "limit" => false, "reset_query_args" => true) );
	if ($match) {
		$score = $this->getScore($team_id, $opponent_id, $match[0], $home);
	} else {
		$score = "n/a";
	}

	return $score;
}

/**
* get score for specific field of crosstable
*
* @param int $team_id
* @param int $opponent_id
* @param array $match
* @param int $home
* @return string
*/
public function getScore($team_id, $opponent_id, $match, $home = 0) {

	// unplayed match
	if ( !$match || (NULL == $match->home_points && NULL == $match->away_points) ) {
		$date = ( substr($match->date, 0, 10) == '0000-00-00' ) ? 'N/A' : mysql2date('D d/m/Y', $match->date);
		$time = ( '00' == $match->hour && '00' == $match->minutes ) ? '' : mysql2date('G:i', $match->date);
		$matchDay = isset($match->match_day) ? __('Match Day', 'racketmanager').' '.$match->match_day : '';
		$out = "<span class='unplayedMatch'>".$matchDay."<br/>".$date."<br/>".$time."</span>";
		// match at home
	} elseif ( $team_id == $match->home_team ) {
		$score = sprintf("%s:%s", $match->home_points, $match->away_points);
		$result = $this->getResult($team_id, $match->winner_id, $match->loser_id);
		$out = $score."<br/>".$result;
		// match away
	} elseif ( $opponent_id == $match->home_team ) {
		$score = sprintf("%s:%s", $match->away_points, $match->home_points);
		$result = $this->getResult($team_id, $match->winner_id, $match->loser_id);
		$out = $score."<br/>".$result;
	} else {
		$out = "";
	}

	return $out;
}

/**
* get result for match
*
* @param int $team
* @param int $winner
* @param int $loser
* @return string
*/
private function getResult($team, $winner, $loser) {
	if ( $winner == $team) {
		$result = "won";
	} elseif ( $loser == $team ) {
		$result = "lost";
	} else {
		$result = "drew";
	}

	return $result;

}

/**
* default ranking function. Re-defined in sports-specific class
* 1) Primary points DESC
* 2) Games Allowed ASC
* 3) Done Matches ASC
*
* @param array $teams
* @return array
*/
protected function rankTeams($teams) {

	$points = $done = $games_allowed = array();
	foreach ( $teams AS $key => $row ) {
		$points[$key] = $row->points['plus'];
		$done[$key] = $row->done;
		$games_allowed[$key] = $row->games_allowed;
	}

	array_multisort($points, SORT_DESC, $games_allowed, SORT_ASC, $done, SORT_ASC, $teams);
	return $teams;
}

/**
* set match day selection in shortcode
*
* @param string $match_day_selection
* @param int $match_day
*/
public function setMatchDaySelection($match_day_selection, $match_day = -1) {
	if (intval($match_day) > 0) {
		$this->show_match_day_selection = false;
	} else {
		if ($match_day_selection == 'true')
		$this->show_match_day_selection = true;
		else if ($match_day_selection == 'false')
		$this->show_match_day_selection = false;
	}
}


/**
* set team selection in shortcode
*
* @param string $team_selection
* @param int $team_id
*/
public function setTeamSelection($team_selection, $team_id = 0) {
	if (intval($team_id) > 0) {
		$this->show_team_selection = false;
	} else {
		if ($team_selection == 'true')
		$this->show_team_selection = true;

		if ($team_selection == 'false')
		$this->show_team_selection = false;
	}
}


/**
* set matches selection in shortcode.
*
* @param string $show_match_day_selection
* @param int $match_day
* @param string $show_team_selection
* @param int $team_id
*/
public function setMatchesSelection($show_match_day_selection, $match_day, $show_team_selection, $team_id) {
	$this->setMatchDaySelection($show_match_day_selection, $match_day);
	$this->setTeamSelection($show_team_selection, $team_id);

	if (($this->show_match_day_selection || $this->show_team_selection) && $this->mode != 'championship')
	$this->show_matches_selection = true;
	else
	$this->show_matches_selection = false;
}

/**
* set tab in league/archive shortcodes
*
* @param boolean $is_archive
*/
public function setTab($is_archive = false) {
	if ( isset($_GET['match_day_'.$this->id]) || isset($_GET['team_id_'.$this->id]) || isset($_GET['match_paged_'.$this->id]) )
	$this->archive_tab = 2;
	if ( isset($_GET['team_'.$this->id]) )
	$this->archive_tab = 3;
	if ( isset($_GET['match_'.$this->id]) )
	$this->archive_tab = 2;
	if ( isset($_GET['team_paged_'.$this->id]) || isset($_GET['show_'.$this->teamroster]) || isset($_GET['paged_'.$this->teamroster]) )
	$this->archive_tab = 3;

	$this->is_archive = $is_archive;
}

/**
* set template in league/archive shortcodes
*
* @param string $key
* @param string $template
*/
public function setTemplate($key, $template) {
	$this->templates[$key] = $template;
}

/**
* set all templates in league/archive shortcodes
*
* @param array $templates An associative array of templatey key => template associations
*/
public function setTemplates($templates) {
	foreach ($templates AS $key => $template)
	$this->setTemplate($key, $template);
}

/**
* check if season exists
*
* @param string $season
* @return boolean
*/
private function seasonExists($season) {
	if (is_array($this->seasons) && in_array($season, array_keys($this->seasons)))
	return true;
	else
	return false;
}

/**
* check if group exists
*
* @param string $group
* @return boolean
*/
private function groupExists($group) {
	if (isset($this->groups) && is_string($this->groups)) {
		$groups = explode(";", $this->groups);
		if (in_array($group, $groups))
		return true;
	}
	return false;
}

/**
* check if final exists
*
* @param string $final
* @return boolean
*/
private function finalExists($final) {
	if (! $this->championship instanceof League_Championship)
	return false;

	$finals = $this->championship->getFinals();
	if (in_array($final, array_keys($finals)))
	return true;
	else
	return false;
}

/**
* check if database column exists
*
* @param string $table
* @param string $column
* @return boolean
*/
public function databaseColumnExists($table, $column) {
	global $wpdb;

	if ($table == "teams")
	$table = $wpdb->racketmanager_teams;
	elseif ($table == "table")
	$table = $wpdb->racketmanager_table;
	elseif ($table == "matches")
	$table = $wpdb->racketmanager_matches;
	elseif ($table == "rubbers")
	$table = $wpdb->racketmanager_rubbers;
	elseif ($table == "leagues")
	$table = $wpdb->racketmanager;
	elseif ($table == "seasons")
	$table = $wpdb->racketmanager_seasons;
	elseif ($table == "competititons")
	$table = $wpdb->racketmanager_competititons;
	else
	return false;

	$sql = $wpdb->prepare("SHOW COLUMNS FROM {$table} LIKE %s", $column);

	$res = wp_cache_get( md5($sql), 'racketmanager' );

	if ( !$res ) {
		$res = $wpdb->query( $sql );
		wp_cache_set( md5($sql), $res, 'racketmanager' );
	}
	$res = ( $res == 1 ) ? true : false;
	return $res;
}

/**
* default ranking function. Re-defined in sports-specific class
* 1) Primary points DESC
* 2) Done Matches ASC
*
* @param array $league_id
* @return boolean
*/
public function _rankTeams( $league_id, $season = FALSE) {

	if ( isset($league_id) && !$league_id == 0 ) {
		$league = get_league( $league_id );

		if ( !isset($season) )
		$season = $this->getSeason($league);

		$season = is_array($season) ? $season['name'] : $season;

		// rank Teams in groups
		$groups = !empty($league->groups) ? explode(";", $league->groups) : array( '0' );

		foreach ( $groups AS $group ) {
			$team_args = array("season" => $season);
			if ( !empty($group) ) $team_args["group"] = $group;

			$teams = $this->getLeagueTeams( $team_args );

			if ( !empty($teams) && $league->team_ranking == 'auto' ) {
				$teams = $this->rankTeams($teams, $league);
				$teams = $this->getRanking($teams);
				$this->updateRanking($teams);
			}
		}
	}
}

/**
* gets ranking of teams
*
* @param string $input serialized string with order
* @param string $listname ID of list to sort
* @return sorted array of parameters
*/
public function getRanking( $teams ) {
	$rank = 1;
	$incr = 1;
	$new_teams = array();
	foreach ($teams AS $key => $team ) {
		$team->oldRank = $team->rank;

		if ($key > 0) {
			if ( !$this->is_championship && (isset($team->points) && $this->isTie($team, $teams[$key-1]))) {
				$incr += 1;
			} else {
				$rank += $incr;
				$incr = 1;
			}
		}

		$team->rank = $rank;
		$team->status = $this->getTeamStatus($team, $rank);

		$new_teams[$key] = $team;
	}

	$new_teams = $this->tiebreak($new_teams);

	return $new_teams;
}

/**
* get team status depending on previous rank
*
* @param Team $team
* @param int $rank
* @return string
*/
private function getTeamStatus( $team, $rank ) {
	if ( $team->oldRank != 0 && $team->done_matches > 1 ) {
		if ( $rank == $team->oldRank ){
			$status = '&#8226;';
		} elseif ( $rank < $team->oldRank ){
			$status = '&#8593;';
		} else{
			$status = '&#8595;';
		}
	} else {
		$status = '&#8226;';
	}

	return $status;
}

/**
* Update Team Rank and status
*/
public function updateRanking( $teams ) {
	global $wpdb;

	foreach ( $teams AS $key => $team ) {
		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->racketmanager_table} SET `rank` = '%d', `status` = '%s' WHERE `id` = '%d'", $team->rank, $team->status, $team->table_id ) );
		wp_cache_delete($team->table_id, 'leagueteam');
		wp_cache_delete($team->league_id, 'leaguetable');
	}
}

/**
* display Season dropdown
*
* @param string $season selected season
* @return string
*/
public function getSeasonDropdown( $season = '' ) {
	$competition = get_competition($this->competition_id);
	$competition->seasons = maybe_unserialize($competition->seasons);

	$out = '<select class="form-select" size="1" id="season" name="season" onChange="Racketmanager.getMatchDropdown('.$this->id.', this.value);">';
	$out .= '<option value="">'.__('Choose Season', 'racketmanager').'</option>';
	foreach ( $competition->seasons AS $s ) {
		$out .= '<option value="'.$s['name'].'"'.selected($season, $s['name'], false).'>'.$s['name'].'</option>';
	}
	$out .= '</select>';

	return $out;
}

/**
* display match dropdown
*
* @param int $match_id selected match ID
* @return string
*/
public function getMatchDropdown( $match_id = 0 ) {

	$matches = $this->getMatches( array("limit" => false, "match_day" => -1, "reset_query_args" => true) );

	$out = '<select class="form-select" size="1" name="match_id" id="match_id" class="alignleft">';
	$out .= '<option value="0">'.__('Choose Match', 'racketmanager').'</option>';
	foreach ( $matches AS $match ) {
		$out .= '<option value="'.$match->id.'"'.selected($match_id, $match->id, false).'>'.$match->getTitle(false).'</option>';
	}
	$out .= '</select>';

	return $out;
}

/**
* =======================
* Administation section
* =======================
*/

/**
* update standings manually
*
* @param array $teams
* @param array $points_plus
* @param array $points_minus
* @param array $num_done_matches
* @param array $num_won_matches
* @param array $num_draw_matches
* @param array $num_lost_matches
* @param array $add_points
* @param array $custom
*/
public function saveStandingsManually( $teams, $points_plus, $points_minus,  $num_done_matches, $num_won_matches, $num_draw_matches, $num_lost_matches, $add_points, $custom) {
	global $wpdb;

	$season = $this->current_season['name'];

	foreach(array_keys($teams) as $id) {
		$points2_plus = isset($custom[$id]['points2']['plus']) ? $custom[$id]['points2']['plus'] : 0;
		$points2_minus = isset($custom[$id]['points2']['minus']) ? $custom[$id]['points2']['minus'] : 0;
		if ( !is_numeric($points2_plus) ) $points2_plus = 0;
		if ( !is_numeric($points2_minus) ) $points2_minus = 0;
		$diff = $points2_plus - $points2_minus;

		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->racketmanager_table} SET `points_plus` = '%d', `points_minus` = '%d', `points2_plus` = '%d', `points2_minus` = '%d', `done_matches` = '%d', `won_matches` = '%d', `draw_matches` = '%d', `lost_matches` = '%d', `diff` = '%d', `add_points` = '%d' WHERE `team_id` = '%d' and `league_id` = '%d' AND `season` = '%s'", $points_plus[$id], $points_minus[$id], $points2_plus, $points2_minus, $num_done_matches[$id], $num_won_matches[$id], $num_draw_matches[$id], $num_lost_matches[$id], $diff, $add_points[$id], $id, $this->id, $season ) );
		wp_cache_flush();
	}

	// Update Teams Rank and Status if not set to manual ranking
	if ($this->team_ranking != 'manual')
	$this->_rankTeams( $this->id, $season );
}

/**
* update match results
*
* @param array $matches
* @param array $home_points
* @param array $away_points
* @param array $home_team
* @param array $away_team
* @param array $status
* @param array $custom
* @return boolean
*/
public function _updateResults( $matches, $home_points, $away_points, $home_team, $away_team, $custom, $season, $final = false, $confirmed = "Y" ) {
	global $wpdb, $racketmanager;

	$num_matches = 0;
	if ( !empty($matches) ) {
		$matchesUpdated = array();
		foreach ($matches AS $match_id) {
			$match = get_match( $match_id );
			$matchHomePoints = $match->home_points;
			$matchAwayPoints = $match->away_points;
			$matchWinner = $match->winner_id;
			$matchLoser = $match->loser_id;
			$matchCustom = $match->custom;

			// update match results, also updating match object for subsequent custom updates
			$c = isset($custom[$match_id]) ? $custom[$match_id] : array();
			$match->updateResults( $this->sport, $home_points[$match_id], $away_points[$match_id], $c );
			// custom results update
			$match = $this->updateResults( $match ) ;
			if ( $match->home_points > 0 || $match->away_points > 0 ) {
				if ($matchHomePoints != $match->home_points || $matchAwayPoints != $match->away_points || $matchWinner != $match->winner_id || $matchLoser != $match->loser_id || $matchCustom != $match->custom || $confirmed != $match->confirmed ) {
					$wpdb->query( $wpdb->prepare("UPDATE {$wpdb->racketmanager_matches} SET `home_points` = ".$match->home_points.", `away_points` = ".$match->away_points.", `winner_id` = '%d', `loser_id` = '%d', `custom` = '%s', `updated_user` = %d, `updated` = now(), `confirmed` = %s WHERE `id` = '%d'", intval($match->winner_id), intval($match->loser_id), maybe_serialize($match->custom), get_current_user_id(), $confirmed, $match_id) );
					wp_cache_delete($match->id, 'matches');
					$num_matches ++;
					$matchesUpdated[] = $match;
				}
			}
		}
	}

	if ( $num_matches > 0 ) {
		$racketmanager->notifyFavourites($matchesUpdated, $this);
		if ( !$final ) {
			// update Standings for each team
			$leagueTeams = $this->getLeagueTeams( array("season" => $season, "cache" => false) );
			foreach ( $leagueTeams AS $i => $leagueTeam ) {
				$leagueTeams[$i] = $this->saveStandings($leagueTeam);
			}

			// Update Teams Rank and Status
			$this->_rankTeams( $this->id, $season );

		}
	}
	return $num_matches;
}

/**
* update points for given team
*
* @param int $team_id
* @return none
*/
private function saveStandings( $leagueTeam ) {
	global $wpdb;

	if ( $this->point_rule != 'manual' ) {
		$leagueTeam = get_leagueteam($leagueTeam);
		$leagueTeam->getNumDoneMatches();
		$leagueTeam->getNumWonMatches();
		$leagueTeam->getNumDrawMatches();
		$leagueTeam->getNumLostMatches();

		$leagueTeam->points = $this->calculatePoints( $leagueTeam );
		$leagueTeam->points2 = $leagueTeam->custom['points2'] = $this->calculateSecondaryPoints( $leagueTeam );
		$leagueTeam->diff = $leagueTeam->points2['plus'] - $leagueTeam->points2['minus'];

		if (!isset($leagueTeam->points2['plus']) && !isset($leagueTeam->points2['minus'])) $leagueTeam->points2 = array( 'plus' => 0, 'minus' => 0 );

		// get custom team standings data
		$leagueTeam->custom = $this->getStandingsData( $leagueTeam->id, $leagueTeam->custom );

		$wpdb->query ( $wpdb->prepare( "UPDATE {$wpdb->racketmanager_table} SET `points_plus` = '%f', `points_minus` = '%f', `points2_plus` = '%d', `points2_minus` = '%d', `done_matches` = '%d', `won_matches` = '%d', `draw_matches` = '%d', `lost_matches` = '%d', `diff` = '%d', `custom` = '%s' WHERE `team_id` = '%d' AND `league_id` = '%d' AND `season` = '%s'", $leagueTeam->points['plus'], $leagueTeam->points['minus'], $leagueTeam->points2['plus'], $leagueTeam->points2['minus'], $leagueTeam->done_matches, $leagueTeam->won_matches, $leagueTeam->draw_matches, $leagueTeam->lost_matches, $leagueTeam->diff, maybe_serialize($leagueTeam->custom), $leagueTeam->id, $leagueTeam->league_id, $leagueTeam->season ) );
		wp_cache_delete($leagueTeam->id, '$leagueteam');
		wp_cache_delete($leagueTeam->league_id, 'leaguetable');
	}

	return $leagueTeam;
}

/**
* calculate points for given team depending on point rule
*
* @param int $team_id
* @param string $option
* @return int
*/
private function calculatePoints( $team, $matches = false ) {

	$season = $team->season;

	$rule = $this->getPointRule($this->point_rule);
	$team_id = $team->id;
	$points = array( 'plus' => 0, 'minus' => 0 );
	$team_points = 0;

	if ( !$matches ) $matches = $this->getMatches( array("team_id" => $team->id, "match_day" => -1, "limit" => false, "season" => $season, "cache" => false, "reset_query_args" => true) );

	if ( 'score' == $rule ) {
		foreach ( $home AS $match ) {
			if ($team->id == $match->home_team) {
				$points['plus'] += $match->home_points;
				$points['minus'] += $match->away_points;
			} else {
				$points['plus'] += $match->away_points;
				$points['minus'] += $match->home_points;
			}
		}
	} else {
		extract( (array)$rule );
		foreach ( $matches AS $match ) {
			if ($team->id == $match->home_team) {
				$team_points += $match->home_points;
			} else {
				$team_points += $match->away_points;
			}
		}

		$points['plus'] = $team->won_matches * $forwin + $team->draw_matches * $fordraw + $team->lost_matches * $forloss + ($team_points * (isset($forscoring) ? $forscoring : 0));
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
	$points = apply_filters( 'team_points_'.$this->sport, $points, $team->id, $rule, $matches );

	return $points;
}

/**
* break ties
*
* @param array $teams
* @return array
*/
private function tiebreak( $teams ) {
	$i = 0;
	$n = count($teams);
	while ( $i < $n-1 ) {
		// current team is tied with next team
		if ( $teams[$i]->rank == $teams[$i+1]->rank && $this->isTie($teams[$i], $teams[$i+1]) ) {
			// head to head matches
			//                if ( $this->tiebreak['headToHeadTwoTeams'] == 1 ) {
			//                    $teams = $this->headToHeadTwoTeams( $teams, $i, $i+1 );
			//                }
		}

		$i++;
	}

	// re-order teams by rank
	foreach ( $teams AS $key => $row ) {
		$rank[$key] = $row->rank;
	}
	array_multisort($rank, SORT_ASC, $teams);

	return $teams;
}

/**
* ========================
* Sports customization section. The following functions can be overriden by sports class
* ========================
*/

/**
* determine if two teams are tied based on
*
* 1) Primary points
* 2) Secondary point difference
* 3) Secondary points
* 4) Win Percentage
*
* @param Team $team1
* @param Team $team2
* @return boolean
*/
protected function isTie( $team1, $team2 ) {
	// initialize results array
	$res = array('primary' => false, 'diff' => false, 'secondary' => false, 'winpercent' => false);

	if ( $team1->points['plus'] == $team2->points['plus'] )
	$res['primary'] = true;

	if ( $team1->diff == $team2->diff )
	$res['diff'] = true;

	if ( $team1->points2['plus'] == $team2->points2['plus'] )
	$res['secondary'] = true;

	if ( $team1->winPercent == $team2->winPercent )
	$res['winpercent'] = true;

	// get unique results
	$res = array_values(array_unique($res));

	// more than one results, i.e. not tied
	if ( count($res) > 1 )
	return false;

	return $res[0];
}

/**
* custom update results method
*
* @param Match $match
* @return Match
*/
protected function updateResults($match) {
	return $match;
}

/**
* calculate secondary points
*
* @param Team $team
* @param array $matches (optional)
* @param array An associative array with fields 'plus' and 'minus'
*/
protected function calculateSecondaryPoints($team, $matches = false) {
	$points = array('plus' => 0, 'minus' => 0);

	// general secondary points calculated from sum of primary points, e.g. soccer, handball, basketball
	if ( isset($this->fields_team['points2']) ) {
		if (!$matches) $matches =  $this->getMatches( array("team_id" => $team->id, "match_day" => -1, "limit" => false, "cache" => false, "reset_query_args" => true) );
		if ( $matches ) {
			foreach ( $matches AS $match ) {
				$custom = maybe_unserialize($match->custom);
				$home_goals = $match->home_points;
				$away_goals = $match->away_points;

				if ( $match->home_team == $team->id ) {
					$points['plus'] += $home_goals;
					$points['minus'] += $away_goals;
				} else {
					$points['plus'] += $away_goals;
					$points['minus'] += $home_goals;
				}
			}
		}
	}

	return $points;
}

/**
* get custom standings data
*
* @param Team $team
* @param array $data
* @param array|false $matches
* @return array
*/
protected function getStandingsData($team, $data, $matches = false) {
	return $data;
}

/**
* add custom standings table display options
*
* @param array $options
* @return array
*/
public function standingsTableDisplayOptions( $options ) {
	if ( count($this->fields_team) > 0 ) {
		foreach ( $this->fields_team AS $key => $data ) {
			$options[$key] = isset($data['desc']) ? $data['desc'] : $data['label'];
		}
	}

	return $options;
}

/**
* display custom standings header
*
*/
public function displayStandingsHeader() {
	global $league;
	if ( count($this->fields_team) > 0 ) {
		foreach ( $this->fields_team AS $key => $data ) {
			if ( show_standings($key) || (is_admin() && get_league_pointrule() == 'manual') ) {
				echo '<th class="manage-column column-'.$key.' column-num d-none d-md-table-cell">'.$data['label'].'</th>';
			}
		}
	}
}

/**
* display custom standings columns
*
* @param Team $team
* @param string $rule
*/
public function displayStandingsColumns($team, $rule) {
	if ( count($this->fields_team) > 0 ) {
		foreach ( $this->fields_team AS $key => $data ) {
			if ( !isset($team->{$key}) ) {
				if ( isset($data['keys']) ) {
					$team->{$key} = array();
					foreach ($data['keys'] AS $k)
					$team->{$key}[$k] = '';
				} else {
					$team->{$key} = '';
				}
			}

			if ( (isset($data['type']) && $data['type'] == 'input') && is_admin() && $rule == 'manual' ) {
				echo '<td class="column-'.$key.' column-num d-none d-md-table-cell" data-colname="'.$data['label'].'">';
				if ( is_array($team->{$key}) ) {
					foreach ( $team->{$key} AS $k => $v ) {
						echo '<input class="points" type="text" size="2" id="home_'.$team->id.'_'.$k.'" name="custom['.$team->id.']['.$key.']['.$k.']" value="'.$team->{$key}[$k].'" />';
					}
				} else {
					echo '<input class="points" type="text" size="2" id="home_'.$team->id.'" name="custom['.$team->id.']['.$key.']" value="'.$team->{$key}.'" />';
				}
				echo '</td>';
			} elseif ( show_standings($key) ) {
				if (is_array($team->{$key})) {
					//$team->{$key} = array_values($team->{$key});
					$team->{$key} = vsprintf($this->point_format2, $team->{$key});
				}
				echo '<td class="num column-'.$key.' d-none d-md-table-cell d-none d-md-table-cell" data-colname="'.$data['label'].'">'.$team->{$key}.'</td>';
			}
		}
	}
}

/**
* display custom standings header
*
*/
public function displayMatchesHeader() {
	if ( count($this->fields_match) > 0 ) {
		foreach ( $this->fields_match AS $key => $data ) {
			echo '<th class="manage-column column-'.$key.' column-num">'.$data['label'].'</th>';
		}
	}
}

/**
* display custom standings columns
*
* @param Match $match
*/
public function displayMatchesColumns($match) {
	if ( count($this->fields_match) > 0 ) {
		foreach ( $this->fields_match AS $key => $data ) {
			if (!isset($match->{$key})) {
				$x = array();
				$tmp_key = array_keys($data['keys'])[0];
				if ( isset($data['keys']) && is_array($data['keys'][$tmp_key]) ) {
					foreach ( $data['keys'] AS $k => $v ) {
						$x[$k] = array();
						$x[$k][$v[0]] = '';
						$x[$k][$v[1]] = '';
					}
				} else {
					if ( isset($data['keys']) ) {
						$x[$data['keys'][0]] = '';
						$x[$data['keys'][1]] = '';
					} else {
						$x = '';
					}
				}
				$match->{$key} = $x;
			}

			echo '<td class="column-'.$key.' column-input column-num" data-colname="'.$data['label'].'">';

			if ( isset($data['url']) ) {
				echo '<a href="'.$data['url'].'&league_id='.$match->league_id.'&season='.$match->season.'&match='.$match->id.'">'.$data['label'].'</a>';
			} elseif ( isset($data['keys']) && is_array($data['keys'][array_keys($data['keys'])[0]]) ) {
				// two-dimensional match keys, e.g. Basketball quarters, volleyball/tennis setSeason
				foreach ( $data['keys'] AS $k => $v ) {
					echo '<p>';
					foreach ( $v AS $f ) {
						echo '<input class="points" type="text" size="2" id="'.$key.'_'.$k.'_'.$f.'_'.$match->id.'" name="custom['.$match->id.']['.$key.']['.$k.']['.$f.']" value="'.$match->{$key}[$k][$f].'" />';
					}
					echo '</p>';
				}
			} else {
				if ( isset($data['keys']) ) {
					foreach ( $data['keys'] AS $f ) {
						echo '<input class="points" type="text" size="2" id="'.$key.'_'.$f.'_'.$match->id.'" name="custom['.$match->id.']['.$key.']['.$f.']" value="'.$match->{$key}[$f].'" />';
					}
				} else {
					echo '<input class="points" type="text" size="2" id="'.$key.'_'.$match->id.'" name="custom['.$match->id.']['.$key.']" value="'.$match->{$key}.'" />';
				}
			}
			echo '</td>';
		}
	}
}

/**
* import matches
*
* @param array $custom
* @param array $line
* @param int $match_id
* @param int $col the starting column index
* @return array
*/
public function importMatches( $custom, $line, $match_id, $col ) {
	if ( count($this->fields_match) > 0 ) {
		foreach ( $this->fields_match AS $key => $data ) {
			if ( isset($data['keys']) && is_array($data['keys'][array_keys($data['keys'])[0]]) ) {
				foreach ( $data['keys'] AS $k => $v ) {
					$p = (isset($line[$col]) && !empty($line[$col])) ? explode("-", $line[$col]) : array('','');

					$x = array();
					$x[$v[0]] = $p[0];
					$x[$v[1]] = $p[1];

					$custom[$match_id][$key][$k] = $x;

					$col++;
				}
			} else {
				if ( isset($data['keys']) ) {
					$p = (isset($line[$col]) && !empty($line[$col])) ? explode("-", $line[$col]) : array('','');

					$x = array();
					$x[$data['keys'][0]] = $p[0];
					$x[$data['keys'][1]] = $p[1];
				} else {
					$x = (isset($line[$col]) && !empty($line[$col])) ? $line[$col] : '';
				}

				$custom[$match_id][$key] = $x;

				$col++;
			}

		}
	}

	return $custom;
}

/**
* import teams
*
* @param array $custom
* @param array $line
* @param int $col the starting column index
* @return array
*/
public function importTeams( $custom, $line, $col ) {
	if ( count($this->fields_team) > 0 ) {
		foreach ( $this->fields_team AS $key => $data ) {
			if ( isset($data['keys']) ) {
				$p = (isset($line[$col]) && !empty($line[$col])) ? explode("-", $line[$col]) : array('','');
				$x = array();
				$x[$data['keys'][0]] = $p[0];
				$x[$data['keys'][1]] = $p[1];
				$custom[$key] = $x;
			} else {
				$custom[$key] = isset($line[$col]) ? $line[$col] : 0;
			}

			$col++;
		}
	}

	return $custom;
}

}

/**
* get League object
*
* @param int|League|null League ID or league object. Defaults to global $league
* @return League|null
*/
function get_league( $league = null ) {
	if ( empty( $league ) && isset( $GLOBALS['league'] ) ) {
		$league = $GLOBALS['league'];
	}

	if ( $league instanceof League ) {
		$_league = $league;
	} elseif ( is_object( $league ) ) {
		// check if specific sports class exists
		if ( !isset($league->sport) ) $league->sport = '';
		$instance = "League_". ucfirst($league->sport);
		if (class_exists($instance)) {
			$_league = new $instance( $league );
		} else {
			$_league = new League( $league );
		}
	} else {
		$_league = League::get_instance( $league );
	}

	if ( ! $_league )
	return null;

	return $_league;
}
?>
