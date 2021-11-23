<?php
/**
 * Match API: Match class
 *
 * @author Kolja Schleich
 * @package LeagueManager
 * @subpackage Match
 */

/**
 * Class to implement the Match object
 *
 */
final class Match {

    /**
     * final flag
     *
     * @var string
     */
    public $final_round = '';

	/**
	 * retrieve match instance
	 *
	 * @param int $match_id
	 */
	public static function get_instance($match_id) {
		global $wpdb;

		$match_id = (int) $match_id;
		if ( ! $match_id )
			return false;

		$match = wp_cache_get( $match_id, 'matches' );
		if ( ! $match ) {
			$match = $wpdb->get_row( $wpdb->prepare("SELECT `final` AS final_round, `group`, `home_team`, `away_team`, DATE_FORMAT(`date`, '%%Y-%%m-%%d %%H:%%i') AS date, DATE_FORMAT(`date`, '%%e') AS day, DATE_FORMAT(`date`, '%%c') AS month, DATE_FORMAT(`date`, '%%Y') AS year, DATE_FORMAT(`date`, '%%H') AS `hour`, DATE_FORMAT(`date`, '%%i') AS `minutes`, `match_day`, `location`, `league_id`, `home_points`, `away_points`, `winner_id`, `loser_id`, `post_id`, `season`, `id`, `custom`, `updated`, `updated_user`, `confirmed`, `home_captain`, `away_captain` FROM {$wpdb->leaguemanager_matches} WHERE `id` = '%d' LIMIT 1", $match_id) );

			if ( !$match ) return false;

			$match = new Match( $match );

			wp_cache_set( $match->id, $match, 'matches' );
 		}

		return $match;
	}

    /**
     * Constructor
     *
     * @param object $match Match object.
     */
    public function __construct( $match = null ) {
        if ( !is_null($match) ) {
            if ( isset($match->custom) ) {
                $match->custom = stripslashes_deep((array)maybe_unserialize($match->custom));
                $match = (object)array_merge((array)$match, (array)$match->custom);
            }

            foreach ( get_object_vars( $match ) as $key => $value )
                $this->$key = $value;

            // get League Object
            $this->league = get_league();
            if ( is_null($this->league) || (!is_null($this->league) && $this->league->id != $this->league_id) )
                $this->league = get_league($this->league_id);

            $this->location = $this->location != '' ? stripslashes($this->location) : 'N/A';
            $this->report = ( $this->post_id != 0 ) ? '<a href="'.get_permalink($this->post_id).'">'.__('Report', 'leaguemanager').'</a>' : '';

            $this->hadOvertime = ( isset($this->overtime) && $this->overtime['home'] != '' && $this->overtime['away'] != '' ) ? true : false;
            $this->hadPenalty = ( isset($this->penalty) && $this->penalty['home'] != '' && $this->penalty['away'] != '' ) ? true : false;
            if ( $this->hadPenalty ) {
                $this->homeScore = $this->penalty['home']+$this->overtime['home'];
                $this->awayScore = $this->penalty['away']+$this->overtime['away'];
                $this->score = sprintf("%d - %d", $this->homeScore, $this->awayScore)." ".__( '(o.P.)', 'leaguemanager' );
            } elseif ( $this->hadOvertime ) {
                $this->homeScore = $this->overtime['home'];
                $this->awayScore = $this->overtime['away'];
                $this->score = sprintf("%d - %d", $this->homeScore, $this->awayScore)." ".__( '(AET)', 'leaguemanager' );
            } elseif ( $this->home_points != "" && $this->away_points != "" ) {
                $this->homeScore = $this->home_points;
                $this->awayScore = $this->away_points;
                $this->score = sprintf("%d - %d", $this->homeScore, $this->awayScore);
            } else {
                $this->homeScore = "-";
                $this->awayScore = "-";
                $this->score = sprintf("%s:%s", $this->homeScore, $this->awayScore);
            }

			if (is_admin()) {
				$url = '';
			} else {
				$url = esc_url(get_permalink());
				$url = add_query_arg( 'match_'.$this->league_id, $this->id, $url );
				foreach ( $_GET AS $key => $value ) {
					$url = add_query_arg( $key, htmlspecialchars(strip_tags($value)), $url );
				}
				$url = remove_query_arg( 'team_'.$this->league_id, $url );
			}
			$this->pageURL = esc_url($url);

			$this->setTeams();

			$this->setDate();
			$this->setTime();

            $this->is_home = $this->isHome();
			$this->match_title = $this->getTitle();

			// set selected marker
			if (isset($_GET['match_'.$this->league_id])) $this->is_selected = true;
        }
    }

