<?php
    /**
     * Send debug code to the Javascript console
     */
    
    
    function debug_to_console($data) {
        if(is_array($data) || is_object($data))
        {
            echo("<script>console.log('PHP: ".json_encode($data)."');</script>");
            error_log('PHP: "'.json_encode($data).'"');
        } else {
            echo("<script>console.log('PHP: ".$data."');</script>");
            error_log('PHP: "'.$data.'"');
        }
    }
    
	/*
	 * Send the output from a backtrace to the error_log
	 * @param string $message Optional message that will be sent the the error_log before the backtrace
	 */
    
	function log_trace($message = '') {
		$trace = debug_backtrace();
		if ($message) {
			error_log($message);
		}
		$caller = array_shift($trace);
		$function_name = $caller['function'];
		error_log(sprintf('%s: Called from %s:%s', $function_name, $caller['file'], $caller['line']));
		foreach ($trace as $entry_id => $entry) {
			$entry['file'] = isset($entry['file']) ? $entry['file'] : '-';
			$entry['line'] = isset($entry['line']) ? $entry['line'] : '-';
			if (empty($entry['class'])) {
				error_log(sprintf('%s %3s. %s() %s:%s', $function_name, $entry_id + 1, $entry['function'], $entry['file'], $entry['line']));
			} else {
				error_log(sprintf('%s %3s. %s->%s() %s:%s', $function_name, $entry_id + 1, $entry['class'], $entry['function'], $entry['file'], $entry['line']));
			}
		}
	}

/**
 * display widget statically
 *
 * @param int $number
 * @param array $instance
 */
    function leaguemanager_display_widget( $number, $instance ) {
        echo "<ul id='leaguemanger-widget-".$instance['league']."' class='leaguemanager_widget'>";
        $widget = new LeagueManagerWidget(true);
        $widget->widget( array('number' => $number), $instance );
        echo "</ul>";
    }


/**
 * display next match box
 *
 * @param int $number
 * @param array $instance
 */
    function leaguemanager_display_next_match_box( $number, $instance ) {
        $widget = new LeagueManagerWidget(true);
        $widget->showNextMatchBox( $number, $instance );
    }

/**
 * display previous match box
 *
 * @param int $number
 * @param array $instance
 */
    function leaguemanager_display_prev_match_box( $number, $instance ) {
        $widget = new LeagueManagerWidget(true);
        $widget->showPrevMatchBox( $number, $instance );
    }

/**
 * get last N matches of given team
 *
 * @param int $team_id
 * @param int $n number of matches
 * @param boolean $logos
 * @return $matches
 */
    function get_last_matches( $team_id, $n = 1, $logos = true ) {
        global $leaguemanager;
        $matches = $leaguemanager->getMatches( array("time" => "prev", "team_id" => $team_id, "limit" => $n, "logos" => $logos) );
        if ( empty($matches) )
            return false;
        
        if ( $n == 1 )
            return $matches[0];
        else
            return $matches;
    }

/**
 * get next N matches of given team
 *
 * @param int $team_id
 * @param int $n number of matches
 * @param boolean $logos
 * @return $matches
 */
    function get_next_matches( $team_id, $n = 1, $logos = true ) {
        global $leaguemanager;
        $matches = $leaguemanager->getMatches( array("time" => "next", "team_id" => $team_id, "limit" => $n, "logos" => $logos) );
        if ( empty($matches) )
            return false;
        
        if ( $n == 1 )
            return $matches[0];
        else
            return $matches;
    }

/**
 * display standings table manually
 *
 * @param int $league_id League ID
 * @param array $args associative array of parameters, see default values (optional)
 * @return void
 */
    function leaguemanager_standings( $league_id, $args = array() ) {
        global $lmShortcodes;
        $defaults = array( 'season' => false, 'template' => 'last5', 'logo' => 'true', 'group' => false, 'home' => 0 );
        $args = array_merge($defaults, $args);
        $args['league_id'] = intval($league_id);
        echo $lmShortcodes->showStandings( $args );
        
        //extract($args, EXTR_SKIP);
        //echo $lmShortcodes->showStandings( array('league_id' => $league_id, 'logo' => $logo, 'season' => $season, 'template' => $template, 'group' => $group, 'home' => $home) );
    }

