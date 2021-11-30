<?php
/**
 * Competition API: Competition class
 *
 * @author Paul Moffat
 * @package LeagueManager
 * @subpackage Competition
 */

/**
 * Class to implement the Competition object
 *
 */
class Competition {
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
	public $sport = "tennis";

	/**
	 * point rule
	 *
	 * @var string
	 */
	public $point_rule = "tennis";

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
	public $default_match_start_time = array("hour" => 19, "minutes" => 30);

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
	public $num_matches_per_page = 10;

    /**
     * league offsets indexed by ID
     *
     * @var array
     */
    public $league_index = array();

    /**
     * league loop
     *
     * @var boolean
     */
    public $in_the_league_loop = false;

    /**
     * current league
     *
     * @var int
     */
    public $league_team = -1;

    /**
     * custom team input field keys and translated labels
     *
     * @var array
     */
    public $fields_team = array();

    /**
     * championship flag
     *
     * @var boolean
     */
    public $is_championship = false;

    /**
     * retrieve competition instance
     *
     * @param int $competition_id
     */
	public static function get_instance($competition_id) {
		global $wpdb;

		$competition_id = (int) $competition_id;
		if ( ! $competition_id )
			return false;

		$competition = wp_cache_get( $competition_id, 'competitions' );

		if ( ! $competition ) {
			$competition = $wpdb->get_row( $wpdb->prepare( "SELECT `name`, `id`, `num_sets`, `num_rubbers`, `type`, `settings`, `seasons` FROM {$wpdb->leaguemanager_competitions} WHERE `id` = '%d'", $competition_id ) );
			$competition->settings = (array)maybe_unserialize($competition->settings);
			$competition = (object)array_merge((array)$competition, $competition->settings);

			if ( !$competition ) return false;

            // check if specific sports class exists
            if ( !isset($competition->sport) ) $competition->sport = '';
            $instance = "Competition_". ucfirst($competition->sport);
            if (class_exists($instance)) {
                $competition = new $instance( $competition );
            } else {
                $competition = new Competition( $competition );
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
        global $leaguemanager;

        if (isset($competition->settings)) {
            $competition->settings = (array)maybe_unserialize($competition->settings);
            $competition->settings_keys = array_keys((array)maybe_unserialize($competition->settings));
            $competition = (object)array_merge((array)$competition, $competition->settings);
        }

        foreach ( get_object_vars( $competition ) as $key => $value ) {
            if ( $key == "standings")
                $this->$key = array_merge($this->$key, $value);
            else
                $this->$key = $value;
        }

        $this->name = stripslashes($this->name);
        $this->num_rubbers = stripslashes($this->num_rubbers);
        $this->num_sets = stripslashes($this->num_sets);
        $this->type = stripslashes($this->type);

        // set seasons
        if ( $this->seasons == '' ) $this->seasons = array();
        $this->seasons = (array)maybe_unserialize($this->seasons);
        $this->num_seasons = count($this->seasons);
        $this->setNumLeagues(true);

        // set default standings display options for additional team fields
        if ( count($this->fields_team) > 0 ) {
            foreach ( $this->fields_team AS $key => $data ) {
                if ( !isset($this->standings[$key]) )
                    $this->standings[$key] = 1;
            }
        }

        // set season to latest
        if ( $this->num_seasons > 0 ) $this->setSeason();

        // Championship
        if ( $this->mode == "championship" ) {
            $this->is_championship = true;
        }

        // add actions & filter
        add_filter( 'competition_standings_options', array(&$this, 'standingsTableDisplayOptions') );
    }

	/**
	 * set current season
	 *
	 * @param mixed $season
	 * @param boolean $force_overwrite
	 */
	public function setSeason( $season = false, $force_overwrite = false ) {
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
		} elseif ( !empty($season) ) {
			$data = $this->seasons[$season];
		} else {
			$data = end($this->seasons);
		}

		if (empty($data)) $data = end($this->seasons);

		$this->current_season = $data;
		$this->num_match_days = $data['num_match_days'];
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
     * get current season
     *
     * @param object $league
     * @param mixed $season
     * @return array
     */
    public function getSeasonCompetition( $season = false, $index = false ) {

        if ( isset($_GET['season']) && !empty($_GET['season']) ) {
            $key = htmlspecialchars(strip_tags($_GET['season']));
            if (!isset($this->seasons[$key]))
                return false;

            $data = $this->seasons[$key];
        } elseif ( isset($_GET['season_'.$this->id]) ) {
            $key = htmlspecialchars(strip_tags($_GET['season_'.$this->id]));
            if (!isset($this->seasons[$key]))
                return false;

            $data = $this->seasons[$key];
        } elseif ( $season ) {
            $data = $this->seasons[$season];
        } elseif ( !empty($this->seasons) ) {
            $data = end($this->seasons);
        } else {
            return false;
        }

        if ( $index )
            return $data[$index];
        else
            return $data;
    }

    /**
     * gets number of leagues
     *
     * @param boolean $total
     */
    public function setNumLeagues($total=false) {
        global $wpdb;

        if ($total === true) {
            $this->num_leagues = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(ID) FROM {$wpdb->leaguemanager} WHERE `competition_id` = '%d'", $this->id) );
        }
    }

    /**
     * get leagues from database
     *
     * @param int $league_id (default: false)
     * @param string $search
     * @return array
     */
    public function getLeagues( $args = array() ) {
        global $wpdb;

        $defaults = array( 'offset' => 0, 'limit' => 99999999, 'competition' => false, 'orderby' => array("title" => "ASC") );
        $args = array_merge($defaults, $args);
        extract($args, EXTR_SKIP);

        $search_terms = array();
        if ( $competition ) {
            $search_terms[] = $wpdb->prepare("`competition_id` = '%d'", intval($competition));
        }

        $search = "";
        if (count($search_terms) > 0) {
            $search = " WHERE ";
            $search .= implode(" AND ", $search_terms);
        }

        $orderby_string = ""; $i = 0;
        foreach ($orderby AS $order => $direction) {
            if (!in_array($direction, array("DESC", "ASC", "desc", "asc"))) $direction = "ASC";
            $orderby_string .= "`".$order."` ".$direction;
            if ($i < (count($orderby)-1)) $orderby_string .= ",";
            $i++;
        }
        $orderby = $orderby_string;
        $sql = $wpdb->prepare( "SELECT `title`, `id`, `settings`, `competition_id` FROM {$wpdb->leaguemanager} $search ORDER BY $orderby LIMIT %d, %d", intval($offset), intval($limit) );
        $leagues = wp_cache_get( md5($sql), 'leagues' );
        if ( !$leagues ) {
            $leagues =  $wpdb->get_results($sql);
            wp_cache_set( md5($sql), $leagues, 'leagues' );
        }

        $league_index = array();
        foreach ( $leagues AS $i => $league ) {

            $league_index[$league->id] = $i;
            $leagues[$i] = $league;
        }

        $this->leagues = $leagues;
        $this->league_index = $league_index;

        return $leagues;
    }

    /**
     * get league from database
     *
     * @param string $title (default: false)
     * @return int $league_id
     */
    public function getLeagueId( $title ) {
        global $wpdb;

        $sql = $wpdb->prepare( "SELECT `id` FROM {$wpdb->leaguemanager} WHERE `title` = '%s'", $title );
        $league = wp_cache_get( md5($sql), 'league' );
        if ( !$league ) {
            $league =  $wpdb->get_row($sql);
            wp_cache_set( md5($sql), $league, 'league' );
        }

        if ( !$league ) return 0;

        return $league->id;
    }

    /**
     * get player stats
     *
     * @param array $query_args
     * @return array
     */
    public function getPlayerStats( $args ) {
        global $wpdb;

        $defaults = array( 'season' => false, 'cache' => true, 'club' => false, 'league_id' => false, 'system' => false, 'roster' => false );
        $args = array_merge($defaults, (array)$args);
        extract($args, EXTR_SKIP);

        $sql1 = "SELECT p.ID AS `player_id`, p.`display_name` AS `fullname`, ro.`id` AS `roster_id`,  ro.`affiliatedclub` FROM {$wpdb->leaguemanager_roster} AS ro, {$wpdb->users} AS p WHERE ro.`player_id` = p.`ID`";
        $sql2 = "FROM {$wpdb->leaguemanager_teams} AS t, {$wpdb->leaguemanager_rubbers} AS r, {$wpdb->leaguemanager_matches} AS m, {$wpdb->leaguemanager_roster} as ro WHERE r.`winner_id` != 0 AND (((r.`home_player_1` = ro.`id` OR r.`home_player_2` = ro.`id`) AND  m.`home_team` = t.`id`) OR ((r.`away_player_1` = ro.`id` OR r.`away_player_2` = ro.`id`) AND m.`away_team` = t.`id`)) AND ro.`affiliatedclub` = t.`affiliatedclub` AND r.`match_id` = m.`id` AND m.`league_id` IN (SELECT `id` FROM {$wpdb->leaguemanager} WHERE `competition_id` = '%d') ";

        $search_terms1 = array();
        $search_terms2 = array($this->id);

        if ($season) {
            $sql2 .= " AND m.`season` = '%s'";
            $search_terms2[] = htmlspecialchars(strip_tags($season));
        }
        if ($league_id) {
            $sql2 .= " AND m.`league_id` = '%d'";
            $search_terms2[] = intval($league_id);
        }
        if ($club) {
            $sql2 .= " AND ro.`affiliatedclub` = '%d'";
            $search_terms2[] = intval($club);
        }
        if ($roster) {
            $sql2 .= " AND ro.`id` = '%d'";
            $search_terms2[] = intval($roster);
        }
        if (!$system) {
            $sql2 .= " AND ro.`system_record` IS NULL";
        }

        $order = "`affiliatedclub`, `fullname` ";

        $sql = $sql1." AND ro.`id` in (SELECT ro.id ".$sql2.")";

        if ( $order != "") $sql .= " ORDER BY $order";

        $sql = $wpdb->prepare($sql, $search_terms2);
        $playerstats = wp_cache_get( md5($sql), 'playerstats' );
        if ( !$playerstats ) {
            $playerstats = $wpdb->get_results( $sql );
            wp_cache_set( md5($sql), $playerstats, 'playerstats' );
        }

        foreach ( $playerstats AS $i => $playerstat ) {

            $sql3 = "SELECT t.`id` AS team_id,  t.`title` AS team_title, m.`season`, m.`match_day`, m.`home_team`, m.`away_team`, m.`winner_id` AS match_winner, m.`home_points`, m.`away_points`, m.`loser_id` AS match_loser, r.`rubber_number`, r.`home_player_1`, r.`home_player_2`, r.`away_player_1`, r.`away_player_2`, r.`winner_id` AS rubber_winner, r.`loser_id` AS rubber_loser, r.`custom`, m.`final`";
            $sql3 .= $sql2." AND ro.`ID` = ".$playerstat->roster_id;
            $sql3 .= " ORDER BY m.`season`, m.`match_day`";

            $sql = $wpdb->prepare($sql3, $search_terms2);
            $stats = wp_cache_get( md5($sql), 'playerstats' );
            if ( !$stats ) {
                $stats = $wpdb->get_results( $sql );
                wp_cache_set( md5($sql), $stats, 'playerstats' );
            }

            foreach ( $stats AS $s => $stat ) {

                $stat->custom = stripslashes_deep(maybe_unserialize($stat->custom));
                $stats[$s] = $stat;

            }

            $playerstat->matchdays = $stats;
            $playerstats[$i] = (object)(array)$playerstat;
        }

        return $playerstats;
    }

    /**
     * get teams from database
     *
     * @param array $args
     * @param string $output OBJECT | ARRAY
     * @return array database results
     */
    public function getTeamsInfo( $args = array() ) {
        global $wpdb, $leaguemanager;

        $defaults = array( 'league_id' => false, 'season' => false, 'group' => false, 'rank' => false, 'orderby' => array("rank" => "ASC", "title" => "ASC"), "home" => false, "cache" => true, 'affiliatedclub' => false );
        $args = array_merge($defaults, $args);
        extract($args, EXTR_SKIP);

        $search_terms = array();
        if ( $league_id ) {
            if ($league_id == "any")
                $search_terms[] = "A.`league_id` != ''";
            else
                $search_terms[] = $wpdb->prepare("A.`league_id` = '%d'", intval($league_id));
        }
        if ( $affiliatedclub ) {
            $search_terms[] = $wpdb->prepare("`affiliatedclub` = '%d'", intval($affiliatedclub));
        }
        if ( $season ) {
            $search_terms[] = $wpdb->prepare("A.`season` = '%s'", htmlspecialchars($season));
        }
        if ( $rank )
            $search_terms[] = $wpdb->prepare("A.`rank` = '%s'", $rank);

        if ( $home )
            $search_terms[] = "B.`home` = 1";

        $search = "";
        if (count($search_terms) > 0) {
            $search = " AND ";
            $search .= implode(" AND ", $search_terms);
        }

        $orderby_string = ""; $i = 0;
        foreach ($orderby AS $order => $direction) {
            if (!in_array($direction, array("DESC", "ASC", "desc", "asc"))) $direction = "ASC";
            $orderby_string .= "`".$order."` ".$direction;
            if ($i < (count($orderby)-1)) $orderby_string .= ",";
            $i++;
        }
        $orderby = $orderby_string;

        $sql = "SELECT DISTINCT B.`id`, B.`title`, C.`captain`, B.`affiliatedclub`, B.`stadium`, B.`home`, B.`roster`, B.`profile`, C.`match_day`, C.`match_time` FROM {$wpdb->leaguemanager_teams} B, {$wpdb->leaguemanager_table} A, {$wpdb->leaguemanager_team_competition} C WHERE B.id = A.team_id AND A.team_id = C.team_id and C.competition_id in (select `competition_id` from {$wpdb->leaguemanager} WHERE `id` = A.league_id) AND C.`competition_id` = ".$this->id." $search ORDER BY $orderby";

        $teams = wp_cache_get( md5($sql), 'teams' );
        if ( !$teams ) {
            $teams = $wpdb->get_results( $sql );
            wp_cache_set( md5($sql), $teams, 'teams' );
        }

        $class = '';
        foreach ( $teams AS $i => $team ) {
            $class = ( 'alternate' == $class ) ? '' : 'alternate';
            $captain = get_userdata($team->captain);
            $team->roster = maybe_unserialize($team->roster);
            $team->title = htmlspecialchars(stripslashes($team->title), ENT_QUOTES);
            if ( $captain != '' ) {
                $team->captain = $captain->display_name;
                $team->captainId = $captain->ID;
                $team->contactno = get_user_meta($captain->ID, 'contactno', true);
                $team->contactemail = $captain->user_email;
            } else {
                $team->captain = 'Unknown';
                $team->captainId = '';
                $team->contactno = '';
                $team->contactemail = '';
            }
            $team->affiliatedclub = stripslashes($team->affiliatedclub);
            $team->affiliatedclubname = get_club( $team->affiliatedclub )->name;
            $team->stadium = stripslashes($team->stadium);
            $team->class = $class;
            $teams[$i] = $team;
        }

        return $teams;
    }

    /**
     * get specific team details from database
     *
     * @param int #team_id
     * @return array database results
     */
    public function getTeamInfo( $team_id ) {
        global $wpdb;

        $sql = "SELECT `captain`, `match_day`, `match_time` FROM {$wpdb->leaguemanager_team_competition} WHERE `competition_id` = ".$this->id." AND `team_id` = ".$team_id;

        $team = wp_cache_get( md5($sql), 'team' );
        if ( !$team ) {
            $team = $wpdb->get_row( $sql );
            wp_cache_set( md5($sql), $team, 'team' );
        }

        $captain = get_userdata($team->captain);
        if ( $captain != '' ) {
            $team->captain = $captain->display_name;
            $team->captainId = $captain->ID;
            $team->contactno = get_user_meta($captain->ID, 'contactno', true);
            $team->contactemail = $captain->user_email;
        } else {
            $team->captain = 'Unknown';
            $team->captainId = '';
            $team->contactno = '';
            $team->contactemail = '';
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
     * reload settings from database
     */
    public function reloadSettings() {
        global $wpdb;

        $result = $wpdb->get_row( $wpdb->prepare("SELECT `settings` FROM {$wpdb->leaguemanager_competitions} WHERE `id` = '%d'", intval($this->id)) );
        foreach ( maybe_unserialize($result->settings) as $key => $value )
            $this->$key = $value;
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

}

/**
 * get Competition object
 *
 * @param int|Competition|null Competition ID or competition object. Defaults to global $competition
 * @return League|null
 */
function get_competition( $competition = null ) {
    if ( empty( $competition ) && isset( $GLOBALS['competition'] ) )
        $competition = $GLOBALS['competition'];

    if ( $competition instanceof Competition ) {
        $_competition = $competition;
    } elseif ( is_object( $competition ) ) {
        // check if specific sports class exists
        if ( !isset($competition->sport) ) $competition->sport = '';
        $instance = "Competition_". ucfirst($competition->sport);
        if (class_exists($instance)) {
            $_competition = new $instance( $competition );
        } else {
            $_competition = new Competition( $competition );
        }
    } else {
        $_competition = Competition::get_instance( $competition );
    }

    if ( ! $_competition )
        return null;

    return $_competition;
}
?>