    /**
     * get Team objects
     *
     */
    private function setTeams() {
        // get championship final rounds teams
        if ( $this->league->championship instanceof League_Championship ) {
            $teams = $this->league->championship->getFinalTeams($this->final_round);
        }
        if ( is_numeric($this->home_team) ) {
            if ( $this->home_team == -1 ) {
                $this->teams['home'] = (object)array('id' => -1, 'title' => "Bye");
            } else {
                $this->teams['home'] = $this->league->getLeagueTeam($this->home_team);
            }
        } else {
            $this->teams['home'] = $teams[$this->home_team];
        }
        if ( is_numeric($this->away_team) ) {
            if ( $this->away_team == -1 ) {
                $this->teams['away'] = (object)array('id' => -1, 'title' => "Bye");
            } else {
                $this->teams['away'] = $this->league->getLeagueTeam($this->away_team);
            }
        } else {
            $this->teams['away'] = $teams[$this->away_team];
        }
    }

    /**
     * get match title
     *
     * @return string
     */
    public function getTitle() {

        // set default title
        $title = "N/A";

        $homeTeam = $this->teams['home'];
        $awayTeam = $this->teams['away'];

        if ( isset($this->title) && (!$homeTeam || !$awayTeam || $this->home_team == $this->away_team) ) {
            $title = stripslashes($this->title);
        } else {
            $home_team_name = $this->is_home ? "<strong>".$homeTeam->title."</strong>" : $homeTeam->title;
            $away_team_name = $this->is_home ? "<strong>".$awayTeam->title."</strong>" : $awayTeam->title;

            $title = sprintf("%s &#8211; %s", $home_team_name, $away_team_name);
        }

         return $title;
    }

    /**
     * test if it's a match of home team
     *
     * @return boolean
     */
    private function isHome() {
        if ( !isset($this->teams) ) return false;
        if ( isset($this->teams['home']) && $this->teams['home'] && isset($this->teams['home']->home) && $this->teams['home']->home == 1 )
            return true;
        elseif ( isset($this->teams['away']) && $this->teams['away'] && isset($this->teams['away']->home) && $this->teams['away']->home == 1 )
            return true;
        else
            return false;
    }

    /**
     * set match date
     *
     * @param string $date_format
     */
    public function setDate($date_format = '') {
        if ($date_format == '') $date_format = get_option('date_format');
        $this->match_date = ( substr($this->date, 0, 10) == '0000-00-00' ) ? 'N/A' : mysql2date($date_format, $this->date);
        $this->setTooltipTitle();
    }

    /**
     * set match start time
     *
     * @param string $time_format
     */
    public function setTime($time_format = '') {
        if ($time_format == '') $time_format = get_option('time_format');
        //$this->start_time = ( '00:00' == $this->hour.":".$this->minutes ) ? '' : mysql2date($time_format, $this->date);
        $this->start_time = mysql2date($time_format, $this->date);
    }

    /**
     * set tooltip title
     *
     */
    private function setTooltipTitle() {
        // make tooltip title for last-5 standings
        if ( $this->home_points == "" && $this->away_points == "")
            $tooltipTitle = 'Next Match: '.$this->teams['home']->title.' - '.$this->teams['away']->title.' ['.$this->match_date.']';
        elseif ( isset($this->title) )
            $tooltipTitle = stripslashes($this->title) .' ['.$this->match_date.']';
        else
            $tooltipTitle = $this->homeScore.':'.$this->awayScore. ' - '.$this->teams['home']->title.' - '.$this->teams['away']->title.' ['.$this->match_date.']';

        $this->tooltipTitle = $tooltipTitle;
    }

    public function updateResults( $sport, $home_points, $away_points, $custom ) {

        if ( empty($home_points) ) {
            if ( $this->home_team == -1 ) {
                $home_points = 0;
                $away_points = 2;
            }
        }
        if ( empty($away_points) ) {
            if ( $this->away_team == -1 ) {
                $home_points = 2;
                $away_points = 0;
            }
        }

        $score = array( 'home' => $home_points, 'away' => $away_points );

        if ( isset($home_points) && isset($away_points) ) {
            $points = $score;
            $this->getMatchResult( $score['home'], $score['away'] );
            // save original score points
            $this->home_points = $home_points;
            $this->away_points = $away_points;
        } else {
            $home_points = ( '' === $home_points ) ? 'NULL' : $home_points;
            $away_points = ( '' === $away_points ) ? 'NULL' : $away_points;
        }

        $this->custom = array_merge( (array)$this->custom, (array)$custom );
        foreach ( $this->custom AS $key => $value ) {
          $this->{$key} = $value;
        }
    }