/**
 * display latest results manually
 *
 * @param int $id_team
 * @param int $limit additional argument (optional)
 * @return $latest_results
 */

    function get_latest_results($id_team, $limit = 5) {
         global $wpdb;
         $latest_results = $wpdb->get_results( $wpdb->prepare("SELECT `id`, `date`, `home_points`, `away_points`, `home_team`, `away_team`, `custom` FROM {$wpdb->leaguemanager_matches} WHERE (home_team = %d OR away_team = %d) AND (DATEDIFF(NOW(), `date`) >= 0) AND (home_points != '' OR away_points != '') ORDER BY date DESC LIMIT %d", $id_team, $id_team, $limit) );

        $i = 0;
        foreach ( $latest_results AS $match ) {
            $latest_results[$i]->custom = $match->custom = maybe_unserialize($match->custom);
            $latest_results[$i]->custom = $match->custom = stripslashes_deep($match->custom);
            $latest_results[$i] = (object)array_merge((array)$match, (array)$match->custom);
            //	unset($matches[$i]->custom);

            $i++;
        }
        return $latest_results;
    }

 /*
function get_next_match($id_team, $limit = 1) {
     global $wpdb;
     $next_results = $wpdb->get_results( $wpdb->prepare("SELECT `id`, `date`, `home_team`, `away_team`
             FROM {$wpdb->leaguemanager_matches}
             WHERE (home_team = %d OR away_team = %d)
             AND (DATEDIFF(NOW(), `date`) <= 0)
             ORDER BY date ASC
             LIMIT %d", $id_team, $id_team, $limit) );

             return $next_results;
}
*/

/**
 * display crosstable table manually
 *
 * @param int $league_id
 * @param array $args associative array of parameters, see default values (optional)
 * @return void
 */
    function leaguemanager_crosstable( $league_id, $args = array() ) {
        global $lmShortcodes;
        $defaults = array('season' => false, 'logo' => 'true', 'group' => '', 'template' => '', 'mode' => '');
        $args = array_merge($defaults, $args);
        $args['league_id'] = intval($league_id);
        echo $lmShortcodes->showCrosstable( $args );
        //extract($args, EXTR_SKIP);
        //echo $lmShortcodes->showCrosstable( array('league_id' => $league_id, 'mode' => $mode, 'template' => $template, 'season' => $season) );
    }

/**
 * display matches table manually
 *
 * @param int $league_id
 * @param array $args associative array of parameters, see default values (optional)
 * @return void
 */
    function leaguemanager_matches( $league_id, $args = array() ) {
        global $lmShortcodes;
        $defaults = array('season' => '', 'template' => '', 'mode' => '', 'limit' => 'true', 'archive' => false, 'match_day' => -1, 'group' => false, 'roster' => false, 'order' => false, 'show_match_day_selection' => '', 'show_team_selection' => '', 'time' => '', 'team' => 0, 'home_only' => 'false', 'match_date' => false, 'dateformat' => '', 'timeformat' => '');
        $args = array_merge($defaults, $args);
        $args['league_id'] = intval($league_id);
        
        //extract($args, EXTR_SKIP);
        echo $lmShortcodes->showMatches($args);
        //echo $lmShortcodes->showMatches( array('league_id' => $league_id, 'limit' => $limit, 'mode' => $mode, 'season' => $season, 'archive' => $archive, 'template' => $template, 'roster' => $roster, 'order' => $order, 'match_day' => $match_day, 'group' => $group) );
    }

/**
 * display one match manually
 *
 * @param int $match_id
 * @param array $args additional arguments as associative array (optional)
 * @return void
 */
function leaguemanager_match( $match_id, $args = array() ) {
	global $lmShortcodes;
	$defaults = array('template' => '');
	$args = array_merge($defaults, $args);
	extract($args, EXTR_SKIP);

	echo $lmShortcodes->showMatch( array('id' => $match_id, 'template' => $template) );
}

/**
 * display team list manually
 *
 * @param int|string $league_id
 * @param array $args additional arguments as associative array (optional)
 * @return void
 */
    function leaguemanager_teams( $league_id, $args = array() ) {
        global $lmShortcodes;
        $defaults = array('season' => false, 'template' => '', 'group' => false);
        $args = array_merge($defaults, $args);
        $args['league_id'] = intval($league_id);
        echo $lmShortcodes->showTeams( $args );
        //extract($args, EXTR_SKIP);

        //echo $lmShortcodes->showTeams( array('league_id' => $league_id, 'season' => $season, 'template' => $template) );
    }

/**
 * display one team manually
 *
 * @param int $team_id
 * @param array $args additional arguments as associative array (optional)
 * @return void
 */
    function leaguemanager_team( $team_id, $args = array() ) {
        global $lmShortcodes;
        $defaults = array('template' => '');
        $args = array_merge($defaults, $args);
        extract($args, EXTR_SKIP);

        echo $lmShortcodes->showTeam( array('id' => intval($team_id), 'template' => $template) );
    }

/**
 * display championship manually
 *
 * @param int $league_id
 * @param array $args additional arguments as associative array (optional)
 * @return void
 */
    function leaguemanager_championship( $league_id, $args = array() ) {
        global $lmShortcodes;
        $defaults = array('template' => '', 'season' => false);
        $args = array_merge($defaults, $args);
        extract($args, EXTR_SKIP);

        echo $lmShortcodes->showChampionship( array('league_id' => intval($league_id), 'template' => $template, 'season' => $season) );
    }

/**
 * display championship manually
 *
 * @param int $league_id
 * @param array $args additional arguments as associative array (optional)
 * @return void
 */
    function leaguemanager_archive( $league_id, $args = array() ) {
        global $lmShortcodes;
        $defaults = array('template' => '');
        $args = array_merge($defaults, $args);
        //extract($args, EXTR_SKIP);
        echo $lmShortcodes->showArchive( $args );
    }

/**
 * display championship manually
 *
 * @param int $league_id
 * @param array $args additional arguments as associative array (optional)
 * @return void
 */
    function leaguemanager_league( $league_id, $args = array() ) {
        global $lmShortcodes;
        $defaults = array('season' => false, 'template' => '');
        $args = array_merge($defaults, $args);
        //extract($args, EXTR_SKIP);

        echo $lmShortcodes->showLeague( $args );
    }

/**
 * helper function to allocate matches and teams of a league to a season and maybe other league
 *
 * @param int $league_id ID of current league
 * @param string $season season to set
 * @param int $new_league_id ID of different league to add teams and matches to (optionl)
 * @param int $old_season (optional) old season if you want to re-allocate teams and matches
 */
    function move_league_to_season( $league_id, $season, $new_league_id = false, $old_season = false ) {
        global $leaguemanager, $wpdb;
        if ( !$new_league_id ) $new_league_id = $league_id;
        
        $team_args = array("league_id" => $league_id);
        if ( $old_season ) $team_args["season"] = $old_season;
        
        $match_args = $team_args;
        
        if ( $teams = $leaguemanager->getTeams($team_args) ) {
            foreach ( $teams AS $team ) {
                $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->leaguemanager_table} SET `season` = '%d', `league_id` = '%d' WHERE `id` = '%d'", $season, $new_league_id, $team->id ) );
            }
        }
        if ( $matches = $leaguemanager->getMatches($match_args) ) {
            foreach ( $matches AS $match ) {
                $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->leaguemanager_matches} SET `season` = '%d', `league_id` = '%d' WHERE `id` = '%d'", $season, $new_league_id, $match->id ) );
            }
        }
    }