    /**
     * determine match result
     *
     * @param int $home_points
     * @param int $away_points
     * @return int
     */
    public function getMatchResult( $home_points, $away_points ) {

        $match = array();
        if ( $home_points > $away_points ) {
            $match['winner'] = $this->home_team;
            $match['loser'] = $this->away_team;
        } elseif ( $this->home_team == -1 ) {
            $match['winner'] = $this->away_team;
            $match['loser'] = 0;
        } elseif ( $this->away_team == -1 ) {
            $match['winner'] = $this->home_team;
            $match['loser'] = 0;
        } elseif ( $home_points < $away_points ) {
            $match['winner'] = $this->away_team;
            $match['loser'] = $this->home_team;
        } elseif ( 'NULL' === $home_points && 'NULL' === $away_points ) {
            $match['winner'] = 0;
            $match['loser'] = 0;
        } else {
            $match['winner'] = -1;
            $match['loser'] = -1;
        }
        $this->winner_id = $match['winner'];
        $this->loser_id = $match['loser'];
        return;
    }

    /**
     * gets rubbers from database
     *
     * @param array $query_args
     * @return array
     */
    public function getRubbers() {
         global $wpdb;

        $sql = "SELECT `group`, `home_player_1`, `home_player_2`, `away_player_1`, `away_player_2`, DATE_FORMAT(`date`, '%%Y-%%m-%%d %%H:%%i') AS date, DATE_FORMAT(`date`, '%%e') AS day, DATE_FORMAT(`date`, '%%c') AS month, DATE_FORMAT(`date`, '%%Y') AS year, DATE_FORMAT(`date`, '%%H') AS `hour`, DATE_FORMAT(`date`, '%%i') AS `minutes`, `match_id`, `home_points`, `away_points`, `winner_id`, `loser_id`, `post_id`, `id`, `custom`, `rubber_number` FROM {$wpdb->leaguemanager_rubbers} WHERE `match_id` = ".$this->id." ORDER BY `date` ASC, `id` ASC";

        $rubbers = wp_cache_get( md5($sql), 'rubbers' );
        if ( !$rubbers ) {
            $rubbers = $wpdb->get_results( $sql );
            wp_cache_set( md5($sql), $rubbers, 'rubbers' );
        }

        $class = '';
        foreach ( $rubbers AS $i => $rubber ) {
            $class = ( 'alternate' == $class ) ? '' : 'alternate';
            $rubber->class = $class;

            $rubber->custom = stripslashes_deep(maybe_unserialize($rubber->custom));
            $rubber = (object)array_merge((array)$rubber, (array)$rubber->custom);

            $rubber->start_time = ( '00:00' == $rubber->hour.":".$rubber->minutes ) ? '' : mysql2date(get_option('time_format'), $rubber->date);
            $rubber->rubber_date = ( substr($rubber->date, 0, 10) == '0000-00-00' ) ? 'N/A' : mysql2date(get_option('date_format'), $rubber->date);

            if ( $rubber->home_points != NULL && $rubber->away_points != NULL ) {
                $rubber->homeScore = $rubber->home_points;
                $rubber->awayScore = $rubber->away_points;
                $rubber->score = sprintf("%s - %s", $rubber->homeScore, $rubber->awayScore);
            } else {
                $rubber->homeScore = "-";
                $rubber->awayScore = "-";
                $rubber->score = sprintf("%s:%s", $rubber->homeScore, $rubber->awayScore);
            }

            $rubber->homePlayer1 = $rubber->home_player_1;
            $rubber->homePlayer2 = $rubber->home_player_2;
            $rubber->awayPlayer1 = $rubber->away_player_1;
            $rubber->awayPlayer2 = $rubber->away_player_2;
            $rubber->rubber_number = $rubber->rubber_number;

            $rubbers[$i] = $rubber;
        }

        return $rubbers;
    }

}

/**
 * get Match object
 *
 * @param int|Match|null Match ID or match object. Defaults to global $match
 * @return Match|null
 */
function get_match( $match = null ) {
    if ( empty( $match ) && isset( $GLOBALS['match'] ) )
        $match = $GLOBALS['match'];

    if ( $match instanceof Match ) {
        $_match = $match;
    } elseif ( is_object( $match ) ) {
        $_match = new Match( $match );
    } else {
        $_match = Match::get_instance( $match );
    }

    if ( ! $_match )
        return null;

    return $_match;
}
?>