/**
 * display player list manually
 *
 * @param int|string $league_id
 * @param array $args additional arguments as associative array (optional)
 * @return void
 */
    function leaguemanager_players( $league_id, $args = array() ) {
        global $lmShortcodes;
        $defaults = array('season' => false, 'template' => '', 'group' => false);
        $args = array_merge($defaults, $args);
        $args['league_id'] = intval($league_id);
        echo $lmShortcodes->showPlayers( $args );
        //extract($args, EXTR_SKIP);

        //echo $lmShortcodes->showTeams( array('league_id' => $league_id, 'season' => $season, 'template' => $template) );
    }

    function sortArrayDesc($data, $field) {
        if(!is_array($field)) $field = array($field);
        usort($data, function($b, $a) use($field) {
            $retval = 0;
            foreach($field as $fieldname) {
                if($retval == 0) $retval = strnatcmp($a[$fieldname],$b[$fieldname]);
            }
            return $retval;
            });
        return $data;
    }
    
    function sortArray($data, $field) {
        if(!is_array($field)) $field = array($field);
        usort($data, function($a, $b) use($field) {
            $retval = 0;
            foreach($field as $fieldname) {
                if($retval == 0) $retval = strnatcmp($a[$fieldname],$b[$fieldname]);
            }
            return $retval;
            });
        return $data;
    }

    function array_msort($array, $cols) {
        $colarr = array();
        foreach ($cols as $col => $order) {
            $colarr[$col] = array();
            foreach ($array as $k => $row) {
                $colarr[$col]['_'.$k] = strtolower($row[$col]);
            }
        }

        $eval = 'array_multisort(';

        foreach ($cols as $col => $order) {
            $eval .= '$colarr[\''.$col.'\'],'.$order.',';
        }

        $eval = substr($eval,0,-1).');';
        eval($eval);
        $ret = array();
        foreach ($colarr as $col => $arr) {
            foreach ($arr as $k => $v) {
                $k = substr($k,1);
                if (!isset($ret[$k])) $ret[$k] = $array[$k];
                $ret[$k][$col] = $array[$k][$col];
            }
        }
        return $ret;
    }
    
    function create_new_url_querystring() {
        add_rewrite_rule(
                       '^leagues/(.+?)-(.+?)-([0-9]{1})/?$',
                       'index.php?pagename=leagues%2F$matches[1]-leagues%2F$matches[1]-$matches[2]&league_id=$matches[1]%20$matches[2]%20$matches[3]',
                       'top'
                       );
        add_rewrite_rule(
                       '^leagues/(.+?)-(.+?)-([0-9]{1})/([0-9]{4})?$',
                       'index.php?pagename=leagues%2F$matches[1]-leagues%2F$matches[1]-$matches[2]&league_id=$matches[1]%20$matches[2]%20$matches[3]&season=$matches[4]',
                       'top'
                       );

        add_rewrite_tag('%league_id%','([^/]*)');
        add_rewrite_tag('%season%','([0-9]{4})');
        add_rewrite_tag('%match_day%','([0-9]{1,2})');
        add_rewrite_tag('%team%','(.+?)');
    }
    add_action('init', 'create_new_url_querystring');
    function leaguemanager_calendar_download() {
        if ( isset( $_GET["team_id"] )  && isset( $_GET["league_id"] ) && isset( $_GET["season"] ) && isset( $_GET["leaguemanager_export"] ) ) {
            global $leaguemanager;
            define('DATE_ICAL', 'Ymd\THis');
            $league_id = $_GET["league_id"];
            $season = $_GET["season"];
            $team_id = $_GET["team_id"];
            $team = $leaguemanager->getTeamDtls($team_id, $league_id);
            $teamname = $team->title;
            $matches = $leaguemanager->getMatches( array("league_id" => $league_id, "season" => $season, "team_id" => $team_id) );
            $leaguemanager->league = $leaguemanager->getLeague($league_id);
            $filename = sanitize_title($leaguemanager->league->title)."-".$teamname.".ics";
            $contents = "BEGIN:VCALENDAR\n";
            $contents .= "VERSION:2.0\n";
            $contents .= "PRODID:-//TENNIS CALENDAR//NONSGML Events //EN\n";
            $contents .= "CALSCALE:GREGORIAN\n";
            $contents .= "DTSTAMP:".date('Ymd\THis')."\n";
            foreach ( $matches AS $match ) {
                $contents .= "BEGIN:VEVENT\n";
                $contents .= "UID:".$match->id."\n";
                $contents .= "DTSTAMP:".mysql2date('Ymd\THis', $match->date)."\n";
                $contents .= "DTSTART:".mysql2date('Ymd\THis', $match->date)."\n";
                $contents .= "DTEND:".date('Ymd\THis', strtotime('+2 hours',strtotime($match->date)))."\n";
                $contents .= "SUMMARY:".$leaguemanager->getMatchTitle($match->id)."\n";
                $contents .= "LOCATION:".$match->location."\n";
                $contents .= "END:VEVENT\n";
            }
            $contents .= "END:VCALENDAR";
            header('Content-Type: text/calendar');
            header('Content-Disposition: attachment; filename="'.$filename.'"');
            echo $contents;
            exit();
        }
    }
    add_action('init', 'leaguemanager_calendar_download');

?>
